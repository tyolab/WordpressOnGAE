<?php
class AffinityEcommerceUtils {
	const SERVICE_MAX_TRIALS = 7;
	const SERVICE_SECONDS_BEFORE_TRYING_AGAIN = 2;
	const SERVICE_TIMEOUT = 600; //in seconds
	
	public static function getCallbackUrl() { 
		return admin_url('admin-ajax.php');
	}
	
	public static function getStoreUrl() { 
		return get_site_url();
	}
	
	public static function getAdminEmail() {
		return get_option('admin_email');
	}
	
	public static function isHttpsBeingUsed() {
		return is_ssl();
	}
	
	public static function redirectToAffinityAuthenticationPage() {
		$authUrl = admin_url('admin.php?page=ebay-sync-settings');
		wp_redirect($authUrl);
		print "<script>location.href = 'admin.php?page=ebay-sync-settings';</script>";
		exit();
	}
	
	public static function generateSecureRandomString() {
		if(function_exists('openssl_random_pseudo_bytes')) {
			return bin2hex(openssl_random_pseudo_bytes(16));
		}
		
		if (function_exists('random_bytes')) {
			$return = bin2hex(random_bytes(16));
		} else {
			set_time_limit(600);
			require_once(__DIR__ . "/../service/phpseclib/Crypt/Random.php");
			$return = bin2hex(crypt_random_string(16));
		}
		
		return $return;
	}
	
	public static function callMethodWithJsonContent($url, $arrParameters, $extraArgs = array()) {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		require_once(__DIR__ . "/../model/AffinityGlobalOptions.php");
		require_once(__DIR__ . "/../service/AffinityEnc.php");
		
		$method = $extraArgs['method'];
		
		if (empty($arrParameters)) {
			$arrParameters = array();
		}
		
		if (!class_exists('WP_Http')) {
			require_once(ABSPATH . WPINC. '/class-http.php');
		}
		
		$token = AffinityEnc::getToken();
		
		$defaultOpts = array(
			'method' => 'GET',
			'httpversion'  => "1.0",
			'headers'  => array(
				"Content-type" => "application/json",
				"Accept" => "application/json",
				"Authorization" => "Bearer " . $token,
			),
			'timeout' => self::SERVICE_TIMEOUT,
			'sslverify' => false
//			'sslcertificates' => __DIR__ . "/ebay.crt" @Todo, make sure production services use ssl certificates
		);
		
		$partnerId = AffinityGlobalOptions::getInstallationId();
		$puuid = get_option('ebayaffinity_puuid');
		
		if (!empty($partnerId)) {
			$defaultOpts['headers']['X-EBAY-AFFINITY-PARTNER-ID'] = $partnerId;
		}
		
		if (!empty($partnerId)) {
			$defaultOpts['headers']['X-EBAY-AFFINITY-PUUID'] = $puuid;
		}
		
		$options = array_merge($defaultOpts, $extraArgs);
		$finalUrl = $url;
		
		if ($method === "DELETE" && count($arrParameters) > 0) {
			$method = 'POST';
		}
		
		switch($method) {
			case "POST":
			case "PUT":
				unset($arrParameters['access_token']);
				$options['body'] = json_encode($arrParameters);
				break;
			default:
				$urlParameters = http_build_query($arrParameters);
				if(!empty($urlParameters)) {
					$finalUrl = $url . '?' . $urlParameters;
				}
		}
		
		$optionsWithoutHeaders = $options;
		unset($optionsWithoutHeaders['headers']);
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Sending HTTP Request", "URL: $finalUrl<br>Options: " . print_r($optionsWithoutHeaders, true));
		
		$requestCount = 1;
		$result = wp_remote_request($finalUrl, $options);
		
		while($requestCount < self::SERVICE_MAX_TRIALS && is_wp_error($result)) {
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "HTTP Call Failed - Trying again $requestCount", "No details");

			sleep(self::SERVICE_SECONDS_BEFORE_TRYING_AGAIN);
			$requestCount += 1;

			$result = wp_remote_request($finalUrl, $options);
		}
		
		if(is_wp_error($result)) {
			$sysError_expire = get_option('affinity_connerror_expire');
			if (empty($sysError_expire) || $sysError_expire < time()) {
				update_option('affinity_connerror_expire', time() + 3600);
				$backend = get_option('ebayaffinity_backend');
				$parr = parse_url($backend);
				
				$b = array();
				$c = array();
				
				exec('traceroute '.escapeshellarg($parr['host']), $b, $c);
				
				foreach($b as $k=>$v) {
					$b[$k] = '<pre style="margin: 0; padding: 0; font-size: 12px;">'.htmlspecialchars($v).'</pre>';
				}
				
				$b = implode("", $b);
				self::sendNotificationMail(self::getAdminEmail(), 'Connection to eBay Failed', "After trying to connect " . self::SERVICE_MAX_TRIALS . " times, we couldn't succeed and gave up. Please check your connection and WordPress site connectivity to Internet.", "After trying to connect " . self::SERVICE_MAX_TRIALS . " times, we couldn't succeed and gave up. Please check your connection and WordPress site connectivity to Internet.<br><br>Traceroute (".htmlspecialchars($parr['host']).") return code: ".$c."<br>".$b);
			}
			
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Connection to eBay Failed", "After trying to connect " . self::SERVICE_MAX_TRIALS . " times, we couldn't succeed and gave up. Please check your connection and WordPress site connectivity to Internet.");

			return false;
		}
		
		$return = array(
			'headers' => $result["headers"],
			'httpResponseCode' => $result["response"]["code"],
			'arrResult' => json_decode($result["body"], true)
		);
		
		if (is_array($return['headers'])) {
			$arr = $return['headers'];
		} else {
			$arr = $return['headers']->getAll();
		}
		
		if (!empty($arr['rlogid'])) {
			$return['rlogid'] = $arr['rlogid'];
		}
		unset($return['headers']);
		$returnWithoutHeaders = $return;
		unset($returnWithoutHeaders['headers']);
		
		if (strlen('Return: ' . print_r($returnWithoutHeaders, true)) <= 65535) {
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Json Post Returning Result", "Return: " . print_r($returnWithoutHeaders, true));
		}
		return $return;
	}
	
	public static function sendNotificationMail($to, $subject, $messageContent, $debugMessageContent='', $notifyUser = true) {
		if (empty($debugMessageContent)) {
			$debugMessageContent = $messageContent;
		}
		$messageWithStyledParagraphs = self::nl2p($messageContent, 'style="margin-top: 20px; margin-bottom: 20px; font-family: \'Helvetica Neue\', \'Open sans\', \'sans-serif\', \'Helvetica\'; color: #868686; font-size: 15px; line-height: 27px;"');
		$debugMessageWithStyledParagraphs = self::nl2p($debugMessageContent, 'style="margin-top: 20px; margin-bottom: 20px; font-family: \'Helvetica Neue\', \'Open sans\', \'sans-serif\', \'Helvetica\'; color: #868686; font-size: 15px; line-height: 27px;"');
		
		$htmlTemplate = file_get_contents(__DIR__ . "/../includes/email-template.html");
		$htmlRenderedTemplate = str_replace("{{content}}", $messageWithStyledParagraphs, $htmlTemplate);
		$debugHtmlRenderedTemplate = str_replace("{{content}}", $debugMessageWithStyledParagraphs, $htmlTemplate);

		if($notifyUser) {
			wp_mail( $to, $subject, $htmlRenderedTemplate, array('Content-Type: text/html; charset=UTF-8') );
		}	
		wp_mail( 'DL-eBay-AU-Sync-Support@ebay.com', $subject.' ('.$to.')', $debugHtmlRenderedTemplate, array('Content-Type: text/html; charset=UTF-8') );
	}
	
	private static function nl2p($string, $paragraphExtraAttributes) {
		$return = '';

		foreach(explode("\n", $string) as $line) {
			if (trim($line)) {
				$return .= "<p $paragraphExtraAttributes>$line</p>";
			}
		}

		return $return;
	}
}

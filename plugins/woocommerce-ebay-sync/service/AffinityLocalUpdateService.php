<?php
class AffinityLocalUpdateService {
	
	private static function validateRequestSecurity($token) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
		require_once(__DIR__ . "/../model/AffinityLog.php");
		require_once(__DIR__ . "/../model/AffinityPushToken.php");
		
		$arrReturn = array();
		$arrReturn['warnings'] = array();
		$arrReturn['errors'] = array();
		
		$objToken = AffinityPushToken::getAndUse($token);
		if(empty($objToken)) {
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Affinity -> WooBay Security Error", "Provided Token is invalid!");
			self::returnUnauthorized();
		}
		
		return $arrReturn;
	}
	
	private static function returnUnauthorized() {
		@header('HTTP/1.1 401 Unauthorized', true, 401);
		exit();
	}
	
	public static function cron($token) {
		$arrSecurityWarningsAndErrors = self::validateRequestSecurity($token);
		if(isset($arrSecurityWarningsAndErrors['errors']) && count($arrSecurityWarningsAndErrors['errors']) > 0) {
			return array(
					'arrWarnings' => $arrSecurityWarningsAndErrors['warnings'],
					'arrErrors' => $arrSecurityWarningsAndErrors['errors']
			);
		}
		wp_clear_scheduled_hook('wp_affinity_cron');
		wp_schedule_event(time(), get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron');
		spawn_cron();
		return array('working'=>'working');
	}
	
	public static function cronInv($token) {
		$arrSecurityWarningsAndErrors = self::validateRequestSecurity($token);
		if(isset($arrSecurityWarningsAndErrors['errors']) && count($arrSecurityWarningsAndErrors['errors']) > 0) {
			return array(
					'arrWarnings' => $arrSecurityWarningsAndErrors['warnings'],
					'arrErrors' => $arrSecurityWarningsAndErrors['errors']
			);
		}
		wp_clear_scheduled_hook('wp_affinity_cron_inv');
		wp_schedule_event(time(), get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron_inv');
		spawn_cron();
		return array('working'=>'working');
	}
	
	public static function cronSyncAll($token) {
		$arrSecurityWarningsAndErrors = self::validateRequestSecurity($token);
		if(isset($arrSecurityWarningsAndErrors['errors']) && count($arrSecurityWarningsAndErrors['errors']) > 0) {
			return array(
					'arrWarnings' => $arrSecurityWarningsAndErrors['warnings'],
					'arrErrors' => $arrSecurityWarningsAndErrors['errors']
			);
		}
		wp_clear_scheduled_hook('wp_affinity_cron_sync_all');
		wp_schedule_single_event(time(), 'wp_affinity_cron_sync_all');
		spawn_cron();
		return array('working'=>'working');
	}
	
	public static function orderNotificationReceived() {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		require_once(__DIR__ . "/AffinityBackendService.php");
		$arrOrdersPayload = AffinityBackendService::getNowOrderDetails();
		return self::orderReceivedFromEbay($arrOrdersPayload);
	}
	
	public static function orderNotificationReceivedToken($token) {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Received new order notification", "-");
	
		$arrSecurityWarningsAndErrors = self::validateRequestSecurity($token);
		if(isset($arrSecurityWarningsAndErrors['errors']) && count($arrSecurityWarningsAndErrors['errors']) > 0) {
			return array(
					'itemId' => null,
					'arrWarnings' => $arrSecurityWarningsAndErrors['warnings'],
					'arrErrors' => $arrSecurityWarningsAndErrors['errors']
			);
		}
	
		require_once(__DIR__ . "/AffinityBackendService.php");
		$arrOrdersPayload = AffinityBackendService::getNowOrderDetails();
		return self::orderReceivedFromEbay($arrOrdersPayload);
	}
	
	public static function orderReceivedFromEbay($arrOrdersPayload) {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Processing order payload", print_r($arrOrdersPayload, true));

		try {
			require_once(__DIR__ . "/../model/AffinityOrder.php");
			$arrOrders = AffinityOrder::orderReceivedFromEbay($arrOrdersPayload);
			
			$return = array();
			foreach($arrOrders as $objAffinityOrder) {
				$return[] = array(
					'itemId' => $objAffinityOrder->ebayItemId,
					'arrWarnings' => $objAffinityOrder->arrWarnings,
					'arrErrors' => $objAffinityOrder->arrErrors
				);
			}
			
			return $return;
		} 
		catch(Exception $error) {
			return array(
				'itemId' => $arrOrdersPayload['itemId'],
				'arrWarnings' => array(),
				'arrErrors' => array($error->getMessage())
			);
		}
	}
}

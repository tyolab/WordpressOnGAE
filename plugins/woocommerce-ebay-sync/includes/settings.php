<?php 
if (!empty($_GET['agree'])) {
	update_option('ebayaffinity_agree', '1');
}

require_once(__DIR__ . "/../service/AffinityBackendService.php");
$hasWoobayGotPushToken = AffinityBackendService::hasWoobayGotPushToken();
$hasLastLoginFailed = AffinityBackendService::hasLastLoginFailed();
$hasLastCommandFailed = AffinityBackendService::hasLastCommandAuthenticationFailed();

if ($hasLastCommandFailed || $hasLastLoginFailed || !$hasWoobayGotPushToken) {
	$disabled = 'disabled';
} else {
	$disabled = false;
}

require_once(__DIR__ . "/support.php");
if (empty($_GET['pnum'])) {
	$_GET['pnum'] = null;
}

if ($_GET['pnum'] == 3) {
?>
<form action="admin.php" autocomplete="off" id="ebayaffinity-inv-filter-form-2">
<?php 
	require_once(__DIR__.'/filter.php');
?>
</form>
<?php 
}
?>
<div class="ebayaffinity-header">
	<span class="ebayaffinity-header-vert-mobile">Settings</span>
<?php 
if ($_GET['pnum'] == 3) {
?>
<a <?php print $disabled?'style="display:none;"':''?> class="ebayaffinity-bt-new-shiprule" href="#">
	<div class="ebayaffinity-bt-new-rule-icon"> + </div>
	<span class="ebayaffinity-add-rule-label ebayaffinity-not-mobile">Create new shipping rule</span>
</a>
<?php 
}
?>
</div>
<div class="ebayaffinity-settingspages">
	<a href="admin.php?page=ebay-sync-settings" class="ebayaffinity-settingspage <?php print empty($_GET['pnum'])?'ebayaffinity-settingspageon':''?>">Account settings</a>
	<a href="admin.php?page=ebay-sync-settings&amp;pnum=2" class="ebayaffinity-settingspage <?php print $_GET['pnum']==2?'ebayaffinity-settingspageon':''?>">Customise store</a>
	<a href="admin.php?page=ebay-sync-settings&amp;pnum=3" class="ebayaffinity-settingspage <?php print $_GET['pnum']==3?'ebayaffinity-settingspageon':''?>">Shipping</a>
	<a href="admin.php?page=ebay-sync-settings&amp;pnum=4" class="ebayaffinity-settingspage <?php print $_GET['pnum']==4?'ebayaffinity-settingspageon':''?>">Returns</a>
	<a href="admin.php?page=ebay-sync-settings&amp;pnum=5" class="ebayaffinity-settingspage <?php print $_GET['pnum']==5?'ebayaffinity-settingspageon':''?>">Store admin</a>
	
</div>
<div class="ebayaffinity-inv-block">
	<form enctype="multipart/form-data" id="ebay-link-settings-form<?php print empty($_GET['pnum'])?'':$_GET['pnum']?>" action="admin.php?page=ebay-sync-settings<?php print empty($_GET['pnum'])?'':('&amp;pnum='.$_GET['pnum'])?>" method="post" autocomplete="off">

<?php 
if (empty($_GET['pnum'])) {
	$phperrors = array();
	
	global $wp_version;
	if (version_compare($wp_version, '4.4', '<')) {
		$phperrors[] = 'WordPress 4.4 or above is required.';
	}
	
	if (defined('WOOCOMMERCE_VERSION') && version_compare(WOOCOMMERCE_VERSION, '2.5', '<')) {
		$phperrors[] = 'WooCommerce 2.5 or above is required.';
	}
	
	if (!function_exists('json_encode')) {
		$phperrors[] = 'JSON extension for PHP is missing. This is required.';
	}
	if (!function_exists('openssl_pkey_new')) {
		$phperrors[] = 'OpenSSL extension for PHP is missing. This is optional, but highly recommended.';
	}
	if ((!function_exists('gmp_add')) && (!function_exists('bcadd'))) {
		$phperrors[] = 'GMP and BC Math extensions for PHP are missing. These are optional, but highly recommended.';
	}
	if (!function_exists('gd_info')) {
		$phperrors[] = 'GD extension for PHP is missing. This is optional, but highly recommended.';
	}
	
	if (!class_exists('WP_Http')) {
		require_once(ABSPATH . WPINC. '/class-http.php');
	}
	
	$options = array(
			'method' => 'GET',
			'httpversion'  => "1.0",
			'headers'  => array(
					"Content-type" => "application/json",
					"Accept" => "application/json",
			),
			'timeout' => '600',
			'sslverify' => false
	);

	$result = wp_remote_request(get_option('ebayaffinity_backend'), $options);
	
	if(is_wp_error($result)) {
		$phperrors[] = 'eBay services not contactable.';
	} else if (empty($result) || empty($result['response']) || empty($result['response']['code']) || $result['response']['code'] != 403) {
		$phperrors[] = 'eBay services not contactable.';
	}
	
	if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
		$phperrors[] = 'WordPress cron is disabled. This must be enabled.';
	}
	
	if (!empty($phperrors)) {
?>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
	<div>Configuration errors</div>
<?php 
foreach ($phperrors as $phperror) {
?>
	<p><?php print esc_html($phperror)?></p>
<?php 
}
?>
</div>
<?php
	}
	
	require_once(__DIR__ . "/../service/AffinityEnc.php");
	
	$pub = get_option('ebayaffinity_pubkey');
	$priv = get_option('ebayaffinity_privkey');
	
	if (empty($pub) || empty($priv)) {
		$keys = AffinityEnc::makekeys();
		add_option('ebayaffinity_pubkey', $keys[1]);
		add_option('ebayaffinity_privkey', $keys[0]);
		update_option('ebayaffinity_pubkey', $keys[1]);
		update_option('ebayaffinity_privkey', $keys[0]);
		
		$pub = get_option('ebayaffinity_pubkey');
		$priv = get_option('ebayaffinity_privkey');
	}
	
	$errors = array();
	if (empty($pub) || empty($priv)) {
?>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
	<div>Configuration errors</div>
	<p>Could not generate keypair.</p>
</div>
<?php
	}
	$a = json_decode(get_option('ebayaffinity_pricing_errors'), true);
	$a = is_array($a) ? $a : array();
	foreach ($a as $el) {
		if (!empty($el)) {
			$errors []= '<p>'.esc_html($el).'</p>';
		}
	}
	if (count($errors) > 0) {
?>
		<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
			<div>Payment Policy errors</div>
<?php 
		print implode('', array_unique($errors));
?>
		</div>
<?php 
	}
	$our_paypal = get_option('ebayaffinity_paypal');
	$our_user = get_option('ebayaffinity_ebayuserid');
	$our_puuid = get_option('ebayaffinity_puuid');
	$paypal = get_option('woocommerce_paypal_settings');
	$setup1 = get_option('ebayaffinity_setup1');
	$hasSetupStep1BeenCompleted = !empty($setup1);
	$expiry = get_option('affinityTokenExpiry');
	if (!empty($expiry)) {
		$expiry = $expiry / 1000;
		if ($expiry < time()) {
			$expiry = '<span style="color: red;">Your API token has expired. Please connect to eBay again.</span>';
		} else {
			$expiry = date('d F Y', $expiry + (get_option('gmt_offset') * HOUR_IN_SECONDS));
		}
	}
?>
<script type="text/javascript">
<?php
$pubkey = get_option('ebayaffinity_pubkey');
$pubkey = str_replace("\n", '', $pubkey);
$pubkey = str_replace('-----BEGIN PUBLIC KEY-----', '', $pubkey);
$pubkey = str_replace('-----END PUBLIC KEY-----', '', $pubkey);
?>
	var ebayaffinity_authurl = <?php print json_encode(get_option('ebayaffinity_auth').urlencode(','.$pubkey))?>;
	var ebayaffinity_ouruser = <?php print json_encode(get_option('ebayaffinity_ebayuserid'))?>;
	var ebayaffinity_ourpuuid = <?php print json_encode(get_option('ebayaffinity_puuid'))?>;
</script>
<?php 
	if (empty($our_paypal) || empty($our_user) || empty($hasWoobayGotPushToken)) {
?>
		<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-noimg">
			<div>eBay Sync setup incomplete</div>
			<p>To complete your eBay store setup you need to enter your eBay and Paypal User ID below.</p>
		</div>
<?php 
	}
?>
		<input type="hidden" name="save" value="1">
		<input type="hidden" name="ebayaffinity_token" id="ebayaffinity_token">
		<input type="hidden" name="ebayaffinity_ebayuserid" id="ebayaffinity_ebayuserid" value="<?php print esc_html($our_user)?>">
		<input type="hidden" name="ebayaffinity_puuid" id="ebayaffinity_puuid" value="<?php print esc_html($our_puuid)?>">
		<div class="ebayaffinity-settingsblock ebayaffinity-settingsblock-entry">
			<div class="ebayaffinity-settingsheader">Account details</div>
<?php 
if (empty($our_user)) {
?>	
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-settingsubbox">
						<div>
							Connect eBay Sync to your eBay account
						</div>
						<a href="#" class="ebayaffinity-settingssave" onclick="ebayaffinity_auth(); return false;">Connect</a>
					</div>
					<div class="ebayaffinity-settingsubbox">
						<div>
							Don't have an eBay account?
						</div>
						<a target="_blank" href="https://scgi.ebay.com.au/ws/eBayISAPI.dll?RegisterEnterInfo&amp;onepagereg=1&amp;company=&amp;aolmasterid=&amp;personalId=&amp;aolencusername=&amp;itemid=&amp;partneruserid=0&amp;acceptq4=0&amp;city=&amp;provinceId=&amp;acceptq3=0&amp;myanswer=&amp;acceptq2=0&amp;acceptq1=0&amp;birthdate3=&amp;birthdate2=&amp;birthdate1=&amp;Last" class="ebayaffinity-settingssave ebayaffinity-settingssavenoac">Create an account</a>
					</div>
				</div>
			</div>
<?php 
} else {
?>
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
<?php 
if ($hasLastCommandFailed || $hasLastLoginFailed || !$hasWoobayGotPushToken) {
?>
					<div class="ebayaffinity-settingsubbox ebayaffinity-settingsubboxalone ebayaffinity-settingsubboxalonefail">
						<div class="ebayaffinity-redtick">&times;</div>
						<div>Error! eBay Sync is not connected to your eBay store!</div>
					</div>
<?php 
} else {
?>
					<div class="ebayaffinity-settingsubbox ebayaffinity-settingsubboxalone ebayaffinity-settingsubboxalonesuccess">
						<div class="ebayaffinity-greentick">&#x2713;</div>
						<div>Success! eBay Sync is connected to your eBay store!</div>
					</div>
<?php 
}
?>
				</div>
			</div>
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-settingsubbox ebayaffinity-settingsubboxsmall">
						<strong>eBay User ID: </strong> <?php print esc_html($our_user)?>
					</div>
					<div class="ebayaffinity-settingsubbox ebayaffinity-settingsubboxsmall">
					
<?php 
if ((!empty($paypal['email'])) && (!empty($our_paypal)) && ($paypal['email'] == $our_paypal)) {
?>
	<strong>PayPal User ID: </strong> <span class="ebayaffinity-paypalremove"><?php print str_replace('@', '@<wbr>', esc_html($paypal['email'])) ?></span> <a href="#" id="ebayaffinity-paypalremove">edit</a> <input id="ebayaffinity_paypal_input" type="hidden" name="ebayaffinity_paypal" value="<?php print esc_html($paypal['email']) ?>">  <a href="#" style="display: none;" id="ebayaffinity-paypalsave">save</a>
<?php 
} else {
?>
	<strong>PayPal User ID: </strong> <input type="text" id="ebayaffinity_paypal_input" name="ebayaffinity_paypal" value="<?php print esc_html($our_paypal) ?>"> <a href="#" id="ebayaffinity-paypalsave">save</a>
<?php 
}
?>
						<div class="ebayaffinity-questiondiv ebayaffinity-questiondivinline">
							<div class="ebayaffinity-question"><span class="info">Your PayPal account must be identical to the one registered on eBay</span>?</div>
						</div>
					
					</div>
				</div>
			</div>
			<div class="ebayaffinity-settingsreconnect">
				<div class="ebayaffinity-settingsiinfo">i</div>
				<div class="ebayaffinity-settingsiright">
					<a href="#" class="ebayaffinity-settingssave ebayaffinity-settingssavenoac" onclick="ebayaffinity_auth(); return false;">Reconnect to your eBay store</a>
				</div>
				<div class="ebayaffinity-settingsicentre">
					You will need to reconnect to your eBay store by: <strong><?php print $expiry?></strong>
					<div class="ebayaffinity-tooltip ebayaffinity-tooltipr" data-tooltip="Your eBay token has a limited lifetime. Reconnecting will refresh your token">
						<a href="#" onclick="return false;">Why do I need to reconnect?</a>
					</div>
				</div>
				<div style="height: 0; overflow: hidden; clear: both;">&nbsp;</div>
			</div>
<?php 
}
?>
		</div>

<?php 
} else if ($_GET['pnum'] == 2) {
	global $imgerror;
	
	if (((!empty($_SERVER['CONTENT_LENGTH'])) && empty($_FILES) && empty($_POST)) || 
			(isset($_FILES['ebayaffinity_logofile']) && (!empty($_FILES['ebayaffinity_logofile']['error'])) && $_FILES['ebayaffinity_logofile']['error'] == 1)) {
				
		function ebayaffinity_return_bytes($val) {
			$val = trim($val);
			$last = strtolower($val[strlen($val)-1]);
			switch($last) {
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}
		
			return $val;
		}
		
		$pms = ebayaffinity_return_bytes(ini_get('post_max_size')); 
		$umf = ebayaffinity_return_bytes(ini_get('upload_max_filesize'));
		$maxsize = $pms;
		if ($umf < $maxsize) {
			$maxsize = $umf;
		}
		$maxsize /= 1024;
		$maxsize /= 1024;
		$imgerror = "Please ensure the file size is no larger than ".$maxsize."MB.";
	}
	
	if (!empty($imgerror)) {
?>
		<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
			<div>Invalid logo image</div>
<?php 
		print esc_html($imgerror);
?>
		</div>
<?php 
	}

	$dir = wp_upload_dir();
	$logo = get_option('ebayaffinity_logo');
	if (!empty($logo)) {
?>
		<div class="ebayaffinity-settingsblock-outer">
			<div class="ebayaffinity-settingsblock-outer-cell">
<?php 
	}
?>
				<div class="ebayaffinity-settingsblock">
					<div class="ebayaffinity-settingsheader">Your store logo <div class="ebayaffinity-settingsheadernote ebayaffinity-settingsheadernote-big">Accepts JPEG, PNG, or GIF.</div></div>
					<div class="ebayaffinity-settingset">
						<div class="ebayaffinity-setting">
							<div class="ebayaffinity-settinglogo">			
<?php 
	if (!empty($logo)) {
?>
								<img src="<?php print esc_html($dir['baseurl'].'/'.$logo);?>" alt="Store logo" id="ebayaffinity_moblogo">
<?php 
	}
?>
								<label for="ebayaffinity_logourl" class="ebayaffinity-settinglogotxt">
									Enter the URL of your logo from your website (right click over your logo image and select &lsquo;copy image URL&rsquo;)
								</label>
								<input type="text" name="ebayaffinity_logourl" id="ebayaffinity_logourl">
								<div class="ebayaffinity-settinglogotxt">
									Add media file from your computer
								</div>
								<label for="ebayaffinity_logofile" id="ebayaffinity_logofilelabel">
									Select file from your computer
								</label>
								<input type="file" name="ebayaffinity_logofile" id="ebayaffinity_logofile">
							</div>
						</div>
					</div>
				</div>
<?php 
	if (!empty($logo)) {
?>
			</div>
			<div class="ebayaffinity-settingsblock-outer-cell">
				<div class="ebayaffinity-settingsblock-outer-cell-img" style="background-image: none;">
					<div id="ebayaffinity_imageid" style="margin: 100px auto 0; background-position: center center; background-repeat: no-repeat; width: 250px; height: 250px; background-size: contain; background-image: url(<?php print esc_html($dir['baseurl'].'/'.$logo.'?r='.mt_rand());?>)">&nbsp;</div>
				</div>
			</div>
		</div>
<?php 
	}
	$customtemplate = get_option('ebayaffinity_customtemplate');
	if (empty($customtemplate)) {
		$customtemplate = file_get_contents(__DIR__.'/../assets/product.html');
		$usecustomtemplate = false;
	} else {
		$usecustomtemplate = true;
	}
	
	$useshort = get_option('ebayaffinity_useshort');
?>
		<div class="ebayaffinity-settingsblock">
			<div class="ebayaffinity-settingsheader">Product template</div>
			
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv" style="width: 220px;">
						<label class="ebayaffinity-label" for="ebayaffinity_useshort">Always use short description</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="checkbox" id="ebayaffinity_useshort" name="ebayaffinity_useshort" value="1" <?php print empty($useshort)?'':'checked'?>>
					</div>
					<div class="ebayaffinity-questiondiv">
					</div>
				</div>
			</div>
			
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv" style="width: 220px;">
						<label class="ebayaffinity-label" for="ebayaffinity_usecustomtemplate">Use custom template</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="checkbox" id="ebayaffinity_usecustomtemplate" name="ebayaffinity_usecustomtemplate" value="1" <?php print empty($usecustomtemplate)?'':'checked'?>>
					</div>
					<div class="ebayaffinity-questiondiv">
					</div>
				</div>
				<div class="ebayaffinity-setting" <?php print empty($usecustomtemplate)?'style="display: none;"':''?>>
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_customtemplate">Template</label>
					</div>
					<div class="ebayaffinity-valuediv">
						Variable tags: <a href="#" class="ebayaffinity_customtemplatevars" data-dat="[[STORELOGO]]">Store Logo</a>
						/
						<a href="#" class="ebayaffinity_customtemplatevars" data-dat="[[TITLE]]">Title</a>
						/
						<a href="#" class="ebayaffinity_customtemplatevars" data-dat="[[IMG]]">Image</a>
						/
						<a href="#" class="ebayaffinity_customtemplatevars" data-dat="[[PRICE]]">Price</a>
						/
						<a href="#" class="ebayaffinity_customtemplatevars" data-dat="[[DESC]]">Description</a>
						<br>
						<textarea id="ebayaffinity_customtemplate" name="ebayaffinity_customtemplate"><?php print htmlspecialchars($customtemplate)?></textarea>
					</div>
					<div class="ebayaffinity-questiondiv">
					</div>
				</div>
			</div>
		</div>
<?php

} else if ($_GET['pnum'] == 3) {
	require_once(__DIR__.'/filter.php');
	require_once(__DIR__.'/../service/AffinityBackendService.php');

	$profile = AffinityBackendService::getProfile();
    $shippingDiscountProfiles = AffinityBackendService::getShippingDiscountProfiles();
	$isClickAndCollectEnabled = !empty($profile['data']['isClickAndCollectEnabled']);
	$isDomesticaRateTableEnabled = !empty($profile['data']['isDomesticaRateTableEnabled']);
?>
<script type="text/javascript">
	var affinity_isClickAndCollectEnabled = <?php print json_encode($isClickAndCollectEnabled)?>;
	var affinity_isDomesticaRateTableEnabled = <?php print json_encode($isDomesticaRateTableEnabled)?>;
    <?php
        if (empty($shippingDiscountProfiles['data']['FlatShippingDiscount']['DiscountProfile'])) {
    ?>
    var affinity_shippingDiscountProfiles = {};
    <?php
        } else {
    ?>
    var affinity_shippingDiscountProfiles = <?php print json_encode($shippingDiscountProfiles['data']['FlatShippingDiscount']['DiscountProfile'])?>;
    <?php
        }
    ?>
</script>
<?php 
	
	require_once(__DIR__.'/../model/AffinityEbayInventory.php');
	$rules = AffinityShippingRule::getAllRules();
	$errors = array();
	$a = json_decode(get_option('ebayaffinity_shipping_errors'), true);
	foreach ($a as $el) {
		if (!empty($el)) {
			$errors []= '<p>'.esc_html($el).'</p>';
		}
	}
	
	$setup2 = get_option('ebayaffinity_setup2');
	if (!empty($setup2)) {
		foreach ($rules as $rule) {
			if (empty($rule->profile_id)) {
				$errors []= '<p>Your shipping policies could not be saved to eBay. This problem may be intermittent. Please try again later.</p>';
				break;
			}
		}
	}
	if ($disabled) {
		$errors []= '<p>eBay Sync is not connected to eBay so this page is read-only.</p>';
	}
	
	if (count($errors) > 0) {
	?>
		<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
					<div>Shipping Policy errors</div>
<?php 
		print implode('', array_unique($errors));
?>
		</div>
<?php 
	}

	global $ebayaffinity_delerrors;
	if (!empty($ebayaffinity_delerrors)) {
?>
		<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
			<div>Cannot delete rule</div>
<?php 
		foreach ($ebayaffinity_delerrors as $ebayaffinity_delerror) {
?>
			<p>
<?php 
			print esc_html($ebayaffinity_delerror);
?>
			</p>
<?php 
		}
?>
		</div>
<?php 
	}
?>

	<div class="ebayaffinity-rules">
<?php 
	
	require_once(__DIR__.'/../model/AffinityEbayCategory.php');
	$cats = AffinityEbayCategory::getCategoriesShipRules();
	$ship_count = array();
	foreach ($cats as $k=>$v) {
		if (empty($ship_count[$v[1]])) {
			$ship_count[$v[1]] = 0;
		}
		$ship_count[$v[1]]++;
?>
		<input type="hidden" class="ebayaffinity_catshiprules" data-name="<?php print esc_html($v[0])?>" id="ebayaffinity_catshiprule_<?php print esc_html($k)?>" name="ebayaffinity_catshiprule[<?php print esc_html($k)?>]" value="<?php print esc_html($v[1])?>">
<?php 
	}

	$maxid = 0;
	if (empty($rules)) {
?>
		<div class="ebayaffinity-no-rules">No rules as yet.</div>
<?php 
	}
	
	foreach ($rules as $rule) {
		if ($rule->id > $maxid) {
			$maxid = $rule->id;
		}
		$prods = AffinityEbayInventory::getBySearchCategory('', 0, 1, '_affinity_shiprule', $rule->id, '', 0, 5, false);
		
		foreach ($prods[0] as $prod) {
?>
		<input type="hidden" class="ebayaffinity_prodshiprules" data-name="<?php print esc_html($prod['title'])?>" id="ebayaffinity_prodshiprule_<?php print esc_html($prod['id'])?>" name="ebayaffinity_prodshiprule[<?php print esc_html($prod['id'])?>]" value="<?php print $rule->id?>">
<?php 
		}			
?>
		<input type="hidden" name="ebayaffinity_shiprule_profile_id[<?php print esc_html($rule->id)?>]" id="ebayaffinity_shiprule_profile_id<?php print esc_html($rule->id)?>" value="<?php print esc_html($rule->profile_id)?>">
		<input type="hidden" name="ebayaffinity_shiprule_profile_name[<?php print esc_html($rule->id)?>]" id="ebayaffinity_shiprule_profile_name<?php print esc_html($rule->id)?>" value="<?php print esc_html($rule->profile_name)?>">
					
		<div class="ebayaffinity-settingsblock">
<?php 
		if ($rule->standard_additional_fee === null) {
			$rule->standard_additional_fee = $rule->standard_fee;
		}
		if ($rule->express_additional_fee === null) {
			$rule->express_additional_fee = $rule->express_fee;
		}
?>
			<div class="ebayaffinity-settingsheader ebayaffinity-settingsheader-ship">
				<span class="ebayaffinity-not-mobile">Shipping rule </span>#<?php print esc_html($rule->id)?> <div class="ebayaffinity-settingsheadernote">You are required to select at least one shipping method.</div>
				<div class="ebayaffinity-settingsheadersetdefault">
					<input <?php print $disabled?> type="radio" name="ebayaffinity_shiprule_default" id="ebayaffinity_shiprule_default<?php print esc_html($rule->id)?>" value="<?php print esc_html($rule->id)?>" <?php print empty($rule->is_default)?'':'checked'?>>
					<label class="ebayaffinity-label ebayaffinity-label-ship-default" for="ebayaffinity_shiprule_default<?php print esc_html($rule->id)?>">Set as default shipping rule</label>
				</div>
				<span <?php print $disabled?'style="display:none;"':''?> class="ebayaffinity-settings-action-button ebayaffinity-bt-del-template-old">
					<span>&nbsp;</span>
				</span>
				<div class="ebayaffinity-settings-action-button ebayaffinity-bt-expand">
					<span>&nbsp;</span>
				</div>
			</div>			
			<div class="ebayaffinity-setting-details-category-container" data-id="<?php print esc_html($rule->id)?>">
				<div class="ebayaffinity-header">
					<div class="ebayaffinity-title">Categories applied to:</div>
					<div class="ebayaffinity-bt-add-category" <?php print (empty($ship_count[0])&&empty($ship_count[2]) || $disabled)?'style="display:none;"':''?>>Add category</div>
				</div>

				<div class="ebayaffinity-categories-applied-to">
<?php 
	$i = 0;

	foreach ($cats as $k=>$v) {
		if ($v[1] != $rule->id) {
			continue;
		}
		$i++
?>
					<div class="ebayaffinity-category-item" data-category-id="<?php print esc_html($k)?>">
						<div <?php print $disabled?'style="display:none;"':''?> class="ebayaffinity-bt-delete">
							&times;
						</div>
						<span class="ebayaffinity-category-unit ebayaffinity-category-leaf"><?php print esc_html($v[0])?></span>
					</div>
<?php 
	}
	
	if ($i == 0) {
?>
							<div class="ebayaffinity-category-item">
								<span class="ebayaffinity-category-unit ebayaffinity-category-leaf ebayaffinity-category-none"><em>None as yet.</em></span>
							</div>
<?php 
	}
?>
				</div>
			</div>
			<div class="ebayaffinity-setting-details-product-container" data-id="<?php print esc_html($rule->id)?>">
				<div class="ebayaffinity-header">
					<div class="ebayaffinity-title">Products applied to:</div>
					<div class="ebayaffinity-bt-add-products" <?php print $disabled?'style="display:none;"':''?>>Add products</div>
				</div>
				<div class="ebayaffinity-products-applied-to">
<?php 
if (empty($prods[0])) {
?>
					<div class="ebayaffinity-product-item">
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf ebayaffinity-product-none"><em>None as yet.</em></span>
					</div>
<?php 
} else {
	foreach ($prods[0] as $prod) {
?>
					<div class="ebayaffinity-product-item" data-product-id="<?php print esc_html($prod['id'])?>">
						<div <?php print $disabled?'style="display:none;"':''?> class="ebayaffinity-bt-delete">
							&times;
						</div>
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf"><?php print esc_html($prod['title'])?></span>
					</div>
<?php 
	}
	if ($prods[1] > 5) {
?>
					<div class="ebayaffinity-product-item">
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf ebayaffinity-product-andmore"><em>&hellip;more not listed.</em></span>
					</div>
<?php 
	}
}

if (!$isDomesticaRateTableEnabled) {
	$rule->rate_table = 0;
}
if (!$isClickAndCollectEnabled) {
	$rule->pudo = 0;
}
?>
				</div>
			</div>
			<div class="ebayaffinity-setting-details-extra-container" data-id="<?php print esc_html($rule->id)?>" <?php print ($isDomesticaRateTableEnabled||$isClickAndCollectEnabled)?'':'style="visibility: hidden !important;border: 0 !important; height: 0 !important; overflow: hidden !important; padding: 0 !important; margin: 0 !important;"'?>>
				<div class="ebayaffinity-settingset" <?php print $isDomesticaRateTableEnabled?'':'style="display: none !important;"'?>>
					<div class="ebayaffinity-labeldiv ebayaffinity_rate_table">
						<input type="checkbox" name="ebayaffinity_rate_table[<?php print esc_html($rule->id)?>]" id="ebayaffinity_rate_table<?php print esc_html($rule->id)?>" value="1" <?php print empty($rule->rate_table)?'':'checked'?>>
						<label class="ebayaffinity-label" for="ebayaffinity_rate_table<?php print esc_html($rule->id)?>">Apply domestic postage rate table</label>
					</div>
				</div>
				<div class="ebayaffinity-settingset" <?php print $isClickAndCollectEnabled?'':'style="display: none !important;"'?>>
					<div class="ebayaffinity-labeldiv ebayaffinity_rate_table">
						<input type="checkbox" name="ebayaffinity_shiprule_pudo[<?php print esc_html($rule->id)?>]" id="ebayaffinity_shiprule_pudo<?php print esc_html($rule->id)?>" value="1" <?php print empty($rule->pudo)?'':'checked'?>>
						<label class="ebayaffinity-label" for="ebayaffinity_shiprule_pudo<?php print esc_html($rule->id)?>">Buyers can collect the item at Woolworths or BIG W with Click &amp; Collect</label>
					</div>
				</div>
			</div>
            <div class="ebayaffinity-setting-details-discount-container" data-id="<?php print esc_html($rule->id)?>">
                <div class="ebayaffinity-header">
                    <div class="ebayaffinity-title">Combined Postage Discounts:
                        <div class="ebayaffinity-questiondiv">
                            <div class="ebayaffinity-question"><span class="info">You can offer buyers postage discounts when they buy multiple items from you allowing them to save on postage. eBay Sync currently only supports flat postage rules, which you need to setup in My eBay. Click <a href="http://pages.ebay.com.au/help/pay/shipping-discounts.html" target="_blank">here</a> for more information.</span>?</div>
                        </div>
                    </div>
                </div>
                <div class="ebayaffinity-discount-applied-to">
                    <div class="ebayaffinity-discount-item">
                        <?php
                        if (empty($shippingDiscountProfiles['data']['FlatShippingDiscount']['DiscountProfile'][0])) {
                            ?>
                            <span class="ebayaffinity-discount-unit ebayaffinity-discount-leaf"><em>None Found</em></span>
                        <?php
                        } else if (count($shippingDiscountProfiles['data']['FlatShippingDiscount']['DiscountProfile']) == 1) {
                            $selectedShippingProfile = null;
                            foreach ($profile['data']['shippingProfile'] as $shippingProfile) {
                                if ($shippingProfile['profileId'] == $rule -> profile_id) {
                                    $selectedShippingProfile = $shippingProfile;
                                    break;
                                }
                            }
                            ?>
                            <div class="ebayaffinity-valuediv ebayaffinity-labeldiv ebayaffinity_postage_discount">
                                <input <?php print $disabled?> type="checkbox" name="ebayaffinity_discount_profile[<?php print esc_html($rule->id)?>]" value="<?php print $shippingDiscountProfiles['data']['FlatShippingDiscount']['DiscountProfile'][0]['DiscountProfileID']?>" id="ebayaffinity_postage_discount<?php print esc_html($rule->id)?>" <?php print empty($selectedShippingProfile['extraShippingDiscountProfileId'])?'':'checked'?>>
                                <label class="ebayaffinity-label" for="ebayaffinity_postage_discount<?php print esc_html($rule->id)?>">Apply Flat Postage Discount</label>
                            </div>
                        <?php
                        }else {
                            ?>
                            <div class="ebayaffinity-valuediv">
                                <select <?php print $disabled?> name="ebayaffinity_discount_profile[<?php print esc_html($rule->id)?>]">
                                    <option value="">Not Applied</option>
                                    <?php

                                    $selectedShippingProfile = null;
                                    foreach ($profile['data']['shippingProfile'] as $shippingProfile) {
                                        if ($shippingProfile['profileId'] == $rule -> profile_id) {
                                            $selectedShippingProfile = $shippingProfile;
                                            break;
                                        }
                                    }

                                    foreach ($shippingDiscountProfiles['data']['FlatShippingDiscount']['DiscountProfile'] as $shippingDiscountProfile) {
                                        ?>
                                        <option value="<?php print esc_html($shippingDiscountProfile['DiscountProfileID'])?>"
                                            <?php
                                            if (isset($selectedShippingProfile['extraShippingDiscountProfileId']) && $selectedShippingProfile['extraShippingDiscountProfileId'] == $shippingDiscountProfile['DiscountProfileID']) {
                                                print 'selected=selected';
                                            }
                                            ?>
                                            >
                                            <?php
                                            if ($shippingDiscountProfile['DiscountProfileName']) {
                                                print esc_html($shippingDiscountProfile['DiscountProfileName']);
                                            } else {
                                                print esc_html($shippingDiscountProfile['DiscountProfileID']);
                                            }
                                            ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
			<div class="ebayaffinity-settingset ebayaffinity-settingset-mob">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-settingcell">
						<div class="ebayaffinity-settingset">
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-settinghead">
									Standard&nbsp;shipping
								</div>
							</div>
						</div>
						<div class="ebayaffinity-settingset ebayaffinity-settingpanel">
							<div class="ebayaffinity-setting ebayaffinity_shiprule_standard_freeshippingrow">
								<div class="ebayaffinity-labeldiv">
									<label class="ebayaffinity-label" for="ebayaffinity_shiprule_standard_freeshipping<?php print esc_html($rule->id)?>">Free shipping?</label>
								</div>
								<div class="ebayaffinity-valuediv">
									<input <?php print $disabled?> type="checkbox" class="ebayaffinity_shiprule_standard_freeshipping" id="ebayaffinity_shiprule_standard_freeshipping<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_standard_freeshipping[<?php print esc_html($rule->id)?>]" value="1" <?php print ($rule->standard_freeshipping)==1?'checked':''?>>
								</div>
								<div class="ebayaffinity-questiondiv">
								</div>
							</div>
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-labeldiv">
									<label class="ebayaffinity-label" for="ebayaffinity_shiprule_standard_fee<?php print esc_html($rule->id)?>">Shipping fee ($)</label>
								</div>
								<div class="ebayaffinity-valuediv">
									<input <?php print $disabled?> type="text" id="ebayaffinity_shiprule_standard_fee<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_standard_fee[<?php print esc_html($rule->id)?>]" <?php print ($rule->standard_freeshipping)==1?'disabled':''?> value="<?php print esc_html(floatval($rule->standard_fee) == 0 ? '' : $rule->standard_fee)?>">
								</div>
								<div class="ebayaffinity-questiondiv">
									<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for your standard shipping services</span>?</div>
								</div>
							</div>
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-labeldiv">
									<label class="ebayaffinity-label" for="ebayaffinity_shiprule_standard_additional_fee<?php print esc_html($rule->id)?>">Each Additional ($)</label>
								</div>
								<div class="ebayaffinity-valuediv">
									<input <?php print $disabled?> type="text" class="ebayaffinity_shiprule_additional_fee" id="ebayaffinity_shiprule_standard_additional_fee<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_standard_additional_fee[<?php print esc_html($rule->id)?>]" <?php print ($rule->standard_freeshipping)==1?'disabled':''?> value="<?php if (($rule->standard_fee) == 0) {print '';} else {print esc_html(floatval($rule->standard_additional_fee) == 0 ? '0.00' : $rule->standard_additional_fee);}?>">
								</div>
								<div class="ebayaffinity-questiondiv">
									<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for shipping of each additional quantity of the same item.</span>?</div>
								</div>
							</div>
						</div>
						<div class="ebayaffinity-settingset">
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-settingsubhead">
									<em>If you do not want to offer standard shipping, leave both fields blank.</em>
								</div>
							</div>
						</div>
					</div>
					<div class="ebayaffinity-settingcell">
						<div class="ebayaffinity-settingset">
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-settinghead">
									Express&nbsp;shipping
								</div>
							</div>
						</div>
						<div class="ebayaffinity-settingset ebayaffinity-settingpanel">
							<div class="ebayaffinity-setting ebayaffinity_shiprule_standard_freeshippingrow">
								<div class="ebayaffinity-labeldiv">
									<label class="ebayaffinity-label" for="ebayaffinity_shiprule_express_freeshipping<?php print esc_html($rule->id)?>">Free shipping?</label>
								</div>
								<div class="ebayaffinity-valuediv">
									<input <?php print $disabled?> type="checkbox" class="ebayaffinity_shiprule_express_freeshipping" id="ebayaffinity_shiprule_express_freeshipping<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_express_freeshipping[<?php print esc_html($rule->id)?>]" value="1" <?php print ($rule->express_freeshipping)==1?'checked':''?>>
								</div>
								<div class="ebayaffinity-questiondiv">
								</div>
							</div>
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-labeldiv">
									<label class="ebayaffinity-label" for="ebayaffinity_shiprule_express_fee<?php print esc_html($rule->id)?>">Shipping fee ($)</label>
								</div>
								<div class="ebayaffinity-valuediv">
									<input type="hidden" id="ebayaffinity_shiprule_oexpress_fee<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_oexpress_fee[<?php print esc_html($rule->id)?>]" value="<?php print esc_html(floatval($rule->express_fee) == 0 ? '' : $rule->express_fee)?>">
									<input <?php print $disabled?> type="text" id="ebayaffinity_shiprule_express_fee<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_express_fee[<?php print esc_html($rule->id)?>]" <?php print ($rule->express_freeshipping)==1?'disabled':''?> value="<?php print esc_html(floatval($rule->express_fee) == 0 ? '' : $rule->express_fee)?>">
								</div>
								<div class="ebayaffinity-questiondiv">
									<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for your express shipping services</span>?</div>
								</div>
							</div>
                            <div class="ebayaffinity-setting">
                                <div class="ebayaffinity-labeldiv">
                                    <label class="ebayaffinity-label" for="ebayaffinity_shiprule_express_additional_fee<?php print esc_html($rule->id)?>">Each Additional ($)</label>
                                </div>
                                <div class="ebayaffinity-valuediv">
                                    <input <?php print $disabled?> type="text" class="ebayaffinity_shiprule_additional_fee" id="ebayaffinity_shiprule_express_additional_fee<?php print esc_html($rule->id)?>" name="ebayaffinity_shiprule_express_additional_fee[<?php print esc_html($rule->id)?>]" <?php print ($rule->express_freeshipping)==1?'disabled':''?> value="<?php if (($rule->express_fee) == 0) {print '';} else {print esc_html(floatval($rule->express_additional_fee) == 0 ? '0.00' : $rule->express_additional_fee);}?>">
                                </div>
                                <div class="ebayaffinity-questiondiv">
                                    <div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for shipping of each additional quantity of the same item.</span>?</div>
                                </div>
                            </div>
						</div>
						<div class="ebayaffinity-settingset">
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-settingsubhead">
									<em>If you do not want to offer express shipping, leave both fields blank.</em>
								</div>
							</div>
						</div>
					</div>
					<div class="ebayaffinity-settingcell">
						<div class="ebayaffinity-settingset">
							<div class="ebayaffinity-setting">
								<div class="ebayaffinity-settinghead">
									Handling&nbsp;time
								</div>
							</div>
						</div>
						<div class="ebayaffinity-settingset">
							<div class="ebayaffinity-setting ebayaffinity_shiprule_standard_freeshippingrow">
								<div class="ebayaffinity-valuediv">
									<select <?php print $disabled?> id="ebayaffinity_shiprule_handledays<?php print esc_html($rule->id)?>" class="ebayaffinity_shiprule_handledays" name="ebayaffinity_shiprule_handledays[<?php print esc_html($rule->id)?>]">
										<option value="0" <?php print ($rule->handledays==0)?'selected':''?>>0</option>
										<option value="1" <?php print ($rule->handledays==1)?'selected':''?>>1</option>
										<option value="2" <?php print ($rule->handledays==2)?'selected':''?>>2</option>
										<option value="3" <?php print ($rule->handledays==3)?'selected':''?>>3</option>
										<option value="4" <?php print ($rule->handledays==4)?'selected':''?>>4</option>
										<option value="5" <?php print ($rule->handledays==5)?'selected':''?>>5</option>
										<option value="10" <?php print ($rule->handledays==10)?'selected':''?>>10</option>
										<option value="15" <?php print ($rule->handledays==15)?'selected':''?>>15</option>
										<option value="20" <?php print ($rule->handledays==20)?'selected':''?>>20</option>
										<option value="30" <?php print ($rule->handledays==30)?'selected':''?>>30</option>
									</select>
								</div>
								<div class="ebayaffinity-labeldiv ebayaffinity_shiprule_handledayslabel">
									<label class="ebayaffinity-label" for="ebayaffinity_shiprule_handledays<?php print esc_html($rule->id)?>">business&nbsp;day(s)</label>
								</div>
								<div class="ebayaffinity-questiondiv">
									<div class="ebayaffinity-question"><span class="info">The number of days you will take to ship your eBay orders. <a target="_blank" href="http://pages.ebay.com.au/help/buy/contextual/domestic-handling-time.html">More info</a></span>?</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php 
	}
	?>
</div>

<input type="hidden" name="ebayaffinity_nextid" id="ebayaffinity_nextid" value="<?php print htmlspecialchars($maxid + 1)?>">

<?php 
} else if ($_GET['pnum'] == 4) {
	$errors = array();
	$a = json_decode(get_option('ebayaffinity_returns_errors'), true);
	foreach ($a as $el) {
		if (!empty($el)) {
			$errors []= '<p>'.esc_html($el).'</p>';
		}
	}
	
	$setup3 = get_option('ebayaffinity_setup3');
	$returns_profile_id = get_option('ebayaffinity_returns_profile_id');
	if ((!empty($setup3)) && empty($returns_profile_id)) {
		$errors []= '<p>Your return policy could not be saved to eBay. This problem may be intermittent. Please try again later.</p>';
	}
	if ($disabled) {
		$errors []= '<p>eBay Sync is not connected to eBay so this page is read-only.</p>';
	}
	
	if (count($errors) > 0) {
?>
			<div class="ebayaffinity-big-error ebayaffinity-big-error-settings">
					<div>Return Policy errors</div>
<?php 
			print implode('', array_unique($errors));
?>
			</div>
<?php 
	}
?>
	<div class="ebayaffinity-settingsblock">
	<div class="ebayaffinity-settingsheader">Returns policy</div>
	<div class="ebayaffinity-settingset">
		
	<div class="ebayaffinity-setting">
	<div class="ebayaffinity-labeldiv">
	<label class="ebayaffinity-label" for="ebayaffinity_returnaccepted1">Returns accepted</label>
	</div>
	<div class="ebayaffinity-valuediv">
	<?php
	$ebayaffinity_returnaccepted = get_option('ebayaffinity_returnaccepted');
	?>
							<input <?php print $disabled?> type="radio" name="ebayaffinity_returnaccepted" id="ebayaffinity_returnaccepted1" value="RETURNS_ACCEPTED" <?php print $ebayaffinity_returnaccepted=='RETURNS_ACCEPTED'?'checked':''?>>
							<label class="ebayaffinity-label" for="ebayaffinity_returnaccepted1">Yes</label>
							<input <?php print $disabled?> type="radio" name="ebayaffinity_returnaccepted" id="ebayaffinity_returnaccepted2" value="RETURNS_NOT_ACCEPTED" <?php print $ebayaffinity_returnaccepted=='RETURNS_NOT_ACCEPTED'?'checked':''?>>
							<label class="ebayaffinity-label" for="ebayaffinity_returnaccepted2">No</label>
						</div>
						<div class="ebayaffinity-questiondiv">
						</div>
					</div>
				
					<div class="ebayaffinity-setting" style="<?php print $ebayaffinity_returnaccepted=='RETURNS_NOT_ACCEPTED'?'display: none;':''?>">
						<div class="ebayaffinity-labeldiv">
							<label class="ebayaffinity-label" for="ebayaffinity_refundoption">Refund option</label>
						</div>
						<div class="ebayaffinity-valuediv">
	<?php 
	$ebayaffinity_refundoption = get_option('ebayaffinity_refundoption');
	?>
							<select <?php print $disabled?> id="ebayaffinity_refundoption" name="ebayaffinity_refundoption">
								<option value="MONEY_BACK" <?php print $ebayaffinity_refundoption=='MONEY_BACK'?'selected':''?>>Money back</option>
								<option value="EXCHANGE" <?php print $ebayaffinity_refundoption=='EXCHANGE'?'selected':''?>>Exchange</option>
								<option value="MERCHANDISE_CREDIT" <?php print $ebayaffinity_refundoption=='MERCHANDISE_CREDIT'?'selected':''?>>Merchandise credit</option>
							</select>
						</div>
						<div class="ebayaffinity-questiondiv">
						</div>
					</div>
					<div class="ebayaffinity-setting" style="<?php print $ebayaffinity_returnaccepted=='RETURNS_NOT_ACCEPTED'?'display: none;':''?>">
						<div class="ebayaffinity-labeldiv">
							<label class="ebayaffinity-label" for="ebayaffinity_returncosts">Return costs are paid by</label>
						</div>
						<div class="ebayaffinity-valuediv">
	<?php 
	$ebayaffinity_returncosts = get_option('ebayaffinity_returncosts');
	?>
							<select <?php print $disabled?> id="ebayaffinity_returncosts" name="ebayaffinity_returncosts">
								<option value="BUYER" <?php print $ebayaffinity_returncosts=='BUYER'?'selected':''?>>Buyer</option>
								<option value="SELLER" <?php print $ebayaffinity_returncosts=='SELLER'?'selected':''?>>Seller</option>
							</select>
						</div>
						<div class="ebayaffinity-questiondiv">
						</div>
					</div>
					<div class="ebayaffinity-setting" style="<?php print $ebayaffinity_returnaccepted=='RETURNS_NOT_ACCEPTED'?'display: none;':''?>">
						<div class="ebayaffinity-labeldiv">
							<label class="ebayaffinity-label" for="ebayaffinity_returnwithin">Return within (days)</label>
						</div>
						<div class="ebayaffinity-valuediv">
	<?php 
	$ebayaffinity_returnwithin = get_option('ebayaffinity_returnwithin');
	?>
							<select <?php print $disabled?> id="ebayaffinity_returnwithin" name="ebayaffinity_returnwithin">
								<option value="DAYS_3" <?php print $ebayaffinity_returnwithin=='DAYS_3'?'selected':''?>>3</option>
								<option value="DAYS_7" <?php print $ebayaffinity_returnwithin=='DAYS_7'?'selected':''?>>7</option>
								<option value="DAYS_14" <?php print $ebayaffinity_returnwithin=='DAYS_14'?'selected':''?>>14</option>
								<option value="DAYS_30" <?php print $ebayaffinity_returnwithin=='DAYS_30'?'selected':''?>>30</option>
								<option value="DAYS_60" <?php print $ebayaffinity_returnwithin=='DAYS_60'?'selected':''?>>60</option>
							</select>
						</div>
						<div class="ebayaffinity-questiondiv">
						</div>
					</div>
					<div class="ebayaffinity-setting" style="<?php print $ebayaffinity_returnaccepted=='RETURNS_NOT_ACCEPTED'?'display: none;':''?>">
						<div class="ebayaffinity-labeldiv">
							<label class="ebayaffinity-label" for="ebayaffinity_returnwithin">Additional notes</label>
						</div>
						<div class="ebayaffinity-valuediv">
	<?php 
	$ebayaffinity_returndesc = get_option('ebayaffinity_returndesc');
	?>
							<textarea <?php print $disabled?> id="ebayaffinity_customtemplate" style="height: 75px;" name="ebayaffinity_returndesc"><?php print esc_html($ebayaffinity_returndesc)?></textarea>
						</div>
						<div class="ebayaffinity-questiondiv">
						</div>
					</div>
				</div>
			</div>	
<?php 
} else if ($_GET['pnum'] == 5) {
	require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
	
	if(isset($_GET['refreshEcommerceUrl']) && $_GET['refreshEcommerceUrl']) {
		AffinityGlobalOptions::refreshCallbackUrl();
	}
	
	$arrLinkRefreshEcommerceUrl = array_merge($_GET, array("refreshEcommerceUrl" => "true"));
	$urlRefreshEcommerceUrl = http_build_query($arrLinkRefreshEcommerceUrl);
?>
		<div class="ebayaffinity-settingsblock">
			<div class="ebayaffinity-settingsheader">General</div>
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_storeurl">eCommerce store URL</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="text" id="ebayaffinity_storeurl" name="ebayaffinity_storeurl" value="<?php print AffinityEcommerceUtils::getStoreUrl(); ?>" readonly disabled>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -70px;">This is the URL of your WooCommerce installation. It is used by eBay to send order information to your store. Unless you know what you are doing, do not change this value</span>?</div>
					</div>
				</div>
				<div class="ebayaffinity-setting ebayaffinity-refresh-site-url">
					<div class="ebayaffinity-labeldiv">
						<a style="color: #777777;" href="?<?php print esc_html($urlRefreshEcommerceUrl) ?>">Refresh</a>
					</div>
				</div>
			
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_pushinvenorytime">Refresh product feed</label>
					</div>
					<div class="ebayaffinity-valuediv">
<?php 
$pushinvenorytime = get_option('ebayaffinity_pushinvenorytime');
?>
						<select id="ebayaffinity_pushinvenorytime" name="ebayaffinity_pushinvenorytime">
							<option value="off" <?php print $pushinvenorytime=='off'?'selected':''?>>Off</option>
							<option value="hourly" <?php print $pushinvenorytime=='hourly'?'selected':''?>>Hourly</option>
							<option value="twicedaily" <?php print $pushinvenorytime=='twicedaily'?'selected':''?>>Twice daily</option>
							<option value="daily" <?php print $pushinvenorytime=='daily'?'selected':''?>>Daily</option>
						</select>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -80px;">How often your inventory synchronisation with eBay occurs. It is strongly recommended you keep this hourly. Switching this to off will prevent your eBay listings being updated</span>?</div>
					</div>
				</div>
			
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_currency">Currency</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="text" id="ebayaffinity_currency" name="ebayaffinity_currency" readonly disabled value="<?php print esc_html(get_woocommerce_currency())?>">
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -30px;">The currency configured on your WooCommerce</span>?</div>
					</div>
				</div>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_timezone">Timezone</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="text" id="ebayaffinity_timezone" name="ebayaffinity_timezone" readonly disabled value="<?php print esc_html(wc_timezone_string())?>">
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -30px;">The timezone configured on your WooCommerce</span>?</div>
					</div>
				</div>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_stockbuffer">Minimum stock buffer</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input onkeypress="var key = event.charCode || event.keyCode || 0; return key == 37 || key == 39 || key == 8 || key == 9 || key == 27 || key == 13 || key == 110 || key == 190 || (key &gt;= 48 &amp;&amp; key &lt;= 57)" type="text" id="ebayaffinity_stockbuffer" name="ebayaffinity_stockbuffer" value="<?php print esc_html(get_option('ebayaffinity_stockbuffer'))?>">
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -40px;">The number of inventory items you want to keep exclusively for your WooCommerce</span>?</div>
					</div>
				</div>
<?php 
$priceadjust = get_option('ebayaffinity_priceadjust');
?>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_priceadjust">Store price adjustment</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<select style="width: 10%; min-width: 40px; vertical-align: top;  display: inline-block;" name="ebayaffinity_priceadjust_posneg">
							<option value="+" <?php print (strpos($priceadjust, '-') === false) ? 'selected':'' ?>>+</option>
							<option value="-" <?php print (strpos($priceadjust, '-') !== false) ? 'selected':'' ?>>-</option>
						</select>
						
						<input onkeypress="var key = event.charCode || event.keyCode || 0; console.log(key); return key == 37 || key == 39 || key == 46 || key == 8 || key == 9 || key == 27 || key == 13 || key == 110 || key == 190 || (key &gt;= 48 &amp;&amp; key &lt;= 57)" style="width: 20%; display: inline-block; vertical-align: top; " type="text" id="ebayaffinity_priceadjust" name="ebayaffinity_priceadjust" value="<?php print esc_html(str_replace('num', '', str_replace('-', '', $priceadjust)))?>">
						<select style="width: 10%; min-width: 40px; vertical-align: top; display: inline-block;" name="ebayaffinity_priceadjust_percdoll">
							<option value="%" <?php print (strpos($priceadjust, 'num') === false) ? 'selected':'' ?>>%</option>
							<option value="$" <?php print (strpos($priceadjust, 'num') !== false) ? 'selected':'' ?>>$</option>
						</select>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -60px;">Adjust the pricing on all eBay listings by increasing/decreasing as a percentage or dollar value.</span>?</div>
					</div>
				</div>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_stocklevel">Default stock level</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input onkeypress="var key = event.charCode || event.keyCode || 0; return key == 37 || key == 39 || key == 8 || key == 9 || key == 27 || key == 13 || key == 110 || key == 190 || (key &gt;= 48 &amp;&amp; key &lt;= 57)" type="text" id="ebayaffinity_stocklevel" name="ebayaffinity_stocklevel" value="<?php print esc_html(get_option('ebayaffinity_stocklevel'))?>">
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -30px;">The stock to use if an item has unmanaged stock</span>?</div>
					</div>
				</div>
				
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv" style="height: auto;">&nbsp;</div>
					<div class="ebayaffinity-valuediv">
						<span style="display: block; color: #f75a5f; margin-top: 0px;">
							<strong>Warning</strong>: <em>The default stock level will maintain your stock availability on eBay at the defined level and can lead to overselling if items go out of stock.</em>
						</span>
					</div>
					<div class="ebayaffinity-questiondiv">&nbsp;</div>
				</div>
<?php 
$autoattributes = get_option('ebayaffinity_noautoattributes');
$autoattributes = empty($autoattributes)?'1':'';
?>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_autoattributes">Automatically create custom item specifics</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="checkbox" id="ebayaffinity_autoattributes" name="ebayaffinity_autoattributes" value="1" <?php print empty($autoattributes)?'':'checked'?>>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -30px;">If unchecked, attributes will be ignored unless they are mapped</span>?</div>
					</div>
				</div>
<?php 
$noautohttp = get_option('ebayaffinity_noautohttp');
$noautohttp = empty($noautohttp)?'1':'';
?>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_autohttp">Automatically convert HTTPS images to HTTP</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="checkbox" id="ebayaffinity_autohttp" name="ebayaffinity_autohttp" value="1" <?php print empty($noautohttp)?'':'checked'?>>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="">eBay requires HTTP image URLs</span>?</div>
					</div>
				</div>
				<?php 
$noautoresize = get_option('ebayaffinity_noautoresize');
$noautoresize = empty($noautoresize)?'1':'';
?>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_autoresize">Automatically resize small product images</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="checkbox" id="ebayaffinity_autoresize" name="ebayaffinity_autoresize" value="1" <?php print empty($noautoresize)?'':'checked'?>>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="">eBay requires at least 500x500 images</span>?</div>
					</div>
				</div>
				
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv" style="height: auto;">&nbsp;</div>
					<div class="ebayaffinity-valuediv">
						<span style="display: block; color: #f75a5f; margin-top: 0px;">
							<strong>Warning</strong>: <em>Products with images that are too small for eBay (smaller than 500x500 pixels) will be ended automatically if this option is unchecked.</em>
						</span>
					</div>
					<div class="ebayaffinity-questiondiv">&nbsp;</div>
				</div>
			</div>
		</div>
<?php 
$logenabled = get_option('ebayaffinity_logenabled');
$logenabled = empty($logenabled)?'':'1';
?>
		<div class="ebayaffinity-settingsblock">
			<div class="ebayaffinity-settingsheader">Logging</div>
			<div class="ebayaffinity-settingset">
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_storeurl">Enable logs</label>
					</div>
					<div class="ebayaffinity-valuediv">
						<input type="checkbox" id="ebayaffinity_logenabled" name="ebayaffinity_logenabled" value="1" <?php print empty($logenabled)?'':'checked'?>>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -40px;">Your logs will be very useful if you need to contact support. So it is best to leave this enabled</span>?</div>
					</div>
				</div>
				<div class="ebayaffinity-setting">
					<div class="ebayaffinity-labeldiv">
						<label class="ebayaffinity-label" for="ebayaffinity_pushinvenorytime">Clear my logs</label>
					</div>
					<div class="ebayaffinity-valuediv">
<?php 
$clearlogtime = get_option('ebayaffinity_clearlogtime');
?>
						<select id="ebayaffinity_clearlogtime" name="ebayaffinity_clearlogtime" style="max-width: 40%">
							<option value="daily" <?php print $clearlogtime=='daily'?'selected':''?>>Daily</option>
							<option value="weekly" <?php print $clearlogtime=='weekly'?'selected':''?>>Weekly</option>
							<option value="monthly" <?php print $clearlogtime=='monthly'?'selected':''?>>Monthly</option>
							<option value="" <?php print $clearlogtime==''?'selected':''?>>Never</option>
						</select>
						<a style="margin-left: 10px;" href="admin.php?page=ebay-sync-settings&amp;pnum=5&amp;clearlogs=1">Clear logs now</a>
					</div>
					<div class="ebayaffinity-questiondiv">
						<div class="ebayaffinity-question"><span class="info" style="margin-top: -50px;">How often your eBay Sync logs will be cleared. The logs can grow quite large if left unattended</span>?</div>
					</div>
				</div>
			</div>
		</div>
		
<?php 
}

if ($_GET['pnum'] > 0) {
?>
		<input <?php print ($disabled && ($_GET['pnum'] == 3 || $_GET['pnum'] == 4))?'style="display:none;"':''?> type="submit" name="save" value="Save changes" class="ebayaffinity-settingssave">
<?php 
}

if ($_GET['pnum'] == 5) {
?>
		<script type="text/javascript">var affinity_hash = <?php print json_encode(sha1(php_uname().date('dmY')))?>;</script>
		<button type="button" class="ebayaffinity-bt-del-store">Reset eBay Sync</button>
		<div class="ebayaffinity-settings-reset-and-delete-section">
			<input id="resetAndDelete" type="checkbox">
			Reset and delete all created listings
		</div>
<?php 
}
?>
	</form>
</div>
<?php 
if (empty($_GET['pnum'])) {
?>
<div id="ebayaffinity-pop">
	<p>Your username has changed. Does it refer to the same eBay account?</p>
	
	<div>
		<input type="button" value="Yes" id="ebayaffinity-pop-y" class="ebayaffinity-settingssave"><input type="button" value="No" id="ebayaffinity-pop-n" class="ebayaffinity-settingssave">
	</div>
</div>
	
<div id="ebayaffinity-pop-back">
	&nbsp;
	</div>
<?php 
}
require_once(__DIR__.'/float.php');

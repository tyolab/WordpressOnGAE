<?php

class AffinityGlobalOptions {
	const OPTION_LOCAL_INSTANCE_ID = 'ebayaffinity_localinstanceid';
	const OPTION_INSTALLATION_ID = 'ebayaffinity_installationid';
	
	public static function getEbayUserId() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::getOption("ebayaffinity_ebayuserid");
	}
	
	public static function getStockBuffer() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::getOption("ebayaffinity_stockbuffer");
	}
	
	public static function getStockLevel() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::getOption("ebayaffinity_stocklevel");
	}
	
	public static function getCallbackUrl() {
		require_once(__DIR__ . "/AffinityPushToken.php");
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
		
		$callbackUrl = AffinityEcommerceUtils::getCallbackUrl();
		$currentToken = AffinityPushToken::getCurrentToken();
		
		return $callbackUrl . "?aT=" . $currentToken;
	}
	
	public static function refreshCallbackUrl() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
		require_once(__DIR__ . "/../service/AffinityBackendService.php");
		
		$callbackUrl = self::getCallbackUrl();
		AffinityBackendService::setupCallbackUrl($callbackUrl);
	}
	
	public static function getLocalInstanceId() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		$return = AffinityDataLayer::getOption(self::OPTION_LOCAL_INSTANCE_ID);
		
		if(empty($return)) {
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
			$return = AffinityEcommerceUtils::generateSecureRandomString();
			AffinityDataLayer::saveOption(self::OPTION_LOCAL_INSTANCE_ID, $return, false);
		}
		
		return $return;
	}
	
	public static function getInstallationId() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		$return = AffinityDataLayer::getOption(self::OPTION_INSTALLATION_ID);
		
		return $return;
	}
	
	public static function setInstallationId($installationId) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		AffinityDataLayer::saveOption(self::OPTION_INSTALLATION_ID, $installationId);
	}
}

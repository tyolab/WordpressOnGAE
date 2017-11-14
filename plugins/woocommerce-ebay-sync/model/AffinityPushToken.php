<?php
class AffinityPushToken {
	const className = "AffinityPushToken";
	const tokenUsageTimesLimit = 3;
	
	public $id;
	public $timestampCreated;
	public $timestampsWasUsed;
	public $token;
	public $timesUsed;
	
	public static function generateNewToken() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
	
		$obj = new AffinityPushToken();
		$obj->timestampCreated = time();
		$obj->timestampsWasUsed = "";
		$obj->token = AffinityEcommerceUtils::generateSecureRandomString();
		$obj->timesUsed = 0;
		
		$obj->save();
		
		return $obj;
	}
	
	public static function getCurrentToken() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		$objCurrentToken = AffinityDataLayer::getObjectWithSmallestAttribute(self::className, "id");
		if(empty($objCurrentToken)) {
			$objCurrentToken = self::generateNewToken();
		}
		
		return $objCurrentToken->token;
	}
	
	private function save() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::save($this);
	}
	
	public static function getAndUse($token) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		require_once(__DIR__ . "/AffinityLog.php");
		
		if(empty($token)) {
			return false;
		}
		
		//Verify if the token exists
		$objToken = AffinityDataLayer::selectByAttribute(self::className, "token", $token);
		if(empty($objToken)) {
			return false;
		}
		
		//Update the object
		$objToken->timesUsed += 1;
		$objToken->timestampsWasUsed .= time() . ";";

		//Save changes to the object
		$objToken->save();
		
		return $objToken;
	}
}

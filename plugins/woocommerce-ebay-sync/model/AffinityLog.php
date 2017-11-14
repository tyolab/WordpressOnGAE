<?php
class AffinityLog {
	const className = "AffinityLog";
	
	const TYPE_DEBUG = "debug";
	const TYPE_WARNING = "warning";
	const TYPE_ERROR = "error";
	
	public $id;
	public $strDateTime;
	public $type;
	public $title;
	public $details;
	public $numDateTime;
	
	public static function saveLog($constType, $title, $details) {
		$obj = new AffinityLog();
		$obj->strDateTime = date("d/m/Y H:i:s");
		$obj->numDateTime = time();
		$obj->type = $constType;
		$obj->title = $title;
		$obj->details = str_replace("password", "********", $details); //@Todo: make a decent filter for passwords
		
		$obj->save();
	}
	
	private function save() {
		$logenabled = get_option('ebayaffinity_logenabled');
		if (empty($logenabled)) {
			return false;
		}
		
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		try {
			return AffinityDataLayer::save($this);
		} catch (Exception $e) {
			return false;
		}
	}
	
	public static function getAll($limit = 10, $offset = 0, $s = '', $startdate = '', $enddate = '') {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		$s = stripslashes($s);
		return AffinityDataLayer::getAll(self::className, $limit, $offset, 'DESC', stripslashes($s), stripslashes($startdate), stripslashes($enddate));
	}
}

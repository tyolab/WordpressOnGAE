<?php

class AffinityDataLayer {
    const SCHEMA_VERSION_FIELD_NAME = "AffinityDataSchemaVersion";
    const SCHEMA_VERSION = 1.1;
	const tableNameField = 'tableName';
	const arrFieldsName = 'fields';
    const prefixOptionName = 'affinity_';
	
	public $id;
	public $strDateTime;
	public $type;
	public $message;
	
	/*
	 * First field will be the ID
	 */
	private static $_mapClassNameWithTableNameAndFields = array(
		"AffinityEbayCategory" => array(
			self::tableNameField => 'ebayaffinity_categories',
			self::arrFieldsName => array('categoryId', 'name', 'level', 'parentCategoryId', 'path', 'leaf')
		),
		"AffinityItemSpecificMapping" => array(
			self::tableNameField => 'ebayaffinity_itemspecific_mapping',
			self::arrFieldsName => array('id', 'ecommerceItemSpecificId', 'ecommerceItemSpecificName', 'ebayItemSpecificId', 'customTypedName', 'mappedName')
		),
		"AffinityLog" => array(
			self::tableNameField => 'ebayaffinity_log',
			self::arrFieldsName => array('id', 'strDateTime', 'type', 'title', 'details', 'numDateTime')
		),
		"AffinityPushToken" => array(
			self::tableNameField => 'ebayaffinity_pushtoken',
			self::arrFieldsName => array('id', 'timestampCreated', 'timestampsWasUsed', 'token', 'timesUsed')
		),
		"AffinityTitleRule" => array(
			self::tableNameField => 'ebayaffinity_titlerules',
			self::arrFieldsName => array('id', 'title', 'is_default', 'rule')
		),
		"AffinityShipRule" => array(
			self::tableNameField => 'ebayaffinity_shiprules',
			self::arrFieldsName => array('id', 'profile_id', 'profile_name', 'is_default', 'standard_freeshipping', 'express_freeshipping', 'standard_fee', 'standard_additional_fee', 'express_fee', 'express_additional_fee', 'handledays')
		),
	);
	
    private static $_mapClassNameWithSql = array(
		"AffinityEbayCategory" =>  "categoryId int(11) NOT NULL DEFAULT '0',
									name varchar(255) DEFAULT NULL,
									level int(11) DEFAULT '0',
									parentCategoryId int(11) DEFAULT '0',
									path varchar(255) DEFAULT NULL,
									leaf tinyint(1) DEFAULT '0',
									PRIMARY KEY  (categoryId)",
		
		"AffinityItemSpecificMapping" =>  "id int(11) NOT NULL AUTO_INCREMENT,
									ecommerceItemSpecificId varchar(512) NOT NULL,
									ecommerceItemSpecificName varchar(512) NOT NULL,
									ebayItemSpecificId varchar(512) DEFAULT NULL,
									customTypedName varchar(512) DEFAULT NULL,
									mappedName varchar(512) DEFAULT NULL,
									PRIMARY KEY  (id)",
		
		"AffinityLog" =>  "id int(11) NOT NULL AUTO_INCREMENT,
									strDateTime varchar(16) NOT NULL,
									numDateTime bigint(20) DEFAULT NULL,
									type varchar(32) NOT NULL,
									title varchar(64) NOT NULL,
									details text NOT NULL,
									PRIMARY KEY  (id),
									KEY numDateTime (numDateTime)",
        
		"AffinityPushToken" =>  "id int(11) NOT NULL AUTO_INCREMENT,
									timestampCreated varchar(16) NOT NULL,
									timestampsWasUsed varchar(256) NOT NULL,
									token varchar(512) NOT NULL,
									timesUsed int(11) NOT NULL,
									PRIMARY KEY  (id)",
		
        "AffinityTitleRule" => "id int(11) NOT NULL AUTO_INCREMENT,
								is_default tinyint(1) NOT NULL DEFAULT '0',
								rule text,
								title varchar(255) DEFAULT NULL,
								PRIMARY KEY  (id)",
    		
    	"AffinityShipRule" => "id int(11) NOT NULL AUTO_INCREMENT,
    							profile_id varchar(255) DEFAULT NULL,
    							profile_name varchar(255) DEFAULT NULL,
								is_default tinyint(1) NOT NULL DEFAULT '0',
								standard_freeshipping tinyint(1) NOT NULL DEFAULT '0',
    							express_freeshipping tinyint(1) NOT NULL DEFAULT '0',
    							standard_fee decimal(10,2) NOT NULL DEFAULT '0',
    							express_fee decimal(10,2) NOT NULL DEFAULT '0',
    							standard_additional_fee decimal(10,2) DEFAULT NULL,
    							express_additional_fee decimal(10,2) DEFAULT NULL,
    							handledays int(11) NOT NULL DEFAULT '0',
    							rate_table tinyint(1) NOT NULL DEFAULT '1',
    							pudo tinyint(1) NOT NULL DEFAULT '1',
								PRIMARY KEY  (id)"
    );
	
	public static function createInitialOptions() {
		require_once(__DIR__.'/../service/AffinityEnc.php');
		
		add_option('ebayaffinity_useshort');
		add_option('ebayaffinity_ebayuserid');
		add_option('ebayaffinity_puuid');
		add_option('affinityPushAccessToken');

		add_option(self::SCHEMA_VERSION_FIELD_NAME, '1.0');
		add_option('ebayaffinity_agree');
		add_option('ebayaffinity_setup1');
		add_option('ebayaffinity_setup2');
		add_option('ebayaffinity_setup3');
		add_option('ebayaffinity_setup4');
		add_option('ebayaffinity_setup5');

		add_option('ebayaffinity_returnaccepted', 'RETURNS_ACCEPTED');
		add_option('ebayaffinity_refundoption', 'MONEY_BACK');
		add_option('ebayaffinity_returnwithin', 'DAYS_14');
		add_option('ebayaffinity_returncosts', 'BUYER');
		add_option('ebayaffinity_returndesc', '');

		add_option('ebayaffinity_paypal');

		add_option('ebayaffinity_pushinvenorytime', 'hourly');
		add_option('ebayaffinity_stockbuffer', '0');
		add_option('ebayaffinity_priceadjust', '0');
		add_option('ebayaffinity_stocklevel', '0');
		add_option('ebayaffinity_noautoattributes', '');
		add_option('ebayaffinity_noautohttp', '');
		add_option('ebayaffinity_noautoresize', '');
		
		add_option('ebayaffinity_clearlogtime', 'monthly');
		add_option('ebayaffinity_logenabled', '1');
		add_option('ebayaffinity_logran', 0);
		
		update_option('ebayaffinity_backend', 'https://ebaylinksvc.ebay.com.au/affinsvc/v1/');
		update_option('ebayaffinity_ebaysite', 'http://www.ebay.com.au/');
		update_option('ebayaffinity_bin', 'http://offer.ebay.com.au/ws/eBayISAPI.dll?BinConfirm&quantity=1&item=');
		update_option('ebayaffinity_auth', 'https://signin.ebay.com/authorize?client_id=urn%3Aebay-marketplace-consumerid%3Ad0248186-5c29-4ca0-8c14-228de05f2f13&response_type=code&redirect_uri=61e25285-5e29-4e2a-be0f-d6dadb7ef30d&scope=https%3A%2F%2Fapi.ebay.com%2Foauth%2Fscope%2Fsell%40user&state=prod');
		add_option('ebayaffinity_pricing_profile_id');
		add_option('ebayaffinity_pricing_profile_name', 'PAYMENT_AFFINITY_POLICY');
		add_option('ebayaffinity_returns_profile_id');

		add_option('ebayaffinity_pricing_errors', '[]');
		add_option('ebayaffinity_returns_errors', '[]');
		add_option('ebayaffinity_shipping_errors', '[]');
		add_option('ebayaffinity_logo');
		
		require_once(__DIR__.'/../service/AffinityEmail.php');
		AffinityEmail::installs();
	}
	
	public static function wipeAllAffinityData() {
		require_once(__DIR__.'/../model/AffinityShippingRule.php');
		require_once(__DIR__.'/../model/AffinityTitleRule.php');

		AffinityShippingRule::trunc('');
		AffinityTitleRule::trunc('');
		
		global $wpdb;
		
		$wpdb->query("DELETE FROM ".$wpdb->prefix."ebayaffinity_itemspecific_mapping");
		$wpdb->query("DELETE FROM ".$wpdb->prefix."ebayaffinity_log");
		$wpdb->query("DELETE FROM ".$wpdb->prefix."ebayaffinity_pushtoken");
		
		update_option('ebayaffinity_sent_installs', '');
		update_option('ebayaffinity_sent_setups', '');
		update_option('ebayaffinity_sent_products', '');
		
		update_option('ebayaffinity_useshort', '');
		update_option('ebayaffinity_successposts', '');
		update_option('ebayaffinity_hideshiprules', '');
		update_option('ebayaffinity_customtemplate', '');
		update_option('affinity_update_inv_executing', '');
		update_option('affinity_inventory_summary', '');
		update_option('affinityDeletingAllListings', '');
		update_option('affinityTokenExpiry', '');
		update_option('ebayaffinity_ebayuserid', '');
		update_option('ebayaffinity_puuid', '');
		update_option('affinityPushAccessToken', '');
		update_option('ebayaffinity_pubkey', '');
		update_option('ebayaffinity_privkey', '');
		update_option('ebayaffinity_agree', '');
		update_option('ebayaffinity_setup1', '');
		update_option('ebayaffinity_setup2', '');
		update_option('ebayaffinity_setup3', '');
		update_option('ebayaffinity_setup4', '');
		update_option('ebayaffinity_setup5', '');
		update_option('ebayaffinity_returnaccepted', 'RETURNS_ACCEPTED');
		update_option('ebayaffinity_refundoption', 'MONEY_BACK');
		update_option('ebayaffinity_returnwithin', 'DAYS_14');
		update_option('ebayaffinity_returncosts', 'BUYER');
		update_option('ebayaffinity_returndesc', '');
		update_option('ebayaffinity_paypal', '');
		update_option('ebayaffinity_pushinvenorytime', 'hourly');
		update_option('ebayaffinity_stockbuffer', '0');
		update_option('ebayaffinity_priceadjust', '0');
		update_option('ebayaffinity_stocklevel', '0');
		update_option('ebayaffinity_noautoattributes', '');
		update_option('ebayaffinity_noautohttp', '');
		update_option('ebayaffinity_noautoresize', '');
		
		update_option('ebayaffinity_clearlogtime', 'monthly');
		update_option('ebayaffinity_logran', 0);
		update_option('ebayaffinity_logenabled', '1');
		update_option('ebayaffinity_pricing_profile_id', '');
		update_option('ebayaffinity_pricing_profile_name', 'PAYMENT_AFFINITY_POLICY');
		update_option('ebayaffinity_returns_profile_id', '');
		update_option('ebayaffinity_pricing_profile_name', 'PAYMENT_AFFINITY_POLICY');
		update_option('ebayaffinity_pricing_errors', '[]');
		update_option('ebayaffinity_returns_errors', '[]');
		update_option('ebayaffinity_shipping_errors', '[]');
		update_option('ebayaffinity_logo', '');
		update_option('ebayaffinity_installationid', '');

		delete_post_meta_by_key('_affinity_ebaycategory');
		delete_post_meta_by_key('_affinity_ebayitemid');
		delete_post_meta_by_key('_affinity_ebayorder');
		delete_post_meta_by_key('_affinity_marked_as_sent');
		delete_post_meta_by_key('_affinity_tracking_number');
		delete_post_meta_by_key('_affinity_order_warnings');
		delete_post_meta_by_key('_affinity_transaction_id');
		delete_post_meta_by_key('_affinity_item_id');
		delete_post_meta_by_key('_affinity_purchase_order_id');
		delete_post_meta_by_key('_affinity_order_buyer_id');
		delete_post_meta_by_key('_affinity_order_update_status');

		delete_post_meta_by_key('_affinity_prod_all_active_variants');
		delete_post_meta_by_key('_affinity_prod_all_variants');
		delete_post_meta_by_key('_affinity_prod_arr_adaptation_errors');
		delete_post_meta_by_key('_affinity_prod_arr_adaptation_warnings');
		delete_post_meta_by_key('_affinity_prod_arr_client_errors');
		delete_post_meta_by_key('_affinity_prod_arr_client_warnings');
		delete_post_meta_by_key('_affinity_prod_ebay_sku');
		delete_post_meta_by_key('_affinity_prod_last_successful_update');
		delete_post_meta_by_key('_affinity_prod_update_failure_count');
		delete_post_meta_by_key('_affinity_prod_update_status');
		delete_post_meta_by_key('_affinity_shiprule');
		delete_post_meta_by_key('_affinity_suggestedCatId');
		delete_post_meta_by_key('_affinity_titlerule');

		delete_metadata('term', null, '_affinity_ebaycategory', null, true);
		delete_metadata('term', null, '_affinity_shiprule', null, true);
		delete_metadata('term', null, '_affinity_suggestedCatId', null, true);
		delete_metadata('term', null, '_affinity_titlerule', null, true);

		wp_cache_set('last_changed', microtime(), 'terms');
	}
		
	public static function getTables() {
		$return = array();
		
		foreach(self::$_mapClassNameWithTableNameAndFields as $className => $arrTableNameAndFields) {
			$return[] = self::getTableName($className);
		}
		
		return $return;
	}
	
	public static function getFields($tableName) {
		$return = array();
		
		foreach(self::$_mapClassNameWithTableNameAndFields as $className => $arrTableNameAndFields) {
			if(self::getTableName($className) !== $tableName) {
				continue;
			}
			
			foreach($arrTableNameAndFields[self::arrFieldsName] as $fieldName) {
				$return[] = $fieldName;
			}
		}
		
		return $return;
	}
	
    public static function createOrUpdateSchema() {
        global $wpdb;

		$charsetCollate = $wpdb->get_charset_collate();
		$sql = "";
		foreach(self::$_mapClassNameWithSql as $className => $sqlSchema) {
			$tableName = self::getTableName($className);

			$sql .= "CREATE TABLE $tableName (
						$sqlSchema
					) $charsetCollate;
			";
		}

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		update_option(self::SCHEMA_VERSION_FIELD_NAME, self::SCHEMA_VERSION);
		
		$wpdb->query('UPDATE '.$wpdb->prefix.'ebayaffinity_log
			SET numDateTime = 
			UNIX_TIMESTAMP(STR_TO_DATE(strDateTime, \'%d/%m/%Y %H:%i\')) WHERE numDateTime IS NULL');
    }
	
	public static function get($className, $id) {
		global $wpdb;
		
		$strAttributesSeparatedByComma = self::getAttributesSeparatedByComma($className);
		$idFieldName = self::getIdAttribute($className);
		
		$row = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT $strAttributesSeparatedByComma FROM " . self::getTableName($className) . " WHERE $idFieldName = %d", $id
			), ARRAY_A
		);
		
		$objInstance = new $className();
		self::assignAssociativeArrayValuesToObj($row, $objInstance);
		return $objInstance;
	}
	
	public static function selectByAttribute($className, $attributeName, $attributeValue) {
		global $wpdb;
		
		$strAttributesSeparatedByComma = self::getAttributesSeparatedByComma($className);
		
		$row = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT $strAttributesSeparatedByComma FROM " . self::getTableName($className) . " WHERE $attributeName = %s", $attributeValue
			), ARRAY_A
		);
		
		if(empty($row)) {
			return false;
		}
		
		$objInstance = new $className();
		self::assignAssociativeArrayValuesToObj($row, $objInstance);
		return $objInstance;
	}
	
	public static function getObjectWithSmallestAttribute($className, $attributeName) {
		global $wpdb;
		
		$strAttributesSeparatedByComma = self::getAttributesSeparatedByComma($className);
		
		$row = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT $strAttributesSeparatedByComma FROM " . self::getTableName($className) . " ORDER by %s ASC LIMIT 1", $attributeName
			), ARRAY_A
		);
		
		if(empty($row)) {
			return false;
		}
		
		$objInstance = new $className();
		self::assignAssociativeArrayValuesToObj($row, $objInstance);
		return $objInstance;
	}
	
    public static function getAll($className, $limit = 0, $offset = 0, $order = 'ASC', $s = '', $startdate = '', $enddate = '') {
    	global $wpdb;
    	
		$limitClause = "";
		if($limit > 0) {
			$limitClause = "LIMIT $offset,$limit";
		}
		
		$where = ' WHERE 1=1 ';
		
		if (!empty($s)) {
			$where .= ' AND details LIKE \'%'.esc_sql($s).'%\' ';
		}
		
		$orig_tz = date_default_timezone_get();
		if (empty($orig_tz)) {
			$orig_tz = 'UTC';
		}
		$ntz = wc_timezone_string();
		
		if (!empty($ntz)) {
			date_default_timezone_set($ntz);
		}
		
		if (!empty($startdate)) {
			$b = explode('/', $startdate);
			$where .= ' AND numDateTime >= '. mktime(0, 0, 0, $b[1], $b[0], $b[2]).' ';
		}
		
		if (!empty($enddate)) {
			$b = explode('/', $enddate);
			$where .= ' AND numDateTime <= '. mktime(23, 59, 59, $b[1], $b[0], $b[2]).' ';
		}
		
		if (!empty($ntz)) {
			date_default_timezone_set($orig_tz);
		}
		
		$strAttributesSeparatedByComma = self::getAttributesSeparatedByComma($className);
		$arrResults = $wpdb->get_results(
			"SELECT $strAttributesSeparatedByComma FROM " . self::getTableName($className) . $where . " ORDER BY id $order $limitClause", ARRAY_A
		);
		
		require_once(__DIR__ . "/../model/$className.php");
		$return = array();
		foreach($arrResults as $row) {
			$newObjInstance = new $className();
			self::assignAssociativeArrayValuesToObj($row, $newObjInstance);
			
			$return[] = $newObjInstance;
		}
		
		return $return;
    }
	
	public static function save(&$objInstance) {
    	return (isset($objInstance->id) && $objInstance->id > 0) ? self::update($objInstance) : self::create($objInstance);
	}
	
	public static function delete($className, $id) {
		global $wpdb;
		$wpdb->delete(self::getTableName($className), array( 'id' => $id ) );
	}
	
	public static function saveOption($name, $value, $autoload = true) {
		update_option($name, $value, $autoload);
	}
	
	public static function getOption($name) {
		return get_option($name, null);
	}
	
	public static function saveDataAssociatedToEcommerceObject($ecommerceObjectId, $optionName, $optionValue, $invisible = true) {
		$optionName = ($invisible) ? "_" . $optionName : $optionName;
		update_post_meta($ecommerceObjectId, $optionName, $optionValue);
	}
	
	public static function getDataAssociatedToEcommerceObject($ecommerceObjectId, $optionName, $invisible = true) {
		$optionName = ($invisible) ? "_" . $optionName : $optionName;
		return get_post_meta($ecommerceObjectId, $optionName, true);
	}
	
	public static function deleteDataAssociatedToEcommerceObject($ecommerceObjectId, $optionName, $invisible = true) {
		$optionName = ($invisible) ? "_" . $optionName : $optionName;
		return delete_post_meta($ecommerceObjectId, $optionName);
	}
	
	public static function getAllDataAssociatedToEcommerceObject($ecommerceObjectId) {
		return get_post_meta($ecommerceObjectId);
	}
	
	public static function saveDataAssociatedToEcommerceTerm($ecommerceObjectId, $optionName, $optionValue, $invisible = true) {
		$optionName = ($invisible) ? "_" . $optionName : $optionName;
		update_term_meta($ecommerceObjectId, $optionName, $optionValue);
	}
	
	public static function getDataAssociatedToEcommerceTerm($ecommerceObjectId, $optionName, $invisible = true) {
		$optionName = ($invisible) ? "_" . $optionName : $optionName;
		return get_term_meta($ecommerceObjectId, $optionName, true);
	}
	
	public static function findObjectIdWithGivenData($optionName, $optionValue, $invisible = true) {
		$optionName = ($invisible) ? "_" . $optionName : $optionName;
		
		global $wpdb;
		
		return $wpdb->get_var($wpdb->prepare("SELECT post_id FROM ".$wpdb->postmeta." WHERE meta_key = '$optionName' AND meta_value = '%s';", $optionValue));
	}
	
	public static function getTableName($className) {
		global $wpdb;
		
		return $wpdb->prefix . self::$_mapClassNameWithTableNameAndFields[$className][self::tableNameField];
	}
	
	/*
	 * Private helpers
	 */
	
	private static function create(&$newObject) {
		global $wpdb;
		$className = get_class($newObject);
		
		$strAttributesSeparatedByComma = self::getAttributesSeparatedByComma($className, true);
		$strPlaceHolders = self::getPlaceholdersSeparatedByCommaForTheNumberOfObjAttributes($className, true);
		$arrValuesInTheObject = self::getArrValuesFromObjAttributes($newObject, true);
		
		$query = $wpdb->prepare(
			'INSERT INTO ' . self::getTableName($className) . " ($strAttributesSeparatedByComma) VALUES ($strPlaceHolders)",
			$arrValuesInTheObject
		);
		
		if(!$wpdb->query($query)) {
			throw new Exception("Error while creating the object");
		}
		
		$newObject->id = !empty($wpdb->insert_id) ? $wpdb->insert_id : 0;
		return $newObject;
	}
	
	private static function update(&$objInstance) {
		global $wpdb;
		$className = get_class($objInstance);
		
		$strAttributesWithPlaceHolders = self::getObjAttributesWithPlaceholdersSeparatedByComma($className, true);
		$arrValuesInTheObject = self::getArrValuesFromObjAttributes($objInstance, true);
		$arrValuesInTheObject[] = $objInstance->id;
		
		$query = $wpdb->prepare(
			'UPDATE ' . self::getTableName($className) . " SET $strAttributesWithPlaceHolders WHERE id = %d",
			$arrValuesInTheObject
		);
		
		if(!$wpdb->query($query)) {
			throw new Exception("Error while updating the object");
		}
		
		return $objInstance;
	}
    
	private static function getPlaceholdersSeparatedByCommaForTheNumberOfObjAttributes($className, $skipId = false) {
		$return = "";
		
		$arrFields = self::getArrFields($className);
		foreach($arrFields as $strFieldName) {
			if($skipId && $strFieldName === $arrFields[0]) {
				continue;
			}
			
			$return .= "%s,";
		}
		
		$return = rtrim($return, ","); 
		return $return;
	}
	
	private static function getArrValuesFromObjAttributes($obj, $skipId = false) {
		$return = array();
		
		$arrFields = self::getArrFields(get_class($obj));
		foreach($obj as $attributeName => $value) {
			if($skipId && $attributeName === $arrFields[0]) {
				continue;
			}
			
			$return[] = $value;
		}
		
		return $return;
	}
	
	private static function getObjAttributesWithPlaceholdersSeparatedByComma($className, $skipId = false) {
		$return = "";
		
		$arrFields = self::getArrFields($className);
		foreach($arrFields as $strFieldName) {
			if($skipId && $strFieldName === $arrFields[0]) {
				continue;
			}
			
			$return .= $strFieldName . "=%s,";
		}
		
		$return = rtrim($return, ",");
		return $return;
	}
	
	private static function getAttributesSeparatedByComma($className, $skipId = false) {
		$arrFields = self::getArrFields($className);
		if($skipId) {
			unset($arrFields[0]);
		}
		
		return implode(",", $arrFields);
	}
	
	private static function getArrFields($className) {
		return self::$_mapClassNameWithTableNameAndFields[$className][self::arrFieldsName];
	}
	
	private static function getIdAttribute($className) {
		$arrFields = self::getArrFields($className);
		return $arrFields[0];
	}
	
	private static function assignAssociativeArrayValuesToObj($arrAttributesWithValues, &$objInstance) {
		foreach($objInstance as $attributeName => $value) {
			$objInstance->$attributeName = $arrAttributesWithValues[$attributeName];
		}
	}
}

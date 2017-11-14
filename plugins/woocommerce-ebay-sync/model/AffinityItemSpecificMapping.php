<?php
class AffinityItemSpecificMapping {
	const className = "AffinityItemSpecificMapping";
	
	private static $_cachedMappingsIndexedByEcommerceItemSpecificId;
	private static $_ebayRequiredItemSpecifics = array(
		"Brand",
		"GTIN",
		"UPC",
		"EAN",
		"MPN",
		"ISBN",
		"Colour", 
		"Gender",
		"Material",
		"Product Type",
		"Character Family",
		"Topic",
		"Era",
		"Theme",
		"Region",
		"Main Stone",
		"Scale",
		"Size",
		"Compatible Brand",
		"Fragrance Name",
		"Age Level",
		"Style",
		"Metal",
		"Type",
		"Series",
		"Year",
		"Country",
		"Gender",
		"Year of Issue",
		"Features",
		"Main Colour",
		"Title",
		"Region of Origin",
		"Platform",
		"Length",
		"Language",
		"Look",
		"Occasion",
		"Composition",
		"Quality",
		"Compatible Model",
		"Character",
		"Decade",
		"Original/Reproduction",
		"Scent",
		"Model",
		"Capacity per Module",
		"Year of Manufacture",
		"Interface",
		"Item Type",
		"Accessory Type",
		"Format",
		"Activity Theme",
		"Pattern",
		"Shade",
		"Weight",
		"Voltage Rating",
		"Clarity Grade",
		"Main Stone Colour",
		"Power",
		"Inseam",
		"Certificate",
		"Gemstone",
		"Cut Grade",
		"Compatible Fuel Type",
		"Suitable For",
		"Special Attributes",
		"Fancy Colour",
		"Connection Type",
		"Min. Number of Players",
		"Award",
		"Climate",
		"Genre",
		"Subgenre",
		"Current Type",
		"Metal Type",
		"Main Stone Treatment",
		"Transparency",
		"Clarity",
		"Cup Size",
		"Authenticity",
		"Sleeve Style",
		"State",
		"Shoe Type",
		"Variety/Type",
		"Service Type",
		"Transport Type",
		"Display Type",
		"Cable Length",
		"Band",
		"Compatible Motherboard Brand",
		"Capacity",
		"Number of Pieces",
		"Size Type",
		"Age Group",
		"Status",
		"Width",
		"Heel Type",
		"To Fit",
		"Heel Height",
		"Items Included",
		"Dress Length",
		"Manufacturer"
	);
	private static $_mapEcommerceToEbayItemSpecificSynonyms = array(
		"sex" => "Gender",
		"genero" => "Gender",
	);
	
	public $id;
	public $ecommerceItemSpecificId;
	public $ecommerceItemSpecificName;
	public $ebayItemSpecificId;
	public $customTypedName;
	public $mappedName; //This will be the value used
	
	public function save() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		if(!affinity_empty($this->ebayItemSpecificId)) {
			$this->mappedName = $this->ebayItemSpecificId;
		} 
		else if(!affinity_empty($this->customTypedName)) {
			$this->mappedName = $this->customTypedName;
		}
		else {
			$this->mappedName = $this->ecommerceItemSpecificName;
		}
		
		return AffinityDataLayer::save($this);
	}
	
	public static function saveFromJson($jsonItemSpecificMappingObject) {
		$jsonItemSpecificMappingObject = stripslashes($jsonItemSpecificMappingObject);
		$arrItemSpecific = json_decode($jsonItemSpecificMappingObject);
		
		$obj = new AffinityItemSpecificMapping();
		if (!empty($arrItemSpecific)) {
			foreach($arrItemSpecific as $attributeName => $value) {
				$obj->$attributeName = $value;
			}
		}
		$obj->save();
		return $obj;
	}
	
	public static function getAll() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceItemSpecific.php");
		
		$arrDifferentItemSpecifics = AffinityEcommerceItemSpecific::getAllUsedAttributes();
		$arrItemSpecificMappings = self::getSavedObjectsIndexedByEcommerceItemSpecificID();
		$tax = wc_get_attribute_taxonomies();
		
		foreach($arrDifferentItemSpecifics as $ecommerceItemSpecificId => $objEcommerceItemSpecific) {
			//Item already exists
			if(isset($arrItemSpecificMappings[$ecommerceItemSpecificId]) || affinity_empty($ecommerceItemSpecificId)) {
				if (substr($ecommerceItemSpecificId, 0, 3) == 'pa_') {
					$found = false;
					foreach ($tax as $v) {
						if ('pa_' . $v->attribute_name == $ecommerceItemSpecificId) {
							if (!empty($v->attribute_label)) {
								$arrItemSpecificMappings[$ecommerceItemSpecificId]->ecommerceItemSpecificName = $v->attribute_label;
							}
							$found = true;
							break;
						}
					}
					if (!$found) {
						unset($arrItemSpecificMappings[$ecommerceItemSpecificId]);
					}
				}
				continue;
			}
			
			$objNewMapping = new AffinityItemSpecificMapping();
			$objNewMapping->ecommerceItemSpecificId = $ecommerceItemSpecificId;
			$objNewMapping->ecommerceItemSpecificName = $objEcommerceItemSpecific->label;
			$objNewMapping->ebayItemSpecificId = self::getMatchingEbayItemSpecific($objEcommerceItemSpecific->label);
			$objNewMapping->save();
			
			$ecommerceItemSpecificId = strtolower($objNewMapping->ecommerceItemSpecificId);
			$arrItemSpecificMappings[$ecommerceItemSpecificId] = $objNewMapping;
		}
		
		return $arrItemSpecificMappings;
	}
	
	public static function getMappedNameFor($ecommerceItemSpecificId) {
		require_once(__DIR__ . "/AffinityLog.php");
		
		$ecommerceItemSpecificId = strtolower($ecommerceItemSpecificId);
		
		$arrMappingIndexedByEcommerceItemSpecificID = self::getSavedObjectsIndexedByEcommerceItemSpecificID();
		if (isset($arrMappingIndexedByEcommerceItemSpecificID[$ecommerceItemSpecificId])) {
			$objMappingForItemSpecific = $arrMappingIndexedByEcommerceItemSpecificID[$ecommerceItemSpecificId];
		}
		
		if((!isset($objMappingForItemSpecific)) || affinity_empty($objMappingForItemSpecific) || (affinity_empty($objMappingForItemSpecific->ebayItemSpecificId) && affinity_empty($objMappingForItemSpecific->customTypedName))) {
			return false;
		}
		
		return $objMappingForItemSpecific->mappedName;
	}
	
	public static function getArrEbayItemSpecifics() {
		return self::$_ebayRequiredItemSpecifics;
	}
	
	/*
	 * @return EbayItemSpecificMatchedID or null if nothing satisfactory was found
	 */
	private static function getMatchingEbayItemSpecific($strEcommerceItemSpecificName) {
		//Remove pa_ prefix before matching, if exists
		$prefixToRemove = 'pa_';
		if(substr($strEcommerceItemSpecificName, 0, strlen($prefixToRemove)) === $prefixToRemove) {
			$strEcommerceItemSpecificName = substr($strEcommerceItemSpecificName, strlen($prefixToRemove));
		} 
		
		//Exact Match
		if(self::isStringEbayItemSpecific($strEcommerceItemSpecificName)) {
			return $strEcommerceItemSpecificName;
		}
		
		//Synonyms match
		if(isset(self::$_mapEcommerceToEbayItemSpecificSynonyms[$strEcommerceItemSpecificName])) {
			return self::$_mapEcommerceToEbayItemSpecificSynonyms[$strEcommerceItemSpecificName];
		}
		
		//Fuzzy Logic match
		$arrEbayItemSpecifics = self::getArrEbayItemSpecifics();
		$bestMatchString = 0;
		$bestMatchPercentage = 0;
		foreach($arrEbayItemSpecifics as $strEbayItemSpecific) {
			$percentMatched = 0;
			similar_text($strEcommerceItemSpecificName, $strEbayItemSpecific, $percentMatched);
			
			if($percentMatched > $bestMatchPercentage) {
				$bestMatchString = $strEbayItemSpecific;
				$bestMatchPercentage = $percentMatched;
			}
		}
		
		if($bestMatchPercentage > 75) {
			return $bestMatchString;
		}
		
		//Nothing satisfactory found
		return null;
	}
	
	private static function isStringEbayItemSpecific($str) {
		foreach(self::getArrEbayItemSpecifics() as $strEbayItemSpecific) {
			if($str === $strEbayItemSpecific) {
				return true;
			}
		}
		
		return false;
	}
	
	private static function getSavedObjectsIndexedByEcommerceItemSpecificID() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		if(self::$_cachedMappingsIndexedByEcommerceItemSpecificId === null) {
			self::$_cachedMappingsIndexedByEcommerceItemSpecificId = array();
			
			$arrSavedItemSpecificMappings = AffinityDataLayer::getAll(self::className);
			foreach($arrSavedItemSpecificMappings as $objItemSpecificMapping) {
				$ecommerceItemSpecificId = strtolower($objItemSpecificMapping->ecommerceItemSpecificId);
				
				self::$_cachedMappingsIndexedByEcommerceItemSpecificId[$ecommerceItemSpecificId] = $objItemSpecificMapping;
			}
		}
		
		return self::$_cachedMappingsIndexedByEcommerceItemSpecificId;
	}
	
	
}

<?php
class AffinityItemSpecific {
	const TYPE_LOCAL_CUSTOM_ATTRIBUTE = 'local_custom_attr';
	const TYPE_GLOBAL_CUSTOM_ATTRIBUTE = 'global_custom_attr';
	const TYPE_PRODUCT_FIELD = 'pfield';
	
	private static $_arrEbayProductIdentifiers = array("isbn", "upc", "ean", "brand", "mpn", "vin", "gtin", "epid", "asin");
	
	public $type;
	public $name; //attribute name | field name	
	public $arrValues; //array of Strings
	
	public static function getArrProductItemSpecifics($objEcommerceProduct) {
		return self::transformFromArrEcommerceItemSpecifics($objEcommerceProduct->arrEcommerceItemSpecifics);
	}
	
	public static function getCurrentVariationItemSpecifics($objEcommerceVariationProduct) {
		return self::transformFromArrEcommerceItemSpecifics($objEcommerceVariationProduct->arrCurrentVariationItemSpecifics);
	}
	
	/*
	 * @returns [
	 *		arrNamesAdded = array()
	 *		arrNamesRemoved = array();
	 *		mapValuesAdded['attributeName'] = ['arr', 'values']
	 *		mapValuesRemoved['attributeName'] = ['arr', 'values']
	 *	]
	 */
	public static function compareItemSpecifics($arrOldItemSpecifics, $arrNewItemSpecifics) {
		if(!is_array($arrOldItemSpecifics) || !is_array($arrNewItemSpecifics)) {
			throw new Exception("Values passed to compareItemSpecifics are not arrays!");
		}
		
		$mapOldItemSpecifics = self::transformArrItemSpecificsInFlatMap($arrOldItemSpecifics);
		$mapNewItemSpecifics = self::transformArrItemSpecificsInFlatMap($arrNewItemSpecifics);
		
		$arrNamesAdded = array();
		$arrNamesRemoved = array();
		$mapValuesAdded = array();
		$mapValuesRemoved = array();
		
		/*
		 * Check removed names and values
		 */
		foreach($mapOldItemSpecifics as $oldItemSpecificName => $arrValues) {
			if(!isset($mapNewItemSpecifics[$oldItemSpecificName])) {
				$arrNamesRemoved[$oldItemSpecificName] = $oldItemSpecificName;
				continue;
			}
			
			if(!is_array($arrValues)) {
				continue;
			}
			
			$arrNewValues = $mapNewItemSpecifics[$oldItemSpecificName];
			foreach($arrValues as $oldItemSpecificValue) {
				if(!in_array($oldItemSpecificValue, $arrNewValues, true)) {
					$mapValuesRemoved[$oldItemSpecificValue] = $oldItemSpecificValue;
				}
			}
		}
		
		/*
		 * Check new names and values
		 */
		foreach($mapNewItemSpecifics as $newItemSpecificName => $arrValues) {
			if(!isset($mapOldItemSpecifics[$newItemSpecificName])) {
				$arrNamesAdded[$newItemSpecificName] = $newItemSpecificName;
				continue;
			}
			
			if(!is_array($arrValues)) {
				continue;
			}
			
			$arrOldValues = $mapOldItemSpecifics[$newItemSpecificName];
			foreach($arrValues as $newItemSpecificValue) {
				if(!in_array($newItemSpecificValue, $arrOldValues, true)) {
					$mapValuesAdded[$newItemSpecificValue] = $newItemSpecificValue;
				}
			}
		}
		
		return array(
			'hasChanged' => (count($arrNamesAdded) || count($arrNamesRemoved) || count($mapValuesAdded) || count($mapValuesRemoved)),
			'arrNamesAdded' => $arrNamesAdded,
			'arrNamesRemoved' => $arrNamesRemoved,
			'mapValuesAdded' => $mapValuesAdded,
			'mapValuesRemoved' => $mapValuesRemoved
		);
	}
	
	public static function getAllVariationsItemSpecifics($objEcommerceProduct) {
		return self::transformFromArrEcommerceItemSpecifics($objEcommerceProduct->arrAllVariationDifferentItemSpecifics);
	}
	
	public static function getArrEbayProductIdentifiers($objEcommerceProduct) {
		$return = array();
		$arrItemSpecifics = self::getArrProductItemSpecifics($objEcommerceProduct);
		
		foreach($arrItemSpecifics as $objItemSpecific) {
			$tocomp = AffinityEcommerceItemSpecific::getAttributeTaxonomyLabel($objItemSpecific->name);
			$tocomp = strtolower($tocomp);
			if(in_array($tocomp, self::$_arrEbayProductIdentifiers, true)) {
				$o = clone $objItemSpecific;
				$o->name = $tocomp;
				$return[] = $o;
			}
		}
		
		return $return;
	}
	
	public static function getArrEbayProductIdentifiersFromVariation($objEcommerceProduct) {
		$return = array();
		$arrItemSpecifics = self::getCurrentVariationItemSpecifics($objEcommerceProduct);
		
		foreach($arrItemSpecifics as $objItemSpecific) {
			$tocomp = AffinityEcommerceItemSpecific::getAttributeTaxonomyLabel($objItemSpecific->name);
			$tocomp = strtolower($tocomp);
			if(in_array($tocomp, self::$_arrEbayProductIdentifiers, true)) {
				$o = clone $objItemSpecific;
				$o->name = $tocomp;
				$return[] = $o;
			}
		}
		
		return $return;
	}
	
	public static function isThereEmptyValuesOn($arrItemSpecifics) {
		if(!is_array($arrItemSpecifics)) {
			return false;
			//throw new Exception("Array Item Specifics passed to isThereEmptyValuesOn is not an array");
		}
		
		foreach($arrItemSpecifics as $objItemSpecific) {
			foreach($objItemSpecific->arrValues as $value) {
				if(affinity_empty($value)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	public static function transformFromArrEcommerceItemSpecifics($arrEcommerceItemSpecifics) {
		$return = array();
		
		if(!is_array($arrEcommerceItemSpecifics)) {
			return $return;
		}
		
		$noauto = get_option('ebayaffinity_noautoattributes');
		
		require_once(__DIR__ . "/AffinityItemSpecificMapping.php");
		foreach($arrEcommerceItemSpecifics as $k=>$objEcommerceItemSpecific) {
			$attributeName = AffinityItemSpecificMapping::getMappedNameFor($objEcommerceItemSpecific->name);

			if ($attributeName === false) {
				$attributeName = $objEcommerceItemSpecific->name;
				if (!empty($noauto)) {
					continue;
				}
			}
			
			if(!isset($return[$attributeName])) {
				$objItemSpecific = new AffinityItemSpecific();
				$objItemSpecific->name = $attributeName;
				$objItemSpecific->arrValues = is_array($objEcommerceItemSpecific->arrValues) ? $objEcommerceItemSpecific->arrValues : array();
				
				$return[$attributeName] = $objItemSpecific;
			}
			//If it already exists, merge values
			else {
				$objItemSpecific = $return[$attributeName];
				$objItemSpecific->arrValues = array_unique(array_merge($objItemSpecific->arrValues, $objEcommerceItemSpecific->arrValues));
			}
		}
		
		return $return;
	}
	
	public static function mergeArrItemSpecifics($arrItemSpecifics, $arrItemSpecifics2) {
		require_once(__DIR__ . "/AffinityLog.php");
		
		if(!is_array($arrItemSpecifics) || !is_array($arrItemSpecifics2)) {
			throw new Exception("One or more parameters supplied to mergeArrItemSpecifics are not arrays!");
		}
		
		$return = $arrItemSpecifics;
		
		$mapItemSpecifics = self::transformArrItemSpecificsInMap($arrItemSpecifics);
		$mapItemSpecifics2 = self::transformArrItemSpecificsInMap($arrItemSpecifics2);
		
		foreach($mapItemSpecifics2 as $attributeName2 => $objItemSpecific2) {
			if(!isset($mapItemSpecifics[$attributeName2])) {
				$return[] = $objItemSpecific2;
			}
			else {
				$objItemSpecific = $mapItemSpecifics[$attributeName2];
				$mergedArray = array_merge($objItemSpecific->arrValues, $objItemSpecific2->arrValues);
				$objItemSpecific->arrValues = array_values( array_unique($mergedArray) );
			}
		}
		
		return $return;
	}
	
	private static function transformArrItemSpecificsInFlatMap($arrItemSpecifics) {
		$return = array();
		
		foreach($arrItemSpecifics as $objItemSpecific) {
			$return[$objItemSpecific->name] = $objItemSpecific->arrValues;
		}
		
		return $return;
	}
	
	private static function transformArrItemSpecificsInMap($arrItemSpecifics) {
		$return = array();
		
		foreach($arrItemSpecifics as $objItemSpecific) {
			$return[$objItemSpecific->name] = $objItemSpecific;
		}
		
		return $return;
	}
	
}


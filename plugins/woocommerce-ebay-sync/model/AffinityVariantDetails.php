<?php

class AffinityVariantDetails {
	public $variantProductId;
	public $ebaySku;
	public $arrItemSpecifics;
	public $dateStarted;
	public $dateEnded;
	
	public static function createNewForVariant($objVariantProduct, $forceNewSku = false) {
		require_once(__DIR__ . "/AffinityLog.php");
		require_once(__DIR__ . "/AffinityProduct.php");
		
		$obj = new AffinityVariantDetails();
		$obj->variantProductId = $objVariantProduct->id;
		$obj->arrItemSpecifics = $objVariantProduct->arrItemSpecificsOfCurrentVariation;
		$obj->dateStarted = date("d/m/Y H:i:s");  
		$obj->ebaySku = (!empty($objVariantProduct->sku) && !$forceNewSku) ? $objVariantProduct->sku : $objVariantProduct->getNewSku();
		
		return $obj;
	}
	
	public function reactivate() {
		$this->dateEnded = null;  
	}
	
	public function deactivate() {
		$this->dateEnded = date("d/m/Y H:i:s");  
	}
	
	public function matches($arrItemSpecifics) {
		if(!is_array($this->arrItemSpecifics) || !is_array($arrItemSpecifics)) {
			return false;
		}
		
		if(count($arrItemSpecifics) !== count($this->arrItemSpecifics)) {
			return false;
		}
		
		foreach($this->arrItemSpecifics as $objItemSpecific) {
			$nameExist = false;
			foreach($arrItemSpecifics as $objItemSpecificToCompare) {
				if($objItemSpecific->name === $objItemSpecificToCompare->name) {
					$nameExist = true;
					
					//Compare Values
					$arrValuesDifferenceForth = array_diff($objItemSpecific->arrValues, $objItemSpecificToCompare->arrValues);
					$arrValuesDifferenceBack = array_diff($objItemSpecificToCompare->arrValues, $objItemSpecific->arrValues);
					if(count($arrValuesDifferenceForth) > 0 || count($arrValuesDifferenceBack) > 0) {
						return false;
					}
					
					break;
				}
			}
				
			if(!$nameExist) {
				return false;
			}
		}
		
		return true;
	}
	
	
}

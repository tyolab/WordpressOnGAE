<?php
require_once(__DIR__ . "/AffinityLog.php");
require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceProduct.php");

class AffinityProduct {
	const UPDATE_STATUS_NEEDS_TO_BE_SAVED = 1;
	const UPDATE_STATUS_NEEDS_TO_BE_DELETED = 2;
	const UPDATE_STATUS_SYNC_ERROR = 3;
	const UPDATE_STATUS_NOT_ENOUGH_INFORMATION_TO_SYNC = 4;
	const UPDATE_STATUS_LISTING_TERMINATED = 5;
	const UPDATE_STATUS_LISTING_NEVER_PROCESSED = 6;
	const UPDATE_STATUS_SYNCHRONISED = 7;

	const SKU_PREFIX = "woobay-";

	const MINIMUM_RECOMMENDED_IMAGE_WIDTH = 500;
	const MINIMUM_RECOMMENDED_IMAGE_HEIGHT = 500;

	const OPTION_ARRAY_INVENTORY_SUMMARY = "affinity_inventory_summary";
	const OPTION_UPDATING_INV_SUMMARY_EXECUTING = "affinity_update_inv_executing";

	const DELETING_ALL_LISTINGS_FROM_ACCOUNT_OPTION = "affinityDeletingAllListings";

	private static $_arrPersistentFields = array(
		"ebayCategoryId" => "affinity_ebaycategory",
		"ebaySuggestedCategoryId" => "affinity_suggestedCatId",
		"sku" => "affinity_prod_ebay_sku",
		"ebayListingID" => "affinity_ebayitemid",
		"jsonEncodedArrServerWarnings" => "affinity_prod_arr_adaptation_warnings",
		"jsonEncodedArrServerErrors" => "affinity_prod_arr_adaptation_errors",
		"jsonEncodedArrClientWarnings" => "affinity_prod_arr_client_warnings",
		"jsonEncodedArrClientErrors" => "affinity_prod_arr_client_errors",
		"arrAllVariantDetails" =>  "affinity_prod_all_variants",
		"storedAllVariantDetails" => "affinity_prod_all_active_variants",
		"storedAllInactiveVariantDetails" => "affinity_prod_all_inactive_variants",
		"storedVariantDetails" => "affinity_prod_active_variants",
		"storedArrInactiveVariantDetails" => "affinity_prod_inactive_variants",
		"updateStatus" => "affinity_prod_update_status",
		"updateFailureCount" => "affinity_prod_update_failure_count",
		"lastSuccessfulUpdate" => "affinity_prod_last_successful_update",
		"shouldNotBeSentToEbay" => "affinity_block",
	);

	private static $_arrPersistentFieldsDefaults = array(
		"ebayCategoryId" => null,
		"ebaySuggestedCategoryId" => null,
		"ebayListingID" => null,
		"sku" => null,
		"jsonEncodedArrServerWarnings" => array(),
		"jsonEncodedArrServerErrors" => array(),
		"jsonEncodedArrClientWarnings" => array(),
		"jsonEncodedArrClientErrors" => array(),
		"arrAllVariantDetails" => array(),
		"storedAllVariantDetails" => null,
		"storedAllInactiveVariantDetails" => array(),
		"storedVariantDetails" => null,
		"storedArrInactiveVariantDetails" => array(),
		"updateStatus" => self::UPDATE_STATUS_LISTING_NEVER_PROCESSED,
		"updateFailureCount" => 0,
		"lastSuccessfulUpdate" => "Never",
		"shouldNotBeSentToEbay" => false,
	);

	private static $_arrWarningMessagesToIgnore = array(
		"Seller is not eligible to list a discounted item.",
		"PayPal may delay the settlement of funds to ensure smooth transactions.",
	);

	private static $_mapWarningMessagesToTranslate = array(
		"The email address you entered isn't linked to a PayPal account. If you don't have a PayPal account, you'll need to set one up with this address so that buyers can pay you. (You can set up your account after your item sells)."
		=>
		"Please log in to eBay and link your account to Paypal."
	);

	private static $_arrErrorMessagesToIgnore = array(
		"Unregistered users or users who have not completed their registration can not complete this request.",
		"None of the requested products were created or updated",
	);

	private static $_mapErrorMessagesToTranslate = array(
		"Input data for tag <Item.PictureDetails.PictureURL> is invalid or missing. Please check API documentation." => "Invalid Picture URL!",
		"An unexpected error occurred" => "Unexpected Error returned by the server!",
		'__pattern' => array(
			'/The tags (?<tagname>.*) is\/are disabled as Variant/' => 'The attribute name(s) "_" is not allowed to have variants in this category. Please change the attribute name & sync again.',
			'/.*The specified UUID has already been used.*/' => 'Re-listing this item has been temporarily delayed. This item should be automatically re-listed soon.',
			'/.*Condition is required for this category.*/' => 'The item condition you selected is not available in this eBay category.',
			'/.*Server returned an unexpected error \(http response code: 404\).*/' => 'Product sync has been delayed. This issue should be resolved automatically.'
		)
	);

    public $id;

    public $title;
    public $description;

    public $objMainImage;
    public $arrObjAdditionalImages;

    public $price;

	public $isStockBeingManaged;
    public $qtyAvailable;
	public $sku;

    public $ebayCategoryId;
    public $ebaySuggestedCategoryId;

    public $shippingPolicy;

    public $arrObjItemSpecifics;
    public $arrObjItemSpecificsEbayProductIdentifiers;

	public $isMultiVariationProduct;
	public $variationObjMainProduct;
	public $arrVariationProducts;
	public $arrAllVariantDetails;
	public $arrItemSpecificsOfCurrentVariation = array();
	public $arrAllVariationsItemSpecifics = array();
	public $arrAllVariationsItemSpecificsIncludingInactive = array();
	public $storedAllVariantDetails = array();
	public $storedAllInactiveVariantDetails = array();
	public $storedVariantDetails = null;
	public $storedArrInactiveVariantDetails = null;
	public $tempAllVariantDetails = array();
	public $tempAllInactiveVariantDetails = array();
	public $tempVariantDetails = null;
	public $tempArrInactiveVariantDetails = null;

	public $ebayListingID;

    public $jsonEncodedArrClientWarnings;
    public $jsonEncodedArrServerWarnings;
    public $jsonEncodedArrClientErrors;
    public $jsonEncodedArrServerErrors;

	public $shouldNotBeSentToEbay;

	public $updateStatus; //uses const UPDATE_STATUS_[CURRENT_STATUS]
	public $updateFailureCount = 0;
	public $lastSuccessfulUpdate;

	public $objEcommerceProduct;


	/*
	 * Persistence Methods
	 */
	public static function get($id) {
		$objEcommerceProduct = AffinityEcommerceProduct::get($id);
		$objAffinityProduct = self::transformFromEcommerceProduct($objEcommerceProduct);

		return $objAffinityProduct;
	}

	public static function getProductIdAssociatedToListing($ebayListingId) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::findObjectIdWithGivenData(self::$_arrPersistentFields['ebayListingID'], $ebayListingId);
	}

	public static function getProductIdAssociatedToSku($sku) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");

		$productId = AffinityDataLayer::findObjectIdWithGivenData(self::$_arrPersistentFields['sku'], $sku);
		if(!empty($productId)) {
			return $productId;
		}

		return str_replace(self::SKU_PREFIX, "", $sku);
	}

	public static function getAll($justPublishedItems = false) {
		$return = array();

		if(!$justPublishedItems) {
			$arrEcommerceProducts = AffinityEcommerceProduct::getAll();
		}
		else {
			$arrEcommerceProducts = AffinityEcommerceProduct::getAll(array('post_status' => 'publish'));
		}

		foreach($arrEcommerceProducts as $objEcommerceProduct) {
			$objAffinityProduct = self::transformFromEcommerceProduct($objEcommerceProduct);
			$return[] = $objAffinityProduct;
		}

		return $return;
	}

	private static function saveProductAttribute($productId, $attributeName, $attributeValue) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		AffinityDataLayer::saveDataAssociatedToEcommerceObject($productId, self::$_arrPersistentFields[$attributeName], $attributeValue);
	}
	private static function deleteProductAttribute($productId, $attributeName) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		AffinityDataLayer::deleteDataAssociatedToEcommerceObject($productId, self::$_arrPersistentFields[$attributeName]);
	}


	/*
	 * Product Lifecycle Hooks
	 */
	public static function productsWereChanged($arrProductIds) {
		foreach($arrProductIds as $productId) {
			$objEcommerceProduct = AffinityEcommerceProduct::get($productId);
			self::productWasPublished($objEcommerceProduct);
		}
	}

	public static function productWasPublished($objEcommerceProduct, $toebay=false) {
		$updatedAffinityProduct = self::transformFromEcommerceProduct($objEcommerceProduct);
		$updatedAffinityProduct->updateStatus = self::UPDATE_STATUS_NEEDS_TO_BE_SAVED;

		if(!$updatedAffinityProduct->shouldBeSynchronisedToEbay()) {
			return;
		}
		self::saveProductAttribute($updatedAffinityProduct->id, "updateStatus", $updatedAffinityProduct->updateStatus);
		if ($toebay) {
			self::saveListing($updatedAffinityProduct);
		}
	}

	public static function productWasUnpublished($objEcommerceProduct, $toebay=false) {
		$updatedAffinityProduct = self::transformFromEcommerceProduct($objEcommerceProduct);

		if(empty($updatedAffinityProduct->ebayListingID)) {
			$updatedAffinityProduct->updateStatus = self::UPDATE_STATUS_LISTING_NEVER_PROCESSED;
			self::saveProductAttribute($updatedAffinityProduct->id, "updateStatus", $updatedAffinityProduct->updateStatus);
			return;
		}

		self::saveProductAttribute($updatedAffinityProduct->id, "updateStatus", 1);
		if ($toebay) {
			self::endListing($updatedAffinityProduct);
		}
	}

	/*
	 * eBay Synchronisation
	 */
	public static function saveListing($objAffinityProduct) {
		require_once(__DIR__ . "/../service/AffinityBackendService.php");

		if(!$objAffinityProduct->isMultiVariationProduct) {
			AffinityBackendService::saveListing($objAffinityProduct);
		}
		else {
			AffinityBackendService::saveMultiVariationListing($objAffinityProduct->variationObjMainProduct);
		}
	}

	public static function listingReturnReceived($sku, $ebayListingId, $arrWarnings, $arrErrors) {
		$productId = self::getProductIdAssociatedToSku($sku);

		require_once(__DIR__ . "/AffinityLog.php");
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Processing Product Return", "Sku: $sku - Listing ID: $ebayListingId - Warnings: " . print_r($arrWarnings, true) . " - Errors: " . print_r($arrErrors, true));

		if(empty($ebayListingId) && count($arrErrors) < 1) {
			$arrErrors = array("The API hasn't returned any error, however a Listing ID wasn't provided!");
		}

		if(!empty($ebayListingId)) {
			self::saveProductAttribute($productId, "ebayListingID", $ebayListingId);
		}

		$status = self::UPDATE_STATUS_SYNCHRONISED;
		foreach ($arrErrors as $error) {
			if (strpos($error, 'The specified UUID has been already used') !== false) {
				$status = self::UPDATE_STATUS_NEEDS_TO_BE_SAVED;
				break;
			} else if (strpos($error, 'Server returned an unexpected error (http response code: 404).') !== false) {
				$status = self::UPDATE_STATUS_NEEDS_TO_BE_SAVED;
				break;
			}
		}

		self::overwriteProductExistingServerWarnings($productId, $arrWarnings);
		self::overwriteProductExistingServerErrors($productId, $arrErrors);
		self::saveProductAttribute($productId, "updateStatus", $status);
		self::saveProductAttribute($productId, "updateFailureCount", 0);
		self::saveProductAttribute($productId, "lastSuccessfulUpdate", date("d/m/Y H:i:s"));

		if ((!empty($ebayListingId))) {
			$sp = get_option('ebayaffinity_successposts');
			if (empty($sp)) {
				require_once(__DIR__.'/../service/AffinityEmail.php');
				AffinityEmail::products($ebayListingId);
				$sp = 0;
			}
			$sp++;
			update_option('ebayaffinity_successposts', $sp);
		}

		return $productId;
	}

	public static function multiVariationListingReturnReceived($objAffinityProduct, $ebayListingId, $arrWarnings, $arrErrors, $isMultiVariation = false) {
		$productId = self::listingReturnReceived($objAffinityProduct->sku, $ebayListingId, $arrWarnings, $arrErrors);

		if(!empty($ebayListingId)) {
			$objAffinityProduct->saveMultiVariationTempVariants();
		}
	}

	public static function endListing($objAffinityProduct) {
		require_once(__DIR__ . "/../service/AffinityBackendService.php");

		if(!$objAffinityProduct->isMultiVariationProduct) {
			AffinityBackendService::endListing($objAffinityProduct);
		}
		else {
			AffinityBackendService::endMultiVariationListing($objAffinityProduct->variationObjMainProduct);
		}
	}

	public static function endAllListingsFromAccountAsync() {
		require_once(__DIR__ . "/../service/AffinityBackendService.php");

		$commandWasSuccessfullyReceivedByServer = AffinityBackendService::deleteAllListings(); //delete all listings and then when it's finished, wipe data
		if($commandWasSuccessfullyReceivedByServer) {
			AffinityDataLayer::saveOption(self::DELETING_ALL_LISTINGS_FROM_ACCOUNT_OPTION, true);
		}
	}

	public static function isDeletingAllListingsInProgress() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::getOption(self::DELETING_ALL_LISTINGS_FROM_ACCOUNT_OPTION);
	}

	public static function endAllListingsHasFinished() {
		require_once(__DIR__ . "/../service/AffinityBackendService.php");

		if(!self::isDeletingAllListingsInProgress()) {
			return true;
		}

		$hasFinished = AffinityBackendService::checkDeleteAllListingsHasFinished();
		if($hasFinished) {
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
			AffinityDataLayer::saveOption(self::DELETING_ALL_LISTINGS_FROM_ACCOUNT_OPTION, false);
			AffinityDataLayer::wipeAllAffinityData();
			return true;
		}

		return false;
	}

	public static function listingWasEnded($sku, $arrWarnings, $arrErrors) {
		$productId = self::getProductIdAssociatedToSku($sku);

		self::deleteProductAttribute($productId, "ebayListingID");
		self::saveProductAttribute($productId, "jsonEncodedArrServerWarnings", addslashes(json_encode($arrWarnings)));
		self::saveProductAttribute($productId, "jsonEncodedArrServerErrors", addslashes(json_encode($arrErrors)));
		self::saveProductAttribute($productId, "updateStatus", self::UPDATE_STATUS_SYNCHRONISED);
		self::saveProductAttribute($productId, "updateFailureCount", 0);
		self::saveProductAttribute($productId, "lastSuccessfulUpdate", date("d/m/Y H:i:s"));
	}

	public static function multiVariationListingWasEnded($objMainVariationProduct, $arrWarnings, $arrErrors) {
		$productId = $objMainVariationProduct->id;

		self::deleteProductAttribute($productId, "ebayListingID");
		self::saveProductAttribute($productId, "jsonEncodedArrServerWarnings", addslashes(json_encode($arrWarnings)));
		self::saveProductAttribute($productId, "jsonEncodedArrServerErrors", addslashes(json_encode($arrErrors)));
		self::saveProductAttribute($productId, "updateStatus", self::UPDATE_STATUS_SYNCHRONISED);
		self::saveProductAttribute($productId, "updateFailureCount", 0);
		self::saveProductAttribute($productId, "lastSuccessfulUpdate", date("d/m/Y H:i:s"));

		//Reset Attributes
		require_once(__DIR__ . "/../model/AffinityLog.php");
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Deleting SKU ghosts from product $productId", "Deleting SKU ghosts from product $productId");

		foreach($objMainVariationProduct->arrAllVariantDetails as $objVariantDetails) {
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Deleting SKU ghosts from variation $objVariantDetails->variantProductId", "Deleting SKU ghosts from variation $objVariantDetails->variantProductId");
			self::deleteProductAttribute($objVariantDetails->variantProductId, "arrAllVariantDetails");
			self::deleteProductAttribute($objVariantDetails->variantProductId, "storedAllVariantDetails");
			self::deleteProductAttribute($objVariantDetails->variantProductId, "storedAllInactiveVariantDetails");
			self::deleteProductAttribute($objVariantDetails->variantProductId, "storedVariantDetails");
			self::deleteProductAttribute($objVariantDetails->variantProductId, "storedArrInactiveVariantDetails");
		}

		foreach($objMainVariationProduct->arrVariationProducts as $obj) {
			$obj->getNewSku();
		}
		$objMainVariationProduct->ebayListingID = '';
		$objMainVariationProduct->getNewSku();

		self::deleteProductAttribute($productId, "arrAllVariantDetails");
		self::deleteProductAttribute($productId, "storedAllVariantDetails");
		self::deleteProductAttribute($productId, "storedAllInactiveVariantDetails");
		self::deleteProductAttribute($productId, "storedVariantDetails");
		self::deleteProductAttribute($productId, "storedArrInactiveVariantDetails");
	}

	public static function syncInventory() {
		require_once(__DIR__ . "/../service/AffinityBackendService.php");

		$arrAffinityProducts = self::getAll();

		$productsToBeSynced = array();
		echo "Synchronising products: ";
		foreach($arrAffinityProducts as $objAffinityProduct) {
			$objAffinityProduct->updateStatus = self::UPDATE_STATUS_NEEDS_TO_BE_SAVED;

			if($objAffinityProduct->shouldBeSynchronisedToEbay()) {
				echo $objAffinityProduct->id . "; ";
				$productsToBeSynced[] = $objAffinityProduct;
			}
		}

		AffinityBackendService::syncInventory($productsToBeSynced);
	}

	private function shouldBeSynchronisedToEbay() {
		if($this->shouldNotBeSentToEbay) {
			return false;
		}

		if($this->updateStatus === null) {
			$this->updateStatus = self::UPDATE_STATUS_LISTING_NEVER_PROCESSED;
			self::saveProductAttribute($this->id, "updateStatus", $this->updateStatus);
		}

		if($this->hasClientSideErrors()) {
			$this->updateStatus = self::UPDATE_STATUS_NOT_ENOUGH_INFORMATION_TO_SYNC;
			self::saveProductAttribute($this->id, "updateStatus", $this->updateStatus);
			return false;
		}
		else if($this->updateStatus == self::UPDATE_STATUS_NOT_ENOUGH_INFORMATION_TO_SYNC) {
			$this->updateStatus = self::UPDATE_STATUS_NEEDS_TO_BE_SAVED;
			self::saveProductAttribute($this->id, "updateStatus", $this->updateStatus);
		}

		if( $this->updateStatus != self::UPDATE_STATUS_LISTING_NEVER_PROCESSED &&
			$this->updateStatus != self::UPDATE_STATUS_NEEDS_TO_BE_SAVED &&
			$this->updateStatus != self::UPDATE_STATUS_NEEDS_TO_BE_DELETED
		)  {
			return false;
		}

		return true;
	}

	private function isValidProduct() {
		if($this->shouldNotBeSentToEbay) {
			return false;
		}

		if($this->hasClientSideErrors()) {
			return false;
		}

		return true;
	}

	/*
	 * Item Transformation
	 */
	public function getObjEbayCategory() {
		require_once(__DIR__ . "/AffinityEbayCategory.php");
		return AffinityEbayCategory::getProductEbayCategory($this);
	}

	private function getItemSpecifics($objEcommerceProduct) {
		require_once(__DIR__ . "/AffinityItemSpecific.php");
		return AffinityItemSpecific::getArrProductItemSpecifics($objEcommerceProduct);
	}

	private function getEbayProductIdentifiers($objEcommerceProduct) {
		require_once(__DIR__ . "/AffinityItemSpecific.php");
		return AffinityItemSpecific::getArrEbayProductIdentifiers($objEcommerceProduct);
	}

	/*
	 * Variations
	 */
	private function getVariations($objEcommerceProduct) {
		if(!is_array($objEcommerceProduct->arrEcommerceProductVariations) || count($objEcommerceProduct->arrEcommerceProductVariations) < 1) {
			$this->isMultiVariationProduct = false;
			return;
		}

		require_once(__DIR__ . "/AffinityItemSpecific.php");

		$this->isMultiVariationProduct = true;
		$this->variationObjMainProduct = $this;
		$this->tempAllInactiveVariantDetails = $this->storedAllInactiveVariantDetails;
		$this->price = 0;

		foreach($objEcommerceProduct->arrEcommerceProductVariations as $objEcommerceVariationProduct) {
			$objVariationProduct = self::transformFromEcommerceProduct($objEcommerceVariationProduct, true);
			$objVariationProduct->isMultiVariationProduct = true;
			$objVariationProduct->variationObjMainProduct = $this;

			$objVariationProduct->title = !affinity_empty($objVariationProduct->title) ? $objVariationProduct->title : $this->title;
			$objVariationProduct->description = !affinity_empty($objVariationProduct->description) ? $objVariationProduct->description : $this->description;
			$objVariationProduct->ebayCategoryId = !empty($this->ebayCategoryId) ? $this->ebayCategoryId : $this->getEbayMappedCategoryId();
			$objVariationProduct->price = !empty($objVariationProduct->price) && $objVariationProduct->price > 0 ? $objVariationProduct->price : $this->price;
			$objVariationProduct->variationObjMainProduct->price += $objVariationProduct->price;
			$objVariationProduct->arrObjItemSpecificsEbayProductIdentifiers = AffinityItemSpecific::getArrEbayProductIdentifiersFromVariation($objEcommerceVariationProduct);

			//Handle Changes to Variant Item Specifics
			$objVariationProduct->arrItemSpecificsOfCurrentVariation = AffinityItemSpecific::getCurrentVariationItemSpecifics($objEcommerceVariationProduct);
			$objVariationProduct->getItemSpecificChangesOfVariantProduct();

			$this->arrVariationProducts[] = $objVariationProduct;
		}

		/*
		 * Remove any active variant from inactive array
		 */
		foreach($this->tempAllInactiveVariantDetails as $key => $objInactiveVariant) {
			foreach($this->tempAllVariantDetails as $objActiveVariant) {
				if($objActiveVariant->matches($objInactiveVariant)) {
					unset($this->tempAllInactiveVariantDetails[$key]);
				}
			}
		}

		$this->arrAllVariationsItemSpecifics = AffinityItemSpecific::getAllVariationsItemSpecifics($objEcommerceProduct);
		$this->arrAllVariationsItemSpecificsIncludingInactive = $this->getAllVariationsItemSpecificsIncludingInactive();

		$this->arrAllVariantDetails = $this->getAllVariantDetails();
		self::saveProductAttribute($this->id, "arrAllVariantDetails", $this->arrAllVariantDetails);
	}

	private static function isThereActiveDuplicateVariant($objMainVariation) {
		//Active Duplicates
		$arrVariationsWithoutErrors = $objMainVariation->getVariationsShouldBeSynchronisedToEbay();
		foreach($arrVariationsWithoutErrors as $objVariantProduct) {
			foreach($arrVariationsWithoutErrors as $objVariantProductToCompare) {
				if($objVariantProduct == $objVariantProductToCompare) {
					continue;
				}

				$mapComparison = AffinityItemSpecific::compareItemSpecifics($objVariantProduct->arrItemSpecificsOfCurrentVariation, $objVariantProductToCompare->arrItemSpecificsOfCurrentVariation);
				if($mapComparison['hasChanged'] !== true) {
					return true;
				}
			}
		}

		return false;
	}

	private static function isThereInactiveDuplicateVariant($objMainVariation) {
		$arrVariationsWithoutErrors = $objMainVariation->getVariationsShouldBeSynchronisedToEbay();

		$arrInactiveVariantDetails = $objAffinityProduct->tempAllInactiveVariantDetails;
		$arrDeletedVariantDetails = $objAffinityProduct->getDeletedVariantDetails();

		$arrInactiveAndDeletedVariantsDetails = array_merge($arrInactiveVariantDetails, $arrDeletedVariantDetails);

		foreach($arrInactiveAndDeletedVariantsDetails as $objInactiveVariantDetails) {
			foreach($arrVariationsWithoutErrors as $objVariantProduct) {
				if($objInactiveVariantDetails->matches($objVariantProduct->arrItemSpecificsOfCurrentVariation)) {
					return true;
				}
			}
		}

		return false;
	}

	public function getArrMultiVariationsSkus() {
		$return = array();

		$arrVariationsWithoutErrors = $this->getVariationsShouldBeSynchronisedToEbay();
		foreach($arrVariationsWithoutErrors as $objVariantProduct) {
			$return[$objVariantProduct->sku] = $objVariantProduct->sku;
		}

		$arrInactiveVariantDetails = $this->tempAllInactiveVariantDetails;
		$arrDeletedVariantDetails = $this->getDeletedVariantDetails();
		$arrInactiveAndDeletedVariantsDetails = array_merge($arrInactiveVariantDetails, $arrDeletedVariantDetails);
		foreach($arrInactiveAndDeletedVariantsDetails as $objVariantDetails) {
			$return[$objVariantDetails->ebaySku] = $objVariantDetails->ebaySku;
		}

		return array_values($return);
	}

	public function getAllVariantDetails() {
		require_once(__DIR__ . "/AffinityLog.php");
		require_once(__DIR__ . "/AffinityItemSpecific.php");

		$return = $this->arrAllVariantDetails;

		foreach($this->arrAllVariantDetails as $objVariantDetails) {
			$objExistingProductVariant = null;
			foreach($this->arrVariationProducts as $objVariantProduct) {
				if((!empty($objVariantDetails)) && $objVariantDetails->matches($objVariantProduct->arrItemSpecificsOfCurrentVariation)) {
					$objExistingProductVariant = $objVariantProduct;
					break;
				}
			}

			if($objExistingProductVariant === null) {
				require_once(__DIR__ . "/AffinityVariantDetails.php");
				if (!empty($objVariantDetails)) {
					$objVariantDetails->deactivate();
				}
			}
		}

		foreach($this->arrVariationProducts as $objVariantProduct) {
			$objExistingVariant = null;
			foreach($this->arrAllVariantDetails as $objVariantDetails) {
				if((!empty($objVariantDetails)) && $objVariantDetails->matches($objVariantProduct->arrItemSpecificsOfCurrentVariation)) {
					$objExistingVariant = $objVariantDetails;
					break;
				}
			}

			if($objExistingVariant === null && !AffinityItemSpecific::isThereEmptyValuesOn($objVariantProduct->arrItemSpecificsOfCurrentVariation)) {
				require_once(__DIR__ . "/AffinityVariantDetails.php");
				$objNewVariantDetails = AffinityVariantDetails::createNewForVariant($objVariantProduct);
				AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Creating new Variant on All Variant Details",  print_r($objNewVariantDetails, true));
				$return[] = $objNewVariantDetails;
			}
		}

		return $return;
	}

	private function getAllVariationsItemSpecificsIncludingInactive() {
		require_once(__DIR__ . "/AffinityLog.php");

		$return = $this->arrAllVariationsItemSpecifics;

		if(!isset($this->tempAllInactiveVariantDetails) || !is_array($this->tempAllInactiveVariantDetails)) {
			return $return;
		}

		foreach($this->tempAllInactiveVariantDetails as $objVariantDetail) {
			if (!empty($objVariantDetail->arrItemSpecifics)) {
				$return = AffinityItemSpecific::mergeArrItemSpecifics($return, $objVariantDetail->arrItemSpecifics);
			}
		}

		foreach($this->arrAllVariantDetails as $objVariantDetail) {
			if (!empty($objVariantDetail->arrItemSpecifics)) {
				$return = AffinityItemSpecific::mergeArrItemSpecifics($return, $objVariantDetail->arrItemSpecifics);
			}
		}

		/*
		 * Remove empty values, if any
		 */
		foreach($return as $objItemSpecific) {
			if(!is_array($objItemSpecific->arrValues)) {
				continue;
			}

			foreach($objItemSpecific->arrValues as $key => $itemSpecificValue) {
				if(affinity_empty($itemSpecificValue)) {
					unset($objItemSpecific->arrValues[$key]);
				}
			}
		}

		return $return;
	}

	/*
	 * Checks changes to the current object properties and store the active and inactive variants based on that
	 */
	private function getItemSpecificChangesOfVariantProduct() {
		require_once(__DIR__ . "/AffinityLog.php");
		require_once(__DIR__ . "/AffinityVariantDetails.php");

		$existingVariantDetail = $this->storedVariantDetails;
		$this->tempArrInactiveVariantDetails = $this->storedArrInactiveVariantDetails;
		$varientDetails = get_post_meta($this->variationObjMainProduct->id, '_affinity_prod_all_variants');

		$newVariantNeedsToBeCreated = false;

		//New Object
		if($this->storedVariantDetails === null || !is_array($this->storedVariantDetails->arrItemSpecifics)) {
			$existingVariantDetail = AffinityVariantDetails::createNewForVariant($this);
			/*
			 * Check if there's an existing arrItemSpecifics that matches the current one
			 */

			if (!empty($varientDetails)) {
				if (is_array($existingVariantDetail->arrItemSpecifics)) {
					foreach($varientDetails as $objAllVariantDetailses) {
						foreach($objAllVariantDetailses as $objAllVariantDetails) {
							if((!empty($objAllVariantDetails)) && $objAllVariantDetails->matches($existingVariantDetail->arrItemSpecifics)) {
								$existingVariantDetail = $objAllVariantDetails;
								$existingVariantDetail->reactivate();
								$this->getNewSku($existingVariantDetail->ebaySku);
							}
						}
					}
				}
			}
		}
		else {
			require_once(__DIR__ . "/AffinityItemSpecific.php");
			$mapVariationChanges = AffinityItemSpecific::compareItemSpecifics($this->storedVariantDetails->arrItemSpecifics, $this->arrItemSpecificsOfCurrentVariation);

			if($mapVariationChanges['hasChanged'] === true) {
				/*
				 * Check if there's an inactive arrItemSpecifics that matches the current one
				 */
				$existingVariantDetail = null;
				foreach($this->tempArrInactiveVariantDetails as $key => $objInactiveVariantDetails) {
					if((!empty($objInactiveVariantDetails)) && $objInactiveVariantDetails->matches($this->arrItemSpecificsOfCurrentVariation)) {
						$existingVariantDetail = $objInactiveVariantDetails;
						$existingVariantDetail->reactivate();
						$this->getNewSku($existingVariantDetail->ebaySku);

						unset($this->tempArrInactiveVariantDetails[$key]);

						break;
					}
				}

				//Deactivate current variation
				$oldVariantDetails = $this->storedVariantDetails;
				$oldVariantDetails->deactivate();

				$this->tempArrInactiveVariantDetails[] = $oldVariantDetails;
				$this->variationObjMainProduct->tempAllInactiveVariantDetails[] = $oldVariantDetails;

				if($existingVariantDetail === null) {
					$existingDetailOnAllVariations = self::getExistingVariantOnAllVariations($this->variationObjMainProduct, $this->arrItemSpecificsOfCurrentVariation);

					if($existingDetailOnAllVariations === null) {
						$existingVariantDetail = AffinityVariantDetails::createNewForVariant($this, true);
					}
					else {
						$existingVariantDetail = $existingDetailOnAllVariations;
					}
				}
			}
		}

		$this->tempVariantDetails = $existingVariantDetail;
		$this->variationObjMainProduct->tempAllVariantDetails[] = $existingVariantDetail;

		/*
		 * Add inactive items to array
		 */
		foreach($this->tempArrInactiveVariantDetails as $objInactiveVariantDetails) {
			$this->variationObjMainProduct->tempAllInactiveVariantDetails[] = $objInactiveVariantDetails;
		}
	}



	public function getVariationsShouldBeSynchronisedToEbay() {
		if(!$this->isMultiVariationProduct) {
			throw new Exception("This is not a Multi Variation Product!");
		}

		$return = array();
		foreach($this->arrVariationProducts as $objAffinityProduct) {
			if($objAffinityProduct->shouldBeSynchronisedToEbay()) {
				$return[] = $objAffinityProduct;
			}
		}

		return $return;
	}

	public function saveMultiVariationTempVariants() {
		self::saveProductAttribute($this->id, "storedAllVariantDetails", $this->tempAllVariantDetails);
		self::saveProductAttribute($this->id, "storedAllInactiveVariantDetails", $this->tempAllInactiveVariantDetails);

		foreach($this->arrVariationProducts as $objVariantProduct) {
			self::saveProductAttribute($objVariantProduct->id, "storedVariantDetails", $objVariantProduct->tempVariantDetails);
			self::saveProductAttribute($objVariantProduct->id, "storedArrInactiveVariantDetails", $objVariantProduct->tempArrInactiveVariantDetails);
		}
	}

	private function getAllVariationsItemSpecifics($objEcommerceProduct) {
		require_once(__DIR__ . "/AffinityItemSpecific.php");

		return AffinityItemSpecific::getAllVariationsItemSpecifics($objEcommerceProduct);
	}

	public function getWarningsMessagesSeparatedBy($separator = "<br>") {
		$arrWarnings = $this->getArrWarnings();

		if(count($arrWarnings) < 1) {
			return "";
		}

		$strWarningMessages = implode($separator, $arrWarnings);
		return $strWarningMessages;
	}

	public function getErrorMessagesSeparatedBy($separator = "<br>") {
		$arrErrors = $this->getArrErrors();

		if(count($arrErrors) < 1) {
			return "";
		}

		$strErrorMessages = implode($separator, $arrErrors);
		return $strErrorMessages;
	}

	private function getArrWarnings($includeClient = true, $includeServer = true) {
		$arrClientWarnings = (empty($this->jsonEncodedArrClientWarnings) || !$includeClient) ? array() : json_decode($this->jsonEncodedArrClientWarnings, true);
		$arrServerWarnings = (empty($this->jsonEncodedArrServerWarnings) || !$includeServer) ? array() : json_decode($this->jsonEncodedArrServerWarnings, true);

		return $arrClientWarnings + $arrServerWarnings;
	}

	private function getArrErrors($includeClient = true, $includeServer = true) {
		$arrClientErrors = empty($this->jsonEncodedArrClientErrors) || !$includeClient ? array() : json_decode($this->jsonEncodedArrClientErrors, true);
		$arrServerErrors = empty($this->jsonEncodedArrServerErrors) || !$includeServer ? array() : json_decode($this->jsonEncodedArrServerErrors, true);

		if (empty($arrClientErrors)) {
			$arrClientErrors  = array();
		}
		if (empty($arrServerErrors)) {
			$arrServerErrors  = array();
		}
		
		return $arrClientErrors + $arrServerErrors;
	}

	public function hasClientSideErrors() {
		$arrClientSideErrors = $this->getArrErrors(true, false);
		return count($arrClientSideErrors) > 0;
	}

	public function hasServerSideErrors() {
		$arrClientSideErrors = $this->getArrErrors(true, false);
		return count($arrClientSideErrors) > 0;
	}

	public function hasErrors() {
		$arrClientSideErrors = $this->getArrErrors(true, true);
		return count($arrClientSideErrors) > 0;
	}


	public static function getInventorySummary() {
		global $wpdb;

		$sql =  "SELECT
			COUNT(id) AS totalProducts,
			SUM(IF (pm3meta_value IS NOT NULL AND pm3meta_value != '', pm3meta_value,
			IF (pm22meta_value IS NOT NULL AND pm22meta_value != '', pm22meta_value, IF (pm1meta_value IS NOT NULL AND pm1meta_value != '', pm1meta_value, pm2meta_value)))) AS totalValue
			FROM
			(SELECT p.id,
			IF(pm1.meta_value IS NULL, 0, pm1.meta_value) * IF(pm3.meta_value IS NULL, 0, pm3.meta_value) AS pm1meta_value,
			IF(pm2.meta_value IS NULL, 0, pm2.meta_value) * IF(pm3.meta_value IS NULL, 0, pm3.meta_value) AS pm2meta_value,
			IF(pm22.meta_value IS NULL, 0, pm22.meta_value) * IF(pm3.meta_value IS NULL, 0, pm3.meta_value) AS pm22meta_value,
			SUM(IF(pm88.meta_value IS NULL OR pm88.meta_value = '', IF(pm7.meta_value IS NULL OR pm7.meta_value = '', IF(pm8.meta_value IS NULL, 0, pm8.meta_value), pm7.meta_value), pm88.meta_value) * IF(pm3.meta_value IS NULL, 0, pm9.meta_value)) AS pm3meta_value
			FROM ".$wpdb->posts." AS p
			LEFT JOIN ".$wpdb->postmeta." AS pm1 ON (p.id = pm1.post_id AND pm1.meta_key = '_sale_price')
			LEFT JOIN ".$wpdb->postmeta." AS pm2 ON (p.id = pm2.post_id AND pm2.meta_key = '_regular_price')
			LEFT JOIN ".$wpdb->postmeta." AS pm22 ON (p.id = pm22.post_id AND pm22.meta_key = '_ebayprice')
			LEFT JOIN ".$wpdb->postmeta." AS pm3 ON (p.id = pm3.post_id AND pm3.meta_key = '_stock')
			LEFT JOIN ".$wpdb->postmeta." AS pm6 ON (p.id = pm6.post_id AND pm6.meta_key = '_affinity_block')
			LEFT JOIN ".$wpdb->posts." AS p2 ON (p.id = p2.post_parent AND p2.post_type = 'product_variation')
			LEFT JOIN ".$wpdb->postmeta." AS pm7 ON (p2.id = pm7.post_id AND pm7.meta_key = '_sale_price')
			LEFT JOIN ".$wpdb->postmeta." AS pm8 ON (p2.id = pm8.post_id AND pm8.meta_key = '_regular_price')
			LEFT JOIN ".$wpdb->postmeta." AS pm88 ON (p2.id = pm88.post_id AND pm88.meta_key = '_ebayprice')
			LEFT JOIN ".$wpdb->postmeta." AS pm9 ON (p2.id = pm9.post_id AND pm9.meta_key = '_stock')
			WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm6.meta_value IS NULL GROUP BY p.id) AS j";
		$row = $wpdb->get_row($sql, ARRAY_A);

		return $row;
	}

	private function generateClientSideWarningsAndErrors() {
		$arrAdaptationWarnings = array();
		$arrAdaptationErrors = array();

		if(affinity_empty($this->title)) {
			$arrAdaptationErrors[] = "Title for your item is missing. Please add a title";
		}
		else if(strlen($this->title) > 80) {
			$arrAdaptationErrors[] = "Title added for the item is more than 80 characters long. Please update with appropriate title.";
		}
		
		if(affinity_empty($this->description)) {
			$arrAdaptationErrors[] = "Description for your item is missing. Please add a description.";
		}
		
		if(empty($this->objMainImage) || $this->objMainImage === FALSE) {
			$arrAdaptationErrors[] = "Product image for your item is missing. Please add a main product image.";
		}
		else {
			if($this->objMainImage->width < self::MINIMUM_RECOMMENDED_IMAGE_WIDTH || $this->objMainImage->height < self::MINIMUM_RECOMMENDED_IMAGE_HEIGHT) {
				$arrAdaptationErrors[] = "eBay requires that product images should be a minimum size of " . self::MINIMUM_RECOMMENDED_IMAGE_WIDTH . "x" . self::MINIMUM_RECOMMENDED_IMAGE_HEIGHT . "px and we have detected that your image is less than that. For best results increase the quality of your image.";
			}
		}

		if(is_array($this->arrObjAdditionalImages)) {
			foreach ($this->arrObjAdditionalImages as $adImg) {
				if($adImg->width < self::MINIMUM_RECOMMENDED_IMAGE_WIDTH || $adImg->height < self::MINIMUM_RECOMMENDED_IMAGE_HEIGHT) {
					$arrAdaptationErrors[] = "eBay requires that product images should be a minimum size of " . self::MINIMUM_RECOMMENDED_IMAGE_WIDTH . "x" . self::MINIMUM_RECOMMENDED_IMAGE_HEIGHT . "px and we have detected that you have a gallery image is less than that. For best results increase the quality of your image.";
					break;
				}
			}
		}


		if(is_array($this->arrObjAdditionalImages) && count($this->arrObjAdditionalImages) > 11) {
			$arrAdaptationWarnings[] = "eBay only accepts up to 12 images per listing and we have detected that your product has more than that. Because of this, some images will not be shown on your eBay listing!";
		}

		if(intval($this->ebayCategoryId) <= 0) {
			$arrAdaptationErrors[] = "Your item needs to be mapped to an eBay category!";
		} else {
			global $wpdb;
			$categoryIds = $wpdb->get_results("SELECT categoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ". $this->ebayCategoryId);

			if (empty($categoryIds)) {
				$arrAdaptationErrors[] = "Your item is mapped to an invalid or expired category!";
			}
		}

		if(!$this->isMultiVariationProduct) {
			if(floatval($this->price) <= 0) {
				$arrAdaptationErrors[] = "Your product must have a price larger than zero!";
			}
		}

		if(is_array($this->arrVariationProducts)) {
			if(self::isThereActiveDuplicateVariant($this)) {
				$arrAdaptationErrors[] = "There are duplicated variants in your product! This product will not be synchronised!";
			}

			foreach ($this->objEcommerceProduct->arrEcommerceItemSpecifics as $arrEcommerceItemSpecifics) {
				if (empty($arrEcommerceItemSpecifics->label)) {
					$arrAdaptationErrors[] = "Attribute used in variation has no label.";
					break;
				}
			}

			$isThereAValidVariation = false;
			$quantity = 0;
			$showquant = true;
			foreach($this->arrVariationProducts as $objProductVariation) {
				$arrVariantAdaptationErrors = array();
				$quantity += $objProductVariation->qtyAvailable;
				if(!$objProductVariation->isStockBeingManaged) {
					$sl = get_option('ebayaffinity_stocklevel');
					if (empty($sl) || $sl < 0) {
						$showquant = false;
						$arrAdaptationWarnings[] = 'The product variation with ID ' . $objProductVariation->id . ' will not be sent to eBay because the option Manage Stock is disabled!';
						$arrVariantAdaptationErrors[] = 'The product variation with ID ' . $objProductVariation->id . ' will not be sent to eBay because the option Manage Stock is disabled!';
					}
				}

				if(floatval($objProductVariation->price) <= 0) {
					$arrAdaptationWarnings[] = 'The product variation with ID ' . $objProductVariation->id . ' will not be sent to eBay because it must have a price larger than zero!';
					$arrVariantAdaptationErrors[] = 'The product variation with ID ' . $objProductVariation->id . ' will not be sent to eBay because it must have a price larger than zero!';
				}

				if(AffinityItemSpecific::isThereEmptyValuesOn($objProductVariation->arrItemSpecificsOfCurrentVariation)) {
					$arrAdaptationWarnings[] = 'The product variation with ID ' . $objProductVariation->id . ' will not be sent to eBay because it has unselected attributes!';
					$arrVariantAdaptationErrors[] = 'The product variation with ID ' . $objProductVariation->id . ' will not be sent to eBay because it has unselected attributes!';
				}

				//Saves the error in the variant, so the server knows it shouldn't be sent
				$jsonEncodedVariantErrors = json_encode($arrVariantAdaptationErrors);
				$objProductVariation->jsonEncodedArrClientErrors = $jsonEncodedVariantErrors;
				self::saveProductAttribute($objProductVariation->id, "jsonEncodedArrClientErrors", $jsonEncodedVariantErrors);

				if(count($arrVariantAdaptationErrors) === 0) {
					$isThereAValidVariation = true;
				}
			}

			if ($showquant) {
				if ($quantity <= 0 && empty($this->ebayListingID)) {
					$arrAdaptationErrors[] = "Product will not be sync'd because it has not been listed before and has no stock.";
				}
			}

			if(!$isThereAValidVariation) {
				$arrAdaptationErrors[] = "This Variable Product will not be synchronised because there's no valid variant included on it.";
			}
		} else {
			if(!$this->isStockBeingManaged) {
				$sl = get_option('ebayaffinity_stocklevel');
				if (empty($sl) || $sl < 0) {
					$arrAdaptationErrors[] = "Your product must have the option 'Manage Stock' enabled on wooCommerce.";
				} else if ($this->qtyAvailable <= 0 && empty($this->ebayListingID)) {
					$arrAdaptationErrors[] = "Product will not be sync'd because it has not been listed before and has no stock.";
				}
			} else if ($this->qtyAvailable <= 0 && empty($this->ebayListingID)) {
				$arrAdaptationErrors[] = "Product will not be sync'd because it has not been listed before and has no stock.";
			}
		}

		$jsonEncodedWarnings = json_encode($arrAdaptationWarnings);
		$jsonEncodedErrors = json_encode($arrAdaptationErrors);
		$this->jsonEncodedArrClientWarnings = $jsonEncodedWarnings;
		$this->jsonEncodedArrClientErrors = $jsonEncodedErrors;

		self::saveProductAttribute($this->id, "jsonEncodedArrClientWarnings", $jsonEncodedWarnings);
		self::saveProductAttribute($this->id, "jsonEncodedArrClientErrors", $jsonEncodedErrors);
	}

	private function getActiveVariantDetails() {
		$return = array();

		foreach($this->arrAllVariantDetails as $objVariantDetail) {
			if($objVariantDetail->dateEnded === null) {
				$return[] = $objVariantDetail;
			}
		}

		return $return;
	}

	public function getDeletedVariantDetails() {
		require_once(__DIR__ . "/AffinityItemSpecific.php");
		$return = array();

		foreach($this->arrAllVariantDetails as $objVariantDetail) {
			if($objVariantDetail->dateEnded !== null) {
				if(AffinityItemSpecific::isThereEmptyValuesOn($objVariantDetail->arrItemSpecifics)) {
					continue;
				}

				/*
				 * If there's an active variant that matches a deleted one, do not include it in the return
				 */
				$existingActiveVariantDetailMatching = null;

				foreach($this->getActiveVariantDetails() as $objActiveVariant) {
					if(count($objVariantDetail->arrItemSpecifics) < 1 || count($objActiveVariant->arrItemSpecifics) < 1) {
						continue;
					}

					$mapDiff = AffinityItemSpecific::compareItemSpecifics($objVariantDetail->arrItemSpecifics, $objActiveVariant->arrItemSpecifics);
					if($mapDiff['hasChanged'] === false) {
						$existingActiveVariantDetailMatching = $objActiveVariant;
						break;
					}
				}

				if($existingActiveVariantDetailMatching === null) {
					$return[] = $objVariantDetail;
				}
			}
		}

		return $return;
	}

	private static function getExistingVariantOnAllVariations($objMainProductionVariation, $arrItemSpecifics) {
		require_once(__DIR__ . "/AffinityItemSpecific.php");

		foreach($objMainProductionVariation->arrAllVariantDetails as $objVariantDetail) {
			if($objVariantDetail->matches($arrItemSpecifics) && empty($objVariantDetail->dateEnded)) {
				return $objVariantDetail;
			}
		}

		return null;
	}

	public function addProductExistingClientWarnings($warningMessage) {
		$arrClientWarnings = json_decode($this->jsonEncodedArrClientWarnings, true);
		$arrClientWarnings[] = $warningMessage;
		self::saveProductAttribute($this->id, "jsonEncodedArrClientWarnings", json_encode($arrClientWarnings));
	}

	private static function overwriteProductExistingServerWarnings($productId, $arrWarnings) {
		/*
		 * Ignore and translate messages
		 */
		foreach($arrWarnings as $key => $warningMessage) {
			if(in_array($warningMessage, self::$_arrWarningMessagesToIgnore)) {
				unset($arrWarnings[$key]);
				continue;
			}

			if(array_key_exists($warningMessage, self::$_mapWarningMessagesToTranslate)) {
				$arrWarnings[$key] = self::$_mapWarningMessagesToTranslate[$warningMessage];
			}
		}

		$jsonEncodedArrServerWarnings = addslashes(json_encode($arrWarnings));
		self::saveProductAttribute($productId, "jsonEncodedArrServerWarnings", $jsonEncodedArrServerWarnings);
	}

	private static function overwriteProductExistingServerErrors($productId, $arrErrors) {
		/*
		 * Ignore and translate messages
		 */
		foreach($arrErrors as $key => $errorMessage) {
			if(in_array($errorMessage, self::$_arrErrorMessagesToIgnore)) {
				unset($arrErrors[$key]);
				continue;
			}

			if(array_key_exists($errorMessage, self::$_mapErrorMessagesToTranslate)) {
				$arrErrors[$key] = self::$_mapErrorMessagesToTranslate[$errorMessage];
			}

			foreach(self::$_mapErrorMessagesToTranslate['__pattern'] as $ptn => $replace) {
				if (preg_match($ptn, $errorMessage, $matches)) {
					$splreplace = explode("_", $replace);
					if (count($splreplace) > 1) {
						$arrErrors[$key] = $splreplace[0] . $matches[1] . $splreplace[1];
					} else {
						$arrErrors[$key] = $splreplace[0];
					}
				}
			}
		}

		$jsonEncodedArrServerErrors = addslashes(json_encode($arrErrors));
		self::saveProductAttribute($productId, "jsonEncodedArrServerErrors", $jsonEncodedArrServerErrors);
	}

	private function getEbayMappedCategoryId() {
		/*
		 * Product to Category Mapped
		 */
		if(!empty($this->ebayCategoryId)) {
			return $this->ebayCategoryId;
		}

		if(!isset($this->objEcommerceProduct->arrEcommerceCategories) || !is_array($this->objEcommerceProduct->arrEcommerceCategories)) {
			return null;
		}

		/*
		 * Category to Category mapping fetch
		 */
		require_once(__DIR__ . "/AffinityEbayCategory.php");
		foreach($this->objEcommerceProduct->arrEcommerceCategories as $objEcommerceCategory) {
			$ebayMatchedCategory = AffinityEbayCategory::getEbayMappedCategoryId($objEcommerceCategory->id);

			if(!empty($ebayMatchedCategory)) {
				return $ebayMatchedCategory;
			}
		}

		return null;
	}

	public function getNewSku($skuToUse = null) {
		global $affinity_nonosku;
		if (!empty($affinity_nonosku)) {
			return '';
		}
		if($skuToUse === NULL) {
			if(empty($this->ebayListingID)) {
				$this->sku = self::SKU_PREFIX . $this->id . "-" . uniqid();
			}
			else {
				$this->sku = self::SKU_PREFIX . $this->id;
			}
		}
		else {
			$this->sku = $skuToUse;
		}

		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "New Sku " .  $this->sku . " Generated for " . $this->id,  "No Details");

		self::saveProductAttribute($this->id, "sku", $this->sku);
		return $this->sku;
	}

	private function getEbayMappedShipping() {
		/*
		 * Product to Shipping Mapped
		 */
		if(!empty($this->shippingPolicy)) {
			return $this->shippingPolicy;
		}

		if (!empty($this->objEcommerceProduct->arrEcommerceShipping['id'])) {
			return $this->objEcommerceProduct->arrEcommerceShipping['profile_id'];
		}

		return null;
	}

	public static function transformFromEcommerceProduct($objEcommerceProduct, $isProductVariant = false) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		require_once(__DIR__ . "/AffinityVariantDetails.php"); //Provide dependency to unserialize object for variants
		require_once(__DIR__ . "/AffinityGlobalOptions.php");

		$objAffinityProduct = new AffinityProduct();
		$objAffinityProduct->objEcommerceProduct = $objEcommerceProduct;

		$objAffinityProduct->condition = $objEcommerceProduct->condition;
		$objAffinityProduct->id = $objEcommerceProduct->id;
		$objAffinityProduct->title = $objEcommerceProduct->title;
		$objAffinityProduct->description = $objEcommerceProduct->description;
		$objAffinityProduct->shortDescription = $objEcommerceProduct->shortDescription;
		$objAffinityProduct->listingDescription = $objEcommerceProduct->listingDescription;
		$objAffinityProduct->objMainImage = $objEcommerceProduct->objMainImage;
		$objAffinityProduct->arrObjAdditionalImages = $objEcommerceProduct->arrObjAdditionalImages;

		$objAffinityProduct->weight = $objEcommerceProduct->weight;
		$objAffinityProduct->length = $objEcommerceProduct->length;
		$objAffinityProduct->width = $objEcommerceProduct->width;
		$objAffinityProduct->height = $objEcommerceProduct->height;

		$objAffinityProduct->retailPrice = $objEcommerceProduct->retailPriceIncludingTax;
		$objAffinityProduct->price = $objEcommerceProduct->priceIncludingTax;


		$objAffinityProduct->isStockBeingManaged = $objEcommerceProduct->isStockBeingManaged;
		$qtyAvailable = $objEcommerceProduct->qtyAvailable - AffinityGlobalOptions::getStockBuffer();
		$objAffinityProduct->qtyAvailable = ($objAffinityProduct->isStockBeingManaged && $qtyAvailable >= 0) ? $qtyAvailable : 0;

		if (empty($objAffinityProduct->qtyAvailable)) {
			if (!$objAffinityProduct->isStockBeingManaged) {
				$sl = AffinityGlobalOptions::getStockLevel();
				if ($sl > 0) {
					if ($objEcommerceProduct->isInStock) {
	 					$objAffinityProduct->qtyAvailable = AffinityGlobalOptions::getStockLevel();
					}
				}
			}
		}

		$objAffinityProduct->arrObjItemSpecifics = $objAffinityProduct->getItemSpecifics($objEcommerceProduct);
		$objAffinityProduct->arrObjItemSpecificsEbayProductIdentifiers = $objAffinityProduct->getEbayProductIdentifiers($objEcommerceProduct);

		//Fetch persistent fields
		foreach(self::$_arrPersistentFields as $strObjFieldName => $strPersistentOptionName) {
			$optionValue = AffinityDataLayer::getDataAssociatedToEcommerceObject($objEcommerceProduct->id, $strPersistentOptionName);
			$objAffinityProduct->$strObjFieldName = !empty($optionValue) ? $optionValue : self::$_arrPersistentFieldsDefaults[$strObjFieldName];
		}

		$objAffinityProduct->getVariations($objEcommerceProduct);

		if(empty($objAffinityProduct->sku)) {
			$objAffinityProduct->getNewSku();
		}

		$objAffinityProduct->ebayCategoryId = $objAffinityProduct->getEbayMappedCategoryId();
		$objAffinityProduct->shippingPolicy = $objAffinityProduct->getEbayMappedShipping();
		$objAffinityProduct->ebayListingID = get_post_meta($objEcommerceProduct->id, '_'.self::$_arrPersistentFields['ebayListingID'], true);

		if (!empty($objAffinityProduct->ebayListingID)) {
			$objAffinityProduct->title = substr($objAffinityProduct->title, 0, 80);
		}

		//Client side validations
		if(!$isProductVariant) {
			$objAffinityProduct->generateClientSideWarningsAndErrors($isProductVariant);
		}

		return $objAffinityProduct;
	}
	/*
	 * End Item Transformation
	 */

}

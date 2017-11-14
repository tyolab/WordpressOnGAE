<?php

class AffinityEcommerceCategory {
	const CATEGORY_TO_CATEGORY_META_TERM = "affinity_ebaycategory";
	
    public $id;
	public $parentId;
    public $name;
	public $arrChildren;
	
	public static $_cachedCategories;
	
	public static function getProductCategories($objNativeProduct) {
		$arrProductCategories = array();
		
		$productId = ($objNativeProduct instanceof WC_Product) ? $objNativeProduct->get_id() : $objNativeProduct->ID;
		$arrNativeCategories = get_the_terms($productId, 'product_cat');
		$arrNativeCategories = is_array($arrNativeCategories) ? $arrNativeCategories : array();
		
		foreach($arrNativeCategories as $objNativeCategory) {
			$cachedCategoryObj = self::get($objNativeCategory->term_id);
			
			if(!$cachedCategoryObj) {
				$cachedCategoryObj = self::transform($objNativeCategory);
				self::$_cachedCategories[$cachedCategoryObj->id] = $cachedCategoryObj;
			}
			
			$arrProductCategories[] = $cachedCategoryObj;
		}
		
		return $arrProductCategories;
	}
	
	public function getMappedEbayCategoryId() {
		if(empty($this->id)) {
			return false;
		}
		
		require_once(__DIR__ . "/AffinityDataLayer.php");
		return AffinityDataLayer::getDataAssociatedToEcommerceTerm($this->id, self::CATEGORY_TO_CATEGORY_META_TERM);
	}
	
	public function isLeaf() {
		return count($this->arrChildren) === 0;
	}
	
	public function isRoot() {
		return $this->parentId === null;
	}
	
	public function getParent() {
		return self::get($this->parentId);
	}
	
	public function getArrCategoriesOrderedFromRootToLeaf($includeItselfAsLastItem = true) {
		$arrCategoriesOrderedFromLeafToRoot = array();
		
		$currentBranch = $includeItselfAsLastItem ? $this : $this->getParent();
		
		while(!$currentBranch->isRoot()) {
			$arrCategoriesOrderedFromLeafToRoot[] = $currentBranch;
			$currentBranch = $currentBranch->getParent();
		}
		
		return array_reverse($arrCategoriesOrderedFromLeafToRoot);
	}
    
    public static function get($id, $useCache = true) {
		if(!$useCache || self::$_cachedCategories === null) {
			self::loadToCache();
		}
		
		if(!isset(self::$_cachedCategories[$id])) {
			return false;
		}
		
		return self::$_cachedCategories[$id];
	}
	
    public static function getAll($useCache = true) {
		if(!$useCache || self::$_cachedCategories === null) {
			self::loadToCache();
		}
		
        return self::$_cachedCategories;
    }
	
	private static function loadToCache() {
		$ecommerceCategories = get_categories(array(
			'taxonomy'     => 'product_cat',
			'orderby'      => 'name',
			'show_count'   => 0,
			'pad_counts'   => 0,
			'hierarchical' => 0,
			'title_li'     => '',
			'hide_empty'   => 1
		));

		self::$_cachedCategories = array();
		foreach($ecommerceCategories as $objOriginalCategory) {
			$objEcommerceCategory = self::transform($objOriginalCategory);

			self::$_cachedCategories[$objEcommerceCategory->id] = $objEcommerceCategory;
		}
		
		foreach(self::$_cachedCategories as $k=>$cc) {
			//If it has as parent, add it as its children
			if(self::$_cachedCategories[$k]->parentId > 0) {
				self::$_cachedCategories[self::$_cachedCategories[$k]->parentId]->arrChildren[] = self::$_cachedCategories[$k];
			}
		}
	}
	
	private static function transform($originalCategory) {
		$objEcommerceCategory = new AffinityEcommerceCategory();
		$objEcommerceCategory->id = $originalCategory->cat_ID;
		$objEcommerceCategory->parentId = $originalCategory->category_parent;
		$objEcommerceCategory->name = $originalCategory->name;
		
		return $objEcommerceCategory;
		
		/*
		 * Complete Woocommerce Obj:
		 * => WP_Term Object
			*  [term_id] => 15
			   [name] => Albums
			   [slug] => albums
			   [term_group] => 0
			   [term_taxonomy_id] => 15
			   [taxonomy] => product_cat
			   [description] => 
			   [parent] => 11
			   [count] => 4
			   [filter] => raw
			   [meta_id] => 7
			   [woocommerce_term_id] => 15
			   [meta_key] => order
			   [meta_value] => 0
			   [cat_ID] => 15
			   [category_count] => 4
			   [category_description] => 
			   [cat_name] => Albums
			   [category_nicename] => albums
			   [category_parent] => 11
		 */
	}
	
	public static function getAllCategories() {
        return get_categories(array(
            'taxonomy' => 'product_cat',
            'orderby' => 'name',
            'show_count' => 0,
            'pad_counts' => 0,
            'hierarchical' => 0,
            'title_li' => '',
            'hide_empty' => 1
        ));
    }
}

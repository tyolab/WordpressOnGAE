<?php

class AffinityEbayCategory {
    const ebayCategoryMetaFieldName = "_affinity_ebaycategory";
    const ebayCategoryClassName = "AffinityEbayCategory";
	
	public $categoryId;
    public $categoryParentId;
    public $name;
	public $path;
	public $level;
	public $leaf;
	 
    
	//@Todo
	public function getCategoryNameWithParents($separator = ">") {
		return "Category Name";
		//return $this->name;
	}
	
	public static function get($categoryId) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		return AffinityDataLayer::get(self::ebayCategoryClassName, $categoryId);
	}
	
	/*
	 * @return objEbayCategory / false if not found
	 */
	public static function getProductEbayCategory($objAffinityProduct) {
		$ebayCategoryId = get_post_meta($objAffinityProduct->id, self::ebayCategoryMetaFieldName, true);
		
		if($ebayCategoryId < 1) {
			return false;
		}
		
		return self::get($ebayCategoryId);
	}
	
	static function showWooCatName($cats, $id, $enc=false) {	
		if (!empty($cats[$id][3])) {
			if ($enc) {
				return self::showWooCatName($cats, $cats[$id][3]) . ' <span class="ebayaffinity-rightblue">&#x203a;</span> ' .esc_html($cats[$id][0]);
			} else {
				return self::showWooCatName($cats, $cats[$id][3]) . ' > ' .$cats[$id][0];
			}
		} else {
			if ($enc) {
				return esc_html($cats[$id][0]);
			} else {
				return $cats[$id][0];
			}
		}
	}
	
	static function getAlleBay($ids = array()) {
		global $wpdb;
		
		$sql = "SELECT CONCAT(IF(c1.name IS NOT NULL, IF(LOCATE('Lots More', c1.name) > 0, CONCAT('zzzzzzzzzzz', c1.name), c1.name), ''),
							IF(c2.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c2.name) > 0, CONCAT('zzzzzzzzzzz', c2.name), c2.name)), ''),
							IF(c3.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c3.name) > 0, CONCAT('zzzzzzzzzzz', c3.name), c3.name)), ''),
							IF(c4.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c4.name) > 0, CONCAT('zzzzzzzzzzz', c4.name), c4.name)), ''),
							IF(c5.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c5.name) > 0, CONCAT('zzzzzzzzzzz', c5.name), c5.name)), ''),
							IF(c6.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c6.name) > 0, CONCAT('zzzzzzzzzzz', c6.name), c6.name)), '')) AS catname,
							IF(c6.categoryId IS NOT NULL, c6.categoryId,
							IF(c5.categoryId IS NOT NULL, c5.categoryId,
							IF(c4.categoryId IS NOT NULL, c4.categoryId,
							IF(c3.categoryId IS NOT NULL, c3.categoryId, 
							IF(c2.categoryId IS NOT NULL, c2.categoryId, c1.categoryId))))) AS categoryId
							FROM 
							".$wpdb->prefix."ebayaffinity_categories AS c1 LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c2 ON (c2.parentCategoryId = c1.categoryId AND c2.level = 2) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c3 ON (c3.parentCategoryId = c2.categoryId AND c3.level = 3) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c4 ON (c4.parentCategoryId = c3.categoryId AND c4.level = 4) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c5 ON (c5.parentCategoryId = c4.categoryId AND c5.level = 5) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c6 ON (c6.parentCategoryId = c5.categoryId AND c6.level = 6)
							WHERE c1.parentCategoryID = -1 AND c1.level = 1 ";
		if (!empty($ids)) {
			$idstr = implode(',', $ids);
			$sql .= " AND (c6.categoryId IN (".$idstr.") OR c5.categoryId IN (".$idstr.") OR c4.categoryId IN (".$idstr.") OR c3.categoryId IN (".$idstr.") OR c2.categoryId IN (".$idstr.")) ";
		}
    	$sql .= "ORDER BY catname";
    	
    	$res = $wpdb->get_results($sql);
    	
    	foreach ($res as $k=>$v) {
    		$res[$k]->catname = str_replace('zzzzzzzzzzz', '', $v->catname);
    	}
		
		return $res;
	}
	
    static function getSubsByParent($parentId = -1) {
    	global $wpdb;
    	$res = $wpdb->get_results(
    			$wpdb->prepare(
    					"SELECT CONCAT(IF(c2.name IS NOT NULL, IF(LOCATE('Other', c2.name) > 0, CONCAT('zzzzzzzzzzz', c2.name), c2.name), ''),
							IF(c3.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c3.name) > 0, CONCAT('zzzzzzzzzzz', c3.name), c3.name)), ''),
							IF(c4.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c4.name) > 0, CONCAT('zzzzzzzzzzz', c4.name), c4.name)), ''),
							IF(c5.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c5.name) > 0, CONCAT('zzzzzzzzzzz', c5.name), c5.name)), ''),
							IF(c6.name IS NOT NULL, CONCAT(' > ', IF(LOCATE('Other', c6.name) > 0, CONCAT('zzzzzzzzzzz', c6.name), c6.name)), '')) AS catname,
							IF(c6.categoryId IS NOT NULL, c6.categoryId,
							IF(c5.categoryId IS NOT NULL, c5.categoryId,
							IF(c4.categoryId IS NOT NULL, c4.categoryId,
							IF(c3.categoryId IS NOT NULL, c3.categoryId, c2.categoryId)))) AS categoryId
							FROM ".$wpdb->prefix."ebayaffinity_categories AS c2 LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c3 ON (c3.parentCategoryId = c2.categoryId AND c3.level = 3) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c4 ON (c4.parentCategoryId = c3.categoryId AND c4.level = 4) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c5 ON (c5.parentCategoryId = c4.categoryId AND c5.level = 5) LEFT JOIN
							".$wpdb->prefix."ebayaffinity_categories AS c6 ON (c6.parentCategoryId = c5.categoryId AND c6.level = 6)
							WHERE c2.parentCategoryID = %d AND c2.level = 2
    						ORDER BY catname", $parentId
    					)
    			);
    	
    	foreach ($res as $k=>$v) {
    		$res[$k]->catname = str_replace('zzzzzzzzzzz', '', $v->catname);
    	}
    	
    	return $res;
    }
    
    static function getByParent($parentId = -1) {
    	global $wpdb;
    	$res = $wpdb->get_results(
    			$wpdb->prepare(
    					"SELECT categoryId, IF(LOCATE('Lots More', name) > 0, CONCAT('zzzzzzzzzzz', name), name) AS name, leaf FROM ".$wpdb->prefix."ebayaffinity_categories WHERE parentCategoryId = %d ORDER BY name", $parentId
    			)
    	);
    	
    	foreach ($res as $k=>$v) {
    		$res[$k]->name = str_replace('zzzzzzzzzzz', '', $v->name);
    	}
    	 
    	return $res;
    }
	
	static function getEbayMappedCategoryId($ecommerceCategoryId) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		return AffinityDataLayer::getDataAssociatedToEcommerceTerm($ecommerceCategoryId, self::ebayCategoryMetaFieldName, false);
	}
    
    static function mapCategory($term_id, $categoryId) {
    	update_term_meta($term_id, self::$ebayCategoryFieldName, $categoryId);
    }
    
    static function mapProduct($post_id, $categoryId) {
    	update_post_meta($post_id, self::$ebayCategoryFieldName, $categoryId);
    }

    static function getUnmappedWCCategories() {
    	$arr = get_categories(array(
    			'taxonomy' => 'product_cat',
    			'orderby' => 'name',
    			'show_count' => 1,
    			'pad_counts' => 0,
    			'hierarchical' => 0,
    			'title_li' => '',
    			'hide_empty' => 1,
    	));
    	
    	$arr_o = array();
    	foreach ($arr as $el) {
    		$id = get_term_meta($el->term_id, self::$ebayCategoryFieldName, true);
    		if (empty($id)) {
    			$arr_o[$el->term_id.''] = html_entity_decode($el->name, ENT_QUOTES, get_option('blog_charset'));
    		}
    	}
    	
    	return $arr_o;
    }
    
    static function getCategoriesShipRules() {
    	$arr = get_categories(array(
    			'taxonomy' => 'product_cat',
    			'orderby' => 'name',
    			'show_count' => 1,
    			'pad_counts' => 0,
    			'hierarchical' => 0,
    			'title_li' => '',
    			'hide_empty' => 1,
    	));
    	 
    	$arr_o = array();
    	foreach ($arr as $el) {
    		$id = get_term_meta($el->term_id, '_affinity_shiprule', true);
    		if (empty($id)) {
    			$id = 0;
    		}
    		$arr_o[$el->term_id.''] = array(html_entity_decode($el->name, ENT_QUOTES, get_option('blog_charset')), $id);
    	}
    	 
    	return $arr_o;
    }
    
    static function getCategoriesTitleRules() {
    	$arr = get_categories(array(
    			'taxonomy' => 'product_cat',
    			'orderby' => 'name',
    			'show_count' => 1,
    			'pad_counts' => 0,
    			'hierarchical' => 0,
    			'title_li' => '',
    			'hide_empty' => 1,
    	));
    
    	$arr_o = array();
    	foreach ($arr as $el) {
    		$id = get_term_meta($el->term_id, '_affinity_titlerule', true);
    		if (empty($id)) {
    			$id = 0;
    		}
    		$arr_o[$el->term_id.''] = array(html_entity_decode($el->name, ENT_QUOTES, get_option('blog_charset')), $id);
    	}
    
    	return $arr_o;
    }
    
    static function getCategoriesCatRules() {
    	$arr = get_categories(array(
    			'taxonomy' => 'product_cat',
    			'orderby' => 'name',
    			'show_count' => 1,
    			'pad_counts' => 0,
    			'hierarchical' => 0,
    			'title_li' => '',
    			'hide_empty' => 1,
    	));
    
    	$arr_o = array();
    	foreach ($arr as $el) {
    		$id = get_term_meta($el->term_id, '_affinity_ebaycategory', true);
    		if (empty($id)) {
    			$id = 0;
    		}
    		$arr_o[$el->term_id.''] = array(html_entity_decode($el->name, ENT_QUOTES, get_option('blog_charset')), $id);
    	}
    
    	return $arr_o;
    }
    
    static function getUnmappedWCProducts() {
    	$exclude_cats = array_keys(self::getUnmappedWCCategories());
    	
    	$args = array(
    			'post_type' => 'product',
    			'paged' => $paged,
    			'posts_per_page' => 12,
    			'tax_query' => array(
						array(
								'taxonomy' => 'product_cat',
								'field' => 'id',
								'terms' => $exclude_cats,
								'operator' => 'NOT IN'
						),
				)
    	);
    	
    	$wp_query = new WP_Query($args);
    	$found = $wp_query->found_posts;
    	
    	$data = array();
    	
    	while ($wp_query->have_posts()) {
    		$wp_query->the_post();
    		$id = get_the_ID();
    		$product = new WC_Product(get_the_ID());
    		$data[] = array(
    				'id' => $id,
    				'title' => $product->get_title(),
					'img' => $product->get_image(array(64, 64))
    		);
    	}
    	
    	return array($data, $found);
    }
    
    static function getUnmappedWCSuggestionsProducts() {
    	$exclude_cats = array_keys(self::getUnmappedWCCategories());
    	 
    	$args = array(
    			'post_type' => 'product',
    			'paged' => $paged,
    			'posts_per_page' => 12,
    			'tax_query' => array(
    					array(
    							'taxonomy' => 'product_cat',
    							'field' => 'id',
    							'terms' => $exclude_cats,
    							'operator' => 'NOT IN'
    					),
    			),
    			'meta_query' => array(
					'key' => '_affinity_suggestedCatId',
					'compare' => 'EXISTS',
				)
    	);
    	 
    	$wp_query = new WP_Query($args);
    	$found = $wp_query->found_posts;
    	 
    	$data = array();
    	
    	while ($wp_query->have_posts()) {
    		$wp_query->the_post();
    		$suggestedCatId = get_post_meta(get_the_ID(), '_affinity_suggestedCatId', true);
    		$id = get_the_ID();
    		$product = new WC_Product(get_the_ID());
    		$data[] = array(
    				'id' => $id,
    				'title' => $product->get_title(),
    				'img' => $product->get_image(array(64, 64)),
    				'suggestedCatId' => $suggestedCatId
    		);
    	}
    	 
    	return array($data, $found);
    }
}

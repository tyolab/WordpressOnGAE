<?php
require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");

class AffinityTitleTemplateUnit {
	const TYPE_STRING = 'str';
	const TYPE_CUSTOM_ATTRIBUTE = 'customattr';
	const TYPE_PRODUCT_FIELD = 'pfield';
	
	public $type;
	public $value; //string value | attribute name | field name	
}

class AffinityTitleRule {
	const className = "AffinityTitleRule";
	
    public $id;
    public $arrTitleTemplateUnit = array(); 
    public $arrCategoriesToApply = array(); //arrEcommerceCategoryObj
    public $arrProductsToApply = array(); //arrAffinityProduct
    
    public static $titleRules;
	
	/*
	 * Template Unit
	 */
	public function addTemplateUnit($type, $value, $position = -1) {
		$obj = new AffinityTitleTemplateUnit();
		$obj->type = $type;
		$obj->value = $value;
		
		$position = ($position === -1) ? count($this->arrTitleTemplateUnit) : $position;
		array_splice($this->arrTitleTemplateUnit, $position, 0, $obj);
		$this->save();
	}
	
	public function editTemplateUnit($position, $newValue) {
		if(empty($this->arrTitleTemplateUnit[$position])) {
			throw new Exception("Template Unit on the position $position doesn't exist!");
		}
		
		$obj = $this->arrTitleTemplateUnit[$position];
		$obj->newValue = $newValue;
		$this->save();
	}
	
	public function deleteTemplateUnit($position) {
		if(empty($this->arrTitleTemplateUnit[$position])) {
			throw new Exception("Template Unit to delete (on the position $position) doesn't exist!");
		}
		
		array_splice($this->arrTitleTemplateUnit, $position, 1);
		$this->save();
	}
	/*
	 * End Template Unit
	 */
	
	
	/*
	 * Category Relationship
	 */
	public function addCategory($id) {
		$this->arrCategoriesToApply[$id] = AffinityEcommerceCategory::get($id);
		$this->save();
	}
	
	public function deleteCategory($id) {
		unset($this->arrCategoriesToApply[$id]);
		$this->save();
	}
	/*
	 * End Category Relationship
	 */
	
	
	/*
	 * Product Relationship
	 */
	public function addProduct($id) {
		$this->arrCategoriesToApply[$id] = AffinityEcommerceCategory::get($id);
		$this->save();
	}
	
	public function deleteProduct($id) {
		unset($this->arrCategoriesToApply[$id]);
		$this->save();
	}
	/*
	 * End Product Relationship
	 */
	
	
	/*
	 * Persisting Lifecycle
	 */
	public function save() {
		$this->arrTitleTemplateUnit = json_encode($this->arrTitleTemplateUnit);
		
		$arrCategoriesToApplyIds = array_keys($this->arrCategoriesToApply);
		$this->arrCategoriesToApply = json_encode($arrCategoriesToApplyIds);
		
		$arrProductsToApplyIds = array_keys($this->arrProductsToApply);
		$this->arrProductsToApply = json_encode($this->arrProductsToApply);
		
		return AffinityDataLayer::save($this);
	}
	
	public static function get($id) {
		$objRetrieved = AffinityDataLayer::get(AffinityTitleRule::className, $id);
		
		$this->arrTitleTemplateUnit = json_decode($this->arrTitleTemplateUnit);
		
		$arrCategoriesToApplyIds = array_keys($this->arrCategoriesToApply);
		$this->arrCategoriesToApply = json_decode($this->arrCategoriesToApply);
		
		$arrProductsToApplyIds = json_decode($this->arrProductsToApply);
		$this->arrProductsToApply = json_decode($this->arrProductsToApply);
		
		return $objRetrieved;
	}
	
	public static function create() {
		$obj = new AffinityTitleRule();
		$obj->addTemplateUnit(AffinityTitleTemplateUnit::TYPE_PRODUCT_FIELD, "title");
		$obj->save();
		
		return $obj;
	}
	
	public static function delete($id) {
		 AffinityDataLayer::delete(AffinityTitleRule::className, $id);
	}
	
   /*
    <rules>
    	<rule type="string">New</rule>
    	<rule type="attr">title</rule>
    </rules>
    */
    static function parseRule($string='') {
    	if (empty($string)) {
    		$string = $this->_titleTemplate;
    	}
    	$rules = simplexml_load_string($string);
    	
    	$arr = array();
    	foreach ($rules as $rule) {
    		$arr[] = array('type' => (string)$rule['type'], 'value' => (string)$rule);
    	}
    	return $arr;
    }
    
    static function trunc() {
    	global $wpdb;
    	$wpdb->query("DELETE FROM ".$wpdb->prefix."ebayaffinity_titlerules");
    }
    
    function upsert($forceinsert=false) {
    	global $wpdb;
    	
    	if ($forceinsert) {
    		$query = $wpdb->prepare(
    				"INSERT INTO ".$wpdb->prefix."ebayaffinity_titlerules (id, is_default, rule, title) VALUES (%d, %d, %s, %s)",
    				$this->_id, $this->_is_default, $this->_titleTemplate, $this->_title
    		);
    	} else if (!empty($this->_id)) {
    		$query = $wpdb->prepare(
    				"UPDATE '.$wpdb->prefix.'ebayaffinity_titlerules SET rule=%s, title=%s WHERE id = %d",
    				$this->_titleTemplate, $this->_title, $this->_id
    		);
    	} else {
    		$query = $wpdb->prepare(
    				"INSERT INTO '.$wpdb->prefix.'ebayaffinity_titlerules (rule, title) VALUES (%s, %s)",
    				$this->_titleTemplate, $this->_title
    		);
    	}
    	
    	if ($wpdb->query($query)) {
	    	if (!empty($wpdb->insert_id)) {
	    		$this->id = $wpdb->insert_id;
	    	} else {
	    		$this->id = 0;
	    	}
	    	return $this->id;
    	} else {
    		return -1;
    	}
    }
    
    static function getAllRules() {
    	global $wpdb;
    	return $wpdb->get_results("SELECT id, is_default, rule, title FROM ".$wpdb->prefix."ebayaffinity_titlerules ORDER BY id");
    }
    
    static function getRule($id) {
    	if (empty($id)) {
    		return false;
    	}
        if (empty(self::$titleRules)) {
    		self::$titleRules = self::getAllRules();
    	}
    	foreach (self::$titleRules as $titleRule) {
    		if ($titleRule->id == $id) {
    			return $titleRule;
    		}
    	}
    	return false;
    }
    
    static function getDefaultRule() {
    	if (empty(self::$titleRules)) {
    		self::$titleRules = self::getAllRules();
    	}
    	foreach (self::$titleRules as $titleRule) {
    		if (!empty($titleRule->is_default)) {
    			return $titleRule;
    		}
    	}
    	return false;
    }
    
    static function generate($product) {
    	if (empty(self::$titleRules)) {
    		self::$titleRules = self::getAllRules();
    	}

    	$rule = self::getRule(get_post_meta($product->get_id(), '_affinity_titlerule', true));
    	
    	if (empty($rule)) {
    		$terms = get_the_terms($product->get_id(), 'product_cat');
    		if (is_array($terms)) {
    			foreach ($terms as $k=>$term) {
    				$r = self::getRule(get_term_meta($term->term_id, '_affinity_titlerule', true));
    				if (!empty($r)) {
    					$rule = $r;
    					break;
    				}
    			}
    		}
    	}
    	if (empty($rule)) {
    		$rule = self::getDefaultRule();
    	}
    	if (empty($rule)) {
    		return array(0, $product->get_title());
    	}
    	
    	$build = array();
    	
    	$taxonomies = wc_get_attribute_taxonomies();
    	$taxonomies_arr = array();
    	foreach ($taxonomies as $taxonomy) {
    		$taxonomies_arr[$taxonomy->attribute_name] = $taxonomy->attribute_label;
    	
    	}
    	
    	$attributes = $product->get_attributes();
    	$ats = array();
    	foreach ($attributes as $k=>$attribute) {
			if (!isset($attribute['name'])) {
                continue;
            }
			$value = $product->get_attribute($attribute['name']);
			if (empty($attribute['is_taxonomy'])) {
				$ats[$k] = $value;
			} else {
				$ats[$k] = $value;
			}
    	}
    	
    	$arr = self::parseRule($rule->rule);
    	foreach ($arr as $el) {
    		if ($el['type'] === 'string') {
    			$build[] = $el['value'];
    		} else if ($el['type'] === 'attr') {
    			if ($el['value'] === 'title') {
    				$build[] = $product->get_title();
    			} else if ($el['value'] === 'fake-fake-condition') {
    				$build[] = 'New';
    			} else {
    				if (!empty($ats[$el['value']])) {
    					$build[] = $ats[$el['value']];
    				}
    			}
    		}
    	}
    	return array($rule->id, implode(' ', $build));
    }
}

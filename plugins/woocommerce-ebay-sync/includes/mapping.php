<?php 
require_once(__DIR__ . "/support.php");
/*
 * TODO:
 * 
 * Get number of products that are not mapped in any way.
 * Get number of products that are not mapping in any way, but have suggestions!
 * 
 */
?>

<div class="ebayaffinity-header <?php print empty($_GET['cat2cat'])?'':'ebayaffinity-header-none-selected'?>">
	<span class="<?php print empty($_GET['attr'])?'':'ebayaffinity-header-vert-mobile'?>">Mapping</span>

<?php 
if (empty($_GET['attr'])) {
	if (empty($_GET['cat2cat']) && empty($_GET['cat2prod'])) {
		$_GET['cat2cat'] = 1;
	}
?>
	<div>
		<a href="admin.php?page=ebay-sync-mapping&amp;cat2cat=1" class="ebayaffinity-map-category <?php print empty($_GET['cat2cat'])?'':'ebayaffinity-map-category-on'?>">Category view</a>
	</div>
	<div>
		<a href="admin.php?page=ebay-sync-mapping&amp;cat2prod=1" class="ebayaffinity-map-product <?php print empty($_GET['cat2prod'])?'':'ebayaffinity-map-product-on'?>">Product view</a>
	</div>
<?php 
}
?>
</div>

<?php

if (empty($_GET['cat2cat']) && empty($_GET['attr'])) {
?>
	<div class="ebayaffinity-settingspages ebayaffinity-settingspages-top ebayaffinity-settingspages-top-force" style="display: block !important;">
		<a class="ebayaffinity-settingspage ebayaffinity-settingspageon" href="admin.php?page=ebay-sync-mapping">
		Category Mapping
		</a>
		<a class="ebayaffinity-settingspage"  href="admin.php?page=ebay-sync-mapping&amp;attr=1">
		Attribute Mapping
		</a>
	</div>

<form action="admin.php" autocomplete="off" id="ebayaffinity-inv-filter-form-2">
<?php 
	require_once(__DIR__.'/filter.php');
?>
</form>

<script type="text/javascript">
<?php
	if (!empty($_POST)) {
?>
	ebayaffinity_ajax_success = '<strong>&#x2713;</strong> Products have been mapped!';
<?php
	}
?>
	ebayaffinity_ajax_title = 'Mapping';
<?php
	if (!empty($_GET['s'])) {
?>
	ebayaffinity_ajax_s = <?php print json_encode(stripslashes($_GET['s']))?>;
<?php 
	}
?>
	ebayaffinity_ajax_callback = ebayaffinitySetProductsToeBayCat;
	ebayaffinityPullItemsAjax();
</script>
<?php 
} else if (empty($_GET['attr'])) {
?>
<script type="text/javascript">
	ebayaffinity_ajax_catmode = 1;
<?php
	if (!empty($_POST)) {
?>
	ebayaffinity_ajax_success = '<strong>&#x2713;</strong> Categories have been mapped!';
<?php
	}
?>	
</script>
<?php 
	require_once(__DIR__.'/../model/AffinityEbayInventory.php');
	require_once(__DIR__.'/../model/AffinityEbayCategory.php');
	$cats = AffinityEbayInventory::categoryset(true, true);
	
	function sortmapping($a, $b) {
		if (empty($_GET['mappedfirst'])) {
			if ((!empty($a[2])) && empty($b[2])) {
				return 1;
			}
			if ((!empty($b[2])) && empty($a[2])) {
				return -1;
			}
		} else {
			if ((!empty($a[2])) && empty($b[2])) {
				return -1;
			}
			if ((!empty($b[2])) && empty($a[2])) {
				return 1;
			}
		}
		return strnatcasecmp($a[0], $b[0]);
	}
	
	uasort($cats, 'sortmapping');
?>
<div class="ebayaffinity-header ebayaffinity-header-some-selected" style="display: none;">
	<div id="ebayaffinity-cancel">Cancel</div>
		<div id="ebayaffinity-confirmselected">
			Map to eBay category
		<strong class="ebay-affinity-rot">&#x221f;</strong>
	</div>
</div>
 
<div class="ebayaffinity-settingspages ebayaffinity-settingspages-top">
	<a class="ebayaffinity-settingspage ebayaffinity-settingspageon" href="admin.php?page=ebay-sync-mapping">
		Category Mapping
	</a>
	<a class="ebayaffinity-settingspage"  href="admin.php?page=ebay-sync-mapping&amp;attr=1">
		Attribute Mapping
	</a>
</div>

<?php 
		if (empty($cats)) {
?>
<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">No results found.</div>
<?php 	
		} else {
			$eids = array();
			foreach ($cats as $k=>$cat_todo) {
				if (!empty($cat_todo[1])) {
					$arr = explode(',', $cat_todo[1]);
					foreach ($arr as $el) {
						$eids[$el] = $el;
					}
				}
				if (!empty($cat_todo[2])) {
					$eids[$cat_todo[2]] = $cat_todo[2];
				}
			}
			
			$eids = array_values($eids);

			$ecats = AffinityEbayCategory::getAlleBay($eids);
?>
<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">
	<div class="ebayaffinity-ajax-header">Select WooCommerce category
		<div class="ebayaffinity-unmapped-box">
			<label for="ebayaffinity-unmapped">Sort by:</label>
			<select name="ebayaffinity-unmapped" id="ebayaffinity-unmapped" class="ebayaffinity-select">
				<option value="admin.php?page=ebay-sync-mapping&amp;cat2cat=1" <?php print empty($_GET['mappedfirst'])?'selected':''?>>Unmapped first</option>
				<option value="admin.php?page=ebay-sync-mapping&amp;cat2cat=1&amp;mappedfirst=1" <?php print empty($_GET['mappedfirst'])?'':'selected'?>>Mapped first</option>
			</select>
		</div>
	</div>
	<table class="ebayaffinity-inv-table ebayaffinity-inv-cat2cat">
		<tr>
			<td>
				<input id="ebayaffinity-checkboxall" type="checkbox" value="1" name="all" autocomplete="off">
			</td>
			<td>WooCommerce category</td>
			<td>Mapped eBay category</td>
		</tr>
<?php 
			foreach ($cats as $k=>$cat_todo) {
				$ecattxt = '';
				foreach ($ecats as $ecat) {
					if ($cat_todo[2] == $ecat->categoryId) {
						if (empty($ecattxt)) {
							$ecattxt = $ecat->catname;
						}
					}
				}
				
				$suggecattxt = array();
				$suggecatid = array();
				foreach ($ecats as $ecat) {
					$arr = explode(',', $cat_todo[1]);
					foreach ($arr as $el) {
						if ($el == $ecat->categoryId) {
							$suggecattxt[] = $ecat->catname;
							$suggecatid[] = $el;
						}
					}
				}
?>
		<tr data-id="<?php print esc_html($k)?>">
			<td><input class="ebayaffinity-checkbox" data-suggid="<?php print implode(':::::', $suggecatid)?>" data-suggtxt="<?php print implode(':::::', $suggecattxt)?>" type="checkbox" value="1" autocomplete="off" id="ebayaffinity_cat_id_<?php print esc_html($k)?>" name="id[<?php print esc_html($k)?>]" data-name="<?php print esc_html($cat_todo[0])?>" data-title="<?php print esc_html($cat_todo[0])?>"></td>
			<td><label for="ebayaffinity_cat_id_<?php print esc_html($k)?>"><?php print AffinityEbayCategory::showWooCatName($cats, $k, true)?><span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span></label></td>
			<td><?php print str_replace('&gt;', '<span class="ebayaffinity-rightblue">&#x203a;</span>', esc_html($ecattxt))?><span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span></td>
		</tr>
<?php 	
			}
?>
	</table>
</div>
<?php 
		}

?>

<?php 
} else {
?>
<div class="ebayaffinity-settingspages ebayaffinity-settingspages-top">
	<a class="ebayaffinity-settingspage" href="admin.php?page=ebay-sync-mapping">
		Category Mapping
	</a>
	<a class="ebayaffinity-settingspage ebayaffinity-settingspageon"  href="admin.php?page=ebay-sync-mapping&amp;attr=1">
		Attribute Mapping
	</a>
</div>
<?php 

	require_once(__DIR__."/../model/AffinityEbayInventory.php");
	$attributes = AffinityEbayInventory::getAllAttributes();
	require_once(__DIR__ . "/../model/AffinityItemSpecificMapping.php");
	$arrItemSpecificsMappings = AffinityItemSpecificMapping::getAll();
	ksort($arrItemSpecificsMappings);
	require_once(__DIR__.'/../model/AffinityEbayCategory.php');
	
	$cats = AffinityEbayInventory::getAllMappedCats();

	global $affinity_apirecs;
	$affinity_apirecs = array();
	global $affinity_found;
	
	$eids = array();
	
	$affinity_apirecs['UPC'] = 'UPC';
	
	if (!empty($cats)) {
		
		foreach ($cats as $v) {
			$eids[$v['meta_value']] = $v['meta_value'];
		}
		
		$eids = array_values($eids);
		$eids = array_slice($eids, 0, 50);
		
		$ecats = AffinityEbayCategory::getAlleBay($eids);
		if (!empty($ecats)) {
			foreach ($ecats as $ecat) {
				if (stripos($ecat->catname, 'book') !== false) {
					$affinity_apirecs['ISBN'] = 'ISBN';
					break;
				}
			}
		}
		$atts = AffinityBackEndService::getAttributesForCategories($eids);
		
		if (!empty($atts['arrResult']['data'])) {
			foreach ($atts['arrResult']['data'] as $data) {
				if (!empty($data['NameRecommendation'])) {
					foreach ($data['NameRecommendation'] as $namerec) {
							$affinity_apirecs[$namerec['Name']] = $namerec['Name'];
					}
				}
			}
		}
	}
	
	$affinity_apirecs = array_values($affinity_apirecs);
	sort($affinity_apirecs);

	if (empty($eids)) {
?>
		<div class="ebayaffinity-big-error">
			<div>
				<div>Error</div>
				<p>Please map at least one category to see eBay item specifics.</p></div>
		</div>
<?php 
	}
	
	/*
	 * Generates select box for each item specific mapping
	 */
	function affinityGetSelectForItemSpecificObj(&$objItemSpecificMapping) {
		global $affinity_apirecs;
		global $affinity_found;
		if (!empty($affinity_apirecs)) {
			$arrEbayItemSpecifics = $affinity_apirecs;
		} else {
			$arrEbayItemSpecifics = array();
		}
		$ebayItemSpecificsSelect = '<select class="ebayaffinity-select">';
		$ebayItemSpecificsSelect .= '<option value="">&nbsp;</option>';
		$found = false;
		foreach($arrEbayItemSpecifics as $strEbayItemSpecific) {
			if($objItemSpecificMapping->ebayItemSpecificId === $strEbayItemSpecific || 
					$objItemSpecificMapping->customTypedName === $strEbayItemSpecific) {
				$found = true;
				$ebayItemSpecificsSelect .= '<option value="'.esc_html($strEbayItemSpecific).'" selected>'.esc_html($strEbayItemSpecific).'</option>';
			}
			else {
				$ebayItemSpecificsSelect .= '<option value="'.esc_html($strEbayItemSpecific).'">'.esc_html($strEbayItemSpecific)."</option>";
			}
		}
		if (!$found) {
			if (empty($objItemSpecificMapping->customTypedName)) {
				if (!empty($objItemSpecificMapping->ebayItemSpecificId)) {
					$objItemSpecificMapping->customTypedName = $objItemSpecificMapping->ebayItemSpecificId;
					$objItemSpecificMapping->mappedName = $objItemSpecificMapping->ebayItemSpecificId;
				}
				$objItemSpecificMapping->ebayItemSpecificId = '';
			}
		} else {
			if (empty($objItemSpecificMapping->ebayItemSpecificId)) {
				$objItemSpecificMapping->ebayItemSpecificId = $objItemSpecificMapping->customTypedName;
				$objItemSpecificMapping->mappedName = $objItemSpecificMapping->customTypedName;
				$objItemSpecificMapping->customTypedName = '';
			}
		}
		$affinity_found = $found;
		if (empty($objItemSpecificMapping->ebayItemSpecificId) && empty($objItemSpecificMapping->customTypedName)) {
			$found = true;
		}
		$ebayItemSpecificsSelect .= '<option value="" ' . ($found?'':'selected') . '>+ Custom</option>';
		$ebayItemSpecificsSelect .= '</select>';
	
		return $ebayItemSpecificsSelect;
	}
?>

<div id="ebayaffinity-item-specifics-not-found" class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">No WooCommerce attributes were found.</div>

<div id="ebayaffinity-item-specifics-found" class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block ebayaffinity-item-specifics-container">
	<div class="ebayaffinity-ajax-header">Select or Create an eBay item specific for each WooCommerce Attribute</div>
	<form action="" method="post" autocomplete="off">
	<table class="ebayaffinity-inv-table ebayaffinity-itemspecific-mapping-table">
		<tr>
			<td>Woocommerce attribute</td>
			<td>eBay item specific</td>
			<td>Custom item specific</td>
		</tr>

<?php
	foreach($arrItemSpecificsMappings as $objItemSpecificMapping) {
?>
		<tr data-id="<?php print  $objItemSpecificMapping->id ?>">
		
			<td>
				<span class="ebayaffinity-inv-detail-mini-title">Woocommerce attribute</span>
<?php
$name = $objItemSpecificMapping->ecommerceItemSpecificName;
if (substr($objItemSpecificMapping->ecommerceItemSpecificName, 0, 3) === 'pa_') {
	if (isset($attributes[substr($objItemSpecificMapping->ecommerceItemSpecificName, 3)])) {
		$name = $attributes[substr($objItemSpecificMapping->ecommerceItemSpecificName, 3)];
	}
}
print esc_html($name); print substr($objItemSpecificMapping->ecommerceItemSpecificId, 0, 3) === 'pa_'?' (Global)':''; ?></td>
			<td>
				<span class="ebayaffinity-inv-detail-mini-title">eBay item specific</span>
				<?php print  affinityGetSelectForItemSpecificObj($objItemSpecificMapping) ?>
			</td>
			<td>
				<span class="ebayaffinity-inv-detail-mini-title">Custom item specific</span>
				<input <?php print empty($objItemSpecificMapping->customTypedName)?'style="display: none;"':''?> class="iptCustomItemSpecific" type="text" value="<?php print esc_html($objItemSpecificMapping->customTypedName) ?>">
			</td>
		</tr>
<?php	
	}
?>
	</table>
	</form>
</div>

<script type="text/javascript">
<?php
	foreach($arrItemSpecificsMappings as $objItemSpecificMapping) {
		echo "EbayAffinityItemSpecificsMapping.prototype.mapAffinityItemSpecificsMappings[$objItemSpecificMapping->id] = new EbayAffinityItemSpecificsMapping($objItemSpecificMapping->id, ".json_encode($objItemSpecificMapping->ecommerceItemSpecificId).", ".json_encode($objItemSpecificMapping->ecommerceItemSpecificName).", ".json_encode($objItemSpecificMapping->ebayItemSpecificId).", ".json_encode($objItemSpecificMapping->customTypedName).", ".json_encode($objItemSpecificMapping->mappedName).");\n";
	}
?>
	EbayAffinityItemSpecificsMapping.prototype.initialize();
</script>
<?php 
}

require_once(__DIR__.'/float.php');

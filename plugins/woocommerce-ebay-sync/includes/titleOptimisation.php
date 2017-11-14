<?php
require_once(__DIR__ . "/support.php");
require_once(__DIR__.'/../model/AffinityEbayInventory.php');

require_once(__DIR__.'/../model/AffinityEbayCategory.php');
?>
<form action="admin.php" autocomplete="off" id="ebayaffinity-inv-filter-form-2">
<?php
require_once(__DIR__.'/filter.php');
?>
</form>
<?php
$cats = AffinityEbayCategory::getCategoriesTitleRules();
$rules = AffinityTitleRule::getAllRules();
$attributes = AffinityEbayInventory::getAllAttributes();
$lengths = AffinityEbayInventory::titlelength();
$tmp = array();
foreach ($attributes as $k=>$v) {
	$tmp['pa_'.$k] = $v;
}
$attributes = $tmp;
require_once(__DIR__ . "/../model/AffinityItemSpecificMapping.php");
$arrItemSpecificsMappings = AffinityItemSpecificMapping::getAll();
foreach ($arrItemSpecificsMappings as $arrItemSpecificsMapping) {
	if (substr($arrItemSpecificsMapping->ecommerceItemSpecificId, 0, 3) !== 'pa_') {
		$attributes[$arrItemSpecificsMapping->ecommerceItemSpecificId] = $arrItemSpecificsMapping->ecommerceItemSpecificName;
	}
}
ksort($attributes);
$attCounts = AffinityEbayInventory::attributeCounts($attributes);
$ship_count = array();

$clengths = array();
?>
<form action="admin.php?page=ebay-sync-title-optimisation" method="post" autocomplete="off">
<?php 
foreach ($cats as $k=>$v) {
	if (empty($ship_count[$v[1]])) {
		$ship_count[$v[1]] = 0;
	}
	$ship_count[$v[1]]++;
	
	// This is the title length in the Woocommerce category.
	$clength = 	AffinityEbayInventory::getCategoryLength($k);
	$clengths[$k] = $clength[0]['clength'];
	?>
		<input type="hidden" class="ebayaffinity_catshiprules" data-length="<?php print esc_html($clength[0]['clength'])?>" data-name="<?php print esc_html($v[0])?>" id="ebayaffinity_catshiprule_<?php print esc_html($k)?>" name="ebayaffinity_catshiprule[<?php print esc_html($k)?>]" value="<?php print esc_html($v[1])?>">
<?php 
	}
?>

<script type="text/javascript">
	var ebayaffinity_attributes = <?php print json_encode($attributes)?>;
	var ebayaffinity_attCounts = <?php print json_encode($attCounts)?>;
	var ebayaffinity_lengths = <?php print json_encode($lengths)?>;
</script>

<?php 
	if ((!class_exists('DOMDocument')) || (!function_exists('simplexml_load_string'))) {
?>
<div class="ebayaffinity-titleopt">
	<div class="ebayaffinity-rules">
		<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-noimg">
			<div>No XML library</div>
			<p>This feature requires PHP to have XML support.</p>
		</div>
	</div>
</div>
<?php
		return;
	}
?>

<div class="ebayaffinity-header" style="margin-bottom: 0;">
	<span class="ebayaffinity-header-vert-mobile ebayaffinity-header-vert-mobile-wide">Customise product title</span>
</div>

<div class="ebayaffinity-titleopt">
	<div class="ebayaffinity-rules">
		<button type="button" name="bt-new-rule" class="ebayaffinity-bt-new-rule" value="Create new title rule"><span class="ebayaffinity-addruleplus">＋</span>Create new title rule</button>
		<div style="clear:both; height: 0; overflow: hidden;">&nbsp;</div>
	<?php 
$maxid = 0;
if (empty($rules)) {
?>
		<div class="ebayaffinity-no-rules">No rules as yet.</div>
<?php 
}
foreach ($rules as $rule) {
	if ($rule->id > $maxid) {
		$maxid = $rule->id;
	}
	$ruleVals = AffinityTitleRule::parseRule($rule->rule);
	
	$longprods = AffinityEbayInventory::getLong($rule->id, $ruleVals, $rule->is_default);
	if (empty($longprods)) {
		$prodLong = array(array(), 0);
	} else {
		$prodLong = AffinityEbayInventory::getBySearchCategory('', 0, 1, '', 0, '', 0, 5, false, 0, $longprods);
	}
	
	$prods = AffinityEbayInventory::getBySearchCategory('', 0, 1, '_affinity_titlerule', $rule->id, '', 0, 1000, false);
	
	$maxprodlength = 0;
	
	foreach ($cats as $k=>$v) {
	    if ($v[1] != $rule->id) {
	        continue;
	    }
	    
	    if ($clengths[$k] > $maxprodlength) {
	        $maxprodlength = $clengths[$k];
	    }
	}
	
	foreach ($prods[0] as $prod) {
	    if (strlen($prod['title']) > $maxprodlength) {
	        $maxprodlength = strlen($prod['title']);
	    }
?>
			<input type="hidden" class="ebayaffinity_prodshiprules" data-length="<?php print strlen($prod['title'])?>" data-name="<?php print esc_html($prod['title'])?>" id="ebayaffinity_prodshiprule_<?php print esc_html($prod['id'])?>" name="ebayaffinity_prodshiprule[<?php print esc_html($prod['id'])?>]" value="<?php print $rule->id?>">
<?php 
	}
	
?>
		<div class="ebayaffinity-rule-container ebayaffinity-rule-collapsed" data-id="<?php print esc_html($rule->id)?>">
			<div class="ebayaffinity-rule-header">
				<input class="ebayaffinity-rule-title" maxlength="25" name="title[<?php print esc_html($rule->id)?>]" data-orig="<?php print esc_html('Rule #'.$rule->id)?>" value="<?php print esc_html(affinity_empty($rule->title) ? 'Rule #'.$rule->id: $rule->title)?>">
				<div class="ebay-affinity-rule-default">
					<input class="ebay-affinity-rule-default-radio" <?php print empty($rule->is_default)?'':'checked'?> type="radio" id="is_default_<?php print esc_html($rule->id)?>" name="is_default" value="<?php print esc_html($rule->id)?>">
					<label class="ebay-affinity-rule-default-label" for="is_default_<?php print esc_html($rule->id)?>">Set as default</label>
				</div>
				<span class="ebayaffinity-rule-action-buttons">
					<span class="ebayaffinity-rule-action-button ebayaffinity-bt-del-template">
						<span>&nbsp;</span>
					</span><span class="ebayaffinity-rule-action-button ebayaffinity-bt-add-to-template">
						+
					</span><span class="ebayaffinity-rule-action-button ebayaffinity-bt-expand">
						<span>&nbsp;</span>
					</span><span class="ebayaffinity-rule-action-button ebayaffinity-bt-collapse">
						<span>&nbsp;</span>
					</span>
				</span>
			</div>
			
			<div class="ebayaffinity-template-container">
<?php 
	$tlength = 0;
	foreach ($ruleVals as $ruleVal) {
		if ($ruleVal['type'] === 'string') {
			$tlength += strlen($ruleVal['value']);
?>
				<div class="ebayaffinity-template-unit ebayaffinity-template-unit-edible" data-type="string" data-count="<?php print strlen($ruleVal['value'])?>">
					<a class="ebayaffinity-little-del" href="#"></a>
					<input type="hidden" name="ruleTypes[<?php print esc_html($rule->id)?>][]" value="string">
					<input type="text" name="ruleVals[<?php print esc_html($rule->id)?>][]" value="<?php print esc_html($ruleVal['value'])?>">
				</div>
<?php 
		} else {
			switch ($ruleVal['value']) {
				case 'title':
					$name = 'Product name';
					if (empty($rule->is_default)) {
					    $count = $maxprodlength;
					} else {
					    $count = intval($lengths['max']);
					}
					$ccount = intval($lengths['max']);
					$num = 2147483647;
					break;
				case 'fake-fake-condition':
					$name = 'New';
					$count = 3;
					$ccount = 3;
					$num = 0;
					break;
				default:
					$name = $attributes[$ruleVal['value']];
					$count = intval($attCounts[$ruleVal['value']][0]);
					$ccount = intval($attCounts[$ruleVal['value']][0]);
					$num = intval($attCounts[$ruleVal['value']][1]);
			}
			$tlength += $count;
			$tlength++; // Add spaces between.
?>
				<div class="ebayaffinity-template-unit" data-type="attr" data-value="<?php print esc_html($ruleVal['value'])?>" data-count="<?php print $ccount?>" data-num="<?php print $num?>">
					<a class="ebayaffinity-little-del" href="#"></a>
					<input type="hidden" name="ruleTypes[<?php print esc_html($rule->id)?>][]" value="attr">
					<input type="hidden" name="ruleVals[<?php print esc_html($rule->id)?>][]" value="<?php print esc_html($ruleVal['value'])?>">
					<?php print affinity_empty($name)?'<em>name missing</em>':esc_html($name)?> <?php print substr($ruleVal['value'], 0, 3) === 'pa_' ? ' <span class="ebayaffinity-global-mark">&nbsp;</span>' :'' ?>
				</div>
<?php 
		}
	}
	$tlength--; // Remove final ending space.
	if ($tlength < 0) {
	    $tlength = 0;
	}
?>
				<div class="ebayaffinity-template-remaining" <?php print ((80 - $tlength) < 0)?'style="color: red;"':''?>><span><?php print (80 - $tlength)?></span> <span class="ebayaffinity-template-remaining-txt">character<?php print ((80 - $tlength)==1)?'':'s'?> remaining</span></div>
			</div>
			
			<div class="ebayaffinity-setting-details">
				<div class="ebayaffinity-setting-details-category-container" data-id="<?php print $rule->id?>">
					<div class="ebayaffinity-header">
<?php 
$all = true;
foreach ($cats as $k=>$v) {
	if ($v[1] != $rule->id) {
		$all = false;
		break;
	}
}
?>
						<div class="ebayaffinity-title">Categories applied to:</div>
						<div class="ebayaffinity-bt-add-category" style="<?php print $all?'display: none;':''?>">Add category</div>
					</div>

					<div class="ebayaffinity-categories-applied-to">
<?php 
	$i = 0;

	foreach ($cats as $k=>$v) {
		if ($v[1] != $rule->id) {
			continue;
		}
		$i++
?>
					<div class="ebayaffinity-category-item" data-category-id="<?php print esc_html($k)?>">
						<div class="ebayaffinity-bt-delete">
							&times;
						</div>
						<span class="ebayaffinity-category-unit ebayaffinity-category-leaf"><?php print esc_html($v[0])?></span>
					</div>
<?php 
	}
	
	if ($i == 0) {
?>
					<div class="ebayaffinity-category-item">
						<span class="ebayaffinity-category-unit ebayaffinity-category-leaf ebayaffinity-category-none"><em>None as yet.</em></span>
					</div>
<?php 
	}
?>
					</div>
				</div>
				
				
				<div class="ebayaffinity-setting-details-product-container" data-id="<?php print $rule->id?>">
				<div class="ebayaffinity-header">
					<div class="ebayaffinity-title">Products applied to:</div>
					<div class="ebayaffinity-bt-add-products">Add products</div>
				</div>
				<div class="ebayaffinity-products-applied-to">
<?php 
if (empty($prods[0])) {
?>
					<div class="ebayaffinity-product-item">
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf ebayaffinity-product-none"><em>None as yet.</em></span>
					</div>
<?php 
} else {
	foreach ($prods[0] as $prod) {
?>
					<div class="ebayaffinity-product-item" data-product-id="<?php print esc_html($prod['id'])?>">
						<div class="ebayaffinity-bt-delete">
							&times;
						</div>
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf"><?php print esc_html($prod['title'])?></span>
					</div>
<?php 
	}
}
?>
				</div>
			</div>
				
				<?php 
if (!empty($prodLong[0])) {
?>
				
			<div class="ebayaffinity-setting-details-productlong-container" data-id="<?php print $rule->id?>">
				<div class="ebayaffinity-header">
					<div class="ebayaffinity-title">Products not listable:</div>
				</div>
				<div class="ebayaffinity-productlong">
<?php 
	foreach ($prodLong[0] as $prod) {
?>
					<div class="ebayaffinity-product-item" data-product-id="<?php print esc_html($prod['id'])?>">
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf"><?php print esc_html($prod['title'])?></span>
					</div>
<?php 
	}
	if ($prodLong[1] > 5) {
?>
					<div class="ebayaffinity-product-item">
						<span class="ebayaffinity-product-unit ebayaffinity-product-leaf ebayaffinity-product-andmore"><em>&hellip;more not listed.</em></span>
					</div>
<?php 
	}
?>
				</div>
			</div>
<?php 
}
?>
				
			</div>
		</div>
<?php 
}
?>
		<input class="ebayaffinity-settingssave" type="submit" value="Save changes" name="save">
	</div>
	
	<div class="ebayaffinity-available-attributes ebayaffinity-not-mobile">
		<div class="ebayaffinity-available-attributes-title">
			Attributes
			<a href="#" id="ebayaffinity-available-attributes-sortby" class="">&nbsp;</a>
			<div id="ebayaffinity-available-attributes-sortbox">
				<span>Sort by:</span>
				<a data-id="1" href="#" class="ebayaffinity-available-attributes-sortbox-selected">Relevancy</a>
				<a data-id="2" href="#">A-Z</a>
				<a data-id="3" href="#">Z-A</a>
				<a data-id="4" href="#">Global attributes</a>
			</div>
		</div>
		<div class="ebayaffinity-attributes-scroll">
		<div class="ebayaffinity-template-unit" data-attr="title" data-count="<?php print intval($lengths['max'])?>" data-num="2147483647">
			Product name
		</div>
<?php 

global $affinity_attCounts;
$affinity_attCounts = $attCounts;

function affinity_cmp($a, $b) {
	global $affinity_attCounts;
	
	if ($affinity_attCounts[$a][1] == $affinity_attCounts[$b][1]) {
		return 0;
	}
	return ($affinity_attCounts[$a][1] > $affinity_attCounts[$b][1]) ? -1 : 1;
}

uksort($attributes, 'affinity_cmp');

foreach ($attributes as $k=>$v) {
?>
		<div class="ebayaffinity-template-unit" data-attr="<?php print esc_html($k)?>" data-count="<?php print intval($attCounts[$k][0])?>" data-num="<?php print intval($attCounts[$k][1])?>">
			<?php print affinity_empty($v)?'<em>name missing</em>':esc_html($v)?><?php print substr($k, 0, 3) === 'pa_' ? ' <span class="ebayaffinity-global-mark">&nbsp;</span>' :'' ?>
		</div>
<?php 
}
?>
	</div>
	<div id="ebayaffinity-global-ident"><span class="ebayaffinity-global-mark">&nbsp;</span> Global attribute</div>
	</div>
</div>

<input type="hidden" name="ebayaffinity_nextid" id="ebayaffinity_nextid" value="<?php print htmlspecialchars($maxid + 1)?>">
</form>

<input type="button" id="ebayaffinity-helpopen">

<div class="ebayaffinity-rule-container ebayaffinity-rule-container-no" style="display: none;" id="ebayaffinity-helpsection">
	<a href="#">×</a>
	<div class="ebayaffinity-rule-header">
		<span class="ebayaffinity-rule-title">How to Build Title Optimisation Rules</span>
	</div>
	<div class="ebayaffinity-template-container">
		<ul class="ebayaffinity-template-container-ul">
			<li>Append WooCommerce attributes to the title by dragging and dropping them from the column on the right or clicking the plus symbol.</li>
			<li>Add text to the title rule by selecting 'Add New' and typing in the box that appears.</li>
			<li>Reorder attribute and text boxes by dragging &amp; dropping them into place.</li>
		</ul>
		<p><strong>Your WooCommerce product titles range from <?php print intval($lengths['min'])?> to <?php print intval($lengths['max'])?> characters.</strong></p>
		<p>Please ensure that your rules don't exceed the eBay product title limit of 80 characters.</p>
	</div>
</div>

<?php 

require_once(__DIR__.'/float.php');

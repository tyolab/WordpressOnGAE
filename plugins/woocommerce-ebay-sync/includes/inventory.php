<?php 
require_once(__DIR__ . "/support.php");
/*
 * We need to display some obvious errors.
 * 
 * Variants need managed stock in every variant.
 * 
 * Simple needs to be managed stock.
 * 
 */

require_once(__DIR__.'/../model/AffinityTitleRule.php');
require_once(__DIR__.'/../model/AffinityShippingRule.php');
?>

<?php 
if (!empty($_GET['id'])) {
	require_once(__DIR__.'/../ecommerce-adapters/AffinityEcommerceProduct.php');
	
	$data = AffinityEbayInventory::inventorysingle($_GET['id']);
	if (empty($data)) {
?>
<div class="ebayaffinity-header ebayaffinity-ebayaffinity-header"></div>
<p>This item could not be found.</p>
<?php
		return;
	}
	$titleRuleResult = AffinityTitleRule::generate($data['product']);
	$shipRuleResult = AffinityShippingRule::generate($data['product']);
?>
<script type="text/javascript">
	ebayaffinity_ajax_invmode = 1;
</script>

<div class="ebayaffinity-header ebayaffinity-ebayaffinity-header">
<?php 
	if (!empty($data['ebayitemid'])) {
?>
	<a href="<?php print get_option('ebayaffinity_ebaysite')?>itm/<?php print esc_html($data['ebayitemid']) ?>" target="_blank" class="ebayaffinity-header-eye"><div>View eBay Listing</div></a>
<?php 
	}
?>
	<a href="post.php?post=<?php print intval($_GET['id'])?>&amp;action=edit" class="ebayaffinity-header-eye ebayaffinity-header-cart <?php print (empty($data['ebayitemid']))?'':'ebayaffinity-header-cart-left'?>"><div>Edit Product</div></a>
	<a href="admin.php?page=ebay-sync-inventory&amp;id=<?php print intval($_GET['id'])?>&amp;sync=1" class="ebayaffinity-header-eye ebayaffinity-header-sync <?php print (empty($data['ebayitemid']))?'ebayaffinity-header-cart-left':'ebayaffinity-header-cart-left-left'?>"><div>Sync to eBay</div></a>
</div>

<?php 
$warnings = array();
if (empty($data['server_warning'])) {
	$b = array();
} else {
	$b = json_decode($data['server_warning'], true);
}
if (!empty($b)) {
	foreach ($b as $el) {
		$warnings[]= '<p>Warning: '.esc_html($el).'</p>';	
	}
}
if (empty($data['client_warning'])) {
	$b = array();
} else {
	$b = json_decode($data['client_warning'], true);
}
if (!empty($b)) {
	foreach ($b as $el) {
		$warnings[]= '<p>Warning: '.esc_html($el).'</p>';
	}
}

if (empty($data['server_error'])) {
	$a = array();
} else {
	$a = json_decode($data['server_error'], true);
}
$errors = array();
if (!empty($a)) {
	foreach ($a as $el) {
		$errors []= '<p>'.esc_html($el).'</p>';
	}
}
if (empty($data['client_error'])) {
	$a = array();
} else {
	$a = json_decode($data['client_error'], true);
}
if (!empty($a)) {
	foreach ($a as $el) {
		$errors []= '<p>'.esc_html($el).'</p>';
	}
}
$errors = array_unique($errors);
$warnings = array_unique($warnings);

$warnings_n = array();
foreach ($warnings as $warning) {
	if (stripos($warning, "re not opted-in to Business Policies") === false) {
		$warnings_n[] = $warning;
	}
}
$warnings = $warnings_n;

if ((!empty($data['ebayitemid'])) && (strlen($titleRuleResult[1]) > 80)) {
	$warnings[] = '<p>Warning: Your title is more than 80 characters long, but it will be trimmed automatically because your item is already listed.</p>';
}

$trimmedattr = get_post_meta($_GET['id'], '_affinity_trimmedattr', true);
if (!empty($trimmedattr)) {
	$warnings[] = '<p>Warning: Some of your attribute values have been trimmed so that your item can list to eBay.</p>';
}
if (count($errors) > 0 || count($warnings) > 0) {
?>
	<div class="ebayaffinity-big-error">
		<div>This product has errors</div>
<?php 
	print implode('', array_unique($warnings));
	print implode('', array_unique($errors));
?>
	</div>
<?php 
}
?>


	<div class="ebayaffinity-inv-detail" data-id="<?php print intval($_GET['id'])?>">
		
		<div class="ebayaffinity-inv-detail-main" style="table-layout: fixed;">
			<div class="ebayaffinity-setting">
				<div class="ebayaffinity-inv-detail-main-left">
						<?php print $data['img'][0]?>
				</div>
				
				<div class="ebayaffinity-inv-detail-main-right">
				
<?php 
if (count($data['imgs']) > 1) {
?>
		<div class="ebayaffinity-scrollerer-container ebayaffinity-scrollerer-container-mob">
<?php 
	if (count($data['imgs']) > 2) {
		?><div class="ebayaffinity-scrollerer-left"><span>&#x221f;</span></div><?php
	}
?>
		<div class="ebayaffinity-scrollerer">
<?php 
	foreach ($data['imgs'] as $k=>$img) {
		$h = explode('height="', $img[1]);
		$h = explode('"', $h[1]);
		$h = $h[0];
		$h = (98 - $h) / 2;
		$img[1] = str_replace(' height="', ' data-big="'.esc_html($img[0]).'" style="margin-top: '.$h.'px" height="', $img[1]);
?><div class="ebayaffinity-scrollbox <?php print empty($k)?'ebayaffinity-scrollbox-selected':''?>"><?php print $img[1]?></div><?php 
	}
?>
		</div>
<?php 
	if (count($data['imgs']) > 2) {
	?><div class="ebayaffinity-scrollerer-right"><span>&#x221f;</span></div><?php
	}
?>
		</div>
<?php 
}
?>
				
				
			
					<div class="ebayaffinity-inv-detail-title">
						<?php print esc_html($data['title'])?>
					</div>
					<div class="ebayaffinity-inv-detail-data-section">
						<table>
							<tr>
								<td>Regular Price</td>
								<td><strong><?php print $data['rrp_price']?></strong></td>
							</tr>
								<tr>
								<td>Sale Price</td>
								<td><strong><?php print $data['sale_price']?></strong></td>
							</tr>
							<tr>
								<td>eBay Price</td>
								<td><strong><?php print $data['price']?></strong></td>
							</tr>
<?php 
	if (!empty($data['sku'])) {
?>
							<tr>
								<td>SKU</td>
								<td><?php print $data['sku']?></td>
							</tr>
<?php 
	}
?>
							<tr>
								<td>eBay Item ID</td>
								<td>
<?php 
	if (!empty($data['ebayitemid'])) {
?>
								<a target="_blank" href="<?php print get_option('ebayaffinity_ebaysite')?>itm/<?php print esc_html($data['ebayitemid']) ?>"><?php print esc_html($data['ebayitemid']) ?></a>
<?php 
	}
?>
								</td>
							</tr>
							<tr data-id="<?php print esc_html($_GET['id'])?>">
								<td>Visible on eBay Store</td>
								<td><div class="ebayaffinity-switch-on-off ebayaffinity-switch-<?php print $data['blocked']==1?'off':'on'?>"><div>&nbsp;</div></div></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
<?php 
if (count($data['imgs']) > 1) {
?>
		<div class="ebayaffinity-scrollerer-container">
<?php 
	if (count($data['imgs']) > 4) {
		?><div class="ebayaffinity-scrollerer-left"><span>&#x221f;</span></div><?php
	}
?>
		<div class="ebayaffinity-scrollerer">
<?php 
	foreach ($data['imgs'] as $k=>$img) {
		$h = explode('height="', $img[1]);
		$h = explode('"', $h[1]);
		$h = $h[0];
		$h = (98 - $h) / 2;
		$img[1] = str_replace(' height="', ' data-big="'.esc_html($img[0]).'" style="margin-top: '.$h.'px" height="', $img[1]);
?><div class="ebayaffinity-scrollbox <?php print empty($k)?'ebayaffinity-scrollbox-selected':''?>"><?php print $img[1]?></div><?php 
	}
?>
		</div>
<?php 
	if (count($data['imgs']) > 4) {
	?><div class="ebayaffinity-scrollerer-right"><span>&#x221f;</span></div><?php
	}
?>
		</div>
<?php 
}
?>

		<div class="ebayaffinity-abs">
			<a href="admin.php?page=ebay-sync-title-optimisation" class="ebayaffinity-abs-edit"><span>Edit title rules</span></a>
		</div>
		<div class="ebayaffinity-inv-detail-data-section-clicker ebayaffinity-inv-detail-data-section-clicker-off ebayaffinity-inv-detail-data-section-clicker-disabled">Item title</div>
		<div class="ebayaffinity-inv-detail-data-section ebayaffinity-inv-detail-data-section-rule-box" style="display: block;">
			<form autocomplete="off" method="post" action="admin.php?page=ebay-sync-inventory&id=<?php print intval($_GET['id'])?>">
				<table class="ebayaffinity-inv-detail-data-section-rule" style="table-layout: fixed;">
					<tr>
						<th style="width: 210px;">Title customisation rule</th>
						<th>Original title</th>
						<th>New title</th>
						<th style="width: 120px;"></th>
					</tr>
					<tr>
						<td style="width: 210px;">
							<span class="ebayaffinity-inv-detail-mini-title">Title customisation rule</span>
							<select name="titlerule" class="ebayaffinity-inv-detail-sel">
								<option value="0">Inherit</option>
<?php 
$rules = AffinityTitleRule::getAllRules();
foreach ($rules as $rule) {
?>
								<option <?php print ($rule->id==$titleRuleResult[0])?'selected':''?> value="<?php print intval($rule->id)?>"><?php print esc_html(affinity_empty($rule->title) ? 'Rule #'.intval($rule->id) : $rule->title)?></option>
<?php
}
?>
							</select>
						</td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">Original title</span>
							<?php print esc_html($data['title'])?>
						</td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">New title</span>
							<?php print esc_html($titleRuleResult[1])?>
						</td>
						<td style="text-align: right !important; width: 120px;"><input type="submit" class="ebayaffinity-settingssave" value="Save"></td>
				</table>
			</form>
		</div>
		
<?php 
		require_once(__DIR__.'/../model/AffinityEbayCategory.php');
		$cats = AffinityEbayInventory::categoryset(true, true);
		$eids = array();
		if (!empty($data['ebaycategory'])) {
			$eids[] = $data['ebaycategory'];
		}
		foreach ($data['categories'] as $v) {
			if (!empty($v['ebaycategory'])) {
				$eids[] = $v['ebaycategory'];
				break;
			}
		}
		if (!empty($eids)) {
			$ecats = AffinityEbayCategory::getAlleBay($eids);
		}
		$ecattxt = '';
		foreach ($eids as $eid) {
			foreach ($ecats as $ecat) {
				if ($eid == $ecat->categoryId) {
					if (empty($ecattxt)) {
						$ecattxt = $ecat->catname;
					}
				}
			}
		}
?>		
		<div class="ebayaffinity-inv-detail-data-section-clicker ebayaffinity-inv-detail-data-section-clicker-off ebayaffinity-inv-detail-data-section-clicker-disabled">Categories</div>
		<div class="ebayaffinity-inv-detail-data-section ebayaffinity-inv-detail-data-section-rule-box" style="display: block;">
			<form action="admin.php?page=ebay-sync-inventory&id=14" method="post" autocomplete="off">
				<table class="ebayaffinity-inv-detail-data-section-rule">
					<tr>
						<th>Original category</th>
						<th>eBay category</th>
					<tr>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">Original category</span>
<?php 
	foreach ($data['categories'] as $k=>$v) {
?> 
							<p><?php print AffinityEbayCategory::showWooCatName($cats, $k, true)?><span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span></p>
<?php 
	}
?>
						</td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">eBay category</span>
							<p>
								<?php print str_replace('&gt;', '<span class="ebayaffinity-rightblue">&#x203a;</span>', esc_html($ecattxt))?><span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span>
							</p>
						</td>
						<td><div class="ebayaffinity-inv-detail-sel ebayaffinity-prod-map-cat">Map to eBay category</div></td>
					</tr>
				</table>
			</form>
		</div>
		
<?php 
	if (count($data['variations']) > 0) {
?>		
		<div class="ebayaffinity-inv-detail-data-section-clicker ebayaffinity-inv-detail-data-section-clicker-off ebayaffinity-inv-detail-data-section-clicker-disabled">Variations</div>
		<div class="ebayaffinity-inv-detail-data-section ebayaffinity-inv-detail-data-section-rule-box ebayaffinity-inv-detail-data-section-rule-box-nopad" style="display: block;">
			<form autocomplete="off" method="post" action="admin.php?page=ebay-sync-inventory&id=<?php print intval($_GET['id'])?>">
				<table class="ebayaffinity-inv-detail-data-section-rule ebayaffinity-inv-detail-data-section-rule-lined">
					<tr>
						<th>&nbsp;</th>
						<th>SKU</th>
						<th>Regular price</th>
						<th>Sale price</th>
						<th>eBay price</th>
<?php 
		$karr = array();
		foreach ($data['variations'] as $v) {
			foreach ($v['attributes'] as $kk=>$vv) {
				$karr[] = $kk;
?>
						<th><?php print esc_html($kk)?></th>
<?php 	
			}
			break;
		}
?>
					</tr>
<?php 
		foreach ($data['variations'] as $v) {
?>
					<tr>
						<td><?php print $v['img']?></td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">SKU</span>
							<?php print esc_html($v['sku'])?>
						</td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">Regular price</span>
							<?php print $v['rrp_price']?>
						</td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">Sale price</span>
							<?php print $v['sale_price']?>
						</td>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">eBay price</span>
							<?php print $v['price']?>	
						</td>
<?php 
			foreach ($karr as $el) {
?>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title"><?php print esc_html($el)?></span>
							<?php print $v['attributes'][$el]?>
						</td>
<?php 
			}
?>
					</tr>
<?php 
		}
?>
				</table>
			</form>
		</div>
<?php 
	}
	
	$rules = AffinityShippingRule::getAllRules();
?>

		<div class="ebayaffinity-abs">
			<a href="admin.php?page=ebay-sync-settings&amp;pnum=3" class="ebayaffinity-abs-edit"><span>Edit shipping options</span></a>
		</div>
		<div class="ebayaffinity-rule-container">
			<div class="ebayaffinity-inv-detail-data-section-clicker ebayaffinity-inv-detail-data-section-clicker-off ebayaffinity-inv-detail-data-section-clicker-disabled">Shipping</div>
			<div class="ebayaffinity-inv-detail-data-section ebayaffinity-inv-detail-data-section-rule-box" style="display: block;">
				<form autocomplete="off" method="post" action="admin.php?page=ebay-sync-inventory&id=<?php print intval($_GET['id'])?>">
					<table class="ebayaffinity-inv-detail-data-section-rule">
						<tr>
							<th>Shipping option</th>
							<th>Standard shipping price</th>
							<th>Express shipping price</th>
							<th></th>
						</tr>
						<tr>
							<td>
								<span class="ebayaffinity-inv-detail-mini-title">Shipping option</span>
								<select name="shiprule" class="ebayaffinity-inv-detail-sel">
									<option value="0">Inherit</option>						
	<?php 
	$rules = AffinityShippingRule::getAllRules();
	foreach ($rules as $rule) {
	?>
									<option <?php print ($rule->id==($shipRuleResult['ruleid']))?'selected':''?> value="<?php print intval($rule->id)?>">Shipping rule #<?php print intval($rule->id)?></option>
	<?php
	}
	?>
								</select>
							</td>
							<td>
								<span class="ebayaffinity-inv-detail-mini-title">Standard shipping price</span>
								<?php print esc_html(empty($shipRuleResult['standard_freeshipping'])?('$'.$shipRuleResult['standard_fee']):'FREE')?>
							</td>
							<td>
								<span class="ebayaffinity-inv-detail-mini-title">Express shipping price</span>
								<?php print esc_html(empty($shipRuleResult['express_freeshipping'])?('$'.$shipRuleResult['express_fee']):'FREE')?>
							</td>
							<td style="text-align: right !important;"><input type="submit" class="ebayaffinity-settingssave" value="Save"></td>
					</table>
				</form>
			</div>
		</div>
		
		<div class="ebayaffinity-inv-detail-data-section-clicker ebayaffinity-inv-detail-data-section-clicker-off ebayaffinity-inv-detail-data-section-clicker-disabled">Item descriptions</div>
		<div class="ebayaffinity-inv-detail-data-section ebayaffinity-inv-detail-data-section-rule-box ebayaffinity-inv-detail-data-section-rule-box-desc" style="display: block;">
			<form action="admin.php?page=ebay-sync-inventory&id=14" method="post" autocomplete="off">
				<table>
					<tr>
						<th></th>
					</tr>
					<tr>
						<th>WooCommerce description</th>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">WooCommerce description</span>
							<div class="ebayaffinity-inv-detail-desc"><?php print $data['desc']?></div>
						</td>
					</tr>
					<tr>
						<th>eBay description</th>
						<td>
							<span class="ebayaffinity-inv-detail-mini-title">eBay description</span>
<?php 
		if (empty($data['ebaydesc'])) {
?>
							This can be created on the edit product page: <a href="post.php?post=<?php print intval($_GET['id'])?>&amp;action=edit">here</a>.
<?php 	
		} else {
?>
							<div class="ebayaffinity-inv-detail-desc"><?php print $data['ebaydesc']?></div>
<?php 
		}
?>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
<?php 	
} else {
	$data = AffinityEbayInventory::inventoryset();
	$found = $data[1];
	$minprice = $data[2];
	$maxprice = $data[3];
	$data = $data[0];
	
	$uri = explode('?', $_SERVER['REQUEST_URI']);
	$uri = $uri[0];
	
?>
	<form action="admin.php" autocomplete="off" id="affinity-invform">
		<input type="hidden" name="page" value="ebay-sync-inventory">
		<div class="ebayaffinity-header">
			<span>Inventory</span>
			<em><?php print intval($found)?> product<?php print ($found==1)?'':'s'?></em>
		</div>
		
		<div class="ebayaffinity-unmapped ebayaffinity-inv-block">
			<input class="ebayaffinity-filter-button ebayaffinity-mate" value="Filter" type="button">
			<select name="selectinvvisprodall" class="ebayaffinity-selecta ebayaffinity-mate" style="display: inline-block;">
				<option value="0" selected="">Visibility</option>
				<option value="1">On</option>
				<option value="2">Off</option>
			</select>
			<select name="ebayaffinity-perpage" class="ebayaffinity-selecta ebayaffinity-mate" style="display: inline-block;">
				<option value="10" <?php print empty($_GET['ebayaffinity-perpage']) ? 'selected' : ''; ?>>10 items</option>
				<option value="25" <?php print ($_GET['ebayaffinity-perpage'] == 25) ? 'selected' : ''; ?>>25 items</option>
				<option value="50" <?php print ($_GET['ebayaffinity-perpage'] == 50) ? 'selected' : ''; ?>>50 items</option>
				<option value="100000" <?php print ($_GET['ebayaffinity-perpage'] == 100000) ? 'selected' : ''; ?>>All items</option>
			</select>
			<input id="ebayaffinity-searchcat" class="ebayaffinity-mate2" value="<?php print esc_html(stripslashes($_GET['s']))?>" name="s" placeholder="Search for a product" style="margin: 0;" type="text">
			<div class="clearall">&nbsp;</div>
		</div>
	</form>
	
	<form action="admin.php" autocomplete="off" id="ebayaffinity-inv-filter-form">
		<input type="hidden" name="ebayaffinity-perpage" value="<?php print $_GET['ebayaffinity-perpage']?>">
<?php 
require_once(__DIR__.'/filter.php');
?>
	</form>
	
	<form action="admin.php?page=ebay-sync-inventory" autocomplete="off" method="post">
	<div class="ebayaffinity-inv-block">
		<table class="ebayaffinity-inv-table ebayaffinity-inv-table-nar">
			<tr>
				<th><input type="checkbox" id="invvisprodall"></th>
				<th colspan="2">Product</th>
				<th class="ebayaffinity-not-mobile">Regular Price</th>
				<th class="ebayaffinity-not-mobile">Sale Price</th>
				<th>eBay Price</th>
				<th class="ebayaffinity-not-mobile ebayaffinity-not-tablet">SKU</th>
				<th class="ebayaffinity-not-mobile">Visibility</th>
				<th class="ebayaffinity-not-mobile">Listing Error</th>
			</tr>
			
<?php 
	foreach ($data as $datum) {
?>
			<tr data-id="<?php print esc_html($datum['id'])?>">
				<td><input type="checkbox" class="invvisprod"></td>
				<td>
					<a href="admin.php?page=ebay-sync-inventory&amp;id=<?php print $datum['id']?>">
						<?php print $datum['img']?>
					</a>
				</td>
				<td>
					<a href="admin.php?page=ebay-sync-inventory&amp;id=<?php print $datum['id']?>">
						<?php print esc_html($datum['title'])?>
					</a>
				</td>
				<td class="ebayaffinity-not-mobile"><?php print $datum['rrp_price']?></td>
				<td class="ebayaffinity-not-mobile"><?php print $datum['sale_price']?></td>
				<td><?php print $datum['price']?></td>
				<td class="ebayaffinity-not-mobile ebayaffinity-not-tablet"><?php print esc_html($datum['sku'])?></td>
				<td class="ebayaffinity-not-mobile"><div class="ebayaffinity-switch-on-off ebayaffinity-switch-<?php print $datum['blocked']==1?'off':'on'?>"><div>&nbsp;</div></div></td>
				<td class="ebayaffinity-not-mobile">
<?php 
		if (!empty($datum['lasterror'])) {
?>
			<div class="ebayaffinity-lasterror"><div><?php print nl2br(esc_html($datum['lasterror']))?></div></div>
<?php 	
		}
?>
				</td>
			</tr>
<?php 
	}
?>
		</table>
<?php 
	global $wp_query, $wp_rewrite;
	
	if ($wp_query->max_num_pages > 1) {
		$format  = '';
		$format .= '?paged=%#%';
		
?>
	<nav class="woocommerce-pagination">
		<?php
			echo paginate_links( apply_filters( 'woocommerce_pagination_args', array(
				'base'         => esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) ),
				'format'       => $format,
				'add_args'     => false,
				'current'      => max( 1, get_query_var( 'paged' ) ),
				'total'        => $wp_query->max_num_pages,
				'prev_text'    => '&larr;',
				'next_text'    => '&rarr;',
				'type'         => 'list',
				'end_size'     => 3,
				'mid_size'     => 3
			) ) );
		?>
	</nav>
<?php 
	}
	
		wp_reset_postdata();

	?>
	</div>
	</form>
<?php
}

require_once(__DIR__.'/float.php');

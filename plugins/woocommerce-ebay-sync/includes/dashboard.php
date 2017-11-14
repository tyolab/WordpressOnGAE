<?php 
require_once(__DIR__ . "/support.php");
require_once(__DIR__.'/../model/AffinityEbayInventory.php');
require_once(__DIR__.'/../model/AffinityEbayCategory.php');
require_once(__DIR__.'/../model/AffinityTitleRule.php');

$prods = AffinityEbayInventory::allProducts();
$allprods_count = $prods[1];

$prods = AffinityEbayInventory::noEbayCategoryProducts();
$nocat_prods = $prods[1];

$prods = AffinityEbayInventory::errorProducts();
$errors_prods = $prods[1];

$prods = AffinityEbayInventory::blockedProducts();
$blocked_prods = $prods[1];

$prods = AffinityEbayInventory::listedProducts();
$listed_prods = $prods[1];

$prods = AffinityEbayInventory::notListedProducts();
$notlisted_prods = $prods[1];

$prods = AffinityEbayInventory::notOptimisedProducts();
$notopt_prods = $prods[1];

if ($allprods_count == 0) {
	$percentage = 0;
} else {
	$percentage = (($allprods_count - (($notopt_prods + $errors_prods + $nocat_prods) / 3)) / $allprods_count) * 100;
}

if ($percentage < 0) {
	$percentage = 0;
}

$revenue = AffinityEbayInventory::getDashboardCountRevenue();

$setup1 = get_option('ebayaffinity_setup1');
$setup2 = get_option('ebayaffinity_setup2');
$setup3 = get_option('ebayaffinity_setup3');
$setup4 = get_option('ebayaffinity_setup4');
$setup5 = get_option('ebayaffinity_setup5');
$agree = get_option('ebayaffinity_agree');

if (!empty($agree)) { 
	if (empty($setup1) || empty($setup2) || empty($setup3) || empty($setup4)) {
		$purl = '';
		if (empty($setup1)) {
			$purl = 'admin.php?page=ebay-sync-settings';
		} else if (empty($setup2)) {
			$purl = 'admin.php?page=ebay-sync-settings&pnum=2';
		} else if (empty($setup3)) {
			$purl = 'admin.php?page=ebay-sync-settings&pnum=3';
		} else if (empty($setup4)) {
			$purl = 'admin.php?page=ebay-sync-mapping';
		}
	?>
	<div class="ebayaffinity-header-setup-black">&nbsp;</div>
	<a href="<?php print esc_html($purl)?>" class="ebayaffinity-header-setup">
		<span>Welcome to eBay Sync</span>
		<em>Before you can begin using eBay Sync and listing your products on eBay, you need to complete your store setup.</em>
		<strong>Complete store setup</strong>
	</a>
	<?php 
	}
}
?>

<div class="ebayaffinity-header">
<?php 
if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4)) && (!empty($setup5))) {
?>
	<a id="ebayaffinity_auth" class="ebayaffinity_auth_sync" href="admin.php?page=ebay-sync&amp;sync=1">Sync to eBay</a>
<?php 
}
$userid = get_option('ebayaffinity_ebayuserid');
if (!empty($userid)) {
	$ebayurl = get_option('ebayaffinity_ebaysite');
	$surl = $ebayurl.'usr/'.rawurlencode($userid);
	?>
		<a id="ebayaffinity_visit" class="ebayaffinity_visit" target="_blank" href="<?php print esc_html($surl)?>">Visit eBay</a>
	<?php 
}
?>
	<span class="ebayaffinity-header-vert-mobile">Dashboard</span>
</div>

<script type="text/javascript">
	var affinity_dashboard_hour_data = <?php print json_encode(AffinityEbayInventory::getDashboardOrdersPerHour())?>;
	var affinity_dashboard_month_data = <?php print json_encode(AffinityEbayInventory::getDashboardOrdersPerMonth())?>;
	var affinity_dashboard_day_data = <?php print json_encode(AffinityEbayInventory::getDashboardOrdersPerDay())?>;
</script>

<?php 
if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4)) && (!empty($setup5))) {
	require_once(__DIR__ . "/../model/AffinityProduct.php");
	require_once(__DIR__ . "/../service/AffinityBackendService.php");
	$arrInventorySummary = AffinityProduct::getInventorySummary();
	$arrEbayLimits = AffinityBackendService::getSellerLimits();
	
	if (get_option('ebayaffinity_ebaysite') === 'http://www.au.paradise.qa.ebay.com/') {
		$arrEbayLimits = array(
				'products' => '100', 
				'value' => '100'
		);
	}
	
	if ((!empty($arrInventorySummary)) && (!empty($arrEbayLimits))) {
		if (($arrEbayLimits['products'] < $arrInventorySummary['totalProducts']) || ($arrEbayLimits['value'] < $arrInventorySummary['totalValue'])) {
?>
<div class="ebayaffinity-big-error">
	<div>Seller limits too low</div>
	<p>In order to list all of your items, you will need to increase the limits on your eBay account: <a target="_blank" href="https://scgi.ebay.com/ws/eBayISAPI.dll?UpgradeLimits">https://scgi.ebay.com/ws/eBayISAPI.dll?UpgradeLimits</a></p>
</div>
<?php 
		}
	}
}
?>
<div class="ebayaffinity-1-2 ebayaffinity-row">
	<div class="ebayaffinity-cell">
		<div class="ebayaffinity-block">
			<div class="ebayaffinity-block-header">To do list</div>
			<div class="ebayaffinity-pie-chart" data-percent="<?php print intval($percentage)?>" data-colour="#f9bd25" data-msg="1"></div>
			<div class="ebayaffinity-todo-links">
				<a href="admin.php?page=ebay-sync-inventory&amp;showneedsmapping=1&amp;justfiltered=1" class="ebayaffinity-todo-mapping"><?php print intval($nocat_prods)?> <small>product<?php print $nocat_prods==1?'':'s'?></small><span>Need<?php print $nocat_prods==1?'s':''?> category mapping</span></a>
				<a href="admin.php?page=ebay-sync-inventory&amp;showerrors=1&amp;justfiltered=1" class="ebayaffinity-todo-errors"><?php print intval($errors_prods)?> <small>product<?php print $errors_prods==1?'':'s'?></small><span><?php print $errors_prods==1?'Has':'Have'?> listing errors</span></a>
				<a href="admin.php?page=ebay-sync-inventory&amp;shownotitleopt=1&amp;justfiltered=1" class="ebayaffinity-todo-optimise"><?php print intval($notopt_prods)?> <small>product<?php print $notopt_prods==1?'':'s'?></small><span>Need<?php print $notopt_prods==1?'s':''?> title optimisation</span></a>
			</div>
			
		</div>
	</div>
	<div class="ebayaffinity-cell">
		<div class="ebayaffinity-block">
			<div class="ebayaffinity-block-header">Orders</div>
			<div class="ebayaffinity-bar-chart">
				<div class="ebayaffinity-bar-chart-tabs">
					<div class="ebayaffinity-bar-chart-left-tab">
						<div class="ebayaffinity-chart-periods">
							<div class="ebayaffinity-chart-period" data-period="today">
								<div class="ebayaffinity-chart-period-label no-text-selection">Today</div>
								<div class="ebayaffinity-chart-period-underline"></div>
							</div>
							<div class="ebayaffinity-chart-period" data-period="week">
								<div class="ebayaffinity-chart-period-label no-text-selection">Week</div>
								<div class="ebayaffinity-chart-period-underline"></div>
							</div>
							<div class="ebayaffinity-chart-period" data-period="month">
								<div class="ebayaffinity-chart-period-label no-text-selection">Month</div>
								<div class="ebayaffinity-chart-period-underline"></div>
							</div>
							<div class="ebayaffinity-chart-period" data-period="year">
								<div class="ebayaffinity-chart-period-label no-text-selection">Year</div>
								<div class="ebayaffinity-chart-period-underline"></div>
							</div>
						</div>
					</div>
					<div class="ebayaffinity-bar-chart-right-tab">
						<a class="ebayaffinity-view-orders no-text-selection" href="edit.php?post_type=shop_order&amp;affinity_ebayorders=1">View orders</a>
					</div>
					<div style="clear:both;"></div>
				</div>
				<div class="ebayaffinity-bar-chart-body" data-type="today"></div>
				<div class="ebayaffinity-bar-chart-body ebayaffinity-bar-chart-body-showing" data-type="week"></div>
				<div class="ebayaffinity-bar-chart-body" data-type="month"></div>
				<div class="ebayaffinity-bar-chart-body" data-type="year"></div>
			</div>
			<div class="ebayaffinity-stat-items-out">
				<div class="ebayaffinity-stat-items">
					<div class="ebayaffinity-stat-item">
						<span>Total number of orders</span>
						<?php print empty($revenue)?0:$revenue->counte; ?>
					</div>
					<div class="ebayaffinity-stat-item">
						<span>Average order value</span>
<?php 
if (empty($revenue)) {
	$disp = 0;
} else {
	if ($revenue->counte > 0) {
		$disp = $revenue->revenue / $revenue->counte;
		if (floatval($disp) == intval($disp)) {
			$disp = intval($disp);
		} else {
			$disp = number_format($disp, 2, '.', ',');
		}
	} else {
		$disp = 0;
	}
}
?>
						$<?php print $disp?>
					</div>
					<div class="ebayaffinity-stat-item">
						<span>Total revenue</span>
<?php 
if (empty($revenue)) {
	$disp = 0;
} else {
	$disp = $revenue->revenue;
	if (floatval($disp) == intval($disp)) {
		$disp = intval($disp);
	} else {
		$disp = number_format($disp, 2, '.', ',');
	}
}
?>
						$<?php print $disp?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="ebayaffinity-4 ebayaffinity-row">
	<div class="ebayaffinity-cell">
		<div class="ebayaffinity-block">
			<div class="ebayaffinity-block-header">eBay Store Overview</div>
			<div class="ebayaffinity-pie-table">
				<a class="ebayaffinity-pie-table-cell" href="admin.php?page=ebay-sync-inventory">
					<div class="ebayaffinity-pie-chart" data-total="<?php print intval($allprods_count)?>" data-number="<?php print intval($allprods_count)?>" data-colour="#a5e120"></div>
					<span>Total products</span>
					in eBay Sync
				</a>
				<a class="ebayaffinity-pie-table-cell" href="admin.php?page=ebay-sync-inventory&amp;showonebay=1">
					<div class="ebayaffinity-pie-chart" data-total="<?php print intval($allprods_count)?>" data-number="<?php print intval($listed_prods)?>" data-colour="#f9bd25"></div>
					<span>Product<?php print $listed_prods==1?'':'s'?> listed</span>
					on your eBay store
				</a>
				<a class="ebayaffinity-pie-table-cell" href="admin.php?page=ebay-sync-inventory&amp;shownotonebay=1">
					<div class="ebayaffinity-pie-chart" data-total="<?php print intval($allprods_count)?>" data-number="<?php print intval($notlisted_prods)?>" data-colour="#2487f4"></div>
					<span>Product<?php print $notlisted_prods==1?'':'s'?> not listed</span>
					on your eBay store
				</a>
				<a class="ebayaffinity-pie-table-cell" href="admin.php?page=ebay-sync-inventory&amp;showunblocked=0&amp;showblocked=1">
					<div class="ebayaffinity-pie-chart" data-total="<?php print intval($allprods_count)?>" data-number="<?php print intval($blocked_prods)?>" data-colour="#f96166"></div>
					<span>Blocked product<?php print $blocked_prods==1?'':'s'?></span>
					on your eBay store
				</a>
			
			</div>
		</div>
	</div>
</div>

<?php 
if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4)) && (!empty($setup5))) {
?>
<div class="ebayaffinity-4 ebayaffinity-row">
	<div class="ebayaffinity-cell">
		<div class="ebayaffinity-block">
			<div class="ebayaffinity-bar-chart-right-tab ebayaffinity-store-upgrade">
				<a target="_blank" href="https://scgi.ebay.com/ws/eBayISAPI.dll?UpgradeLimits" class="ebayaffinity-view-orders ebayaffinity-request-limit">Request a limit increase</a>
			</div>
			<div class="ebayaffinity-block-header">
				Store Limits
				<div class="ebayaffinity-questiondiv ebayaffinity-questiondivinline ebayaffinity-questiondivinliner">
					<div class="ebayaffinity-question"><span class="info">You may have limits placed on your account or on particular categories and items until you confirm certain information or establish a positive selling history. These limits help you become a more successful seller, in addition to ensuring a safer experience for all eBay members.
					<br><br>
					For more information about store limits go to <a href="http://pages.ebay.com.au/help/sell/sellinglimits.html" target="_blank">http://pages.ebay.com.au/help/sell/sellinglimits.html</a></span>?</div>
			</div>
			</div>
			<div class="ebayaffinity-seller-limits">
				<?php
				if($arrEbayLimits === FALSE):
					echo "No limits found for your eBay user!";
				else:
					$prodPerc = ($arrInventorySummary['totalProducts'] / $arrEbayLimits['products']) * 100;
					$valPerc = ($arrInventorySummary['totalValue'] / $arrEbayLimits['value']) * 100;
					
					if ($prodPerc > 100) {
						$prodPerc = 100;
					}
					if ($valPerc > 100) {
						$valPerc = 100;
					}
				?>
					<div>
						<div class="ebayaffinity-seller-limits-head">Products</div>
						<div style="background-color: #F1F1F1; height: 30px; width: 100%;">
							<div style="background-color: #A4E020; height: 30px; width: <?php print floatval($prodPerc)?>%;"></div>
						</div>
						<div class="ebayaffinity-seller-limits-numprod">Number of products: <strong><?php print $arrInventorySummary['totalProducts']?></strong>
							<span>
								Limit: <strong><?php print $arrEbayLimits['products']?></strong>
							</span>
						</div>
					</div>
					
					<div>
						<div class="ebayaffinity-seller-limits-head">Value (AUD)</div>
						<div style="background-color: #F1F1F1; height: 30px; width: 100%;">
							<div style="background-color: #FBC131; height: 30px; width: <?php print floatval($valPerc)?>%;"></div>
						</div>
						<div class="ebayaffinity-seller-limits-numval">Value of products: <strong>$<?php print number_format($arrInventorySummary['totalValue'], 2)?></strong>
							<span>
								Limit: <strong>$<?php print number_format($arrEbayLimits['value'], 2)?></strong>
							</span>
						</div>
					</div>
				<?php
				endif;
				?>
			</div>
		</div>
	</div>
</div>
<?php 
}
?>

<?php 
if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4)) && empty($setup5)) {
	require_once(__DIR__.'/float.php');
} else if (empty($agree)) {
	require_once(__DIR__.'/float.php');
}

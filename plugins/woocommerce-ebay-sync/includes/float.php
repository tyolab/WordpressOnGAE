<?php 
$setup_ebayuserid = get_option('ebayaffinity_ebayuserid');
$setup_token = get_option('affinityPushAccessToken');
$setup_paypal = get_option('ebayaffinity_paypal');
$setup_agree = get_option('ebayaffinity_agree');

$setup0 = (!empty($setup_ebayuserid)) && (!empty($setup_token)) && (!empty($setup_paypal));
$setup1 = get_option('ebayaffinity_setup1');
$setup2 = get_option('ebayaffinity_setup2');
$setup3 = get_option('ebayaffinity_setup3');
$setup4 = get_option('ebayaffinity_setup4');
$setup5 = get_option('ebayaffinity_setup5');

if (empty($_GET['pnum'])) {
	$_GET['pnum'] = '';
}

if (empty($setup0) && $_GET['page'] === 'ebay-sync-settings' && (!empty($_GET['pnum']))) {
	wp_redirect('admin.php?page=ebay-sync-settings');
	print "<script>location.href = 'admin.php?page=ebay-sync-settings';</script>";
	exit();
}

if (empty($setup_agree)) {
?>
	<div class="ebayaffinity-header-setup-black">&nbsp;</div>
	<div class="ebayaffinity-header-setup ebayaffinity-header-setup-agree">
		<span>Welcome to eBay Sync</span>
		<iframe src="<?php print untrailingslashit(plugins_url('/../assets/agreement.html', __FILE__ ));?>"></iframe>
		<a href="index.php" class="ebayaffinity-donotagree">I do not agree</a>
		<a href="admin.php?page=ebay-sync-settings&amp;agree=1"  class="ebayaffinity-agree">I agree</a>
		<div style="clear: both; height: 0; overflow: hidden;">&nbsp;</div>
	</div>
<?php 
} else if (empty($setup0) && $_GET['page'] !== 'ebay-sync-settings') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<a class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed" href="admin.php?page=ebay-sync-settings">
	You need to complete your store setup <strong class="ebay-affinity-rot">&#x221f;</strong>
</a>
<?php 
} else if (empty($setup0) && empty($_GET['pnum']) && $_GET['page'] === 'ebay-sync-settings') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step">
	<span class="ebayaffinity-big-error-settings-step">STEP 1</span>Connect eBay Sync to your eBay account
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
</div>
<?php 
} else if ((!empty($setup0)) && empty($setup1) && empty($_GET['pnum'])) {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<a class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step" href="admin.php?page=ebay-sync-settings&amp;pnum=2">
	Now let's customise your store
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
	<span class="ebayaffinity-big-error-settings-next">
		Next <span class="ebayaffinity-big-error-settings-next-rot">&#x221f;</span>
	</span>
</a>
<?php 
} else if (empty($setup1) && $_GET['pnum'] == 2 && $_GET['page'] === 'ebay-sync-settings') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step">
	<span class="ebayaffinity-big-error-settings-step">STEP 2</span>Upload your store logo
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
</div>
<?php 		
} else if ((!empty($setup1)) && empty($setup2) && $_GET['pnum'] != 3) {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<a class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step" href="admin.php?page=ebay-sync-settings&amp;pnum=3">
	Now let's add a shipping method
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
	<span class="ebayaffinity-big-error-settings-next">
		Next <span class="ebayaffinity-big-error-settings-next-rot">&#x221f;</span>
	</span>
</a>
<?php 
} else if (empty($setup2) && $_GET['pnum'] == 3 && $_GET['page'] === 'ebay-sync-settings') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step">
	<span class="ebayaffinity-big-error-settings-step">STEP 3</span>Add a shipping method
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
</div>
<?php 		
} else if ((!empty($setup2)) && empty($setup3) && $_GET['pnum'] != 4) {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<a class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step" href="admin.php?page=ebay-sync-settings&amp;pnum=4">
	Now let's enter your returns information
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
	<span class="ebayaffinity-big-error-settings-next">
		Next <span class="ebayaffinity-big-error-settings-next-rot">&#x221f;</span>
	</span>
</a>
<?php 
} else if (empty($setup3) && $_GET['pnum'] == 4 && $_GET['page'] === 'ebay-sync-settings') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step">
	<span class="ebayaffinity-big-error-settings-step">STEP 4</span>Enter your returns information
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
</div>
<?php 

} else if ((!empty($setup3)) && empty($setup4) && $_GET['page'] !== 'ebay-sync-mapping') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<a class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step" href="admin.php?page=ebay-sync-mapping">
	Now let's map your categories
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-off">&nbsp;</span>
	</span>
	<span class="ebayaffinity-big-error-settings-next">
		Next <span class="ebayaffinity-big-error-settings-next-rot">&#x221f;</span>
	</span>
</a>
<?php 
} else if (empty($setup4) && $_GET['page'] === 'ebay-sync-mapping') {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<div class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-step">
	<span class="ebayaffinity-big-error-settings-step">STEP 5</span>Map your WooCommerce categories to eBay categories
	<span class="ebayaffinity-big-error-settings-dots">
		<span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span><span class="ebayaffinity-big-error-settings-dots-on">&nbsp;</span>
	</span>
</div>
<?php 		
} else if ((!empty($setup4)) && empty($setup5)) {
?>
<div class="ebayaffinity-big-error-settings-blah">&nbsp;</div>
<a class="ebayaffinity-big-error ebayaffinity-big-error-settings ebayaffinity-big-error-settings-fixed ebayaffinity-big-error-settings-fixed-final" href="admin.php?page=ebay-sync&amp;sync=1">
	<span>Your store setup is almost complete!</span>
	<small>Hit the sync button to send your products to eBay. You only need to do this once. All future product updates will be synced automatically.</small> <em>Sync to eBay</em>
</a>
<?php 
}

<?php
require_once(__DIR__ . "/support.php");
require_once(__DIR__ . "/../service/AffinityBackendService.php");
$hasFinished = AffinityProduct::endAllListingsHasFinished();
if($hasFinished) {
	wp_redirect("admin.php?page=ebay-sync-settings");
	print "<script>location.href = 'admin.php?page=ebay-sync-settings';</script>";
	exit();
}
?>

<div class="ebay-affinity-acc-blocked">
	<script>
		jQuery('document').ready(function() {
			jQuery("#btCheckAgainIfBlocked").click(function() {
				location.reload(true);
				return false;
			});
		});
	</script>
	<div class="ebayaffinity-header">Account Blocked</div>
	
	<div>
		<h2>The plugin account has been blocked until we finish deleting the previously created listings.</h2>
		<h2>This operation may take several minutes.</h2>
		
		<input id="btCheckAgainIfBlocked" value="Check again" class="ebayaffinity-settingssave">
	</div>
</div>

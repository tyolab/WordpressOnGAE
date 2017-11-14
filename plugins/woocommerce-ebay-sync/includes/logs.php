<?php
require_once(__DIR__ . "/support.php");
if (empty($_GET['startdate'])) {
	$_GET['startdate'] = date('d/m/Y');
}
if (empty($_GET['enddate'])) {
	$_GET['enddate'] = date('d/m/Y');
}
$block = 50;
require_once(__DIR__ . "/../model/AffinityLog.php");
$_GET['p'] = intval(empty($_GET['p'])?0:$_GET['p']);

$arrObjLogs = AffinityLog::getAll($block, $_GET['p'] * $block, empty($_GET['s'])?'':$_GET['s'], empty($_GET['startdate'])?'':$_GET['startdate'], empty($_GET['enddate'])?'':$_GET['enddate']);
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

if (!empty($_GET['did'])) {
	require_once(__DIR__ . "/../model/AffinityProduct.php");
	require_once(__DIR__ . "/../service/AffinityBackendService.php");
	require_once(__DIR__. '/../model/AffinityGlobalOptions.php');
	require_once(__DIR__. '/../model/AffinityProduct.php');
	$objEcommerceProduct = AffinityEcommerceProduct::get($_GET['did']);
	$affinityProduct = AffinityProduct::transformFromEcommerceProduct($objEcommerceProduct);
	AffinityProduct::multiVariationListingWasEnded($affinityProduct);
}
?>

<form autocomplete="off" action="admin.php">
	<input type="hidden" value="ebay-sync-logs" name="page">
	<div class="ebayaffinity-header">
		<span class="ebayaffinity-header-vert-mobile">Logs</span>
	</div>
	<div class="ebayaffinity-inv-block">
	<table class="ebayaffinity-settingsblock">
		<tr>
			<td style="padding: 10px;">
				<label for="s">Search:</label>
			</td>
			<td style="padding: 10px;">
				<input type="text" name="s" id="s" value="<?php print esc_html(stripslashes(empty($_GET['s'])?'':$_GET['s']))?>">
			</td>
		</tr>
		<tr>
			<td style="padding: 10px;">
				<label for="startdate">Start date:</label>
			</td>
			<td style="padding: 10px;">
				<input type="text" id="startdate" name="startdate" value="<?php print htmlspecialchars(empty($_GET['startdate'])?'':$_GET['startdate'])?>"/>
			</td>
		</tr>
		<tr>
			<td style="padding: 10px;">
				<label for="enddate">End date:</label>
			</td>
			<td style="padding: 10px;">
				<input type="text" id="enddate" name="enddate" value="<?php print htmlspecialchars(empty($_GET['enddate'])?'':$_GET['enddate'])?>"/>
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
			<td style="padding: 10px;">
				<input type="submit" value="Search" class="ebayaffinity-settingssave">
			</td>
		</tr>
	</table>
	<script type="text/javascript">
		jQuery(document).ready(function() {
		    jQuery('#startdate').datepicker({
		        dateFormat : 'dd/mm/yy'
		    });
		    jQuery('#enddate').datepicker({
		        dateFormat : 'dd/mm/yy'
		    });
		});
	</script>

<?php 
if (count($arrObjLogs) == 0) {
?>
<?php 
	if ($_GET['p'] > 0) {
?>
		<a href="admin.php?page=ebay-sync-logs&amp;p=<?php print $_GET['p'] - 1?>&amp;startdate=<?php print urlencode(empty($_GET['startdate'])?'':$_GET['startdate'])?>&amp;enddate=<?php print urlencode(empty($_GET['enddate'])?'':$_GET['enddate'])?>&amp;s=<?php print urlencode(empty($_GET['s'])?'':$_GET['s'])?>" style="">Previous page</a>
<?php 
	}
?>
		<p>No results.</p>
	</div>
</form>
<?php 
	return;
}
?>

<?php 
if ($_GET['p'] > 0) {
?>
	<a href="admin.php?page=ebay-sync-logs&amp;p=<?php print $_GET['p'] - 1?>&amp;startdate=<?php print urlencode(empty($_GET['startdate'])?'':$_GET['startdate'])?>&amp;enddate=<?php print urlencode(empty($_GET['enddate'])?'':$_GET['enddate'])?>&amp;s=<?php print urlencode(empty($_GET['s'])?'':$_GET['s'])?>" style="">Previous page</a>
<?php 
}
?>
<?php 
if (count($arrObjLogs) == $block) {
?>
	<a href="admin.php?page=ebay-sync-logs&amp;p=<?php print $_GET['p'] + 1?>&amp;startdate=<?php print urlencode(empty($_GET['startdate'])?'':$_GET['startdate'])?>&amp;enddate=<?php print urlencode(empty($_GET['enddate'])?'':$_GET['enddate'])?>&amp;s=<?php print urlencode(empty($_GET['s'])?'':$_GET['s'])?>" style="">Next page</a>
<?php 
}

$orig_tz = date_default_timezone_get();
if (empty($orig_tz)) {
	$orig_tz = 'UTC';
}
$ntz = wc_timezone_string();

if (!empty($ntz)) {
	date_default_timezone_set($ntz);
}

?>
	<table class="ebayaffinity-inv-table ebayaffinity-inv-table-nar" style="margin-top: 30px; margin-bottom: 30px;">
		<tr>
			<th>ID</th>
			<th>Date/Time</th>
			<th>Type</th>
			<th>Title</th>
			<th>URL</th>
		</tr>
		<?php
		foreach($arrObjLogs as $objLog):
			$a = explode("<br>", $objLog->details);
			if (strpos($a[0], 'affinsvc') !== false && (strpos($a[0], 'URL: ') !== false)) {
				$url = str_replace('URL: ', '', $a[0]);
				$url = trim($url);
				array_shift($a);
			} else {
				$url = '';
			}
			$a = implode("<br>", $a);
			$a = explode("\n", $a);
			$c = array();
			$body = '';
			$rlogid = '';
			foreach ($a as $b) {
				if (strpos($b, '    [rlogid] => ') === 0) {
					$rlogid = substr($b, 15);
				} else if (strpos($b, '    [body] => {') === 0) {
					$body = substr($b, 14);
				} else if (strpos($b, '    [body] => [') === 0) {
					$body = substr($b, 14);
				} else {
					$c[] = $b;
				}
			}
			$a = implode("\n", $c);
		?>	
			<tr class="ebayaffinity-open ebayaffinity-open1-<?php print $objLog->id ?>">
				<td rowspan="1" style="vertical-align: top;"><a href="#" data-id="<?php print $objLog->id ?>"><?php print $objLog->id ?></a></td>
				<td><?php print date('d/m/Y H:i', $objLog->numDateTime) ?></td>
				<td><?php print strtoupper($objLog->type) ?></td>
				<td><?php print htmlspecialchars($objLog->title) ?></td>
				<td>
					<?php print htmlspecialchars($url)?>
				</td>
			</tr>
			<tr class="ebayaffinity-open2-<?php print $objLog->id ?>"  style="display: none;">
				<td colspan="4" style="display: none;">
				<?php 
				if (!empty($rlogid)) {
					print '<strong>RlogID: <em>'.htmlspecialchars($rlogid).'</em></strong><br><br>';
				}
				$a = trim($a);
				$body_out = '';
				$inquote = false;
				$indent = 0;
				$body_arr = str_split($body);
				foreach ($body_arr as $k=>$char) {
					if ($char === '"') {
						$inquote = !$inquote;
					}
					if (!$inquote) {
						if ($char === '}' || $char === ']') {
							$body_out .= "\n";
							$indent--;
							for ($i = 0; $i < $indent * 4; $i++) {
								$body_out .= " ";
							}
						}
						if ($char === '{' || $char === '[') {
							$indent++;
						}
					}
					$body_out .= $char;
					if (!$inquote) {
						if ($char === ',' || $char === '}' || $char === '{' || $char === ']' || $char === '[') {
							if (isset($body_arr[$k+1]) && $char == '}' && $body_arr[$k+1] === '}') {
								
							} else if (isset($body_arr[$k+1]) && $char === '}' && $body_arr[$k+1] === ',') {
								
							} else if (isset($body_arr[$k+1]) && $char === ']' && $body_arr[$k+1] === ']') {
							
							} else if (isset($body_arr[$k+1]) && $char === '[' && $body_arr[$k+1] === ']') {	
								
							} else if (isset($body_arr[$k+1]) && $char === '}' && $body_arr[$k+1] === ']') {
							
							} else if (isset($body_arr[$k+1]) && $char === ']' && $body_arr[$k+1] === '}') {
								
							} else if (isset($body_arr[$k+1]) && $char === ']' && $body_arr[$k+1] === ',') {
								
							} else {
								$body_out .= "\n";
							}
							for ($i = 0; $i < $indent * 4; $i++) {
								if (isset($body_arr[$k+1]) && $char === '}' && $body_arr[$k+1] === '}') {
									
								} else if (isset($body_arr[$k+1]) && $char === '}' && $body_arr[$k+1] === ',') {
									
								} else if (isset($body_arr[$k+1]) && $char === ']' && $body_arr[$k+1] === ']') {
								
								} else if (isset($body_arr[$k+1]) && $char === '[' && $body_arr[$k+1] === ']') {	
									
								} else if (isset($body_arr[$k+1]) && $char === '}' && $body_arr[$k+1] === ']') {
								
								} else if (isset($body_arr[$k+1]) && $char === ']' && $body_arr[$k+1] === '}') {
									
								} else if (isset($body_arr[$k+1]) && $char === ']' && $body_arr[$k+1] === ',') {
									
								} else {
									$body_out .= " ";
								}
							}
						}
						if ($char === ':') {
							$body_out .= " ";
						}
					}
				}
				$body = $body_out;
				$a = str_replace('.', 'dotdotdotdotdotdotdotdotdotdotdotdotdotdotdotdot', $a);
				$a = str_replace('$', 'dollardollardollardollardollardollardollardollar', $a);
				$a = str_replace(',', 'commacommacommacommacommacommacommacommacommacomma', $a);
				$a = str_replace('@', 'atatatatatatatatatatatatatatatatatat', $a);
				$a = str_replace('"', 'dquotedquotedquotedquotedquotedquotedquotedquote', $a);
				$a = str_replace("'", 'quotequotequotequotequotequotequotequotequotequote', $a);
				$a = str_replace('/', 'fslashfslashfslashfslashfslashfslashfslashfslashfslash', $a);
				$a = str_replace('list', 'listlistlistlistlistlistlistlistlistlistlistlistlist', $a);
				$a = str_replace('continue', 'continuecontinuecontinuecontinuecontinuecontinuecontinuecontinuecontinuecontinue', $a);
				$a = str_replace('or', 'orororororororororororororororor', $a);
				$a = str_replace('as', 'asasasasasasasasasasasasasasasas', $a);
				
				$a = highlight_string('<?php '.$a, true);
				$a = str_replace('&lt;?php&nbsp;', '', $a);
				
				$a = str_replace('asasasasasasasasasasasasasasasas', 'as', $a);
				$a = str_replace('orororororororororororororororor', 'or', $a);
				$a = str_replace('continuecontinuecontinuecontinuecontinuecontinuecontinuecontinuecontinuecontinue', 'continue', $a);
				$a = str_replace('listlistlistlistlistlistlistlistlistlistlistlistlist', 'list', $a);
				$a = str_replace('fslashfslashfslashfslashfslashfslashfslashfslashfslash', '/', $a);
				$a = str_replace('quotequotequotequotequotequotequotequotequotequote', "'", $a);
				$a = str_replace('dquotedquotedquotedquotedquotedquotedquotedquote', '"', $a);
				$a = str_replace('atatatatatatatatatatatatatatatatatat', '@', $a);
				$a = str_replace('commacommacommacommacommacommacommacommacommacomma', ',', $a);
				$a = str_replace('dollardollardollardollardollardollardollardollar', '$', $a);
				$a = str_replace('dotdotdotdotdotdotdotdotdotdotdotdotdotdotdotdot', '.', $a);
				
				print $a;
				if (!empty($body)) {
					print '<br>';
					$body = 'Body: '.$body;
					print str_replace('&lt;?php&nbsp;', '', highlight_string('<?php '.$body, true));
				}
				?>
				</td>
			</tr>
		<?php
		endforeach;
		?>
	</table>
<?php 

if (!empty($ntz)) {
	date_default_timezone_set($orig_tz);
}

if ($_GET['p'] > 0) {
?>
	<a href="admin.php?page=ebay-sync-logs&amp;p=<?php print $_GET['p'] - 1?>&amp;startdate=<?php print urlencode(empty($_GET['startdate'])?'':$_GET['startdate'])?>&amp;enddate=<?php print urlencode(empty($_GET['enddate'])?'':$_GET['enddate'])?>&amp;s=<?php print urlencode(empty($_GET['s'])?'':$_GET['s'])?>" style="">Previous page</a>
<?php 
}
?>
<?php 
if (count($arrObjLogs) == $block) {
?>
	<a href="admin.php?page=ebay-sync-logs&amp;p=<?php print $_GET['p'] + 1?>&amp;startdate=<?php print urlencode(empty($_GET['startdate'])?'':$_GET['startdate'])?>&amp;enddate=<?php print urlencode(empty($_GET['enddate'])?'':$_GET['enddate'])?>&amp;s=<?php print urlencode(empty($_GET['s'])?'':$_GET['s'])?>" style="">Next page</a>
<?php 
}
?>
</div>
</form>
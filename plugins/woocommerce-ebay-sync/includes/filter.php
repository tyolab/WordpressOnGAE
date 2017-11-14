<?php 
require_once(__DIR__.'/../model/AffinityEbayInventory.php');
$localcats = AffinityEbayInventory::categoryset();
if (empty($data)) {
	$data = AffinityEbayInventory::inventoryset();
	$found = $data[1];
	$minprice = $data[2];
	$maxprice = $data[3];
	$data = $data[0];
}
?>

	
	<div class="ebayaffinity-inv-filter" <?php print empty($_GET['justfiltered'])?'':'style="display: block;"'?>>
		<a id="ebayaffinity-inv-filter-close" href="#">&times;</a>
<?php 
	unset($_GET['justfiltered']);
?>
			<input type="hidden" name="justfiltered" value="1">
			<input type="hidden" name="paged" id="ebayaffinity-inv-filter-paged" value="1">
			<input type="hidden" name="page" value="ebay-sync-inventory">
			<input type="hidden" name="showerrors" value="<?php print esc_html($_GET['showerrors'])?>">
			<input type="hidden" name="s" value="<?php print esc_html(stripslashes($_GET['s']))?>">
			<div class="ebayaffinity-inv-filter-header">Filter</div>
			
			<div class="ebayaffinity-inv-filter-block">
				<div class="ebayaffinity-inv-filter-block-header">Price range</div>
				
				<div class="ebayaffinity-inv-filter-price-slide">
					<div class="ebayaffinity-inv-filter-price-slider" data-minprice="<?php print $minprice?>" data-maxprice="<?php print $maxprice?>">
<?php 
	if ($maxprice - $minprice == 0) {
		$minmarg = 0;
		$maxmarg = 100;
	} else {
		$minmarg = (($_GET['pricemin'] - $minprice) / ($maxprice - $minprice)) * 100;
		$maxmarg = (($_GET['pricemax'] - $minprice) / ($maxprice - $minprice)) * 100;
	}
	
	if ($minmarg == $maxmarg) {
		$maxmarg += 5;
		if ($maxmarg > 100) {
			$maxmarg = 100;
		}
		if ($minmarg == $maxmarg) {
			$minmarg -= 5;
			if ($minmarg < 0) {
				$maxmarg = 0;
			}
		}
	}
?>
						<div class="ebayaffinity-inv-filter-price-slider-in" style="left: <?php print $minmarg?>%; right: <?php print 100-$maxmarg?>%;"><span style="display: none;">&nbsp;</span></div>
	
						<div class="ebayaffinity-inv-filter-price-slider-pricemin" style="left: <?php print $minmarg?>%;"><span style="display: none;">&nbsp;</span></div>
						<div class="ebayaffinity-inv-filter-price-slider-pricemax" style="left: <?php print $maxmarg?>%;"><span style="display: none;">&nbsp;</span></div>
					</div>
					<input type="hidden" name="pricemin" id="pricemin" value="<?php print esc_html($_GET['pricemin'])?>">
					<input type="hidden" name="pricemax" id="pricemax" value="<?php print esc_html($_GET['pricemax'])?>">
					
					<div class="ebayaffinity-inv-filter-price-slider-pricemin-read">$<?php print esc_html($_GET['pricemin'])?></div>
					<div class="ebayaffinity-inv-filter-price-slider-pricemax-read">$<?php print esc_html($_GET['pricemax'])?></div>
					<div style="height: 0; overflow: hidden; clear: both;">&nbsp;</div>
				</div>
			</div>
			<div class="ebayaffinity-inv-filter-block">
				<div class="ebayaffinity-inv-filter-block-header">Categories</div>
<?php 
	$i = 0;
	foreach ($localcats as $k=>$v) {
		$i++;
?>
				<div class="ebayaffinity-inv-filter-cat <?php print $i>4?'ebayaffinity-inv-filter-show-more-cat':''?>">
					<input type="checkbox" name="catslugs[]" <?php print in_array($k, $_GET['catslugs'])?'checked':''?> value="<?php print $k?>"> <?php print esc_html($v)?>
				</div>
<?php 
	}
	if ($i > 4) {
?>
				<a href="#" id="ebayaffinity-inv-filter-show-more-cat-link">
					show more <span>&#x221f;</span>
				</a>
				<a href="#" id="ebayaffinity-inv-filter-show-less-cat-link">
					show less <span>&#x221f;</span>
				</a>
<?php 
	}
?>
	
			</div>
			<div class="ebayaffinity-inv-filter-block">
				<div class="ebayaffinity-inv-filter-block-header">Product visibility</div>
				<table>
					<tr>
						<td>Show visible products <input type="hidden" name="showunblocked" value="<?php print esc_html($_GET['showunblocked'])?>"></td>
						<td>
							<div class="ebayaffinity-filter-switch-on-off ebayaffinity-filter-switch-<?php print $_GET['showunblocked']==1?'on':'off'?>"><div>&nbsp;</div></div>
						</td>
					</tr>
					<tr>
						<td>Show invisible products <input type="hidden" name="showblocked" value="<?php print esc_html($_GET['showblocked'])?>"></td>
						<td>
							<div class="ebayaffinity-filter-switch-on-off ebayaffinity-filter-switch-<?php print $_GET['showblocked']==1?'on':'off'?>"><div>&nbsp;</div></div>
						</td>
					</tr>
				</table>
			</div>
			
			
			<div class="ebayaffinity-inv-filter-block">
				<div class="ebayaffinity-inv-filter-block-header">Product issues</div>
				<table>
					<tr>
						<td>Needs category mapping <input type="hidden" name="showneedsmapping" value="<?php print esc_html($_GET['showneedsmapping'])?>"></td>
						<td>
							<div class="ebayaffinity-filter-switch-on-off ebayaffinity-filter-switch-<?php print $_GET['showneedsmapping']==1?'on':'off'?>"><div>&nbsp;</div></div>
						</td>
					</tr>
					<tr>
						<td>Needs title optimisation <input type="hidden" name="shownotitleopt" value="<?php print esc_html($_GET['shownotitleopt'])?>"></td>
						<td>
							<div class="ebayaffinity-filter-switch-on-off ebayaffinity-filter-switch-<?php print $_GET['shownotitleopt']==1?'on':'off'?>"><div>&nbsp;</div></div>
						</td>
					</tr>
					<tr>
						<td>Has listing errors <input type="hidden" name="showerrors" value="<?php print esc_html($_GET['showerrors'])?>"></td>
						<td>
							<div class="ebayaffinity-filter-switch-on-off ebayaffinity-filter-switch-<?php print $_GET['showerrors']==1?'on':'off'?>"><div>&nbsp;</div></div>
						</td>
					</tr>
				</table>
			</div>
			
			
			
			<div class="ebayaffinity-inv-filter-block">
				<div class="ebayaffinity-inv-filter-block-header">Sort by</div>
				<select name="order">
					<option <?php print $_GET['order']=='title'?'selected':''?> value="title">Title</option>
					<option <?php print $_GET['order']=='seller'?'selected':''?> value="seller">Best seller</option>
					<option <?php print $_GET['order']=='priceasc'?'selected':''?> value="priceasc">Price ascending</option>
					<option <?php print $_GET['order']=='pricedesc'?'selected':''?> value="pricedesc">Price descending</option>
				</select>
			</div>
	</div>
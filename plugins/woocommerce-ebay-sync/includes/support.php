<?php 
$setup1 = get_option('ebayaffinity_setup1');
$setup2 = get_option('ebayaffinity_setup2');
$setup3 = get_option('ebayaffinity_setup3');
$setup4 = get_option('ebayaffinity_setup4');
$setup5 = get_option('ebayaffinity_setup5');
if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4)) && (!empty($setup5))) {
?>

<script type="text/javascript">
window.zESettings = {
		webWidget: {
			helpCenter: {
				filter: {
					category: '203792408-eBay-Sync-for-WooCommerce'
				}
			}
		}
};

  function zendesk_web_widget(){
    window.zEmbed = window.zE = null;
    jQuery('head iframe[src="javascript:false"]').remove();

    (function(url, host) {
      var queue = [],
          dom,
          doc,
          where,
          iframe = document.createElement('iframe'),
          iWin,
          iDoc;

      window.zEmbed = function() {
        queue.push(arguments);
      };

      window.zE = window.zE || window.zEmbed;

      iframe.src = 'javascript:false';
      iframe.title = ''; iframe.role='presentation';  // a11y
      (iframe.frameElement || iframe).style.cssText = 'display: none';
      where = document.getElementsByTagName('script');
      where = where[where.length - 1];
      where.parentNode.insertBefore(iframe, where);

      iWin = iframe.contentWindow;
      iDoc = iWin.document;

      try {
        doc = iDoc;
      } catch(e) {
        dom = document.domain;
        iframe.src='javascript:var d=document.open();d.domain="'+dom+'";void(0);';
        doc = iDoc;
      }
      doc.open()._l = function() {
        var js = this.createElement('script');
        if (dom) this.domain = dom;
        js.id = 'js-iframe-async';
        js.src = url;
        this.t = +new Date();
        this.zendeskHost = host;
        this.zEQueue = queue;
        this.body.appendChild(js);
      };
      doc.write('<body onload="document._l();">');
      doc.close();
    }('//assets.zendesk.com/embeddable_framework/main.js', "ebaysync.zendesk.com"));
  }

  jQuery(document).on("ready page:load", function() {
    zendesk_web_widget();
  });
</script>
<?php 
}
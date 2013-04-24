<script language="javascript" type="text/javascript">
	function PO_submit_url() {
		if (PO_form_validation('po_url_form')) {
			var urlId = jQuery('#url_id').val();
			var affectChildren = 0;
			if (jQuery('#affectChildren').attr('checked')) {
				affectChildren = jQuery('#affectChildren').val();
			}
			
			var enabledPlugins = new Array();
			jQuery(".enabled_plugin_check").each(function() {
				if (this.checked) {
					enabledPlugins[enabledPlugins.length] = this.value;
				}
			});
			
			var disabledPlugins = new Array();
			jQuery(".disabled_plugin_check").each(function() {
				if (this.checked) {
					disabledPlugins[disabledPlugins.length] = this.value;
				}
			});

			var permalink = jQuery('#permalink').val();
			var load_element = jQuery('#PO-url-admin-wrap');
			var revertHtml = load_element.html();
			load_element.html('<div id="loading-image" style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_submit_url'), { 'url_id': urlId, 'affectChildren': affectChildren, 'enabledPlugins[]': enabledPlugins, 'disabledPlugins[]': disabledPlugins, 'permalink': permalink, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				load_element.html(result);
				jQuery('#PO_submit_url').click(function() {
					PO_submit_url();
				});
			});
		}
	}
	
	jQuery(document).ready(function() {
		if (jQuery(".deleteUrl").length > 0) {
			jQuery(".deleteUrl").each(function() {
				jQuery('#'+this.id).click(function() {
					return confirm("Are you sure you want to delete this URL?");
				});
			});
		}
		jQuery('#PO_submit_url').click(function() {
			PO_submit_url();
		});
	});

</script>
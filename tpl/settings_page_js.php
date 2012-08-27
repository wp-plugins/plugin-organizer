<?php
if ( current_user_can( 'activate_plugins' ) ) {
	?>
	<script type="text/javascript" language="javascript">
		function PO_submit_disable_settings() {
			var disable_plugins = 0;
			var admin_disable_plugins = 0;
			jQuery('.PO-disable-plugins').each(function() {
				if (this.checked) {
					disable_plugins = this.value;
				}
			});

			jQuery('.PO-admin-disable-plugins').each(function() {
				if (this.checked) {
					admin_disable_plugins = this.value;
				}
			});

			var load_element = jQuery('#PO-disable-settings-div .inside');
			var revertHtml = load_element.html();
			load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_submit_disable_plugin_settings'), { 'PO_disable_plugins': disable_plugins, 'PO_admin_disable_plugins': admin_disable_plugins, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				load_element.html(revertHtml);
				jQuery('.PO-disable-plugins').each(function() {
					if (this.value == disable_plugins) {
						this.checked = true;
					}
				});

				jQuery('.PO-admin-disable-plugins').each(function() {
					if (this.value == admin_disable_plugins) {
						this.checked = true;
					}
				});
			});
		}
		
		function PO_submit_fuzzy_url_matching() {
			var fuzzy_url_matching = 0;
			jQuery('.PO-fuzzy-url-radio').each(function() {
				if (this.checked) {
					fuzzy_url_matching = this.value;
				}
			});
			var load_element = jQuery('#PO-fuzzy-url-matching-div .inside');
			var revertHtml = load_element.html();
			load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_submit_fuzzy_url_matching'), { 'PO_fuzzy_url_matching': fuzzy_url_matching, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				load_element.html(revertHtml);
				jQuery('.PO-fuzzy-url-radio').each(function() {
					if (this.value == fuzzy_url_matching) {
						this.checked = true;
					}
				});
			});
		}
		
		function PO_submit_ignore_protocol() {
			var ignore_protocol = 0;
			jQuery('.PO-ignore-protocol-radio').each(function() {
				if (this.checked) {
					ignore_protocol = this.value;
				}
			});
			var load_element = jQuery('#PO-ignore-protocol-div .inside');
			var revertHtml = load_element.html();
			load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_submit_ignore_protocol'), { 'PO_ignore_protocol': ignore_protocol, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				load_element.html(revertHtml);
				jQuery('.PO-ignore-protocol-radio').each(function() {
					if (this.value == ignore_protocol) {
						this.checked = true;
					}
				});
			});
		}
		
		function PO_submit_redo_permalinks() {
			var load_element = jQuery('#redo-permalinks-div .inside');
			var revertHtml = load_element.html();
			load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_redo_permalinks'), { PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				load_element.html(revertHtml);
			});
		}

		function PO_submit_post_type_support() {
			var PO_cutom_post_type = new Array();
			jQuery('.PO_cutom_post_type').each(function() {
				if (this.checked) {
					PO_cutom_post_type[PO_cutom_post_type.length] = this.value;
				}
			});
			var load_element = jQuery('#PO-custom-post-type-div .inside');
			var revertHtml = load_element.html();
			load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_post_type_support'), { 'PO_cutom_post_type[]': PO_cutom_post_type, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				load_element.html(revertHtml);
				jQuery('.PO_cutom_post_type').each(function() {
					var valFound = false;
					for(i=0; i<PO_cutom_post_type.length; i++) {
						if (this.value == PO_cutom_post_type[i]) {
							valFound = true;
						}
					}
					this.checked = valFound;
				});
			});
		}

		function PO_manage_mu_plugin_file() {
			var selected_action = '';
			jQuery('.PO-manage-mu-radio').each(function() {
				if (this.checked) {
					selected_action = this.value;
				}
			});
			if (selected_action != '') {
				var load_element = jQuery('#PO-manage-mu-div .inside');
				var revertHtml = load_element.html();
				load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
				jQuery.post(encodeURI(ajaxurl + '?action=PO_manage_mu_plugin'), { 'selected_action': selected_action, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
					alert(result);
					load_element.html(revertHtml);
				});
			}
		}

		function PO_submit_preserve_settings() {
			var preserve_settings = '';
			jQuery('.PO-preserve-settings-radio').each(function() {
				if (this.checked) {
					preserve_settings = this.value;
				}
			});
			var load_element = jQuery('#PO-preserve-settings-div .inside');
			var revertHtml = load_element.html();
			load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			jQuery.post(encodeURI(ajaxurl + '?action=PO_submit_preserve_settings'), { 'PO_preserve_settings': preserve_settings, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				load_element.html(revertHtml);
				jQuery('.PO-preserve-settings-radio').each(function() {
					if (this.value == preserve_settings) {
						this.checked = true;
					}
				});
			});
		}
	</script>
	<?php
}
?>
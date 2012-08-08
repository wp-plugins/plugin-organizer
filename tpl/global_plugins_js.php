<?php
global $wpdb;
if ( current_user_can( 'activate_plugins' ) ) {
	$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
	?>
	<script type="text/javascript" language="javascript">
		function submitGlobalPlugins(){
			var disabledList = new Array();
			jQuery('.disabled_plugin_check').each(function() {
				if (this.checked) {
					disabledList[disabledList.length] = this.value;
				}
			});
			var revertHtml = jQuery('#pluginListdiv').html();
			jQuery('#pluginListdiv').html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			
			if (disabledList.length == 0) {
				disabledList[0]="EMPTY";
			}
			jQuery.post(encodeURI(ajaxurl + '?action=PO_save_global_plugins'), { 'disabledList[]': disabledList, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				jQuery('#pluginListdiv').html(revertHtml);
				//var pluginList = jQuery('input[name=group[]]');
				jQuery('.disabled_plugin_check').each(function() {
					if (disabledList.indexOf(this.value) != -1) {
						jQuery("#"+this.id).attr('checked', true);
					} else {
						jQuery("#"+this.id).attr('checked', false);
					}
				});
			});
		}
	</script>
	<?php
}
?>
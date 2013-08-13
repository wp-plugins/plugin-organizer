<?php
global $wpdb;
if ( current_user_can( 'activate_plugins' ) ) {
	?>
	<script type="text/javascript" language="javascript">
		function PO_submit_global_plugins(){
			var disabledList = new Array();
			var disabledMobileList = new Array();
			jQuery('.pluginsList').each(function() {
				if (this.checked) {
					disabledList[disabledList.length] = jQuery(this).val();
				}
			});

			jQuery('.mobilePluginsList').each(function() {
				if (this.checked) {
					disabledMobileList[disabledMobileList.length] = jQuery(this).val();
				}
			});
			
			var revertHtml = jQuery('#pluginListdiv').html();
			jQuery('#pluginListdiv').html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
			
			if (disabledList.length == 0) {
				disabledList[0]="EMPTY";
			}
			jQuery.post(encodeURI(ajaxurl + '?action=PO_save_global_plugins'), { 'disabledList[]': disabledList, 'disabledMobileList[]': disabledMobileList, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
				alert(result);
				jQuery('#pluginListdiv').html(revertHtml);
				jQuery('.pluginsList').each(function() {
					if (disabledList.indexOf(this.value) != -1) {
						jQuery(this).attr('checked', true);
					} else {
						jQuery(this).attr('checked', false);
					}
				});
			});
		}
	</script>
	<?php
}
?>
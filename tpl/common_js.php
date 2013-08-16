<script language="javascript" src="<?php print $this->urlPath; ?>/js/validation.js"></script>
<script language="javascript" type="text/javascript">
	function PO_toggle_all_plugins() {
		var toggle = jQuery("#toggleAllPlugins").attr('checked');
		jQuery(".pluginsList").each(function() {  
			var splitID = this.id.split('_');
			if (toggle) {
				PO_set_on_off('pluginsButton_'+splitID[1], 'plugins_'+splitID[1], 1);
			} else {
				PO_set_on_off('pluginsButton_'+splitID[1], 'plugins_'+splitID[1], 0);
			}
		});  
	}

	function PO_toggle_all_mobile_plugins() {
		var toggle = jQuery("#toggleAllMobilePlugins").attr('checked');
		jQuery(".mobilePluginsList").each(function() {  
			var splitID = this.id.split('_');
			if (toggle) {
				PO_set_on_off('mobilePluginsButton_'+splitID[1], 'mobilePlugins_'+splitID[1], 1);
			} else {
				PO_set_on_off('mobilePluginsButton_'+splitID[1], 'mobilePlugins_'+splitID[1], 0);
			}
		});  
	}

	function PO_toggle_on_off(buttonID, checkboxID) {
		if (jQuery('#'+buttonID).hasClass('pluginsButtonOff')) {
			PO_set_on_off(buttonID, checkboxID, 1);
		} else {
			PO_set_on_off(buttonID, checkboxID, 0);
		}
	}
	
	function PO_set_on_off(buttonID, checkboxID, onOff) {
		if (onOff == 1) {
			jQuery('#'+checkboxID).attr('checked', false);
			jQuery('#'+buttonID).attr('src', '<?php print $this->urlPath; ?>/image/on-button.png');
			jQuery('#'+buttonID).attr('alt', 'On');
			jQuery('#'+buttonID).removeClass('pluginsButtonOff');
			jQuery('#'+buttonID).addClass('pluginsButtonOn');
		} else {
			jQuery('#'+checkboxID).attr('checked', true);
			jQuery('#'+buttonID).attr('src', '<?php print $this->urlPath; ?>/image/off-button.png');
			jQuery('#'+buttonID).attr('alt', 'Off');
			jQuery('#'+buttonID).removeClass('pluginsButtonOn');
			jQuery('#'+buttonID).addClass('pluginsButtonOff');
		}
	}
	
	<?php
	print "var regex = new Array();\n";
	foreach ($this->regex as $key=>$val) {
		print "regex['$key'] = $val;\n";
	}
	?>
</script>
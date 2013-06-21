<script language="javascript" src="<?php print $this->urlPath; ?>/js/validation.js"></script>
<script language="javascript" type="text/javascript">
	function PO_toggle_all_plugins() {
		var toggle = jQuery("#toggleAllPlugins").attr('checked');
		jQuery(".pluginsList").each(function() {  
			var splitID = this.id.split('_');
			if (toggle) {
				PO_set_on_off(splitID[1], 1);
			} else {
				PO_set_on_off(splitID[1], 0);
			}
		});  
	}

	function PO_toggle_on_off(buttonID) {
		if (jQuery('#pluginsButton_'+buttonID).hasClass('pluginsButtonOff')) {
			PO_set_on_off(buttonID, 1);
		} else {
			PO_set_on_off(buttonID, 0);
		}
	}
	
	function PO_set_on_off(buttonID, onOff) {
		if (onOff == 1) {
			jQuery('#plugins_'+buttonID).attr('checked', false);
			jQuery('#pluginsButton_'+buttonID).attr('src', '<?php print $this->urlPath; ?>/image/on-button.png');
			jQuery('#pluginsButton_'+buttonID).attr('alt', 'On');
			jQuery('#pluginsButton_'+buttonID).removeClass('pluginsButtonOff');
			jQuery('#pluginsButton_'+buttonID).addClass('pluginsButtonOn');
		} else {
			jQuery('#plugins_'+buttonID).attr('checked', true);
			jQuery('#pluginsButton_'+buttonID).attr('src', '<?php print $this->urlPath; ?>/image/off-button.png');
			jQuery('#pluginsButton_'+buttonID).attr('alt', 'Off');
			jQuery('#pluginsButton_'+buttonID).removeClass('pluginsButtonOn');
			jQuery('#pluginsButton_'+buttonID).addClass('pluginsButtonOff');
		}
	}
	
	<?php
	print "var regex = new Array();\n";
	foreach ($this->regex as $key=>$val) {
		print "regex['$key'] = $val;\n";
	}
	?>
</script>
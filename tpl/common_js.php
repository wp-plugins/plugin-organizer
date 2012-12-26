<script language="javascript" src="<?php print $this->urlPath; ?>/js/validation.js"></script>
<script language="javascript" type="text/javascript">
	function PO_check_all_enable_plugins() {
		jQuery(".enabled_plugin_check").each(function() {  
			this.checked = jQuery("#selectAllEnablePlugins").attr("checked");  
		});  
	}
	
	function PO_check_all_disable_plugins() {
		jQuery(".disabled_plugin_check").each(function() {  
			this.checked = jQuery("#selectAllDisablePlugins").attr("checked");  
		});  
	}

	
	<?php
	print "var regex = new Array();\n";
	foreach ($this->regex as $key=>$val) {
		print "regex['$key'] = $val;\n";
	}
	?>
</script>
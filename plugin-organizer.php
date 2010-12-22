<?php
/*
Plugin Name: Plugin Organizer
Plugin URI: http://www.jsterup.com/dev/wordpress/plugin-organizer
Description: A plugin for specifying the load order of your plugins.
Version: 0.1
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

$POAbsPath = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__));
$PluginOrganizer = new Plugin_Organizer();

add_action('admin_menu', array($PluginOrganizer, 'PO_admin_menu'));
add_filter("plugin_action_links", array($PluginOrganizer, 'PO_plugin_page'), 10, 4);
add_action('admin_head', array($PluginOrganizer, 'PO_ajax_request'));
add_action('wp_ajax_plugin_organizer',  array($PluginOrganizer, 'PO_save_order'));

class Plugin_Organizer {
	function PO_admin_menu() {
		add_options_page('Plugin Organizer', 'Plugin Organizer', 'manage_options', 'Plugin_Organizer', array($this, 'PO_edit_list'));
	}

	function PO_edit_list() {
		global $POAbsPath;
		$plugins = get_option("active_plugins");
		
		if (isset($_POST['submit']) && $_POST['submit'] == "Save Order") {
			$newPlugArray = $_POST['order'];
			if (sizeof(array_unique($newPlugArray)) == sizeof($_POST['order'])) {
				$newPluginOrder = array();
				$sorted = 0;
				while($sorted == 0) {
					$sorted = 1;
					for($i=0; $i<sizeof($_POST['order']) - 1; $i++) {
						if (isset($_POST['order'][$i+1]) && $_POST['order'][$i+1] < $_POST['order'][$i]) {
							$sorted = 0;
							$tmpVal = $_POST['order'][$i+1];
							$_POST['order'][$i+1] = $_POST['order'][$i];
							$_POST['order'][$i] = $tmpVal;
							$tmpVal = $plugins[$i+1];
							$plugins[$i+1] = $plugins[$i];
							$plugins[$i] = $tmpVal;
						}
					}
				}
				foreach ($_POST['order'] as $key=>$pos) {
					$newPluginOrder[] = $plugins[$key];
				}
				update_option("active_plugins", $newPluginOrder);
				
				$plugins = get_option("active_plugins");
			} else {
				$errMsg = "The order values were not unique so no changes were made.";
			}
		}
		
		require_once($POAbsPath . "/tpl/pluginList.php");
	}

	function PO_plugin_page($buttons, $pluginFile, $arg3, $arg4) {
		
		$plugins = get_option("active_plugins");
		$newButtonArray = array();
		if (array_search($pluginFile, $plugins) !== false) {
			$orderSelect = $this->getOrderSelect(array_search($pluginFile, $plugins), $plugins);
			$buttons[] = "Load Order:".$orderSelect;
		}
		
		return $buttons;

		
	}

	function getOrderSelect($count, $plugins) {
		$orderSelect = "<select name=\"order[]\" id=\"order_" . $count . "\" onchange=\"uniqueOrder('order_" . $count . "');\">";
			for ($i = 0; $i<sizeof($plugins); $i++) {
				$orderSelect .= "<option value=\"" . $i . "\" " . (($i == $count) ? "selected=\"selected\"" : "") . ">" . ($i+1) . "</option>";
			}
		$orderSelect .= "</select>";
		$orderSelect .= "<input type=\"hidden\" id=\"old_order_" . $count . "\" value=\"" . $count . "\">";
		$orderSelect .= "<input type=\"button\" value=\"Save Order\" onmousedown=\"submitPluginLoadOrder();\">";
		return $orderSelect;
	}

	function PO_ajax_request() {
		?>
		<script type="text/javascript" language="javascript">
			function uniqueOrder(currentId) {
				var newVal = jQuery("#" + currentId).val();
				var oldVal = jQuery("#old_" + currentId).val();
				var selections = jQuery('select[name^=order]');
				for (var i=0; i<selections.length; i++) {
					if (selections[i].id != currentId && selections[i].value == newVal) {
						selections[i].value = oldVal;
						jQuery("#old_" + selections[i].id).val(oldVal);
					}
				}
				jQuery("#old_" + currentId).val(newVal);

			}
			function submitPluginLoadOrder(){
				var selections = jQuery('select[name^=order]');
				alert("Your request is being processed");
				var agentList = '';
				for (var i=0; i<selections.length; i++) {
					if (i == selections.length-1) {
						agentList += selections[i].value;
					} else {
						agentList += selections[i].value + ",";
					}
				}
				if(navigator.appName == "Microsoft Internet Explorer") {
				  Ajax = new ActiveXObject("Microsoft.XMLHTTP");
				} else {
				  Ajax = new XMLHttpRequest();
				}
				Ajax.open("GET", encodeURI(ajaxurl + '?action=plugin_organizer&agentList=' + agentList));
				Ajax.onreadystatechange=function() {
				  if(Ajax.readyState == 4) {
					alert(Ajax.responseText);
					document.body.removeChild(loadingDiv);
				  }
				}
				Ajax.send(null);
			}
		</script>
		<?php
	}

	function PO_save_order() {
		$returnStatus = "";
		$plugins = get_option("active_plugins");
		if (preg_match("/^(([0-9])+[,]*)*$/", $_GET['agentList'])) {
			$newPlugArray = explode(",", $_GET['agentList']);
			if (sizeof(array_unique($newPlugArray)) == sizeof($plugins)) {
				$newPluginOrder = array();
				$sorted = 0;
				while($sorted == 0) {
					$sorted = 1;
					for($i=0; $i<sizeof($newPlugArray) - 1; $i++) {
						if (isset($newPlugArray[$i+1]) && $newPlugArray[$i+1] < $newPlugArray[$i]) {
							$sorted = 0;
							$tmpVal = $newPlugArray[$i+1];
							$newPlugArray[$i+1] = $newPlugArray[$i];
							$newPlugArray[$i] = $tmpVal;
							$tmpVal = $plugins[$i+1];
							$plugins[$i+1] = $plugins[$i];
							$plugins[$i] = $tmpVal;
						}
					}
				}
				foreach ($newPlugArray as $key=>$pos) {
					$newPluginOrder[] = $plugins[$key];
				}
				update_option("active_plugins", $newPluginOrder);
				
				$returnStatus = "The plugin load order has been changed.";
			} else {
				$returnStatus = "The order values were not unique so no changes were made.";
			}
		} else {
			$returnStatus = "Did not recieve the proper variables.  No changes made.";
		}
		print $returnStatus;
		die();
	}
}



?>
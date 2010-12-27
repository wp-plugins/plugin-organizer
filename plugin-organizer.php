<?php
/*
Plugin Name: Plugin Organizer
Plugin URI: http://www.nebraskadigital.com/2010/12/27/plugin-organizer/
Description: A plugin for specifying the load order of your plugins.
Version: 0.3
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

$POAbsPath = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__));
$PluginOrganizer = new Plugin_Organizer();

add_action('admin_menu', array($PluginOrganizer, 'PO_admin_menu'));
add_filter("plugin_action_links", array($PluginOrganizer, 'PO_plugin_page'), 10, 2);
add_action('admin_head', array($PluginOrganizer, 'PO_ajax_request'));
add_action('wp_ajax_plugin_organizer',  array($PluginOrganizer, 'PO_save_order'));
add_action('all_plugins',  array($PluginOrganizer, 'PO_reorder_plugins'));

class Plugin_Organizer {
	function PO_admin_menu() {
		if ( current_user_can( 'activate_plugins' ) ) {
			add_options_page('Plugin Organizer', 'Plugin Organizer', 'manage_options', 'Plugin_Organizer', array($this, 'PO_edit_list'));
		}

	}

	function PO_edit_list() {
		global $POAbsPath;
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			
			require_once($POAbsPath . "/tpl/pluginList.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function PO_plugin_page($buttons, $pluginFile) {
		
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			$newButtonArray = array();
			if (array_search($pluginFile, $plugins) !== false) {
				$orderSelect = $this->getOrderSelect(array_search($pluginFile, $plugins), $plugins);
				$buttons[] = "Load Order:".$orderSelect;
			}
		} else {
			wp_die("You dont have permissions to access this page.");
		}	
		return $buttons;

		
	}

	function getOrderSelect($count, $plugins) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$orderSelect = "<select name=\"order[]\" id=\"order_" . $count . "\" onchange=\"uniqueOrder('order_" . $count . "');\">";
				for ($i = 0; $i<sizeof($plugins); $i++) {
					$orderSelect .= "<option value=\"" . $i . "\" " . (($i == $count) ? "selected=\"selected\"" : "") . ">" . ($i+1) . "</option>";
				}
			$orderSelect .= "</select>";
			$orderSelect .= "<input type=\"hidden\" id=\"old_order_" . $count . "\" value=\"" . $count . "\">";
			$orderSelect .= "<input type=\"hidden\" id=\"start_order_" . $count . "\" value=\"" . $count . "\">";
			$orderSelect .= "<input type=\"button\" value=\"Save Order\" onmousedown=\"submitPluginLoadOrder();\">";
		} else {
			wp_die("You dont have permissions to access this page.");
		}	
		return $orderSelect;
	}

	function PO_ajax_request() {
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<script type="text/javascript" language="javascript">
				function uniqueOrder(currentId) {
					var newVal = jQuery("#" + currentId).val();
					var oldVal = jQuery("#old_" + currentId).val();
					var selections = jQuery('select[name=order[]]');
					for (var i=0; i<selections.length; i++) {
						if (selections[i].id != currentId && selections[i].value == newVal) {
							selections[i].value = oldVal;
							jQuery("#old_" + selections[i].id).val(oldVal);
						}
					}
					jQuery("#old_" + currentId).val(newVal);

				}
				function submitPluginLoadOrder(){
					var selections = jQuery('select[name=order[]]');
					alert("Your request is being processed");
					var orderList = '';
					var startOrderList = '';
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					for (var i=0; i<selections.length; i++) {
						if (i == selections.length-1) {
							orderList += selections[i].value;
							startOrderList += jQuery("#start_" + selections[i].id).val();
						} else {
							orderList += selections[i].value + ',';
							startOrderList += jQuery("#start_" + selections[i].id).val() + ',';
						}
					}
					
					jQuery.post(encodeURI(ajaxurl + '?action=plugin_organizer'), { orderList: orderList, startOrder: startOrderList, PO_nonce: PO_nonce }, function (result) {
						if (result == "The plugin load order has been changed.") {
							for (var i=0; i<selections.length; i++) {
								jQuery("#start_" + selections[i].id).val(selections[i].value);
							}
						}
						alert(result);
					});
				}
			</script>
			<?php
		}	
	}

	function PO_save_order() {
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			if (preg_match("/^(([0-9])+[,]*)*$/", $_POST['orderList']) && preg_match("/^(([0-9])+[,]*)*$/", $_POST['startOrder'])) {
				$newPlugArray = explode(",", $_POST['orderList']);
				$startOrderArray = explode(",", $_POST['startOrder']);
				if (sizeof(array_unique($newPlugArray)) == sizeof($plugins) && sizeof(array_unique($startOrderArray)) == sizeof($plugins)) {
					array_multisort($startOrderArray, $newPlugArray);
					array_multisort($newPlugArray, $plugins);
					update_option("active_plugins", $plugins);
					
					$returnStatus = "The plugin load order has been changed.";
				} else {
					$returnStatus = "The order values were not unique so no changes were made.";
				}
			} else {
				$returnStatus = "Did not recieve the proper variables.  No changes made.";
			}
		} else {
			$returnStatus = "You dont have permissions to access this page.";
		}
		print $returnStatus;
		die();
	}

	function PO_reorder_plugins($allPluginList) {
		$plugins = get_option("active_plugins");
		$activePlugins = Array();
		$inactivePlugins = Array();
		$newPluginList = Array();
		$activePluginOrder = Array();
		
		foreach ($allPluginList as $key=>$val) {
			if (in_array($key, $plugins)) {
				$activePlugins[$key] = $val;
				$activePluginOrder[] = array_search($key, $plugins);
			} else {
				$inactivePlugins[$key] = $val;
			}
		}
		array_multisort($activePluginOrder, $activePlugins);
		
		$newPluginList = array_merge($activePlugins, $inactivePlugins);	
		return $newPluginList;
	}
}



?>
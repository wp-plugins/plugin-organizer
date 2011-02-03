<?php
class PluginOrganizer {
	function PO_activate() {
		global $wpdb;
		$sql = "CREATE TABLE ".$wpdb->prefix."PO_groups (
			group_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_name varchar(255) NOT NULL default '',
			group_members longtext DEFAULT NULL,
			UNIQUE KEY group_id (group_id)
			) ENGINE=innoDB  DEFAULT CHARACTER SET=utf8  DEFAULT COLLATE=utf8_unicode_ci;";
	
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_groups'") != $wpdb->prefix."PO_groups") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		$row = $wpdb->get_row("SELECT count(*) as count FROM " . $wpdb->prefix . "PO_groups");
		if ($row->count == 0) {
			$this->PO_create_default_group();
		}


		$sql = "CREATE TABLE ".$wpdb->prefix."PO_disabled_plugins (
			post_id bigint(20) unsigned NOT NULL,
			permalink longtext NOT NULL default '',
			plugin_list longtext NOT NULL default '',
			UNIQUE KEY post_id (post_id)
			) ENGINE=innoDB  DEFAULT CHARACTER SET=utf8  DEFAULT COLLATE=utf8_unicode_ci;";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_disabled_plugins'") != $wpdb->prefix."PO_disabled_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
	}
	
	function PO_create_default_group() {
		global $wpdb;
		$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>"Default", "group_members"=>serialize(array())));
		$row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "PO_groups");
		update_option("PO_default_group", $row->group_id);
	}
	
	function PO_admin_menu() {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_groups'") != $wpdb->prefix."PO_groups") {
			$this->PO_activate();
		}
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugin_page=add_menu_page('Plugin Organizer', 'Plugin Organizer', 'activate_plugins', 'Plugin_Organizer', array($this, 'PO_settings_page'));
			add_action('admin_head-plugins.php', array($this, 'PO_ajax_load_order'));
			add_action('admin_head-plugins.php', array($this, 'PO_ajax_plugin_page'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Load Order', 'Load Order', 'activate_plugins', 'PO_Load_Order', array($this, 'PO_edit_list'));
			add_action('admin_head-'.$plugin_page, array($this, 'PO_ajax_plugin_group'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Groups', 'Groups', 'activate_plugins', 'PO_Groups', array($this, 'PO_group_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'PO_ajax_plugin_group'));
		}

	}

	function PO_settings_page() {
		global $POAbsPath;
		
		if ( current_user_can( 'activate_plugins' ) ) {
			if ($_POST['submit'] == "Save Settings" && wp_verify_nonce( $_POST['PO_noncename'], plugin_basename(__FILE__) )) {
				if (preg_match("/^(1|0)$/", $_POST['selective_load'])) {
					update_option("PO_disable_plugins", $_POST['selective_load']);
				}
			}
			$PO_noncename = wp_create_nonce( plugin_basename(__FILE__) );
			require_once($POAbsPath . "/tpl/settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
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

	function PO_group_page() {
		global $wpdb, $POAbsPath;
		if ( current_user_can( 'activate_plugins' ) ) {
			$members = array();
			$plugins = get_plugins();
			if ($_POST['createGroup'] == "Create Group") {
				$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>$_POST['new_group_name'], "group_members"=>$wpdb->prepare(serialize(array()))));
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".$wpdb->insert_id, ARRAY_A);
				$members = unserialize($currGroup['group_members']);
			} else if ($_POST['deleteGroup'] == "Delete Group" && is_numeric($_POST['PO_group'])) {
				$wpdb->query("DELETE FROM ".$wpdb->prefix."PO_groups WHERE group_id=".$wpdb->prepare($_POST['PO_group']));
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".get_option('PO_default_group'), ARRAY_A);
				if (!isset($currGroup['group_id'])) {
					$this->PO_create_default_group();
					$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".get_option('PO_default_group'), ARRAY_A);
				}
				$members = unserialize($currGroup['group_members']);
			} else if (is_numeric($_POST['PO_group'])) {
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".$_POST['PO_group'], ARRAY_A);
				$members = unserialize($currGroup['group_members']);
			} else {
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".get_option('PO_default_group'), ARRAY_A);
				if (!isset($currGroup['group_id'])) {
					$this->PO_create_default_group();
					$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".get_option('PO_default_group'), ARRAY_A);
				}
				$members = unserialize($currGroup['group_members']);
			}
			$allGroups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			
			require_once($POAbsPath . "/tpl/groupList.php");
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

	function PO_ajax_plugin_group() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<script type="text/javascript" language="javascript">
				function submitPluginGroup(group_id){
					var pluginList = jQuery('input[name=group[]]');
					var groupList = new Array();
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					for (var i=0; i<pluginList.length; i++) {
						if (pluginList[i].checked) {
							groupList[groupList.length] = pluginList[i].value;
						}
					}
					var group_name=jQuery('#group_name').val();
					var revertHtml = jQuery('#plugingroupdiv .inside').html();
					jQuery('#plugingroupdiv .inside').html('<div style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></div>');
					
					if (groupList.length == 0) {
						groupList[0]="EMPTY";
					}
					jQuery.post(encodeURI(ajaxurl + '?action=PO_save_group'), { 'groupList[]': groupList, PO_group: group_id, PO_nonce: PO_nonce, group_name: group_name }, function (result) {
						alert(result);
						jQuery('#plugingroupdiv .inside').html(revertHtml);
						//var pluginList = jQuery('input[name=group[]]');
						for (var i=0; i<pluginList.length; i++) {
							if (groupList.indexOf(pluginList[i].value) != -1) {
								jQuery("#"+pluginList[i].id).attr('checked', true);
							} else {
								jQuery("#"+pluginList[i].id).attr('checked', false);
							}
						}
					});
				}
			</script>
			<?php
		}	
	}
	
	function PO_ajax_plugin_page() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			?>
			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function () {
					var groupDropdown = '<select name="PO_group" onchange="syncGroupIds(this);">';
					<?php
						foreach ($groups as $group) {
							print "groupDropdown += '<option value=\"" . $group->group_id . "\">" . $group->group_name . "</option>';\n";
						}
					?>
					groupDropdown += '</select>';
					groupDropdown += '<input type="submit" name="group_plugins" value="View Group">';
					jQuery('.tablenav .actions').html(jQuery('.tablenav .actions').html()+groupDropdown);
					
				});
			</script>
			<?php
		}
	}
	
	function PO_ajax_load_order() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
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
					var orderList = new Array();
					var startOrderList = new Array();
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					for (var i=0; i<selections.length; i++) {
						orderList[orderList.length] = selections[i].value;
						startOrderList[startOrderList.length] = jQuery("#start_" + selections[i].id).val();
					}
					var load_element = '';
					var revertHtml = '';
					if (jQuery('#all-plugins-table .plugins').length) {
						load_element = jQuery('#all-plugins-table .plugins');
						revertHtml = load_element.html();
						load_element.html('<tr><td colspan=2 style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></td></tr>');
					} else {
						load_element = jQuery('#poststuff');
						revertHtml = load_element.html();
						load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></div>');
					}
					
					
					jQuery.post(encodeURI(ajaxurl + '?action=plugin_organizer'), { 'orderList[]': orderList, 'startOrder[]': startOrderList, PO_nonce: PO_nonce }, function (result) {
						if (result == "The plugin load order has been changed.") {
							for (var i=0; i<selections.length; i++) {
								jQuery("#start_" + selections[i].id).val(selections[i].value);
							}
						}
						alert(result);
						load_element.html(revertHtml);
					});
				}
				function syncGroupIds(element) {
					var selectedIndex = element.options['selectedIndex'];
					var selections = jQuery('select[name=PO_group]');
					for (var i=0; i<selections.length; i++) {
						selections[i].options['selectedIndex'] = selectedIndex;
					}
					
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
			if (preg_match("/^(([0-9])+[,]*)*$/", implode(",", $_POST['orderList'])) && preg_match("/^(([0-9])+[,]*)*$/", implode(",", $_POST['startOrder']))) {
				$newPlugArray = $_POST['orderList'];
				$startOrderArray = $_POST['startOrder'];
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
		global $wpdb;
		$plugins = get_option("active_plugins");
		$activePlugins = Array();
		$inactivePlugins = Array();
		$newPluginList = Array();
		$activePluginOrder = Array();
		
		if (is_numeric($_POST['PO_group'])) {
			$group = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".$_POST['PO_group'], ARRAY_A);
			$members = unserialize($group['group_members']);
			foreach ($allPluginList as $key=>$val) {
				if (in_array($val['Name'], $members)) {
					$activePlugins[$key] = $val;
					$activePluginOrder[] = array_search($key, $plugins);
				}
			}
		} else {
			foreach ($allPluginList as $key=>$val) {
				if (in_array($key, $plugins)) {
					$activePlugins[$key] = $val;
					$activePluginOrder[] = array_search($key, $plugins);
				} else {
					$inactivePlugins[$key] = $val;
				}
			}
		}
		array_multisort($activePluginOrder, $activePlugins);
		
		$newPluginList = array_merge($activePlugins, $inactivePlugins);	
		return $newPluginList;
	}


	function PO_save_group() {
		global $wpdb;
		$wpdb->show_errors();
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			if (is_array($_POST['groupList']) && is_numeric($_POST['PO_group'])) {
				$wpdb->update($wpdb->prefix."PO_groups", array("group_members"=>$wpdb->prepare(serialize($_POST['groupList'])), 'group_name'=>$_POST['group_name']), array('group_id'=>$_POST['PO_group']));
				$returnStatus = "The plugin group has been saved.";
			} else {
				$returnStatus = "Did not recieve the proper variables.  No changes made.";
			}
		} else {
			$returnStatus = "You dont have permissions to access this page.";
		}
		print $returnStatus;
		die();
	}



	function PO_disable_plugin_box() {
		if ( current_user_can( 'activate_plugins' ) ) {
			add_meta_box(
			'disable_plugins',
			'Disable Plugins',
			array($this, 'PO_get_disable_plugin_box'),
			'post',
			'normal',
			'high' 
			);

			add_meta_box(
			'disable_plugins',
			'Disable Plugins',
			array($this, 'PO_get_disable_plugin_box'),
			'page',
			'normal',
			'high' 
			);
		}
	}

	function PO_get_disable_plugin_box($content, $content2) {
		global $wpdb, $post_id;
		$disabledPlugins = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_disabled_plugins WHERE post_id = ".$post_id, ARRAY_A);
		$pluginList = unserialize($disabledPlugins['plugin_list']);
		if (!is_array($pluginList)) {
			$pluginList = array();
		}
		$plugins = get_plugins();
		?>
		<script type="text/javascript" language="javascript">
			function checkAllPlugins() {
				jQuery("input[name=disabledPlugins\[\]]").each(function() {  
					this.checked = jQuery("#selectAllPlugins").attr("checked");  
				});  
			}
		</script>
		<input type="checkbox" id="selectAllPlugins" name="selectAllPlugins" value="" onclick="checkAllPlugins();">Select All<br><br>
		<?php
		foreach ($plugins as $key=>$plugin) {
			if (in_array($key, $pluginList)) {
				?>
				<input type="checkbox" name="disabledPlugins[]" value="<?php print $key; ?>" checked="checked"><?php print $plugin['Name']; ?><br>
				<?php
			} else {
				?>
				<input type="checkbox" name="disabledPlugins[]" value="<?php print $key; ?>"><?php print $plugin['Name']; ?><br>
				<?php
			}
		}
			
	}

	function PO_save_disable_plugin_box($post_id) {
		global $wpdb;
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) 
			return $post_id;


		if ( !current_user_can( 'edit_page', $post_id ) || !current_user_can( 'activate_plugins' ) ) {
			return $post_id;
		}

		if (isset($_POST['disabledPlugins'])) {
			$preparedUrl = $wpdb->prepare(get_permalink($post_id));
			$postCount = $wpdb->get_row("SELECT count(*) as count FROM ".$wpdb->prefix."PO_disabled_plugins WHERE post_id = ".$post_id, ARRAY_A);
			if ($postCount['count'] > 0) {
				 $wpdb->update($wpdb->prefix."PO_disabled_plugins", array("plugin_list"=>$wpdb->prepare(serialize($_POST['disabledPlugins']))), array("post_id"=>$post_id));
			} else {
				$wpdb->insert($wpdb->prefix."PO_disabled_plugins", array("plugin_list"=>$wpdb->prepare(serialize($_POST['disabledPlugins'])), "permalink"=>$preparedUrl, "post_id"=>$post_id));
			}
		} else {
			$preparedUrl = $wpdb->prepare(get_permalink($post_id));
			$postCount = $wpdb->get_row("SELECT count(*) as count FROM ".$wpdb->prefix."PO_disabled_plugins WHERE post_id = ".$post_id, ARRAY_A);
			if ($postCount['count'] > 0) {
				$wpdb->query("DELETE FROM ".$wpdb->prefix."PO_disabled_plugins WHERE post_id = ".$post_id);
			}
		}
			
	}

	function PO_delete_disabled_plugins($post_id) {
		global $wpdb;
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
		$wpdb->query("DELETE FROM ".$wpdb->prefix."PO_disabled_plugins WHERE post_id = ".$post_id);
	}

}
?>
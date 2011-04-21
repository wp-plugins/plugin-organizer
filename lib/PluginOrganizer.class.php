<?php
class PluginOrganizer {
	var $pluginPageActions = "1";
	function __construct() {
		if (get_option("PO_version_num") != "0.8.3") {
			$this->activate();
		}
	}
	function activate() {
		global $wpdb, $POAbsPath;
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
			$this->create_default_group();
		}
		
		$sql = "CREATE TABLE ".$wpdb->prefix."PO_post_plugins (
			post_id bigint(20) unsigned NOT NULL,
			permalink longtext NOT NULL default '',
			disabled_plugins longtext NOT NULL default '',
			enabled_plugins longtext NOT NULL default '',
			UNIQUE KEY post_id (post_id)
			) ENGINE=innoDB  DEFAULT CHARACTER SET=utf8  DEFAULT COLLATE=utf8_unicode_ci;";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_post_plugins'") != $wpdb->prefix."PO_post_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}

		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_disabled_plugins'") == $wpdb->prefix."PO_disabled_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$disabledPlugins = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_disabled_plugins", ARRAY_A);
			foreach ($disabledPlugins as $row) {
				$wpdb->insert($wpdb->prefix."PO_post_plugins", array("disabled_plugins"=>$row['plugin_list'], "permalink"=>$row['permalink'], "post_id"=>$row['post_id']));
			}
			
			$wpdb->query("DROP TABLE ".$wpdb->prefix."PO_disabled_plugins;");

		}
		if (!file_exists(ABSPATH . "wp-content/mu-plugins/")) {
			@mkdir(ABSPATH . "wp-content/mu-plugins/");
		}

		if (file_exists(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php")) {
			@unlink(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php");
		}
		
		if (file_exists(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php")) {
			@copy(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php", ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php");
		}
		
		if (!is_array(get_option("PO_custom_post_type_support"))) {
			update_option("PO_custom_post_type_support", array("post", "page"));
		}
		
		if (get_option("PO_version_num") != "0.8.3") {
			update_option("PO_version_num", "0.8.3");
		}
	}
	
	function create_default_group() {
		global $wpdb;
		$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>"Default", "group_members"=>serialize(array())));
		$row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "PO_groups");
		update_option("PO_default_group", $row->group_id);
	}
	
	function admin_menu() {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_groups'") != $wpdb->prefix."PO_groups") {
			$this->activate();
		}
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugin_page=add_menu_page('Plugin Organizer', 'Plugin Organizer', 'activate_plugins', 'Plugin_Organizer', array($this, 'settings_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_PO_settings'));
			add_action('admin_head-plugins.php', array($this, 'ajax_load_order'));
			add_action('admin_head-plugins.php', array($this, 'ajax_plugin_page'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Load Order', 'Load Order', 'activate_plugins', 'PO_Load_Order', array($this, 'edit_list'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_load_order'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Groups', 'Groups', 'activate_plugins', 'PO_Groups', array($this, 'group_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_plugin_group'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Global Plugins', 'Global Plugins', 'activate_plugins', 'PO_global_plugins', array($this, 'global_plugins_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_global_plugins'));

			
		}

	}

	function settings_page() {
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
	
	function edit_list() {
		global $POAbsPath;
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			
			require_once($POAbsPath . "/tpl/pluginList.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function group_page() {
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
					$this->create_default_group();
					$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".get_option('PO_default_group'), ARRAY_A);
				}
				$members = unserialize($currGroup['group_members']);
			} else if (is_numeric($_POST['PO_group'])) {
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".$_POST['PO_group'], ARRAY_A);
				$members = unserialize($currGroup['group_members']);
			} else {
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".get_option('PO_default_group'), ARRAY_A);
				if (!isset($currGroup['group_id'])) {
					$this->create_default_group();
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

	function global_plugins_page($post_id) {
		global $POAbsPath;
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_plugins();
			$disabledPlugins = get_option('PO_disabled_plugins');
			$activePlugins = get_option("active_plugins");
			if (!is_array($disabledPlugins)) {
				$disabledPlugins = array();
			}
			require_once($POAbsPath . "/tpl/globalPlugins.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}


	function plugin_page($buttons, $pluginFile) {
		
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			$newButtonArray = array();
			if (array_search($pluginFile, $plugins) !== false) {
				$orderSelect = $this->get_order_select(array_search($pluginFile, $plugins), $plugins);
				$buttons[] = "Load Order:".$orderSelect;
			}
		} else {
			wp_die("You dont have permissions to access this page.");
		}	
		return $buttons;

		
	}

	function get_order_select($count, $plugins) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$orderSelect = "<select class=\"plugin_order_select\" name=\"order[]\" id=\"order_" . $count . "\" onchange=\"uniqueOrder('order_" . $count . "');\">";
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

	function ajax_plugin_group() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<script type="text/javascript" language="javascript">
				function submitPluginGroup(group_id){
					var groupList = new Array();
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					jQuery('.group_member_check').each(function() {
						if (this.checked) {
							groupList[groupList.length] = this.value;
						}
					});
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
						jQuery('.group_member_check').each(function() {
							if (groupList.indexOf(this.value) != -1) {
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
	}
	
	function ajax_global_plugins() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			?>
			<script type="text/javascript" language="javascript">
				function submitGlobalPlugins(){
					var disabledList = new Array();
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					jQuery('.disabled_plugin_check').each(function() {
						if (this.checked) {
							disabledList[disabledList.length] = this.value;
						}
					});
					var revertHtml = jQuery('#pluginListdiv').html();
					jQuery('#pluginListdiv').html('<div style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></div>');
					
					if (disabledList.length == 0) {
						disabledList[0]="EMPTY";
					}
					jQuery.post(encodeURI(ajaxurl + '?action=PO_save_global_plugins'), { 'disabledList[]': disabledList, PO_nonce: PO_nonce }, function (result) {
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
	}
	
	function ajax_plugin_page() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			?>
			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function () {
					var groupDropdown = '<div class="alignleft actions"><select name="PO_group_view" onchange="syncGroupIds(this);">';
					<?php
						foreach ($groups as $group) {
							print "groupDropdown += '<option value=\"" . $group->group_id . "\">" . $group->group_name . "</option>';\n";
						}
					?>
					groupDropdown += '</select>';
					groupDropdown += '<input type="submit" name="group_plugins" value="View Group"></div><br class="clear">';
					jQuery('.tablenav.top .clear').remove();
					jQuery('.tablenav.top').html(jQuery('.tablenav.top').html()+groupDropdown);
					jQuery('.tablenav.bottom .clear').remove();
					jQuery('.tablenav.bottom').html(jQuery('.tablenav.bottom').html()+groupDropdown);
					
				});
			</script>
			<?php
		}
	}
	
	function ajax_load_order() {
		global $POUrlPath, $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			?>
			<script type="text/javascript" language="javascript">
				function uniqueOrder(currentId) {
					var newVal = jQuery("#" + currentId).val();
					var oldVal = jQuery("#old_" + currentId).val();
					jQuery('.plugin_order_select').each(function() {
						if (this.id != currentId && this.value == newVal) {
							this.value = oldVal;
							jQuery("#old_" + this.id).val(oldVal);
						}
					});
					jQuery("#old_" + currentId).val(newVal);

				}
				function submitPluginLoadOrder(){
					var orderList = new Array();
					var startOrderList = new Array();
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					jQuery('.plugin_order_select').each(function() {
						orderList[orderList.length] = this.value;
						startOrderList[startOrderList.length] = jQuery("#start_" + this.id).val();
					});
					var load_element = '';
					var revertHtml = '';
					if (jQuery('#the-list').length) {
						load_element = jQuery('#the-list');
						revertHtml = load_element.html();
						load_element.html('<tr><td colspan=2 style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></td></tr>');
					} else {
						load_element = jQuery('#poststuff');
						revertHtml = load_element.html();
						load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></div>');
					}
					
					
					jQuery.post(encodeURI(ajaxurl + '?action=PO_plugin_organizer'), { 'orderList[]': orderList, 'startOrder[]': startOrderList, PO_nonce: PO_nonce }, function (result) {
						alert(result);
						load_element.html(revertHtml);
						if (result == "The plugin load order has been changed.") {
							jQuery('.plugin_order_select').each(function() {
								var orderIndex = orderList.shift();
								jQuery("#" + this.id).val(orderIndex);
								jQuery("#start_" + this.id).val(orderIndex);
							});
						}
					});
				}
				function syncGroupIds(element) {
					var selectedIndex = element.options['selectedIndex'];
					var selections = jQuery('select[name=PO_group_view]');
					for (var i=0; i<selections.length; i++) {
						selections[i].options['selectedIndex'] = selectedIndex;
					}
					
				}
			</script>
			<?php
		}	
	}

	function ajax_PO_settings() {
		global $POUrlPath;
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<script type="text/javascript" language="javascript">
				function submitRedoPermalinks() {
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					var load_element = jQuery('#redo-permalinks-div .inside');
					var revertHtml = load_element.html();
					load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></div>');
					jQuery.post(encodeURI(ajaxurl + '?action=PO_redo_permalinks'), { PO_nonce: PO_nonce }, function (result) {
						alert(result);
						load_element.html(revertHtml);
					});
				}

				function submitPostTypeSupport() {
					var PO_nonce = '<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>';
					var PO_cutom_post_type = new Array();
					jQuery('.PO_cutom_post_type').each(function() {
						if (this.checked) {
							PO_cutom_post_type[PO_cutom_post_type.length] = this.value;
						}
					});
					var load_element = jQuery('#PO-custom-post-type-div .inside');
					var revertHtml = load_element.html();
					load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $POUrlPath . "/image/ajax-loader.gif"; ?>"></div>');
					jQuery.post(encodeURI(ajaxurl + '?action=PO_post_type_support'), { 'PO_cutom_post_type[]': PO_cutom_post_type, PO_nonce: PO_nonce }, function (result) {
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
			</script>
			<?php
		}
	}
	
	function save_global_plugins() {
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			if (is_array($_POST['disabledList'])) {
				$disabledPlugins = $_POST['disabledList'];
				update_option("PO_disabled_plugins", $disabledPlugins);
				$returnStatus = "Global plugin list has been saved.";
			} else {
				$returnStatus = "Did not recieve the proper variables.  No changes made.";
			}
		} else {
			$returnStatus = "You dont have permissions to access this page.";
		}
		print $returnStatus;
		die();
	}
	
	function save_order() {
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
					update_option("PO_plugin_order", $plugins);
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

	function reorder_plugins($allPluginList) {
		global $wpdb;
		$plugins = get_option("active_plugins");
		if (is_admin() && $this->pluginPageActions == 1) {
			$perPage = get_user_option("plugins_per_page");
			if (!is_numeric($perPage)) {
				$perPage = 20;
			}
			if (sizeOf($plugins) > $perPage) {
				remove_filter("plugin_action_links", array($this, 'plugin_page'), 10, 2);
				remove_action('all_plugins',  array($this, 'reorder_plugins'));
				$this->pluginPageActions = 0;
				return $allPluginList;
			}
		}
		$activePlugins = Array();
		$inactivePlugins = Array();
		$newPluginList = Array();
		$activePluginOrder = Array();
		
		if (is_numeric($_POST['PO_group_view'])) {
			$group = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".$_POST['PO_group_view'], ARRAY_A);
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


	function save_group() {
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



	function disable_plugin_box() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$supportedPostTypes = get_option("PO_custom_post_type_support");
			if (is_array($supportedPostTypes)) {
				foreach ($supportedPostTypes as $postType) {
					add_meta_box(
					'enable_plugins',
					'Enable Plugins',
					array($this, 'get_enable_plugin_box'),
					$postType,
					'normal',
					'high' 
					);
					add_meta_box(
					'disable_plugins',
					'Disable Plugins',
					array($this, 'get_disable_plugin_box'),
					$postType,
					'normal',
					'high' 
					);
					
				}
			}
		}
	}

	function get_disable_plugin_box($content, $content2) {
		global $wpdb, $post_id;
		$postPlugins = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id, ARRAY_A);
		$pluginList = unserialize($postPlugins['disabled_plugins']);
		if (!is_array($pluginList)) {
			$pluginList = array();
		}
		$plugins = get_plugins();
		$activePlugins = get_option("active_plugins");
		?>
		<style type="text/css">
			.activeDisablePlugin {
				color: #FF0033;
			}
		</style>
		<script type="text/javascript" language="javascript">
			function checkAllDisablePlugins() {
				jQuery(".disabled_plugin_check").each(function() {  
					this.checked = jQuery("#selectAllDisablePlugins").attr("checked");  
				});  
			}
		</script>
		<input type="checkbox" id="selectAllDisablePlugins" name="selectAllDisablePlugins" value="" onclick="checkAllDisablePlugins();">Select All<br><br>
		<?php
		foreach ($plugins as $key=>$plugin) {
			if (in_array($key, $pluginList)) {
				?>
				<input class="disabled_plugin_check" type="checkbox" name="disabledPlugins[]" value="<?php print $key; ?>" checked="checked"><?php print (in_array($key, $activePlugins))? "<span class=\"activeDisablePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
				<?php
			} else {
				?>
				<input class="disabled_plugin_check" type="checkbox" name="disabledPlugins[]" value="<?php print $key; ?>"><?php print (in_array($key, $activePlugins))? "<span class=\"activeDisablePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
				<?php
			}
		}
		?>
		<br><br>NOTE:  This is a list of all plugins for this site.  If a plugin is checked it will be disabled for this page.  Plugins in <span class="activePlugin">RED</span> are active for this site.
		<?php
			
	}


	function get_enable_plugin_box($content, $content2) {
		global $wpdb, $post_id;
		$globalPlugins = get_option('PO_disabled_plugins');
		$postPlugins = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id, ARRAY_A);
		$pluginList = unserialize($postPlugins['enabled_plugins']);
		if (!is_array($pluginList)) {
			$pluginList = array();
		}
		$plugins = get_plugins();
		$activePlugins = get_option("active_plugins");
		?>
		<style type="text/css">
			.activeEnablePlugin {
				color: #FF0033;
			}
		</style>
		<script type="text/javascript" language="javascript">
			function checkAllEnablePlugins() {
				jQuery(".enabled_plugin_check").each(function() {  
					this.checked = jQuery("#selectAllEnablePlugins").attr("checked");  
				});  
			}
		</script>
		<input type="checkbox" id="selectAllEnablePlugins" name="selectAllEnablePlugins" value="" onclick="checkAllEnablePlugins();">Select All<br><br>
		<?php
		foreach ($plugins as $key=>$plugin) {
			if (in_array($key, $globalPlugins)) {
				if (in_array($key, $pluginList)) {
					?>
					<input class="enabled_plugin_check" type="checkbox" name="enabledPlugins[]" value="<?php print $key; ?>" checked="checked"><?php print (in_array($key, $activePlugins))? "<span class=\"activeEnablePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
					<?php
				} else {
					?>
					<input class="enabled_plugin_check" type="checkbox" name="enabledPlugins[]" value="<?php print $key; ?>"><?php print (in_array($key, $activePlugins))? "<span class=\"activeEnablePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
					<?php
				}
			}
		}
		?>
		<br><br>NOTE:  This is a list of globally disabled plugins.  If a plugin is checked it will be enabled for this page.  Plugins in <span class="activePlugin">RED</span> are active for this site.
		<?php
			
	}

	function save_disable_plugin_box($post_id) {
		global $wpdb;
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) 
			return $post_id;


		if ( !current_user_can( 'edit_page', $post_id ) || !current_user_can( 'activate_plugins' ) ) {
			return $post_id;
		}

		if (isset($_POST['disabledPlugins'])) {
			$preparedUrl = $wpdb->prepare(get_permalink($post_id));
			$postCount = $wpdb->get_row("SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id, ARRAY_A);
			if ($postCount['count'] > 0) {
				 $wpdb->update($wpdb->prefix."PO_post_plugins", array("disabled_plugins"=>$wpdb->prepare(serialize($_POST['disabledPlugins']))), array("post_id"=>$post_id));
			} else {
				$wpdb->insert($wpdb->prefix."PO_post_plugins", array("disabled_plugins"=>$wpdb->prepare(serialize($_POST['disabledPlugins'])), "permalink"=>$preparedUrl, "post_id"=>$post_id));
			}
		} else {
			$preparedUrl = $wpdb->prepare(get_permalink($post_id));
			$postCount = $wpdb->get_row("SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id, ARRAY_A);
			if ($postCount['count'] > 0) {
				$wpdb->update($wpdb->prefix."PO_post_plugins", array("disabled_plugins"=>""), array("post_id"=>$post_id));
			}
		}
			
	}
	


	function save_enable_plugin_box($post_id) {
		global $wpdb;
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) 
			return $post_id;


		if ( !current_user_can( 'edit_page', $post_id ) || !current_user_can( 'activate_plugins' ) ) {
			return $post_id;
		}

		if (isset($_POST['enabledPlugins'])) {
			$preparedUrl = $wpdb->prepare(get_permalink($post_id));
			$postCount = $wpdb->get_row("SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id, ARRAY_A);
			if ($postCount['count'] > 0) {
				 $wpdb->update($wpdb->prefix."PO_post_plugins", array("enabled_plugins"=>$wpdb->prepare(serialize($_POST['enabledPlugins']))), array("post_id"=>$post_id));
			} else {
				$wpdb->insert($wpdb->prefix."PO_post_plugins", array("enabled_plugins"=>$wpdb->prepare(serialize($_POST['enabledPlugins'])), "permalink"=>$preparedUrl, "post_id"=>$post_id));
			}
		} else {
			$preparedUrl = $wpdb->prepare(get_permalink($post_id));
			$postCount = $wpdb->get_row("SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id, ARRAY_A);
			if ($postCount['count'] > 0) {
				$wpdb->update($wpdb->prefix."PO_post_plugins", array("enabled_plugins"=>""), array("post_id"=>$post_id));
			}
		}
			
	}

	function delete_plugin_lists($post_id) {
		global $wpdb;
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
		$wpdb->query("DELETE FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = ".$post_id);
	}

	function redo_permalinks() {
		global $wpdb;
		$failedCount = 0;
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$posts = $wpdb->get_results("SELECT post_id, permalink FROM ".$wpdb->prefix."PO_post_plugins", ARRAY_A);
		foreach ($posts as $post) {
			$preparedUrl = $wpdb->prepare(get_permalink($post['post_id']));
			if ($preparedUrl != $post['permalink']) {
				if(!$wpdb->update($wpdb->prefix."PO_post_plugins", array("permalink"=>$preparedUrl), array("post_id"=>$post['post_id']))) {
					$failedCount++;
				}
			}
		}

		if ($failedCount > 0) {
			print $failedCount . " permalinks failed to update!";
		} else {
			print "All permalinks were updated successfully.";
		}
		die();
	}

	function add_custom_post_type_support() {
		global $wpdb;
		$failedCount = 0;
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		update_option("PO_custom_post_type_support", $_POST['PO_cutom_post_type']);
		if (sizeof(array_diff(get_option("PO_custom_post_type_support"), $_POST['PO_cutom_post_type'])) == 0) {
			print "Post types saved.";
		} else {
			print "Saving post types failed!";
		}
		die();
	}

	function recreate_plugin_order() {
		$plugins = get_option("active_plugins");
		$pluginOrder = get_option("PO_plugin_order");
		$newPlugArray = $plugins;
		$activePlugins = $plugins;
		if (is_array($pluginOrder) && sizeof(array_diff_assoc($plugins, $pluginOrder)) > 0) {
			$newPlugins = array_diff($plugins, $pluginOrder);
			foreach ($newPlugins as $newPlug) {
				$pluginOrder[] = $newPlug;
			}
			$pluginLoadOrder = Array();
			$activePlugins = array();
			foreach ($plugins as $val) {
				$activePlugins[] = $val;
				$pluginLoadOrder[] = array_search($val, $pluginOrder);
			}
			array_multisort($pluginLoadOrder, $activePlugins);
			update_option("active_plugins", $activePlugins);
			update_option("PO_plugin_order", $activePlugins);
		}
	}
}
?>
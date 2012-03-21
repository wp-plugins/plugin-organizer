<?php
class PluginOrganizer {
	var $pluginPageActions = "1";
	var $regex;
	var $absPath;
	var $urlPath;
	var $nonce;
	function __construct($absPath, $urlPath) {
		$this->absPath = $absPath;
		$this->urlPath = $urlPath;
		$this->regex = array(
			"permalink" => "/^((https?):((\/\/)|(\\\\))+[\w\d:#@%\/;$()~_?\+-=\\\.&]*)$/",
			"group_name" => "/^[A-Za-z0-9_\-]+$/",
			"new_group_name" => "/^[A-Za-z0-9_\-]+$/",
			"default" => "/^(.|\\n)*$/"
		);
		if (get_option("PO_version_num") != "2.0") {
			$this->activate();
		}
	}
	function activate() {
		global $wpdb;
		$sql = "CREATE TABLE ".$wpdb->prefix."PO_groups (
			group_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_name varchar(255) NOT NULL default '',
			group_members longtext DEFAULT NULL,
			PRIMARY KEY PO_group_id (group_id)
			);";
	
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
			PRIMARY KEY PO_post_id (post_id)
			);";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_post_plugins'") != $wpdb->prefix."PO_post_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}

		$sql = "CREATE TABLE ".$wpdb->prefix."PO_url_plugins (
			url_id bigint(20) unsigned NOT NULL auto_increment,
			permalink longtext NOT NULL default '',
			disabled_plugins longtext NOT NULL default '',
			enabled_plugins longtext NOT NULL default '',
			PRIMARY KEY PO_id (url_id)
			);";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_url_plugins'") != $wpdb->prefix."PO_url_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
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
		
		if (get_option("PO_version_num") != "2.0") {
			update_option("PO_version_num", "2.0");
		}
	}
	
	function create_default_group() {
		global $wpdb;
		$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>"Default", "group_members"=>serialize(array())));
		$row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "PO_groups");
		update_option("PO_default_group", $row->group_id);
	}
	
	function validate_field($fieldname) {
		if (isset($this->regex[$fieldname]) && preg_match($this->regex[$fieldname], $_POST[$fieldname])) {
			return true;
		} else if (preg_match($this->regex['default'], $_POST[$fieldname])) {
			return true;
		} else {
			return false;
		}
	}
	
	function setup_nonce() {
		$this->nonce = wp_create_nonce(plugin_basename(__FILE__));
	}
	
	function admin_menu() {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_groups'") != $wpdb->prefix."PO_groups" || get_option("PO_version_num") != "2.0") {
			$this->activate();
		}
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugin_page=add_menu_page('Plugin Organizer', 'Plugin Organizer', 'activate_plugins', 'Plugin_Organizer', array($this, 'settings_page'), $this->urlPath."/image/po-icon-16x16.png");
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_PO_settings'));
			add_action('admin_head-plugins.php', array($this, 'ajax_plugin_page'));
			add_action('admin_head-plugins.php', array($this, 'make_draggable'));
			
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Load Order', 'Load Order', 'activate_plugins', 'PO_Load_Order', array($this, 'edit_list'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_load_order'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Groups', 'Groups', 'activate_plugins', 'PO_Groups', array($this, 'group_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_plugin_group'));
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Global Plugins', 'Global Plugins', 'activate_plugins', 'PO_global_plugins', array($this, 'global_plugins_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_global_plugins'));

			$plugin_page=add_submenu_page('Plugin_Organizer', 'URL Admin', 'URL Admin', 'activate_plugins', 'PO_url_admin', array($this, 'url_admin'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'ajax_url_admin'));

			
		}

	}

	function admin_styles() {
		?>
		<style type="text/css">
			#icon-po-settings {
				background: url("<?php print $this->urlPath; ?>/image/po-icon-32x32.png") no-repeat scroll 0px 0px transparent;
			}
			#icon-po-group {
				background: url("<?php print $this->urlPath; ?>/image/po-group-32x32.png") no-repeat scroll 0px 0px transparent;
			}
			#icon-po-global {
				background: url("<?php print $this->urlPath; ?>/image/po-global-32x32.png") no-repeat scroll 0px 0px transparent;
			}
			
		</style>
		<?php
	}
		
	function settings_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			if ($_POST['submit'] == "Save Settings" && wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
				if (preg_match("/^(1|0)$/", $_POST['PO_disable_plugins'])) {
					update_option("PO_disable_plugins", $_POST['PO_disable_plugins']);
				}

				if (preg_match("/^(1|0)$/", $_POST['PO_admin_disable_plugins'])) {
					update_option("PO_admin_disable_plugins", $_POST['PO_admin_disable_plugins']);
				}
			}
			require_once($this->absPath . "/tpl/settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}
	
	function edit_list() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			
			require_once($this->absPath . "/tpl/pluginList.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function group_page() {
		global $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$members = array();
			$plugins = get_plugins();
			if ($_POST['createGroup'] == "Create Group") {
				$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>$_POST['new_group_name'], "group_members"=>serialize(array())));
				$currGroup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = ".$wpdb->insert_id, ARRAY_A);
				$members = unserialize($currGroup['group_members']);
			} else if ($_POST['deleteGroup'] == "Delete Group" && is_numeric($_POST['PO_group'])) {
				$deleteGroupQuery = "DELETE FROM ".$wpdb->prefix."PO_groups WHERE group_id=%d";
				$wpdb->query($wpdb->prepare($deleteGroupQuery, $_POST['PO_group']));
				$currGroupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
				$currGroup = $wpdb->get_row($wpdb->prepare($currGroupQuery, get_option('PO_default_group')), ARRAY_A);
				if (!isset($currGroup['group_id'])) {
					$this->create_default_group();
					$currGroupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
					$currGroup = $wpdb->get_row($wpdb->prepare($currGroupQuery, get_option('PO_default_group')), ARRAY_A);
				
				}
				$members = unserialize($currGroup['group_members']);
			} else if (is_numeric($_POST['PO_group'])) {
				$currGroupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
				$currGroup = $wpdb->get_row($wpdb->prepare($currGroupQuery, $_POST['PO_group']), ARRAY_A);
				$members = unserialize($currGroup['group_members']);
			} else {
				$currGroupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
				$currGroup = $wpdb->get_row($wpdb->prepare($currGroupQuery, get_option('PO_default_group')), ARRAY_A);
				if (!isset($currGroup['group_id'])) {
					$this->create_default_group();
					$currGroupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
					$currGroup = $wpdb->get_row($wpdb->prepare($currGroupQuery, get_option('PO_default_group')), ARRAY_A);
				}
				$members = unserialize($currGroup['group_members']);
			}
			$allGroups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			
			require_once($this->absPath . "/tpl/groupList.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function global_plugins_page($post_id) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_plugins();
			$disabledPlugins = get_option('PO_disabled_plugins');
			$activePlugins = get_option("active_plugins");
			if (!is_array($disabledPlugins)) {
				$disabledPlugins = array();
			}
			require_once($this->absPath . "/tpl/globalPlugins.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function url_admin() {
		global $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			if ($_REQUEST['url_admin_page'] == "add") {

				$plugins = get_plugins();
				$activePlugins = get_option("active_plugins");
				$globalPlugins = get_option("PO_disabled_plugins");
				if (!is_array($globalPlugins)) {
					$globalPlugins = array();
				}
				
				require_once($this->absPath . "/tpl/urlAdd.php");
			} else if ($_REQUEST['url_admin_page'] == "edit") {
				if ($_POST['add_url'] == '1' && $this->validate_field("permalink")) {
					$getDupUrlQuery = "SELECT count(*) as count FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink=%s";
					$getDupUrlResult = $wpdb->get_results($wpdb->prepare($getDupUrlQuery, $_POST['permalink']),ARRAY_A);
					$urlCount = $getDupUrlResult[0]['count'];
					if ($urlCount != 0) {
						$errMsg = "That URL already exists in the database.";
						$plugins = get_plugins();
						$activePlugins = get_option("active_plugins");
						$globalPlugins = get_option("PO_disabled_plugins");
						if (!is_array($globalPlugins)) {
							$globalPlugins = array();
						}
						
						require_once($this->absPath . "/tpl/urlAdd.php");
						return "";
					} else {
						$wpdb->insert($wpdb->prefix."PO_url_plugins", array("disabled_plugins"=>serialize($_POST['disabledPlugins']),"enabled_plugins"=>serialize($_POST['enabledPlugins']), "permalink"=>$_POST['permalink']));
						$urlId = $wpdb->insert_id;
						if (!is_numeric($urlId)) {
							$urlId = 0;
						}
						$errMsg = "URL successfully added to the database.";
					}
				} else if (is_numeric($_REQUEST['url_id'])) {
					$urlId = $_REQUEST['url_id'];
				} else {
					$urlId = 0;
				}
				if ($_POST['edit_url'] == '1' && $urlId != 0 && $this->validate_field("permalink")) {
					$wpdb->update($wpdb->prefix."PO_url_plugins", array("disabled_plugins"=>serialize($_POST['disabledPlugins']),"enabled_plugins"=>serialize($_POST['enabledPlugins']), "permalink"=>$_POST['permalink']), array("url_id"=>$urlId));
					$errMsg = "URL successfully edited.";
				}
				
				$urlDetailQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE url_id = %d";
				$urlDetails = $wpdb->get_row($wpdb->prepare($urlDetailQuery, $urlId), ARRAY_A);
				$disabledPlugins = unserialize($urlDetails['disabled_plugins']);
				$enabledPlugins = unserialize($urlDetails['enabled_plugins']);
				if (!is_array($disabledPlugins)) {
					$disabledPlugins = array();
				}
				if (!is_array($enabledPlugins)) {
					$enabledPlugins = array();
				}
				$plugins = get_plugins();
				$activePlugins = get_option("active_plugins");
				$globalPlugins = get_option("PO_disabled_plugins");
				if (!is_array($globalPlugins)) {
					$globalPlugins = array();
				}
				require_once($this->absPath . "/tpl/urlEdit.php");
			} else {
				if (is_numeric($_REQUEST['url_id']) && $_REQUEST['delete_url'] == 1) {
					$urlId = $_REQUEST['url_id'];
					$deleteUrlQuery = "DELETE FROM ".$wpdb->prefix."PO_url_plugins WHERE url_id=%d";
					$deleteUrl = $wpdb->get_results($wpdb->prepare($deleteUrlQuery, $urlId));
				}
				$urlList = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_url_plugins");
				require_once($this->absPath . "/tpl/urlList.php");
			}
		} else {
			wp_die("You dont have permissions to access this page.");
		}
		
	}

	function ajax_url_admin() {
		$this->include_js_validation();
		?>
		<script language="javascript" type="text/javascript">
			jQuery(document).ready(function() {
				if (jQuery(".deleteUrl").length > 0) {
					jQuery(".deleteUrl").each(function() {
						jQuery('#'+this.id).click(function() {
							return confirm("Are you sure you want to delete this URL?");
						});
					});
				}
				
				jQuery("#PO_submit_url").click(function() {
					return(PO_form_validation('po_url_form'));
				});
			});

		</script>
		<?php
		
	}

	function add_hidden_start_order($pluginMeta, $pluginFile) {
		
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			if (array_search($pluginFile, $plugins) !== false) {
				$pluginMeta[0] .= "<input type=\"hidden\" class=\"start_order\" id=\"start_order_" . array_search($pluginFile, $plugins) . "\" value=\"" . array_search($pluginFile, $plugins) . "\">";
			}
		} else {
			wp_die("You dont have permissions to access this page.");
		}	
		return $pluginMeta;
	}
	
	function get_order_select($count, $plugins) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$orderSelect = "<select class=\"plugin_order_select\" name=\"order[]\" id=\"order_" . $count . "\" onchange=\"uniqueOrder('order_" . $count . "');\">";
				for ($i = 0; $i<sizeof($plugins); $i++) {
					$orderSelect .= "<option value=\"" . $i . "\" " . (($i == $count) ? "selected=\"selected\"" : "") . ">" . ($i+1) . "</option>";
				}
			$orderSelect .= "</select>";
			$orderSelect .= "<input type=\"hidden\" class=\"old_order\" id=\"old_order_" . $count . "\" value=\"" . $count . "\">";
			$orderSelect .= "<input type=\"hidden\" class=\"start_order\" id=\"start_order_" . $count . "\" value=\"" . $count . "\">";
			$orderSelect .= "<input type=\"button\" value=\"Save Order\" onmousedown=\"submitPluginLoadOrder();\">";
		} else {
			wp_die("You dont have permissions to access this page.");
		}	
		return $orderSelect;
	}

	function include_js_validation() {
		?>
		<style type="text/css">
			.badInputLabel {
				color: #FF0033;
				font-weight: bold;
			}
			.badInput {
				background-color: #FF0033;
			}
		</style>
		<script language="javascript" src="<?php print $this->urlPath; ?>/js/validation.js"></script>
		<script type="text/javascript" language="javascript">
			<?php
			print "var regex = new Array();\n";
			foreach ($this->regex as $key=>$val) {
				print "regex['$key'] = $val;\n";
			}
			?>
		</script>
		<?php
	}
	function ajax_plugin_group() {
		global $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$this->include_js_validation();
			?>
			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery("#saveGroup").click(function() {
						return(PO_form_validation('po_edit_plugin_group'));
					});
					jQuery("#createGroup").click(function() {
						return(PO_form_validation('po_create_plugin_group'));
					});
				});
				function submitPluginGroup(group_id){
					if (!PO_form_validation('po_edit_plugin_group')) {
						return false;
					}
					var groupList = new Array();
					jQuery('.group_member_check').each(function() {
						if (this.checked) {
							groupList[groupList.length] = this.value;
						}
					});
					var group_name=jQuery('#group_name').val();
					var revertHtml = jQuery('#plugingroupdiv .inside').html();
					jQuery('#plugingroupdiv .inside').html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
					
					if (groupList.length == 0) {
						groupList[0]="EMPTY";
					}
					jQuery.post(encodeURI(ajaxurl + '?action=PO_save_group'), { 'groupList[]': groupList, PO_group: group_id, PO_nonce: '<?php print $this->nonce; ?>', group_name: group_name }, function (result) {
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
	}
	
	function ajax_plugin_page() {
		global $wpdb;
		if (!array_key_exists('PO_group_view', $_REQUEST) && ($_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active' || !array_key_exists('plugin_status', $_REQUEST))) {
			if ( current_user_can( 'activate_plugins' ) ) {
				$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
				?>
				<script type="text/javascript" language="javascript">
					function save_draggable_plugin_order() {
						var orderList = new Array();
						var startOrderList = new Array();
						var count=0;
						jQuery('tr.active').each(function () {
							orderList[orderList.length] = count;
							startOrderList[startOrderList.length] = jQuery(this).find('.start_order').val();
							count++;
						});
						var load_element = '';
						var revertHtml = '';
						if (jQuery('#the-list').length) {
							load_element = jQuery('#the-list');
							revertHtml = load_element.html();
							load_element.html('<tr><td colspan=2 style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></td></tr>');
						} else {
							load_element = jQuery('#poststuff');
							revertHtml = load_element.html();
							load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
						}

						jQuery.post(encodeURI(ajaxurl + '?action=PO_plugin_organizer'), { 'orderList[]': orderList, 'startOrder[]': startOrderList, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
							alert(result);
							load_element.html(revertHtml);
							if (result == "The plugin load order has been changed.") {
								jQuery('.plugin_order_select').each(function() {
									var orderIndex = orderList.shift();
									jQuery("#" + this.id).val(orderIndex);
									jQuery("#start_" + this.id).val(orderIndex);
								});
							}
							make_plugins_draggable();
						});
					}
					jQuery(document).ready(function () {
						var saveOrderButton = '<div class="alignleft actions"><input type="button" class="save_plugin_order_button" name="save_plugin_order" value="Save Order"></div><br class="clear">';
						jQuery('.tablenav.top .clear').remove();
						jQuery('.tablenav.top').html(jQuery('.tablenav.top').html()+saveOrderButton);
						jQuery('.tablenav.bottom .clear').remove();
						jQuery('.tablenav.bottom').html(jQuery('.tablenav.bottom').html()+saveOrderButton);
						jQuery('.save_plugin_order_button').click(function() {
							save_draggable_plugin_order();
						});
						jQuery('.column-draghandle').css('width', '35px');
					});
					
				</script>
				<?php
			}
		} else if (array_key_exists('PO_group_view', $_REQUEST)) {
			?>
			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery('.subsubsub .count').remove();
				});
			</script>
			<?php
		}
	}
	
	function add_group_views($views) {
		global $wpdb;
		$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
		foreach ($groups as $group) {
			$group->group_members = unserialize($group->group_members);
			if ($group->group_members[0] != 'EMPTY') {
				$groupCount = sizeof($group->group_members);
			} else {
				$groupCount = 0;
			}
			$groupName = $group->group_name;
			$loopCount = 0;
			while(array_key_exists($groupName, $views) && $loopCount < 10) {
				$groupName = $group->group_name.$loopCount;
				$loopCount++;
			}
			$views[$groupName] = '<a href="'.$_SERVER['PHP_SELF'].'?PO_group_view='.$group->group_id.'">'.$group->group_name.'</a> ('.$groupCount.')';
		}
		return $views;
	}
	
	function make_draggable() {
		if (!array_key_exists('PO_group_view', $_REQUEST) && ($_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active' || !array_key_exists('plugin_status', $_REQUEST))) {
			wp_enqueue_script('jquery-tablednd', $this->urlPath.'/js/jquery.tablednd.js');
			?>
			<style type="text/css">
				tr.active .column-draghandle {
					background-image:url('<?php print $this->urlPath; ?>/image/drag-16x16.png');
					background-repeat:no-repeat;
					background-position:center;
				}
			</style>
			<script type="text/javascript" language="javascript">
				function make_plugins_draggable() {
					//jQuery('tr.inactive .draghandle').css('background', 'none');
					jQuery('tr.inactive').each(function () {
						jQuery(this).addClass('nodrag');
						jQuery(this).addClass('nodrop');
					});
					jQuery('#the-list').tableDnD({dragHandle: "column-draghandle"});
				}
				jQuery(document).ready(function() {
					make_plugins_draggable();
				});
			</script>
			<?php
		}
	}
	
	function ajax_load_order() {
		global $wpdb;
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
					jQuery('.plugin_order_select').each(function() {
						orderList[orderList.length] = this.value;
						startOrderList[startOrderList.length] = jQuery("#start_" + this.id).val();
					});
					var load_element = '';
					var revertHtml = '';
					if (jQuery('#the-list').length) {
						load_element = jQuery('#the-list');
						revertHtml = load_element.html();
						load_element.html('<tr><td colspan=2 style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></td></tr>');
					} else {
						load_element = jQuery('#poststuff');
						revertHtml = load_element.html();
						load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
					}
					
					
					jQuery.post(encodeURI(ajaxurl + '?action=PO_plugin_organizer'), { 'orderList[]': orderList, 'startOrder[]': startOrderList, PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
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
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<script type="text/javascript" language="javascript">
				function submitRedoPermalinks() {
					var load_element = jQuery('#redo-permalinks-div .inside');
					var revertHtml = load_element.html();
					load_element.html('<div style="width: 100%;text-align: center;"><img src="<?php print $this->urlPath . "/image/ajax-loader.gif"; ?>"></div>');
					jQuery.post(encodeURI(ajaxurl + '?action=PO_redo_permalinks'), { PO_nonce: '<?php print $this->nonce; ?>' }, function (result) {
						alert(result);
						load_element.html(revertHtml);
					});
				}

				function submitPostTypeSupport() {
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
				remove_action('all_plugins',  array($this, 'reorder_plugins'));
				$this->pluginPageActions = 0;
				return $allPluginList;
			}
		}
		$activePlugins = Array();
		$inactivePlugins = Array();
		$newPluginList = Array();
		$activePluginOrder = Array();
		
		if (is_numeric($_REQUEST['PO_group_view'])) {
			$groupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
			$group = $wpdb->get_row($wpdb->prepare($groupQuery, $_REQUEST['PO_group_view']), ARRAY_A);
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


	function get_column_headers($columns) {
		$count = 0;
		$newColumns = array();
		if (!array_key_exists('PO_group_view', $_REQUEST) && ($_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active' || !array_key_exists('plugin_status', $_REQUEST))) {
			foreach ($columns as $key=>$column) {
				if ($count==1) {
					$newColumns['draghandle'] = __('Drag');
					$newColumns[$key]=$column;
				} else {
					$newColumns[$key]=$column;
				}
				$count++;
			}
		} else {
			$newColumns = $columns;
		}
		#$columns['draghandle'] = __('Drag');
		return $newColumns;
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
			if (is_array($_POST['groupList']) && is_numeric($_POST['PO_group']) && $this->validate_field("group_name")) {
				$wpdb->update($wpdb->prefix."PO_groups", array("group_members"=>serialize($_POST['groupList']), 'group_name'=>$_POST['group_name']), array('group_id'=>$_POST['PO_group']));
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

	function get_disable_plugin_box($post) {
		global $wpdb;
		if ($post->ID != "" && is_numeric($post->ID)) {
			$postPluginsQuery = "SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$postPlugins = $wpdb->get_row($wpdb->prepare($postPluginsQuery, $post->ID), ARRAY_A);
		} else {
			$postPlugins = array();
		}
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


	function get_enable_plugin_box($post) {
		global $wpdb;
		$globalPlugins = get_option('PO_disabled_plugins');
		if (is_numeric($post->ID)) {
			$postPluginsQuery = "SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$postPlugins = $wpdb->get_row($wpdb->prepare($postPluginsQuery, $post->ID), ARRAY_A);
		} else {
			$postPlugins = array();
		}
		$pluginList = unserialize($postPlugins['enabled_plugins']);
		if (!is_array($pluginList)) {
			$pluginList = array();
		}
		if (!is_array($globalPlugins)) {
			$globalPlugins = array();
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
			$postCountQuery = "SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$postCount = $wpdb->get_row($wpdb->prepare($postCountQuery, $post_id), ARRAY_A);
			if ($postCount['count'] > 0) {
				 $wpdb->update($wpdb->prefix."PO_post_plugins", array("disabled_plugins"=>serialize($_POST['disabledPlugins'])), array("post_id"=>$post_id));
			} else {
				$wpdb->insert($wpdb->prefix."PO_post_plugins", array("disabled_plugins"=>serialize($_POST['disabledPlugins']), "permalink"=>get_permalink($post_id), "post_id"=>$post_id));
			}
		} else {
			$postCountQuery = "SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$postCount = $wpdb->get_row($wpdb->prepare($postCountQuery, $post_id), ARRAY_A);
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
			$postCountQuery = "SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$postCount = $wpdb->get_row($wpdb->prepare($postCountQuery, $post_id), ARRAY_A);
			if ($postCount['count'] > 0) {
				 $wpdb->update($wpdb->prefix."PO_post_plugins", array("enabled_plugins"=>serialize($_POST['enabledPlugins'])), array("post_id"=>$post_id));
			} else {
				$wpdb->insert($wpdb->prefix."PO_post_plugins", array("enabled_plugins"=>serialize($_POST['enabledPlugins']), "permalink"=>get_permalink($post_id), "post_id"=>$post_id));
			}
		} else {
			$postCountQuery = "SELECT count(*) as count FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$postCount = $wpdb->get_row($wpdb->prepare($postCountQuery, $post_id), ARRAY_A);
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
		if (is_numeric($post_id)) {
			$deletePluginQuery = "DELETE FROM ".$wpdb->prefix."PO_post_plugins WHERE post_id = %d";
			$wpdb->query($wpdb->prepare($deletePluginQuery, $post_id));
		}
	}

	function redo_permalinks() {
		global $wpdb;
		$failedCount = 0;
		$updatedCount = 0;
		$noUpdateCount = 0;
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$posts = $wpdb->get_results("SELECT post_id, permalink FROM ".$wpdb->prefix."PO_post_plugins", ARRAY_A);
		foreach ($posts as $post) {
			if (get_permalink($post['post_id']) != stripslashes_deep($post['permalink'])) {
				if($wpdb->update($wpdb->prefix."PO_post_plugins", array("permalink"=>get_permalink($post['post_id'])), array("post_id"=>$post['post_id']))) {
					$updatedCount++;
				} else {
					$failedCount++;
				}
			} else {
				$noUpdateCount++;
			}
		}

		if ($failedCount > 0) {
			print $failedCount . " permalinks failed to update!\n";
			print $updatedCount . " permalinks were updated successfully.\n";
			print $noUpdateCount . " permalinks were already up to date.";
		} else {
			print $updatedCount . " permalinks were updated successfully.\n";
			print $noUpdateCount . " permalinks were already up to date.";
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
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
		if (get_option("PO_version_num") != "2.5.4") {
			$this->activate();
		}
	}
	function activate() {
		global $wpdb;
		$sql = "CREATE TABLE ".$wpdb->prefix."PO_groups (
			group_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_name varchar(255) NOT NULL default '',
			group_members longtext NOT NULL,
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
			permalink longtext NOT NULL,
			disabled_plugins longtext NOT NULL,
			enabled_plugins longtext NOT NULL,
			PRIMARY KEY PO_post_id (post_id)
			);";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_post_plugins'") != $wpdb->prefix."PO_post_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}

		$sql = "CREATE TABLE ".$wpdb->prefix."PO_url_plugins (
			url_id bigint(20) unsigned NOT NULL auto_increment,
			permalink longtext NOT NULL,
			children int(1) NOT NULL default 0,
			disabled_plugins longtext NOT NULL,
			enabled_plugins longtext NOT NULL,
			PRIMARY KEY PO_id (url_id)
			);";
		
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_url_plugins'") != $wpdb->prefix."PO_url_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}

		//Add columns to PO_url_plugins table
		$showColumnSql = "SHOW COLUMNS FROM ".$wpdb->prefix."PO_url_plugins";
		$showColumnResults = $wpdb->get_results($showColumnSql);
		$fieldFound = 0;
		foreach ($showColumnResults as $column) {
			if ($column->Field == "children") {
				$fieldFound = 1;
			}
		}

		if ($fieldFound == 0) {
			$addColumnSql = "ALTER TABLE ".$wpdb->prefix."PO_url_plugins ADD COLUMN children int(1) NOT NULL default 0;";
			$addColumnResult = $wpdb->query($addColumnSql);
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
		
		//delete alternate admin setting if it exists.
		if (get_option('PO_alternate_admin') != "") {
			delete_option('PO_alternate_admin');
		}
		
		if (get_option('PO_fuzzy_url_matching') == "") {
			update_option('PO_fuzzy_url_matching', "1");
		}
		
		if (get_option('PO_preserve_settings') == "") {
			update_option('PO_preserve_settings', "1");
		}
		
		if (get_option("PO_version_num") != "2.5.4") {
			update_option("PO_version_num", "2.5.4");
		}
	}
	
	function deactivate() {
		global $wpdb;
		//Delete database tables and options if the option to preserve is set to 0.
		if (get_option("PO_preserve_settings") == "0") {
			$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_url_plugins");
			$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_post_plugins");
			$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_groups");

			delete_option("PO_preserve_settings");
			delete_option("PO_alternate_admin");
			delete_option("PO_fuzzy_url_matching");
			delete_option("PO_version_num");
			delete_option("PO_custom_post_type_support");
			delete_option("PO_disable_plugins");
			delete_option("PO_admin_disable_plugins");
			
		}
		if (file_exists(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php")) {
			@unlink(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php");
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
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugin_page=add_menu_page('Plugin Organizer', 'Plugin Organizer', 'activate_plugins', 'Plugin_Organizer', array($this, 'settings_page'), $this->urlPath."/image/po-icon-16x16.png");
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'settings_page_js'));
			add_action('admin_head-'.$plugin_page, array($this, 'common_js'));
			
			add_action('admin_head-plugins.php', array($this, 'plugin_page_js'));
			add_action('admin_head-plugins.php', array($this, 'make_draggable'));
			add_action('admin_head-post-new.php', array($this, 'admin_styles'));
			add_action('admin_head-post-new.php', array($this, 'common_js'));
			
			add_action('admin_head-post.php', array($this, 'admin_styles'));
			add_action('admin_head-post.php', array($this, 'common_js'));
			
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Settings', 'Settings', 'activate_plugins', 'Plugin_Organizer', array($this, 'settings_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'settings_page_js'));
			add_action('admin_head-'.$plugin_page, array($this, 'common_js'));
			
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Global Plugins', 'Global Plugins', 'activate_plugins', 'PO_global_plugins', array($this, 'global_plugins_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'global_plugins_js'));
			add_action('admin_head-'.$plugin_page, array($this, 'common_js'));

			$plugin_page=add_submenu_page('Plugin_Organizer', 'URL Admin', 'URL Admin', 'activate_plugins', 'PO_url_admin', array($this, 'url_admin'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_styles'));
			add_action('admin_head-'.$plugin_page, array($this, 'url_admin_js'));
			add_action('admin_head-'.$plugin_page, array($this, 'common_js'));

			
		}

	}

	function common_js() {
		require_once($this->absPath . "/tpl/common_js.php");
	}
	
	function plugin_page_js() {
		global $wpdb;
		require_once($this->absPath . "/tpl/plugin_page_js.php");
	}

	function url_admin_js() {
		require_once($this->absPath . "/tpl/url_admin_js.php");
	}

	function global_plugins_js() {
		require_once($this->absPath . "/tpl/global_plugins_js.php");
	}

	function settings_page_js() {
		require_once($this->absPath . "/tpl/settings_page_js.php");
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

			.activePlugin {
				color: #FF0033;
			}

			.badInputLabel {
				color: #FF0033;
				font-weight: bold;
			}
			.badInput {
				background-color: #FF0033;
			}
			
		</style>
		<?php
	}
		
	function settings_page() {
		global $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$errMsg = "";
			
			if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_groups'") != $wpdb->prefix."PO_groups") {
				$errMsg .= "A required database table is missing.  Please run the following sql command on your database server to create the missing table.<br />";
				$errMsg .= "CREATE TABLE ".$wpdb->prefix."PO_groups (group_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, group_name varchar(255) NOT NULL default '', group_members longtext NOT NULL, PRIMARY KEY PO_group_id (group_id));<br /><br />";
			}
			
			if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_post_plugins'") != $wpdb->prefix."PO_post_plugins") {
				$errMsg .= "A required database table is missing.  Please run the following sql command on your database server to create the missing table.<br />";
				$errMsg .= "CREATE TABLE ".$wpdb->prefix."PO_post_plugins (post_id bigint(20) unsigned NOT NULL, permalink longtext NOT NULL, disabled_plugins longtext NOT NULL, enabled_plugins longtext NOT NULL, PRIMARY KEY PO_post_id (post_id));<br /><br />";
			}

			if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_url_plugins'") != $wpdb->prefix."PO_url_plugins") {
				$errMsg .= "A required database table is missing.  Please run the following sql command on your database server to create the missing table.<br />";
				$errMsg .= "CREATE TABLE ".$wpdb->prefix."PO_url_plugins (url_id bigint(20) unsigned NOT NULL auto_increment, permalink longtext NOT NULL, children int(1) NOT NULL default 0, disabled_plugins longtext NOT NULL, enabled_plugins longtext NOT NULL, PRIMARY KEY PO_id (url_id));<br /><br />";
			}

			require_once($this->absPath . "/tpl/settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}
	
	function global_plugins_page($post_id) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_plugins();
			$disabledPlugins = get_option('PO_disabled_plugins');
			$activePlugins = get_option("active_plugins");
			$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
			if (!is_array($disabledPlugins)) {
				$disabledPlugins = array();
			}
			require_once($this->absPath . "/tpl/globalPlugins.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function submit_url() {
		global $wpdb;
		if ($this->validate_field("permalink") && wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			$errMsg = '';
			if (isset($_POST['effectChildren']) && preg_match("/^(1|0)$/", $_POST['effectChildren'])) {
				$effectChildren = $_POST['effectChildren'];
			} else {
				$effectChildren = 0;
			}

			if (isset($_POST['disabledPlugins']) && is_array($_POST['disabledPlugins'])) {
				$disabledPlugins = $_POST['disabledPlugins'];
			} else {
				$disabledPlugins = array();
			}

			if (isset($_POST['enabledPlugins']) && is_array($_POST['enabledPlugins'])) {
				$enabledPlugins = $_POST['enabledPlugins'];
			} else {
				$enabledPlugins = array();
			}
			
			if (isset($_POST['url_id']) && $_POST['url_id'] === '0') {
				$getDupUrlQuery = "SELECT count(*) as count FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink=%s";
				$getDupUrlResult = $wpdb->get_results($wpdb->prepare($getDupUrlQuery, $_POST['permalink']),ARRAY_A);
				$urlCount = $getDupUrlResult[0]['count'];
					
				if ($urlCount != 0) {
					$errMsg = "That URL already exists in the database.";
				} else {
					
					if ($wpdb->insert($wpdb->prefix."PO_url_plugins", array("disabled_plugins"=>serialize($disabledPlugins),"enabled_plugins"=>serialize($enabledPlugins), "permalink"=>$_POST['permalink'], "children"=>$effectChildren))) {
						$urlId = $wpdb->insert_id;
						if (!is_numeric($urlId)) {
							$urlId = 0;
						}
						$errMsg = "URL successfully added to the database.";
					} else {
						$errMsg = "There was a problem adding the URL";
					}
				}
			} else if (isset($_POST['url_id']) && is_numeric($_POST['url_id'])) {
				$urlId = $_POST['url_id'];
				
				$wpdb->update($wpdb->prefix."PO_url_plugins", array("disabled_plugins"=>serialize($disabledPlugins),"enabled_plugins"=>serialize($enabledPlugins), "permalink"=>$_POST['permalink'], "children"=>$effectChildren), array("url_id"=>$urlId));
				$errMsg = "URL successfully edited.";
			} else {
				$urlId = 0;
			}
			$this->get_url_admin_form($urlId, $errMsg, 1);
		}
		die();
	}
	
	function get_url_admin_form($urlId, $errMsg, $ajaxRequest=0) {
		global $wpdb;
		$urlDetailQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE url_id = %d";
		$urlDetails = $wpdb->get_row($wpdb->prepare($urlDetailQuery, $urlId), ARRAY_A);
		$disabledPlugins = unserialize($urlDetails['disabled_plugins']);
		$enabledPlugins = unserialize($urlDetails['enabled_plugins']);
		$effectChildren = $urlDetails['children'];
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
		
		require_once($this->absPath . "/tpl/urlForm.php");
	}
	
	function url_admin() {
		global $wpdb;
		if ( current_user_can( 'activate_plugins' ) ) {
			$errMsg = '';
			if (isset($_REQUEST['url_admin_page'])) {
				$urlAdminPage = $_REQUEST['url_admin_page'];
			} else {
				$urlAdminPage = '';
			}
			
			if ($urlAdminPage == "add") {
				//get the form
				$this->get_url_admin_form(0, '');
			} else if ($urlAdminPage == "edit") {
				if (isset($_REQUEST['url_id']) && is_numeric($_REQUEST['url_id'])) {
					$urlId = $_REQUEST['url_id'];
				} else {
					$urlId = 0;
				}
				
				//get the form
				$this->get_url_admin_form($urlId, '');
			} else {
				if (isset($_REQUEST['url_id']) && is_numeric($_REQUEST['url_id']) && isset($_REQUEST['delete_url']) && $_REQUEST['delete_url'] == 1 && wp_verify_nonce( $_REQUEST['PO_nonce'], plugin_basename(__FILE__) )) {
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
	
	function add_group_views($views) {
		global $wpdb;
		$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
		if (!array_key_exists('all', $views)) {
			$views = array_reverse($views, true);
			$views['all'] = '<a href="'.$_SERVER['PHP_SELF'].'?plugin_status=all">All <span class="count">('.count(get_plugins()).')</span></a>';
			$views = array_reverse($views, true);
		}
		foreach ($groups as $group) {
			$group->group_members = unserialize($group->group_members);
			if (isset($group->group_members[0]) && $group->group_members[0] != 'EMPTY') {
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
			$views[$groupName] = '<a href="'.$_SERVER['PHP_SELF'].'?PO_group_view='.$group->group_id.'">'.$group->group_name.' <span class="count">('.$groupCount.')</span></a> ';
		}
		return $views;
	}
	
	function make_draggable() {
		if ($this->pluginPageActions == '1' && !isset($_REQUEST['PO_group_view']) && (!isset($_REQUEST['plugin_status']) || $_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active')) {
			?>
			<script type="text/javascript" src="<?php print $this->urlPath.'/js/jquery.tablednd.js'; ?>"></script>
			<style type="text/css">
				tr.active .column-PO_draghandle {
					background-image:url('<?php print $this->urlPath; ?>/image/drag-16x16.png');
					background-repeat:no-repeat;
					background-position:center;
				}
			</style>
			<script type="text/javascript" language="javascript">
				function make_plugins_draggable() {
					//jQuery('tr.inactive .PO_draghandle').css('background', 'none');
					jQuery('tr.inactive').each(function () {
						jQuery(this).addClass('nodrag');
						jQuery(this).addClass('nodrop');
					});
					jQuery('#the-list').tableDnD({dragHandle: "column-PO_draghandle"});
				}
				jQuery(document).ready(function() {
					make_plugins_draggable();
				});
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
		$networkPluginFound = 0;
		foreach($plugins as $key=>$pluginFile) {
			if (is_plugin_active_for_network($pluginFile)) {
				$networkPluginFound = 1;
				array_splice($plugins, $key, 1);
			}
		}
		if ($networkPluginFound == 1) {
			update_option("active_plugins", $plugins);
		}
		
		if (is_admin() && $this->pluginPageActions == 1 && (!isset($_REQUEST['PO_group_view']) || !is_numeric($_REQUEST['PO_group_view']))) {
			$perPage = get_user_option("plugins_per_page");
			if (!is_numeric($perPage)) {
				$perPage = 999;
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
		
		if (isset($_REQUEST['PO_group_view']) && is_numeric($_REQUEST['PO_group_view'])) {
			$groupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
			$group = $wpdb->get_row($wpdb->prepare($groupQuery, $_REQUEST['PO_group_view']), ARRAY_A);
			$members = unserialize($group['group_members']);
			foreach ($allPluginList as $key=>$val) {
				if (is_array($members) && in_array($val['Name'], $members)) {
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
		if ($this->pluginPageActions == '1' && !array_key_exists('PO_group_view', $_REQUEST) && ($_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active' || !array_key_exists('plugin_status', $_REQUEST))) {
			foreach ($columns as $key=>$column) {
				if ($count==1) {
					$newColumns['PO_draghandle'] = __('Drag');
					$newColumns[$key]=$column;
				} else {
					$newColumns[$key]=$column;
				}
				$count++;
			}
		} else {
			$newColumns = $columns;
		}
		$newColumns['PO_groups'] = __('Groups');
		return $newColumns;
	}

	function set_custom_column_values($column_name, $pluginPath, $plugin ) {
		global $wpdb;
		switch ($column_name) {
			case 'PO_groups' :
				$groups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups", ARRAY_A);
				$assignedGroups = "";
				foreach ($groups as $group) {
					$members = unserialize($group['group_members']);
					if (array_search($plugin['Name'], $members) !== FALSE) {
						$assignedGroups .= '<a href="'.get_admin_url().'plugins.php?PO_group_view='.$group['group_id'].'">'.$group['group_name'].'</a> ,';
					}
				}
				print rtrim($assignedGroups, ',');
				break;
			default:
		}
	}

	
	function change_page_title($translation, $original) {
		global $pagenow, $wpdb;
		if ($pagenow == "plugins.php" && $original == 'Plugins') {
			if (isset($_REQUEST['PO_group_view']) && is_numeric($_REQUEST['PO_group_view'])) {
				$groupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
				$group = $wpdb->get_row($wpdb->prepare($groupQuery, $_REQUEST['PO_group_view']), ARRAY_A);
				if (is_array($group)) {
					return 'Plugin Group: '.$group['group_name'];
				}
			}
		}
		return $translation;
	}
	
	function save_group() {
		global $wpdb;
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			if (is_array($_POST['groupList']) && is_numeric($_POST['PO_group']) && $this->validate_field("group_name")) {
				$wpdb->update($wpdb->prefix."PO_groups", array("group_members"=>serialize($_POST['groupList']), 'group_name'=>$_POST['group_name']), array('group_id'=>$_POST['PO_group']));
				$returnStatus = "The plugin group has been updated.";
			} else if (is_array($_POST['groupList']) && $_POST['PO_group'] == "" && $this->validate_field("group_name")) {
				$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>$_POST['group_name'], "group_members"=>serialize($_POST['groupList'])));
				$returnStatus = "The plugin group has been created.";
			} else {
				$returnStatus = "Did not recieve the proper variables.  No changes made.";
			}
		} else {
			$returnStatus = "You dont have permissions to access this page.";
		}
		print $returnStatus;
		die();
	}

	function add_to_group() {
		global $wpdb;
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = get_option("active_plugins");
			if (is_array($_POST['groupList']) && is_numeric($_POST['PO_group']) && $this->validate_field("group_name")) {
				$groupQuery = "SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d";
				$group = $wpdb->get_row($wpdb->prepare($groupQuery, $_POST['PO_group']), ARRAY_A);
				$members = unserialize($group['group_members']);
				#print_r($members);
				foreach($_POST['groupList'] as $newGroupMember) {
					#print $newGroupMember . " - " . array_search($newGroupMember, $members) . "\n";
					if (array_search($newGroupMember, $members) === FALSE) {
						$members[]=$newGroupMember;
					}
				}
				if ($members === unserialize($group['group_members'])) {
					$returnStatus = "The selected plugins were not added to the group because they already belong to it.";
				} else {
					$wpdb->update($wpdb->prefix."PO_groups", array("group_members"=>serialize($members), 'group_name'=>$_POST['group_name']), array('group_id'=>$_POST['PO_group']));
					$returnStatus = "The plugin group has been updated.";
				}
			} else if (is_array($_POST['groupList']) && $_POST['PO_group'] == "" && $this->validate_field("group_name")) {
				$wpdb->insert($wpdb->prefix."PO_groups", array("group_name"=>$_POST['group_name'], "group_members"=>serialize($_POST['groupList'])));
				$returnStatus = "The plugin group has been created.";
			} else {
				$returnStatus = "Did not recieve the proper variables.  No changes made.";
			}
		} else {
			$returnStatus = "You dont have permissions to access this page.";
		}
		print $returnStatus;
		die();
	}

	function delete_group() {
		global $wpdb;
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if (current_user_can('activate_plugins') && is_numeric($_POST['PO_group'])) {
			$deleteGroupQuery = "DELETE FROM ".$wpdb->prefix."PO_groups WHERE group_id=%d";
			$result = $wpdb->query($wpdb->prepare($deleteGroupQuery, $_POST['PO_group']));
			if ($result) {
				$returnStatus = "The plugin group has been deleted.";
			} else {
				$returnStatus = "There was a problem deleting the plugin group.";
			}
		}
		print $returnStatus;
		die();
	}

	function remove_plugins_from_group() {
		global $wpdb;
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if (current_user_can('activate_plugins') && is_numeric($_POST['PO_group'])) {
			$currGroup = stripslashes_deep($wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."PO_groups WHERE group_id = %d", $_POST['PO_group']), ARRAY_A));
			$members = unserialize($currGroup['group_members']);
			foreach($_POST['groupList'] as $key=>$pluginToRemove) {
				if (array_search($pluginToRemove, $members) !== FALSE) {
					unset($members[array_search($pluginToRemove, $members)]);
				}
			}
			$members = array_values($members);
			if ($members === unserialize($currGroup['group_members'])) {
				$returnStatus = "The selected plugins were not found in the group.";
			} else {
				if ($wpdb->update($wpdb->prefix."PO_groups", array("group_members"=>serialize($members)), array('group_id'=>$_POST['PO_group']))) {
					$returnStatus = "The selected plugins were removed from the group.";
				} else {
					$returnStatus = "There was a problem removing the plugins from the group.";
				}
			}
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
		$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
		?>
		<input type="checkbox" id="selectAllDisablePlugins" name="selectAllDisablePlugins" value="" onclick="PO_check_all_disable_plugins();">Select All<br><br>
		<?php
		foreach ($plugins as $key=>$plugin) {
			?>
			<input class="disabled_plugin_check" type="checkbox" name="disabledPlugins[]" value="<?php print $key; ?>" <?php print (in_array($key, $pluginList))? 'checked="checked"':''; ?>><?php print (in_array($key, $activeSitewidePlugins) || in_array($key, $activePlugins))? "<span class=\"activePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
			<?php
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
		$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
		?>
		<input type="checkbox" id="selectAllEnablePlugins" name="selectAllEnablePlugins" value="" onclick="PO_check_all_enable_plugins();">Select All<br><br>
		<?php
		foreach ($plugins as $key=>$plugin) {
			if (in_array($key, $globalPlugins)) {
				?>
				<input class="enabled_plugin_check" type="checkbox" name="enabledPlugins[]" value="<?php print $key; ?>" <?php print (in_array($key, $pluginList))? 'checked="checked"':''; ?>><?php print (in_array($key, $activeSitewidePlugins) || in_array($key, $activePlugins))? "<span class=\"activePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
				<?php
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

	function reset_plugin_order() {
		$activePlugins = get_option("active_plugins");
		usort($activePlugins, array($this, 'custom_sort_plugins'));
		update_option("active_plugins", $activePlugins);
		update_option("PO_plugin_order", $activePlugins);
		print "The order has been reset.";
		die();
	}

	function custom_sort_plugins($a, $b) { 
		$aData = get_plugin_data(WP_PLUGIN_DIR.'/'.$a);
		$bData = get_plugin_data(WP_PLUGIN_DIR.'/'.$b);
		return strcasecmp($aData['Name'], $bData['Name']);
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

	function manage_mu_plugin() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$result = "";
		if ($_POST['selected_action'] == 'delete') {
			if (file_exists(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php")) {
				if (@unlink(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php")) {
					$result = "The MU plugin component has been removed.";
				} else {
					$result = "There was an issue removing the MU plugin component!";
				}
			} else {
				$result = "There was an issue removing the MU plugin component!";
			}
		} else if ($_POST['selected_action'] == 'move') {
			if (!file_exists(ABSPATH . "wp-content/mu-plugins/")) {
				@mkdir(ABSPATH . "wp-content/mu-plugins/");
			}
			if (file_exists(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php")) {
				@copy(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php", ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php");
			}
			if (file_exists(ABSPATH . "wp-content/mu-plugins/PluginOrganizerMU.class.php")) {
				$result = "The MU plugin component has been moved to the mu-plugins folder.";
			} else {
				$result = "There was an issue moving the MU plugin component!";
			}
		}
		print $result;
		die();
	}

	function set_ignore_protocol() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$result = "";
		if (preg_match("/^(1|0)$/", $_POST['PO_ignore_protocol'])) {
			update_option("PO_ignore_protocol", $_POST['PO_ignore_protocol']);
			$result = "Update was successful.";
		} else {
			$result = "Update failed.";
		}
		print $result;
		die();
	}

	function set_ignore_arguments() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$result = "";
		if (preg_match("/^(1|0)$/", $_POST['PO_ignore_arguments'])) {
			update_option("PO_ignore_arguments", $_POST['PO_ignore_arguments']);
			$result = "Update was successful.";
		} else {
			$result = "Update failed.";
		}
		print $result;
		die();
	}



	function set_fuzzy_url_matching() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$result = "";
		if (preg_match("/^(1|0)$/", $_POST['PO_fuzzy_url_matching'])) {
			update_option("PO_fuzzy_url_matching", $_POST['PO_fuzzy_url_matching']);
			$result = "Update was successful.";
		} else {
			$result = "Update failed.";
		}
		print $result;
		die();
	}

	function set_disable_plugin_settings() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		
		$result = "";
		if (preg_match("/^(1|0)$/", $_POST['PO_disable_plugins'])) {
			update_option("PO_disable_plugins", $_POST['PO_disable_plugins']);
			$result = "Update was successful.";
		} else {
			$result = "Update failed.";
		}

		if (preg_match("/^(1|0)$/", $_POST['PO_admin_disable_plugins'])) {
			update_option("PO_admin_disable_plugins", $_POST['PO_admin_disable_plugins']);
			$result .= "Update was successful.";
		} else {
			$result .= "Update failed.";
		}
		print $result;
		die();
	}

	function set_preserve_settings() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$result = "";
		if (preg_match("/^(1|0)$/", $_POST['PO_preserve_settings'])) {
			update_option('PO_preserve_settings', $_POST['PO_preserve_settings']);
			$result = "Update was successful.";
		} else {
			$result = "Update failed.";
		}
		print $result;
		die();
	}
}
?>
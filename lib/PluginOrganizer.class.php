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
		if (get_option("PO_version_num") != "3.2.1") {
			$this->activate();
		}
	}
	
	function move_old_groups() {
		global $wpdb;
		if (get_option('PO_old_groups_moved') == '') {
			$groupList = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_groups");
			foreach ($groupList as $group) {
				$post_id = wp_insert_post(array('post_title'=>$group->group_name, 'post_type'=>'plugin_group', 'post_status'=>'publish'));
				if (!is_wp_error($post_id)) {
					update_post_meta($post_id, '_PO_group_members', unserialize($group->group_members));
				}
			}
		}
		$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_groups");
	}

	function move_old_post_plugins() {
		global $wpdb;
		if (get_option('PO_old_posts_moved') == '') {
			$postList = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_post_plugins");
			foreach ($postList as $post) {
				if (is_numeric($post->post_id)) {
					update_post_meta($post->post_id, '_PO_enabled_plugins', unserialize($post->enabled_plugins));
					update_post_meta($post->post_id, '_PO_disabled_plugins', unserialize($post->disabled_plugins));
					update_post_meta($post->post_id, '_PO_affect_children', $post->children);
					update_post_meta($post->post_id, '_PO_permalink', $post->permalink);
				}
			}
		}
		$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_post_plugins");
	}
	
	function move_old_url_plugins() {
		global $wpdb;
		if (get_option('PO_old_urls_moved') == '') {
			$postList = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."PO_url_plugins");
			foreach ($postList as $post) {
				$post_id = wp_insert_post(array('post_title'=>$post->permalink, 'post_type'=>'plugin_filter', 'post_status'=>'publish'));
				if (!is_wp_error($post_id)) {
					update_post_meta($post_id, '_PO_enabled_plugins', unserialize($post->enabled_plugins));
					update_post_meta($post_id, '_PO_disabled_plugins', unserialize($post->disabled_plugins));
					update_post_meta($post_id, '_PO_affect_children', $post->children);
					update_post_meta($post_id, '_PO_permalink', $post->permalink);
				}
			}
		}
		$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_url_plugins");
	}

	function activate() {
		global $wpdb;
		if ($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_groups'") == $wpdb->prefix."PO_groups") {
			$this->move_old_groups();
		}

		if ($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_post_plugins'") == $wpdb->prefix."PO_post_plugins") {
			$this->move_old_post_plugins();
		}
		
		if ($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_url_plugins'") == $wpdb->prefix."PO_url_plugins") {
			$this->move_old_url_plugins();
		}
		
		$postTypeSupport = get_option("PO_custom_post_type_support");
		if (!is_array($postTypeSupport)) {
			$postTypeSupport = array('plugin_filter');
		} else {
			$postTypeSupport[] = 'plugin_filter';
		}
		
		if (!file_exists(WPMU_PLUGIN_DIR)) {
			@mkdir(WPMU_PLUGIN_DIR);
		}

		if (file_exists(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
			@unlink(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
		}
		
		if (file_exists(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php")) {
			@copy(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php", WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
		}
		
		if (!is_array(get_option("PO_custom_post_type_support"))) {
			update_option("PO_custom_post_type_support", array("post", "page"));
		}
		
		if (get_option('PO_fuzzy_url_matching') == "") {
			update_option('PO_fuzzy_url_matching', "1");
		}
		
		if (get_option('PO_preserve_settings') == "") {
			update_option('PO_preserve_settings', "1");
		}
		
		if (get_option("PO_version_num") != "3.2.1") {
			update_option("PO_version_num", "3.2.1");
		}

		//Add capabilities to the administrator role
		$administrator = get_role( 'administrator' );
		if ( is_object($administrator) ) {			
			$administrator->add_cap('edit_plugin_filter');
			$administrator->add_cap('edit_plugin_filters');
			$administrator->add_cap('edit_private_plugin_filters');
			$administrator->add_cap('delete_plugin_filter');
			$administrator->add_cap('delete_plugin_filters');
			$administrator->add_cap('edit_others_plugin_filters');
			$administrator->add_cap('read_plugin_filters');
			$administrator->add_cap('read_private_plugin_filters');
			$administrator->add_cap('publish_plugin_filters');
			$administrator->add_cap('delete_others_plugin_filters');
			$administrator->add_cap('delete_published_plugin_filters');
			$administrator->add_cap('delete_private_plugin_filters');

			$administrator->add_cap('edit_plugin_group');
			$administrator->add_cap('edit_plugin_groups');
			$administrator->add_cap('edit_private_plugin_groups');
			$administrator->add_cap('delete_plugin_group');
			$administrator->add_cap('delete_plugin_groups');
			$administrator->add_cap('edit_others_plugin_groups');
			$administrator->add_cap('read_plugin_groups');
			$administrator->add_cap('read_private_plugin_groups');
			$administrator->add_cap('publish_plugin_groups');
			$administrator->add_cap('delete_others_plugin_groups');
			$administrator->add_cap('delete_published_plugin_groups');
			$administrator->add_cap('delete_private_plugin_groups');
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
		if (file_exists(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
			@unlink(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
		}

		$administrator = get_role( 'administrator' );
		if ( is_object($administrator) ) {			
			$administrator->remove_cap('edit_plugin_filter');
			$administrator->remove_cap('edit_plugin_filters');
			$administrator->remove_cap('edit_private_plugin_filters');
			$administrator->remove_cap('delete_plugin_filter');
			$administrator->remove_cap('delete_plugin_filters');
			$administrator->remove_cap('edit_others_plugin_filters');
			$administrator->remove_cap('read_plugin_filters');
			$administrator->remove_cap('read_private_plugin_filters');
			$administrator->remove_cap('publish_plugin_filters');
			$administrator->remove_cap('delete_others_plugin_filters');
			$administrator->remove_cap('delete_published_plugin_filters');
			$administrator->remove_cap('delete_private_plugin_filters');

			$administrator->remove_cap('edit_plugin_group');
			$administrator->remove_cap('edit_plugin_groups');
			$administrator->remove_cap('edit_private_plugin_groups');
			$administrator->remove_cap('delete_plugin_group');
			$administrator->remove_cap('delete_plugin_groups');
			$administrator->remove_cap('edit_others_plugin_groups');
			$administrator->remove_cap('read_plugin_groups');
			$administrator->remove_cap('read_private_plugin_groups');
			$administrator->remove_cap('publish_plugin_groups');
			$administrator->remove_cap('delete_others_plugin_groups');
			$administrator->remove_cap('delete_published_plugin_groups');
			$administrator->remove_cap('delete_private_plugin_groups');
		}
	}
	
	function create_default_group() {
		global $wpdb;
		$post_id = wp_insert_post(array('post_title'=>"Default", 'post_type'=>'plugin_group', 'post_status'=>'publish'));
		if (!is_wp_error($post_id)) {
			update_post_meta($post_id, '_PO_group_members', array());
		}
		update_option("PO_default_group", $post_id);
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
			add_action('admin_head-plugins.php', array($this, 'plugin_page_js'));
			add_action('admin_head-plugins.php', array($this, 'make_draggable'));
			add_action('admin_head-post-new.php', array($this, 'admin_css'));
			add_action('admin_head-post-new.php', array($this, 'common_js'));
			
			add_action('admin_head-post.php', array($this, 'admin_css'));
			add_action('admin_head-post.php', array($this, 'common_js'));
			
			$plugin_page=add_submenu_page('options-general.php', 'Plugin Organizer Settings', 'Plugin Organizer', 'activate_plugins', 'Plugin_Organizer', array($this, 'settings_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_css'));
			add_action('admin_head-'.$plugin_page, array($this, 'settings_page_js'));
			add_action('admin_head-'.$plugin_page, array($this, 'common_js'));
			
			$plugin_page=add_submenu_page('edit.php?post_type=plugin_filter', 'Global Plugins', 'Global Plugins', 'activate_plugins', 'PO_global_plugins', array($this, 'global_plugins_page'));
			add_action('admin_head-'.$plugin_page, array($this, 'admin_css'));
			add_action('admin_head-'.$plugin_page, array($this, 'global_plugins_js'));
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

	function global_plugins_js() {
		require_once($this->absPath . "/tpl/global_plugins_js.php");
	}

	function settings_page_js() {
		require_once($this->absPath . "/tpl/settings_page_js.php");
	}

	function admin_css() {
		require_once($this->absPath . "/tpl/admin_css.php");
	}
		
	function check_mu_plugin() {
		$muPlugins = get_mu_plugins();
		if (!isset($muPlugins['PluginOrganizerMU.class.php']['Version'])) {
			return "You are missing the MU Plugin.  Please use the tool provided on the settings page to move the plugin into place or manually copy ".$this->absPath."/lib/PluginOrganizerMU.class.php to ".WPMU_PLUGIN_DIR."/PluginOrganizerMU.class.php.  If you don't do this the plugin will not work.  This message will disappear when everything is correct.";
		} else if (isset($muPlugins['PluginOrganizerMU.class.php']['Version']) && $muPlugins['PluginOrganizerMU.class.php']['Version'] != get_option("PO_version_num")) {
			return "You are running an old version of the MU Plugin.  Please use the tool provided on the settings page to move the updated version into place or manually copy ".$this->absPath."/lib/PluginOrganizerMU.class.php to ".WPMU_PLUGIN_DIR."/PluginOrganizerMU.class.php.  If you don't do this the plugin will not work.  This message will disappear when everything is correct.";
		} else {
			return "";
		}
	}
	
	function settings_page() {
		global $wpdb;
		
		if ( current_user_can( 'activate_plugins' ) ) {
			$muPlugins = get_mu_plugins();
			$errMsg = $this->check_mu_plugin();

			require_once($this->absPath . "/tpl/settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}
	
	function global_plugins_page($post_id) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$errMsg = $this->check_mu_plugin();
			$plugins = $this->reorder_plugins(get_plugins());
			$disabledPlugins = get_option('PO_disabled_plugins');
			$disabledMobilePlugins = get_option('PO_disabled_mobile_plugins');
			$activePlugins = $this->get_active_plugins();
			$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
			if (!is_array($disabledPlugins)) {
				$disabledPlugins = array();
			}
			if (!is_array($disabledMobilePlugins)) {
				$disabledMobilePlugins = array();
			}
			require_once($this->absPath . "/tpl/globalPlugins.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}	

	function add_hidden_start_order($pluginMeta, $pluginFile) {
		
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = $this->get_active_plugins();
			if (array_search($pluginFile, $plugins) !== false) {
				$pluginMeta[0] .= "<input type=\"hidden\" class=\"start_order\" id=\"start_order_" . array_search($pluginFile, $plugins) . "\" value=\"" . array_search($pluginFile, $plugins) . "\">";
			}
		} else {
			wp_die("You dont have permissions to access this page.");
		}	
		return $pluginMeta;
	}
	
	function add_group_views($views) {
		$groups = get_posts(array('post_type'=>'plugin_group', 'posts_per_page'=>-1));
		if (!array_key_exists('all', $views)) {
			$views = array_reverse($views, true);
			$views['all'] = '<a href="'.$_SERVER['PHP_SELF'].'?plugin_status=all">All <span class="count">('.count(get_plugins()).')</span></a>';
			$views = array_reverse($views, true);
		}
		foreach ($groups as $group) {
			$group_members = get_post_meta($group->ID, '_PO_group_members', $single=true);
			if (isset($group_members[0]) && $group_members[0] != 'EMPTY') {
				$groupCount = sizeof($group_members);
			} else {
				$groupCount = 0;
			}
			$groupName = $group->post_title;
			$loopCount = 0;
			while(array_key_exists($groupName, $views) && $loopCount < 10) {
				$groupName = $group->post_title.$loopCount;
				$loopCount++;
			}
			$views[$groupName] = '<a href="'.$_SERVER['PHP_SELF'].'?PO_group_view='.$group->ID.'">'.$group->post_title.' <span class="count">('.$groupCount.')</span></a> ';
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
			if (is_array($_POST['disabledList'])) {
				$disabledPlugins = $_POST['disabledList'];
				update_option("PO_disabled_plugins", $disabledPlugins);
				$returnStatus .= "Global plugin list has been saved.\n";
			} else {
				update_option("PO_disabled_plugins", array());
				$returnStatus .= "Global mobile plugin list has been saved.\n";
			}
			if (get_option('PO_disable_mobile_plugins') == 1) {
				if (is_array($_POST['disabledMobileList'])) {
					$disabledMobilePlugins = $_POST['disabledMobileList'];
					update_option("PO_disabled_mobile_plugins", $disabledMobilePlugins);
					$returnStatus .= "Global mobile plugin list has been saved.\n";
				} else {
					update_option("PO_disabled_mobile_plugins", array());
					$returnStatus .= "Global mobile plugin list has been saved.\n";
				}
			}
		} else {
			$returnStatus .= "You dont have permissions to access this page.\n";
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
			$plugins = $this->get_active_plugins();
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

	function get_active_plugins() {
		global $PluginOrganizerMU;
		if (is_object($PluginOrganizerMU)) {
			remove_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);
		}
		
		$plugins = get_option("active_plugins");
		
		#print_r($plugins);
		$networkPlugins = get_site_option('active_sitewide_plugins');
		$networkPluginMissing = 0;
		foreach($networkPlugins as $key=>$pluginFile) {
			if (!array_search($key, $plugins)) {
				$plugins[] = $key;
				$networkPluginMissing = 1;
			}
		}
		#print_r($plugins);
		if ($networkPluginMissing == 1) {
			update_option("active_plugins", $plugins);
		}
		
		if (is_object($PluginOrganizerMU)) {
			add_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);
		}
		
		return $plugins;
	}
	
	function reorder_plugins($allPluginList) {
		global $wpdb;
		$plugins = $this->get_active_plugins();
		
		
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
		
		$globalPlugins = get_option('PO_disabled_plugins');
		if (!is_array($globalPlugins)) {
			$globalPlugins = array();
		}
		
		if (isset($_REQUEST['PO_group_view']) && is_numeric($_REQUEST['PO_group_view'])) {
			$members = get_post_meta($_REQUEST['PO_group_view'], '_PO_group_members', $single=true);
			$members = stripslashes_deep($members);
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
		if ($this->pluginPageActions == '1' && !isset($_REQUEST['PO_group_view']) && (!isset($_REQUEST['plugin_status']) || $_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active')) {
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
				$groups = get_posts(array('post_type'=>'plugin_group', 'posts_per_page'=>-1));
				$assignedGroups = "";
				foreach ($groups as $group) {
					$members = get_post_meta($group->ID, '_PO_group_members', $single=true);
					$members = stripslashes_deep($members);
					if (is_array($members) && array_search($plugin['Name'], $members) !== FALSE) {
						$assignedGroups .= '<a href="'.get_admin_url().'plugins.php?PO_group_view='.$group->ID.'">'.$group->post_title.'</a> ,';
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
				$group = get_posts(array('ID'=>$_REQUEST['PO_group_view'], 'post_type'=>'plugin_group'));
				if (is_array($group[0])) {
					return 'Plugin Group: '.$group[0]->post_title;
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
			$plugins = $this->get_active_plugins();
			if (is_array($_POST['groupList']) && is_numeric($_POST['PO_group']) && $this->validate_field("group_name")) {
				$post_id = wp_update_post(array('ID'=>$_POST['PO_group'], 'post_title'=>$_POST['group_name']));
				if ($post_id > 0) {
					update_post_meta($post_id, "_PO_group_members", $_POST['groupList']);
				}
				$returnStatus = "The plugin group has been updated.";
			} else if (is_array($_POST['groupList']) && $_POST['PO_group'] == "" && $this->validate_field("group_name")) {
				$post_id = wp_insert_post(array('post_title'=>$_POST['group_name'], 'post_type'=>'plugin_group', 'post_status'=>'publish'));
				if (!is_wp_error($post_id)) {
					update_post_meta($post_id, "_PO_group_members", $_POST['groupList']);
				}
					
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
		if ( !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		$returnStatus = "";
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = $this->get_active_plugins();
			if (is_array($_POST['groupList']) && is_numeric($_POST['PO_group']) && $this->validate_field("group_name")) {
				$members = get_post_meta($_POST['PO_group'], '_PO_group_members', $single=true);
				$members = stripslashes_deep($members);
				if (!is_array($members)) {
					$members = array();
				}
				
				foreach($_POST['groupList'] as $newGroupMember) {
					#print $newGroupMember . " - " . array_search($newGroupMember, $members) . "\n";
					if (array_search($newGroupMember, $members) === FALSE) {
						$members[]=$newGroupMember;
					}
				}
				if ($members === get_post_meta($_POST['PO_group'], '_PO_group_members', $single=true)) {
					$returnStatus = "The selected plugins were not added to the group because they already belong to it.";
				} else {
					$post_id = wp_update_post(array('ID'=>$_POST['PO_group'], 'post_title'=>$_POST['group_name']));
					if ($post_id > 0) {
						update_post_meta($post_id, "_PO_group_members", $members);
					}
					$returnStatus = "The plugin group has been updated.";
				}
			} else if (is_array($_POST['groupList']) && $_POST['PO_group'] == "" && $this->validate_field("group_name")) {
				$post_id = wp_insert_post(array('post_title'=>$_POST['group_name'], 'post_type'=>'plugin_group', 'post_status'=>'publish'));
				if (!is_wp_error($post_id)) {
					update_post_meta($post_id, "_PO_group_members", $_POST['groupList']);
				}
				
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
			$result = wp_delete_post($_POST['PO_group'], true);
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
			$members = get_post_meta($_POST['PO_group'], '_PO_group_members', $single=true);
			if (!is_array($members)) {
				$members = array();
			}
			foreach($_POST['groupList'] as $key=>$pluginToRemove) {
				if (array_search($pluginToRemove, $members) !== FALSE) {
					unset($members[array_search($pluginToRemove, $members)]);
				}
			}
			$members = array_values($members);
			if ($members === get_post_meta($_POST['PO_group'], '_PO_group_members', $single=true)) {
				$returnStatus = "The selected plugins were not found in the group.";
			} else {
				$result = update_post_meta($_POST['PO_group'], "_PO_group_members", $members);
				if ($result) {
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
			$supportedPostTypes[] = 'plugin_filter';
			if (is_array($supportedPostTypes)) {
				foreach ($supportedPostTypes as $postType) {
					add_meta_box(
						'plugin_organizer',
						'Plugin Organizer',
						array($this, 'get_post_meta_box'),
						$postType,
						'normal',
						'high'
					);
				}
			}
		}
	}

	function get_post_meta_box($post) {
		global $wpdb;
		$errMsg = $this->check_mu_plugin();
		if ($post->ID != "" && is_numeric($post->ID)) {
			$filterName = $post->post_title;
			$affectChildren = get_post_meta($post->ID, '_PO_affect_children', $single=true);
			$disabledPluginList = get_post_meta($post->ID, '_PO_disabled_plugins', $single=true);
			if (!is_array($disabledPluginList)) {
				$disabledPluginList = array();
			}

			$enabledPluginList = get_post_meta($post->ID, '_PO_enabled_plugins', $single=true);
			if (!is_array($enabledPluginList)) {
				$enabledPluginList = array();
			}

			$disabledMobilePluginList = get_post_meta($post->ID, '_PO_disabled_mobile_plugins', $single=true);
			if (!is_array($disabledMobilePluginList)) {
				$disabledMobilePluginList = array();
			}

			$enabledMobilePluginList = get_post_meta($post->ID, '_PO_enabled_mobile_plugins', $single=true);
			if (!is_array($enabledMobilePluginList)) {
				$enabledMobilePluginList = array();
			}

			$permalinkFilter = get_post_meta($post->ID, '_PO_permalink', $single=true);
		} else {
			$filterName = "";
			$affectChildren = 0;
			$disabledPluginList = array();
			$enabledPluginList = array();
			$disabledMobilePluginList = array();
			$enabledMobilePluginList = array();
			$permalinkFilter = "";
		}
		
		$globalPlugins = get_option('PO_disabled_plugins');
		if (!is_array($globalPlugins)) {
			$globalPlugins = array();
		}

		$globalMobilePlugins = get_option('PO_disabled_mobile_plugins');
		if (!is_array($globalMobilePlugins)) {
			$globalMobilePlugins = array();
		}
		
		$plugins = $this->reorder_plugins(get_plugins());
		
		$activePlugins = $this->get_active_plugins();
		$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
		require_once($this->absPath . "/tpl/postMetaBox.php");
	}

	function change_plugin_filter_title($title) {
		global $post;
		$supportedPostTypes = get_option("PO_custom_post_type_support");
		$supportedPostTypes[] = 'plugin_filter';
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can( 'edit_post', $post_id ) || !current_user_can( 'activate_plugins' ) || !in_array(get_post_type($post_id), $supportedPostTypes) || !isset($_POST['poSubmitPostMetaBox'])) {
			return $title;
		}
		
		if (is_object($post) && get_post_type($post->ID) == 'plugin_filter') {
			if (isset($_POST['filterName']) && $_POST['filterName'] != '') {
				return $_POST['filterName'];
			} else if (!isset($_POST['permalinkFilter']) || $_POST['permalinkFilter'] == '') {
				$randomTitle = "";
				for($i=0; $i<10; $i++) {
					$randomTitle .= chr(mt_rand(109,122));
				}
				return $randomTitle;
			} else {
				return $_POST['permalinkFilter'];
			}
		} else {
			return $title;
		}
	}
	
	function save_post_meta_box($post_id) {
		$supportedPostTypes = get_option("PO_custom_post_type_support");
		$supportedPostTypes[] = 'plugin_filter';
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can( 'edit_post', $post_id ) || !current_user_can( 'activate_plugins' ) || !in_array(get_post_type($post_id), $supportedPostTypes) || !isset($_POST['poSubmitPostMetaBox'])) {
			return $post_id;
		}

		if (isset($_POST['affectChildren'])) {
			update_post_meta($post_id, '_PO_affect_children', 1);
		} else {
			update_post_meta($post_id, '_PO_affect_children', 0);
		}


		$globalPlugins = get_option('PO_disabled_plugins');
		if (!is_array($globalPlugins)) {
			$globalPlugins = array();
		}
		$disabledPlugins = array();
		$enabledPlugins = array();
			
		if (isset($_POST['pluginsList']) && is_array($_POST['pluginsList'])) {
			foreach ($_POST['pluginsList'] as $plugin) {
				if (!in_array($plugin, $globalPlugins)) {
					$disabledPlugins[] = $plugin;
				}
			}

			foreach ($globalPlugins as $plugin) {
				if (!in_array($plugin, $_POST['pluginsList'])) {
					$enabledPlugins[] = $plugin;
				}
			}
		} else {
			foreach ($globalPlugins as $plugin) {
				$enabledPlugins[] = $plugin;
			}
		}

		update_post_meta($post_id, '_PO_disabled_plugins', $disabledPlugins);
		update_post_meta($post_id, '_PO_enabled_plugins', $enabledPlugins);


		if (get_option('PO_disable_mobile_plugins') == 1) {
			$globalMobilePlugins = get_option('PO_disabled_mobile_plugins');
			if (!is_array($globalMobilePlugins)) {
				$globalMobilePlugins = array();
			}
			$disabledMobilePlugins = array();
			$enabledMobilePlugins = array();
				
			if (isset($_POST['mobilePluginsList']) && is_array($_POST['mobilePluginsList'])) {
				foreach ($_POST['mobilePluginsList'] as $plugin) {
					if (!in_array($plugin, $globalMobilePlugins)) {
						$disabledMobilePlugins[] = $plugin;
					}
				}

				foreach ($globalMobilePlugins as $plugin) {
					if (!in_array($plugin, $_POST['mobilePluginsList'])) {
						$enabledMobilePlugins[] = $plugin;
					}
				}
			} else {
				foreach ($globalMobilePlugins as $plugin) {
					$enabledMobilePlugins[] = $plugin;
				}
			}
			update_post_meta($post_id, '_PO_disabled_mobile_plugins', $disabledMobilePlugins);
			update_post_meta($post_id, '_PO_enabled_mobile_plugins', $enabledMobilePlugins);
		}


		if (get_post_type($post_id) != 'plugin_filter') {
			update_post_meta($post_id, '_PO_permalink', get_permalink($post_id));
		} else {
			update_post_meta($post_id, '_PO_permalink', $_POST['permalinkFilter']);
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
		$posts = get_posts(array('posts_per_page'=>-1, 'post_type'=>get_option("PO_custom_post_type_support")));
		foreach ($posts as $post) {
			if (get_permalink($post->ID) != get_post_meta($post->ID, '_PO_permalink', $single=true)) {
				if(update_post_meta($post->ID, '_PO_permalink', get_permalink($post->ID))) {
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
		$activePlugins = $this->get_active_plugins();
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
		$plugins = $this->get_active_plugins();
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
			if (file_exists(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
				if (@unlink(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
					$result = "The MU plugin component has been removed.";
				} else {
					$result = "There was an issue removing the MU plugin component!";
				}
			} else {
				$result = "There was an issue removing the MU plugin component!";
			}
		} else if ($_POST['selected_action'] == 'move') {
			if (!file_exists(WPMU_PLUGIN_DIR)) {
				@mkdir(WPMU_PLUGIN_DIR);
			}
			if (file_exists(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php")) {
				@unlink(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
				@copy(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php", WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
			}
			if (file_exists(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
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
			$result .= "The selective plugin loading setting was saved successfully.\n";
		} else {
			$result .= "There was a problem saving the selective plugin loading setting.\n";
		}

		if (preg_match("/^(1|0)$/", $_POST['PO_disable_mobile_plugins'])) {
			update_option("PO_disable_mobile_plugins", $_POST['PO_disable_mobile_plugins']);
			$result .= "The selective mobile plugin loading setting was saved successfully.\n";
		} else {
			$result .= "There was a problem saving the selective mobile plugin loading setting.\n";
		}

		if (preg_match("/^(1|0)$/", $_POST['PO_admin_disable_plugins'])) {
			update_option("PO_admin_disable_plugins", $_POST['PO_admin_disable_plugins']);
			$result .= "The admin areas setting was saved successfully.\n";
		} else {
			$result .= "There was a problem saving the admin areas setting.\n";
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

	function save_mobile_user_agents() {
		if ( !current_user_can( 'activate_plugins' ) || !wp_verify_nonce( $_POST['PO_nonce'], plugin_basename(__FILE__) )) {
			print "You dont have permissions to access this page.";
			die();
		}
		
		$userAgents = preg_replace("/\\r\\n/", "\n", $_POST['PO_mobile_user_agents']);
		$userAgents = explode("\n", $userAgents);
		if (!is_array($userAgents)) {
			$userAgents = array();
		}
		
		if (update_option('PO_mobile_user_agents', $userAgents)) {
			print "The user agents were saved.";
		}
		die();
	}
	
	function register_type() {
		$labels = array(
			'name' => _x('Plugin Filters', 'post type general name'),
			'singular_name' => _x('Plugin Filter', 'post type singular name'),
			'add_new' => _x('Add Plugin Filter', 'neo_theme'),
			'add_new_item' => __('Add New Plugin Filter'),
			'edit_item' => __('Edit Plugin Filter'),
			'new_item' => __('New Plugin Filter'),
			'view_item' => __('View Plugin Filter'),
			'search_items' => __('Search Plugin Filter'),
			'not_found' =>  __('No Plugin Filters found'),
			'not_found_in_trash' => __('No Plugin Filters found in Trash'), 
			'parent_item_colon' => 'Parent Plugin Filter:',
			'parent' => 'Parent Plugin Filter'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'menu_icon' => $this->urlPath . '/image/po-icon-16x16.png', 		
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('custom-fields'),
			'capability_type' => 'plugin_filter'
		); 
		register_post_type('plugin_filter',$args);
		
		$labels = array(
			'name' => _x('Plugin Groups', 'post type general name'),
			'singular_name' => _x('Plugin Group', 'post type singular name'),
			'add_new' => _x('Add Plugin Group', 'neo_theme'),
			'add_new_item' => __('Add New Plugin Group'),
			'edit_item' => __('Edit Plugin Group'),
			'new_item' => __('New Plugin Group'),
			'view_item' => __('View Plugin Group'),
			'search_items' => __('Search Plugin Group'),
			'not_found' =>  __('No PPlugin Groups found'),
			'not_found_in_trash' => __('No Plugin Groups found in Trash'), 
			'parent_item_colon' => 'Parent Plugin Group:',
			'parent' => 'Parent Plugin Group'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => false, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('custom-fields'),
			'capability_type' => 'plugin_group'
		); 
		register_post_type('plugin_group',$args);
	}
	
	function custom_updated_messages( $messages ) {
		global $post, $post_ID;
		$messages['plugin_filter'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Plugin Filter updated.'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Plugin Filter updated.'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Plugin Filter restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Plugin Filter published.'), esc_url( get_permalink($post_ID) ) ),
			7 => __('theme saved.'),
			8 => sprintf( __('Plugin Filter submitted.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Plugin Filter scheduled for: <strong>%1$s</strong>.'),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Plugin Filter draft updated.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

		$messages['plugin_group'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Plugin Group updated.'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Plugin Group updated.'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Plugin Group restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Plugin Group published.'), esc_url( get_permalink($post_ID) ) ),
			7 => __('theme saved.'),
			8 => sprintf( __('Plugin Group submitted.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Plugin Group scheduled for: <strong>%1$s</strong>.'),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Plugin Group draft updated.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}

	function deactivated_plugin($plugin, $networkWide = null) {
		global $wpdb;
		if ($networkWide != null) {
			$sites = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
			foreach ($sites as $site) {
				if (switch_to_blog($site->blog_id)) {
					$activePlugins = $this->get_active_plugins();
					$activePlugins = array_values(array_diff($activePlugins, array($plugin)));
					update_option('active_plugins', $activePlugins);
				}
			}
			restore_current_blog();
		}
	}

	function activated_plugin($plugin, $networkWide = null) {
		global $wpdb;
		if ($networkWide != null) {
			$sites = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
			foreach ($sites as $site) {
				if (switch_to_blog($site->blog_id)) {
					$activePlugins = $this->get_active_plugins();
					if (!in_array($plugin, $activePlugins)) {
						$activePlugins[] = $plugin;
						update_option('active_plugins', $activePlugins);
					}
				}
			}
			restore_current_blog();
		}
	}
}
?>
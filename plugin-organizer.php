<?php
/*
Plugin Name: Plugin Organizer
Plugin URI: http://wpmason.com/plugin-organizer/
Description: A plugin for specifying the load order of your plugins.
Version: 1.1
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

if (is_admin()) {
	$POAbsPath = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__));
	$POUrlPath = plugins_url("", __FILE__);
	require_once($POAbsPath . "/lib/PluginOrganizer.class.php");


	$PluginOrganizer = new PluginOrganizer();

	register_activation_hook(__FILE__,array($PluginOrganizer, 'activate'));

	add_action('admin_menu', array($PluginOrganizer, 'admin_menu'));
	if (!isset($_POST['PO_group']) && ($_GET['plugin_status'] == 'all' || $_GET['plugin_status'] == 'active' || !isset($_GET['plugin_status']))) {
		add_filter("plugin_action_links", array($PluginOrganizer, 'plugin_page'), 10, 2);
		add_action('all_plugins',  array($PluginOrganizer, 'reorder_plugins'));
	}
	add_action('wp_ajax_PO_plugin_organizer',  array($PluginOrganizer, 'save_order'));
	add_action('wp_ajax_PO_save_group',  array($PluginOrganizer, 'save_group'));
	add_action('wp_ajax_PO_save_global_plugins',  array($PluginOrganizer, 'save_global_plugins'));
	add_action('wp_ajax_PO_redo_permalinks',  array($PluginOrganizer, 'redo_permalinks'));
	add_action('wp_ajax_PO_post_type_support',  array($PluginOrganizer, 'add_custom_post_type_support'));

	if (get_option("PO_disable_plugins") == "1") {
		add_action('admin_menu', array($PluginOrganizer, 'disable_plugin_box'));
		add_action('save_post', array($PluginOrganizer, 'save_disable_plugin_box'));
		add_action('save_post', array($PluginOrganizer, 'save_enable_plugin_box'));
	}
	add_action('delete_post', array($PluginOrganizer, 'delete_plugin_lists'));

	add_action('pre_current_active_plugins', array($PluginOrganizer, 'recreate_plugin_order'));
}

?>
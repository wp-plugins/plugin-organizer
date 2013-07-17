<?php
/*
Plugin Name: Plugin Organizer
Plugin URI: http://wpmason.com
Description: A plugin for specifying the load order of your plugins.
Version: 3.1.1
Author: Jeff Sterup
Author URI: http://www.jsterup.com
License: GPL2
*/

require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/lib/PluginOrganizer.class.php");

$PluginOrganizer = new PluginOrganizer(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)), plugins_url("", __FILE__));


register_activation_hook(__FILE__,array($PluginOrganizer, 'activate'));

register_deactivation_hook(__FILE__, array($PluginOrganizer, 'deactivate'));

if (!is_network_admin()) {
	add_action('init',  array($PluginOrganizer, 'setup_nonce'));
	add_filter('views_plugins',  array($PluginOrganizer, 'add_group_views'));
	add_action('admin_menu', array($PluginOrganizer, 'admin_menu'));
	
	if (!array_key_exists('plugin_status', $_REQUEST) || $_REQUEST['plugin_status'] == 'all' || $_REQUEST['plugin_status'] == 'active') {
		add_filter("plugin_row_meta", array($PluginOrganizer, 'add_hidden_start_order'), 10, 2);
		add_action('all_plugins',  array($PluginOrganizer, 'reorder_plugins'));
	}
	
	add_action('wp_ajax_PO_plugin_organizer',  array($PluginOrganizer, 'save_order'));
	add_action('wp_ajax_PO_save_group',  array($PluginOrganizer, 'save_group'));
	add_action('wp_ajax_PO_delete_group',  array($PluginOrganizer, 'delete_group'));
	add_action('wp_ajax_PO_remove_plugins_from_group',  array($PluginOrganizer, 'remove_plugins_from_group'));
	add_action('wp_ajax_PO_add_to_group',  array($PluginOrganizer, 'add_to_group'));
	add_action('wp_ajax_PO_save_global_plugins',  array($PluginOrganizer, 'save_global_plugins'));
	add_action('wp_ajax_PO_redo_permalinks',  array($PluginOrganizer, 'redo_permalinks'));
	add_action('wp_ajax_PO_post_type_support',  array($PluginOrganizer, 'add_custom_post_type_support'));
	add_action('wp_ajax_PO_manage_mu_plugin',  array($PluginOrganizer, 'manage_mu_plugin'));
	add_action('wp_ajax_PO_submit_ignore_protocol',  array($PluginOrganizer, 'set_ignore_protocol'));
	add_action('wp_ajax_PO_submit_ignore_arguments',  array($PluginOrganizer, 'set_ignore_arguments'));
	add_action('wp_ajax_PO_submit_fuzzy_url_matching',  array($PluginOrganizer, 'set_fuzzy_url_matching'));
	add_action('wp_ajax_PO_submit_disable_plugin_settings',  array($PluginOrganizer, 'set_disable_plugin_settings'));
	add_action('wp_ajax_PO_submit_preserve_settings',  array($PluginOrganizer, 'set_preserve_settings'));
	add_action('wp_ajax_PO_reset_to_default_order',  array($PluginOrganizer, 'reset_plugin_order'));
	add_action('wp_ajax_PO_submit_mobile_user_agents',  array($PluginOrganizer, 'save_mobile_user_agents'));
	
	if (get_option("PO_disable_plugins") == "1") {
		add_action('admin_menu', array($PluginOrganizer, 'disable_plugin_box'));
		add_action('save_post', array($PluginOrganizer, 'save_post_meta_box'));
	}
	
	add_action('delete_post', array($PluginOrganizer, 'delete_plugin_lists'));
	add_action('pre_current_active_plugins', array($PluginOrganizer, 'recreate_plugin_order'));
	add_action('manage_plugins_columns', array($PluginOrganizer, 'get_column_headers'));
	add_filter('manage_plugins_custom_column', array($PluginOrganizer, 'set_custom_column_values'), 10, 3);
	add_filter('gettext', array($PluginOrganizer, 'change_page_title'), 10, 2);
	add_filter('title_save_pre', array($PluginOrganizer, 'change_plugin_filter_title'));
	add_action('init', array($PluginOrganizer, 'register_type'));
	add_filter('post_updated_messages', array($PluginOrganizer, 'custom_updated_messages'));

}

?>
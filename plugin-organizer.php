<?php
/*
Plugin Name: Plugin Organizer
Plugin URI: http://www.nebraskadigital.com/2010/12/27/plugin-organizer/
Description: A plugin for specifying the load order of your plugins.
Version: 0.5
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

$POAbsPath = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__));
$POUrlPath = plugins_url("", __FILE__);
require_once($POAbsPath . "/lib/PluginOrganizer.class.php");


$PluginOrganizer = new PluginOrganizer();

register_activation_hook(__FILE__,array($PluginOrganizer, 'PO_activate'));

add_action('admin_menu', array($PluginOrganizer, 'PO_admin_menu'));
if (!isset($_POST['PO_group']) && ($_GET['plugin_status'] == 'all' || !isset($_GET['plugin_status']))) {
	add_filter("plugin_action_links", array($PluginOrganizer, 'PO_plugin_page'), 10, 2);
}
add_action('wp_ajax_plugin_organizer',  array($PluginOrganizer, 'PO_save_order'));
add_action('wp_ajax_PO_save_group',  array($PluginOrganizer, 'PO_save_group'));
add_action('all_plugins',  array($PluginOrganizer, 'PO_reorder_plugins'));
if (get_option("PO_disable_plugins") == "1") {
	add_action('admin_menu', array($PluginOrganizer, 'PO_disable_plugin_box'));
	add_action('save_post', array($PluginOrganizer, 'PO_save_disable_plugin_box'));
	add_action('delete_post', array($PluginOrganizer, 'PO_delete_disabled_plugins'));
}
?>
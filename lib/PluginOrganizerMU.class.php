<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://www.nebraskadigital.com/2010/12/27/plugin-organizer/
Description: This is part of the Plugin Organizer plugin.  It enables the selective loading of plugins per post or page.
Version: 0.5
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

	
class PluginOrganizerMU {
	function PO_disable_plugins($pluginList) {
		global $wpdb;
		$newPluginList = array();
		if (get_option("PO_disable_plugins") == "1") {
			/*** check for https ***/
			$protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
			/*** return the full address ***/
			$url = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$preparedUrl = $wpdb->prepare($url);
			$disabledPlugins = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_disabled_plugins WHERE permalink = '".$preparedUrl."'", ARRAY_A);
			$disabledPlugins = unserialize($disabledPlugins['plugin_list']);
			if (is_array($disabledPlugins)) {
				foreach ($pluginList as $plugin) {
					if (!in_array($plugin, $disabledPlugins)) {
						$newPluginList[] = $plugin;
					}
				}
			} else {
				$newPluginList = $pluginList;
			}
		} else {
			$newPluginList = $pluginList;
		}
		return $newPluginList;
	}
}

$PluginOrganizerMU = new PluginOrganizerMU();
add_filter('option_active_plugins', array($PluginOrganizerMU, 'PO_disable_plugins'), 10, 1);

?>
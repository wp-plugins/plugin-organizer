<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://www.nebraskadigital.com/2010/12/27/plugin-organizer/
Description: This is part of the Plugin Organizer plugin.  It enables the selective loading of plugins per post or page.
Version: 0.8.2
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

	
class PluginOrganizerMU {
	function disable_plugins($pluginList) {
		global $wpdb;
		$newPluginList = array();
		if (get_option("PO_disable_plugins") == "1" && !is_admin()) {
			if (get_option("PO_version_num") != "0.8.2") {
				$newPluginList = $pluginList;
				update_option("PO_disable_plugins", "0");
			} else {
				$globalPlugins = get_option("PO_disabled_plugins");
				/*** check for https ***/
				$protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
				/*** return the full address ***/
				$url = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
				$preparedUrl = $wpdb->prepare($url);
				$postPlugins = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE permalink = '".$preparedUrl."'", ARRAY_A);
				$disabledPlugins = unserialize($postPlugins['disabled_plugins']);
				$enabledPlugins = unserialize($postPlugins['enabled_plugins']);
				if (!is_array($enabledPlugins)) {
					$enabledPlugins = array();
				}
				if (is_array($globalPlugins)) {
					foreach ($pluginList as $plugin) {
						if (in_array($plugin, $globalPlugins)) {
							if (in_array($plugin, $enabledPlugins)) {
								$newPluginList[] = $plugin;
							}
						} else {
							$newPluginList[] = $plugin;
						}
					}
				}
				if (sizeof($newPluginList) > 0) {
					$pluginList = $newPluginList;
					$newPluginList = array();
				}
				if (is_array($disabledPlugins)) {
					foreach ($pluginList as $plugin) {
						if (!in_array($plugin, $disabledPlugins)) {
							$newPluginList[] = $plugin;
						}
					}
				} else {
					$newPluginList = $pluginList;
				}
			}
		} else {
			$newPluginList = $pluginList;
		}
		return $newPluginList;
	}
}

$PluginOrganizerMU = new PluginOrganizerMU();
add_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);

?>
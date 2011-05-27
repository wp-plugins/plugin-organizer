<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://www.nebraskadigital.com/2010/12/27/plugin-organizer/
Description: This is part of the Plugin Organizer plugin.  It enables the selective loading of plugins per post or page.
Version: 0.9
Author: Jeff Sterup
Author URI: http://www.jsterup.com
*/

	
class PluginOrganizerMU {
	function disable_plugins($pluginList) {
		global $wpdb;
		$newPluginList = array();
		if (get_option("PO_disable_plugins") == "1" && !is_admin()) {
			if (get_option("PO_version_num") != "0.9") {
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
				$urlPlugins = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink = '".$preparedUrl."'", ARRAY_A);
				$disabledPostPlugins = unserialize($postPlugins['disabled_plugins']);
				$enabledPostPlugins = unserialize($postPlugins['enabled_plugins']);
				$disabledUrlPlugins = unserialize($urlPlugins['disabled_plugins']);
				$enabledUrlPlugins = unserialize($urlPlugins['enabled_plugins']);
				if (!is_array($disabledPostPlugins)) {
					$disabledPostPlugins = array();
				}
				if (!is_array($enabledPostPlugins)) {
					$enabledPostPlugins = array();
				}
				if (!is_array($disabledUrlPlugins)) {
					$disabledUrlPlugins = array();
				}
				if (!is_array($enabledUrlPlugins)) {
					$enabledUrlPlugins = array();
				}
				
				$disabledPlugins = array_merge($disabledPostPlugins, $disabledUrlPlugins);
				$enabledPlugins = array_merge($enabledPostPlugins, $enabledUrlPlugins);

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
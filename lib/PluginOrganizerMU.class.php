<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://wpmason.com
Description: A plugin for specifying the load order of your plugins.
Version: 2.0
Author: Jeff Sterup
Author URI: http://www.jsterup.com
License: GPL2
*/

	
class PluginOrganizerMU {
	function disable_plugins($pluginList) {
		global $wpdb, $pagenow;
		$newPluginList = array();
		if (get_option("PO_disable_plugins") == "1" && ((get_option('PO_admin_disable_plugins') != "1" && !is_admin()) || (get_option('PO_admin_disable_plugins') == "1" && $pagenow != "plugins.php"))) {
			if (get_option("PO_version_num") != "2.0" && !is_admin()) {
				$newPluginList = $pluginList;
				update_option("PO_disable_plugins", "0");
			} else {
				$globalPlugins = get_option("PO_disabled_plugins");
				/*** check for https ***/
				$protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
				/*** return the full address ***/
				$url = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
				$postPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE permalink = %s";
				$postPlugins = $wpdb->get_row($wpdb->prepare($postPluginQuery, $url), ARRAY_A);
				$urlPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink = %s";
				$urlPlugins = $wpdb->get_row($wpdb->prepare($urlPluginQuery, $url), ARRAY_A);
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
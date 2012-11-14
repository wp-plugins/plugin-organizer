<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://wpmason.com
Description: A plugin for specifying the load order of your plugins.
Version: 2.5
Author: Jeff Sterup
Author URI: http://www.jsterup.com
License: GPL2
*/

	
class PluginOrganizerMU {
	function disable_plugins($pluginList) {
		global $wpdb, $pagenow;
		$newPluginList = array();
		if (get_option("PO_disable_plugins") == "1" && ((get_option('PO_admin_disable_plugins') != "1" && !is_admin()) || (get_option('PO_admin_disable_plugins') == "1" && $pagenow != "plugins.php"))) {
			if (get_option("PO_version_num") != "2.5" && !is_admin()) {
				$newPluginList = $pluginList;
				update_option("PO_disable_plugins", "0");
				update_option("PO_admin_disable_plugins", "0");
			} else {
				$ignoreProtocol = get_option('PO_ignore_protocol');
				$ignoreArguments = get_option('PO_ignore_arguments');
				$globalPlugins = get_option("PO_disabled_plugins");
				
				if ($ignoreArguments == '1') {
					$splitPath = explode('?', $_SERVER['REQUEST_URI']);
					$requestedPath = $splitPath[0];
				} else {
					$requestedPath = $_SERVER['REQUEST_URI'];
				}
				
				if ($ignoreProtocol == '1') {
					$url = $_SERVER['HTTP_HOST'].$requestedPath;
					$postPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE permalink LIKE %s";
					$postPlugins = $wpdb->get_row($wpdb->prepare($postPluginQuery, '%'.$url), ARRAY_A);
					$urlPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink LIKE %s";
					$urlPlugins = $wpdb->get_row($wpdb->prepare($urlPluginQuery, '%'.$url), ARRAY_A);
				} else {
					$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
					$url = $protocol.'://'.$_SERVER['HTTP_HOST'].$requestedPath;
					$postPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_post_plugins WHERE permalink = %s";
					$postPlugins = $wpdb->get_row($wpdb->prepare($postPluginQuery, $url), ARRAY_A);
					$urlPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink = %s";
					$urlPlugins = $wpdb->get_row($wpdb->prepare($urlPluginQuery, $url), ARRAY_A);
				}
				
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
				
				if (sizeof($disabledPlugins) == 0 && get_option("PO_fuzzy_url_matching") == "1") {
					$endChar = '';
					if (preg_match('/\/$/', $url)) {
						$endChar = '/';
					}
					$choppedUrl = $url;
					//Dont allow an endless loop
					$loopCount = 0;
					if ($ignoreProtocol == '1') {
						$lastUrl = $_SERVER['HTTP_HOST'].$endChar;
					} else {
						$lastUrl = $protocol.'://'.$_SERVER['HTTP_HOST'].$endChar;
					}
					while ($loopCount < 15 && ($choppedUrl = preg_replace('/\/[^\/]+\/?$/', $endChar, $choppedUrl)) && $choppedUrl != $lastUrl) {
						$loopCount++;
						if ($ignoreProtocol == '1') {
							$urlPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink LIKE %s AND children=1";
							$urlPlugins = $wpdb->get_row($wpdb->prepare($urlPluginQuery, '%'.$choppedUrl), ARRAY_A);
						} else {
							$urlPluginQuery = "SELECT * FROM ".$wpdb->prefix."PO_url_plugins WHERE permalink = %s AND children=1";
							$urlPlugins = $wpdb->get_row($wpdb->prepare($urlPluginQuery, $choppedUrl), ARRAY_A);
						}
						
						if ($wpdb->num_rows > 0) {
							$disabledUrlPlugins = unserialize($urlPlugins['disabled_plugins']);
							$enabledUrlPlugins = unserialize($urlPlugins['enabled_plugins']);
							if (!is_array($disabledUrlPlugins)) {
								$disabledUrlPlugins = array();
							}
							if (!is_array($enabledUrlPlugins)) {
								$enabledUrlPlugins = array();
							}
							$disabledPlugins = array_merge($disabledPlugins, $disabledUrlPlugins);
							$enabledPlugins = array_merge($enabledPlugins, $enabledUrlPlugins);
							break;
						}
					}
				}

				if (is_array($globalPlugins)) {
					foreach ($pluginList as $plugin) {
						if (in_array($plugin, $globalPlugins) && (!preg_match('/plugin-organizer.php$/', $plugin) || !is_admin())) {
							if (in_array($plugin, $enabledPlugins)) {
								$newPluginList[] = $plugin;
							}
						} else {
							$newPluginList[] = $plugin;
						}
					}
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

	function disable_network_plugins($pluginList) {
		if (sizeOf($pluginList) > 0) {
			$tempPluginList = array_keys($pluginList);
			$tempPluginList = $this->disable_plugins($tempPluginList);
			$newPluginList = array();
			foreach($tempPluginList as $pluginFile) {
				$newPluginList[$pluginFile] = $pluginList[$pluginFile];
			}
			return $newPluginList;
		}
	}
}

$PluginOrganizerMU = new PluginOrganizerMU();
add_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);
add_filter('site_option_active_sitewide_plugins', array($PluginOrganizerMU, 'disable_network_plugins'), 10, 1);

?>
<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://wpmason.com
Description: A plugin for specifying the load order of your plugins.
Version: 5.6.3
Author: Jeff Sterup
Author URI: http://www.jsterup.com
License: GPL2
*/

class PluginOrganizerMU {
	var $ignoreProtocol, $ignoreArguments, $requestedPermalink, $postTypeSupport;
	var $protocol, $mobile, $detectMobile, $requestedPermalinkHash, $permalinkSearchField;
	function __construct() {
		$this->ignoreProtocol = get_option('PO_ignore_protocol');
		$this->ignoreArguments = get_option('PO_ignore_arguments');
		$this->set_requested_permalink();
		$this->postTypeSupport = get_option('PO_custom_post_type_support');
		$this->postTypeSupport[] = 'plugin_filter';
		$this->detectMobile = get_option('PO_disable_mobile_plugins');
		if ($this->detectMobile == 1) {
			$this->detect_mobile();
		}
	}
	
	function disable_plugins($pluginList, $networkPlugin=0) {
		global $wpdb, $pagenow;
		$newPluginList = array();
		if (get_option("PO_disable_plugins") == "1" && ((get_option('PO_admin_disable_plugins') != "1" && !is_admin()) || (get_option('PO_admin_disable_plugins') == "1" && !in_array($pagenow, array("plugins.php", "update-core.php", "update.php"))))) {
				
			if (isset($GLOBALS["PO_CACHED_PLUGIN_LIST"]) && is_array($GLOBALS["PO_CACHED_PLUGIN_LIST"]) && $networkPlugin == 0) {
				$newPluginList = $GLOBALS["PO_CACHED_PLUGIN_LIST"];
			} else {
				if (get_option("PO_version_num") != "5.6.3" && !is_admin()) {
					$newPluginList = $pluginList;
					update_option("PO_disable_plugins", "0");
					update_option("PO_admin_disable_plugins", "0");
				} else {
					if ($this->detectMobile == 1 && $this->mobile) {
						$globalPlugins = get_option("PO_disabled_mobile_plugins");
						$globalGroups = get_option("PO_disabled_mobile_groups");
					} else {
						$globalPlugins = get_option("PO_disabled_plugins");
						$globalGroups = get_option("PO_disabled_groups");
					}

					if ($this->ignoreProtocol == '1') {
						$requestedPostQuery = "SELECT * FROM ".$wpdb->prefix."PO_plugins WHERE ".$this->permalinkSearchField." = %s AND status = 'publish' AND secure = %d";
						$requestedPost = $wpdb->get_results($wpdb->prepare($requestedPostQuery, $this->requestedPermalinkHash, $this->secure), ARRAY_A);
					} else {
						$requestedPostQuery = "SELECT * FROM ".$wpdb->prefix."PO_plugins WHERE ".$this->permalinkSearchField." = %s AND status = 'publish'";
						$requestedPost = $wpdb->get_results($wpdb->prepare($requestedPostQuery, $this->requestedPermalinkHash), ARRAY_A);
					}
					if (!is_array($requestedPost)) {
						$requestedPost = array();
					} else if (sizeOf($requestedPost) > 1) {
						usort($requestedPost, array($this, 'sort_posts'));
					}
					
					$disabledPlugins = array();
					$enabledPlugins = array();
					$disabledGroups = array();
					$enabledGroups = array();
					foreach($requestedPost as $currPost) {
						if ($this->detectMobile == 1 && $this->mobile) {
							$disabledPlugins = @unserialize($currPost['disabled_mobile_plugins']);
							$enabledPlugins = @unserialize($currPost['enabled_mobile_plugins']);
							$disabledGroups = @unserialize($currPost['disabled_mobile_groups']);
							$enabledGroups = @unserialize($currPost['enabled_mobile_groups']);
						} else {
							$disabledPlugins = @unserialize($currPost['disabled_plugins']);
							$enabledPlugins = @unserialize($currPost['enabled_plugins']);
							$disabledGroups = @unserialize($currPost['disabled_groups']);
							$enabledGroups = @unserialize($currPost['enabled_groups']);
						}
						if ((is_array($disabledPlugins) && sizeof($disabledPlugins) > 0) || (is_array($enabledPlugins) && sizeof($enabledPlugins) > 0) || (is_array($disabledGroups) && sizeof($disabledGroups) > 0) || (is_array($enabledGroups) && sizeof($enabledGroups) > 0)) {
							break;
						}
					}
					
					if (!is_array($disabledPlugins)) {
						$disabledPlugins = array();
					}

					if (!is_array($enabledPlugins)) {
						$enabledPlugins = array();
					}

					if (!is_array($disabledGroups)) {
						$disabledGroups = array();
					}

					if (!is_array($enabledGroups)) {
						$enabledGroups = array();
					}

					if (get_option("PO_fuzzy_url_matching") == "1" && sizeof($disabledPlugins) == 0 && sizeof($enabledPlugins) == 0 && sizeof($disabledGroups) == 0 && sizeof($enabledGroups) == 0) {
						$endChar = (preg_match('/\/$/', get_option('permalink_structure')) || is_admin())? '/':'';
						$lastUrl = $_SERVER['HTTP_HOST'].$endChar;
						
						//Dont allow an endless loop
						$loopCount = 0;
						$matchFound = 0;
			
		
						while ($loopCount < 25 && $matchFound == 0 && $this->requestedPermalink != $lastUrl && ($this->requestedPermalink = preg_replace('/\/[^\/]+\/?$/', $endChar, $this->requestedPermalink))) {
							$loopCount++;
							$this->requestedPermalinkHash = md5($this->requestedPermalink);
							if ($this->ignoreProtocol == '1') {
						
								$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."PO_plugins WHERE ".$this->permalinkSearchField." = %s AND status = 'publish' AND secure = %d AND children = 1";
								$fuzzyPost = $wpdb->get_results($wpdb->prepare($fuzzyPostQuery, $this->requestedPermalinkHash, $this->secure), ARRAY_A);
								$matchFound = (sizeof($fuzzyPost) > 0)? 1:$matchFound;
								
							} else {
								$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."PO_plugins WHERE ".$this->permalinkSearchField." = %s AND status = 'publish' AND children = 1";
								$fuzzyPost = $wpdb->get_results($wpdb->prepare($fuzzyPostQuery, $this->requestedPermalinkHash), ARRAY_A);
								
								$matchFound = (sizeof($fuzzyPost) > 0)? 1:$matchFound;
							}

							
							if ($matchFound > 0) {
								$matchFound = 0;
								if (!is_array($fuzzyPost)) {
									$fuzzyPost = array();
								} else if (sizeOf($fuzzyPost) > 0) {
									usort($fuzzyPost, array($this, 'sort_posts'));
								}

								foreach($fuzzyPost as $currPost) {
									if ($this->detectMobile == 1 && $this->mobile) {
										$disabledFuzzyPlugins = @unserialize($currPost['disabled_mobile_plugins']);
										$enabledFuzzyPlugins = @unserialize($currPost['enabled_mobile_plugins']);
										$disabledFuzzyGroups = @unserialize($currPost['disabled_mobile_groups']);
										$enabledFuzzyGroups = @unserialize($currPost['enabled_mobile_groups']);
									} else {
										$disabledFuzzyPlugins = @unserialize($currPost['disabled_plugins']);
										$enabledFuzzyPlugins = @unserialize($currPost['enabled_plugins']);
										$disabledFuzzyGroups = @unserialize($currPost['disabled_groups']);
										$enabledFuzzyGroups = @unserialize($currPost['enabled_groups']);
									}
									if ((is_array($disabledFuzzyPlugins) && sizeof($disabledFuzzyPlugins) > 0) || (is_array($enabledFuzzyPlugins) && sizeof($enabledFuzzyPlugins) > 0) || (is_array($disabledFuzzyGroups) && sizeof($disabledFuzzyGroups) > 0) || (is_array($enabledFuzzyGroups) && sizeof($enabledFuzzyGroups) > 0)) {
										$matchFound = 1;
										break;
									}
								}
								
								if ($matchFound > 0) {
									if (!is_array($disabledFuzzyPlugins)) {
										$disabledFuzzyPlugins = array();
									}

									if (!is_array($enabledFuzzyPlugins)) {
										$enabledFuzzyPlugins = array();
									}

									if (!is_array($disabledFuzzyGroups)) {
										$disabledFuzzyGroups = array();
									}

									if (!is_array($enabledFuzzyGroups)) {
										$enabledFuzzyGroups = array();
									}

									$disabledPlugins = $disabledFuzzyPlugins;
									$enabledPlugins = $enabledFuzzyPlugins;
									$disabledGroups = $disabledFuzzyGroups;
									$enabledGroups = $enabledFuzzyGroups;
								}
							}
						}
					}

					$disabledGroupMembers = array();
					$enabledGroupMembers = array();
					if (is_array($disabledGroups)) {
						foreach($disabledGroups as $group) {
							$groupMembers = get_post_meta($group, '_PO_group_members', $single=true);
							if (!is_array($groupMembers)) {
								$groupMembers = array();
							}
							$disabledGroupMembers = array_merge($disabledGroupMembers, $groupMembers);
						}
					}

					if (is_array($enabledGroups)) {
						foreach($enabledGroups as $group) {
							$groupMembers = get_post_meta($group, '_PO_group_members', $single=true);
							if (!is_array($groupMembers)) {
								$groupMembers = array();
							}
							$enabledGroupMembers = array_merge($enabledGroupMembers, $groupMembers);
						}
					}
					$disabledGroupMembers = array_unique($disabledGroupMembers);
					$enabledGroupMembers = array_unique($enabledGroupMembers);
					

					foreach($disabledGroupMembers as $groupMember) {
						if (!in_array($groupMember, $disabledPlugins)) {
							$disabledPlugins[] = $groupMember;
						}
					}
					
					foreach($enabledGroupMembers as $groupMember) {
						if (!in_array($groupMember, $enabledPlugins)) {
							$enabledPlugins[] = $groupMember;
						}
					}


					if (is_array($globalPlugins) && sizeOf($globalPlugins) > 0) {
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

					if (is_array($globalGroups) && sizeOf($globalGroups) > 0) {
						foreach($globalGroups as $group) {
							$groupMembers = get_post_meta($group, '_PO_group_members', $single=true);
							if (!is_array($groupMembers)) {
								$groupMembers = array();
							}
							
							foreach ($pluginList as $plugin) {
								if (in_array($plugin, $groupMembers) && (!preg_match('/plugin-organizer.php$/', $plugin) || !is_admin())) {
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
				if ($networkPlugin == 0) {
					$GLOBALS["PO_CACHED_PLUGIN_LIST"] = $newPluginList;
				}
			}
		} else {
			$newPluginList = $pluginList;
		}
		return $newPluginList;
	}
	
	function sort_posts($a, $b) {
			if ($a['post_type'] == 'plugin_filter' && $b['post_type'] != 'plugin_filter') {
				return 1;
			} else if($a['post_type'] != 'plugin_filter' && $b['post_type'] == 'plugin_filter') {
				return -1;
			} else {
				return 0;
			}
	}
	
	function disable_network_plugins($pluginList) {
		if (isset($GLOBALS["PO_CACHED_NET_PLUGINS"]) && is_array($GLOBALS["PO_CACHED_NET_PLUGINS"])) {
			$newPluginList = $GLOBALS["PO_CACHED_NET_PLUGINS"];
		} else {
			$newPluginList = array();
			if (is_array($pluginList) && sizeOf($pluginList) > 0) {
				$tempPluginList = array_keys($pluginList);
				$tempPluginList = $this->disable_plugins($tempPluginList, 1);
				foreach($tempPluginList as $pluginFile) {
					$newPluginList[$pluginFile] = $pluginList[$pluginFile];
				}
			}
			$GLOBALS["PO_CACHED_NET_PLUGINS"] = $newPluginList;
		}
		
		return $newPluginList;
	}

	function set_requested_permalink() {
		if ($this->ignoreArguments == '1') {
			$splitPath = explode('?', $_SERVER['REQUEST_URI']);
			$requestedPath = $splitPath[0];
			$this->permalinkSearchField = 'permalink_hash';
		} else {
			$requestedPath = $_SERVER['REQUEST_URI'];
			$this->permalinkSearchField = 'permalink_hash_args';
		}
		
		$this->requestedPermalink = $_SERVER['HTTP_HOST'].$requestedPath;
		$this->requestedPermalinkHash = md5($this->requestedPermalink);

		if ($this->ignoreProtocol == '0') {
			$this->secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 1 : 0;
		} else {
			$this->secure = 0;
		}


	}

	function detect_mobile() {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$mobileAgents = get_option('PO_mobile_user_agents');
		if (!is_array($mobileAgents)) {
			$mobileAgents = array();
		}
		$this->mobile = false;

		foreach ( $mobileAgents as $agent ) {
			if ( $agent != "" && stripos($userAgent, $agent) !== FALSE ) {
				$this->mobile = true;
				break;
			}
		}
	}
}
$PluginOrganizerMU = new PluginOrganizerMU();

add_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);

add_filter('site_option_active_sitewide_plugins', array($PluginOrganizerMU, 'disable_network_plugins'), 10, 1);

?>
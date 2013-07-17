<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://wpmason.com
Description: A plugin for specifying the load order of your plugins.
Version: 3.1.1
Author: Jeff Sterup
Author URI: http://www.jsterup.com
License: GPL2
*/

class PluginOrganizerMU {
	var $ignoreProtocol, $ignoreArguments, $requestedPermalink, $postTypeSupport;
	var $protocol, $mobile, $detectMobile;
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
		if (isset($GLOBALS["PO_CACHED_PLUGIN_LIST"]) && is_array($GLOBALS["PO_CACHED_PLUGIN_LIST"]) && $networkPlugin == 0) {
			$newPluginList = $GLOBALS["PO_CACHED_PLUGIN_LIST"];
		} else {
			$newPluginList = array();
			if (get_option("PO_disable_plugins") == "1" && ((get_option('PO_admin_disable_plugins') != "1" && !is_admin()) || (get_option('PO_admin_disable_plugins') == "1" && !in_array($pagenow, array("plugins.php", "update-core.php", "update.php"))))) {
				if (get_option("PO_version_num") != "3.1.1" && !is_admin()) {
					$newPluginList = $pluginList;
					update_option("PO_disable_plugins", "0");
					update_option("PO_admin_disable_plugins", "0");
				} else {
					if ($this->detectMobile == 1 && $this->mobile) {
						$globalPlugins = get_option("PO_disabled_mobile_plugins");
					} else {
						$globalPlugins = get_option("PO_disabled_plugins");
					}

					if ($this->ignoreProtocol == '1') {
						add_filter('posts_where', array($this, 'fix_where_clause'), 10, 2);
						$requestedPost = get_posts(
											array(
												'suppress_filters'=>false,
												'post_type'=>$this->postTypeSupport,
												'meta_query' => array(
													'relation' => 'AND',
													array(
														'key' => '_PO_permalink', 
														'value' => '%' . $this->requestedPermalink,
														'compare' => 'LIKE'
													)
												)
											));
						remove_filter('posts_where', array($this, 'fix_where_clause'), 10, 2);
					} else {
						$requestedPost = get_posts(array('post_type'=>$this->postTypeSupport, 'meta_key'=>'_PO_permalink', 'meta_value'=>$this->requestedPermalink));
					}
					usort($requestedPost, array($this, 'sort_posts'));
					
					$disabledPlugins = array();
					$enabledPlugins = array();
					if (isset($requestedPost[0]->ID)) {
						if ($this->detectMobile == 1 && $this->mobile) {
							$disabledPlugins = get_post_meta($requestedPost[0]->ID, '_PO_disabled_mobile_plugins', $single=true);
							$enabledPlugins = get_post_meta($requestedPost[0]->ID, '_PO_enabled_mobile_plugins', $single=true);
						} else {
							$disabledPlugins = get_post_meta($requestedPost[0]->ID, '_PO_disabled_plugins', $single=true);
							$enabledPlugins = get_post_meta($requestedPost[0]->ID, '_PO_enabled_plugins', $single=true);
						}
					}
					
					if (!is_array($disabledPlugins)) {
						$disabledPlugins = array();
					}

					if (!is_array($enabledPlugins)) {
						$enabledPlugins = array();
					}

					if (sizeof($disabledPlugins) == 0 && sizeof($enabledPlugins) == 0 && get_option("PO_fuzzy_url_matching") == "1") {
						$endChar = '';
						if (preg_match('/\/$/', $this->requestedPermalink)) {
							$endChar = '/';
						}

						$choppedUrl = $this->requestedPermalink;

						
						if ($this->ignoreProtocol == '1') {
							$lastUrl = $_SERVER['HTTP_HOST'].$endChar;
						} else {
							$lastUrl = $this->protocol.'://'.$_SERVER['HTTP_HOST'].$endChar;
						}

						//Dont allow an endless loop
						$loopCount = 0;
						$matchFound = 0;
						
						while ($loopCount < 15 && $matchFound == 0 && $this->requestedPermalink != $lastUrl && ($this->requestedPermalink = preg_replace('/\/[^\/]+\/?$/', $endChar, $this->requestedPermalink))) {
							$loopCount++;
							if ($this->ignoreProtocol == '1') {
						
								add_filter('posts_where', array($this, 'fix_where_clause'), 10, 2);
								$fuzzyPost = get_posts(
											array(
												'suppress_filters'=>false,
												'post_type'=>$this->postTypeSupport,
												'meta_query' => array(
													'relation' => 'AND',
													array(
														'key' => '_PO_permalink', 
														'value' => '%' . $this->requestedPermalink,
														'compare' => 'LIKE'
													),
													array(
														'key' => '_PO_affect_children', 
														'value' => '1',
														'compare' => '='
													)
												)
											));

								$matchFound = (sizeof($fuzzyPost) > 0)? 1:$matchFound;
								remove_filter('posts_where', array($this, 'fix_where_clause'), 10, 2);
							} else {
								$fuzzyPost = get_posts(
											array(
												'post_type'=>$this->postTypeSupport,
												'meta_query' => array(
													'relation' => 'AND',
													array(
														'key' => '_PO_permalink', 
														'value' => $this->requestedPermalink,
														'compare' => '='
													),
													array(
														'key' => '_PO_affect_children', 
														'value' => '1',
														'compare' => '='
													)
												)
											));
								
								$matchFound = (sizeof($fuzzyPost) > 0)? 1:$matchFound;
							}

							
							if ($matchFound > 0) {
								usort($fuzzyPost, array($this, 'sort_posts'));
								
								if (isset($fuzzyPost[0]->ID)) {
									if ($this->detectMobile == 1 && $this->mobile) {
										$disabledFuzzyPlugins = get_post_meta($fuzzyPost[0]->ID, '_PO_disabled_mobile_plugins', $single=true);
										$enabledFuzzyPlugins = get_post_meta($fuzzyPost[0]->ID, '_PO_enabled_mobile_plugins', $single=true);
									} else {
										$disabledFuzzyPlugins = get_post_meta($fuzzyPost[0]->ID, '_PO_disabled_plugins', $single=true);
										$enabledFuzzyPlugins = get_post_meta($fuzzyPost[0]->ID, '_PO_enabled_plugins', $single=true);
									}
								}
								
								if (!is_array($disabledFuzzyPlugins)) {
									$disabledFuzzyPlugins = array();
								}

								if (!is_array($enabledFuzzyPlugins)) {
									$enabledFuzzyPlugins = array();
								}

								$disabledPlugins = $disabledFuzzyPlugins;
								$enabledPlugins = $enabledFuzzyPlugins;
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
			if ($networkPlugin == 0) {
				$GLOBALS["PO_CACHED_PLUGIN_LIST"] = $newPluginList;
			}
		}
		return $newPluginList;
	}
	
	function fix_where_clause($where) {
		$where = preg_replace('/%\\\\\\\\%'.preg_replace('/\//', '\/', $this->requestedPermalink).'%/', '%'.$this->requestedPermalink, $where);
		return $where;
	}
	
	function sort_posts($a, $b) {
			if ($a->post_type == 'plugin_filter' && $b->post_type != 'plugin_filter') {
				return 1;
			} else if($a->post_type != 'plugin_filter' && $b->post_type == 'plugin_filter') {
				return -1;
			} else {
				return 0;
			}
	}
	
	function disable_network_plugins($pluginList) {
		if (isset($GLOBALS["PO_CACHED_NET_PLUGINS"]) && is_array($GLOBALS["PO_CACHED_NET_PLUGINS"])) {
			$newPluginList = $GLOBALS["PO_CACHED_NET_PLUGINS"];
		} else {
			if (!isset($GLOBALS['wp_taxonomies'])) {
				$GLOBALS['wp_taxonomies'] = array();
			}
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
		} else {
			$requestedPath = $_SERVER['REQUEST_URI'];
		}
		
		if ($this->ignoreProtocol == '1') {
			$this->requestedPermalink = $_SERVER['HTTP_HOST'].$requestedPath;
		} else {
			$this->protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
			$this->requestedPermalink = $this->protocol.'://'.$_SERVER['HTTP_HOST'].$requestedPath;
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
			if ( $agent != "" && preg_match( "#".preg_replace('/#/', '?', preg_quote($agent))."#i", $userAgent ) ) {
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
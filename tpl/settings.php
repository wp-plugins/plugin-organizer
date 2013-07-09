<div id="wrap">
    <div class="icon32" id="icon-po-settings"> <br /> </div>

    <h2>Settings</h2>
    <p>Genral Settings</p>
	<?php
	if ($errMsg != "") {
		?>
		<p style="color: #CC0066;"><?php print $errMsg; ?></p>
		<?php
	}
	?>
	<div id="poststuff" class="metabox-holder">
      <div id="post-body">
        <div id="post-body-content">
	        <div id="PO-preserve-settings-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_preserve_settings">Preserve plugin settings when Plugin Organizer is deactivated?</label></h3>
			  <div class="inside">
				<?php $preserveSettings = get_option("PO_preserve_settings"); ?>
				<input type="radio" name="PO_preserve_settings" class="PO-preserve-settings-radio" value="1" <?php print ($preserveSettings === "1" || $preserveSettings == '')? "checked='checked'":""; ?> />Yes<br />
				<input type="radio" name="PO_preserve_settings" class="PO-preserve-settings-radio" value="0" <?php print ($preserveSettings === "0")? "checked='checked'":""; ?> />No<br />
				<input type="button" name="manage-mu-plugin" value="Submit" onmousedown="PO_submit_preserve_settings();">
			  </div>
		    </div>
		  <br /><br />  
		    <div id="PO-disable-settings-div" class="stuffbox" style="width: 98%">
              <h3><label for="PO_disable_plugins">Selective Plugin Loading</label></h3>
			  <div class="inside">
            	<strong>Selective Plugin Loading:</strong><br />
				<?php $selectiveLoad = get_option("PO_disable_plugins"); ?>
				<input type="radio" name="PO_disable_plugins" class="PO-disable-plugins" value="1" <?php print ($selectiveLoad == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_disable_plugins" class="PO-disable-plugins" value="0" <?php print ($selectiveLoad != "1")? "checked='checked'":""; ?>> Disable
				<br />
				NOTE:  When this option is enabled you must move the PluginOrganizerMU.class.php file from /wp-content/plugins/plugin_organizer/lib to <?php print WPMU_PLUGIN_DIR; ?> before it will take affect.  If you don't have an mu-plugins folder you need to create it.
				<br /><br /><br />
				<strong>Selective Mobile Plugin Loading:</strong><br />
				<?php $selectiveMobileLoad = get_option("PO_disable_mobile_plugins"); ?>
				<input type="radio" name="PO_disable_mobile_plugins" class="PO-disable-mobile-plugins" value="1" <?php print ($selectiveMobileLoad == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_disable_mobile_plugins" class="PO-disable-mobile-plugins" value="0" <?php print ($selectiveMobileLoad != "1")? "checked='checked'":""; ?>> Disable
				<br />
				NOTE:  When this option is enabled plugins will be disabled differently for mobile browsers. The first option must be enabled before this one will take affect.
				<br /><br /><br />
				<strong>Admin Areas:</strong><br />
				<?php $selectiveAdminLoad = get_option("PO_admin_disable_plugins"); ?>
				<input type="radio" name="PO_admin_disable_plugins" class="PO-admin-disable-plugins" value="1" <?php print ($selectiveAdminLoad == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_admin_disable_plugins" class="PO-admin-disable-plugins" value="0" <?php print ($selectiveAdminLoad != "1")? "checked='checked'":""; ?>> Disable
				<br />
				NOTE:  When this option is enabled plugins will be disabled on the admin pages. The first option must be enabled before this one will take affect.
				<br />
				<input type="button" name="submit-disable-settings" value="Save Settings" onmousedown="PO_submit_disable_settings();" />
              </div>
            </div>
		  <br /><br />
	        <div id="PO-fuzzy-url-matching-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_fuzzy_url_matching">Fuzzy URL matching</label></h3>
			  <div class="inside">
				<?php $fuzzyUrlMatching = get_option("PO_fuzzy_url_matching"); ?>
				<input type="radio" name="PO_fuzzy_url_matching" class="PO-fuzzy-url-radio" value="1" <?php print ($fuzzyUrlMatching == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_fuzzy_url_matching" class="PO-fuzzy-url-radio" value="0" <?php print ($fuzzyUrlMatching != "1")? "checked='checked'":""; ?>> Disable
				<br />
				This gives URLs entered into the URL admin the ability to affect children of that URL.
				<br />
				<input type="button" name="submit-fuzzy-url" value="Save Settings" onmousedown="PO_submit_fuzzy_url_matching();" />
			  </div>
		    </div>
		  <br /><br />
		    <div id="PO-ignore-protocol-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_ignore_protocol">Ignore URL Protocol</label></h3>
			  <div class="inside">
				<?php $ignoreProtocol = get_option("PO_ignore_protocol"); ?>
				<input type="radio" name="PO_ignore_protocol" class="PO-ignore-protocol-radio" value="1" <?php print ($ignoreProtocol == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_ignore_protocol" class="PO-ignore-protocol-radio" value="0" <?php print ($ignoreProtocol != "1")? "checked='checked'":""; ?>> Disable
				<br />
				This allows you to ignore the protocol (http, https) of a URL when trying to match it in the database at page load time.  With this enabled https://yoururl.com/page/ will have the same plugins loaded as http://yoururl.com/page/.  If disabled they can be set seperately using the URL admin.
				<br />
				<input type="button" name="submit-ignore-protocol" value="Save Settings" onmousedown="PO_submit_ignore_protocol();" />
			  </div>
		    </div>
		  <br /><br />
		    <div id="PO-ignore-arguments-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_ignore_arguments">Ignore URL Arguments</label></h3>
			  <div class="inside">
				<?php $ignoreArguments = get_option("PO_ignore_arguments"); ?>
				<input type="radio" name="PO_ignore_arguments" class="PO-ignore-arguments-radio" value="1" <?php print ($ignoreArguments == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_ignore_arguments" class="PO-ignore-arguments-radio" value="0" <?php print ($ignoreArguments != "1")? "checked='checked'":""; ?>> Disable
				<br />
				This allows you to ignore the arguments of a URL when trying to match it in the database at page load time.  With this enabled http://yoururl.com/page/?foo=2&bar=3 will have the same plugins loaded as http://yoururl.com/page/.  If disabled you can enter URLs with arguments included to load different plugins depending on what arguments are used in the URL admin.
				<br />
				<input type="button" name="submit-ignore-arguments" value="Save Settings" onmousedown="PO_submit_ignore_arguments();" />
			  </div>
		    </div>
		  <br /><br />
		    <div id="redo-permalinks-div" class="stuffbox" style="width: 98%">
			  <h3><label for="redo-permalinks">Recreate Permalinks</label></h3>
			  <div class="inside">
				<input type="button" name="redo-permalinks" value="Recreate Permalinks" onmousedown="PO_submit_redo_permalinks();" />
			  </div>
		    </div>
		  <br /><br />
		    <div id="PO-custom-post-type-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_cutom_post_type">Custom Post Type Support</label></h3>
			  <div class="inside">
				<?php
				$supportedPostTypes = get_option("PO_custom_post_type_support");
				$customPostTypes = get_post_types();
				if (is_array($supportedPostTypes)) {
					foreach ($customPostTypes as $postType) {
						if (!in_array($postType, array("attachment", "revision", "nav_menu_item", "plugin_group", "plugin_filter"))) {
							print '<input type="checkbox" class="PO_cutom_post_type" name="PO_cutom_post_type[]" value="'.$postType.'" '.((in_array($postType, $supportedPostTypes))? 'checked="checked"' : '').' />'.$postType.'<br />';
						}
					}
				}
				?>
				<input type="button" name="add-post-support" value="Save Post Types" onmousedown="PO_submit_post_type_support();">
			  </div>
		    </div>
		  <br /><br />
		    <div id="PO-browser-string-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_mobile_user_agents">Mobile User Agents</label></h3>
			  <div class="inside">
				<textarea name="PO_mobile_user_agents" id="PO_mobile_user_agents" rows="7" cols="50" style="width: 100%;"><?php
					$userAgents = get_option("PO_mobile_user_agents");
					if (is_array($userAgents)) {
						foreach ($userAgents as $agent) {
							print $agent . "\n";
						}
					}
				?></textarea>
				<br />
				<input type="button" name="save-user-agents" value="Save User Agents" onmousedown="PO_submit_mobile_user_agents();">
			  </div>
		    </div>
		  <br /><br />
		    <div id="PO-manage-mu-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_manage_mu">Manage MU plugin file</label></h3>
			  <div class="inside">
				<input type="radio" name="PO_manage_mu" class="PO-manage-mu-radio" value="delete" />Delete<br />
				<input type="radio" name="PO_manage_mu" class="PO-manage-mu-radio" value="move" />Move<br />
				<input type=button name="manage-mu-plugin" value="Submit" onmousedown="PO_manage_mu_plugin_file();">
			  </div>
		    </div>
	    </div>
      </div>
    </div>
</div>


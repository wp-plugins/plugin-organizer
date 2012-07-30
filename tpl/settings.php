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
	      <form method=post name="po_general_settings" action="">
	        <?php echo '<input type="hidden" name="PO_nonce" id="PO_nonce" value="' . $this->nonce . '" />'; ?>			
			<div id="general-settings-div" class="stuffbox" style="width: 98%">
              <h3><label for="PO_disable_plugins">Selective Plugin Loading</label></h3>
			  <div class="inside">
            	<strong>Selective Plugin Loading:</strong><br />
				<?php $selectiveLoad = get_option("PO_disable_plugins"); ?>
				<input type="radio" name="PO_disable_plugins" value="1" <?php print ($selectiveLoad == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_disable_plugins" value="0" <?php print ($selectiveLoad != "1")? "checked='checked'":""; ?>> Disable
				<br />
				NOTE:  When this option is enabled you must move the PluginOrganizerMU.class.php file from /wp-content/plugins/plugin_organizer/lib to /wp-content/mu-plugins before it will take effect.  If you don't have an mu-plugins folder you need to create it.
				<br /><br /><br />
				<strong>Admin Areas:</strong><br />
				<?php $selectiveAdminLoad = get_option("PO_admin_disable_plugins"); ?>
				<input type="radio" name="PO_admin_disable_plugins" value="1" <?php print ($selectiveAdminLoad == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_admin_disable_plugins" value="0" <?php print ($selectiveAdminLoad != "1")? "checked='checked'":""; ?>> Disable
				<br />
				NOTE:  When this option is enabled plugins will be disabled on the admin pages. The first option must be enabled before this one will take affect.
				<br />
				<input type=submit name=submit value="Save Settings" />
              </div>
            </div>
			<input type=hidden name="page" value="Plugin_Organizer" />
			<?php echo '<input type="hidden" name="PO_nonce" id="PO_nonce" value="' . $this->nonce . '" />'; ?>	
		  </form>
		  <br /><br />
		  <form method=post name="po_alternate_admin_settings" action="">
	        <div id="alternate-admin-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_alternate_admin">Alternate Admin</label></h3>
			  <div class="inside">
				<?php $alternateAdmin = get_option("PO_alternate_admin"); ?>
				<input type="radio" name="PO_alternate_admin" value="1" <?php print ($alternateAdmin == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_alternate_admin" value="0" <?php print ($alternateAdmin != "1")? "checked='checked'":""; ?>> Disable
				<br />
				This enables the old admin pages.  If you are having problems with the bulk actions on the plugins page turn this on.
				<br />
				<input type=submit name=submit value="Save Settings" />
			  </div>
		    </div>
			<input type=hidden name="page" value="Plugin_Organizer" />
			<?php echo '<input type="hidden" name="PO_nonce" id="PO_nonce" value="' . $this->nonce . '" />'; ?>	
		  </form>
		  <br /><br />
		  <form method=post name="po_fuzzy_url_matching_settings" action="">
	        <div id="fuzzy-url-matching-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_fuzzy_url_matching">Fuzzy URL matching</label></h3>
			  <div class="inside">
				<?php $fuzzyUrlMatching = get_option("PO_fuzzy_url_matching"); ?>
				<input type="radio" name="PO_fuzzy_url_matching" value="1" <?php print ($fuzzyUrlMatching == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_fuzzy_url_matching" value="0" <?php print ($fuzzyUrlMatching != "1")? "checked='checked'":""; ?>> Disable
				<br />
				This gives URLs entered into the URL admin the ability to effect children of that URL.
				<br />
				<input type=submit name=submit value="Save Settings" />
			  </div>
		    </div>
			<input type=hidden name="page" value="Plugin_Organizer" />
			<?php echo '<input type="hidden" name="PO_nonce" id="PO_nonce" value="' . $this->nonce . '" />'; ?>	
		  </form>
		  <br /><br />
		  <form method=post name="po_ignore_protocol_settings" action="">
	        <div id="ignore-protocol-div" class="stuffbox" style="width: 98%">
			  <h3><label for="PO_ignore_protocol">Ignore URL Protocol</label></h3>
			  <div class="inside">
				<?php $ignoreProtocol = get_option("PO_ignore_protocol"); ?>
				<input type="radio" name="PO_ignore_protocol" value="1" <?php print ($ignoreProtocol == "1")? "checked='checked'":""; ?>> Enable<br />
				<input type="radio" name="PO_ignore_protocol" value="0" <?php print ($ignoreProtocol != "1")? "checked='checked'":""; ?>> Disable
				<br />
				This allows you to ignore the protocol (http, https) of a URL when trying to match it in the database at page load time.  With this enabled https://yoururl.com/page/ will have the same plugins loaded as http://yoururl.com/page/.  If disabled they can be set seperately using the URL admin.
				<br />
				<input type=submit name=submit value="Save Settings" />
			  </div>
		    </div>
			<input type=hidden name="page" value="Plugin_Organizer" />
			<?php echo '<input type="hidden" name="PO_nonce" id="PO_nonce" value="' . $this->nonce . '" />'; ?>	
		  </form>
		  <br /><br />
		  <div id="redo-permalinks-div" class="stuffbox" style="width: 98%">
			  <h3><label for="redo-permalinks">Recreate Permalinks</label></h3>
			  <div class="inside">
				<input type=button name="redo-permalinks" value="Recreate Permalinks" onmousedown="submitRedoPermalinks();" />
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
						if (!in_array($postType, array("attachment", "revision", "nav_menu_item"))) {
							print '<input type="checkbox" class="PO_cutom_post_type" name="PO_cutom_post_type[]" value="'.$postType.'" '.((in_array($postType, $supportedPostTypes))? 'checked="checked"' : '').' />'.$postType.'<br />';
						}
					}
				}
				?>
				<input type=button name="add-post-support" value="Save Post Types" onmousedown="submitPostTypeSupport();">
			  </div>
		  </div>
	    </div>
      </div>
    </div>
</div>


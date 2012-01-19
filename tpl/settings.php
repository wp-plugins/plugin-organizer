<div id="theme-options-wrap">
    <div class="icon32" id="icon-options-general"> <br /> </div>

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
	      <form method=post name="po_general_settings" action="" enctype="multipart/form-data">
	        <?php 
			echo '<input type="hidden" name="PO_nonce" id="PO_nonce" value="' . $PO_nonce . '" />';
			?>			
			<div id="general-settings-div" class="stuffbox" style="width: 98%">
              <h3><label for="order[]">Selective Plugin Loading</label></h3>
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
				<input type=hidden name="page" value="Plugin_Organizer" />
				<input type=submit name=submit value="Save Settings" />
              </div>
            </div>
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


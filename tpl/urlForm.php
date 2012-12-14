<?php print ($ajaxRequest)? '':'<div id="PO-url-admin-wrap">'; ?>
    <div class="icon32" id="icon-link-manager"> <br /> </div>

    <h2>Arbitrary URL's</h2>
    <p>Enter the url and disable or enable plugins by checking the checkboxes.</p>
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
	      <form method=post id="po_url_form" name="po_url_edit" action="<?php print admin_url('admin.php'); ?>?page=PO_url_admin">
	        <div id="po_permalink_div" class="stuffbox" style="width: 98%">
              <h3><label id="permalinkLabel" for="permalink">URL</label></h3>
			  <div class="inside">
				<input type="text" id="permalink" name="permalink" size="25" title="URL" value="<?php print $urlDetails['permalink']; ?>"><br />
				<input type="checkbox" name="effectChildren" id="effectChildren" value="1" <?php print ($effectChildren == 1)? 'checked="checked"' : ''; ?>> Also effect children

			  </div>
			</div>
		    <div id="po_disabled_plugins_div" class="stuffbox" style="width: 98%">
			  <h3><label id="disabledPluginsLabel" for="disabledPlugins[]">Disabled Plugins</label></h3>
			  <div class="inside">
            	<input type="checkbox" id="selectAllDisablePlugins" name="selectAllDisablePlugins" value="" onclick="PO_check_all_disable_plugins();">Select All<br><br>
				<?php
				  $count = 1;
				  foreach ($plugins as $key=>$plugin) {
					  ?>
					  <input type="checkbox" class="disabled_plugin_check" name="disabledPlugins[]" id="disabled_plugin_<?php print $count; ?>" value="<?php print $key; ?>" <?php print (in_array($key,  $disabledPlugins))? 'checked="checked"' : ''; ?>><?php print (in_array($key,  $activePlugins))? "<span class=\"activePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
					  <?php
					  $count++;
				  }
			    ?>
              </div>
            </div>
			<div id="po_global_plugins_div" class="stuffbox" style="width: 98%">
			  <h3><label id="enabledPluginsLabel" for="enabledPlugins[]">Enabled Plugins</label></h3>
			  <div class="inside">
            	<input type="checkbox" id="selectAllEnablePlugins" name="selectAllEnablePlugins" value="" onclick="PO_check_all_enable_plugins();">Select All<br><br>
				<?php
				  $count = 1;
				  foreach ($plugins as $key=>$plugin) {
					  if (in_array($key,  $globalPlugins)) {
						  ?>
						  <input type="checkbox" class="enabled_plugin_check" name="enabledPlugins[]" id="enabled_plugin_<?php print $count; ?>" value="<?php print $key; ?>" <?php print (in_array($key,  $enabledPlugins))? 'checked="checked"' : ''; ?>><?php print $plugin['Name']; ?><br>
						  <?php
					  }
					  $count++;
				  }
			    ?>
              </div>
            </div>
			<input type=hidden id="url_id" name="url_id" value="<?php print $urlId; ?>">
			<input type="button" name="PO_submit_url" id="PO_submit_url" value="Save URL" />
	      </form>
	    </div>
      </div>
    </div>
<?php print ($ajaxRequest)? '':'</div>'; ?>
<style type="text/css">
	.activePlugin {
		color: #FF0033;
	}
</style>
<div id="wrap">
    <div class="icon32" id="icon-link-manager"> <br /> </div>

    <h2>Arbitrary URL's</h2>
    <p>This is a list of URL's that don't have a post tied to them.  Click the edit link for the url you would like to modify.</p>
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
	      <form method=post id="po_url_form" name="po_url_edit" action="<?php print admin_url('admin.php'); ?>?page=PO_url_admin" enctype="multipart/form-data">
	        <div id="po_permalink_div" class="stuffbox" style="width: 98%">
              <h3><label id="permalinkLabel" for="permalink">URL</label></h3>
			  <div class="inside">
				<input type="text" name="permalink" size="25" title="URL" value="<?php print $urlDetails['permalink']; ?>">

			  </div>
			</div>
		    <div id="po_disabled_plugins_div" class="stuffbox" style="width: 98%">
			  <h3><label for="disabledPlugins[]">Disabled Plugins</label></h3>
			  <div class="inside">
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
			  <h3><label for="enabledPlugins[]">Enabled Plugins</label></h3>
			  <div class="inside">
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
			<input type=hidden name="edit_url" value="1">
			<input type=hidden name="url_admin_page" value="edit">
			<input type=hidden name="url_id" value="<?php print $urlId; ?>">
			<input type=submit id="PO_submit_url" name=submit value="Save URL">
	      </form>
	    </div>
      </div>
    </div>
</div>


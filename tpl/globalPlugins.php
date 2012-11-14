<div class="wrap" id="wrap">
    <div class="icon32" id="icon-po-global"> <br /> </div>

    <h2>Global Plugins</h2>
    <p>Select the plugins you would like to disable site wide.  This will allow you to not load the plugin on any page unless it is specifically allowed in the post or page.</p>
	<div id="poststuff" class="metabox-holder">
      <div id="post-body">
        <div id="post-body-content">
	      <form method=post name="po_global_plugin_list" action="">
	        <div id="pluginListdiv" class="stuffbox" style="width: 98%">
              <input type="checkbox" id="selectAllDisablePlugins" name="selectAllDisablePlugins" value="" onclick="PO_check_all_disable_plugins();">Select All<br><br>
			  <?php
				$count = 1;
				foreach ($plugins as $key=>$plugin) {
					?>
					<input type="checkbox" class="disabled_plugin_check" name="disabledPlugins[]" id="disabled_plugin_<?php print $count; ?>" value="<?php print $key; ?>" <?php print (in_array($key, $disabledPlugins)) ? 'checked="checked"': ''; ?>><?php print (in_array($key, $activeSitewidePlugins) || in_array($key, $activePlugins))? "<span class=\"activePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
					<?php
					$count++;
				}
			  ?>
			  <div class="inside">
            	<input type=hidden name="page" value="PO_global_plugins">
				<input type=button name=submit value="Save" onmousedown="PO_submit_global_plugins();">
              </div>
            </div>
	      </form>
	    </div>
      </div>
    </div>
</div>


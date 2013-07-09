<div class="wrap" id="wrap">
    <div class="icon32" id="icon-po-global"> <br /> </div>

    <h2>Global Plugins</h2>
    <p>Select the plugins you would like to disable site wide.  This will allow you to not load the plugin on any page unless it is specifically allowed in the post or page.</p>
	<div id="poststuff" class="metabox-holder">
      <div id="post-body">
        <div id="post-body-content">
	      <div id="pluginListdiv" class="stuffbox" style="width: 98%">
              <div id="pluginContainer" class="metaBoxContent">
					<div class="pluginWrap">
						<div class="toggleContainer">
							Standard<br />
							<input type="checkbox" id="toggleAllPlugins" name="toggleAllPlugins" value="" onclick="PO_toggle_all_plugins();">Toggle
						</div>
						<?php if (get_option('PO_disable_mobile_plugins') == 1) { ?>
							<div class="toggleContainer">
								Mobile<br />
								<input type="checkbox" id="toggleAllMobilePlugins" name="toggleAllMobilePlugins" value="" onclick="PO_toggle_all_mobile_plugins();">Toggle
							</div>
						<?php } ?>
						<div style="clear: both;"></div>
					</div>
					
					<?php
					$count = 0;
					foreach ($plugins as $key=>$plugin) {
						$count++;
						?>
						<div class="pluginWrap <?php print (in_array($key, $activeSitewidePlugins) || in_array($key, $activePlugins))? "activePluginWrap" : "inactivePluginWrap"; ?>">
							<div class="toggleContainer">
								<?php 
								if (in_array($key, $disabledPlugins)) {
									?>
									<input type="checkbox" class="pluginsList" id="plugins_<?php print $count; ?>" name="pluginsList[]" value="<?php print $key; ?>" checked="checked" />
									<img src="<?php print $this->urlPath; ?>/image/off-button.png" class="pluginsButton pluginsButtonOff" alt="Off" id="pluginsButton_<?php print $count; ?>" onclick="PO_toggle_on_off('pluginsButton_<?php print $count; ?>', 'plugins_<?php print $count; ?>', '<?php print $count; ?>');" />
									<?php
								} else {
									?>
									<input type="checkbox" class="pluginsList" id="plugins_<?php print $count; ?>" name="pluginsList[]" value="<?php print $key; ?>" />
									<img src="<?php print $this->urlPath; ?>/image/on-button.png" class="pluginsButton pluginsButtonOn" alt="On" id="pluginsButton_<?php print $count; ?>" onclick="PO_toggle_on_off('pluginsButton_<?php print $count; ?>', 'plugins_<?php print $count; ?>', '<?php print $count; ?>');" />
									<?php
								}
								?>
							</div>
							<?php if (get_option('PO_disable_mobile_plugins') == 1) { ?>
								<div class="toggleContainer">
									<?php 
									if (in_array($key, $disabledMobilePlugins)) {
										?>
										<input type="checkbox" class="mobilePluginsList" id="mobilePlugins_<?php print $count; ?>" name="mobilePluginsList[]" value="<?php print $key; ?>" checked="checked" />
										<img src="<?php print $this->urlPath; ?>/image/off-button.png" class="pluginsButton pluginsButtonOff" alt="Off" id="mobilePluginsButton_<?php print $count; ?>" onclick="PO_toggle_on_off('mobilePluginsButton_<?php print $count; ?>', 'mobilePlugins_<?php print $count; ?>', '<?php print $count; ?>');" />
										<?php
									} else {
										?>
										<input type="checkbox" class="mobilePluginsList" id="mobilePlugins_<?php print $count; ?>" name="mobilePluginsList[]" value="<?php print $key; ?>" />
										<img src="<?php print $this->urlPath; ?>/image/on-button.png" class="pluginsButton pluginsButtonOn" alt="On" id="mobilePluginsButton_<?php print $count; ?>" onclick="PO_toggle_on_off('mobilePluginsButton_<?php print $count; ?>', 'mobilePlugins_<?php print $count; ?>', '<?php print $count; ?>');" />
										<?php
									}
									?>
								</div>
							<?php } ?>
							<div class="pluginLabel">
								<?php print $plugin['Name']; ?>
							</div>
							<div style="clear: both;"></div>
						</div>
						<?php
					}
					?>
				<div style="clear: both;"></div>
			  <div class="inside">
            	<input type=hidden name="page" value="PO_global_plugins">
				<input type=button name=submit value="Save" onmousedown="PO_submit_global_plugins();">
              </div>
            </div>
	    </div>
      </div>
    </div>
</div>


<div class="metaBoxLabel">
	Settings
</div>
<div class="metaBoxContent">
	NOTE:  By checking this box the plugins disabled or enabled for this page will be used for its children if they have nothing set.
	<hr>
	<input type="checkbox" id="affect_children" name="affect_children" value="1" <?php print ($affectChildren == "1")? 'checked="checked"':''; ?>>Also Affect Children
</div>
<div class="metaBoxLabel">
	Disabled Plugins
</div>
<div class="metaBoxContent">
	NOTE:  This is a list of all plugins for this site.  If a plugin is checked it will be disabled for this page.  Plugins in <span class="activePlugin">RED</span> are active for this site.
	<hr>
	<input type="checkbox" id="selectAllDisablePlugins" name="selectAllDisablePlugins" value="" onclick="PO_check_all_disable_plugins();">Select All<br><br>
	<?php
	foreach ($plugins as $key=>$plugin) {
		?>
		<input class="disabled_plugin_check" type="checkbox" name="disabledPlugins[]" value="<?php print $key; ?>" <?php print (in_array($key, $disabledPluginList))? 'checked="checked"':''; ?>><?php print (in_array($key, $activeSitewidePlugins) || in_array($key, $activePlugins))? "<span class=\"activePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
		<?php
	}
	?>
</div>
<div class="metaBoxLabel">
	Enabled Plugins
</div>
<div class="metaBoxContent">
	NOTE:  This is a list of globally disabled plugins.  If a plugin is checked it will be enabled for this page.  Plugins in <span class="activePlugin">RED</span> are active for this site.
	<hr>
	<input type="checkbox" id="selectAllEnablePlugins" name="selectAllEnablePlugins" value="" onclick="PO_check_all_enable_plugins();">Select All<br><br>
	<?php
	foreach ($plugins as $key=>$plugin) {
		if (in_array($key, $globalPlugins)) {
			?>
			<input class="enabled_plugin_check" type="checkbox" name="enabledPlugins[]" value="<?php print $key; ?>" <?php print (in_array($key, $enabledPluginList))? 'checked="checked"':''; ?>><?php print (in_array($key, $activeSitewidePlugins) || in_array($key, $activePlugins))? "<span class=\"activePlugin\">".$plugin['Name']."</span>" : $plugin['Name']; ?><br>
			<?php
		}
	}
	?>
</div>
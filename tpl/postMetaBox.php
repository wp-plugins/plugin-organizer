<?php
if ($errMsg != "") {
	?>
	<p style="color: #CC0066;"><?php print $errMsg; ?></p>
	<?php
}
?>
<?php if(get_post_type($post->ID) == 'plugin_filter') { ?>
	<div class="metaBoxLabel">
		Name
	</div>
	<div class="metaBoxContent">
		<input type="text" class="poFilterNameInput" size="25" name="filterName" value="<?php print $filterName; ?>">
	</div>
	<div class="metaBoxLabel">
		Permalink
	</div>
	<div class="metaBoxContent">
		<input type="text" class="poPermalinkInput" size="25" name="permalinkFilter" value="<?php print $permalinkFilter; ?>">
	</div>
<?php } ?>

<div class="metaBoxLabel">
	Settings
</div>
<div class="metaBoxContent">
	NOTE:  By checking this box the plugins disabled or enabled for this page will be used for its children if they have nothing set.
	<hr>
	<input type="checkbox" id="affectChildren" name="affectChildren" value="1" <?php print ($affectChildren == "1")? 'checked="checked"':''; ?>>Also Affect Children
</div>
<div class="metaBoxLabel">
	Plugins
</div>
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
				if ((in_array($key, $globalPlugins) && !in_array($key, $enabledPluginList)) || in_array($key, $disabledPluginList)) {
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
					if ((in_array($key, $globalMobilePlugins) && !in_array($key, $enabledMobilePluginList)) || in_array($key, $disabledMobilePluginList)) {
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
</div>
<div style="clear: both;"></div>
<input type="hidden" name="poSubmitPostMetaBox" value="1" />
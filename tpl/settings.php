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
			echo '<input type="hidden" name="PO_noncename" id="PO_noncename" value="' . $PO_noncename . '" />';
			?>			
			<div id="general-settings-div" class="stuffbox" style="width: 98%">
              <h3><label for="order[]">Selective Plugin Loading</label></h3>
			  <div class="inside">
            	<?php $selectiveLoad = get_option("PO_disable_plugins"); ?>
				<input type="radio" name="selective_load" value="1" <?php print ($selectiveLoad == "1")? "checked='checked'":""; ?>> Enable<br>
				<input type="radio" name="selective_load" value="0" <?php print ($selectiveLoad != "1")? "checked='checked'":""; ?>> Disable
				<br>
				NOTE:  When this option is enabled you must move the PluginOrganizerMU.class.php file from /wp-content/plugins/plugin_organizer/lib to /wp-content/mu-plugins before it will take effect.  If you don't have an mu-plugins folder you need to create it.
				<br>
				<input type=hidden name="page" value="Plugin_Organizer">
              </div>
            </div>
			<input type=submit name=submit value="Save Settings">
	      </form>
	    </div>
      </div>
    </div>
  </div>
</div>


<div id="PO-group-wrap">
    <div class="icon32" id="group-general"> <br /> </div>

    <h2><?php print $currGroup['group_name']; ?></h2>
    <p>Select the plugins you would like to display in this group.</p>
	<?php
	if ($errMsg != "") {
		?>
		<p style="color: #CC0066;"><?php print $errMsg; ?></p>
		<?php
	}
	?>

    <div id="groupstuff" class="metabox-holder">
      <div id="group-body">
        <div id="group-body-content">
	      <form method=post name="po_edit_plugin_group" action="" enctype="multipart/form-data">
	        <div id="plugingroupdiv" class="stuffbox" style="width: 98%">
              <h3><label for="group[]">Group Details</label></h3>
			  <div id="group_details" class="inside">
            	Group Name: <input type="text" id="group_name" name="group_name" value="<?php print $currGroup['group_name']; ?>">
				<br><br>
				<?php
				$count = 1;
				foreach ($plugins as $plugin) {
					$pluginDetails = get_plugins("/" . dirname($plugin));
					?>
					<input type="checkbox" name="group[]" id="group_member_<?php print $count; ?>" value="<?php print $pluginDetails[basename($plugin)]['Name']; ?>" <?php print (in_array($pluginDetails[basename($plugin)]['Name'], $members)) ? 'checked="checked"': ''; ?>> <?php print $pluginDetails[basename($plugin)]['Name']; ?><br>
					<?php
					$count++;
				}
				?>
				<br>
				<input type=hidden name="page" value="PO_Groups">
				<input type=button name=submit value="Save Group" onmousedown="submitPluginGroup('<?php print $currGroup['group_id']; ?>');">
				<br>
			  </div>
            </div>
	      </form>

		  <form method=post name="po_switch_plugin_group" action="" enctype="multipart/form-data">
	        <div id="pluginselectdiv" class="stuffbox" style="width: 98%">
              <h3><label for="PO_group">Select Group To Edit</label></h3>
			    <select name="PO_group">
				<?php
				foreach ($allGroups as $group) {
					print "<option value=\"" . $group->group_id . "\">" . $group->group_name . "</option>";
				}
			    ?>
				</select>
				<input type=hidden name="page" value="PO_Groups">
				<input type=submit name="switchGroup" value="Switch Group">
            </div>
	      </form>

		  <form method=post name="po_create_plugin_group" action="" enctype="multipart/form-data">
	        <div id="newgroupdiv" class="stuffbox" style="width: 98%">
              <h3><label for="new_group_name">Create New Group</label></h3>
			    <input type="text" name="new_group_name">
				<input type=hidden name="page" value="PO_Groups">
				<input type=submit name="createGroup" value="Create Group">
            </div>
	      </form>
		  <form method=post name="po_delete_plugin_group" action="" enctype="multipart/form-data">
	        <div id="delgroupdiv" class="stuffbox" style="width: 98%">
              <h3><label for="del_group_name">Delete Group</label></h3>
			    <select name="PO_group">
				<?php
				foreach ($allGroups as $group) {
					print "<option value=\"" . $group->group_id . "\">" . $group->group_name . "</option>";
				}
			    ?>
				</select>
				<input type=hidden name="page" value="PO_Groups">
				<input type=submit name="deleteGroup" value="Delete Group">
            </div>
	      </form>
	    </div>
      </div>
    </div>
  </div>


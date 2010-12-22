<script type="text/javascript" language="javascript">
	function uniqueOrder(currentId) {
		var newVal = jQuery("#" + currentId).val();
		var oldVal = jQuery("#old_" + currentId).val();
		var selections = jQuery('select[name^=order]');
		for (var i=0; i<selections.length; i++) {
			if (selections[i].id != currentId && selections[i].value == newVal) {
				selections[i].value = oldVal;
				jQuery("#old_" + selections[i].id).val(oldVal);
			}
		}
		jQuery("#old_" + currentId).val(newVal);

	}
</script>
<div id="theme-options-wrap">
    <div class="icon32" id="icon-options-general"> <br /> </div>

    <h2>Plugin Load Order</h2>
    <p>Select the order that you want your plugins to be loaded.</p>
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
	      <form method=post name="po_edit_plugin_list" action="" enctype="multipart/form-data">
	        <div id="pluginListdiv" class="stuffbox" style="width: 98%">
              <?php
				$count = 1;
				foreach ($plugins as $plugin) {
					$pluginDetails = get_plugins("/" . dirname($plugin));
					?>
					<h3><label for="order[]"><?php print $pluginDetails[basename($plugin)]['Name']; ?></label></h3>
					<div class="inside">
					  <input type="hidden" id="old_order_<?php print $count; ?>" value="<?php print $count; ?>">
					  <select name="order[]" id="order_<?php print $count; ?>" onchange="uniqueOrder('order_<?php print $count; ?>');">
					    <?php
						for ($i = 1; $i<=sizeof($plugins); $i++) {
							?>
							<option value="<?php print $i; ?>" <?php print ($i == $count) ? "selected=\"selected\"" : ""; ?>><?php print $i; ?>
							<?php
						}
						?>
					  </select>
					</div>
					<?php
					$count++;
				}
			  ?>
			  <div class="inside">
            	<input type=hidden name="page" value="Plugin_Organizer">
				<input type=submit name=submit value="Save Order" tabindex="8">
              </div>
            </div>
	      </form>
	    </div>
      </div>
    </div>
  </div>
</div>


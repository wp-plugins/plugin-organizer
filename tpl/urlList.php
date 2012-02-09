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
	      <form method=post name="po_url_list" action="<?php print admin_url('admin.php'); ?>?page=PO_url_admin" enctype="multipart/form-data">
	        <input type="hidden" name="url_admin_page" value="add">
			<input type="submit" name="create_url" value="Add New">
			<div id="po_url_list_div" class="stuffbox" style="width: 98%">
              <h3><label for="url">URL List</label></h3>
			  <div class="inside">
			  <?php
				$count = 1;
				foreach ($urlList as $url) {
					print $url->permalink;
					?>
					  <div style="float: right;"><a class="editUrl" href="<?php print admin_url('admin.php'); ?>?page=PO_url_admin&url_admin_page=edit&url_id=<?php print $url->url_id; ?>">Edit</a> - <a id="deleteUrl<?php print $url->url_id; ?>" class="deleteUrl" href="<?php print admin_url('admin.php'); ?>?page=PO_url_admin&delete_url=1&url_id=<?php print $url->url_id; ?>">Delete</a></div><div style="clear:right;"><hr></div>
					<?php
				}
			  ?>
			  </div>
            </div>
	      </form>
	    </div>
      </div>
    </div>
</div>


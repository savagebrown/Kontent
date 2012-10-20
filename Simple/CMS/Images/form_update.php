<?php

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process image edit form #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'imageupdate', 'The image information has been updated successfully.');

	if (isset($_POST['imageeditsubmitted'])) {

		// Post
		$image_caption		= safe_escape($_POST['image_caption']);
		$image_placement	= ($_POST['image_placement'])?$_POST['image_placement']:1;

		$image_id			= $_POST['image_id'];
		$image_table		= $_POST['image_table'];
		$image_path			= $_POST['image_path'];
		$image_inline		= $_POST['image_inline'];

		$owner_id_field		= $_POST['owner_id_field'];
		$owner_page			= $_POST['owner_page'];
		$owner_param		= $_POST['owner_param'];
		$owner_id			= $_POST['owner_id'];

		$sql_update_image_caption = <<<SQL
		
UPDATE $image_table SET
	caption = '$image_caption',
	placement = $image_placement
WHERE 
	id = $image_id

SQL;
		$q_update_image_caption = $db->query($sql_update_image_caption);
		if (DB::isError($q_update_image_caption)) { sb_error($q_update_image_caption); }

		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated image information for '.$owner_param.' id#'.$owner_id);
		}

		header ('Location: '.$owner_page.'?'.$owner_param.'='.$owner_id.'&imageupdate=1');
		exit;
	}
?>
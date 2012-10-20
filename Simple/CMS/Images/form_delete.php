<?php

if (!$delete_all_images_for) {
		
	if (ctype_digit(trim($_GET['imgdeleteid'])) && trim($_GET['imgdeleteid']) > 0) {

		$del_id = $_GET['imgdeleteid'];
	    $sql_img = <<<SQL

SELECT id, $item_id_label, rank FROM $item_images_table WHERE id = $del_id

SQL;
	    $q_img = $db->query($sql_img);
	    if (DB::iserror($q_img)) { sb_error($q_img); }
	    
	    $r_img = $q_img->fetchrow(DB_FETCHMODE_ASSOC);

	    $sql = <<<SQL

DELETE FROM $item_images_table WHERE id = $del_id

SQL;
	    $q = $db->query($sql);
	    if (DB::isError($q)) { sb_error($q); }

		$image_file = $r_img[$item_id_label].'_'.$del_id;

		foreach( $item_image_dir as $dir ) {
			@unlink($item_image_path.'/'.$dir.'/'.$image_file.'.jpg');
		}

		if (is_array($item_image_alpha)) {
			foreach ($item_image_alpha as $alpha) {
				@unlink($item_image_path.'/'.$item_image_alpha_dir.'/'.$image_file.'_'.$alpha.'.jpg');
			}
		}
		
		// FIXME: get title of owner for logging
		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Deleted image ('.$image_file.')');
		}
		
	    // Redirect
		if ($redirect_after_deletion) {
	    	go_to(null, '?'.$item_id_param.'='.$r_img[$item_id_label].'&deleted='.$del_id);
		}

	}

// Delete all images associated with item (page, article, gallery, listing, etc)
} else {
	
	    $sql_img = <<<SQL

SELECT id FROM $item_images_table WHERE $item_id_label = $delete_all_images_for

SQL;
	    $q_img = $db->query($sql_img);
	    if (DB::iserror($q_img)) { sb_error($q_img); }
	
	
		while ($r_img = $q_img->fetchrow(DB_FETCHMODE_ASSOC)) {

		    $image_file = $delete_all_images_for.'_'.$r_img['id'];
               
		    foreach( $item_image_dir as $dir ) {
		    	@unlink($item_image_path.'/'.$dir.'/'.$image_file.'.jpg');
		    }
               
		    foreach ($item_image_alpha as $alpha) {
		    	@unlink($item_image_path.'/'.$item_image_alpha_dir.'/'.$image_file.'_'.$alpha.'.jpg');
		    }
		}

	    $sql = <<<SQL

DELETE FROM $item_images_table WHERE $item_id_label = $delete_all_images_for

SQL;
	    $q = $db->query($sql);
	    if (DB::isError($q)) { sb_error($q); }

}

?>
<?php

		// Clean input ------------------------------------------------------

		$submit_vars = array(
			$item_id_label	  => $current_item,
			'image_placement' => ($form->getSubmitValue('image_placement'))?$form->getSubmitValue('image_placement'):1,
			'image_title'	  => safe_escape($form->getSubmitValue('image_title')),
			'image_caption'	  => safe_escape($form->getSubmitValue('image_caption')),
			'dateCrtd'		  => date('Y-m-d H:i:s')
		);

		// Image Files ------------------------------------------------------

		if ($file_image->isUploadedFile()) {

			$sql_add_i = <<<SQL

INSERT INTO $item_images_table
	(
		$item_id_label, placement, caption, dateCrtd, rank
	)
VALUES
	(
		'{$submit_vars[$item_id_label]}',
		 {$submit_vars['image_placement']},
		'{$submit_vars['image_caption']}',
		'{$submit_vars['dateCrtd']}',
		IFNULL((SELECT X.rank FROM $item_images_table AS X WHERE X.$item_id_label = '{$submit_vars[$item_id_label]}' ORDER BY X.rank DESC LIMIT 1)+1, 0)
	)

SQL;
			$q_add_i = $db->query($sql_add_i);
			if (DB::isError($q_add_i)) { sb_error($q_add_i); }

			// Get new article_images id
			$new_image_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM ".$item_images_table);

			// Add id directory if necessary
			if ($item_image_path_seg) {
				$item_image_path = $item_image_path.'/'.$current_item;
			}

			// Move file. First delete existing.
			if ($form->process('process_image_upload', true)) {

				$file_info = $form->getSubmitValue('image_upload');
				if (($pos = strrpos($file_info['name'], ".")) === FALSE) {
					print "where is the extension?";
				} else {
					$extension = 'jpg';//substr($file_info['name'], $pos + 1);
				}

				// Save Image Files -----------------------------------------

				require_once 'Simple/Image/NewSize.php';
				require_once 'Simple/Image/Watermark.php';

				foreach ($item_image AS $k => $v) {


					$source_image = $item_image_path.'/Originals/'.$current_item."_".$new_image_id.".".$extension; // image to be resized
					$rename = $current_item."_".$new_image_id.".".$extension; // image name
					$saveto = $item_image_path.'/'.$v['dir']; // destination
					$max_w	= $v['x']; // maximum width
					$max_h	= $v['y']; // maximum height
					$crop	= $v['crop']; // crop or not

					$save_this_image = array($source_image, $rename, $saveto, $max_w, $max_h, $crop);
					$new_image = new simple_image_newsize($save_this_image);
				}

				// Save Alph Image Files ------------------------------------

				foreach ($item_image_alpha AS $alpha) {
					$img = $item_image_path.'/'.$item_image_alpha_dir.'/'.$current_item."_".$new_image_id.".".$extension;
					$watermark_img = $item_image_alpha_object_path.'/'.$alpha.'.png';
					$position = $item_image_alpha_position;
					$padding = $item_image_alpha_padding;
					$rename = $current_item."_".$new_image_id.'_'.$alpha.'.'.$extension;
					$saveto = $item_image_path.'/'.$item_image_alpha_dir.'/';

					$save_this_alpha = array($img, $watermark_img, $position, $padding, $rename, $saveto);
					$new_alpha = new simple_image_watermark($save_this_alpha);
				}
			}
			// FIXME: get title of owner for logging
			// Log activity
			if ($g['log']['active']==1) {
				$log = new LogManager();
				$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added '.$item_display_title.' image ('.$current_item.')');
			}

		} else {
			go_to(null, '?error=Image was not uploaded. Please contact your website administrator.');
		}


	function process_image_upload($values) {

		global $file_image;
		global $current_item;
		global $new_image_id;
		global $item_image_path;

		if ($file_image->isUploadedFile()) {

			$file_image->moveUploadedFile($item_image_path.'/Originals');

			$file_info = $file_image->getValue();

			if (($pos = strrpos($file_info['name'], ".")) === false) {
				return false;
			} else {
				$extension = 'jpg';//substr($file_info['name'], $pos + 1);
			}

			if (file_exists($item_image_path.'/'.$current_item."_".$new_image_id.".".$extension)) {
				unlink($item_image_path.'/'.$current_item."_".$new_image_id.".".$extension);
			}

			$old_mask = umask(0);
			@chmod($item_image_path.'/Originals/'.$file_info['name'], 0777);
			umask($old_mask);

			if (rename($item_image_path.'/Originals/'.$file_info['name'], $item_image_path.'/Originals/'.$current_item."_".$new_image_id.".".$extension)) {
				return true;
			} else {
				return false;
			}
		}
	}

?>
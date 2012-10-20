<?php

	/*
		The following must be set

		* $idset (bool)
		* $image_owner_param
		* $image_owner_id_field
		* $image_owner_current_id
		* $image_owner_page
		* $image_owner_title
		* $image_table
		* $image_path
		* $image_path_seg
		* $image_inline (bool)
	*/

	if ($idset) {

		$g['page']['js_top'] .= <<<HTML

		<script type="text/javascript" src="{$g['page']['js']}/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
		<script type="text/javascript" src="{$g['page']['js']}/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
		<link rel="stylesheet" type="text/css" href="{$g['page']['js']}/fancybox/jquery.fancybox-1.3.4.css" media="screen" />

HTML;

		$g['page']['header'] .= <<<HTML

<script type="text/javascript" charset="utf-8">

$(document).ready(function() {
  $('#image-upload-add').click(function() {
	$('#add-new-image').slideToggle('fast');
	
	if( $("#image-upload-add").text().indexOf('Cancel') >= 0) {
	        $('#image-upload-add').text("Add a new image");
	    } else {
			$('#image-upload-add').text("Cancel");
	}
	return false;
  });
});

$(document).ready(function() {
	$(".edit_image").fancybox({
		'width'				: '70%',
		'height'			: '70%',
		'autoScale'			: true
	});
});

</script>

HTML;


		$sql_item_img = <<<SQL

SELECT id, caption, placement, rank
FROM $image_table
WHERE $image_owner_id_field = $image_owner_current_id
ORDER BY rank ASC

SQL;
		$q_item_img = $db->query($sql_item_img);
		if ($db->iserror($q_item_img)) { sb_error($q_item_img); }

		if ($q_item_img->numrows() > 0) {
			$count=0;
			while ($r_item_img = $q_item_img->fetchrow(DB_FETCHMODE_ASSOC)) {
				$count++;
				$item_image_caption = $r_item_img['caption'];
				$item_image_name = $image_owner_current_id.'_'.$r_item_img['id'].'.jpg';
				$image_placement_key = array (1=>'Left',2=>'Right',3=>'Span');
				$item_image_placement = $image_placement_key[$r_item_img['placement']];
				$item_image_link = '<li id="item_'.$r_item_img['id'].'" class="image-unit"><div class="handle"></div><a class="lbOn edit_image" href="image_edit.php?image_table='.$image_table.'&image_id='.$r_item_img['id'].'&owner_id_field='.$image_owner_id_field.'&path='.$image_path.'&owner_page='.$image_owner_page.'&image_owner_title='.$image_owner_title.'&owner_param='.$image_owner_param.'&inline='.$image_inline.'&markup_text=IMAGE'.$count.'">';
				if (file_exists($image_path.'/ms/'.$item_image_name)) {
					$item_image_src = $image_path.'/ss/'.$item_image_name;
				} else if (file_exists($image_path.'/ms/'.$item_image_name)) {
					$item_image_src = $image_path.'/ms/'.$item_image_name;
				} else {
					$item_image_src = 'Images/no-preview.gif';
				}

				$item_images .= $item_image_link.'<img src="'.$item_image_src.'" width="100" height="100" /></a></li>'."\n";
			}
			$add_item_images = '<div style="clear:both"></div><p style="padding-left:15px;margin:10px 0;"><a id="image-upload-add" href="#">Add a new image</a></p>';

			$form->addElement('html', '

				<tr>
					<td class="label">Images:<br /><em id="workingMsg" style="display:none;color:green;">Updating...</em></td>
					<td>
						<ul id="listContainer">
						'.$item_images.'
						</ul>
						'.$add_item_images.'
			');

		} else {
			$add_item_images = '<p style="margin: 0 0 10px 0;">
								<em>No images are attached.</em>
								<a id="image-upload-add" href="#">Add a NEW image</a>
								</p>';

			$form->addElement('html', '

				<tr>
					<td class="label">Images:</td>
					<td>
						'.$add_item_images.'
			');
		}

		// Image add form ---------------------------------------------------

		$max_size = 4194304; // 4MB

		$form->addElement('html', '

		<div id="add-new-image" style="display:none;">
		<p><em>Only jpg images are allowed and max image upload size is '.format_byte($max_size).'.</em></p>

		<div style="margin:5px 5px 5px 0;padding:5px;border:1px solid #e5e5e5;background-color:#fff;">

');

		// Image upload
		$file_image =& $form->addElement('file','image_upload','First Image Upload:');
		// Have HTML_QuickForm test, after the file is uploaded, that it is
		// less than 4mb
		$form->addRule('image_upload','Your file is too large. Please optimize the image and try again or select another image to upload. Limit 4MB','maxfilesize',$max_size);
		// Tell well-behaved browsers not to allow upload of a file larger
		// than 4mb
		$form->setMaxFileSize($max_size);
		// Mimetype
		$form->addRule('image_upload', 'Image must be a jpg', 'mimetype', $g['mimes']['jpeg']);

		$form->addElement('html', '</div><p>Image Caption:</p><p> ');

		$form->addElement('textarea', 'image_caption', 'Caption');

		if ($image_inline) {
			$form->addElement('html', '</p><p>Image Alignment: ');

			$image_placement_options = array(1=>'Left',2=>'Right',3=>'Span');
			$form->addElement('select', 'image_placement', 'Select Image Placement: ', $image_placement_options);

			$form->addElement('html', '
				<img src="'.$g['page']['images'].'/icon_img_left.gif" width="10" height="9">
				<img src="'.$g['page']['images'].'/icon_img_right.gif" width="10" height="9">
				<img src="'.$g['page']['images'].'/icon_img_span.gif" width="10" height="9">
				</p>
				<p>
			');
		}

		$form->addElement('image', 'btnAddImage', $g['page']['buttons'].'/btn-uploadfile.gif');

		$form->addElement('html','
		</p>
		</div>
				</td>
			</tr>

		');

		/////////////////////////////////////////////////////////////////////////
		# BEGIN Sortable Settings #
		/////////////////////////////////////////////////////////////////////////

		require_once 'Simple/Ajax/Sortable.php';
		session_start();
		$_SESSION['sortable_table'] = $image_table;
		$sortable_images = new simple_ajax_sortable($db, $_SESSION['sortable_table']);
		// javascript
		$g['page']['js_top'] .= $sortable_images->getJS(true);

	}

?>
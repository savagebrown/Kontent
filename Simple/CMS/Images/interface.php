<?php

	require_once 'Includes/Configuration.php';
	require_once 'Simple/Image/NewSize.php';
	require_once 'Simple/Image/Watermark.php';
	require_once 'Simple/Ajax/Sortables.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default variables #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET[$item_id_label])) && trim($_GET[$item_id_label]) > 0) {
		$_SESSION[$item_id_label] = $_GET[$item_id_label];
	}

	if (ctype_digit(trim($_SESSION[$item_id_label])) && trim($_SESSION[$item_id_label]) > 0) {
		$current_item = $_SESSION[$item_id_label];

		// Image path default
		if ($item_image_path_id) {
			$item_image_path = $item_image_path.'/'.$current_item;
		}

		// Parent title default
		$item_parent_field_name = ($item_parent_field_name)?$item_parent_field_name:'title';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Image #
	/////////////////////////////////////////////////////////////////////////

		$delete_all_images_for = '';
		$redirect_after = true;
		include 'delete.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

		$display_message = setDisplayMessage($display_message, 'updated', 'The image information has been updated successfully.');
		$display_message = setDisplayMessage($display_message, 'added', 'A new '.$item_display_title.' image has been added successfully.');
		$display_message = setDisplayMessage($display_message, 'reordered', $item_display_title.' images have been reordered successfully.');
		$display_message = setDisplayMessage($display_message, 'deleted', 'The '.$item_display_title.' image has been deleted successfully.');
		$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

		$item_id_label = strtolower(str_replace(" ","",$item_display_title)).'_id';

		$page_vars			  = build_page($db, $admin_page_id);
		$display_page_title	  = $page_vars['title'];
		$display_mainmenu	  = $page_vars['mainmenu'];
		$display_utility_menu = $page_vars['utilitymenu'];
		$display_submenu	  = $page_vars['submenu'];
		$g['page']['instructions'] .= $textile->textileThis($page_vars['instructions']);
		$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

		$body = $item_table.'_images';

		// This page uses lightbox for product image caption updates
		$g['page']['js_top'] .= <<<HTML

<script type="text/javascript" src="Scripts/Lightbox/Content.js"></script>

HTML;

		$g['page']['header'] .= <<<HTML

<link rel="stylesheet" href="css/lightbox.css" type="text/css" media="screen" />
<style type="text/css" title="text/css">
#lightbox { text-align:center; margin:-300px 0 0 -250px; }
#lightbox form { text-align:left; padding:10px; background-color:#f0f0f0; border:1px solid #ccc; }
#lightbox .close { display:block; padding:4px; background-color:orange; color:#fff; text-decoration:none; }
</style>

HTML;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Instantiate Quickform #
	/////////////////////////////////////////////////////////////////////////

		// Action Target
		$action_target = $_SERVER['PHP_SELF'].'?'.$item_id_label.'='.$current_item;
		// Instantiate QuickForm
		$form = new HTML_QuickForm('frm', 'post', $action_target);
		// Instantiate the renderer
		$renderer =& $form->defaultRenderer();
		// Clear QuickForm template
		$renderer->clearAllTemplates();
		// Define new templates
		$renderer->setFormTemplate('
			<div id="form_container">
				<form{attributes}>
					<table><tr><td>
						{content}
					</td></tr></table>
				</form>
			</div>
		');

		$renderer->setElementTemplate('
			<!-- BEGIN error --><div class="error">{error}</div><!-- END error -->
			<!-- BEGIN required --><em class="drf">*</em><!-- END required -->
			{label} <span class="trace">{element}</span>
		');

	// Alternate One --------------------------------------------------------

		$input_alt_html = <<<HTML
<p>
<!-- BEGIN error --><div class="error_full">{error}</div><!-- END error -->
<span id="indicator" style="display:none;float:right;"><img src="{$g['page']['images']}/indicator.gif" alt="Uploading File" border="0" /></span>
<!-- BEGIN required --><em class="drf">*</em><!-- END required -->
{label}</p>

<p>{element}</p>

HTML;

		$renderer->setElementTemplate($input_alt_html, 'image_upload');

	// Buttons --------------------------------------------------------------

		$btn_alt_html = <<<HTML

{element}

HTML;

		$renderer->setElementTemplate($btn_alt_html, 'btnAddImg');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Update interface #
	/////////////////////////////////////////////////////////////////////////

		$sql_item_images = <<<SQL
		SELECT	ii.id AS id,
				ii.$item_id_label AS $item_id_label,
				ii.placement AS placement,
				ii.caption AS caption,
				i.$item_parent_field_name AS $item_parent_field_name

		FROM	$item_images_table ii, $item_table i

		WHERE	ii.$item_id_label = i.id AND
				ii.$item_id_label = $current_item

		ORDER BY ii.rank
SQL;

		$q_item_images = $db->query($sql_item_images);
		if (DB::iserror($q_item_images)) { sb_error($q_item_images); }

		if ($q_item_images->numrows() > 0) {

			// We have images
			$count = 0;

			while ($r_item_images = $q_item_images->fetchrow(DB_FETCHMODE_ASSOC)) {

				$count++;
				$thisimgid = $r_item_images['id'];
				$thisimgcaption = $r_item_images['caption'];
				$thisimgfile = $current_item.'_'.$r_item_images['id'].'.jpg';

				// While we are while-ing lets build the sortable list
				$sortable_image_list .= "\t".
					'<li id="item_'.$thisimgid.'" style="background: url('.$item_image_path.'/s/'.$thisimgfile.') no-repeat center 5px;">
					<a class="lbOn edit_image" href="image_edit.php?table='.$item_images_table.'&file='.$thisimgid.'&field='.$item_id_label.'&path='.$item_image_path.'&inline='.$item_image_inline.'">edit</a>
					<a class="delete_image" href="javascript:;" onclick="cf=confirm(\'Are you sure you want to delete this '.$item_display_title.' image?\');if (cf)window.location=\'?deleteid='.$thisimgid.'\'; return false;">delete</a></li>'."\n";

				// These should be consistent throughout all the image items so we can
				// set them here
				$default_vars[$item_parent_field_name] = $r_item_images[$item_parent_field_name];
				$default_vars[$current_item] = $r_item_images[$item_id_label];
			}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Reorder form #
	/////////////////////////////////////////////////////////////////////////

			$sortable = new simple_ajax_sortables();
			$sortable->addList('sortable','sortableListOrder','li',"overlap:'horizontal',constraint:false");
			$sortable->debug = false;

			$sortable_image_list_ul = <<<HTML

<ul id="sortable">
	$sortable_image_list
</ul>
<div style="clear:left;"></div>

HTML;

			// Add form
			$reorder_list_extras = '<p><span class="like_h2">'.$item_display_title.' Images for "'.$default_vars[$item_parent_field_name].'"</span></p>'.$sortable_image_list_ul;
			$reorder_list =	 '<div id="sortable_list">'.$sortable->printForm($_SERVER['PHP_SELF'], 'post', 'sortableForm', $g['page']['buttons'].'/btn-savechanges.gif', $reorder_list_extras).'</div>';

			// Head javascript
			$g['page']['js_top'] .= $sortable->printTopJS();
			// Close Javascript
			$g['page']['js_bottom'] .= $sortable->printBottomJs();

		} else { // end if there are images

			$reorder_list = '';

			$sql_item_images = <<<SQL

SELECT id, title FROM $item_table WHERE id = $current_item

SQL;
			$q_item_images = $db->query($sql_item_images);
			if (DB::iserror($q_item_images)) { sb_error($q_item_images); }

			$r_item_images = $q_item_images->fetchrow(DB_FETCHMODE_ASSOC);

			$default_vars['title'] = $r_item_images['title'];
			$default_vars['article_id'] = $r_item_images['id'];

			if ($_GET['new']!=true){
				$form->addElement('html', '

<p><span class="like_h2">'.$item_display_title.' Images for "'.$default_vars['title'].'"</span></p>
<p class="note">No '.$item_display_title.' images yet. Go ahead and add one using the form below.</p>

				');
			} else {
				$form->addElement('html', '

			<div class="introduction">
				<p>Your album <strong>"'.$default_vars['title'].'"</strong> has been created.
				<br /><span>Start adding images</span></p>
			</div>

				');

			}

		} // end if there are no images

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Add Form #
	/////////////////////////////////////////////////////////////////////////

		$form->addElement('html', '
<table>
<tr><td colspan=2" class="green">
<div id="images_handle_add">Add a NEW image to the '.$item_display_title.' "'.$default_vars['title'].'"</div>


		');

		// Image upload
		$file_image =& $form->addElement('file','image_upload','Your Image File: <em>(Uploaded image will be automatically resized and saved.)</em>');
		// We want files of 5MB or less
		$max_size = 5123840;
		// Make sure that a file is uploaded
		$form->addRule('image_upload','Please select a file to upload','uploadedfile');
		// Have HTML_QuickForm test, after the file is uploaded, that it is less than 10MB
		$form->addRule('image_upload','Your file is too large. Your file must be less that 5MB (5123840 bytes)','maxfilesize',$max_size);
		// Mimetype
		$form->addRule('image_upload', 'Image must be a jpg', 'mimetype', $g['mimes']['jpeg']);
		// Path to image
		$form->addElement('hidden', 'image_path', $item_image_path.'/Originals');
		// Product ID
		$form->addElement('hidden', $item_id_label, $current_item);

		// Image Caption
		$attrs = array("rows"=>"2", "cols"=>"55");
		$form->addElement('textarea', 'image_caption', 'Caption: <em>(optional)</em><br />', $attrs);

		if ($item_image_inline) {
			$image_placement_options = array(1=>'Left align image',2=>'Right align image',3=>'Span the image');
			$form->addElement('select', 'image_placement', 'Image Placement:', $image_placement_options);
			$form->addElement('html', '

<img src="'.$g['page']['images'].'/icon_img_left.gif" width="10" height="9" alt="place image left" />
<img src="'.$g['page']['images'].'/icon_img_right.gif" width="10" height="9" alt="place image right" />
<img src="'.$g['page']['images'].'/icon_img_span.gif" width="10" height="9" alt="span image" />

			');
		} else {
			$form->addElement('hidden', 'image_placement', 0);
		}

		$form->addElement('html', '</td></tr><tr><td>');
		$form->addElement('image', 'btnAddImg', $g['page']['buttons'].'/btn-addnewimage.gif', array("value"=>"Add","onclick"=>"new Effect.Appear('indicator')"));
		$form->addElement('html', '</td><td class="right"><em class="drf">*</em> denotes required fields</td>');
		$form->addElement('html', '</tr></table>');
		$form->addElement('hidden', 'formcheck', $title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process reorder form #
	/////////////////////////////////////////////////////////////////////////

		if (isset($_POST['sortableListsSubmitted'])) {

			$orderArray = simple_ajax_sortables::getOrderArray($_POST['sortableListOrder'],'sortable');

			foreach($orderArray as $item) {
				$sql_update_reorder = "UPDATE ".$item_images_table." set rank = ".$item['order']." WHERE id = ".$item['element'];
				$q_update_reorder = $db->query($sql_update_reorder);
				if (DB::isError($q_update_reorder)) { sb_error($q_update_reorder); }

			}

			// Log activity
			if ($g['log']['active']==1) {
				$log = new LogManager();
				$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Reordered images for '.$default_vars[$item_parent_field_name]);
			}

			go_to(null, '?reordered=1');
		}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process image edit form #
	/////////////////////////////////////////////////////////////////////////

		if (isset($_POST['imageeditsubmitted'])) {
			$sql_update_image_caption = "UPDATE ".$item_images_table." set caption = '".safe_escape($_POST['image_caption'])."', placement = '".safe_escape($_POST['image_placement'])."' WHERE id = ".$_POST['image_id'];
			$q_update_image_caption = $db->query($sql_update_image_caption);
			if (DB::isError($q_update_image_caption)) { sb_error($q_update_image_caption); }

			// Log activity
			if ($g['log']['active']==1) {
				$log = new LogManager();
				$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated image information for '.$default_vars[$item_parent_field_name]);
			}

			go_to(null, '?updated=1');
		}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process add form #
	/////////////////////////////////////////////////////////////////////////

		if ($form->validate()) {

			$redirect_after = true;
			include 'add.php';

		}

		$form->setDefaults($default_vars);
		$display_form = $reorder_list.$form->toHtml();

	} else { // display choice or rediret with message

		go_to($_SERVER['HTTP_REFERER'],'?error=A '.$item_display_title.' selection must be made to view its images.');

	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());

?>
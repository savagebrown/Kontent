<?php

	require_once 'Includes/Configuration.php';
	require_once 'Simple/Ajax/Sortable.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Image #
	/////////////////////////////////////////////////////////////////////////

	include 'Simple/CMS/Images/form_delete.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Album #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		$sql_album ="SELECT title FROM albums WHERE id = ".$del_id;
		$q_album = $db->query($sql_album);
		if (DB::iserror($q_album)) { sb_error($q_album); }

		$r_album = $q_album->fetchrow(DB_FETCHMODE_ASSOC);
		$album_title = $r_album['title'];

		// Remove from db
		$sql = "DELETE FROM albums WHERE id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		$delete_all_images_for = $del_id;
		$redirect_after = false;
		include 'Simple/CMS/Images/form_delete.php';

		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Deleted the album '.$album_title);
		}

		// Redirect
		header ("Location: albums.php?deleted=".urlencode($album_title));
		exit;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Image Actions #
	/////////////////////////////////////////////////////////////////////////

	if ($display_album_images) {
		include 'Simple/CMS/Images/form_update.php';
		include 'Simple/CMS/Images/form_delete.php';
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'updated', 'The album <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', 'The album <strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:43;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_album_title  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$g['page']['instructions']	= '';

	$body = "gallery";

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default Variables #
	/////////////////////////////////////////////////////////////////////////

	if ($_GET['session'] == 'clear' || $_GET['new']==1) {
		$_SESSION['album_id'] = '';
	}

	if (ctype_digit(trim($_GET['album'])) && trim($_GET['album']) > 0) {
		$_SESSION['album_id'] = $_GET['album'];
	}

	if (ctype_digit(trim($_SESSION['album_id'])) && trim($_SESSION['album_id']) > 0) {

		$current_album = $_SESSION['album_id'];
		$idset = true;

		$g['page']['instructions']	= '<p><a class="neutral" href="album.php?session=clear"><img src="'.$g['page']['buttons'].'/btn-big-addnewalbum.png" alt="Add a new album" /></a></p>';

		$sql = <<<SQL

SELECT
	id, title, description, category_id, active
FROM
	albums
WHERE
	id = $current_album

SQL;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }


		$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
		$default_vars = array();
		foreach ($r AS $k => $v) {
			$default_vars[$k] = $v;
		}

	} else {
		$idset = false;
		$default_vars['active'] = 0;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

	// Form setup -----------------------------------------------------------

	if ($idset) {
		$target = $_SERVER['PHP_SELF'].'?album='.$current_album;
	} else {
		$target = $_SERVER['PHP_SELF'];
	}

	// Instantiate QuickForm
	$form = new HTML_QuickForm('frm', 'post', $target);

	// Default Template -----------------------------------------------------

	$renderer =& $form->defaultRenderer();
	$renderer->clearAllTemplates();
	$renderer->setFormTemplate($qf_container);
	$renderer->setHeaderTemplate($qf_header);
	$renderer->setElementTemplate($qf_element);
	$renderer->setElementTemplate($qf_button, 'btnUpdate');
	$renderer->setElementTemplate($qf_button, 'btnAdd');

	// Unique -----------------------------------------------------------------

	$renderer->setElementTemplate($qf_plain, 'btnAddImage');
	$renderer->setElementTemplate($qf_plain, 'image_caption');
	$renderer->setElementTemplate($qf_plain, 'image_placement');
	$renderer->setElementTemplate($qf_plain, 'image_upload');
	$renderer->setElementTemplate($qf_plain, 'template');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Fields #
	/////////////////////////////////////////////////////////////////////////

	// Header ---------------------------------------------------------------

	if ($idset) {
		// Header
		$display_content_title = '<a href="albums.php">Albums</a> &nbsp;&#x2192; You are updating "'.$default_vars['title'].'"';
	} else {
		// Header
		$display_content_title = '<div class="head-flag-links"><a href="albums.php">Cancel</a></div><a href="albums.php">Albums</a> &nbsp;&#x2192; Add a New Album';
	}

	// Album Title -----------------------------------------------------------

	$form->addElement('text', 'title', 'Album Name:', 'class=long');
	$form->addRule('title', 'Please provide a name for this album.', 'required');
	$form->addRule('title', 'Can not exceed 50 characters for the album name. Please try something shorter.', 'maxlength', 75);

	// Album Category -------------------------------------------------------

	$sql_category_id = <<<SQL

SELECT id, name FROM album_categories ORDER BY rank ASC

SQL;
	$q_category_id = $db->query($sql_category_id);
	if (DB::iserror($q_category_id)) { sb_error($q_category_id); }
	while ($r_category_id = $q_category_id->fetchrow(DB_FETCHMODE_ASSOC)) {
		$category_list[$r_category_id['id']] = $r_category_id['name'];
	}
	$form->addElement('select', 'category_id', 'Category:', $category_list);

	// Album Description ------------------------------------------------------------

	// Place a default row ammount
	$description_rows = ($description_rows) ? $description_rows:12;
	if ($g['xipe']['wysiwyg']==1) { 
		$attrs = array('id'=>'albumdescription', "cols"=>"55", "rows"=>$description_rows);
		$form->addElement('html', textarea_wysiwyg('albumdescription'));
	} else {
		$attrs = array("rows"=>$description_rows, "cols"=>"55");
	}
	$form->addElement('textarea', 'description', '<span class="highlight">'.$description_label.'</span>', $attrs);
	$form->addRule('copy', 'Please provide album description.', 'required');

	// Album Active ----------------------------------------------------------

	if ($default_vars['active']) {
		$form->addElement('checkbox', 'active', null, ' This album is active and available for the public to view. Uncheck to make album inactive.');
	} else {
		$form->addElement('checkbox', 'active', null, ' Check the box to make this album active and available for the public to view.');
	}

	// Images ---------------------------------------------------------------

	if ($display_album_images) {
		$image_owner_param = 'album';
		$image_owner_id_field = 'album_id';
		$image_owner_current_id = $current_album;
		$image_owner_album = 'album.php';
		$image_table = 'album_images';
		$image_path = '../Images/Gallery/Albums/'.$current_album;
		$image_path_seg = 1;
		$image_inline = 0;
		include 'Simple/CMS/Images/form_row.php';
	}

	// BUTTON ---------------------------------------------------------------

	if ($idset) {
		// Update info button
		//$form->addElement('image', 'btnPreview', $g['page']['buttons'].'/btn-preview.gif');
		$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-updatealbuminfo.gif');
		// Album ID
		$form->addElement('hidden', 'id');
	} else {
		$form->addElement('image', 'btnAdd', $g['page']['buttons'].'/btn-addnewalbum.gif');
	}
	// Form protection
	$form->addElement('hidden', 'formcheck', $display_album_title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

	if ($idset && !$default_vars['protect']) {
		$linkstuff = js_confirm('?deleteid='.$default_vars['id'],
								'Delete this album',
								'Are you sure you want to delete the album -> '.$default_vars['title'].'?',
								'Delete '.$default_vars['title'],
								'attention');
		$display_content_title = '<div class="head-flag-links"><a href="albums.php">Cancel</a> | '.$linkstuff.'</div>'.$display_content_title;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process form #
	/////////////////////////////////////////////////////////////////////////

	// Keeps errors from displaying if form coming in from else where
	if ($form->getSubmitValue('formcheck') == $display_album_title) {

		if ($form->validate()) {
			// write information to database
			$submit_vars = array(
							'id'	   => safe_escape($form->getSubmitValue('id'),		 'int', true),
							'title'	   => safe_escape($form->getSubmitValue('title'),	 'str', true),
							'category_id'	=> safe_escape($form->getSubmitValue('category_id'),	 'int', true),
							'description' => safe_escape($form->getSubmitValue('description'), 'str', true),
							'active'   => safe_escape($form->getSubmitValue('active'),	 'chk', true),
							'dateCrtd'	=> date('Y-m-d'),
							'dateMdfd'	=> date('y-m-d')
							);
	// Add image ------------------------------------------------------------

			if ($form->getSubmitValue('btnAddImage_x')) {
				$current_item = $current_album;
				include 'Simple/CMS/Images/form_insert.php';
				go_to('album.php', '?album='.$submit_vars['id'].'&addedimage=1');
			}

	// Update ---------------------------------------------------------------

			if ($form->getSubmitValue('btnUpdate_x')) {

				$sql_u = <<<SQL

UPDATE albums SET
	title	 = {$submit_vars['title']},
	category_id	 = {$submit_vars['category_id']},
	description	 = {$submit_vars['description']},
	active	 = {$submit_vars['active']},
	dateMdfd = {$submit_vars['dateMdfd']}
WHERE
	id = {$submit_vars['id']}

SQL;

				$q_u = $db->query($sql_u);
				if (DB::isError($q_u)) { sb_error($q_u); }

				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the album '.$form->getSubmitValue('title'));
				}

				go_to(null, '?album='.$submit_vars['id'].'&updated='.urlencode(stripslashes($submit_vars['title'])));

			}

	// Insert ---------------------------------------------------------------

			if ($form->getSubmitValue('btnAdd_x')) {

				$sql_add_album = <<<SQL

INSERT INTO albums	(
	title, category_id, description, active, dateCrtd
					)
VALUES 	(
	{$submit_vars['title']},
	{$submit_vars['category_id']},
	{$submit_vars['description']},
	{$submit_vars['active']},
	{$submit_vars['dateCrtd']}
		)
SQL;
				$q_add_album = $db->query($sql_add_album);
				if (DB::isError($q_add_album)) { sb_error($q_add_album); }

				// Get new id
				$new_album_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM albums" );
				
				// Create Image Directories
				
				foreach ($item_image_dir AS $v) {
					createFolder('../Images/Gallery/Albums/'.$new_album_id.'/'.$v);	
				}
				
				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added the album '.$form->getSubmitValue('title'));
				}

				go_to(null, '?album='.$new_album_id.'&added='.urlencode(stripslashes($form->getSubmitValue('title'))));

			}

		}

	}

	$form->setDefaults($default_vars);
	$display_form = $form->toHtml();

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
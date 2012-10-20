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
		
		// Remove all albums from categories
		$sql = "DELETE FROM albums2categories WHERE album_id = {$del_id}";
		$q = $db->query($sql);
		if (DB::iserror($q)) { sb_error($q); }
		
		// Set variable for form delete
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
		
		$sql_category_defaults ="SELECT category_id FROM albums2categories WHERE album_id = ".$default_vars['id'];
		$q_category_defaults = $db->query($sql_category_defaults);
		if (DB::iserror($q_category_defaults)) { sb_error($q_category_defaults); }

		while ($r_category_defaults = $q_category_defaults->fetchrow(DB_FETCHMODE_ASSOC)) {
			$default_vars['multiple_album_'.$r_category_defaults['category_id']] = 1;
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
/*
	$sql_category_id = <<<SQL

SELECT id, name FROM album_categories ORDER BY rank ASC

SQL;
	$q_category_id = $db->query($sql_category_id);
	if (DB::iserror($q_category_id)) { sb_error($q_category_id); }
	while ($r_category_id = $q_category_id->fetchrow(DB_FETCHMODE_ASSOC)) {
		$category_list[$r_category_id['id']] = $r_category_id['name'];
	}
	$form->addElement('select', 'category_id', 'Category:', $category_list);
*/
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
	
	
	// Categories ----------------------------------------------------------
	
	$sql_album_categories = 'SELECT id, name FROM album_categories ORDER BY rank ASC';
	$q_album_categories = $db->query($sql_album_categories);
	if (DB::isError($q_album_categories)) { sb_error($q_album_categories); }

	$category_list = array();
	if ($q_album_categories->numrows()>0) {
		while ($r_album_category = $q_album_categories->fetchrow(DB_FETCHMODE_ASSOC)) {

			$cat_checked = ($default_vars['multiple_album_'.$r_album_category['id']] == 1)?' checked="checked':'';
			$more_album_cat_list[$r_album_category['id']] = <<<HTML
			<input name="multiple_album_{$r_album_category['id']}" type="checkbox" value="1" id="multiple_album_{$r_album_category['id']}"{$cat_checked} /><label for="multiple_album_{$r_album_category['id']}"> <strong>{$r_album_category['name']}</strong></label>
HTML;
		}
			
		$category_columns = 1;
		$form->addElement('html', '<tr><td class="label">Album Categories:</td><td>');
		$form->addElement('html', "\n\n".'<div id="list-option">');
		$form->addElement('html', "\n\n".'<table>');
		$form->addElement('html',"\n\n".table_array($category_columns, $more_album_cat_list,true));
		$form->addElement('html',"\n\n".'</table></div></td></tr>');

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
							//'category_id'	=> safe_escape($form->getSubmitValue('category_id'),	 'int', true),
							'description' => safe_escape($form->getSubmitValue('description'), 'str', true),
							'active'   => safe_escape($form->getSubmitValue('active'),	 'chk', true)
							//'dateCrtd'	=> safe_escape(date('Y-m-d'), 'date', true),
							//'dateMdfd'	=> safe_escape(date('y-m-d'), 'date', true)
							);
	// Add image ------------------------------------------------------------

			if ($form->getSubmitValue('btnAddImage_x')) {
				$current_item = $current_album;
				include 'Simple/CMS/Images/form_insert.php';
				go_to('album.php', '?album='.$submit_vars['id'].'&addedimage=1');
			}

	// Update ---------------------------------------------------------------

			if ($form->getSubmitValue('btnUpdate_x')) {
				//category_id	 = {$submit_vars['category_id']},
				$sql_u = <<<SQL

UPDATE albums SET
	title	 = {$submit_vars['title']},
	description	 = {$submit_vars['description']},
	active	 = {$submit_vars['active']},
	dateMdfd = NOW()
WHERE
	id = {$submit_vars['id']}

SQL;

				$q_u = $db->query($sql_u);
				if (DB::isError($q_u)) { sb_error($q_u); }
				
				$sql = <<<SQL

SELECT 
	*
FROM 
	albums2categories
WHERE 
	album_id = {$submit_vars['id']}
ORDER BY 
	rank

SQL;
				
				$q = $db->query($sql);
				if (DB::iserror($q)) { sb_error($q); }
				
				$catList = array();
				
				while($r = $q->fetchrow(DB_FETCHMODE_ASSOC)) {
					$catList[$r['category_id']] = $r['rank'];
				}
				
				// Delete all existing categories
				//$sql_del_categories = "DELETE FROM albums2categories WHERE album_id = ".$submit_vars['id'];
				//$q_del_categories = $db->query($sql_del_categories);
				//if (DB::isError($q_del_categories)) { sb_error($q_del_categories); }
				
		// Categories -----------------------------------------------------------

				$sql_add_category ="SELECT id, name FROM album_categories";
				$q_add_category = $db->query($sql_add_category);
				if (DB::iserror($q_add_category)) { sb_error($q_add_category); }

				while ($r_add_category = $q_add_category->fetchrow(DB_FETCHMODE_ASSOC)) {
				
					if (isset($catList[$r_add_category['id']]) && !isset($_POST['multiple_album_'.$r_add_category['id']])) {
						// REMOVE FROM LIST & REORDER
						
						$sql = <<<SQL
DELETE FROM
	albums2categories
WHERE
	album_id = {$submit_vars['id']}
AND
	category_id = {$r_add_category['id']}				
SQL;
						
						$q = $db->query($sql);
						if (DB::iserror($q)) { sb_error($q); }
						
						$sql = <<<SQL
UPDATE
	albums2categories
SET 
	rank = rank - 1
WHERE
	album_id = {$submit_vars['id']}
AND
	rank > {$catList[$r_add_category['id']]}				
SQL;
										
						$q = $db->query($sql);
						if (DB::iserror($q)) { sb_error($q); }
									
					} else if (!isset($catList[$r_add_category['id']]) && isset($_POST['multiple_album_'.$r_add_category['id']])) {
						// ADD TO END OF LIST
						
						$sql = <<<SQL
INSERT INTO
	albums2categories (album_id, category_id, rank)
VALUES ({$submit_vars['id']},
		{$r_add_category['id']},
		IFNULL((SELECT X.rank FROM albums2categories AS X WHERE X.album_id = '{$submit_vars['id']}' ORDER BY X.rank DESC LIMIT 1)+1, 0))
SQL;
						
						$q = $db->query($sql);
						if (DB::iserror($q)) { sb_error($q); }
					}
				}

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
	title, description, active, dateCrtd
					)
VALUES 	(
	{$submit_vars['title']},
	{$submit_vars['description']},
	{$submit_vars['active']},
	NOW()
		)
SQL;
				$q_add_album = $db->query($sql_add_album);
				if (DB::isError($q_add_album)) { sb_error($q_add_album); }

				// Get new id
				$new_album_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM albums" );
		
				// Categories -----------------------------------------------------------

				$sql_add_category ="SELECT id, name FROM album_categories";
				$q_add_category = $db->query($sql_add_category);
				if (DB::iserror($q_add_category)) { sb_error($q_add_category); }

				while ($r_add_category = $q_add_category->fetchrow(DB_FETCHMODE_ASSOC)) {

					if ($_POST['multiple_album_'.$r_add_category['id']] == 1) {

						$sql_add_multiple_category = <<<SQL
INSERT INTO 
	albums2categories (album_id, category_id, rank)
VALUES ({$new_album_id},
		{$r_add_category['id']},
		IFNULL((SELECT X.rank FROM albums2categories AS X WHERE X.album_id = '{$new_album_id}' ORDER BY X.rank DESC LIMIT 1)+1, 0))
SQL;

						$q_add_multiple_category = $db->query($sql_add_multiple_category);
						if (DB::isError($q_add_multiple_category)) { sb_error($q_add_multiple_category); }

					}

				}
				
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
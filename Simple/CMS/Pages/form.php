<?php

	require_once 'Includes/Configuration.php';
	require_once 'Simple/Ajax/Sortable.php';
	
	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Image #
	/////////////////////////////////////////////////////////////////////////
		
	include 'Simple/CMS/Images/form_delete.php';
	
	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Page #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		$sql_page ="SELECT title FROM pages WHERE id = ".$del_id;
		$q_page = $db->query($sql_page);
		if (DB::iserror($q_page)) { sb_error($q_page); }

		$r_page = $q_page->fetchrow(DB_FETCHMODE_ASSOC);
		$page_title = $r_page['title'];

		// Remove from db
		$sql = "DELETE FROM pages WHERE id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		// Sidebar association
		$sql = "DELETE FROM pages2sidebars WHERE page_id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		$delete_all_images_for = $del_id;
		$redirect_after = false;
		include 'Simple/CMS/Images/form_delete.php';

		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Deleted the page '.$page_title);
		}

		// Redirect
		header ("Location: pages.php?deleted=".urlencode($page_title));
		exit;
	}
	
	/////////////////////////////////////////////////////////////////////////
	# BEGIN Image Actions #
	/////////////////////////////////////////////////////////////////////////

	if ($display_page_images) {
		include 'Simple/CMS/Images/form_update.php';
		include 'Simple/CMS/Images/form_delete.php';
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'updated', 'The page <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', 'The page <strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:20;

	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$g['page']['instructions']  = '';

	$body = "manage_page";

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default Variables #
	/////////////////////////////////////////////////////////////////////////

	if ($_GET['session'] == 'clear' || $_GET['new']==1) {
		$_SESSION['page_id'] = '';
	}

	if (ctype_digit(trim($_GET['page'])) && trim($_GET['page']) > 0) {
		$_SESSION['page_id'] = $_GET['page'];
	}

	if (ctype_digit(trim($_SESSION['page_id'])) && trim($_SESSION['page_id']) > 0) {

		$current_page = $_SESSION['page_id'];
		$idset = true;

		$g['page']['instructions']  = '<p><a class="neutral" href="page.php?session=clear"><img src="'.$g['page']['buttons'].'/btn-big-addnewpage.png" alt="Add a new page" /></a></p>';
		$g['page']['instructions'] .= ($page_html_option)?'<p><a class="neutral" href="page.php?html=raw"><img src="'.$g['page']['buttons'].'/btn-big-addhtmlpage.png" alt="Add a HTML page" /></a></p>':'';

		$q = $db->query("SELECT * FROM pages WHERE id = $current_page");
		if (DB::isError($q)) { sb_error($q); }


		$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
		$default_vars = array();
		foreach ($r AS $k => $v) {
			$default_vars[$k] = $v;
		}

	// Sidebar Defaults -----------------------------------------------------

		$sql_sidebar_defaults ="SELECT sidebar_id FROM pages2sidebars WHERE page_id = ".$default_vars['id'];
		$q_sidebar_defaults = $db->query($sql_sidebar_defaults);
		if (DB::iserror($q_sidebar_defaults)) { sb_error($q_sidebar_defaults); }

		while ($r_sidebar_defaults = $q_sidebar_defaults->fetchrow(DB_FETCHMODE_ASSOC)) {
			$default_vars['multiple_article_sidebar_'.$r_sidebar_defaults['sidebar_id']] = 1;
		}

	} else {

		$idset = false;
		$default_vars['active'] = 1;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

	// Form setup -----------------------------------------------------------

	if ($idset) {
		$target = $_SERVER['PHP_SELF'].'?page='.$current_page;
	} else {
		$target = $_SERVER['PHP_SELF'];
	}

	// Instantiate QuickForm --------------------------------------------------

	$form = new HTML_QuickForm('frm', 'post', $target);

	// Default Templates ------------------------------------------------------

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
	$renderer->setElementTemplate($qf_plain, 'titlebar');
	$renderer->setElementTemplate($qf_plain, 'metadescription');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Fields #
	/////////////////////////////////////////////////////////////////////////

	// HTML page creation. Set all paramaters to false ----------------------

	if ($_GET['html'] == 'raw' || $default_vars['html'] || $form->getSubmitValue('html')!='') {

		$html_page = true;

		$page_copy_alt_display = false;
		$page_sidebar_template_option = false;
		$display_page_images = false;
		$display_page_sidebars = false;
		$page_sidebar_template_option = false;
		$display_SEO = false;

	}

	// Header -----------------------------------------------------------------

	if ($idset) {
		// Header
		$display_content_title = '<a href="pages.php">Pages</a> &nbsp;&rarr;&nbsp;You are updating "'.$default_vars['title'].'"';
	} else {
		// Header
		$display_content_title = '<div class="head-flag-links"><a href="pages.php">Cancel</a></div><a href="pages.php">Pages</a> &nbsp;&rarr;&nbsp;Add a New sub page';
	}

	// Page Title -------------------------------------------------------------

	$form->addElement('text', 'title', 'Page Title:', 'class=long');
	$form->addRule('title', 'Please provide a title for this page.', 'required');
	$form->addRule('title', 'Can not exceed 50 characters for the title. Please try something shorter.', 'maxlength', 75);

	// Page Parent ------------------------------------------------------------

	if ($idset==false || ($idset==true && $default_vars['parent'] != 0)) {
		//print_r(getParentList($current_page));
		$parent_list = getParentList($current_page, $default_vars['parent'], $page_depth, false);
		$form->addElement('select', 'parent', 'Parent page:', $parent_list);
	} else {
		$form->addElement('hidden', 'parent', 0);
	}

	// Page Body --------------------------------------------------------------

	if ($html_page) {
		$pagecopy_attrs = array("rows"=>25, 'class'=>'page-html', "cols"=>"55");
		$form->addElement('textarea', 'html', 'Page HTML<br><em>(This field accepts full html page code)</em>', $pagecopy_attrs);
		$form->addRule('html', 'Please provide html code for this page.', 'required');
	} else {		
		// Place a default row ammount
		$page_copy_rows = ($page_copy_rows) ? $page_copy_rows:12;
		if ($g['xipe']['wysiwyg']==1) { 
			$pagecopy_attrs = array('id'=>'pagecopy', "cols"=>"55", "rows"=>$page_copy_rows);
			$form->addElement('html', textarea_wysiwyg('pagecopy'));
		} else {
			$pagecopy_attrs = array("rows"=>$page_copy_rows, "cols"=>"55");
		}
		$form->addElement('textarea', 'copy', '<span class="highlight">'.$page_copy_label.'</span>', $pagecopy_attrs);
		$form->addRule('copy', 'Please provide copy for this page.', 'required');
	}

	// Page Copy Alt --------------------------------------------------------

	if ($page_copy_alt_display) {
		if ($g['xipe']['wysiwyg']==1 && !$page_copy_alt_wysiwyg_override) {
			$pagealtcopy_attrs = array('id'=>'pagecopyalt', "cols"=>"55", "rows"=>$page_copy_alt_rows);
			$form->addElement('html', textarea_wysiwyg('pagecopyalt'));
		} else {
			$pagealtcopy_attrs = array("rows"=>$page_copy_alt_rows, "cols"=>"55");
		}
		
		$form->addElement('textarea', 'copy_alt', '<span class="highlight">'.$page_copy_alt_label.'</span>', $pagealtcopy_attrs);
	}

	// Page Active ----------------------------------------------------------

	if ($default_vars['parent'] > 0) {
		$form->addElement('checkbox', 'active', null, ' Page is active.');
	} else {
		$form->addElement('hidden', 'active');
	}

	// Images ---------------------------------------------------------------

	if ($display_page_images) {
		$image_owner_param = 'page';
		$image_owner_id_field = 'page_id';
		$image_owner_current_id = $current_page;
		$image_owner_page = 'page.php';
		$image_owner_title = $default_vars['title'];
		$image_table = 'page_images';
		$image_path = '../Images/Inline';
		$image_inline = 1;
		include 'Simple/CMS/Images/form_row.php';
	}

	// Page Sidebars --------------------------------------------------------

	if ($display_page_sidebars) {

		$sql_sidebar = 'SELECT id, title, protect FROM page_sidebars ORDER BY rank ASC';
		$q_sidebar = $db->query($sql_sidebar);
		if (DB::isError($q_sidebar)) { sb_error($q_sidebar); }

		$sidebar_list = array();
		if ($q_sidebar->numrows()>0) {
			while ($r_sidebar = $q_sidebar->fetchrow(DB_FETCHMODE_ASSOC)) {

				$sidebar_checked = ($default_vars['multiple_article_sidebar_'.$r_sidebar['id']] == 1)?' checked="checked':'';
				if ($r_sidebar['protect'] != 1) {
					$more_sidebar_list[$r_sidebar['id']] = '<input name="multiple_article_sidebar_'.$r_sidebar['id'].'" type="checkbox" value="1" id="multiple_article_sidebar_'.$r_sidebar['id'].'"'.$sidebar_checked.' /><label for="multiple_article_sidebar_'.$r_sidebar['id'].'"> '.$r_sidebar['title'].'</label>';
				} else {
					$more_sidebar_list[$r_sidebar['id']] = '<input name="multiple_article_sidebar_'.$r_sidebar['id'].'" type="checkbox" value="1" id="multiple_article_sidebar_'.$r_sidebar['id'].'"'.$sidebar_checked.' /><label for="multiple_article_sidebar_'.$r_sidebar['id'].'"> <strong>'.$r_sidebar['title'].'</strong></label>';
				}
			}

			$sidebar_columns = 1;
			$form->addElement('html', '<tr><td class="label">Page Sidebars:</td><td>');
			$form->addElement('html', "\n\n".'<div id="list-option">');
			$form->addElement('html', "\n\n".'<table>');
			if ($page_sidebar_template_option) {
				$form->addElement('html', "\n\n".'<tr><td class="template" colspan="'.$sidebar_columns.'">Placement: ');
				$form->addElement('select', 'template', 'Sidebar Placement: ', array(1=>'Display sidebar on left',2=>'Display sidebar on right'));
				$form->addElement('html', "\n\n".'</td></tr>');
			}
			$form->addElement('html',"\n\n".table_array($sidebar_columns, $more_sidebar_list,true));
			$form->addElement('html',"\n\n".'</table></div></td></tr>');

		}
	}

	// SEO Options ----------------------------------------------------------
	
	if ($display_SEO) {
		
		if ($default_vars['titlebar'] || $default_vars['metadescription']) {
			$seo_open_display_on = 'none';
			$seo_close_display_on = 'block';
		} else {
			$seo_open_display_on = 'block';
			$seo_close_display_on = 'none';
		}

		$form->addElement('html', '<tr><td class="label">SEO Options:</td><td>');
		$seo_open = <<<HTML

<div id="seo_open" style="display:$seo_open_display_on">
	<a href="javascript:;" onclick="kSlideToggle('seo_close','seo_open');return false;">Override Titlebar and default Meta Description for this page</a>
</div>

HTML;
		$seo_close = <<<HTML

<div id="seo_close" style="display:$seo_close_display_on;">
	<a class="btn-close" href="javascript:;" onclick="kSlideToggle('seo_open','seo_close');return false;"><img src="Images/cancel-grey.png" alt="" /></a>
	<div class="seo_close_inner">
		<p><em>Overriding the Titlebar and meta description for this page allows you to add extra keywords without compromising readability on what is displayed to the user. It will ultimately help with your search engine optimization (SEO) as well. If left blank defaults will be used.</em></p>		
		<p>Titlebar:<br />

HTML;
		$form->addElement('html', $seo_open);
		$form->addElement('html', $seo_close);

		// Page TitleBar ----------------------------------------------------

		$form->addElement('text', 'titlebar', 'Title Bar:', 'class=long');

		// Page Meta Description --------------------------------------------
	
		$form->addElement('html', '</p><p style="margin:0;">Meta description:<br />');

		$form->addElement('textarea', 'metadescription', 'Meta Description', array("rows"=>2, "cols"=>"55"));

		$form->addElement('html', '</p></div></div></td></tr>');
	}

	// BUTTON ---------------------------------------------------------------

	if ($idset) {
		// Update info button
		//$form->addElement('image', 'btnPreview', $g['page']['buttons'].'/btn-preview.gif');
		$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-updatepageinfo.gif');
		// Page ID
		$form->addElement('hidden', 'id');
	} else {
		$form->addElement('image', 'btnAdd', $g['page']['buttons'].'/btn-addnewpage.gif');
	}
	// Form protection
	$form->addElement('hidden', 'formcheck', $display_page_title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

	if ($idset && !$default_vars['protect']) {
		$linkstuff = js_confirm('?deleteid='.$default_vars['id'],
								'Delete this page',
								'Are you sure you want to delete the page -> '.$default_vars['title'].'?',
								'Delete '.$default_vars['title'],
								'attention');
		$display_content_title = '<div class="head-flag-links"><a href="pages.php">Cancel</a> | '.$linkstuff.'</div>'.$display_content_title;
	}

	if ($default_vars['protect']) {
		$display_content_title = '<div class="head-flag-links protected"><a href="pages.php">Cancel</a> | Protected page</div>'.$display_content_title;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process form #
	/////////////////////////////////////////////////////////////////////////

	// Keeps errors from displaying if form coming in from else where
	if ($form->getSubmitValue('formcheck') == $display_page_title) {

		if ($form->validate()) {
			// write information to database
			$submit_vars = array(
							'id'				=> safe_escape($form->getSubmitValue('id'),					'int', true),
							'title'				=> safe_escape($form->getSubmitValue('title'),				'str', true),
							'parent'			=> safe_escape($form->getSubmitValue('parent'),				'int', true),
							'template'			=> safe_escape($form->getSubmitValue('template'),			'int', true),
							'copy'				=> safe_escape($form->getSubmitValue('copy'),				'wysiwyg', true),
							'copy_alt'			=> safe_escape($form->getSubmitValue('copy_alt'),			($page_copy_alt_wysiwyg_override ? 'textarea' : 'wysiwyg'), true),
							'titlebar'			=> safe_escape($form->getSubmitValue('titlebar'),			'str', true),
							'html'				=> safe_escape($form->getSubmitValue('html'),				'str', true),
							'metadescription'	=> safe_escape($form->getSubmitValue('metadescription'),	'str', true),
							'active'			=> safe_escape($form->getSubmitValue('active'),				'chk', true)
							);

	// Add image ------------------------------------------------------------
	
			if ($form->getSubmitValue('btnAddImage_x')) {
				$current_item = $current_page;
				include 'Simple/CMS/Images/form_insert.php';
				go_to('page.php', '?page='.$submit_vars['id'].'&addedimage=1');
			}
			
	// Update ---------------------------------------------------------------

			if ($form->getSubmitValue('btnUpdate_x')) {

				$sql_u = "UPDATE pages SET
								title 			= ".$submit_vars['title'].",
								parent			= ".$submit_vars['parent'].",
								copy			= ".$submit_vars['copy'].",
								copy_alt		= ".$submit_vars['copy_alt'].",
								html 			= ".$submit_vars['html'].",
								template		= ".$submit_vars['template'].",
								titlebar		= ".$submit_vars['titlebar'].",
								metadescription = ".$submit_vars['metadescription'].",
								active			= ".$submit_vars['active']."
						   WHERE
								id				= ".$submit_vars['id'];

				$q_u = $db->query($sql_u);
				if (DB::isError($q_u)) { sb_error($q_u); }

				// Delete all existing categories
				$sql_del_categories = "DELETE FROM pages2sidebars WHERE page_id = ".$submit_vars['id'];
				$q_del_categories = $db->query($sql_del_categories);
				if (DB::isError($q_del_categories)) { sb_error($q_del_categories); }

	// Categories -----------------------------------------------------------

				$sql_add_sidebar ="SELECT id, title FROM page_sidebars";
				$q_add_sidebar = $db->query($sql_add_sidebar);
				if (DB::iserror($q_add_sidebar)) { sb_error($q_add_sidebar); }

				while ($r_add_sidebar = $q_add_sidebar->fetchrow(DB_FETCHMODE_ASSOC)) {

					if ($_POST['multiple_article_sidebar_'.$r_add_sidebar['id']] == 1) {

						$sql_add_multiple_sidebar = "INSERT INTO pages2sidebars (page_id, sidebar_id)
														  VALUES (".$submit_vars['id'].",
																  ".$r_add_sidebar['id'].")";
						$q_add_multiple_sidebar = $db->query($sql_add_multiple_sidebar);
						if (DB::isError($q_add_multiple_sidebar)) { sb_error($q_add_multiple_sidebar); }

					}

				}

				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the page '.$form->getSubmitValue('title'));
				}

				go_to(null, '?page='.$submit_vars['id'].'&updated='.urlencode(stripslashes($submit_vars['title'])));

			}

	// Insert ---------------------------------------------------------------

			if ($form->getSubmitValue('btnAdd_x')) {

				$sql_add_page = "INSERT INTO pages (title, parent, copy, copy_alt, html, template, titlebar, metadescription, active)
								 VALUES (".$submit_vars['title'].",
										 ".$submit_vars['parent'].",
										 ".$submit_vars['copy'].",
										 ".$submit_vars['copy_alt'].",
										 ".$submit_vars['html'].",
										 ".$submit_vars['template'].",
										 ".$submit_vars['titlebar'].",
										 ".$submit_vars['metadescription'].",
										 ".$submit_vars['active'].")";
				$q_add_page = $db->query($sql_add_page);
				if (DB::isError($q_add_page)) { sb_error($q_add_page); }

				// Get new id
				$new_page_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM pages" );

	// Sidebars -------------------------------------------------------------

				$sql_add_sidebar ="SELECT id, title FROM page_sidebars";
				$q_add_sidebar = $db->query($sql_add_sidebar);
				if (DB::iserror($q_add_sidebar)) { sb_error($q_add_sidebar); }

				while ($r_add_sidebar = $q_add_sidebar->fetchrow(DB_FETCHMODE_ASSOC)) {

					if ($_POST['multiple_article_sidebar_'.$r_add_sidebar['id']] == 1) {

						$sql_add_multiple_sidebar = "INSERT INTO pages2sidebars (page_id, sidebar_id)
														  VALUES (".$new_page_id.",
																  ".$r_add_sidebar['id'].")";
						$q_add_multiple_sidebar = $db->query($sql_add_multiple_sidebar);
						if (DB::isError($q_add_multiple_sidebar)) { sb_error($q_add_multiple_sidebar); }

					}

				}

				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added the page '.$form->getSubmitValue('title'));
				}

				go_to(null, '?page='.$new_page_id.'&added='.urlencode(stripslashes($form->getSubmitValue('title'))));

			}

		}

	}

	$form->setDefaults($default_vars);
	$display_form = $form->toHtml().$subpages_list;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
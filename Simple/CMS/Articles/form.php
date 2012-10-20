<?php

	require_once 'Includes/Configuration.php';
	require_once 'Simple/Image/NewSize.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Article #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		$sql_info ="SELECT title FROM ".$article_settings['table']." WHERE id = ".$del_id;
		$q_info = $db->query($sql_info);
		if (DB::iserror($q_info)) {
			sb_error($q_info);
		}
		$r_info = $q_info->fetchrow(DB_FETCHMODE_ASSOC);

		if ($q_info->numrows()==1) {
			$article_title = $r_info['title'];

			// Remove from db
			$sql = "DELETE FROM ".$article_settings['table']." WHERE id = ".$del_id;
			$q = $db->query($sql);
			if (DB::isError($q)) { sb_error($q); }

			// Get image info
			$sql_img ="SELECT id, article_id FROM ".$article_settings['table_images']." WHERE article_id = ".$del_id;
			$q_img = $db->query($sql_img);
			if (DB::isError($q_img)) { sb_error($q_img); }

			while($r_img = $q_img->fetchrow(DB_FETCHMODE_ASSOC)) {

				$article_image =  $r_img['article_id'].'_'.$r_img['id'].'.jpg';

				// Remove from db
				$sql = "DELETE FROM ".$article_settings['table_images']." WHERE article_id = ".$del_id;
				$q = $db->query($sql);
				if (DB::isError($q)) { sb_error($q); }

				// Remove small thumbnail file
				@unlink($article_settings['path_images'].'/s/'.$article_image);
				// Remove medium thumbnail file
				@unlink($article_settings['path_images'].'/m/'.$article_image);
				// Remove medium square thumbnail file
				@unlink($article_settings['path_images'].'/ms/'.$article_image);
				// Remove large image file
				@unlink($article_settings['path_images'].'/l/'.$article_image);
				// Remove medium thumbnail file
				@unlink($article_settings['path_images'].'/Originals/'.$article_image);

				// Delete articles2categories
				$sql_categories = "DELETE FROM ".$article_settings['table_2categories']." WHERE article_id = ".$del_id;
				$q_categories = $db->query($sql_categories);
				if (DB::isError($q_categories)) { sb_error($q_categories); }
			}

			// Delete articles2comments
			$sql_comments = "DELETE FROM ".$article_settings['table_comments']." WHERE article_id = ".$del_id;
			$q_comments = $db->query($sql_comments);
			if (DB::isError($q_comments)) { sb_error($q_comments); }

			// Redirect
			go_to($article_settings['path_list'], '?deleted='.urlencode($article_title));
		} else {
			go_to($article_settings['path_list'], '?error=There was an error deleting the
				specified '.$article_settings['title_singular'].'. Please try again. If you should continue
				to have problems please contact the <a href="mailto:'.$g['administrator']['email'].'">website administrator</a> (<strong>'.$g['administrator']['phone'].'</strong>).');
		}
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Image Actions #
	/////////////////////////////////////////////////////////////////////////

	if ($display_article_images) {
		include 'Simple/CMS/Images/form_update.php';
		include 'Simple/CMS/Images/form_delete.php';
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'updated', 'The '.$article_settings['title_singular'].' <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', 'The '.$article_settings['title_singular'].' <strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////
	
	$admin_page_id = ($admin_page_id)?$admin_page_id:43;

	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$g['page']['instructions']	= '';

	$body = $article_settings['body'];

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default Variables #
	/////////////////////////////////////////////////////////////////////////

	if ($_GET['session'] == 'clear' || $_GET['new']==1) {
		$_SESSION['article_id'] = '';
	}

	if (ctype_digit(trim($_GET['article'])) && trim($_GET['article']) > 0) {
		$_SESSION['article_id'] = $_GET['article'];
	}

	if (ctype_digit(trim($_SESSION['article_id'])) && trim($_SESSION['article_id']) > 0) {

		$current_article = $_GET['article'];
		$idset = true;

		$g['page']['instructions']	= '<p><a class="neutral" href="'.$article_settings['path_addedit'].'?session=clear"><img src="'.$g['page']['buttons'].'/btn-big-addnewarticle.png" alt="Add a new article" /></a></p>';

		$current_article = $_SESSION['article_id'];
		$idset = true;
		$sql = <<<SQL

SELECT
		a.id			  AS article_id,
		a.title			  AS article_title,
		a.author		  AS article_author,
		a.body			  AS article_body,
		a.excerpt		  AS article_excerpt,
		a.article_link	  AS article_link,
		a.sidebar		  AS article_sidebar,
		a.call_out		  AS article_call_out,
		a.active		  AS article_active,
		a.highlight		  AS article_highlight,
		a.home_display	  AS article_home_display,
		a.start_date	  AS article_start_date,
		a.end_date		  AS article_end_date,
		a.start_time	  AS article_start_time,
		a.end_time		  AS article_end_time,
		a.allow_comments  AS article_allow_comments,
		a.dateCrtd		  AS article_dateCrtd,
		a.dateMdfd		  AS article_dateMdfd
FROM	{$article_settings['table']} a
WHERE	a.id = $current_article

SQL;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		// Build default vars
		$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
		$default_vars = array();
		foreach ($r AS $k => $v) { $default_vars[$k] = $v; }

		if (!$default_vars['article_active']) {
			$default_vars['article_dateCrtd'] = date('Y-m-d');
		}

	// Category Defaults ----------------------------------------------------

		$sql_category_defaults = "SELECT category_id
								  FROM ".$article_settings['table_2categories']."
								  WHERE article_id = ".$default_vars['article_id'];
		$q_category_defaults = $db->query($sql_category_defaults);
		if (DB::isError($q_category_defaults)) { sb_error($q_category_defaults); }

		while ($r_category_defaults = $q_category_defaults->fetchrow(DB_FETCHMODE_ASSOC)) {
			$default_vars['multiple_article_category_'.$r_category_defaults['category_id']] = 1;
			if ($q_category_defaults->numrows() == 1) {
			   $default_vars['article_category'] = $r_category_defaults['category_id'];
			}
		}

		$action_target = $_SERVER['PHP_SELF'].'?article='.$current_article;

	} else {

		$action_target = $_SERVER['PHP_SELF'];
		$idset = false;

	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

	// Form setup -----------------------------------------------------------

	// Instantiate QuickForm
	$form = new HTML_QuickForm('frm', 'post', $action_target, null, array("onsubmit"=>"return validateCheckboxGroup(frm);"));

	// Default Template -----------------------------------------------------

	$renderer =& $form->defaultRenderer();
	$renderer->clearAllTemplates();
	$renderer->setFormTemplate($qf_container);
	$renderer->setHeaderTemplate($qf_header);
	$renderer->setElementTemplate($qf_element);
	$renderer->setElementTemplate($qf_button, 'btnUpdate');
	$renderer->setElementTemplate($qf_button, 'btnAdd');

	// Unique -----------------------------------------------------------------

	$renderer->setElementTemplate($qf_plain, 'btnUpdatePublish');
	$renderer->setElementTemplate($qf_plain, 'btnUpdateSave');
	$renderer->setElementTemplate($qf_plain, 'btnAddImage');
	$renderer->setElementTemplate($qf_plain, 'image_caption');
	$renderer->setElementTemplate($qf_plain, 'image_placement');
	$renderer->setElementTemplate($qf_plain, 'image_upload');
	$renderer->setElementTemplate($qf_plain, 'article_category');
	$renderer->setElementTemplate($qf_plain, 'template');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Fields #
	/////////////////////////////////////////////////////////////////////////

	// Header ---------------------------------------------------------------

	if ($idset) {
		// Header
		$display_content_title = '<a href="'.$article_settings['path_list'].'">'.$article_settings['title'].'</a> &nbsp;&rarr;&nbsp;You are updating "'.$default_vars['article_title'].'"';
		if (!$default_vars['article_active']) {
			$form->addElement('html', '<h3 class="draft">'.$article_settings['title_singular'].' Draft</h3>');
		} else {
			$form->addElement('html', '<h3 class="published">Published '.$article_settings['title_singular'].'</h3>');
		}
	} else {
		// Header
		$display_content_title = '<div class="head-flag-links"><a href="'.$article_settings['path_list'].'">Cancel</a></div><a href="'.$article_settings['path_list'].'">'.$article_settings['title'].'</a> &nbsp;&rarr;&nbsp;Add a New '.$article_settings['title_singular'];
	}

	// Article title --------------------------------------------------------

	$form->addElement('text', 'article_title', $item_display_title.' Title:<br />', array('alt'=>'Title','class'=>'long'));
	$form->addRule('article_title', 'Please provide a title.', 'required');
	$form->addRule('article_title', 'Can not exceed 100 characters for the title. Please try something shorter.', 'maxlength', 100);

	// Article Author -------------------------------------------------------

	$form->addElement('text', 'article_author', $item_display_title.' Author:<br />', array('alt'=>'Author','class'=>'long'));
	$form->addRule('article_title', 'Please limit to 100 characters for author field.', 'maxlength', 100);

	// Article Excerpt ------------------------------------------------------

	$attrs1 = array("rows"=>"5", "cols"=>"55", "class"=>"wordcount", "id"=>"wordCounter",'alt'=>'Excerpt');
	$form->addElement('textarea', 'article_excerpt', 'Excerpt:<br /><em>Word Counter (<span id="wordCounterWordCount"></span>)</em>', $attrs1);

	// Article Body ---------------------------------------------------------

	if ($g['xipe']['wysiwyg']==1) {
		$articlebody_attrs = array('id'=>'article_body', "cols"=>"55", "rows"=>"12");
		$form->addElement('html', textarea_wysiwyg('article_body'));
	} else {
		$articlebody_attrs = array("rows"=>"12", "cols"=>"55", 'alt'=>'Body');
	}
	$form->addElement('textarea', 'article_body', '<span class="highlight">'.$item_display_title.' Body:</span><br />', $articlebody_attrs);
	$form->addRule('article_body', 'Please provide the content.', 'required');

	// Publish Date ---------------------------------------------------------

	if ($idset) {
		$form->addElement('date','article_dateCrtd','Change Publish Date: ', array('format' => 'M d, Y'));
	}

	// Article Categories ---------------------------------------------------

	$sql_category = 'SELECT id, name FROM '.$article_settings['table_categories'].' ORDER BY name ASC';
	$q_category = $db->query($sql_category);
	if (DB::isError($q_category)) { sb_error($q_category); }
	$category_list = array();
	// Build selection array
	while ($r_category = $q_category->fetchrow(DB_FETCHMODE_ASSOC)) {
		$category_list[$r_category['id']] = shorten_this($r_category['name'], 25);
		$category_checked = ($default_vars['multiple_article_category_'.$r_category['id']] == 1)?' checked="checked':'';
		$more_category_list[$r_category['id']] = '<input name="multiple_article_category_'.$r_category['id'].'" type="checkbox" value="1" id="multiple_article_category_'.$r_category['id'].'"'.$category_checked.' /><label for="multiple_article_category_'.$r_category['id'].'"> '.$r_category['name'].'</label>';
		$checkbox_validation .= 'frm.multiple_article_category_'.$r_category['id'].'.checked == false &&'."\n";
	}

	$g['page']['js_top'] .= <<<HTML

	<script type="text/javascript" charset="utf-8">

	$(document).ready(function() {
	  $('#list-option-toggle').click(function() {
		$('#list-option').slideToggle('fast');

		if( $("#list-option-toggle").text().indexOf('Cancel multiple categories') >= 0) {
				$('#list-option-toggle').text("Apply multiple categories");
			} else {
				$('#list-option-toggle').text("Cancel multiple categories");
		}
		return false;
	  });
	});

	function validateCheckboxGroup(frm) {
		if (
			$checkbox_validation
			frm.article_category.disabled == true)
		{
			alert ('Please select at least one category');
			return false;
		} else {
			new Effect.Appear('indicator');
			return true;
		}
	}

	function disableIt(obj) {
		obj.disabled = !(obj.disabled);
		var z = (obj.disabled) ? 'disabled' : 'enabled';
	}

// -->
</script>

HTML;

	$form->addElement('html', '<tr><td class="label">'.$item_display_title.' Category:</td><td>');

	if ((!$idset) || $default_vars['article_category']) {
		$form->addElement('select', 'article_category', $item_display_title.' Category:', $category_list);
		$form->addElement('html', '&nbsp;<a class="bttn" id="list-option-toggle" href="javascript:;" onclick="disableIt(document.forms[0].article_category);">Apply multiple categories</a>');
		$form->addElement('html', '<div id="list-option" style="display:none;"><div class="vert-spacer"></div>'.table_array(1, $more_category_list).'</div></td></tr>');
	} else {
		$form->addElement('select', 'article_category', $item_display_title.' Article Category:', $category_list, 'disabled=disabled');
		$form->addElement('html', '&nbsp;<a class="bttn" id="list-option-toggle" href="javascript:;" onclick="disableIt(document.forms[0].article_category);">Cancel multiple categories</a>');
		$form->addElement('html', '<div id="list-option"><div class="vert-spacer"></div>'.table_array(1, $more_category_list).'</div></td></tr>');
	}

	// Images ---------------------------------------------------------------

	if ($display_article_images) {
		$image_owner_param = 'article';
		$image_owner_id_field = 'article_id';
		$image_owner_current_id = $current_article;
		$image_owner_page = $article_settings['path_addedit'];
		$image_table = $article_settings['table_images'];
		$image_path = $article_settings['path_images'];
		$image_inline = 1;
		include 'Simple/CMS/Images/form_row.php';
	}

	// Article Active (y/n) -------------------------------------------------

	if ($default_vars['article_active']) {
		$form->addElement('checkbox', 'article_active', null, '&nbsp;&nbsp;Uncheck to deactivate '.$article_settings['title_singular'].' and save it as a draft.');
	}

	// BUTTON ---------------------------------------------------------------

	if ($idset) {
		// Update info button
		//$form->addElement('image', 'btnPreview', $g['page']['buttons'].'/btn-preview.gif');
		if ($default_vars['article_active']==1) {
			$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-updatearticle.gif');
		} else {
			$form->addElement('html', '<tr><td class="last"><span style="font-size:10px;" class="required">Required fields in bold</span></td><td class="last">');
			$form->addElement('image', 'btnUpdatePublish', $g['page']['buttons'].'/btn-publisharticle.gif');
			$form->addElement('html', '&nbsp;&nbsp;');
			$form->addElement('image', 'btnUpdateSave', $g['page']['buttons'].'/btn-savearticle.gif');
			$form->addElement('html', '</td></tr>');
		}
		$form->addElement('hidden', 'article_id');
	} else {
		$form->addElement('image', 'btnSave', $g['page']['buttons'].'/btn-save.gif');
	}
	// Form protection
	$form->addElement('hidden', 'formcheck', $display_page_title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

	if ($idset && !$default_vars['protect']) {
		$linkstuff = js_confirm('?deleteid='.$default_vars['article_id'],
								'Delete',
								'Are you sure you want to delete the page -> '.$default_vars['article_title'].'?',
								'Delete '.$default_vars['article_title'],
								'attention');
		$display_content_title = '<div class="head-flag-links"><a href="articles.php">Cancel</a> | '.$linkstuff.'</div>'.$display_content_title;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process form #
	/////////////////////////////////////////////////////////////////////////

	if ($form->validate()) {

	// Clean input ----------------------------------------------------------

		$submit_vars = array(
			'id'			 => safe_escape($form->getSubmitValue('article_id')				,'int',true),
			'title'			 => safe_escape($form->getSubmitValue('article_title')			,'str',true),
			'author'		 => safe_escape($form->getSubmitValue('article_author')			,'str',true),
			'article_link'	 => safe_escape($form->getSubmitValue('article_link')			,'str',true),
			'excerpt'		 => safe_escape($form->getSubmitValue('article_excerpt')		,'str',true),
			'body'			 => safe_escape($form->getSubmitValue('article_body')			,'wysiwyg',true),
			'sidebar'		 => safe_escape($form->getSubmitValue('article_sidebar')		,'str',true),
			'call_out'		 => safe_escape($form->getSubmitValue('article_call_out')		,'str',true),
			'active'		 => safe_escape($form->getSubmitValue('article_active')			,'chk',true),
			'highlight'		 => safe_escape($form->getSubmitValue('article_highlight')		,'chk',true),
			'allow_comments' => safe_escape($form->getSubmitValue('article_allow_comments') ,'chk',true),
			'home_display'	 => safe_escape($form->getSubmitValue('article_home_display')	,'chk',true),
			'published_date' => safe_escape($form->getSubmitValue('article_dateCrtd')		,'dte',true),
			'dateCrtd'		 => safe_escape(date('Y-m-d H:i:s')								,'str',true),
			'category'		 => safe_escape($form->getSubmitValue('article_category')		,'int',true)
		);

	// Update Article -------------------------------------------------------

		if ($form->getSubmitValue('btnUpdate_x') ||
			$form->getSubmitValue('btnUpdateSave_x') ||
			$form->getSubmitValue('btnUpdatePublish_x') ||
			$form->getSubmitValue('btnAddImage_x')) {

			if ($form->getSubmitValue('btnAddImage_x')) {
				$current_item = $current_article;
				include 'Simple/CMS/Images/form_insert.php';
				go_to($article_settings['path_addedit'], '?article='.$submit_vars['id'].'&addedimage=1');
			}

			if ($form->getSubmitValue('btnUpdatePublish_x')) {
				$submit_vars['active'] = 1;
			}

			if ($form->getSubmitValue('btnUpdateSave_x')) {
				$submit_vars['active'] = 0;
			}
			
			// TODO: foreach submit_vars (except category)
			$sql_u = <<<SQL

UPDATE {$article_settings['table']} SET
	id			   = {$submit_vars['id']},
	title		   = {$submit_vars['title']},
	author		   = {$submit_vars['author']},
	article_link   = {$submit_vars['article_link']},
	excerpt		   = {$submit_vars['excerpt']},
	body		   = {$submit_vars['body']},
	sidebar		   = {$submit_vars['sidebar']},
	call_out	   = {$submit_vars['call_out']},
	active		   = {$submit_vars['active']},
	highlight	   = {$submit_vars['highlight']},
	allow_comments = {$submit_vars['allow_comments']},
	home_display   = {$submit_vars['home_display']},
	dateCrtd	   = {$submit_vars['published_date']}

WHERE
	id = {$submit_vars['id']}

SQL;

			$q_u = $db->query($sql_u);
			if (DB::isError($q_u)) { sb_error($q_u); }

			// Delete all existing categories
			$sql_del_categories = "DELETE FROM ".$article_settings['table_2categories']." WHERE article_id = ".$submit_vars['id'];
			$q_del_categories = $db->query($sql_del_categories);
			if (DB::isError($q_del_categories)) { sb_error($q_del_categories); }

	// Categories -----------------------------------------------------------

			if (!$submit_vars['category']) {
				$sql_add_category ="SELECT id, name FROM ".$article_settings['table_categories'];
				$q_add_category = $db->query($sql_add_category);
				if (DB::iserror($q_add_category)) { sb_error($q_add_category); }

				while ($r_add_category = $q_add_category->fetchrow(DB_FETCHMODE_ASSOC)) {

					if ($_POST['multiple_article_category_'.$r_add_category['id']] == 1) {

						$sql_add_multiple_category = "INSERT INTO ".$article_settings['table_2categories']." (article_id, category_id)
														  VALUES ('".$submit_vars['id']."',
																  '".$r_add_category['id']."')";
						$q_add_multiple_category = $db->query($sql_add_multiple_category);
						if (DB::isError($q_add_multiple_category)) { sb_error($q_add_multiple_category); }

					}

				}
			} else {
				$sql_add_multiple_category = "INSERT INTO ".$article_settings['table_2categories']." (article_id, category_id)
												  VALUES ('".$submit_vars['id']."',
														  '".$submit_vars['category']."')";
				$q_add_multiple_category = $db->query($sql_add_multiple_category);
				if (DB::isError($q_add_multiple_category)) { sb_error($q_add_multiple_category); }
			}

			// Log activity
			if ($g['log']['active']==1) {
				$log = new LogManager();
				$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the '.$article_settings['title_singular'].' '.$form->getSubmitValue('article_title'));
			}

			go_to($article_settings['path_addedit'], '?article='.$submit_vars['id'].'&updated='.urlencode(stripslashes($submit_vars['title'])));

		} // end update form

	// Insert Article -------------------------------------------------------

		if ($form->getSubmitValue('btnSave_x')) {

			$submit_vars['active'] = 0;
			// TODO: foreach submit_vars (except category)
			// Add to products
			$sql_add_a = <<<SQL

INSERT INTO {$article_settings['table']}
	(
		title,
		author,
		article_link,
		excerpt,
		body,
		sidebar,
		call_out,
		active,
		highlight,
		allow_comments,
		home_display,
		dateCrtd
	)
VALUES
	(
		{$submit_vars['title']},
		{$submit_vars['author']},
		{$submit_vars['article_link']},
		{$submit_vars['excerpt']},
		{$submit_vars['body']},
		{$submit_vars['sidebar']},
		{$submit_vars['call_out']},
		{$submit_vars['active']},
		{$submit_vars['highlight']},
		{$submit_vars['allow_comments']},
		{$submit_vars['home_display']},
		{$submit_vars['dateCrtd']}
	)
SQL;

			$q_add_a = $db->query($sql_add_a);
			if (DB::isError($q_add_a)) { sb_error($q_add_a); }

			// Get new id
			$new_article_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM ".$article_settings['table']);

	// Categories -----------------------------------------------------------

			if (!$submit_vars['category']) {
				$sql_add_category ="SELECT id, name FROM ".$article_settings['table_categories'];
				$q_add_category = $db->query($sql_add_category);
				if (DB::iserror($q_add_category)) { sb_error($q_add_category); }

				while ($r_add_category = $q_add_category->fetchrow(DB_FETCHMODE_ASSOC)) {

					if ($_POST['multiple_article_category_'.$r_add_category['id']] == 1) {

						$sql_add_multiple_category = "INSERT INTO ".$article_settings['table_2categories']." (article_id, category_id)
														  VALUES ('".$new_article_id."',
																  '".$r_add_category['id']."')";
						$q_add_multiple_category = $db->query($sql_add_multiple_category);
						if (DB::isError($q_add_multiple_category)) { sb_error($q_add_multiple_category); }

					}

				}
			} else {
				$sql_add_multiple_category = "INSERT INTO ".$article_settings['table_2categories']." (article_id, category_id)
												  VALUES ('".$new_article_id."',
														  '".$submit_vars['category']."')";
				$q_add_multiple_category = $db->query($sql_add_multiple_category);
				if (DB::isError($q_add_multiple_category)) { sb_error($q_add_multiple_category); }
			}

			go_to($article_settings['path_addedit'], '?article='.$new_article_id.'&added='.urlencode(stripslashes($submit_vars['title'])));

		} // end add form

	} // end form validate

	$form->setDefaults($default_vars);
	$display_form = $form->toHtml().$subpages_list;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
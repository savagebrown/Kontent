<?php

	require_once 'Includes/Configuration.php';
	require_once 'Pager/Pager.php';
	require_once 'Simple/Ajax/Sortable.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'deleted', 'The sidebar <strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'updated', 'The sidebar <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', 'The sidebar <strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:21;

	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$g['page']['instructions']	= '<p><a href="page_sidebars.php?new=1"><img src="'.$g['page']['buttons'].'/btn-big-addnewsidebar.png" align="absmiddle" /></a></p>';
	$g['page']['instructions'] .= '<p><img src="'.$g['page']['images'].'/arrow-left.png" align="absmiddle" />&nbsp;&nbsp;<a href="pages.php">Back to Pages</a></p>';
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "page_sidebars";

	$display_content_title = '<div class="head-flag-links"><a href="pages.php">Cancel</a></div><a href="pages.php">Pages</a> &nbsp;&rarr;&nbsp; Sidebars';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Sidebar #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		$sql_item ="SELECT title FROM page_sidebars WHERE id = ".$del_id;
		$q_item = $db->query($sql_item);
		if (DB::iserror($q_item)) { sb_error($q_item); }

		$r_item = $q_item->fetchrow(DB_FETCHMODE_ASSOC);
		$item_title = $r_item['title'];

		// Remove from db
		$sql = "DELETE FROM page_sidebars WHERE id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }


		// Remove from db
		$sql = "DELETE FROM pages2sidebars WHERE sidebar_id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		// Redirect
		go_to(null, '?deleted='.urlencode($item_title));

	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default Variables #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['sidebar'])) && trim($_GET['sidebar']) > 0) {
		$current_sidebar = $_GET['sidebar'];
		$idset=true;

		$sql = <<<SQL

SELECT id, title, description, link, class, html, protect
FROM page_sidebars
WHERE id = $current_sidebar

SQL;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
		$default_vars = array();
		foreach ($r AS $k => $v) {
			$default_vars[$k] = $v;
		}

	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

	if ($idset || $_GET['new']==1) {

	// Form setup -----------------------------------------------------------

		if ($idset) {
			$target = 'page_sidebars.php?sidebar='.$current_sidebar;
		} else {
			$target = 'page_sidebars.php?new=1';
		}

	// Instantiate QuickForm
		$form = new HTML_QuickForm('frm', 'post', $target);

	// Default Template -----------------------------------------------------

		$renderer =& $form->defaultRenderer();
		$renderer->clearAllTemplates();
		$renderer->setFormTemplate('

<div id="form_container">
	<form{attributes}>
		<div>
			<table class="listingform">
				{content}
			</table>
		</div>
	</form>
</div>

		');
		$renderer->setHeaderTemplate('<tr><td colspan="2"><h2>{header}</h2></td></tr>');
		$renderer->setElementTemplate('

<!-- BEGIN error -->
<tr>
<td colspan="2">
<div class="error">{error}</div>
</td>
</tr>
<!-- END error -->

<tr>
	<td class="label">
	<span <!-- BEGIN required -->class="required"<!-- END required --> >
	{label}
	</span>
	</td>
	<td>{element}</td>
</tr>

		');

	// Buttons --------------------------------------------------------------

		$btn_alt_html = <<<HTML

<tr>
	<td class="last"><span style="font-size:10px;" class="required">Required fields in bold</span></td>
	<td class="last">{element}</td>
</tr>

HTML;

		$renderer->setElementTemplate($btn_alt_html, 'btnUpdate');
		$renderer->setElementTemplate($btn_alt_html, 'btnAdd');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Fields #
	/////////////////////////////////////////////////////////////////////////

	// Header ---------------------------------------------------------------

		if ($idset) {
			$display_content_title = '<a href="pages.php">Pages</a> &nbsp;&rarr;&nbsp;<a href="page_sidebars.php">Sidebars</a> &nbsp;&rarr;&nbsp; You are updating "'.$default_vars['title'].'"';
		} else {
			$display_content_title = '<div class="head-flag-links"><a class="attention" href="page_sidebars.php">Cancel</a></div><a href="pages.php">Pages</a> &nbsp;&rarr;&nbsp;<a href="page_sidebars.php">Sidebars</a> &nbsp;&rarr;&nbsp; Add a New Sidebar';
			$g['page']['instructions'] = '<p><img src="'.$g['page']['images'].'/arrow-left.png" align="absmiddle" />&nbsp;&nbsp;<a href="pages.php">Back to Pages</a></p>';
		}

	// Item Title -----------------------------------------------------------

		$form->addElement('text', 'title', 'Sidebar Title:', array('alt'=>'Reference title','class'=>'long'));
		$form->addRule('title', 'Please provide a title for this item.', 'required');
		$form->addRule('title', 'Can not exceed 50 characters for the title. Please try something shorter.', 'maxlength', 75);

	// Item Description -----------------------------------------------------
		if ($g['xipe']['wysiwyg']) {
			$item_attrs = array('id'=>'sidebar_description', "rows"=>"5");
			$form->addElement('html', textarea_wysiwyg('sidebar_description'));
		} else {
			$item_attrs = array("rows"=>"5", "cols"=>"55");
		}
		$form->addElement('textarea', 'description', '<span class="highlight">Sidebar Content:</span>', $item_attrs);
		$form->addRule('description', 'Please provide a description for this item.', 'required');

	// Item Link ------------------------------------------------------------

		$form->addElement('text', 'link', 'Should Link to?', array('alt'=>'Reference title','class'=>'long'));
		if ($force_link) {
			$form->addRule('link', 'Please provide a link for this item.', 'required');
		}
		$form->addRule('link', 'Can not exceed 200 characters for the link. Please try something shorter.', 'maxlength', 200);

	// Item Class (Select) -------------------------------------------------

		$form->addElement('select', 'class', 'Select a Style:', $item_info_select);

	// BUTTON ---------------------------------------------------------------

		if ($idset) {
			$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-updateitem.gif');
			// Item ID
			$form->addElement('hidden', 'id');
		} else {
			$form->addElement('image', 'btnAdd', $g['page']['buttons'].'/btn-addnewitem.gif');
		}

		// Form protection
		$form->addElement('hidden', 'formcheck', $display_page_title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

		if ($idset) {
			$linkstuff = js_confirm('?deleteid='.$default_vars['id'],
									'Delete',
									'Are you sure you want to delete the sidebar item -> '.$default_vars['title'].'?',
									'Delete '.$default_vars['title'],
									'attention');
			$display_content_title = '<div class="head-flag-links"><a class="attention" href="page_sidebars.php">Cancel</a> | '.$linkstuff.'</div>'.$display_content_title;
		}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process form #
	/////////////////////////////////////////////////////////////////////////

		// Keeps errors from displaying if form coming in from else where
		if ($form->getSubmitValue('formcheck') == $display_page_title) {

			if ($form->validate()) {
				// write information to database
				$submit_vars = array(
								'id'		  => $form->getSubmitValue('id'),
								'title'		  => $form->getSubmitValue('title'),
								'description' => $form->getSubmitValue('description'),
								'link'		  => $form->getSubmitValue('link'),
								'category_id' => 0,//$form->getSubmitValue('category_id'),
								'class'		  => $form->getSubmitValue('class'),
								'html'		 => '',//$form->getSubmitValue('info2'),
								'protect'		=> '',//$form->getSubmitValue('info3')
								);

		// Update ---------------------------------------------------------------

				if ($form->getSubmitValue('btnUpdate_x')) {

					$sql_u = "UPDATE page_sidebars SET
									title		= '".safe_escape($submit_vars['title'], 'str')."',
									description = '".safe_escape($submit_vars['description'], 'wysiwyg')."',
									link		= '".safe_escape($submit_vars['link'], 'str')."',
									category_id =  ".safe_escape($submit_vars['category_id'], 'int').",
									class		= '".safe_escape($submit_vars['class'], 'str')."'

							   WHERE
									id = ".$submit_vars['id'];

					$q_u = $db->query($sql_u);
					if (DB::isError($q_u)) { sb_error($q_u); }

					// Log activity
					if ($g['log']['active']==1) {
						$log = new LogManager();
						$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the sidebar '.$form->getSubmitValue('title'));
					}

					go_to('page_sidebars.php', '?updated='.urlencode($submit_vars['title']));

				}

		// Insert ---------------------------------------------------------------

				if ($form->getSubmitValue('btnAdd_x')) {

					$sql_add_page = "INSERT INTO page_sidebars (title, description, link, category_id, class)
									 VALUES ('".safe_escape($submit_vars['title'], 'str')."',
											 '".safe_escape($submit_vars['description'], 'wysiwyg')."',
											 '".safe_escape($submit_vars['link'], 'str')."',
											  ".safe_escape($submit_vars['category_id'], 'int').",
											 '".safe_escape($submit_vars['class'], 'str')."')";
					
					$q_add_page = $db->query($sql_add_page);
					if (DB::isError($q_add_page)) { sb_error($q_add_page); }

					// Get new id
					$new_page_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM page_sidebars");

					// Log activity
					if ($g['log']['active']==1) {
						$log = new LogManager();
						$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added the sidebar '.$form->getSubmitValue('title'));
					}

					go_to('page_sidebars.php', '?added='.urlencode($submit_vars['title']));

				}

			}

		}

		$form->setDefaults($default_vars);
		$display_sidebar = $form->toHtml();

	} else { // end if idset or new

		$g['page']['markup'] = '';

		/////////////////////////////////////////////////////////////////////////
		# BEGIN Item List #
		/////////////////////////////////////////////////////////////////////////

		$sidebars_list = '';

		if (!$idset && $_GET['new']!=1) {
			$sql_sidebars = <<<SQL

SELECT id, title, protect
FROM page_sidebars
ORDER BY rank ASC

SQL;
			$q_sidebars = $db->query($sql_sidebars);
			if (DB::iserror($q_sidebars)) { sb_error($q_sidebars); }

			if ($q_sidebars->numrows()>1) {
				while ($r_sidebars = $q_sidebars->fetchrow(DB_FETCHMODE_ASSOC)) {
					$sql_item_count ="SELECT COUNT(*) FROM pages2sidebars WHERE sidebar_id = ".$r_sidebars['id'];
					$page_count = $db->getOne($sql_item_count);
					if (DB::iserror($page_count)) { sb_error($page_count); }

					$display_page_count = ($page_count>1)?'applied to '.$page_count.' pages':'applied to '.$page_count.' page';
					$display_page_count = ($page_count==0)?'not used':$display_page_count;

					if ($r_sidebars['protect']) {
						$sidebar_line_class = ' class="protected"';
						$sidebar_actionable = '';
					} else {
						$sidebar_actionable = '<a class="neutral" style="font-size:11px;float:right;" href="?sidebar='.$r_sidebars['id'].'"><img src="'.$g['page']['buttons'].'/thin-btn-edit.gif" /></a>';
						$sidebar_line_class = '';
					}
					$sidebar_items .= "\n\t".'<li id="item_'.$r_sidebars['id'].'"><div'.$sidebar_line_class.'>'.$sidebar_actionable.'<span class="handle" title="drag">&nbsp;</span>'.$r_sidebars['title'].' <em style="font-size:10px;color:grey">('.$display_page_count.')</em></div></li>';
				}

				$display_sidebar = <<<HTML

<div class="table-wrapper">
	<h3 class="head-sub">Drag &amp; drop to reorder sidebars &nbsp;&nbsp;<em id="workingMsg" style="display:none;color:green;">Updating...</em></h3>
	<div id="sortable_list">
		<ul id="listContainer">
			$sidebar_items
		</ul>
	</div>
</div>

HTML;

			session_start();
			$_SESSION['sortable_table'] = 'page_sidebars';
			$sortable_sidebars = new simple_ajax_sortable($db, $_SESSION['sortable_table']);
			// javascript
			$g['page']['js_top'] .= $sortable_sidebars->getJS();

			}
		}

	}

	$display_form = $display_sidebar;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());

?>
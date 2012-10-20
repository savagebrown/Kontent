<?php

	/*
		The following must be set

		$item_table           
	    $item_categories_table
		$item_display_title  
	*/ 

	require_once 'Includes/Configuration.php';
	require_once 'Simple/Ajax/Sortable.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Category #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		if ($item_assoc_table) {
			$default_category_id = $db->getone("SELECT id FROM $item_categories_table WHERE default_cat = 1");

			// Change associated articles to default category
			$sql_check ="SELECT id FROM $item_assoc_table WHERE category_id = $del_id";
			$q_check = $db->query($sql_check);
			if (DB::iserror($q_check)) { sb_error($q_check); }

			if ($q_check->numrows()>0) {
				while ($r_check = $q_check->fetchrow(DB_FETCHMODE_ASSOC)) {

					$sql_update_default = "UPDATE ".$item_assoc_table."
												SET category_id = ".$default_category_id."
										   WHERE
												id = ".$r_check['id'];
					$q_update_default = $db->query($sql_update_default);
					if (DB::isError($q_update_default)) { sb_error($q_update_default); }

				}
			}
		}

		// Get title
		$category_title = $db->getone("SELECT name FROM $item_categories_table WHERE id = $del_id");

		// Remove from db
		$sql = <<<SQL

DELETE FROM $item_categories_table WHERE id = $del_id

SQL;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Deleted the category '.$category_title);
		}

		// Redirect
		go_to($item_categories_table.'.php', '?deleted='.$category_title);
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////
	
	$display_message = setDisplayMessage($display_message, 'updated', 'The category <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', 'The category <strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'deleted', 'The category <strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = $page_vars['submenu'];
	$g['page']['instructions']  = '<p><img src="'.$g['page']['images'].'/arrow-left.png" align="absmiddle" />&nbsp;&nbsp;<a href="'.$item_table.'.php">Back to '.ucwords(str_replace('_',' ',$item_table)).'</a></p>';

	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = 'categories';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Instantiate Quickform #
	/////////////////////////////////////////////////////////////////////////

	// Instantiate QuickForm
	$form = new HTML_QuickForm('frm', 'post');
	// Instantiate the renderer
	$renderer =& $form->defaultRenderer();
	// Clear QuickForm template
	$renderer->clearAllTemplates();
	// Define new templates
	$renderer->setFormTemplate('

<div id="edit_categories">
	<form{attributes}>
		{content}
	</form>
</div>

	');

	$renderer->setElementTemplate('{element}');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Update Form #
	/////////////////////////////////////////////////////////////////////////

	$display_content_title = '<a href="'.$item_table.'.php">'.ucfirst($item_table).'</a> &nbsp;&rarr;&nbsp;'.$item_display_title.' Categories';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Add Form #
	/////////////////////////////////////////////////////////////////////////

	$form->addElement('html', '<div class="add-single-container">Add New Category: ');
	$form->addElement('text', 'name', 'New Category Name:<br />', 'class=big');
	$form->addElement('html', '&nbsp;&nbsp;');
	$form->addElement('image', 'btnAddCat', $g['page']['buttons'].'/btn-addcategory.gif', 'align=top');
	$form->addElement('html', '</div>');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Category List #
	/////////////////////////////////////////////////////////////////////////
	
	$form->addElement('html', '<h3 class="head-sub">Drag &amp; drop to reorder &nbsp;&nbsp;<em id="workingMsg" style="display:none;color:green;">Updating...</em></h3>');
	$form->addElement('html','<ul id="listContainer">');

	$sql_categories = <<<SQL

SELECT id, name, default_cat FROM $item_categories_table ORDER BY rank

SQL;
	$q_categories = $db->query($sql_categories);
	if (DB::iserror($q_categories)) { sb_error($q_categories); }

	$count = 0;
	while ($r_categories = $q_categories->fetchrow(DB_FETCHMODE_ASSOC)) {

		$count++;
		$thiscatid = $r_categories['id'];
		$thiscatname = $r_categories['name'];
		$thiscatdescription = $r_categories['description'];

		if (!$r_categories['default_cat']) {
			$thiscathtmlstart = <<<HTML

		<li id="item_$thiscatid">
			<span class="handle" title="drag">&nbsp;</span>
			<span id="print$thiscatid">
				$thiscatname&nbsp;
				<a style="text-decoration:none;" title="Delete $thiscatname" href="javascript:;" onclick="cf=confirm('Are you sure you want to delete the category &quot;$thiscatname&quot;?');if (cf)window.location='?deleteid=$thiscatid'; return false;">
					<img src="Images/trash.gif" alt="Delete $thiscatname" />
				</a>&nbsp;
				<a id="on$thiscatid" href="javascript:;" onclick="kToggle('input$thiscatid', 'print$thiscatid');return false;">edit</a>
			</span>
			<span id="input$thiscatid" style="display:none;">

HTML;
		} else {
			$thiscathtmlstart = <<<HTML

		<li id="item_$thiscatid">
			<span class="handle" title="drag">&nbsp;</span>
			<span id="print$thiscatid">
				<strong>$thiscatname</strong>&nbsp;
				<img src="Images/trash_fade.gif" alt="Delete $thiscatname" />
				&nbsp;<a id="on$thiscatid" href="javascript:;" onclick="kToggle('input$thiscatid', 'print$thiscatid');return false;">edit</a>
			</span>
			<span id="input$thiscatid" style="display:none;">

HTML;
		}

		$form->addElement('html', $thiscathtmlstart);
		$form->addElement('text', 'name_'.$count, 'Category Name: ', 'class=medium');
		$default_vars['name_'.$count] = $thiscatname;
		$form->addElement('html', '&nbsp;');
		$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/thin-btn-save.gif', 'value=Update');
		$form->addElement('html', "&nbsp;&nbsp;<em>(<a href=\"javascript:;\" onclick=\"kToggle('print".$thiscatid."', 'input".$thiscatid."');return false;\" title=\"Cancel\" >cancel</a>)</em></span>");

		$form->addElement('html', '</li>');
		$form->addElement('hidden', 'catid_'.$count, $thiscatid);
		$form->addElement('hidden', 'verify_name_'.$count, $thiscatname);
	}
	// Use this number to iterate through updating categories
	$form->addElement('hidden', "u_count", $q_categories->numrows());

	$form->addElement('html', '</ul>');

	$form->addElement('hidden', 'formcheck', $title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Sortable Settings #
	/////////////////////////////////////////////////////////////////////////

	session_start();
	$_SESSION['sortable_table'] = $item_categories_table;
	$sortable_categories = new simple_ajax_sortable($db, $_SESSION['sortable_table']);
	// javascript
	$g['page']['js_top'] .= $sortable_categories->getJS();

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process form #
	/////////////////////////////////////////////////////////////////////////

	if ($form->validate()) {
		// write information to database
		$submit_vars = array(
						'id'   => $form->getSubmitValue('id'),
						'name' => ucwords($form->getSubmitValue('name'))
						);

		if ($form->getSubmitValue('btnUpdate_x')) {

			for ($i = 1; $i <= $form->getSubmitValue('u_count'); $i++) {

				// Check that Category name is not Null
				if ($form->getSubmitValue('name_'.$i) != '' &&
					$form->getSubmitValue('verify_name_'.$i) != $form->getSubmitValue('name_'.$i) ) {

					// Build an array from the submitted form values
					$update_vars = array(
						'name' => safe_escape($form->getSubmitValue('name_'.$i)),
						'catid' => $form->getSubmitValue('catid_'.$i));

					// Update category information
					$sql_u = "UPDATE ".$item_categories_table." SET
								 name = '".$update_vars['name']."',
								 description = '".$update_vars['description']."'
							   WHERE
								 id = ".$update_vars['catid'];

					$q_u =& $db->query($sql_u);
					if (DB::isError($q_u)) { sb_error($q_u); }

					// Log activity
					if ($g['log']['active']==1) {
						$log = new LogManager();
						$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the '.$item_display_title.' category '.$form->getSubmitValue('name_'.$i));
					}

					go_to($item_categories_table.'.php', "?updated=".$form->getSubmitValue('name_'.$i));

				}

			}

			go_to($item_categories_table.'.php', "?updated=false");
		}

		if ($form->getSubmitValue('btnAddCat_x')) {
			// Add
			$sql_add_a = "INSERT INTO ".$item_categories_table." (name, description)
						  VALUES ('".safe_escape($submit_vars['name'])."',
								  '".safe_escape($submit_vars['description'])."')";
			$q_add_a = $db->query($sql_add_a);
			if (DB::isError($q_add_a)) { sb_error($q_add_a); }

			$new_id = $db->getOne( "SELECT LAST_INSERT_ID() FROM ".$item_categories_table);

			// Log activity
			if ($g['log']['active']==1) {
				$log = new LogManager();
				$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added the '.$item_display_title.' category '.$form->getSubmitValue('name'));
			}

			go_to($item_categories_table.'.php','?i='.$new_id.'&added='.urlencode($submit_vars['name']));
		}

	} else {
		$form->setDefaults($default_vars);
		$display_form = $form->toHtml().$reorder_list;
	}

	//////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	//////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
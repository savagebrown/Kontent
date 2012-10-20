<?php
	require_once 'Includes/Configuration.php';

	// TODO: Build a function for each instance of $item_info['list_field']
	// Display field variables
	if (is_array($item_info['list_field'])) {
		foreach ($item_info['list_field'] as $indiviual_field) {
			$display_list_field = ($display_list_field)?', '.$indiviual_field:$indiviual_field;
		}
	} else {
		$display_list_field = $item_info['list_field'];
	}
	
	$item_info['uploadFolder'] = $item_info['uploadFolder'] ? $item_info['uploadFolder'] : "Files/Upload";
	
	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Item #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];
		$del_name = $_GET['deletename'];
		
		$sql_query = <<<SQL
		
SELECT * FROM {$item_info['table']} WHERE id = $del_id	
		
SQL;

		$q_files = $db->query($sql_query);
		if (DB::isError($q_files)) { sb_error($q_files); }
		
		$db_row = $q_files->fetchrow(DB_FETCHMODE_ASSOC);
		
		foreach ($item_info['fields'] as $k => $v) {
			if ($v['data_type'] == 'file') {
				@unlink($db_row[$v['field']]);
			}
		}

		// Remove from db
		$sql = <<<SQL

DELETE FROM {$item_info['table']} WHERE id = $del_id

SQL;
		$q = $db->query($sql);
		if (DB::isError($q)) { sb_error($q); }

		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Deleted the '.$item_info['title_singular'].' '.$_GET['deletename']);
		}

		// Redirect
		go_to($_SERVER['PHP_SELF'], '?deleted='.urlencode($del_name));
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'deleted', '<strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'updated', '<strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'added', '<strong>"%GET%"</strong> has been added successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
		
	if (file_exists($g['page']['buttons'].'/btn-big-addnew'.strtolower(str_replace(' ','',$item_info['title_singular'])).'.png')) {
		$add_new_button = $g['page']['buttons'].'/btn-big-addnew'.strtolower(str_replace(' ','',$item_info['title_singular'])).'.png';
	} else {
		$add_new_button = $g['page']['buttons'].'/btn-big-addnewitem.png';
	}
	$g['page']['instructions']	= (!isset($_GET['new']))?'<p><a class="neutral" href="'.$item_info['table'].'.php?new=1"><img src="'.$add_new_button.'" alt="Add a new '.$item_info['title_singular'].'" /></a></p>':'';
	$g['page']['instructions'] .= ($item_info['categories'])?'<p class="sidebar-link"><img src="'.$g['page']['images'].'/icon-cat-add.gif" align="absmiddle" />&nbsp;&nbsp;<a href="'.$item_info['categories'].'.php">Manage '.($item_info['category_title']?$item_info['category_title']:$item_info['title_singular']).' Categories</a></p>':'';
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "item";

	if ($_GET['new'] || (ctype_digit(trim($_GET['id'])) && trim($_GET['id']) > 0) ) {

		/////////////////////////////////////////////////////////////////////////
		# BEGIN Update Item #
		/////////////////////////////////////////////////////////////////////////

		if ($_GET['id']){
			$idset=true;
			$current_item = $_GET['id'];

			// Compile sql
			$sql_select='id';
			if ($item_info['categories']) {
				$sql_select .= ', category_id';
			}
			foreach ($item_info['fields'] as $k => $v) {
				$sql_select .= ","."\n\t".$v['field'];
			}
			$sql = <<<SQL

SELECT
	$sql_select
FROM
	{$item_info['table']}
WHERE
	id = $current_item

SQL;

			$q = $db->query($sql);
			if (DB::iserror($q)) { sb_error($q); }
			$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
			$default_vars = array();
			foreach ($r AS $k => $v) {
				$default_vars[$k] = $v;
			}

			if (is_array($item_info['list_field'])) {
				$display_list_field_default = '';
				foreach ($item_info['list_field'] as $indiviual_field) {
					$display_list_field_default .= ($display_list_field_default)?', '.$default_vars[$indiviual_field]:$default_vars[$indiviual_field];
				}
			} else {
				$display_list_field_default = $default_vars[$item_info['list_field']];
			}

		} // end if edit

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

		// Form setup -----------------------------------------------------------

		if ($idset) {
			$action_target = $item_info['table'].'.php?id='.$current_item;
		} else if ($_GET['new']==1) {
			$action_target = $item_info['table'].'.php?new=1';
		}

		// Instantiate QuickForm
		$form = new HTML_QuickForm('frm', 'post', $action_target);

		// Default Template ---------------------------------------------------

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
		$renderer->setElementTemplate($qf_element);

		// Alternative Template -----------------------------------------------

		$renderer->setElementTemplate($qf_button, 'btnUpdate');
		$renderer->setElementTemplate($qf_button, 'btnAdd');

		// Categories
		if ($item_info['categories']) {
			$sql_category_id = <<<SQL

SELECT id, name FROM {$item_info['categories']} ORDER BY rank ASC

SQL;
			$q_category_id = $db->query($sql_category_id);
			if (DB::iserror($q_category_id)) { sb_error($q_category_id); }
			while ($r_category_id = $q_category_id->fetchrow(DB_FETCHMODE_ASSOC)) {
				$category_list[$r_category_id['id']] = $r_category_id['name'];
			}
			$form->addElement('select', 'category_id', 'Category:', $category_list);
		}
		// Compile form elements
		foreach ($item_info['fields'] as $element) {
		
			if ($element['data_type']=='text'||$element['data_type']=='textarea') {
				$element['label'] = ($element['textile'])?'<span class="highlight">'.$element['label'].'</span>':$element['label'];
				$form->addElement(	$element['data_type'],
									$element['field'],
									$element['label'].":",
									$element['attributes']);
			} else if ($element['data_type']=='wysiwyg') {
				
				// Make sure id is set one way or another
				if (is_array($element['attributes'])) {
					if (!array_key_exists('id',$element['attributes'])) {
						$attrs = array_pop($element['attributes'],array('id' => $element['field'].'_wysiwyg'));
					}
				} else {
					$attrs_explode = explode('=',$element['attributes']);
					$attrs = array(
						$attrs_explode[0] => $attrs_explode[1],
						'id' => $element['field'].'_wysiwyg'
					);
				}
				$form->addElement('html', textarea_wysiwyg($element['field'].'_wysiwyg'));
				
				$form->addElement(	'textarea',
									$element['field'],
									$element['label'].":",
									$attrs);

			} else if ($element['data_type']=='checkbox') {
				$label_left = ($element['label'])?$element['label'].':':'';
				$form->addElement(	$element['data_type'],
									$element['field'],
									$label_right,
									" ".$element['label_right']);
			} else if ($element['data_type']=='date') {
				$form->addElement(	'date',
									$element['field'],
									$element['label'].":",
									array('format'=>'d M, Y'));
				if (!$idset){$default_vars[$element['field']] = date('Y-m-d');}
			} else if ($element['data_type']=='time') {
				$form->addElement(	'date',
									$element['field'],
									$element['label'].":",
									array('format'=>'h:i a'));
				if (!$idset){$default_vars[$element['field']] = '12:00 pm';}
			} else if ($element['data_type']=='datetime') {
				$form->addElement(	'date',
									$element['field'],
									$element['label'].":",
									array('format'=>'d M, Y @ h:i a'));
				$default_vars[$element['field']] = date('Y-m-d H:i:s');
			} else if ($element['data_type']=='file') {
				$form->addElement(	'file',
									$element['field'],
									$element['label'].":",
									$element['attributes']);
			} /*
else if ($element['data_type']=='wysiwyg') {
				$form->addElement(	'html', textarea_wysiwyg($element['field']));
				$element['label'] = ($element['textile'])?'<span class="highlight">'.$element['label'].'</span>':$element['label'];
				$form->addElement(	'textarea',
									$element['field'],
									$element['label'].":",
									$element['attributes']);
			}
*/

			if (!is_array($element['rule']) && $element['rule'] != null) {
				$rule = $element['rule'];
				$element['rule'] = array($rule);
			}
				
			if ($element['rule']) {
				
				foreach ($element['rule'] as $rule) {
					switch ($rule) {
						case 'required':
							if (!isset($_GET['id'])) {
								$form->addRule( $element['field'],
												'Please provide info for '.$element['label'],
												'required',
												null,
												'client');
							}
							
							break;
	
						case 'email':
							$form->addRule( $element['field'],
										'Please provide info for '.$element['label'],
										'email',
										null,
										'client');
						 	break;
						 case 'mimetype':
						 	$form->addRule( $element['field'],
						 				$element['mime_message'],
						 				'mimetype',
						 				$element['mimetypes']);
						 	break;
	
					}
				}
			}
		}

		// BUTTON ---------------------------------------------------------------

		if ($idset) {
			$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-savechanges.gif');
			// Item ID
			$form->addElement('hidden', 'id');
		} else {
			$form->addElement('image', 'btnAdd', $g['page']['buttons'].'/btn-save.gif');
		}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

		if ($idset) {
			$linkstuff = js_confirm('?deleteid='.$default_vars['id'].'&deletename='.urlencode($display_list_field_default),
									'Delete',
									'Are you sure you want to delete -> '.$display_list_field_default.'?',
									'Delete '.$display_list_field_default,
									'attention');
			$display_content_title = '<div class="head-flag-links"><a href="?view=all">Cancel</a> | '.$linkstuff.'</div>Updating "'.$display_list_field_default.'"';

		} else if (isset($_GET['new'])) {
			$display_content_title = '<div class="head-flag-links"><a href="?view=all">Cancel</a></div>Add a New '.$item_info['title_singular'];
		}
		
		if ( $form->validate() && ($form->getSubmitValue('btnUpdate_x') || $form->getSubmitValue('btnAdd_x')) ) {

			
			foreach ($item_info['fields'] AS $k => $v) {
				
				if ($v['data_type'] == 'file') {
					
					if ($_FILES[$v['field']][name] != null) {
					
						// Upload file to store directory
						$fm->upload_file();
			
						if ($fm->error_message != '') {
							header ('Location: '.$item_info['table'].'.php?error='.$p_message = $fm->error_message);
							exit;
						}
	
						$submit_vars[$v['field']] = safe_escape ($fm->get_fullfilename(), 'text', true);
					}
					
				} else {
				
					$submit_vars[$v['field']] = safe_escape( $form->getSubmitValue($v['field']), $v['data_type'], true);
					
				}
			}
			
			if (function_exists(genSubVars)) {
				genSubVars();
			}
			
			if ($item_info['categories']) {
				$submit_vars['category_id'] = $form->getSubmitValue('category_id');
			}
			
			if (is_array($item_info['list_field'])) {
				foreach ($item_info['list_field'] as $indiviual_field) {
					$display_list_field_added .= ($display_list_field_added)?', '.stripslashes(str_replace("'",'',$submit_vars[$indiviual_field])):stripslashes(str_replace("'",'',$submit_vars[$indiviual_field]));
				}
			} else {
				$display_list_field_added = stripslashes(str_replace("'",'',$submit_vars[$item_info['list_field']]));
			}

			if ($form->getSubmitValue('btnAdd_x')) {
				// Add item to database
				
				$sql = get_insert_sql($submit_vars, $item_info['table']);
				$q = $db->query($sql);
				if (DB::iserror($q)) { sb_error($q); } 
		
				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Added the '.$item_info['title_singular'].' "'.$display_list_field_added.'"');
				}
				header ('Location: '.$item_info['table'].'.php?added='.urlencode($display_list_field_added));
				exit;
			} else if ($form->getSubmitValue('btnUpdate_x')) {
				// Add id field
				$submit_vars['id'] = $form->getSubmitValue('id');
				// Update database
				$sql = get_update_sql($submit_vars, $item_info['table']);
				$q = $db->query($sql);
				if (DB::iserror($q)) { sb_error($q); }
				
				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated the '.$item_info['title_singular'].' "'.$display_list_field_added.'"');
				}
				header ('Location: '.$item_info['table'].'.php?updated='.urlencode($display_list_field_added));
				exit;
			} 
		} else {
			$form->setDefaults($default_vars);
			$display_form = $form->toHtml();
		}

	} else { // end if new or edit
		
		// Hide markup
		$g['page']['markup']='';
		// Set page header
		$display_content_title = $item_info['title'];

		// Compile sql
		$sql_select='i.id AS id';
		foreach ($item_info['fields'] as $k => $v) {
			$sql_select .= ","."\n\t".'i.'.$v['field'].' AS '.$v['field'];
		}

		// Order direction
		$order_direction = ($item_info['order_direction'])?$item_info['order_direction']:'ASC';

		// Add category filter
		if ($item_info['categories']) {
			if (ctype_digit(trim($_GET['category'])) && trim($_GET['category']) > 0) {
				$category_filter = ' AND i.category_id = '.$_GET['category'];
				$current_category = $_GET['category'];
			} else {
				$current_category = '';
				$category_filter = '';
			}
			
			// category options list
			$sql_category_id = <<<SQL

SELECT id, name FROM {$item_info['categories']} ORDER BY rank ASC

SQL;
			$q_category_id = $db->query($sql_category_id);
			if (DB::iserror($q_category_id)) { sb_error($q_category_id); }
			while ($r_category_id = $q_category_id->fetchrow(DB_FETCHMODE_ASSOC)) {
				$category_list[$r_category_id['id']] = $r_category_id['name'];
			}
			
			$display_content_title = '<div class="head-flag-links">'.create_jumplist($category_list, $current_category, null, 'View All', 'Filter By '.($item_info['category_title']?$item_info['category_title']:'Category'), 'category').'</div>'.$display_content_title;

			$sql = <<<SQL

SELECT
	$sql_select,
	i.category_id AS category_id,
	c.name AS category_name
FROM
	{$item_info['table']} i,
	{$item_info['categories']} c
WHERE
	i.category_id = c.id
	$category_filter
ORDER BY
	c.rank ASC, i.{$item_info['order_field']} $order_direction

SQL;
		} else {
		
			if (!isset($item_info['where']))
				$item_info['where'] = '';
		
			$sql = <<<SQL

SELECT
	$sql_select
FROM
	{$item_info['table']} i
{$item_info['where']}
ORDER BY
	i.{$item_info['order_field']} $order_direction

SQL;

		}

		$q = $db->query($sql);
		if (DB::iserror($q)) { sb_error($q); }

		if ($q->numrows()>0) {
			$item_rows = array();
			while ($r = $q->fetchrow(DB_FETCHMODE_ASSOC)) {
				if($item_info['categories']) {
					if (is_array($item_info['list_field'])) {
						foreach ($item_info['list_field'] as $indiviual_field) {
							$item_rows[$r['category_name']][$r['id']] .= ' '.$r[$indiviual_field];
						}
					} else {
						$item_rows[$r['category_name']][$r['id']] = $r[$item_info['list_field']];
					}
				} else {
					if(is_array($item_info['list_field'])) {
						$seperator = ($item_info['list_field_seperator'])?$item_info['list_field']:' ';
						
						$count = 0;
						foreach ($item_info['list_field'] as $indiviual_field) {
							
							// If date then format
							if ($indiviual_field=='dateCrtd') {
								$indiviual_field_display = '<span class="date-item-field">'.date('M j, Y',strtotime($r[$indiviual_field])).'</span>';
							} else {
								$indiviual_field_display = '<span class="item-field">'.$r[$indiviual_field].'</span>';
							}
							
							// Only show separator between items, not at the end.
							if ($count < count($item_info['list_field']) - 1) {
								$indiviual_field_display .= $item_info['list_field_seperator'];
							}

							$item_rows[0][$r['id']] .= ' '.$indiviual_field_display;
							
							$count += 1;
						}
					} else {
						$item_rows[0][$r['id']] = $r[$item_info['list_field']];
					}
				}
			}
			
			$display_item_list ='';
			foreach ($item_rows as $k => $v) {
				$display_item_list .= ($item_info['categories'])?"\n".'<h3 id="'.underscore($k).'">'.$k.'</h3>':'';
				$display_item_list .= "\n\t".'<ul>';
				foreach ($v AS $k2 => $v2) {
					$display_item_list .= "\n\t\t".'<li id="'.underscore($k).'_'.$k2.'"><a href="?id='.$k2.'">'.$v2.'</a></li>';
				}
				$display_item_list .= "\n\t".'</ul>';
			}
			
			$display_form = "\n".'<div class="item_list">'."\n".$display_item_list."\n".'</div>';
			
		} else {
			$display_form = '<div class="empty_note"><a href="?new=1">Add your first '.$item_info['title_singular'].' now</a><br />'.$item_info['title'].' will be listed here once added to the system</div>';
		}

	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
	
	/*
	function processFileUploads() {

		global $file_list;
		global $g;
		global $item_info;
		
		$folder = $item_info['uploadFolder'] ? $item_info['uploadFolder'] : "Files/Upload";
		
		//$category_path = $item_info['categories'] ? "/{$item_info['categories']}" : "";
		
		$item_path = "{$g['global']['base']}/{$folder}";//"{$g['global']['base']}/{$folder}{$category_path}";
		
		foreach ($file_list as $file) {

			if ($file->isUploadedFile()) {
	
				$file->moveUploadedFile($item_path);
	
				$file_info = $file->getValue();
	
				//if (file_exists("{$item_path}/{$file_info['name']}")) {
				//	unlink("{$item_path}/{$file_info['name']}");
				//}
	
				$old_mask = umask(0);
				@chmod("{$item_path}{$category_path}/{$file_info['name']}", 0777);
				umask($old_mask);
	
				return true;
			}
		}
	}
	*/


?>
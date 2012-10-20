<?php
	require_once 'Includes/Configuration.php';
	require_once 'Simple/Files/Manager.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:3;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = $page_vars['submenu'];
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';
	$g['page']['instructions']	= '';

	$body = "files";

	$g['page']['js_top'] .= <<<HTML

	<script type="text/javascript" src="{$g['page']['js']}/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
	<script type="text/javascript" src="{$g['page']['js']}/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
	<link rel="stylesheet" type="text/css" href="{$g['page']['js']}/fancybox/jquery.fancybox-1.3.4.css" media="screen" />

	<script type="text/javascript" charset="utf-8">
		$(document).ready(function() {
			$(".lbOn").fancybox({
				'width'				: '70%',
				'height'			: '70%',
				'autoScale'			: true
			});
		});
	</script>

HTML;

	// TODO: Tie options into user permissions
	$options = array(
						'title'			  => false,
						'file_store'	  => $file_store,
						'file_list_allow' => true,
						'file_del_allow'  => true,
						'alt_row_enable'  => true,
						'detail_page'	 => 'file_details.php');

	// Instantiate Simple File Manager
	$fm = new simple_files_manager($options);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	// Get any messages
	if ($fm->success_message != '') {
		$display_message = '<div class="success_full">{$fm->success_message}</div>';
		// Log activity
		if ($g['log']['active']==1) {
			$log = new LogManager();
			$log_mssg = str_replace('The file ','Deleted the file ',$fm->success_message);
			$log_mssg = str_replace(' has been deleted successfully!','',$log_mssg);
			$log->adminLogger($g['log']['file'], $user->get_fullname(), $log_mssg);
		}
	} elseif ($fm->error_message != '') {
		$display_message = "<div class=\"error_full\">{$fm->error_message}</div>";
	} elseif ($_GET['emssg']) {
		$display_message = "<div class=\"error_full\">{$_GET['emssg']}</div>";
	} elseif ($_GET['smssg']) {
		$display_message = "<div class=\"success_full\">{$_GET['smssg']}</div>";
	} elseif ($_GET['mssg']) {
		$display_message = "<div class=\"error_full\">{$_GET['mssg']}</div>";
	}

	///////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm #
	///////////////////////////////////////////////////////////////////////////

	if ($_GET['new']) {
		$display_content_title = 'Upload a File';
		$g['page']['instructions'] = '<p><img src="'.$g['page']['images'].'/arrow-left.png" align="absmiddle" />&nbsp;&nbsp;<a href="files.php">Back to File List</a></p>';

		// Instantiate QuickForm
		$form = new HTML_QuickForm('frmUpload', 'post', '?new=1');

		// ------------------------------------------------------------------------

		// Instantiate the renderer
		$renderer =& $form->defaultRenderer();
		// Clear QuickForm template
		$renderer->clearAllTemplates();
		// Define new templates
		$renderer->setFormTemplate($qf_container);
		$renderer->setElementTemplate($qf_element);
		$renderer->setElementTemplate($qf_button, 'btnAdd');

		// ------------------------------------------------------------------------

		// File upload
		$form->addElement('file','fileupload','Your File:');
		// We want files of 10MB or less
		$max_size = 10247680;
		// Make sure that a file is uploaded
		$form->addRule('fileupload','Please select a file to upload','uploadedfile');
		// Have HTML_QuickForm test, after the file is uploaded, that it is less than 2MB
		$form->addRule('fileupload','Your file is too large. Your file must be less that 2MB (2049536 bytes)','maxfilesize',$max_size);
		// Array of acceptable mimetypes
		$mime = $fm->get_mimetype_list();
		$form->addRule('fileupload','This file type is not accepted. Please choose another file to upload','mimetype', $mime);
		// Tell well-behaved browsers not to allow upload of a file larger than allowed
		$form->setMaxFileSize($max_size);
		// Rename
		$form->addElement('text', 'rename', 'Rename file to: <em>(optional)</em>');
		// Button
		$bttn_attr = array('onclick' => 'this.value=\'Uploading...\';');
		$form->addElement('image', 'btnAdd', $g['page']['buttons'].'/btn-uploadfile.gif');

		if ($form->validate()) {
		
			// Upload file to store directory
			$fm->upload_file();

			if ($fm->success_message != '') {
				$p_message = $fm->success_message;
				$param = 'smssg';
				$log_mssg = str_replace('The file ', 'Uploaded the file ', $p_message);
				$log_mssg = str_replace(' has been uploaded successfully.','',$log_mssg);
				// Log activity
				if ($g['log']['active']==1) {
					$log = new LogManager();
					$log->adminLogger($g['log']['file'], $user->get_fullname(), $log_mssg);
				}
			} elseif ($fm->error_message != '') {
				$p_message = $fm->error_message;
				$param = 'emssg';
			}

			header("Location: ".$_SERVER['PHP_SELF'].'?'.$param.'='.$p_message);
		}

		$display_form = $form->toHtml();

	} else {
		$query_add = ($_GET['sort'])?'&sort='.$_GET['sort'].'&sort_direction='.$_GET['sort_direction']:'';
		
		$display_content_title	= '<div class="head-flag-links">Filter:&nbsp;&nbsp;';
		$display_content_title .= ($_GET['filter'] && $_GET['filter']!='all')?'<a href="?filter=all'.$query_add.'">Show All</a> | ':'';
		$display_content_title .= ($_GET['filter']=='documents')?'Documents | ':'<a href="?filter=documents'.$query_add.'">Documents</a> | ';
		$display_content_title .= ($_GET['filter']=='pdfs')?'PDFs | ':'<a href="?filter=pdfs'.$query_add.'">PDFs</a> | ';
		$display_content_title .= ($_GET['filter']=='images')?'Images | ':'<a href="?filter=images'.$query_add.'">Images</a> | ';
		$display_content_title .= ($_GET['filter']=='videos')?'Videos | ':'<a href="?filter=videos'.$query_add.'">Videos</a> | ';
		$display_content_title .= ($_GET['filter']=='archives')?'Archives</div>':'<a href="?filter=archives'.$query_add.'">Archives</a></div>';
		$display_content_title .= ($_GET['filter'])?'Files: '.ucfirst($_GET['filter']):'Files: All';

		$g['page']['instructions']	= '<p><a id="upload-button" class="neutral" href="?new=1"><img src="'.$g['page']['buttons'].'/btn-big-uploadfile.png" alt="Upload File" /></a></p>';

		// Get list of files in project directory
		$list = $fm->build_file_list();

		$fm_table = $fm->display_file_list($g['page']['images'], $g['page']['icons']['files']);

		$display_content_title .= ' <em style="color:green;font-size:10px;font-style:normal;padding:2px;background:#fff">'.$fm->get_filelist_count().'</em>';

		$display_form = <<<HTML

<div class="table-wrapper">
	$fm_table
</div>

HTML;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
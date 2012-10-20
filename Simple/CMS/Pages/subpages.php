<?php

	require_once 'Includes/Configuration.php';
	require_once 'Pager/Pager.php';
	require_once 'Simple/Ajax/Sortable.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:29;

	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$g['page']['instructions'] = '<p><img src="'.$g['page']['images'].'/arrow-left.png" align="absmiddle" />&nbsp;&nbsp;<a href="pages.php">Back to Pages</a></p>';
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "page_subpages";

	$display_content_title = '<div class="head-flag-links"><a href="pages.php">Cancel</a></div>Reorder Subpages';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Sort subpages #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['parent'])) && trim($_GET['parent']) > 0) {
		$parent = $_GET['parent'];
	} else {
		header("Location: pages.php");
		exit;
	}

	$subpages_list = '';

	if ($parent) {
		$sql_subs = <<<SQL

SELECT id, title
FROM pages
WHERE parent = $parent
ORDER BY rank ASC

SQL;
		$q_subs = $db->query($sql_subs);
		if (DB::iserror($q_subs)) { sb_error($q_subs); }

		if ($q_subs->numrows()>1) {
			while ($r_subs = $q_subs->fetchrow(DB_FETCHMODE_ASSOC)) {
				$sub_items .= "\n\t".'<li id="item_'.$r_subs['id'].'"><span class="handle" title="drag">&nbsp;</span>'.$r_subs['title'].'</li>';
			}

			$subpages_list = <<<HTML

<div class="table-wrapper">
	<div id="sortable_list">
		<h3 class="head-sub">Drag &amp; drop to reorder subpages &nbsp;&nbsp;<em id="workingMsg" style="display:none;color:green;">Updating...</em></h3>
		<ul id="listContainer">
			$sub_items
		</ul>
	</div>
</div>

HTML;

		session_start();
		$_SESSION['sortable_table'] = 'pages';
		$sortable_pages = new simple_ajax_sortable($db, $_SESSION['sortable_table']);
		// javascript
		$g['page']['js_top'] .= $sortable_pages->getJS();

		}
	}

	$display_form = $subpages_list;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());

?>
<?php

	require_once 'Includes/Configuration.php';
	require_once 'Pager/Pager.php';
	require_once 'Simple/Ajax/Sortable.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:45;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$g['page']['instructions'] = '<p><img src="'.$g['page']['images'].'/arrow-left.png" align="absmiddle" />&nbsp;&nbsp;<a href="albums.php">Back to Albums</a></p>';
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "gallery";

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Sort subpages #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['category'])) && trim($_GET['category']) > 0) {
		$category = $_GET['category'];
	} else {
		header("Location: albums.php");
		exit;
	}

	$albums_list = '';

	if ($category) {
		if (isset($item_multiple_categories) && $item_multiple_categories) {
			$sql_albums = <<<SQL
SELECT
	ATC.id	AS id, 
	A.title AS title,
	AC.name	AS category_name
FROM albums AS A
JOIN albums2categories AS ATC ON ATC.album_id = A.id
JOIN album_categories AS AC ON AC.id = ATC.category_id
WHERE ATC.category_id = $category
ORDER BY ATC.rank ASC

SQL;
		} else {
			$sql_albums = <<<SQL

SELECT
	a.id	AS id, 
	a.title AS title,
	c.name	AS category_name
FROM albums a, album_categories c
WHERE a.category_id = $category AND c.id = $category
ORDER BY a.rank ASC

SQL;
		}
		
		$q_albums = $db->query($sql_albums);
		if (DB::iserror($q_albums)) { sb_error($q_albums); }

		if ($q_albums->numrows()>1) {
			while ($r_albums = $q_albums->fetchrow(DB_FETCHMODE_ASSOC)) {
				$album_rows .= "\n\t".'<li id="item_'.$r_albums['id'].'"><span class="handle" title="drag">&nbsp;</span>'.$r_albums['title'].'</li>';
				$category_name = $r_albums['category_name'];
			}

			$albums_list = <<<HTML

<div class="table-wrapper">
	<div id="sortable_list">
		<h3 class="head-sub">Drag &amp; drop to reorder albums&nbsp;&nbsp;<em id="workingMsg" style="display:none;color:green;">Updating...</em></h3>
		<ul id="listContainer">
			$album_rows
		</ul>
	</div>
</div>

HTML;

		$display_content_title = '<div class="head-flag-links"><a href="albums.php">Cancel</a></div>Reorder albums in the category "'.$category_name.'"';

		session_start();
		if (isset($item_multiple_categories) && $item_multiple_categories)
			$_SESSION['sortable_table'] = 'albums2categories';
		else
			$_SESSION['sortable_table'] = 'albums';
		$sortable_pages = new simple_ajax_sortable($db, $_SESSION['sortable_table']);
		// javascript
		$g['page']['js_top'] .= $sortable_pages->getJS();

		} else {
			header("Location: albums.php?error=There really is not much to reorder in that category. Add more albums and try again.");
			exit;
		}
	}

	$display_form = $albums_list;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());

?>
<?php
	require_once 'Includes/Configuration.php';
	require_once 'Simple/Image/NewSize.php';
	require_once 'Pager/Pager.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'deleted', 'The '.$article_settings['title_singular'].' <strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = $page_vars['submenu'];
	$g['page']['instructions']	= (!isset($_GET['new']))?'<p><a class="neutral" href="'.$article_settings['path_addedit'].'?new=1"><img src="'.$g['page']['buttons'].'/btn-big-addnewarticle.png" alt="Add a new '.$article_settings['title_singular'].'" /></a></p>':'';
	// $g['page']['instructions'] .= ($display_comments)?'<p class="sidebar-link"><img src="'.$g['page']['images'].'/icon-comment.png" align="absmiddle" />&nbsp;&nbsp;<a href="article_comments.php">Moderate Comments</a><span class="sidebar-alert"><strong>12</strong> unmoderated comments</span></p>':'';
	$g['page']['instructions'] .= '<p class="sidebar-link"><img src="'.$g['page']['images'].'/icon-cat-add.gif" align="absmiddle" />&nbsp;&nbsp;<a href="'.$article_settings['path_categories'].'">Manage Categories</a></p>';
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "articles";

	$display_content_title = $article_settings['title'];

	$sql_articles = <<<SQL

		SELECT id, active, author, title, DATE_FORMAT(dateCrtd, '%b %d') AS article_date, DATE_FORMAT(dateCrtd, '%Y') AS article_year
		FROM {$article_settings['table']}
		ORDER BY dateCrtd DESC

SQL;

	$q_articles = $db->query($sql_articles);

	if ($db->iserror($q_articles)) { sb_error($q_articles); }

	if ($q_articles->numrows()>0) {
		while($r_articles = $q_articles->fetchrow(DB_FETCHMODE_ASSOC)) {
			$article_name = htmlentities($r_articles['title'], ENT_QUOTES);
			$article_author = ($r_articles['author'])?'<br /><em>by '.htmlentities($r_articles['author'], ENT_QUOTES):'';
			$article_state = ($r_articles['active'])?'<span class="published">published</span>':'<span class="draft">draft</span>';
			$published_articles[$r_articles['article_year']][$r_articles['id']] = <<<HTML

<tr>
	<td class="article-state">$article_state</td>
	<td class="article-title"><a href="{$article_settings['path_addedit']}?article={$r_articles['id']}">$article_name</a>$article_author</td>
	<td class="article-date">{$r_articles['article_date']}</td>
</tr>

HTML;
		}

		foreach ($published_articles as $year => $rows) {
			$top_year_class = ($year != date("Y"))?'':' top';

			$display_published_article_rows .= "\n\n".'<tr><td colspan=3" class="year'.$top_year_class.'">'.$year.'</td></tr>';
			foreach ($rows as $row) {
				$display_published_article_rows .= $row;
			}
		}

		$display_articles = <<<HTML

<div class="table-wrapper">
	<table class="articles">
		$display_published_article_rows
	</table>
</div>

HTML;
	} else {
		$display_articles = <<<HTML

<div class="table-wrapper">
	<div class="empty_note">
		<a href="{$article_settings['path_addedit']}?new=1">Add your first {$article_settings['title_singular']} now</a><br />
		Saved drafts and published {$article_settings['title']} will be listed here once added to the system
	</div>
</div>

HTML;
	}

	$display_form = $display_articles;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
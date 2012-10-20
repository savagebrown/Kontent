<?php

    require_once 'Includes/Configuration.php';

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Display Messages #
    /////////////////////////////////////////////////////////////////////////

    $display_message = setDisplayMessage($display_message, 'deleted', 'The page <strong>"%GET%"</strong> has been deleted successfully.');
    $display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Page Settings #
    /////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:1;

    $page_vars            = build_page($db, $admin_page_id);
    $display_page_title   = $page_vars['title'];
    $display_mainmenu     = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu      = $page_vars['submenu'];
	$g['page']['instructions']  = '<p><a class="neutral" href="page.php?session=clear"><img src="'.$g['page']['buttons'].'/btn-big-addnewpage.png" alt="Add a new page" /></a></p>';
	$g['page']['instructions'] .= ($page_html_option)?'<p><a class="neutral" href="page.php?html=raw"><img src="'.$g['page']['buttons'].'/btn-big-addhtmlpage.png" alt="Add a HTML page" /></a></p>':'';
	$g['page']['instructions'] .= ($display_page_sidebars)?'<p class="sidebar-link"><img src="'.$g['page']['images'].'/icon-sidebar.gif" align="absmiddle" />&nbsp;&nbsp;<a href="page_sidebars.php?session=clear">Manage Sidebars</a></p>':'';

    $g['page']['markup']    = ($page_vars['markupref']) ? $g['page']['markup'] : '';
    $display_content_title = 'Website Pages';

	$body = "manage_pages";

    $g['page']['js_top'] .= <<<HTML

<script type="text/javascript">
    <!--
    window.onload=function(){tableruler();}
    //-->
</script>

HTML;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Submenu Extra #
	/////////////////////////////////////////////////////////////////////////

	$display_submenu = '';

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Page List #
    /////////////////////////////////////////////////////////////////////////

	$_SESSION['page_id'] = '';

    $sql_pages ="SELECT * FROM pages WHERE parent = 0 ORDER BY rank ASC, title ASC";
    $q_pages = $db->query($sql_pages);
    if (DB::iserror($q_pages)) { sb_error($q_pages); }
    
    $page_list='';
    while ($r_pages = $q_pages->fetchrow(DB_FETCHMODE_ASSOC)) {
		
		// First check for subpages
		$sql_subpage ="SELECT * FROM pages WHERE parent = ".$r_pages['id']." ORDER BY rank ASC, title ASC";
		$q_subpage = $db->query($sql_subpage);
		if (DB::iserror($q_subpage)) { sb_error($q_subpage); }

		// Set parent info
		$page_protected = ($r_pages['protect'])?' class="protected"':'';
        $page_update_link = 'page.php?page='.$r_pages['id'];
		$reorder_subpages_link = ($q_subpage->numrows()>1)?'&nbsp;&nbsp;<em style="font-size:10px;">(<a href="page_subpages.php?parent='.$r_pages['id'].'">Reorder Subpages</a>)</em>':'';
        $indicate_html = ($r_pages['html'])?' <span class="btn-black">html</span>':'';
		$page_title = '<strong>'.$r_pages['title'].(($r_pages['active'])?'':' <em>(inactive page)</em>').$indicate_html.$reorder_subpages_link;
		$line_style = ($r_pages['active'])?'':' class="inactive"';
        $page_list .= <<<HTML

<tr$line_style onclick="document.location = '$page_update_link';">
    <td$page_protected>$page_title</td>
</tr>

HTML;

		if ($q_subpage->numrows()>0) {
			while ($r_subpage = $q_subpage->fetchrow(DB_FETCHMODE_ASSOC)) {

				// First check for sub-subpages
				$sql_sub_subpage ="SELECT * FROM pages WHERE parent = ".$r_subpage['id']." ORDER BY rank ASC, title ASC";
				$q_sub_subpage = $db->query($sql_sub_subpage);
				if (DB::iserror($q_sub_subpage)) { sb_error($q_sub_subpage); }

				$page_protected = ($r_subpage['protect'])?' class="protected"':'';
				$subpage_update_link = 'page.php?page='.$r_subpage['id'];
				$reorder_subsubpages_link = ($q_sub_subpage->numrows()>1)?'&nbsp;&nbsp;<em style="font-size:10px;">(<a href="page_subpages.php?parent='.$r_subpage['id'].'">Reorder Sub-Subpages</a>)</em>':'';
		        $subpage_indicate_html = ($r_subpage['html'])?' <span class="btn-black">html</span>':'';
				$subpage_title = '<strong>'.$r_subpage['title'].(($r_subpage['active'])?'':' <em>(inactive page)</em>').$subpage_indicate_html.$reorder_subsubpages_link;
				$line_style = ($r_subpage['active'])?'':' inactive';

				$page_list .= <<<HTML

    <tr class="inset$line_style" onclick="document.location = '$subpage_update_link';">
        <td$page_protected style="padding-left:40px;">&rarr;&nbsp;$subpage_title</td>
    </tr>

HTML;
				while ($r_sub_subpage = $q_sub_subpage->fetchrow(DB_FETCHMODE_ASSOC)) {
					$line_style = 'inset-inset';
					$subsubpage_protected = ($r_sub_subpage['protect'])?' class="protected"':'';
					$subsubpage_update_link = 'page.php?page='.$r_sub_subpage['id'];
					$subsubpage_indicate_html = ($r_sub_subpage['html'])?' <span class="btn-black">html</span>':'';
			        $subsubpage_title = '<strong>'.$r_sub_subpage['title'].(($r_sub_subpage['active'])?'':' <em>(inactive page)</em>').$subsubpage_indicate_html;
					$line_style = ($r_sub_subpage['active'])?$line_style.'':$line_style.' inactive';

					$page_list .= <<<HTML

    <tr class="$line_style" onclick="document.location = '$subsubpage_update_link';">
        <td$page_protected style="padding-left:80px;">&rarr;&nbsp;$subsubpage_title</td>
    </tr>

HTML;
	
				}
			}
		}

    }

    $display_form = <<<HTML

<div class="table-wrapper">
	<table id="list_table">
		$page_list
	</table>
</div>

HTML;

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Xipe #
    /////////////////////////////////////////////////////////////////////////

    $tpl = new HTML_Template_Xipe($g['xipe']['options']);
    $tpl->compile($g['xipe']['path'].'/default.tpl');
    include($tpl->getCompiledTemplate());
?>
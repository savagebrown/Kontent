<?php

	require_once 'Includes/Configuration.php';
	require_once 'Pager/Pager.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Comment #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {

		$del_id = $_GET['deleteid'];

		$sql_item ="SELECT author FROM article_comments WHERE id = ".$del_id;
		$q_item = $db->query($sql_item);
		if (DB::iserror($q_item)) {
			sb_error($q_item);
		}
		$r_item = $q_item->fetchrow(DB_FETCHMODE_ASSOC);
		$item_title = $r_item['author'];

		// Remove from db
		$sql = "DELETE FROM article_comments WHERE id = ".$del_id;
		$q = $db->query($sql);
		if (DB::isError($q)) {
			sb_error($q);
		}

		// Redirect
		go_to('article_comments.php', '?deleted='.urlencode($item_title));

	}

	// TODO: make comments list divided by article

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'deleted', 'The comment by <strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'updated', 'The comment by <strong>"%GET%"</strong> has been updated successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:12;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = $page_vars['submenu'];
	$g['page']['instructions'] .= $textile->textileThis($page_vars['instructions']);
	$g['page']['markup']	= ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "article_comments";

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Comments List #
	/////////////////////////////////////////////////////////////////////////

	// Get Article filter
	if (ctype_digit(trim($_SESSION['article_id'])) && trim($_SESSION['article_id']) > 0) {
		$current_article = $_SESSION['article_id'];
		$current_article_filter = ' WHERE article_id = '.$current_article;
	} else {
		$current_article_filter = '';
	}

	$sql_article_comments =<<<SQL

SELECT id, article_id, author, email, url, comment, moderated, DATE_FORMAT(dateCrtd, '%m/%d/%Y') AS comment_date
FROM article_comments
$current_article_filter
ORDER BY dateCrtd

SQL;

	$q_article_comments = $db->query($sql_article_comments);
	if (DB::iserror($q_article_comments)) {
		sb_error($q_article_comments);
	}
	if ($q_article_comments->numrows() > 0) {

		if ($q_article_comments->numrows() > 8) {
			$article_comments_count = '<a href="article_comments.php">View all</a> ('.($q_article_comments->numrows()-8).' more)';
		} else {
			$article_comments_count = '';
		}

		$default_vars='';
		while ($r_article_comments = $q_article_comments->fetchrow(DB_FETCHMODE_ASSOC)) {

			// Get specific comment variables
			if ($r_article_comments['id'] == $_GET['comment']) {
				$idset = true;
				$default_vars['id'] = $r_article_comments['id'];
				$default_vars['article_id'] = $r_article_comments['article_id'];
				$default_vars['author'] = $r_article_comments['author'];
				$default_vars['email'] = $r_article_comments['email'];
				$default_vars['url'] = $r_article_comments['url'];
				$default_vars['comment'] = $r_article_comments['comment'];
				$default_vars['moderated'] = $r_article_comments['moderated'];
				$default_vars['moderated_class'] = ($r_article_comments['moderated'])?'true':'false';
				$default_vars['comment_date'] = $r_article_comments['comment_date'];
			}

			$article_comment_link_location = 'article_comments.php?comment='.$r_article_comments['id'];
			$article_comment_author = $r_article_comments['author'];
			$article_comment_excerpt = truncate_this($r_article_comments['comment'], 13);
			$article_comment_date = $r_article_comments['comment_date'];

			if ($r_article_comments['moderated']) {

				$comments_moderated[] = <<<HTML

<tr onclick="document.location = '$article_comment_link_location';">
	<td class="author" nowrap="nowrap">$article_comment_author</td>
	<td class="article-title">$article_comment_excerpt</td>
	<td class="article-date" nowrap="nowrap">$article_comment_date</td>
</tr>

HTML;

			} else {

				$comments_not_moderated[] = <<<HTML

<tr onclick="document.location = '$article_comment_link_location';">
	<td class="author" nowrap="nowrap">$article_comment_author</td>
	<td class="article-title">$article_comment_excerpt</td>
	<td class="article-date" nowrap="nowrap">$article_comment_date</td>
</tr>

HTML;

			}

		}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Comments Not Moderated (cnm) #
	/////////////////////////////////////////////////////////////////////////

		if (!empty($comments_not_moderated)) {

			$cnm_params = array('itemData'	   => $comments_not_moderated,
								'perPage'	   => 10,
								'delta'		   => 1,
								'append'	   => true,
								'separator'	   => ' | ',
								'clearIfVoid'  => false,
								'urlVar'	   => 'set',
								'useSessions'  => true,
								'closeSession' => true,
								'mode'		   => 'Sliding'
			);
			$cnm_pager = & Pager::factory($cnm_params);
			$cnm_pager_links = $cnm_pager->getLinks();
			$cnm_pager_data = $cnm_pager->getPageData();

			$cnm_navigation = (count($comments_not_moderated)>10)?'Comment Sets:&nbsp;&nbsp;'.$cnm_pager_links['all']:'';

			$cnm_listed = '';
			foreach ($cnm_pager_data AS $k => $v) { $cnm_listed .= $v; }

			$display_cnm = <<<HTML

<div class="table-wrapper">
	<h3>Comments Awaiting Moderation</h3>
	<table id="article_comments_table" class="articles">
		<tr>
			<th class="article-author" nowrap="nowrap">Author</th>
			<th width="100%" class="article-title">Comment Excerpt</th>
			<th class="article-date" nowrap="nowrap">Date</th>
		</tr>

	$cnm_listed

	<tfoot>
		<tr><td colspan="3">$cnm_navigation</td></tr>
	</tfoot>
	</table>
</div>

HTML;

	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Comments Moderated (cm) #
	/////////////////////////////////////////////////////////////////////////

		if (!empty($comments_moderated)) {

			$cm_params = array( 'itemData'	   => $comments_moderated,
								'perPage'	   => 20,
								'delta'		   => 1,
								'append'	   => true,
								'separator'	   => ' | ',
								'clearIfVoid'  => false,
								'urlVar'	   => 'set',
								'useSessions'  => true,
								'closeSession' => true,
								'mode'		   => 'Sliding'
			);
			$cm_pager = & Pager::factory($cm_params);
			$cm_pager_links = $cm_pager->getLinks();
			$cm_pager_data = $cm_pager->getPageData();

			$cm_navigation = (count($comments_moderated)>20)?'Comment Sets:&nbsp;&nbsp;'.$cm_pager_links['all']:'';

			$cm_listed = '';
			foreach ($cm_pager_data AS $k => $v) { $cm_listed .= $v; }

			$display_cm = <<<HTML

<div class="table-wrapper">
	<h3>Moderated Comments</h3>
	<table id="article_published_table" class="articles">
		<tr class="alt">
			<th nowrap="nowrap">Author</th>
			<th class="article-title" width="100%">Comment Excerpt</th>
			<th nowrap="nowrap">Date</th>
		</tr>

	$cm_listed

	<tfoot>
		<tr><td colspan="3">$cm_navigation</td></tr>
	</tfoot>
	</table>
</div>

HTML;
		}

	} else {
		$no_comments_message = <<<HTML

<p style="padding:10px;color:green;font-size:larger;background:#ffffcc;margin:10px 0 25px 0;">No comments have been submitted for this article yet.</p>

HTML;
		$display_cnm = '';
		$display_cm	 = '';
	}

	if (!empty($default_vars)) {

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Setup & Templates #
	/////////////////////////////////////////////////////////////////////////

	// Form setup -----------------------------------------------------------

		// Instantiate QuickForm
		$form = new HTML_QuickForm('frmComment', 'post', $_SERVER['PHP_SELF'].'?comment='.$default_vars['id']);
		// Instantiate the renderer
		$renderer =& $form->defaultRenderer();
		// Clear QuickForm template
		$renderer->clearAllTemplates();

	// Default Template -----------------------------------------------------

		$renderer->setFormTemplate('
<div id="form_container">
	<form{attributes}>
		<table cellspacing="0" cellpadding="0">
			{content}
		</table>
	</form>
</div>
		');

		$renderer->setElementTemplate('

<!-- BEGIN error --><tr><td colspan="2"><div class="error_full">{error}</div></td></tr><!-- END error -->
<tr>
	<td class="label"><!-- BEGIN required --><em class="drf">*</em><!-- END required -->{label}</td>
	<td><span class="trace">{element}</span></td>
</tr>

		');

		$td_html = <<<HTML

		<!-- BEGIN error --><tr><td colspan="2"><div class="error_full">{error}</div></td></tr><!-- END error -->
		<tr>
			<td class="label"><!-- BEGIN required --><em class="drf">*</em><!-- END required -->{label}</td>
			<td>{element}</td>
		</tr>

HTML;

		$renderer->setElementTemplate($td_html, 'moderated');

	// Header ---------------------------------------------------------------

		$renderer->setHeaderTemplate('<tr><td colspan="2"><h2>{header}</h2></td></tr>');

	// Buttons --------------------------------------------------------------

		$plain_html = <<<HTML

<tr>
<td class="last"></td><td class="last">{element}<span style="float:right;font-size:10px;margin-right:20px;"><em class="drf">*</em> denotes required fields</span></td>
</tr>

HTML;

		$renderer->setElementTemplate($plain_html, 'btnSave');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm Fields #
	/////////////////////////////////////////////////////////////////////////

	// Header ---------------------------------------------------------------

		$form->addElement('header', 'hd', 'Edit comment posted on '.$default_vars['comment_date']);

	// Comment Author -------------------------------------------------------

		$form->addElement('text', 'author', 'Author:<br />', array('alt'=>'Comment Author','size'=>45));
		$form->addRule('author', 'Author is a required field.', 'required');

	// Comment Email --------------------------------------------------------

		$form->addElement('text', 'email', 'Email:<br />', array('alt'=>'Comment Author','size'=>45));
		$form->addRule('email', 'An email is a required.', 'required');
		$form->addRule('email', 'Email must be in proper format.', 'email');

	// Comment Email --------------------------------------------------------

		//$form->addElement('html', '<span'.$default_vars['moderated_class'].'">');
		$form->addElement('checkbox', 'moderated', 'Moderated', '&nbsp;&nbsp;<em>Check if this comment is ok by you</em>');

	// Comment Author -------------------------------------------------------

		//$form->addElement('text', 'url', 'URL:<br />', array('alt'=>'Comment URL','size'=>45));

	// Comment Body ---------------------------------------------------------

		$attrs2 = array("rows"=>"5", "cols"=>"55", 'alt'=>'Comment Body');
		$form->addElement('textarea', 'comment', 'Comment:<br />', $attrs2);
		$form->addRule('comment', 'Comment body is required.', 'required');

	// Button ---------------------------------------------------------------

		$form->addElement('image', 'btnSave', $g['page']['buttons'].'/btn-savechanges.gif');
		$form->addElement('hidden', 'formcheck', $display_page_title);
		$form->addElement('hidden', 'id');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Option #
	/////////////////////////////////////////////////////////////////////////

		if ($idset) {
			$linkstuff = js_confirm('?deleteid='.$default_vars['id'],
									'Delete comment by "'.$default_vars['author'].'" from the system?',
									'Are you sure you want to delete -> '.$default_vars['author'].'?',
									'Delete '.$default_vars['author'],
									'larger');
			$delete_item_option = '<tr><td colspan="2"><div class="delete">'.$linkstuff.'<br /><em>(Deleting a comment immediately and permanently removes them from the system. There is no Undo, so be absolutely sure you want to delete this comment.)</em></div></td></tr>';

			$form->addElement('html', $delete_item_option);
		}

		if ($form->validate()) {

	// Clean input ----------------------------------------------------------

			$submit_vars = array(
				'author'	 =>	 $form->getSubmitValue('author'),
				'id'		 =>	 $form->getSubmitValue('id'),
				'comment'	 =>	 $form->getSubmitValue('comment'),
				'email'		 =>	 $form->getSubmitValue('email'),
				'moderated'	 => ($form->getSubmitValue('moderated'))?1:0);

	// Update ---------------------------------------------------------------

			if ($form->getSubmitValue('btnSave_x')) {

				$sql_u = "UPDATE article_comments SET
				author	   = '".safe_escape($submit_vars['author'])."',
				comment	   = '".safe_escape($submit_vars['comment'])."',
				email	   = '".safe_escape($submit_vars['email'])."',
				moderated  =  ".$submit_vars['moderated']."
						  WHERE id = ".$submit_vars['id'];

				$q_u = $db->query($sql_u);
				if (DB::isError($q_u)) {
					sb_error($q_u);
				}

				go_to('article_comments.php', '?updated='.urlencode($form->getSubmitValue('author')));

			}
		} else {
			$form->setDefaults($default_vars);
			$display_form = $form->toHtml();

		}
	}

	$display_form = $no_comments_message.$display_form.$display_cnm.$display_cm;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
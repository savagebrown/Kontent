<?php

	require_once 'Includes/Configuration.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Delete Listing #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['deleteid'])) && trim($_GET['deleteid']) > 0) {
		include 'listing_delete.php';
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = setDisplayMessage($display_message, 'deleted', 'The album <strong>"%GET%"</strong> has been deleted successfully.');
	$display_message = setDisplayMessage($display_message, 'error', '%GET%', true);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$admin_page_id = ($admin_page_id)?$admin_page_id:42;
	
	$page_vars			  = build_page($db, $admin_page_id);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = $page_vars['submenu'];

	$g['page']['instructions']  = "\n".'<p><a class="neutral" href="album.php?session=clear"><img src="'.$g['page']['buttons'].'/btn-big-addnewalbum.png" alt="Add a new photo album" /></a></p>';
	$g['page']['instructions'] .= "\n".'<p class="sidebar-link"><img src="'.$g['page']['images'].'/icon-cat-add.gif" align="absmiddle" />&nbsp;&nbsp;<a href="album_categories.php">Manage Categories</a></p>';
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "gallery";

	$display_content_title = $item_display_title;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Category ID #
	/////////////////////////////////////////////////////////////////////////

	if (ctype_digit(trim($_GET['category'])) && trim($_GET['category']) > 0) {
		$filter_category = true;
		$filter_category_id = $_GET['category'];
	} else {
		$filter_category_sql = '';
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Photo Albums #
	/////////////////////////////////////////////////////////////////////////

	$gallery_albums = '';

	// Album Category -------------------------------------------------------

	$sql_gallery_category = <<<SQL

SELECT id, name
FROM album_categories
ORDER BY rank

SQL;

	$q_gallery_category = $db->query($sql_gallery_category);
	if (DB::iserror($q_gallery_category)) { sb_error($q_gallery_category); }

	while ($r_gallery_category = $q_gallery_category->fetchrow(DB_FETCHMODE_ASSOC)) {

	// Category name --------------------------------------------------------

	$gallery_albums .= "\n".'<div class="gallery_list">'."\n";
	$gallery_albums .= "\n".'<h2><a href="album_rank.php?category='.$r_gallery_category['id'].'"><img src="'.$g['page']['images'].'/icon-reorder.gif" alt="Reorder Albums in the category '.$r_gallery_category['name'].'" /></a>'.$r_gallery_category['name'].'</h2>'."\n";

	// ALbums ---------------------------------------------------------------

	if (isset($item_multiple_categories) && $item_multiple_categories) {

		$sql_gallery_albums = <<<SQL
SELECT
	A.id, A.title, A.description, A.active
FROM 
	albums AS A
JOIN
	albums2categories AS ATC
	ON ATC.album_id = A.id
WHERE
	ATC.category_id = {$r_gallery_category['id']}
ORDER BY
	ATC.rank
		
SQL;
	
	} else {

		$sql_gallery_albums = <<<SQL

SELECT
	id, title, description, active
FROM
	albums
WHERE
	category_id = {$r_gallery_category['id']}
ORDER BY
	rank
SQL;

	}
		$q_gallery_albums = $db->query($sql_gallery_albums);
		if (DB::iserror($q_gallery_albums)) { sb_error($q_gallery_albums); }

		if ($q_gallery_albums->numrows()>0) {
			while ($r_gallery_albums = $q_gallery_albums->fetchrow(DB_FETCHMODE_ASSOC)) {

				// Get preview image
				$sql_preview_image = <<<SQL

SELECT id
FROM album_images
WHERE album_id = {$r_gallery_albums['id']}
ORDER BY rank

SQL;
				$q_preview_image = $db->getOne($sql_preview_image);
				if (DB::iserror($q_preview_image)) { sb_error($q_preview_image); }

				if ($q_preview_image) {
					$photo_album_image_preview = 'Images/Gallery/Albums/'.$r_gallery_albums['id'].'/ms/'.$r_gallery_albums['id'].'_'.$q_preview_image.'.jpg';
				} else {
					$photo_album_image_preview = 'Images/Gallery/Albums/no_image.gif';
				}

				// Get image count
				$sql_image_count ="SELECT COUNT(*) FROM album_images WHERE album_id = ".$r_gallery_albums['id']." ORDER BY rank";
				$album_image_count = $db->getOne($sql_image_count);
				$album_image_count_text = ($album_image_count>1)?'<em>('.$album_image_count.' images)</em>':'<em>(only one image)</em>';
				$album_image_count_text = ($album_image_count!=0)?$album_image_count_text:'<em>(no images)</em>';
				$album_active_state_class = ($r_gallery_albums['active'])?'':' inactive_album';

				$gallery_albums .= <<<HTML

<div class="photo_album_cover$album_active_state_class">
	<a href="album.php?album={$r_gallery_albums['id']}">
		<span class="cover"><img src="{$g['global']['root_images']}/$photo_album_image_preview" alt="{$r_gallery_albums['description']}" width="72" height="72" /></span>
		<p>{$r_gallery_albums['title']}<br />$album_image_count_text</p>
	</a>
</div>

HTML;

			} // end while albums

		} else {
			$gallery_albums .= "\n".'<div class="announcement">There are no albums in this category. <a href="album.php?new=1">Add one now</a>.</div>'."\n";
		}
		
		$gallery_albums .= "\n".'<div class="clear"></div></div>'; // close gallery_list class

	} // end while categories

	$display_form = "\n\n".'<div class="table-wrapper">'."\n\n".$gallery_albums."\n\n".'</div>'."\n\n";

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>
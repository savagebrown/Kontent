<?php

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Pages #
	/////////////////////////////////////////////////////////////////////////
	
	// Page Parent List -------------------------------------------------------

	// Builds select list according to page parent settings
	// requires page id and allowed page depth (default 3)
	function getParentList($page_id, $parent_id=0, $depth=3, $returnSelect=true, $includeParent=true) {
		global $db;

		$sql = <<<SQL

SELECT id, title, parent, rank
FROM pages
WHERE allow_children = 1
ORDER BY id ASC

SQL;

		$q = $db->query($sql);
		if (DB::iserror($q)) { sb_error($q); }

		while($r = $q->fetchrow(DB_FETCHMODE_ASSOC)) {
			$pages[] = array(
							'title'=>$r['title'],
							'id'=>$r['id'],
							'parent'=>$r['parent'],
							'rank'=>$r['rank']
							);
		}

		$tree = makePageTree($pages);
		if ($returnSelect)
			return getParentSelect($tree, $parent_id, $depth);
		else
			return getSelectArray($tree, $page_id, $depth, $parent_id, $includeParent);
	}

	function makePageTree($pages) {
		// Creat a root so math works
		$pagetree = array(array('id' => 'root', 'title'=>'root','parent' => -1, 'children' => array()));
		$treeBase = array(0 => &$pagetree[0]);
		foreach($pages as $item){
			$children = &$treeBase[$item['parent']]['children'];
			$count = count($children);
			$children[$count] = $item;
			$children[$count]['children'] = array();
			$treeBase[$item['id']] = &$children[$count];
		}
		// Return the children of the root node. --NICK
		return $pagetree[0]['children'];
	}

	function RankSort($a, $b) {
		return (int)$a['rank'] > (int)$b['rank'];
	}

	function getParentSelect($pages, $current_page=null, $depth=3, $attributes=null, $dash='--') {

		$s_attributes = "";
		if (isset($attributes)) {
				foreach ($attributes as $key=>$value) {
					$s_attributes .= "$key=\"$value\" ";
			}
		}

			$select = "<select $s_attributes>";
			$select .= getOptions($pages, $current_page, $depth, $dash, 0);
			$select .= "</select>";

			return $select;
	}

	function getSelectArray($pages, $current_id=null, $depth=3, $parent_id=0, $includeParent=true, $dash='--', $cur_depth=0) {

		usort($pages, 'RankSort');

		$array = array();

		foreach ($pages as $page) {
			if (!isset($current_id) || (int)$current_id != (int)$page['id']) {
				if (($parent_id == $page['id'] && $includeParent) || $parent_id != $page['id'])
					$array[$page['id']] = str_repeat($dash, $cur_depth).$page['title'];

				if (!empty($page['children']) && $cur_depth+1 < $depth) {
					$array += getSelectArray($page['children'], $current_id, $depth, $parent_id, $includeParent, $dash, $cur_depth+1);
				}
			}
		}

		return $array;
	}

	function getOptions($pages, $current_page, $depth, $dash, $cur_depth) {

		usort($pages, 'RankSort');

		$options = "";
			foreach ($pages as $page) {

			$selected = "";
			if ($current_page == $page['id']) {
				$selected = "SELECTED";
			}

			$options .= '<option value="'.$page['id'].'" '.$selected.'>'.str_repeat($dash, $cur_depth).$page['title'].'</option>';

			if (!empty($page['children']) && $cur_depth+1 < $depth) {
				$options .= getOptions($page['children'], $current_page, $depth, $dash, $cur_depth+1);
			}
		}
		return $options;
	}

	function getPageUL($pages, $attributes=null, $current_page=null, $depth=3) {

		$s_attributes = "";
		if (isset($attributes)) {
				foreach ($attributes as $key=>$value) {
				$s_attributes .= "$key=\"$value\" ";
			}
		}

		$ul = "<ul $s_attributes>";
		$ul .= getUL($pages, $current_page, $depth, 0);
		$ul .= "</ul>";

			return $ul;
	}

	function getUL($pages, $current_page, $depth, $cur_depth) {

		usort($pages, 'RankSort');

		$ul = "";
		foreach ($pages as $page) {
			$selected = "";

			if ($current_page == $page['id']) {
				$selected = 'class="current"';
			}

			$ul .= "<li $selected>".$page['title'];

			if (!empty($page['children']) && $cur_depth+1 < $depth) {
				$ul .= "<ul>";
				$ul .= getUL($page['children'], $current_page, $depth, $cur_depth+1);
				$ul .= "</ul>";
			}
		}

		return $ul;
	}

?>
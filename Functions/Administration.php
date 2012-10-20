<?php

	/**
	 * Sets administration page variables
	 *
	 * @param object
	 * @param intger
	 * @return array
	 * @author Savage Brown
	 */
	function build_page($db, $page_id = 0) {
		// Set at CMS/Permissions/access.php
		global $user;
		// Variables set with account .ini file
		global $g;
		$user_access_level = $user->get_access_level();
		check_access($db, $user_access_level, $page_id);
		// TODO: change back to adminpage_
		if ($_SESSION['xxadminpage_'.$page_id]) {
			return $_SESSION['adminpage_'.$page_id];
		} else {
		
			$sql_page =<<<SQL
			
SELECT
	p.id			AS page_id,
	p.title			AS page_title,
	p.menu_title	AS page_menu_title,
	p.instructions	AS page_instructions,
	p.page_path		AS page_path,
	p.parent		AS page_parent,
	p.show_textile	AS page_show_textile,
	p.access_level	AS page_access_level,
	p.active		AS page_active
FROM
	pages_admin p
WHERE
	p.active = 1 || p.active = 2 AND
	p.access_level >= $user_access_level
ORDER BY
	p.parent,
	p.rank

SQL;
			$q_page = $db->query($sql_page);
			if ($db->iserror($q_page)) { sb_error($q_page); }

			// Instantiate return array
			$page_items = array();
			// Instantiate menu variables
			$mm = array(); // Main menu
			$sm = array(); // Sub menu
			while ($r_page = $q_page->fetchrow(DB_FETCHMODE_ASSOC)) {

				if ($r_page['page_parent'] == 0) {
					// Build Main Menu
					$mm[$r_page['page_id']] = array(
						'parent'=> $r_page['page_parent'],
						'title' => ($r_page['page_menu_title']) ? $r_page['page_menu_title']:$r_page['page_title'],
						'path'	=> $r_page['page_path'],
						'active'=> $r_page['page_active'],
						'hl'	=> ($page_id == $r_page['page_id']) ? true:false);
				} else {
					// Build Sub Menu
					$sm[$r_page['page_id']] = array(
						'parent'=> $r_page['page_parent'],
						'title' => ($r_page['page_menu_title']) ? $r_page['page_menu_title']:$r_page['page_title'],
						'path'	=> $r_page['page_path'],
						'active'=> $r_page['page_active'],
						'hl'	=> ($page_id == $r_page['page_id']) ? true:false);
				}

				// Set page stuff
				if ($page_id == $r_page['page_id']) {
					$page_items['id']			= $r_page['page_id'];
					$page_items['title']		= $r_page['page_title'];
					$page_items['instructions'] = $r_page['page_instructions'];
					$page_items['markupref']	= $r_page['page_show_textile'];
					$page_items['page_path']	= $r_page['page_path'];
					$page_items['parent']		= $r_page['page_parent'];

					if ($r_page['page_parent'] > 0) {
						$mm[$r_page['page_parent']]['hl'] = true;
						$sm[$r_page['page_id']]['hl'] = true;
					}
				}
			}

			// Mainmenu
			foreach($mm AS $k => $v) {
				$mm_highlight = ($v['hl']) ? ' id="current_page"':'';
				if ($v['active'] == 1) {
					$menu .= "\n".'<li'.$mm_highlight.'><a href="'.$v['path'].'">'.$v['title'].'</a></li>';
				} else if ($v['active'] == 2){
					// Utility Menu
					$utility_menu .= "\n".'<li'.$mm_highlight.'><a href="'.$v['path'].'">'.$v['title'].'</a></li>';
				}
				if ($k == $page_items['parent']) {
					$first_submenu_item = "\n".'<li><a href="'.$v['path'].'">List</a></li>'."\n";
				} else if ($k == $page_items['id']) {
					$first_submenu_item = "\n".'<li class="current"><a href="'.$v['path'].'">List</a></li>'."\n";
				}
			}
			// Submenu
			foreach($sm AS $k => $v) {
				if ($v['parent'] == $page_items['parent']) {
					$sm_highlight = ($v['hl']) ? ' class="current"':'';
					$submenu .= "\n".'<li'.$sm_highlight.'><a href="'.$v['path'].'">'.$v['title'].'</a></li>';
				} else if ($v['parent'] == $page_items['id']) {
					$submenu .= "\n".'<li><a href="'.$v['path'].'">'.$v['title'].'</a></li>';
				}
			}

			$submenu = $first_submenu_item.$submenu;

			$page_items['mainmenu']	   = "\n\n<ul>".$menu."\n</ul>\n\n";
			$page_items['dropdown']	   = $mm;
			$page_items['submenu']	   = "\n\n<ul id=\"subnav\">".$submenu."\n</ul>\n\n";
			$page_items['utilitymenu'] = "\n\n<div id=\"sysnav\">\n<ul>\n
										  <li><span>Logged in as:&nbsp;&nbsp;<strong>".$user->get_fullname()."</strong></span></li>
										 ".$utility_menu."
										  <li><a href=\"?action=logout\">Logout</a></li>
										  <li><a target=\"_blank\" href=\"".$g['global']['root']."\">Goto Website</a></li>
										  \n</ul></div>\n\n";
		
			$_SESSION['adminpage_'.$page_items['id']] = $page_items;
			
			return $page_items;
		}
	}

	function check_access($db, $user_access_level, $page_id) {

		$sql = <<<SQL

SELECT id, access_level
FROM  pages_admin 
WHERE  id = $page_id

SQL;
		$q = $db->query($sql);
		if (DB::iserror($q)) { sb_error($q); }
		$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
		
		if ($r['access_level']>=$user_access_level) {
			return true;
		} else {
			$q = '?error=restricted area';
			$redirect_to = ($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER'].$q:'index.php'.$q;
			//print '<strong>Page access level:</strong> '.$r['access_level'].'<br /><strong>User access level:</strong> '.$user_access_level;
			header ("Location: ".$redirect_to);
			exit;
		}
	}	

	/**
	 * If provided variable is true return display message. Insert variable by
	 * replacing %GET%
	 *
	 * Set $fade to true if fade effect should be applied.
	 * Requires Scriptaculous
	 *
	 * @param mixed
	 * @param mixed
	 * @param string
	 * @param boolean
	 * @param boolean
	 * @return mixed
	 * @author Savage Brown
	 **/
	function setDisplayMessage($display_message, $get, $message, $error=false, $fade=true) {
		if(!$display_message) {

			if (isset($_GET[$get]) && $_GET[$get] != '') {
				$class = (!$error)?'success_full':'error_full';
				$message = str_replace('%GET%', urldecode($_GET[$get]), $message);

				$display_message = <<<HTML

<div class="$class">
	$message
</div>

HTML;
				return $display_message;
				
			} else if (isset($_SESSION[$get]) && $_SESSION[$get] != '') {
				$class = (!$error)?'success_full':'error_full';
				$message = str_replace('%GET%', urldecode($_GET[$get]), $message);

				$display_message = <<<HTML

<div class="$class">
	$message
</div>

HTML;
				$_SESSION[$get]='';
				return $display_message;
			
			} else {
				return FALSE;
			}
		} else {
			return $display_message;
		}
	}

	function version_conversion() {
		global $g;
		$version_conversion = str_replace("'",'', $g['system']['version']);
		$version_conversion = str_replace('_','.', $version_conversion);
		return $version_conversion;
	}


?>
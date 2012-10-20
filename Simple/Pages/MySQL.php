<?php

	// TODO: get rid of the db_table paramater - will always be pages

	/**
	 * MySQL Pages
	 *
	 * Provides MySQL API for the Pages class.
	 *
	 * @package		 SBCMS (SavageBrown Content Management System)
	 * @author		 Koray Girton
	 * @version		 0.5b
	 * @category	 Systems
	 * @copyright	 Copyright (c) 2005 SavageBrown.com
	 * @license		 mine mine mine
	 * @author		 Koray Girton/savagebrown
	 * @filesource
	 */

	// Include Main Pages Class
	require_once 'Simple/Pages/Page.php';

	 /**
	  * This class extends Pages. Adds MySQL page info collection.
	  */
	 Class MySQL_Page extends Pages {
		/**
		 * Custom db table fields
		 * @access private
		 * @var array
		 */
		var $page_info;
		/**
		 * Database table name
		 * @access private
		 * @var string
		 */
		var $db_table = 'pages';
		/**
		 * Table id field name
		 * @access private
		 * @var string
		 */
		var $db_id_field = 'id';
		/**
		 * Path of Inserted images
		 * @access private
		 * @var string
		 */
		var $image_path = '..';

		/**
		 * Constructor class. Overrides Pages Class constructor
		 *
		 * @param int Page ID
		 * @param object DB object
		 * @param string List of DB Table fields
		 * @param string Custom DB Table name
		 * @param string Custom DB Table ID field
		 * @return void
		 */
		function MySQL_Page($id, $db, $fields='', $table='', $id_field='', $images=true) {
			$this->pageID = $id;
			$this->db = $db;
			$this->set_id_field($id_field);
			$this->set_table($table);
			$this->set_page($fields);
		}

		/**
		 * Returns Page Copy. Copy, by default is parsed by textile class. Set
		 * parameter to false if this is not desired.
		 *
		 * @access public
		 * @param bool Will parse with Textile if true
		 * @return string Page copy
		 */
		function get_copy($textile = true, $images = false, $tags='') {

			// Parse textile formatting
			if ($textile) {
				$this->page_copy = $this->textile($this->page_copy);
			}
			// Add page images
			if ($images) {
				$this->page_copy = $this->insert_images($this->page_copy, $this->image_path.'/Images/Inline/');
			}
			// Add page tag replacements
			if (is_array($tags)) {
				foreach($tags AS $k => $v) {
					// First replace those wrapped in <p> tags
					$this->page_copy = str_replace('<p>'.$k.'</p>', $v, $this->page_copy);
					// Then just replace tag alone
					$this->page_copy = str_replace($k, $v, $this->page_copy);
				}
			}

			return $this->page_copy;

		}
		
		function get_HTML() {
			if ($this->page_html) {
				return $this->page_html;
			} else {
				return false;
			}
		}

		function get_template() {
			return $this->page_template;
		}

		/**
		 * Sets Database table ID field if provided
		 *
		 * @access private
		 * @param string Custom DB Table ID field
		 * @return void
		 */
		function set_id_field($id_field) {
			if ($id_field) {
				$this->db_id_field = $id_field;
			}
		}

		/**
		 * Sets Database Table name if provided
		 *
		 * @access private
		 * @param string Custom DB Table name
		 * @return void
		 */
		function set_table($table) {
			if ($table) {
				$this->db_table = $table;
			}
		}

		/**
		 * Sets default page elements (id, title, copy) OR sets array of
		 * custom elements from database. Uses PEAR::DB API to retrieve data
		 *
		 * @access private
		 * @param string Comma separated fields to use in query
		 * @return void
		 */
		function set_page($fields='') {

			if ($fields) {
				$query_fields = $fields;
			} else {
				$query_fields = $this->db_id_field.', title, copy, copy_alt, template, titlebar, metadescription';
			}
			$sql_page .= 'SELECT *
						  FROM '.$this->db_table.'
						  WHERE '.$this->db_id_field.' = '.$this->pageID;
			$q_page = $this->db->query($sql_page);
			if ($this->db->iserror($q_page)) {
				die($q_page->getMessage().'<hr /><pre>'.$q_page->userinfo.'</pre>');
			}
			if ($q_page->numrows() > 0) {
				$r_page = $q_page->fetchrow(DB_FETCHMODE_ASSOC);

				if ($fields) {
					$this->page_info = $r_page;
				} else {
					$this->page_title = $r_page['title'];
					$this->page_titlebar = ($r_page['titlebar'])?$r_page['titlebar']:$r_page['title'];
					if (!$r_page['html']) {
						$this->page_metadescription = $r_page['metadescription'];
						$this->page_copy = $r_page['copy'];
						$this->page_copy_alt  = $r_page['copy_alt'];
						$this->page_template = $r_page['template'];
					} else {
						$this->page_html = $r_page['html'];
					}
				}
			} else {
				die('<div style="padding:15px;
								 background:#ffffcc;
								 color:red;width:350px;
								 border:1px solid red;">
						No page information was retrieved for ID '
						.$this->pageID.'. Please make sure that the page exists.
					</div>');
			}
		}

		/**
		 * Sets the image path parameter
		 *
		 * @access public
		 * @return void
		 */
		function set_image_path($path) {
			$this->image_path = $path;
		}

		function get_titlebar() {
			return $this->page_titlebar;
		}

		function get_metadescription() {
			return $this->page_metadescription;
		}

		/**
		 * Returns array of custom elements from database
		 *
		 * @access public
		 * @return array
		 */
		function get_info() {
			if (is_array($this->page_info)) {
				return $this->page_info;
			} else {
				return false;
			}
		}
		
		
		// Function to be expanded on at a later date, currently returns just the children 1 level deep of
		// the given pageId.
		
		//TODO: Expand function to include options for parents, siblings and multi-level children.
		//NOTE: Need to figure out something with the classes to make them show up perdy for multi-level children and siblings		
		
		function getNav($pageId, $params, $root, $ul_class = '', $selectPage = null) {
		
			$current_page = ($pageId) ? $pageId : $this->pageID;
			$selectPage = ($selectPage == null) ? $current_page : $selectPage;
			
			$parentId = $this->db->getone("SELECT parent FROM {$this->db_table} WHERE {$this->db_id_field} = {$current_page}");
			
			// SET INIT PARAMS
			//*******************************
			$params['getChildren'] = (isset($params['getChildren'])) ? $params['getChildren'] : true;
			$params['childLevel'] = (isset($params['childLevel'])) ? $params['childLevel'] : 1;
			$params['showSelf'] = (isset($params['showSelf'])) ? $params['showSelf'] : false;
			
			//*******************************
			
			if ($params['getChildren']) {
			
				$sql = "SELECT id, title FROM {$this->db_table} WHERE active = 1 AND parent = {$current_page} ORDER BY rank ASC";
				$q_children = $this->db->query($sql);
				
				if ($params['showSelf']) {
					
					$sql_self = "SELECT id, title FROM {$this->db_table} WHERE active = 1 AND id = {$current_page}";
					$q_self = $this->db->query($sql_self);
					$self = $q_self->fetchrow(DB_FETCHMODE_ASSOC);
				
					$list .= ($ul_class) ? "<ul class=\"{$ul_class}\">\n" : "<ul>\n";
					
					$highlight = ($self['id'] == $selectPage) ? ' class="current" ' : '';
					
					$list .= <<<HTML
					<li id="p{$self['id']}"{$highlight}><a href="{$root}?page={$self['id']}">{$self['title']}</a>			
HTML;
				}
			
				if ($q_children->numrows() > 0) {

					$list .= ($ul_class && !$params['showSelf']) ? "<ul class=\"{$ul_class}\">\n" : "<ul>\n";
					
					while ($child = $q_children->fetchrow(DB_FETCHMODE_ASSOC)) {
					
						$highlight = ($child['id'] == $selectPage) ? ' class="current" ' : '';
						
						$list .= <<<HTML
						<li id="p{$child['id']}"{$highlight}><a href="{$root}?page={$child['id']}">{$child['title']}</a>
HTML;
						$list .= "</li>";
					}
										
					$list .= "</ul>\n";
					
				} else {
					
					if ($parentId != 3 && $parentId != 0) {
						$list = $this->getNav($parentId, $params, $root, $ul_class, $current_page);
					}
				
				}
				
				if ($params['showSelf']) $list .= "</li></ul>\n";
				
			}

			return $list;
		}
		

		/**
		 * This method returns an unordered list of children pages for
		 * current parent. If current page is child it retrieves list for parent.
		 *
		 * TODO: make page children list multi level friendly
		 * @access private
		 * @return string
		 */
		function get_children($show_parent=true, $page = '', $root='', $ul_class='') {

			$current_page = ($page)?$page:$this->pageID;
			
			$parent = $this->db->getone("SELECT parent FROM ".$this->db_table." WHERE ".$this->db_id_field." = ".$current_page);
			$sql  = "SELECT id, title FROM ".$this->db_table." WHERE active = 1 AND parent = ";
			if ($parent == 0) {
				$sql .= $current_page;
				$parent_id = $current_page;
			} else {
				$sql .= $parent;
				$parent_id = $parent;
			}
			$parent_title = $this->db->getone("SELECT title FROM ".$this->db_table." WHERE ".$this->db_id_field." = ".$parent_id);

			$parent_id = ($parent == 0)?$current_page:$parent;
			$sql .= " ORDER BY rank ASC";
			$q_children = $this->db->query($sql);
			if ($this->db->iserror($q_children)) {
				die($q_children->getMessage().'<hr /><pre>'.$q_children->userinfo.'</pre>');
			}
			if ($q_children->numrows() > 0) {

				$list = ($ul_class)?"\n<ul class=\"$ul_class\">\n":"\n<ul>\n";
				if ($show_parent) {
					// Add main parent as first item
					$highlight_parent = ($parent == 0)?' class="current"':'';
					$list .= "\t".'<li id="p'.$parent_id.'"'.$highlight_parent.'><a href="'.$root.'?page=parent">'.$parent_title.'</a></li>'."\n";
				}
				$count = 0;
				while ($r_children = $q_children->fetchrow(DB_FETCHMODE_ASSOC)) {
					$count++;
					$hightlight = ($r_children['id'] == $this->pageID)?' class="current" ':'';
					$list .= "\t<li id=\"p".$r_children['id']."\"".$hightlight.'><a href="'.$root.'?page='.$r_children['id'].'">'.$r_children['title']."</a></li>\n";
				}
				return $list."</ul>\n";
			} else {
				return false;
			}

		}

		/**
		 * Replaces placeholder tags with images associated with Page
		 *
		 * @access public
		 * @param string
		 * @return boolean
		 **/
		function insert_images($text, $image_path) {
			$current_page = $this->pageID;
			$sql = <<<SQL

SELECT
	id,
	caption,
	placement
FROM
	page_images
WHERE
	page_id = $current_page
ORDER BY
	rank

SQL;
			$q = $this->db->query($sql);
			if ($this->db->iserror($q)) {
				die($q->getMessage().'<hr /><pre>'.$q->userinfo.'</pre>');
			}
			
			$tag_image = array();
			
			// TODO: Install a check that image exists
			if ($q->numrows() > 0) {
				$imgcount = 0;
				while ($r = $q->fetchrow(DB_FETCHMODE_ASSOC)) {

					switch ($r['placement']) {
						case 1:
							$img_placement = 'picture-left';
							$img_subdir	   = 'm/';
							break;
						case 2:
							$img_placement = 'picture-right';
							$img_subdir	   = 'm/';
							break;
						default:
							$img_placement = 'picture-span';
							$img_subdir	   = 'l/';
							break;
					}
					$imgcount++;
					$caption = ($r['caption'])?"<p>".htmlentities($r['caption']).'</p>':'';

					$tag_image[$imgcount] = <<<HTML

<div class="picture $img_placement">
	<img src="{$image_path}{$img_subdir}{$current_page}_{$r['id']}.jpg" alt="" />
	$caption
</div>

HTML;
					// Find image (within p, h1, h2, h3)
					$tags[$imgcount][] = '/(<p>|<h1>|<h2>|<h3>)?(IMAGE'.$imgcount.')(?![0-9])(<\/p>|<\/h1>|<\/h2>|<\/h3>)?/';
				}

				foreach($tags as $key=>$gtag) {
					foreach ($gtag as $tag) {
						$text = preg_replace($tag, $tag_image[$key], $text);
					}
				}

				// Clean up.
				$remove_tags = '/(<p>|<h1>|<h2>|<h3>)?(IMAGE([0-9]+))(<\/p>|<\/h1>|<\/h2>|<\/h3>)?/';
				$text = preg_replace($remove_tags, '', $text);

			}
				return $text;
		}

		/**
		 * Returns UL list of all active pages
		 *
		 * @param string
		 * @param boolean
		 * @param boolean
		 * @access public
		 * @return string
		 **/
		function get_mainmenu($root = '', $children = false, $showhome = true) {
			$current_page = $this->pageID;
			$sql_parent = <<<SQL

SELECT id, title, page_path
FROM pages
WHERE active = 1 AND parent = 0
ORDER BY rank ASC

SQL;
			$q_parent = $this->db->query($sql_parent);
			if ($this->db->iserror($q_parent)) {
				die($q_parent->getMessage().'<hr /><pre>'.$q_parent->userinfo.'</pre>');
			}
			$sitemap  = "\n".'<ul>';
			while ( $r_parent = $q_parent->fetchrow(DB_FETCHMODE_ASSOC) ) {
				if ( ($showhome == false && !($r_parent['page_path'] == '/')) ||
					 ($showhome == true) ) {
					$active_parent = ($r_parent['id']==$this->pageID)?' class="active"':'';
					$sitemap .= "\n\t".'<li'.$active_parent.'><a href="'.$root.$r_parent['page_path'].'">'.$r_parent['title'].'</a>';

					if ( $children ) {

						$parent_id = $r_parent['id'];
						$sql_children = <<<SQL

SELECT id, title
FROM pages
WHERE active = 1 AND parent = $parent_id
ORDER BY rank ASC

SQL;
						$q_children = $this->db->query($sql_children);
						if ($this->db->iserror($q_children)) {
							die($q_children->getMessage().'<hr /><pre>'.$q_children->userinfo.'</pre>');
						}
						if ($q_children->numrows()>0) {
							$sitemap .= "\n\t\t".'<ul>';
							while ($r_chilren = $q_children->fetchrow(DB_FETCHMODE_ASSOC)) {
								$active_child = ($r_chilren['id'] == $this->pageID)?' class="active"':'';
								$sitemap .= "\n\t\t\t".'<li'.$active_child.'><a href="'.$root.$r_parent['path'].'?page='.$r_chilren['id'].'">'.$r_chilren['title'].'</a></li>';
							}
							$sitemap .= "\n\t\t".'</ul>';
						}
					}
					$sitemap .= "\n\t".'</li>';
				}
			}
			$sitemap .= "\n".'</ul>'."\n";

			return $sitemap;
		}

	 }

?>

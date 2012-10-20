<?php

	/**
	 * Simple Image Gallery
	 *
	 * @package		 Simple
	 * @author		 Koray Girton <koray@savagebrown.com>
	 * @version		 2
	 * @category	 Image
	 * @copyright	 Copyright (c) 2005 SavageBrown.com
	 * @license		 http://opensource.org/licenses/lgpl-license.php
	 *				 GNU Lesser General Public License
	 * @filesource
	 */

	/**
	 * Simple Image Gallery
	 *
	 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
	 */
	class  simple_image_gallery {

		/**
		 * The list should be provided in the following format:
		 *
		 * <pre>
		 *	 // If img_file or img_thumb are not provided the image will be discarded
		 *	 $group1_images[1] = array('img_title'		=> 'Kiz Kalesi (Princess Castle)',
		 *							   'img_caption'	=> 'Beach with a one of a kind view.',
		 *							   'img_file_thumb' => 'Images/Thumbs/kizkalesi.jpg',
		 *							   'img_file'		=> 'Images/kizkalesi.jpg');
		 *
		 *	 $group1_images[2] = array('img_title'		=> 'Hagia Sofia',
		 *							   'img_caption'	=> 'One of the most beautiful buildings in Istanbul.',
		 *							   'img_file_thumb' => 'Images/Thumbs/istanbul.jpg'
		 *							   'img_file'		=> 'Images/istanbul.jpg');
		 *
		 *	 $groups[1] = array('name'		  => 'Things to see in Turkey',
		 *						'description' => 'Largest Open-air museum in the world',
		 *						'images'	  => $group1_images);
		 * </pre>
		 *
		 * @access private
		 * @var array
		 */
		var $thelist = array();
		/**
		 * Group ID
		 * @access private
		 * @var int
		 */
		var $group_id;
		/**
		 * Group Name
		 * @access private
		 * @var string
		 */
		var $group_name;
		/**
		 * Group Description
		 * @access private
		 * @var string
		 */
		var $group_description;
		/**
		 * Group unordered list
		 * @access private
		 * @var string
		 */
		var $groups_list = array();
		/**
		 * Number of groups total
		 * @access private
		 * @var int
		 */
		var $groups_count;
		/**
		 * Postion of group within list
		 * @access private
		 * @var int
		 */
		var $group_position;

		/**
		 * Thumbnail unordered image list
		 * @access private
		 * @var string
		 */
		var $thumbs_list = array();
		/**
		 * Number of images in group
		 * @access private
		 * @var int
		 */
		var $thumbs_count;
		/**
		 * ID of current focus in group
		 * @access private
		 * @var int
		 */
		var $focus_id;
		/**
		 * Postion of focus within group
		 * @access private
		 * @var int
		 */
		var $focus_position;
		/**
		 * Image of current focus in group
		 * @access private
		 * @var string
		 */
		var $focus_image;
		/**
		 * Title of current focus in group
		 * @access private
		 * @var string
		 */
		var $focus_title;
		/**
		 * Caption of current focus in group
		 * @access private
		 * @var string
		 */
		var $focus_caption;
		/**
		 * Description of current focus in group
		 * @access private
		 * @var string
		 */
		var $focus_description;
		/**
		 * Previous group ID
		 * @access private
		 * @var int
		 */
		var $previous_group;
		/**
		 * Next group ID
		 * @access private
		 * @var int
		 */
		var $next_group;
		/**
		 * First group ID
		 * @access private
		 * @var int
		 */
		var $first_group;
		/**
		 * Last group ID
		 * @access private
		 * @var int
		 */
		var $last_group;
		/**
		 * Previous group item ID
		 * @access private
		 * @var int
		 */
		var $previous_thumb;
		/**
		 * Next group item ID
		 * @access private
		 * @var int
		 */
		var $next_thumb;
		/**
		 * First group item ID
		 * @access private
		 * @var int
		 */
		var $first_thumb;
		/**
		 * Last group item ID
		 * @access private
		 * @var int
		 */
		var $last_thumb;
		/**
		 * URL paramater to store/read group ID
		 * @access private
		 * @var string
		 */
		var $url_param_grp = 'c';
		/**
		 * URL paramater to store/read image ID
		 * @access private
		 * @var string
		 */
		var $url_param_img = 'i';

		/**
		 * Constructor Method
		 *
		 * Compiles gallery paramaters, sets states and defines navigation for
		 * groups
		 *
		 * @uses compile_gallery()
		 * @uses build_navigation()
		 * @access private
		 * @return boolean
		 */
		function  simple_image_gallery($submitted_list) {
			if (is_array($submitted_list)) {
				$this->thelist = $submitted_list;
				$this->compile_gallery();
				// Set group navigation paramaters
				$this->build_navigation($this->thelist, $this->group_id, 'groups');
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Captures get variables as id. To reset get variable names use
		 * set_groupid_param() and set_focusid_param()
		 *
		 * @access private
		 * @return void
		 */
		function prepare_id() {
			// Prepare group ID
			if (isset($_GET[$this->url_param_grp]) && $_GET[$this->url_param_grp] != '') {
				$this->group_id = $_GET[$this->url_param_grp];
			} else {
				$this->group_id = false;
			}
			// Prepare image ID
			if (isset($_GET[$this->url_param_img]) && $_GET[$this->url_param_img] != '') {
				$this->focus_id = $_GET[$this->url_param_img];
			} else {
				$this->focus_id = false;
			}
		}

		/**
		 * Loops through the list and uses other functions to set class paramaters
		 *
		 * @uses appoint_group_focus()
		 * @access private
		 * @return void
		 */
		function compile_gallery() {

			$this->prepare_id();

			// Set group count
			$this->groups_count = count($this->thelist);
			// Start count for position
			$count=0;
			// Run through provided array
			foreach ($this->thelist AS $k01 => $v01) {
				$count++;
				// Set if group ID is match
				if ($this->group_id == $k01) {
					$this->appoint_group_focus($k01, $v01);
					$this->group_position = $count;
				} else {
					// Compile groups list
					$this->groups_list[$k01] = "\t".'<li><a href="?'.$this->url_param_grp.'='.$k01.'" alt="'.$v01['name'].'">'.$v01['name'].'</a></li>'."\n";
					$this->groups_list_detail[$k01] = '<td><a href="?'.$this->url_param_grp.'='.$k01.'" alt="'.$v01['name'].'"><img src="'.$v01['preview'].'" alt="'.$v01['name'].'" /></a></td><td><h3><a href="?'.$this->url_param_grp.'='.$k01.'" alt="'.$v01['name'].'">'.$v01['name'].'</a></h3>'.$v01['description'].'</td>';
				}

				// Set first if not already set
				if (!$this->group_id) {
					$this->appoint_group_focus($k01, $v01);
				}

			}

		}

		/**
		 * This method sets group paramaters
		 *
		 * @access private
		 * @param int
		 * @param array
		 * @return void
		 */
		function appoint_group_focus($group_id, $group_info) {
			$this->group_id = $group_id;
			// Group Name
			$this->group_name = $group_info['name'];
			// Group Description
			$this->group_description = $group_info['description'];
			// Group Preview
			$this->group_image = $group_info['preview'];
			// Compile groups list
			$this->groups_list[$group_id] = "\t".'<li class="highlight"><a href="?'.$this->url_param_grp.'='.$group_id.'" alt="'.$group_info['name'].'">'.$group_info['name'].'</a></li>'."\n";
			$this->groups_list_detail[$group_id] = '<td class="highlight"><a href="?'.$this->url_param_grp.'='.$group_id.'" alt="'.$group_info['name'].'"><img src="'.$group_info['preview'].'" alt="'.$group_info['name'].'" /></a></td><td><h3><a href="?'.$this->url_param_grp.'='.$group_id.'" alt="'.$group_info['name'].'">'.$group_info['name'].'</a></h3>'.$group_info['description'].'</td>';
			// Start count to get focus position
			$count=0;
			// Gather current group thumbs
			foreach ($group_info['images'] AS $k => $v) {
				$count++;
				// Set if image ID is match
				if ($k == $this->focus_id) {
					$this->appoint_image_focus($k, $v);
					// Set thumb navigation paramaters
					$this->build_navigation($group_info['images'], $k, 'thumbs');
					$this->focus_position = $count;
				} else {
					// Compile thumbs list
					$this->thumbs_list[$k] = "\t".'<li><a href="?'.$this->url_param_grp.'='.$group_id.'&'.$this->url_param_img.'='.$k.'" title="'.$v['img_title'].'"><img src="'.$v['img_file_thumb'].'" alt="'.$v['img_title'].'" /></a></li>'."\n";
				}
				// Set first if not already set
				if (!$this->focus_id) {
				   $this->appoint_image_focus($k, $v);
				   // Set thumb navigation paramaters
				   $this->build_navigation($group_info['images'], $k, 'thumbs');
				   $this->focus_position = 1;
				}
			}
			// Set group item count
			$this->thumbs_count = count($group_info['images']);
		}

		/**
		 * This method sets image paramaters
		 *
		 * @access private
		 * @param int
		 * @param array
		 * @return void
		 */
		function appoint_image_focus($img_id, $img_info) {
			$this->focus_id = $img_id;
			// Set focus image
			$this->focus_image = $img_info['img_file'];
			// Set focus title
			$this->focus_title = $img_info['img_title'];
			// Set focus caption if available
			$this->focus_caption = $img_info['img_caption'];
			// Set focus caption if available
			$this->focus_description = $img_info['img_description'];
			// Thumbs list
			$this->thumbs_list[$img_id] = "\t".'<li class="highlight"><a href="?'.$this->url_param_grp.'='.$this->group_id.'&'.$this->url_param_img.'='.$img_id.'" title="'.$img_info['img_title'].'"><img src="'.$img_info['img_file_thumb'].'" alt="'.$img_info['img_title'].'" /></a></li>'."\n";
		}

		/**
		 * This method is given the current position of the main list and
		 * defines navigation id for first, previous, next, and last accordingly.
		 *
		 * @access private
		 * @param array
		 * @param integer Current position
		 * @param string can only be "groups" or "thumbs"
		 * @return void
		 */
		function build_navigation($list, $key, $for) {

			$keys = array_keys($list);
			$keyIndexes = array_flip($keys);

			if ($for == 'groups') {
				// Set previous
				if (isset($keys[$keyIndexes[$key]-1])) {
					$this->previous_group = $keys[$keyIndexes[$key]-1];
				} else {
					$this->previous_group = $keys[sizeof($keys)-1];
				}
				// Set next
				if (isset($keys[$keyIndexes[$key]+1])) {
					$this->next_group = $keys[$keyIndexes[$key]+1];
				} else {
					$this->next_group = $keys[0];
				}
				// Set first
				$this->first_group = $keys[0];
				// Set last
				end($list);
				$this->last_group  = key($list);
			} else if ($for == 'thumbs') {
				// Set previous
				if (isset($keys[$keyIndexes[$key]-1])) {
					$this->previous_thumb = $keys[$keyIndexes[$key]-1];
				} else {
					$this->previous_thumb = $keys[sizeof($keys)-1];
				}
				// Set next
				if (isset($keys[$keyIndexes[$key]+1])) {
					$this->next_thumb = $keys[$keyIndexes[$key]+1];
				} else {
					$this->next_thumb = $keys[0];
				}
				// Set first
				$this->first_thumb = $keys[0];
				// Set last
				end($list);
				$this->last_thumb = key($list);
			}
		}

		/**
		 * Returns an HTML unordered list of groups
		 * (linked with url param id)
		 *
		 * @access public
		 * @return string
		 */
		function get_groups($format='LIST') {
			switch ($format) {
			case 'LIST':
				$ul = "\n<ul id=\"groups\">\n";
				foreach ($this->groups_list AS $k => $v) {
					$ul .= $v;
				}
				$ul = $ul."</ul>\n\n";
				return $ul;
				break;
			case 'DETAIL':
				$table = "\n<table id=\"groups_table\">\n";
				foreach ($this->groups_list_detail AS $k => $v) {
					$table .= '<tr>'.$v.'</tr>'."\n";
				}
				$table = $table."</table>\n\n";
				return $table;
				break;
			case 'FOCUS_TREE':
				$ul = "\n<ul id=\"groups\">\n";
				foreach ($this->groups_list AS $k => $v) {
					if ($k != $this->group_id) {
						$ul .= $v;
					} else {
						$tree = "</li>\n".'<li class="group-thumbs">'."\n".$this->get_thumbs()."\n</li><div class=\"group-thumbs-end\"></div>\n";
						$line_item	= str_replace('</li>', $tree, $v);
						$ul .= $line_item;
					}
				}
				$ul = $ul."</ul>\n\n";
				return $ul;


			}
		}

		/**
		 * Returns an HTML unordered list of thumbnail images
		 * (linked with url param id)
		 *
		 * @access public
		 * @return string
		 */
		function get_thumbs($class='thumbs') {
			$ul = "\n".'<ul id="'.$class.'">'."\n";
			foreach ($this->thumbs_list AS $k => $v) {
				$ul .= $v;
			}
			$ul = $ul."</ul>\n\n";
			return $ul;
		}

		/**
		 * Group ID
		 * @access public
		 * @return integer
		 */
		function get_group_id() { return $this->group_id; }
		/**
		 * Group Name
		 * @access public
		 * @return string
		 */
		function get_group_name() { return $this->group_name; }
		/**
		 * Group description
		 * @access public
		 * @return string
		 */
		function get_group_description() { return $this->group_description; }
		/**
		 * Group preview image (path)
		 * @access public
		 * @return string
		 */
		function get_group_image() { return $this->group_image; }

		/**
		 * Focus ID
		 * @access public
		 * @return integer
		 */
		function get_focus_id() { return $this->focus_id; }
		/**
		 * Focus Position
		 * @access public
		 * @return integer
		 */
		function get_focus_position() { return $this->focus_position; }
		/**
		 * Focus Image path
		 * @access public
		 * @return string
		 */
		function get_focus_image() { return $this->focus_image; }
		/**
		 * Focus Image title
		 * @access public
		 * @return string
		 */
		function get_focus_title() { return $this->focus_title; }
		/**
		 * Focus image caption
		 * @access public
		 * @return string
		 */
		function get_focus_caption() { return $this->focus_caption; }
		/**
		 * Focus image description
		 * @access public
		 * @return string
		 */
		function get_focus_description() { return $this->focus_description; }

		/**
		 * Navigation: previous group id
		 * @access public
		 * @return integer
		 */
		function get_previous_group() { return $this->previous_group; }
		/**
		 * Navigation: next group id
		 * @access public
		 * @return integer
		 */
		function get_next_group() { return $this->next_group; }
		/**
		 * Navigation: first group id
		 * @access public
		 * @return integer
		 */
		function get_first_group() { return $this->first_group; }
		/**
		 * Navigation: last group id
		 * @access public
		 * @return integer
		 */
		function get_last_group() { return $this->last_group; }

		/**
		 * Navigation: previous image id
		 * @access public
		 * @return integer
		 */
		function get_previous_thumb() { return $this->previous_thumb; }
		/**
		 * Navigation: next image id
		 * @access public
		 * @return integer
		 */
		function get_next_thumb() { return $this->next_thumb; }
		/**
		 * Navigation: first image id
		 * @access public
		 * @return integer
		 */
		function get_first_thumb() { return $this->first_thumb; }
		/**
		 * Navigation: last image id
		 * @access public
		 * @return integer
		 */
		function get_last_thumb() { return $this->last_thumb; }

		/**
		 * Returns total number of images in group
		 * @access public
		 * @return integer
		 */
		function get_thumbs_count() { return $this->thumbs_count; }
		/**
		 * Returns total number of groups
		 * @access public
		 * @return integer
		 */
		function get_groups_count() { return $this->groups_count; }
		/**
		 * Group Position
		 * @access public
		 * @return integer
		 */
		function get_group_position() { return $this->group_position; }

		/**
		 * Changes group id URL paramater name
		 * @access public
		 * @param string
		 * @return boolean
		 */
		function set_groupid_param($new_param) {
			if ($new_param) {
				$this->url_param_grp = $new_param;
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Changes image id URL paramater name
		 * @access public
		 * @param string
		 * @return boolean
		 */
		function set_focusid_param($new_param) {
			if ($new_param) {
				$this->url_param_img = $new_param;
				return true;
			} else {
				return false;
			}
		}

	}
?>
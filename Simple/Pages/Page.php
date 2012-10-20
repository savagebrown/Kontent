<?php

	 // TODO: Explore repeat region functionality
	 // TODO: Complete XML extension

	/**
	 * Pages
	 *
	 * This class provides a simple way to provide page information by ID. Three
	 * default elements are provided: id, title, and copy. However there are
	 * simple methods to retrieve other fields from the database.
	 *
	 * @package		 SBCMS (SavageBrown Content Management System)
	 * @author		 Koray Girton
	 * @version		 0.5b
	 * @category	 Systems
	 * @copyright	 Copyright (c) 2005 SavageBrown.com
	 * @license		 http://opensource.org/licenses/lgpl-license.php
	 *				 GNU Lesser General Public License
	 * @author		 Koray Girton/savagebrown
	 * @filesource
	 */

	// Include Textile class
	require_once '3rdParty/Textile.php';

	/**
	 * Simple Content Management System Base
	 */
	 Class Pages {

		/**
		 * Page ID
		 * @access private
		 */
		var $pageID;
		/**
		 * Page Title
		 * @access private
		 */
		var $page_title;
		/**
		 * Page Copy
		 * @access private
		 */
		var $page_copy;

		var $page_copy_alt;

		/**
		 * Constructor method
		 * @access public
		 * @param int Page ID
		 * @return void
		 */
		function Pages($pageID) {
			$this->pageID = $pageID;
		}

		/**
		 * Returns Page ID
		 *
		 * @access public
		 * @return int Page ID
		 */
		function get_id() {
			return $this->pageID;
		}

		/**
		 * Returns Page Title
		 *
		 * @access public
		 * @return string Page Title
		 */
		function get_title() {
			return $this->page_title;
		}

		/**
		 * Returns Page Copy. Copy, by default is parsed by textile class. Set
		 * parameter to false if this is not desired.
		 *
		 * @access public
		 * @param bool Will parse with Textile if true
		 * @return string Page copy
		 */
		function get_copy($textile = true) {
			if ($textile) {
				return $this->textile($this->page_copy);
			} else {
				return $this->page_copy;
			}
		}

		/**
		 * Returns Page Copy. Copy, by default is parsed by textile class. Set
		 * parameter to false if this is not desired.
		 *
		 * @access public
		 * @param bool Will parse with Textile if true
		 * @return mixed
		 */
		function get_copy_alt($textile = true, $tags='') {
			if (trim($this->page_copy_alt)) {
				if ($textile) {
					$this->page_copy_alt = $this->textile($this->page_copy_alt);
				}
				if (is_array($tags)) {
					foreach($tags AS $k => $v) {
						// First replace those wrapped in <p> tags
						$this->page_copy_alt = str_replace('<p>'.$k.'</p>', $v, $this->page_copy_alt);
						// Then just replace tag alone
						$this->page_copy_alt = str_replace($k, $v, $this->page_copy_alt);
					}
				}
				return $this->page_copy_alt;
			} else {
				return false;
			}
		}

		/**
		 * Returns a construct of page asset. Adds provided extension to Page ID
		 * and sets in asset path. Example = images/342.jpg
		 *
		 * @access public
		 * @param string Path to asset (images/gallery/)
		 * @param string Asset extension (jpg, txt, swf, etc)
		 * @return string Asset construct
		 */
		function get_asset($path, $ext) {
			// Check if path includes closing slash
			$path = trim($path);
			$path = (substr($path, -1, 1) == '/') ? $path : $path.'/';
			$ext = trim($ext);
			$ext = (substr($ext, 0, 1) == '.') ? $ext : '.'.$ext;
			return $path.$this->pageID.$extension;
		}

		/*
		 * Wrapper for Textile
		 *
		 * @access private
		 * @param string
		 * @return string
		 */
		function textile($copy) {
			// Instantiate Textile
			$textile = new Textile();
			$string = $textile->textileThis($copy);
			$dirty = array(
				"</p><br />",
				"</h1><br />",
				"</h2><br />",
				"</h3><br />",
				"</h4><br />",
				"</h5><br />",
				"</h6><br />",
				"</blockquote><br />",
				"</div><br />",
				"</li><br />",
				"</ul><br />",
				"<ul><br />",
				"</ol><br />",
				"<ol><br />"
			);
			$clean = array(
				"</p>",
				"</h1>",
				"</h2>",
				"</h3>",
				"</h4>",
				"</h5>",
				"</h6>",
				"</blockquote>",
				"</div>",
				"</li>",
				"</ul>",
				"<ul>",
				"</ol>",
				"<ol>"
			);
			$string = str_replace($dirty, $clean, $string);
			return $string;
		}
	 }
?>

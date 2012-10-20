<?php

	/**
	 * Simple Words Articles
	 *
	 * This class will compile all articles into an array and use that array
	 * to deliver the information in various formats. It will deliver a set
	 * number of teaser articles, full article if given an id and returns
	 * various smart lists for navigation and promotion.
	 *
	 * @package		 Simple
	 * @author		 Koray Girton <koray@savagebrown.com>
	 * @version		 2
	 * @category	 Words
	 * @copyright	 Copyright (c) 2006 SavageBrown.com
	 * @license		 http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
	 * @filesource
	 */

	// Default parser. Will allow alternate methds of markup parsing in version 3.
	require_once '3rdParty/Textile.php';

	/**
	 * Simply Words Articles
	 *
	 * @link http://powermatter.local/~koray/_lib/Examples//index.php Examples on how to use this class
	 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
	 */
	class  simple_words_articles {

		/**
		 * Database object instance
		 * @access private
		 * @var object
		 */
		var $db;

		/**
		 * Database table name
		 * @access private
		 * @var string
		 */
		var $db_article_tables = array(
			'table' => 'articles',
			'table_images' => 'article_images',
			'table_categories' => 'article_categories',
			'table_2categories' => 'articles2categories',
			'table_comments' => 'article_comments'
		);

		/**
		 * Array of all articles filtered by category
		 * @access private
		 * @var array
		 */
		var $articles = array();

		/**
		 * Article ID of current article
		 * @access private
		 * @var integer
		 */
		var $article_id;

		/**
		 * Array of all used categories
		 * @access private
		 * @var array
		 */
		var $category_list = array();

		/**
		 * Show only active true
		 * Show all including inactive false
		 * @access private
		 * @var boolean
		 */
		var $active = false;

		/**
		 * Number of words to truncate excerpts to
		 * @access private
		 * @var integer
		 */
		var $truncate_to = 50;

		/**
		 * Array of filter parameters for category and dates
		 * @access private
		 * @var array
		 */
		var $filter_paramaters = array();

		/**
		 * Current article
		 * @access private
		 * @var array
		 */
		var $current_article = array();

		/**
		 * Current article comments
		 * @access private
		 * @var array
		 */
		var $current_article_comments = array();

		/**
		 * Allow comments for current issue
		 * @access private
		 * @var boolean
		 */
		var $comments_allowed = false;

		/**
		 * Maximum number of articles to display
		 * @access private
		 * @var integer
		 */
		var $max_articles = 25;

		/**
		 * Maximum number of highlight articles to display
		 * @access private
		 * @var integer
		 */
		var $max_highlight = 3;

		/**
		 * Maximum number of recent articles to display
		 * @access private
		 * @var integer
		 */
		var $max_recent = 3;

		/**
		 * Number of weeks an article is considered new. 0 will turn feature off
		 * @access private
		 * @var integer
		 */
		var $weeks_new = 2;

		/**
		 * Path to article images directory
		 * @access private
		 * @var string
		 */
		var $image_path = '../Images/Articles/';

		/**
		 * Template for article list
		 * @access private
		 * @var string
		 */
		var $list_template;

		/**
		 * Template for single article
		 * @access private
		 * @var string
		 */
		var $single_template;

		/**
		 * Template for recent article list
		 * @access private
		 * @var string
		 */
		var $recent_template;

		/**
		 * Template for article comments
		 * @access private
		 * @var string
		 */
		var $comments_template;

		/**
		 * Template for highlight article list
		 * @access private
		 * @var string
		 */
		var $highlight_template;

		var $tags = array();

		/**
		 * Constructor Method
		 *
		 * Compiles gallery parameters, sets states and defines navigation for
		 * groups
		 *
		 * @uses compile_gallery()
		 * @uses build_navigation()
		 * @access private
		 * @return boolean
		 */
		function  simple_words_articles($db, $active=true, $image_path='', $db_custom_tables=array()) {
			$this->db = $db;
			$this->db_tables = ($db_custom_tables)?$db_custom_tables:$this->db_article_tables;
			$this->image_path = ($image_path)?$image_path:$this->image_path;
			$this->active=$active;
			$this->apply_filters();
			$this->build_articles();
		}

		/**
		 * Returns list of articles
		 * @access public
		 * @return array
		 */
		function get_articles($tags='') {
			if ($this->filter_paramaters['issue']) {
				if ($tags) { $this->set_tags($tags); }
				return $this->populate_template($this->current_article, 'SINGLE');
			} else {
				return $this->populate_template($this->filter_articles($this->articles), 'LIST');
			}
		}

		function get_article_id() {
			if ($this->article_id) {
				return $this->article_id;
			} else {
				return false;
			}
		}

		/**
		 * Returns true id comment is allowed. Can only return true if issue is set
		 * @access public
		 * @var boolean
		 */
		function is_comment_allowed() {
			return $this->comments_allowed;
		}

		function get_archives_by_year($root, $show_count=true) {

			$qspos = (strpos($root, '?')) ? "&" : "?";

			if (is_array($this->articles)) {

				foreach ($this->articles AS $year => $months) {
					$highlight = ($this->filter_paramaters['year'] != $year)?'':' class="highlight_year"';
					$archive_list .= '<li'.$highlight.'><a href="'.$root.$qspos.'year='.$year.'">'.$year.' Articles</a>';
					if ($show_count) {
						$article_count = 0;
						foreach ($months AS $month => $day) {
							$article_count += count($day);
						}
						$archive_list .= ' <em>('.$article_count.')</em></li>'."\n";
					} else {
						$archive_list .= '</li>'."\n";
					}
				}

				$archive_list .= ($this->filter_paramaters['year'])?"<li class=\"all_listings\"><a href=\"".$root."\">Back to current listings</a></li>":'';
				$archive_list  = "\n<ul id=\"archive-year\">\n".$archive_list."\n</ul>\n";

			}

			if ($archive_list) {
				return $archive_list;
			} else {
				return false;
			}

		}

		/**
		 * Returns a UL list of dates
		 * @access public
		 * @param boolean
		 * @return mixed Return false on fail
		 */
		function get_archives($root='') {

			$all_months = array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,
								7=>0,8=>0,9=>0,10=>0,11=>0,12=>0);
			if (is_array($this->articles)) {

				// Define the first year and last year.
				// We'll trim them down later
				$articles_reverese = $this->articles;
				$latest_year = key($articles_reverese);
				ksort($articles_reverese);
				$earliest_year = key($articles_reverese);

				$ul = "\n<ul id=\"archive_list\">\n";

				foreach ($this->articles AS $year => $months) {
					// Highlight archive year
					$highlight_year = ($this->filter_paramaters['year'] == $year)?' class="highlight_year"':'';
					$ul .= "\t".'<li'.$highlight_year.'><a href="'.$_SERVER['PHP_SELF'].'?year='.$year.'">'.$year."</a>\n\t\t<ul>\n";

					$archived_months = array();
					$newmonths = $all_months;

					foreach ($months AS $month => $day) {
						foreach ($day as $key => $value) {
							$count = count($value);
						}
						$archived_months[$month] = $count;
					}

					// Combine full calendar with archived months
					foreach ($archived_months AS $k => $v) { $newmonths[intval($k)] = $v; }

					// Clean empty early months in first year
					if ($year == $earliest_year) {
						krsort($newmonths);
						foreach($newmonths AS $k => $v) { if ($v>0) { $cut = $k; } }
						ksort($newmonths);
						for ($i = 1; $i < $cut; $i++) { unset($newmonths[$i]); }
						krsort($newmonths);
					} else {
						krsort($newmonths);
					}

					// Clean empty top months for latest month
					if ($year == $latest_year) {

						foreach ($newmonths AS $k => $v) {
							if(!$stop) {
								if ($v>0) {
									$stop = true;
								} else {
									unset($newmonths[$k]);
								}
							}
						}
					}

					// Now add the months as line items
					foreach ($newmonths AS $k => $v) {

						if ($v>0) {
							$archive_item = '<a href="?year='.$year.'&month='.
											$this->int_month($k).'">'.
											$this->int_month($k).'</a> <em>('.$v.')</em>';
						} else {
							$archive_item = $this->int_month($k);
						}
						// Highlight archive month
						$highlight_month = ($this->filter_paramaters['month'] == $this->int_month($k) && $this->filter_paramaters['year'] == $year)?' class="highlight_month"':'';
						$ul .= "\t\t\t<li".$highlight_month.">".$archive_item;
						$ul .= "</li>\n";
					}
					$ul .= "\t\t</ul>\n\t</li>\n";
				}
				$ul .= '</ul>';
				$ul .= ($this->filter_paramaters['year'])?"\t<p class=\"all_listings\"><a href=\"".$root.'?archives=all'."\">Back to current listings</a></p>\n":'';

				return $ul;
			} else {
				return false;
			}
		}

		/**
		 * Current archive filter date
		 * @access public
		 * @return mixed
		 */
		function get_archive_date() {
			if ($this->filter_paramaters['year'] != '') {
				$date = $this->filter_paramaters['year'];
				if ($this->filter_paramaters['month'] != '') {
					$date = $this->filter_paramaters['month'].', '.$this->filter_paramaters['year'];
				}
				return $date;
			} else {
				return false;
			}
		}

		/**
		 * Returns $count number of latest articles
		 * @access public
		 * @param integer
		 * @return string
		 */
		function get_highlights($root='') {
			if ($this->populate_template($this->articles, 'HIGHLIGHT', $root)!=''){
				return $this->populate_template($this->articles, 'HIGHLIGHT', $root);
			} else {
				return false;
			}
		}

		/**
		 * Returns top articles in articles array
		 * @access public
		 * @param integer
		 * @return string
		 */
		function get_recent($root='') {
			return $this->populate_template($this->articles, 'RECENT', $root);
		}

		/**
		 * Returns comments list. Return false if no comments
		 * @access public
		 * @param integer
		 * @return mixed
		 */
		function get_comments() {
			if (!empty($this->current_article_comments)) {

				$tags = array('%ID%', '%AUTHOR%', '%TEXT%', '%COMMENTDATE%');
				foreach ($this->current_article_comments AS $k => $values) {
					$alt_class = ($k & 1) ? 'odd_comment' : 'even_comment';
					$comments_list .= str_replace($tags, $values, $this->default_comment_template($alt_class));
				}
				$comments_list =  <<<HTML

<div id="comments">
<h2>Comments</h2>
$comments_list
</div>

HTML;

				return $comments_list;
			} else {
				return false;
			}
		}

		/**
		 * Returns a UL list of categories.
		 * note: build_article_list() must be initiated first
		 * @access public
		 * @param boolean
		 * @return mixed Return false on fail
		 */
		function get_categories($show_count=true, $add_ul=true, $root='') {

			if (is_array($this->category_list)) {

				arsort($this->category_list);

				$ul_start = '';
				$ul_end	  = '';

				if ($add_ul) {
					$ul_start = "\n<ul id=\"category_list\">\n";
					$ul_end	  = "</ul>\n";
				}

				foreach($this->category_list AS $id => $v) {
					foreach($v AS $name => $count) {
						$highlight = ($this->filter_paramaters['category'] != $name)?'':' class="highlight_category"';
						$count = ($show_count)?' <em>('.$count.')</em>':'';
						$ul .= "\t".'<li'.$highlight.'><a href="'.$root.'?category='.$name.'">'.$name.'</a>'.$count."</li>\n";
					}
				}

				$ul .= ($this->filter_paramaters['category'])?"\t<li class=\"all_listings\"><a href=\"".$root.'?categories=all'."\">View all categories</a></li>\n":'';

				return $ul_start.$ul.$ul_end;
			} else {
				return false;
			}
		}

		/**
		 * Replace default template
		 * @access public
		 * @param string
		 * @return void
		 */
		function set_template($html,$for) {
			switch ($for) {
				case 'LIST':	  $this->list_template		= $html; break;
				case 'SINGLE':	  $this->single_template	= $html; break;
				case 'COMMENTS':  $this->comments_template	= $html; break;
				case 'HIGHLIGHT': $this->highlight_template = $html; break;
				case 'RECENT':	  $this->recent_template	= $html; break;
			}
		}

		function set_tags($tags) {
			if (!empty($tags)) {
				$this->tags = $tags;
			}
		}

		function replace_tags($copy) {
			if(!empty($this->tags)) {
				foreach ($this->tags as $k => $v) {
					// First replace those wrapped in <p> tags
					$copy = str_replace('<p>'.$k.'</p>', $v, $copy);
					// Then just replace tag alone
					$copy = str_replace($k, $v, $copy);
				}
			}
			return $copy;
		}

		/**
		 * Replace default truncate_to parameter which controls the number of
		 * words to allow in created excerpt if excerpt itself is null.
		 * @access public
		 * @param int
		 * @return void
		 */
		function set_truncate_to($int) {
			$this->truncate_to = $int;
		}

		/**
		 * Creates two lists. One full (might be filtered for active posts) which will be used
		 * to define archive list and category list. Another filtered according to category set.
		 * @access private
		 * @return void
		 */
		function build_articles() {

 // Articles ------------------------------------------------------------------

			if ($this->active) {
				$filter = ($this->active)?" WHERE a.active = 1":'';
			}

			$sql_articles = <<<SQL

SELECT a.id AS id,
	a.author AS author,
	a.article_link AS article_link,
	a.title AS title,
	a.excerpt AS excerpt,
	a.body AS body,
	a.sidebar AS sidebar,
	a.call_out AS call_out,
	a.active AS active,
	a.highlight AS highlight,
	a.allow_comments AS allow_comments,
	a.home_display AS home_display,
	a.rank AS rank,
	DATE_FORMAT(a.dateCrtd, '%Y') AS yearCrtd,
	DATE_FORMAT(a.dateCrtd, '%m') AS monthCrtd,
	DATE_FORMAT(a.dateCrtd, '%d') AS dayCrtd,
	DATE_FORMAT(a.dateCrtd, '%b %d') AS display_date,
	DATE_FORMAT(a.dateCrtd, '%W, %M %d, %Y') AS display_date_long,
	TIME_FORMAT(a.dateCrtd, '%h:%i%p') AS display_time,
	a.dateCrtd AS dateCrtd,
	a.dateMdfd AS dateMdfd
FROM
	{$this->db_tables['table']} a
$filter
ORDER BY
	a.dateCrtd DESC

SQL;
			$q_articles = $this->db->query($sql_articles);
			if ($this->db->iserror($q_articles)) {
				die($q_articles->getMessage().'<hr /><pre>'.$q_articles->userinfo.'</pre>');
			}

			if ($q_articles->numrows() > 0) {

				// Instantiate Textile
				$textile = new Textile();

				// Instantiate
				$article_list = array();
				$article_count = 0;


				while ($r_articles = $q_articles->fetchrow(DB_FETCHMODE_ASSOC)) {
					$article_list[$r_articles['yearCrtd']]
								 [$r_articles['monthCrtd']]
								 [$r_articles['dayCrtd']]
								 [$r_articles['id']]= $r_articles;

					$article_list[$r_articles['yearCrtd']]
								 [$r_articles['monthCrtd']]
								 [$r_articles['dayCrtd']]
								 [$r_articles['id']]= array(

								 'id'			  => $r_articles['id'],
								 'year'			  => $r_articles['yearCrtd'],
								 'month'		  => $r_articles['monthCrtd'],
								 'day'			  => $r_articles['dayCrtd'],
								 'author'		  => $r_articles['author'],
								 'article_link'	  => $r_articles['article_link'],
								 'title'		  => htmlentities($r_articles['title']),
								 'excerpt'		  => ($r_articles['excerpt'])?$textile->textileThis($this->truncate($r_articles['excerpt'], $this->truncate_to)):
																			  $textile->textileThis($this->truncate(str_replace('!IMAGE1!', '', $r_articles['body']), $this->truncate_to)),
								 'body'			  => $r_articles['body'], // Apply markup on individual basis
								 'sidebar'		  => $r_articles['sidebar'],
								 'call_out'		  => $r_articles['call_out'],
								 'active'		  => $r_articles['active'],
								 'highlight'	  => ($r_articles['highlight'])?' class="highlight_article"':'',
								 'allow_comments' => $r_articles['allow_comments'],
								 'home_display'	  => $r_articles['home_display'],
								 'short_date'	  => $r_articles['display_date'],
								 'long_date'	  => $r_articles['display_date_long'],
								 'time'			  => $r_articles['display_time'],
								 'start_time'	  => $r_articles['start_time'],
								 'end_time'		  => $r_articles['end_time'],
								 'new'			  => ($this->mark_new($r_articles['dateCrtd']))?'<span class="new_article">New</span>':''
					);

 // Categories --------------------------------------------------------------

					$sql_article_categories ="SELECT ac.name AS name,
													 ac.id AS id
											  FROM	 {$this->db_tables['table_2categories']} a2c,
													 {$this->db_tables['table_categories']} ac
											  WHERE	 a2c.article_id = ".$r_articles['id']." AND
													 ac.id = a2c.category_id
											  ORDER BY ac.name ASC";
					$q_article_categories = $this->db->query($sql_article_categories);
					if ($this->db->iserror($q_article_categories)) {
						die($q_article_categories->getMessage().'<hr /><pre>'.$q_article_categories->userinfo.'</pre>');
					}

					if ($q_article_categories->numrows() > 0) {
						$article_categories = array();
						while ($r_article_categories = $q_article_categories->fetchrow(DB_FETCHMODE_ASSOC)) {
							// Article specific categories
							$article_categories[$r_article_categories['id']] = $r_article_categories['name'];
							// Add categories to nav list as well
							$this->category_list[$r_article_categories['id']][$r_article_categories['name']]++;
						}

						// Add to article list array
						$article_list[$r_articles['yearCrtd']]
									 [$r_articles['monthCrtd']]
									 [$r_articles['dayCrtd']]
									 [$r_articles['id']]
									 ['categories']= $article_categories;

						// Add to article list array
						$article_list[$r_articles['yearCrtd']]
									 [$r_articles['monthCrtd']]
									 [$r_articles['dayCrtd']]
									 [$r_articles['id']]
									 ['categories_formatted']= $this->format_categories($article_categories);
					}

 // Comment Count -----------------------------------------------------------

					if ($this->filter_paramaters['issue'] != $r_articles['id']) {

						if ($r_articles['allow_comments'] == 1) {

							$sql_cmmnt = "SELECT COUNT(*) AS num_rows
										  FROM {$this->db_tables['table_comments']}
										  WHERE article_id = ".$r_articles['id']."
										  ORDER BY dateCrtd ASC";
							$q_cmmnt = $this->db->query($sql_cmmnt);
							if ($this->db->iserror($q_cmmnt)) {
								die($q_cmmnt->getMessage().'<hr /><pre>'.$q_cmmnt->userinfo.'</pre>');
							}
							$r_cmmnt = $q_cmmnt->fetchrow(DB_FETCHMODE_ASSOC);

							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['comment_count']= '<a href="?issue='.$r_articles['id'].'#comments">'.$r_cmmnt['num_rows'].' Comments</a>';
						} else {
							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['comment_count']= '';
						}


 // First Article Image -----------------------------------------------------

						// TODO: Update sql to get first image and not dependent on rank equalling one
						$sql_img ="SELECT id FROM {$this->db_tables['table_images']} WHERE article_id = ".$r_articles['id']." ORDER BY rank, id";
						$q_img = $this->db->getOne($sql_img);
						if ($this->db->iserror($q_img)) {
							die($q_img->getMessage().'<hr /><pre>'.$q_img->userinfo.'</pre>');
						}
						if ($q_img) {

							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['image']= '<img src="'.$this->image_path.'m/'.$r_articles['id'].'_'.$q_img.'.jpg" alt="'.htmlentities($r_articles['title']).'" />';
							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['imageFull']= '<img src="'.$this->image_path.'l/'.$r_articles['id'].'_'.$q_img.'.jpg" alt="'.htmlentities($r_articles['title']).'" />';
							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['imageFile']= $r_articles['id'].'_'.$q_img.'.jpg';
						} else {
							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['image']= '';
							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['imageFull']= '';
							// Add to article list array
							$article_list[$r_articles['yearCrtd']]
										 [$r_articles['monthCrtd']]
										 [$r_articles['dayCrtd']]
										 [$r_articles['id']]
										 ['imageFile']= '';
						}

					} else {

 // Current Article Comments ------------------------------------------------

						$sql_cmmnts ="SELECT id, author, comment, DATE_FORMAT(dateCrtd, '%l:%i%p %e %b %Y') AS comment_date
									  FROM {$this->db_tables['table_comments']}
									  WHERE article_id = ".$r_articles['id']."
									  ORDER BY dateCrtd";
						$q_cmmnts = $this->db->query($sql_cmmnts);
						if ($this->db->iserror($q_cmmnts)) {
							die($q_cmmnts->getMessage().'<hr /><pre>'.$q_cmmnts->userinfo.'</pre>');
						}
						while ($r_cmmnts = $q_cmmnts->fetchrow(DB_FETCHMODE_ASSOC)) {

							$this->current_article_comments[$r_cmmnts['id']] =
										array($r_cmmnts['id'],
											  $r_cmmnts['author'],
											  $textile->textileThis($r_cmmnts['comment']),
											  strtolower($r_cmmnts['comment_date']));

						}

						// Set comments allowed paramater
						$this->comments_allowed = ($r_articles['allow_comments'])?true:false;

 // Current Article ---------------------------------------------------------

						$this->article_id = $r_articles['id'];

						$this->current_article = $article_list[$r_articles['yearCrtd']]
															  [$r_articles['monthCrtd']]
															  [$r_articles['dayCrtd']]
															  [$r_articles['id']];

						// Get list of article images
						$sql_img = <<<SQL

SELECT id, article_id, caption, placement, rank
FROM {$this->db_tables['table_images']}
WHERE article_id = {$r_articles['id']}
ORDER BY rank ASC

SQL;
						$q_img = $this->db->query($sql_img);
						if ($this->db->iserror($q_img)) {
							die($q_img->getMessage().'<hr /><pre>'.$q_img->userinfo.'</pre>');
						}
						$count=0;
						while ($r_img = $q_img->fetchrow(DB_FETCHMODE_ASSOC)) {

							switch ($r_img['placement']) {
								case 1:
									$img_placement = ' class="picture picture-left"';
									$img_subdir	   = 'm/';
									break;
								case 2:
									$img_placement = ' class="picture picture-right"';
									$img_subdir	   = 'm/';
									break;
								default:
									$img_placement = ' class="picture picture-span"';
									$img_subdir	   = 'l/';
									break;
							}
							$img_caption = ($r_img['caption'])?'<p>'.$r_img['caption'].'</p>':'';
							$count++;
							$img_tags[] = 'IMAGE'.$count;
							$img_values[] = '<div'.$img_placement.'><img src="'.$this->image_path.$img_subdir.$r_img['article_id'].'_'.$r_img['id'].'.jpg" alt="" />'.$img_caption.'</div>';
						}

						// CHANGED: added captions to images and style specification

						$this->current_article['body'] = str_replace($img_tags, $img_values, $this->current_article['body']);

						// Apply textile markup
						$this->current_article['body'] = $textile->textileThis($this->current_article['body']);

					}

				}

				$this->articles = $article_list;

			} else {
				return false;
			}
		}

		/**
		 * This method determines if article is new
		 * @access private
		 * @param date
		 * @return boolean
		 */
		function mark_new($date) {
			$utime = strtotime($date);
			if ($utime >= strtotime('-'.$this->weeks_new." weeks")) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Formats category list per post
		 * 1. Cat1
		 * 2. Cat1 & Cat2
		 * 3. Cat1, Cat2, Cat3
		 * @access private
		 * @param array
		 * @return string
		 */
		function format_categories($array) {
			if (is_array($array)) {
				$target = '';
				$seperator = (count($array) == 2) ? ' & ' : ', ';
				foreach($array AS $k => $v) {
					$cat_list .= '<a href="'.$target.'?category='.$v.'">'.$v.'</a>'.$seperator;
				}
				$cat_list = trim($cat_list);
				// Strip trailing comma or ampersand
				$cat_list = substr($cat_list, 0, -1);
				// HTML entity: clean up ampersand
				$cat_list = str_replace(' & ', ' &amp; ', $cat_list);
				return $cat_list;
			} else {
				return false;
			}
		}

		/**
		 * Filters out articles depending on $filter_paramaters
		 * @access private
		 * @param array
		 * @return array
		 */
		function filter_articles($articles) {

			$list = $articles;

			// Filter to year
			if (isset($this->filter_paramaters['year']) && $this->filter_paramaters['year'] != '') {
				foreach ($list AS $year => $v) {
					if ($this->filter_paramaters['year'] != $year) {
						unset($list[$year]);
					}
					if (isset($this->filter_paramaters['month']) && $this->filter_paramaters['month'] != '') {
						foreach ($v AS $month => $v2) {
							if ($this->filter_paramaters['month'] != $this->int_month(ceil($month))) {
								unset($list[$year][$month]);
							}
						}
					}
				}
			}

			// Filter to category
			if (isset($this->filter_paramaters['category']) && $this->filter_paramaters['category'] != '') {
				foreach ($list AS $year => $v) {
					foreach ($v AS $month => $v2) {
						foreach ($v2 AS $day => $id) {
							foreach ($id AS $realid => $param) {
								if ($param['categories']) {
									if (!in_array($this->filter_paramaters['category'], $param['categories'])) {
										// Remove from list
										unset($list[$year][$month][$day][$realid]);
									}
								}
							}
						}
					}
				}
			}

			return $list;
		}

		/**
		 * Abstraction for $_GET. If category, year, or month is set set them to
		 * array
		 * @access private
		 * @return void
		 */
		function apply_filters() {
			if (isset($_GET['year']) && $_GET['year'] != '') {
				$this->filter_paramaters['year'] = $_GET['year'];
			}
			if (isset($_GET['month']) && $_GET['month'] != '') {
				$this->filter_paramaters['month'] = $_GET['month'];
			}
			if (isset($_GET['category']) && $_GET['category'] != '') {
				$this->filter_paramaters['category'] = $_GET['category'];
			}
			if (isset($_GET['issue']) && $_GET['issue'] != '') {
				$this->filter_paramaters['issue'] = $_GET['issue'];
			}
		}

		/**
		 * Replaces tags with values per article
		 * @access private
		 * @return string
		 */
		function populate_template($list, $type, $root='') {
			// Tags
			$tags = array(
				'%ID%','%YEAR%','%MONTH%','%DAY%','%AUTHOR%','%ARTICLE_LINK%',
				'%TITLE%','%EXCERPT%','%BODY%','%SIDEBAR%','%CALLOUT%',
				'%PUBLISHED%','%HIGHLIGHT%','%ALLOWCOMMENTS%','%HOMEDISPLAY%',
				'%SHORTDATE%','%LONGDATE%','%TIME%', '%START_TIME%',
				'%END_TIME%', '%NEW%','%CATEGORIES_ARRAY%', '%CATEGORIES%',
				'%COMMENTS%', '%IMAGE%', '%IMAGEFULL%', '%IMAGEFILE%');

			$compiled = '';

			switch ($type) {

				case 'LIST':
					// Establish template
					if ($this->list_template != '') {
						$template = $this->list_template;
					} else {
						$template = $this->default_list_template();
					}
					$count_year=0;
					$count_article=0;
					foreach ($list AS $year => $v) {
						if (!($count_article === $this->max_articles)) {
							$count_year++;
							if ($count_year != 1) {
								// TEMP TURN OFF // $compiled .= '<h2>'.$year.'</h2>';
							}
							foreach ($v AS $month => $v2) {
								if (!($count_article === $this->max_articles)) {
									foreach ($v2 AS $day => $id) {
										if (!($count_article === $this->max_articles)) {
											$count_article++;
											foreach ($id AS $value) {
												if ($count <= $this->max_articles) {
													$compiled .= str_replace($tags, $value, $template);
												}
											}
										} else {
											break;
										}
									}
								} else {
									break;
								}
							}
						} else {
							break;
						}
					}

				break;
				case 'SINGLE':
					// Establish template
					if ($this->single_template != '') {
						$template = $this->single_template;
					} else {
						$template = $this->default_single_template();
					}
					$this->current_article['body'] = $this->replace_tags($this->current_article['body']);
					$compiled = str_replace($tags, $this->current_article, $template);

				break;
				case 'RECENT':
					// Establish template
					if ($this->recent_template != '') {
						$template = $this->recent_template;
					} else {
						$template = $this->default_recent_template($root);
					}

					$count=0;
					foreach ($list AS $year => $v) {
						foreach ($v AS $month => $v2) {
							foreach ($v2 AS $day => $id) {
								foreach ($id AS $value) {
									if ($value['id'] != $this->article_id) {
										$count++;
										if ($count <= $this->max_recent) {
											$compiled .= str_replace($tags, $value, $template);
										}
									}
								}
							}
						}
					}
				break;
				case 'HIGHLIGHT':
					// Establish template
					if ($this->highlight_template != '') {
						$template = $this->highlight_template;
					} else {
						$template = $this->default_highlight_template($root);
					}
					$count=0;
					foreach ($list AS $year => $v) {
						foreach ($v AS $month => $v2) {
							foreach ($v2 AS $day => $id) {
								foreach ($id AS $value) {
									if($value['highlight'] != '' && $value['id'] != $this->article_id) {
										$count++;
										if ($count <= $this->max_highlight) {
											$compiled .= str_replace($tags, $value, $template);
										}
									}
								}
							}
						}
					}
				break;
			}

			return $compiled;
		}

		/**
		 * Returns default template. Can be replaced with set_template('LIST')
		 * @access private
		 * @return string
		 */
		function default_list_template() {

			$post_html = <<<HTML

<div class="post"%HIGHLIGHT%>
	<div class="post_header">
		<h2 id="%ID%"%HIGHLIGHT%><a href="?issue=%ID%">%TITLE%</a> %NEW%</h2>
		%IMAGE%
		<h3>
			<!--<span class="post_author">%AUTHOR%</span>&nbsp;-->
			<span class="post_date">%SHORTDATE%</span>&nbsp;
			<span class="post_categories">Filed under:&nbsp;%CATEGORIES%</span>&nbsp;
			<span class="post_comments">%COMMENTS%</span>
		</h3>
	</div>
	%EXCERPT% <a class="post_more_link" href="?issue=%ID%">Read more...</a>
</div>

HTML;

			return $post_html;

		}

		/**
		 * Returns default template. Can be replaced with set_template('SINGLE')
		 * @access private
		 * @return string
		 */
		function default_single_template() {

			$post_html = <<<HTML

<div id="article">
	<div class="article_header">
		<h1>%TITLE%</h1>
		<h3>
			<!--<span class="article_author">by %AUTHOR%</span><br />-->
			<span class="article_date">%SHORTDATE%</span>&nbsp;
			<span class="article_categories">Filed under:&nbsp;%CATEGORIES%</span>&nbsp;

		</h3>
	</div>
	%BODY%
</div>

HTML;

			return $post_html;

		}

		/**
		 * Returns default template. Can be replaced with set_template('COMMENTS')
		 * @access private
		 * @return string
		 */
		function default_comment_template($class) {

			$post_html = <<<HTML

<div id="%ID%" class="$class">
	<h3 class="comment_info">at %COMMENTDATE%, %AUTHOR% wrote:</h3>
	%TEXT%
</div>

HTML;

			return $post_html;

		}

		/**
		 * Returns default template. Can be replaced with set_template('RECENT')
		 * @access private
		 * @return string
		 */
		function default_recent_template($root='') {

			$post_html = <<<HTML

<li><a class="post_more_link" href="$root?issue=%ID%">%TITLE%</a></li>

HTML;

			return $post_html;

		}

		/**
		 * Returns default template. Can be replaced with set_template('HIGHLIGHT')
		 * @access private
		 * @return string
		 */
		function default_highlight_template($root='') {

			$post_html = <<<HTML

<li><a class="post_more_link" href="$root?issue=%ID%">%TITLE%</a></li>

HTML;

			return $post_html;

		}

		/**
		 * Returns month name from int provided. If int is not between 1 and 12
		 * January is retuned.
		 * @access private
		 * @param int
		 * @retun string
		 */
		function int_month($int) {
			$months = array( 1	=> 'January',
							 2	=> 'February',
							 3	=> 'March',
							 4	=> 'April',
							 5	=> 'May',
							 6	=> 'June',
							 7	=> 'July',
							 8	=> 'August',
							 9	=> 'September',
							 10 => 'October',
							 11 => 'November',
							 12 => 'December');
			if ($int > 0 && $int <= 12) {
				return $months[$int];
			} else {
				return $months[1];
			}
		}

		/**
		 * Restrict string to a set number of words followed by designated trailer.
		 *
		 * @param string
		 * @param int
		 * @param string
		 * @return string
		 */
		function truncate($string, $length, $trailer = '&#8230;') {

			// Create the array of words
			$words = explode(' ', $string);
			// Count the number of words
			$word_count = count($words);
			// Check that word count is more than length
			if ($word_count > $length) {
				// Slice off excess words
				$words = array_slice($words, 0, $length);
				// Bring it back together and add trailer
				$short_string = implode(' ', $words);
				// Clear any white space
				$short_string = trim($short_string);
				// Check last character for comma
				if (substr($short_string, -1) == ',') {
					$short_string = substr($short_string, 0, -1);
				}
				$short_string = $short_string.$trailer;

				return $short_string;
			} else {
				return $string;
			}
		}
	} // End Class
?>
<?php
    /** Last updated 4/29/06 11:29 PM by Savage Brown */
    /*
       +--------------------------------------------------------------------+
       | Simple Words Item                                                  |
       +--------------------------------------------------------------------+
       | Copyright (c) 2005 Koray Girton, savagebrown.com                   |
       | Web           http://savagebrown.com/                              |
       | License       GNU Lesser General Public License (LGPL)             |
       +--------------------------------------------------------------------+
       | This library is free software; you can redistribute it and/or      |
       | modify it under the terms of the GNU Lesser General Public         |
       | License as published by the Free Software Foundation; either       |
       | version 2.1 of the License, or (at your option) any later version. |
       +--------------------------------------------------------------------+
       | This software is provided by the copyright holders and             |
       | contributors "as is" and any express or implied warranties,        |
       | including, but not limited to, the implied warranties of           |
       | merchantability and fitness for a particular purpose are           |
       | disclaimed. In no event shall the copyright owner or contributors  |
       | be liable for any direct, indirect, incidental, special,           |
       | exemplary, or consequential damages (including, but not limited    |
       | to, procurement of substitute goods or services; loss of use,      |
       | data, or profits; or business interruption) however caused and on  |
       | any theory of liability, whether in contract, strict liability, or |
       | tort (including negligence or otherwise) arising in any way out of |
       | the use of this software, even if advised of the possibility of    |
       | such damage.                                                       |
       +--------------------------------------------------------------------+
    */

	require_once '3rdParty/Textile.php';

    /**
     * Simple Words Item
     *
     * simple_words_item class does some pretty neat things. Explain further here
     *
     * @package      SAVAGE_LIBRARY
     * @author       Koray Girton
     * @version      0.1b
     * @category     Simple System
     * @copyright    Copyright (c) 2005 SavageBrown.com
     * @license      http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
     * @author       Koray Girton/SavageBrown
     * @filesource
     */

    /**
     * Mailing List
     *
     * @link http://powermatter.local/~koray/_lib/Examples/simple_words_item/index.php Example on how to use this class
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     */
    class simple_words_items {
        /**
         * Instance of the database connection class
         * @access private
         * @var object
         */
        var $db;

        var $by_category;
        
        var $filter_data;

        /**
         * Constructor Method
         *
         * This is the constructor method for this class
         *
         * @param object Instance of the database connection class
         * @access private
         * @return void
         *
         */
        function simple_words_items ($db, $by_category = true, $textile = false, $filter_table = null) {
            $this->db = $db;
            $this->by_category = $by_category;
            $this->apply_textile = $textile;
        }

        /**
         * Database table name must be provided. The corresponding categories 
         * table must be "tablename_categories". "_categories" will be added.
         * This is a very simple and specific class. If the need for more 
         * functionality arises build a separate class.
         * @access private
         * @param string
         * @param string
         * @return mixed
         */
        function get_list($table, $template, $filter_data=null) {
        
            $tags = $this->get_tags();
            
            $list ='';
            if ($items = $this->build_list($table, $filter_data)) {

                if ($this->by_category) {
                    foreach ($items AS $k => $v) {
                        $list .= '<h2>'.$k.'</h2>';
                        //asort($v);
                        foreach ($v AS $id => $value) {
                            $list .= str_replace($tags, $value, $template);
                        }
                    }
                } else {
                    //asort($items);
                    foreach ($items AS $id => $value) {
                        $list .= str_replace($tags, $value, $template);
                    }
                }
                return $list;
            } else {
                return false;
            }
        }

		// TODO: Add session caching to speed things up
        function build_list($table, $filter_data=null) {
                
            if ($this->by_category) {
            
                if (is_array($filter_data)) {
                    $filter_from = ', '.$filter_data['filter_table'].' f';
                    $filter_where = 'AND f.'.$filter_data['filter_id_label'].' = '.$filter_data['filter']." AND r.id = f.".$filter_data['main_id_label'];
                } else {
                    $filter_from = '';
                    $filter_where = '';
                }
                
                $sql = "SELECT r.id AS id,
                               r.title AS title,
                               r.link AS link,
                               r.description AS description,
                               r.info1 AS info1,
                               r.info2 AS info2,
                               r.info3 AS info3,
                               c.name AS category
                        FROM   ".$table." r, ".$this->strip_plural($table)."_categories c ".$filter_from."
                        WHERE  r.category_id = c.id ".$filter_where."
                        ORDER BY c.rank, r.title";
            } else {
            
                if (is_array($filter_data)) {
                    $filter_from = ', '.$filter_data['filter_table'].' f';
                    $filter_where = 'WHERE f.'.$filter_data['filter_id_label'].' = '.$filter_data['filter']." AND r.id = f.".$filter_data['main_id_label'];
                } else {
                    $filter_from = '';
                    $filter_where = '';
                }

                $sql = "SELECT r.id AS id,
                               r.title AS title,
                               r.link AS link,
                               r.description AS description,
                               r.info1 AS info1,
                               r.info2 AS info2,
                               r.info3 AS info3
                        FROM   ".$table." r ".$filter_from." ".$filter_where."
                        ORDER BY r.rank";
            }
                        
            $q = $this->db->query($sql);
            if ($this->db->iserror($q)) {
                die($q->getMessage().'<hr /><pre>'.$q->userinfo.'</pre>');
            }
            if ($q->numrows() > 0) {
            
                if ($this->apply_textile) {
                    // Instantiate Textile
                    $textile = new Textile();
                }

                while ($r = $q->fetchrow(DB_FETCHMODE_ASSOC)) {

                    if ($this->by_category) {

                        $items[$r['category']][$r['id']] = array(
                                             $r['id'],
                                             $r['title'],
                                             ($this->apply_textile)?$textile->textileThis($r['description']):$r['description'],
                                             $r['link'],
                                             $r['info1'],
                                             $r['info2'],
                                             $r['info3']);
                    } else {

                        $items[$r['id']] = array($r['id'],
                                             $r['title'],
                                             ($this->apply_textile)?$textile->textileThis($r['description']):$r['description'],
                                             $r['link'],
                                             $r['info1'],
                                             $r['info2'],
                                             $r['info3']);
                    }
                }
                
                return $items;
            } else {
                return false;
            }
        }
        
        function get_tags() {
            return array('%ID%','%TITLE%','%DESCRIPTION%','%LINK%',
                          '%INFO1%','%INFO2%','%INFO3%');
        }
        
        function strip_plural($string) {
            if (substr($string, -1) == s) {
                $len = strlen($string)-1;
                return substr($string, 0, $len);
            } else {
                return $string;
            }
        }

    }
?>
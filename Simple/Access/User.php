<?php
    /**
     * @package SBLIB
     * @version $Id: User.php,v 1.5 2003/11/13 05:37:28 kevin Exp $
     */
    /**
     * User Class
     *
     * Used to store information about users, such as permissions based on the 
     * session variable "login" (set in constants as S_LOGIN)
     *
     * @access public
     * @package SPLIB
     */
    class simple_access_user {
        /**
         * Database connection
         * @access private
         * @var  object
         */
        var $db;
        /**
         * Session Manager
         * @access private
         * @var  object
         */
        var $session;
        /**
         * The id which identifies this user
         * @access private
         * @var int
         */
        var $userId;
        /**
         * The access level which user belongs to
         * @access private
         * @var int
         */
        var $access_level;
        /**
         * The users email
         * @access private
         * @var string
         */
        var $email;
        /**
         * First Name
         * @access private
         * @var string
         */
        var $firstName;
        /**
         * Last Name
         * @access private
         * @var string
         */
        var $lastName;
        /**
         * Full Name
         * @access private
         * @var string
         */
        var $fullName;

        /**
         * Permissions
         * @access private
         * @var array
         */
        var $permissions;
    
        /**
         * User constructor
         * @param object instance of database connection
         * @param object instance of sessions manager
         * @access public
         */
        function simple_access_user (&$db, $session) {
            $this->db =& $db;
            $this->session =& $session;
            $this->set_variables();
        }
    
        /**
         * Determines the user's id from the login session variable
         * @return void
         * @access private
         */
        function set_variables() {

            $sql="SELECT
                      ".USER_COL_ID.",
                      ".USER_COL_ACCESSLEVEL.",
                      ".USER_COL_EMAIL.", 
                      ".USER_COL_FIRST.",
                      ".USER_COL_LAST."
                  FROM
                      ".USER_TABLE."
                  WHERE
                      ".USER_COL_LOGIN."='".$this->session->get(S_LOGIN)."'";
            $q=$this->db->query($sql);
            if (DB::iserror($q)) {
                die($q->getMessage().'<hr /><pre>'.$q->userinfo.'</pre>');
            }
            $r=$q->fetchrow(DB_FETCHMODE_ASSOC);
            $this->userId       = $r[USER_COL_ID];
            $this->access_level = $r[USER_COL_ACCESSLEVEL];
            $this->email        = $r[USER_COL_EMAIL];
            $this->firstName    = $r[USER_COL_FIRST];
            $this->lastName     = $r[USER_COL_LAST];
			$this->fullName     = $r[USER_COL_FIRST].' '.$r[USER_COL_LAST];
        }
    
        /**
         * Returns the users id
         * @return int
         * @access public
         */
        function get_id() {
            return $this->userId;
        }
        /**
         * Returns the access level
         * @return int
         * @access public
         */
        function get_access_level() {
            return $this->access_level;
        }
        /**
         * Returns the users email
         * @return int
         * @access public
         */
        function get_email() {
            return $this->email;
        }
    
        /**
         * Returns the users first name
         * @return string
         * @access public
         */
        function get_fname() {
            return $this->firstName;
        }
    
        /**
         * Returns the users last name
         * @return string
         * @access public
         */
        function get_lname() {
            return $this->lastName;
        }

        /**
         * Returns the users last name
         * @return string
         * @access public
         */
        function get_fullname() {
            return $this->fullName;
        }
    
        /**
         * Checks to see if the user has the named access level
         * @param string requested access level
         * @return boolean TRUE if user has the access level
         * @access public
         */
        function check_permission($access_level) {
            
			if ($this->access_level == $access_level) {
				return true;
			} else {
				return false;
			}
            
        }
    }
?>
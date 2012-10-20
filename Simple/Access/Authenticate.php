<?php

	//NICK: Moved to access.php directory
	//include_once 'constants.default.php';

	/**
	 * @package SBLIB
	 * @version v 1.6 2004/09/16 22:13:17 koray
	 */
	/**
	 * Authentication Class<br />
	 * Automatically authenticates users on construction<br />
	 * <b>Note:</b> Requires that the Session/Session class be available
	 * @access public
	 * @package SBLIB
	 */
	class simple_access_authenticate {
		/**
		 * Instance of database connection class
		 * @access private
		 * @var object
		 */
		var $db;
		/**
		 * Instance of Session class
		 * @access private
		 * @var Session
		 */
		var $session;
		/**
		 * Url to re-direct to in not authenticated
		 * @access private
		 * @var string
		 */
		var $redirect;
		/**
		 * String to use when making hash of username and password
		 * @access private
		 * @var string
		 */
		var $hashKey;
		/**
		 * Path to logfile. If set login will be logged
		 *
		 */
		var $logfile;
		/**
		 * Are passwords being encrypted
		 * @access private
		 * @var boolean
		 */
		var $md5;
		/**
		 * Auth constructor
		 * Checks for valid user automatically
		 * @param object database connection
		 * @param string URL to redirect to on failed login
		 * @param string key to use when making hash of username and password
		 * @param boolean if passwords are md5 encrypted in database (optional)
		 * @access public
		 */
		function simple_access_authenticate ( & $db, $redirect, $hashKey, $md5 = true, $logfile = null ) {
			$this->db=& $db;
			$this->redirect=$redirect;
			$this->hashKey=$hashKey;
			$this->md5=$md5;
			$this->logfile = $logfile;
			$this->session = & new simple_session_wrapper();
			$this->login();
		}
		/**
		 * Checks username and password against database
		 * @return void
		 * @access private
		 */
		function login() {
			// See if we have values already stored in the session
			if ( $this->session->get('login_hash') ) {
				$this->confirmAuth();
				return;
			}

			// If this is a fresh login, check $_POST variables
			if ( !isset($_POST[POST_LOGIN]) ||
					!isset($_POST[POST_PASSW]) ) {
				$this->redirect();
			}

			if ( $this->md5 )
				$password=md5($_POST[POST_PASSW]);
			else
				$password=$_POST[POST_PASSW];

			// Escape the variables for the query
			$login = trim(mysql_real_escape_string($_POST[POST_LOGIN]));
			$password = trim(mysql_real_escape_string($password));

			// Query to count number of users with this combination
			$sql="SELECT *
					FROM ".USER_TABLE."
					WHERE ".USER_COL_LOGIN."='".$login."'
					AND ".USER_COL_PASSW."='".$password."'";

			$q=$this->db->query($sql);
			if (DB::iserror($q)) {
				die($q->getMessage().'<hr /><pre>'.$q->userinfo.'</pre>');
			}

			// If there isn't exactly one entry, redirect
			if ( $q->numrows() != 1 ) {
				$this->redirect();
			// Else is a valid user; set the session variables
			} else {
				// Retreive user info
				$user_info = $q->fetchrow(DB_FETCHMODE_ASSOC);
				$this->storeAuth($user_info);
			}
		}
		/**
		 * Sets the session variables after a successful login
		 * @return void
		 * @access protected
		 */
		function storeAuth($user_info) {

			// Set login session variable
			$this->session->set(S_LOGIN,  $user_info[USER_COL_LOGIN]);
			// Set password session variable
			$this->session->set(S_PASSW,  $user_info[USER_COL_PASSW]);
			// Set user id session variable
			$this->session->set(S_USERID, $user_info[USER_COL_ID]);
			// Set login time session variable
			$this->session->set(S_LOGIN_TIME, date('g:ia'));
			// Set full name session variable
			$this->session->set(S_FULL_NAME, $user_info[USER_COL_FIRST].' '.$user_info[USER_COL_LAST]);

			// Create a session variable to use to confirm sessions
			$hashKey = md5($this->hashKey.$user_info[USER_COL_LOGIN].$user_info[USER_COL_PASSW]);
			$this->session->set('login_hash',$hashKey);

			// Log login if set
			if ($this->logfile != '') {
				$this->logger = new LogManager();
				$this->logger->adminLogger($this->logfile, $this->session->get(S_FULL_NAME), 'Logged in');
			}
		}
		/**
		 * Confirms that an existing login is still valid
		 * @return void
		 * @access private
		 */
		function confirmAuth() {
			$login	  = $this->session->get(S_LOGIN);
			$password = $this->session->get(S_PASSW);
			$hashKey  = $this->session->get('login_hash');
			if ( md5($this->hashKey.$login.$password) != $hashKey ) {
				$this->logout(true);
			}
		}
		/**
		 * Logs the user out
		 * @param boolean Parameter to pass on to Auth::redirect() (optional)
		 * @return void
		 * @access public
		 */
		function logout($from=false) {
			// Log action login if set
			if ($this->logfile != '') {
				$this->logger = new LogManager();
				$this->logger->adminLogger($this->logfile, $this->session->get(S_FULL_NAME), 'Logged out');
			}

			$this->session->del(S_LOGIN);
			$this->session->del(S_PASSW);
			$this->session->del(S_USERID);
			$this->session->del(S_LOGIN_TIME);
			$this->session->del('login_hash');
			$this->session->destroy();
			$this->redirect($from);
		}
		/**
		 * Redirects browser and terminates script execution
		 * @param boolean adverstise URL where this user came from (optional)
		 * @return void
		 * @access private
		 */
		function redirect($from=true) {
			if ( $from ) {
				header ( 'Location: '.$this->redirect.'?from='.
					$_SERVER['REQUEST_URI'] );
			} else {
				header ( 'Location: '.$this->redirect );
			}
			exit();
		}
	}
?>
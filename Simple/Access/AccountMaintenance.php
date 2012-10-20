<?php

	/**
	 * @package SBCMS (SavageBrown Content Management System)
	 * @version v 1.6 2004/09/16 22:13:17 koray
	 */
	/**
	 * AccountMaintenance Class<br />
	 * Provides functionality for users to manage their own accounts
	 * @access public
	 * @package		 SBCMS (SavageBrown Content Management System)
	 */
	class AccountMaintenance {
		/**
		 * Database connection
		 * @access private
		 * @var object
		 */
		var $db;

		/**
		 * A list of words to use in generating passwords
		 * @access private
		 * @var array
		 */
		var $words;

		/**
		 * AccountMaintenance constructor
		 * @param object instance of database connection
		 * @access public
		 */
		function AccountMaintenance (&$db) {
			$this->db=& $db;
		}

		/**
		 * Given an email address, returns the user details
		 * that account. Useful is password is not encrypted
		 * @param string email address
		 * @return array user details
		 * @access public
		 */
		function fetchLogin($email) {
			$email=mysql_escape_string($email);
			$sql="SELECT
					  ".USER_TABLE_LOGIN.", ".USER_TABLE_PASSW.",
					  ".USER_TABLE_FIRST.", ".USER_TABLE_LAST."
				  FROM
					  ".USER_TABLE."
				  WHERE
					  ".USER_TABLE_EMAIL."='".$email."'";
			$result=$this->db->query($sql);
			if ( $result->size() == 1 )
				return $result->fetch();
			else
				return false;
		}

		/**
		 * Given a username / email combination, resets the password
		 * for that user and returns the new password.
		 * @param string login name
		 * @param string email address
		 * @return array of user details or FALSE if failed
		 * @access public
		 */
		function resetPassword($login,$email) {
			$login=mysql_escape_string($login);
			$email=mysql_escape_string($email);
			$sql="SELECT ".USER_TABLE_ID.",
					  ".USER_TABLE_LOGIN.", ".USER_TABLE_PASSW.",
					  ".USER_TABLE_FIRST.", ".USER_TABLE_LAST."
				  FROM
					  ".USER_TABLE."
				  WHERE
					  ".USER_TABLE_LOGIN."='".$login."'
				  AND
					  ".USER_TABLE_EMAIL."='".$email."'";
			$result=$this->db->query($sql);
			if ( $result->size() == 1 ) {
				$row=$result->fetch();
				if ( $password = $this->generatePassword() ) {
					$sql="UPDATE
							  ".USER_TABLE."
						  SET
							  ".USER_TABLE_PASSW."='".md5($password)."'
						  WHERE
							  ".USER_TABLE_ID."='".$row[USER_TABLE_ID]."'";
					$result=$this->dbConn->fetch($sql);
					if (!$result->isError()) {
						$row[USER_TABLE_PASSW]=$password;
						return $row;
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		 * Add a list of words to generate passwords with
		 * @param array
		 * @return void
		 * @access public
		 */
		function addWords ($words) {
			$this->words = $words;
		}

		/**
		 * Generates a random but memorable password
		 * @return string the password
		 * @access private
		 */
		function generatePassword () {
			srand((double)microtime() * 1000000);
			$seperators = range(0,9);
			$seperators[] = '_';
			$count = count($this->words);
			if ( $count == 0 )
				return false;
			$password = array();
			for ( $i=0; $i<4; $i++ ) {
				if ( $i % 2 == 0 ) {
					shuffle ($this->words);
					$password[$i] = trim($this->words[0]);
				} else {
					shuffle ( $seperators );
					$password[$i] = $seperators[0];
				}
			}
			shuffle($password);
			return implode ('', $password);
		}

		/**
		 * Changes a password both in the database and in the current session
		 * variable. Assumes the new password has been validated correctly elsewhere.
		 * @param string old password
		 * @param string new password
		 * @return boolean TRUE on success
		 * @access public
		 */
		function changePassword(& $auth, $oldPassword, $newPassword) {
			$oldPassword = safe_escape($oldPassword);
			$newPassword = safe_escape($newPassword);
			// If md5 is used
			if ( $auth->md5 ) {
				// old pass
				$op = md5($oldPassword);
				// new pass
				$np = md5($newPassword);
			} else {
				// old pass
				$op = $oldPassword;
				// new pass
				$np = $newPassword;
			}

			// Check that the login and old password match
			$sql_o = "SELECT
						 *
					  FROM
						 ".USER_TABLE."
					  WHERE
						 ".USER_COL_LOGIN."='".$auth->session->get(S_LOGIN)."'
					  AND
						 ".USER_COL_PASSW."='".$op."'";
			$q_o = $this->db->query($sql_o);
			if (DB::isError($q_o)) { die('the old password does not match'); }
			if ( $q_o->numrows() != 1 ) {
				return false;
			}

			// Update the password
			$sql_u = "UPDATE
						".USER_TABLE."
					  SET
						".USER_COL_PASSW."='".$np."'
					  WHERE
						".USER_COL_LOGIN."='".$auth->session->get(S_LOGIN)."'";
			$q_u = $this->db->query($sql_u);
			if (!DB::isError($q_u)) {
				// Set the session variable for the password
				$auth->storeAuth($auth->session->get(S_LOGIN),$newPassword);
				return true;
			} else {
				return false;
			}
		}
	}
?>
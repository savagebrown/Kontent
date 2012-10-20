<?php

	/**
	 * Strip Magic Quotes
	 *
	 * Checks if magic_quotes_gpc = On and strips them of incoming
	 * requests if necessary.
	 *
	 * @return void
	 */
	function strip_quotes() {
		if (get_magic_quotes_gpc()) {

			$_GET	 = array_map('stripslashes', $_GET);
			$_POST	 = array_map('stripslashes', $_POST);
			$_COOKIE = array_map('stripslashes', $_COOKIE);

		} else {
			return false;
		}
	}

	// Run Strip Magic Quotes -------------------------------------------------
	// strip_quotes();

	/**
	 * Safe Escape
	 *
	 * This function allows mysql provided API to escape strings.
	 * It is not consistent with portability strategy but can be
	 * nulled if different db is used
	 *
	 * @param mixed
	 * @return mixed
	 */
	function safe_escape($string, $type='str', $quote=false) {
		
		switch ( $type ) {
			// String
			case 'str':
				$string = mysql_real_escape_string($string);
				$string = trim($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
			break;
			case 'text':
				$string = mysql_real_escape_string($string);
				$string = trim($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
			break;
			case 'textarea':
				$string = trim($string);

				$string = mysql_real_escape_string($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
			break;
			case 'wysiwyg':
				$string = trim($string);

				for ($i=0; $i < 3; $i++) { 
					$string = wysiwygFilter($string);
				}

				$string = mysql_real_escape_string($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
			break;
			// Intiger
			case 'int':
				$string = ($string)?$string:0;
				return $string;
			break;
			// Checkbox
			case 'chk':
			case 'checkbox':
				$string = ($string)?1:0;
				return $string;
			break;
			// Datetime (yyyy-mm-dd hh:mm:ss)
			case 'datetime':
			case 'dtetme':
				$string = format_date($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
				break;
			// Date (Ymd)
			case 'dte':
			case 'date':
				$string = format_date($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
			break;
			// Date (Y)
			case 'dty':
				if (is_array($string)) {
					// Year
					$string = $string['Y'];
				}
				return ($string)?$string:"''";
			break;
			// Time (00:00:00)
			case 'tme':
			case 'time':
				$string = format_time($string);
				if ($quote) {
					return "'".$string."'";
				} else {
					return $string;
				}
			break;
			// Decimal
			case 'dec':
				$string = str_replace(',','',$string);
				if (!$string) { $string=0; }
				return $string;
			break;
			// Heirselect first value
			case 'hs1':
				$string = $string[0];
				return $string;
			break;
			// Heirselect
			case 'hs2':
				$string = $string[1];
				return $string;
			break;

		}

	}

	/**
	 * Safe Escape
	 *
	 * This function allows mysql provided API to escape strings.
	 * It is not consistent with portability strategy but can be
	 * nulled if different db is used
	 *
	 * @param string
	 * @return string
	 */
	function safe_escape_string($string) {
		if($string != '') {
			if (!is_int($string)) {
				$string = mysql_real_escape_string($string);
				$string = trim($string);
				return "'".$string."'";
			} else {
				return $string;
			}
		} else {
			return "'".$string."'";
		}
	}

	function get_update_sql($vars, $table) {

		if (is_array($vars)) {
			$fields='';
			foreach ($vars as $k => $v) {
				if ($k!='id') {
					$fields .= $k.' = '.$v.','."\n";
				} else {
					$id = $v;
				}
			}
			$fields = substr(trim($fields), 0, -1);
			$sql = <<<SQL

UPDATE
	$table
SET

$fields

WHERE
	id = $id

SQL;
			return $sql;
		} else {
			return false;
		}

	}

	function get_insert_sql($vars, $table) {

		if (is_array($vars)) {
			$insert='';
			foreach ($vars as $k => $v) {
				if ($k!='id') {
					$insert .= $k.','."\n";
					$values .= $v.','."\n"; 
				}
			}
			$insert = substr(trim($insert), 0, -1);
			$values = substr(trim($values), 0, -1);
			$sql = <<<SQL

INSERT INTO $table
(
$insert
)
VALUES
(
$values
)

SQL;
			return $sql;
		} else {
			return false;
		}

	}

?>
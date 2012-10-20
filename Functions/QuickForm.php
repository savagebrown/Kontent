<?php

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Quickform Process Functions #
	/////////////////////////////////////////////////////////////////////////

	/**
	 * Specific to the (M d, Y) format. Used to compile the date given
	 * back by quick_form's date form item.
	 *
	 * @param array
	 * @return date
	 */
	function format_date($array) {

		if (is_array($array)) {
			// Month
			if ($array[M] <= 9) {
				$month = '0'.$array[M];
			} else {
				$month = $array[M];
			}
			// Day
			if ($array[d] <= 9) {
				$day = '0'.$array[d];
			} else {
				$day = $array[d];
			}
			// Year
			$year = $array[Y];
			// Compile
			$date = $year.$month.$day;

			return $date;
		}
	}

	/**
	 * Specific to the (M d, Y) format. Used to compile the date given
	 * back by quick_form's date form item.
	 *
	 * @param array
	 * @return date
	 */
	function qf_format_year($array) {

		if (is_array($array)) {
			// Year
			$date = $array[Y];
			return $date;
		}
	}

	/**
	 * Specific to the (h:i:a) format. Used to compile the date given
	 * back by quick_form's date form item.
	 *
	 * @param array
	 * @return date
	 */
	function format_time($array) {

		if (is_array($array)) {
			// Hour
			if ($array[a] == 'am') {
				if($array[h] == 12) {
					$hh = '00';
				} else {
					$hh = $array[h];
				}
			} else {
				if($array[h] != 12) {
					$hh = $array[h]+12;
				} else {
					$hh = $array[h];
				}
			}
			// Make Doubledigit
			if ($hh <= 9) {
				$hh = '0'.$hh;
			}
			// Minute
			$mm = $array[i];
			// Make Doubledigit
			if ($mm <= 9) {
				$mm = '0'.$mm;
			}
			// Second
			$ss = '00';
			// Compile
			$time = $hh.$mm.$ss;

			return $time;
		}
	}

	/**
	 * Build repeat array for insert. Administrator has option to repeat
	 * events. This function will insert all dates into an array which
	 * will be used to loop and insert events into database.
	 *
	 * Function does not include given date to the array.
	 *
	 * @param array QuickForm date array
	 * @param int Repeat option
	 * @return array
	 */
	function repeat_date($start_date, $repeat_option) {

		// Start Month
		$m = $start_date[M];
		// Start Day
		$d = $start_date[d];
		// Start Year
		$y = $start_date[Y];

		// Walk through repeat options
		switch ($repeat_option) {
		// No repeat
		case 0:
			return false;
			break;
		// Every week for a month
		case 1:
			for ($i = 1; $i <= 3; $i++) {
				$r = $i*7;
				$all_dates[] = date('Ymd', mktime(0, 0, 0, $m, $d+$r, $y));
			}
			break;
		// Every week for a year
		case 2:
			for ($i = 1; $i <= 51; $i++) {
				$r = $i*7;
				$all_dates[] = date('Ymd', mktime(0, 0, 0, $m, $d+$r, $y));
			 }
			break;
		// Every week for six weeks
		case 3:
			for ($i = 1; $i <= 5; $i++) {
				$r = $i*7;
				$all_dates[] = date('Ymd', mktime(0, 0, 0, $m, $d+$r, $y));
			 }
			break;

		// Every month for a year
		case 4:
			for ($i = 1; $i <= 11; $i++) {
				$r = $i;
				$all_dates[] = date('Ymd', mktime(0, 0, 0, $m+$r, $d, $y));
			 }
			break;
		// Every year for 5 years
		case 5:
			for ($i = 1; $i <= 4; $i++) {
				$r = $i;
				$all_dates[] = date('Ymd', mktime(0, 0, 0, $m, $d, $y+$r));
			 }
			 break;
		// No repeat default
		default:
			return false;
		}

		return $all_dates;
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Quickform Register-able Functions #
	/////////////////////////////////////////////////////////////////////////

	/**
	 * Validate date for QuickForm date field. QuickForm returns an array
	 * $date[M], $date[d], $date[Y]
	 *
	 * Can register function through quickform:
	 * $form->registerRule('val_date','function','validate_date');
	 *
	 * @param string Quickform Element Name
	 * @param array Quickform Element Value
	 * @return bool
	 */
	function validate_date($element_name, $element_value) {
		// Create individual variables from array
		$m = ceil($element_value[M]);
		$d = ceil($element_value[d]);
		$y = ceil($element_value[Y]);
		// Check if combination exists
		if (checkdate($m, $d, $y)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validates that there are no spaces in value.
	 * Useful for filenames, usernames, etc.
	 *
	 * Can register function through quickform:
	 * $form->registerRule('val_spaces','function','check_spaces');
	 *
	 * @param string  Quickform Element Name
	 * @param string  Quickform Element Value
	 * @return bool
	 */
	function check_spaces($element_name, $element_value) {
		$element_value = trim($element_value);

		$space_count = @substr_count($element_value);
		if ($space_count > 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Check username against database
	 * These two gobals must be defined:
	 * $user_table, $user_field
	 *
	 * This global can be set to exclude current user
	 * (useful for updating existing user):
	 * $current_user_id
	 *
	 * Can register function through quickform:
	 * $form->registerRule('val_username','function','check_username');
	 *
	 * @param string  Quickform Element Name
	 * @param string  Quickform Element Value
	 * @return bool
	 */
	function check_username($element_name, $element_value) {

		global $db, $user_table, $user_field, $current_user_id;

		$sql = <<<SQL

			SELECT $user_field
			FROM $user_table
			WHERE $user_field = '$element_value'

SQL;
		// If current user id is set exclude that record from the check
		if (isset($current_user_id) && $current_user_id != '') {
			$sql .= " AND ".$user_field." <> ".$current_user_id;
		}

		$q = $db->query($sql);
		if (DB::isError($q)) {
			die($q->getMessage().'<hr /><pre>'.$q->userinfo.'</pre>');
		}
		if ($q->numrows() > 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Return false if select option value = 0
	 *
	 * Can register function through quickform:
	 * $form->registerRule('val_notFirst','function','not_first');
	 *
	 * @param string Quickform Element Name
	 * @param string Quickform Element Value
	 * @return void
	 * @author Savage Brown
	 **/
	function not_first($element_name, $element_value) {
		if (!$element_value == 0) {
			return true;
		} else {
			return false;
		}
	}

	function validate_all_day($element_name, $element_value) {

		global $form;

		if ($form->getElementValue('all_day'))
			return true;
		else {
			if (!empty($element_value))
				return true;
			else
				return false;
		}
	}

	/**
	 * Checks that confirm password matches password field
	 * Assumes Quickform Object is named $form & that password field
	 * Element Name = password
	 *
	 * Can register function through quickform:
	 * $form->registerRule('val_confirmPassword','function','compare_password');
	 *
	 * @param string Quickform Element Name
	 * @param string Quickform Element Value
	 * @return boolean
	 * @author Savage Brown
	 **/

	function compare_password($element, $pass2val) {
		global $form;
		$pass1val = $form->getElementValue('password');
		return ($pass1val == $pass2val);
	}
	
	function filter_comma ($str) {
		return str_replace(',','',$str);
	}

	// WYSIWYG ----------------------------------------------------------------

	// Requires JQuery and JHtmlArea Javascript Libraries
	// http://jhtmlarea.codeplex.com/
	function textarea_wysiwyg($textarea_id) {

		global $g;
		$wysiwyg_js = <<<HTML

		<script type="text/javascript">
			$(function() {
				$("#{$textarea_id}").htmlarea({
					css: "{$g['page']['js']}/jHtmlArea/css/jHtmlArea.Editor.css",
					toolbar: [// Overrides/Specifies the Toolbar buttons to show
						["bold", "italic", "|"],
						["p", "h1", "h2", "h3", "|"], 
						["orderedList", "unorderedList", "|"], 
						["blockquote", "|"],
						["link", "unlink", "|"], 
						["html","|"],
						[{
							css: "videoButton",
							text: "Embed a video",
							action: function(btn) {
								embedURL = prompt("Video URL:", "");
								if (embedURL)
									$("#{$textarea_id}").htmlarea('pasteHTML', "<p>[VIDEO:"+embedURL+"]</p>");
							}
						}]
					]
				});
			});
		</script>

HTML;
		return $wysiwyg_js;
	}

?>

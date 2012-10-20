<?php

	/**
	 * Javascript function ocw() needs to be accessable.<br />
	 * Will open a centered popup window
	 */
	 function ocw($link, $text, $name='popup', $width=500, $height=500) {
		return '<a href="javascript:;" onClick="ocw(\''.$link.'\',\''.$name.'\',\''.$width.'\',\''.$height.'\',\'yes\');return false;">'.$text.'</a>';
	}

	/**
	 * Will open a javascript confirm box
	 */
	function js_confirm($link, $text, $message, $a_title='', $a_class='') {
		$a_class = ($a_class)?' class="'.$a_class.'"':'';
		$a_title = ($a_title)?' title="'.$a_title.'"':'';
		$message = htmlentities(strip_tags(str_replace("'", '',$message)));
		$confirm_link  = "<a".$a_class.$a_title." href=\"javascript:;\" onclick=\"cf=confirm('".$message."');if (cf)window.location='".$link."'; return false;\">";
		$confirm_link .= htmlentities(strip_tags($text));
		$confirm_link .= '</a>';
		return $confirm_link;
	}

	/**
	 * Creates JumpList select field
	 * @param array List of value and lables to populate select form
	 *				$list = array($value1 => $label1,
	 *							  $value2 => $label2);
	 * @param string Value to match SELECTED option
	 * @param bool Determines if add link is active
	 * @return string Select field with options
	 */
	function create_jumplist($list, $current_selection=null, $target=null, $add='ADD', $update='SELECT ITEM TO UPDATE', $urlparam='i') {

		$target = ($target) ? $target:$_SERVER['PHP_SELF'];

		// If list is empty disable selection field and change text
		if (!empty($list)) {
			if ($add) {
				if ($current_selection) {
					$options .= "\n\t".'<option value="'.$target.'">'.$add.'</option>';
				} else {
					$options .= "\n\t".'<option value="">'.$update.'</option>';
				}
			}
			// Compile list into options
			foreach ($list AS $k => $v) {
				$selected = ($k == $current_selection) ? ' selected="selected"' : '';
				$url = $target.'?'.$urlparam.'='.$k;
				$v = truncate_this(strip_tags($v), 40);
				$options .= "\n\t".'<option value="'.$url.'"'.$selected.'>'.$v.'</option>';
			}
		} else {
			$disabled = ' disabled="true"';
			$options = "\n\t".'<options value="">-- NO ITEMS YET --</options>';
		}

		$jumplist  = "\n";
		$jumplist .='<select'.$disabled.' name="jumpclient" id="jumpclient" onChange="sb_jumpMenu(\'parent\',this,0)">';
		$jumplist .= $options;
		$jumplist .= "\n".'</select>';

		return $jumplist;

	}

?>
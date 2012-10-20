<?php

	/**
	 * Creates a javascript enabled jumplist that preserves querystring with
	 * option to unset one
	 *
	 * <script language="javascript">
	 * function sb_jumpMenu(targ,selObj,restore) {
	 *		eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
	 *		if (restore) selObj.selectedIndex=0;
	 * }
	 * </script>
	 *
	 * @param array List of value and labels to populate select form
	 *				$list = array($value1 => $label1,
	 *							  $value2 => $label2);
	 * @param string Value to match SELECTED option
	 * @param bool Determines if add link is active
	 * @return string Select field with options
	 */
	function js_jumplist($list, $urlvar=null, $current_selection=null, $target=null) {

		// If list is empty disable selection field and change text
		if (!empty($list)) {

			if (!$target) {
				// Preserve Querystring
				if ((isset($_SERVER['QUERY_STRING'])) && ($_SERVER['QUERY_STRING'] != "")) {
					$target = $_SERVER['PHP_SELF'];
					$existing_qs = split_query($_SERVER['QUERY_STRING']);
					unset($existing_qs[$urlvar]);
					$qs = "?";
					if(!empty($existing_qs)) {
						$qs .= stitch_query($existing_qs)."&";
					}
					$target = $target.$qs;
				} else {
					$target = $target."?";
				}
			} else {
				$target .= (strpos($target, "?")) ? '&':'?';
			}

			// Compile list into options
			foreach ($list AS $k => $v) {
				$selected = ($k == $current_selection) ? ' SELECTED' : '';
				$url = $target.$urlvar.'='.$k;
				$options .= "\n\t".'<option value="'.$url.'"'.$selected.'>'.$v.'</option>';
			}
		} else {

			$disabled = ' disabled="true"';
			$options = "\n\t".'<options value="">-- NO ITEMS YET --</options>';
		}

		$jumplist  = "\n\n";
		$jumplist .= '<select'.$disabled.' name="jumpclient" id="jumpclient" onChange="sb_jumpMenu(\'parent\',this,0)">';
		$jumplist .= $options;
		$jumplist .= "\n".'</select>';

		return $jumplist;
	}

	function valid_email($email) {
		if (eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}", $email)){
			return TRUE;
		} else {
			return FALSE;
		}
	}
?>
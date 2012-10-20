<?php

	/**
	 * Returns a table with specified columns populates cells with array items
	 * @uses is_divisable() Functions/Integers.php
	 * @param integer
	 * @param array
	 * @return string
	 */
	function table_array($columns, $array, $just_rows = FALSE) {

		$rowcount = 0;

		$array_keys = array_keys($array);
		$end = end($array_keys);

		$table = '';
		foreach ($array AS $k => $v) {
			$rowcount++;
			$colcount++;
			if (is_divisable($rowcount, $columns)) {

				$table .= '<td>'.$v.'</td>';
				$table .= '</tr><tr>';
				$colcount=0;
			} else {

				if ($k == $end) {
					$table .= '<td>'.$v.'</td>';
					for($i = 1; $i <=($columns-$colcount); $i++) {
						$table .= '<td class="empty_cell"></td>';
					}
					break;
				} else {
					$table .= '<td>'.$v.'</td>';

				}
			}
		}
		if ($just_rows) {
			$table = '<tr>'.$table.'</tr>';
		} else {
			$table = '<table><tr>'.$table.'</tr></table>';
		}
		return $table;

	}

	/**
	 * Unordered List
	 *
	 * Generates an HTML unordered list from an single or multi-dimensional array.
	 *
	 * @access	public
	 * @param	array
	 * @param	mixed
	 * @return	string
	 */ 
	function ul($list, $attributes = '')
	{
		return _list('ul', $list, $attributes);
	}

	// ------------------------------------------------------------------------

	/**
	 * Ordered List
	 *
	 * Generates an HTML ordered list from an single or multi-dimensional array.
	 *
	 * @access	public
	 * @param	array
	 * @param	mixed
	 * @return	string
	 */ 
	function ol($list, $attributes = '')
	{
		return _list('ol', $list, $attributes);
	}

	// ------------------------------------------------------------------------

	/**
	 * Generates the list
	 *
	 * Generates an HTML ordered list from an single or multi-dimensional array.
	 *
	 * @access	private
	 * @param	string
	 * @param	mixed		
	 * @param	mixed		
	 * @param	integer		
	 * @return	string
	 */ 
	function _list($type = 'ul', $list, $attributes = '', $depth = 0)
	{
		// If an array wasn't submitted there's nothing to do...
		if ( ! is_array($list))
		{
			return $list;
		}

		// Set the indentation based on the depth
		$out = str_repeat(" ", $depth);

		// Were any attributes submitted?  If so generate a string
		if (is_array($attributes))
		{
			$atts = '';
			foreach ($attributes as $key => $val)
			{
				$atts .= ' ' . $key . '="' . $val . '"';
			}
			$attributes = $atts;
		}

		// Write the opening list tag
		$out .= "<".$type.$attributes.">\n";

		// Cycle through the list elements.	 If an array is 
		// encountered we will recursively call _list()

		static $_last_list_item = '';
		foreach ($list as $key => $val) {	
			$_last_list_item = $key;

			$out .= str_repeat(" ", $depth + 2);
			$out .= "<li>";

			if ( ! is_array($val)) {
				$out .= $val;
			} else {
				$out .= $_last_list_item."\n";
				$out .= _list($type, $val, '', $depth + 4);
				$out .= str_repeat(" ", $depth + 2);
			}

			$out .= "</li>\n";		
		}

		// Set the indentation for the closing tag
		$out .= str_repeat(" ", $depth);

		// Write the closing list tag
		$out .= "</".$type.">\n";

		return $out;
	}

?>
<?php

	function clean_number_format ($int) {
		return str_replace('.00','', number_format($int, 2));
	}

	/**
	* Retruns true if number is divisable by divisor
	* @param integer
	* @param integer
	* @return boolean
	*/
	function is_divisable ($number,$divisor){
		return is_int($number/$divisor)?1:0;
	}

	function format_byte($int) {
		$k = 1024;
		$m = 1048576;
		$g = 1073741824;
		if ($int>=$m && $int<$g) {
			return round(($int/$m),1).'MB';
		} else if ($int>=$g) {
			return round(($int/$g),1).'GB';
		} else {
			return $int.'KB';
		}
	}

	/**
	 * Adds zeros to int to reach 4 digits (12 into 0012)
	 *
	 * @param int
	 * @return int
	 */
	function kThisInt($int) {
		// 9999
		if ($int > 999) {
			$kayd = $int;
		// 0+999
		} else if (($int > 99) && ($int < 1000)) {
			$kayd = "0".$int;
		// 00+99
		} else if (($int > 9) && ($int < 100)) {
			$kayd = "00".$int;
		// 000+9
		} else {
			$kayd = "000".$int;
		}
		return $kayd;
	}

	function ordinal_suffix($int) {
		// Get last number
		$last_int = substr($int, -1, 1);
		switch ($last_int) {
			 case 1: return $int.'st'; break;
			 case 2: return $int.'nd'; break;
			 case 3: return $int.'rd'; break;
			default: return $int.'th';
		}
	}

	function clean_phone($phone) {
		$p = trim(strtolower($phone));
		if (substr($p, 0, 1) == 1) {
			$p = substr($p, 1);
		}
		for ($i=0;$i<strlen($p);$i++) {
			$a = ord(substr($p, $i, 1));
			// If ( Not Numeric ) or ( Not 'x' )
			if ((($a >= 48) && ($a <= 57)) || ($a == 120)) $r .= substr($p, $i, 1);
		}
	  return $r;
	}

	function format_phone($phone) {
		$phone = clean_phone($phone);
		$ret = "";
		$ext = "";
		$i = strpos($phone,'x');
		if (!($i === false)) {
			// Contains extension
			$ext = "x".substr($phone,$i);
			$ext = str_replace('xx', 'x', $ext);
			$phone = substr($phone,0,$i);
		}
		// Phones with no extension
		switch(strlen($phone)) {
			case 7:
				$ret = substr($phone, 0, 3)."-".substr($phone, 3);
				break;
			case 8:
				$ret = substr($phone, 0, 4)."-".substr($phone, 4);
				break;
			case 10:
				$ret = "(".substr($phone, 0, 3).") ".substr($phone, 3, 3)."-".substr($phone, 6, 4);
				break;
			default:
				$ret = $phone;
		}
		return $ret.$ext;
	}

?>
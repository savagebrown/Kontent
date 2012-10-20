<?php

	/**
	 * Prints valid XHTML highlighted code
	 * @param string
	 * @return void
	 */
	function highlightcode($file) {
		$t = show_source( "$file", true );
		$t = ereg_replace( "<font" , "<span" , $t);
		$t = ereg_replace( "</font>", "</span>", $t );
		$t = ereg_replace( "color=\"", "style=\"color:", $t);
		echo $t;
	}

	/**
	 * Show File with line numbers
	 * @param string
	 * @return void
	 */
	function show_file($file) {
		$code = show_source($file,true);
		$kod = "<table cellpadding=\"0\" cellspacing=\"0\">";
		$lines = count(file($file));
		for ($i=1; $i <= $lines; $i++) {
			$kod .= "<tr><td align=\"center\" width=\"20\">";
			$kod .= ($i==1) ?
			  "1</td><td valign=\"top\" rowspan=\"".$lines."\">".$code."</td></tr>" :
			  "$i</td></tr>";
		}
		$kod .= "</table>";
		return $kod;
	}

?>
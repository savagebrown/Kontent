<?php

	/**
	 * This is to remove evil attributes. Borrowed from kind PHP community.
	 *
	 * @param string
	 * @return string
	 */
	function removeEvilAttributes($rea_source) {
		$stripAttrib = "' (style|class)=\"(.*?)\"'i";
		$rea_source = stripslashes($rea_source);
		$rea_source = preg_replace($stripAttrib, '', $rea_source);
		return $rea_source;
	}

	function removeEvilTags($ret_source) {
		$allowedTags='<blockquote><a><h1><h2><h3><h4><em><img><li><ol><strong><ul>';
		$ret_source = strip_tags($ret_source, $allowedTags);
		return preg_replace('/<(.*?)>/ie', "'<'.removeEvilAttributes('\\1').'>'", $ret_source);
	}

	function normalizeNewline($nn_source) {
		/* fix DOS newlines */
		str_replace("\r\n", "\n", $nn_source);
		/* fix MAC newlines */
		str_replace("\r", "\n", $nn_source);
		return $nn_source;
	}

	/**
	 * Clean string of any special characters
	 *
	 * @param string
	 * @return string
	 * @author Savage Brown
	 **/
	function stripPunctuation($string) {
		$string = str_replace('.','',$string);
		$string = str_replace('?','',$string);
		$string = str_replace(',','',$string);
		$string = str_replace("'",'',$string);
		$string = str_replace('!','',$string);
		$string = str_replace('&','and',$string);
		return $string;
	}

	/**
	 * Adds HTML paragraph and break tags.
	 *
	 * Replaces multiple consecutive break tags with paragraph and
	 * disables multiple returns
	 *
	 * @param string
	 * @return string
	 */
	function pTagThis($ptt_source) {

	   // add break tags to source
	   $ptt_source = preg_replace("/(\015\012)|(\015)|(\012)/","&nbsp;<br />", $ptt_source);
	   // replace double break tags with paragraph tags
	   $ptt_source = "<p>" . str_replace( "&nbsp;<br />&nbsp;<br />", "</p><p>", $ptt_source ) . "</p>";
	   // go back and erase combined paragraph and break tags
	   // this will eliminate multiple spaces between paragraphs
	   $ptt_source = str_replace( "</p><p><br />", "</p><p>", $ptt_source );

	   return $ptt_source;

	}

	/**
	 * SPAM safe email
	 *
	 * Formats any given email address for safe-r HTML link display. Email
	 * address name@domain.com will be converted into<br>
	 * <code>
	 * &#109;&#97;&#105;&#108;&#116;&#111;&#58;name&#64;domain&#46;com
	 * </code>
	 * HTML equivalent of mailto:name@domain.com
	 *
	 * @param string
	 * @return string
	 */
	function dieSpammerDie($dsd_source) {
		$mail_to = "&#109;&#97;&#105;&#108;&#116;&#111;&#58;";
		$dsd_source = str_replace("@", "&#64;", $dsd_source);
		$dsd_source = str_replace(".", "&#46;", $dsd_source);
		$dsd_source = $mail_to . $dsd_source;
		return $dsd_source;
	}

	/**
	 * Makes Possessive
	 *
	 * If string ends in "s" - will add apostrophe. If string ends in anything
	 * other than "s" - will add 's<br>
	 * john becomes john's sparticus becomes sparticus'<br>
	 * includes protection for crazy strings like che' (accent).
	 *
	 * @param string
	 * @return string
	 */
	function possessMe($pm_source) {
		// grab last character
		$pm_source_lastChar = substr($pm_source,Ê-1);
		// protection against accent
		if ($pm_source_lastChar != "'") {
			// determine if s or other
			if ($pm_source_lastChar == "s") {
				$pm_source = $pm_source . "'";
			} else {
				$pm_source = $pm_source . "'s";
			}
		} else {
			$pm_source = $pm_source . "s";
		}
		return $pm_source;
	}

	/**
	 * Auto Link
	 *
	 * Sifts through available link Heirarchy |>> URL >> email >> nothing|<br>
	 *
	 * @uses dieSpammerDie()
	 * @uses possessMe()
	 * @param string Author Name
	 * @param string email address of author
	 * @param string URI of author
	 * @return string
	 */
	function whichLink($wl_author, $wl_email, $wl_url = NULL ) {
		// Define comment author link
		if (!$wl_url) {
			// If URL true define as link to use
			$author_link = $wl_url;
			$show_link = "<a href=\"" . $author_link . "\" title=\"" . possessMe($wl_author) . " website\">" . $wl_author . "</a>";
		} elseif (!$wl_email) {
			// if email true define as link to use
			$author_link = dieSpammerDie($wl_email);
			$show_link = "<a href=\"" . $author_link . "\" title=\"" . possessMe($wl_author) . " email\">" . $wl_author . "</a>";
		} else {
			// if URL and email false - no link
			$show_link = $wl_author;
		}
		return $show_link;
	}

	/**
	 * Output specified link + any existing querystring.
	 *
	 * @param string
	 * @return string
	 */
	function redirectWstr($rws_url = null) {

		// Set default if url not given
		if(!$rws_url) {
			$url = $_SERVER['PHP_SELF'];
		} else {
			$url = $rws_url; // set url
		}

		// Pull any query string and add
		if ((isset($_SERVER['QUERY_STRING'])) && ($_SERVER['QUERY_STRING'] != "")) {
			$existing_qs = $_SERVER['QUERY_STRING'];

			// Filter out common doubling
			// Make this so an array can be submited and foreach will run through
			if (isset($_GET['updated'])) {
				$existing_qs = str_replace("update=".$_GET['updated'],"",$existing_qs);
			}

			$url .= (strpos($url, '?')) ? "&" : "?";
			$url .= $existing_qs;
			// remove double ampersands - should improve this to remove multple ampersands
			$url  = str_replace("&&", "&", $url);
		}

		return $url;
	}

	/**
	 * Redirects page to url
	 *
	 * @param String
	 * @return Void
	 */
	function go_to($url = null, $qry = null) {

		// Set default if url not given
		if(!$url && !$qry) {
			$url = $_SERVER['PHP_SELF'];
		}

		if ($qry) {
			$url = $url.$qry;
		}

		// Action if string is false
		//header("Refresh: 0;url=$url");
		header("Location: $url");
		exit;
	}

	function shorten_this($string, $length, $include_last_word = false, $trailer = '&#8230;') {

		if (strlen($string)>$length) {
		
			if ($include_last_word) {
				$l_string = substr($string, 0, $length);
				$r_string = substr($string, $length, strlen($string));
				
				preg_match('/[\w]*[\s]/',$r_string,$match);
				$string = $l_string.$match[0].$trailer;
			} else {
				$string	 = substr($string, 0, $length);
				$string .= $trailer;
			}
		}
		
		return $string;
	}

	/**
	 * Restrict string to a set number of words followed by designated trailer.
	 *
	 * @param string
	 * @param int
	 * @param string
	 * @return string
	 */
	function truncate_this($string, $length, $trailer = '&#8230;') {

		// Create the array of words
		$words = explode(' ', $string);
		// Count the number of words
		$word_count = count($words);
		// Check that word count is more than length
		if ($word_count > $length) {
			// Slice off excess words
			$words = array_slice($words, 0, $length);
			// Bring it back together and add trailer
			$short_string = implode(' ', $words);
			// Clear any white space
			$short_string = trim($short_string);
			// Check last character for comma
			if (substr($short_string, -1) == ',') {
				$short_string = substr($short_string, 0, -1);
			}
			$short_string = $short_string.$trailer;

			return $short_string;
		} else {
			return $string;
		}

	}

	function highlight_word ($word, $text) {
		$highlight =  str_replace($word,'<span class="highlight_term">'.$word.'</span>', $text);
		// find lowercase
		$highlight =  str_replace(strtolower($word),'<span class="highlight_term">'.strtolower($word).'</span>', $highlight);
		// find uppercase
		$highlight =  str_replace(strtoupper($word),'<span class="highlight_term">'.strtoupper($word).'</span>', $highlight);
		// find upper first
		$highlight =  str_replace(ucfirst($word),'<span class="highlight_term">'.ucfirst($word).'</span>', $highlight);
		return $highlight;
	}

	/**
	 * Wrapper for truncat_this().
	 *
	 * @param string
	 * @param int
	 * @param string
	 * @return string
	 */
	function truncate_this_block($string, $length, $trailer = '&#8230;') {
		return truncate_this($string, $length, $trailer = '&#8230;');
	}

	/*
	 * Will place acronym tags within string.
	 * Improvemements will include categories (tech, business, etc)
	 *
	 * @param string
	 * @return string
	 */
	function acronymit($text) {
		$acronyms = array(
					'WYSIWYG' => 'what you see is what you get',
					'XHTML'	  => 'eXtensible HyperText Markup Language',
					'IIRC'	  => 'if I remember correctly',
					'HDTV'	  => 'High Definition TeleVision',
					'LGPL'	  => 'GNU Lesser General Public License',
					'MSDN'	  => 'Microsoft Developer Network',
					'WCAG'	  => 'Web Content Accessibility Guidelines',
					'SOAP'	  => 'Simple Object Access Protocol',
					'OPML'	  => 'Outline Processor Markup Language',
					'MSIE'	  => 'Microsoft Internet Explorer',
					'FOAF'	  => 'Friend of a Friend vocabulary',
					'GFDL'	  => 'GNU Free Documentation License',
					'XSLT'	  => 'eXtensible Stylesheet Language Transformation',
					'HTML'	  => 'HyperText Markup Language',
					'IHOP'	  => 'International House of Pancakes',
					'IMAP'	  => 'Internet Message Access Protocol',
					'RAID'	  => 'Redundant Array of Independent Disks',
					'HPUG'	  => 'Houston Palm Users Group',
					'VNC'	  => 'Virtual Network Computing',
					'URL'	  => 'Uniform Resource Locator',
					'W3C'	  => 'World Wide Web Consortium',
					'MSN'	  => 'Microsoft Network',
					'USB'	  => 'Universal Serial Bus',
					'P2P'	  => 'Peer To Peer',
					'PBS'	  => 'Public Broadcasting System',
					'RSS'	  => 'Rich Site Summary',
					'SIG'	  => 'Special Interest Group',
					'RDF'	  => 'Resource Description Framework',
					'AOL'	  => 'American Online',
					'PHP'	  => 'PHP Hypertext Processor',
					'SSN'	  => 'Social Security Number',
					'JSP'	  => 'Java Server Pages',
					'DOM'	  => 'Document Object Model',
					'DTD'	  => 'Document Type Definition',
					'DVD'	  => 'Digital Video Disc',
					'DNS'	  => 'Domain Name System',
					'CSS'	  => 'Cascading Style Sheets',
					'CGI'	  => 'Common Gateway Interface',
					'CMS'	  => 'Content Management System',
					'FAQ'	  => 'Frequently Asked Questions',
					'FSF'	  => 'Free Software Foundation',
					'API'	  => 'Application Interface',
					'PDF'	  => 'Portable Document Format',
					'IIS'	  => 'Internet Infomation Server',
					'XML'	  => 'eXtensible Markup Language',
					'XSL'	  => 'eXtensible Stylesheet Language',
					'GPL'	  => 'GNU General Public License',
					'KDE'	  => 'K Desktop Environment',
					'IE'	  => 'Internet Explorer',
					'CD'	  => 'Compact Disk',
					'GB'	  => 'Gigabyte',
					'MB'	  => 'Megabyte',
					'KB'	  => 'Kilobyte'
			);
		uksort($acronyms, 'sortr_longer'); // comment out if already sorted

		foreach ($acronyms as $acronym => $definition) {
			$text = preg_replace("#$acronym(?!</(ac|sp))#", "<acronym title=\"$definition\">$acronym</acronym>", $text, 1);
			$text = preg_replace("#$acronym(?!</(ac|sp))#", "<span class='caps'>$acronym</span>", $text);
		}
		return $text;
	}

	/**
	 * Converts URLs and email addresses to links. Works on http://savagebrown.com,
	 *		www.savagebrown.com, koray@savagebrown.com
	 *
	 * @todo 1. Fix bug where email is reapplied if already in link in string
	 *		 2. Fix bug where www.URL is at begining of string the required
	 *			space in front is not found and conversion does not take place
	 * @param string String to be scanned
	 * @param int Length of URL allowed to display
	 * @param string Trailer if URL is truncated
	 * @return string
	 */
	function urlhighlight($str, $show_txt = '', $length=30, $trailer='&#8230;') {
		$pos = strpos(strtolower($str), 'http://');
		if ($pos === false) {
			$str = 'http://'.$str;
		} else {
			$str = $str;
		}
		preg_match_all("/http:\/\/?[^ ][^<]+/i",$str,$lnk);
		$size = sizeof($lnk[0]);
		$i = 0;
		while ($i < $size) {

			if (!$show_txt) {

				$lnk_txt = str_replace('http://', '', $lnk[0][$i]);
				$len = strlen($lnk_txt);
				if($len > $length) {
				$lnk_txt = substr($lnk_txt,0,$length).$trailer;
			   }
			} else {
				$lnk_txt = $show_txt;
			}

			$ahref = $lnk[0][$i];
			$str = str_replace($ahref,"<a href='$ahref' target='_blank'>$lnk_txt</a>",$str);
			$i++;
		}

		return $str;

	}

	function emailhighlight($str, $show_txt='') {

	$lnk_txt = ($show_txt)?$show_txt:'\\1';
		$str = eregi_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',
			'<a href="mailto:\\1">'.$lnk_txt.'</a>', $str);
	return $str;
	}

	// THE FUNCTION:
	// Wrap lines in $oldstr at $wrap characters. Return $newstr.
	/*
	$string="LORD ARTHUR SAVILE'S CRIME\nabcdefghijklmnopqrstuvwxyz When a fortune teller sees murder in the palm of Lord Arthur Savile, there is only one possible solution. Lord Arthur must fulfil his fate before he can marry his great love, the sweetly innocent Sybil.";
	print "<pre>".wraplines($string,20);
	print "</pre><br>";
	print str_replace("\n","<br />\n",wraplines($string,20));
	*/
	function wraplines($oldstr, $wrap) {
		// we expect the following things to be newlines:
		$oldstr=str_replace("<br>","\n",$oldstr);
		$oldstr=str_replace("<BR>","\n",$oldstr);
		$oldstr=str_replace("<br />","\n",$oldstr);
		$newstr = ""; $newline = "";
		// Add a temporarary linebreak at the end of the $oldstr.
		// We will use this to find the ending of the string.
		$oldstr .= "\n";
		do
		{
			// Use $i to point at the position of the next linebreak in $oldstr!
			// If a linebreak is encountered earlier than the wrap limit, put $i there.
			if (strpos($oldstr, "\n") <= $wrap)
			{
				$i = strpos($oldstr, "\n");
			}
			// Otherwise, begin at the wrap limit, and then move backwards
			// until it finds a blank space where we can break the line.
			else
			{
				$i = $wrap;
				while (!ereg("[\n\t ]", substr($oldstr, $i, 1)) && $i > 0)
				{
					$i--;
				}
			}
			// $i should now point at the position of the next linebreak in $oldstr!
			// Extract the new line from $oldstr, including the
			// linebreak/space at the end.
			$newline = substr($oldstr, 0, $i+1);
			// Turn the last char in the string (which is probably a blank
			// space) into a linebreak.
			if ($i!=0) $newline[$i] = "\n";
			// Decide whether it's time to stop:
			// Unless $oldstr is already empty, remove an amount of
			// characters equal to the length of $newstr. In other words,
			// remove the same chars that we extracted into $newline.
			if ($oldstr[0] != "")
			{
				$oldstr = substr($oldstr, $i+1);
			}
			// Add $newline to $newstr.
			$newstr .= $newline;
			// If $oldstr has become empty now, quit. Otherwise, loop again.
		} while (strlen($oldstr) > 0);
		// Remove the temporary linebreak we added at the end of $oldstr.
		$newstr = substr($newstr, 0, -1);
	return $newstr;
	}

	/**
	 * Auto-linker
	 *
	 * Automatically links URL and Email addresses.
	 * Note: There's a bit of extra code here to deal with
	 * URLs or emails that end in a period.	 We'll strip these
	 * off and add them after the link.
	 *
	 * @access	public
	 * @param	string	the string
	 * @param	string	the type: email, url, or both
	 * @param	bool	whether to create pop-up links
	 * @return	string
	 */
	function auto_link($str, $type = 'both', $popup = FALSE)
	{
		if ($type != 'email')
		{
			if (preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
			{
				$pop = ($popup == TRUE) ? " target=\"_blank\" " : "";

				for ($i = 0; $i < sizeof($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['6'][$i]))
					{
						$period = '.';
						$matches['6'][$i] = substr($matches['6'][$i], 0, -1);
					}

					$str = str_replace($matches['0'][$i],
										$matches['1'][$i].'<a href="http'.
										$matches['4'][$i].'://'.
										$matches['5'][$i].
										$matches['6'][$i].'"'.$pop.'>http'.
										$matches['4'][$i].'://'.
										$matches['5'][$i].
										$matches['6'][$i].'</a>'.
										$period, $str);
				}
			}
		}

		if ($type != 'url')
		{
			if (preg_match_all("/([a-zA-Z0-9_\.\-]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", $str, $matches))
			{
				for ($i = 0; $i < sizeof($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['3'][$i]))
					{
						$period = '.';
						$matches['3'][$i] = substr($matches['3'][$i], 0, -1);
					}

					$str = str_replace($matches['0'][$i], safe_mailto($matches['1'][$i].'@'.$matches['2'][$i].'.'.$matches['3'][$i]).$period, $str);
				}

			}
		}
		return $str;
	}

?>
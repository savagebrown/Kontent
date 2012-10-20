<?php

	/**
	 * Wysiwyg Filter cleans the ghastly output of most wysiwyg plugins
	 *
	 * @todo Add repeat variable for recursive. This function is best if run four times
	 * @return string
	 * @author Killer Interactive, LLC
	 **/
	function wysiwygFilter($text) {

		// Clean up -----------------------------------------------------------
		$patterns = array(
						'/\s\s+/', // whitespace
						'/\sstyle=".*?"/', // Strip all styles
						"#(<span.*?>|</span>)#i", // strip all spans
						'{<div[^>]*><br[^>]*>(.*?)</div>}', // empty div br
						'{<div[^>]*>(.*?)<br[^>]*></div>}', // empty div br
						'/<div>(.+?)<\/div>/', // div content
						'/<p>(.+?)<\/p>/', // p content
						'/<\s?[bB]\s?>/', // strong open
						'/<\s?\/\s?[bB]\s?>/', // strong close
						'/<\s?[iI]\s?>/', // emphasis open
						'/<\s?\/\s?[iI]\s?>/' // emphasis close
						 );
		$replacements = array(
						'', // whitespace
						'', // strip all styles
						'', // strip all spans
						'$1', // empty div br
						'$1', // empty div br
						'<p>$1</p>'."\n\n", // div content
						'<p>$1</p>'."\n\n", // p content
						'<strong>', // strong open
						'</strong>', // strong close
						'<em>', // emphasis open
						'</em>' // emphasis close
		);		
		$text = preg_replace($patterns, $replacements, $text);

		// TODO: add these to above preg_replace
		$text = str_replace(
			array('<br></div>','<div></div>'),
			array('</div>',''),
			$text
		);
		// Add new lines ------------------------------------------------------

		$text = str_replace(
			array('<br>','</p>','</h1>','</h2>','</h3>','</h4>','</h5>','</h6>'),
			array("\n",'</p>'."\n\n",'</h1>'."\n\n",'</h2>'."\n\n",'</h3>'."\n\n",'</h4>'."\n\n",'</h5>'."\n\n",'</h6>'."\n\n"),
			$text
		);

		// Textile parser -----------------------------------------------------
		$textile = new Textile();
		$text = trim($text);
		$text = $textile->textileThis($text);
		// Empty <p> and Stuff ------------------------------------------------
		$text = preg_replace('/<p[^>]*>(?:\s+|(?:&nbsp;)+|(?:<br\s*\/?>)+)*<\/p>/', '', $text);

		$text = str_replace(
			array('<p><span></span></p>','<p><span><strong></strong></span></p>','.&nbsp; ','&nbsp;</p>'),
			array('','','. ','</p>'),
			$text
		);
		// Remove remaining empty tags and another go at the freakin <p> ------
		$text = preg_replace(
			array("/<[^\/>]*>([\s]?)*<\/[^>]*>/", "/<p[^>]*><\\/p[^>]*>/"),
			'',
			$text
		);
		
		// Smart Quotes and the such ------------------------------------------
		// Utf
		$text = str_replace(
			array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
			array("'", "'", '"', '"', '-', '--', '...'),
			$text
		);
		// Windows
		$text = str_replace(
			array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
			array("'", "'", '"', '"', '-', '--', '...'),
			$text
		);

		// Incidentals --------------------------------------------------------
/*

 ADD NEW FILTERS HERE
 CONSOLIDATE ON NEW VERSION OF LIBRARY

*/
		$text = str_replace(
			array(' class="Content"', ' class="content"',' class="Apple-style-span"'),
			'',
			$text
		);

		return $text;
	}

?>
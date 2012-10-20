<?php

/**
 * Displays a random image from specified folder.
 *
 * Call from separate file for each random instance.
 * Include:
 * <code>
 * <?php
 *		 require_once 'fnctn.rndmFldrImg.php';
 *		 $path = 'img/path';
 *		 random_folder_image($path)
 * ?>
 * </code>
 * HTML:
 * <code>
 * <html>
 *		 <body>
 *		 <img src = "random_img.php" Title="Sometitle">
 *		 </body>
 * </html>
 * </code>
 *
 * @param string
 * @return void
 */
function random_folder_image($fldrPath) {

	$files = array();
	if ($handle=opendir("$fldrPath")) {
		while(false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if(	substr($file,-3)=='gif' ||
					substr($file,-3)=='jpg' ||
					substr($file,-3)=='png' ||
					substr($file,-3)=='bmp')
 					$files[count($files)] = $file;
				}
			}
		}
		closedir($handle);
		$random=rand(0,count($files)-1);
		if(substr($files[$random],-3)	  == 'gif') header("Content-type: image/gif" );
		elseif(substr($files[$random],-3) == 'jpg') header("Content-type: image/jpeg");
		elseif(substr($files[$random],-3) == 'png') header("Content-type: image/png" );
		elseif(substr($files[$random],-3) == 'bmp') header("Content-type: image/bmp" );
		readfile("$fldrPath/$files[$random]");
	}


?>
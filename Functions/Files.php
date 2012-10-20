<?php

	/**
	 * File Functions
	 *
	 * @package SBLIB
	 */

	/**
	 * Creates writable directories and subdirectories
	 *
	 * @param string File path to create /path/to/file/
	 * @return mixed
	 */
	function createFolder($folder) {

		$folder_list = split("/", $folder);
		$len = count($folder_list);
		
		for($i=0; $i<$len; $i++) {
			$tmp .= $folder_list[$i] . '/';
			@mkdir($tmp, 0777);
			$old = umask(0);
			@chmod($tmp, 0777);
			umask($old);
		}
	 }
 
	/**
	 * Get Extension of File
	 *
	 * Gets the extension of a file/string
	 *
	 * @param string
	 * @return string
	 */
	function get_ext($ge_source) {
		return substr($ge_source, strrpos($ge_source, ".") + 1);
	}
 
	/**
	 * Get a list of a directories contents
	 *
	 * @param string path of directory
	 * @param bool True return real path false returns filename
	 * @return mixed
	 */
	function dir_contents($dir, $realpath = true) {
		// Instatiate $files as array
		$files = array();
		// Open directory
		$open = opendir($dir);
		// loop through contents
		while($file = readdir($open)) { 
			if($file != "." && $file != ".."			  // hidden files
							&& $file!=".DS_Store"		  // mac hell
							&& substr($file, 0, 1) != "." // hidden files 
							) {
				// If real path is requested		   
				if ( $realpath ) {			  
					$files[] = realpath($dir).DIRECTORY_SEPARATOR.$file;
				// only files names
				} else {
					$files[] = $file;
				}
			 } 
		}
		// Close Directory
		closedir($open);
		// Alphabetical
		sort($files);
		// Internal pointer of array back to its first element
		reset($files);
		// return array or false if array is empty
		if (!empty($files)) {
			return $files;
		} else {
			return false;
		}
	}

	function dir_size($folder) {
		if( !is_dir($folder) ): return -1; endif;

		$handle = opendir($folder); $dirsize = 0;
		$folder = preg_replace('/(\/|\\\\)+$/', '', $folder).'/';

		while(($currentItem = readdir($handle)) !== false) {
			if(( $currentItem != '.' ) && ( $currentItem != '..' )) {
				$function = is_dir($folder.$currentItem) ? 'dirsize' : 'filesize';
				$dirsize += call_user_func($function, $folder.$currentItem);
			}
		}

		return $dirsize;
	}

	/**
	 * Get number of files in directory
	 *
	 * @param string path of directory
	 * @param bool True return real path false retuns filename
	 * @return mixed
	 */
	function dir_file_count($dir, $realpath = true) {
		// Open directory
		$open = opendir($dir);
		// Instantiate file count
		$count = 0;
		// loop through contents
		while($file = readdir($open)) { 
			if($file != "." && $file != ".."			  // hidden files
							&& $file!=".DS_Store"		  // mac hell
							&& substr($file, 0, 1) != "." // hidden files 
							) {
				$count++;
			 } 
		}
		// Close Directory
		closedir($open);
		return $count;
	}

	/**
	 */
	function rm($fileglob) {
		delete_files($path, $del_dir = true, $level = 0);
	}

	function delete_files($path, $del_dir = FALSE, $level = 0)
	{	
		// Trim the trailing slash
		$path = preg_replace("|^(.+?)/*$|", "\\1", $path);

		if ( ! $current_dir = @opendir($path))
			return;

		while(FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." and $filename != "..")
			{
				if (is_dir($path.'/'.$filename))
				{
					$level++;
					delete_files($path.'/'.$filename, $del_dir, $level);
				}
				else
				{
					unlink($path.'/'.$filename);
				}
			}
		}
		@closedir($current_dir);

		if ($del_dir == TRUE AND $level > 0)
		{
			@rmdir($path);
		}
	}

	function force_download($filename = '', $data = '')
	{
		if ($filename == '' OR $data == '')
		{
			return FALSE;
		}

		// Try to determine if the filename includes a file extension.
		// We need it in order to set the MIME type
		if (FALSE === strpos($filename, '.'))
		{
			return FALSE;
		}

		// Grab the file extension
		$x = explode('.', $filename);
		$extension = end($x);

		// Load the mime types
		$mimes = get_mimes();

		// Set a default mime if we can't find it
		if ( ! isset($mimes[$extension]))
		{
			$mime = 'application/octet-stream';
		}
		else
		{
			$mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
		}

		// Generate the server headers
		if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
			header('Content-Type: "'.$mime.'"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header("Content-Transfer-Encoding: binary");
			header('Pragma: public');
			header("Content-Length: ".strlen($data));
		} else {
			header('Content-Type: "'.$mime.'"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header("Content-Transfer-Encoding: binary");
			header('Expires: 0');
			header('Pragma: no-cache');
			header("Content-Length: ".strlen($data));
		}

		echo $data;
	}

	function get_mimes() {
		$mimes = array( 'hqx'	=>	'application/mac-binhex40',
						'cpt'	=>	'application/mac-compactpro',
						'csv'	=>	array('text/x-comma-separated-values', 'application/vnd.ms-excel'),
						'bin'	=>	'application/macbinary',
						'dms'	=>	'application/octet-stream',
						'lha'	=>	'application/octet-stream',
						'lzh'	=>	'application/octet-stream',
						'exe'	=>	'application/octet-stream',
						'class' =>	'application/octet-stream',
						'psd'	=>	'application/x-photoshop',
						'so'	=>	'application/octet-stream',
						'sea'	=>	'application/octet-stream',
						'dll'	=>	'application/octet-stream',
						'oda'	=>	'application/oda',
						'pdf'	=>	array('application/pdf', 'application/x-download'),
						'ai'	=>	'application/postscript',
						'eps'	=>	'application/postscript',
						'ps'	=>	'application/postscript',
						'smi'	=>	'application/smil',
						'smil'	=>	'application/smil',
						'mif'	=>	'application/vnd.mif',
						'xls'	=>	array('application/excel', 'application/vnd.ms-excel'),
						'ppt'	=>	'application/powerpoint',
						'wbxml' =>	'application/wbxml',
						'wmlc'	=>	'application/wmlc',
						'dcr'	=>	'application/x-director',
						'dir'	=>	'application/x-director',
						'dxr'	=>	'application/x-director',
						'dvi'	=>	'application/x-dvi',
						'gtar'	=>	'application/x-gtar',
						'gz'	=>	'application/x-gzip',
						'php'	=>	'application/x-httpd-php',
						'php4'	=>	'application/x-httpd-php',
						'php3'	=>	'application/x-httpd-php',
						'phtml' =>	'application/x-httpd-php',
						'phps'	=>	'application/x-httpd-php-source',
						'js'	=>	'application/x-javascript',
						'swf'	=>	'application/x-shockwave-flash',
						'sit'	=>	'application/x-stuffit',
						'tar'	=>	'application/x-tar',
						'tgz'	=>	'application/x-tar',
						'xhtml' =>	'application/xhtml+xml',
						'xht'	=>	'application/xhtml+xml',
						'zip'	=> array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
						'mid'	=>	'audio/midi',
						'midi'	=>	'audio/midi',
						'mpga'	=>	'audio/mpeg',
						'mp2'	=>	'audio/mpeg',
						'mp3'	=>	'audio/mpeg',
						'aif'	=>	'audio/x-aiff',
						'aiff'	=>	'audio/x-aiff',
						'aifc'	=>	'audio/x-aiff',
						'ram'	=>	'audio/x-pn-realaudio',
						'rm'	=>	'audio/x-pn-realaudio',
						'rpm'	=>	'audio/x-pn-realaudio-plugin',
						'ra'	=>	'audio/x-realaudio',
						'rv'	=>	'video/vnd.rn-realvideo',
						'wav'	=>	'audio/x-wav',
						'bmp'	=>	'image/bmp',
						'gif'	=>	'image/gif',
						'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
						'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
						'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
						'png'	=>	array('image/png',	'image/x-png'),
						'tiff'	=>	'image/tiff',
						'tif'	=>	'image/tiff',
						'css'	=>	'text/css',
						'html'	=>	'text/html',
						'htm'	=>	'text/html',
						'shtml' =>	'text/html',
						'txt'	=>	'text/plain',
						'text'	=>	'text/plain',
						'log'	=>	array('text/plain', 'text/x-log'),
						'rtx'	=>	'text/richtext',
						'rtf'	=>	'text/rtf',
						'xml'	=>	'text/xml',
						'xsl'	=>	'text/xml',
						'mpeg'	=>	'video/mpeg',
						'mpg'	=>	'video/mpeg',
						'mpe'	=>	'video/mpeg',
						'qt'	=>	'video/quicktime',
						'mov'	=>	'video/quicktime',
						'avi'	=>	'video/x-msvideo',
						'movie' =>	'video/x-sgi-movie',
						'doc'	=>	'application/msword',
						'word'	=>	array('application/msword', 'application/octet-stream'),
						'xl'	=>	'application/excel',
						'eml'	=>	'message/rfc822'
					);
		return $mimes;
	}

?>
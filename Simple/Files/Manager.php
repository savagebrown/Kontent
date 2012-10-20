<?php
	// TODO: add del all option somewhere. Admin settings?
	// FIXME: forcedownload stalls and crashes browser

	/**
	 * Simple File Manager
	 *
	 * @package		 SB Library
	 * @author		 Koray Girton/Savagebrown, Inc.
	 * @todo		 Updates/Additions:
	 *				 1. Add directory browsing
	 *				 2. Add column filtering by name, ext, filesize & date
	 *
	 * @version		 2.5b
	 * @category	 CMS Family
	 * @copyright	 Copyright (c) 2000-2004 SAVAGEBROWN.COM
	 * @license		 http://opensource.org/licenses/gpl-license.php The GNU General Public License (GPL) v2
	 * @filesource
	 *
	 * @link http://savagematter.local/~savagebrown/Lib/examples/SimpleFileManager
	 */
	class simple_files_manager {

		/**
		 * Table Header
		 * @access public
		 * @var string
		 */
		var $title;
		/**
		 * File Upload name
		 * @access public
		 * @var string
		 */
		var $fileupload_name;
		/**
		 * File detail pAge
		 * @access public
		 * @var string
		 */
		var $file_detail_page = 'details.php';
		/**
		 * The upload store directory (chmod 777)
		 * @access public
		 * @var string
		 */
		var $file_store= "store";
		/**
		 * Uploadable mimtypes
		 * @access public
		 * @var array
		 */
		var $mimetypes = array();
		var $mimeTypes = array();
		/**
		 * Option to display the file list
		 * To enable set to TRUE - to disable set to FALSE
		 * @access public
		 * @var bool
		 */
		var $file_list_allow = true;
		/**
		 * Option to allow file deletion
		 * To enable set to TRUE - to disable set to FALSE
		 * @access public
		 * @var bool
		 */
		var $file_del_allow = true;
		/**
		 * Error message
		 * @access public
		 * @var mixed
		 */
		var $error_message;
		/**
		 * Success message
		 * @access public
		 * @var mixed
		 */
		var $success_message;
		/**
		 * Warning messages
		 * @access public
		 * @var array
		 */
		var $warning = array();
		/**
		 * Kill style is used for fatal exception warnings since html to call a
		 * style sheet is not available.
		 * @access public
		 * @var string
		 */
		var $kill_style = '<div style="color:red;
									 padding:10px;
									 background:#fff0ae;
									 border:1px solid brown;
									 width:450px;
									 margin-top;100px;">';
		/**
		 * Option to activate alternate row colors for file list table.
		 *
		 * To enable set to TRUE - to disable set to FALSE
		 * @access public
		 * @var bool
		 */
		var $alt_row_enable = true;
		/**
		 * One property to rule them all.
		 * Optional array to set all properties.
		 * @access public
		 * @var array
		 */
		var $options;

		/**
		 * Constructor Method
		 *
		 * This will clear cache, catch all _GET and _POST variables and set
		 * any error or success messages.
		 *
		 * @access private
		 * @return void
		 *
		 */
		function __construct($options = null) {
		
			$default_options = array(
				'title'				=> false,
				'file_store'		=> "STORAGE_LOCATION",
				'file_list_allow'	=> true,
				'file_del_allow'	=> true,
				'alt_row_enable'	=> true,
				'detail_page'		=> 'file_details.php',
				'upload_file_id'	=> 'DB_FIELD_NAME'
			);
		
			if ($options == null) {
				$options = $default_options;
			} else {
				$options['title'] 			== isset($options['title']) ? $options['title'] : $default_options['title'];
				$options['file_store'] 		== isset($options['file_store']) ? $options['file_store'] : $default_options['file_store'];
				$options['file_list_allow'] == isset($options['file_list_allow']) ? $options['file_list_allow'] : $default_options['file_list_allow'];
				$options['file_del_allow'] 	== isset($options['file_del_allow']) ? $options['file_del_allow'] : $default_options['file_del_allow'];
				$options['alt_row_enable'] 	== isset($options['alt_row_enable']) ? $options['alt_row_enable'] : $default_options['alt_row_enable'];
				$options['detail_page'] 	== isset($options['detail_page']) ? $options['detail_page'] : $default_options['detail_page'];
				$options['upload_file_id'] 	== isset($options['upload_file_id']) ? $options['upload_file_id'] : $default_options['upload_file_id'];
			}

			// Settings
			$this->title 			= $this->set_option($options['title'],				$this->title);
			$this->file_store 		= $this->set_option($options['file_store'],			$this->file_store);
			$this->mimetypes 		= $this->set_option($options['mimetypes'],			$this->mimetypes);
			// Permissions
			$this->file_list_allow 	= $this->set_option($options['file_list_allow'],	$this->file_list_allow);
			$this->file_del_allow 	= $this->set_option($options['file_del_allow'],		$this->file_del_allow);
			// Cosmetic
			$this->alt_row_enable 	= $this->set_option($options['alt_row_enable'],		$this->alt_row_enable);
			$this->kill_style 		= $this->set_option($options['kill_style'],			$this->kill_style);
			// Goto page
			$this->file_detail_page = $this->set_option($options['detail_page'],		$this->file_detail_page);
			
			$this->upload_file_id 	= $this->set_option($options['upload_file_id'],		'fileupload');

			// Filelist sorting
			$this->set_sort_by();
			$this->set_sort_direction();
			// Filelist Filter
			$this->set_file_filter();
			// Act on paramaters
			$this->act();
			// Clear the stat cache
			// clearstatcache();
			// Check if $file_store is writable
			$this->check_chmod($this->file_store);
		}

		/**
		 * Acts on $_GET['act'] : Force download, delete, delete all
		 *
		 * @access private
		 * @return void
		 */
		private function act() {

			// Delete single file -------------------------------------------

			if ( ($_GET['act'] == "del") && $_GET['file'] ) {

				$value_de = base64_decode($_GET['file']);
				// Check file permissions
				if (@unlink($this->file_store.'/'.$value_de)) {
					$this->success_message = 'The file '.$value_de.' has been deleted successfully!';
				} else {
					$this->error_message = 'The file '.$value_de.' could not be deleted. It may be locked. Please contact the system administrator for additional help.';
				}
			}

			// Download file ------------------------------------------------

			if (($_GET['act'] == "dl") && $_GET['file']) {

				$value_de = base64_decode($_GET['file']);
				$dl_full  = $this->file_store."/".$value_de;
				$dl_name  = $value_de;

				if (!file_exists($dl_full)) {
					print ( $this->kill_style.'Could not download the file,
									 it does not exist. Please <a href="'.
									 $_SERVER[PHP_SELF].'">go back</a> to check
									 the file list. It may have been updated by
									 another party.</div>' );
					exit();
				}

				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename=$dl_name");
				header("Content-Length: ".filesize($dl_full));
				header("Accept-Ranges: bytes");
				header("Pragma: no-cache");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-transfer-encoding: binary");

				@readfile($dl_full);

				exit();

			}

			// Delete all files ---------------------------------------------

			if ($_GET['act'] == "delall") {

				$file = array();
				$open = opendir($this->file_store);
				$number_deleted = 0;
				while($file = readdir($open)) {
					if (($file != ".") && ($file != "..")) {
						if (@unlink($this->file_store."/".$file)) {
							$number_deleted++;
						}
					}
				}
				closedir($open);

				if ($number_deleted > 0) {
					$this->success_message = $number_deleted.' files have been deleted! <em>(If any files remain they may be locked. Please contact the system administrator for additional help.)</em>';
				} else {
					$this->error_message = 'No files could be deleted. All files may be locked. Please contact the system administrator for additional help.';
				}
			}
		}

		function force_download ($data, $name, $mimetype='', $filesize=false) {
			// File size not set?
			if ($filesize == false OR !is_numeric($filesize)) {
				$filesize = filesize($data);
			}

			// Mimetype not set?
			if (empty($mimetype)) {
				$mimetype = 'application/octet-stream';
			}

			// Make sure there's not anything else left
			$this->ob_clean_all();

			// Start sending headers
			header("Pragma: public"); // required
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false); // required for certain browsers
			header("Content-Transfer-Encoding: binary");
			header("Content-Type: " . $mimetype);
			header("Content-Length: " . $filesize);
			header("Content-Disposition: attachment; filename=\"" . $name . "\";" );

			// TODO: Determine purpose and clean up
			// Send data
			echo 'http://savagebrown.local/~sb/baxcommercial/Assets/'.$name;
			die();
		}

		function ob_clean_all () {
			$ob_active = ob_get_length () !== false;
			while($ob_active) {
				ob_end_clean();
				$ob_active = ob_get_length () !== false;
			}

			return true;
		}

		/**
		 * Sets member variables with preference variable if specified else
		 * revert to default.
		 *
		 * @access private
		 * @param string Prefernce variable
		 * @param string Property variable
		 * @return mixed
		 */
		function set_option($preference, $self) {
			// If Boolean
			if (!is_bool($preference)) {
				if ($preference != "") {
					$preference = trim($preference);
					return $preference;
				} else {
					return $self;
				}
			// If String
			} else {
				if (isset($preference)) {
					if ($preference) {
						return true;
					} else {
						return false;
					}
				} else {
					if ($self) {
						return true;
					} else {
						return false;
					}
				}
			}

		}

		/**
		 * Sets property variables with uploaded file info
		 * @private
		 * @return void
		 */
		function set_upload_info() {
			// Upload variables
			$this->upload_file_tmp	= $_FILES[$this->post_upload]['tmp_name'];
			$this->upload_file_name = $_FILES[$this->post_upload]['name'];
			$this->upload_file_size = $_FILES[$this->post_upload]['size'];
			$this->upload_file_type = $_FILES[$this->post_upload]['type'];
		}

		/**
		 * @param int
		 */
		function get_warning($number) {
			switch ($number) {
				 case 100:
					$message = '<div class="system_note">You do not have
								suffecient priveleges to view files.<br />
								However, on the bright side, you may still
								upload files.<br /><strong>
								<a href="?new=1">Upload a file now</a></strong>
								</div>';
					break;
				 case 102:
					$message = '<div class="system_note">No Files have been
								uploaded.<br /><strong><a href="?new=1">Upload
								a file now</a></strong></div>';
					break;
				 case 101:
					$message = '<div class="system_note">No files of this type
								have been uploaded.<br /><strong>
								<a href="?new=1">Upload a file now</a>
								</strong></div>';
					break;
			}
			return $message;
		}
		/**
		 *
		 *
		 */
		function check_chmod($directory) {
			// Is the defined store directory writeble?
			if (!eregi("777", decoct(@fileperms($directory)))) {
				// Display error message that no write permissions are granted to
				// specified directory $file_store
				print( $this->kill_style.'Can not access the
					   directory. Please chmod the "'.$directory.'" directory with value
					   0777 (xrw-xrw-xrw) and then <a href="'.$_SERVER['PHP_SELF'].'">
					   refresh this page</a></div>');
				exit;
			} else {

				return true;
			}
		}

		/**
		 * Will set provided array as mimetypes
		 *
		 * @access public
		 * @param array
		 * @return bool
		 */
		function get_mimetypes($mimetypes) {

			if ( !empty($mimetypes) ) {
				$this->mimetypes = $mimetypes;
				return true;
			} else {
				return false;
			}
		}

		private function set_sort_by() {
			$_COOKIE['sort'] = '';
			if (isset($_GET['sort']) && $_GET['sort'] !='') {
				setcookie('sort', $_GET['sort']);
				$this->sort_by = $_GET['sort'];
			} else {
				if (isset($_COOKIE['sort']) && $_COOKIE['sort'] !='') {
					$this->sort_by = $_COOKIE['sort'];
				} else {
					$this->sort_by = 'name';
				}
			}
		}

		private function set_sort_direction()
		{
			if ($_GET['sort_direction'] == 'ASC') {
				setcookie('sort_direction', 'ASC');
				$this->sort_by_direction = 'ASC';
			} else if ($_GET['sort_direction'] == 'DESC') {
				setcookie('sort_direction', 'DESC');
				$this->sort_by_direction = 'DESC';
			} else if ($_COOKIE['sort_direction']) {
				$this->sort_by_direction = $_COOKIE['sort_direction'];
			} else {
				$this->sort_by_direction = 'ASC';
			}
		}

		private function set_file_filter() {
			if (isset($_GET['filter']) && $_GET['filter']!='') {
				$this->file_filter = $_GET['filter'];
			} else {
				$this->file_filter = 'all';
			}
		}

		private function get_filter_groups($type) {
			$groups = array(
				'images' => array('jpg','jpeg','png','gif','tiff'),
				'documents' => array('xls','ppt','doc', 'docx', 'txt'),
				'archives' => array('sit','rar','zip'),
				'videos' => array('m4v','mov','mp4','avi','flv'),
				'pdfs' => array('pdf')
			);
			return $groups[$type];
		}

		/**
		 * Build the file list
		 *
		 * Opens specified directory and list its contents. Will exclude
		 * specified files (i.e. ".", "..", etc.). Will not list
		 * sub directories.
		 *
		 * @
		 * @access private
		 * @return mixed
		 */
		function build_file_list() {

			$opendir = @opendir($this->file_store);

			while ($readdir = @readdir($opendir)) {

				if ($readdir <> "." && $readdir <> ".."
									&& $readdir <> ".DS_Store"
									&& $readdir <> "index.php"
									&& $readdir <> "index.html"
									&& !is_dir($this->file_store.'/'.$readdir)) {
					if ($this->file_filter == 'all') {
						$filearr[] = $readdir;
					} else {
						if (in_array($this->get_ext($readdir), $this->get_filter_groups($this->file_filter))) {
							$filearr[] = $readdir;
						}
					}
				}

			}

			if (!empty($filearr)) {
				switch ($this->sort_by) {
					case 'name':
						foreach ($filearr as $v) {
							$sort[$v] = $v;
						}
						break;
					case 'date':
						foreach ($filearr as $v) {
							$sort[filemtime($this->file_store."/".$v)] = $v;
						}
						break;
					case 'size':
						foreach ($filearr as $v) {
							$sort[filesize($this->file_store."/".$v)] = $v;
						}
						break;
				}
				if ($this->sort_by_direction == 'ASC') {
					ksort($sort);
				} else {
					krsort($sort);
				}
			}

			if (is_array($sort) && $sort != '') {
				$this->filelist_count = count($sort);
				$this->file_list = $sort;
			} else {
				$this->file_list = '';
				return false;
			}
		}

		public function get_filelist_count() {
			return $this->filelist_count;
		}

		/**
		 * Display the complied file list
		 *
		 * @param array
		 * @access public
		 * @return string
		 */
		function display_file_list($img_dir = null, $icon_dir = null) {

			if (($this->file_list_allow != false) && is_array($this->file_list)) {

				// Table header
				$table_caption = ($this->title)?'summary="'.$this->title.'"':'';

				// Add filter paramater
				$filter_param = ($_GET['filter']!='')?'&filter='.$_GET['filter']:'';

				// Sort link
				$sort_link_name	 = 'ASC'.$filter_param;
				$sort_link_date	 = 'ASC'.$filter_param;
				$sort_link_size	 = 'ASC'.$filter_param;
				$sort_link_for	 = 'sort_link_'.$this->sort_by;
				$sort_link_for	 = ($this->sort_by_direction =='ASC')?'DESC'.$filter_param:'ASC'.$filter_param;

				// Sort image
				$sort_image_name = 'nosort';
				$sort_image_date = 'nosort';
				$sort_image_size = 'nosort';
				$sort_image_for	 = 'sort_image_'.$this->sort_by;
				$sort_image_for = $this->sort_by_direction;

				// Table start
				$fmt = <<<HTML

<table id="file-manager" $table_caption border="0" cellspacing="0" cellpadding="0">
<caption>$table_caption</caption>
<thead>
	<th class="left" scope="col" nowrap="nowrap"><a$hightlight_sort_name href="?sort=name&sort_direction={$sort_link_name}">Name</a> <img src="$img_dir/$sort_image_name.gif" alt="" align="absmiddle" /></th>
	<!--<th scope="col" nowrap >File Type</th>-->
	<th scope="col" nowrap="nowrap"><a$hightlight_sort_size href="?sort=size&sort_direction={$sort_link_size}">Size</a> <img src="$img_dir/$sort_image_size.gif" alt="" align="absmiddle" /></th>
	<th scope="col" nowrap="nowrap"><a$hightlight_sort_date href="?sort=date&sort_direction={$sort_link_date}">Date</a> <img src="$img_dir/$sort_image_date.gif" alt="" align="absmiddle" /></th>
	<th scope="col" nowrap="nowrap" colspan="2">Actions</th>
</thead>
<tbody>

HTML;

				for ($i=1; $i <= count($this->file_list); $i++) {

					if ($this->alt_row_enable) {
						// Alternate row
						$alt_row = ($i & 1) ? '#fff' : '#f2f2f2';
						$set_bgcolor = ' bgcolor="'.$alt_row.'"';
					}

					@list($key, $value) = each($this->file_list);

					if ($value) {
						$value_en = base64_encode($value);
						$value_view=$value;

						if (strlen($value) >= 48) {
							$value_view = substr($value_view, 0, 45) . '&hellip;';
						}

						// Filename -----------------------------------------
						$file_icon = ($icon_dir)?'<img src="'.$icon_dir.'/'.$this->get_file_icon($value).'" align="absmiddle" style="margin-right:8px;" />':'';
						$file_link = $this->file_detail_page.'?file='.$value;
						$value_view = $file_icon.'<a class="lbOn" href="'.$file_link.'">'.$value_view.'</a>';

						// File size ----------------------------------------
						$show_filesize = $this->get_bytage(filesize($this->file_store."/".$value));

						// File time ----------------------------------------
						if ( date("Ymd", filemtime($this->file_store."/".$value)) == date("Ymd")) {
							$file_time = '<span>Today</span>';
						} else {
							$file_time = date("m/d/y", filemtime($this->file_store."/".$value));
						}

						// File delete link ---------------------------------
						if ( $this->file_del_allow ) {
							$del_text = ($img_dir)?'<img src="'.$img_dir.'/trash.gif" />':'Delete';
							$delete_link = "&nbsp;<a style=\"color:red;\" title=\"Delete file\" href=\"javascript:;\" onClick=\"cf=confirm('Are you sure you want to delete this file?');if (cf)window.location='?act=del&file=$value_en'; return false;\">".$del_text."</a>";
						} else {
							$del_text = ($img_dir)?'<img src="'.$img_dir.'/trash_fade.gif" />':'Delete';
							$delete_link = '&nbsp;<span class="grey_out">'.$del_text.'</span>';
						}

						// File download link -------------------------------
						$dwnld_text = ($img_dir)?'<img src="'.$img_dir.'/download.gif" />':'Download';
						$download_link = '<a style="color:green;" title="Download file" href="?act=dl&file='.$value_en.'">'.$dwnld_text.'</a>';
						//$download_link = '<a style="color:green;" title="Download file" href="/Assets/'.$value.'">'.$dwnld_text.'</a>';

						// Table body ---------------------------------------
						$fmt .= <<<HTML

<tr$set_bgcolor>
	<td class="file-name"		nowrap="nowrap">$value_view</td>
	<td class="file-size"		nowrap="nowrap">$show_filesize</td>
	<td class="file-date"		nowrap="nowrap">$file_time</td>
	<td class="file-download"	nowrap="nowrap">$download_link</td>
	<td class="file-delete"		nowrap="nowrap">$delete_link</td>
</tr>

HTML;

					}
				}

				$fmt .= <<<HTML

	</tbody>
<!--
	<tfoot>
		<tr>
			<td colspan="5">Should I place a pager here?</td>
		</tr>
	</tfoot>
-->
</table>

HTML;

			} else { // end is array

				// Can not show dir contents display error
				if (!$this->file_list_allow) {
					$fmt = $this->get_warning(100);
				} else if ($this->file_filter!='all') {
					$fmt = $this->get_warning(101);
				} else {
					$fmt = $this->get_warning(102);
				}
			}

			return $fmt;

		}

		/**
		 *
		 */
		function upload_file() {

			// Set the file information
			$this->set_upload_info();

			$uploadpath = $this->file_store."/";
			$source = $_FILES[$this->upload_file_id][tmp_name];
			$this->fileupload_name = $_FILES[$this->upload_file_id][name];
			$weight = $_FILES[$this->upload_file_id][size];

			// Set the rename option if submitted
			if ($_POST['rename']) {
				$fileupload_rename = strtolower($this->clean($_POST['rename']).'.'.$this->get_ext($this->fileupload_name));
			} else {
				$this->fileupload_name = strtolower($this->clean($this->fileupload_name).'.'.$this->get_ext($this->fileupload_name));
			}

			// Original full
			$destination = $uploadpath.$this->fileupload_name;
			// Rename full
			$destination_rename = $uploadpath.$fileupload_rename;

			// Check if original exists
			if (file_exists($destination)) {
				// File exists
				$continue_this = false;
				$return_fileupload_name = $this->fileupload_name;
			} else {
				// File does not exist
				$continue_this = true;
				$return_fileupload_name = $this->fileupload_name;
			}

			// Check if the renamed exists
			if ($_POST['rename']) {
				if (file_exists($destination_rename)) {
					// File exists
					$continue_this = false;
					$return_fileupload_name = $fileupload_rename;
				} else {
					// File does not exist
					$continue_this = true;
					$return_fileupload_name = $fileupload_rename;
					$destination = $uploadpath.'temp'.$this->get_ext($this->fileupload_name);
				}
			}

			if ($continue_this) {

				// Copy temparary file to specified directory
				if (copy($source, $destination)) {
					// If rename field is filled rename file
					if ($_POST['rename']) {
						$_POST['rename'] = strtolower($this->clean($_POST['rename']));
						$exfile = explode(".", $this->fileupload_name);

						if (@rename($destination, $destination_rename)) {
							$this->fileupload_name = $fileupload_rename;
						}
					}

					// Make sure chmod is correct
					$old_mask = umask(0);
					@chmod($uploadpath.$this->fileupload_name, 0777);
					umask($old_mask);

					// Set success message
					$this->success_message = 'The file '.$this->fileupload_name.' has been uploaded successfully.';
					return true;
				} else {
					$this->error_message = 'There was an error uploading the file. Please contact the system administrator.';
					return false;
				}
			} else {
				$newFileName = explode('.', $return_fileupload_name);
				$this->error_message = 'The file "'.$return_fileupload_name.'" was uploaded previously. Please choose another file to upload or rename the file as you upload. <em>(for example: '.$return_fileupload_name.' to '.$newFileName[0].'_ver2.'.$newFileName[1].')</em>';
				return false;
			}

		}

		/**
		 * Retrieves name of uploaded file
		 * @return string
		 */
		function get_fileupload_name() {
			return $this->fileupload_name;
		}
		
		function get_fullfilename() {
			return $this->file_store.$this->fileupload_name;
		}

		/**
		 *
		 */
		function get_ext($file_name) {
			$pos = strrpos($file_name,".");
			$extension = substr($file_name, $pos+1);

			return strtolower($extension);
		}

		/**
		 *
		 */
		function clean($string) {

			// trim whitespace
			$string = trim($string);
			// Clean out undesirables
			$string = str_replace("/","",$string);
			$string = str_replace("\\","",$string);
			$string = str_replace(":","",$string);
			$string = str_replace("*","",$string);
			$string = str_replace("?","",$string);
			$string = str_replace("<","",$string);
			$string = str_replace(">","",$string);
			$string = str_replace("\"","",$string);
			$string = str_replace("|","",$string);
			$string = str_replace("#","",$string);
			$string = str_replace("$","",$string);
			$string = str_replace("%","",$string);
			$string = str_replace("@","",$string);
			$string = str_replace("'","",$string);

			// Replace spaces with underscores
			$string = str_replace(" ","_",$string);

			if (strrpos($string,".")) {
				$pos = strrpos($string,".");
				$string = substr($string, 0, $pos);
			}

			return $string;
		}

		/**
		 *
		 *
		 */
		function get_bytage($file_size) {

			if ($file_size >= 1048576) {
				$file_size_rnd = round(($file_size/1024000), 2)." MB";
			} elseif ($file_size >= 1024) {
				$file_size_rnd = round(($file_size/1024), 2)." KB";
			} elseif ($file_size >= 0) {
				$file_size_rnd = $file_size." bytes";
			} else {
				$file_size_rnd = "0 bytes";
			}

			return $file_size_rnd;
		}

		function get_mimetype_list(){
						
			// ------------ Images -------------
			
			$this->mimeTypes['.psd'] = array('application/psd','image/photoshop','image/x-photoshop','image/psd','application/photoshop','application/psd','zz-application/zz-winassoc-psd');
			$this->mimeTypes['.bmp'] = array('image/bmp','image/x-windows-bmp');
			$this->mimeTypes['.gif'] = array('image/gif');
			$this->mimeTypes['.jpg'] = array('image/jpeg','image/pjpeg');
			$this->mimeTypes['.png'] = array('image/png');
			$this->mimeTypes['.tiff'] = array('image/tiff','image/x-tiff');
			$this->mimeTypes['.pict'] = array('image/pict');
			$this->mimeTypes['.qif'] = array('image/x-quicktime');
			$this->mimeTypes['.jps'] = array('image/x-jps');
			
			$this->mimeTypes['images'] = array_merge(
				$this->mimeTypes['.psd'],
				$this->mimeTypes['.bmp'],
				$this->mimeTypes['.gif'],
				$this->mimeTypes['.jpg'],
				$this->mimeTypes['.png'],
				$this->mimeTypes['.tiff'],
				$this->mimeTypes['.pict'],
				$this->mimeTypes['.qif']
			);
			
			// ------------ Audio ------------
			
			$this->mimeTypes['.aiff'] = array('audio/aiff','audio/x-aiff');
			$this->mimeTypes['.wav'] = array('audio/wav','audio/x-wav');
			$this->mimeTypes['.ra'] = array('audio/x-pn-realaudio','audio/x-pn-realaudio-plugin','audio/x-realaudio',);
			$this->mimeTypes['.m3u'] = array('audio/x-mpegurl');
			$this->mimeTypes['.mid'] = array('audio/mid');
			$this->mimeTypes['.au'] = array('audio/basic');
			$this->mimeTypes['.mp3'] = array('audio/mpeg','audio/x-mpeg','audio/mpeg3','audio/x-mpeg-3');
			
			$this->mimeTypes['audio'] = array_merge(
				$this->mimeTypes['.aiff'],
				$this->mimeTypes['.wav'],
				$this->mimeTypes['.ra'],
				$this->mimeTypes['.m3u'],
				$this->mimeTypes['.mid'],
				$this->mimeTypes['.mp3']
			);
			
			// ------------ Video ------------
			
			$this->mimeTypes['.avi'] = array('application/x-troff-msvideo','video/avi','video/msvideo','video/x-msvideo');
			$this->mimeTypes['.asf'] = array('video/x-ms-asf');
			$this->mimeTypes['.mov'] = array('video/quicktime');
			$this->mimeTypes['.mpeg'] = array('video/mpeg','video/x-mpeg','video/x-mpeq2a');
			$this->mimeTypes['.qt'] = array('video/quicktime');
			$this->mimeTypes['.movie'] = array('video/x-sgi-movie');
			
			$this->mimeTypes['video'] = array_merge(
				$this->mimeTypes['.asf'],
				$this->mimeTypes['.mov'],
				$this->mimeTypes['.mpeg'],
				$this->mimeTypes['.qt'],
				$this->mimeTypes['.avi'],
				$this->mimeTypes['.movie']
			);
			
			// ----------- Docs ------------
			
			$this->mimeTypes['.pdf'] = array('application/pdf');
			$this->mimeTypes['.doc'] = array('application/msword');
			$this->mimeTypes['.wpd'] = array('application/wordperfect', 'application/wordperfect6');
			$this->mimeTypes['.txt'] = array('application/plain', 'text/plain');
			
			$this->mimeTypes['documents'] = array_merge(
				$this->mimeTypes['.pdf'],
				$this->mimeTypes['.doc'],
				$this->mimeTypes['.wpd'],
				$this->mimeTypes['.txt']
			);
			
			
			// ------------ Other ------------
			
			$mimetypes = array(
				// .asp
				'text/asp',
				// .bin
				'application/mac-binary',
				'application/macbinary',
				'application/octet-stream',
				'application/x-binary',
				'application/x-macbinary',
				// .css
				'application/x-pointplus',
				'text/css',
				// .eps
				'application/postscript',
				// .htm
				'text/html',
				// .html
				'text/html',
				// .htmls
				'text/html',
				// .js
				'application/x-javascript',
				// .php
				'application/x-httpd-php',
				'text/php',
				'application/php',
				'magnus-internal/shellcgi',
				'application/x-php',
				// .ppt
				'application/mspowerpoint',
				'application/powerpoint',
				'application/vnd// .ms-powerpoint',
				'application/x-mspowerpoint',
				// .ppz
				'application/mspowerpoint',
				// .ps
				'application/postscript',
				// .psd
				'application/octet-stream',
				// .rt
				'text/richtext',
				'text/vnd// .rn-realtext',
				// .rtf
				'application/rtf',
				'application/x-rtf',
				'text/richtext',
				// .shtml
				'text/html',
				'text/x-server-parsed-html',
				// .sit
				'application/x-sit',
				'application/x-stuffit',
				// .swf
				'application/x-shockwave-flash',
				'application/x-shockwave-flash2-preview',
				'application/futuresplash',
				'image/vnd.rn-realflash',
				// .tar
				'application/x-tar',
				// .wq1
				'application/x-lotus',
				// .wri
				'application/mswrite',
				// .xl
				'application/excel',
				// .xla
				'application/excel',
				'application/x-excel',
				'application/x-msexcel',
				// .xlb
				'application/excel',
				'application/vnd',
				'application/x-excel',
				// .xlc
				'application/excel',
				'application/vnd',
				'application/x-excel',
				// .xld
				'application/excel',
				'application/x-excel',
				// .xlk
				'application/excel',
				'application/x-excel',
				// .xll
				'application/excel',
				'application/vnd',
				'application/x-excel',
				// .xlm
				'application/excel',
				'application/vnd',
				'application/x-excel',
				// .xls
				'text/xls',
				// .xls
				'application/excel',
				'application/vnd',
				'application/x-excel',
				'application/x-msexcel',
				'application/msexcel ',
				'application/x-msexcel',
				'application/x-ms-excel',
				'application/vnd.ms-excel',
				'application/x-excel',
				'application/x-dos_ms_excel',
				'application/xls',
				'application/x-xls',
				'zz-application/zz-winassoc-xls',
				// .xlt
				'application/excel',
				'application/x-excel',
				// .xlv
				'application/excel',
				'application/x-excel',
				// .xlw
				'application/excel',
				'application/vnd',
				'application/x-excel',
				'application/x-msexcel',
				// .xml
				'application/xml',
				'text/xml',
				// .z
				'application/x-compress',
				'application/x-compressed',
				// .zip
				'application/octet-stream',
				'application/x-compress',
				'application/x-compressed',
				'application/x-zip',
				'application/x-zip-compressed',
				'application/zip',
				'multipart/x-zip'
			);
			
			return array_merge(
				$mimetypes,
				$this->mimeTypes['audio'],
				$this->mimeTypes['video'],
				$this->mimeTypes['images'],
				$this->mimeTypes['documents']
			);
		}

		function get_file_icon($file) {
			$icons = array(
				"gif"=>"image.png",
				"jpg"=>"image.png",
				"jpeg"=>"image.png",
				"bmp"=>"image.png",
				"png"=>"image.png",

				"mp3"=>"audio.png",
				"mov"=>"video.png",
				"aif"=>"audio.png",
				"aiff"=>"audio.png",
				"wav"=>"audio.png",
				"swf"=>"flash.png",
				"flv"=>"video.png",
				"mpg"=>"document.png",
				"avi"=>"video.png",
				"mpeg"=>"document.png",
				"mid"=>"document.png",

				"html"=>"code.png",
				"htm"=>"code.png",
				"txt"=>"document.png",
				"css"=>"code.png",

				"php"=>"code.png",
				"php3"=>"code.png",
				"php4"=>"code.png",
				"asp"=>"code.png",
				"js"=>"code.png",

				"pdf"=>"pdf.png",
				"doc"=>"word.png",
				"docx"=>"word.png",
				"xls"=>"excel.png",
				"zip"=>"archive.png",
				"sit"=>"archive.png",
				"rar"=>"archive.png",
				"rm"=>"document.png",
				"ram"=>"document.png"
			);

			$ext = $this->get_ext($file);

			return $icons[$ext];
		}

	} // close class
?>
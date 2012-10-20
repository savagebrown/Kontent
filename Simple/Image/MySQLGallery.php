<?php

	require_once 'Gallery.php';
	require_once 'Functions/Error.php';

    class  simple_mysql_album extends simple_image_gallery {
	    /**
	     * Database Object
	     * @access private
	     * @var object
	     */
	    var $db;

		var $db_table;

		var $image_path;

		var $album_images;

		function simple_mysql_album($db, $table, $image_path = '/Images/Gallery/Albums/', $path_by_id = true) {
			$this->db = $db;
			$this->db_table = $table;
			$this->image_path = $image_path;

			$this->list = $this->set_mysql_gallery();

			parent::simple_image_gallery($this->list);

		}

		function set_mysql_gallery() {

			// Category -----------------------------------------------------
			$category_table = $this->singular($this->db_table).'_categories';

			$sql_category = <<<SQL

SELECT   id, name, description
FROM     $category_table
ORDER BY rank ASC

SQL;
			$q_category = $this->db->query($sql_category);
			if ($this->db->iserror($q_category)) { sb_error($q_category); }
			$category = array();
			while ($r_category = $q_category->fetchrow(DB_FETCHMODE_ASSOC)) {

				$category_id = $r_category['id'];
				// Albums ---------------------------------------------------
				$album_table = $this->db_table;

				$sql_albums = <<<SQL

SELECT
	id, title, short_description, description
FROM
	$album_table
WHERE
	active = 1 AND
	category_id = $category_id
ORDER BY
	rank ASC
SQL;
				$q_albums = $this->db->query($sql_albums);
				if ($this->db->iserror($q_albums)) { sb_error($q_albums); }

				if ($q_albums->numrows()>0) {
					$category[$r_category['id']] = array(
						'name' => $r_category['name'],
				        'description' => $r_category['description']
					);

					$album = array();
					while ($r_albums = $q_albums->fetchrow(DB_FETCHMODE_ASSOC)) {

						$album_id = $r_albums['id'];

						// Album Images -----------------------------------------
						$album_image_table = $this->singular($this->db_table).'_images';
						$sql_images = <<<SQL

SELECT
	id, title, caption
FROM
	$album_image_table
WHERE
	album_id = $album_id
ORDER BY
	rank ASC

SQL;
						$q_images = $this->db->query($sql_images);
						if ($this->db->iserror($q_images)) { sb_error($q_images); }

						$first_image = '';
						while ($r_images = $q_images->fetchrow(DB_FETCHMODE_ASSOC)) {

							if(!$first_image) {
			                    $first_image = $this->image_path.$r_albums['id'].'/l/'.$r_albums['id'].'_'.$r_images['id'].'.jpg';
			                }

			                $this->album_images[$r_albums['id']]['images'][$r_images['id']] = array('image'=>$this->image_path.$r_albums['id'].'/ms/'.$r_albums['id'].'_'.$r_images['id'].'.jpg',
																									'caption'=>$r_images['caption']);

						} // end while album images


						// Add albums to gallery array --------------------------

						$category[$category_id]['images'][$r_albums['id']] = array(
							'img_title' => $r_albums['title'],
	                        'img_caption' => $r_albums['short_description'],
	                        'img_description' => $r_albums['description'],
	                        'img_file' => $first_image,
	                        'img_file_thumb' => str_replace('/l/','/ms/',$first_image)
						);


					} // end while albums


				}



			} // end while category

			return $category;

		}

		function get_simple_gallery() {
		
		
			$album_thumbnails = '';
			$count=0;
			foreach( $this->album_images[$this->get_focus_id()]['images'] as $k => $v) {


				if ($count>5) {
					break;
				} else {
					$count++;
				}
				$image = $v['image'];
				$target_image = str_replace('/ms/','/l/',$image);
				$clean_out = array("\n","\r","'");
				$caption = ($v['caption'])?'<p>'.str_replace($clean_out," ", trim($v['caption'])).'</p>':'';
				if (!$initial_caption){
					$initial_caption = ($v['caption'])?$caption:'<!-- No Caption -->';
				}

				$album_thumbnails .= <<<HTML

	<li><a href="#_self" onclick="LoadGallery('AlbumFocus','$target_image','AlbumFocusCaption','$caption')"><img src="$image" height="75" width="75"/></a></li>

HTML;
				$preload_thumbnails .= "'".$target_image."',\n";
			}
				
			if (count($this->album_images[$this->get_focus_id()]['images'])>1) {

				$album_thumbnails = "\n<ul class=\"thumbs\">".$album_thumbnails."</ul>\n";
				
				$preload_javascript = <<<HTML

<script language="JavaScript">
// Gallery script.
// With image cross fade effect for those browsers that support it.
// Script copyright (C) 2004 www.cryer.co.uk.
// Script is free to use provided this copyright header is included.
function LoadGallery(pictureName,imageFile,titleCaption,captionText)
{
	if (document.all)
	{
		document.getElementById(pictureName).style.filter="blendTrans(duration=1)";
		document.getElementById(pictureName).filters.blendTrans.Apply();
	}
		document.getElementById(pictureName).src = imageFile;
	if (document.all)
	{
		document.getElementById(pictureName).filters.blendTrans.Play();
	}
	document.getElementById(titleCaption).innerHTML=captionText;
}
</script>


<SCRIPT language="JavaScript">
<!--

var preloaded = new Array();
function preload_images() {
    for (var i = 0; i < arguments.length; i++){
        preloaded[i] = document.createElement('img');
        preloaded[i].setAttribute('src',arguments[i]);
    };
};
preload_images(
$preload_thumbnails

);

//-->
</SCRIPT>

HTML;
			
			} else {
				$album_thumbnails = '';
				$javascript = '';
			}
	
			// Album Navigation ---------------------------------------------
			
			if ($this->get_next_thumb() != $this->get_first_thumb()) {
				$next_thumb = '?c='.$this->get_group_id().'&i='.$this->get_next_thumb();
			} else {
				$next_thumb = '?c='.$this->get_next_group();
			}

			if ($this->get_previous_thumb() != $this->get_last_thumb()) {
				$prev_thumb = '?c='.$this->get_group_id().'&i='.$this->get_previous_thumb();
			} else {
				$prev_thumb = '?c='.$this->get_previous_group();
			}
		
			$album_navigation = <<<HTML
	
			<a class="btn" href="$prev_thumb">prev</a>&nbsp;&nbsp;
			<a class="btn" href="$next_thumb">next</a>&nbsp;&nbsp;

HTML;
			// Album Image --------------------------------------------------
			
			$album_image = '<img id="AlbumFocus" width="300" height="300" src="'.$this->get_focus_image().'" />';
		
			// Compile ------------------------------------------------------
		
			// Category
			$gallery['categories']              = $this->get_groups();
			$gallery['category_tree']			= $this->get_groups('FOCUS_TREE');
			$gallery['category_id']             = $this->get_group_id();
			$gallery['category_name']           = $this->get_group_name();
			$gallery['category_position']		= $this->get_group_position();
			// Album             
			$gallery['album_count']             = $this->get_thumbs_count(); 
			$gallery['album_position']		    = $this->get_focus_position();              
			$gallery['album_title']             = $this->get_focus_title();
			$gallery['album_id']                = $this->get_focus_id();
			$gallery['album_short_description'] = $this->get_focus_caption();
			$gallery['album_description']       = $this->get_focus_description();
			// Images                           
			$gallery['album_image']             = $album_image;
			$gallery['album_image_caption']     = $initial_caption;
			$gallery['album_navigation']        = $album_navigation;
			$gallery['album_thumbnails']        = $album_thumbnails;
			// Javascript
			$gallery['javascript'] = $preload_javascript;
			
			return $gallery;
		}

		function singular($str) {
			$str = strtolower(trim($str));
			$end = substr($str, -3);

			if ($end == 'ies')
			{
				$str = substr($str, 0, strlen($str)-3).'y';
			}
			else
			{
				$end = substr($str, -1);

				if ($end == 's')
				{
					$str = substr($str, 0, strlen($str)-1);
				}
			}

			return $str;
		}


























		function display_test() {

			if (count($this->album_images[$this->get_focus_id()]['images'])>1) {
				$album_thumbnails = '';
				$count=0;
				foreach( $this->album_images[$this->get_focus_id()]['images'] as $k => $v) {
				
				
					if ($count>5) {
						break;
					} else {
						$count++;
					}
					$image = $v['image'];
					$target_image = str_replace('/ms/','/l/',$image);
					$caption = ($v['caption'])?'<p>'.str_replace("\r", "", str_replace("\n"," ", trim($v['caption']))).'</p>':'';
					if (!$initial_caption){
						$initial_caption = ($v['caption'])?$caption:'<!-- No Caption -->';
					}

					$album_thumbnails .= <<<HTML

	<li><a href="#_self" onclick="LoadGallery('AlbumFocus','$target_image','AlbumFocusCaption','$caption')"><img style="margin:0 4px 4px 0;" src="$image" height="75" width="75"/></a></li>

HTML;
					$preload_thumbnails .= "'".$image."',\n";
				}
				
				$album_thumbnails = "\n<ul class=\"thumbs\">".$album_thumbnails."</ul>\n";
			}


			$start = <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>untitled</title>

	<script language="JavaScript">
	// Gallery script.
	// With image cross fade effect for those browsers that support it.
	// Script copyright (C) 2004 www.cryer.co.uk.
	// Script is free to use provided this copyright header is included.
	function LoadGallery(pictureName,imageFile,titleCaption,captionText)
	{
	  if (document.all)
	  {
	    document.getElementById(pictureName).style.filter="blendTrans(duration=1)";
	    document.getElementById(pictureName).filters.blendTrans.Apply();
	  }
	  document.getElementById(pictureName).src = imageFile;
	  if (document.all)
	  {
	    document.getElementById(pictureName).filters.blendTrans.Play();
	  }
	  document.getElementById(titleCaption).innerHTML=captionText;
	}
	</script>

	<SCRIPT language="JavaScript">
	<!--

	var preloaded = new Array();
	function preload_images() {
	    for (var i = 0; i < arguments.length; i++){
	        preloaded[i] = document.createElement('img');
	        preloaded[i].setAttribute('src',arguments[i]);
	    };
	};
	preload_images(
	$preload_thumbnails

	);

	//-->
	</SCRIPT>

	<style type="text/css" media="screen">
		.highlight {
			background:green;
			padding: 5px;
		}
		.highlight a {
			text-decoration:none;
			color:#fff;
		}
		.caption p {
			padding: 10px;
			background: #ffc;
		}
		.thumbs, .thumbs li {
			list-style:none;
			margin:0;
			padding:0;
			float:left;
			
		}
		.thumbs li a {
			margin:0 5px 5px 0;
			display: block;
			float: left;
		}
		.thumbs li a img {
			padding:4px;
			border:1px solid #ccc;
		}
	</style>

</head>
<body>





HTML;


			$end = <<<HTML





</body>
</html>

HTML;
			print $start;
			print '<table border="1" cellpadding="5" cellspacing="5">';
			print '<tr><td style="color:green;font-weight:bolder;">get_groups()           </td><td>'.'<pre>'.$this->get_groups()           .'</pre></td></tr>';

		/* No need for group nave other than list
		    print '<tr><td style="color:green;font-weight:bolder;">Category Navigation</td>
				 <td>
					<ul class="browser">
                       <li class="first"><a href="?c='.$this->get_first_group()    .'">First Group    ('.$this->get_first_group()   .')</a></li>
                       <li class="prev"> <a href="?c='.$this->get_previous_group() .'">Previous Group ('.$this->get_previous_group().')</a></li>
                       <li class="next"> <a href="?c='.$this->get_next_group()     .'">Next Group     ('.$this->get_next_group()    .')</a></li>
                       <li class="last"> <a href="?c='.$this->get_last_group()     .'">Last Group     ('.$this->get_last_group()    .')</a></li>
             		</ul>
				</td></tr>';
		*/

			if ($this->get_next_thumb() != $this->get_first_thumb()) {
				$next_thumb = '?c='.$this->get_group_id().'&i='.$this->get_next_thumb();
			} else {
				$next_thumb = '?c='.$this->get_next_group();
			}

			if ($this->get_previous_thumb() != $this->get_last_thumb()) {
				$prev_thumb = '?c='.$this->get_group_id().'&i='.$this->get_previous_thumb();
			} else {
				$prev_thumb = '?c='.$this->get_previous_group();
			}


		    print '<tr><td style="color:green;font-weight:bolder;">Album Navigation</td>
				 <td>
					<ul class="browser">
                    <li class="first"><a href="?c='.$this->get_group_id().'&i='.$this->get_first_thumb().'">   First Album    </a>('.$this->get_first_thumb().')</li>
                    <li class="prev"> <a href="'.$prev_thumb.'">Previous Album </a>('.$this->get_previous_thumb().')</li>
                    <li class="next"> <a href="'.$next_thumb.'">    Next Album     </a>('.$this->get_next_thumb().')</li>
                    <li class="last"> <a href="?c='.$this->get_group_id().'&i='.$this->get_last_thumb().'">    Last Album     </a>('.$this->get_last_thumb().')</li>
             		</ul>
				</td></tr>';

			print '<tr><td colspan="2">';
			print $album_thumbnails;
			print '</td></tr>';

		    print '<tr><td style="color:green;font-weight:bolder;">group ID - group Name        </td><td>'.'<pre>'.$this->get_group_id().' - '.$this->get_group_name().'</pre></td></tr>';
		    print '<tr><td style="color:green;font-weight:bolder;">get_focus_id()         </td><td>'.'<pre>'.$this->get_focus_id()         .'</pre></td></tr>';
		    print '<tr><td style="color:green;font-weight:bolder;"><span class="caption" id="AlbumFocusCaption">'.$initial_caption.'</span></td><td>'.'<img id="AlbumFocus" width="300" height="300" src="'.$this->get_focus_image().'" /></td></tr>';
		    print '<tr><td style="color:green;font-weight:bolder;">Album Name      </td><td>'.'<pre>'.$this->get_focus_title()      .'</pre></td></tr>';
		    print '<tr><td style="color:green;font-weight:bolder;">Album Short Description    </td><td>'.'<p>'.$this->get_focus_caption()    .'</p></td></tr>';

		    print '<tr><td style="color:green;font-weight:bolder;">Album Description    </td><td>'.'<pre>'.$this->get_focus_description()    .'</pre></td></tr>';
		    print '<tr><td style="color:green;font-weight:bolder;">get_thumbs()           </td><td>'.'<pre>'.$this->get_thumbs()           .'</pre></td></tr>';

			print '</table';

			print $end;

		}







	}

?>
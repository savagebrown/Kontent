<?php
	require_once 'Includes/Configuration.php';

	$asset_root = ($asset_root)?$asset_root:$g['global']['root_images'];

	if (isset($_GET['file']) && $_GET['file']!='') {
		$file = $_GET['file'];
	} else {
		header ("Location: files.php");
		exit;
	}
	$file_ext = get_ext($file);

	$image_exts = array('jpg','jpeg','gif','png','tiff');

	// Images -----------------------------------------------------------------
	if (in_array($file_ext, $image_exts)) {
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$preview = '<div class="embedd"><img src="'.$asset_root.'/Assets/'.$file.'" height="200" /></div>';
		$markuplink_text  = '<img src="'.$g['page']['icons']['files'].'/image.png" align="absmiddle"/>&nbsp;&nbsp;';
		$markuplink_text = 'highlight and copy <strong class="highlight">image markup</strong> to display in your text';
	// PDFs -------------------------------------------------------------------
	} else if ($file_ext == 'pdf') {
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$markuplink_text  = '<img src="'.$g['page']['icons']['files'].'/pdf.png" align="absmiddle"/>&nbsp;&nbsp;';
		$markuplink_text .= 'highlight and copy <strong class="highlight">pdf document link markup</strong> to use in your text';
	// Word Documents ---------------------------------------------------------
	} else if ($file_ext == 'doc' || $file_ext == 'docx') {
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$markuplink_text  = '<img src="'.$g['page']['icons']['files'].'/word.png" align="absmiddle"/>&nbsp;&nbsp;';
		$markuplink_text .= 'highlight and copy <strong class="highlight">word document link markup</strong> to use in your text';
	// Excel Documents --------------------------------------------------------
	} else if ($file_ext == 'xls') {
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$markuplink_text  = '<img src="'.$g['page']['icons']['files'].'/excel.png" align="absmiddle"/>&nbsp;&nbsp;';
		$markuplink_text .= 'highlight and copy <strong class="highlight">excel document link markup</strong> to use in your text';
	// Audio ------------------------------------------------------------------
	} else if ($file_ext == 'mp3') {
		$markuplink_text = 'highlight and copy <strong class="highlight">mp3 link markup</strong> to use in your text';
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$preview = <<<HTML

<div class="embedd">
	<object type="application/x-shockwave-flash" width="100%" height="15"
	data="{$g['global']['root']}/Assets/Players/MP3/xspf_player_slim.swf?song_url={$g['global']['root']}/Assets/$file&autoplay=true&song_title=$file">
	<param name="movie"
	value="{$g['global']['root']}/Assets/Players/MP3/xspf_player_slim.swf?song_url={$g['global']['root']}/Assets/$file&autoplay=true&song_title=$file" />
	</object>
</div>

HTML;

	} else if ($file_ext == 'mov' || $file_ext == 'aif') {
		$markuplink_text = 'highlight and copy <strong class="highlight">video link markup</strong> to use in your text';
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$preview = <<<HTML

<div class="embedd">
	<object classid="clsid:02bf25d5-8c17-4b23-bc80-d3488abddc6b"
	codebase="http://www.apple.com/qtactivex/qtplugin.cab">

	<param name="src" value="{$g['global']['root']}/Assets/$file">
	<param name="autoplay" value="true">
	<param name="controller" value="true">

	<embed src="{$g['global']['root']}/Assets/$file"
	autoplay="true" controller="true"
	pluginspage="http://www.apple.com/quicktime/download/">
	</embed>

	</object>
</div>

HTML;

	} else if ($file_ext == 'flv') {
		$markuplink_text = 'highlight and copy <strong class="highlight">video link markup</strong> to use in your text';
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		// TODO: Find flv player that works in lightbox
		$preview = <<<HTML

<div class="embedd">

	<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="160" height="135" id="160x135" align="middle">
		<param name="Flashvars" value="url={$g['global']['root']}/Assets/$file" />
		<param name="allowScriptAccess" value="sameDomain" />
		<param name="movie" value="{$g['global']['root']}/Assets/Players/FLV/SB/160x135.swf" />
		<param name="quality" value="high" />
		<param name="bgcolor" value="#000" />
		<embed src="{$g['global']['root']}/Assets/Players/FLV/SB/160x135.swf" swliveconnect="true" flashvars="url={$g['global']['root']}/Assets/$file" quality="high" bgcolor="#000" width="160" height="135" name="160x135" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></embed>
	</object>

</div>

HTML;
	} else {
		$markuplink = $g['global']['root'].'/Assets/'.$file;
		$markuplink_text = 'highlight and copy <strong class="highlight">link markup</strong> to use in your text';
	}

	echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>File Details</title>
</head>
<body>
	
<div id="image-edit-popup-container">
	$preview
	<div class="markup-instructions">
		$markuplink_text
	</div>
	<div class="markup-copypaste">
		$markuplink
	</div>
</div>

</body>
</html>
HTML;
?>
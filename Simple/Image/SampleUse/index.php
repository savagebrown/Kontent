<?php

	ini_set("include_path", '/Users/sb/Sites/Library^2_5'     . PATH_SEPARATOR . ini_get("include_path"));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Simple Image Newsize Sample Use</title>
	<style type="text/css" title="text/css">
	/* <![CDATA[ */
		body {
			background:#eee;
			text-align: center;
		}
		h1 {
			clear: both;
			font-size:large;
			color: #cc3300;;
			font: small 'Lucida Grande', LucidaGrande, Lucida, Helvetica, Arial, sans-serif;
			text-transform: uppercase;
			letter-spacing: 5px;
		}
		#container {
			margin: 0 auto;
			width:667px;
			text-align:left;
		}
		.image_box {
			float:left;
			background: white;
			padding:10px;
			margin: 0 5px 5px 0;
			border:1px solid #ccc;
			border-right:1px solid gray;
			border-bottom:1px solid gray;
		}
		em {
			font-size:smaller;
		}
	/* ]]> */
	</style>
</head>
<body>
<div id="container">

<h1>Simple Image Resize</h1>
<?php

	// Class test
	require_once 'Simple/Image/NewSize.php';
	
	
	for ($i = 1; $i <= 8; $i++) {
	
		print '<div class="image_box">';
	
		$img = 'Images/Originals/'.$i.'.jpg'; // image to be resized
		$rename = 'newimage_'.$i.'.jpg'; // image name
		$saveto = 'Images/Thumbs/'; // destination
		$max_w = 100; // maximum width
		$max_h = 100; // maximum height
		$crop = true; // Will use max_w & max_h to crop off excess
		$bydate = false; // Saves images in date folders (ie. Images/Thumbs/2006/02/05/thmb_image.jpg)
	
		$myimg = array($img, $rename, $saveto, $max_w, $max_h, $crop, $bydate);
	
		$thmb = new simple_image_newsize($myimg);
	
		if ($thmb) {
			if (!$bydate) {
				print '<img src="'.$saveto.$rename.'" />';
			} else {
				print '<img src="'.$saveto."/".date('Y')."/".date('m')."/".date('d')."/".$rename.'" />';
			}
		} else {
			print 'No Image was saved!!<hr />';
		}
		print '</div>';
	}

?>
<h1>Simple Image Frame <em>(extends simple image resize)</em></h1>
<div class="image_box">
<?php

	// Class test
	require_once 'Simple/Image/Frame.php';
		
	for ($i = 1; $i <= 8; $i++) {
		
		$img = 'Images/Originals/'.$i.'.jpg'; // image to be resized
		$frame = 'Images/Frames/'.$i.'.png'; // image to be resized
		$window_w = 112; // frame window width
		$window_h = 90; // frame window height
		$pad_x = 8; // maximum width
		$pad_y = 8; // maximum height
		$rename = 'newimage_'.$i.'.jpg'; // image name
		$saveto = 'Images/Themed/'; // destination
		$degree_tilt = null;
	
		$myimg = array($img, $frame, $window_w, $window_h, $pad_x, $pad_y, $rename, $saveto, $degree_tilt);
	
		$thmb = new simple_image_frame($myimg);
	
		print '<img src="'.$saveto.$rename.'" />';
		
	}

?>
</div>
<h1>Simple Image Watermark</h1>
<div class="image_box">
<?php
	// Class test
	require_once 'Simple/Image/Watermark.php';
	
    $img = 'Images/Originals/3.jpg'; // image to be resized
	$watermark_img = 'Images/Watermarks/rhino.png'; // image to be resized
	$position = 7; // 0. Top left 1. Top right 2. Bottom Left 3. Bottom right
    $padding = 10; // Padding from edge
    $rename = 'newimage_watermark.jpg'; // save as image name
    $saveto = 'Images/Watermarked/'; // save to destination

    $myimg = array($img, $watermark_img, $position, $padding, $rename, $saveto);

    $thmb = new simple_image_watermark($myimg);
    
    if ($thmb) {
		print '<img src="'.$saveto.$rename.'" />';
	} else {
		print 'Something went wrong - terribly wrong';
	}

?>


</div>
</div>
<p style="clear:both">&nbsp;</p>
</body>
</html>

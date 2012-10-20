<?php
	ini_set("include_path", '/Users/sb/Sites/Library^2_5'     . PATH_SEPARATOR . ini_get("include_path"));

    // include class
    require_once 'Simple/Image/Gallery.php';
    
    $opendir = @opendir('GalleryImages/RoadTrip');
    while ($readdir = @readdir($opendir)) {
        if ($readdir <> "." && $readdir <> ".." && $readdir <> ".DS_Store"
                            && !is_dir('Images/RoadTrip/'.$readdir)) {
            $filearr[] = $readdir;
        }
        $sort=array();
        for($i=1;$i<=count($filearr);$i++) {
            $key = sizeof($filearr)-$i;
            $file = $filearr[$key];
            $sort[$i]=$file;
        }
        asort($sort);
    }
    
    // Take the contents of the dir and add them to the group
    foreach($sort AS $k => $v) {
	   $group_images[$k] = array('img_title' => '',
                                 'img_caption' => '',
                                 'img_file' => 'GalleryImages/RoadTrip/'.$v);
	}
	$list[1] = array('name' => 'RoadTrip Gallery',
					 'description' => 'Roadtrip across america with Jason and Emrah.',
					 'images' => $group_images);
	
    // Instantiate SavageGallery Class
    $sg = new simple_image_gallery($list);

    // Thumb Navigation
    $sg_first_thumb = $sg->get_first_thumb();
    $sg_prev_thumb  = $sg->get_previous_thumb();
    $sg_next_thumb  = $sg->get_next_thumb();
    $sg_last_thumb  = $sg->get_last_thumb();
    // Current Group paramaters
    $sg_group_id = $sg->get_group_id();
    $sg_group_name = $sg->get_group_name();
    $sg_group_description = $sg->get_group_description();
    $sg_group_image = $sg->get_group_image();
    // Current Focus paramaters
    $sg_focus_id = $sg->get_focus_id();
    $sg_focus_image = $sg->get_focus_image();
    // Lists
    $sg_thumbs_list = $sg->get_thumbs();


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
        <title>Simply Savage Gallery: Pinpoint Class Elements</title>
        <link rel="stylesheet" rev="stylesheet" href="css/albums.css" media="screen" />
    </head>
    <body>
        <div id="container">
        <!--
            <div id="header">
                <h1>Simply Savage Gallery: <em>Album Example</em></h1>
                <p>A gallery class built for designers. No 
                constrictions and most importantly no pre-designed layouts. You 
                are in control. Take the elements you need and place them where 
                ever you want in your designs. Browse this section of the site 
                for helpful hints and examples. Please 
                <a href="contact.php">let me know</a> what you think.
                </p>
            </div>
          -->  
            
            
            <ul class="browser">
                <li class="first"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_first_thumb; ?>">First View</a></li>
                <li class="prev"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_prev_thumb; ?>">Previous View</a></li>
                <li class="next"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_next_thumb; ?>">Next View</a></li>
                <li class="last"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_last_thumb; ?>">Last View</a></li>
            </ul><h2><?php echo $sg_group_name; ?></h2>
            <div style="clear:left;"></div><h2>
            <div class="box">
                <p class="image">
                    <img src="<?php echo $sg_focus_image; ?>" alt="<?php echo $sg_focus_title; ?>" />
                </p>
                <p class="caption"><em></em></p>
            </div>
                        
        </div>

    </body>
</html>
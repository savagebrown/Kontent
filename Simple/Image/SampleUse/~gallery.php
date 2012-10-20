<?php
	ini_set("include_path", '/Users/sb/Sites/Library^2_5'     . PATH_SEPARATOR . ini_get("include_path"));

    // include class
    require_once 'Simple/Image/Gallery.php';

	// Setup of an array ------------------------------------------------------

    $category = "Postcards";
    $group_image1[11] = array('img_title' => 'Any town USA',
                              'img_caption' => 'Found in Stroudsburg, PA',
                              'img_file' => 'GalleryImages/PinPoint/misc01.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/misc01.jpg');
    $group_image1[25] = array('img_title' => 'Sofia',
                              'img_caption' => 'I love this building. Found this in a ditch where I was hiding from Bulgarians.',
                              'img_file' => 'GalleryImages/PinPoint/misc02.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/misc02.jpg');
    $group_image1[37] = array('img_title' => 'U2',
                              'img_caption' => 'Some say it is at this point that they sold out. I don\'t know, you tell me. Found downtown Adana, Turkey.',
                              'img_file' => 'GalleryImages/PinPoint/misc03.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/misc03.jpg');
    $group_image1[35] = array('img_title' => 'Port NY',
                              'img_caption' => 'Founf Port NY in Port Oregon, creepy right?',
                              'img_file' => 'GalleryImages/PinPoint/misc04.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/misc04.jpg');
    $group_image1[89] = array('img_title' => 'My Standard',
                              'img_caption' => 'Traded my guitar for this postcard at the Mine&Yours Pawn shop in Erie, PA.',
                              'img_file' => 'GalleryImages/PinPoint/misc05.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/misc05.jpg');
    $list[8] = array('name' => 'Miscellaneous',
                     'description' => 'Postcards I found out and about. They did not fit into any particular category.',
                     'images' => $group_image1);

	// Setup of an array ------------------------------------------------------

    $group_image2[17] = array('img_title' => 'Nails',
                              'img_caption' => 'Why?Really why?',
                              'img_file' => 'GalleryImages/PinPoint/odd01.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/odd01.jpg');
    $group_image2[77] = array('img_title' => 'Accordion',
                              'img_caption' => 'This is the most disturbing of all. I mean, look at that smile. Just plain scary. My cousin Earl, by the way',
                              'img_file' => 'GalleryImages/PinPoint/odd02.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/odd02.jpg');
    $group_image2[21] = array('img_title' => 'Unicorn x-ray',
                              'img_caption' => 'This is a real X-ray. Yes, they can take one while unicorns are running but is that reall what you should be focusing on?',
                              'img_file' => 'GalleryImages/PinPoint/odd03.jpg',
                              'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/odd03.jpg');
    $list[12] = array('name' => 'Oddities',
                      'description' => 'I find this selection of postcards to be disturbing.',
                      'images' => $group_image2);

	// Setup of an array ------------------------------------------------------

    $group_image3[417] = array('img_title' => 'Pinup Number One',
                               'img_caption' => 'Croquet is a fine sport.',
                               'img_file' => 'GalleryImages/PinPoint/pinup01.jpg',
							   'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/pinup01.jpg');
    $group_image3[477] = array('img_title' => 'Pinup Number Two',
							   'img_caption' => 'Swinging aimlessly is also a fine sport.',
							   'img_file' => 'GalleryImages/PinPoint/pinup02.jpg',
							   'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/pinup02.jpg');
    $group_image3[421] = array('img_title' => 'Pinup Number Three',
							   'img_caption' => 'I think it bares repeating - Crochet is a fine sport.',
							   'img_file' => 'GalleryImages/PinPoint/pinup03.jpg',
							   'img_file_thumb' => 'GalleryImages/PinPoint/Thumbs/pinup03.jpg');
    $list[56] = array('name' => 'Pinups',
                     'description' => 'A pin-up girl is a woman whose physical
                     attractiveness would entice one to place a picture of her
                     on a wall. The term was first attested to in English in
                     1941; however the practice is documented back at least to
                     the 1890s. The Òpin upÓ images could be cut out of
                     magazines or newspapers, or be from postcard or
                     chromo-lithographs, and so on. Such photos often appear on
                     calendars, which are meant to be pinned up anyway. Later,
                     posters of Òpin-up girlsÓ were mass produced.',
                     'images' => $group_image3);

    // Instantiate SavageGallery Class
    $sg = new simple_image_gallery($list);

    // Available paramaters

    // Group Navigation
    $sg_first_group = $sg->get_first_group();
    $sg_prev_group  = $sg->get_previous_group();
    $sg_next_group  = $sg->get_next_group();
    $sg_last_group  = $sg->get_last_group();
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
    $sg_focus_title = $sg->get_focus_title();
    $sg_focus_caption = wordwrap($sg->get_focus_caption(), 50, "<br />");
    // Lists
    $sg_group_list = $sg->get_groups();
    $sg_thumbs_list = $sg->get_thumbs();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
        <title>Simply Savage Gallery: Pinpoint Class Elements</title>
        <link rel="stylesheet" rev="stylesheet" href="css/default.css" media="screen" />
    </head>
    <body>
        <div id="container">
            <div id="header">
                <h1>Simple Image Gallery: <em>Sample Use</em></h1>
                <p>A gallery class built for designers. No
                constrictions and most importantly no pre-designed layouts. You
                are in control. Take the elements you need and place them where
                ever you want in your designs. Browse this section of the site
                for helpful hints and examples. Please
                <a href="contact.php">let me know</a> what you think.
                </p>
            </div>
            <div id="left_sidebar">
                    <p class="blurb"><em>Group Navigation</em></p>
                    <ul class="browser">
                        <li class="first"><a href="?c=<?php echo $sg_first_group; ?>">First Group</a></li>
                        <li class="prev"><a href="?c=<?php echo $sg_prev_group; ?>">Previous Group</a></li>
                        <li class="next"><a href="?c=<?php echo $sg_next_group; ?>">Next Group</a></li>
                        <li class="last"><a href="?c=<?php echo $sg_last_group; ?>">Last Group</a></li>
                    </ul>
                    <p class="blurb"><em>Group listing + count (<?php echo $sg->get_groups_count(); ?>)</em></p>
                    <?php echo $sg_group_list; ?>
                    <p class="blurb">
                        <em class="instruction">
                            I do not accept
                            donations but If you find this PHP Class useful please
                            consider <a href="#">buying a shirt</a>.
                        </em>
                    </p>
                    <div class="box">
                        <p class="image">Download</p>
                        <p class="caption">
                            <em><a href="savagegallery.zip">SSG.zip.tar</a></em>
                        </p>
                    </div>
            </div>
            <div id="content">
                    <p class="blurb"><em>Group name</em></p>
                    <h2><?php echo $sg_group_name; ?></h2>
                    <p class="blurb"><em>Group description</em></p>
                    <p id="group_description"><?php echo $sg_group_description; ?></p>
                    <p class="blurb"><em>Group item thumbnails + count (<?php echo $sg->get_thumbs_count(); ?>)</em></p>
                    <?php echo $sg_thumbs_list; ?>
            </div>
            <div id="right_sidebar">
                    <p class="blurb"><em>Focus Navigation</em></p>
                    <ul class="browser">
                        <li class="first"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_first_thumb; ?>">First View</a></li>
                        <li class="prev"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_prev_thumb; ?>">Previous View</a></li>
                        <li class="next"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_next_thumb; ?>">Next View</a></li>
                        <li class="last"><a href="?c=<?php echo $sg_group_id; ?>&i=<?php echo $sg_last_thumb; ?>">Last View</a></li>
                    </ul>
                    <p  class="blurb"><em>Focus Information</em></p>
                    <div class="box">
                        <p class="image">
                            <img src="<?php echo $sg_focus_image; ?>" alt="<?php echo $sg_focus_title; ?>" />
                        </p>
                        <p class="caption">
                            <em><strong><?php echo $sg_focus_title; ?></strong><br /><?php echo $sg_focus_caption; ?></em>
                        </p>
                    </div>
            </div>
            <div id="footer"><p>This is the footer where I will place copy</p></div>
        </div>
    </body>
</html>
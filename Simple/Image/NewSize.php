<?php

	// TODO: Open up option for image quality (currently 100%)

    /**
     * Simple Image Newsize
     *
     * Resizes and/or crops image
     *
     * @package      Simple
     * @author       Koray Girton <koray@savagebrown.com>
     * @version      1.0.1
     * @category     Image
     * @copyright    Copyright (c) 2005 SavageBrown.com
     * @license      http://opensource.org/licenses/lgpl-license.php 
     *               GNU Lesser General Public License
     * @filesource
     */
    Class simple_image_newsize {
        /**
         * Path to image file which is to be resized
         * @access private
         * @var string
         */
        var $img;
        /**
         * Name that created image should be saved as
         * @access private
         * @var string
         */
        var $img_savename;
        /**
         * Path to where resized image should be saved to
         * @access private
         * @var string
         */
        var $img_saveto;
        /**
         * Maximum image width
         * @access private
         * @var integer 
         */
        var $img_max_w;
        /**
         * Maximum image height
         * @access private
         * @var integer 
         */
        var $img_max_h;
        /**
         * Determines if image is to be cropped to $img)max_w and $img_max_h
         * paramaters. Cropped if set to true.
         * @access private
         * @var boolean
         */
        var $crop = false;
        
        /**
         * Image width
         * @access private
         * @var integer 
         */
        var $img_w;
        /**
         * Image height
         * @access private
         * @var integer 
         */
        var $img_h;
        /**
         * Resize ratio
         * @access private
         * @var integer 
         */
        var $img_ratio;
        /**
         * New image instance
         * @access private
         * @var object
         */
        var $new_img;
        /**
         * New image width
         * @access private
         * @var integer 
         */
        var $new_img_w;
        /**
         * New image height
         * @access private
         * @var integer 
         */
        var $new_img_h;
        /**
         * Copy image instance
         * @access private
         * @var object
         */
        var $copy_img;
        /**
		 * Cleaned version of $img_saveto paramater
         * @access private
         * @var string
         */
        var $img_path;
        /**
         * Width to reveal from original image when cropped
         * @access private
         * @var integer 
         */
        var $reveal_width;
        /**
         * Height to reveal from original image when cropped
         * @access private
         * @var integer 
         */
        var $reveal_height;
        /**
         * Left padding (x) to position image when cropping
         * @access private
         * @var integer 
         */
        var $pad_x;
        /**
         * Top padding (y) to position image when padding
         * @access private
         * @var integer 
         */
        var $pad_y;

        /**
         * Constructor method applies provided settings to paramaters and runs
         * action methods
         *
         * @uses save_resized_image()
         * @retun boolean
         */
        function simple_image_newsize($image_options) {

            if (is_array($image_options)) {

                list($this->img,
                     $this->img_savename,
                     $this->img_saveto,
                     $this->img_max_w,
                     $this->img_max_h, 
                     $this->crop) = $image_options;

                if ($this->save_resized_image()) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * This method will crop (if set to true) and create an instance of the 
         * resized image
         *
         * @uses set_image_info()
         * @access private
         * @return boolean
         */
        function resize() {

            $this->set_image_info();
            
            if (($this->crop == true) && ($this->img_max_w != 0) && ($this->img_max_h != 0)) {

                // Create New image
                $this->new_img = imagecreatetruecolor($this->img_max_w, $this->img_max_h) or die('Could not create new image instance');
                $this->copy_img = imagecreatefromjpeg($this->img);
                imagecopyresampled($this->new_img, $this->copy_img, 0, 0, $this->pad_x, $this->pad_y, $this->img_max_w, $this->img_max_h, $this->reveal_width, $this->reveal_height);

            } else {

                // Create New image
                $this->new_img = imagecreatetruecolor($this->new_img_w, $this->new_img_h) or die('Could not create new image instance');
                $this->copy_img = imagecreatefromjpeg($this->img);
                imagecopyresampled($this->new_img, $this->copy_img, 0, 0, 0, 0, $this->new_img_w, $this->new_img_h, $this->img_w, $this->img_h);
            }

        }
        
        /**
         * This method runs the resize() method and saves the resulting resized
         * image to specified destination as specified name.
         *
         * @uses resize()
         * @uses create_path()
         * @access private
         * @return boolean
         */
        function save_resized_image() {

            $this->resize();
            $this->create_path();

            // Save image
            if(ImageJPEG($this->new_img, $this->img_path.$this->img_savename, 100)) {
                // Clean up
                imagedestroy($this->new_img); imagedestroy($this->copy_img);

                return true;
            } else {
                // Clean up
                imagedestroy($this->new_img); imagedestroy($this->copy_img);

                return false;
            }

        }

        /**
         * Sets member variables with image info
         * @access private
         * @return void
         */
        function set_image_info() {

            // Get image width & image height
            list($this->img_w, $this->img_h) = getimagesize($this->img);

            // Use original name if savename is not provided
            if (!$this->img_savename) {
                $this->img_savename = basename($this->img);
            }

            if ($this->crop && ($this->img_max_w != 0 || $this->img_max_h !=0)) {

                $this->pad_x = 0;
                $this->pad_y = 0;

                if ($this->img_w >= $this->img_h) {
                    // orentation is horizontal do something
                    $set_ratio = $this->img_max_h / $this->img_max_w;
                    $img_ratio = $this->img_h / $this->img_w;

                    if ($set_ratio <= $img_ratio) {
                        // image ratio is greater use longer side
                        $ratio = $this->img_max_w / $this->img_w;
                        // Use aquired ratio to constrain proportions to new size
                        $this->new_img_w = intval($this->img_w * $ratio);
                        $this->new_img_h = intval($this->img_h * $ratio);
                        $this->reveal_width = $this->img_w;
                        $this->reveal_height = intval(((($this->img_max_h * 100) / $this->new_img_h) / 100) * $this->img_h);
                        $this->pad_y = ($this->img_h - $this->reveal_height) / 2;
                    } else {
                        // image ratio is less use shorter side
                        $ratio = $this->img_max_h / $this->img_h;
                        // Use aquired ratio to constrain proportions to new size
                        $this->new_img_w = intval($this->img_w * $ratio);
                        $this->new_img_h = intval($this->img_h * $ratio);
                        $this->reveal_height = $this->img_h;
                        $this->reveal_width = intval(((($this->img_max_w * 100) / $this->new_img_w) / 100) * $this->img_w);
                        $this->pad_x = ($this->img_w - $this->reveal_width) / 2;
                    }

                } else {
                    // orentation is vertical do something
                    $set_ratio = $this->img_max_w / $this->img_max_h;
                    $img_ratio = $this->img_w / $this->img_h;

                    if ($set_ratio <= $img_ratio) {
                        // image ratio is greater use longer side
                        $ratio = $this->img_max_h / $this->img_h;
                        // Use aquired ratio to constrain proportions to new size
                        $this->new_img_w = intval($this->img_w * $ratio);
                        $this->new_img_h = intval($this->img_h * $ratio);
                        $this->reveal_height = $this->img_h;
                        $this->reveal_width = intval(((($this->img_max_w * 100) / $this->new_img_w) / 100) * $this->img_w);
                        $this->pad_x = ($this->img_w - $this->reveal_width) / 2;
                    } else {
                        // image ratio is less use shorter side
                        $ratio = $this->img_max_w / $this->img_w;
                        // Use aquired ratio to constrain proportions to new size
                        $this->new_img_w = intval($this->img_w * $ratio);
                        $this->new_img_h = intval($this->img_h * $ratio);
                        $this->reveal_width = $this->img_w;
                        $this->reveal_height = intval(((($this->img_max_h * 100) / $this->new_img_h) / 100) * $this->img_h);
                        $this->pad_y = ($this->img_h - $this->reveal_height) / 2;
                    }
                }

            } else {

                // Are both max_w & max_h set?
                if (($this->img_max_w != 0) && ($this->img_max_h != 0)) {

                    if (($this->img_max_w <= $this->img_w ) || ($this->img_max_h <= $this->img_h)) {
                        $this->img_ratio = ($this->img_w > $this->img_h) ? ($this->img_max_w / $this->img_w) : ($this->img_max_h / $this->img_h);
                    } else {
                        $this->img_ratio = 1;
                    }

                // How about just max_w?
                } elseif ($this->img_max_w != 0) {

                    if ($this->img_max_w <= $this->img_w ) {
                        $this->img_ratio = $this->img_max_w / $this->img_w;
                    } else {
                        $this->img_ratio = 1;
                    }

                // How about just max_h
                } elseif ($this->img_max_h != 0) {

                    if ($this->img_max_h <= $this->img_h ) {
                        $this->img_ratio = $this->img_max_h / $this->img_h;
                    } else {
                        $this->img_ratio = 1;
                    }

                // Ok, then do not resize
                } else {
                    $this->img_ratio = 1;
                }

                $this->new_img_w = intval($this->img_w * $this->img_ratio);
                $this->new_img_h = intval($this->img_h * $this->img_ratio);

                // Final check
                if (($this->new_img_w > $this->img_max_w) && ($this->img_max_w != 0)) {
                    $this->img_ratio = $this->img_max_w / $this->img_w;
                    $this->new_img_w = intval($this->img_w * $this->img_ratio);
                    $this->new_img_h = intval($this->img_h * $this->img_ratio);
                } elseif (($this->new_img_h > $this->img_max_h) && ($this->img_max_h != 0)) {
                    $this->img_ratio = $this->img_max_h / $this->img_h;
                    $this->new_img_w = intval($this->img_w * $this->img_ratio);
                    $this->new_img_h = intval($this->img_h * $this->img_ratio);
                }
            }
        }
        
		/**
		 * This method prepares the path and executes create_folder() method
		 * @uses create_folder()
		 * @access private
		 * @return void
		 */
        function create_path() {
            // Create path
            $this->img_path = $this->img_saveto;
            // Add trailing backslash if needed
            if (substr($this->img_path, -1, 1) != '/') { $this->img_path = $this->img_path.'/'; }
            $this->create_folder($this->img_path);
        }

        /**
         * This method creates writable directories and subdirectories by
         * breaking up the path provided
         * @param string File path to create /path/to/create/
         * @return void
         */
        function create_folder($path) {

            $folder_list = split("/", $path);
            $len = count($folder_list);

            for($i=0;$i<$len;$i++) {
                $tmp .= $folder_list[$i] . '/';
                $oldumask = umask(0);
                @mkdir($tmp, 0777);
                umask($oldumask);
            }
         }
    }

?>
<?php
    
    // TODO: Make rename setting optional

    /**
     * Simple Image Watermark
     *
     * This class applies a watermark to image.
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
    Class simple_image_watermark  {
    
        /**
         * Path to image which watermark is to be applied
         * @access private
         * @var string
         */
        var $img;
        /**
         * Path to PNG watermark image (GIF is not supported)
         * @access private
         * @var string
         */
        var $img_watermark;
        /**
         * Placement of watermark on image
         * Default is center
         * 1 = Top left
         * 2 = Top right
         * 3 = Bottom left
         * 4 = Bottom right
         * @access private
         * @var integer 
         */
        var $position;
        /**
         * Padding to be applied to flush edges if position is not center
         * @access private
         * @var integer 
         */
        var $padding;
        /**
         * Name that created image should be saved as
         * @access private
         * @var string
         */
        var $img_savename;
        /**
         * Path to where created image should be saved to
         * @access private
         * @var string
         */
        var $img_saveto;
		/**
		 * Cleaned version of $img_saveto paramater
		 * @access private
		 * @var string
		 */
        var $img_path;

        /**
         * Constructor method applies provided settings to paramaters and runs
         * action methods
         * @retun boolean
         */
        function simple_image_watermark($image_options) {

            list($this->img, 
                 $this->img_watermark, 
                 $this->position, 
                 $this->padding, 
                 $this->img_savename, 
                 $this->img_saveto) = $image_options;
                 
             // Use original name if savename is not provided
             if (!$this->img_savename) {
                 $this->img_savename = basename($this->img);
             }

            if($this->watermark()) {
                return true;
            } else {
                return false;
            }
        }
        
		/**
		 * This method creates an instance of the image and watermark, then 
		 * merges them and saves created watermarked image to specified 
		 * location as specified name
		 * @access private
		 * @return boolean
		 */
        function watermark() {

            $image = imagecreatefromjpeg($this->img);
            $img_w = imageSX($image);
            $img_h = imageSY($image);

            $watermark = imagecreatefrompng($this->img_watermark);
            $wm_w = imagesx($watermark);
            $wm_h = imagesy($watermark);

            $pos = $this->get_position($img_w, $img_h, $wm_w, $wm_h);

            imagealphablending($watermark, 1);

            imagecopy($image, $watermark, $pos['left'], $pos['top'], 0, 0, $wm_w, $wm_h);

            $this->create_path();

            if (imagejpeg($image, $this->img_path.$this->img_savename, 100)) {
                imagedestroy($image); imagedestroy($watermark);
                return true;
            } else {
                imagedestroy($image); imagedestroy($watermark);
                return false;
            }

        }
        
		/**
		 * Returns x & y coordinates. Takes position paramater and padding 
		 * paramater adjusts coordinates accordingly.
		 * Position default is centered. 1 = top left, 2 = top right,
		 * 3 = bottom left, 4 = bottom right. Padding is added to two sides.
		 *
		 * @param Image width
		 * @param Image height
		 * @param Watermark width
		 * @param Watermark height
		 * @access private
		 * @return Array
		 */
        function get_position($img_w, $img_h, $wm_w, $wm_h) {

            $pos = array();

            switch ($this->position) {
                case 1: // Top left
                    $pos['left'] = 0;
                    $pos['top']  = 0;
                    if($this->padding) {
                        $pos['left'] += $this->padding;
                        $pos['top']  += $this->padding;
                    }
                    break;
                case 2: // Top right
                    $pos['left'] = $img_w - $wm_w;
                    $top  = 0;
                    if($this->padding) {
                        $pos['left'] -= $this->padding;
                        $pos['top']  += $this->padding;
                    }
                    break;
                case 3: // Bottom left
                    $pos['left'] = 0;
                    $pos['top']  = $img_h - $wm_h;
                    if($this->padding) {
                        $pos['left'] += $this->padding;
                        $pos['top']  -= $this->padding;
                    }
                    break;
                case 4: // Bottom right
                    $pos['left'] = $img_w - $wm_w;
                    $pos['top']  = $img_h - $wm_h;
                    if($this->padding) {
                        $pos['left'] -= $this->padding;
                        $pos['top']  -= $this->padding;
                    }
                    break;
                default: // Bottom right
                    $pos['left'] = ($img_w - $wm_w) / 2;
                    $pos['top']  = ($img_h - $wm_h) / 2;
                    break;
            }
            return $pos;

        }
		
		/**
		 * This method prepares the path and executes create_folder method
		 * @access private
		 * @return void
		 */
        function create_path() {
            // Create path
            $this->img_path = $this->img_saveto;
            // Add trailing backslash if needed
            if (substr($this->img_path, -1, 1) != '/') { 
                $this->img_path = $this->img_path.'/'; 
            }
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
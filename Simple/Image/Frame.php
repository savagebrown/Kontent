<?php
	/*
	   +--------------------------------------------------------------------+
	   | Simple Image Frame													|
	   +--------------------------------------------------------------------+
	   | TRANSPARENT PNG WINDOW AREA										|
	   +--------------------------------------------------------------------+
	   +------------+
	   | +--------+ |
	   | | window | | <- Frame Image
	   | |	area  | |
	   | +--------+ |
	   +------------+
	   +--------------------------------------------------------------------+
	*/

	/**
	 * Simple Image Frame (extends simple_image_newsize class)
	 *
	 * Merges a jpeg and transparent png image. Useful to apply frames to
	 * images. Uses parent methods to resize image before merge.
	 *
	 * @package		 Simple
	 * @author		 Koray Girton <koray@savagebrown.com>
	 * @version		 1.0.1
	 * @category	 Image
	 * @copyright	 Copyright (c) 2005 SavageBrown.com
	 * @license		 http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
	 */
	Class simple_image_frame extends simple_image_newsize  {
		/**
		 * Path to PNG frame image
		 * @access private
		 * @var string
		 */
		var $img_frame;
		/**
		 * Degree of tilt if image is to be rotated
		 * @access private
		 * @var integer
		 */
		var $degree_tilt;
		/**
		 * Left padding to where window begins
		 * @access private
		 * @var integer
		 */
		var $pad_left;
		/**
		 * Top padding to where window begins
		 * @access private
		 * @var integer
		 */
		var $pad_top;

		/**
		 * Constructor method applies provided settings to parameters and runs
		 * action methods
		 *
		 * @param string Settings
		 * @return boolean
		 */
		function simple_image_frame($image_options) {
			if (is_array($image_options)) {
				// Populate paramaters
				list($this->img,
					 $this->img_frame,
					 $this->img_max_w,
					 $this->img_max_h,
					 $this->pad_left,
					 $this->pad_top,
					 $this->img_savename,
					 $this->img_saveto,
					 $this->degree_tilt) = $image_options;
				// Set crop to true so that images are centered
				$this->crop = true;
				if ($this->frame()) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		 * Merges image and frame
		 *
		 * @access private
		 * @uses parent method set_image_info()
		 * @uses parent method resize()
		 * @return boolean
		 */
		function frame() {

			// set horizontal active dimensions from mask info
			$frame = imagecreatefrompng($this->img_frame);

			$frame_w = imageSX($frame);
			$frame_h = imageSY($frame);

			$this->set_image_info(); #parent method
			$this->resize(); #parent method

			// create base @ size of theme object
			$base = @imagecreatetruecolor($frame_w, $frame_h)
				or die("Cannot Initialize new GD image stream");
			$color = imagecolorallocate($base, 255, 255, 255);
			imagefill ($base, 0, 0, $color);

			// if specified rotate image
			if ($this->degree_tilt) {
				$im = imagerotate($im, $this->degree_tilt, $color);
			}

			// place image on base
			Imagecopyresampled($base, $this->new_img, $this->pad_left, $this->pad_top, 0, 0, $frame_w, $frame_h, $frame_w, $frame_h);
			// merge with frame
			ImageCopy($base, $frame, 0, 0, 0, 0, $frame_w, $frame_h);

			$this->create_path(); #parent method

			if(imagejpeg($base, $this->img_path.$this->img_savename)) {
				// Clean up
				imagedestroy($this->new_img); imagedestroy($this->copy_img);
				imagedestroy($base); imagedestroy($frame);
				return true;
			} else {
				imagedestroy($this->new_img); imagedestroy($base); imagedestroy($frame);
				return false;
			}

		}

	}
?>
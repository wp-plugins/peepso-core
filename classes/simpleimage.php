<?php

/**
 * File: SimpleImage.php
 * Author: Simon Jarvis
 * Copyright: 2006 Simon Jarvis
 * Date: 08/11/06
 * Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 * Link: http://www.white-hat-web-design.co.uk/blog/resizing-images-with-php/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * http://www.gnu.org/licenses/gpl.html
 */


class PeepSoSimpleImage
{

	var $image;
	var $image_type;
	private $orientation = NULL;

	/**
	 * Loads an image file
	 * @param string $filename Image filename
	 */
	public function load($filename)
	{
		if (function_exists('exif_read_data') && function_exists('exif_imagetype') && IMAGETYPE_JPEG === exif_imagetype($filename)) {
			$exif = @exif_read_data($filename);
			if (!empty($exif['Orientation']))
				$this->orientation = $exif['Orientation'];
		} else {
			$exif = new PeepSoExif($filename);
			$this->orientation = $exif->get_orientation();
		}

		$image_info = getimagesize($filename);
		$this->image_type = $image_info[2];
		if ($this->image_type == IMAGETYPE_JPEG)
			$this->image = imagecreatefromjpeg($filename);
		elseif ($this->image_type == IMAGETYPE_GIF)
			$this->image = imagecreatefromgif($filename);
		elseif ($this->image_type == IMAGETYPE_PNG)
			$this->image = imagecreatefrompng($filename);
	}

	/**
	 * Saves file
	 * @param string $filename Output file name
	 * @param int $image_type Image type
	 * @param int $compression Compression
	 * @param int $permission
	 */
	public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null)
	{
		$this->fix_orientation();
		if ($image_type == IMAGETYPE_JPEG)
			imagejpeg($this->image, $filename, $compression);
		elseif ($image_type == IMAGETYPE_GIF)
			imagegif($this->image, $filename);
		elseif ($image_type == IMAGETYPE_PNG)
			imagepng($this->image, $filename);

		imagedestroy($this->image);
		if ($permissions != null)
			chmod($filename, $permissions);
	}

	/**
	 * Fix image orientation
	 */
	public function fix_orientation()
	{
		switch ($this->orientation)
		{
		case 3:
			$this->image = imagerotate($this->image, 180, 0);
			break;
		case 6:
			$this->image = imagerotate($this->image, -90, 0);
			break;
		case 8:
			$this->image = imagerotate($this->image, 90, 0);
			break;
		}
	}

	/**
	 * Save PNG as JPEG image
	 * @param string $input_file
	 */
	public function png_to_jpeg($input_file) 
	{
		list($width, $height, $type) = getimagesize($input_file);
		if ($type == IMAGETYPE_PNG) {
			$input = imagecreatefrompng($input_file);
			$output = imagecreatetruecolor($width, $height);
			// Set white background for transparent PNG
			$white = imagecolorallocate($output,  255, 255, 255);
			imagefilledrectangle($output, 0, 0, $width, $height, $white);
			imagecopy($output, $input, 0, 0, 0, 0, $width, $height);
			imagejpeg($output, $input_file, 100);
		}
	}

	/**
	 * Output an image
	 * @param int $image_type Image type
	 */
	public function output($image_type = IMAGETYPE_JPEG)
	{
		if ($image_type == IMAGETYPE_JPEG)
			imagejpeg($this->image);
		elseif ($image_type == IMAGETYPE_GIF)
			imagegif($this->image);
		elseif ($image_type == IMAGETYPE_PNG)
			imagepng($this->image);
	}

	/**
	 * Get image width
	 * @return int image width
	 */
	public function getWidth()
	{
		return imagesx($this->image);
	}

	/**
	 * Get image height
	 * @return int image height
	 */
	public function getHeight()
	{
		return imagesy($this->image);
	}

	/**
	 * Resize to height
	 * @param int $height Image height
	 */
	public function resizeToHeight($height)
	{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width, $height);
	}

	/**
	 * Resize to width
	 * @param int $width Image width
	 */
	public function resizeToWidth($width)
	{
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width, $height);
	}

	/**
	 * Resize the image based on scale
	 * @param int $scale Dimension scale
	 */
	public function scale($scale)
	{
		$width = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;
		$this->resize($width, $height);
	}

	/**
	 * Resize an image
	 * @param int $width Image width
	 * @param int $height Image height
	 */
	public function resize($width, $height)
	{
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}

}

// EOF

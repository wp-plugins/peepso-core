<?php

class PeepSoExif
{
	private $_filename = NULL;
	private $_fh = NULL;
	private $_msg = NULL;

/*
 * http://sylvana.net/jpegcrop/jpegexiforient.c
 *   1        2       3      4         5            6           7          8
 *
 * 888888  888888      88  88      8888888888  88                  88  8888888888
 * 88          88      88  88      88  88      88  88          88  88      88  88
 * 8888      8888    8888  8888    88          8888888888  8888888888          88
 * 88          88      88  88
 * 88          88  888888  888888
*/

	public function __construct($file)
	{
		if (file_exists($file))
			$this->_filename = $file;
	}

	/**
	 * Finds the image orientation value
	 * @return mixed FALSE on error or non-JPG type images, otherwise the image orientation value (see above) 0=normal, 3=rotate 180, 6=rotate 270, 8=rotate 90
	 */
	public function get_orientation()
	{
		if (NULL === $this->_filename)
			return ($this->_set_error(__('no file given (' . var_export($this->_filename, TRUE) . ')', 'peepso')));

		$this->_fh = fopen($this->_filename, 'r');
		if (FALSE === $this->_fh)
			return ($this->_set_error(__('unable to open file', 'peepso')));

		$exif_date = array();
		// read file head, check for JPEG SOI + Exif APP1
		for ($i = 0; $i < 4; $i++)
		    $exif_data[$i] = ord($this->_read1());
		// check for valid JPEG headers
		if (0xFF != $exif_data[0] ||
			0xD8 != $exif_data[1] ||
			0xFF != $exif_data[2] ||
			0xE1 != $exif_data[3])
			return ($this->_set_error(__('JPG header not found', 'peepso')));

		// get the marker parameter length count
		$length = $this->_read2();
		// length includes itself, so must be at least 2
		// following Exif data length must be at least 6
		if ($length < 8)
			return (0);
		$length -= 8;
//echo 'length: ', $length, PHP_EOL;

		// read Exif head, check for "Exif"
		for ($i = 0; $i < 6; $i++)
			$exif_data[$i] = ord($this->_read1());
		if (0x45 != $exif_data[0] ||
			0x78 != $exif_data[1] ||
			0x69 != $exif_data[2] ||
			0x66 != $exif_data[3] ||
			0 != $exif_data[4] ||
			0 != $exif_data[5])
			return ($this->_set_error(__('Exif data not found ' . var_export($exif_data, TRUE), 'peepso')));

		// read Exif body
		for ($i = 0; $i < $length; $i++)
			$exif_data[$i] = ord($this->_read1());

		if ($length < 12)
			return ($this->_set_error(__('bad IFD entry length', 'peepso'))); // length of an IFD entry

		// determine byte order
		if (0x49 == $exif_data[0] && 0x49 == $exif_data[1])
			$is_motorola = 0;
		else if (0x4D == $exif_data[0] && 0x4D == $exif_data[1])
			$is_motorola = 1;
		else
			return ($this->_set_error(__('unable to determine byte order', 'peepso')));

		// check Tag mark
		if ($is_motorola) {
			if (0 != $exif_data[2])
				return ($this->_set_error(__('invalid tag mark', 'peepso')));
			if (0x2A != $exif_data[3])
				return ($this->_set_error(__('unrecognized Tag mark: 0x', 'peepso') . dechex($exif_data[3])));
		} else {
			if (0 != $exif_data[3])
				return ($this->_set_error(__('invalid tag mark', 'peepso')));
			if (0x2A != $exif_data[2])
				return ($this->_set_error(__('unrecognized Tag mark: 0x', 'peepso') . dechex($exif_data[3])));
		}
//echo 'found proper tag mark', PHP_EOL;

		// get first IFD offset (offset to IFD0)
		if ($is_motorola) {
			if (0 != $exif_data[4])
				return ($this->_set_error(__('invalid IFD offset', 'peepso')));
			if (0 != $exif_data[5])
				return ($this->_set_error(__('invalid IFD offset', 'peepso')));
//echo '0x', dechex($exif_data[6]), ' 0x', dechex($exif_data[7]), PHP_EOL;
			$offset = $exif_data[6];
			$offset <<= 8;
			$offset += $exif_data[7];
		} else {
			if (0 != $exif_data[7])
				return ($this->_set_error(__('invalid IFD offset', 'peepso')));
			if (0 != $exif_data[6])
				return ($this->_set_error(__('invalid IFD offset', 'peepso')));
			$offset = $exif_data[5];
			$offset <<= 8;
			$offset += $exif_data[4];
		}
		// check end of data segment
		if ($offset > $length - 2)
			return ($this->_set_error(__('invalid offset', 'peepso')));
//echo 'offset: ', $offset, PHP_EOL;

		// get the number of directory entries contained in this IFD
		if ($is_motorola) {
			$number_of_tags = $exif_data[$offset];
			$number_of_tags <<= 8;
			$number_of_tags += $exif_data[$offset + 1];
		} else {
			$number_of_tags = $exif_data[$offset + 1];
			$number_of_tags <<= 8;
			$number_of_tags += $exif_data[$offset];
		}
		if (0 === $number_of_tags)
			return ($this->_set_error (__('invalid number of tags: ', 'peepso') . $number_of_tags));
//echo 'number of tags: ', $number_of_tags, PHP_EOL;
		$offset += 2;

		// search for Orientation Tag in IFD0
		for (;;) {
			// check end of data segment
			if ($offset > $length - 12)
				return ($this->_set_error(__('exceeded end of data segment', 'peepso')));
			// get Tag number
			if ($is_motorola) {
				$tagnum = $exif_data[$offset];
				$tagnum <<= 8;
				$tagnum += $exif_data[$offset + 1];
			} else {
				$tagnum = $exif_data[$offset + 1];
				$tagnum <<= 8;
				$tagnum += $exif_data[$offset];
			}
//echo 'tagnum: ', $tagnum, PHP_EOL;
			if (0x0112 == $tagnum)
				break;			// found Orientation Tag
			if (0 == --$number_of_tags)
				return ($this->_set_error(__('number of bytes is zero', 'peepso')));
			$offset += 12;
		}

		// get the Orientation value
		if ($is_motorola) {
			if (0 != $exif_data[$offset + 8])
				return ($this->_set_error(__('orientagion value is 0', 'peepso')));
			$set_flag = $exif_data[$offset + 9];
		} else {
			if (0 != $exif_data[$offset + 9])
				return ($this->_set_error(__('orientagion value is 0', 'peepso')));
			$set_flag = $exif_data[$offset + 8];
		}
//echo 'set_flag: ', $set_flag, PHP_EOL;
		if ($set_flag > 8)
			return ($this->_set_error(__('invalid orientation tag:', 'peepso') . $set_flag));

		return ($set_flag);
	}

	/**
	 * Reads a single byte from the input file
	 * @return string The character read or FALSE at EOF
	 */
	private function _read1()
	{
		$ch = fread($this->_fh, 1);
		if (FALSE === $ch)
			return ($this->_set_error(__('premature EOF', 'peepso')));
		return ($ch);
	}

	/**
	 * Reads two bytes from the input file
	 * @return string The characters read or FALSE at EOF
	 */
	private function _read2()
	{
		$ch1 = fread($this->_fh, 1);
		$ch2 = fread($this->_fh, 1);
		if (FALSE === $ch1 || FALSE === $ch2)
			return ($this->_set_error(__('premature eof', 'peepso')));

		$a1 = ord($ch1);
		$a2 = ord($ch2);
		$ret = ($a1 << 8) + $a2;
		return ($ret);
	}

	/**
	 * Sets the error that the class instance will "remember"
	 * @param string $msg Error message to remember
	 * @return boolean Always returns FALSE to indicate an error
	 */
	private function _set_error($msg)
	{
		$this->_msg = $msg;
		return (FALSE);
	}

	/**
	 * Gets the last error message
	 * @return string Returns the last error message that the instance "remembered" or NULL if no errors were stored
	 */
	public function get_error()
	{
		return ($this->_msg);
	}
}

// EOF
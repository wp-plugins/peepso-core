<?php

if (!function_exists('convert_php_size_to_bytes')) {
	//This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
	function convert_php_size_to_bytes($sSize)
	{
		if (is_numeric($sSize))
		   return $sSize;

		$sSuffix = substr($sSize, -1);
		$iValue = substr($sSize, 0, -1);

		switch(strtoupper($sSuffix))
		{
		case 'P':
			$iValue *= 1024;
		case 'T':
			$iValue *= 1024;
		case 'G':
			$iValue *= 1024;
		case 'M':
			$iValue *= 1024;
		case 'K':
			$iValue *= 1024;
			break;
		}
		return ($iValue);
	}
}

if (!function_exists('redirect_https')) {
	function redirect_https()
	{
		if (!is_ssl()) {
			$redirect= "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			header("Location:$redirect");
		}
	}
}

if (!function_exists('dateformat_PHP_to_jQueryUI')) {
	/*
	 * Matches each symbol of PHP date format standard
	 * with jQuery equivalent codeword
	 * @author Tristan Jahier
	 */
	function dateformat_PHP_to_jQueryUI($php_format)
	{
	    $SYMBOLS_MATCHING = array(
	        // Day
	        'd' => 'dd',
	        'D' => 'D',
	        'j' => 'd',
	        'l' => 'DD',
	        'N' => '',
	        'S' => '',
	        'w' => '',
	        'z' => 'o',
	        // Week
	        'W' => '',
	        // Month
	        'F' => 'MM',
	        'm' => 'mm',
	        'M' => 'M',
	        'n' => 'm',
	        't' => '',
	        // Year
	        'L' => '',
	        'o' => '',
	        'Y' => 'yyyy',
	        'y' => 'yy',
	        // Time
	        'a' => '',
	        'A' => '',
	        'B' => '',
	        'g' => '',
	        'G' => '',
	        'h' => '',
	        'H' => '',
	        'i' => '',
	        's' => '',
	        'u' => ''
	    );
	    $jqueryui_format = '';
	    $escaping = FALSE;
	    for ($i = 0; $i < strlen($php_format); $i++) {
	        $char = $php_format[$i];
	        if ('\\' === $char) {		// PHP date format escaping character
	            ++$i;
	            if ($escaping)
					$jqueryui_format .= $php_format[$i];
	            else
					$jqueryui_format .= '\'' . $php_format[$i];
	            $escaping = TRUE;
	        } else {
	            if ($escaping) {
					$jqueryui_format .= "'";
					$escaping = FALSE;
				}
	            if (isset($SYMBOLS_MATCHING[$char]))
	                $jqueryui_format .= $SYMBOLS_MATCHING[$char];
	            else
	                $jqueryui_format .= $char;
	        }
	    }
	    return ($jqueryui_format);
	}
}

if (!function_exists('ps_oembed_get')) {
	/**
	 * PeepSo wrapper for wp_oembed_get.
	 * Turns off discover for oembed calls when the WP version is less than 3.9 prior to https://core.trac.wordpress.org/ticket/27656 .
	 * Attempts to fetch the embed HTML for a provided URL using oEmbed.
	 *
	 * @see WP_oEmbed
	 *
	 * @uses _wp_oembed_get_object()
	 * @uses WP_oEmbed::get_html()
	 *
	 * @param string $url The URL that should be embedded.
	 * @param array $args Additional arguments and parameters.
	 * @return bool|string False on failure or the embed HTML on success.
	 */
	function ps_oembed_get($url, $args = '', $check_force = FALSE)
	{
		global $wp_version;

		if (version_compare($wp_version, '3.9') < 0) {
			$args['discover'] = FALSE;
		}

		require_once( ABSPATH . WPINC . '/class-oembed.php' );

		$oembed = _wp_oembed_get_object();

		$html = $oembed->get_html( $url, $args );

		// < 1.2.0 - for legacy reasons return only HTML if the third flag is not set
		if( FALSE === $check_force ) {
			return $html;
		}

		// >= 1.2.0 build a response array
		$return = array(
			'html'				=> $html,
			'force_oembed' 		=> FALSE,
		);

		// if it's a valid oembed
		if( $oembed->get_provider($url) || $oembed->discover($url)  ) {
			$return['force_oembed'] = TRUE;
		} else {
			// if NOT an oembed, reset the content to force og-image fallback
			$return['html'] = '';
		}

		return $return;
	}
}


if (!function_exists('ps_isempty')) {
	/**
	 * Checks parameter value to be 'empty', as in: not assigned, FALSE, NULL, empty string or empty array
	 * Note: a string of '0' is *NOT* considered 'empty', unlike the PHP isempty() function
	 * @param mixed $val
	 * @return Boolean TRUE if value is empty as defined above; otherwise FALSE
	 */
	function ps_isempty($val)
	{
		if (!isset($val) || is_null($val) ||
			(is_string($val) && '' === trim($val) && !is_bool($val)) ||
			(FALSE === $val && is_bool($val)) ||
			(is_array($val) && empty($val)))
			return (TRUE);
		return (FALSE);
	}
}
// EOF

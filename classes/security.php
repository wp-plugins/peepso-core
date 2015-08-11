<?php

class PeepSoSecurity
{
	/*
	 * Strips invalid/unsafe HTML from content
	 * @param string $content The content to sanitize
	 * @return string The $content string, with invalid HTML removed.
	 */
	public static function strip_content($content)
	{
		if (function_exists('wp_kses_allowed_html')) {
			$html = wp_kses_allowed_html('post');
		} else {
			global $allowedposttags;
			$html = $allowedposttags;
		}
		$content = wp_kses($content, $html);
		return ($content);
	}
}

// EOF
<?php
/*
 * Performs tasks for Admin page requests
 * @package PeepSo
 * @author PeepSo
 */

class PeepSoRemoteContent
{
	const INTEGRATION_JSON_URL = 'http://peepso.com/peepsotools-integration-json';
	#const INTEGRATION_JSON_URL = 'http://dev.peepsodev.com/peepsotools-integration-json';
	#const INTEGRATION_JSON_URL = 'http://current.peepso.ninja/peepsotools-integration-json';

	/**
	 * @param $section name of the transient everything is cached in
	 * @param $file name of the file in the home office
	 * @param null $replace  array of from-to replacements
	 * @return mixed|string
	 */
	public static function get($section, $file, $replace = NULL)
	{
		// if the cache override is used for testing purposes
		if(isset($_GET['nocache'])) {
				delete_transient($section);
		}

		// if the cache is empty
		if (NULL == $response = get_transient($section)) {

			// grab the remote url
			$url = self::INTEGRATION_JSON_URL . '/' . $file;
			echo '<!--Fetching', $url, '-->';

			$resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 15, 'sslverify' => FALSE));

			if (!is_wp_error($resp)) {
				$response = wp_remote_retrieve_body($resp);
				set_transient($section, $response, 1 * HOUR_IN_SECONDS);
			} else {
				$response = 'Timeout';
			}
		}

		if( NULL !== $replace) {
			$response = str_ireplace($replace['from'], $replace['to'], $response);
		}

		// run additional "filter": HTML parse
		$method = 'parse_html_'.$section;
		if( method_exists('PeepSoRemoteContent', $method)) {
			$response = self::$method($response);
		}

		// run additional "filter": JSON parse
		$method = 'parse_json_'.$section;
		if( method_exists('PeepSoRemoteContent', $method)) {
			$response = self::$method($response);
		}

		return $response;
	}

	/**
	 * Hides or shows buy/installed buttons on the bundle box
	 * Modify inline CSS inside $response
	 *
	 * @param string $html_response
	 * @param bool $has_all_plugins
	 * @return string
	 */
	public static function parse_html_peepso_ws_bundles( $html_response )
	{
		$replace_hide = strtoupper("PLUGIN-BUNDLE-INSTALLED");
		$replace_show = strtoupper("PLUGIN-BUNDLE-BUY");
		$has_all_plugins = self::has_all_plugins();

		if( TRUE === $has_all_plugins ) {
			$replace_tmp = $replace_hide;
			$replace_hide = $replace_show;
			$replace_show = $replace_tmp;
		}

		$html_response  = str_ireplace($replace_hide, 'display:none', $html_response );
		$html_response  = str_ireplace($replace_show, 'display:block', $html_response );
		return $html_response;
	}

	/**
	 * Hides or shows buy/installed buttons on the list of plugins
	 * Grab JSON list of plugins from peepso.com
	 * Loop through them and modify inline CSS inside $response
	 *
	 * @param string $html_response
	 * @param string $has_all_plugins
	 * @return string
	 */
	public static function parse_html_peepso_ws_plugins( $html_response )
	{
		$plugins = self::get('peepso_plugins','peepso_dashboard_plugins.json');

		// loop through the plugin list
		foreach ($plugins as $class => $plugin) {
			$id = intval($plugin['product-id']);
			if( $id == 0) {
				continue;
			}

			// replace inline CSS blocks to reflect each plugin activation state
			$replace_hide = strtoupper("plugin-{$id}-installed");
			$replace_show = strtoupper("plugin-{$id}-buy");
			if(class_exists($class)) {
				$replace_tmp = $replace_hide;
				$replace_hide = $replace_show;
				$replace_show = $replace_tmp;
			}

			$html_response  = str_ireplace($replace_hide, 'display:none', $html_response );
			$html_response  = str_ireplace($replace_show, 'display:block', $html_response );
		}

		return $html_response ;
	}

	// JSON plugin listings
	/**
	 * Unserialize JSON, print error if fails
	 *
	 * @param $json_response
	 * @return array|bool|mixed
	 */
	public static function parse_json_peepso_plugins( $json_response )
	{
		// double check for errors
		if (!strlen($json_response) || !($plugins = json_decode($json_response, true))) {
			echo "Sorry, there has been an error fetching available plugins";
			return false;
		}

		$plugins = array_pop($plugins);
		return $plugins;
	}

	/**
	 * Count all plugins from the home office
	 * @return int
	 */
	public static function plugins_count_all()
	{
		$count = 0;

		$plugins = self::get('peepso_plugins','peepso_dashboard_plugins.json');

		foreach ($plugins as $class => $plugin) {
			if (class_exists($class)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Count all plugins from the home office list installed locally
	 * @return int
	 */
	public static function plugins_count_mine()
	{
		$count = 0;

		$plugins = self::get('peepso_plugins','peepso_dashboard_plugins.json');

		foreach ($plugins as $class => $plugin) {
			if( intval($plugin['product-id']) > 0) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Check if all the plugins are installed
	 *
	 * @return bool
	 */
	public static function has_all_plugins()
	{
		return ( self::plugins_count_all() == self::plugins_count_mine()) ? TRUE : FALSE;
	}
}
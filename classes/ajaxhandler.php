<?php

class PeepSoAjaxHandler
{
	private $page = NULL;

	public function __construct($page)
	{
		$this->page = $page;

		// use 'init' action to allow WP to fully load
		add_action('wp', array(&$this, 'call_listeners'));
		add_filter('the_posts', array(&$this, 'post_filter'));
	}

	/*
	 * Callback for 'init' action. This is where any AJAX listeners will be called
	 */
	public function call_listeners()
	{
//PeepSo::log(__METHOD__.'() ** starting');
		remove_action('the_posts', array(&$this, 'post_filter'));		// turn off the filter
		do_action('peepso_ajax_start', $this->page);

		$resp = new PeepSoAjaxResponse();
		$parts = explode('.', $this->page, 2);
		$class = $parts[0];
		$method = isset($parts[1]) ? $parts[1] : '';

		$class = 'PeepSo' . ucwords($class);
PeepSo::log(__METHOD__."() class=[{$class}] method=[{$method}]");
		if (class_exists($class)) {
			// calling class_exists will load the class
			$inst = call_user_func(array($class, 'get_instance'));

			// check to make sure it's a valid object
			if (!is_object($inst)) {
				$resp->error('Not a valid PeepSo request');
				$resp->send();
			}
			// make sure it implements PeepSoAjaxCallback to help prevent arbitrary code execution
			if (!is_a($inst, 'PeepSoAjaxCallback')) {
				$resp->error('Not a valid PeepSo request');
				$resp->send();
			}

			$res = call_user_func(array($inst, $method), $resp);
PeepSo::log(' - returned: ' . var_export($res, TRUE));
		} else {
PeepSo::log(' - class does not exist: ' . $class);
			do_action('peepso_ajax_call_' . $this->page, $resp);
			do_action('peepso_ajax_call', $this->page, $resp);
		}

		do_action('peepso_ajax_before_send', $resp);
		$resp->send(FALSE);

		do_action('peepso_ajax_end', $this->page);
//PeepSo::log(__METHOD__.'() ** done');
		exit(0);
	}


	/*
	 * called to filter the post results of WP_Query. When an AJAX call is made, the
	 * page doesn't exist - so this method resets everything and builds a virtual page
	 */
	public function post_filter($posts)
	{
		global $wp_query;
PeepSo::log(__METHOD__.'()');
		//create a fake post instance
		$post = new stdClass;
		// fill properties of $post with everything a page in the database would have
		$post->ID = -1;                          // use an illegal value for page ID
		$post->post_author = 0;						// post author id
		$post->post_date = '0000-00-00 00:00:00';	// date of post
		$post->post_date_gmt = $post->post_date;
		$post->post_content = '';
		$post->post_title = '';
		$post->post_excerpt = '';
		$post->post_status = 'publish';
		$post->comment_status = 'closed';        // mark as closed for comments, since page doesn't exist
		$post->ping_status = 'closed';           // mark as closed for pings, since page doesn't exist
		$post->post_password = '';               // no password
		$post->post_name = '';
		$post->to_ping = '';
		$post->pinged = '';
		$post->modified = $post->post_date;
		$post->modified_gmt = $post->post_date_gmt;
		$post->post_content_filtered = '';
		$post->post_parent = 0;
		$post->guid = $_SERVER['SERVER_PROTOCOL'] . $_SERVER['HTTP_HOST'] . '/';
		$post->menu_order = 0;
		$post->post_type = 'page';
		$post->post_mime_type = '';
		$post->comment_count = 0;

		// set filter results
		$posts = array($post);

		// reset wp_query properties to simulate a found page
		$wp_query->is_page = TRUE;
		$wp_query->is_singular = TRUE;
		$wp_query->is_home = FALSE;
		$wp_query->is_archive = FALSE;
		$wp_query->is_category = FALSE;
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404 = FALSE;

		return ($posts);
	}
}

// EOF

<?php

class PeepSoVirtualPage
{
	private $page = NULL;
	private $extra = NULL;

	public function __construct($page, $extra)
	{
		$this->page = $page;
		$this->extra = $extra;

		add_filter('redirect_canonical', array(&$this, 'prevent_redirect'), 10, 2);
		$this->setup_query_content();
	}

	public function setup_query_content()
	{
		global $wp_query;

		// special handling for "not found" child pages
		if ($wp_query->is_404) {
			header('HTTP/1.0 200 OK');			// reset HTTP result code, no longer a 404 error

			$wp_query->is_404 = FALSE;
			$wp_query->is_page = TRUE;
			$wp_query->is_admin = FALSE;
			unset($wp_query->query['error']);
			$wp_query->query_vars['error'] = '';
		}

		// create a fake post instance
		$vpost = new stdClass;
		// fill properties of $post with everything a page in the database would have
		$vpost->ID = -1;                          // use an illegal value for page ID
		$vpost->post_author = 0;						// post author id
		$vpost->post_date = '0000-00-00 00:00:00';	// date of post
		$vpost->post_date_gmt = $vpost->post_date;
		$vpost->post_content = '';
		$vpost->post_title = '';
		$vpost->post_excerpt = '';
		$vpost->post_status = 'publish';
		$vpost->comment_status = 'closed';        // mark as closed for comments, since page doesn't exist
		$vpost->ping_status = 'closed';           // mark as closed for pings, since page doesn't exist
		$vpost->post_password = '';               // no password
		$vpost->post_name = '';
		$vpost->to_ping = '';
		$vpost->pinged = '';
		$vpost->modified = $vpost->post_date;
		$vpost->modified_gmt = $vpost->post_date_gmt;
		$vpost->post_content_filtered = '';
		$vpost->post_parent = 0;
		$vpost->guid = $_SERVER['SERVER_PROTOCOL'] . $_SERVER['HTTP_HOST'] . '/' .
			$this->page . ($this->extra !== NULL ? '/' : $this->extra) . '/';
		$vpost->menu_order = 0;
		$vpost->post_type = 'page';
		$vpost->post_mime_type = '';
		$vpost->comment_count = 0;

		// set filter results
		$posts = array($vpost);
		$wp_query->post_count = 1;
		$wp_query->posts = $posts;

		// redirect to the root page
		$newposts = query_posts('pagename=' . $this->page);
		if (count($newposts) > 0) {
			$wp_query->posts = $newposts;
			$wp_query->post_count = count($newposts);
			$wp_query->found_posts = count($newposts);

			$wp_query->query = array('page' => '', 'pagename' => $this->page);
			$wp_query->queried_object = $newposts[0];
			$wp_query->queried_object_id = $newposts[0]->ID;

			global $post;
			$post = $newposts[0];
		}

		// reset wp_query properties to simulate a found page
		$wp_query->is_page = TRUE;
		$wp_query->is_singular = TRUE;
		$wp_query->is_home = FALSE;
		$wp_query->is_archive = FALSE;
		$wp_query->is_category = FALSE;
	}

	/**
	 * Cancels canonical redirects when on a subpage / virtual page.
	 * 
	 * @param string $redirect_url  The redirect URL.
	 * @param string $requested_url The requested URL.
	 */
	public function prevent_redirect($redirect_url, $requested_url)
	{
		// It's okay to redirect from top level slugs, but once we go "/top/second" not so much
		return (NULL === $this->extra);
	}
}

// EOF
<?php

class PeepSoMembersShortcode
{
	public $template_tags = array(
		'show_member'
	);

	public function __construct()
	{
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}

	/**
	 * Enqueues the scripts used in this shortcode only.
	 */
	public function enqueue_scripts()
	{
		
	}

	/**
	 * Displays the member search page.
	 */
	public function shortcode_search()
	{
		PeepSo::set_current_shortcode('peepso_members');
		$allow = apply_filters('peepso_access_content', TRUE, 'peepso_members', PeepSo::MODULE_ID);
		if (!$allow) {
			echo apply_filters('peepso_access_message', NULL);
			return;
		}

		$input = new PeepSoInput();
		$search = trim($input->get('query', ''));

		$num_results = 0;
		$member_search = PeepSoMemberSearch::get_instance();

		if (FALSE === empty($search) && wp_verify_nonce($input->get('_wpnonce'), 'member-search')) {
			$search_results = new PeepSoUserSearch(array(), PeepSo::get_user_id(), $search);
			$member_search->set_member_query($search_results);
			$num_results = count($search_results->results);
			$search = stripslashes_deep($search);
		}

		$ret = PeepSoTemplate::get_before_markup() .
				PeepSoTemplate::exec_template('members', 'search', array('search' => $search, 'num_results' => $num_results), TRUE) .
				PeepSoTemplate::get_after_markup();

		wp_reset_query();

		// disable WP comments from displaying on page
		global $wp_query;
		$wp_query->is_single = FALSE;
		$wp_query->is_page = FALSE;

		return ($ret);
	}
}

// EOF

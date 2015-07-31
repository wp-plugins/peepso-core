<?php

class PeepSoActivityShortcode
{
	private static $_instance = NULL;

	private $page = NULL;
	private $extra = NULL;
	private $permalink = NULL;
	private $post_id = NULL;
	private $act_access = NULL;
	private $act_owner_id = NULL;

	// TODO: shortcodes should not have template callbacks; this needs to be moved to PeepSoActivity
	public $template_tags = array(
		'is_permalink_page'
	);

	public function __construct()
	{
		add_shortcode('peepso_activity', array(&$this, 'do_shortcode'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
        add_filter('peepso_page_title', array(&$this,'peepso_page_title'));
	}

	/*
	 * return singleton instance of the plugin
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/**
	 * Check if author has permission to see the owner post
	 * @return boolean return as TRUE or FALSE depending on permission
	 */
	private function is_accessible()
	{
		if (! $this->is_permalink_page())
			return (TRUE);

		if (NULL === $this->act_access) {
			global $wpdb;

			$sql = 'SELECT `ID`, `act_access`, `act_owner_id` ' .
				" FROM `{$wpdb->posts}` " .
				" LEFT JOIN `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` ON `act_external_id`=`{$wpdb->posts}`.`ID` " .
				' WHERE `post_name`=%s AND `post_type`=%s ' .
				' LIMIT 1 ';
			$ret = $wpdb->get_row($wpdb->prepare($sql, $this->permalink, PeepSoActivityStream::CPT_POST));

			if (NULL !== $ret) {
				$this->post_id = $ret->ID;
				$this->act_access = $ret->act_access;
				$this->act_owner_id = $ret->act_owner_id;
			}
		}

		// look up the post so check_permissions() knows which post we're talking about
		$args = array('page_id' => $this->post_id, 'post_type' => PeepSoActivityStream::CPT_POST);
		$query = new WP_Query($args);
		global $post;
		if ($query->have_posts()) {
			$query->the_post();
			// fix up the post values
			$post->act_access = $this->act_access;
			$post->act_owner_id = $this->act_owner_id;
		}
		// use check_permissions() to see if current user van view this post
		return (PeepSo::check_permissions(intval($post->post_author), PeepSo::PERM_POST_VIEW, PeepSo::get_user_id(), TRUE));
	}

	// @todo docblock
    public function peepso_page_title( $title )
    {
        if('peepso_activity' == $title['title']) {
            $title['newtitle'] = __('Activity', 'peepso');
        }

        return $title;
    }

	/*
	 * shortcode callback for the Activity Stream
	 * @param array $atts Shortcode attributes
	 * @param string $content Contents of the shortcode
	 * @return string output of the shortcode
	 */
	public function do_shortcode($atts, $content)
	{
		PeepSo::set_current_shortcode('peepso_activity');
		$allow = apply_filters('peepso_access_content', TRUE, 'peepso_activity', PeepSoActivity::MODULE_ID);
		if (!$allow) {
			echo apply_filters('peepso_access_message', NULL);
			return;
		}

		if ($this->is_accessible()) {
			wp_enqueue_script('peepso-activitystream-js');

			$ret = PeepSoTemplate::get_before_markup() .
				PeepSoTemplate::exec_template('activity', 'activity', NULL, TRUE) .
				PeepSoTemplate::get_after_markup();
		} else {
			$ret = PeepSoTemplate::get_before_markup() .
				'<h4 class="ps-text-title ps-text-danger">' . __('This content is not available at this time.', 'peepso') . '</h4>' .
				'<p>' . __('Possible causes for the content of the page not to show:', 'peepso') . '</p>' .
				'<ul class="ps-list-classic">' .
					'<li>' . __('It has been removed.', 'peepso') . '</li>' .
					'<li>' . __('You may not have the necessary permissions to view it.', 'peepso') . '</li>' .
				'</ul>' .
				PeepSoTemplate::get_after_markup();
		}

		PeepSo::reset_query();

		return ($ret);
	}

	/*
	 * enqueues the scripts needed by the Activity Stream
	 */
	public function enqueue_scripts()
	{
//PeepSo::log(__METHOD__.'()');
//PeepSo::log('  asset: ' . PeepSo::get_template_asset('activity', 'activity.css'));
		wp_register_script('peepso-window', PeepSo::get_asset('js/pswindow.min.js'),
			array('jquery'), PeepSoActivityStream::PLUGIN_VERSION, TRUE);
		wp_enqueue_script('peepso-window');

		wp_localize_script('peepso-window', 'peepsowindowdata', array(
			'label_confirm' => __('Confirm', 'peepso'),
			'label_confirm_delete' => __('Confirm Delete', 'peepso'),
			'label_confirm_delete_content' => __('Are you sure you want to delete this?', 'peepso'),
			'label_yes' => __('Yes', 'peepso'),
			'label_no' => __('No', 'peepso'),
			'label_delete' => __('Delete', 'peepso'),
			'label_cancel' => __('Cancel', 'peepso'),
			'label_okay' => __('Okay', 'peepso'),
		));

		wp_register_script('peepso-activitystream-js', PeepSo::get_asset('js/activitystream.min.js'),
			array('peepso', 'peepso-window'), PeepSoActivityStream::PLUGIN_VERSION, TRUE);
		wp_enqueue_script('peepso-activitystream-js');

		wp_register_script('peepso-dropdown', PeepSo::get_asset('js/dropdown.min.js'),
			array('peepso', 'peepso-activitystream-js'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_enqueue_script('peepso-dropdown');

		wp_register_script('peepso-resize', PeepSo::get_asset('js/jquery.autosize.min.js'),
			array('jquery'), PeepSoActivityStream::PLUGIN_VERSION, TRUE);
		wp_enqueue_script('peepso-resize');

		wp_enqueue_script('peepso-postbox');
		wp_enqueue_script('peepso-share');
		wp_enqueue_script('peepso-posttabs');
	}


	/*
	 * Sets up the page for viewing. The combination of page and exta information
	 * specifies which post's permalink to view.
	 * @param string $page The 'root' of the page, i.e. 'activity'
	 * @param string $extra Optional specifier of extra data, i.e. 'status/{permalink}'
	 */
	public function set_page($url_segments)
	{
        if(!$url_segments instanceof PeepSoUrlSegments) {
            $url_segments = new PeepSoUrlSegments();
        }

		$this->url_segments = $url_segments;

		global $wp_query;

		if ($wp_query->is_404) {
PeepSo::log('  ** a 404');
			$virt = new PeepSoVirtualPage($this->url_segments->get(0), $this->url_segments->get(1));
		}

		if ($this->url_segments->get(1)) {

			switch ($this->url_segments->get(1))
			{
			case 'status':
				$this->permalink = sanitize_key($this->url_segments->get(2));
PeepSo::log('  saving permalink [' . $this->permalink . ']');
				break;
			}
		}
	}

	/*
	 * Return the permalink stored in the ActivityShortcode that indicates what content to display
	 * @return string post_title value to be shown as activity
	 */
	public function get_permalink()
	{
		return ($this->permalink);
	}

	/**
	 * Returns TRUE or FALSE whether the current page is from a permalink.
	 * @return boolean
	 */
	public function is_permalink_page()
	{
		return (!is_null($this->permalink));
	}
}

// EOF

<?php

class PeepSoPostbox implements PeepSoAjaxCallback
{
	protected static $_instance = NULL;

	public $template_tags = array(
		'post_interactions',				// output controls for post interactions
		'post',								// AJAX callback method
		'before_postbox',					// called before PostBox HTML is output
		'after_postbox',					// called after PostBox HTML is output
		'postbox_tabs',
	);

	private function __construct()
	{
		add_filter('peepso_postbox_interactions', array(&$this, 'postbox_privacy_interaction'), 10);
		add_filter('peepso_postbox_interactions', array(&$this, 'postbox_status_interaction'), 90);
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}


	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/*
	 * Enqueue scripts used by the PostBox
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script('peepso-postbox');
		wp_enqueue_script('peepso-resize');

		$aData = array(
			'postsize_reached' => 
				sprintf(
					__('You can only enter up to %d characters', 'peepso'), 
					PeepSo::get_option('site_status_limit', 4000)
				)
		);
		wp_localize_script('peepso-postbox', 'peepsopostbox', $aData);
		// Fires when postbox is used.
		do_action('peepso_postbox_enqueue_scripts');
	}


	//// implementation of template tags

	/*
	 * Outputs post interaction UI elements
	 */
	public function post_interactions()
	{
		$inter = apply_filters('peepso_postbox_interactions', array());

		if (isset($inter['privacy'])) {
			$privacy = PeepSoPrivacy::get_instance();
			$privacy_settings = apply_filters('peepso_postbox_access_settings', $privacy->get_access_settings());

			$user_default_privacy = PeepSo::get_user_access(PeepSo::get_user_id());
			if (!isset($privacy_settings[$user_default_privacy]))	// fix #190
				$user_default_privacy = PeepSo::ACCESS_PUBLIC;		// check for invalid index and assume public

			$inter['privacy']['extra'] = sprintf('<input type="hidden" autocomplete="off" id="postbox_acc" name="postbox_acc" value="%d" />', $user_default_privacy);
			$inter['privacy']['icon'] = (isset($privacy_settings[$user_default_privacy])) ? $privacy_settings[$user_default_privacy]['icon'] : '';
			$inter['privacy']['label'] = (isset($privacy_settings[$user_default_privacy])) ? $privacy_settings[$user_default_privacy]['label'] : '';

			$privacy_option = '<li><a id="postbox-acc-%1$d" href="javascript:" data-option-value="%1$d"><i class="ps-icon-%2$s"></i><span>%3$s</span></a></li>';

			$inter['privacy']['extra'] .= '<ul class="ps-postbox-privacy ps-privacy-dropdown dropdown-menu" style="display: none;">';

			foreach ($privacy_settings as $value => $setting)
				$inter['privacy']['extra'] .= sprintf($privacy_option, $value, $setting['icon'], $setting['label']);

			$inter['privacy']['extra'] .= '</ul>';
		}

		$fOutput = FALSE;
		foreach ($inter as $key => $data) {
			echo '<li id="', $data['id'], '"';
			if (!empty($data['class']))
				echo ' class="', $data['class'], '"';
			echo '><div class="interaction-icon-wrapper">';
			if (!empty($data['click'])) {
				echo '<a class="pstd-secondary" onclick="', esc_js($data['click']), '" ';
				if (isset($data['title']))
					echo ' title="', esc_attr($data['title']), '"';
				echo '>', PHP_EOL;
			}
			if (isset($data['icon']))
				echo '<i class="ps-icon-', $data['icon'], '"></i>', PHP_EOL;
			else if (isset($data['icon_html']))
				echo $data['icon_html'];
			if (!empty($data['click']))
				echo '</a>', PHP_EOL;
			echo '</div>';

			if (isset($data['extra']))
				echo $data['extra'];

			echo '</li>';
			
			$fOutput = TRUE;
		}		

		if (!$fOutput)
			echo '<li>&nbsp;</li>';
	}

	/**
	 * This function inserts the privacy dropdown on the post box, keep it as a filter callback so that
	 * other addons can know what position to place their custom post types.
	 * @param array $interactions is the formated html code that get inserted in the postbox
	 */
	public function postbox_privacy_interaction($interactions)
	{
		$interactions['privacy'] = array(
			'id' => 'privacy-tab',
			'class' => 'ps-list-item',
			'click' => 'return;',
			'title' => __('Privacy settings for your post', 'peepso'),
		);

		return ($interactions);
	}

	/**
	 * This function inserts the status post type on the post box, keep it as a filter callback so that
	 * other addons can know what position to place their custom post types.
	 * @param array $interactions is the formated html code that get inserted in the postbox
	 */
	public function postbox_status_interaction($interactions)
	{
		$interactions['status'] = array(
			'icon' => 'pencil',
			'id' => 'status-post',
			'class' => 'ps-list-item',
			'click' => 'return;',
			'label' => '',
			'title' => __('Post a Status', 'peepsovideos')
		);

		return ($interactions);
	}

	/*
	 * Triggers action/hook points for add-ons to output content before the postbox
	 */
	public function before_postbox()
	{
		do_action('peepso_postbox_before');
	}


	/*
	 * Triggers action/hook points for add-ons to output content after the postbox
	 */
	public function after_postbox()
	{
		do_action('peepso_postbox_after');
	}

	/**
	 * Display available post box tabs
	 */
	public function postbox_tabs()
	{
		$tabs = apply_filters('peepso_postbox_tabs', array());

		foreach ($tabs as $id => $html) {
			echo '<div data-tab-id="', $id, '">';
			echo $html;
			echo '</div>';
		}
	}

	/**
	 * Performs a post operation, adding to a user's wall
	 * @param  PeepSoAjaxResponse $resp Instance of PeepSoAjaxResponse
	 */
	public function post(PeepSoAjaxResponse $resp)
	{
PeepSo::log(__METHOD__.'()');
		$input = new PeepSoInput();
		$content = $input->post_raw('content');			// post content
		$user_id = $input->post_int('id');				// current user's id
		$owner_id = $input->post_int('uid');			// user id of the wall being written on
		$access = $input->post_int('acc');
		$repost = $input->post_int('repost', NULL);

		if (0 === $owner_id)
			$owner_id = $user_id;
		$type = $input->post('type');					// type of post operation

		if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST, $user_id)) {
			$args = array(
				'content' => $content,
				'user_id' => $user_id,
				'target_user_id' => $owner_id,
				'type' => $type,
				'written' => 0,
			);

//PeepSo::log('  calling Activity::add_post()');
			$act = PeepSoActivity::get_instance();
			$extra = array();

			$extra['show_preview'] = $input->post_int('show_preview', 1);

			if ($access)
				$extra['act_access'] = $access;

			if (!is_null($repost))
				$extra['repost'] = $repost;

//PeepSo::log(__METHOD__.'() calling add_post() with ' . var_export($extra, TRUE));
			$res = $act->add_post($owner_id, $user_id, $content, $extra);

			if (FALSE !== $res) {
				$args['written'] = 1;
				$args['post_id'] = $res;
			}
//PeepSo::log('  after post: ' . var_export($args, TRUE));

			if (isset($args['written']) && 1 == $args['written']) {
				$resp->success(TRUE);

				// query the post that was just created
//				$act = PeepSoActivity::get_instance();
//				$act->set_user_id($user_id);
//				$act->set_owner_id($owner_id);
				$wpq = $act->get_post(intval($args['post_id']), $owner_id, $user_id);

				if ($act->has_posts()) { // ($wpq->have_posts()) {
					$act->next_post();

					ob_start();
					$act->show_post();
					$post_data = ob_get_clean();

					$resp->set('post_id', $args['post_id']);
					$resp->set('html', $post_data);

					if (NULL !== $repost)
						$resp->notice(__('This post was successfully shared.', 'peepso'));
					else
						$resp->notice(__('Post added.', 'peepso'));

				}
			} else {
				$resp->success(FALSE);
				$resp->error('Error in writing Activity Stream post');
			}
		} else {
			$resp->success(FALSE);
			$resp->error('Invalid user id ' . $user_id . '/' . $owner_id);
		}
	}

	/**
	 * Returns the media template for the given URL
	 * @param  PeepSoAjaxResponse $resp Instance of PeepSoAjaxResponse
	 */
	public function get_url_preview(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		// @TODO: verify if URL is a URL 
		$resp->success(TRUE);

		// get PeepSoActivity instance so we can make use of make_link()
		$peepso_activity = PeepSoActivity::get_instance();

		// use PeepSoActivity::make_link() to build the media array
		$peepso_activity->make_link(array($input->post('url')));
		$media = $peepso_activity->get_media();

		if (!isset($media['url']) || !isset($media['description'])) {
			$resp->success(FALSE);
			continue;
		}

		$media['target'] = '';
		$media['host'] = parse_url($media['url'], PHP_URL_HOST);

		$resp->set('html', PeepSoTemplate::exec_template('activity', 'url-preview', array('media' => $media), TRUE));
	}	
}

// EOF

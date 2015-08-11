<?php

class PeepSoProfile implements PeepSoAjaxCallback
{
	protected static $_instance = NULL;

	private $user_id = NULL;
	private $user = NULL;

	private $notifications = NULL;
	private $num_notifications = 0;
	private $note_data = array();
	private $message = NULL;

	private $blocked = NULL;
	private $num_blocked = 0;
	private $block_idx = 0;
	private $block_data = array();

	// list of allowed template tags
	public $template_tags = array(
		'after_edit_form',			// called after rendering the profile edit page
		'alerts_form_fields',		// output the email and notification alert form
		'avatar_image',				// display link for use with avatar
		'avatar_full',				// display link for full-sized avatar image
		'avatar_orig',				// display link for original avatar image
		'block_user',				// display the user id of the current blocked user
		'block_username',			// diplay blocked user's username
		'can_edit',					// check if current user can edit this profile
		'cover_photo',				// display URL for cover photo
		'cover_photo_position',		// display the current user's cover photo position percentage
		'dialogs',					// give add-ons a chance to output some dialog content
		'edit_form',				// output the edit form
		'edit_preferences',			// output the preferences form
		'edit_profile_link',		// the link to the profile edit page
		'error_message',			// error messages
		'has_avatar',				// TRUE if user has uploaded avatar
		'has_blocked',				// TRUE if current user has any blocked users
		'has_cover',				// TRUE if user has a cover photo
		'has_errors',				// TRUE if there were errors on the page
		'has_message',				// checks if there are any messages to be displayed
		'has_notifications',		// check for notifications
		'has_sidebar',				// checks if user has a sidebar
		'interactions',				// user interactions
		'is_current_user',			// TRUE if profile is for current user
		'next_blocked',				// retrieve the next blocked user
		'next_notification',		// retrieve the next notification
		'notification_age',			// output age of the current notification
		'notification_delete',		// deletes selected notification messages
		'notification_id',			// output id for the current notification
		'notification_link',		// output link to post for current notification
		'notification_message',		// output the notification message
		'notification_timestamp',	// output timestamp for the notification
		'notification_type',		// output type for the current notification
		'notification_user',		// obtain user name of sender of notification
		'num_alerts_fields',		// get the number of email and notification alert fields
		'num_blocked',				// get number of blocked users
		'num_notifications',		// get number of pending notifications
        'profile_actions',			// create UI actions
        'profile_segment_menu',			// create profile submenu
		'profile_likes',			// the number of likes on a user's profile
		'profile_message',			// Displays $message if there's any
		'profile_views',			// the number of views of a user's profile
		'show_blocked',				// show the current blocked user
		'show_notification',		// show the current notification
		'upload_size',				// max upload file size
		'user_activities',			// show HTML for user activities
		'user_bio',					// the user's bio
		'user_birthdate',			// the user's birth date
		'user_display_name',		// displays the user's name, accounting for access permissions
		'user_hasbio',				// check the user's bio
		'user_hasbirthdate',        // check the user's birth date  #264
		'user_hasgender',			// check the user's gender  #264
		'user_haswebsite',        	// check the user's web site  #308
		'user_gender',				// the user's gender
		'user_id',					// the user's id
		'user_last_online',			// the date the user last logged in
		'user_link',				// link to user's profile
		'user_name',				// user name
		'user_profile_fields',		// fields to be displayed on the about section
		'user_registered',			// date the user registered
		'user_website',				// user's web site
	);

	private function __construct()
	{
		add_filter('peepso_postbox_access_settings', array(&$this, 'postbox_access_settings'), 10, 1);
		add_filter('peepso_profile_edit_form_fields', array(&$this, 'get_edit_form_fields'));
		// Hook this late so other addons can add fields before the submit button
		add_filter('peepso_profile_edit_form_fields', array(&$this, 'get_edit_form_submit'), 90);
	}


	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/**
	 * Returns whether or not viewing a profile page
	 * @return boolean
	 */
	public static function in_profile_page()
	{
		return (self::$_instance !== NULL);
	}

	/* Sets the user id who's profile will be displayed
	 * @param int $user The ID of the user of FALSE if user not found/error
	 */
	public function set_user_id($user)
	{
		$this->user_id = $user;
		if (FALSE !== $user)
			$this->user = new PeepSoUser($this->user_id);
	}
public function profile_segment_menu($args)
{
    $links = array();
    $links = apply_filters('peepso_profile_segment_menu_links', $links);

    $args['links'] = $links;
    return PeepSoTemplate::exec_template('profile','profile-menu', $args);
}

	/* return instance of WP_User object for the profile being displayed
	 * @return WP_User reference to WP_User instance being used
	 */
	private function get_wp_user()
	{
		if (NULL === $this->user) {
			if (NULL === $this->user_id)
				$this->user_id = PeepSo::get_user_id();
			$this->user = new PeepSoUser($this->user_id);
		}
		return ($this->user);
	}


	/* return propeties for the profile page
	 * @param string $prop The name of the property to return
	 * @return mixed The value of the property
	 */
	public function get_prop($prop)
	{
		$ret = '';

		switch ($prop)
		{
		case 'can_edit':
			$ret = '0';
			// check if user is an admin, or the owner of the current profile
			if (PeepSo::get_user_id() === $this->user_id || PeepSo::is_admin())
				$ret = '1';
			break;
		case 'has_sidebar':
			// TODO: implement based on user's settings once sidebar apps are available
			$ret = '0';
			break;
		case 'user_id':
			$ret = $this->user_id;
			break;
		case 'num_blocked':
			$ret = $this->num_blocked();
			break;
		case 'num_alerts_fields':
			$ret = $this->num_alerts_fields();
			break;
		}

		return ($ret);
	}

	//// ajax callback functions

	/*
	 * Called from AjaxHandler when an image crop request is performed
	 */
	public function crop(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		$user_id = $input->get_int('u');
		$this->set_user_id($user_id);

		$x = $input->get_int('x');
		$y = $input->get_int('y');
		$x2 = $input->get_int('x2');
		$y2 = $input->get_int('y2');
		$width = $input->get_int('width');
		$height = $input->get_int('height');
		$tmp = $input->get_int('tmp');

		if (wp_verify_nonce($input->get('_wpnonce'), 'profile-photo') && $this->can_edit()) {
			$user = new PeepSoUser($user_id);

			// re-crop full  avatar image
			$src_file = $user->get_image_dir() . 'avatar-orig' . ($tmp ? '-tmp' : '') . '.jpg';
			$dest_file = $user->get_image_dir() . 'avatar-full' . ($tmp ? '-tmp' : '') . '.jpg';

			$si = new PeepSoSimpleImage();
			$si->load($src_file);
			// Resize image as edited on the screen, we do this because getting x and y coordinates
			// are unreliable when we are cropping from the edit avatar page; the dimensions on the edit
			// avatar page is not the same as the original image dimensions.
			$si->resize($width, $height);

			$new_image = imagecreatetruecolor(PeepSoUser::IMAGE_WIDTH, PeepSoUser::IMAGE_WIDTH);
			imagecopyresampled($new_image, $si->image,
				0, 0, $x, $y,
				PeepSoUser::IMAGE_WIDTH, PeepSoUser::IMAGE_WIDTH, $x2 - $x, $y2 - $y);
			imagejpeg($new_image, $dest_file, 75);

			// re-crop thumbnailavatar image
			$dest_file = $user->get_image_dir() . 'avatar' . ($tmp ? '-tmp' : '') . '.jpg';

			// create a new instance of PeepSoSimpleImage - just in case
			$_si = new PeepSoSimpleImage();
			$_si->load($src_file);
			$new_image = imagecreatetruecolor(PeepSoUser::THUMB_WIDTH, PeepSoUser::THUMB_WIDTH);
			imagecopyresampled($new_image, $si->image, // Resize from cropeed image "$si"
				0, 0, $x, $y,
				PeepSoUser::THUMB_WIDTH, PeepSoUser::THUMB_WIDTH, $x2 - $x, $y2 - $y);
			imagejpeg($new_image, $dest_file, 75);

//imagecopyresampled ( resource $dst_image , resource $src_image ,
//  int $dst_x , int $dst_y , int $src_x , int $src_y ,
//  int $dst_w , int $dst_h , int $src_w , int $src_h )

			$image_url = $tmp ? $user->get_tmp_avatar() : $user->get_avatar();
			$resp->set('image_url', $image_url);
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
			$resp->error(__('Invalid access', 'peepso'));
		}
	}

	/*
	 * Called from AjaxHandler when an avatar upload request is performed
	 */
	public function upload_avatar(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		if (FALSE === wp_verify_nonce($input->post('_wpnonce'), 'profile-photo')) {
			$resp->success(FALSE);
			$resp->error(__('Request could not be verified.', 'peepso'));
		} else {
			$user = new PeepSoUser($input->post_int('user_id'));
			$this->set_user_id($user->get_id());
			$this->user = $user;

			$shortcode = PeepSoProfileShortcode::get_instance();
			$shortcode->set_page('profile');
			$shortcode->init();

			$success = (FALSE === $shortcode->has_error());
			$resp->success($success);

			if (FALSE === $success) {
				$resp->error($shortcode->get_error_message());
			} else {
				$image_url = $user->get_tmp_avatar();
				$full_image_url = $user->get_tmp_avatar(TRUE);
				$orig_image_url = str_replace('-full', '-orig', $full_image_url);
				$resp->set('image_url', $image_url);
				$resp->set('orig_image_url', $orig_image_url);
				$resp->set('html', PeepSoTemplate::exec_template('profile', 'dialog-profile-avatar', NULL, TRUE));
			}
		}
	}

	/*
	 * Called from AjaxHandler when an avatar upload is finalized
	 */
	public function confirm_avatar(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		if (FALSE === wp_verify_nonce($input->post('_wpnonce'), 'profile-photo')) {
			$resp->success(FALSE);
			$resp->error(__('Request could not be verified.', 'peepso'));
		} else {
			$user = new PeepSoUser($input->post_int('user_id'));
			$this->set_user_id($user->get_id());
			$this->user = $user;

			$user->finalize_move_avatar_file();

			$resp->success(TRUE);
		}
	}

	/*
	 * Called from AjaxHandler when a cover photo upload request is performed
	 * @param object PeepSoAjaxResponse $resp
	 */
	public function upload_cover(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		if (FALSE === wp_verify_nonce($input->post('_wpnonce'), 'profile-photo')) {
			$resp->success(FALSE);
			$resp->error(__('Request could not be verified.', 'peepso'));
		} else {
			// can-edit is called on PeepSoProfileShortcode::save_cover_form()
			$user = new PeepSoUser($input->post_int('user_id'));
			$this->set_user_id($user->get_id());
			$this->user = $user;
			$shortcode = PeepSoProfileShortcode::get_instance();
			$shortcode->set_page('profile');
			$shortcode->init();

			$success = (FALSE === $shortcode->has_error());
			$resp->success($success);

			if (FALSE === $success) {
				$resp->error($shortcode->get_error_message());
			} else {
				$user = new PeepSoUser($input->post_int('user_id'));
				$this->set_user_id($user->get_id());
				$this->user = $user;

				$resp->set('image_url', $user->get_coverphoto());
				$resp->set('html', PeepSoTemplate::exec_template('profile', 'dialog-profile-cover', NULL, TRUE));
			}
		}
	}

	/*
	 * Called from AjaxHandler when a cover photo repositoin request is performed
	 */
	public function reposition_cover(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		$user_id = $input->post_int('user_id');

		if (FALSE === wp_verify_nonce($input->post('_wpnonce'), 'profile-photo')) {
			$resp->success(FALSE);
			$resp->error(__('Request could not be verified.', 'peepso'));
		} else {
			$user = new PeepSoUser($input->post_int('user_id'));
			$this->set_user_id($user->get_id());
			$this->user = $user;

			if ($this->can_edit()) {
				$x = $input->post_int('x', 0);
				$y = $input->post_int('y', 0);
				update_user_meta($user_id, 'peepso_cover_position_x', $x);
				update_user_meta($user_id, 'peepso_cover_position_y', $y);

				$resp->notice(__('Changes saved.', 'peepso'));
				$resp->success(TRUE);
			} else {
				$resp->success(FALSE);
				$resp->error(__('You do not have enough permissions.', 'peepso'));
			}
		}
	}

	/*
	 * Performs delete operation on the current user's profile information
	 * @param PeepSoAjaxResponse $resp The response object
	 */
	public function delete_profile(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$user_id = $input->post_int('uid');

		// intentinoally leaving the is_admin() check off. Admins can delete from within the console.
		if ($user_id === PeepSo::get_user_id()) {
			require_once(ABSPATH.'wp-admin/includes/user.php');
			$user = new PeepSoUser();

			$user->delete_data($user_id);
			wp_delete_user($user_id);
			wp_logout();					// log the user out

			$resp->set('url', PeepSo::get_page('activity'));
			$resp->success(1);
		} else {
			$resp->notice(__('You do not have permission to do that.', 'peepso'));
			$resp->success(FALSE);
		}
	}

	/*
	 * AJAX callback for like actions
	 * @param PeepSoAjaxResponse $resp The AJAX response object
	 */
	public function like(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$like_id = $input->post_int('likeid');		// id of item being "liked"
		$user_id = $input->post_int('uid');			// id of user doing the "like"

		$this->set_user_id($like_id);

		if (!PeepSo::check_permissions($like_id, PeepSo::PERM_PROFILE_LIKE, $user_id)) {
			new PeepSoError(sprintf(__('User %1$d is not allowed to Like user %2$d Profile.', 'peepso'), $user_id, $like_id));
			return (FALSE);
		}
		$peepso_like = new PeepSoLike();

		if (FALSE === $peepso_like->user_liked($like_id, PeepSo::MODULE_ID, $user_id)) {
			$peepso_like->add_like($like_id, PeepSo::MODULE_ID, $user_id);
			if ($like_id !== $user_id) {
				$user_like = new PeepSoUser($like_id);
				$user = new PeepSoUser($user_id);
				$data = array(
					'permalink' => PeepSo::get_page('profile') . '?notifications',
				);
				$data = array_merge($data, $user->get_template_fields('from'), $user_like->get_template_fields('user'));
				PeepSoMailQueue::add_message($like_id, $data, sprintf(__('%s liked your profile', 'peepso'), $user_like->get_username()), 'like_profile', 'profile_like', PeepSo::MODULE_ID);

				$peepso_notifications = new PeepSoNotifications();
				$peepso_notifications->add_notification($user_id, $like_id, __('likes your profile', 'peepso'), 'profile_like', PeepSo::MODULE_ID);
			}
		} else {
			$peepso_like->remove_like($like_id, PeepSo::MODULE_ID, $user_id);
		}

		$resp->success(TRUE);
		$resp->set('like_count', $peepso_like->get_like_count($like_id, PeepSo::MODULE_ID));

		ob_start();
		$this->interactions();
		$resp->set('html', ob_get_clean());
	}


	//// implementation of template tags

	/*
	 * Check if current user can edit the profile user's informaiton
	 */
	public function can_edit()
	{
		// check if user is an admin, or the owner of the current profile
		if (PeepSo::get_user_id() === $this->user_id || PeepSo::is_admin())
			return (TRUE);
		return (FALSE);
	}


	/*
	 * Obtain an href to a user's profile image
	 * @return string href value to the user's profile image
	 */
	public function avatar_image()
	{
		echo PeepSo::get_avatar($this->user_id);
	}


	/*
	 * Obtain href for user's full sized avatar image
	 *
	 */
	public function avatar_full()
	{
		$this->get_wp_user();
		$avatar = $this->user->get_avatar(TRUE);
		echo $avatar;
	}

	/**
	 * Display the user's original avatar
	 */
	public function avatar_orig()
	{
		$this->get_wp_user();
		$avatar = $this->user->get_avatar(TRUE);
		$avatar = str_replace('-full.jpg', '-orig.jpg', $avatar);
		echo $avatar;
	}

	/**
	 * Checks whether a user has an avatar
	 * @return boolean
	 */
	public function has_avatar()
	{
		$this->get_wp_user();
		$avatar = $this->user->get_avatar();
		if (strpos($avatar, '/peepso/users/') !== FALSE)
			return (TRUE);
		return (FALSE);
	}

	/*
	 * Check if user has uploaded a custom cover photo
	 * @return Boolean TRUE if custom cover photo, otherwise FALSE
	 */
	public function has_cover()
	{
		$this->get_wp_user();
		$cover = $this->user->get_coverphoto();

		if (FALSE !== stripos($cover, 'peepso/users/'))
			return (TRUE);
		return (FALSE);
	}


	/*
	 * Determine if user has any pending notifications
	 */
	public function has_notifications()
	{
		return (0 !== $this->num_notifications());
	}


	/*
	 * Return number of pending notifications
	 * @return int Number of pending notifications
	 */
	public function num_notifications()
	{
		if (0 === $this->num_notifications) {
			$note = new PeepSoNotifications();
			$this->num_notifications = $note->get_count_for_user(PeepSo::get_user_id());
		}
		return ($this->num_notifications);
	}


	/*
	 * Checks for any remaining notifications and sets up current notification data
	 * for showing with 'show_notification' template tag.
	 * @return Boolean TRUE if more notifications; otherwise FALSE
	 */
	public function next_notification($limit = 40, $offset = 0)
	{
		if (NULL === $this->notifications) {
			$note = new PeepSoNotifications();
			$this->notifications = $note->get_by_user(PeepSo::get_user_id(), $limit, $offset);
			$this->note_idx = 0;
			$note->mark_as_read(PeepSo::get_user_id());
		}

		if (0 !== count($this->notifications)) {
			if ($this->note_idx >= count($this->notifications)) {
				return (FALSE);											// ran out; exit loop
			} else {
				$this->note_data = get_object_vars($this->notifications[$this->note_idx]);
				++$this->note_idx;
				return (TRUE);
			}
		} else {
			return (FALSE);
		}
	}


	/*
	 * Outputs notification content based on template
	 */
	public function show_notification()
	{
		PeepSoTemplate::exec_template('profile', 'notification', $this->note_data);
	}


	/*
	 * Display notifications age in human readable form
	 */
	public function notification_age()
	{
		$post_date = mysql2date('U', $this->note_data['not_timestamp'], FALSE);
		$curr_date = date('U', current_time('timestamp', 0));

		echo '<span title="', esc_attr($this->note_data['not_timestamp'], ' ', $this->note_data['not_timestamp']), '">';
		echo PeepSoTemplate::time_elapsed($post_date, $curr_date), '</span>';
	}


	/*
	 * Performs delete operation on notification messages
	 * @param PeepSoAjaxResponse $resp The AJAX response object
	 */
	public function notification_delete(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		if ('' === ($delete_values = $input->post('delete'))) {
			$resp->success(FALSE);
			$resp->error(__('Please select at least one notification to delete.', 'peepso'));
		} else {
			$note_ids = explode(',', $delete_values);
			$aIds = array();

			foreach ($note_ids as $id) {
				$id = intval($id);
				if (!in_array($id, $aIds))
					$aIds[] = $id;
			}

			if (0 !== count($aIds)) {
				$note = new PeepSoNotifications();
				$note->delete_by_id($aIds);
			}

			$resp->success(1);
		}
	}


	/*
	 * Displays the notification record's ID value
	 */
	public function notification_id()
	{
		echo $this->note_data['not_id'];
	}


	/*
	 * Displays the notification record's "from" user id
	 */
	public function notification_user()
	{
		return ($this->note_data['not_from_user_id']);
	}


	/*
	 * Displays the link for the notification's content
	 */
	public function notification_link()
	{
		if (0 === intval($this->note_data['not_external_id']))
			return;

		$link = PeepSo::get_page('activity') . 'status/' . $this->note_data['post_title'] . '/';
		$link = apply_filters('peepso_profile_notification_link', $link, $this->note_data['not_module_id']);

		$content = apply_filters('the_content', $this->note_data['post_content'], $this->note_data);
		$main_notification_content = '<a href="%s">' . substr(strip_tags($content), 0, 80) . '</a>';

		if ('user_comment' === $this->note_data['not_type']) {
			$activities = PeepSoActivity::get_instance();

			$not_activity = $activities->get_activity_data($this->note_data['not_external_id'], $this->note_data['not_module_id']);

			$parent_activity = $activities->get_activity_data($not_activity->act_comment_object_id, $not_activity->act_comment_module_id);
			if (is_object($parent_activity)) {
				$parent_post = $activities->get_activity_post($parent_activity->act_id);
				$parent_id = $parent_post->act_external_id;

				$parent_link = PeepSo::get_page('activity') . 'status/' . $parent_post->post_title . '/';

				// TODO: format of the message needs to be improved in order to accommodate translation better
				// TODO: i.e.: printf('%1$s on %2$s%3$s%4$s',
				//		$notification_content,
				//		'<a href...'
				//		$post_desc
				//		'</a>'
				printf($main_notification_content, $parent_link . '?t=' . time() . '#comment|' . $this->note_data['not_external_id'] . '|' . $parent_activity->act_id);

				echo ' ', __('on', 'peepso'), ' ';

				echo '<a href="', $parent_link, '">';
				if (intval($parent_post->post_author) === PeepSo::get_user_id())
					echo __('your post', 'peepso');
				else
					echo substr(strip_tags($parent_post->post_content), 0, 80);
				echo '</a>', PHP_EOL;
			}
		} else if (!empty($main_notification_content))
			echo ' : ', sprintf($main_notification_content, $link);
	}


	/*
	 * Displays the notification message
	 */
	public function notification_message()
	{
		echo $this->note_data['not_message'];
	}


	/*
	 * Displays the notification record's timestamp value
	 */
	public function notification_timestamp()
	{
		echo $this->note_data['not_timestamp'];
	}


	/*
	 * Displays the notification record's type
	 */
	public function notification_type()
	{
		echo $this->note_data['not_type'];
	}


	/*
	 * Determine if user has anyone in their blocked list
	 * @returns Boolean TRUE if there are blocked users
	 */
	public function has_blocked()
	{
		return (0 !== $this->num_blocked());
	}


	/*
	 * Get number of blocked users
	 * @returns int Number of blocked users
	 */
	public function num_blocked()
	{
		if (0 === $this->num_blocked) {
			$blk = new PeepSoBlockUsers();
			$this->num_blocked = $blk->get_count_for_user(PeepSo::get_user_id());
		}
PeepSo::log(__METHOD__.'() num_blocked=' . $this->num_blocked);
		return ($this->num_blocked);
	}


	/*
	 * Get the number of email and notification alert fields
	 * @returns int Number of fields
	 */
	public function num_alerts_fields()
	{
		return (count($this->get_available_alerts()));
	}


	/*
	 * Checks for any remaining blocked users and sets up current blocked user ata
	 * for showing with 'show_blocked' template tag.
	 * @return Boolean TRUE if more blocked users; otherwise FALSE
	 */
	public function next_blocked()
	{
		if (NULL === $this->blocked) {
			$blk = new PeepSoBlockUsers();
			$this->blocked = $blk->get_by_user(PeepSo::get_user_id());
			$this->block_idx = 0;
		}

		if (0 !== count($this->blocked)) {
			if ($this->block_idx >= count($this->blocked)) {
				$this->blocked = NULL;
				return (FALSE);											// ran out; exit loop
			} else {
				$this->block_data = get_object_vars($this->blocked[$this->block_idx]);
				++$this->block_idx;
				return (TRUE);
			}
		} else {
			return (FALSE);
		}
	}


	/*
	 * Outputs blocked user content based on template
	 */
	public function show_blocked()
	{
		PeepSoTemplate::exec_template('profile', 'blocked', $this->block_data);
	}


	/*
	 * Gets the user id of the blocked user
	 * @returns int User id of blocked user
	 */
	public function block_user()
	{
		return ($this->block_data['blk_blocked_id']);
	}


	/*
	 * Outputs user name of blocked user
	 */
	// TODO: do we need this method? We're removing the accessibility for first/last name and using the config setting to decide what to display.
	public function block_username()
	{
		$user = new PeepSoUser($this->block_data['blk_blocked_id']);
		echo $user->get_display_name();
	}


	/*
	 * AJAX callback for deleting blocked users
	 * @param PeepSoAjaxResponse $resp The response object
	 * @returns PeepSoAjaxResponse object
	 */
	public function block_delete(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$block_ids = explode(',', $input->post('delete'));
		$aIds = array();

		foreach ($block_ids as $id) {
			$id = intval($id);
			if (!in_array($id, $aIds))
					$aIds[] = $id;
		}

		if (0 != count($aIds)) {
			$blk = new PeepSoBlockUsers();
			$blk->delete_by_id($aIds);
		}

		$resp->success(1);
	}


	/*
	 * Obtain an href to the user's custom cover photo, or one based on gender
	 * @return string href value to the image
	 */
	public function cover_photo()
	{
		$this->get_wp_user();
		$cover = $this->user->get_coverphoto();
		echo $cover;
	}

	/**
	 * Display the current user's cover photo position percentage.
	 */
	public function cover_photo_position()
	{
		$this->get_wp_user();

		$x = get_user_meta($this->user_id, 'peepso_cover_position_x', TRUE);
		$y = get_user_meta($this->user_id, 'peepso_cover_position_y', TRUE);

		if ($x)
			echo 'top: ' . $x . '%;';
		else
			echo 'top: 0;';

		if ($y)
			echo 'left: ' . $y . '%;';
		else
			echo 'left: 0;';
	}

	/**
	 * Deletes a user's cover photo
	 */
	public function remove_cover_photo(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$this->set_user_id($input->post_int('user_id'));

		if ($this->can_edit() && wp_verify_nonce($input->post('_wpnonce', ''), 'cover-photo')) {
			$this->get_wp_user();

			$resp->success($this->user->delete_cover_photo());
		} else {
			$resp->success(FALSE);
		}
	}

	/**
	 * Deletes a user's avatar
	 */
	public function remove_avatar(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$this->set_user_id($input->post_int('user_id'));

		if ($this->can_edit() && wp_verify_nonce($input->post('_wpnonce', ''), 'profile-photo')) {
			$this->get_wp_user();
			$this->user->delete_avatar();
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
		}
	}


	/*
	 * constructs the profile edit form
	 */
	public function edit_form()
	{
		$fields = apply_filters('peepso_profile_edit_form_fields', array(), $this->user_id);

		$form = array(
			'container' => array(
				'element' => 'div',
				'class' => 'ps-form-row',
			),
			'fieldcontainer' => array(
				'element' => 'div',
				'class' => 'ps-form-group',
			),
			'form' => array(
				'name' => 'profile-edit',
				'action' => PeepSo::get_page('profile') . '?edit',
				'method' => 'POST',
				'class' => 'community-form-validate',
				'extra' => 'autocomplete="off"',
			),
			'fields' => $fields,
		);

		$peepso_form = PeepSoForm::get_instance();
		$peepso_form->render(apply_filters('peepso_profile_edit_form', $form));
	}

	/**
	 * Return the fields used in the profile edit page
	 * @return array
	 */
	public function get_edit_form_fields()
	{
		$user = $this->user;
		if (!isset($user))
			$user = $this->user = new PeepSoUser(PeepSo::get_user_id());

		$fields = array(
			'user_nicename' => array(
				'section' => __('Basic Information', 'peepso'),
				'label' => __('User Name', 'peepso'),
				'descript' => __('Enter your user name', 'peepso'),
				'value' => $user->get_username(),
				'required' => 1,
				'type' => 'text',
				'validation' => array(
					'alphanumeric',
					'required',
					'minlen:' . PeepSoUser::USERNAME_MINLEN,
					'maxlen:' . PeepSoUser::USERNAME_MAXLEN,
					'custom'
				),
				'validation_options' => array(
					'error_message' => __('That name is already in use by someone else.', 'peepso'),
					'function' => array($this, 'check_username_change')
				)
			),
			'first_name' => array(
				'section' => __('Basic Information', 'peepso'),
				'label' => __('First Name', 'peepso'),
				'descript' => __('Enter your first name', 'peepso'),
				'value' => $user->get_firstname(FALSE),
				'required' => 1,
				'type' => 'text',
				'validation' => array(
					'name-utf8',
					'minlen:' . PeepSoUser::FIRSTNAME_MINLEN,
					'maxlen:' . PeepSoUser::FIRSTNAME_MAXLEN
				),
				//'access' => $user->get_accessibility('first_name'),
			),
			'last_name' => array(
				'label' => __('Last Name', 'peepso'),
				'descript' => __('Enter your last name', 'peepso'),
				'value' => $user->get_lastname(FALSE),
				'required' => 1,
				'type' => 'text',
				'validation' => array(
					'name-utf8',
					'minlen:' . PeepSoUser::LASTNAME_MINLEN,
					'maxlen:' . PeepSoUser::LASTNAME_MAXLEN
				),
				//'access' => $user->get_accessibility('last_name'),
			),
			'gender' => array(
				'label' => __('Gender', 'peepso'),
				'descript' => __('Select gender', 'peepso'),
				'required' => 1,
				'class' => 'ps-name-tips tipRight',
				'type' => 'select',
				'options' => array('' => __('Select Below', 'peepso'), 'm' => __('Male', 'peepso'), 'f' => __('Female', 'peepso')),
				'value' => $user->get_gender(FALSE),
				'access' => $user->get_accessibility('gender'),
			),
			'birthdate' => array(
				'label' => __('Birthdate', 'peepso'),
				'descript' => __('Enter your date of birth so other users can know when to wish you happy birthday', 'peepso'),
				'required' => 1,
				'class' => 'ps-name-tips tipRight',
				'type' => 'datepicker',
				'options' => array(),
				'value' => $user->get_birthdate(FALSE), // '1964-02-29',
				'access' => $user->get_accessibility('birthdate'),
				'validation' => array('past', 'date')
			),
			'description' => array(
				'label' => __('About Me', 'peepso'),
				'descript' => __('Tell us more about yourself', 'peepso'),
//				'required' => 1,
				'class' => 'ps-name-tips tipRight',
				'type' => 'textarea',
				'raw' => TRUE,
				'value' => $user->get_description(FALSE),
				'access' => $user->get_accessibility('description'),
			),
			'user_url' => array(
				'label' => __('Web site', 'peepso'),
				'descript' => __('Enter your web site address', 'peepso'),
				'class' => '',
				'type' => 'text',
				'value' => $user->get_userurl(FALSE),
				'access' => $user->get_accessibility('user_url'),
				'validation' => array('website')
			),
			'change_password' => array(
				'label' => __('Change Password', 'peepso'),
				'descript' => __('Enter password to change', 'peepso'),
				'class' => '',
				'type' => 'password',
				'validation' => array('password'),
				/*'validation_options' => array(
					'error_message' => __('Passwords mismatched.', 'peepso'),
					'function' => array($this, 'check_password_change'),
				),*/
			),
			'verify_password' => array(
				'label' => __('Verify Password', 'peepso'),
				'descript' => __('Enter password to verify', 'peepso'),
				'class' => '',
				'type' => 'password',
			),
			'user_id' => array(
				'type' => 'hidden',
				'value' => $this->user_id,
			),
			'task' => array(
				'type' => 'hidden',
				'value' => 'profile_edit_save',
			),
			'-form-id' => array(
				'type' => 'hidden',
				'value' => wp_create_nonce('profile-edit-form'),
			),
			'authkey' => array(
				'type' => 'hidden',
				'value' => '',
			),
		);

		return ($fields);
	}

	/**
	 * Adds a form submit button to existing fields for save profile
	 * @param array $fields List of fields
	 * @return array $fields List of fields with submit button
	 */
	public function get_edit_form_submit($fields)
	{
		$fields['submit'] = array(
			'label' => __('Save', 'peepso'),
			'class' => 'ps-btn-primary',
			'click' => 'submitbutton(\'frmSaveProfile\'); return false;',
			'type' => 'submit',
		);

		return ($fields);
	}

	/**
	 * Set validation for change_password field
	 * @param boolean $valid Whether or not the form passed the initial validation
	 * @param object $form Instance of PeepSoForm
	 * @return boolean
	 */
	public function change_password_validate_after($valid, PeepSoForm $form)
	{
		$field = &$form->fields['change_password'];

		$input = new PeepSoInput();
		$change_password = $input->post('change_password');
		$verify_password = $input->post('verify_password');

		if ($valid && $change_password) {
			if ($change_password === $verify_password) {
				$valid = TRUE;
				$field['valid'] = TRUE;
			} else {
				$valid = FALSE;
				$field['valid'] = FALSE;
				$field['error_messages'][] = __('Please enter the same password in the verify password field.', 'peepso');
			}
		}

		return $valid;
	}

	/**
	 * Return the fields used in the profile edit page
	 * @return array
	 */
	public function edit_preferences()
	{
		$fields = array(
			'feeds_to_show' => array(
				'section' => __('Preferences', 'peepso'),
				'label' => __('Activity Stream Feeds', 'peepso'),
				'descript' => __('Sets the number of activities to be displayed on your profile.', 'peepso'),
				'value' => $this->user->get_num_feeds_to_show(),
				'validation' => array(/*'required',*/ 'positive', 'int', 'minval:5', 'maxval:100'), //#258
				'type' => 'text',
			),
			'profile_likes' => array(
				'label' => __('Profile Likes', 'peepso'),
				'descript' => __('Allow others to "like" my profile', 'peepso'),
				'value' => $this->user->is_profile_likable(),
				'type' => 'yesno_switch',
			),
			'usr_profile_acc' => array(
				'label' => __('Profile Privacy', 'peepso'),
				'value' => $this->user->get_accessibility('profile'),
				'type' => 'access-profile',
				'validation' => array(/*'required'*/),
			),
			/*'profile_url' => array(
				'label' => __('Your Profile URL', 'peepso'),
				'value' => PeepSo::get_page('profile') . $this->user->get_username(FALSE) . '/',
				'type' => 'text',
				'extra' => 'disabled readonly',
			),*/
			'user_id' => array(
				'type' => 'hidden',
				'value' => $this->user_id,
			)
		);

		if (1 === intval(PeepSo::get_option('system_override_name', 0)))
			$fields['profile_display_name_as'] = array(
				'label' => __('Display My Name as', 'peepso'),
				'type' => 'select',
				'validation' => array('required'),
				'options' => array(
					'real_name' => __('My Real Name', 'peepso'),
					'username' => __('My Username', 'peepso'),
				),
				'value' => $this->user->get_display_name_as(),
			);

		$form = array(
			'name' => 'profile-edit',
			'action' => PeepSo::get_page('profile') . '?pref',
			'method' => 'POST',
			'class' => 'cform community-form-validate',
			'extra' => 'autocomplete="off"',
		);

		$fields = apply_filters('peepso_profile_edit_preferences_fields', $fields);

		$fields = array_merge($fields,
			array(
				'task' => array(
					'type' => 'hidden',
					'value' => 'profile_preferences_save',
				),
				'-form-id' => array(
					'type' => 'hidden',
					'value' => wp_create_nonce('profile-edit-preferences-form'),
				),
				'authkey' => array(
					'type' => 'hidden',
					'value' => '',
				),
				'submit' => array(

					'label' => __('Save', 'peepso'),
					'class' => 'ps-btn ps-btn-primary',
					'click' => 'submitbutton(\'frmSaveProfile\'); return false;',
					'type' => 'submit',
				)
			)
		);

		$form = array(
			'container' => array(
				'element' => 'div',
				'class' => 'ps-form-row',
			),
			'fieldcontainer' => array(
				'element' => 'div',
				'class' => 'ps-form-group',
			),
			'form' => $form,
			'fields' => $fields,
		);

		return ($form);
	}

	/*
	 * output actions that can be performed on a profile page
	 * @return string HTML markup with actions
	 */
	public function profile_actions()
	{
		$act = array();

		if (is_user_logged_in()) {
			if ($this->user_id != PeepSo::get_user_id()) {
				$blk = new PeepSoBlockUsers();
				if ($blk->is_user_blocking(PeepSo::get_user_id(), $this->user_id)) {
					$act['block'] = array(
						'label' => __('Unblock User', 'peepso'),
						'class' => 'ps-btn ps-btn-small',
						'title' => __('Allow this user to see all of your activities', 'peepso'),
						'click' => 'profile.unblock_user(' . $this->user_id . ', this); return false;',
					);
				} else {
					$act['block'] = array(
						'label' => __('Block User', 'peepso'),
						'class' => 'ps-btn ps-btn-small',
						'title' => __('This user will be blocked from all of your activities', 'peepso'),
						'click' => 'profile.block_user(' . $this->user_id . ', this); return false;',
					);
				}
			}

			$act = apply_filters('peepso_profile_actions', $act, $this->user_id);
		}

		foreach ($act as $name => $data) {
			echo '<a href="#" ';
			if (isset($data['class']))
				echo ' class="', esc_attr($data['class']), '" ';
			if (isset($data['title']))
				echo ' title="', esc_attr($data['title']), '" ';
			if (isset($data['click']))
				echo ' onclick="', esc_js($data['click']), '" ';
			echo '><span>', $data['label'], '</span> <img style="display:none" src="', PeepSo::get_asset('images/ajax-loader.gif'), '"></a>', PHP_EOL;
		}
	}

	/*
	 * Output the number of likes on a user's profile
	 */
	public function profile_likes()
	{
		$peepso_likes = new PeepSoLike();
		echo $peepso_likes->get_profile_likes($this->user_id);
	}

	/*
	 * Output the number of views of a user's profile page
	 */
	public function profile_views()
	{
		$user = $this->get_wp_user();
		$views = $user->get_view_count();
		echo $views;
//		echo PeepSoViewLog::get_views($this->user_id, PeepSo::MODULE_ID);
	}

	/* display user's biographical data
	 */
	public function user_bio()
	{
		$this->get_wp_user();
		echo htmlspecialchars($this->user->get_description());
	}

	// #264
	/* Check the user's birthdate, if exist return true else return false
	 */
	public function user_hasbirthdate()
	{
		$this->get_wp_user();
		$dt = $this->user->get_birthdate();
		if (FALSE === $dt)
			return (FALSE);
		return (!empty($dt) && '0000-00-00' !== $dt);
	}

	/* Output the user's birthdate
	 */
	public function user_birthdate()
	{
		$this->get_wp_user();
		$dt = $this->user->get_birthdate();
		$ret = mysql2date(get_option('date_format'), $dt);
		echo $ret;
	}

	// #264
	/*
	 * Check the user's gender, if exist return true else return false
	 */
	public function user_hasgender()
	{
		$this->get_wp_user();
		$ret = $this->user->get_gender();
		if (FALSE === $ret)
			return (FALSE);
		return (TRUE);
	}

	/*
	 * Output the user's gender
	 */
	public function user_gender()
	{
		$this->get_wp_user();
		$ret = $this->user->get_gender();
		switch ($ret)
		{
		case 'm':		_e('Male', 'peepso');		break;
		case 'f':		_e('Female', 'pepso');		break;
		default:		_e('Unknown', 'peepso');	break;
		}
	}

	/*
	 * Output the user's id number
	 */
	public function user_id()
	{
		echo $this->user_id;
	}

	/*
	 * output date user was last online
	 */
	public function user_last_online()
	{
		$this->get_wp_user();
		echo $this->user->get_last_online();
	}

	/*
	 * Output a link to the user's profile page
	 */
	public function user_link()
	{
		$this->get_wp_user();
		$ret = PeepSo::get_page('profile') . $this->user->get_username();
		$ret = apply_filters('peepso_username_link', $ret, $this->user_id);
		echo $ret;
	}

	/**
	 * Echoes the user_name for the profile being displayed
	 */
	public function user_name()
	{
		$this->get_wp_user();
		echo $this->user->get_username();
	}


	/**
	 * Echoes the user_display_name for the profile being displayed
	 */
	public function user_display_name()
	{
		$this->get_wp_user();
		echo $this->user->get_display_name();
	}


	/*
	 * Output a series of <li> with links for profile interactions
	 */
	public function interactions()
	{
		$aAct = array();

		if (PeepSo::get_option('site_socialsharing_enable', TRUE)) {
			$aAct['share'] = array(
				'label' => __('Share', 'peepso'),
				'title' => __('Share this Profile', 'peepso'),
				'click' => 'share.share_url("' . $this->user->get_profileurl() . '");',
				'icon' => 'share',
			);
		}

		if (is_user_logged_in()) {
			if (PeepSo::get_option('site_likes_profile', TRUE) && $this->user->is_profile_likable()) {
				$peepso_like = new PeepSoLike();
				$likes = $peepso_like->get_like_count($this->user_id, PeepSo::MODULE_ID);

				if (FALSE === $peepso_like->user_liked($this->user_id, PeepSo::MODULE_ID, PeepSo::get_user_id())) {
					$like_icon = 'thumbs-up';
					$like_label = __('Like', 'peepso');
					$like_title = __('Like this Profile', 'peepso');
				} else {
					$like_icon = 'thumbs-down';
					$like_label = __('Unlike', 'peepso');
					$like_title = __('Unlike this Profile', 'peepso');
				}

				$aAct['like'] = array(
					'label' => $like_label,
					'title' => $like_title,
					'click' => 'profile.new_like();',
					'icon' => $like_icon,
					'count' => (! empty($likes) ? $likes : 0),
				);
			}

			if ($this->user_id !== PeepSo::get_user_id() && 1 === PeepSo::get_option('site_reporting_enable')) {
				$aAct['report'] = array(
					'label' => __('Report User', 'peepso'),
					'title' => __('Report this Profile', 'peepso'),
					'click' => 'profile.report_user()',
					'icon' => 'warning-sign'
				);
			}
		}

		$aAct['views'] = array(
				'label' => __('Views', 'peepso'),
				'title' => __('Profile Views', 'peepso'),
				'icon' => 'eye',
				'count' => $this->get_wp_user()->get_view_count(), // PeepSoViewLog::get_views($this->user_id, PeepSo::MODULE_ID),
		);

		$aAct = apply_filters('peepso_user_activities_links', $aAct);

		foreach ($aAct as $sName => $aAttr) {
			$withClick = (isset($aAttr['click']) && !empty($aAttr['click']));
			echo '<li>', PHP_EOL;
//echo '<!-- label=', $aAttr['label'], ' -->', PHP_EOL;
			if ($withClick)
				echo '<a href="#" onclick="', esc_js(trim($aAttr['click'], ';')), '; return false;" ',
					(isset($aAttr['title']) ? ' title="' . esc_attr($aAttr['title']) . '" ' : ''),
					'>', PHP_EOL;
			else
				echo '<span ',
					(isset($aAttr['title']) ? ' title="' . esc_attr($aAttr['title']) . '" ' : ''),
					' >', PHP_EOL;

			echo '<i class="ps-icon-', esc_attr($aAttr['icon']), '"></i>';
			if (isset($aAttr['count']))
				echo '<span id="', $sName, '-count">', ($aAttr['count'] > 0 ? intval($aAttr['count']) : '') ,'</span>&nbsp;';
//			echo		$aAttr['label'], PHP_EOL;
			echo ($withClick ? '</a>' : '</span>'), PHP_EOL;
			echo '</li>', PHP_EOL;
		}
	}


	/*
	 * Output a series of <li> with user activity interactions
	 */
	public function user_activities()
	{
		$aAct = apply_filters('peepso_user_activities_links', array());
		$username = $this->get_wp_user()->get_username();

		foreach ($aAct as $sName => $aAttr) {
			echo '<li><a href="', PeepSo::get_page('home'), $sName, '/', $username, '">', $aAttr['count'], ' ', $aAttr['label'], '</a></li>';
		}
	}

	/**
	 * Echoes the registration date of the viewed user
	 */
	public function user_registered()
	{
		$this->get_wp_user();
		echo $this->user->get_date_registered();
	}

	/*
	 * Checks if user has a bio and it's accessible
	 */
	public function user_hasbio()
	{
		$this->get_wp_user();
		$bio = $this->user->get_description();
		if (FALSE === $bio)
			return (FALSE);
		return (!empty($bio));
	}

	/* #308
	 * Check the user's web site, return TRUE if has and return FALSE if not
	 */
	public function user_haswebsite()
	{
		$this->get_wp_user();
		$url = $this->user->get_userurl();
		if ($url === FALSE)
			return (FALSE);
		return (!empty($url));
	}

	/*
	 * Output the user's web site
	 */
	public function user_website()
	{
		$this->get_wp_user();
		$ret = '<a href="' . esc_url($this->user->get_userurl()) . '" rel="nofollow" target="_blank">' . $this->user->get_userurl() . '</a>';
		echo ($ret);
	}


	/**
	 * [user_profile_fields description]
	 */
	public function user_profile_fields()
	{
		PeepSoTemplate::exec_template('profile', 'profile-fields');
	}


	/*
	 * This template tag gives add-on authors a chance to output dialog box HTML content
	 */
	public function dialogs()
	{
		do_action('peepso_profile_dialogs');
	}


	/*
	 * Report a profile as inappropriate content
	 * @param PeepSoAjaxResponse $resp The AJAX response object
	 */
	public function report(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$user_id = $input->get_int('uid');
		$profile_id = $input->get_int('act_id'); // 'postid');
		$reason = $input->get('reason');

		if (PeepSo::check_permissions($profile_id, PeepSo::PERM_REPORT, $user_id)) {
			$rep = new PeepSoReport();
			$rep->add_report($profile_id, $user_id, PeepSo::MODULE_ID, $reason);

			$resp->success(TRUE);
			$resp->notice(__('This profile has been reported', 'peepso'));
		} else {
			$resp->success(FALSE);
			$resp->error(__('You do not have permission to do that.', 'peepso'));
		}
	}

	/**
	 * Filter callback for peepso_postbox_access_settings.
	 * Removes the "only me" privacy setting when viewing a different profile.
	 * @param  array $acc The access settings from the apply_filters call.
	 * @return array The modified access settings.
	 */
	public function postbox_access_settings($acc)
	{
		if ($this->user_id !== intval(PeepSo::get_user_id()))
			unset($acc[PeepSo::ACCESS_PRIVATE]);

		return ($acc);
	}

	/**
	 * Returns the max upload size from php.ini and wp.
	 * @return string The max upload size bytes in human readable format.
	 */
	public function upload_size()
	{
		$peepso_general = PeepSoGeneral::get_instance();
		return ($peepso_general->upload_size());
	}

	/**
	 * Add a message to be stored temporarily.
	 * @param string $message
	 */
	public function add_message($message)
	{
		$this->message = $message;
	}

	/**
	 * Checks if there are any messages to be displayed
	 * @return boolean
	 */
	public function has_message()
	{
		return (!is_null($this->message));
	}

	/**
	 * Displays $message if there's any.
	 */
	public function profile_message()
	{
		if ($this->has_message())
			echo $this->message;
	}

	/**
	 * Return whether the current form/page has an error.
	 * @return boolean
	 */
	public function has_errors()
	{
		$shortcode = PeepSoProfileShortcode::get_instance();

		return ($shortcode->has_error());
	}


	/**
	 * Display the shortcode's error message
	 */
	public function error_message()
	{
		$shortcode = PeepSoProfileShortcode::get_instance();

		echo $shortcode->get_error_message();
	}


	/**
	 * Checks to see whether the current viewed profile is the current user's own profile.
	 * @return boolean
	 */
	public function is_current_user()
	{
		return ($this->user_id == PeepSo::get_user_id());
	}

	/**
	 * Called after rendering the profile edit page.
	 */
	public function after_edit_form()
	{
		do_action('peepso_profile_after_edit_form');
	}

	/**
	 * Defines all alerts
	 * @return array $alerts List of all alerts
	 */
	public function get_alerts_definition()
	{
		static $alerts = NULL;
		if (NULL !== $alerts)
			return ($alerts);

		$alerts = array(
			'activity' => array(
				'title' => __('Activity Stream', 'peepso'),
				'items' => array(
/*
					array(
						// TODO: what's the difference between 'new comment' and 'reply comment'? Are these all Comments on Posts? The wording needs to be more clear
						// Art: They are just copied from the Technical Specs, see Enhancement specs
						'label' => __('New Comments on Stream Items', 'peepso'),
						'setting' => 'stream_new_comment',
					),
					array(
						'label' => __('New Replies to Stream Comments', 'peepso'),
						'setting' => 'stream_reply_comment',
					),
*/
					array(
						'label' => __('Someone Posted on your Profile', 'peepso'),
						'setting' => 'wall_post',
					),
					array(
						'label' => __('Someone Commented on your Post', 'peepso'),
						'setting' => 'user_comment',
					),
					array(
						'label' => __('Someone Liked your Post', 'peepso'),
						'setting' => 'like_post',
					),
					array(
						'label' => __('Someone Shared your Post', 'peepso'),
						'setting' => 'share',
					),
					// TODO: need to add settings for each type of alert/email being created
					// Art: I don't think we need this? 2 checkboxes are created for each setting
					// TODO: check calls to PeepSoNotifications::add_notification() and PeepSoMailQueue::add_messsage()- we need a config setting for each of those
					// Art: I'm not quite understand, for each setting we have 2 distinct names, for instance 'stream_reply_comment' creates 2 settings named 'stream_reply_comment_notification' and 'stream_reply_comment_email' and they are controlled or managed by 2 checkboxes for each setting, hence the 'stream_reply_comment' is just a prefix for the 2 notifications
				),
			),
			'profile' => array(
				'title' => __('Profile', 'peepso'),
				'items' => array(
					array(
						'label' => __('Profile Likes', 'peepso'),
						'setting' => 'profile_like',
					),
					// TODO: we *always* want emails/notifications for password change/recovery messages. These are not to be user configurable since these are on user demand.
/*					array(
						'label' => __('Change Password', 'peepso'),
						'setting' => 'password_changed',
					),
					array(
						'label' => __('Password Recovery', 'peepso'),
						'setting' => 'password_recover',
					), */
				),
			),

			// NOTE: when adding new items here, also add settings to /install/activate.php site_alerts_ sections
		);
		$alerts = apply_filters('peepso_profile_alerts', $alerts);
		return ($alerts);
	}

	/**
	 * Get available or configurable alerts
	 * @return array List of alerts where user can override
	 */
	public function get_available_alerts()
	{
		$alerts = array();
		$alerts_definition = $this->get_alerts_definition();
		foreach ($alerts_definition as $key => $value) {
			if (!isset($value['items']))
				continue;
			$items = array();
			foreach ($value['items'] as $item) {
				$field_name = PeepSoConfigSections::SITE_ALERTS_SECTION . $item['setting'];
				if (1 === intval(PeepSo::get_option($field_name, 0)))
					$items[] = $item;
			}
			if ($items) {
				$value['items'] = $items;
				$alerts[$key] = $value;
			}
		}
		return ($alerts);
	}

	/**
	 * Get alerts form fields definitions
	 * @return array $fields
	 */
	public function get_alerts_form_fields()
	{
		$alerts = $this->get_available_alerts();

		$form = array(
			'name' => 'profile-alerts',
			'action' => PeepSo::get_page('profile') . '?alerts',
			'method' => 'POST',
			'class' => 'ps-form community-form-validate',
			'extra' => 'autocomplete="off"',
		);

		$fields = array();
		if (!empty($alerts)) {
			$fields = array(
				'form_header' => array(
					'label' => '',
					'fields' => array(
						array(
							'label' => __('Email', 'peepso'),
							'type' => 'label',
						),
						array(
							'label' => __('On-Site', 'peepso'),
							'type' => 'label',
						)
					),
					'type' => 'custom',
				),
			);

			$counter = 0;
			// generate form fields
			foreach ($alerts as $key => $value) {
				if ($counter++ > 0) // add an extra space between each section
					$fields["{$key}_clear"] = array(
						'label' => '',
						'descript' => '',
						'fields' => array(),
						'type' => 'custom',
						'section' => 1,
					);
				// generate section
				$fields[$key] = array(
					'label' => '',
					'descript' => "<b>{$value['title']}</b>",
					'value' => 1,
					'fields' => array(
						array(
							'label' => '',
							'name' => "__{$key}_email",
							'type' => 'checkbox',
						),
						array(
							'label' => '',
							'name' => "__{$key}_notification",
							'type' => 'checkbox',
						)
					),
					'type' => 'custom',
					'section' => 1,
				);

				// title
				if (!isset($value['items']) || empty($value['items']))
					continue;

				$peepso_notifications = get_user_meta(get_current_user_id(), 'peepso_notifications');
				$notifications = ($peepso_notifications) ? $peepso_notifications[0] : array();
				if (count($value['items']) <= 1)
					$fields[$key]['fields'] = array();

				// generate items
				foreach ($value['items'] as $item) {
					$name_email = "{$item['setting']}_email";
					$name_notification = "{$item['setting']}_notification";
					$fields[$item['setting']] = array(
						'label' => '',
						'descript' => ' &nbsp; &nbsp; '.$item['label'],
						'value' => 1,
						'fields' => array(
							array(
								'label' => '',
								'name' => $name_email,
								'type' => 'checkbox',
								'group_key' => "__{$key}_email",
								'value' => (!in_array($name_email, $notifications) ? 1 : 0),
							),
							array(
								'label' => '',
								'name' => $name_notification,
								'type' => 'checkbox',
								'group_key' => "__{$key}_notification",
								'value' => (!in_array($name_notification, $notifications) ? 1 : 0),
							)
						),
						'type' => 'custom',
					);
				}
			}
			$fields['submit'] = array(
				'label' => __('Save', 'peepso'),
				'class' => 'ps-btn ps-btn-primary',
				//'click' => 'submitbutton(\'frmSaveProfile\'); return false;',
				'type' => 'submit',
			);
		}
		$fields = apply_filters('peepso_profile_alerts_form_fields', $fields);
		return ($fields);
	}

	/**
	 * Generate alerts form fields
	 * @return array $fields
	 */
	public function alerts_form_fields()
	{
		$form = array(
			'container' => array(
				'element' => 'ul',
				'class' => 'ps-list-alerts ccheck-list creset-list',
			),
			'fieldcontainer' => array(
				'element' => 'li',
				'class' => 'ps-list-item ccheck-list check-section',
			),
			'form' => array(
				'name' => 'profile-alerts',
				'action' => PeepSo::get_page('profile') . '?alerts',
				'method' => 'POST',
				'class' => 'ps-form community-form-validate',
				'extra' => 'autocomplete="off"',
			),
			'fields' => $this->get_alerts_form_fields(),
		);

		//remove_filter('peepso_render_form_field', array(&$this, 'render_custom_form_field'), 10, 2);
		add_filter('peepso_render_form_field', array(&$this, 'render_custom_form_field'), 10, 2);

		$peepso_form = PeepSoForm::get_instance();
		$peepso_form->render($form);
	}

	/**
	 * Generate customized form field
	 * @param array $field input field
	 * @param string $name an input name
	 * @return string the generated customize input field
	 */
	public function render_custom_form_field($field, $name)
	{
		$peepso_form = PeepSoForm::get_instance();

		$custom_field = '<div class="ps-alerts-section">';
		if (isset($field['descript']) && !empty($field['descript']))
			$custom_field .= '<label class="ps-form-label"> ' . $field['descript'] . '</label>';

		$custom_field .= '<div class="ps-list-alerts-check">';
		foreach ($field['fields'] as $value) {
			$custom_field .= '<span>';
			if ('checkbox' === $value['type']) {
				if (isset($field['section']))
					$custom_field .= '<input type="checkbox" class="input" onclick="ps_alerts.toggle(\'' . esc_attr($value['name']) . '\', this.checked)" >';
				else {
					$checked = (1 === $value['value'])? 'checked="checked"' : '';
					$custom_field .= '<input type="checkbox" id="' . esc_attr($value['name']) . '" name="' . esc_attr($value['name']) . '" value="1" ' . $checked . ' class="input ' . esc_attr($value['group_key']) . '" />';
				}
			} else
				$custom_field .= "<b>{$value['label']}</b>";
			$custom_field .= '</span>';
		}
		$custom_field .= '</div>';	// .check-control
		$custom_field .= '</div>';	// .ps-alerts-section
		return ($custom_field);
	}

	/**
	 * Used in conjunction with form validation
	 * @param string $value The value of Change Password field
	 * @return boolean Either to generate an error message if FALSE otherwise not
	 */
	public function check_password_change($value)
	{
		$input = new PeepSoInput();
		$verify_password = $input->post('verify_password');
		if (($value || $verify_password) && $value !== $verify_password)
			return (FALSE);
		return (TRUE);
	}

	/**
	 * Used in conjunction with form validation
	 * @param string $value The value of User Name field
	 * @return boolean Either to generate an error message if FALSE otherwise not
	 */
	public function check_username_change($value)
	{
		if ($value !== $this->user->get_username()) {
			$check_existing_username = get_user_by('login', $value);
			if (FALSE === $check_existing_username)
				return (TRUE);
			return (FALSE);
		}
		return (TRUE);
	}
}

// EOF

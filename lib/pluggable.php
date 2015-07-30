<?php

#if (!function_exists('get_avatar')) {
#	/*
#	 * returns avatar image
#	 * Use the PeepSo avatar for this user, or fallback to using gravatar
#	 * @param string $id_or_email user id or email address of user
#	 * @param int $size size in pixels of avatar image
#	 * @param string $default fallback image url
#	 * @param string $alt alternative text
#	 * @return string <img> tag for avatar
#	 */
#	function get_avatar($id_or_email, $size = '96', $default = '', $alt = false)
#	{
#		if (is_object($id_or_email)) {
#			return (apply_filters('get_avatar', '<img>', $id_or_email, $size, $default, $alt));
#		}
#		else if (is_email($id_or_email)) {
#			$wp_user = get_user_by_email($id_or_email);
#			$user = new PeepSoUser($wp_user->ID);
#		} else {
#			$user = new PeepSoUser($id_or_email);
#		}
#
#		return sprintf('<img src="%s" width="%d" height="%d" alt="%d" />', $user->get_avatar(), $size, $size, $alt);
#	}
#}


if (!function_exists('wp_new_user_notification')) {
	/*
	 * called when sending new user notifications
	 */
	function wp_new_user_notificationxx($user_id, $plaintext_pass = '')
	{
		$user = new PeepSoUser($user_id);

		$data = array(
			'useremail' => $user->get_email(),
			'username' => $user->get_username(),
			'firstname' => $user->get_lastname(),
			'lastname' => $user->get_firstname(),
			'password' => $plaintext_pass
		);

		PeepSoMailQueue::add_message($user_id, $data, __('New User Registration', 'peepso'), 'register', 'register');
	}
}


if (!function_exists('wp_notify_moderator')) {
	function wp_notify_moderator($comment_id) {
		// TODO: implement
		// this function is for native wp comments, I don't think we're using this
	}
}


if (!function_exists('wp_password_change_notification')) {
	/*
	 * called when users requests a password reset
	 */
	function wp_password_change_notification($user)
	{
		if (is_multisite())
			$blogname = $GLOBALS['current_site']->site_name;
		else
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$title = sprintf(__('%s Password Reset'), $blogname);
		/**
		 * Filter the subject of the password reset email.
		 * @since 2.8.0
		 * @param string $title Default email title.
		 */
		$title = apply_filters('retrieve_password_title', $title);

		$peepso_user = new PeepSoUser($user->ID);
		$data = $peepso_user->get_template_fields('user');

		PeepSoMailQueue::add_message($user->ID, $data, $title, 'password_changed', 'password_changed', PeepSo::MODULE_ID);
	}
}

// EOF

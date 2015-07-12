<?php

class PeepSoRecoverPasswordShortcode
{
	private static $_instance = NULL;

	public function __construct()
	{
		if (is_user_logged_in()) {
			wp_redirect(PeepSo::get_page('activity'));
			die;
		}

        add_filter('peepso_page_title', array(&$this,'peepso_page_title'));
	}

	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}


    public function peepso_page_title( $title )
    {
        if('peepso_recover' == $title['title']) {
            $title['newtitle'] = __('Recover password', 'peepso');
        }

        return $title;
    }

	/*
	 * Callback function for the Recover Password shortcode
	 * @param array $atts Attributes array
	 * @param string $content The content within the shortcode
	 */
	public function do_shortcode($atts, $content = '')
	{
		PeepSo::set_current_shortcode('peepso_recover');
		$ret = PeepSoTemplate::get_before_markup();

		if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['email'])) {
			$res = wp_verify_nonce($_POST['-form-id'], 'peepso-recover-password-form');
			if (1 !== $res)
				$err = new WP_Error('bad_form', __('Invalid form contents, please resubmit', 'peepso'));
			else
				$err = $this->retrieve_password();

			if (is_wp_error($err) && 'user_login_blocked' !== $err->get_error_code()) {
				$ret .= PeepSoTemplate::exec_template('general', 'recover-password', array('error' => $err), TRUE);
			} else {
				$ret .= PeepSoTemplate::exec_template('general', 'recover-password-sent', NULL, TRUE);
			}
		} else {
			$ret .= PeepSoTemplate::exec_template('general', 'recover-password', NULL, TRUE);
		}
		$ret .= PeepSoTemplate::get_after_markup();

		wp_reset_query();

		// disable WP comments from displaying on page
		global $wp_query;
		$wp_query->is_single = FALSE;
		$wp_query->is_page = FALSE;

		return ($ret);
	}


	/*
	 * Creates and sends email based on user information submitted
	 * @return multi TRUE if successful, otherwise WP_Error instance
	 */
	public function retrieve_password()
	{
		global $wpdb, $wp_hasher;

		$input = new PeepSoInput();
		$errors = new WP_Error();
		$user_data = NULL;

		$email = $input->post('email');

		if (empty($email)) {
			$errors->add('empty_username', __('<strong>ERROR</strong>: Please enter your email address.', 'peepso'));
		} else if (is_email($email)) { // if (FALSE !== strpos($_POST['email'], '@')) {
			$user_data = get_user_by('email', sanitize_email($email));
			if (empty($user_data))
				$errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.', 'peepso'));
		}

		/**
		 * Fires before errors are returned from a password reset request.
		 * @since 2.1.0
		 */
		do_action('lostpassword_post');

		if ($errors->get_error_code())
			return ($errors);

		if (empty($user_data)) {
			$errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid email address provided.', 'peepso'));
			return ($errors);
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		/**
		 * Fires before a new password is retrieved.
		 * @since 1.5.0
		 * @deprecated 1.5.1 Misspelled. Use 'retrieve_password' hook instead.
		 * @param string $user_login The user login name.
		 */
		do_action('retreive_password', $user_login);
		/**
		 * Fires before a new password is retrieved.
		 * @since 1.5.1
		 * @param string $user_login The user login name.
		 */
		do_action('retrieve_password', $user_login);

		/**
		 * Filter whether to allow a password to be reset.
		 * @since 2.7.0
		 * @param bool true           Whether to allow the password to be reset. Default true.
		 * @param int  $user_data->ID The ID of the user attempting to reset a password.
		 */
		$allow = apply_filters('allow_password_reset', true, $user_data->ID);

		if (!$allow)
			return (new WP_Error('no_password_reset', __('Password reset is not allowed for this user', 'peepso')));
		else if (is_wp_error($allow))
			return ($allow);

		// Generate something random for a password reset key.
		$key = wp_generate_password(20, FALSE);

		/**
		 * Fires when a password reset key is generated.
		 * @since 2.5.0
		 * @param string $user_login The username for the user.
		 * @param string $key        The generated password reset key.
		 */
		do_action('retrieve_password_key', $user_login, $key);

		// Now insert the key, hashed, into the DB.
		if (empty($wp_hasher)) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$wp_hasher = new PasswordHash(8, true);
		}
		$hashed = $wp_hasher->HashPassword($key);
		$wpdb->update($wpdb->users, array('user_activation_key' => $hashed), array('user_login' => $user_login));

		$peepso_user = new PeepSoUser($user_data->ID);
		$data = $peepso_user->get_template_fields('user');
		$data['recover_url'] = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');

		if (is_multisite())
			$blogname = $GLOBALS['current_site']->site_name;
		else
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$title = sprintf(__('%s Password Reset', 'peepso'), $blogname);
		/**
		 * Filter the subject of the password reset email.
		 * @since 2.8.0
		 * @param string $title Default email title.
		 */
		$title = apply_filters('retrieve_password_title', $title);

		PeepSoMailQueue::add_message($user_data->ID, $data, $title, 'password_recover', 'password_recover', PeepSo::MODULE_ID);

		return (TRUE);
	}
}

// EOF

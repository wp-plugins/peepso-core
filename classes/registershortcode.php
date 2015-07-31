<?php

class PeepSoRegisterShortcode
{
	private static $_instance = NULL;

	private $_err_message = NULL;

	private $_form = NULL;

	public function __construct()
	{
		// if user already logged in, show "already logged in" message
		if (is_user_logged_in()) {
			wp_redirect(get_bloginfo('wpurl'));
			die;
		}

		if (PeepSo::get_option('site_registration_enable_ssl'))
			redirect_https();

		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_filter('peepso_register_error', array(&$this, 'error_message'), 10, 1);
        add_filter('peepso_page_title', array(&$this,'peepso_page_title'));

PeepSo::log('PeepSoRegisterShortcode::__construct() - method=[' . $_SERVER['REQUEST_METHOD'] . '] POST: ' . var_export($_POST, TRUE));
		if ('POST' === $_SERVER['REQUEST_METHOD']) {
			if (isset($_POST['submit-activate'])) {
				// submitted the activation code
				$this->activate_account();
			}
			else if (isset($_POST['submit-resend'])) {
				// submitted resend activation link
				$this->resend_activation();
			} else {
				if (FALSE !== $this->register_user()) {
					wp_redirect(PeepSo::get_page('register') . '?success');
					die;
				} else {
	PeepSo::log('PeepSoRegisterShortcode::__construct() register_user() error: ' . $this->_err_message);
				}
			}
		}
	}

	/*
	 * return singleton instance of teh plugin
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

    public function peepso_page_title( $title )
    {
        if('peepso_register' == $title['title']) {
            $title['newtitle'] = __('Registration', 'peepso');
        }

        return $title;
    }

	/*
	 * shortcode callback for the Registration Page
	 * @param array $atts Shortcode attributes
	 * @param string $content Contents of the shortcode
	 * @return string output of the shortcode
	 */
	public function do_shortcode($atts, $content)
	{
		PeepSo::set_current_shortcode('peepso_register');
		wp_enqueue_style('peepso-register');
		wp_enqueue_script('peepso-register');

		$data = array('error' => $this->_err_message);

		$ret = PeepSoTemplate::get_before_markup();
		if (isset($_GET['activate'])) {
			$error = ('POST' === $_SERVER['REQUEST_METHOD']) ? array('error' => new WP_Error('error', $data['error'])) : array();
			$ret .= PeepSoTemplate::exec_template('register', 'register-activate', $error, TRUE);
		} else if (isset($_GET['success']))
			$ret .= PeepSoTemplate::exec_template('register', 'register-complete', NULL, TRUE);
		else if (isset($_GET['verified']))
			$ret .= PeepSoTemplate::exec_template('register', 'register-verified', NULL, TRUE);
		else if (isset($_GET['resend'])) {
			if ('POST' === $_SERVER['REQUEST_METHOD']) {
				// check for any errors from call to resend_activation() in __construct()
				if (NULL === $this->_err_message)
					$ret .= PeepSoTemplate::exec_template('register', 'register-resent', NULL, TRUE);
				else
					$ret .= PeepSoTemplate::exec_template('register', 'register-resend', array('error' => new WP_Error('error', $this->_err_message)), TRUE);
			} else {
				$ret .= PeepSoTemplate::exec_template('register', 'register-resend', NULL, TRUE);
			}
		} else
			$ret .= PeepSoTemplate::exec_template('register', 'register', $data, TRUE);
		$ret .= PeepSoTemplate::get_after_markup();

		wp_reset_query();

		// disable WP comments from displaying on page
		global $wp_query;
		$wp_query->is_single = FALSE;
		$wp_query->is_page = FALSE;

		return ($ret);
	}

	/*
	 * Performs registration operation
	 */
	private function register_user()
	{
PeepSo::log(__METHOD__.'() - post: ' . var_export($_POST, TRUE));
		$input = new PeepSoInput();
		$sNonce = $input->post('-form-id'); // isset($_POST['-form-id']) ? $_POST['-form-id'] : '';
		if (wp_verify_nonce($sNonce, 'register-form')) {
			$u = new PeepSoUser();

			$fname = $input->post('firstname', '');
			$lname = $input->post('lastname', '');
			$uname = $input->post('username', '');
			$email = $input->post('email', '');
			$passw = $input->post('password', '');
			$pass2 = $input->post('password2', '');
			$gender = $input->post('gender', '');

			$task = $input->post('task');

			$register = PeepSoRegister::get_instance();
			$register_form = $register->register_form();
			$form = PeepSoForm::get_instance();
			$form->add_fields($register_form['fields']);
			$form->map_request();

			if (FALSE === $form->validate()) {
				$this->_err_message = __('Form contents are invalid.', 'peepso');
				return (FALSE);
			}

			// verify form contents
			if ('-register-save' != $task) {
				$this->_err_message = __('Form contents are invalid.', 'peepso');
				return (FALSE);
			}

			if (empty($fname) || empty($lname) || empty($uname) || empty($email) || empty($passw) || empty($gender)) {
				$this->_err_message = __('Required form fields are missing.', 'peepso');
				return (FALSE);
			}

			if (!is_email($email)) {
				$this->_err_message = __('Please enter a valid email address.', 'peepso');
				return (FALSE);
			}

			$id = get_user_by('email', $email);
			if (FALSE !== $id) {
				$this->_err_message = __('That email address is already in use.', 'peepso');
				return (FALSE);
			}

			$id = get_user_by('login', $uname);
			if (FALSE !== $id) {
				$this->_err_message = __('That user name is already in use.', 'peepso');
				return (FALSE);
			}

			if ($passw != $pass2) {
				$this->_err_message = __('The passwords you submitted do not match.', 'peepso');
				return (FALSE);
			}
//PeepSo::log("calling create_user('{$fname}', '{$lname}', '{$uname}', '{$email}', '{$passw}', '{$gender}')");
			$wpuser = $u->create_user($fname, $lname, $uname, $email, $passw, $gender);
			do_action('peepso_register_new_user', $wpuser);
		} else {
//PeepSo::log(__CLASS__.'::'.__FUNCTION__.'() - nonce failed');
			$this->_err_message = __('Incomplete form contents.', 'peepso');
			return (FALSE);
		}

		return (TRUE);
	}

	/*
	 * Resends the email activation link to new users
	 */
	private function resend_activation()
	{
		$input = new PeepSoInput();

		$err = NULL;
		$nonce = $input->post('-form-id');
		if (!wp_verify_nonce($nonce, 'resent-activation-form')) {
			$this->_err_message = __('Invalid form contents.', 'peepso');
			return (FALSE);
		}

PeepSo::log(__METHOD__.'() verified nonce');
		$email = sanitize_email($input->post('email'));
		if (!is_email($email)) {
			$this->_err_message = __('Please enter a valid email address', 'peepso');
			return (FALSE);
		}

		// verify form contents
		$task = $input->post('task');
		if ('-resend-activation' !== $task) {
			$this->_err_message = __('Invalid form contents.', 'peepso');
			return (FALSE);
		}

		// form is valid; look up user by email address
		$user = get_user_by('email', $email);
		if (FALSE !== $user) {
			// if it's a valid user - resend the email
			$u = new PeepSoUser($user->ID);
			$u->send_activation($email);
		}
		// if it's not a valid user, we don't want to act like there was a problem
	}

	/**
	 * Returns the error message
	 * @param  string $msg The error message, assigned to $this->_err_message
	 * @return string      The error message
	 */
	public function error_message($msg)
	{
		if (NULL !== $this->_err_message)
			$msg = $this->_err_message;
PeepSo::log('PeepSoRegisterShortcode::error_message() : ' . $msg);
		return ($msg);
	}

	public function enqueue_scripts()
	{
		$data = array();

		if (1 === PeepSo::get_option('site_registration_enableterms', 0)) {
			$data['terms'] = nl2br(PeepSoSecurity::strip_content(PeepSo::get_option('site_registration_terms', '')));
		}

		wp_register_script('validate', PeepSo::get_asset('js/validate-1.5.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_enqueue_script('validate');
		wp_localize_script('peepso', 'peepsoregister', $data);
	}

	/**
	 * Changes the user's role to peepso_verified.
	 */
	public function activate_account()
	{
		$input = new PeepSoInput();
		$key = $input->post('activate', NULL);

		// Get user by meta
		if (NULL !== $key && !empty($key)) {
			$args = array(
				'fields' => 'ID',
				'meta_key' => 'peepso_activation_key',
				'meta_value' => $key,
				'number' => 1 // limit to 1 user
			);
			$user = new WP_User_Query($args);

			if (count($user->results) > 0) {
				$user = get_user_by('id', $user->results[0]);
				$wpuser = new PeepSoUser($user->ID);
				do_action('peepso_register_verified', $wpuser);
				if (PeepSo::get_option('site_registration_enableverification', '0')) {
					$wpuser->set_user_role('verified');
//					$user->set_role('peepso_verified');

					wp_safe_redirect(PeepSo::get_page('register') . '?verified');
					exit();
				} else {
					$wpuser->set_user_role('member');
//					$user->set_role('peepso_member');

					wp_clear_auth_cookie();
				    wp_set_current_user($user->ID);
				    wp_set_auth_cookie($user->ID);
				}

				wp_redirect(PeepSo::get_page(PeepSo::get_option('site_frontpage_redirectlogin')));
				exit();
			}
		}
		$this->_err_message = __('Please enter a valid activation code', 'peepso');
		return (FALSE);
	}
}

// EOF

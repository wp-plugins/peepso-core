<?php

class PeepSoRegister
{
	protected static $_instance = NULL;

	private $user_id = NULL;
	private $user = NULL;

	// list of allowed template tags
	public $template_tags = array(
		'register_form',		// return the registration form
		'display_terms_and_conditions',
	);

	private function __construct()
	{
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

	/* return propeties for the profile page
	 * @param string $prop The name of the property to return
	 * @return mixed The value of the property
	 */
	public function get_prop($prop)
	{
		$ret = '';
		return ($ret);
	}

	//// implementation of template tags

	/*
	 * constructs the profile edit form
	 */
	public function register_form()
	{
		$input = new PeepSoInput();

		$fields = array(
			'section' => array(
				'label' => __('Enter Your User Information', 'peepso'),
				'type' => 'section',
			),
			'firstname' => array(
				'label' => __('First Name', 'peepso'),
				'descript' => __('Enter your first name', 'peepso'),
				'value' => $input->post('firstname', ''),
				'required' => 1,
				'validation' => array(
					'name-utf8',
					'minlen:' . PeepSoUser::FIRSTNAME_MINLEN,
					'maxlen:' . PeepSoUser::FIRSTNAME_MAXLEN
				),
				'type' => 'text',
			),
			'lastname' => array(
				'label' => __('Last Name', 'peepso'),
				'descript' => __('Enter your last name', 'peepso'),
				'value' => $input->post('lastname', ''),
				'required' => 1,
				'validation' => array(
					'name-utf8',
					'minlen:' . PeepSoUser::LASTNAME_MINLEN,
					'maxlen:' . PeepSoUser::LASTNAME_MAXLEN
				),
				'type' => 'text',
			),
			'username' => array(
				'label' => __('User Name', 'peepso'),
				'descript' => __('Enter your desired User Name', 'peepso'),
				'value' => $input->post('username', ''),
				'required' => 1,
				'validation' => array(
					'alphanumeric', 
					'required',  
					'minlen:' . PeepSoUser::USERNAME_MINLEN, 
					'maxlen:' . PeepSoUser::USERNAME_MAXLEN,
					'custom'
				),
				'validation_options' => array(
					'error_message' => __('Username must not be the same as your password.','peepso'),
					'function' => array(&$this, 'validate_username_not_password')
				),
				'type' => 'text',
			),
			'email' => array(
				'label' => __('Email', 'peepso'),
				'descript' => __('Enter your email address', 'peepso'),
				'value' => $input->post('email', ''),
				'required' => 1,
				'type' => 'text',
				'validation' => array(
					'email',
					'required',  
					'maxlen:' . PeepSoUser::EMAIL_MAXLEN,
				),
			),
			'password' => array(
				'label' => __('Password', 'peepso'),
				'descript' => __('Enter your desired password', 'peepso'),
				'value' => '',
				'required' => 1,
				'validation' => array('password', 'required'),
				'type' => 'password',
			),
			'password2' => array(
				'label' => __('Verify Password', 'peepso'),
				'descript' => __('Please re-enter your password', 'peepso'),
				'value' => '',
				'required' => 1,
				'validation' => array('password', 'required'),
				'type' => 'password',
			),
			'gender' => array(
				'label' => __('Your Gender', 'peepso'),
				'descript' => __('Please enter your gender', 'peepso'),
				'value' => $input->post('gender', 'm'),
				'required' => 1,
				'type' => 'radio',
				'options' => array('m' => __('Male', 'peepso'), 'f' => __('Female', 'peepso')),
			),
			'terms' => array(
				'label' => sprintf(__('I agree to the %s.', 'peepso'), 
					'<a href="javascript:show_terms();">' . __('Terms and Conditions', 'peepso') . '</a>'
					),
				'type' => 'checkbox',
				'required' => 1,
				'value' => 1
			),
			'message' => array(
				'label' => __('Fields marked with an asterisk (<span class="required-sign">*</span>) are required.', 'peepso'),
				'type' => 'message',
			),
			'task' => array(
				'type' => 'hidden',
				'value' => '-register-save',
			),
			'-form-id' => array(
				'type' => 'hidden',
				'value' => wp_create_nonce('register-form'),
			),
			'authkey' => array(
				'type' => 'hidden',
				'value' => '',
			),
			'submit' => array(
				'label' => __('Next', 'peepso'),
				'class' => 'ps-btn-primary',
				'click' => 'submitbutton(\'frmSaveProfile\'); return false;',
				'type' => 'submit',
			)
		);

		$form = array(
			'name' => 'profile-edit',
			'action' => PeepSo::get_page('register'),
			'method' => 'POST',
			'class' => 'cform community-form-validate',
			'extra' => 'autocomplete="off"',
		);

		if (0 === PeepSo::get_option('site_registration_enableterms', 0)) {
			unset($fields['terms']);
			unset($fields['terms_text']);
		}

		$fields = apply_filters('peepso_register_form_fields', $fields);

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

	/**
	 * Custom form validation -
	 * Validates if username is not equal to the password.
	 * @param  string $value The username, supplied from the post value.
	 * @return boolean
	 */
	public function validate_username_not_password($value)
	{
		$input = new PeepSoInput();

		return (!empty($value) && $input->post('password') !== $value);
	}
}

// EOF

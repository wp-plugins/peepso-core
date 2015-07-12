<?php

class PeepSoFormValidate
{
	private $_error_message = '';

	protected $_error_messages = array();

	public $options = array();
	public $type = NULL;
	public $param = NULL;

	public function __construct($type, $options = array())
	{
		// Set default args
		$this->options = array_merge(
			array (
				'error_message' => '', // Custom error message for this validator, perhaps we should enable creation of custom validation classes
				'function' => null // callback function to be used for custom validation
			),
			$options
		);

		// Set message per type
		$this->_error_messages = array(
			'required' => __('This field is required.', 'peepso'),
			'numeric' => __('This field must be a number.', 'peepso'),
			'email' => __('This field must be an email.', 'peepso'),
			'alphanumeric' => __('This field only accepts alphanumeric characters.', 'peepso'),
			'alpha' => __('This field only accepts alpha letters.', 'peepso'),
			'name' => __('This field only accepts alpha letters, spaces, dashes(-), and apostrophes(\').', 'peepso'),
			'past' => __('Please enter a date in the past.', 'peepso'),
			'maxlen' => __('This field is too long, should be no more than %d characters.', 'peepso'),
			'minlen' => __('This field is too short, should be at least %d characters.', 'peepso'),
			'website' => __('This field must be a valid website.', 'peepso'),
			'date' => __('This field must be a valid date.', 'peepso'),
			'positive' => __('This field must be positive.', 'peepso'),
			'int' => __('This field must be int value.', 'peepso'),
			'maxval' => __('This field value should be no more than %d.', 'peepso'),
			'minval' => __('This field value should be at least %d.', 'peepso'),
			'password' => sprintf(__('The password should be at least %d characters.', 'peepso'), apply_filters('peepso_validate_password_length', 6)),
			'custom' => $this->options['error_message']
		);

		if (FALSE !== strpos($type, ':'))
			list($this->type, $this->param) = explode(':', $type, 2);
		else
			$this->type = $type;
	}

	/**
	 * Validate value based on type
	 * @param  mixed $value The value to be validated
	 * @return boolean
	 */
	public function validate(&$value)
	{
		switch ($this->type)
		{
		case 'positive':
			return ($value > 0 ? TRUE : FALSE);
		case 'int':
			return (ctype_digit($value));
		case 'required':
			return (isset($value) && '' !== trim($value));
		case 'numeric':
			return (is_numeric($value));
		case 'email':
			if (empty($value))
				return (TRUE);
			return (is_email($value));
		case 'alphanumeric':
			$comp = str_replace('_', '', $value);
			return (empty($comp) ? TRUE : ctype_alnum($comp));
		case 'alpha':
			$comp = str_replace(' ', '', $value); // allow spaces
			return (empty($comp) ? TRUE : ctype_alpha($comp));
		case 'name':
			$comp = str_replace(array(' ', '-', '\''), '', $value); // allow spaces, dash and apostrophe
			return (empty($comp) ? TRUE : ctype_alpha($comp));
		case 'maxlen':
			return (strlen($value) <= intval($this->param) ? TRUE : FALSE);
		case 'minlen':
			return (strlen($value) >= intval($this->param) ? TRUE : FALSE);
		case 'maxval':
			return (($value <= $this->param) ? TRUE : FALSE);
		case 'minval':
			return (($value >= $this->param) ? TRUE : FALSE);
		case 'past':
			return (strtotime($value) < time());
		case 'website':
			$v = trim($value);
			if (empty($v)) // accept empty values?
				return (TRUE);

			if (FALSE === strpos($value, '://'))
			    $value = 'http://' . $value;

			return (filter_var($value, FILTER_VALIDATE_URL) && !(wp_remote_get($value) instanceof WP_Error));
		case 'date':
			$d = new PeepSoDate($value);
    		return ($d && $d->ToString('Y-m-d') == $value);
    	case 'password':
    		$v = trim($value);
    		if (empty($v))
				return (TRUE);

    		return (strlen($value) >= intval(apply_filters('peepso_validate_password_length', 6)) ? TRUE : FALSE);
		case 'custom':
			if (!isset($this->options['error_message']))
				throw new Exception(__('Error message must be set for custom validation', 'peepso'), 1);
			else
				return (call_user_func_array($this->options['function'], array($value)));
			break;
		default:
			return (TRUE);
			break;
		}
	}

	/**
	 * Return error messages from validation
	 * @return array
	 */
	public function get_error_message()
	{
		if (NULL !== $this->param)
			return (sprintf($this->_error_messages[$this->type], $this->param));
		return $this->_error_messages[$this->type];
	}
}

// EOF

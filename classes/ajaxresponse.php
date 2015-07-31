<?php

class PeepSoAjaxResponse
{
	public $session_timeout = FALSE;
	public $focus = NULL;						// focus element
	public $errors = array();					// list of errors
	public $notices = array();					// list of notices
	public $success = 0;						// assume no success
	public $form = NULL;						// form id
	public $validation = array();				// validation information

	public $data = array();

	// constructor
	public function __construct()
	{
		if (!is_user_logged_in())
			$this->session_timeout = 1;
	}

	// return TRUE if instance is tracking any errors
	public function has_errors()
	{
		if (count($this->errors) || count($this->validation))
			return (TRUE);
		return (FALSE);
	}

	// clears previous value in session timeout flag
	public function clear_timeout()
	{
		$this->session_timeout = 0;
	}

	// set a data property to be returned under the 'data.' element
	public function set($sName, $sValue)
	{
		$this->data[$sName] = $sValue;
	}

	// set the form name
	public function form($sFormId)
	{
		$this->form = $sFormId;
	}

	// sets the form id to have focus
	public function focus($sElementId)
	{
		$this->focus = $sElementId;
	}

	// sets the success flag
	public function success($value)
	{
		$this->success = ($value ? 1 : 0);
	}

	// return TRUE if success value is set on
	public function is_success()
	{
		if (1 === $this->success)
			return (TRUE);
		return (FALSE);
	}

	// adds an error message to the 'errors.' element
	public function error($sMsg)
	{
		$this->errors[] = $sMsg;
	}

	// adds an notification message to the 'notices.' element
	public function notice($sMsg)
	{
		$this->notices[] = $sMsg;
	}

	// adds a validation message to the 'validation.' element
	public function validation($sField, $sMsg)
	{
		$val = new AjaxValidationObj($sField, $sMsg);
		$this->validation[] = $val;
		if (NULL === $this->focus)				// if the focus elemnet has not been set
			$this->focus($sField);				// set it here
	}

	// sends data to browser
	public function send($fExit = TRUE)
	{
		$sOutput = $this->toString();			// construct data to send to browser
		header('Content-Type: application/json');
		echo $sOutput;							// send data to browser
		if ($fExit)
			exit(0);							// stop script
	}

	// convert data to a json response
	public function toString()
	{
		$aOutput = array();

		if ($this->session_timeout) {
			$aOutput['session_timeout'] = 1;
			$aOutput['login_dialog'] = PeepSoTemplate::exec_template('general', 'login', NULL, TRUE);
		}

		if (NULL !== $this->focus)
			$aOutput['focus'] = $this->focus;

		if (count($this->errors))
			$aOutput['errors'] = $this->errors;

		if (count($this->notices))
			$aOutput['notices'] = $this->notices;

		if (count($this->errors) + count($this->validation) > 0)
			$aOutput['has_errors'] = 1;
		else
			$aOutput['has_errors'] = 0;

		if ($this->success)
			$aOutput['success'] = 1;
		else
			$aOutput['success'] = 0;

		if (NULL !== $this->form)
			$aOutput['form'] = $this->form;

		if (count($this->validation))
			$aOutput['validation'] = $this->validation;

		if (count($this->data))
			$aOutput['data'] = $this->data;

		// check WP version and use appropriate encoding method
		global $wp_version;
		if (version_compare($wp_version, '4.1', '>=') && function_exists('wp_json_encode'))
			$sOutput = wp_json_encode($aOutput);
		else
			$sOutput = json_encode($aOutput);

		return ($sOutput);
	}
}

// EOF
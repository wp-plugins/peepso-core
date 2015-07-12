<?php

class PeepSoAuth implements PeepSoAjaxCallback
{
	protected static $_instance = NULL;

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

	/**
	 * Handles AJAX login requests.
	 * @param  PeepSoAjaxResponse $resp
	 */
	public function login(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		check_ajax_referer('ajax-login-nonce', 'security');

	    $info = array();
	    $info['user_login'] = $input->post('username');
	    $info['user_password'] = $input->post('password');
	    $info['remember'] = $input->post('remember');

	    $user_signon = wp_signon($info, false);
	    if (is_wp_error($user_signon)){
	    	$resp->success(FALSE);
	    	$resp->set('dialog_title', __('Login Error', 'peepso'));

	    	if (empty($info['user_login']) && empty($info['user_password']))
	    		$resp->error(__('Username and password required.', 'peepso'));
			else {
				$msg = $user_signon->get_error_message();
				$resp->error($msg);
				return (FALSE);
			}
	    } else {
	        $resp->success(TRUE);
	    }
	}
}

// EOF
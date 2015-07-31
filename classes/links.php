<?php

class PeepSoLinks
{
	private static $_instance = NULL;

	private function __construct()
	{

	}

	// @todo docblock
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

	// @todo move up
	public $template_tags = array(
		'edit_profile',			// link to the profile editor page
		'home',					// home page
		'register',				// registration link
		'recover',				// recover password link
		'unsubscribe'			// link to
	);

	/*
	 * Outputs the href for the user's edit profile page
	 */
	public function edit_profile()
	{
		$ret = PeepSo::get_page('profile') . '?edit';
		echo $ret;
	}

	/**
	 * Prints the site URL set in Settings > General
	 */
	public function home()
	{
		echo get_bloginfo('wpurl');
	}

	/**
	 * Prints the URL of Site Registration page
	 */
	public function register()
	{
		echo get_bloginfo('wpurl'), '/', PeepSo::get_option('page_register'), '/';
	}

	/**
	 * Prints the URL of Recover Password page
	 */
	public function recover()
	{
		echo get_bloginfo('wpurl'), '/', PeepSo::get_option('page_recover'), '/';
	}

	/**
	 * Prints the unsubscribe URL which is an Emails and Notifications alerts page
	 */
	public function unsubscribe()
	{
		$link = PeepSo::get_page('profile') . '?alerts';
		echo $link;
	}
}

// EOF

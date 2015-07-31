<?php

class PeepSoConfigSettings
{
	public $options = NULL;
	public static $_instance = NULL;

	const OPTION_KEY = 'peepso_config';

	/*
	 * Loads wordpress options based on OPTION_KEY to $options
	 */
	private function __construct()
	{
		$this->options = get_option(self::OPTION_KEY);
	}

	/*
	 * Return a singleton instance of PeepSoConfigSettings
	 * @return object PeepSoConfigSettings
	 */
	public static function get_instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/*
	 * Returns an option from PeepSoConfigSettings::$options
	 *
	 * @param string $option The key for the option under OPTION_KEY
	 * @param string $default (optional) The default value to be returned
	 *
	 * @return mixed The value if it exists, else $default
	 */
	public function get_option($option, $default = NULL)
	{
		return (isset($this->options[$option]) ? $this->options[$option] : $default);
	}

	/*
	 * Sets an option to be added or updated to OPTION_KEY to be saved via update_option
	 *
	 * @param string $option The option key
	 * @param mixed $value The value to be assigned to $option
	 */
	public function set_option($option, $value)
	{
		$options = $this->get_options();
		$options[$option] = $value;

		update_option(self::OPTION_KEY, $options);

		$this->options = $options;
	}

	/*
	 * Returns all options under OPTION_KEY
	 */
	public function get_options()
	{
		return $this->options;
	}

	/**
	 * Removes an option from peepso_config option
	 * @param  mixed $options Can be a string indicating a single option to be removed or an array of option names
	 */
	public function remove_option($remove_options)
	{
#PeepSo::log('PeepSoConfigSettings::remove_option is removing '.var_export($remove_options));

		if (!is_array($remove_options)) {
			$remove_options = array($remove_options);
		}

		$options = $this->get_options();

		foreach ($remove_options as $remove_option) {
			unset($options[$remove_option]);
		}

		update_option(PeepSoConfigSettings::OPTION_KEY, $options);

	}
}

// EOF
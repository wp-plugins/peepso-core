<?php

class PeepSoPrivacy
{
	private static $_instance = NULL;

	public $template_tags = array(
		'display_dropdown'
	);

	private function __construct()
	{
	}

	/*
	 * retrieve singleton class instance
	 * @return instance reference to plugin
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/**
	 * Return the array of privacy options available.
	 * @return array
	 */
	public function get_access_settings()
	{
		$aAccess = array(
			PeepSo::ACCESS_PUBLIC => array('icon' => 'globe', 'label' => __('Public', 'peepso')),
			PeepSo::ACCESS_MEMBERS => array('icon' => 'users', 'label' => __('Site Members', 'peepso')),
			PeepSo::ACCESS_PRIVATE => array('icon' => 'lock', 'label' => __('Only Me', 'peepso')),
		);

		return (apply_filters('peepso_privacy_access_levels', $aAccess));
	}

    /**
     * Return the array of privacy options available for user profiles
     * @return array
     */
    public function get_access_settings_profile()
    {
        $aAccess = $this->get_access_settings();

        // cherry pick only "public" and "members"
        $aAccess = array(
            PeepSo::ACCESS_PUBLIC => $aAccess[PeepSo::ACCESS_PUBLIC],
            PeepSo::ACCESS_MEMBERS =>$aAccess[PeepSo::ACCESS_MEMBERS],
        );

        return (apply_filters('peepso_privacy_access_levels_profile', $aAccess));
    }

	/**
	 * Get an access level by associative key.
	 * @param  string $access_level The key of the access level to get. Access level may be one of the following PeepSo::ACCESS_PUBLIC, PeepSo::ACCESS_MEMBERS, PeepSo::ACCESS_PRIVATE
	 * @return array for icon and label
	 */
	public function get_access_setting($access_level)
	{
		$levels = $this->get_access_settings();

		return (isset($levels[$access_level]) ? $levels[$access_level] : $levels[PeepSo::ACCESS_PUBLIC]);
	}

	/**
	 * Displays the privacy options in an unordered list.
	 * @param string $callback Javascript callback
	 */
	public function display_dropdown($callback = '')
	{
		echo '<ul class="dropdown-menu">';

		$options = $this->get_access_settings();

		foreach ($options as $key => $option) {
			printf('<li><a data-option-value="%d" onclick="return %s" href="javascript:void(0);"><i class="ps-icon-%s"></i><span>%s</span></a></li>',
				$key, $callback, $option['icon'], esc_html($option['label'])
			);
		}
		echo '</ul>';
	}
}

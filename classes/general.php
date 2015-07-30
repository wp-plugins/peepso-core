<?php

class PeepSoGeneral
{
	protected static $_instance = NULL;

	public $template_tags = array(
		'access_types',				// options for post/content access types
		'navbar',					// output the navigation bar
		'post_types',				// options for post types
		'show_error',				// outputs a WP_Error object
        'navbar_mobile',
        'navbar_sidebar_mobile',
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
	}

	//// implementation of template tags

	public function access_types()
	{
		$access = array(
			'public' => array(
				'icon' => 'globe',
				'label' => __('Public', 'peepso'),
				'descript' => __('Can be seen by everyone, even if they\'re not members', 'peepso'),
			),
			'site_members' => array(
				'icon' => 'users',
				'label' => __('Site Members', 'peepso'),
				'descript' => __('Can be seen by registered members', 'peepso'),
			),
			'friends' => array(
				'icon' => 'user',
				'label' => __('Friends', 'peeps'),
				'descript' => __('Can be seen by your friends', 'peepso'),
			),
			'me' => array(
				'icon' => 'lock',
				'label' => __('Only Me', 'peepso'),
				'descript' => __('Can only be seen by you', 'peepso'),
			)
		);

		foreach ($access as $name => $data) {
			echo '<li data-priv="', $name, '">', PHP_EOL;

			echo '<p class="reset-gap">';
			echo '<i class="ps-icon-', $data['icon'], '"></i>', PHP_EOL;
			echo $data['label'], "</p>\r\n";
			echo '<span>', $data['descript'], "</span></li>", PHP_EOL;
		}
	}

    // Displays the frontend navbar	for mobile
    public function navbar_mobile()
    {
        // Put the filter at the last of the queue so we get them all.
        add_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_menus'), 100);
        $this->navbar();
        remove_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_menus'), 100);
    }

    // Displays the frontend navbar	for mobile
    public function navbar_sidebar_mobile()
    {
        // Put the filter at the last of the queue so we get them all.
        add_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_sidebar'), 100);
        $this->navbar();
        remove_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_sidebar'), 100);
    }

    /**
     * Tries to determine which menus are available on the navigation header.
     * Returns menus with icons.
     * @param  array $menus
     * @return array
     */
    public function parse_mobile_menus($menus)
    {
        foreach ($menus as $index => &$menu) {
            if (!isset($menu['icon'])) {
                unset($menus[$index]);
                continue;
            }

            if (isset($menu['label']))
                unset($menu['label']);

            $menu['class'] = str_replace('', '', isset($menu['class']) ? $menu['class'] : '');
            $menu['class'] = str_replace('ps-right', '', $menu['class']);
        }

        return $menus;
    }

    /**
     * Tries to determine which menus are available on the navigation sidebar
     * Returns menus with icons.
     * @param  array $menus
     * @return array
     */
    public function parse_mobile_sidebar($menus)
    {
        foreach ($menus as $index => &$menu) {
            if (isset($menu['icon'])) {
                unset($menus[$index]);
                continue;
            }

            if (!isset($menu['label'])) {
                $menu['label'] = 'TODO';
            }

            if( array_key_exists('menu', $menu) && count($menu['menu']) ) {

                foreach($menu['menu'] as &$submenu) {
                    if (isset($submenu['icon'])) {
                        unset($submenu['icon']);
                    }
                }
            }

            $menu['class'] = str_replace('', '', isset($menu['class']) ? $menu['class'] : '');
            $menu['class'] = str_replace('ps-right', '', $menu['class']);
        }

        return $menus;
    }

	// Displays the frontend navbar
	public function navbar()
	{
		$note = new PeepSoNotifications();
		$unread_notes = $note->get_unread_count_for_user(PeepSo::get_user_id());

		$navbar = array(
			'home' => array(
				'href' => PeepSo::get_page('activity'),
				'icon' => 'home',
				'title' => __('Activity Stream', 'peepso'),
				'order' => 0
			),
			'profile' => array(
				'class' => 'ps-dropdown-toggle',
				'menuclass' => 'dropdown-menu',
				'label' => __('Profile', 'peepso'),
				'order' => 1,
				'menu' => array(
					'profile' => array(
						'href' => PeepSo::get_page('profile'),
						'label' => __('My Profile', 'peepso'),
						'icon' => 'user',
					),
					'edit' => array(
						'href' => PeepSo::get_page('profile') . '?edit',
						'label' => __('Edit Profile', 'peepso'),
						'icon' => 'edit',
					),
					'preferences' => array(
						'href' => PeepSo::get_page('profile') . '?pref',
						'label' => __('Preferences', 'peepso'),
						'icon' => 'cog',
					),
					'notifications' => array(
						'href' => PeepSo::get_page('profile') . '?notifications',
						'label' => __('Notifications', 'peepso'),
						'icon' => 'volume-up',
					),
					'blocked' => array(
						'href' => PeepSo::get_page('profile') . '?blocked',
						'label' => __('Block List', 'peepso'),
						'icon' => 'stop',
					),
					'alerts' => array(
						'href' => PeepSo::get_page('profile') . '?alerts',
						'label' => __('Emails and Notifications', 'peepso'),
						'icon' => 'volume-up',
					),
				),
			),
			'notifications' => array(
				'href' => PeepSo::get_page('notifications'),
				'icon' => 'globe',
				'class' => 'dropdown-notification ps-js-notifications',
				'title' => __('Pending Notifications', 'peepso'),
				'count' => $unread_notes,
				'order' => 80
			),
			'members' => array(
				'href' => 'javascript:void(0)',
				'icon' => 'search-user',
				'class' => 'member-search-toggle',
				'title' => __('Member Search', 'peepso'),
				'order' => 90
			),
			'logout' => array(
				'href' => PeepSo::get_page('logout'),
				'icon' => 'exit',
				'title' => __('Log Out', 'peepso'),
				'class' => 'ps-right',
				'order' => 100
			),
		);

		/*
		 * if there are no notifications, this code will disable the notification icon's click functionality
		 * and will prevent the popup from being shown
		 */
//		if (0 === $unread_notes) {
//			$navbar['notifications']['href'] = 'javascript:void(0)';
//			$navbar['notifications']['class'] = ' visible-desktop ';
//		}

		$navbar = apply_filters('peepso_navbar_menu', $navbar);

	    $sort_col = array();

	    foreach ($navbar as $nav)
	        $sort_col[] = (isset($nav['order']) ? $nav['order'] : 10);

	    array_multisort($sort_col, SORT_ASC, $navbar);

		foreach ($navbar as $item => $data) {
			if (isset($data['menu'])) {
				echo '<li class="dropdown">', PHP_EOL;
				echo '<a onclick="return false;" ';
				if (isset($data['href']))
					echo ' href="', $data['href'], '" ';
				if (isset($data['class']))
					echo ' class="', $data['class'], '">';
				echo $data['label'], '</a>', PHP_EOL;

				echo '<ul ';
				if (isset($data['menuclass']))
					echo ' class="', $data['menuclass'], '" ';
				echo '>', PHP_EOL;

				foreach ($data['menu'] as $name => $submenu) {
					echo '<li ';
					if (isset($submenu['class']))
						echo ' class="', $submenu['class'], '" ';
					echo '>';
					echo '<a href="', $submenu['href'], '">';
					if (isset($submenu['icon']))
						echo '<i class="ps-icon-', $submenu['icon'], '"></i>';
					echo $submenu['label'], '</a>', PHP_EOL;
					echo '</li>', PHP_EOL;
				}
				echo '</ul>', PHP_EOL;
				echo '</li>', PHP_EOL;
			} else {
				if (isset($data['class']) && strpos($data['class'], 'pull-right') !== FALSE) {
					echo '</ul>';
					echo '<ul class="nav pull-right">';
				}
				// visible-desktop
				echo '<li class=" ', (isset($data['class']) ? $data['class'] : ''), '" ';

				echo '>', PHP_EOL;
				echo '<a href="', $data['href'], '" ';
				if (isset($data['title']))
					echo ' title="', esc_attr($data['title']), '" ';
				echo '>';
				if (isset($data['icon']))
					echo '<i class="ps-icon-', $data['icon'], '"></i>';
				if (isset($data['label']))
					echo $data['label'];
				if (isset($data['count'])) {
					echo '<span class="js-counter ps-notification-counter ps-js-counter"' , ($data['count'] > 0 ? '' : ' style="display:none"'),'>', ($data['count'] > 0 ? $data['count'] : ''), '</span>';
				}
				echo '</a>', PHP_EOL;
				echo '</li>', PHP_EOL;
			}
		}
	}

	/**
	 * Displays the post types available on the post box. Plugins can add to these via the `peepso_post_types` filter.
	 */
	public function post_types()
	{
		$opts = array(
			'status' => array(
				'icon' => 'pencil',
				'name' => __('Status', 'peepso'),
				'class' => 'ps-list-item active',
			),
			// plugins will add these
/*			'photo' => array(
				'icon' => 'camera',
				'name' => __('Photo', 'peepso'),
			),
			'video' => array(
				'icon' => 'videocam',
				'name' => __('Video', 'peepso'),
			),
			'event' => array(
				'icon' => 'calendar',
				'name' => __('Event', 'peepso'),
			)*/
		);

		$opts = apply_filters('peepso_post_types', $opts);

		foreach ($opts as $type => $data) {
			echo '<li data-tab="', $type, '" ';
			if (isset($data['class']) && !empty($data['class']))
				echo 'class="', $data['class'], '" ';
			echo '>', PHP_EOL;
			echo '<a href="javascript:void(0)">';

			echo '<i class="ps-icon-', $data['icon'], '"></i>';
			echo '<span>', $data['name'], '</span>', PHP_EOL;

			echo '</a></li>', PHP_EOL;
		}
	}


	/*
	 * Displays error messages contained within an error object
	 * @param WP_Error $error The instance of WP_Error to display messages from.
	 */
	public function show_error($error)
	{
		if (!is_wp_error($error))
			return;

		$codes = $error->get_error_codes();
		foreach ($codes as $code) {
			echo '<div class="ps-alert">', PHP_EOL;
			$msg = $error->get_error_message($code);
			echo $msg;
			echo '</div>';
		}
	}

	/**
	 * Returns the max upload size from php.ini and wp.
	 * @return string The max upload size bytes in human readable format.
	 */
	public function upload_size()
	{
		$upload_max_filesize = convert_php_size_to_bytes(ini_get('upload_max_filesize'));
		$post_max_size = convert_php_size_to_bytes(ini_get('post_max_size'));

		return (size_format(min($upload_max_filesize, $post_max_size, wp_max_upload_size())));
	}

}

// EOF

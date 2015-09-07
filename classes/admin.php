<?php
/*
 * Performs tasks for Admin page requests
 * @package PeepSo
 * @author PeepSo
 */

class PeepSoAdmin
{
	const NOTICE_KEY = 'peepso_admin_notices_';
	const NOTICE_TTL = 3600;				// set TTL to 1 hour - probably overkill

	private static $_instance = NULL;

	private $dashboard_tabs = NULL;
	private $dashboard_metaboxes = NULL;
	private $tab_count = 0;

	private function __construct()
	{
PeepSo::log('PeepSoAdmin::__construct()');
		if (get_option('permalink_structure'))
			add_action('admin_menu', array(&$this, 'admin_menu'), 9);

		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));

		add_action('deleted_user', array(&$this, 'delete_callback'), 10, 1);
		//allow redirection, even if my theme starts to send output to the browser
		add_action('init', array(&$this, 'do_output_buffer'));

		add_action('admin_notices', array(&$this, 'admin_notices'));

		// check for wp-admin/user.php page and include hooks/classes for user list
		add_filter('views_users', array(&$this, 'filter_user_views'), 100, 1);
		add_filter('manage_users_custom_column', array(&$this, 'filter_custom_user_column'), 10, 3);
		add_filter('user_row_actions', array(&$this, 'filter_user_actions'), 10, 2);
		add_action('manage_users_columns', array(&$this, 'filter_user_list_columns'));
//		add_action('set_user_role', array(&$this, 'set_user_role'), 10, 3);
		add_action('restrict_manage_users', array(&$this, 'peepso_roles'));
		add_action('current_screen', array(&$this, 'update_user_roles'));
	}


	/*
	 * return singleton instance of PeepSoAdmin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/*
	 * Callback for displaying admin notices
	 */
	public function admin_notices()
	{
		$screen = get_current_screen();
		if ('users.php' === $screen->parent_file) {
			// check if there are one or more users with a role of 'verified' or 'registered'
//			$result = count_users();
//			if (isset($result['avail_roles']['peepso_register']) || isset($result['avail_roles']['peepso_verified'])) {
			$usradm = new PeepSoUserAdmin();
			$count_roles = $usradm->count_for_roles(array('verified', 'register'));
			if (0 !== $count_roles) {
				$notice = __('You have Registered or Verified users that need to be approved. To approve, change the user\'s role to PeepSo Member or other appropriate role.', 'peepso');
				$notice .= sprintf(__(' %1$sClick here%2$s for more information on assigning roles.', 'peepso'),
							'<a href="#TB_inline?&inlineId=assign-roles-modal-id" class="thickbox">',
							'</a>');
//				$notice .= ' <a href="#TB_inline?inlineId=assign-roles-modal-id" class="thickbox">' . __('Click here', 'peepso') . '</a>' . __(' for more information on assigning roles.', 'peepso');
				echo '<div class="update-nag" style="padding:11px 15px; margin:5px 15px 2px 0;">', $notice, '</div>', PHP_EOL;
				echo '<div id="assign-roles-modal-id" style="display:none;">';
				echo '<div>';
				echo '<h3>', __('PeepSo User Roles:', 'peepso'), '</h3>';
				echo '<p>', sprintf(__('You can change Roles for PeepSo users by selecting the checkboxes for individual users and then selecting the desired Role from the %s dropdown.', 'peepso'),
							'<select><option>' . __('- Select Role -', 'peepso') . '</option></select>'), '</p>';
				echo '<p>', sprintf(__('Once the new Role is selected, click on the %s button and those users will be updated.', 'peepso'),
						'<input type="button" name="sample" id="sample" class="button" value="' . __('Change Role', 'peepso') . '">'), '</p>';
				echo '<p>', __('Meaning of user roles:', 'peepso'), '</p>';
				$roles = $this->get_roles();
				foreach ($roles as $name => $desc)
					echo '&nbsp;&nbsp;<b>', ucwords($name), '</b> - ', esc_html($desc), '<br/>';
				echo '</div>';
				echo '</div>'; // #assign-roles-modal-id
				wp_enqueue_script('thickbox');
				wp_enqueue_style('thickbox');
			}
		}

		$key = self::NOTICE_KEY . PeepSo::get_user_id();
		$notices = get_transient($key);

		if ($notices) {
			foreach ($notices as $notice)
				echo '<div class="', $notice['class'], '" style="padding:11px 15px; margin:5px 15px 2px 0;">', $notice['message'], '</div>' . PHP_EOL;
		}
		delete_transient($key);
	}


	/*
	 * callback for admin_menu event. set up menus
	 */
	public function admin_menu()
	{
		$admin = PeepSoAdmin::get_instance();
		// $dasboard_hookname = toplevel_page_peepso
		$dashboard_hookname = add_menu_page(__('PeepSo', 'peepso'), __('PeepSo', 'peepso'),
			'manage_options',
			'peepso',
			array(&$this, 'dashboard'),
			PeepSo::get_asset('images/logo-icon_20x20.png'),
			4);

		add_action('load-' . $dashboard_hookname, array(&$this, $dashboard_hookname . '_loaded'));
		add_action('load-' . $dashboard_hookname, array(&$this, 'config_page_loaded'));

		$aTabs = $admin->get_tabs();

		// add submenu items for each item in tabs list
		foreach ($aTabs as $color => $tabs) {
			foreach ($tabs as $name => $tab) {
				$function = (isset($tab['function'])) ? $tab['function'] : null;

				$count = '';
				if (isset($tab['count']) && ($tab['count'] > 0 || (!is_int($tab['count']) && strlen($tab['count'])))) {
					$count = '<span class="awaiting-mod"><span class="pending-count">' . $tab['count'] . '</span></span>';
				}
				$submenu = '';
				if (isset($tab['submenu']))
					$submenu = $tab['submenu'];

				$submenu_page = add_submenu_page('peepso',
					$tab['menu'], $tab['menu'] . $count . $submenu,
					'manage_options', $tab['slug'], $function);

				if (method_exists($this, $submenu_page . '_loaded'))
					add_action('load-' . $submenu_page, array(&$this, $submenu_page . '_loaded'));


				add_action('load-' . $submenu_page, array(&$this, 'config_page_loaded'));
			}
		}

		$rep = new PeepSoReport();
		$items = $rep->get_num_reported_items();
		$count = '';
		if ($items > 0)
			$count = '<span class="awaiting-mod"><span class="pending-count">' . $items . '</span></span>';

		$report_sub = add_submenu_page(
			'peepso',
			__('Reported Items', 'peepso'),
			__('Reported Items', 'peepso') . $count,
			'manage_options',
			'peepso-reports',
			array('PeepSoAdminReport', 'dashboard')
		);
		add_action('load-' . $report_sub, array(&$this, 'config_page_loaded'));
	}


	public static function admin_header($title)
	{
		echo '<h2><img src="', PeepSo::get_asset('images/logo.png'), '" width="150" />';
		echo ' v' . PeepSo::PLUGIN_VERSION;

		if(strlen(PeepSo::PLUGIN_RELEASE)) {
			echo "-".PeepSo::PLUGIN_RELEASE;
		}

		echo ' - ' ,  $title , '</h2>', PHP_EOL;
	}
	/*
	 * callback to display the PeepSo Dashboard
	 */
	public function dashboard()
	{
PeepSo::log('inside PeepSoAdmin::dashboard()');
		$aTabs = apply_filters('peepso_admin_dashboard_tabs', $this->dashboard_tabs);

		$admin = PeepSoAdmin::get_instance();
		$admin->define_dashboard_metaboxes();
		$this->dashboard_metaboxes = apply_filters('peepso_admin_dashboard_metaboxes', $this->dashboard_metaboxes);
		$admin->prepare_metaboxes();

		PeepSoAdmin::admin_header(__('Dashboard','peepso'));
		echo '<div id="peepso" class="wrap">';

		echo '<div class="row-fluid">';

		$span = floor(intval(PeepSo::get_option('site_dashboard_reportperiod')) / 24);
		printf(__('New items from the last %d days:', 'peepso'), $span);
		echo '<br/>';

		echo '<div class="dashtab">';
		foreach ($aTabs as $color => $tabs)
			$this->output_tabs($color, $tabs);
		echo '</div>';

		echo '<div class="dashgraphs">';
		echo '<div class="row">
				<div class="col-xs-12">
				<div class="row">
					<!-- Left column -->
					<div class="col-xs-12 col-sm-6">';
		do_meta_boxes('toplevel_page_peepso', 'left', null);
		echo '
					</div>
					<!-- Right column -->
					<div class="col-xs-12 col-sm-6">';

		do_meta_boxes('toplevel_page_peepso', 'right', null);
		echo '
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
		<div class="clearfix"></div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';	// .wrap
	}

	/**
	 * Output the admin dashboard tabs
	 * @param  string $color   The infobox color used as css class
	 * @param  array $tablist The tabs to be displayed
	 * @return void          Echoes the tab HTML.
	 */
	private function output_tabs($color, $tablist)
	{
		$size = number_format((100 / $this->tab_count) - 1, 2);
		if ($size > 15)
			$size = 15;
		foreach ($tablist as $tab => $data) {
			echo	'<div class="infobox infobox-', $color, ' infobox-dark" style="width:', $size, '%">', PHP_EOL;
			if ('/' === substr($data['slug'], 0, 1))
				echo	'<a href="', get_admin_url(NULL, $data['slug']), '">', PHP_EOL;
			else
				echo	'<a href="admin.php?page=', $data['slug'], '">', PHP_EOL;
			echo			'<div class="infobox-icon dashicons dashicons-', $data['icon'], '"></div>' , PHP_EOL;
			if (isset($data['count'])) {
				echo			'<div class="infobox-data">', PHP_EOL;
				echo				'<div class="infobox-content">', $data['count'], '</div>', PHP_EOL;
				echo			'</div>', PHP_EOL;
			}
			echo			'<div class="infobox-caption">', $data['menu'], '</div>', PHP_EOL;
			echo			'</a>', PHP_EOL;
			echo	'</div>', PHP_EOL;
		}
	}


	/*
	 * Enqueue scripts and styles for PeepSo admin
	 */
	public function enqueue_scripts()
	{
		global $wp_styles;

		wp_register_style('ace-admin-boostrap-min', PeepSo::get_asset('aceadmin/css/bootstrap.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-boostrap-responsive', PeepSo::get_asset('aceadmin/bootstrap-responsive.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-boostrap-timepicker', PeepSo::get_asset('aceadmin/bootstrap-timepicker.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');

		wp_register_style('ace-admin-fonts', PeepSo::get_asset('aceadmin/css/ace-fonts.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-fontawesome', PeepSo::get_asset('aceadmin/css/font-awesome.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin', PeepSo::get_asset('aceadmin/css/ace.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-responsive', PeepSo::get_asset('aceadmin/css/ace-responsive.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-skins', PeepSo::get_asset('aceadmin/css/ace-skins.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-ie', PeepSo::get_asset('aceadmin/css/ace-ie.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		$wp_styles->add_data('ace-admin-ie', 'conditional', 'IE 7');
		wp_register_style('peepso-admin', PeepSo::get_asset('css/admin.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');

		wp_register_script('peepso', PeepSo::get_asset('js/peepso.min.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_register_script('peepso-admin-config', PeepSo::get_asset('js/peepso-admin-config.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

		$aData = array(
			'ajaxurl' => get_bloginfo('wpurl') . '/peepsoajax/',
			'version' => PeepSo::PLUGIN_VERSION,
			'currentuserid' => PeepSo::get_user_id(),
			'userid' => apply_filters('peepso_user_profile_id', 0),		// user id of the user being viewed (from PeepSoProfileShortcode)
			'objectid' => apply_filters('peepso_object_id', 0),			// user id of the object being viewed
			'objecttype' => apply_filters('peepso_object_type', ''),	// type of object being viewed (profile, group, etc.)
		);
		wp_localize_script('peepso', 'peepsodata', $aData);
		wp_enqueue_script('peepso');

		wp_enqueue_style('peepso', PeepSo::get_template_asset(NULL, 'css/peepso.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_script('peepso-window', PeepSo::get_asset('js/pswindow.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_localize_script('peepso-window', 'peepsowindowdata', array(
			'label_confirm' => __('Confirm', 'peepso'),
			'label_confirm_delete' => __('Confirm Delete', 'peepso'),
			'label_confirm_delete_content' => __('Are you sure you want to delete this?', 'peepso'),
			'label_yes' => __('Yes', 'peepso'),
			'label_no' => __('No', 'peepso'),
			'label_delete' => __('Delete', 'peepso'),
			'label_cancel' => __('Cancel', 'peepso'),
			'label_okay' => __('Okay', 'peepso'),
		));

		// if version < 3.9 include dashicons
		global $wp_version;
		if (version_compare($wp_version, '3.9', 'lt')) {
			wp_register_style('peepso-dashicons', PeepSo::get_asset('css/dashicons.css'),
				array(), PeepSo::PLUGIN_VERSION, 'all');
			wp_enqueue_style('peepso-dashicons');
		}
	}

	/*
	 * return list of tab items for PeepSo Dashboard display
	 */
	public function get_tabs()
	{
		if (NULL === $this->dashboard_tabs) {
			global $wpdb;

			// add counts for peepso users
			$date = date('Y-m-d H:i:s', strtotime('now - ' . intval(PeepSo::get_option('site_dashboard_reportperiod')) . ' hours'));
			$sql = "SELECT COUNT(*) AS `val` " .
					" FROM `{$wpdb->users}` `u` " .
					" LEFT JOIN `{$wpdb->usermeta}` `meta` ON `meta`.`user_id` = `u`.`ID` " .
						" AND `meta`.`meta_key`='{$wpdb->prefix}capabilities' " .
					" WHERE `u`.`user_registered` > %s AND " .
					"	`meta`.`meta_value` LIKE '%%peepso%%'";

			$user_count = $wpdb->get_var($wpdb->prepare($sql, $date));

//PeepSo::log('PeepSoAdmin::get_tabs() ' . $wpdb->last_query);

//			$sql = "SELECT COUNT(*) AS `val` " .
//					" FROM `{$wpdb->prefix}peepso_mail_queue` ";
//			$msg_count = $wpdb->get_var($sql);
			$msg_count = PeepSoMailQueue::get_pending_item_count();

			$tabs = array(
				'blue' => array(
					'welcome' => array(
						'slug' => 'peepso-welcome',
						'menu' => __('Getting Started', 'peepso'),
						'icon' => 'info',
						'function' => array('PeepSoConfig', 'welcome_screen'),
						#'count'=>'NEW!!!',
					),
					'members' => array(
						'slug' => '/users.php?',
						'menu' => __('Members', 'peepso'),
						'icon' => 'id-alt', // 'group',					// dashicons-id-alt
						'count' => intval($user_count),
					),
					'messages' => array(
						'slug' => 'peepso-mailqueue', // peepso-messages',
						'menu' => __('Mail Queue', 'peepso'),
						'icon' => 'email', // 'envelope',				// dashicons-email
						'count' => intval($msg_count),
						'function' => array('PeepSoAdminMailQueue', 'administration'),
					),
				),
				'red' => array(
				),
				'green' => array(
				),
				'orange' => array(
				),
				'gray' => array(
					'config' => array(
						'slug' => PeepSoConfig::$slug,
						'menu' => __('Config', 'peepso'),
						'icon' => 'admin-generic',
						'function' => array('PeepSoConfig', 'init')
					)
				),
			);

			if (isset($_GET['page']) && 'peepso_config' === $_GET['page']) {
				$cfg = PeepSoConfig::get_instance();
				$cfg_tabs = $cfg->get_tabs();
				$list = '';
				foreach ($cfg_tabs as $cfg_tab => $cfg_data) {
					$list .= '<li><a href="' . admin_url('admin.php?page=peepso_config&tab=' . $cfg_data['tab']) . '">';
					$list .= '&raquo;&nbsp;' . $cfg_data['label'] . '</a></li>';
				}
				$tabs['gray']['config']['submenu'] = '</a>' .
					'<ul class="wp-submenu wp-submenu-wrap" style="margin: 0 0 0 10px">' .
					$list .
					'</ul>';
			}

//PeepSo::log('PeepSoAdmin::get_tabs() ' . var_export($tabs, TRUE));
			$tabs = apply_filters('peepso_admin_dashboard_tabs', $tabs);
			$this->dashboard_tabs = &$tabs;

			$this->tab_count = 0;
			foreach ($tabs as $color => $tabitems)
				$this->tab_count += count($tabitems);
		}

		return ($this->dashboard_tabs);
	}


	/*
	 * called from wp_delete_user() to signal a user has been deleted
	 * @param int $id The id of the user that is to be deleted
	 */
	public function delete_callback($id)
	{
		$user = new PeepSoUser($id);
		$user->delete_data($id);
	}


	/**
	 * Add notice with type and message
	 * @param string $notice The message to display in an Admin Notice
	 * @param string $type The type of notice. One of: 'error', 'warning', 'info', 'note', 'none'
	 */
	public function add_notice($notice, $type = 'error')
	{
		$types = array(
			'error' => 'error',
			'warning' => 'update-nag',
			'info' => 'check-column',
			'note' => 'updated',
			'none' => '',
		);
		if (!array_key_exists($type, $types))
			$type = 'none';

		$notice_data = array('class' => $types[$type], 'message' => $notice);

		$key = self::NOTICE_KEY . PeepSo::get_user_id();
		$notices = get_transient($key);

		if (FALSE === $notices)
			$notices = array($notice_data);

		// only add the message if it's not already there
		$found = FALSE;
		foreach ($notices as $notice) {
			if ($notice_data['message'] === $notice['message'])
				$found = TRUE;
		}
		if (!$found)
			$notices[] = $notice_data;

		set_transient($key, $notices, self::NOTICE_TTL);
	}

	// TODO: let's try to remove this and do away with output buffering
	public function do_output_buffer()
	{
        ob_start();
	}


	/*
	 * Update the columns displayed for the WP user list
	 * @param array $columns The current columns to display in the user list
	 * @return array The modified column list
	 */
	public function filter_user_list_columns($columns)
	{
		$ret = array();
		foreach ($columns as $key => $value) {
			// remove the 'Posts' column
			if ('posts' === $key)
				continue;
			$ret[$key] = $value;
			// add the PeepSo Role column after the WP Role column
			if ('role' === $key)
				$ret['peepso_role'] = __('PeepSo Role', 'peepso');
		}
		return ($ret);
	}

	/**
	 * Filters the list of view links, adding some for PeepSo roles
	 * @param array $views List of views
	 * @return array The modified list of views
	 */
	public function filter_user_views($views)
	{
PeepSo::log(__METHOD__.'() views: ' . var_export($views, TRUE));
		$usradm = new PeepSoUserAdmin();
		$res = $usradm->get_counts_by_role();
		if (is_array($res)) {
			foreach ($res as $row) {
//	'all' => '<a href=\'users.php\' class="current">All <span class="count">(5)</span></a>',
//	'subscriber' => '<a href=\'users.php?role=subscriber\'>Subscriber <span class="count">(3)</span></a>',

				$link = '<a href="users.php?psrole=' . $row['role'] . '">PeepSo ' . ucwords($row['role']) . ' <span class="count">(' . $row['count'] . ')</span></a>';
				$views[$row['role']] = $link;
			}
		}
		return ($views);
	}

	/**
	 * Filters the custom column, displaying the PeepSo Role value for the indicated user
	 * @param string $value Filter value
	 * @param string $column The name of the column
	 * @param int $id The user id for the row being displayed
	 * @return string Appropriate column value for the user being displayed
	 */
	public function filter_custom_user_column($value, $column, $id)
	{
		switch ($column)
		{
		case 'peepso_role':
			$roles = $this->get_roles();

			$user = new PeepSoUser($id);
			$role = $user->get_user_role();

			$value = '<span title="' . esc_attr($roles[$role]) . '">' .
				'PeepSo ' . ucwords($role) . '</span>';
			break;
		}
		return ($value);
	}

	/**
	 * Filters the WP_User_Query, adding the WHERE clause to look for PeepSo roles
	 * @param WP_User_query $query The query object to filter
	 * @return WP_User_Query The modified query object
	 */
	public function filter_user_query($query)
	{
		global $wpdb;
		$input = new PeepSoInput();

		$query->query_from .= " LEFT JOIN `{$wpdb->prefix}" . PeepSoUser::TABLE . "` ON `{$wpdb->users}`.ID = `usr_id` ";
		$query->query_where .= " AND `usr_role`='" . esc_sql($input->get('psrole', 'member')) . '\' ';
		return ($query);
	}

	/**
	 * Performs updates on the user selected via the Bulk Action checkboxes
	 * @param object $screen The current screen object
	 * @return type
	 */
	public function update_user_roles($screen)
	{
		// if it's not the users page, don't do anything
		if ('users' !== $screen->base)
			return;
		// if there is a PeepSo Role filter requestsed, add the WP_Users_query filter
		if (isset($_GET['psrole']))
			add_filter('pre_user_query', array(&$this, 'filter_user_query'));
		if ('GET' === $_SERVER['REQUEST_METHOD']) {
			$input = new PeepSoInput();
			$role = strtolower($input->get('peepso-role-select', '0'));
			if ('0' !== $role) {
				// verify that the form is valid
				if (!current_user_can('edit_users')) {
					$this->add_notice(__('You do not have permission to do that.', 'peepso'), 'error');
					return;
				}
				if (!wp_verify_nonce($input->get('ps-role-nonce'), 'psrole-nonce')) {
					$this->add_notice(__('Form is invalid.', 'peepso'), 'error');
					return;
				}
				$users = (isset($_GET['users']) ? $_GET['users'] : array()); // $input->get('users', array());
				$roles = $this->get_roles();
				if (in_array($role, array_keys($roles)) && 0 < count($users)) {
					foreach ($users as $user_id) {
						$user = new PeepSoUser($user_id);
						$old_role = $user->get_user_role();

						// perform approval; sends welcome email
						if ('member' === $role && 'verified' === $old_role) {
							$adm = new PeepSoUserAdmin($user_id);
							$adm->approve_user();
						}
						// update the user with their new role
//						$data = array('usr_role' => $role);
//						$user->update_peepso_user($data);
						$user->set_user_role($role);
					}
				}
			} else {
				if (isset($_GET['change-peepso-role']))
					$this->add_notice(__('Please select a PeepSo Role before clicking on "Change Role".', 'peepso'), 'warning');
			}
		}
	}

	/**
	 * Outputs UI controls for setting the User roles
	 */
	public function peepso_roles()
	{
		echo '<div id="peepso-role-wrap" style="vertical-align: baseline">';
		echo '<span>';
		echo __('Set PeepSo Role:', 'peepso'), '&nbsp;&nbsp;';
		echo '<select id="peepso-role-select" name="peepso-role-select">';
			echo '<option value="0">', __(' - Select Role -', 'peepso'), '</option>';
			$roles = $this->get_roles();
			$translated_roles = $this->get_translated_roles();
			foreach ($roles as $name => $desc) {
				echo '<option value="', $name, '">', $translated_roles[$name], '</option>';
			}
		echo '</select>';
		echo '<input type="hidden" name="ps-role-nonce" value="', wp_create_nonce('psrole-nonce'), '" />';
		echo '<input type="submit" name="change-peepso-role" id="change-peepso-role" class="button" value="', __('Change Role', 'peepso'), '">';
		echo '</span>';
		echo '</div>';
		echo '<style>';
		echo '#peepso-role-wrap { display: inline-block; margin-left: 1em; padding: 3px 5px; }';
		echo '#peepso-role-wrap span { bottom; padding-top: 2em }';
		echo '#peepso-role-wrap #peepso-role-select { float:none; }';
		echo '</style>';
	}

	/**
	 * Get a list of the Roles recognized by PeepSo
	 * @return Array The list of Roles
	 */
	public function get_roles()
	{
		$ret = array(
			'user' => __('Standard user account', 'peepso'),
			'member' => __('Full member, can write posts and participate', 'peepso'),
			'moderator' => __('Full member, can moderate posts', 'peepso'),
			'admin' => __('PeepSo Administrator, can Moderate, edit users, etc.', 'peepso'),
			'register' => __('Registered, awaiting email verification', 'peepso'),
			'verified' => __('Verified email, awaiting Adminstrator approval', 'peepso'),
			'ban' => __('Banned, cannot login or participate', 'peepso'),
		);


		// TODO: before we can allow filtering/adding to this list we need to change the `peepso_users`.`usr_role` column
		return ($ret);
	}

	public function get_translated_roles()
	{
		$ret = array(
			'user' 		=> __('role_user', 		'peepso'),
			'member' 	=> __('role_member', 	'peepso'),
			'moderator' => __('role_moderator', 'peepso'),
			'admin' 	=> __('role_admin', 	'peepso'),
			'register' 	=> __('role_register', 	'peepso'),
			'verified' 	=> __('role_verified', 	'peepso'),
			'ban' 		=> __('role_ban', 		'peepso'),
		);

		foreach($ret as $k=>$v) {
			if(stristr($v, 'role_')) {
				$ret[$k] = ucwords($k);
			}
		}

		return $ret;
	}


	/*
	 * Filter the avatar so that the PeepSo avatar is displayed
	 * @param string $avatar The avatar HTML content
	 * @param midxed $id_or_email The user id or email address of the user
	 * @param int $size The size of the avatar to create
	 * @param mixed $default
	 * @param string $alt Alternate text
	 */


	/*
	 * Add a link to the user's profile page to the actions
	 * @param array $actions The current list of actions
	 * @param WP_User $user The WP_User instance
	 * @return array List of actions, with a profile link added
	 */
	public function filter_user_actions($actions, $user = NULL)
	{
		// add the 'Profile Link' action to the list of actions
		$user = new PeepSoUser($user->ID);
//PeepSo::log('  url=' . $user->get_userurl(FALSE));
		$actions['profile'] = '<a class="submitdelete" href="' . $user->get_profileurl(FALSE) . '" target="_blank">' . __('Profile Link', 'peepso') . '</a>';
		return ($actions);
	}

	/**
	 * Enqueues scripts after the config page has been loaded
	 */
	public function config_page_loaded()
	{
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_ace_admin_scripts'));
	}

	/**
	 * Enqueues the admin dashboard assets
	 */
	public function enqueue_ace_admin_scripts()
	{
		wp_enqueue_style('ace-admin-boostrap-min');
		wp_enqueue_style('ace-admin');
		wp_enqueue_style('ace-admin-fontawesome');
		wp_enqueue_style('peepso-admin');
	}

	/**
	 * Enqueues scripts when the peepso backend is accessed
	 */
	public function toplevel_page_peepso_loaded()
	{
		wp_register_script('bootstrap', PeepSo::get_asset('aceadmin/js/bootstrap.min.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('flot', PeepSo::get_asset('aceadmin/js/flot/jquery.flot.min.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('flot-pie', PeepSo::get_asset('aceadmin/js/flot/jquery.flot.pie.min.js'),
			array('flot'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('flot-time', PeepSo::get_asset('aceadmin/js/flot/jquery.flot.time.js'),
			array('flot'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso-admin-dashboard', PeepSo::get_asset('js/admin-dashboard.js'),
			array('flot'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_enqueue_script('bootstrap');
		wp_enqueue_script('flot');
		wp_enqueue_script('flot-time');
		wp_enqueue_script('flot-pie');
		wp_enqueue_script('peepso-admin-dashboard');
	}

	/**
	 * Calls add_meta_box for every metabox defined in define_dashboard_metaboxes()
	 */
	public function prepare_metaboxes()
	{
		foreach ($this->dashboard_metaboxes as $metabox) {
			add_meta_box(
		        'peepso_dashboard_' . $metabox['name'], // meta box ID
		        $metabox['title'],						// meta box Title
		        $metabox['callback'],					// callback defining the plugin's innards
		        'toplevel_page_peepso',					// screen to which to add the meta box
		        isset($metabox['context']) ? $metabox['context'] : 'left', // context
		        'default');
		}
	}

	/*
	 * Defines the default metaboxes for the dashboard
	 */
	public function define_dashboard_metaboxes()
	{
		$this->dashboard_metaboxes = array(
			array(
				'name' => 'user_engagement',
				'title' => __('User Engagement', 'peepso'),
				'callback' => array(&$this, 'engagement_metabox'),
				'context' => 'left'
			),

			array(
				'name' => 'child_plugins_bundles_nocontain',
				'title' => __('Buy bundle of all plugins, save $90 <strong class="label label-danger label-title">Best Value</strong>', 'peepso'),
				'callback' => array(&$this, 'child_plugins_bundles'),
				'context' => 'right',
			),
			array(
				'name' => 'child_plugins_nocontain',
				'title' => __('Buy individual plugins for a great price', 'peepso'),
				'callback' => array(&$this, 'child_plugins'),
				'context' => 'right',
			),
			array(
				'name' => 'most_recent',
				'title' => __('Most Recent Content', 'peepso'),
				'callback' => array(&$this, 'recent_metabox'),
				'context' => 'left'
			),
			array(
				'name' => 'demographic',
				'title' => __('User Demographics', 'peepso'),
				'callback' => array(&$this, 'demographic_metabox'),
				'context' => 'left'
			)
		);
	}

	/**
	 * Renders the demographic metabox on the dashboard
	 */
	public function demographic_metabox()
	{
		$peepso_user_model = new PeepSoUserAdmin();
		// Should this be 'm'?
		$males = $peepso_user_model->get_count_by_gender('m');
		$females = $peepso_user_model->get_count_by_gender('f');
		$unknown = $peepso_user_model->get_count_by_gender('u');

		$data = array();
		if (0 < $males)
			$data[] = array(
				'label' => __('Male', 'peepso'),
				'value' => $males,
				'icon' => PeepSo::get_asset('images/user-male-thumb.png'),
				'color' => 'rgb(237,194,64)'
			);
		if (0 < $females)
			$data[] = array(
				'label' => __('Female', 'peepso'),
				'value' => $females,
				'icon' => PeepSo::get_asset('images/user-female-thumb.png'),
				'color' => 'rgb(175,216,248)'
			);
		if (0 < $unknown)
			$data[] = array(
				'label' => __('Unknown', 'peepso'),
				'value' => $unknown,
				'icon' => PeepSo::get_asset('images/user-neutral-thumb.png'),
				'color' => 'rgb(180,180,180)'
			);

		$options = array(
			'series' => array(
				'pie' => array(
					'show' => true,
					'radius' => 100,
					'highlight' => array(
						'opacity' => 0.25
					),
					'label' => array(
						'show' => true
					)
				)
			),
			'legend' => array(
				'show' => true,
				'position' => "ne",
			),
			'grid' => array(
				'hoverable' => true,
				'clickable' => true
			)
		);

		$data = apply_filters('peepso_admin_dashboard_demographic_data', $data);
		$options = apply_filters('peepso_admin_dashboard_demographic_options', $options);

		echo '<script>', PHP_EOL;
		echo 'var demographic_data = ', json_encode($data), ';', PHP_EOL;
		echo 'var demographic_options = ', json_encode($options), ';', PHP_EOL;
		echo '</script>', PHP_EOL;
		echo '<div id="demographic-pie"></div>', PHP_EOL;
		echo '<div class="hr hr-double"></div>', PHP_EOL;
		echo '<div class="clearfix">', PHP_EOL;

		$demographic_ctr = 0;

		foreach ($data as $demographic) {
			echo '<div class="col-md-4 text-center">', PHP_EOL;

			if (isset($demographic['icon']))
				echo '		<img src="', $demographic['icon'], '" class= "inline avg-avatar img-circle"/>', PHP_EOL;

			echo	'<h3 class="inline">', $demographic['value'], '</h3>', PHP_EOL;
			echo	'<span class="block">', $demographic['label'], '</span>', PHP_EOL;
			echo '</div>';

			if (count($data) === ++$demographic_ctr) {
				echo '</div>';
				echo '<div class="hr hr-double"></div>';
				echo '<div class="clearfix">';
				$demographic_ctr = 0;
			}
		}

		echo '</div>', PHP_EOL; // </clearfix>
	}

	/*
	 * Display the content of the Most Recent metabox and gathers additional tabs from other plugins
	 */
	public function recent_metabox()
	{
		// This metabox's default tabs
		$tabs = array(
			array(
				'id' => 'recent-posts',
				'title' => __('Posts', 'peepso'),
				'callback' => array(&$this, 'recent_posts_tab')
			),
			array(
				'id' => 'recent-comments',
				'title' => __('Comments', 'peepso'),
				'callback' => array(&$this, 'recent_comments_tab')
			),
			array(
				'id' => 'recent-members',
				'title' => __('Members', 'peepso'),
				'callback' => array(&$this, 'recent_members_tab')
			)
		);

		$tabs = apply_filters('peepso_admin_dashboard_recent_metabox_tabs', $tabs);

		echo '<ul class="nav nav-tabs">', PHP_EOL;

		$first = TRUE;
		foreach ($tabs as $tab) {
			echo '<li class="', ($first ? 'active' : ''), '">
					<a href="#', $tab['id'], '" data-toggle="tab">', $tab['title'], '</a>
				</li>', PHP_EOL;

			$first = FALSE;
		}

		echo '</ul>', PHP_EOL;

		$first = TRUE;
		echo '<div class="tab-content">', PHP_EOL;

		foreach ($tabs as $tab) {
			echo '<div class="tab-pane ', ($first ? 'active' : ''), '" id="', $tab['id'], '">', PHP_EOL;
			echo call_user_func($tab['callback']);
			echo '</div>', PHP_EOL;

			$first = FALSE;
		}

		echo '</div>', PHP_EOL;
	}

	/*
	 * Display the content of the Posts tab under the Most Recent metabox
	 */
	public function recent_posts_tab()
	{
		$activities = PeepSoActivity::get_instance();

		$posts = $activities->get_all_activities(
			'post_date_gmt',
			'desc',
			5,
			0,
			array(
				'post_type' => PeepSoActivityStream::CPT_POST
			)
		);

		if (0 === $posts->post_count) {
			echo __('No recent posts.', 'peepso');
		} else {
			echo '<div class="dialogs">', PHP_EOL;

			foreach ($posts->posts as $post) {
				$type = get_post_type_object($post->post_type);
				$user = new PeepSoUser($post->post_author);

				echo '<div class="itemdiv dialogdiv">' , PHP_EOL;
				echo '	<div class="user">' , PHP_EOL;
				echo '		<img title="', $user->get_username(), '" alt="', esc_attr($user->get_username()), '" src="', $user->get_avatar(), '" />', PHP_EOL;
				echo '	</div>', PHP_EOL;
				echo '	<div class="body">', PHP_EOL;
				echo '		<div class="time">', PHP_EOL;
				echo '			<i class="ace-icon fa fa-clock-o"></i>', PHP_EOL;
				echo '			<span class="green">', PeepSoTemplate::time_elapsed(strtotime($post->post_date_gmt), current_time('timestamp')), ' </span>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div class="name">', PHP_EOL;
				echo '			<a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso')), '" target="_blank">', $user->get_fullname(), '</a>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div class="text">', ucfirst($type->labels->activity_action), ': "', substr(strip_tags($post->post_content), 0, 30), '"', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div class="tools">', PHP_EOL;
				echo '			<a href="', PeepSo::get_page('activity'), 'status/', $post->post_title, '/" title="', esc_attr(__('View post', 'peepso')), '" target="_blank" class="btn btn-minier btn-info">', PHP_EOL;
				echo '				<i class="icon-only ace-icon fa fa-share"></i>', PHP_EOL;
				echo '			</a>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '	</div>', PHP_EOL;
  				echo '</div>', PHP_EOL;
			}

			echo '</div>', PHP_EOL;

			echo '<div class="center cta-full">
					<a href="', admin_url('admin.php?page=peepso-activities'), '">',
						__('See all Activities', 'peepso'), ' &nbsp;
						<i class="fa fa-arrow-right"></i>
					</a>
				</div>', PHP_EOL;
		}
	}

	/*
	 * Display the content of the Comments tab under the Most Recent metabox
	 */
	public function recent_comments_tab()
	{
		$activities = PeepSoActivity::get_instance();

		$comments = $activities->get_all_activities(
			'post_date_gmt',
			'desc',
			5,
			0,
			array(
				'post_type' => PeepSoActivityStream::CPT_COMMENT
			)
		);

		if (0 === $comments->post_count) {
			echo __('No recent posts.', 'peepso');
		} else {
			echo '<div class="dialogs">', PHP_EOL;

			foreach ($comments->posts as $post) {
				$type = get_post_type_object($post->post_type);
				$user = new PeepSoUser($post->post_author);

				echo '<div class="itemdiv dialogdiv">', PHP_EOL;
				echo '	<div class="user">', PHP_EOL;
				echo '		<img title="', esc_attr($user->get_username()), '" alt="', esc_attr($user->get_username()), '" src="', $user->get_avatar(), '" />', PHP_EOL;
				echo '	</div>', PHP_EOL;
				echo '	<div class="body">', PHP_EOL;
				echo '		<div class="time">', PHP_EOL;
				echo '			<i class="ace-icon fa fa-clock-o"></i>', PHP_EOL;
				echo ' 			<span class="green">', PeepSoTemplate::time_elapsed(strtotime($post->post_date_gmt), current_time('timestamp')), '</span>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div class="name">', PHP_EOL;
				echo '			<a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso')), '" target="_blank">', $user->get_fullname(), '</a>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div class="text">', PHP_EOL;
				echo '			<i class="fa fa-quote-left"></i>', PHP_EOL;
				echo 			substr(strip_tags($post->post_content), 0, 30);
				echo '		</div>', PHP_EOL;
				echo '		<div class="tools">', PHP_EOL;
				echo '			<a href="', PeepSo::get_page('activity'), 'status/', $post->post_title, '/" title="', esc_attr(__('View comment', 'peepso')), '" target="_blank" class="btn btn-minier btn-info">', PHP_EOL;
				echo '				<i class="icon-only ace-icon fa fa-share"></i>', PHP_EOL;
				echo '			</a>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '	</div>', PHP_EOL;
  				echo '</div>', PHP_EOL;
			}

			echo '</div>', PHP_EOL;
		}
	}

	/*
	 * Display the content of the Members tab under the Most Recent metabox
	 */
	public function recent_members_tab()
	{
		global $wp_version, $wpdb;

		$args = array(
			'number' => 10,
			'orderby' => 'user_registered',
			'order' => 'DESC',
			'meta_key' => $wpdb->prefix . 'capabilities',
			'meta_value' => 'subscriber',
			'meta_compare' => 'LIKE'
		);

		$user_query = new WP_User_Query($args);

		if (0 === $user_query->total_users) {
			echo __('No users found', 'peepso');
		} else {
			$legacy_edit_link = (version_compare($wp_version, '3.5') < 0);

			foreach ($user_query->results as $user) {
				$user = new PeepSoUser($user->ID);

				if ($legacy_edit_link)
					$edit_link = admin_url('user-edit.php?user_id=' . $user->get_id());
				else
					$edit_link = get_edit_user_link($user->get_id());

				echo '<div class="itemdiv memberdiv clearfix">', PHP_EOL;
				echo '	<div class="user">', PHP_EOL;
				echo '		<a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso')), '" target="_blank">', PHP_EOL;
				echo '			<img alt="', esc_attr($user->get_firstname()), '" src="', $user->get_avatar(), '">', PHP_EOL;
				echo '		</a>', PHP_EOL;
				echo '	</div>', PHP_EOL;
				echo '	<div class="body">', PHP_EOL;
				echo '		<div class="name">', PHP_EOL;
				echo '			<a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso')), '" target="_blank">', $user->get_fullname(), '</a>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div class="time">', PHP_EOL;
				echo '			<i class="ace-icon fa fa-clock-o"></i>', PHP_EOL;
				echo '			<span class="green">', PeepSoTemplate::time_elapsed(strtotime($user->get_date_registered()), current_time('timestamp')), '</span>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '		<div>', PHP_EOL;
				echo '			<span class="label label-success arrowed-in">', implode(', ', $user->get_role()), '</span>', PHP_EOL;
				echo '			<a href="', $edit_link, '" title="', esc_attr(__('Edit this user', 'peepso')), '"><i class="ace-icon fa fa-edit"></i></a>', PHP_EOL;
				echo '		</div>', PHP_EOL;
				echo '	</div>', PHP_EOL;
				echo '</div>', PHP_EOL;
			}

			echo '<div class="clearfix"></div>', PHP_EOL;
		}

		echo '<div class="center cta-full">
			<a href="', admin_url('users.php'), '">',
				__('See all Members', 'peepso'), ' &nbsp;
				<i class="fa fa-arrow-right"></i>
			</a>
		</div>', PHP_EOL;
	}



	private static function plugin_exists($filename, $class)
	{
		if(class_exists($class)) {
			return true;
		}
	}
	/*
	 * Displays the "child plugins" metabox
	 */
	public function child_plugins_bundles()
	{
		$content =  PeepSoRemoteContent::get('peepso_ws_bundles', 'peepso_welcome_screen_bundles.html');

		if(stristr($content, '<!--content-->')){
			$content = explode('<!--content-->', $content);
			$content = $content[1];
		}

		echo $content;
	}


	public function child_plugins()
	{
		$content = PeepSoRemoteContent::get('peepso_ws_plugins', 'peepso_welcome_screen_plugins.html');

		if(stristr($content, '<!--content-->')){
			$content = explode('<!--content-->', $content);
			$content = $content[1];
		}

		echo $content;
	}

	/*
	 * Displays the User Engagement metabox and gathers additional tabs from other plugins
	 */
	public function engagement_metabox()
	{
		add_filter(
			'peepso_admin_dashboard_engagement-' . PeepSoActivity::MODULE_ID . '_stat_types',
			array(&$this, 'stream_stat_types'));
		// This metabox's default tabs
		$tabs = array(
			array(
				'id' => 'engagment-stream',
				'title' => __('Stream', 'peepso'),
				'callback' => array(&$this, 'engagement_tab'),
				'module_id' => PeepSoActivity::MODULE_ID
			)
		);

		$tabs = apply_filters('peepso_admin_dashboard_engagement_metabox_tabs', $tabs);

		echo '<ul class="nav nav-tabs">';

		$first = TRUE;
		foreach ($tabs as $tab) {
			echo '<li class="', ($first ? 'active' : ''), '" data-module-id=', $tab['module_id'], '>
					<a href="#', $tab['id'], '" data-toggle="tab">', $tab['title'], '</a>
				</li>';

			$first = FALSE;
		}

		echo '</ul>';

		$first = TRUE;
		echo '<div class="tab-content">';

		foreach ($tabs as $tab) {
			echo '<div class="tab-pane ', ($first ? 'active' : ''), '" id="', $tab['id'], '">';
			echo call_user_func_array($tab['callback'], array($tab['module_id']));
			echo '</div>';

			$first = FALSE;
		}

		echo '</div>';
	}

	/*
	 * Renders the contents of the tab under the User Engagement metabox
	 * @param string $module_id MODULE_ID of the plugin from which the data will be referencing
	 */
	public function engagement_tab($module_id)
	{
		$date_range_filters = apply_filters('peepso_admin_dashboard_' . $module_id . '_date_range',
			array(
				'this_week' => __('This week', 'peepso'),
				'last_week' => __('Last week','peepso'),
				'this_month' => __('This month', 'peepso'),
				'last_month' => __('Last month', 'peepso'),
			)
		);

		$stat_types = apply_filters('peepso_admin_dashboard_engagement-' . $module_id . '_stat_types', array());

		// Content is called via ajax PeepSoActivity::get_graph_data()
		echo '<div class="container-fluid">
				<div class="row">
					<div class="col-xs-12">
						<select name="engagement_', $module_id, '_date_range" class="engagement_date_range">', PHP_EOL;

		foreach ($date_range_filters as $val => $date_range)
			echo '<option value="', $val, '">', $date_range, '</option>', PHP_EOL;

		echo '			</select>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12 graph-container"></div>
					<div class="col-xs-12 series-container">', PHP_EOL;

		foreach ($stat_types as $stat) {
			echo '<label>
					<input value="', $stat['stat_type'], '" type="checkbox" name="stats[]" checked="checked" id="id', $stat['label'], '" style="margin:0">
					<span class="lbl" for="id', $stat['label'], '">', ucwords($stat['label']), '</span> &nbsp; &nbsp;
				</label>', PHP_EOL;
		}

		echo '		</div>
				</div>
			</div>', PHP_EOL;
	}

	/**
	 * Define which stats to track on the dashboard for the 'activity' module
	 * @param array $types
	 * @return array Stat types
	 */
	public function stream_stat_types($types)
	{
		return array(
			array(
				'label' => __('posts', 'peepso'),
				'stat_type' => PeepSoActivityStream::CPT_POST
			),
			array(
				'label' => __('comments', 'peepso'),
				'stat_type' => PeepSoActivityStream::CPT_COMMENT
			),
			array(
				'label' => __('likes', 'peepso'),
				'stat_type' => 'likes'
			)
		);
	}

	/**
	 * Fires after the user's role has changed.
	 * @param int    $user_id   The user ID.
	 * @param string $role      The new role.
	 * @param array  $old_roles An array of the user's previous roles.
	 */
//	public function set_user_role($user_id, $role, $old_roles)
//	{
//		if ('peepso_member' === $role && in_array('peepso_verified', $old_roles)) {
//			$user = new PeepSoUserAdmin($user_id);
//			$user->approve_user();
//		}
//	}
}

// EOF

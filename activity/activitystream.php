<?php
/*
Plugin_Name PeepSo Activity Stream
Plugin_URI http://www.peepso.com
Description_Activity Stream add-on for PeepSo - The next generation Social Networking solution for your web site.
Author_PeepSo
Author_URI http://www.peepso.com
Version_0.9.0
*/

class PeepSoActivityStream
{
	private static $_instance = NULL;
	private $activity_shortcode = NULL;

	const PLUGIN_NAME = 'PeepSo Activity Stream';
	const PLUGIN_SLUG = 'peepso-activity';
	const PLUGIN_VERSION = '0.9.0';

	const CPT_POST = 'peepso-post';
	const CPT_COMMENT = 'peepso-comment';
	
	private function __construct()
	{
		if (is_admin()) {
			add_filter('peepso_admin_dashboard_tabs', array('PeepSoActivityAdmin', 'add_dashboard_tabs'));
			add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));

			add_filter('manage_users_custom_column', array(&$this, 'filter_user_columns'), 20, 3);
			add_action('manage_users_columns', array(&$this, 'filter_user_list_columns'));
		} else {
			add_action('wp', array(&$this, 'check_query'));
		}
		PeepSo::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);

		// register post types
		add_action('init', array(&$this, 'register_cpts'));
		add_action('deleted_user', array(&$this, 'delete_callback'), 10, 1);
	}


	public function enqueue_scripts()
	{
		wp_register_script('adminactivityreport-js', PeepSo::get_asset('js/adminactivityreport.js'),
			array('peepso'), PeepSoActivityStream::PLUGIN_VERSION, TRUE);
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


	/*
	 * checks query object to see if it's the Activity Stream page
	 * if it is, set up short code early so it can register things
	 */
	public function check_query()
	{
		global $wp_query;

//PeepSo::log(__METHOD__.'() checking');
//PeepSo::log('  uri: ' . $_SERVER['REQUEST_URI']);
//PeepSo::log('  option: ' . PeepSo::get_option('page_activity'));
//PeepSo::log('  page: ' . PeepSo::get_page('activity'));

//global $post;
//PeepSo::log(__METHOD__.'() post content=' . $post->post_content);

		// always initialize #59
		$this->activity_shortcode = PeepSoActivityShortcode::get_instance();

		$url_root = PeepSo::get_option('page_activity');
//		$url = trim($_SERVER['REQUEST_URI'], '/');
		$url = PeepSo::get_page_url();
		$virt_page = PeepSo::get_option('page_activity') . '/status';
		if (substr($url, 0, strlen($virt_page)) === $virt_page && $wp_query->is_404) {
//PeepSo::log('  virt page: ' . $_SERVER['REQUEST_URI']);

//			$this->activity_shortcode = PeepSoActivityShortcode::get_instance();
			$this->activity_shortcode->set_page($url_root, substr($url, strlen($url_root) + 1));
			return;
			$path = trim(parse_url($url, PHP_URL_PATH), '/');
			$parts = explode('/', $path, 2);
			$page = $parts[0];
			$extra = isset($parts[1]) ? $parts[1] : '';
		}

		// use config setting to determin which page the Activity Stream is on
		if (isset($wp_query->queried_object)) {
			if (PeepSo::get_option('page_activity') === $wp_query->queried_object->post_name) {
//				$this->activity_shortcode = PeepSoActivityShortcode::get_instance();

				add_filter('wp_title', array(&$this, 'set_activity_page_title'), 10, 2);
			}
		}
	}

	/*
	 * registers custom post types used by the Activity Stream module
	 */
	public function register_cpts()
	{
		// post type for Activity Stream Posts
		$labels = array(
			'name'					=> _x('PeepSo Posts', 'PeepSo Posts', 'peepso'),
			'singular_name'			=> _x('PeepSo Post', 'PeepSo Post', 'peepso' ),
			'menu_name'				=> __('PeepSo Posts', 'peepso'),
			'parent_item_colon'		=> __('PeepSo Posts:', 'peepso'),
			'all_items'				=> __('All PeepSo Posts', 'peepso'),
			'view_item'				=> __('View PeepSo Post', 'peepso'),
			'add_new_item'			=> __('Add New PeepSo Post', 'peepso'),
			'add_new'				=> __('Add New PeepSo Post', 'peepso'),
			'edit_item'				=> __('Edit PeepSo Post', 'peepso'),
			'update_item'			=> __('Update PeepSo Post', 'peepso'),
			'search_items'			=> __('Search PeepSo Posts', 'peepso'),
			'not_found'				=> __('Not found', 'peepso'),
			'not_found_in_trash'	=> __('Not found in Trash', 'peepso'),
			'activity_action'		=> __('posted', 'peepso'),
			'activity_type'			=> __('post', 'peepso')
		);
		$rewrite = array(
			'slug'					=> 'ps_posts',
			'with_front'			=> FALSE,
			'pages'					=> TRUE,
			'feeds'					=> FALSE,
		);
		$args = array(
			'label'					=> __('PeepSo Posts', 'peepso'),
			'description'			=> __('PeepSo Posts', 'peepso'),
			'labels'				=> $labels,
//			'supports'				=> array('title', 'editor'),
//			'taxonomies'			=> array('category'),
			'hierarchical'			=> FALSE,
			'public'				=> FALSE,
			'show_ui'				=> FALSE,
			'show_in_menu'			=> FALSE,
			'show_in_nav_menus'		=> FALSE,
			'show_in_admin_bar'		=> FALSE,
//			'menu_position'			=> 5,
//			'menu_icon'				=> '',
			'can_export'			=> FALSE,
			'has_archive'			=> FALSE,
			'exclude_from_search'	=> TRUE,
			'publicly_queryable'	=> FALSE,
//			'rewrite'				=> FALSE,
			'capability_type'		=> 'page',
		);
		register_post_type(self::CPT_POST, $args);

		// post type for Activity Stream Comments
		$labels = array(
			'name'					=> __('PeepSo Comments', 'peepso'),
			'singular_name'			=> __('PeepSo Comment', 'peepso' ),
			'menu_name'				=> __('PeepSo Comments', 'peepso'),
			'parent_item_colon'		=> __('PeepSo Comments:', 'peepso'),
			'all_items'				=> __('All PeepSo Comments', 'peepso'),
			'view_item'				=> __('View PeepSo Comment', 'peepso'),
			'add_new_item'			=> __('Add New PeepSo Comment', 'peepso'),
			'add_new'				=> __('Add New PeepSo Comment', 'peepso'),
			'edit_item'				=> __('Edit PeepSO Comment', 'peepso'),
			'update_item'			=> __('Update PeepSo Comment', 'peepso'),
			'search_items'			=> __('Search PeepSo Comments', 'peepso'),
			'not_found'				=> __('Not found', 'peepso'),
			'not_found_in_trash'	=> __('Not found in Trash', 'peepso'),
			'activity_action'		=> __('commented', 'peepso'),
			'activity_type'			=> __('comment', 'peepso')
		);
		$rewrite = array(
			'slug'					=> 'ps_comments',
			'with_front'			=> FALSE,
			'pages'					=> TRUE,
			'feeds'					=> FALSE,
		);
		$args = array(
			'label'					=> __('PeepSo Comments', 'peepso'),
			'description'			=> __('PeepSo Comments', 'peepso'),
			'labels'				=> $labels,
//			'supports'				=> array('title', 'editor'),
//			'taxonomies'			=> array('category'),
			'hierarchical'			=> FALSE,
			'public'				=> FALSE,
			'show_ui'				=> FALSE,
			'show_in_menu'			=> FALSE,
			'show_in_nav_menus'		=> FALSE,
			'show_in_admin_bar'		=> FALSE,
//			'menu_position'			=> 5,
//			'menu_icon'				=> '',
			'can_export'			=> FALSE,
			'has_archive'			=> FALSE,
			'exclude_from_search'	=> TRUE,
			'publicly_queryable'	=> FALSE,
//			'rewrite'				=> $rewrite,
			'capability_type'		=> 'page',
		);
		register_post_type(self::CPT_COMMENT, $args);
	}


	/*
	 * called from wp_delete_user() to signal a user will be deleted
	 * @param int $id The id of the user that is to be deleted
	 */
	public function delete_callback($id)
	{
		global $wpdb;
		$sql = "DELETE FROM `{$wpdb->prefix}peepso_activities` " .
				" WHERE `act_owner_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $id));

		$sql = "DELETE FROM `{$wpdb->prefix}peepso_activity_hide` " .
				" WHERE `hide_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $id));
	}


	/*
	 * Add custom columns to user list
	 * @param array $columns Columns in display
	 */
	public function filter_user_list_columns($columns)
	{
		$columns['activities'] = __('Activities', 'peepso');
		return ($columns);
	}


	/*
	 * Return data for Activity Stream's added column
	 * @param mixed $value Current value to display
	 * @param string $column_name Name of column
	 * @param int $user_id The user id being displayed
	 * @return string The value to be displayed for the named column
	 */
	public function filter_user_columns($value, $column_name, $user_id)
	{
		switch ($column_name)
		{
		case 'activities':
			$act = PeepSoActivity::get_instance();
			$value = $act->get_posts_by_user($user_id);
			break;
		}
		
		return ($value);
	}

	public function set_activity_page_title($title, $sep)
	{
		return PeepSo::get_option('site_frontpage_title') . ' ' . $sep . ' ' . get_bloginfo( 'name' );
	}
}

PeepSoActivityStream::get_instance();

// EOF

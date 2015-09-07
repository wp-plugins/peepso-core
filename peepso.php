<?php
/**
 * Plugin Name: PeepSo
 * Plugin URI: https://peepso.com
 * Description: Social Networking Plugin for WordPress
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.2.1
 * Copyright: (c) 2015 PeepSo, Inc. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepso
 * Domain Path: /language
 *
 * PeepSo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * PeepSo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY. See the
 * GNU General Public License for more details.
 */

/*
 * Main plugin declaration
 * @package PeepSo
 * @author PeepSo
 */
class PeepSo
{
    const DEBUG = FALSE;
    const MODULE_ID = 0;
    const PLUGIN_VERSION = '1.2.1';
	const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE
    const PLUGIN_NAME = 'PeepSo';
    const PLUGIN_SLUG = 'peepso_';
	const PEEPSOCOM_LICENSES = 'http://tiny.cc/peepso-licenses';

    const ACCESS_FORCE_PUBLIC = -1;
    const ACCESS_PUBLIC = 10;
    const ACCESS_MEMBERS = 20;
    const ACCESS_PRIVATE = 40;
    const CRON_MAILQUEUE = 'peepso_mailqueue_send_event';
    const CRON_DAILY_EVENT = 'peepso_daily_event';
    const CRON_WEEKLY_EVENT = 'peepso_weekly_event';


    private static $_instance = NULL;
    private static $_current_shortcode = NULL;

    private $_widgets = array(
        'PeepSoWidgetMe',
    );

    /* array of paths to use in autoloading */
    private static $_autoload_paths = array();

    /* options data */
    private static $_config = NULL;

    private $is_ajax = FALSE;

	private $wp_title = array();

    public $shortcodes= array(
            'peepso_activity' => 'PeepSoActivityShortcode::get_instance',
            'peepso_profile' => 'PeepSo::profile_shortcode',
            'peepso_register' => 'PeepSo::register_shortcode',
            'peepso_recover' => 'PeepSo::recover_shortcode',
            'peepso_members' => 'PeepSo::search_shortcode',
    );

    private function __construct()
    {
        self::log(NULL);
        // set up autoloading
        self::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
        self::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR);
        $res = spl_autoload_register(array(&$this, 'autoload'));

        PeepSoTemplate::add_template_directory(dirname(__FILE__));

        // add five minute schedule to be used by mailqueue
        add_filter('authenticate', array(&$this, 'auth_signon'), 30, 3);
        add_filter('allow_password_reset', array(&$this, 'allow_password_reset'), 20, 2);
        add_filter('body_class', array(&$this,'body_class_filter'));
        add_filter('cron_schedules', array(&$this, 'filter_cron_schedules'));
        add_filter('peepso_widget_me_links', array(&$this, 'peepso_widget_me_links'));
        add_filter('peepso_widget_args_internal', array(&$this, 'peepso_widget_args_internal'));
        add_filter('peepso_widget_instance', array(&$this, 'peepso_widget_instance'));
        add_filter('peepso_activity_more_posts_link', array(&$this, 'peepso_activity_more_posts_link'));
        add_filter('the_title', array(&$this,'the_title'), 5, 2);
        add_filter('get_avatar', array(&$this, 'filter_avatar'), 20, 5);

        register_sidebar(array(
            'name'=> 'PeepSo',
            'id' => 'peepso',
            'description' => 'Area reserved for PeepSo Integrated widgets. Widgets that are not "PeepSo Integrated" will not be shown on the page.',
        ));

        add_filter('peepso_profile_segment_menu_links', array(&$this, 'peepso_profile_segment_menu_links'));

        if (defined('DOING_CRON') && DOING_CRON) {
            PeepSoCron::initialize();
        }

        add_action('plugins_loaded', array(&$this, 'load_textdomain'));

        // setup plugin's hooks
        if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            add_action('admin_init', array(__CLASS__, 'can_install'));
            add_action('init', array(&$this, 'check_admin_access'));

            // @todo  #269 - welcome email must be opt-in, re-implement along with the first activation welcome screen

            if (!self::get_option('peepso_welcome_screen_done') || isset($_GET['nocache_welcome_screen'])) {
            $settings = PeepSoConfigSettings::get_instance();
                $settings->remove_option('peepso_welcome_screen_done');
                add_action('plugins_loaded',array(&$this,'welcome_screen'));
            }

            PeepSoAdmin::get_instance();
        } else {
            $this->register_shortcodes();
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));

            add_action('wp_loaded', array(&$this, 'check_ajax_query'));
            add_action('wp', array(&$this, 'check_query'), 1);
        }

        add_action('admin_init', array(&$this, 'activation_redirect'));
        add_action('init', array(&$this, 'init_callback'), 50);
        add_action('set_current_user', array(&$this, 'set_user'));

        add_action( 'save_post', array(&$this,'peepso_save_post_action'),1,3);

        // activation hooks
        register_activation_hook(__FILE__, array(&$this, 'activate'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));

        // register widgets
        add_action('widgets_init', array(&$this, 'widgets_init'));
        add_filter( 'peepso_widget_prerender', array(&$this, 'get_widgets_in_position'));
        add_filter( 'peepso_widget_form', array(&$this, 'get_widget_form'));
        add_filter( 'peepso_widget_list_positions', array(&$this, 'get_widget_positions'));

        add_filter('peepso_access_types', array(&$this, 'filter_access_types'));

		add_filter( 'wp_title', array(&$this, 'peepso_change_page_title'), 100, 2);
		add_filter('peepso_page_title', array(&$this,'peepso_page_title'));
    }

    public function peepso_save_post_action($post_id, $post, $update)
    {
        // ignore if the [age content is not a PeepSo shortcode
        if(!stristr($content = $post->post_content,'[peepso_')) {
            return FALSE;
        }

        // we assume the content is [peepso_something] so we trim the brackets
        $content = trim($content,'[]');

        // register core filter
        add_filter('peepso_save_post', array(&$this, 'peepso_save_post'));

        // look for peepso_something in other filters
        $option = apply_filters('peepso_save_post', $content);

        // if something changed, update the option
        if ($option != $content) {
            $settings = PeepSoConfigSettings::get_instance();
            $settings->set_option($option, $post->post_name);
        }
    }

    public function peepso_save_post($shortcode)
    {
        if(array_key_exists($shortcode, $this->shortcodes)) {
            $page = 'page_'.str_replace(array('peepso_','peepso'),'',$shortcode);
            return $page;
        }

        return $shortcode;
    }

    public function opengraph_tags()
    {
        $tags = array(
            'description' => 'og desc',
            'image'       => 'img',
        );

        foreach($tags as $key => $val) {
            $key = 'og:'.esc_attr($key);
            $val = esc_attr($val);

            echo "<meta property=\"$key\" name=\"$key\" content=\"$val\" />\n";
        }
    }

    public function peepso_page_title( $title )
    {
        if ('peepso_members' == $title['title']) {
            $title['newtitle'] = __('Members', 'peepso');
        }

        return $title;
    }

	public function peepso_change_page_title($title, $sep){

        if ( !is_admin() ) {

	        $post = get_post();

            if ( isset($post->post_content) && '[peepso' == substr($post->post_content,0,7) ) {
	            $old_title 	= $title;
			    $title 		= trim($post->post_content,'[]');
                $title 		= apply_filters('peepso_page_title', array('title'=>$title,'newtitle'=>$title));

				if (isset($title['newtitle']) && $title['newtitle'] != '') {
					$this->wp_title = array('old_title' => $old_title, 'title' => $title['title'], 'newtitle' => $title['newtitle']);
					return $title['newtitle'];
				}
            }
        }

		return $title;
	}

    public function the_title($title, $post_id) {

        if (in_the_loop() && !is_admin() ) {

			$post = get_post();

            if ( isset($post->post_content) && '[peepso' == substr($post->post_content,0,7) ) {
				$old_title 	= $title;
                $title 		= trim($post->post_content,'[]');

				if (empty($this->wp_title) || (isset($this->wp_title['newtitle']) && $this->wp_title['newtitle'] == '')) {
                	$this->wp_title 				= apply_filters('peepso_page_title', array('title'=>$title,'newtitle'=>$title));
					$this->wp_title['old_title'] 	= $old_title;
				}
                $title= ''
                    . '<span id="peepso_page_title">'.$this->wp_title['newtitle'].'</span>'
                    . '<span id="peepso_page_title_old" style="display:none">'.$old_title.'</span>';
            }
        }

        return $title;
    }

    /**
     * Loads the translation file for the PeepSo plugin
     */
    public function load_textdomain()
    {
        $path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
        load_plugin_textdomain('peepso', FALSE, $path);
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


    /*
     * Checks for AJAX queries, sets up AJAX Handler
     */
    public function check_ajax_query()
    {
//PeepSo::log(__METHOD__.'()');
        global $wp_query;

        $sPageName = $_SERVER['REQUEST_URI'];
        $path = trim(parse_url($sPageName, PHP_URL_PATH), '/');
//PeepSo::log('check for AJAX request: ' . $sPageName);
//PeepSo::log('  path=' . $path);
//PeepSo::log('  req=' . var_export($_REQUEST, TRUE));

        $parts = explode('/', $path);
        $segment = count($parts) - 2;

        if ($segment >= 0 && 'peepsoajax' === $parts[$segment]) {
            $page = (isset($parts[$segment + 1]) ? $parts[$segment + 1] : '');
            new PeepSoAjaxHandler($page);		// loads AJAX handling code

            header('HTTP/1.0 200 OK');			// reset HTTP result code, no longer a 404 error
            $wp_query->is_404 = FALSE;
            $wp_query->is_page = TRUE;
            $wp_query->is_admin = FALSE;
            unset($wp_query->query['error']);


            if (array_key_exists('HTTP_REFERER', $_SERVER)) {
                setcookie('peepso_last_visited_page', $_SERVER['HTTP_REFERER'], time() + (MINUTE_IN_SECONDS * 30), '/');
            }

            $this->is_ajax = TRUE;
            return;
        }
    }


    /*
     * Called when WP is loaded; need to signal PeepSo plugins that everything's ready
     */
    public function init_callback()
    {
        PeepSo::log(__METHOD__.'()');
        do_action('peepso_init');
        $act = new PeepSoActivityShortcode();
    }


    /*
     * Initialize all PeepSo widgets
     */
    public function widgets_init()
    {
        $this->_widgets = apply_filters('peepso_widgets', $this->_widgets);

        if (count($this->_widgets)) {
            foreach ($this->_widgets as $widget_name) {
                register_widget($widget_name);
            }
        }
    }

    /*
     * Load widget instances for a given position
     */
    public function get_widgets_in_position($profile_position){

        $widgets = wp_get_sidebars_widgets();

        $result_widgets = array();

        foreach($widgets as $position => $list) {

            // SKIP if the position name does not start with peepso
            if ('peepso' != substr($position,0,6)){
                continue;
            }

            // SKIP if the position is empty
            if (!count($list)) {
                continue;
            }

            $widget_instances = array();

            // loop through widgets in a position
            foreach($list as $widget) {

                // SKIP if the widget name does not contain "peepsowidget"
                if (!stristr($widget, 'peepsowidget')) {
                    continue;
                }

                // remove "peepsowidget"
                $widget = str_ireplace('peepsowidget', '', $widget);

                // extract last part of class name and id of the instance
                // eg "videos-1" becomes "videos" and "1"
                $widget = explode('-', $widget);

                $widget_class = 'PeepSoWidget'.ucfirst($widget[0]);
                $widget_instance_id = $widget[1];

                // to avoid creating multiple instances  use the local aray to store repeated widgets
                if (!array_key_exists($widget_class, $widget_instances) && class_exists($widget_class)) {
                    $widget_instance = new $widget_class;
                    $widget_instances[$widget_class] = $widget_instance->get_settings();
                }

                // load the instance we are interested in (eg PeepSoVideos 1)
                if (array_key_exists($widget_class, $widget_instances)){
                    $current_instance = $widget_instances[$widget_class][$widget_instance_id];
                } else {
                    continue;
                }
                // SKIP if the instance isn't in a valid position
                if ($current_instance['position'] != $profile_position) {
                    continue;
                }

                $current_instance['widget_class'] = $widget_class;

                // add to result array
                $result_widgets[]=$current_instance;
            }
        }

        return $result_widgets;
    }

    /**
     * Returns HTML used to render options for PeepSo Widgets (including profile widgets)
     * @TODO parameters (optional/additional fields) when needed
     * @TODO text domain
     * @param $widget
     * @return array
     */
    public function get_widget_form($widget)
    {
        $widget['html'] = $widget['html'] . PeepSoTemplate::exec_template('widgets', 'admin_form', $widget, true);
        return $widget;
    }

    public function get_widget_positions($positions)
    {
        return array_merge($positions, array('profile_sidebar_top', 'profile_sidebar_bottom', 'profile_main'));
    }

    /*
     * checks current URL to see if it's one of the PeepSo specific pages
     * If it is, loads the appropriate shortcode early so it can set up it's hooks
     */
    public function check_query()
    {
        if ($this->is_ajax)
            return;

        // check if a logout is requested
        if (isset($_GET['logout'])) {
            setcookie('peepso_last_visited_page', '', time() - 3600);
            wp_logout();
            PeepSo::redirect(self::get_page('activity'));
        } else
            setcookie('peepso_last_visited_page', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}", time() + (MINUTE_IN_SECONDS * 30), '/');

        $url = new PeepSoUrlSegments();

        // If permalinks are turned on use the post name instead. For example 'register':
        // TODO: this is probably no longer needed
        $pl = get_option('permalink_structure');
        if (!empty($pl)) {
            global $post;
            if (NULL !== $post)
                $page = $post->post_name;
        }

        $sc = NULL;

        switch ($url->get(0))
        {
            case 'peepso_profile':				// PeepSo::get_option('page_profile'):
                $sc = PeepSoProfileShortcode::get_instance();
                break;

            case 'peepso_recover':				// PeepSo::get_option('page_recover'):
                PeepSoRecoverPasswordShortcode::get_instance();
                break;

            case 'peepso_register':				// PeepSo::get_option('page_register'):
                PeepSoRegisterShortcode::get_instance();
                break;

            case 'peepso_activity':
                $sc = PeepSoActivityShortcode::get_instance();
                break;

            default:
                $sc = apply_filters('peepso_check_query', NULL, $url->get(0));
                break;
        }

        if (NULL !== $sc) {
            add_filter( 'the_title', ARRAY(&$this,'the_title'), 10, 2 );
            $sc->set_page($url);
        }
        PeepSo::log(__METHOD__.'() - returning');
    }


    /*
     * Checks the user role and redirects non-admin requests back to the front of the site
     */
    public function check_admin_access()
    {
        return;
        $role = self::_get_role();
        if ('admin' !== $role) {
            PeepSo::redirect(get_home_url());
        }

        // if it's a "peepso_" user, redirect to the front page
//		$sRole = self::get_user_role();
//		if (substr($sRole, 0, 7) == 'peepso_') {
//			PeepSo::redirect(get_home_url());
//			die;
//		}
    }


    /*
     * autoloading callback function
     * @param string $class name of class to autoload
     * @return TRUE to continue; otherwise FALSE
     */
    public function autoload($class)
    {
        // setup the class name
        $classname = $class = strtolower($class);
        if ('peepso' === substr($class, 0, 6))
            $classname = substr($class, 6);		// remove 'peepso' prefix on class file name

        // check each path
        $continue = TRUE;
        foreach (self::$_autoload_paths as $path) {
            $classfile = $path . $classname . '.php';
            if (file_exists($classfile)) {
                require_once($classfile);
                $continue = FALSE;
                break;
            }
        }
        return ($continue);
    }


    /*
     * Adds a directory to the list of autoload directories. Can be used by add-ons
     * to include additional directories to look for class files in.
     * @param string $dirname the directory name to be added
     */
    public static function add_autoload_directory($dirname)
    {
        if (substr($dirname, -1) != DIRECTORY_SEPARATOR)
            $dirname .= DIRECTORY_SEPARATOR;
//self::log("adding directory [{$dirname}] to path list");
        self::$_autoload_paths[] = $dirname;
    }


    /*
     * called on plugin first activation
     */
    public function activate()
    {
        PeepSo::log('PeepSo::activate() called');
        if ($this->can_install()) {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
            $install = new PeepSoActivate();
            $res = $install->plugin_activation();
            if (FALSE === $res) {
                // error during installation - disable
                deactivate_plugins(plugin_basename(__FILE__));
            } else if (NULL === get_option('peepso_install_date', NULL)) {
                add_option('peepso_do_activation_redirect', TRUE);
                add_option('peepso_install_date', date('Y-m-d'));
            }
        }
    }

    /**
     * Redirects to the File Systems settings after activation, to setup directories.
     */
    public function activation_redirect()
    {
        // stage 1, redirect to filesystem settings
        if (get_option('peepso_do_activation_redirect', FALSE) || isset($_GET['freshinstall']))  {
            set_transient('peepso_set_welcome_screen',1,3600*24*365);
            delete_option('peepso_do_activation_redirect');
            PeepSo::redirect('admin.php?page=peepso_config&tab=filesystem');
        } else {
            // stage 2, mark a future welcome page redirect
            if(get_transient('peepso_set_welcome_screen')) {
                delete_transient('peepso_set_welcome_screen');
                set_transient('peepso_do_welcome_screen',1,3600*24*365);
            } else {
                // stage 3, redirect to welcome screen
                if(get_transient('peepso_do_welcome_screen')) {
                    delete_transient('peepso_do_welcome_screen');
                    PeepSo::redirect('admin.php?page=peepso-welcome');
                }
            }
        }
    }

    /*
     * Method for determining if permalinks are turned on and disabling PeepSo if not
     * @return Boolean TRUE if a permalink structure is defined; otherwise FALSE
     */
    public static function has_permalinks()
    {
        if (!get_option('permalink_structure')) {
            if (isset($_GET['activate']))
                unset($_GET['activate']);

            deactivate_plugins(plugin_basename(__FILE__));

            $msg = sprintf(__('Cannot activate PeepSo; it requires <b>Permalinks</b> to be enabled. Go to <a href="%1$s">Settings -&gt; Permalinks</a> and select anything but the <i>Default</i> option.', 'peepso'),
                get_admin_url(get_current_blog_id()) . 'options-permalink.php');
            PeepSoAdmin::get_instance()->add_notice($msg);

            if (is_plugin_active(plugin_basename(__FILE__))) {
                PeepSo::deactivate();
            }
            return (FALSE);
        }
        return (TRUE);
    }


    /**
     * Checks whether PeepSo can be installed on the current hosting and Wordpress setup.
     * Checks if necessary directories are writeable and if permalinks are enabled.
     *
     * @return boolean TRUE|FALSE if install is possible.
     */
    public static function can_install()
    {
        if (!is_writable(WP_CONTENT_DIR)) {
            if (isset($_GET['activate']))
                unset($_GET['activate']);

            deactivate_plugins(plugin_basename(__FILE__));

            PeepSoAdmin::get_instance()->add_notice(
                sprintf(__('PeepSo requires the %1$s folder to be writable.', 'peepso'), WP_CONTENT_DIR));

            if (is_plugin_active(plugin_basename(__FILE__)))
                PeepSo::deactivate();

            return (FALSE);
        }

        return (self::has_permalinks());
    }

    /*
     * called on plugin deactivation
     */
    public function deactivate()
    {
        PeepSo::log('PeepSo::deactivate() called');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'deactivate.php');
        PeepSoUninstall::plugin_deactivation();
    }

    /*
     * enqueue scripts needed
     */
    public function enqueue_scripts()
    {
        wp_register_style('peepso', PeepSo::get_template_asset(NULL, 'css/template.css'),
            NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepso');

        wp_register_style('peepso-icons', PeepSo::get_template_asset(NULL, 'css/icons.css'),
            NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepso-icons');

        wp_register_style('peepso-window', PeepSo::get_asset('css/window.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepso-window');

        wp_register_style('peepso-lightbox', PeepSo::get_asset('css/lightbox.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepso-lightbox');

        wp_register_style('peepso-fileupload', PeepSo::get_asset('css/jquery.fileupload.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');

        $custom = locate_template('peepso/custom.css');
        // only enqueue if custom.css exists in theme/peepso directory
        if (!empty($custom)) {
            $custom = get_stylesheet_directory_uri() . '/peepso/custom.css';
            wp_register_style('peepso-custom', $custom, array('peepso', 'bootstrap', 'peepso-activitystream-css'),
                PeepSo::PLUGIN_VERSION, 'all');
            wp_enqueue_style('peepso-custom');
        }

        wp_enqueue_script('bootstrap', PeepSo::get_asset('aceadmin/js/bootstrap.min.js'),
            array('jquery'), self::PLUGIN_VERSION, TRUE);

        wp_register_script('peepso', PeepSo::get_asset('js/peepso.min.js'),
            array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

        $aData = array(
            'ajaxurl' => get_bloginfo('wpurl') . '/peepsoajax/',
            'version' => PeepSo::PLUGIN_VERSION,
            'postsize' => PeepSo::get_option('site_status_limit', 4000),
            'currentuserid' => PeepSo::get_user_id(),
            'userid' => apply_filters('peepso_user_profile_id', 0),		// user id of the user being viewed (from PeepSoProfileShortcode)
            'objectid' => apply_filters('peepso_object_id', 0),			// user id of the object being viewed
            'objecttype' => apply_filters('peepso_object_type', ''),	// type of object being viewed (profile, group, etc.)
            'date_format' => dateformat_PHP_to_jQueryUI(get_option('date_format')),
            'notifications_page' => $this->get_page('notifications'),
            'members_page' => $this->get_page('members'),
            'open_in_new_tab' => PeepSo::get_option('site_activity_open_links_in_new_tab'),
            'loading_gif' => PeepSo::get_asset('images/ajax-loader.gif'),
            'upload_size' => wp_max_upload_size(),
            'peepso_nonce' => wp_create_nonce('peepso-nonce'),
            // TODO: all labels and messages, etc. need to be moved into HTML content instead of passed in via js data
            // ART: Which template best suited to define the HTML content for these labels?
            // TODO: the one in which they're used. The 'Notice' string isn't used on all pages. Find the javascript that uses it and add it to that page's template
            'label_error' => __('Error', 'peepso'),
            'label_notice' => __('Notice', 'peepso'),
            'view_all_text' => __('View All', 'peepso'),
            'mime_type_error' => __('The file type you uploaded is not allowed.', 'peepso'),
        );
        wp_localize_script('peepso', 'peepsodata', $aData);

        wp_enqueue_script('peepso');

        wp_register_script('peepso-observer', PeepSo::get_asset('js/observer.min.js'), array(), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso-observer');

        wp_register_script('peepso-time', PeepSo::get_asset('js/time.min.js'), array('jquery', 'peepso-observer'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso-time');

        // register these but don't enqueue them. The templates will do that if needed
        wp_register_script('peepso-window', PeepSo::get_asset('js/pswindow.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-postbox', PeepSo::get_asset('js/postbox.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-share', PeepSo::get_asset('js/share.min.js'), array('jquery', 'peepso-window'), PeepSo::PLUGIN_VERSION, TRUE);
//		wp_register_script('peepso-login', PeepSo::get_asset('js/login.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-iframetransport', PeepSo::get_asset('js/jquery.iframe-transport.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-fileupload', PeepSo::get_asset('js/jquery.fileupload.min.js'), array('jquery-ui-widget', 'peepso-iframetransport'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-underscore', PeepSo::get_asset('js/underscore.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-posttabs', PeepSo::get_asset('js/posttabs.min.js'), array('peepso', 'peepso-observer', 'peepso-underscore'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-datepicker', PeepSo::get_asset('js/bootstrap-datepicker.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-members', PeepSo::get_asset('js/member-search.min.js'), array('peepso-notification'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('image-scale', PeepSo::get_asset('js/image-scale.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-lightbox', PeepSo::get_asset('js/lightbox.min.js'), array('jquery', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-modal-comments', PeepSo::get_asset('js/modal-comments.min.js'), array('peepso-observer', 'peepso-activitystream-js', 'image-scale', 'peepso-lightbox', 'peepso-underscore', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-chosen', plugin_dir_url(__FILE__) . 'assets/js/chosen.jquery.min.js', array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_register_script('peepso-load-image', plugin_dir_url(__FILE__) . 'assets/js/load-image.all.min.js', array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

        wp_register_style('peepso-chosen', plugin_dir_url(__FILE__) . 'assets/css/chosen.min.css', array('peepso'), PeepSo::PLUGIN_VERSION);
        // Enqueue peepso-window, a lot of functionality uses the popup dialogs
        wp_enqueue_script('peepso-notification', PeepSo::get_asset('js/notifications.min.js'), array('peepso-observer', 'peepso-underscore', 'jquery-ui-position'), PeepSo::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepso-window');
        wp_enqueue_script('peepso-members');
        wp_enqueue_script('peepso-modal-comments');
        // Always enqueue this, it's needed for modal logins
//		wp_enqueue_script('peepso-login');

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

        wp_localize_script('peepso-time', 'peepsotimedata', array(
            'ts'     => current_time('U'),
            'now'    => __('just now', 'peepso'),
            'min'    => sprintf( __('%s ago', 'peepso'), _n('%s min', '%s mins', 1) ),
            'mins'   => sprintf( __('%s ago', 'peepso'), _n('%s min', '%s mins', 2) ),
            'hour'   => sprintf( __('%s ago', 'peepso'), _n('%s hour', '%s hours', 1) ),
            'hours'  => sprintf( __('%s ago', 'peepso'), _n('%s hour', '%s hours', 2) ),
            'day'    => sprintf( __('%s ago', 'peepso'), _n('%s day', '%s days', 1) ),
            'days'   => sprintf( __('%s ago', 'peepso'), _n('%s day', '%s days', 2) ),
            'week'   => sprintf( __('%s ago', 'peepso'), _n('%s week', '%s weeks', 1) ),
            'weeks'  => sprintf( __('%s ago', 'peepso'), _n('%s week', '%s weeks', 2) ),
            'month'  => sprintf( __('%s ago', 'peepso'), _n('%s month', '%s months', 1) ),
            'months' => sprintf( __('%s ago', 'peepso'), _n('%s month', '%s months', 2) ),
            'year'   => sprintf( __('%s ago', 'peepso'), _n('%s year', '%s years', 1) ),
            'years'  => sprintf( __('%s ago', 'peepso'), _n('%s year', '%s years', 2) )
        ));

    }


    /*
     * registers shortcode
     */
    private function register_shortcodes()
    {
        foreach ($this->shortcodes as $shortcode => $callback) {
            if(is_callable($callback)) {
                add_shortcode($shortcode, $callback);
            }
        }
    }

    /**
     * Sets the current shortcode identifier, only the first call to this method is ran
     * @param string $shortcode A string that may be used to identify which shortcode ran first
     */
    public static function set_current_shortcode($shortcode)
    {
        if (NULL === self::$_current_shortcode)
            self::$_current_shortcode = $shortcode;
    }

    /**
     * Returns the identifier for the first PeepSo shortcode that was called
     * @return string
     */
    public static function get_current_shortcode()
    {
        return (self::$_current_shortcode);
    }

    /*
     * callback function for the 'peepso_profile' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function profile_shortcode($atts, $content = '')
    {
        $sc = new PeepSoProfileShortcode($atts, $content);
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_register' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function register_shortcode($atts, $content = '')
    {
        $sc = new PeepSoRegisterShortcode();
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_recover' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function recover_shortcode($atts, $content = '')
    {
        $sc = new PeepSoRecoverPasswordShortcode();
        return ($sc->do_shortcode($atts, $content));
    }

    /*
     * callback function for the 'peepso_members' shortcode
     * @param array $atts shortcode attributes
     * @param string $content contents of shortcode
     */
    public static function search_shortcode($atts, $content = '')
    {
        $sc = new PeepSoMembersShortcode();
        return ($sc->shortcode_search($atts, $content));
    }

    /*
     * return PeepSo option values
     * @param string $name name of the option value being requested
     * @param string $default default value to return if nothing found
     * @return multi the stored option value
     */
    public static function get_option($name, $default = NULL)
    {
        if (NULL === self::$_config)
            self::$_config = PeepSoConfigSettings::get_instance();
        return (self::$_config->get_option($name, $default));
    }


    /*
     * Return a named page as a fully qualified URL
     * @param string $name Name of page
     * @return string URL to the fully qualified page name
     */
    public static function get_page($name)
    {
        switch ($name)
        {
            case 'logout':
                $ret = self::get_page('activity') . '?logout';
                break;

            case 'notifications':
                $ret = self::get_page('profile') . '?notifications';
                break;

            case 'redirectlogin':
                $ret = self::get_page(PeepSo::get_option('site_frontpage_redirectlogin'));
                break;

            default:
                $ret = get_bloginfo('wpurl') . '/' . self::get_option('page_' . $name) . '/';
                break;
        }
        return ($ret);
    }


    /*
     * builds a link to a user's profile page
     * @param int $user_id
     * @return string URL to user's profile
     */
    public static function get_user_link($user_id)
    {
        $ret = get_home_url();

        $user = get_user_by('id', $user_id);
        if (FALSE !== $user) {
            $ret .= '/' . PeepSo::get_option('page_profile') . '/';
            $ret .= $user->user_login . '/';
        }

        return (apply_filters('peepso_username_link', $ret, $user_id));
    }

    /*
     * returns URL to user's avatar image or a default image based on user's gender
     * @return string URL to avatar image
     */
    public static function get_avatar($user_id)
    {
        $user = new PeepSoUser($user_id);
        $avatar = $user->get_avatar();
        return ($avatar);
    }


    /*
     * Filter function for 'get_avatar'. Substitutes the PeepSo avatar for the WP one
     * @param string $avater The HTML for the <img> reference to the avatar
     * @param mixed $id_or_email The user id for the avatar (if value is numeric)
     * @param int $size Size in pixels of desired avatar
     * @param string $default The src= attribute value for the <img>
     * @param string $alt The alt= attribute for the <img>
     * @return string The HTML for the full <img> element
     */
    public function filter_avatar($avatar, $id_or_email, $size, $default, $alt)
    {
        // if id_or email is an object, it's a Wordpress default, try getting an email address from it
        if (is_object($id_or_email) && property_exists($id_or_email, 'comment_author_email')) {

            // if the email exists
            if (strlen($id_or_email->comment_author_email)){
                $id_or_email = $id_or_email->comment_author_email;
            }
        }

        // numeric id
        if (is_numeric($id_or_email)) {
            $user_id = intval($id_or_email);
        } else if (is_object($id_or_email)) {
            // if it's an object then it's a wp_comments avatar; just return what's already there
            return ($avatar);
        } else {
            $user = get_user_by('email', $id_or_email);
            $user_id = $user->ID;
            if (FALSE === $user_id)						// if we can't lookup by email
                return ($avatar);						// just return what's already found
        }
        $user = new PeepSoUser($user_id);
        $img = $user->get_avatar();
        $avatar = '<img alt="' . esc_attr($user->get_fullname()) . ' avatar" src="' . $img . '" class="avatar avatar-' . $size . " photo\" width=\"{$size}\" height=\"{$size}\" />";
        return ($avatar);
    }

// Users, roles, permissions

    // the following are used to check permissions
    // @todo clean up the const
    const PERM_POST = 'post';
    const PERM_POST_VIEW = 'post_view';
    const PERM_POST_EDIT = 'post_edit';
    const PERM_POST_DELETE = 'post_delete';
    const PERM_COMMENT = 'comment';
    const PERM_COMMENT_DELETE = 'delete_comment';
    const PERM_POST_LIKE = 'like_post';
    const PERM_COMMENT_LIKE = 'like_comment';
    const PERM_PROFILE_LIKE = 'like_profile';
    const PERM_PROFILE_VIEW = 'view_profile';
    const PERM_PROFILE_EDIT = 'edit_profile';
    const PERM_REPORT = 'report';

    /**
     * Returns the PeepSo specific role assigned to the current user
     * @return string One of the role names, 'user','member','moderator','admin','ban','register','verified' or FALSE if the user is not logged in
     */
    private static function _get_role()
    {
        static $role = NULL;
        if (NULL !== $role)
            return ($role);

        if (!is_user_logged_in())
            return ($role = FALSE);

        $user = new PeepSoUser(PeepSo::get_user_id());
        return ($role = $user->get_user_role());
    }

    /*
     * Checks if current user has admin priviledges
     * @return boolean TRUE if user has admin priviledges, otherwise FALSE
     */
    public static function is_admin()
    {
        static $is_admin = NULL;
        if (NULL !== $is_admin)
            return ($is_admin);

        // WP administrators is set to PeepSo admins automatically
        if (current_user_can('manage_options'))
            return ($is_admin = TRUE);

        // if user not logged in, always return FALSE
        if (!is_user_logged_in())
            return ($is_admin = FALSE);

        // check the PeepSo user role
        $role = self::_get_role();
        if ('admin' === $role)
            return ($is_admin = TRUE);

        // TODO: use current_user_can() when/if we create capabilities
//		if (current_user_can('peepso_admin'))
//			return ($is_admin = TRUE);

        return ($is_admin = FALSE);
    }

    /**
     * Checks if current user is a member, i.e. has access to viewing the site.
     * @return boolean TRUE if user is allowed to view the site; otherwise FALSE.
     */
    public static function is_member()
    {
        static $is_member = NULL;
        if (NULL !== $is_member)
            return ($is_member);

        $role = self::_get_role();
        // banned, and registered/verified but not approved users are not full members
        if ('ban' === $role || 'register' === $role || 'verified' === $role)
            return ($is_member = FALSE);

        // TODO: use current_user_can() when/if we create capabilities
//		if (current_user_can('peepso_member'))
//			return ($is_member = FALSE);

        return ($is_member = TRUE);
    }

    /**
     * Checks if current user is a moderator.
     * @return boolean TRUE if user is a moderator; otherwise FALSE.
     */
    public static function is_moderator()
    {
        static $is_moderator = NULL;
        if (NULL !== $is_moderator)
            return ($is_moderator);

        $role = self::_get_role();
        if ('moderator' === $role)
            return ($is_moderator = TRUE);

        // TODO: use current_user_can() when/if we create capabilities
//		if (current_user_can('peepso_moderator'))
//			return ($is_moderator = TRUE);

        return ($is_moderator = FALSE);
    }

    /*
     * Check if author has permission to perform action on an owner's Activity Stream
     * @param int $owner The user id of the owner of the Activity Stream
     * @param string $action The action that the author would like to perform
     * @param int $author The author requesting permission to perform the action
     * @param boolean $allow_logged_out Whether or not to allow guest permissions
     * @return Boolean TRUE if author can take the requested action; otherwise FALSE
     */
    public static function check_permissions($owner, $action, $author, $allow_logged_out = FALSE)
    {
        static $check_debug = FALSE;
        if ($check_debug) PeepSo::log(__METHOD__."()  owner={$owner} author={$author} action='{$action}'");

        // verify user and author ids
        if (0 === $owner || (0 === $author && FALSE === $allow_logged_out)) {
            if ($check_debug) PeepSo::log(__METHOD__.'() user id or author id invalid');
            return (self::error(__('User id or author id is invalid', 'peepso')));
        }

        // owner always has permissions to do something to themself
        if ($owner === $author) {
            if ($check_debug) PeepSo::log(__METHOD__.'() owner matches author');
            return (TRUE);
        }

        // admin always has permissions to do something
        if (self::is_admin()) {
            if ($check_debug) PeepSo::log(__METHOD__.'() user is admin');
            return (TRUE);
        }

        // check if author_id is the current user
        if ($author != self::get_user_id()) {
            if ($check_debug) PeepSo::log(__METHOD__.'() author not current user');
            return (self::error(__('Invalid authorship', 'peepso')));
        }

        // check if on the user's block list
        $blk = new PeepSoBlockUsers();
        if ($blk->is_user_blocking($owner, $author, TRUE)) {
            if ($check_debug) PeepSo::log(__METHOD__.'() author is in owner block list');
            // author is on the owner's block list - exit
            return (self::error(sprintf(__('User %1$d tried to write on %2$d\'s wall and was blocked', 'peepso'), $author, $owner)));
        }

        // check author access depending on the action being performed
        switch ($action)
        {
            case self::PERM_POST_VIEW:
                if ($check_debug) PeepSo::log(__METHOD__.'() checking view_post permission');
                global $post;
                if (isset($post->act_access)) {
                    $access = intval($post->act_access);
                    $post_owner = intval($post->act_owner_id);
                } else {
                    // in case someone calls this from outside PeepSoActivityShortcode
                    global $wpdb;
                    $sql = 'SELECT `act_access`, `act_owner_id` ' .
                        " FROM `{$wpdb->posts}` " .
                        " LEFT JOIN `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` `act` ON `act`.`act_external_id`=`{$wpdb->posts}`.`ID` " .
                        ' WHERE `ID`=%d AND `act`.`act_module_id`=%d
					  LIMIT 1 ';

                    $module_id = (isset($post->act_module_id) ? $post->act_module_id : PeepSoActivity::MODULE_ID);
                    $ret = $wpdb->get_row($wpdb->prepare($sql, $post->ID, $module_id));
                    if ($check_debug) PeepSo::log(__METHOD__.'() got post: ' . var_export($ret, TRUE));
                    if ($ret) {
                        $access = intval($ret->act_access);
                        $post_owner = intval($ret->act_owner_id);
                    } else {
                        $access = 10;
                        $post_owner = NULL;
                    }
                }
                switch ($access)
                {
                    case self::ACCESS_PUBLIC:
                        return (TRUE);
                        break;
                    case self::ACCESS_MEMBERS:
                        if (is_user_logged_in())
                            return (TRUE);
                        break;
                    case self::ACCESS_PRIVATE:
                        if (PeepSo::get_user_id() === $owner)
                            return (TRUE);
                        break;
                }
                if ($check_debug) PeepSo::log(__METHOD__.'() still undetermined; allowing others to check');
                $can_access = apply_filters('peepso_check_permissions-' . $action, -1, $owner, $author, $allow_logged_out);

                if (-1 !== $can_access)
                    return ($can_access);
                return (FALSE);
                break;

            case self::PERM_POST:
            case self::PERM_COMMENT:
                break;

            case self::PERM_POST_EDIT:
                if ($owner !== self::get_user_id())
                    return (FALSE);
                break;

            case self::PERM_POST_DELETE:
            case self::PERM_COMMENT_DELETE:
                return (($owner === $author) || ($owner === self::get_user_id()));
                break;

            case self::PERM_POST_LIKE:			 // intentionally fall through
            case self::PERM_COMMENT_LIKE:
            case self::PERM_PROFILE_VIEW:
                $user = new PeepSoUser($owner);
                return ($user->is_accessible('profile'));
                break;

            case self::PERM_PROFILE_LIKE:
                if (! PeepSo::get_option('site_likes_profile', TRUE))
                    return (FALSE);

                $user = new PeepSoUser($owner);
                return ($user->is_profile_likable());
                break;

            case self::PERM_REPORT:
                if (1 === PeepSo::get_option('site_reporting_enable'))
                    return (TRUE);				// if someone can see the content, they can report it
                // TODO: possibly allow reporting only by logged in users
                return (FALSE);
                break;

            default:
                $can_access = apply_filters('peepso_check_permissions-' . $action, -1, $owner, $author, $allow_logged_out);

                if (-1 !== $can_access)
                    return ($can_access);
            // Fall through if a filter for the action doesn't exist.
        }
        if ($check_debug) PeepSo::log(__METHOD__.'() got past switch()');

        // anything that falls through -- check owner's access settings

        $ret = FALSE;

        $own = new PeepSoUser($owner);
        if ($own) {
            $ret = $own->check_access($action, $author);
            if ($check_debug) PeepSo::log(__METHOD__.'() check_access() result=' . var_export($ret, TRUE));
        }
        if ($check_debug) PeepSo::log(__METHOD__.'()  returning: ' . var_export($ret, TRUE));

        return ($ret);
    }


    /* Determine if a given user id is the owner of an item
     * @param int $post_id The id of the post item to check
     * @param int $owner_id The user id of the post item to check
     * @return Boolean TRUE if it's the owner, otherwise FALSE
     */
    public static function is_owner($post_id, $owner_id)
    {
        // TODO: expand capabilities to do checks on other types of data/tables

        global $wpdb;
        // TODO: use class constant for table name
        $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}peepso_activities` " .
            " WHERE `act_id`=%d AND `act_owner_id`=%d ";
        $ret = $wpdb->get_var($wpdb->prepare($sql, $post_id, $owner_id));
        PeepSo::log(__METHOD__.'() SQL: ' . $wpdb->last_query);

        return (intval($ret) > 0 ? TRUE : FALSE);
    }


    /*
     * returns the access level for a user
     */
    public static function get_user_access($user_id)
    {
        $user = new PeepSoUser($user_id);

        return $user->get_accessibility('profile');
    }


    /*
     * return current user's id
     */
    public static function get_user_id()
    {
        $user = wp_get_current_user();
        return (isset($user->ID) ? intval($user->ID) : 0);
    }
    public function set_user()
    {
        if (!current_user_can('edit_posts'))
            show_admin_bar(false);
    }

    /*
     * Returns the current user's role
     * @return string The name of the current user's PeepSo role (one of 'ban', 'register', 'verified', 'user', 'member', 'moderator', 'admin') or NULL if the user is not logged in
     */
    public static function get_user_role()
    {
        // http://wordpress.org/support/topic/how-to-get-the-current-logged-in-users-role
        $role = NULL;
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $role = self::_get_role();
//			global $current_user;
//
//			$aRoles = array_values($current_user->roles);
//			if (count($aRoles) > 0)
//				$sRet = $aRoles[0];
        }
        return ($role);
    }

// Notifications
    /*
     * Return user id of administrator that should receive notifications
     * @return boolean|int Admin user id if email exists, FALSE if otherwise
     */
    public static function get_notification_user()
    {
        $email = self::get_notification_emails();
        $wpuser = get_user_by('email', $email);

        return (FALSE !== $wpuser) ? $wpuser->ID : FALSE;
    }

    public static function get_notification_emails()
    {
        $email = PeepSo::get_option('site_emails_admin_email');
        return ($email);
    }


// URLs and paths

    /*
     * return user's IP address
     * @return string The IP address of the current user
     */
    public static function get_ip_address()
    {
        // ci/system/libraries/Email.php
        static $ip = NULL;

        if (NULL !== $ip)
            return ($ip);

        $ret = '';

        if (empty($ret) && isset($_SERVER['REMOTE_ADDR']))
            $ret = $_SERVER['REMOTE_ADDR'];

        if (empty($ret) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can return a comma-separated list of IP addresses
            $aParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'], 1);
            $ret = $aParts[0];
        }

        if (empty($ret) && isset($_SERVER['HTTP_X_REAL_IP']))
            $ret = $_SERVER['HTTP_X_REAL_IP'];

        if (empty($ret) && isset($_SERVER['HTTP_CLIENT_IP']))
            $ret = $_SERVER['HTTP_CLIENT_IP'];

        if (empty($ret))						// use localhost as a last resort
            $ret = '127.0.0.1';

        return ($ip = $ret);
    }

    /*
     * Returns the current page URL with any directory prefixes (when WP is installed in a child directory) removed
     * @return string The URL of the current page, with directory prefixes removed
     */
    public static function get_page_url()
    {
        $url = $_SERVER['REQUEST_URI'];

        $page = get_site_url('/');
        $page = str_replace('http://', '', $page);
        $page = str_replace('https://', '', $page);

        // remove host name at beginning of URL
        if (isset($_SERVER['HTTP_HOST']) && substr($page, 0, strlen($_SERVER['HTTP_HOST'])) === $_SERVER['HTTP_HOST'])
            $page = substr($page, strlen($_SERVER['HTTP_HOST']));

        // remove directory prefix from REQUEST_URI
        if (substr($url, 0, strlen($page)) === $page)
            $url = substr($url, strlen($page));

        // remove any surrounding / characters
        $url = trim($url, '/');

        return ($url);
    }

    /*
     * Get the directory that PeepSo is installed in
     * @return string The PeepSo plugin directory, including a trailing slash
     */
    public static function get_plugin_dir()
    {
        return (plugin_dir_path(__FILE__));
    }

    /*
     * return reference to asset, relative to the base plugin's /assets/ directory
     * @param string $ref asset name to reference
     * @return string href to fully qualified location of referenced asset
     */
    public static function get_asset($ref)
    {
        $ret = plugin_dir_url(__FILE__) . 'assets/' . $ref;
        return ($ret);
    }

    /*
     * return the URL to an asset within the template directories
     * @param string $section application section to load the template asset from
     * @param string $ref the reference to the asset
     * @return string the fully qualified URL to the requested asset
     */
    public static function get_template_asset($section, $ref)
    {
        $dir = plugin_dir_url(__FILE__) . 'templates/';
        if (NULL !== $section)
            $dir .= $section . '/';
        $dir = apply_filters('peepso_template_asset', $dir, $section);
        $ret = $dir . $ref;
        return ($ret);
    }

    /*
     * Return the PeepSo working directory, adjusted for MultiSite installs
     * @return string PeepSo working directory
     */
    public static function get_peepso_dir()
    {
        static $peepso_dir;

        if (!isset($peepso_dir)) {
            // wp-content/peepso/users/{user_id}/
            //$peepso_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso';
            $peepso_dir = self::get_option('site_peepso_dir', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso');
            if (is_multisite())
                $peepso_dir .= '-' . get_current_blog_id();
            $peepso_dir .= DIRECTORY_SEPARATOR;
        }
        $peepso_dir = apply_filters('peepso_working_directory', $peepso_dir);
        return ($peepso_dir);
    }

    /*
     * Return the PeepSo working directory as a URL
     * @return string PeepSo working directory URL
     */
    public static function get_peepso_uri()
    {
        static $peepso_uri;

        if (!isset($peepso_uri)) {
            $peepso_uri = content_url() . '/peepso';
            if (is_multisite())
                $peepso_uri .= '-' . get_current_blog_id ();
            $peepso_uri .= '/';
        }
        $peepso_uri = apply_filters('peepso_working_url', $peepso_uri);
        return ($peepso_uri);
    }

    /*
     * return the fully qualified directory for a specific user
     * @param int user id
     * @return string directory name
     */
    public static function get_userdir($user)
    {
        $ret = self::get_peepso_dir() . $user . '/';
        return ($ret);
    }

    public static function get_useruri($user)
    {
        $ret = self::get_peepso_uri() . $user . '/';
        return ($ret);
    }

// Auth

    /**
     * Perform our own authentication on login.
     * @param  mixed $user      null indicates no process has authenticated the user yet. A WP_Error object indicates another process has failed the authentication. A WP_User object indicates another process has authenticated the user.
     * @param  string $username The user's username.
     * @param  string $password The user's password (encrypted).
     * @return mixed            Either a WP_User object if authenticating the user or, if generating an error, a WP_Error object.
     */
    public function auth_signon($user, $username, $password)
    {
        if (!is_wp_error($user) && NULL !== $user) {
            $ban = $for_approval = FALSE;
            $psuser = new PeepSoUser($user->ID);
            $role = $psuser->get_user_role(); // PeepSo::get_user_role();
            $ban = ('ban' === $role);
            $for_approval = ('verified' === $role || 'register' === $role);

            if ($ban) {
                return (new WP_Error('account_suspended', __('Your account has been suspended.', 'peepso')));
            }

            if ($for_approval && self::get_option('site_registration_enableverification', '0')) {
                return (new WP_Error('pending_approval', __('Your account is awaiting admin approval.', 'peepso')));
            }

            if ('register' === $role) {
                return (new WP_Error('pending_approval', __('Please verify the email address you have provided using the link in the email that was sent to you.', 'peepso')));
            }
        }

        /*
        @todo commented out due to #304 -  "PeepSo login hook breaks WP mobile app login"

        // check referer to ensure login came from installed domain
        if (!isset($_SERVER['HTTP_REFERER']))
            return (new WP_Error('nonwebsite_login', __('Must login from web site', 'peepso')));

        $ref_domain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $our_domain = parse_url(get_bloginfo('wpurl'), PHP_URL_HOST);
        if ($ref_domain !== $our_domain)
            return (new WP_Error('nonwebsite_login', __('Must login from web site', 'peepso')));
        */
        return ($user);
    }

    /**
     * Checks peepso roles whether to allow a password to be reset.
     * @param bool $allow Whether to allow the password to be reset. Default true.
     * @param int  $user_id The ID of the user attempting to reset a password.
     * @return mixed TRUE if password reset is allowed, WP_Error if not
     */
    public function allow_password_reset($allow, $user_id)
    {
        $role = self::_get_role();

        $ban = $for_approval = FALSE;

        $ban = ('ban' === $role);
        $for_approval = in_array($role, array('register', 'verified'));

        // end process and display success message
        if ($ban || ($for_approval && PeepSo::get_option('site_registration_enableverification', '0')))
            $allow = new WP_Error('user_login_blocked', __('This user may not login at the moment.', 'peepso'));

        return ($allow);
    }

// HTML, widget, linking utils

    public function body_class_filter($classes)
    {
        $classes[]='plg-peepso';
        return $classes;
    }

    /*
    * Clean up default HTML output for integrated widgets
    */
    public function peepso_widget_args_internal( $args )
    {
        $args['before_widget']  = str_replace('widget ','', $args['before_widget']);
        $args['after_widget']   = '</div>';
        $args['before_title']   = str_replace('widgettitle','', $args['before_title']);
        $args['after_title']    = '</h2>';

        return $args;
    }

    /*
    * Adjust widget instance
    */
    public function peepso_widget_instance( $instance )
    {
        if (isset($instance['is_profile_widget'])) {
            $instance['class_suffix'] ='';
        } else {
            $instance['class_suffix'] ='-external';
        }

        return $instance;
    }

    /*
     * Hide "load more" link for guests
     */
    public function peepso_activity_more_posts_link( $link )
    {
        if (!PeepSo::get_user_id()) {
            $link = '';
        }

        return $link;
    }

    /*
     * Add links to the profile widget
     */
    public function peepso_widget_me_links($links)
    {
        $links[0][] = array(
            'href' => PeepSo::get_page('activity'),
            'title' => __('Activity', 'peepso'),
            'icon' => 'ps-icon-home',
        );

        $links[99][] = array(
            'href' => PeepSo::get_page('logout'),
            'title' => __('Log Out', 'peepso'),
            'icon' => 'ps-icon-exit',
        );

        ksort($links);
        return $links;
    }

    /*
     * Add links to the profile segment submenu
     */
    public function peepso_profile_segment_menu_links($links)
    {
        $links[0][]=
            array(
                'href' => '',
                'title' => __('Profile', 'peepso'),
                'id' => 'profile',
            );
        ksort($links);
        return $links;
    }

// Versioning

    public static function check_version_compat($version, $release = NULL)
    {
        // initial success array
        $response = array(
            'ver_core' => self::PLUGIN_VERSION,
			'rel_core' => self::PLUGIN_RELEASE,
			'ver_self' => $version,
			'rel_self' => $release,
            'compat'    =>  1, // 1 - OK, 0 - ERROR, -1 - WARNING
            'part'          => '',
        );

        // if the strings are the same
        if ( $version == self::PLUGIN_VERSION ) {

            if (NULL !== $release) {
                if ( $release != self::PLUGIN_RELEASE ) {
					$response['compat'] = -1;
				}
			}

            return $response;
        }

        // explode the parts
        $v_peepso = self::get_version_parts(self::PLUGIN_VERSION);
        $v_plugin = self::get_version_parts($version);


        // if the parts failed to build, return error
        if ( FALSE == $v_peepso || FALSE == $v_plugin ) {
            $response['compat'] = 0;
            return $response;
        }

        // loop through parts and compare one by one
        foreach ($v_peepso as $part => $value) {

            //if parts are different, add failure code
            if ( $value != $v_plugin[$part] ) {
                $response['compat'] = 0;
                $response['part'] = $part;

                // if the failed part is bugfix, we will only issue a warning
                if ( 'bugfix' == $part ) {
                    $response['compat'] = -1;
                }

                return $response;
            }
        }

        return $response;
    }

    public static function get_version_parts($version)
    {
        $version = explode('.', $version);

        if (is_array($version) && 3 == count($version)) {
            foreach($version as $sub) {
                if (!is_numeric($sub)) {
                    return false;
                }
            }

            return array(
                'major' => $version[0],
                'minor' => $version[1],
                'bugfix' => $version[2],
            );
        }

        return false;
    }

// Admin notices & alerts

    // @todo HTML rendering methods should probably be refactored

    /**
     * Show message if peepsofriends can not be installed or run
     */
    public static function license_notice($plugin_name, $plugin_slug, $forced=FALSE)
    {
        $style="";
        if (isset($_GET['page']) && 'peepso_config' == $_GET['page'] && !isset($_GET['tab'])) {

            if (!$forced) {
                return;
            }

            $style="display:none";
        }

        $license_data = PeepSoLicense::get_license($plugin_slug);
        echo "<!--";print_r($license_data);echo "-->";
        switch ($license_data['response']) {
            case 'site_inactive':
                $message = __('This domain is not registered, you can still use PeepSo with PLUGIN_NAME, but you will need to register your domain to get technical support. You can do it <a target="_blank" href="PEEPSOCOM_LICENSES">here</a>.', 'peepso');
                break;
            case 'expired':
                $message = __('License for PLUGIN_NAME has expired. Please renew your license on peepso.com and enter a valid license. You can do it <a target="_blank" href="PEEPSOCOM_LICENSES">here</a>.', 'peepso');
                break;
            case 'invalid':
            case 'inactive':
            case 'item_name_mismatch':
            default:
                $message = __('License for PLUGIN_NAME is missing or invalid. Please <a href="ENTER_LICENSE">enter a valid license</a> to activate it. You can get your license key <a target="_blank" href="PEEPSOCOM_LICENSES">here</a>.', 'peepso');
                break;
        }

        #var_dump($license_data);
        $from = array(
            'PLUGIN_NAME',
            'ENTER_LICENSE',
            'PEEPSOCOM_LICENSES',
        );

        $to = array(
            $plugin_name,
            'admin.php?page=peepso_config#licensing',
            self::PEEPSOCOM_LICENSES,
        );

        $message = str_ireplace( $from, $to, $message );
        #var_dump($message);

        echo '<div class="error peepso" id="error_'.$plugin_slug.'" style="'.$style.'">';
        echo '<strong>', $message , '</strong>';
        echo '</div>';
    }

    public static function version_notice($plugin_name, $plugin_slug, $version_check)
    {
        if (strlen($version_check['rel_core'])) {
            $version_check['ver_core'] .= "-" . $version_check['rel_core'];
        }

        if ( strlen( $version_check['ver_self'])  && strlen( $version_check['rel_self'] ) ) {
            $version_check['ver_self'] .= "-" . $version_check['rel_self'];
        }

        $message = "$plugin_name <i>{$version_check['ver_self']}</i>";


        if ( -1 == $version_check['compat'] ) {
            /*
				PLUGIN_NAME X.X.X might not fully compatible with PeepSo X.X.Y.
				Please upgrade PLUGIN_NAME  and PeepSo core to avoid conflicts and issues. [Upgrade Now
			*/

            $message .= __(' might not be fully compatible with PeepSo ', 'peepspo');
            $message .= " <i>{$version_check['ver_core']}</i>. ";
        } else {
            $message .= __(' is not compatible with PeepSo ', 'peepspo');
            $message .= " <i>{$version_check['ver_core']}</i> and has been disabled. ";
        }

        $message .= __('Please upgrade', 'peepso');
        $message .= " $plugin_name ";
        $message .= __('and PeepSo core to avoid conflicts and issues.', 'peepso');
        // @todo 245 add URL

        $message .= ' <a href="'.self::PEEPSOCOM_LICENSES.'" target="_blank">';
        $message .= __('Upgrade now!', 'peepso');
        $message .= '</a>';

        echo '<div class="error peepso"><strong>'.$message.'</strong></div>';
    }

    public static function welcome_screen()
    {
        return false;
        ob_start();
        ?>

        PeepSo has created <a href="edit.php?s=peepso&post_status=all&post_type=page&action=-1&m=0&paged=1&action2=-1">a few pages</a> containing shortcodes.

        You can read more about it <a href="http://tiny.cc/peepso-pages" target="_blank">here</a>.

        <hr>

        Would you like us to send you an email with important information about PeepSo?

        It's a one time email sent from your WordPress site to <b><?php echo get_bloginfo('admin_email');?></b>.

        <hr>

        [No (@todo ajax call)] [Send the email (@todo ajax call)]

        <?php
        PeepSoAdmin::get_instance()->add_notice(ob_get_contents(),'info');
        ob_end_clean();
        return true;
    }

    /* @todo #269 - welcome email must be opt-in, re-implement along with the first activation welcome screen
    public static function send_welcome_email() {

        $settings = PeepSoConfigSettings::get_instance();
        $settings->set_option('peepso_welcome_screen_done', self::PLUGIN_VERSION.'-'.self::PLUGIN_RELEASE);

        // get HTML
        $url_html = PeepSo::PEEPSO_INTEGRATION_JSON_URL . '/peepso_welcome_email.html';

        $resp_html  = wp_remote_get(add_query_arg(array(), $url_html), array('timeout' => 15, 'sslverify' => FALSE));

        if (!is_wp_error($resp_html)) {
            $replace_from = array(
                'site_url',
                'site_name',
            );

            $replace_to = array(
                get_bloginfo('url'),
                get_bloginfo('name')
            );

            $message = str_ireplace($replace_from, $replace_to, wp_remote_retrieve_body($resp_html));
        }

    if (!strlen($message)){
            return false;
        }

        // get JSON settings
        $url = PeepSo::PEEPSO_INTEGRATION_JSON_URL . '/peepso_welcome_email.json';

        $resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 15, 'sslverify' => FALSE));

        if (!is_wp_error($resp)) {
            $response = wp_remote_retrieve_body($resp);
        }

    if (!strlen($response) || !($email_config = json_decode($response, true))) {
            return false;
        }

        $email_config = array_pop($email_config);

        $to = get_bloginfo('admin_email');

        $subject = $email_config['subject'];

        $headers = "From: {$email_config['header_from']} <{$email_config['header_from_email']}>\r\n";
        $headers.= "Reply-To: {$email_config['header_reply_to']} <{$email_config['header_reply_to_email']}>\r\n";
        $headers.= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers.= "MIME-Version: 1.0\r\n";
        $headers.= "Content-type: text/html; charset=utf-8\r\n";


    if (isset($_GET['nocache_welcome_email'])) {

            echo "\n\n<!--\n\n$subject\n\nHTML: ".strlen($message)." chars\n\n";

            print_r($headers);

            echo "\n\n";

            print_r($to);

            echo "\n\n-->";
        }


        if (function_exists('wp_mail')){
            wp_mail($to, $subject, $message, $headers);
        }
    }
    */

// Debug & utils

    /*
     * Issue #241
     * Adjust WP_Query flags to disable comments rendering under pages
     * Attempt re-init() of WP_Query where %postname% permalink structure might interfere with our routing
     *
     * @todo might yield UNFORESEEN CONSEQUENCES
     * 2-4-1 = -3
     * Half Life 3 confirmed
     */
    public static function reset_query()
    {
        wp_reset_query();

        // disable WP comments from displaying on page
        global $wp_query;

        $permalink = get_option('permalink_structure');

        if (stristr($permalink, '%postname%')) {
            $wp_query->init();
        }

        $wp_query->is_single = FALSE;
        $wp_query->is_page = FALSE;
    }

    /*
     * Adds needed intervals
     * @param array $schedules
     * @return array $schedules
    */
    public static function filter_cron_schedules($schedules)
    {
        // adds an interval called 'five_minutes' to cron schedules
        $schedules['five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every Five Minutes', 'peepso')
        );

        // Adds once weekly to the existing schedules.
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __('Once Weekly', 'peepso')
        );

        return ($schedules);
    }

    /*
		* Logs errors for later review
	*/
    public static function error($msg)
    {
        if (PeepSo::get_option('system_enable_logging'))
            new PeepSoError($msg);

        return (FALSE);
    }

    /**
     * Add access types hook required for PeepSoPMPro plugin
     * @param array $types existing access types
     * @return array $types new access types
     */
    public function filter_access_types($types)
    {
        $types['peepso_activity'] = array(
            'name' => __('Activity Stream', 'peepso'),
            'module' => PeepSoActivity::MODULE_ID,
        );

        $types['peepso_members'] = array(
            'name' => __('Search', 'peepso'),
            'module' => self::MODULE_ID,
        );

        $types['peepso_profile'] = array(
            'name' => __('Profile Pages', 'peepso'),
            'module' => self::MODULE_ID,
        );

        return ($types);
    }

    public static function redirect($url)
    {
        if (is_user_logged_in()) {

            if(!headers_sent()) {
                wp_redirect($url);
                die();
            }

            echo '<script>window.location.replace("'.$url.'");</script>';
            die();
        }
    }

    /*
     * log information for debugging
     * @param string $msg the message to be logged
     */
    public static function log($msg = NULL, $backtrace = FALSE)
    {
        if (!self::DEBUG)
            return;

        $file = dirname(__FILE__) . '/peepsolog.txt';
        $fh = @fopen($file, 'a+');

        if (FALSE !== $fh) {
            if (NULL === $msg)
                fwrite($fh, date("\r\nY-m-d H:i:s:\r\n"));
            else
                fwrite($fh, date('Y-m-d H:i:s - ') . $msg . PHP_EOL);

            if ($backtrace) {
                $callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                array_shift($callers);
                $path = dirname(dirname(dirname(plugin_dir_path(__FILE__)))) . DIRECTORY_SEPARATOR;

                $n = 1;
                foreach ($callers as $caller) {
                    $func = $caller['function'] . '()';
                    if (isset($caller['class']) && !empty($caller['class'])) {
                        $type = '->';
                        if (isset($caller['type']) && !empty($caller['type']))
                            $type = $caller['type'];
                        $func = $caller['class'] . $type . $func;
                    }
                    $file = isset($caller['file']) ? $caller['file'] : '';
                    $file = str_replace($path, '', $file);
                    if (isset($caller['line']) && !empty($caller['line']))
                        $file .= ':' . $caller['line'];
                    $frame = $func . ' - ' . $file;
                    fwrite($fh, '    #' . ($n++) . ': ' . $frame . PHP_EOL);
                }
            }

            fclose($fh);
        }
        if (self::$log_to_console)
            echo $msg, PHP_EOL;
    }
    private static $log_to_console = FALSE;
    public static function log_to_console()
    {
        self::$log_to_console = TRUE;
    }
}

defined('WPINC') || die;
PeepSo::get_instance();


/*
 * global access to PeepSo application
 * @param string $op Operation to perform or name of object to operate on
 * @param string $p Parameter to operate with
 * @param multi $data optional data value
 * @return multi Depends on the operation and parameter
 *
 * Forms:
 * peepso('object name', 'method name')		- calls the method of the object
 * peepso('object name', 'get.property')	- returns the named data property from the object
 * peepso('object name', 'show.property')	- outputs the named data property from the object
 *
 * Example:
 * peepso('activity', 'show-post')			- calls the PeepSoActivity::show_post() method
 * peepso('activity', 'get.post_author')	- returns the post_author property from the current ActivityStream object
 * peepso('avatar', $user_id)				- outputs an href value for an avatar image for the requested user
 * peepso('user-link', $user_id)			- outputs an href value for a profile page for the requested user
 * peepso('display-name', $user_id)
 */
function peepso($op = NULL, $p = NULL, $data = NULL)
{
    if (NULL === $op)
        return (PeepSo::getInstance());

    if (is_string($op)) {
        // first look for non class operations
        $fFound = TRUE;
        $oper = strtolower($op);
        switch ($oper)
        {
            case 'user-link':
            case 'get-user-link':
                $data = PeepSo::get_user_link($p);
                break;

            case 'page-link':
            case 'get-page-link':
                $data = PeepSo::get_page($p);
                break;

            case 'redirect-login':
                $data = PeepSo::get_page('redirectlogin');
                if ($data == '' && isset($_COOKIE['peepso_last_visited_page'])) {
                    // Check if domain is the same to prevent spoofing
                    $domain = parse_url($_COOKIE['peepso_last_visited_page'], PHP_URL_HOST);

                    if ($domain === $_SERVER['SERVER_NAME'])
                        $data = $_COOKIE['peepso_last_visited_page'];
                }
                break;

            case 'user-id':
            case 'get-user-id':
                $data = PeepSo::get_user_id();
                break;

            case 'display-name':
                $u = new PeepSoUser($p);
                $data = $u->get_display_name();
                break;
            case 'load-template':
                $sPath = PeepSoTemplate::locate_template($p);
                list($sSect, $sTmpl) = explode('/', $p, 2);
                $data = PeepSoTemplate::exec_template($sSect, $sTmpl, $data, TRUE);
                break;

            case 'avatar':
                $data = PeepSo::get_avatar($p);
                break;

            default:
                $fFound = FALSE;
                break;
        }
        if ($fFound) {
            if (substr($oper, 0, 4) !== 'get-')
                echo $data;
            return ($data);
        }

        // look for class based operations
        $class = 'PeepSo' . ucwords($op);
        $method = strtr($p, '-', '_');

        $obj = call_user_func($class . '::get_instance');
        if ('get.' === substr($method, 0, 4)) {
            // return the named property of the object
            $prop = substr($method, 4);
            $ret = $obj->get_prop($prop);
            return ($ret);
        } else if ('show.' === substr($method, 0, 5)) {
            // output the named property of the object
            $prop = substr($method, 4);
            $ret = $obj->get_prop($prop);
            echo $ret;
            return;
        } else {
            // call the named method from the object
            if (!in_array($method, $obj->template_tags)) {
                PeepSo::log("***peepso('{$op}', '{$p}') cannot find method requested");
                $ex = new Exception();
                PeepSo::log('trace: ' . $ex->getTraceAsString());
                echo '<div class="peepso-error">' .
                    sprintf(__('Error: template tag %1$s of section %2$s is not recognized', 'peepso'),
                        $method, $class) . '</div>';
            } else {
                if (is_null($data))
                    $ret = call_user_func(array($obj, $method));
                else
                    $ret = call_user_func(array($obj, $method), $data);
                return ($ret);
            }
        }
    }
}


// load the ActivityStream plugin
require_once(dirname(__FILE__) . '/activity/activitystream.php');
// load helpers
require_once(dirname(__FILE__) . '/lib/helpers.php');
require_once(dirname(__FILE__) . '/lib/pluggable.php');
// EOF

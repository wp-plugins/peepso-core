<?php

/*
 * Performs deactivation process
 * @package PeepSo
 * @author PeepSo
 */
class PeepSoUninstall
{
	const DELETE_ALL_DATA_SETTINGS = 'delete_on_deactivate';
	const DELETE_POST_COMMENT_DATA = 'delete_post_data';

	/*
	 * called on plugin deactivation; performs all uninstallation tasks
	 */
	public static function plugin_deactivation()
	{
//		* - This method should be static
//		* - Check if the $_REQUEST content actually is the plugin name
//		* - Run an admin referrer check to make sure it goes through authentication
//		* - Verify the output of $_GET makes sense
//		* - Repeat with other user roles. Best directly by using the links/query string parameters.
//		* - Repeat things for multisite. Once for a single site in the network, once sitewide.

		self::clear_scheduled_events();
		self::remove_roles();
		self::plugin_uninstall();
	}


	/**
	 * Called when plugin is uninstalled and deletes all data and settings or only post and comment data as the case may be.
	 */
	public static function plugin_uninstall()
	{
		if (!defined('WP_UNINSTALL_PLUGIN')) //if uninstall not called from WordPress exit
			return;

		if (1 === intval(PeepSo::get_option(self::DELETE_POST_COMMENT_DATA, 0)))
			self::remove_custom_post_data();

		if (1 === intval(PeepSo::get_option(self::DELETE_ALL_DATA_SETTINGS, 0))) {
			self::remove_custom_post_data();
			self::remove_tables();

			$peepso_dir = PeepSo::get_option('site_peepso_dir', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso');
			if (is_dir($peepso_dir))
				self::recursive_rmdir($peepso_dir);

			// this should be done last, in case above processes require any settings
			self::remove_config_settings();
		}
	}


	/*
	 * Remove scheduled tasks
	 */
	private static function clear_scheduled_events()
	{
		$events = array(PeepSo::CRON_DAILY_EVENT, PeepSo::CRON_MAILQUEUE);
		$events = apply_filters('peepso_uninstall_scheduled_events', $events);

		foreach ($events as $event)
			wp_clear_scheduled_hook($event);
	}


	/*
	 * Remove custom post type data from wp_posts and wp_postmeta
	 */
	private static function remove_custom_post_data()
	{
		$posts = array(PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT);
		$posts = apply_filters('peepso_uninstall_cpt_types', $posts);

		$in_list = implode(', ', array_pad(array(), count($posts), '%s'));

		global $wpdb;
		$sql = "DELETE `{$wpdb->postmeta}` "
			 . "FROM `{$wpdb->postmeta}` "
			 . "LEFT JOIN `{$wpdb->posts}` ON `{$wpdb->posts}`.`ID` = `{$wpdb->postmeta}`.`post_id` "
			 . "WHERE `{$wpdb->posts}`.`post_type` IN ({$in_list}) ";
		$wpdb->query($wpdb->prepare($sql, $posts));

		$sql = "DELETE FROM `{$wpdb->posts}` "
			 . "WHERE `post_type` IN ({$in_list}) ";
		$wpdb->query($wpdb->prepare($sql, $posts));
	}

	/*
	 * DROP all the PeepSo tables from the database
	 */
	private static function remove_tables()
	{
		global $wpdb;
		$tables = array_keys(PeepSoActivate::get_table_data());
		$tables = apply_filters('peepso_uninstall_tables', $tables);

		foreach ($tables as &$table) {
			$table = $wpdb->prefix . 'peepso_' . $table;
		}

		$tables = implode('`,`', $tables);

		$sql = "DROP TABLE IF EXISTS `{$tables}` ";

		$wpdb->query($sql);
	}


	/*
	 * Remove PeepSo custom roles and set all subscribers with 'peepso_*' role to 'subscriber' role
	 */
	private static function remove_roles()
	{
//		$roles = array('peepso_user', 'peepso_member', 'peepso_moderator', 'peepso_admin', 'peepso_ban', 'peepso_verified', 'peepso_register');
//		$roles = apply_filters('peepso_uninstall_roles', $roles);
//
//		foreach ($roles as $role) {
//			$args = array(
//				'role' => $role,
//			);
//			$user_query = new WP_User_Query($args);
//			if (!empty( $user_query->results)) {
//				foreach ($user_query->results as $user) {
//					// Remove peepso_ role
//					$user->remove_role($role);
//					// Add default role
//					$user->add_role('subscriber');
//				}
//			}
//			remove_role($role);
//		}
	}


	/*
	 * Removes all configuration settings for PeepSo
	 */
	private static function remove_config_settings()
	{
		$settings = array(
			// TODO: verify all of these config settings
			// TODO: check all add-ons and verify that they remove their settings if the add-on is deactivated but PeepSo core is not.
			'config', 'config_notice',
			'config_site_registration_confirm', 'config_site_registration_terms', 'config_site_registration_welcome',
			'email_activity_notice', 'email_like_post', 'email_new_user', 'email_password_recover',
				'email_register', 'email_user_comment', 'email_wall_post', 'email_welcome',
				'email_password_changed', 'email_user_approved', 'email_like_profile',
				'email_new_user_registration',
			'mailqueue_history', 'install_date'
		);
		$settings = apply_filters('peepso_uninstall_config_settings', $settings);

		foreach ($settings as $setting)
			delete_option('peepso_' . $setting);
	}

	/**
	 * Recursively remove all files within the speified directory
	 *
	 * @param string $dir The directory path
	 * @return void
	 */
	private static function recursive_rmdir($dir)
	{
		if (!is_dir($dir))
			return;
		$files = scandir($dir);
		foreach ($files as $file) {
			if ('.' === $file || '..' === $file)
				continue;
			$filename = $dir . DIRECTORY_SEPARATOR . $file;
			if ('dir' === filetype($filename))
				self::recursive_rmdir($filename);
			else
				unlink($filename);
		}
		reset($files);
		rmdir($dir);
	}

}

// EOF

<?php

/*
 * Performs installation process
 * @package PeepSo
 * @author PeepSo
 */
class PeepSoInstall
{
	// these items are stored individually
	protected $extended_config = array();
	protected $default_config = array();
	/*
	 * called on plugin activation; performs all installation tasks
	 */
	public function plugin_activation()
	{
		$this->create_database_tables();

		// not all child classes are going to have a migration step
		if (method_exists($this, 'migrate_database_tables'))
			$this->migrate_database_tables();

		$this->create_pages();
		$this->create_options();
		$this->create_roles();
		$this->create_scheduled_events();

		return (TRUE);
	}

	protected function get_email_contents()
	{	
		return array();
	}

	/*
	 * return default page names information
	 */
	protected function get_page_data()
	{
		return array();
	}

	/*
	 * return array of default data used in PeepSo page creation
	 */
	protected function get_post_data()
	{
		$aRet = array(
			'post_content'   => '',
			'post_name'      => '',
			'post_title'     => '',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => PeepSo::get_user_id(),
			'ping_status'    => 'closed',
			'post_parent'    => 0,
			'menu_order'     => 0,
			'to_ping'        => '',
			'pinged'         => '',
			'post_password'  => '',
			'post_excerpt'   => '',
			'post_date'      => current_time('mysql'),
			'post_date_gmt'  => current_time('mysql'),
			'comment_status' => 'closed',
			'post_category'  => '',
			'tags_input'     => '',
			'tax_input'      => '',
			'page_template'  => '',
		);
		return ($aRet);
	}

	/**
	 * Returns definitions for plugin tables.
	 * Sample: 
	 * 	'photos' => "
	 *			CREATE TABLE `photos` (
	 *				`pho_id`				BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	 *				`pho_album_id`			BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	 *				`pho_post_id`			BIGINT(20) UNSIGNED NOT NULL,
	 *				`pho_acc`				TINYINT(1) UNSIGNED DEFAULT 0,
	 *				`pho_stored`			TINYINT(1) UNSIGNED DEFAULT 0,
	 *				`pho_file_name`			VARCHAR(100),
	 *				`pho_orig_name`			VARCHAR(100),
	 *				`pho_filesystem_name`	VARCHAR(100),
	 *				`pho_size`				INT(11) UNSIGNED,
	 *				`pho_created`			TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	 *				`pho_token`				VARCHAR(200) NULL,
 	 *
	 *				PRIMARY KEY (`pho_id`),
	 *				INDEX `post` (`pho_post_id`)
	 *			) ENGINE=InnoDB",
	 * @return array
	 */
	public static function get_table_data()
	{
		return array();
	}

	/**
	 * Runs dbDelta based on the table data from get_table_data() to create the database tables.
	 */
	protected function create_database_tables()
	{
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = '';
		if ( !empty( $wpdb->charset ) )
			$charset_collate = " DEFAULT CHARACTER SET $wpdb->charset";
		if ( !empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";

		ob_start();
		$aTables = $this->get_table_data();
		foreach ($aTables as $sTable => $sql) {
			$sql = str_replace('CREATE TABLE `', 'CREATE TABLE `' . $wpdb->prefix . 'peepso_', $sql);
			$sql .= $charset_collate;

//PeepSo::log('SQL: ' . $sql);
			// TODO: we shouldn't be doing ALTERs as part of these scripts. Only for modifying any tables that are not fixed up by a dbDelta() call
			$sql = str_replace('ALTER TABLE `', 'ALTER TABLE `' . $wpdb->prefix . 'peepso_', $sql);
			$ret = dbDelta($sql);
//PeepSo::log('dbDelta() returned: ' . var_export($ret, TRUE));
		}
		$res = ob_get_clean();
//PeepSo::log('dbDelta: ' . $res);
	}

	/**
	 * Creates plugin specific pages, as defined in get_page_data().
	 */
	protected function create_pages()
	{
		$this->_create_pages($this->get_page_data());
	}

	/**
	 * Creates plugin specific pages, acts as recursive function to create child pages.
	 * @param  array  $aPages Array of page definitions
	 * @param  integer $parent The parent page ID
	 */
	private function _create_pages($aPages, $parent = 0)
	{
		foreach ($aPages as $sPage => $data) {
PeepSo::log("creating page '{$sPage}' with: " . var_export($data, TRUE));
			$args = array(
				'post_type' => 'page',
				'name' => $data['slug'],
			);
			if (NULL !== $data['content']) {
				$res = get_posts($args);
PeepSo::log('  get_posts(): ' . var_export($res, TRUE));
				if (count($res) == 0) {
					$aPostInfo = $this->get_post_data();
					$aPostInfo['post_title'] = $data['title'];
					$aPostInfo['post_name'] = $data['slug'];
					$aPostInfo['post_content'] = $data['content'];
					$aPostInfo['post_parent'] = $parent;

PeepSo::log('creating post: ' . var_export($aPostInfo, TRUE));
					$wp_err = FALSE;
					$id = wp_insert_post($aPostInfo, $wp_err);

					if ($id && isset($data['children']) && is_array($data['children'])) {
						$this->_create_pages($data['children'], $id);
					}
				}
			}

			// add to the config settings being written out later
			$this->default_config['page_' . $sPage] = $data['slug'];
            PeepSo::log("adding page '{$sPage}' to config. this->config: " . var_export($this->default_config, TRUE));
		}
	}

	/**
	 * Loops through $this->extended_config to run update_option(), adds the peepso_config_ prefix.
	 */
	protected function create_options()
	{
PeepSo::log('PeepSoInstall::create_options() wrote ' . var_export($this->default_config, TRUE));

		// NOTE: must be called after create_pages(), which creates the 'page_...' settings
		$opts = get_option('peepso_config');
		if (FALSE !== $opts) {
			$this->default_config = array_merge($this->default_config, $opts);
			update_option('peepso_config', $this->default_config, FALSE, TRUE);
		} else {
			add_option('peepso_config', $this->default_config, FALSE, TRUE);
		}

PeepSo::log('PeepSoInstall::create_options() merged new config with existing: ' . var_export($this->default_config, TRUE));

		foreach ($this->extended_config as $setting => $value) {
			delete_option('peepso_config_' . $setting);
			add_option('peepso_config_' . $setting, $value, FALSE, FALSE);
		}
		
		// write the email content settings
		$emails = $this->get_email_contents();
		
		foreach ($emails as $name => $content) {
			$option = 'peepso_' . $name;
			delete_option($option);
			update_option($option, $content);
		}

        $opts = get_option('peepso_config');
PeepSo::log('PeepSoInstall::create_options() options after write ' . var_export($opts, TRUE));
	}

	/**
	 * Place creation of user roles here.
	 */
	protected function create_roles()
	{
		// implement in child class
	}

	/*
	 * Create all of the scheduled events
	 */
	protected function create_scheduled_events()
	{
		// implement in child class
	}
}

// EOF

<?php

class PeepSoUserSearch
{
	public $results;
	public $query;

	private $_iterator = NULL,
			$_array_object = NULL;

	/**
	 * Search for users
	 * @param  string $search  The search string
	 * @param  int $user_id The user doing the search, if set to NULL defaults to the current user
	 * @return WP_User_Query
	 */
	public function __construct($args = array(), $user_id = NULL, $search = '')
	{
		global $wpdb;

		if (is_null($user_id))
			$user_id = PeepSo::get_user_id();

		$args = apply_filters('peepso_user_search_args',
			array_merge(
				$args,
				array(
					'fields' => 'ID',
					'_peepso_user_id' => intval($user_id),
					'_peepso_search' => $search
				)
			)
		);

		add_action('pre_user_query', array(&$this, 'pre_user_query'));
		$this->query = new WP_User_Query($args);
		$this->results = $this->query->results;
		$this->_array_object = new ArrayObject($this->results);
		$this->_iterator = $this->_array_object->getIterator();
		remove_action('pre_user_query', array(&$this, 'pre_user_query'));
	}

	/**
	 * Alter the WP_User_Query object to account for privacy settings
	 * @param  WP_User_Query $wp_user_query
	 */
	public function pre_user_query(WP_User_Query $wp_user_query)
	{
		global $wpdb;

		$user_id = $wp_user_query->query_vars['_peepso_user_id'];
		$search = $wp_user_query->query_vars['_peepso_search'];

		// check to see if the "Allow User to Override Name Setting" option is enabled.
		if (!empty($user_id) && 1 === intval(PeepSo::get_option('system_override_name', 0))) {
			// read the user's setting for display options
			$current_user = new PeepSoUser($user_id);
			$display_name_as = $current_user->get_display_name_as();
		} else // get the site config setting for the display name style.
			$display_name_as = PeepSo::get_option('system_display_name_style', 'username');

		switch ($display_name_as)
		{
		case 'real_name':
			$wp_user_query->query_orderby = ' ORDER BY `display_name` ';
			break;
		default:
			$wp_user_query->query_orderby = ' ORDER BY `user_login` ';
		}

		if (!empty($search)) {
			$wp_user_query->query_from .= ' INNER JOIN `' . $wpdb->usermeta . '` `psmeta`
				ON `' . $wpdb->users . '`.`ID` = `psmeta`.`user_id` ';

			global $wp_version;
			if (version_compare($wp_version, '4.0', 'lt'))
				$search = like_escape($search);
			else
				$search = $wpdb->esc_like($search);

			// < IF1 >
			$wp_user_query->query_where .= ' AND ( IF(`acc`.`usr_first_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . ' AND `acc`.`usr_last_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . ', ';
			// First and last name is accessible, let's use display name.

			$wp_user_query->query_where .= $wpdb->prepare(' ' . $wpdb->users . '.`display_name` LIKE %s ', '%' . $search . '%');
			// Check first name access only

			// < IF 2 >
			$wp_user_query->query_where .= ', IF(`acc`.`usr_first_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . ', ';
			$wp_user_query->query_where .= $wpdb->prepare(' `psmeta`.`meta_key` = "first_name" AND CAST(`psmeta`.`meta_value` AS CHAR) LIKE %s ', '%' . $search . '%');
			// Check last name access only

			// < IF 3 >
			$wp_user_query->query_where .= ', IF(`acc`.`usr_last_name_acc` <> ' . PeepSo::ACCESS_PRIVATE . ', ';
			$wp_user_query->query_where .= $wpdb->prepare(' `psmeta`.`meta_key` = "last_name" AND CAST(`psmeta`.`meta_value` AS CHAR) LIKE %s ', '%' . $search . '%');

			// </ IF3 > 
			$wp_user_query->query_where .= ',FALSE)';
			// </ IF2 > 
			$wp_user_query->query_where .= ')';	
			// </ IF1 > 
			$wp_user_query->query_where .= ')';

			$wp_user_query->query_where .= ' OR `' . $wpdb->users . '`.`user_login` LIKE "%' . $search . '%") ';

			$wp_user_query->query_orderby = ' GROUP BY `' . $wpdb->users . '`.`ID` ' . $wp_user_query->query_orderby;
		}

		$wp_user_query->query_from .= '
			LEFT JOIN `' . $wpdb->prefix . PeepSoUser::TABLE . '` `acc`
				ON `acc`.`usr_id` = `' . $wpdb->users . '`.`ID`
			LEFT JOIN `' . $wpdb->prefix . PeepSoActivity::BLOCK_TABLE_NAME  . '` `blk`
				ON `blk_user_id` = `' . $wpdb->users . '`.`ID` AND `blk_blocked_id`= ' . $user_id . '
					OR `blk_user_id` = ' . $user_id . ' AND `blk_blocked_id` = `' . $wpdb->users . '`.`ID`
		';

		// exclude self
		$wp_user_query->query_where .= ' AND `ID` <> ' . $user_id . ' ';
		// exclude banned users and unvalidated users
		$wp_user_query->query_where .= ' AND `acc`.`usr_role` NOT IN ("register", "verified", "ban") ';

		// Private
		$wp_user_query->query_where .= '
			AND `acc`.`usr_profile_acc` <> ' . PeepSo::ACCESS_PRIVATE . '
		';
		// Members only
		$wp_user_query->query_where .= '
			AND IF (`acc`.`usr_profile_acc` = ' . PeepSo::ACCESS_MEMBERS . ', ' . $user_id . ' > 0, TRUE)
		';

		// blocked
		$wp_user_query->query_where .= ' AND `blk_blocked_id` IS NULL ';

		/**
		 * Fires after the WP_User_Query has been parsed, and before
		 * the query is executed.
		 *
		 * The passed WP_User_Query object contains SQL parts formed
		 * from parsing the given query.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_User_Query $this The current WP_User_Query instance,
		 *                            passed by reference.
		 */
		do_action_ref_array('peepso_pre_user_query', array(&$wp_user_query, $user_id));
	}

	/**
	 * Iterates through the ArrayObject and returns the current user in the loop as an
	 * instance of PeepSoUser.	 
	 * @return PeepSoUser A PeepSoUser instance of the current friend in the loop.
	 */
	public function get_next()
	{
		if (is_null($this->_array_object))
			return (FALSE);

		if ($this->_iterator->valid()) {
			$user = new PeepSoUser($this->_iterator->current());
			$this->_iterator->next();
			return ($user);
		}

		return (FALSE);
	}
}

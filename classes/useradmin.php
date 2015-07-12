<?php

// TODO: move other methods from the PeepSoUser class that are only used in the admin
class PeepSoUserAdmin extends PeepSoUser
{
	/**
	 * Fires after admin has approved the user.
	 * Sends notice that the user may now login.
	 */
	public function approve_user()
	{
		$data = $this->get_template_fields('user');
		$data['useremail'] = $this->get_email();

		PeepSoMailQueue::add_message($this->id, $data, __('Account activated', 'peepso'), 'user_approved', 'user_approved');
	}


	/*
	 * Fetches from the peepso_users table per gender
	 * @param string $gender Gender identifier ('u', 'f', 'm')
	 * @param Boolean $check_acc TRUE to do accessibility checks; otherwise FALSE
	 * @return object $wpdb result
	 */
	public function get_by_gender($gender, $check_acc = TRUE)
	{
		global $wpdb;
		
		$gender = strtolower(substr($gender, 0, 1));
		if ('m' !== $gender && 'f' !== $gender && 'u' !== $gender)
			$gender = 'u';

		$sql = 'SELECT * ' .
				" FROM `{$wpdb->prefix}" . self::TABLE . "` " .
				" LEFT JOIN `{$wpdb->prefix}peepso_blocks` ON `blk_user_id`=`usr_id` OR `blk_blocked_id`=`usr_id` ";

		if ($check_acc) {
			// add exclusion if the Gender is not accessible

			// public
			$where[] = ' (`usr_gender_acc`=' . PeepSo::ACCESS_PUBLIC . " AND `usr_gender`='{$gender}') ";

			// members: logged in search for gender, otherwise check for unknown
			if (is_user_logged_in())
				$where[] = ' (`usr_gender_acc`=' . PeepSo::ACCESS_MEMBERS . " AND `usr_gender`='{$gender}') ";
			else if ('u' === $gender)
				$where[] = ' (`usr_gender_acc`=' . PeepSo::ACCESS_MEMBERS . " AND `usr_gender`='u') ";

			// TODO: handle Friends accessibility

			// private: admin search for gender, otherwise check for unknown
			if (PeepSo::is_admin())
				$where[] = ' (`usr_gender_acc`=' . PeepSo::ACCESS_PRIVATE . " AND `usr_gender`='{$gender}') ";
			else if ('u' === $gender)
				$where[] = ' (`usr_gender_acc`=' . PeepSo::ACCESS_PRIVATE . " AND `usr_gender`='u') ";
		} else {
			$where[] = "`usr_gender`='{$gender}' ";
		}

		// add the WHERE clause to the statement
		$sql .= ' WHERE ' . implode(' OR ', $where);

		return ($wpdb->get_results($wpdb->prepare($sql, $gender), ARRAY_A));
	}

	/*
	 * Fetches the count of members from the peepso_users table by gender
	 * @param string $gender Gender identifier ('u', 'f', 'm')
	 * @return int Number of users found
	 */
	public function get_count_by_gender($gender)
	{
		global $wpdb;
		// TODO: add exclusion if the Gender is not accessible
		$sql = 'SELECT COUNT(*) AS `count` ' .
				" FROM `{$wpdb->prefix}" . self::TABLE . "` " .
				' WHERE `usr_gender`=%s ';
		$ret = intval($wpdb->get_var($wpdb->prepare($sql, $gender)));
		return ($ret);
	}

	/**
	 * Returns counts for the number of users 
	 * @global type $wpdb
	 * @return type
	 */
	public function get_counts_by_role()
	{
		global $wpdb;
		$sql = "SELECT COUNT(*) `count`, `usr_role` AS `role`
				FROM `{$wpdb->prefix}" . self::TABLE . "`
				LEFT JOIN `{$wpdb->users}` ON `ID`=`usr_id`
				WHERE `ID` IS NOT NULL
				GROUP BY `usr_role`
				ORDER BY `usr_role`";

		$res = $wpdb->get_results($sql, ARRAY_A);
		return ($res);
	}
	public function count_for_roles($roles)
	{
		if (0 === count($roles))
			return (0);

		$inlist = array();
		foreach ((array) $roles as $role)
			$inlist[] = '\'' . esc_sql($role) . '\'';
		$inlist = implode(',', $inlist);

		global $wpdb;
		$sql = "SELECT COUNT(*) `count`
				FROM `{$wpdb->prefix}" . self::TABLE . "`
				LEFT JOIN `{$wpdb->users}` ON `ID`=`usr_id`
				WHERE `ID` IS NOT NULL AND `usr_role` IN ({$inlist})";
		return (intval($wpdb->get_var($sql)));
	}
}

// EOF

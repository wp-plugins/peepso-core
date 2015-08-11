<?php

class PeepSoBlockUsers
{
	const TABLE = 'peepso_blocks';

	/*
	 * Adds a user id to the user's list of blocked users
	 * @param int $block_id The user id to be blocked
	 * @param int $user_id The user id that is doing the blocking
	 * @return Boolean TRUE if successfully blocked; otherwise FALSE
	 */
	public function block_user_from_user($block_id, $user_id)
	{
		$data = array(
			'blk_user_id' => $user_id,
			'blk_blocked_id' => $block_id
		);
		global $wpdb;
		$res = $wpdb->insert($wpdb->prefix . self::TABLE, $data);
		return ($res);
	}

	/*
	 * See if a user is blocking another user
	 * @param int $user_id The user id that is is being checked
	 * $param int $block_user_id The user id that is being blocked
	 * @param Boolean $recip TRUE if also checking if $block_user_id is blocking $user_id
	 * @retrun Boolean TRUE if $user_id is blocking $block_user_id
	 */
	public function is_user_blocking($user_id, $block_user_id, $recip = FALSE)
	{
		global $wpdb;
		
		$sql = 'SELECT COUNT(*) AS `count` ' .
				" FROM `{$wpdb->prefix}" . self::TABLE . '` ' .
				' WHERE (`blk_user_id`=%1$d AND `blk_blocked_id`=%2$d) ';
		if ($recip)
			$sql .= ' OR (`blk_user_id`=%2$d AND `blk_blocked_id`=%1$d) ';

		$count = $wpdb->get_var(sprintf($sql, intval($user_id), intval($block_user_id)));
		return ($count > 0);
	}


	/*
	 * Get number of blocked users by user id
	 * @param int $user_id Id of user to count blocked users for
	 * @returns int Number of blocked users for specified user id
	 */
	public function get_count_for_user($user_id)
	{
		global $wpdb;
		
		$sql = "SELECT COUNT(*) AS `count` " .
				" FROM `{$wpdb->prefix}" . self::TABLE . "` " .
				" WHERE `blk_user_id`=%d ";
		$count = $wpdb->get_var($wpdb->prepare($sql, $user_id));
		return intval($count);
	}


	/*
	 * Retrieved blocked user by user id
	 * @param int $user_id The user id to retrieve blocked users for
	 * @return array List of blocked user ids and login names
	 */
	public function get_by_user($user_id)
	{
		global $wpdb;
		
		$sql = "SELECT `blk`.*, `user_login` " .
				" FROM `{$wpdb->prefix}" . self::TABLE . "` `blk` " .
				" LEFT JOIN `{$wpdb->users}` ON `ID`=`blk_user_id` " .
				" LEFT JOIN `{$wpdb->prefix}peepso_users` `ps` ON `ps`.`usr_id`=`blk`.`blk_user_id` " .
				" WHERE `blk`.`blk_user_id`=%d ";
		$res = $wpdb->get_results($wpdb->prepare($sql, $user_id), OBJECT);
		return ($res);
	}


	/*
	 * Removed blocked users for a given user id
	 * @param array $ids A list of blocked user ids to remove
	 * @param int $user_id The user id to delete blocked users
	 */
	public function delete_by_id($ids, $user_id = NULL)
	{
		global $wpdb;

		$ids = implode(',', $ids);
		if (NULL === $user_id)
			$user_id = PeepSo::get_user_id();

		$sql = "DELETE FROM `{$wpdb->prefix}peepso_blocks` " .
			" WHERE `blk_user_id`=%d AND `blk_blocked_id` IN ({$ids}) ";
		$res = $wpdb->query($wpdb->prepare($sql, $user_id));
		return ($res);
	}
}

// EOF
<?php

class PeepSoNotifications
{
	const TABLE = 'peepso_notifications';
	private $table = self::TABLE;

	private $data = NULL;

	public function __construct($id = NULL)
	{
		global $wpdb;

		if (is_integer($id)) {
			$sql = "SELECT * FROM `{$wpdb->prefix}{$this->table}` " .
					" WHERE `not_id`=%d " .
					" LIMIT 1 ";
			$res = $wpdb->get_row($wpdb->prepare($sql, $id), OBJECT);
			if (NULL !== $res)
				$this->data = $res;
		}
	}

	/*
	 * Create a notification record
	 * @param int $from_user The user_id of the one creating the notification (sender)
	 * @param int $to_user The user_id of the on getting the notification (recipient)
	 * @param string $msg The message to be sent
	 * @param string $type The type or category of the message
	 * @param int $module The module id creating the notification
	 * @param int $external The ID of the external reference
	 * @return int The id of the newly created notification or FALSE if no Notification created
	 */
	public function add_notification($from_user, $to_user, $msg, $type, $module_id, $external = 0)
	{
		$notifications = get_user_meta($to_user, 'peepso_notifications');
		// do not send any notification when it's disabled
		if (1 === intval(PeepSo::get_option('site_alerts_' . $type, 0)) &&
			isset($notifications[0]) &&
			in_array($type . '_notification', $notifications[0]))
			return (FALSE);

		$block_users = new PeepSoBlockUsers();
		// do not send any notification when blocking
		if ($block_users->is_user_blocking($from_user, $to_user, TRUE))
			return (FALSE);

		$data = apply_filters('peepso_notifications_data_before_add',
			array(
				'not_user_id' => $to_user,
				'not_from_user_id' => $from_user,
				'not_module_id' => $module_id,
				'not_external_id' => $external,
				'not_type' => substr($type, 0, 20),
				'not_message' => substr($msg, 0, 200),
				'not_timestamp' => current_time('mysql')
			)
		);

		global $wpdb;
		$id = $wpdb->insert($wpdb->prefix . self::TABLE, $data);
		return ($id);
	}


	/*
	 * Return message for this notification, replacing any tokens
	 * @return String The message associated with this notification instance
	 */
	public function get_message()
	{
		$msg = NULL;
		if (NULL !== $this->data) {
			$msg = $this->data->not_message;
			$tokens = $this->get_tokens();

			$mag = str_replace(array_keys($tokens), array_values($tokens), $msg);
		}

		return ($msg);
	}

	/**
	 * Return replacement tokens for the user
	 * @return array The replacement tokens
	 */
	private function get_tokens()
	{
		$from = new PeepSoUser($this->data->not_from_user_id);

		$ret = array(
			'%from_user_name%' => $from->get_fullname(),
			'%from_user_link%' => PeepSo::get_user_link($this->data->not_from_user_id),
			'%item%' => $this->data->not_type,
		);
		return ($ret);
	}


	/*
	 * Get number of notification for the given user
	 * @param int $user_id The user id to count notifications for
	 * @return int Number of notifications for the given user
	 */
	public function get_count_for_user($user_id)
	{
		global $wpdb;

		$sql = "SELECT COUNT(*) AS `count` " .
				" FROM `{$wpdb->prefix}{$this->table}` " .
				" WHERE `not_user_id`=%d ";
		$ret = intval($wpdb->get_var($wpdb->prepare($sql, $user_id)));
		return ($ret);
	}


	/*
	 * Get number of unread notification for the given user
	 * @param int $user_id The user id to count notifications for
	 * @return int Number of notifications for the given user
	 */
	public function get_unread_count_for_user($user_id)
	{
		global $wpdb;

		$access = ' (IF (`act`.`act_id` IS NOT NULL, (`act_access`=' . PeepSo::ACCESS_PRIVATE . ' AND `act_owner_id`=' . PeepSo::get_user_id() . ') OR ' .
				' (`act_access`=' . PeepSo::ACCESS_MEMBERS . ') OR (`act_access`<=' . PeepSo::ACCESS_PUBLIC . ') ';

		// Hooked methods must wrap the string within a paranthesis
		$access = apply_filters('peepso_activity_post_filter_access', $access);
		$access .= ', 1=1))';

		$sql = "SELECT COUNT(*) AS `count` " .
				" FROM `{$wpdb->prefix}{$this->table}` `not`" .
				" LEFT JOIN `{$wpdb->users}` `fu` ON `fu`.`ID` = `not`.`not_from_user_id` " .
				" LEFT JOIN `{$wpdb->posts}` `p` ON `p`.ID = `not`.`not_external_id` " .
				" LEFT JOIN `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` `act` ON `act`.`act_external_id`=`p`.`id` " .				
				" WHERE `not_user_id`=%d AND `not_read`=0 AND " . $access;

		$ret = intval($wpdb->get_var($wpdb->prepare($sql, $user_id)));
		return ($ret);
	}


	/*
	 * Return notification data by user id
	 * @param int $user_id The ID of the user who's notifications are to be retrieved
	 * @return array Notification data by user
	 */
	public function get_by_user($user_id, $limit = 40, $offset = 0)
	{
		global $wpdb;

		// TODO: instead of filtering the results, if the user doesn't have access to the post the notification record should not be created
		$access = ' (IF (`act`.`act_id` IS NOT NULL, (`act_access`=' . PeepSo::ACCESS_PRIVATE . ' AND `act_owner_id`=' . PeepSo::get_user_id() . ') OR ' .
				' (`act_access`=' . PeepSo::ACCESS_MEMBERS . ') OR (`act_access`<=' . PeepSo::ACCESS_PUBLIC . ') ';

		// Hooked methods must wrap the string within a paranthesis
		$access = apply_filters('peepso_activity_post_filter_access', $access);
		$access .= ', 1=1))';

		$sql = "SELECT `not`.*, `fu`.`user_login`, `p`.`post_title`, `p`.`post_content` " .
				" FROM `{$wpdb->prefix}{$this->table}` `not` " .
				" LEFT JOIN `{$wpdb->users}` `fu` ON `fu`.`ID` = `not`.`not_from_user_id` " .
				" LEFT JOIN `{$wpdb->posts}` `p` ON `p`.ID = `not`.`not_external_id` " .
				" LEFT JOIN `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` `act` ON `act`.`act_external_id`=`p`.`id` " .
				" WHERE `not`.`not_user_id`=%d AND " . $access .
				" ORDER BY `not_timestamp` DESC " .
				" LIMIT %d, %d ";

		$res = $wpdb->get_results($wpdb->prepare($sql, $user_id, $offset, $limit), OBJECT); // ARRAY_A);
		return ($res);
	}


	/*
	 * Deletes records from notifications table for current user by id
	 * @param array $ids An array of notification id numbers to delete
	 * @param int $user_id The user id that owns the notification records
	 */
	public function delete_by_id($ids, $user_id = NULL)
	{
		global $wpdb;

		$ids = implode(',', $ids);
		if (NULL === $user_id)
			$user_id = PeepSo::get_user_id();

		$sql = "DELETE FROM `{$wpdb->prefix}" . self::TABLE . "` " .
			" WHERE `not_user_id`=%d AND `not_id` IN ({$ids}) ";

		$res = $wpdb->query($wpdb->prepare($sql, $user_id));
	}


	/*
	 * Mark notification records as having been read
	 * @param int $user_id User id of notification records to update
	 */
	public function mark_as_read($user_id = NULL)
	{
		global $wpdb;

		if (NULL === $user_id)
			$user_id = PeepSo::get_user_id();

		$sql = "UPDATE `{$wpdb->prefix}" . self::TABLE . "` " .
				" SET `not_read`=1 " .
				" WHERE `not_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));
	}

	/**
	 * Get the latest user's notification
	 * @param int $user_id User ID
	 * @return array Numerically indexed array of row objects
	 */
	public function get_latest($user_id = NULL)
	{
		global $wpdb;

		$sql = "SELECT `not`.*, `fu`.`user_login`, `p`.`post_title`, `p`.`post_content` " .
				" FROM `{$wpdb->prefix}{$this->table}` `not` " .
				" LEFT JOIN `{$wpdb->users}` `fu` ON `fu`.`ID` = `not`.`not_from_user_id` " .
				" LEFT JOIN `{$wpdb->posts}` `p` ON `p`.ID = `not`.`not_external_id` " .
				" WHERE `not`.`not_user_id`=%d " .
				" ORDER BY `not_timestamp` DESC " .
				" LIMIT 2 ";

		$res = $wpdb->get_results($wpdb->prepare($sql, $user_id), OBJECT); // ARRAY_A);
		return ($res);
	}
}

// EOF

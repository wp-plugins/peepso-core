<?php

class PeepSoReport
{
	const TABLE = 'peepso_report';

	public function __construct()
	{

	}

	public static function get_table_name()
	{
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}


	/*
	 * Adds item to report table
	 * @param int $post_id The ID of the item being reported
	 * @param int $user_id The User Id of the person reporting the item
	 * @param int $module The Module (Activity, Events, etc.) of the item being reported
	 * @return Boolean TRUE on success; FALSE on failure
	 */
	public function add_report($post_id, $user_id, $module = NULL, $reason = NULL)
	{
		$data = array(
			'rep_user_id' => $user_id,
			'rep_external_id' => $post_id,
		);
		if (NULL !== $module)
			$data['rep_module_id'] = intval($module);
		if (NULL !== $reason)
			$data['rep_reason'] = $reason;

		global $wpdb;
		$wpdb->insert($wpdb->prefix . self::TABLE, $data);
		return (TRUE);
	}
	
	/**
	 * Check whether post is already reported
	 * @param int $post_id The ID of the item being reported
	 * @param int $user_id The User Id of the person reporting the item
	 * @param int $module The Module (Activity, Events, etc.) of the item being reported
	 * @return bool Whether the post is already reported
	 */
	public function is_reported($post_id, $user_id, $module)
	{
		global $wpdb;
		
		$sql = "SELECT COUNT(`rep_id`) AS `count` " .
			" FROM `" . $this->get_table_name() . "` " .
			" WHERE `rep_external_id` = %d AND `rep_user_id` = %d AND `rep_module_id` = %d ";
		
		$total_items = $wpdb->get_var($wpdb->prepare($sql, $post_id, $user_id, $module));
		
		if ($total_items > 0)
			return (TRUE);
		
		return (FALSE);	
	}


	/*
	 * Retrives a list of Reported items
	 * @param string $orderby The data column to perform ordering on
	 * $param string $order The ordering type, 'ASC' or 'DESC'
	 * @param int $offset The offset used in the LIMIT clause
	 * $param int $limit The limit used in the LIMIT clause
	 * @return array The collection of items queried
	 */
	public function get_reports($orderby, $order, $offset, $limit)
	{
		global $wpdb;

		$subselect = " ( SELECT COUNT(DISTINCT(`rep_user_id`)) " .
						" FROM `" . $this->get_table_name() . "` " .
						" WHERE `psrep`.`rep_external_id`=`rep_external_id` AND `psrep`.`rep_module_id`=`rep_module_id` ) ";

		$sql = "SELECT *, {$subselect} AS `rep_user_count`, `{$wpdb->posts}`.`post_title`, `{$wpdb->posts}`.`post_content` " .
				" FROM `" . $this->get_table_name(). "` AS `psrep` " .
				" LEFT JOIN `{$wpdb->posts}` ON `{$wpdb->posts}`.`ID`=`psrep`.`rep_external_id` " .
				" GROUP BY `rep_external_id` " .
				(!empty($orderby) ? " ORDER BY {$orderby} {$order} " : '') .
				" LIMIT {$offset},{$limit} ";
		$aItems = $wpdb->get_results($sql, ARRAY_A);
//PeepSo::log(__METHOD__."() sql [{$sql}]=" . var_export($aItems, TRUE));
		return ($aItems);
	}


	/*
	 * Return the number of reported items in the queueu
	 * @return int Number of items in the queue
	 */
	public function get_num_reported_items()
	{
		global $wpdb;

//		$sql = "SELECT COUNT(*) AS `count` " .
//				" FROM `" . $this->get_table_name() . "` " .
//				" GROUP BY `rep_external_id` ";
		$sql = "SELECT COUNT(DISTINCT `rep_external_id`) " .
				" FROM `" . $this->get_table_name() . "` ";
		$totalItems = $wpdb->get_var($sql);
		return ($totalItems);
	}


	/**
	 * Bans the profile.
	 * @param  int $rep_id The report ID.
	 * @return mixed Returns the number of rows deleted or FALSE on error.
	 */
	public function ban_user($rep_id)
	{
		global $wpdb;

		$query = $wpdb->prepare('SELECT * FROM `' . self::get_table_name() . '` WHERE `rep_id` = %d', $rep_id);
		$report = $wpdb->get_row($query);

		if ($report->rep_module_id == PeepSo::MODULE_ID) {
//			$user = new WP_User($report->rep_external_id);
//			$user->set_role('peepso_ban');
			$user = new PeepSoUser($report->rep_external_id);
			$user->set_user_role('ban');

			return ($this->delete_reports($report));
		}

		return (FALSE); // Report is not of profile type.
	}


	/**
	 * Deletes the report from the 'peepso_report' table.
	 * @param  int $rep_id The report ID.
	 * @return mixed Returns the number of rows deleted or FALSE on error.
	 */
	public function dismiss_report($rep_id)
	{
		global $wpdb;

		$query = $wpdb->prepare('SELECT * FROM `' . self::get_table_name() . '` WHERE rep_id = %d', $rep_id);
		$report = $wpdb->get_row($query);

		return $this->delete_reports($report);
	}

	/**
	 * Sets a posts status to pending and deletes the report.
	 * @param  int $rep_id The report ID.
	 * @return mixed Returns the number of rows deleted or FALSE on error.
	 */
	public function unpublish_report($rep_id)
	{
		global $wpdb;

		$query = $wpdb->prepare('SELECT * FROM `' . self::get_table_name() . '` WHERE rep_id = %d', $rep_id);
		$report = $wpdb->get_row($query);
		
		if (wp_update_post(array('ID' => $report->rep_external_id, 'post_status' => 'pending')))
			return $this->delete_reports($report);

		return FALSE;
	}


	/**
	 * Deletes the report from the 'peepso_report' table.
	 * @param  object $report The report object from the database.
	 * @return mixed Returns the number of rows deleted or FALSE on error.
	 */
	public function delete_reports($report)
	{
		global $wpdb;

		return $wpdb->delete(self::get_table_name(), 
			array
			(
				'rep_external_id' => $report->rep_external_id,
				'rep_module_id' => $report->rep_module_id,
			)
		);
	}
}

// EOF

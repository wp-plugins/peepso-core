<?php

class PeepSoLike
{
	const TABLE = 'peepso_likes';

	public function __construct()
	{

	}


	/*
	 * Adds a like record to the database
	 * @param int $item_id The item being like, such as a post_id
	 * @param int $module_id The module that is adding the like, such as Activity Stream (MODULE_ID = 1)
	 * @param int $user_id The user id of the person adding the like
	 * $param int $type The type of the like; can be different for each module. Example: Activity Stream
	 *				can use 1 for 'likes' and 2 for 'unlikes'
	 * @return Boolean TRUE for successfully adding the like
	 */
	public function add_like($item_id, $module_id, $user_id, $type = 1)
	{
		$data = array(
			'like_user_id' => intval($user_id),			// user_id adding the like
			'like_external_id' => intval($item_id),		// id of item being liked; i.e. post_id
			'like_module_id' => intval($module_id),
			'like_type' => intval($type),
		);

		global $wpdb;
		$wpdb->hide_errors();							// turn off errors, in case someone likes something twice
		$res = $wpdb->insert($wpdb->prefix . self::TABLE, $data);
		$wpdb->show_errors();
		return (TRUE);
	}


	/*
	 * Removes a like record from the database
	 * @param int $item_id The item that was liked, such as a post_id
	 * @param int $module_id The module that is removing the like, such as Activity Stream (MODULE_ID = 1)
	 * @param int $user_id The user id of the person removing the like
	 * $param int $type The type of the like; can be different for each module. Example: Activity Stream
	 *				can use 1 for 'likes' and 2 for 'unlikes'
	 * @return Boolean TRUE for successfully adding the like
	 */
	public function remove_like($item_id, $module_id, $user_id, $type = 1)
	{
		$data = array(
			'like_user_id' => intval($user_id),
			'like_external_id' => intval($item_id),
			'like_module_id' => intval($module_id),
			'like_type' => intval($type),
		);

		global $wpdb;
		$wpdb->hide_errors();							// turn off errors, in case of removing something not there
		$res = $wpdb->delete($wpdb->prefix . self::TABLE, $data);
		$wpdb->show_errors();
		return (TRUE);
	}


	/*
	 * Return number of "likes" found for a given item
	 * @param int $item_id the `like_external_id` column value
	 * @param int $module_id the module that the item id comes from
	 * @return int The number of records found
	 */
	public function get_like_count($item_id, $module_id = NULL, $type = NULL)
	{
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}" . self::TABLE . "` " .
				" WHERE `like_external_id`=%d ";
		if (NULL !== $module_id)
			$sql .= " AND `like_module_id`=%d ";
		else {
			// if module is NULL add a where condition and set module to a number
			// this allows us to keep the same parameter order in prepare()
			$module_id = 0;
			$sql .= " AND 0=%d ";
		}
		if (NULL !== $type)
			$sql .= " AND `like_type`=%d ";
		$res = $wpdb->get_var($wpdb->prepare($sql, $item_id, $module_id, $type));
//PeepSo::log(__METHOD__.'() item=' . $item_id . ' count=' . $res);
		return (intval($res));
	}


	/*
	 * Return user_id and user_login values for all users that liked a specific item
	 * @param int $item_id the `like_external_id` column value
	 * @param int $module_id the module that the item id comes from
	 * @param int $type An optional type value for the item
	 * @return array A list of user ids and login names that liked the specified item
	 */
	public function get_like_names($item_id, $module_id, $type = NULL)
	{
		global $wpdb;
		$sql = "SELECT `users`.`ID`, `users`.`user_login` " .
				" FROM `{$wpdb->prefix}" . self::TABLE . "` `like` " .
				" LEFT JOIN `{$wpdb->users}` `users` ON `users`.`ID`=`like`.`like_user_id` " .
				" WHERE `like_external_id`=%d AND `like_module_id`=%d ";
		if (NULL !== $type)
			$sql .= " AND `like_type`=%d ";
		$res = $wpdb->get_results($wpdb->prepare($sql, $item_id, $module_id, $type));
		return ($res);
	}


	/*
	 * Return user_id and user_login values for all users that liked a specific item
	 * @param int $item_id the `like_external_id` column value
	 * @param int $module_id the module that the item id comes from	
	 * @param int $user_id The User ID of the person liking
	 * @return boolean TRUE or FALSE whether $user_id has liked $item_id
	 */
	public function user_liked($item_id, $module_id, $user_id)
	{
		global $wpdb;
		$sql = "SELECT COUNT(*) AS `count` " .
				" FROM `{$wpdb->prefix}" . self::TABLE . "` `like` " .
				" WHERE `like_external_id`=%d AND `like_module_id`=%d AND `like_user_id`=%d";

		$count = $wpdb->get_var($wpdb->prepare($sql, $item_id, $module_id, $user_id));

		return ($count > 0);
	}


	/*
	 * Return likes by module
	 * @param int $module_id The module id from which the like came from
	 * @param array $date_range (optional) Where clause to be added
	 * @return array Count-date pair of number of likes per day
	 */
	public function get_likes_graph_data_by_module($module_id, $date_range = NULL)
	{
		global $wpdb;

		$base_query = '
			SELECT 
				COUNT(ID) AS `post_count`, DATE_FORMAT(`like_timestamp`, "%%Y-%%m-%%d") AS `_post_date` 
				FROM `'. $wpdb->posts . '`
				LEFT JOIN `'. $wpdb->prefix . self::TABLE . '` pa 
					ON `pa`.`like_external_id` = `'. $wpdb->posts . '`.`ID`
				WHERE `pa`.`like_module_id` = %d ';

		if (is_array($date_range)) {
			$base_query .= ' AND (DATE_FORMAT(`like_timestamp`, "%%Y-%%m-%%d") >= "' . $date_range['from'] . '" AND
				DATE_FORMAT(`like_timestamp`, "%%Y-%%m-%%d") < "' . $date_range['to'] . '") ';
		}

		$base_query .= ' GROUP BY `_post_date`';

		return $wpdb->get_results(
			$wpdb->prepare($base_query, $module_id)
		);
	}
}

// EOF
<?php

class PeepSoActivityHide
{
	const TABLE = 'peepso_activity_hide';

	/*
	 * Adds a user id to the list of users that have hidden a given post
	 * @param int $act_id The post id that the user is hiding
	 * @param int $user_id The user id that is hiding the post
	 * @return Boolean TRUE if successfully hidden; otherwise FALSE
	 */
	public function hide_post_from_user($act_id, $user_id)
	{
PeepSo::log(__METHOD__."() post={$act_id}  user={$user_id}");
		$aData = array(
			'hide_activity_id' => $act_id,
			'hide_user_id' => $user_id
		);

		global $wpdb;
		$res = $wpdb->insert($wpdb->prefix . self::TABLE, $aData);

		return ($res);
	}
}

// EOF
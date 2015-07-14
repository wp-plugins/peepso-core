<?php

class PeepSoActivityPurge
{
	const TABLE = 'peepso_activities';
	
	public static function purge_activity_items()
	{
PeepSo::log(__METHOD__.'() has been called');
		$sStartTime = microtime(true);

		$act = PeepSoActivity::get_instance();

		global $wpdb;

		$args = array(
			'fields' => 'ids',
			'post_type' => PeepSoActivityStream::CPT_POST,
			'orderby' => 'post_date_gmt',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'offset' => 0,
		);
		
		add_filter('posts_where', array(__CLASS__, 'filter_where'));
		$actQuery = new WP_Query($args);
		remove_filter('posts_where', array(__CLASS__, 'filter_where'));
		
echo " query: {$actQuery->request}\r\n";
echo " found " . $actQuery->found_posts . " items\r\n";
//var_export($actQuery);

		$posts = &$actQuery->posts;
		foreach ($posts as $idx => $post_id) {
echo "  working on post id #{$post_id}\r\n";
//			$act->delete_post($post_id);
		}
	}


	/*
	 * Filters the WHERE clause on custom query to find posts older than XX days
	 * @param string $where The WHERE clause to filter
	 * @return string The modified WHERE clause
	 */
	public static function filter_where($where)
	{
echo "filter_where() '{$where}'\r\n";
		$days = intval(PeepSo::get_option('site_contentpurge_purge_after_days'));
		if ($days < 1)
			$days = 5;

		$purge_timestamp = strtotime('today -' . ($days) . ' days');
		$purge_date = date('Y-m-d H:i:s', $purge_timestamp);

		$where .= " AND `post_date_gmt` < '{$purge_date}' ";

		return ($where);
	}
}

// EOF

<?php

class PeepSoAdminEngagementDashboard implements PeepSoAjaxCallback
{
	private static $_instance = NULL;

	private function __construct()
	{
	}

	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/*
	 * Builds the required flot data set based on the request
	 * @param PeepSoAjaxResponse $resp The response object
	 */
	public function get_graph_data(PeepSoAjaxResponse $resp)
	{
PeepSo::log(__METHOD__.'() starting');
// http://ps.davidjesch.com/peepsoajax/adminEngagementDashboard.get_graph_data?
//	date_range=this_month&
//	module_id=1&
//	stats[]=peepso-comment&
//	stats[]=peepso-post&
//	stats[]=likes
		if (!PeepSo::is_admin()) {
			$resp->success(FALSE);
			$resp->error(__('Insufficient permissions.', 'peepso'));
			return;
		}

		$input = new PeepSoInput();
		$stats = $input->get('stats', 'peepso-comment,peepso-post,likes');
		$stats = explode(',', $stats);

		$series = array();
		foreach ($stats as $stat) {
			$data = $this->get_stats($stat, $resp);
			$total = 0;
			foreach ($data as $point)
				$total += $point[1];

			$series[] = array(
				'type' => $stat,
				'data' => $data,
				'total' => $total
			);
		}

		$resp->set('series', $series);
PeepSo::log(__METHOD__.'() done');
		$resp->success(TRUE);
	}

	/**
	 * Default function
	 * Returns series data from the peepso_activities table, custom data should create other functions
	 * @param $type string The post type
	 * @return array Must contain [min, max, data] for proper rendering of graph
	 */
	public function get_stats($type, &$resp)
	{
		global $wpdb;

		$input = new PeepSoInput();
		$module_id = $input->get('module_id');

		add_filter('peepso_activity_date_range_where', array(&$this, 'get_date_range_where'));
		$range = apply_filters('peepso_activity_date_range_where', $input->get('date_range'));

		$resp->set('min', strtotime($range['min']) * 1000);
		$resp->set('max', strtotime($range['max']) * 1000);

		add_filter('peepso_admin_engagement_graph_stats', array(&$this, '_get_stats'), 10, 3);

		$query = apply_filters('peepso_admin_engagement_graph_stats', $type, $module_id, $range);

		$data = array();

		if (count($query) > 0) {
			foreach ($query as $_data) {
//echo 'data=' . var_export($_data, TRUE), PHP_EOL;
//echo 'post_date=', var_export($_data->_post_date), ' strtotime=', strtotime($_data->_post_date) * 1000, PHP_EOL;
				$data[] = array(strtotime($_data->_post_date) * 1000, $_data->post_count);
			}
		}

		return ($data);
	}

	/**
	 * Called from 'peepso_admin_engagement_graph_stats'
	 * @param  string $type      The post type to get stats of
	 * @param  int $module_id 	 The module ID used as reference to the posts
	 * @param  array $range      Contains min, max and a where clause for date range filtering
	 * @return object            The WP_Query object
	 */
	public function _get_stats($type, $module_id, $range)
	{
		global $wpdb;

		switch ($type) {
			case PeepSoActivityStream::CPT_POST:
			case PeepSoActivityStream::CPT_COMMENT:
				// TODO: use TABLE_NAME constant from PeepSoActivity for table reference
				$base_query = '
					SELECT
						COUNT(`ID`) AS `post_count`, CAST(`post_date` AS DATE) AS `_post_date`
						FROM `'. $wpdb->posts .'`
						LEFT JOIN `'. $wpdb->prefix . 'peepso_activities` `pa`
							ON `pa`.`act_external_id` = `'. $wpdb->posts . '`.`ID`
						WHERE ';
				$where = array('`pa`.`act_module_id` = %d');

				$where[] = '`post_type` = %s';
				$where[] = $range['where'];
				$base_query .= implode(' AND ', $where) . ' GROUP BY `_post_date`';
				$base_query = $wpdb->prepare($base_query, $module_id, $type);
PeepSo::log(__METHOD__.'() base query=' . $base_query);
//				return ($wpdb->get_results($base_query));
//echo 'query=' . $base_query, PHP_EOL;
				$res = $wpdb->get_results($base_query);
//echo 'res=' . var_export($res, TRUE), PHP_EOL;
				return ($res);
			case 'likes':
				$likes = new PeepSoLike();
				return ($likes->get_likes_graph_data_by_module(
					$module_id,
					array('from' => $range['min'], 'to' => $range['max'])
				));
			default:
				// Try to get a custom filter.
				return (apply_filters('peepso_admin_engagement_graph_stats-' . $type, $module_id, $range));
		}
	}

	/*
	 * Determines the minimum and maximum dates to query for data and precreates an
	 * SQL WHERE condition
	 * @string $date_range The date range indentifier
	 * @return array [min, max, where]
	 */
	public function get_date_range_where($date_range)
	{
		// Note: strtotime() returns different data for certain strings such as 'first day this month' depending on the version of PHP.
		// Because of this, I added checking for a return value of FALSE, indicating an error. In these cases, I calculate the date
		// "the hard way" by using the day of week or the day of month. This ensure correct behavior across different PHP versions.

		// TODO: should be using the configured starting day of week instead of assuming Sun...Sat
		switch ($date_range)
		{
		case 'this_week':
			$min = strtotime('sunday last week'); // this week');	// $date_time_from->modify('this week')->format('Y-m-d');
			// Add one more day so that saturday this week is included
			$max = strtotime('sunday this week'); // last week');	// $date_time_from->modify('next week')->format('Y-m-d');
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
//			if (FALSE === $min || FALSE === $max) {
				$dow = intval(date('w'));						// 0=Sun, 1=Mon, etc.
				$today = strtotime('now');
				$min = strtotime(sprintf('%d days ago', $dow));	// use day of week to calculate beginning of week
				$max = $today;									// use today as end of week
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
//			}
			break;
		case 'last_week':
			$min = strtotime('sunday 1 week ago'); // monday last week');	// $date_time_from->modify('monday last week')->format('Y-m-d');
			$max = strtotime('sunday last week');	// this week');			// $date_time_to->modify('this week')->format('Y-m-d');
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
//			if (FALSE === $min || FALSE === $max) {
				$dow = intval(date('w'));						// 0=Sun, 1=Mon, etc.
				$today = strtotime('now');
				$max = strtotime(sprintf('%d days ago', $dow + 1));	// use day of week to calculate end of last week
				$min = strtotime('6 days ago', $max);
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
//			}
			break;
		case 'this_month':
			$min = strtotime('first day this month');	// $date_time_from->modify('first day of this month')->format('Y-m-d');
			$max = strtotime('last day this month');		// $date_time_to->modify('last day of this month')->format('Y-m-d');
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
			if (FALSE === $min || FALSE === $max || 1 !== intval(date('d', $min))) {
				$today = strtotime('now');					// start with current date

				$day = intval(date('d', $today));			// get day of month
				$max = $today;								// it's the current month so use today

				$min = strtotime(sprintf('%d days ago', $day - 1), $max);	// first day of month = last - (days in month - 1)
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
			}
			break;
		case 'last_month':
PeepSo::log('calculating last month:');
			$min = strtotime('first day of last month');	// $date_time_from->modify('first day of last month')->format('Y-m-d');
			$max = strtotime('last day of last month');		// $date_time_to->modify('last day of last month')->format('Y-m-d');
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
			if (FALSE === $min || FALSE === $max || 1 !== intval(date('d', $min))) {
				$today = strtotime('now');					// start with current date

				$day = intval(date('d', $today));			// get day of month
				$max = strtotime(sprintf('%d days ago', $day), $today);	// last day of previous month = today - day_of_month

				$days_in_month = intval(date('d', $max));		// get days in last month
				$min = strtotime(sprintf('%d days ago', $days_in_month - 1), $max);	// first day of month = last - (days in month - 1)
//echo 'min=', var_export($min), date(' Y-m-d', $min), ' max=', var_export($max), date(' Y-m-d', $max), ' : ', __LINE__, PHP_EOL;
			}
			break;
		default:
			return '1';
		}
		$mindate = date('Y-m-d', $min);
		$maxdate = date('Y-m-d', $max);
PeepSo::log(__METHOD__."() range={$date_range} min={$min}/{$mindate}  max={$max}/{$maxdate}");

		return (array(
			'min' => $mindate,
			'mints' => $min,
			'max' => $maxdate,
			'maxts' => $max,
			'where' => " (`post_date` BETWEEN '{$mindate}' AND '{$maxdate}') "
		));
	}
}

// EOF
<?php

class PeepSoActivityAdmin
{
	public static function administration()
	{
		$oPeepSoListTable = new PeepSoActivityListTable();
		$oPeepSoListTable->prepare_items();


		echo '<form id="form-activity" method="post">';
		PeepSoAdmin::admin_header('Activities');
		echo '<div id="peepso" class="wrap">';
		wp_nonce_field('bulk-action', 'activity-nonce');
		echo $oPeepSoListTable->search_box(__('Search User', 'peepso'), 'search');
		$oPeepSoListTable->display();
		echo '</div>';
		echo '</form>';
	}

	/**
	 * adds items to the dashboard tabs
	 * @param array $tabs Dashboard tabs
	 * @return array $tabs Dashboard tabs with new post menu
	 */
	// TODO: make this a non-static method
	public static function add_dashboard_tabs($tabs)
	{
		global $wpdb;

		// use config setting for date span
//		$date = date('Y-m-d H:i:s', strtotime('now - ' . intval(PeepSo::get_option('site_dashboard_reportperiod')) . ' hours'));
		$date = date('Y-m-d', strtotime('now - ' . intval(PeepSo::get_option('site_dashboard_reportperiod')) . ' hours'));
		$sql = "SELECT COUNT(*) AS `val` " .
				" FROM `{$wpdb->posts}` " .
				" WHERE CAST(`post_date` AS DATE)>=%s AND `post_status`='publish' AND `post_type` IN ('peepso-post', 'peepso-comment')";
		$val = $wpdb->get_var($wpdb->prepare($sql, $date));

		$activity = array(
			'slug' => 'peepso-activities',
			'menu' => __('Activities', 'peepso'),
			'icon' => 'format-aside',
			'count' => $val,
			'function' => array('PeepSoActivityAdmin', 'administration'),
		);

		$tabs['blue']['posts'] = $activity;
		return ($tabs);
	}
}

// EOF
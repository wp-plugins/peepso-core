<?php

class PeepSoDataPurge
{
	public static function purge_notification_items()
	{
PeepSo::log(__METHOD__.'() starting...');
		global $wpdb;

		// look for items more than 20 days old
		$dt = date('Y-m-d', strtotime('20 days ago'));

		// TODO: use PeepSoNotifications class constant for table name
		$sql = "DELETE FROM `{$wpdb->prefix}peepso_notifications` " .
			" WHERE CAST(`not_timestamp` AS DATE) < %s ";
		$wpdb->query($wpdb->prepare($sql, $dt));
PeepSo::log('  query: ' . $wpdb->last_query);

		// TODO: delete any mail queue items that are `status`=PeepSoMailQueue::STATUS_SENT and more than 24 hours old
	}
}

// EOF
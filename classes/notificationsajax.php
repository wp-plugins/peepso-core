<?php

class PeepSoNotificationsAjax implements PeepSoAjaxCallback
{
	private static $_instance = NULL;

	private function __construct()
	{
	}

	// @todo docblock
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

	// @todo docblock
	public function get_latest(PeepSoAjaxResponse $resp)
	{
		$user_id = PeepSo::get_user_id();

		$profile = PeepSoProfile::get_instance();
		$profile->set_user_id($user_id);

		$input = new PeepSoInput();
		$limit = $input->get('per_page', 10);
		$page = $input->get('page', 1);

		$offset = $limit * max(0, $page - 1);

		$notifications = array();

		if ($profile->has_notifications()) {
			while ($profile->next_notification($limit, $offset)) {
				$notifications[] = PeepSoTemplate::exec_template('general', 'notification-popover-item', NULL, TRUE);
			}

			$resp->success(TRUE);
			$resp->set('notifications', $notifications);
		} else {
			$resp->success(FALSE);
			$resp->error(__('No notifications.', 'peepso'));
		}
	}

	// @todo docblock
	public function get_latest_count(PeepSoAjaxResponse $resp) {

		$note = new PeepSoNotifications();
		$unread_notes = $note->get_unread_count_for_user(PeepSo::get_user_id());
		$data = array('count' => $unread_notes);
		
		$resp->data['ps-js-notifications'] 			= array();
		$resp->data['ps-js-notifications'] 			= $data;
		$resp->data['ps-js-notifications']['el'] 	= 'ps-js-notifications';

		$resp->success(TRUE);
		$resp = apply_filters('peepso_live_notifications', $resp);
	}
}

// EOF
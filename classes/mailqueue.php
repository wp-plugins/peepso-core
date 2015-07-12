<?php

class PeepSoMailQueue
{
	const TABLE = 'peepso_mail_queue';

	// values for the `mail_status` column
	const STATUS_PENDING = 0;
	const STATUS_PROCESSING = 1;
	const STATUS_DELAY = 2;
	const STATUS_FAILED = 3;
//	const STATUS_SENT = 4;					// TODO: not used yet

	private static $_max_exec_time = 30;

	/**
	 * Returns the privately defined $_max_exec_time variable value.
	 * @return int
	 */
	private static function get_max_exec_time()
	{
		return (self::$_max_exec_time);
	}

	/*
	 * Adds an item to the mail queue. NOTE: This should only be called from add_message()
	 * @param int $user_id The user id of the recipient
	 * @param string $email The email address of the recipient
	 * @param string $subject The email subject line
	 * @param string $content The contents of the email to send to the recipent
	 * @param string $type The type of notification settings to be checked if email is allowed or not
	 * @param int $module The module id originating the message
	 */
	private static function add($user_id, $email, $subject, $content, $type, $module = 0)
	{
		// the new_user and user_approved emails are not configurable for ignoring
		// TODO: better approach would be to ignore these when writing the settings, instead of every time we're using them.
		if (!in_array($type, array('new_user', 'user_approved', 'register', 'welcome', 'password_recover', 'password_changed', 'new_user_registration'))) {
			$notifications = get_user_meta($user_id, 'peepso_notifications');
			// do not send any notification when it's disabled
			if (1 === intval(PeepSo::get_option('site_alerts_' . $type, 0)) &&
				isset($notifications[0]) &&
				in_array($type . '_email', $notifications[0]))
				return (FALSE);
		}

		$aCols = array(
			'mail_user_id' => intval($user_id),
			'mail_recipient' => $email,
			'mail_subject' => $subject,
			'mail_message' => $content,
			'mail_module_id' => $module,
		);

		global $wpdb;
		$wpdb->insert($wpdb->prefix . self::TABLE, $aCols);
	}

	/*
	 * add item to the mail queue
	 * @param int $user_id The user id of the recipient
	 * @param array $data The tokens for the email template
	 * @param string $subject The email subject line
	 * @param string $template The template name to send to the recipent
	 * @param string $type The type of notification settings to be checked if email is allowed or not
	 * @param int $module The module id originating the message
	 */
	public static function add_message($user_id, $data, $subject, $template, $type, $module = 0)
	{
		// load the general email template
		$contents = file_get_contents(PeepSoTemplate::get_template('general', 'email'));

		// load the contents of the email
		$message = get_option('peepso_email_' . $template);

		// setup the template replacement data
		$em = new PeepSoEmailTemplate();
		$em->set_tokens($data);

		$msg = $em->replace_tokens($contents, $message);

		self::add($user_id, $data['useremail'], $subject, $msg, $type, $module);
	}

	/*
	 * Add item to the mail queue directly to an email address. This method is used when the recipient may not be a member.
	 * @param mixed $email The email address of the recipient
	 * @param array $data The tokens for the email template
	 * @param string $subject The email subject line
	 * @param string $message The message to send to the recipent
	 * @param string $type The type of notification settings to be checked if email is allowed or not
	 * @param int $module The module id originating the message
	 * @return int $sent The number of sent messages
	 */
	public static function send_message($emails, $data, $subject, $message, $type, $module = 0)
	{
		if (!is_array($emails))
			$emails = array($email);

		// load the general email template
		$contents = file_get_contents(PeepSoTemplate::get_template('general', 'email'));

		// setup the template replacement data
		$em = new PeepSoEmailTemplate();

		$sent = 0;
		foreach ($emails as $email) {
			if (!is_email($email))
				continue;

			$data['userfullname'] = $email;
			$data['useremail'] = $email;

			$em->set_tokens($data);

			$msg = $em->replace_tokens($contents, $message);

			self::add(0, $email, $subject, $msg, $type, $module);
			++$sent;
		}

		return ($sent);
	}

	/*
	 * Processes the items in the mail queue.
	 */
	public static function process_mailqueue()
	{
		global $wpdb;

		$sStartTime = microtime(true);

		$headers[] = 'From: ' . PeepSo::get_option('site_emails_sender') . ' <' . PeepSo::get_option('site_emails_admin_email') . '>';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';

		add_filter('wp_mail_content_type', array(__CLASS__, 'html_content_type'));

		$queue = self::get_by_status(self::STATUS_PENDING, PeepSo::get_option('site_emails_process_count', 25));

		$iProcessed = 0;
		$sCurrentRunTime = 0;
		foreach ($queue as $mail) {
			// same WHERE clause is used multiple times
			$where = array('mail_id' => $mail->mail_id);

			$wpdb->update(self::get_table_name(), array('mail_status' => self::STATUS_PROCESSING), $where);

			$success = wp_mail($mail->mail_recipient, $mail->mail_subject, $mail->mail_message, $headers);

			// update `mail_status` on failure
			if (!$success)
				$wpdb->update(self::get_table_name(), array('mail_status' => self::STATUS_FAILED), $where);
			else
				$wpdb->delete(self::get_table_name(), $where);

			++$iProcessed;

			$sCurrentRunTime = microtime(true) - $sStartTime;
			if ($sCurrentRunTime  > self::get_max_exec_time())
				break;
		}

		remove_filter('wp_mail_content_type', array(__CLASS__, 'html_content_type'));

		$aBatchHistory = array('elapsed' => $sCurrentRunTime, 'processed' => $iProcessed);

		$aPeepSoMailqueueHistory = get_option('peepso_mailqueue_history');

		if (!$aPeepSoMailqueueHistory)
			$aPeepSoMailqueueHistory = array();

		if (count($aPeepSoMailqueueHistory) >= 10)
			array_shift($aPeepSoMailqueueHistory);

		$aPeepSoMailqueueHistory[] = $aBatchHistory;

		update_option('peepso_mailqueue_history', $aPeepSoMailqueueHistory);
	}


	/*
	 * Sets the content type for email messages
	 * @return string The SMTP content type for the email
	 */
	public static function html_content_type($content_type)
	{
		return ('text/html; charset=iso-8859-1');
	}


	/**
	 * Fetches all mail queued up on the database.
	 * @param  int $limit  How many records to fetch.
	 * @param  int $offset Fetch records beginning from this index.
	 * @param  string  $order  Order by column.
	 * @param  string  $dir    The sort direction, defaults to 'asc'
	 * @return array Array of the result set.
	 */
	public static function fetch_all($limit = NULL, $offset = 0, $order = NULL, $dir = 'asc')
	{
		global $wpdb;

		$query = 'SELECT *				
			FROM `' . self::get_table_name() . '` ';

		if (isset($order))
			$query .= ' ORDER BY `' . $order . '` ' . $dir;

		if (isset($limit))
			$query .= ' LIMIT ' . $offset . ', ' . $limit;

		return ($wpdb->get_results($query, ARRAY_A));
	}

	/*
	 * Return list of MailQueue items based on the status
	 * @param string $status The status to filter with.
	 * @return array Returns array of MailQueue items in chronologic order
	 */
	public static function get_by_status($status, $limit = 25)
	{
		global $wpdb;

		$sql = 'SELECT * 
				FROM `' . self::get_table_name() . '`
				WHERE `mail_status` = %d
				ORDER BY `mail_created_at` ASC
				LIMIT %d ';
		$res = $wpdb->get_results($wpdb->prepare($sql, $status, $limit));
		return ($res);
	}

	/*
	 * Get a count of the pending items in the mail queue
	 * @return int A count of the items
	 */
	public static function get_pending_item_count()
	{
		global $wpdb;

		$sql = 'SELECT COUNT(*) AS `val`
				FROM `' . self::get_table_name() . '`
				WHERE `mail_status`=%d ';
		$msg_count = $wpdb->get_var($wpdb->prepare($sql, self::STATUS_PENDING));

		return ($msg_count);
	}

	/*
	 * Get number of seconds to complete mail queue
	 * @return int
	 */
	public static function get_completion_estimate()
	{
		$queue_history = get_option('peepso_mailqueue_history');

		if (!$queue_history)
			return (0);

		$history_count = count($queue_history);
		$pending_item_count = self::get_pending_item_count();
		$run_time = 0;
		$processed = 0;

		// Get average running time and emails sent per process
		foreach ($queue_history as $history) {
			$run_time += $history['elapsed'];
			$processed += $history['processed'];
		}

		$average_run_time = $run_time / $history_count;
		$average_processed = $processed / $history_count;

		$runs_left = $pending_item_count / max($average_processed, 1);
		// Multiply	runs left to time interval and average run time
		$schedule = wp_get_schedule(PeepSo::CRON_MAILQUEUE);
		$schedules = wp_get_schedules();
		$interval = $schedules[$schedule]['interval'];

		$estimate_time = ($interval * $runs_left) + ($average_run_time * $runs_left);

		return ($estimate_time);
	}

	/**
	 * Convenience function to return the mailqueue table name as a string.
	 * @return string The table name.
	 */
	public static function get_table_name()
	{
		global $wpdb;

		return ($wpdb->prefix . self::TABLE);
	}
}

// EOF

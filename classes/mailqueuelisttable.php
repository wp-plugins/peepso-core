<?php

class PeepSoMailqueueListTable extends PeepSoListTable 
{
	/**
	 * Defines the query to be used, performs sorting, filtering and calling of bulk actions.
	 * @return void
	 */
	public function prepare_items()
	{
		global $wpdb;
		$input = new PeepSoInput();
		if (isset($_POST['action']))
			$this->process_bulk_action();

		$limit = 20;
		$offset = ($this->get_pagenum() - 1) * $limit;

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$totalItems = count(PeepSoMailQueue::fetch_all());

		$aQueueu = PeepSoMailQueue::fetch_all($limit, $offset, $input->get('orderby', NULL), $input->get('order', NULL));

		foreach ($aQueueu as $mail) {
			$mail['mail_message'] = substr(wp_strip_all_tags($mail['mail_message']), 0, 49);
		}

		$this->set_pagination_args(array(
				'total_items' => $totalItems,
				'per_page' => $limit
			)
		);
		$this->items = $aQueueu;
	}

	/**
	 * Return and define columns to be displayed on the Mail Queue table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_columns()
	{
		return array(
			'cb' => '<input type="checkbox" />',
			'mail_id' => 'ID',
			'mail_recipient' => __('Recepient', 'peepso'),
			'mail_subject' => __('Subject', 'peepso'),
			'mail_message' => __('Message', 'peepso'),
			'mail_created_at' => __('Date Created', 'peepso'),
			'mail_status' => __('Status', 'peepso')
		);
	}

	/**
	 * Return and define columns that may be sorted on the Mail Queue table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_sortable_columns()
	{
		return array(
			'mail_id' => array('mail_id', true),
			'mail_recipient' => array('mail_recipient', false), 
			'mail_subject' => array('mail_subject', false), 
			'mail_created_at' => array('mail_created_at', false), 
			'mail_status' => array('mail_status', false)
		);
	}

	/**
	 * Return default values to be used per column
	 * @param  array $item The post item.
	 * @param  string $column_name The column name, must be defined in get_columns().
	 * @return string The value to be displayed.
	 */
	public function column_default($item, $column_name)
	{
		return $item[$column_name];
	}

	/**
	 * Returns the output for the message column.
	 * @param  array $item The current post item in the loop.
	 * @return string The message cell's HTML.
	 */
	public function column_mail_message($item)
	{
		return wp_strip_all_tags($item['mail_message']);
	}

	/**
	 * Returns the HTML for the checkbox column.
	 * @param  array $item The current post item in the loop.
	 * @return string The checkbox cell's HTML.
	 */
	public function column_cb($item)
	{
		return sprintf('<input type="checkbox" name="mailqueue[]" value="%d" />',
    		$item['mail_id']
    	);
	}

	/**
	 * Returns the output for the status column.
	 * @param  array $item The current post item in the loop.
	 * @return string The status cell's HTML.
	 */
	public function column_mail_status($item)
	{
		switch ($item['mail_status']) {
		case PeepSoMailQueue::STATUS_PENDING:
			return __('Waiting', 'peepso');
			break;
		case PeepSoMailQueue::STATUS_PROCESSING:
			return __('Processing', 'peepso');
			break;
		case PeepSoMailQueue::STATUS_DELAY:
			return __('Delay', 'peepso');
			break;
		case PeepSoMailQueue::STATUS_FAILED:
			return __('Failed', 'peepso');
//		case PeepSoMailQueue::STATUS_SENT:
//			return __('Sent', 'peepso');
		default:
			return __('Unknown', 'peepso');
			break;
		}
	}

	/**
	 * Define bulk actions available
	 * @return array Associative array of bulk actions, keys are used in self::process_bulk_action().
	 */
	public function get_bulk_actions() 
	{
		return array(
			'delay' => __('Delay items', 'peepso'),
			'waiting' => __('Set items to waiting', 'peepso'),
			'delete' => __('Delete items', 'peepso')
		);
	}

	/** 
	 * Performs bulk actions based on $this->current_action()
	 * @return void Redirects to the current page.
	 */
	public function process_bulk_action()
	{
//		if ('-1' !== $this->current_action() && isset($_POST['mailqueue-nonce']) &&
//			wp_verify_nonce($_POST['mailqueue-nonce'], 'mailqueue-nonce')) {
		if ('-1' !== $this->current_action() && check_admin_referer('bulk-action', 'mailqueue-nonce')) {
			global $wpdb;

			if ('delete' === $this->current_action()) {
				foreach ($_POST['mailqueue'] as $mailId) {
					$query = 'DELETE FROM `' . PeepSoMailQueue::get_table_name() . '` WHERE `mail_id` = %d';
					$wpdb->query($wpdb->prepare($query, $mailId));
				}

				$message = __('deleted', 'peepso');
			} else if (in_array($this->current_action(), array('waiting', 'delay'))) {
				if ('waiting' === $this->current_action()) {
					$status = PeepSoMailQueue::STATUS_PENDING;
				} else {
					$status = PeepSoMailQueue::STATUS_DELAY;
				}

				foreach ($_POST['mailqueue'] as $mailId) {
					$wpdb->update(
						PeepSoMailQueue::get_table_name(), 
						array('mail_status' => $status),
						array('mail_id' => $mailId)
					);
				}

				$message = __('updated', 'peepso');
			}
			$count = count($_POST['mailqueue']);

			PeepSoAdmin::get_instance()->add_notice(
				sprintf(__('%1$d %2$s %3$s', 'peepso'),
					$count,
					_n('email', 'emails', $count, 'peepso'),
					$message),
//				$count . ' ' . _n('email', 'emails', $count, 'peepso') . ' ' . $message . '.',
				'note');

			PeepSo::redirect("//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		}
	}

	/**
	 * Adds The 'Process Emails' button and mail queue estimate to the top of the table.
	 * @param  string $which The current position to display the HTML.
	 * @return void Echoes the content.
	 */
	public function extra_tablenav($which)
	{
		if ('top' === $which) {
			$nonce = wp_create_nonce('process-mailqueue-nonce');
			echo '
			<div class="alignleft actions">
				<a href="', admin_url('admin.php?page=peepso-mailqueue&action=process-mailqueue&_wpnonce=' . $nonce), '">
					<input type="button" class="button" value="', __('Process Emails', 'peepso'), '" />
				</a>
			</div>';

			$completion_estimate = PeepSoMailQueue::get_completion_estimate();

			if ($completion_estimate) {
				$completion_estimate = ceil($completion_estimate / 60);
				echo '<div class="alignright actions admin-tablenav">', PHP_EOL;
				echo '<span>', sprintf(__('Estimated time until Mail Queue is empty: %1$d %2$s.', 'peepso'),
						$completion_estimate, _n('minute', 'minutes', $completion_estimate, 'peepso')), '</span>', PHP_EOL;
				echo '</div>', PHP_EOL;
			}
		}
	}
}

// EOF
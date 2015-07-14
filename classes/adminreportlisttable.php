<?php

class PeepSoAdminReportListTable extends PeepSoListTable 
{
	/**
	 * Set options for WP_List_Table.
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'singular' => 'report',
				'plural' => 'reports',
				'ajax' => true,
				'screen' => 'interval-list' 
			));
	}

	/**
	 * Defines the query to be used, performs sorting, filtering and calling of bulk actions.
	 * @return void
	 */
	public function prepare_items()
	{
		global $wpdb;

		$input = new PeepSoInput();

		add_filter('peepso_admin_report_columns', array(&$this, 'prepare_columns'));
		add_filter('peepso_admin_report_column_data', array(&$this, 'get_column_data'), 10, 2);

		if ($input->post_exists('action'))
			$this->process_bulk_action();

		$limit = 20;
		$offset = ($this->get_pagenum() - 1) * $limit;

		$this->_column_headers = array(
			$this->get_columns(),
			array('rep_external_id', 'rep_module_id'),
			$this->get_sortable_columns()
		);

		$rep = new PeepSoReport();
		$orderby = '';
		if (isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $this->get_sortable_columns())) {
			$orderby = $_GET['orderby'];

//			if (isset($_GET['order']))
//				$order = strtoupper($_GET['order']);
//			$order = ($order === 'ASC') ? 'ASC' : 'DESC';
		} else {
			$orderby = 'rep_id';
		}

		$order = 'DESC';
		if (isset($_GET['order']))
			$order = strtoupper($_GET['order']);
		$order = ('ASC' === $order) ? 'ASC' : 'DESC';
PeepSo::log(__METHOD__."() orderby={$orderby} order={$order}");

		$items = $rep->get_reports($orderby, $order, $offset, $limit);

		$this->set_pagination_args(array(
				'total_items' => $rep->get_num_reported_items(),
				'per_page' => $limit
			));
		$this->items = $items;
	}

	/**
	 * Return and define columns to be displayed on the Report table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'rep_id' => __('ID', 'peepso'),
			'rep_user_count' => __('Users Reporting', 'peepso'),
			'rep_reason' => __('Reason', 'peepso'),
			'post_title' => __('Link to Post', 'peepso'),
			'post_excerpt' => __('Content', 'peepso'),
			'rep_timestamp' => __('Date Submitted', 'peepso'),
			'actions' => __('Actions', 'peepso')
		);

		return (apply_filters('peepso_admin_report_columns', $columns));
	}

	public function prepare_columns($columns)
	{
		return ($columns);
	}


	/**
	 * Return and define columns that may be sorted on the Report table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_sortable_columns()
	{
		return (array(
			'rep_id' => array('rep_id', true),
		));
	}

	/**
	 * Return default values to be used per column
	 * @param  array $item The post item.
	 * @param  string $column_name The column name, must be defined in get_columns().
	 * @return string The value to be displayed.
	 */
	public function column_default($item, $column_name)
	{
		return apply_filters('peepso_admin_report_column_data', $item, $column_name);
	}

	/**
	 * Get the data from the $item based on the given $column_name
	 * @param array $item An array of single report row/item
	 * @param string $column_name The field or column name
	 * @return string
	 */
	public function get_column_data($item, $column_name)
	{
PeepSo::log(__METHOD__.'() column=' . $column_name .' item=' . var_export($item, TRUE));
		switch ($column_name)
		{
		case 'post_title':
			switch ($item['rep_module_id'])
			{
			case PeepSoActivity::MODULE_ID:
				return ('<a href="' . PeepSo::get_page('activity') . 'status/' . $item[$column_name] . '/" target="_blank">' . $item[$column_name] . '</a>');
				break;
			case PeepSo::MODULE_ID:
				$user = $this->get_user($item['rep_external_id']);
				return ('<a href="' . $user->get_profileurl() . '">' . $user->get_profileurl() . '</a>');
				break;
			default:
				$title = apply_filters('peepso_report_column_title', NULL, $item, $column_name);

				if (NULL !== $title)
					return ($title);
				break;
			}
			break;
		case 'post_excerpt':
			switch ($item['rep_module_id'])
			{
			case PeepSoActivity::MODULE_ID:
				return (substr(strip_tags($item[$column_name]), 0, 30));
				break;
			case PeepSo::MODULE_ID:
				$user = $this->get_user($item['rep_external_id']);
				return ('<a href="' . $user->get_profileurl() . '">' . $user->get_profileurl() . '</a>');
				break;
			default:
				# code...
				break;
			}
		}
		return ($item[$column_name]);
	}

	/**
	 * Generate row actions div
	 * @param array $item An array of single report row/item
	 * @return array $actions The list of actions
	 */
	public function column_actions($item)
	{
//		$uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$actions = array(
//            'unpublish' => '<a href="#" onclick="list.unpublish(' . $item['rep_id'] . '); return false;">
//            	<input type="button" value="' . __('Unpublish', 'peepso') . '" ' .
//				' title="' . __('Make this item no longer published, but not deleted', 'peepso') . '" class="button action"/></a>',
            'dismiss'   => '<a href="#" onclick="list.dismiss(' . $item['rep_id'] . '); return false;">
            	<input type="button" value="' . __('Dismiss', 'peepso') . '" ' .
				' title="' . __('Delete this item, leave the post alone.', 'peepso') . '" class="button action"/></a>',
        );

		switch ($item['rep_module_id']) {
			case PeepSoActivity::MODULE_ID:
				$actions['unpublish'] = '<a href="#" onclick="list.unpublish(' . $item['rep_id'] . '); return false;">
	            	<input type="button" value="' . __('Unpublish', 'peepso') . '" ' .
					' title="' . __('Make this item no longer published, but not deleted', 'peepso') . '" class="button action"/></a>';
				break;
			case PeepSo::MODULE_ID:
				$actions['ban'] = '<a href="#" onclick="list.ban(' . $item['rep_id'] . '); return false;">
	            	<input type="button" value="' . __('Ban Profile', 'peepso') . '" ' .
					' title="' . __('Ban this profile, but not deleted', 'peepso') . '" class="button action"/></a>';
				break;
			default:
				# code...
				break;
		}

  		return ($this->row_actions($actions));
	}

	/**
	 * Returns the HTML for the checkbox column.
	 * @param  array $item The current post item in the loop.
	 * @return string The checkbox cell's HTML.
	 */
	public function column_cb($item)
	{
		return (sprintf('<input type="checkbox" name="reports[]" value="%d" />',
    		$item['rep_id']));
	}

	/**
	 * Define bulk actions available
	 * @return array Associative array of bulk actions, keys are used in self::process_bulk_action().
	 */
	public function get_bulk_actions() 
	{
		return (array(
			'unpublish' => __('Unpublish', 'peepso'),
			'dismiss' => __('Dismiss', 'peepso')
		));
	}

	/** 
	 * Performs bulk actions based on $this->current_action()
	 * @return void Redirects to the current page.
	 */
	public function process_bulk_action()
	{
		if ($this->current_action() && check_admin_referer('bulk-action', 'report-nonce')) {
			global $wpdb;
			$count = 0;
			$oReport = new PeepSoReport();

			if ('unpublish' === $this->current_action()) {
				foreach ($_POST['reports'] as $repId)
					$count += $oReport->unpublish_report(intval($repId));

				$message = __('unpublished', 'peepso');
			} else if ('dismiss' === $this->current_action()) {
				foreach ($_POST['reports'] as $repId)
					$count += $oReport->dismiss_report(intval($repId));

				$message = __('dismissed', 'peepso');
			}

			PeepSoAdmin::get_instance()->add_notice(
				sprintf(__('%1$d %2$s %3$s', 'peepso'),
					$count,
					_n('report', 'reports', $count, 'peepso'),
					$message),
//				$count . ' ' . _n('report', 'reports', $count, 'peepso') . ' ' . $message . '.',
				'note');

			wp_redirect("//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		}
	}


	/**
	 * Gets a PeepSoUser object based on the given user id
	 * @param int $user_id User ID
	 * @return object An instance of PeepSoUser class populated with user data based on the given $user_id
	 */
	public function get_user($user_id)
	{
		static $users = array();

		if (!isset($users[$user_id]))
			$users[$user_id] = new PeepSoUser($user_id);

		return ($users[$user_id]);
	}
}

// EOF

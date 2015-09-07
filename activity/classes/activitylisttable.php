<?php

class PeepSoActivityListTable extends PeepSoListTable 
{
	private $_users = array();

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Defines the query to be used, performs sorting, filtering and calling of bulk actions.
	 * @return void
	 */
	public function prepare_items()
	{
		global $wpdb;

		add_filter('peepso_admin_activity_column_data', array(&$this, 'get_column_data'), 10, 2);

		$input = new PeepSoInput();

		if ($input->post_exists('action'))
			$this->process_bulk_action();

		$limit = 20;
		$offset = ($this->get_pagenum() - 1) * $limit;

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$activities = PeepSoActivity::get_instance();
		$orderby = '';
		$order = 'DESC';
		
		if (isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $this->get_sortable_columns())) {
			$orderby = $_GET['orderby'];
			
			if (isset($_GET['order']))
				$order = strtoupper($_GET['order']);
			$order = ($order === 'ASC') ? 'ASC' : 'DESC';
		}


		$search = array();
		if (isset($_REQUEST['s'])) {
			$wp_list_table = _get_list_table('WP_Users_List_Table');
			$wp_list_table->prepare_items();
			$search['author__in'] = empty($wp_list_table->items) ? array(0) : array_keys($wp_list_table->items);			
		}

		$items = $activities->get_all_activities($orderby, $order, $limit, $offset, $search);

		$this->set_pagination_args(array(
				'total_items' => $items->found_posts,
				'per_page' => $limit
			));

		$this->items = $items->posts;
	}

	/**
	 * Return and define columns to be displayed on the Activity table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_columns()
	{
		$columns = array(
			'ID' => __('#', 'peepso'),
			'cb' => '<input type="checkbox" />',
			'user_avatar' => '',
			'activity_action' => __('Title', 'peepso'),
			'post_date_gmt' => __('Created', 'peepso'),
			'post_status' => __('Status', 'peepso')
		);

		return (apply_filters('peepso_admin_activity_columns', $columns));
	}

	/**
	 * Return and define columns that may be sorted on the Activity table.
	 * @return array Associative array of columns with the database columns used as keys.
	 */
	public function get_sortable_columns()
	{
		return (array(
			'ID' => array('ID', true),
			'post_date_gmt' => array('post_date_gmt', true),
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
		return (apply_filters('peepso_admin_activity_column_data', $item, $column_name));
	}

	/**
	 * Return values based on the column requested.
	 * @param  array $item The post item.
	 * @param  string $column_name The column name, must be defined in get_columns().
	 * @return mixed The value to be displayed.
	 */
	public function get_column_data($item, $column_name)
	{
		$user = $this->get_user($item->post_author);

		switch ($column_name)
		{
		case 'post_status':
			return (ucfirst($item->post_status));
//		case 'post_title':
//			return ('<a href="' . PeepSo::get_page('activity') . 'status/' . $item->$column_name . '/" target="_blank">' . $item->$column_name . '</a>');
		case 'post_excerpt':
			return (substr(strip_tags($item->$column_name), 0, 30));
		case 'user_avatar':
			$content = '<a href="' . $user->get_profileurl() . '" target="_blank" title="' . $user->get_username() . '">';
			$content .= '<img src="' . $user->avatar . '" title="' . $user->get_username() . '" width="48" height="48" alt="" /></a>';
			return ($content);
		case 'activity_action':
			$type = get_post_type_object($item->post_type);

			$content = '<a href="' . $user->get_profileurl() . '" target="_blank">' . $user->get_username() . '</a>&nbsp;';
			$content .= strip_tags($item->post_excerpt);
			$content .= '<br/><a href="' . PeepSo::get_page('activity') . 'status/' . $item->post_title . '/" target="_blank">';
			$content .= __('Link to post', 'peepso') . '</a>';
			return ($content);
		}

		return ($item->$column_name);
	}

	/**
	 * Gets a PeepSoUser object and caches it.
	 * @param  integer $id The user ID.
	 * @return object The PeepSoUser object.
	 */
	public function get_user($id = 0)
	{
		if (!isset($this->_users[$id])) {
			$user = new PeepSoUser($id);
			$user->avatar = $user->get_avatar();
			$this->_users[$id] = $user;
		}

		return ($this->_users[$id]);
	}

	/**
	 * Returns the HTML for the checkbox column.
	 * @param  array $item The current post item in the loop.
	 * @return string The checkbox cell's HTML.
	 */
	public function column_cb($item)
	{
		return (sprintf('<input type="checkbox" name="posts[]" value="%d" />', $item->ID));
	}

	/**
	 * Define bulk actions available
	 * @return array Associative array of bulk actions, keys are used in self::process_bulk_action().
	 */
	public function get_bulk_actions() 
	{
		return (array(
			'archive' => __('Archive', 'peepso'),
			'publish' => __('Publish', 'peepso'),
			'delete' => __('Delete', 'peepso'),
		));
	}

	/** 
	 * Performs bulk actions based on $this->current_action()
	 * @return void Redirects to the current page.
	 */
	public function process_bulk_action()
	{
		if ($this->current_action() && check_admin_referer('bulk-action', 'activity-nonce')) {
			$input = new PeepSoInput();
			$count = 0;
			$posts = $input->post('posts', array());
			$post = array();
			if ('archive' === $this->current_action() || 'publish' === $this->current_action()) {
				foreach ($posts as $id) {
					$post['ID'] = intval($id);
					$post['post_status'] = $this->current_action();

					wp_update_post($post);
				}

				$message = __('Updated', 'peepso');
			} else if ('delete' === $this->current_action()) {
				foreach ($posts as $id)
					wp_delete_post(intval($id));

				$message = __('Deleted', 'peepso');
			}

			$count = count($posts);

			PeepSoAdmin::get_instance()->add_notice(
				sprintf(__('%1$d %2$s %3$s', 'peepso'),
					$count,
					_n('post', 'posts', $count, 'peepso'),
					$message),
				'note');

			PeepSo::redirect("//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		}
	}
}

// EOF

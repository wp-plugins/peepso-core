<?php

class PeepSoMemberSearch implements PeepSoAjaxCallback
{
	protected static $_instance = NULL;
	private $_member_query = NULL;

	public $template_tags = array(
		'found_members',
		'get_next_member',
		'show_member'
	);

	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/**
	 * Search for users matching the query.
	 * @param  PeepSoAjaxResponse $resp
	 */
	public function search(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();

		if (FALSE === wp_verify_nonce($input->get('_wpnonce'), 'member-search')) {
			$resp->success(FALSE);
			$resp->error(__('Unable to validate form contents. Try reloading the page.', 'peepso'));
			return;
		}

		$query = stripslashes_deep($input->get('query', ''));
		$query_results = new PeepSoUserSearch(array(), PeepSo::get_user_id(), $query);

		if (FALSE === empty($query) && count($query_results->results) > 0) {
			foreach ($query_results->results as $user_id)
				$notifications[] = PeepSoTemplate::exec_template('members', 'search-popover-item', array('user_id' => $user_id), TRUE);

			$resp->success(TRUE);
			$resp->set('notifications', $notifications);
		} else {
			$resp->success(FALSE);
			$resp->error(__('No users found.', 'peepso'));
		}		
	}

	/**
	 * Sets the _member_query variable to use is template tags
	 * @param PeepSoUserSearch $query
	 */
	public function set_member_query(PeepSoUserSearch $query)
	{
		$this->_member_query = $query;
	}

	/**
	 * Return TRUE/FALSE if the user has friends
	 * @return boolean
	 */
	public function found_members()
	{
		if (is_null($this->_member_query))
			return FALSE;

		return (count($this->_member_query) > 0);
	}

	/**
	 * Iterates through the $_member_query and returns the current member in the loop.	 
	 * @return PeepSoUser A PeepSoUser instance of the current member in the loop.
	 */
	public function get_next_member()
	{
		if (is_null($this->_member_query))
			return FALSE;

		return $this->_member_query->get_next();
	}

	/**
	 * Displays the member.
	 * @param  PeepSoUser $member A PeepSoUser instance of the member to be displayed.
	 */
	public function show_member($member)
	{
		echo '<div class="ps-members-item-avatar">
				<div class="ps-avatar">',
			sprintf('<a href="' . $member->get_profileurl() . '"><img alt="%s" title="%s" src="%s" class="ps-name-tips"></a>',
				$member->get_display_name(),
				$member->get_display_name(),
				$member->get_avatar(TRUE)),
			'
				</div>
			</div>			
			<div class="ps-members-item-body">
				<a href="' , $member->get_profileurl(), '" class="ps-members-item-title">'
					, $member->get_display_name(), 
				'</a>
				<span class="ps-members-item-status">';

		do_action('peepso_after_member_thumb', $member->get_id());

		echo '</span></div>';		


		$this->member_options($member->get_id());
	}

	/**
	 * Displays a dropdown menu of options available to perform on a certain user based on their member status.
	 * @param int $user_id The current member in the loop.
	 */
	public function member_options($user_id)
	{
		$options = apply_filters('peepso_member_options', array(), $user_id);

		if (0 === count($options))
			// if no options to display, exit
			return;

		$member_options = '';
		foreach ($options as $name => $data) {
			$member_options .= '<li';

			if (isset($data['li-class']))
				$member_options .= ' class="' . $data['li-class'] . '"';
			if (isset($data['extra']))
				$member_options .= ' ' . $data['extra'];

			$member_options .= '><a href="#" ';
			if (isset($data['click']))
				$member_options .= ' onclick="' . esc_js($data['click']) . '" ';
			$member_options .= ' ">';

			$member_options .= '<i class="ps-icon-' . $data['icon'] . '"></i><span>' . $data['label'] . '</span>' . PHP_EOL;
			$member_options .= '</a></li>' . PHP_EOL;
		}

		echo PeepSoTemplate::exec_template('members', 'member-options', array('member_options' => $member_options), TRUE);		
	}	
}

// EOF

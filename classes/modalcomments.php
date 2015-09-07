<?php

class PeepSoModalComments implements PeepSoAjaxCallback
{
	protected static $_instance = NULL;

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
	 * Gets the comments to be displayed for a commentable object.
	 * @param  PeepSoAjaxResponse $resp
	 */
	public function get_object_comments(PeepSoAjaxResponse $resp)
	{
		$input = new PeepSoInput();
		$object_id = $input->get_int('object_id', NULL);
		$type = $input->get('type', NULL);

		if (is_null($object_id) || is_null($type)) {
			$resp->success(FALSE);
			$resp->error(__('Could not find the post.', 'peepso'));
		} else {
			$object_data = apply_filters('peepso_get_object_comments', array());
			$resp->success(TRUE);
		}
	}

	/**
	 * Gets the HTML to be displayed for a commentable object.
	 * @param  PeepSoAjaxResponse $resp
	 */
	public function get_object(PeepSoAjaxResponse $resp)
	{
		global $post;

		$input = new PeepSoInput();
		// The act_id - since comments are joined from the activities table
		$act_external_id = $input->get_int('object_id', NULL);
		$type = $input->get('type', NULL);

		if (is_null($act_external_id) || is_null($type)) {
			$resp->success(FALSE);
			$resp->error(__('Could not find the post.', 'peepso'));
		} else {
			// The format must be
			//   $objects = array(
			//   	ACT_EXTERNAL_ID => array(
			//   		'module_id' => MODULE_ID
			//   		'content' => CONTENT,
			//   		'post' => WP_POST data
			//   	);
			//   );
			// so we can get which index to display first.
			$objects = apply_filters('peepso_get_object_' . $type, array(), $act_external_id);
			$activity = PeepSoActivity::get_instance();

			$total_objects = count($objects);

			add_filter('peepso_activity_post_actions', array(&$this, 'add_post_actions'));

			// Wrap each object in a div
			foreach ($objects as $key => &$object) {
				$act_id = isset($object['act_id']) ? $object['act_id'] : NULL;
				$act_description = isset($object['act_description']) ? $object['act_description'] : NULL;
				$act = $activity->get_activity_data($key, $object['module_id']);
				$act_post_data = array_merge((array) $object['post'], (array) $act);
				$object = array_merge($object, $act_post_data);

				$object['ID'] = $key;
				$object['type'] = $type;
				$object['object_id'] = $act_external_id;
				$activity->post_data = $object;
				$object['_total_objects'] = $total_objects;

				// disable privacy for multiple object, should follow parent (post) object
				$object['disable_privacy'] = $total_objects > 1 ? TRUE : FALSE;

				// object caption should be the post content in case of single object
				if ($total_objects == 1) {
					// $object['post_attachments'] = $activity->get_content_attachments($object['post']);
					// $object['post_attachments'] = $activity->format_content_attachments($object['post_attachments']);
					// remove_all_filters('peepso_activity_content_attachments');
					$object['act_description'] = $activity->content($object['post'], FALSE);
				} else {
					$object['act_description'] = do_shortcode($act_description);
					$object['act_id'] = $act_id !== NULL ? $act_id : $object['act_id'];
				}

				unset($object['post']);
				$post = json_decode(json_encode($object), FALSE);// convert $post from array to object
				setup_postdata($post);
				$object['content'] = PeepSoTemplate::exec_template('activity', 'comment-modal', $object, TRUE);
				$object['attachment'] = PeepSoTemplate::exec_template('activity', 'comment-modal-attachment', $object, TRUE);
			}

			remove_filter('peepso_activity_post_actions', array(&$this, 'add_post_actions'));

			$index = 0;
			if (is_array($objects) && FALSE === empty($objects))
				$index = array_search($act_external_id, array_keys($objects));

			$resp->set('index', $index);
			$resp->set('objects', array_values($objects)); // Reindex array, needed for javascript index (displays which item was clicked)
			$resp->success(TRUE);
		}
	}

	/**
	 * Adds edit and delete option to objects on the modal.
	 * @param array $options The default options per post
	 * @return  array
	 */
	public function add_post_actions($options)
	{
		$post = $options['post'];

		$user_id = PeepSo::get_user_id();

		if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, $user_id)) {
			$delete_script = 'return activity.delete_activity(' . $post->act_id . ');';

			if ($post->_total_objects == 1) {
				$delete_script = 'return activity.action_delete(' . $post->ID . ');';
			}

			$options['acts']['delete'] = array(
				'href' => 'javscript:void(0);',
				'label' => __('Delete', 'peepso'),
				'class' => 'actaction-delete',
				'icon' => 'trash',
				'click' => $delete_script,
			);
		}

		// only add this if current_user == owner_id or it's an admin
		if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_EDIT, $user_id))
			$options['acts']['edit'] = array(
				'href' => 'javscript:void(0);',
				'label' => __('Edit Caption', 'peepso'),
				'icon' => 'edit',
				'click' => 'activity.edit_activity_description(' . $post->act_id . ', \'' . $post->type . '\', ' . $post->object_id . '); return false',
			);

		return ($options);
	}
}

// EOF

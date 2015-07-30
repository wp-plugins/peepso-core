<?php
// $commentdata['comment_date_gmt'] = current_time('mysql', 1);

/*
 * Implementation of the Activty Stream
 * This class is called via the peepso('activity'...) template tags
 */
class PeepSoActivity implements PeepSoAjaxCallback
{
    protected static $_instance = NULL;

    private $post_list = NULL;
    private $post_idx = 0;

    public $query_type = NULL;				// type of query, the CPT name. used in filter_post_clauses() to adjust WHERE
    public $post_query = NULL;				// WP_Query instance for post queries
    public $post_data = NULL;				// $posts value returned from latest show_post() call

    public $comment_query = NULL;			// WP_Query instance for comment queries
    public $comment_data = NULL;			// $posts value returned from latest show_comment() call

    const TABLE_NAME = 'peepso_activities';
    const HIDE_TABLE_NAME = 'peepso_activity_hide';
    const BLOCK_TABLE_NAME = 'peepso_blocks';

    const MODULE_ID = 1;

    private $owner_id = 0;					// used in modifying query
    private $user_id = NULL;				// used to override the user_id in unit tests
//	private $current_user = 0;				// used in modifying query
    private $post_media = NULL;				// contains the array for the comment/post box media
    private $peepso_media = array();		// contains the array for the $post_media
    private $last_post_id = NULL; 			// used in filter_since_id() to get posts after this ID
    private $first_post_id = NULL; 			// used in filter_before_id() to get posts before this ID
    private $oembed_title = NULL;
    private $oembed_description = NULL;

    // list of allowed template tags
    public $template_tags = array(
        'comment_actions',		// display comment action options
        'comment_age',
        'comment_attachment',	// display add-on content related to comment
        'comment_content',		// outputs the comment's content area
        'content',				// display the_content
        'content_media_class',	// displays/returns content's media class
        'dialogs',				// gives add-ons a chance to output dialog boxes
        'post_status',			// display post status box
        'has_comments',			// looping method for comments
        'has_likes',
        'has_max_comments', 	// whether a post has reached the maximum comments allowed
        'has_posts',			// looping method for posts
        'next_comment',			// retrieves next comment object from query
        'next_post',			// retrieves next post object from query
        'post_action_title', 	// outputs the description of an activity stream item
        'post_access',			// display the privacy icon for the post
        'post_age',				// display the current post's age
        'post_attachment',		// display add-on content related to post
        'post_actions',			// display post action options
        'post_link',			// display the permalink href= value for the post
        'post_options',			// display the post options drop-down
        'post_timestamp',		// display the timestamp for the post
        'report_reasons',		// display report reasons
        'show_comment',			// show comments associated with a post
        'show_like_count',		// display the number of likes for the post
        'show_post',			// output a single post
        'show_more_comments_link',// output the show more comments link
        'show_more_posts_link',	// output the show more posts link
        'show_recent_comments',	// output the last `n` comments
    );

    public function __construct()
    {
        add_filter('peepso_activity_post_content', array(&$this, 'activity_post_content'), 10, 1);
        add_filter('peepso_privacy_access_levels', array(&$this, 'privacy_access_levels'), 10, 1);
        add_filter('oembed_dataparse', array(&$this, 'set_media_properties'), 10, 2);
        // Run this last to give priority to addons
        add_filter('peepso_activity_get_post', array(&$this, 'activity_get_post'), 90, 4);

        add_action('peepso_activity_post_attachment', array(&$this, 'content_attach_media'), 20);
        add_action('peepso_activity_post_attachment', array(&$this, 'post_attach_repost'), 10, 1);
        add_action('peepso_activity_comment_attachment', array(&$this, 'content_attach_media'), 10);
        add_action('peepso_after_post_author', array(&$this, 'after_post_author'), 10, 1);
        add_action('peepso_activity_delete', array(&$this, 'delete_post_or_comment'));

        // add Vine to the list of allowed oEmbed providers
        // fallback for functions that go straight to oEmbed
        wp_oembed_add_provider(
            '#https?://vine\.co/v/([a-z0-9]+)\/?#i', // URL format to match
            'https://vine.co/oembed.{format}', // oEmbed provider URL
            TRUE                               // regex URL format
        );
    }

    /*
     * return singleton instance
     */
    public static function get_instance()
    {
        if (NULL === self::$_instance)
            self::$_instance = new self();
        return (self::$_instance);
    }

    /*
     * Sets the user id to be used for queries
     * @param int $user The user id that used for queries
     */
    public function set_user_id($user)
    {
        $this->user_id = $user;
    }

    /*
     * Sets the user id considered as the owner for queries
     * @param int $owner The user id of the owner to be used for queries
     */
    public function set_owner_id($owner)
    {
        $this->owner_id = $owner;
    }

    /*
     * returns the named property value from the current result set entry
     * @param string $prop The name of the property to retrieve
     */
    public function get_prop($prop)
    {
        if (isset($this->post_data[$prop]))
            return ($this->post_data[$prop]);
        return ('');
    }

    /*
     * add a post to an activity stream
     * @param int $owner id of owner - who's Wall to place the post on
     * @param int $author id of author making the post
     * @param string $content the contents of the Activity Stream Post
     * @param array $extra additional data used to create the post
     * @return mixed The post id on success, FALSE on failure
     */
    public function add_post($owner, $author, $content, $extra = array())
    {
        PeepSo::log(__METHOD__.'()');
        // check owner's permissions
        if (PeepSo::check_permissions($owner, PeepSo::PERM_POST, $author) === FALSE) {
            new PeepSoError(sprintf(__('User %1$d does not allow %2$d to write on their wall.', 'peepso'), $owner, $author));
            return (FALSE);
        }
//PeepSo::log('user has permission');

        // clean content
        // Cleaning here, because we cannot call htmlspecialchars while displaying the HTML since we'll be showing
        // some links, if there are any, on the post content.
        $content = htmlspecialchars($content);
        $content = substr(trim(PeepSoSecurity::strip_content($content)), 0, PeepSo::get_option('site_status_limit', 4000));

        $repost = NULL;

        if (!empty($extra['repost']) && $this->get_post($extra['repost']))
            $repost = $this->get_repost_root($extra['repost']);

        // don't do anything if contents are empty
        if (empty($content) && NULL === $repost && !apply_filters('peepso_activity_allow_empty_content', FALSE))
            return (FALSE);

        // create post
        $aPostData = array(
            'post_title'	=> "{$owner}-{$author}-" . time(),
            'post_excerpt'  => $content,
            'post_content'  => $content,
            'post_status'   => 'publish',
//			'post_date'		=> gmdate('Y-m-d H:i:s'), // date('Y-m-d H:i:s'),
//			'post_date_gmt' => date('Y-m-d H:i:s'), // gmdate('Y-m-d H:i:s'),
            'post_author'   => $author,
            'post_type'		=> PeepSoActivityStream::CPT_POST
        );
        $aPostData = apply_filters('peepso_pre_write_content', array_merge($aPostData, $extra), self::MODULE_ID, __FUNCTION__);
        $content = $aPostData['post_content'];
        PeepSo::log(' - post data=' . var_export($aPostData, TRUE));
        $id = wp_insert_post($aPostData);

        // add metadata to indicate whether or not to display link previews for this post
        add_post_meta($id, '_peepso_display_link_preview', (isset($extra['show_preview']) ? $extra['show_preview'] : 1), TRUE);

        // check $id for failure?
        if (0 === $id)
            return (FALSE);

        // add data to Activity Stream data table
        $aActData = array(
            'act_owner_id' => $owner,
            'act_module_id' => (isset($extra['module_id']) ? $extra['module_id'] : self::MODULE_ID),
            'act_external_id' => (isset($extra['external_id']) ? $extra['external_id'] : $id),
            'act_access' => (isset($extra['act_access']) ? $extra['act_access'] : PeepSo::get_user_access($author)),
            'act_ip' => PeepSo::get_ip_address(),
            'act_repost_id' => intval($repost),
        );
//PeepSo::log(__METHOD__.'() inserting: ' . var_export($aActData, TRUE));
//PeepSo::log('  extra: ' . var_export($extra, TRUE));

        $aActData = apply_filters('peepso_activity_insert_data', $aActData);

        global $wpdb;
        $res = $wpdb->insert($wpdb->prefix . self::TABLE_NAME, $aActData);

        /**
         * @param int The WP post ID
         * @param int The act_id
         */
        do_action('peepso_activity_after_add_post', $id, $wpdb->insert_id);

        PeepSo::log(__METHOD__.'() - before "the_content" filter: ' . $content);
        add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
        // TODO: let's run the filter on the content before adding it via the wp_insert_post() above.
        // this will remove the need for the wp_update_post().
        // Art: Post ID is required so ID is not yet ready if we need to run this filter before adding via wp_insert_post()
        $filtered_content = apply_filters('peepso_activity_post_content', $content, $id);

        remove_filter('oembed_result', array(&$this, 'oembed_result'));

        wp_update_post(array('ID' => $id, 'post_content' => $filtered_content));

        $this->save_peepso_media($id);

        $note = new PeepSoNotifications();
        // send owner an email
        if ($author !== $owner) {
            $user_owner = new PeepSoUser($owner);	// get_user_by('id', $owner_id);
            $user_author = new PeepSoUser($author);	// get_user_by('id', $author_id);
            $orig_post = get_post($id);

            $data = array(
                'permalink' => PeepSo::get_page('activity') . 'status/' . $orig_post->post_title,
                'post_content' => $orig_post->post_content,
            );
            $data = array_merge($data, $user_author->get_template_fields('from'), $user_owner->get_template_fields('user'));

            PeepSoMailQueue::add_message($owner, $data, __('Someone Posted on your Wall', 'peepso'), 'wall_post', 'wall_post', PeepSoActivity::MODULE_ID);

            $note->add_notification($author, $owner, __('Made a post', 'peepso'), 'wall_post', self::MODULE_ID, $id);
        }

        // Send original author a notification if content is shared
        if (NULL !== $repost) {
            $orig_post = $this->get_activity_post($repost);

            $user_owner = new PeepSoUser($owner);
            $user_author = new PeepSoUser($author);

            $data = array(
                'permalink' => PeepSo::get_page('activity') . 'status/' . $orig_post->post_title,
                'post_content' => $orig_post->post_content,
            );
            $data = array_merge($data, $user_author->get_template_fields('from'), $user_owner->get_template_fields('user'));
            PeepSoMailQueue::add_message($owner, $data, __('Someone shared your post', 'peepso'), 'share', 'share', PeepSoActivity::MODULE_ID);

            $note->add_notification($owner, $orig_post->post_author, __('Shared your post', 'peepso'), 'share', self::MODULE_ID, $id);
        }

        return ($id);
    }


    /*
     * adds a comment to the specified post_id
     * @param int $post_id The post id to add the comment to
     * @param int $author_id The user_id of the author adding the comment
     * @param string $content The contents of the commetn to add
     * @param array $extra optional extra information for the comment
     * @return int The post id if comment is successfully added or 0 if not successful
     */
    public function add_comment($post_id, $author_id, $content, $extra = array())
    {
        $module_id = (isset($extra['module_id']) ? $extra['module_id'] : self::MODULE_ID);

        if ($this->has_max_comments($post_id, $module_id))
            return (FALSE);

        $act_data = $this->get_activity_data($post_id, $module_id);

        if (NULL === $act_data)
            return (FALSE);

        $owner_id = intval($act_data->act_owner_id);

        // check owner's permissions
        if (FALSE === PeepSo::check_permissions($owner_id, PeepSo::PERM_COMMENT, $author_id)) {
            new PeepSoError(sprintf(__('User %1$d does not allow %2$d to write on their wall.', 'peepso'), $owner_id, $author_id));
            return (FALSE);
        }
//global $wpdb;
//PeepSo::log(__METHOD__."() query: {$wpdb->last_query}");
//PeepSo::log('  user has permission');

        $note = new PeepSoNotifications();

        // clean content
        $content = htmlspecialchars($content);
        $content = substr(PeepSoSecurity::strip_content($content), 0, PeepSo::get_option('site_status_limit', 4000));

        // create post
        $aPostData = array(
            'post_title'	=> "{$owner_id}-{$author_id}-" . time(),
            'post_content'  => $content,
            'post_excerpt'  => $content,
            'post_status'   => 'publish',
            'post_author'   => $author_id,
            'post_type'		=> PeepSoActivityStream::CPT_COMMENT
        );
        $aPostData = apply_filters('peepso_pre_write_content', array_merge($aPostData, $extra), self::MODULE_ID, __FUNCTION__);
//PeepSo::log('  post data: ' . var_export($aPostData, TRUE));
        $id = wp_insert_post($aPostData);

        // TODO: check wp_insert_post() reutrn value 0 == error, the rest of the code here will fail

        PeepSo::log("  added comment id #{$id}");

        // add data to Activity Stream data table
        $external_id = (isset($extra['external_id']) ? $extra['external_id'] : $id);
        $aActData = array(
            'act_owner_id' => $owner_id,
            'act_module_id' => self::MODULE_ID, // comments always belong to the activity module
            'act_external_id' => $external_id,
            'act_access' => (isset($extra['access']) ? $extra['access'] : PeepSo::get_user_access($author_id)),
            'act_ip' => PeepSo::get_ip_address(),
            'act_comment_object_id' => $act_data->act_external_id,
            'act_comment_module_id' => $module_id
        );

        global $wpdb;
//PeepSo::log('  act post data: ' . var_export($aActData, TRUE));
        $wpdb->insert($wpdb->prefix . self::TABLE_NAME, $aActData);

        add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
        $filtered_content = apply_filters('peepso_activity_post_content', $content, $id);
        remove_filter('oembed_result', array(&$this, 'oembed_result'));

        wp_update_post(array('ID' => $id, 'post_content' => $filtered_content));

        $this->save_peepso_media($id);

        // update the post to reflect
        $wpdb->update($wpdb->prefix . self::TABLE_NAME, array('act_has_replies' => 1), array('act_external_id' => $external_id, 'act_module_id' => $module_id));

        // send author an email
        if ($author_id !== $owner_id) {
            $user_owner = new PeepSoUser($owner_id);	// get_user_by('id', $owner_id);
            $user_author = new PeepSoUser($author_id);	// get_user_by('id', $author_id);
            $orig_post = $this->get_activity_post($act_data->act_id);

//			$data = array(
//				'email' => $user_owner->get_email(),
//				'ownername' => $user_owner->get_username(),
//				'authorname' => $user_author->get_fullname(),
//				'username' => $user_author->get_username(),
//				'post_title' => $orig_post->post_title
//			);
            $data = array(
                'permalink' => PeepSo::get_page('activity') . 'status/' . $orig_post->post_title,
                'post_content' => $orig_post->post_content,
            );
            $data = array_merge($data, $user_author->get_template_fields('from'), $user_owner->get_template_fields('user'));
//			PeepSoMailQueue::add($owner_id, $data, __('Someone Commented on Your Post', 'peepso'), 'usercomment');
            PeepSoMailQueue::add_message($owner_id, $data, __('Someone Commented on your Post', 'peepso'), 'user_comment', 'user_comment', PeepSoActivity::MODULE_ID);

            $note->add_notification($author_id, $owner_id, __('made a comment', 'peepso'), 'user_comment', self::MODULE_ID, $id);
        }

        $users = $this->get_comment_users($post_id, $module_id);

        while ($users->have_posts()) {
            $users->next_post();

            if (intval($users->post->post_author) !== $author_id && intval($users->post->post_author) !== $owner_id)
                $note->add_notification($author_id, $users->post->post_author, __('made a comment', 'peepso'), 'user_comment', self::MODULE_ID, $id);
        }

        return ($id);
    }

    /*
     * Saves a comment after editing
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function savecomment(PeepSoAjaxResponse $resp)
    {
        PeepSo::log(__METHOD__.'()');
        $input = new PeepSoInput();
        $post_id = $input->post_int('postid');
        $owner_id = $this->get_author_id($post_id);
        $user_id = $input->post_int('uid');
        $post = $input->post('post');

        // don't allow empty comments
        if (empty($post)) {
            $resp->success(0);
            $resp->notice(__('Comment is empty', 'peepso'));
            return;
        }

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $post = substr(PeepSoSecurity::strip_content($post), 0, PeepSo::get_option('site_status_limit', 4000));
            add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
            $filtered_content = apply_filters('peepso_activity_post_content', $post, $post_id);
            remove_filter('oembed_result', array(&$this, 'oembed_result'));

            $data = apply_filters('peepso_pre_write_content', array(
                'post_content' => $filtered_content,
                'post_excerpt' => $post,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', TRUE)
            ), self::MODULE_ID, __FUNCTION__);
            global $wpdb;
            $wpdb->update($wpdb->posts, $data, array('ID' => $post_id));
            $_post = $this->get_activity_data($post_id);

            if (empty($_post->act_repost_id))
                $this->save_peepso_media($post_id);

            $this->get_comment($post_id);
            $this->next_comment();

            $html = $this->content(NULL, FALSE);

            ob_start();
            $this->comment_attachment();
            $resp->success(1);
            $resp->set('html', $html);
            $resp->set('attachments', ob_get_clean());
        }
    }

    /**
     * Allows user to edit a comment.
     * @param PeepSoAjaxResponse $resp The AJAX Response instance.
     */
    public function editcomment(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $post_id = $input->post_int('postid');
        $user_id = $input->post_int('uid');
        $owner_id = intval($this->get_author_id($post_id));

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);
        $wpq = $this->get_comment($post_id);
        $this->next_comment();
        global $post;

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $data = array('cont' => $post->post_excerpt, 'post_id' => $post_id);
            $html = PeepSoTemplate::exec_template('activity', 'comment-edit', $data, TRUE);

            $resp->set('html', $html);
            $resp->success(1);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso'));
        }
    }

    /*
     * add to the peepso_activity_hide table to mark a post as hidden
     * @param int $user_id user doing the hiding
     * @param int $post_id post id to hide
     */
    /*	public function hide_post($user_id, $post_id)
        {
            $aData = array(
                'hide_activity_id' => $post_id,
                'hide_user_id' => $user_id
            );

            global $wpdb;
            $wpdb->insert($wpdb->prefix . self::HIDE_TABLE_NAME, $aData);
        } */


    /*
     * deletes a post and all associated hides, likes, and child comments/posts
     * @param int $post_id the post identifier
     */
    public function delete_post($post_id)
    {
        global $wpdb;

        // find all comments/child posts of the specified post id
        $sql = "SELECT `ID`
				FROM `{$wpdb->posts}`
				WHERE `post_parent`=%d AND `post_type`=%s";
        $all_posts = $wpdb->get_col($wpdb->prepare($sql, $post_id, PeepSoActivityStream::CPT_COMMENT));

        $all_posts = array();

        $post = $this->get_post($post_id);
        if ($post->have_posts()) {
            $post->the_post();
            $this->post_data = get_object_vars($post->post);

            if ($this->has_comments())
                while ($this->next_comment())
                    $all_posts[] = $this->comment_data['ID'];
        }
        // add the specified post_id to the list
        $all_posts[] = intval($post_id);

        // delete all of the posts in the list
        foreach ($all_posts as $postid) {
            $post_query = $this->get_post($postid);

            if (FALSE === $post_query->have_posts()) {
                $sql = "DELETE FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` WHERE `act_external_id`=%d AND `act_module_id`=%d";
                $wpdb->query($wpdb->prepare($sql, $postid, self::MODULE_ID));
            } else {
                $post = $post_query->post;

                $sql = "DELETE FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` WHERE (`act_external_id`=%d OR `act_repost_id`=%d) AND `act_module_id`=%d";
                $wpdb->query($wpdb->prepare($sql, $postid, $post->act_id, self::MODULE_ID));
            }

            $sql = "DELETE FROM `{$wpdb->prefix}peepso_activity_hide` WHERE `hide_activity_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, $postid));

            $sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoLike::TABLE . "` " .
                " WHERE `like_module_id`=%d AND `like_external_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, self::MODULE_ID, $postid));

            $sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoReport::TABLE . "` WHERE `rep_external_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, $postid));

            $sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoNotifications::TABLE . "` WHERE `not_external_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, $postid));

            // let any add-ons know about the delete
            do_action('peepso_delete_content', $postid);

            wp_delete_post($postid, TRUE);
        }

    }


    /*
     * get the the peepso_activities data associated with a given post
     * @param int $act_external_id the act_external id of the record to retrieve
     * @return Object The data record on sucess or FALSE if not found.
     */
    public function get_activity_data($act_external_id, $module_id = self::MODULE_ID)
    {
        global $wpdb;

        $sql = 'SELECT * ' .
            " FROM `{$wpdb->prefix}" . self::TABLE_NAME . '` ' .
            ' WHERE `act_external_id`=%d AND `act_module_id`=%d' .
            ' LIMIT 1 ';
        $ret = $wpdb->get_row($wpdb->prepare($sql, $act_external_id, $module_id));

        return ($ret);
    }

    /**
     * Return the WP_Post object that is related to an activity object, usually the parent post of a media object.
     * @param  int $act_id Activity's post Id to get
     * @return mixed Returns NULL if $act_id not found or no post; othewise returns WP_Post object
     */
    public function get_activity_post($act_id)
    {
        $act_data = $this->get_activity($act_id);

        // added check to get rid of "Trying to get property of non-object" errors
        if (!is_object($act_data))
            return (NULL);

        $id = apply_filters('peepso_activity_post_id', $act_data->act_external_id, $act_data);

        $this->owner_id = $this->user_id = PeepSo::get_user_id();

        $args = array(
            'p' => $id,
            'post_type' => apply_filters('peepso_activity_post_types', array(PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT)),
            '_bypass_permalink' => TRUE
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $post = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        if ($post->have_posts())
            return ($post->post);
        return (NULL);
    }

    /*
     * Get the the peepso_activities data associated by ID
     * @param int $act_id the act_id of the record to retrieve
     * @return Object The data record on sucess or NULL if not found.
     */
    public function get_activity($act_id)
    {
        // TODO: this appears to be called four times while outputting a single post to the Activity Stream. Let's try to reduce that!
        // Art: I checked this and it only called once per $act_id
        global $wpdb;

        $sql = 'SELECT * ' .
            " FROM `{$wpdb->prefix}" . self::TABLE_NAME . '` ' .
            ' WHERE `act_id`=%d LIMIT 1 ';
        $ret = $wpdb->get_row($wpdb->prepare($sql, $act_id));

        // TODO: this is failing sometimes, returnning NULL. The code that calls this needs to check the result and recover, otherwise it throws "Trying to get property of non-object" errors
        // TODO: note: this is failing when the requesting user is not the same as the posting user on a reposted item.
        return ($ret);
    }

    /*
     * Retrieve a single Activity Stream post by id
     * @param int $id The post id of the Activity Stream post to retrieve
     * @param int $owner_id The user_id of the owner of the post to retrieve
     * @param int $user_id The user_id of the user retrieving the post
     * @param bool $bypass_permalink Whether or not to bypass the permalink queries in filter_post_clauses,
     *                               useful if we want to call this function without worrying about the link.
     *
     * @return WP_Query instance of WP_Query that contains the post
     */
    public function get_post($id, $owner_id = NULL, $user_id = NULL, $bypass_permalink = FALSE)
    {
        $this->owner_id = $this->user_id = 0;

        if (NULL === $owner_id) {
            // use the current user's id
            $user = PeepSo::get_user_id();
        } else
            $user = $owner_id;
        $this->owner_id = $user;

        if (NULL === $user_id) {
            // use the current user'd id
            $user = PeepSo::get_user_id();
        } else
            $user = $user_id;
        $this->user_id = $user;

        $args = array(
            'p' => $id,
            'post_type' => PeepSoActivityStream::CPT_POST,
//			'posts_per_page' => 1,
            '_bypass_permalink' => $bypass_permalink
        );

        if (NULL !== $owner_id)
            $this->owner_id = $owner_id;

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $this->post_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

//PeepSo::log(__METHOD__.'() query: ' . $this->post_query->request);
//PeepSo::log('  query results: ' . var_export($this->post_query, TRUE));

        return ($this->post_query);
    }


    /*
     * Return the number of ActivityStream posts created by this user
     * @param int $user_id The user id to count posts from
     * @return int The number of posts by that user
     */
    public function get_posts_by_user($user_id)
    {
        global $wpdb;

        $sql = 'SELECT COUNT(*) AS `count` ' .
            " FROM `{$wpdb->posts}` " .
            ' WHERE `post_author`=%d AND `post_type`=%s AND `post_status`=\'publish\' ';
        $res = $wpdb->get_var($wpdb->prepare($sql, $user_id, PeepSoActivityStream::CPT_POST));
        return ($res);
    }


    /*
     * return a WP_Query instance for a user's activity stream posts
     * @param int $offset The number of posts to offset the query by
     * @param int $owner_ud The user_id of of the owner of the posts to be queried. If NULL will get posts from all users.
     * @param int $user_id The user_id value of the Activity Stream items to view
     * @param int $paged The page to display if opting to paginate
     * @return WP_Query instance of queried Activity Stream data
     */
    public function get_posts($offset = NULL, $owner_id = NULL, $user_id = NULL, $paged = NULL)
    {
        $this->owner_id = $this->user_id = 0;

        if (NULL === $owner_id) {
            // use the current user's id
            $user = PeepSo::get_user_id();
        } else
            $user = $owner_id;
        $this->owner_id = $user;

        if (NULL === $user_id) {
            // use the current user'd id
            $user = PeepSo::get_user_id();
        } else
            $user = $user_id;
        $this->user_id = $user;

        if (is_user_logged_in()) {
            $current_user = new PeepSoUser(PeepSo::get_user_id());
            $limit = $current_user->get_num_feeds_to_show();
        } else {
            $limit = intval(PeepSo::get_option('site_activity_posts'));
        }

        $args = array(
            'post_type' => $this->query_type = PeepSoActivityStream::CPT_POST,
            'order_by' => 'post_date_gmt',
            'order' => 'DESC',
            'posts_per_page' => $limit,
            'paged' => (NULL === $paged ? 0 : $paged),
            'offset' => (NULL === $offset ? 0 : $offset),
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $this->post_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);
//PeepSo::log(__METHOD__.'() found ' . $this->post_query->post_count . ' posts');
//PeepSo::log($this->post_query->request);

        return ($this->post_query);
    }


    /*
     * Retrieves a single Activity Stream comment by id
     * @param int $post_id The comment id of the Activity Stream comment to retrieve
     * @return WP_Query instance of WP_Query that contains the comment
     */
    public function get_comment($post_id)
    {
        $this->owner_id = $this->user_id = 0;

        $args = array(
            'p' => $post_id,
            'post_type' => PeepSoActivityStream::CPT_COMMENT,
//			'post_parent' => $post_id,
//			'posts_per_page' => 1,
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $this->comment_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        return ($this->comment_query);
    }


    /*
     * return comments for a given post_id
     * @param int $post_id The post id to find comments for
     * @param int $offset The number of posts to offset the query by
     * @param int $module_id The module ID to match the $post_id belongs to
     * @return WP_Query instance of the queried Activity Stream Comment data
     */
    public function get_comments($post_id, $offset = NULL, $paged = 1, $limit = NULL, $module_id = self::MODULE_ID)
    {
//		$this->owner_id = $this->user_id = 0;
        if (NULL === $limit)
            $limit = intval(PeepSo::get_option('site_activity_comments'));

        $args = array(
            'post_type' => $this->query_type = PeepSoActivityStream::CPT_COMMENT,
            'order_by' => 'post_date_gmt',
            'order' => 'ASC',
            'posts_per_page' => $limit,
            'offset' => (NULL === $offset ? 0 : $offset),
            '_comment_object_id' => $post_id,
            '_comment_module_id' => $module_id
        );

        if (0 !== $paged && is_int($paged))
            $args['paged'] = $paged;

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20, 2);
        $this->comment_query = new WP_Query($args);
//PeepSo::log(__METHOD__.'() query: ' . $this->comment_query->request);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20);

        return ($this->comment_query);
    }


    /*
     * Checks whether the comment query has any remaining posts
     * @return boolean TRUE if there are more comments in the query
     */
    public function has_comments()
    {
//global $post;
//PeepSo::log(__METHOD__.'() post->id=' . $post->id . ' / post_data[id]=' . $this->post_data['ID']);
        if (NULL === $this->comment_query)
            $this->get_comments(intval($this->post_data['ID']), NULL, 1, NULL, $this->post_data['act_module_id']);

        if ($this->comment_query->have_posts())
            return (TRUE);

        // Need to reset comment query when no posts are found to ensure that the next run
        // updates $this->comment_query
        $this->comment_query = NULL;
        return (FALSE);
    }


    /*
     * sets up the next post from the result set to be used with the templating system
     * @return Boolean TRUE on success with a valid post ready; FALSE otherwise
     */
    public function next_comment()
    {
        if ($this->comment_query->have_posts()) {
            if ($this->comment_query->current_post >= $this->comment_query->post_count)
                return (FALSE);

            $this->comment_query->the_post();
            $this->comment_data = get_object_vars($this->comment_query->post);
            return (TRUE);
        }
//PeepSo::log(__METHOD__.'() setting comment_query to NULL');
        $this->comment_query = NULL;
        return (FALSE);
    }

    /**
     * Show number of recent comments based on "site_activity_comments" setting
     * @return void
     */
    public function show_recent_comments()
    {
        add_filter('posts_clauses_request', array(&$this, 'filter_last_rows'), 10, 2);
        $this->get_comments($this->post_data['ID'], NULL, 1, NULL, $this->post_data['act_module_id']);
        add_filter('posts_clauses_request', array(&$this, 'filter_last_rows'), 10, 2);

        if ($this->comment_query->max_num_pages > 1)
            PeepSoTemplate::exec_template('activity', 'comment-header');

        // TODO: in get_comments() there is: 'order' => 'ASC' - can we change this to 'DESC' and remove the array_reverse() or the filter above??
        // Reverse the array so we get them in ASC order, skips out on building some SQL
        if (isset($this->comment_query->posts))
            $this->comment_query->posts = array_reverse($this->comment_query->posts);

        while ($this->next_comment())
            $this->show_comment();

        // reset comment_query
        $this->comment_query = NULL; // Reset because this only takes the latest comments, some functions may require all comments

        PeepSoTemplate::exec_template('activity', 'comment-footer');
    }

    /**
     * Get the last rows of a WP_Query filter first
     * @param array $clauses array holding the clauses for the SQL being built
     * @param WP_Query $query Query instance
     * @return array The modified array of clauses
     */
    public function filter_last_rows($clauses, $query)
    {
        global $wpdb;

        $clauses['orderby'] = "`{$wpdb->posts}`.`post_date` DESC";

        return ($clauses);
    }

    /*
     * Obtain remaining comments for display in Activity Stream
     * @param PeepSoAjaxResponse $resp The AJAX response objects
     * @output JSON encoded data of the remainin comments for the post
     */
    public function show_previous_comments(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $this->first_post_id = $input->get_int('first', NULL);

        $activity = $this->get_activity($input->get_int('act_id'));

        add_filter('peepso_user_profile_id', array(&$this, 'ajax_get_profile_id'));

        add_filter('posts_where', array(&$this, 'filter_before_id'));
        $this->get_comments($activity->act_external_id, NULL, 1, -1, $activity->act_module_id);
        remove_filter('posts_where', array(&$this, 'filter_before_id'));

        ob_start();

        while ($this->comment_query->have_posts()) {
            $this->next_comment();
            $this->show_comment();
        }

        $comments_html = ob_get_clean();

        $resp->success(1);
        $resp->set('html', $comments_html);
    }


    /*
     * Display the 'Show More Posts' link
     */
    public function show_more_posts_link()
    {
        $input = new PeepSoInput();
        $page = $input->get_int('page', 1);

        if ($this->has_posts() && $this->post_query->max_num_pages > 1 && $page < $this->post_query->max_num_pages) {
            echo '<div id="div-show-more-posts">';
            echo apply_filters(
                'peepso_activity_more_posts_link',
                sprintf('<button id="show-more-posts" onclick="activity.load_page(%d);" class="ps-button hidden-desktop visible-mobile">%s</button>', ++$page, __('Show More Posts', 'peepso'))
            );
            echo '<img class="hidden post-ajax-loader" src="', PeepSo::get_asset('images/ajax-loader.gif'), '" alt="" />';
            echo '</div>';
        }
    }


    /*
     * Display the 'Show All Comment' link
     */
    public function show_more_comments_link()
    {
        // this is only called when there are comments now; can remove has_comments() check
        if ($this->comment_query->max_num_pages > 1)
            echo __('Show All Comments', 'peepso');
        else if (PeepSo::get_option('site_activity_comments') <= $this->comment_query->post_count) {
            if ($this->comment_query->post_count > 1)
                echo sprintf(__('All %1$d comments displayed.', 'peepso'), $this->comment_query->post_count);
        }
    }


    /*
     * outputs the contents of a single comment
     */
    public function show_comment()
    {
//PeepSo::log(__METHOD__.'() - comment data: id=' . $this->comment_data['ID']); // var_export($this->comment_data, TRUE));
        PeepSoTemplate::exec_template('activity', 'comment', $this->comment_data);
    }

    /*
     * filter the SQL clauses; adding WHERE statements for comment ID
     * @param array $clauses array holding the clauses for the SQL being built
     * @param WP_Query $query Query instance
     * @return array The modified array of clauses
     */
    public function filter_post_clauses_comments($clauses, $query)
    {
        global $wpdb;

        if (!ps_isempty($query->query['_comment_object_id']) && !ps_isempty($query->query['_comment_module_id']))
            $clauses['where'] .= $wpdb->prepare(' AND `act`.`act_comment_object_id` = %d AND `act`.`act_comment_module_id` = %d', $query->query['_comment_object_id'], $query->query['_comment_module_id']);

        return ($clauses);
    }

    /**
     * Sets the JOIN for the activity table.
     * @param  string $join The JOIN clause
     * @return string
     */
    public function filter_act_join($join)
    {
        global $wpdb;

        $join .= " LEFT JOIN `{$wpdb->prefix}" . self::TABLE_NAME . "` `act` ON `act`.`act_external_id`=`{$wpdb->posts}`.`id` ";

        return ($join);
    }

    /*
     * filter the SQL clauses; adding our JOINs and other conditions
     * @param array $clauses array holding the clauses for the SQL being built
     * @param WP_Query $query Query instance
     * @return array The modified array of clauses
     */
    function filter_post_clauses($clauses, $query)
    {
        global $wpdb;
        // Add the default groupby clause anyway, to prevent duplicate records retrieved, one instance of this behavior is showing comments with the friends add-on enabled
        $clauses['groupby'] = "{$wpdb->posts}.`ID`";
        // determine if this is a "permalink" type request #57
        $is_permalink = FALSE;
        $as = PeepSoActivityShortcode::get_instance();
        if (NULL !== ($permalink = $as->get_permalink()) && PeepSoActivityStream::CPT_POST === $this->query_type)
            $is_permalink = TRUE;

        // add our columns to the query
        if (strpos(',', $clauses['fields']) === FALSE) {
            // SELECT wp_posts.*, act.*, author_id, author_name
            $clauses['fields'] .= ', `act`.*, `auth`.`ID` AS `author_id`, `auth`.`display_name` AS `author_name` ';
        }

        $owner = apply_filters('peepso_user_profile_id', 0);

        if (isset($query->query['_bypass_permalink']) && TRUE === $query->query['_bypass_permalink']) {
            $is_permalink = FALSE;
            $owner = 0;
        }

        // add JOIN clauses
        $clauses['join'] .= " LEFT JOIN `{$wpdb->users}` `auth` ON `auth`.`ID`=`{$wpdb->posts}`.`post_author` ";
        if ($this->user_id > 0) {
            if (!$is_permalink) // #57
                $clauses['join'] .= " LEFT JOIN `{$wpdb->prefix}" . self::HIDE_TABLE_NAME . "` `hide` ON " .
                    " `hide`.`hide_activity_id`=`act`.`act_id` AND `hide`.`hide_user_id`='{$this->user_id}' ";


            // and blocked / ignored users
            $clauses['join'] .= " LEFT JOIN `{$wpdb->prefix}" . self::BLOCK_TABLE_NAME  . "` `blk` ON " .
                " (`blk`.`blk_user_id` = `{$wpdb->posts}`.`post_author` AND `blk`.`blk_blocked_id`={$this->user_id}) " .
                " OR (`blk`.`blk_user_id` = {$this->user_id} AND `blk`.`blk_blocked_id`=`{$wpdb->posts}`.`post_author` ) ";
        }

        // if it's a permalink request *and* the CPT is the post (not comments!), adjust the WHERE clause
        if ($is_permalink) {
//			$permalink = $wpdb->escape($permalink);
            $clauses['where'] = $wpdb->prepare(" AND `{$wpdb->posts}`.`post_title`='%s' ", $permalink);
        } else {
            // adjust the WHERE clause
            if (FALSE === strpos('hide_activity_id', $clauses['where'])) {
                if ($owner > 0)
                    $clauses['where'] = " AND `act_owner_id`='{$owner}' " . $clauses['where'];

                if ($this->user_id > 0) {
                    $clauses['where'] .= ' AND `hide_activity_id` IS NULL ';

                    // exclude blocked users
                    $clauses['where'] .= ' AND `blk_blocked_id` IS NULL ';
                }

                // add checks for post's access
                if (is_user_logged_in()) {
                    // PRIVATE and owner by current user id  - OR -
                    // MEMBERS and user is logged in - OR -
                    // PUBLIC
                    $access = ' ((`act_access`=' . PeepSo::ACCESS_PRIVATE . ' AND `act_owner_id`=' . PeepSo::get_user_id() . ') OR ' .
                        ' (`act_access`=' . PeepSo::ACCESS_MEMBERS . ') OR (`act_access`<=' . PeepSo::ACCESS_PUBLIC . ') ';

                    // Hooked methods must wrap the string within a paranthesis
                    $access = apply_filters('peepso_activity_post_filter_access', $access);

                    $access .= ') ';
                } else {
                    // PUBLIC
                    $access = ' (`act_access`<=' . PeepSo::ACCESS_PUBLIC . ' ) ';
                }
                $clauses['where'] .= " AND {$access}";
            }
        }
//PeepSo::log(__METHOD__.'() query=' . var_export($query, TRUE));

        // add ORDER BY clause
        if (!isset($clauses['orderby']))
            $clauses['orderby'] = " `{$wpdb->posts}`.`post_date_gmt` DESC ";
//PeepSo::log(__METHOD__.'() clauses: ' . var_export($clauses, TRUE));

        return (apply_filters('peepso_activity_post_clauses', $clauses, $this->user_id));
    }

    /**
     * Filters for posts newer than $this->last_post_id
     * @param  string $where The WP_Query where clause
     * @return string
     */
    public function filter_since_id($where = '')
    {
        global $wpdb;

        if (NULL !== $this->last_post_id)
            $where .= $wpdb->prepare(" AND `{$wpdb->posts}`.`ID` > %d ", $this->last_post_id);

        return ($where);
    }

    /**
     * Filters for posts older than $this->first_post_id
     * @param  string $where The WP_Query where clause
     * @return string
     */
    public function filter_before_id($where = '')
    {
        global $wpdb;

        if (NULL !== $this->first_post_id)
            $where .= $wpdb->prepare(" AND `{$wpdb->posts}`.`ID` < %d ", $this->first_post_id);

        return ($where);
    }

    /*
     * Get the owner of a specific post
     * @param int $post_id The id of the post
     * @return int The user id of the owner of that post
     */
    private function get_post_owner($post_id)
    {
        global $wpdb;
        $sql = "SELECT `act_owner_id` FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` " .
            " WHERE `act_id`=%d LIMIT 1";
        $owner = intval($wpdb->get_var($wpdb->prepare($sql, $post_id)));
        PeepSo::log(__METHOD__."() owner of post #{$post_id} is {$owner}");
        return ($owner);
    }


    /**
     * Get an activity stream item's owner ID
     * @param  int $post_id The post ID.
     * @return int The WP user ID.
     */
    public function get_owner_id($post_id, $module_id = self::MODULE_ID)
    {
        global $wpdb;

        $sql = "SELECT `act_owner_id` FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` " .
            ' WHERE `act_external_id`=%d AND `act_module_id` = %d';
        $owner = intval($wpdb->get_var($wpdb->prepare($sql, $post_id, $module_id)));

        return ($owner);
    }


    /**
     * Return the original author of a post.
     * @param  int $post_id The post ID to get the author from.
     * @return int User id of the author of the content
     */
    public function get_author_id($post_id)
    {
        global $wpdb;

        $sql = "SELECT `post_author` FROM `{$wpdb->posts}` " .
            ' WHERE `ID`=%d ';
        $owner = intval($wpdb->get_var($wpdb->prepare($sql, $post_id)));
        return ($owner);
    }

    //
    // the following are the AJAX callbacks
    //

    public function like(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->post_int('act_id');
        $user_id = $input->post_int('uid');
//PeepSo::log(__METHOD__.'() user=' . $user_id . ' post=' . $post_id);
        if ($user_id != PeepSo::get_user_id()) {
            $resp->error(__('Invalid User id', 'peepso'));
            return;
        }

        $activity = $this->get_activity($act_id);
        $module_id = $activity->act_module_id;

        $act_post = $this->get_activity_post($act_id);
        $post_id = $act_post->ID;
        $owner_id = $this->get_author_id($post_id);

        $fSuccess = FALSE;
        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_LIKE, $user_id)) {
            $like = new PeepSoLike();
            $add_like = (FALSE === $like->user_liked($activity->act_external_id, $module_id, $user_id));
            if ($add_like)
                $res = $like->add_like($activity->act_external_id, $module_id, $user_id);
            else
                $res = $like->remove_like($activity->act_external_id, $module_id, $user_id);

            if (FALSE !== $res)
                $fSuccess = TRUE;
        }

        $resp->success($fSuccess);
        if ($fSuccess) {
            $count = $like->get_like_count($activity->act_external_id, $module_id);
            $resp->set('count', $count);
            ob_start();
            $this->show_like_count($count, $act_id);
            $resp->set('count_html', ob_get_clean());

            ob_start();
            $like = $this->get_like_status($activity->act_external_id, $module_id);

            $acts = array(
                'like' => array(
                    'href' => '#like',
                    'label' => $like['label'],
                    'class' => 'actaction-like',
                    'icon' => $like['icon'],
                    'click' => 'return activity.action_like(this, ' . $activity->act_id . ');',
                    'count' => $like['count'],
                ),
            );

            $this->_display_post_actions($post_id, $acts);

            $resp->set('like_html', ob_get_clean());

            // send owner an email
            if ($user_id !== $owner_id && $add_like) {
                $user_owner = new PeepSoUser($owner_id);
                $user = new PeepSoUser($user_id);
                $orig_post = get_post($post_id);

                $data = array(
                    'permalink' => PeepSo::get_page('activity') . 'status/' . $orig_post->post_title,
                    'post_content' => $orig_post->post_content,
                );
                $data = array_merge($data, $user->get_template_fields('from'), $user_owner->get_template_fields('user'));

                $post_type = get_post_type($post_id);
                $post_type_object = get_post_type_object($post_type);
                PeepSo::log(__METHOD__.'() template data: ' . var_export($data, TRUE));
//				PeepSoMailQueue::add($owner_id, $data, __('Someone Liked your post', 'peepso'), 'likepost');
                PeepSoMailQueue::add_message($owner_id, $data, sprintf(__('Someone Liked your %s', 'peepso'), $post_type_object->labels->activity_type), 'like_post', 'like_post', PeepSoActivity::MODULE_ID);

                $note = new PeepSoNotifications();
                $notification_message = sprintf(__('Likes your %s', 'peepso'), $post_type_object->labels->activity_type);

                $note->add_notification($user_id, $owner_id, $notification_message, 'like_post', $module_id, $post_id);
            }
        } else {
            $resp->error(__('Unable to process', 'peepso'));
        }
    }

    /*
     * Allows user to edit a post
     */
    public function editpost(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $post_id = $input->post_int('postid');
        $user_id = $input->post_int('uid');
        $owner_id = intval($this->get_author_id($post_id));

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);
        $wpq = $this->get_post($post_id, $user_id);
        $this->next_post();

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            global $post;
            $data = array('cont' => $post->post_excerpt, 'post_id' => $post_id, 'act_id' => $post->act_id);
            $data = apply_filters('peepso_activity_post_edit', $data);
            $html = PeepSoTemplate::exec_template('activity', 'post-edit', $data, TRUE);

            $resp->set('html', $html);
            $resp->set('act_id', $post->act_id);
            $resp->success(1);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso'));
        }
    }

    /*
     * Allows user to edit a post
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function edit_description(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->post_int('act_id', NULL);
        $user_id = $input->post_int('uid');
        $type = $input->post('type', NULL);
        $act_external_id = $input->post_int('object_id', NULL);

        if (NULL === $act_id) {
            $resp->success(FALSE);
            $resp->error(__('Post not found.', 'peepso'));
            return;
        }

        $activity = $this->get_activity($act_id);
        $owner_id = intval($activity->act_owner_id);

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $cont = $activity->act_description;
            $objects = apply_filters('peepso_get_object_' . $type, array(), $act_external_id);

            // object caption should be the post content in case of single object
            if ( 1 === count($objects)) {
                $object = array_pop($objects);
                $cont = $object['post']->post_excerpt;
            }

            $data = array('cont' => $cont, 'act_id' => $act_id, 'type' => $type, 'act_external_id' => $act_external_id);
            $html = PeepSoTemplate::exec_template('activity', 'description-edit', $data, TRUE);

            $resp->set('html', $html);
            $resp->set('act_id', $activity->act_id);
            $resp->success(1);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso'));
        }
    }

    /**
     * AJAX callback
     * Saves the description by using a custom query that allows NULL values
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function save_description(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->post_int('act_id', NULL);
        $description = $input->post('description', NULL);
        $user_id = $input->post_int('uid');
        $type = $input->post('type', NULL);
        $act_external_id = $input->post_int('object_id', NULL);
        $success = FALSE;

        if (NULL === $act_id) {
            $resp->success(FALSE);
            $resp->error(__('Post not found.', 'peepso'));
            return;
        }

        $activity = $this->get_activity($act_id);
        $owner_id = intval($activity->act_owner_id);

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $objects = apply_filters('peepso_get_object_' . $type, array(), $act_external_id);

            // object caption should be the post content in case of single object
            if (1 === count($objects)) {
                $object = array_pop($objects);
                $post_id = $object['post']->ID;

                $description = substr(PeepSoSecurity::strip_content($description), 0, PeepSo::get_option('site_status_limit', 4000));
                add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
                $filtered_content = apply_filters('peepso_activity_post_content', $description, $post_id);
                remove_filter('oembed_result', array(&$this, 'oembed_result'));

                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $filtered_content,
                    'post_excerpt' => $description
                ), true);

                if (is_wp_error($post_id)) {
                    $resp->error(__('Could not update the description.', 'peepso'));
                } else {
                    $success = TRUE;

                    $activity = PeepSoActivity::get_instance();
                    $object['post']->post_content = $filtered_content;
                    $description = $activity->content($object['post'], FALSE);
                    $resp->set('html', $description);
                }

            } else {

                global $wpdb;

                // Use custom query to update row, accepts NULL
                $query = "UPDATE `{$wpdb->prefix}" . self::TABLE_NAME . "` SET `act_description`=%s";

                if (empty($description))
                    $query = sprintf($query, 'NULL');
                else
                    $query = $wpdb->prepare($query, $description);

                $query .= $wpdb->prepare(' WHERE `act_id`=%d', $act_id);

                $success = $wpdb->query($query);
                $resp->success($success);

                $description = do_shortcode($description);
            }

            $resp->success($success);
            if (FALSE === $success)
                $resp->error(__('Could not update the description.', 'peepso'));
            else
                $resp->set('html', $description);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso'));
        }
    }

    /*
     * Saves a post after editing
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function savepost(PeepSoAjaxResponse $resp)
    {
        PeepSo::log(__METHOD__.'()');
        $input = new PeepSoInput();
        $act_id = $input->post_int('act_id');
        $act_post = $this->get_activity_post($act_id);
        $post_id = $act_post->ID;
        $owner_id = $this->get_author_id($post_id);
        $user_id = $input->post_int('uid');
        $post = $input->post('post');

        // don't do anything if contents are empty
        if (empty($post) && !apply_filters('peepso_activity_allow_empty_content', FALSE)) {
            $resp->success(FALSE);
            $resp->error(__('Post is empty', 'peepso'));
        } else if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $post = substr(PeepSoSecurity::strip_content($post), 0, PeepSo::get_option('site_status_limit', 4000));
            add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
            $filtered_content = apply_filters('peepso_activity_post_content', $post, $post_id);
            remove_filter('oembed_result', array(&$this, 'oembed_result'));

            $data = apply_filters('peepso_pre_write_content', array(
                'post_content' => $filtered_content,
                'post_excerpt' => $post,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', TRUE)
            ), self::MODULE_ID, __FUNCTION__);
            global $wpdb;
            $wpdb->update($wpdb->posts, $data, array('ID' => $post_id));
            $_post = $this->get_activity_data($post_id);

            if (empty($_post->act_repost_id))
                $this->save_peepso_media($post_id);
            do_action('peepso_activity_after_save_post', $post_id);

            $note = new PeepSoNotifications();
            $users = $this->get_comment_users($post_id, $act_post->act_module_id);

            while ($users->have_posts()) {
                $users->next_post();

                if (intval($users->post->post_author) !== $owner_id)
                    $note->add_notification($owner_id, $users->post->post_author, __('Updated a post', 'peepso'), 'wall_post', self::MODULE_ID, $post_id);
            }

            $this->get_post($post_id, $owner_id, 0);
            $this->next_post();

            $html = $this->content(NULL, FALSE);

            ob_start();
            $this->post_attachment();
            $resp->success(1);
            $resp->set('html', $html);
            $resp->set('attachments', ob_get_clean());
        }
    }


    /*
     * Add a post_id to a user's list of hidden posts
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function hidepost(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->post_int('act_id');
        $act = $this->get_activity($act_id);
        $owner_id = $act->act_owner_id;
        $user_id = $input->post_int('uid');

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST, $user_id)) {
            $hide = new PeepSoActivityHide();
            $hide->hide_post_from_user($act_id, $user_id);
            $resp->success(1);
        }
    }


    /*
     * Add a user to user's list of blocked users
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function blockuser(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $user_id = $input->post_int('uid');
        $block_id = $input->post_int('user_id');

        // don't allow users to block themselves
        if ($user_id === $block_id) {
            $resp->success(0);
            return;
        }

        if (PeepSo::check_permissions($user_id, PeepSo::PERM_POST, $user_id)) {
            $block = new PeepSoBlockUsers();
            $res = $block->block_user_from_user($block_id, $user_id);
            if ($res) {
                $resp->success(1);
                $profile = PeepSoProfile::get_instance();
                $profile->set_user_id($block_id);

                ob_start();
                $profile->profile_actions();
                $actions = ob_get_clean();

                $user = new PeepSoUser($block_id);
                $resp->set('header', __('Notice', 'peepso'));
                $resp->set('message', sprintf(__('The user %1$s has been blocked', 'peepso'), $user->get_display_name()));
                $resp->set('actions', $actions);
            }
        }
        else PeepSo::log(__METHOD__.'() unable to add user #' . $block_id . ' to user #' . $user_id . "'s block list");
    }


    /*
     * Remove a user from user's list of blocked users
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function unblockuser(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $user_id = $input->post_int('uid');
        $block_id = $input->post_int('user_id');

        // don't allow users to block themselves
        if ($user_id === $block_id) {
            $resp->success(0);
            return;
        }

        if (PeepSo::check_permissions($user_id, PeepSo::PERM_POST, $user_id)) {
            $block = new PeepSoBlockUsers();
            $res = $block->delete_by_id(array($block_id), $user_id);
            if ($res) {
                $resp->success(1);
                $profile = PeepSoProfile::get_instance();
                $profile->set_user_id($block_id);

                ob_start();
                $profile->profile_actions();
                $actions = ob_get_clean();

                $user = new PeepSoUser($block_id);
                $resp->set('header', __('Notice', 'peepso'));
                $resp->set('message', sprintf(__('The user %s has been unblocked', 'peepso'), $user->get_display_name()));
                $resp->set('actions', $actions);
            }
        }
        else PeepSo::log(__METHOD__.'() unable to remove user #' . $block_id . ' from user #' . $user_id . "'s block list");
    }


    /*
     * Called from AJAX handler to delete a post/comment
     * @param AjaxResponse $resp The AJAX response
     */
    public function delete(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $post_id = $input->post_int('postid');
        $user_id = $input->post_int('uid');

        $args = array(
            'p' => $post_id,
            'post_type' => array(PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT)
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $post_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        $post = $post_query->post;
        // verify it's the current user AND they have ownership of the item
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, $user_id) ||
            PeepSo::check_permissions(intval($post->act_owner_id), PeepSo::PERM_POST_DELETE, $user_id)) {
            $this->delete_post($post_id);
            $resp->set('act_id', $post->act_id);
            $resp->success(TRUE);
        } else {
            $resp->success(FALSE);
            $resp->error(__('You do not have permission to do that.', 'peepso'));
        }
    }

    /**
     * `peepso_activity_delete` callback
     * Deletes a post or comment based on activity
     * @param  array $activity
     */
    public function delete_post_or_comment($activity)
    {
        if (self::MODULE_ID === intval($activity->act_module_id))
            if (PeepSo::check_permissions($this->get_author_id($activity->act_external_id), PeepSo::PERM_POST_DELETE, PeepSo::get_user_id()))
                $this->delete_post($activity->act_external_id);
    }

    /**
     * Calls delete_activity via ajax
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function ajax_delete_activity(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();

        if (wp_verify_nonce($input->post('_wpnonce'), 'activity-delete' )) {
            $act_id = $input->post_int('act_id', NULL);
            $activity = $this->get_activity($act_id);

            // Allows other addons to send additional data.
            do_action('peepso_before_ajax_delete_activity', $resp, $activity);

            $delete = $this->delete_activity($act_id);

            do_action('peepso_after_after_delete_activity', $resp, $activity, $delete);

            if (is_wp_error($delete)) {
                $resp->success(FALSE);
                $resp->error($delete->get_error_message());
            } else {
                $resp->set('module_id', $activity->act_module_id);
                $resp->success(TRUE);
            }
        } else {
            $resp->success(FALSE);
            $resp->error(__('Could not verify nonce.', 'peepso'));
        }
    }

    /**
     * Deletes an activity and calls the `peepso_activity_delete` action
     * @param  int $act_id The activity to delete
     * @return bolean
     */
    public function delete_activity($act_id)
    {
        $activity = $this->get_activity($act_id);

        if (FALSE === PeepSo::check_permissions(intval($activity->act_owner_id), PeepSo::PERM_POST_DELETE, PeepSo::get_user_id()))
            return (new WP_Error('no_access', __('You do not have permission to do that.', 'peepso')));
        else {
            global $wpdb;

            do_action('peepso_activity_delete', $activity);
            $wpdb->delete($wpdb->prefix . self::TABLE_NAME, array('act_id' => $act_id));

            return (TRUE);
        }
    }

    /*
     * Report a post as inappropriate content
     * @param PeepSoAjaxResponse $resp The AJAX response object
     */
    public function report(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->get_int('act_id');
        $act_data = $this->get_activity($act_id);
        $post_id = $act_data->act_external_id;
        $user_id = $input->get_int('uid');

        if (PeepSo::check_permissions($this->get_post_owner($act_id), PeepSo::PERM_REPORT, $user_id)) {
            $reason = $input->get('reason');
            $rep = new PeepSoReport();

            if (!$rep->is_reported($post_id, $user_id, $act_data->act_module_id))
                $rep->add_report($post_id, $user_id, $act_data->act_module_id, $reason);

            $resp->success(TRUE);
            $resp->notice(__('This item has been reported', 'peepso'));
        } else {
            $resp->success(FALSE);
            $resp->error(__('You do not have permission to do that.', 'peepso'));
        }
    }

    /*
     * Writes a comment on a post
     * @param PeepSoAjaxResponse $resp The AJAX response object
     */
    public function makecomment(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $content = $input->post_raw('content');

        // don't allow empty comments
        if (empty($content)) {
            $resp->success(FALSE);
            $resp->notice(__('Comment is empty', 'peepso'));
            return;
        }

        $act_id = $input->post_int('act_id');
        $activity = $this->get_activity($act_id);

        if (NULL === $activity) {
            $resp->success(FALSE);
            $resp->notice(__('Activity not found', 'peepso'));
            return;
        }

        $module_id = $activity->act_module_id;
        $user_id = $input->post_int('uid');
        $owner_id = $activity->act_owner_id;
        $post_id = $activity->act_external_id;

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_COMMENT, $user_id)) {
            $args = array(
                'content' => $content,
                'user_id' => $user_id,
                'target_user_id' => $owner_id,
//				'type' => $type,
                'written' => 0,
            );

            $extra = array('module_id' => $module_id);

            $id = $this->add_comment($post_id, $user_id, $content, $extra);
            $resp->set('has_max_comments', $max_comments = $this->has_max_comments($post_id, $module_id));

            if (FALSE !== $id) {
                $args['written'] = 1;
                $args['post_id'] = $id;
            }

            if (isset($args['written']) && 1 === $args['written']) {
                $resp->success(TRUE);

                $this->set_user_id($user_id);
                $this->set_owner_id($owner_id);

                $this->last_post_id = $input->post_int('last', NULL);
                add_filter('posts_where', array(&$this, 'filter_since_id'));
                $wpq = $this->get_comments($post_id, NULL, 1, NULL, $module_id);
                remove_filter('posts_where' , array(&$this, 'filter_since_id'));

                if ($this->has_comments()) { // ($wpq->have_posts()) {
                    ob_start();
                    while ($this->next_comment())
                        $this->show_comment();

                    $comment_data = ob_get_clean();

                    $resp->set('html', $comment_data);
                }
            } else {
                $resp->success(FALSE);
                if ($max_comments)
                    $resp->error(__('The comment limit for this post has been reached.', 'peepso'));
                else
                    $resp->error(__('Error in writing Activity Stream comment.', 'peepso'));
            }
        } else {
            $resp->success(FALSE);
            $resp->error(__('You don\'t have permissions for that ', 'peepso') . $user_id . '/' . $owner_id);
        }
    }

    //
    // the following are the Activity Stream template tag methods
    //

    /*
     * Output post action options for the post
     */
    public function comment_actions()
    {
        global $post;

        $logged_in = is_user_logged_in();		// we're using this a lot, save function overhead

        $like = $this->get_like_status($post->act_external_id, $post->act_module_id);

        $acts = array(
            'like' => array(
                'href' => '#like',
                'label' => $like['label'],
                'class' => 'actaction-like',
                'icon' => $like['icon'],
                'click' => 'activity.comment_action_like(this, ' . $post->act_id . '); return false;',
                'count' => $like['count'],
            ),
            'report' => array(
                'href' => '#report',
                'label' => __('Report', 'peepso'),
                'class' => 'actaction-report',
                'icon' => 'warning-sign',
                'click' => 'activity.comment_action_report(' . $post->act_id . '); return false;',
            ),
        );

        // if it's the post author or an admin - add edit  action
        if (PeepSo::get_user_id() === intval($post->author_id) || PeepSo::is_admin())
            $acts['edit'] = array(
                'href' => '#edit',
                'label' => 'Edit',
                'class' => 'actaction-edit',
                'icon' => 'pencil',
                'click' => 'activity.comment_action_edit(' . $post->ID . ', this); return false;',
            );


        // if it's the post author, owner or an admin - add delete action
        if (PeepSo::get_user_id() === intval($post->author_id) || PeepSo::get_user_id() === intval($post->act_owner_id) || PeepSo::is_admin())
            $acts['delete'] = array(
                'href' => '#delete',
                'label' => 'Delete',
                'class' => 'actaction-delete',
                'icon' => 'trash',
                'click' => 'activity.comment_action_delete(' . $post->ID . '); return false;',
            );

        if (! PeepSo::get_option('site_reporting_enable', TRUE) || // global config
            (!is_user_logged_in() && !PeepSo::get_option('site_reporting_allowguest', FALSE)) ||
            PeepSo::get_user_id() === intval($post->author_id))	// own content
            unset($acts['report']);

        if (! PeepSo::get_option('site_socialsharing_enable', TRUE))
            unset($acts['share']);

        if (! PeepSo::check_permissions($post->post_author, PeepSo::PERM_POST_LIKE, PeepSo::get_user_id()))
            unset($acts['like']);

        $acts = apply_filters('peeps_activity_comment_actions', $acts);

        // if no actions, exit
        if (0 === count($acts))
            return;

        echo '<nav class="ps-stream-status-action ps-stream-status-action">', PHP_EOL;
        foreach ($acts as $name => $act) {
            echo '<a data-stream-id="', $post->ID, '" ';
            if (isset($act['click']) && $logged_in)
                echo ' onclick="', $act['click'], '" ';
            else
                echo ' onclick="return false;" ';
            if (isset($act['title']) && $logged_in)
                echo ' title="', $act['title'], '" ';
            else if (!$logged_in)
                echo ' title="', __('Please register or log in to perform this action', 'peepso'), '" ';
            echo ' href="', ($logged_in ? $act['href'] : '#'), '" ';
            echo ' class="', $act['class'], ' ps-icon-', $act['icon'], '">';
            echo '<span>',$act['label'],'</span>';
            echo '</a>', PHP_EOL;
        }
        echo '</nav>', PHP_EOL;
    }


    /**
     * Allow add-on to attach content to a comment
     */
    public function comment_attachment()
    {
        global $post;

        // let other add-ons have a chance to attach content to the comment
        do_action('peepso_activity_comment_attachment', $post, $post->ID, $post->act_module_id);
    }


    /**
     * Outputs the_content for the current post
     */
    public function content($post = NULL, $echo = TRUE)
    {
        if (is_null($post))
            global $post;

        $content = apply_filters(
            'peepso_activity_content',
            apply_filters('the_content', $post->post_content),
            $post
        );

        $attachments = $this->get_content_attachments($post);

        if ( count($attachments) >= 1 ) {
            $content = rtrim($content);

            if ('</p>' === ($markup = substr($content, -4))) {
                $content = substr($content, 0, -4);
            } else {
                $markup = '';
            }

            $content .= ' ' . $this->format_content_attachments($attachments) . $markup;
        }

        if ($echo)
            echo $content;
        else
            return ($content);
    }

    /**
     * Get list of attachments for particular post
     * @param WP_Post The current post object
     * @return array List of attachment contents
     */
    public function get_content_attachments($post)
    {
        $args = array(
            'post_id' => $post->ID,
            'attachments' => array()
        );

        $args = apply_filters('peepso_activity_content_attachments', $args);

        return $args['attachments'];
    }

    /**
     * Format post attachments
     * @param array List of post attachments
     * @return string Formatted post attachments
     */
    public function format_content_attachments($attachments)
    {
        $content = '';

        if ( count($attachments) >= 1 ) {
            $glue = ' ' . __('and', 'peepso') . ' ';
            $content .= '&mdash; ' . implode($glue, $attachments);
        }

        return ($content);
    }

    /**
     * Displays class used as attribute in any HTML element
     * @param string $class Default class name
     * @param boolean $return_raw If set to TRUE it returns string otherwise it prints the modified class name
     */
    public function content_media_class($class)
    {
        $class = apply_filters('peepso_activity_content_media_class', $class);
        echo $class;
    }

    /**
     * Displays the embeded media on the post or comment.
     * - peepso_activity_post_attachment
     * - peepso_activity_comment_attachment
     * @param WP_Post The current post object
     */
    public function content_attach_media($post)
    {
        $show_preview = get_post_meta($post->ID, '_peepso_display_link_preview', TRUE);

        if ('0' === $show_preview)
            return;

        $peepso_media = get_post_meta($post->ID, 'peepso_media');

        if (empty($peepso_media))
            return;

        $peepso_media = apply_filters('peepso_content_media', $peepso_media, $post);
        $new_tabs = PeepSo::get_option('site_activity_open_links_in_new_tab', 1);
        foreach ($peepso_media as $media) {
            if (!isset($media['url']) || !isset($media['description']))
                continue;
            $media['target'] = '';
            if ($new_tabs)
                $media['target'] = 'target="_blank"';

            $media['host'] = parse_url($media['url'], PHP_URL_HOST);

            PeepSoTemplate::exec_template('activity', 'content-media', $media);
        }
    }

    /*
     * loads the poststatus template
     */
    public function post_status()
    {
        PeepSoTemplate::exec_template('activity', 'poststatus');

        wp_enqueue_script('peepso-activitystream');
    }


    /*
     * checks whether the query has any remaining posts
     * returns only the first page
     * @return boolean TRUE if there are more posts in the query
     */
    public function has_posts()
    {
        if (NULL === $this->post_query) {
            if (PeepSo::get_option('site_activity_hide_stream_from_guest', 0) && FALSE === is_user_logged_in())
                return (0);

            $owner = apply_filters('peepso_user_profile_id', 0);
            $user = PeepSo::get_user_id();
            $this->get_posts(0, $owner, $user, 1);
        }
        return ($this->post_query->have_posts());
    }


    /*
     * sets up the next post from the result set to be used with the templating system
     * @return Boolean TRUE on success with a valid post ready; FALSE otherwise
     */
    public function next_post()
    {
        if ($this->post_query->have_posts()) {
            if ($this->post_query->current_post >= $this->post_query->post_count)
                return (FALSE);

            $this->post_query->the_post();
            $this->post_data = get_object_vars($this->post_query->post);
            return (TRUE);
        }
        return (FALSE);
    }

    /* display post age
     */
    public function post_age()
    {
        $post_date = get_the_date('U');
        $curr_date = date('U', current_time('timestamp', 0));

        echo '<span title="', esc_attr(get_the_date() . ' ' . get_the_time()), '">', PeepSoTemplate::time_elapsed($post_date, $curr_date), '</span>';
    }


    /*
     * Output post action options for the post
     */
    public function post_actions()
    {
        global $post;

        $like = $this->get_like_status($post->act_external_id, $post->act_module_id);
        $logged_in = is_user_logged_in();		// we're using this a lot, save function overhead

        $acts = array(
            'like' => array(
                'href' => '#like',
                'label' => $like['label'],
                'class' => 'actaction-like',
                'icon' => $like['icon'],
                'click' => 'return activity.action_like(this, ' . $post->act_id . ');',
                'count' => $like['count'],
            ),
            'repost' => array(
                'href' => '#repost',
                'label' => __('RePost', 'peepso'),
                'class' => 'actaction-share',
                'icon' => 'forward',
                'click' => 'return activity.action_repost(' . $post->act_id . ');'
            ),
            'report' => array(
                'href' => '#report',
                'label' => __('Report', 'peepso'),
                'class' => 'actaction-report',
                'icon' => 'warning-sign',
                'click' => 'return activity.action_report(' . $post->act_id . ');',
            ),
        );

        if (! PeepSo::get_option('site_reporting_enable', TRUE) || !$logged_in || intval($post->post_author) === PeepSo::get_user_id())
            unset($acts['report']);

        if (! PeepSo::get_option('site_socialsharing_enable', TRUE) ||
            !$logged_in || intval($post->post_author) === PeepSo::get_user_id())
            unset($acts['repost']);

        if (! PeepSo::check_permissions($post->post_author, PeepSo::PERM_POST_LIKE, PeepSo::get_user_id()))
            unset($acts['like']);

        $options = apply_filters('peepso_activity_post_actions', array('acts'=>$acts,'post'=>$post));
        $acts = $options['acts'];

        if (0 === count($acts)) {
            // if no options, exit
            return;
        }

        echo '<nav class="ps-stream-status-action ps-stream-status-action">', PHP_EOL;
        $this->_display_post_actions($post->act_id, $acts);
        echo '</nav>', PHP_EOL;
    }


    /**
     * Echo the html for activity feed actions.
     * @param  int $post_id The post ID.
     * @param  array $acts  The list of actions with labels and click methods.
     */
    private function _display_post_actions($post_id, $acts)
    {
        $logged_in = is_user_logged_in();		// we're using this a lot, save function overhead
        foreach ($acts as $name => $act) {
            echo '<a data-stream-id="', $post_id, '" ';
            if (isset($act['click']) && $logged_in)
                echo ' onclick="', $act['click'], '" ';
            else
                echo ' onclick="return false;" ';
            if (isset($act['title']) && $logged_in)
                echo ' title="', $act['title'], '" ';
            else if (!$logged_in)
                echo ' title="', __('Please register or log in to perform this action', 'peepso'), '" ';
            echo ' href="', ($logged_in ? $act['href'] : '#'), '" ';
            echo ' class="', (isset($act['class']) ? $act['class'] : ''), ' ps-icon-', $act['icon'], '">';
            echo '<span>',$act['label'],'</span>';
            echo '</a>', PHP_EOL;
        }
    }


    /*
     * Creates a drop-down menu of post options, user and context appropriate
     */
    public function post_options()
    {
        global $post;
        if (!is_user_logged_in() ||
            ($post->act_owner_id === PeepSo::get_user_id() || $post->post_author === PeepSo::get_user_id()) )
            return;

        // current user is post owner

        $user_id = PeepSo::get_user_id();

        $options = array();

        if (intval($post->post_author) !== $user_id) {
            // only add this if it's not the current user
            $options['hide'] = array(
                'label' => __('Hide this post', 'peepso'),
                'icon' => 'eye',
                'click' => 'activity.option_hide(' . $post->act_id . '); return false',
            );

            $options['ignore'] = array(
                'label' => __('Block this user', 'peepso'),
                'icon' => 'remove', // 'minus-sign',
                'click' => 'activity.option_block(' . $post->ID . ',' . $post->post_author . '); return false',
            );
        }

        // only add this if current_user == owner_id or it's an admin
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_EDIT, $user_id))
            $options['edit'] = array(
                'label' => __('Edit Post', 'peepso'),
                'icon' => 'edit',
                'click' => 'activity.option_edit(' . $post->ID . '); return false',
            );

        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, $user_id) ||
            PeepSo::check_permissions(intval($post->act_owner_id), PeepSo::PERM_POST_DELETE, $user_id))
            $options['delete'] = array(
                'label' => __('Delete Post', 'peepso'),
                'icon' => 'trash', // 'remove',
                'click' => 'return activity.action_delete(' . $post->ID . ');',
            );

        $options = apply_filters('peepso_post_filters', $options);

        // if no options to display, exit
        if (0 === count($options))
            return;


        echo '<div class="ps-dropdown ps-dropdown-stream">', PHP_EOL;
        echo	'<a href="javascript:" class="ps-dropdown-toggle" data-value="" data-toggle="dropdown">', PHP_EOL;
        echo		'<span class="dropdown-caret ps-icon-caret-down"></span>', PHP_EOL;
        echo	'</a>', PHP_EOL;

        echo	'<ul class="dropdown-menu">', PHP_EOL;
        foreach ($options as $name => $data) {
            echo '<li';

            if (isset($data['li-class']))
                echo ' class="', $data['li-class'], '"';
            if (isset($data['extra']))
                echo ' ', $data['extra'];

            echo '><a href="#" ';
            if (isset($data['click']))
                echo ' onclick="', esc_js($data['click']), '" ';
            echo ' data-post-id="', $post->ID, '">';

            echo '<i class="ps-icon-', $data['icon'], '"></i><span>', $data['label'], '</span>', PHP_EOL;
            echo '</a></li>', PHP_EOL;
        }
        echo	'</ul>', PHP_EOL;
        echo '</div>', PHP_EOL;
    }


    /*
     * Display the permalink href= link for the given post
     */
    public function post_link($echo = TRUE)
    {
        global $post;
        $link = PeepSo::get_page('activity') . 'status/' . $post->post_title . '/';
        if ($echo)
            echo $link;
        else
            return ($link);
    }


    /*
     * Output the post's modified time as a GMT based timestamp
     */
    public function post_timestamp()
    {
        the_time('U');
    }


    /*
     * Allows other add-ons to output their post-specific data
     */
    public function post_attachment()
    {
        global $post;

        // let other add-ons have a chance to attach content to the post
        do_action('peepso_activity_post_attachment', $post, $post->ID, $post->act_module_id);
    }


    /**
     * Displays the activity privacy icon set on a post.
     */
    public function post_access()
    {
        global $post;

        $privacy = PeepSoPrivacy::get_instance();
        $level = $privacy->get_access_setting($post->act_access);

        echo '<i class="ps-icon-', $level['icon'], '"></i>';
    }

    /*
     * outputs the contents of a single post
     */
    public function show_post()
    {
//PeepSo::log(__METHOD__.'() - post data: id=' . $this->post_data['ID']); // var_export($this->post_data, TRUE));
        PeepSoTemplate::exec_template('activity', 'post', $this->post_data);
    }


    /**
     * Ajax callback, returns json encoded data of a page in an activity feed
     * @param  PeepSoAjaxResponse $resp
     */
    public function show_posts_per_page(PeepSoAjaxResponse $resp)
    {
        add_filter('peepso_user_profile_id', array(&$this, 'ajax_get_profile_id'));
        $input = new PeepSoInput();
        $page = $input->get_int('page', 1);
        $user = $input->get_int('uid');
        $owner = $input->get_int('user_id');

        $this->get_posts(0, $owner, $user, $page);

        ob_start();
        while ($this->next_post())
            $this->show_post(); // display post and any comments

        $this->show_more_posts_link();
        $resp->set('max_num_pages', $this->post_query->max_num_pages);
        $resp->set('found_posts', $this->post_query->found_posts);
        $resp->set('post_count', $this->post_query->post_count);
        $resp->set('posts', ob_get_clean());
    }


    /**
     * Filter callback to retrieve the current profile id, used in filter_post_clauses.
     * @return int The current viewed profile ID
     */
    public function ajax_get_profile_id()
    {
        $input = new PeepSoInput();
        return ($input->get_int('user_id'));
    }


    /*
     * return a WP_Query instance for all custom post types by peepso
     * @param string $orderby The column which to sort from
     * @param string $order Sort direction
     * @param int $limit The number of posts to limit the query by
     * @param int $offset The number of posts to offset the query by
     * @param array $search Key-value pair of search options using sub parameters of WP_Query
     * @return WP_Query instance of queried Post data
     */
    public function get_all_activities($orderby = 'post_date_gmt', $order = 'DESC', $limit = NULL, $offset = NULL, $search = array())
    {
        $args = array(
            'post_type' => apply_filters('peepso_activity_post_types',
                array(
                    PeepSoActivityStream::CPT_POST,
                    PeepSoActivityStream::CPT_COMMENT
                )
            ),
            'order_by' => $orderby,
            'order' => $order,
            'post_status' => 'any',
            'posts_per_page' => (NULL === $limit ? -1 : $limit),
            'offset' => (NULL === $offset ? 0 : $offset)
        );

        foreach ($search as $parameter => $value)
            $args[$parameter] = $value;

        return (new WP_Query($args));
    }


    /*
     * Callback for filtering post content to create anchor links
     * @param string $content The post content to filter
     * @return string Modified post content with URL converted to <a>nchor links
     */
    public function activity_post_content($content)
    {
        $cont = trim($content);		// empty(trim()) throws a PHP error
        if (empty($cont))
            return ('');

        if (function_exists('mb_convert_encoding'))
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        $xml = new DOMDocument();
        $xml->loadHTML($content);

        $links = $xml->getElementsByTagName('a');

        // loop through each <a> tags and replace them by their text content
        for ($i = $links->length - 1; $i >= 0; $i--) {
            $link_node = $links->item($i);
            $link_text = $link_node->getAttribute('href');
            $new_text_node = $xml->createTextNode($link_text);
            $link_node->parentNode->replaceChild($new_text_node, $link_node);
        }

        // remove <!DOCTYPE
        $xml->removeChild($xml->firstChild);
        $content = $xml->saveXML();

        // remove extra XML content added by parser
        $content = trim(str_replace(array('<?xml version="1.0" standalone="yes"?>',
            '<html>',
            '</html>',
            '<body>',
            '</body>'),'', $content));

        //$pattern = "/(((ftp|https?):\/\/)(www\.)?|www\.)([\da-z-_\.]+)([a-z\.]{2,7})([\/\w\.-_\-\?\&]*)*\/?/";
        $pattern = "#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#i";
        $content = preg_replace('/<p[^>]*>(.*)<\/p[^>]*>/i', '$1', $content);

        return (preg_replace_callback($pattern, array(&$this, 'make_link'), $content));
    }

    /*
     * Callback for preg_replace_callback to convert URLs to <a>nchor links
     * OR images/video if applicable
     *
     * @param array $matches The matched items
     * @return string the new content, with <a>nchor links added
     */
    public function make_link($matches)
    {
        $url = strip_tags($matches[0]);

        if (FALSE === strpos($url, '://'))
            $url = 'http://' . $url;

        $target = '';
        if (PeepSo::get_option('site_activity_open_links_in_new_tab'))
            $target = 'target="_blank"';

        $url_text = $url;

        if (!empty($this->post_media))
            return ('<a href="' . $url . '" rel="nofollow" ' . $target . '>' . $url_text . '</a>');

        $this->post_media = array(
            'title' => $url,
            'description' => $url,
        );

        // Get first image/video
        if (($embed_code = ps_oembed_get($url, array('width' => 220, 'height' => 150)))) {
            $this->post_media['content'] = $embed_code;
        } else if ($this->is_image_link($url)) {
            $parts = parse_url($url);

            $this->post_media['content'] = '';
            $this->post_media['title'] = $parts['host'];
            $this->post_media['description'] = $parts['host'];
            $this->post_media['content'] = '<img src="' . $url . '" height="150" width="150" alt="" />';
        }

        if (($og_tags = $this->_fetch_og_tags($url)) && $og_tags->image && !isset($this->post_media['content']))
            $this->post_media['content'] = '<img src="' . esc_url($og_tags->image) . '" alt="" />';

        // generate hash to avoid duplicate media
        if (FALSE === empty($this->post_media)) {
            $this->post_media['url'] = $url;
            $hash = md5(serialize($this->post_media));
            $this->peepso_media[$hash] = $this->post_media;
        }

        return ('<a href="' . $url . '" rel="nofollow" ' . $target . '>' . $url_text . '</a>');
    }

    /**
     * Checks whether the given URL is a link to an image.
     * From - http://stackoverflow.com/a/676954
     *
     * @param  string $url The image URL to be checked.
     * @return boolean
     */
    private function is_image_link($url)
    {
        $params = array('http' => array(
            'method' => 'HEAD'
        ));

        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', FALSE, $ctx);

        if (!$fp)
            return FALSE;  // Problem with url

        $meta = stream_get_meta_data($fp);

        if ($meta === FALSE) {
            fclose($fp);
            return FALSE;  // Problem reading data from url
        }

        $wrapper_data = $meta["wrapper_data"];

        if (is_array($wrapper_data)) {
            foreach (array_keys($wrapper_data) as $hh) {
                // strlen("Content-Type: image") == 19
                if (substr($wrapper_data[$hh], 0, 19) == "Content-Type: image") {
                    fclose($fp);
                    return TRUE;
                }
            }
        }

        fclose($fp);

        return FALSE;
    }

    /**
     * Try and get facebook og tags for the given URL, sets post_media values.
     * @param  string $url The URL to get og tags of.
     * @return array The OpenGraph tags available, if any.
     */
    private function _fetch_og_tags($url)
    {
        $og_tags = PeepSoOpenGraph::fetch($url);

        if ($og_tags) {
            if ($og_tags->title)
                $this->post_media['title'] = $og_tags->title;

            if ($og_tags->description)
                $this->post_media['description'] = $og_tags->description;
        }

        return ($og_tags);
    }

    /**
     * Set width and height of the videos
     * @param  string $html The embed HTML
     * @param  string $url  Media URL
     * @param  array $args  An array of arguments passed.
     * @return string The customized HTML.
     */
    public function oembed_result($html, $url, $args)
    {
        // Set the width of the video
        $width_pattern = "/width=\"[0-9]*\"/";
        $html = preg_replace($width_pattern, "width='100%'", $html);

        // Now return the updated markup
        return ($html);
    }

    /**
     * Returns the current retrieved link information
     * @return array
     */
    public function get_media()
    {
        return ($this->post_media);
    }

    /**
     * Set media properties
     * @param  array $return
     * @param  object $data The oembed response data
     */
    public function set_media_properties($return, $data)
    {
        if (isset($data->title))
            $this->post_media['title'] = $data->title;
        return ($return);
    }

    /*
     * Outputs <select> element for list of reporting reasons
     */
    public function report_reasons()
    {
        $reasons = str_replace("\r", '', PeepSo::get_option('site_reporting_types', __('Spam', 'peepso')));
        $reasons = explode("\n", $reasons);

        $reasons = apply_filters('peepso_activity_report_reasons', $reasons);

        echo '<select id="rep_reason" name="rep_reason" class="ps-select full">', PHP_EOL;
        echo '<option value="0">', __('- select reason -', 'peepso'), '</option>', PHP_EOL;

        foreach ($reasons as $reason) {
            $reason = esc_attr($reason);
            echo '<option value="', $reason, '">', $reason, '</option>';
        }

        echo '</select>', PHP_EOL;
    }


    /*
     * This template tag gives add-on authors a chance to output dialog box HTML content
     */
    public function dialogs()
    {
        do_action('peepso_activity_dialogs');
    }

    /**
     * Return the number of likes for an activity
     * @param  int  $act_id The act_id to look for
     * @return int  The number of likes
     */
    public function has_likes($act_id)
    {
        $activity = $this->get_activity($act_id);
        $like = new PeepSoLike();
        return ($like->get_like_count($activity->act_external_id, $activity->act_module_id));
    }

    /**
     * Show the "`n` person likes this" text
     * @param  int $count Set like count display explicitly
     */
    public function show_like_count($count = 0, $act_id = NULL)
    {
        if (NULL === $act_id) {
            global $post;
            $act_id = $post->act_id;
        }

        if ($count > 0)
            echo '<a onclick="return activity.show_likes(', $act_id, ');" href="#showLikes">',
            $count, _n(' person likes this', ' people like this.', $count, 'peepso'), '</a>';
    }

    /**
     * Return an html list of persons who liked a post.
     */
    public function get_like_names(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->get_int('act_id');

        $activity = $this->get_activity($act_id);

        $like = new PeepSoLike();
        $names = $like->get_like_names($activity->act_external_id, $activity->act_module_id);

        if (count($names) > 0) {
            $users = array();
            $html_names = array();
            $html = '';

            foreach ($names as $name) {
                $user = new PeepSoUser($name->ID);
                $users[$name->ID] = array(
                    'display_name' => $user->get_display_name(),
                    'profile_url' => $user->get_profileurl()
                );

                $html_names[] = '<a class="ps-comment-user" href="' . $users[$name->ID]['profile_url'] . '">' . $users[$name->ID]['display_name'] . '</a>';
            }

            $users = apply_filters('peepso_activity_like_names', $users, $act_id);

            $html .= implode(', ', $html_names);
            $html .= ' like this.';

            $resp->success(TRUE);
            $resp->set('users', $users);
            $resp->set('html', $html);
        } else {
            $resp->success(FALSE);
        }
    }


    /**
     * Return an array of Like information. Icon, count and label to display.
     * @param  int $post_id The post ID of which item to get like info from
     * @return array The Like data
     */
    public function get_like_status($post_id, $module_id = PeepSoActivity::MODULE_ID)
    {
        $logged_in = is_user_logged_in();
        $like = new PeepSoLike();
        $likes = $like->get_like_count($post_id, $module_id);
        $user_liked = $like->user_liked($post_id, $module_id, PeepSo::get_user_id());

        $like_icon = 'thumbs-up';
        $like_label = $like_text = ($user_liked ? __('Unlike', 'peepso') : __('Like', 'peepso'));

        if ($likes > 0 && $logged_in) {
            $like_label = '<span title="' . $likes . _n(' person likes this', ' people like this', $likes, 'peepso') .
                '">' . $like_text . '</span>';

            $like_icon = ($user_liked ? 'thumbs-down' : 'thumbs-up');
        }

        return (array(
            'label' => $like_label,
            'icon' => $like_icon,
            'count' => $likes
        ));
    }

    /**
     * Get a single post's html via ajax.
     * @param  PeepSoAjaxResponse $resp
     */
    public function ajax_show_post(PeepSoAjaxResponse $resp)
    {
        global $post;

        $input = new PeepSoInput();

        $owner_id = $input->get_int('user_id');
        $user_id = $input->get_int('uid');
        $act_id = $input->get_int('act_id');

        $activity = $this->get_activity($act_id);

        $act_post = apply_filters('peepso_activity_get_post', NULL, $activity, $owner_id, $user_id);
        if (NULL !== $act_post) {
            $post = get_post($act_post->ID, OBJECT);
            setup_postdata($act_post);

            ob_start();
            $this->content();
            $this->post_attachment();

            $resp->set('html', ob_get_clean());
            $resp->success(TRUE);
        } else
            $resp->success(FALSE);
    }

    /**
     * Returns the HTML content to display or NULL if no relevant content is found
     * @param  string $post  The post to return
     * @param  array $activity  The activity data
     * @param  int $owner_id The owner of the activity
     * @param  int $user_id The user requesting access to the activity post
     *
     * @return mixed The HTML post to display | NULL if no relevant post is found
     */
    public function activity_get_post($post, $activity, $owner_id, $user_id)
    {
        if (NULL === $post && is_object($activity)) {
            $this->get_post($activity->act_external_id, $owner_id, $user_id, TRUE);

            if ($this->post_query->have_posts()) {
                $this->post_query->the_post();
                $post = $this->post_query->post;
            }
        }

        return ($post);
    }

    /**
     * Get a single comment's html via ajax.
     * @param  PeepSoAjaxResponse $resp
     */
    public function ajax_show_comment(PeepSoAjaxResponse $resp)
    {
        global $post;

        $input = new PeepSoInput();

        $act_id = $input->get_int('act_id');
        $activity = $this->get_activity($act_id);

        $this->get_comment($activity->act_external_id);
        $this->comment_query->the_post();

        ob_start();
        $this->content();
        $this->comment_attachment();
        $resp->set('html', ob_get_clean());
        $resp->set('act_id', $post->act_id);

        $resp->success(TRUE);
    }

    /**
     * Return a WP_Query object containing comments from the given post, grouped by author.
     * @param  int $post_id The post to get the comments from.
     * @return object WP_Query object
     */
    public function get_comment_users($post_id, $module_id)
    {
        $args = array(
            'post_type' => $this->query_type = PeepSoActivityStream::CPT_COMMENT,
            '_comment_object_id' => $post_id,
            '_comment_module_id' => $module_id
        );

        add_filter('posts_groupby', array(&$this, 'groupby_author_id'), 10, 1);
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20, 2);
        $query = new WP_Query($args);
        remove_filter('posts_groupby', array(&$this, 'groupby_author_id'), 10);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20);

        return ($query);
    }

    /**
     * posts_groupby callback to group by author.
     * @param  string $groupby The groupby string.
     * @return string
     */
    public function groupby_author_id($groupby)
    {
        global $wpdb;

        return ($wpdb->posts . ".post_author");
    }

    /**
     * Returns Determines if a post has met it's maximum number of comments.
     * @param  int  $post_id The ID of the post to check the comment count.
     * @return boolean TRUE if the post has reached the max number of comments allowed; otherwise FALSE
     */
    public function has_max_comments($post_id, $module_id = self::MODULE_ID)
    {
        $limit_comments = PeepSo::get_option('site_activity_limit_comments');
        if (0 === intval($limit_comments))
            return (FALSE);

        $comments_allowed = PeepSo::get_option('site_activity_comments_allowed');

        $args = array(
            'post_type' => PeepSoActivityStream::CPT_COMMENT,
            'order_by' => 'post_date_gmt',
            'order' => 'ASC',
            '_comment_object_id' => $post_id,
            '_comment_module_id' => $module_id
        );

        // Not running through the posts_clauses_request filter because we need to get ALL comments
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20, 2);
        $comment_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20);

        return ($comment_query->found_posts >= $comments_allowed);
    }

    /**
     * Checks if a post is a repost and sets the html
     * @param  object $current_post The post
     */
    public function post_attach_repost($current_post)
    {
        $repost = $current_post->act_repost_id;

        if ($repost) {
            global $post;
            // Store original loop query, calling $this->get_post() will overwrite it.
            $_orig_post_query = $this->post_query;
            $activity = $this->get_activity($repost);

            $act_post = apply_filters('peepso_activity_get_post', NULL, $activity, NULL, NULL);

            if (NULL !== $act_post) {
                // TODO: resetting the value of the global $post variable is dangerous.
                $post = $act_post;
                // Add this property so that callbacks can do necessary adjustments if it's a repost.
                $post->is_repost = TRUE;
                setup_postdata($post);

                $this->post_data = get_object_vars($post);
                PeepSoTemplate::exec_template('activity', 'repost', $this->post_data);
            } else {
//				$post = get_post($repost);
                // TODO: this will reset the global $post variable. Avoid this
//				$post = get_post($repost)
                $re_post = get_post($activity->act_external_id);
                $data = array(
                    'post_author' => (NULL !== $re_post) ? $re_post->post_author : ''
                );
                PeepSoTemplate::exec_template('activity', 'repost-private', $data);
            }

            // Reset to the original loop
            $this->post_query = $_orig_post_query;
            $this->post_data = get_object_vars($this->post_query->post);
            $this->comment_query = NULL;

            // TODO: if you can avoid changing this then it's not needed. Definitely not needed in both cases above so only change it in one and reset before the end of the if-block
            $post = $this->post_query->post;
            setup_postdata($post);
        }
    }

    /**
     * Displays	the original author name from a repost
     */
    public function after_post_author()
    {
        global $post;
        $repost = $post->act_repost_id;

        if ($repost) {
            $repost = $this->get_activity_post($repost);

            if (NULL === $repost)
                return;

            $author = new PeepSoUser($repost->post_author);

            printf(__('via %s', 'peepso'),
                '<a href="' . $author->get_profileurl() . '">' . $author->get_display_name() . '</a>');
        }
    }

    /**
     * Change a post's privacy setting.
     * @param  PeepSoAjaxResponse $resp
     */
    public function change_post_privacy(PeepSoAjaxResponse $resp)
    {
        $input = new PeepSoInput();
        $act_id = $input->post_int('act_id');
        $user_id = $input->post_int('uid');

        $activity = $this->get_activity_post($act_id);
        $owner_id = intval($activity->post_author);

        if (wp_verify_nonce($input->post('_wpnonce'), 'peepso-nonce') &&
            PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            global $wpdb;

            $aActData = array(
                'act_access' => $input->post_int('acc'),
            );

            do_action('peepso_activity_change_privacy', $activity, $input->post_int('acc'));

            $resp->notice(__('Changes saved.', 'peepso'));
            $resp->success($wpdb->update($wpdb->prefix . self::TABLE_NAME, $aActData, array('act_id' => $act_id)));
        } else {
            $resp->error(__('You do not have permission to change post privacy settings.', 'peepso'));
            $resp->success(FALSE);
        }
    }

    /**
     * Outputs the description of an activity stream item
     */
    public function post_action_title()
    {
        global $post;

        if ($post->post_author === $post->act_owner_id) {
            $user = new PeepSoUser($post->post_author);

            $default_action = __('shared a %s', 'peepso');
            $default_action_text = __('post', 'peepso');
            if ($post->act_repost_id) {
                $repost = $this->get_activity_post($post->act_repost_id);

                if (NULL !== $repost)
                    $default_action_text = '<a href="' . PeepSo::get_page('activity') . 'status/' . $repost->post_title . '/' . '">' . $default_action_text . '</a>';
            }

            $action = apply_filters('peepso_activity_stream_action', sprintf($default_action, $default_action_text), $post);
            $title = sprintf('<a class="ps-stream-user" href="%s">%s</a> ' . $action . ' ',
                $user->get_profileurl(), $user->get_display_name());
        } else {
            $author = new PeepSoUser($post->post_author);
            $owner = new PeepSoUser($post->act_owner_id);

            $title = sprintf(
                '<a class="ps-stream-user" href="%s">%s</a><i class="ps-icon-caret-right"></i>
				<a class="ps-stream-user" href="%s">%s</a>',
                $author->get_profileurl(), $author->get_display_name(),
                $owner->get_profileurl(), $owner->get_display_name()
            );
        }

        echo apply_filters('peepso_activity_stream_title', $title, $post);
    }

    /**
     * Returns the act_id of the original activity being shared.
     *
     * @param  int $post_id
     * @return int
     */
    protected function get_repost_root($post_id)
    {
        if (empty($post_id))
            return (0);

        $sql = $this->_get_repost_root_query($post_id);
        $sql .= ' LIMIT 1';

        global $wpdb;
        $result = $wpdb->get_row($sql);

        return ($result->act_id);
    }

    /**
     * Returns the sql string to generate the hierarchy of repost events of an activity.
     *
     * source: http://explainextended.com/2009/07/20/hierarchical-data-in-mysql-parents-and-children-in-one-query/
     *
     * @param  intval $post_id The post ID
     * @return string The prepared sql query
     */
    private function _get_repost_root_query($post_id)
    {
        global $wpdb;

        $sql = "SELECT `T2`.`act_id`
			FROM (
				SELECT
					@r AS `_id`,
					(SELECT @r := `act_repost_id` FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` WHERE `act_id` = _id) AS `act_repost_id`,
					@l := @l + 1 AS lvl
				FROM
					(SELECT @r := %d, @l := 0) vars,
					`{$wpdb->prefix}" . self::TABLE_NAME . "` `h`
				WHERE @r <> 0) `T1`
			JOIN `{$wpdb->prefix}" . self::TABLE_NAME . "` `T2`
				ON `T1`.`_id` = `T2`.`act_id`
			ORDER BY `T1`.`lvl` DESC";

        return ($wpdb->prepare($sql, $post_id));
    }

    /**
     * Removes the Only Me access if a post does not belong to the current user's stream
     * @param  array $acc
     * @return array
     */
    public function privacy_access_levels($acc)
    {
        global $post;

        if ($post->post_author !== $post->act_owner_id)
            unset($acc[PeepSo::ACCESS_PRIVATE]);

        return ($acc);
    }

    /**
     * Saves/updates post meta data "peepso_media"
     * @param int $post_id Post content ID
     */
    protected function save_peepso_media($post_id)
    {
        $old_peepso_media = get_post_meta($post_id, 'peepso_media');
        // delete oldies
        if (!empty($old_peepso_media))
            foreach ($old_peepso_media as $old_media)
                delete_post_meta($post_id, 'peepso_media', $old_media);

        foreach ($this->peepso_media as $post_media)
            add_post_meta($post_id, 'peepso_media', $post_media);
    }
}

// EOF

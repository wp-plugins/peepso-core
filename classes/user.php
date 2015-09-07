<?php

class PeepSoUser
{
	const TABLE = 'peepso_users';

	const THUMB_WIDTH = 64;
	const IMAGE_WIDTH = 160;

	const COVER_WIDTH = 1140;
	const COVER_HEIGHT = 428;

	const DEFAULT_ROLE = 'member';
	const DEFAULT_WP_ROLE = 'subscriber';

	const ACCESS_SETTING = 'peepso_user_access';

	const USERNAME_MAXLEN = 20;
	const USERNAME_MINLEN = 2;
	
	const FIRSTNAME_MAXLEN = 30;
	const FIRSTNAME_MINLEN = 1;
	const LASTNAME_MAXLEN = 30;	
	const LASTNAME_MINLEN = 1;
	const EMAIL_MAXLEN = 320;

	private $default_settings = array(
		'stream_view' => PeepSo::ACCESS_PUBLIC,
		'stream_post' => PeepSo::ACCESS_MEMBERS,
		'stream_like_post' => PeepSo::ACCESS_MEMBERS,
		'stream_comment' => PeepSo::ACCESS_MEMBERS,
		'stream_like_comment' => PeepSo::ACCESS_MEMBERS,
	);

	private $id = NULL;
	private $wp_user = FALSE;
	private $peepso_user = NULL;
	private $gender = 'u';

	/*
	 * Constructor
	 * @param int $id The user id to create this instance for
	 */
	public function __construct($id = 0)
	{
		if ($id > 0) {
			$this->id = $id;
			$this->wp_user = get_user_by('id', $id);

			if( $this->id == PeepSo::get_user_id() ) {
				$this->update_last_activity();
			}

			if (FALSE === $this->wp_user)
				return (FALSE);

			global $wpdb;
			$sql = "SELECT * FROM `{$wpdb->prefix}" . self::TABLE . "` " .
					" WHERE `usr_id`=%d ";
			$res = $wpdb->get_results($wpdb->prepare($sql, $id), ARRAY_A);
			if (count($res) == 0) {
				// if peepso_users record doesn't exist, create it
				$data = array(
					'usr_id' => $id,
					'usr_profile_acc' => PeepSo::ACCESS_PUBLIC,
					'usr_first_name_acc' => PeepSo::ACCESS_PUBLIC,
					'usr_last_name_acc' => PeepSo::ACCESS_PUBLIC,
					'usr_description_acc' => PeepSo::ACCESS_PUBLIC,
					'usr_user_url_acc' => PeepSo::ACCESS_PUBLIC,
					'usr_gender_acc' => PeepSo::ACCESS_PUBLIC,
					'usr_birthdate_acc' => PeepSo::ACCESS_PUBLIC,
				);
				$wpdb->insert($wpdb->prefix . self::TABLE, $data);
				$res = $wpdb->get_results($wpdb->prepare($sql, $id), ARRAY_A);
			}
			if (count($res) > 0)
				$this->peepso_user = $res[0];
		}
	}

	public function get_asset_hash()
	{
		$hash = get_user_meta($this->peepso_user['usr_id'],'peepso_asset_hash', TRUE);

		if( empty($hash)) {
			$hash = substr(md5(time()),5,5);
			update_user_meta($this->peepso_user['usr_id'],'peepso_asset_hash', $hash);
		}

		return $hash;
	}
	/*
	 * Return the user's cover photo
	 * @return string href value for the user's cover photo
	 */
	public function get_coverphoto()
	{
		$hash = $this->get_asset_hash();

		$cover = NULL;
		if (isset($this->peepso_user['usr_cover_photo']))
			$cover = $this->peepso_user['usr_cover_photo'];

		if (empty($cover)) {
			$gender = $this->get_gender();
			// get default image based on gender
			switch ($gender)
			{
			case 'm':	$file = 'male';			break;
			case 'f':	$file = 'female';		break;
			default:	$file = 'undefined';	break;
			}
			$cover = PeepSo::get_asset('images/cover/' . $file . '-default.png');
		}

		$cover .='?'.$hash;
		return ($cover);
	}


	/*
	 * Return user's avatar image
	 * @param boolean $full TRUE to return full-size image or FALSE for small image
	 * @return string The href value for the avatar image suitible for use in an <img> tag
	 */
	public function get_avatar($full = FALSE)
	{
		$file = $this->get_image_dir() . 'avatar' . ($full ? '-full' : '') . '.jpg';

		$hash = $this->get_asset_hash();

		if (!file_exists($file)) {

			$this->set_avatar_custom_flag(FALSE);

			// fallback to male/female image if no avatar
			$g = $this->get_gender();		// gender returns FALSE if gender is inaccessible
			switch ($g)
			{
			case 'm':		$gender = 'male';		break;
			case 'f':		$gender = 'female';		break;
			default:		$gender = 'neutral';	break;
			}
			$file = PeepSo::get_asset('images/user-' . $gender . ($full ? '' : '-thumb') . '.png');
		} else {
			$this->set_avatar_custom_flag(TRUE);
			$file = $this->get_image_url() . 'avatar' . ($full ? '-full' : '') . '.jpg';
		}

		$file.='?'.$hash;
		return ($file);
	}


	function set_avatar_custom_flag( $flag = TRUE )
	{
		global $wpdb;
		$flag = ( TRUE === $flag) ? TRUE : FALSE;
		$data = array('usr_avatar_custom' => $flag);

		$wpdb->update($wpdb->prefix . self::TABLE, $data, array('usr_id' => $this->id));

	}


	/*
	 * Return user's temporary avatar image
	 * @param boolean $full TRUE to return full-size image or FALSE for small image
	 * @return string The href value for the avatar image suitible for use in an <img> tag
	 */
	public function get_tmp_avatar($full = FALSE)
	{
		$file = $this->get_image_dir() . 'avatar' . ($full ? '-full' : '') . '-tmp.jpg';

		if (!file_exists($file)) {
			// fallback to male/female image if no avatar
			$g = $this->get_gender();		// gender returns FALSE if gender is inaccessible
			switch ($g)
			{
			case 'm':		$gender = 'male';		break;
			case 'f':		$gender = 'female';		break;
			default:		$gender = 'neutral';	break;
			}
			$file = PeepSo::get_asset('images/user-' . $gender . ($full ? '' : '-thumb') . '.png');
		} else {
			$file = $this->get_image_url() . 'avatar' . ($full ? '-full' : '') . '-tmp.jpg';
		}

		return ($file);
	}


	/*
	 * Returns accessibility value for given property name used in form generation
	 * @param string $name Name of property
	 * @return int The accessibility code for the given property or -1 if it's an unrecognized property
	 */
	public function get_accessibility($name)
	{
		$prop = "usr_{$name}_acc";
		if (isset($this->peepso_user[$prop]))
			return (intval($this->peepso_user[$prop]));
		return (0);
	}


	/*
	 * Determine if data is accessible by the current user
	 * @param string $col_name The name of the column data to check
	 * @return Boolean TRUE for accessible and FALSE for not accessible
	 */
	public function is_accessible($data)
	{
		$acc = 40;

		$col_name = 'usr_' . $data . '_acc';
		if (isset($this->peepso_user[$col_name]))
			$acc = intval($this->peepso_user[$col_name]);

		// to start, assume FALSE
		$ret = FALSE;

		// if the user is checking their own information -- always return TRUE
		// if user is an admin - always return TRUE
		if (PeepSo::get_user_id() === intval($this->id) || PeepSo::is_admin()) {
			// set this so it can be filtered down below
			$ret = TRUE;
		} else {
			// check based on access type
			switch ($acc)
			{
			case 0:
			case PeepSo::ACCESS_PUBLIC:
				$ret = TRUE;
				break;

			case PeepSo::ACCESS_MEMBERS:
				if (is_user_logged_in())
					$ret = TRUE;
				break;

			case PeepSo::ACCESS_PRIVATE:
				// fall through and return FALSE
				break;
			}
		}

		// run the calculated value through filter to allow add-ons a chance to modify it
		return (apply_filters('peepso_user_is_accessible', $ret, $acc, $this));
	}


	/*
	 * Helper function to return data properties
	 */
	private function _get_prop($data, $check_acc = TRUE)
	{
		$col_name = 'usr_' . $data;
		$acc_name = $col_name . '_acc';			// name of access column in peepso_users table

		if ($check_acc) {
			// if there's an access column, check it
			if (isset($this->peepso_user[$acc_name])) {
				if (!$this->is_accessible($data))
					return (FALSE);
			}
		}

		// no access restriction requested or no access column, feel free to return the data
		if (isset($this->peepso_user[$col_name])) {
			// the column name exists in peepso_users, so return it
			return ($this->peepso_user[$col_name]);
		} else {
			// the column name doesn't exist in peepso_users so try wp_user
			if (isset($this->wp_user->$data))
				return ($this->wp_user->$data);
		}
		return (FALSE);
	}

	/*
	 * Return birthdate
	 */
	public function get_birthdate($check_acc = TRUE)
	{
		return ($this->_get_prop('birthdate', $check_acc));
	}

	/**
	 * Returns the wordpress username
	 * @return string The user login
	 */
	public function get_username()
	{
		// TODO: this is okay for now, but we need to determine why wp_user was not created and make sure it gets created
		// Spydroid: I think this is a rare case and only occurs when user is manually deleted in the db leaving all the user's posts and comments orphaned
		// TODO: The person reporting this did not delete any users from the database, so something else is causing this. Maybe it's caused by an instantiation done without a user id, then later trying to get the username from the incomplete instance.
		if (FALSE === $this->wp_user)
			return ('');
		return ($this->wp_user->user_login);
	}

	/**
	 * Returns the email that was used to register
	 * @return string The user's email
	 */
	public function get_email()
	{
		// TODO: this is okay for now, but we need to determine why wp_user was not created and make sure it gets created
		if (FALSE === $this->wp_user)
			return ('');
		return ($this->wp_user->user_email);
	}

	/**
	 * Returns the first name of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The first name if has access, otherwise FALSE
	 */
	public function get_firstname($check_acc = TRUE)
	{
		return ($this->_get_prop('first_name', $check_acc));
	}

	/**
	 * Returns the last name of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The last name if has access, otherwise FALSE
	 */
	public function get_lastname($check_acc = TRUE)
	{
		return ($this->_get_prop('last_name', $check_acc));
	}

	/**
	 * Returns the full name of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The full name if has access, otherwise FALSE
	 */
	public function get_fullname($check_acc = TRUE)
	{
		if ($check_acc && !($this->is_accessible('first_name') && $this->is_accessible('last_name')))
			return (FALSE);
		return ($this->_get_prop('display_name', FALSE));
	}

	/**
	 * Returns the first name or the username of the user - depending on the config
	 */
	public function get_firstname_safe()
	{
		$name = $this->get_display_name();
		$name = explode(' ', $name);
		return $name[0];
	}

	/**
	 * Returns a display name based on privacy settings for first and last name.
	 * If neither first and last name are accessible, returns user name
	 * @return string
	 */
	public function get_display_name()
	{
		// check to see if the "Allow User to Override Name Setting" option is enabled.
		if (1 === intval(PeepSo::get_option('system_override_name', 0))) // read the user's setting for display options
			$display_name_as = $this->get_display_name_as();
		else // get the site config setting for the display name style.
			$display_name_as = PeepSo::get_option('system_display_name_style', 'username');

		$name = array();
		switch ($display_name_as)
		{
		case 'real_name':
			$name[] = $this->get_firstname(FALSE);
			$name[] = $this->get_lastname(FALSE);
			break;
		default:
			$name[] = $this->get_username();
		}

		return (implode(' ', $name));
	}

	/**
	 * Returns the gender of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The gender if has access, otherwise FALSE
	 */
	public function get_gender($check_acc = TRUE)
	{
		return ($this->_get_prop('gender', $check_acc));
	}

	/**
	 * Returns the date of last login of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The date of last login if has access, otherwise FALSE
	 */
	public function get_last_online()
	{
		$last_online = $this->_get_prop('last_activity', FALSE);

		if ('0000-00-00 00:00:00' === $last_online)
			return (__('Never', 'peepso'));

		return ($last_online);
	}

	/**
	 * Returns the registration date of the user	 
	 * @return string The user's registration date
	 */
	public function get_date_registered()
	{
		// TODO: this is okay for now, but we need to determine why wp_user was not created and make sure it gets created
		if (FALSE === $this->wp_user)
			return ('');
		$dt = $this->wp_user->user_registered;
		return ($dt);
	}

	/**
	 * Returns the description field of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The description field if has access, otherwise FALSE
	 */
	public function get_description($check_acc = TRUE)
	{
		return ($this->_get_prop('description', $check_acc));
	}

	/**
	 * Returns the url of the user
	 * @param  boolean $check_acc Whether or not to check if the viewing user has access to this object
	 * @return mixed The url if has access, otherwise FALSE
	 */
	public function get_userurl($check_acc = TRUE)
	{
		return ($this->_get_prop('user_url', $check_acc));
	}

	/**
	 * Return the roles assigned to the user
	 * @deprecated since Jan 2015. Use get_user_role() instead
	 * @return string The user's Wordpress roles
	 */
	public function get_role()
	{
		// TODO: this is okay for now, but we need to determine why wp_user was not created and make sure it gets created
		if (FALSE === $this->wp_user)
			return ('');
		return ($this->wp_user->roles);
	}

	/**
	 * Get's the role information from the `peepso_users` table column `usr_role`
	 * @return string The user's role. One of 'user','member','moderator','admin','ban','register'
	 */
	public function get_user_role()
	{
		return ($this->_get_prop('role', FALSE));
//		return $this->wp_user->roles;
	}

	/**
	 * Changes the user's role to the specified value
	 * @param string $role The new role to change the user to
	 */
	public function set_user_role($role)
	{
		global $wpdb;
		$wpdb->update($wpdb->prefix . self::TABLE, array('usr_role' => $role), array('usr_id' => $this->id));
	}

	public function update_last_activity()
	{
		global $wpdb;
		$trans = 'peepso_'.$this->id.'_online';
		set_transient($trans,1,60);
		$wpdb->update($wpdb->prefix . self::TABLE, array('usr_last_activity' => current_time('mysql')), array('usr_id' => $this->id));
	}

	public function is_online()
	{
		$trans = 'peepso_'.$this->id.'_online';
		return (get_transient($trans)) ? TRUE : FALSE;
	}

	/**
	 * Return the user's ID
	 * @return int The user ID field
	 */
	public function get_id()
	{
		return ($this->id);
	}

	/*
	 * Get the user's profile page URL
	 * @return string The user's profile URL
	 */
	public function get_profileurl()
	{
		$page = PeepSo::get_page('profile') . urlencode($this->get_username()) . '/';
		return (apply_filters('peepso_username_link', $page, $this->id));
	}


	/*
	 * creates a WordPress user and "marks" it as a PeepSo user
	 * @param string $fname The new user's first name
	 * @param string $lname The new user's last name
	 * @param string $uname The new user's username
	 * @param string $email The new user's email address
	 * @param string $passw The new user's password
	 * @param string $gender The new user's gender (Optional)
	 * @return multi The new user's id on success or FALSE on error
	 */
	// TODO: this can be moved to PeepSoUserAdmin
	public function create_user($fname, $lname, $uname, $email, $passw, $gender = 'u')
	{
		$default_role = apply_filters('peepso_user_default_role', 'register');

		// sanitize user name, removing non-allowed characters
		$uname = sanitize_user($uname, TRUE);
		$uname = str_replace('@', '', $uname);
		$uname = str_replace('*', '', $uname);
		$uname = str_replace(' ', '', $uname);
		
		// sanitize first name and last name
		$fname = sanitize_text_field(strip_tags($fname));
		$lname = sanitize_text_field(strip_tags($lname));		
		
		// sanitize email
		$email = sanitize_email($email);

		// create the peepso_users table record
		$data = array(
			'user_login' => $uname,
			'user_pass' => $passw,
			'user_nicename' => $uname,
			'user_email' => $email,
			'first_name' => $fname,
			'last_name' => $lname,
			'user_registered' => current_time('mysql'),
			'role' => self::DEFAULT_WP_ROLE				// $default_role
		);
		$id = wp_insert_user($data);

		if (is_wp_error($id)) {
			return (FALSE);
		}

		// create the WordPress user
		$data = array(
			'usr_id' => $id,
			'usr_last_activity' => current_time('mysql'),
			'usr_role' => $default_role,
			'usr_profile_acc' => PeepSo::ACCESS_PUBLIC,
			'usr_first_name_acc' => PeepSo::ACCESS_PUBLIC,
			'usr_last_name_acc' => PeepSo::ACCESS_PUBLIC,
			'usr_description_acc' => PeepSo::ACCESS_PUBLIC,
			'usr_user_url_acc' => PeepSo::ACCESS_PUBLIC,
			'usr_gender' => $gender,
			'usr_gender_acc' => PeepSo::ACCESS_PUBLIC,
			'usr_birthdate' => '0000-00-00 00:00:00',
			'usr_birthdate_acc' => PeepSo::ACCESS_PUBLIC,
		);
		global $wpdb;

		$res = $wpdb->insert($wpdb->prefix . self::TABLE, $data);
		update_user_meta($id, 'show_admin_bar_front', 'false');

		// create the user's directory directory
		$temp_id = $this->id;
		$this->id = $id;

		$user_dir = $this->get_image_dir();
		$parent_dir = dirname($user_dir);
		if (!file_exists($parent_dir))
			@mkdir($parent_dir);
		@mkdir($user_dir);
//		$this->id = $temp_id;		// we need to keep the *new* user id

		$this->wp_user = get_user_by('id', $this->id);

		// send user an activation email
		$this->send_activation($email,1);

		// send admin an email
		$adm_id = PeepSo::get_notification_user();
		$adm_email = PeepSo::get_notification_emails();

		$data = array(
			'useremail' => $adm_email,
			'userlogin' => $uname,
			'userfirstname' => $fname,
			'userlastname' => $lname,
			'permalink' => admin_url('user-edit.php?user_id=' . $this->id),
		);
//		PeepSoMailQueue::add_message($adm_id, $data, __('New User Registration', 'peepso'), 'new_user_registration', 'new_user_registration');

//		wp_new_user_notification($id, $passw);

		return ($id);
	}


	/*
	 * Sends activation email with code to new user
	 * @param string $email The email address of the user
	 * @param int $now If set to 1 the email will be sent immediately
	 */
	public function send_activation($email,$now = 0)
	{
		$key = md5(wp_generate_password(20, FALSE) . $this->id . time());
		do_action('retrieve_password_key', $this->get_username(), $key);		// let others know

		// update the database
		global $wpdb;
		$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('ID' => $this->id));

		$data = $this->get_template_fields('user');
		$data['activation'] = $key;
		$data['activatelink'] = PeepSo::get_page('register') . '?peepso_activate&peepso_activation_code=' . $key;
		$data['useremail'] = $email;
		// Save the key as meta so this user is searchable.
		add_user_meta($this->id, 'peepso_activation_key', $key, TRUE);

//		$content = PeepSoMailQueue::add_message($this->id, $data, sprintf(__('Welcome to %s', 'peepso'), get_bloginfo('sitename')), 'new_user', 'new_user');
		if (PeepSo::get_option('site_registration_enableverification', '0'))
			$template = 'new_user_no_approval';
		else
			$template = 'new_user';

		PeepSoMailQueue::add_message($this->id, $data, sprintf(__('Welcome to %s', 'peepso'),
			get_bloginfo('sitename')), $template /*'new_user_no_approval'*/, 'new_user',0,$now);
		
		if (1 === $now) 
			PeepSoMailQueue::process_mailqueue(1);			
	}

	/* check if an action is allowed to be performed by an author
	 * @param string $action Name of action to check
	 * @param int $author Id of author attempting the action
	 */
	public function check_access($action, $author)
	{
		$access = get_user_meta($this->id, self::ACCESS_SETTING, TRUE);
		if (empty($access))
			$access = $this->default_settings;
		else
			$access = unserialize($access);

		$val = PeepSo::ACCESS_PRIVATE;
		if (isset($access['stream_' . $action]))
			$val = $access['stream_' . $action];

		switch ($val)
		{
		// TODO: check this - better to do 'case 0:' and 'case ACCESS_PUBLIC:' on separate lines
		case 0 || PeepSo::ACCESS_PUBLIC:
			return (TRUE);
			break;
		case PeepSo::ACCESS_MEMBERS:
			if ($author)				// if author != 0, they're logged in and therefore a member
				return (TRUE);
			break;
		case PeepSo::ACCESS_PRIVATE:
			break;
		}
		return (FALSE);
	}


	/*
	 * Adds to profile view count.
	 * @param int $user_id The user id to add to the view count. If not provided will use
	 * the instance's user id.
	 */
	public function add_view_count($user_id = NULL)
	{
		if (NULL === $user_id && NULL === $this->id)
			return;

		if (NULL === $user_id)
			$user_id = $this->id;

		global $wpdb;
		$sql = "UPDATE `{$wpdb->prefix}" . self::TABLE . "` " .
				" SET `usr_views` = `usr_views`+1 " .
				" WHERE `usr_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));
	}


	/*
	 * Retrieve the user's profile view count
	 * @return int The number of views of the profile
	 */
	public function get_view_count()
	{
		return ($this->peepso_user['usr_views']);
	}


	/**
	 * Move $src_file to the image directory and update the database entry for its location
	 * @param  string $src_file The original file path to get the file from
	 */
	// TODO: move to PeepSoProfile
	public function move_cover_file($src_file)
	{
		$dir = $this->get_image_dir();

		// TODO: check for image orientation and rotate if needed

		$dest_file = $dir . 'cover.jpg';
		$si = new PeepSoSimpleImage();
		$si->png_to_jpeg($src_file);
		$si->load($src_file);
		$si->save($dest_file, IMAGETYPE_JPEG);

		// update database table entry
		$data = array('usr_cover_photo' => $this->get_image_url() . 'cover.jpg');

		global $wpdb;
		$wpdb->update($wpdb->prefix . self::TABLE, $data, array('usr_id' => $this->id));

		delete_user_meta($this->id, 'peepso_cover_position_x');
		delete_user_meta($this->id, 'peepso_cover_position_y');
	}


	/*
	 * Deletes the user's cover photo and removes the database entry
	 */
	// TODO: move to PeepSoProfile
	public function delete_cover_photo()
	{
		$ret = FALSE;
		$cover_file = $this->get_image_dir() . 'cover.jpg';
		if (file_exists($cover_file)) {
			unlink($cover_file);
			$ret = TRUE;
		}

		global $wpdb;
		$data = array('usr_cover_photo' => '');
		$wpdb->update($wpdb->prefix . self::TABLE, $data, array('usr_id' => $this->id));		
		delete_user_meta($this->id, 'peepso_cover_position_x');
		delete_user_meta($this->id, 'peepso_cover_position_y');

		return ($ret);
	}


	/*
	 * Deletes the user's avatar image, including the original and small versions
	 */
	// TODO: move to PeepSoProfile
	public function delete_avatar()
	{
		$dir = $this->get_image_dir();

		if (file_exists($dir . 'avatar.jpg'))
			unlink($dir . 'avatar.jpg');

		if (file_exists($dir . 'avatar-full.jpg'))
			unlink($dir . 'avatar-full.jpg');

		if (file_exists($dir . 'avatar-orig.jpg'))
			unlink($dir . 'avatar-orig.jpg');

		$this->set_avatar_custom_flag(FALSE);
	}

	/**
	 * Fix image orientation
	 * @param object $image WP_Image_Editor
	 * @param array $exif EXIF metadata
	 * @return object $image WP_Image_Editor
	 */
	// TODO: move this. Where is it used? PeepSoProfile?
	public function fix_image_orientation($image, $orientation)
	{
		switch ($orientation)
		{
		case 3:
			$image->rotate(180);
			break;
		case 6:
			$image->rotate(-90);
			break;
		case 8:
			$image->rotate(90);
			break;
		}
		return ($image);
	}

	/*
	 * Moves an uploaded avatar file into the user's directory, renaming and converting
	 * the file to .jpg
	 * @param string $src_file Path to the source / uploaded file
	 * @param Boolean $delete Set to TRUE to delete $src_file
	 */
	// TODO: move to PeepSoProfile
	public function move_avatar_file($src_file, $delete = FALSE)
	{
		$dir = $this->get_image_dir();
		
		$si = new PeepSoSimpleImage();
		$si->png_to_jpeg($src_file);

		$image = wp_get_image_editor($src_file);

		if (!is_wp_error($image)) {
			$dest_orig = $dir . 'avatar-orig-tmp.jpg';
			$dest_full = $dir . 'avatar-full-tmp.jpg';
			$dest_thumb = $dir . 'avatar-tmp.jpg';

			if (function_exists('exif_read_data') && function_exists('exif_imagetype') && IMAGETYPE_JPEG === exif_imagetype($src_file)) {
				$exif = @exif_read_data($src_file);
				$orientation = isset($exif['Orientation']) ? $exif['Orientation'] : 0;
			} else {
				$exif = new PeepSoExif($src_file);
				$orientation = $exif->get_orientation();
			}
			$image->set_quality(75);
			$image = $this->fix_image_orientation($image, $orientation);
			$image->save($dest_orig, IMAGETYPE_JPEG);

			$image = wp_get_image_editor($src_file);
			$image->resize(self::IMAGE_WIDTH, self::IMAGE_WIDTH, TRUE);
			$image->set_quality(75);
			$image = $this->fix_image_orientation($image, $orientation);
			$image->save($dest_full, IMAGETYPE_JPEG);

			$image = wp_get_image_editor($src_file);
			$image->resize(self::THUMB_WIDTH, self::THUMB_WIDTH, TRUE);
			$image->set_quality(75);
			$image = $this->fix_image_orientation($image, $orientation);
			$image->save($dest_thumb, IMAGETYPE_JPEG);
		}

		if ($delete)
			unlink($src_file);
	}


	/*
	 * Finalize moves temporary avatar files into designated location.
	 */
	public function finalize_move_avatar_file()
	{
		$dir = $this->get_image_dir();

		$src_thumb = $dir . 'avatar-tmp.jpg';
		$src_full = $dir . 'avatar-full-tmp.jpg';
		$src_orig = $dir . 'avatar-orig-tmp.jpg';

		$dest_thumb = $dir . 'avatar.jpg';
		$dest_full = $dir . 'avatar-full.jpg';
		$dest_orig = $dir . 'avatar-orig.jpg';

		if (file_exists($src_thumb) && file_exists($src_full) && file_exists($src_orig)) {
			rename($src_thumb, $dest_thumb);
			rename($src_full, $dest_full);
			rename($src_orig, $dest_orig);
		}
	}


	/*
	 * Checks for and creates the user's image directory
	 * @param string $file The file name that is going to be created
	 */
	private function _make_user_dir($dir_name)
	{
		$dir_name = rtrim($dir_name, '/');
		if (!file_exists($dir_name) ) {
			$ret = @mkdir($dir_name, 0755, TRUE);
			return ($ret);
		}
		return (TRUE);
	}


	/*
	 * return directory where user's images are located
	 * @param int $id User id to retrieve directory
	 * @return string directory where user's images are located
	 */
	public function get_image_dir()
	{
		// wp-content/peepso/users/{user_id}/
//		$dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso' . DIRECTORY_SEPARATOR .
//			'users' . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR;
		$dir = PeepSo::get_peepso_dir() . 'users' . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR;

		// Make sure the dir exists
		$this->_make_user_dir($dir);

		return ($dir);
	}
	public function get_image_url()
	{
//		$dir = WP_CONTENT_URL . '/peepso/users/' . $this->id . '/';
		$dir = PeepSo::get_peepso_uri() . 'users/' . $this->id . '/';
		return ($dir);
	}


	// used for testing...
	public function get_user()
	{
		return ($this->wp_user);
	}
	public function get_peepso_user()
	{
		return ($this->peepso_user);
	}

	/*
	 * updates the peepso_user record with data passed in via array
	 * @param array $data Key=>value array of column names found in the peepso_user table
	 * @return Boolean TRUE on sucess, otherwise 1 - the number of records updated
	 */
	public function update_peepso_user($data)
	{
		// update local store of data - ensure only known columns are passed in
		foreach ($this->peepso_user as $col => $val) {
			if (isset($data[$col])) {
PeepSo::log(' >> '.__METHOD__.'data[' . $col . ']='.$val . ' => ' . $data[$col]);
				$this->peepso_user[$col] = $data[$col];
			}
		}

		global $wpdb;
		return ($wpdb->update($wpdb->prefix . PeepSoUser::TABLE, $this->peepso_user, array('usr_id' => $this->id)));
	}


	/*
	 * Returns user-specific template fields. Used by PeepSoEmailTemplate
	 * @param string $prefix The prefix name for the array elements to return. Used to differentiate between the user and from names
	 * @return array Associative array of template fields in key => value form
	 */
	// TODO: move to PeepSoEmailTemplate class
	public function get_template_fields($prefix)
	{
		$ret = array();
		$ret[$prefix . 'email'] = $this->get_email();
		$ret[$prefix . 'fullname'] = $this->get_fullname();
		$ret[$prefix . 'firstname'] = $this->get_firstname();
		$ret[$prefix . 'lastname'] = $this->get_lastname();
		$ret[$prefix . 'login'] = $this->get_username();
		return ($ret);
	}


	/*
	 * Deletes *ALL* PeepSo related data for a single user
	 * @param int $user_id The user ID who's data should be deleted
	 */
	// TODO: this can be moved to PeepSoUserAdmin
	public function delete_data($user_id)
	{
		do_action('peepso_user_delete_data', $user_id);

		$this->delete_cover_photo();
		$this->delete_avatar();

		$activity = PeepSoActivity::get_instance();

		global $wpdb;

		// Delete reposts
		$sql = "DELETE `act`.* FROM `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` `act` WHERE `act_repost_id` IN (
					SELECT `act_id` FROM 
						(
							SELECT `act_id` FROM `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` 
								WHERE `act_owner_id`=%d
						) x
				)";

		$wpdb->query($wpdb->prepare($sql, $user_id));

		// Note: not deleting activities - should this be a configurable option?
		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` " .
				" WHERE `act_owner_id`=%d";
		$wpdb->query($wpdb->prepare($sql, $user_id));

		$user_posts = new WP_Query(array(
			'post_type' => PeepSoActivityStream::CPT_POST,
			'author' => $user_id,
			'fields' => 'ids'
			)
		);

		foreach ($user_posts->posts as $post_id)
			$activity->delete_post($post_id);

		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoActivityHide::TABLE . "` " .
				" WHERE `hide_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));

		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoBlockUsers::TABLE . "` " .
				" WHERE `blk_user_id`=%d OR `blk_blocked_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id, $user_id));

		$sql = "DELETE FROM `{$wpdb->prefix}peepso_cache` " .
				" WHERE `user_id`=%d";
		$wpdb->query($wpdb->prepare($sql, $user_id));

		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoLike::TABLE . "` " .
				" WHERE `like_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));

		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoMailQueue::TABLE . "` " .
				" WHERE `mail_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));
		
		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoNotifications::TABLE . "` " .
				" WHERE `not_user_id`=%d OR `not_from_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id, $user_id));

		$sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoReport::TABLE . "` " .
				" WHERE `rep_user_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));

		// TODO: no model class exists for this
		$sql = "DELETE FROM `{$wpdb->prefix}peepso_unfollow` " .
				" WHERE `unf_user_id`=%d OR `unf_unfollowed_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id, $user_id));

		$sql = "DELETE FROM `{$wpdb->prefix}" . self::TABLE . "` " .
				" WHERE `usr_id`=%d ";
		$wpdb->query($wpdb->prepare($sql, $user_id));
	}

	/**
	 * Returns whether or not this user's profile can be "Like"`d
	 * Defaults to TRUE if not set.
	 * @return boolean
	 */
	public function is_profile_likable()
	{
		$likable = get_user_meta($this->id, 'peepso_is_profile_likable', TRUE);

		return (('' !== $likable) ? $likable : TRUE);
	}

	/**
	 * Returns the site_activity_posts setting or the users's preference, if any, of the number of
	 * activity stream feeds to show at a time.
	 * @return int
	 */
	public function get_num_feeds_to_show()
	{
		$user_feeds_to_show = get_user_meta($this->id, 'peepso_feeds_to_show', TRUE);

		return (('' !== $user_feeds_to_show) ? $user_feeds_to_show : PeepSo::get_option('site_activity_posts'));
	}

	/**
	 * Returns the site_activity_posts setting or the users's preference, if any, of the number of
	 * activity stream feeds to show at a time.
	 * @return int
	 */
	public static function get_gmt_offset($user_id)
	{
		$user_gmt_offset = get_user_meta($user_id, 'peepso_gmt_offset', TRUE);

		return (('' !== $user_gmt_offset) ? $user_gmt_offset : get_option('gmt_offset'));
	}

	/**
	 * Returns the profile_display_name_as setting
	 * @return int
	 */
	public function get_display_name_as()
	{
		$display_name_as = get_user_meta($this->id, 'profile_display_name_as', TRUE);
		return (('' !== $display_name_as) ? $display_name_as : PeepSo::get_option('system_display_name_style', 'username'));
	}
}

// EOF

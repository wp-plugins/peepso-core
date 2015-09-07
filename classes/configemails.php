<?php

class PeepSoConfigEmails
{
	private static $_instance = NULL;

	private $aEmails = array();

	private function __construct()
	{
		add_action('peepso_admin_config_save-email', array(&$this, 'save_config'));
		add_action('peepso_admin_config_tab-email', array(&$this, 'output_form'));
		
		$this->aEmails = array(
			'email_new_user' => array(
				'title' => __('New User Email', 'peepso'),
				'description' => __('This will be sent to new users upon completion of the registration process', 'peepso')),
			'email_new_user_no_approval' => array(
				'title' => __('New User Email (No Account Verification)', 'peepso'),
				'description' => __('This will be sent to new users upon completion of the registration process when Account Verification is disabled', 'peepso')),
			'email_user_approved' => array(
				'title' => __('Account Approved', 'peepso'),
				'description' => __('This will be sent when an Admin approves a user registration.', 'peepso')),
			'email_activity_notice' => array(
				'title' => __('Activity Notice', 'peepso'),
				'description' => __('This will be sent when someone interacts with a user\'s Activity Stream', 'peepso')),
			'email_like_post' => array(
				'title' => __('Like Post', 'peepso'),
				'description' => __('This will be sent when a user "likes" another user\'s post', 'peepso')),
			'email_register' => array(
				'title' => __('Registration', 'peepso'),
				'description' => __('This will be sent when a user registers on the site', 'peepso')),
			'email_user_comment' => array(
				'title' => __('User Comment', 'peepso'),
				'description' => __('This will be sent to a post owner when another user comments on the post', 'peepso')),
			'email_wall_post' => array(
				'title' => __('Wall Post', 'peepso'),
				'description' => __('This will be sent when a user writes on another user\'s wall', 'peepso')),
			'email_welcome' => array(
				'title' => __('Welcome Message', 'peepso'),
				'description' => __('This will be sent when a user finalizes their registration', 'peepso')),
			'email_password_recover' => array(
				'title' => __('Recover Password', 'peepso'),
				'description' => __('This will be sent when a user requests a password recovery', 'peepso')),
			'email_password_changed' => array(
				'title' => __('Password Changed', 'peepso'),
				'description' => __('This will be sent when a user changes their password after recovery', 'peepso')),			
			'email_like_profile' => array(
				'title' => __('Like Profile', 'peepso'),
				'description' => __('This will be sent when a user "likes" another user\'s profile', 'peepso')),
			'email_new_user_registration' => array(
				'title' => __('New User Registration', 'peepso'),
				'description' => __('This will be sent to admin user when new user needs approval', 'peepso')),
		);
	}

	// Outputs the config form
	public function output_form()
	{
		if (isset($_REQUEST['peepso-config-nonce']) && 
			wp_verify_nonce($_REQUEST['peepso-config-nonce'], 'peepso-config-nonce')) {
			do_action('peepso_admin_config_save');
		}
		
		$adm = PeepSoAdmin::get_instance();
		$adm->admin_notices();


		$cfg = PeepSoConfig::get_instance();
		$cfg->render_tabs();

		echo '<form action="', admin_url('admin.php?page=peepso_config&tab=email'), '" method="post" >', PHP_EOL;
		echo '<input type="hidden" name="peepso-email-nonce" value="', wp_create_nonce('peepso-email-nonce'), '"/>', PHP_EOL;

		echo '<div id="tokens" class="meta-box-sortables col-xs-4 col-sm-4" style="float:right; margin-right:0">', PHP_EOL;
		echo	'<br />', PHP_EOL;
		echo	'<div class="meta-box-sortables">', PHP_EOL;
		echo		'<div class="postbox">', PHP_EOL;
//		echo			'<div class="handlediv" title="Click to toggle"><br></div>', PHP_EOL;
		echo			'<h3 class="hndle"><span>&nbsp;', __('Allowed Tokens:', 'peepso'), '</span></h3>', PHP_EOL;
		echo			'<div class="inside">', PHP_EOL;
		echo '<p>', __('The following tokens can be used within the content of emails:', 'peepso'), '</p>';
		echo '{date} - ', __('Current date in the format that WordPress displays dates.', 'peepso'), '<br/>';
		echo '{datetime} - ', __('Current date and time in the format that WordPress displays dates with time.', 'peepso'), '<br/>';
		echo '{sitename} - ', __('Name of your site from the WordPress title configuration.', 'peepso'), '<br/>';
		echo '{siteurl} - ', __('URL of your site.', 'peepso'), '<br/>';
		echo '{unsubscribeurl} - ', __('URL to receiving user\'s Alert Configuration page.', 'peepso'), '<br/>';
		echo '{year} - ', __('The current four digit year.', 'peepso'), '<br/>';
		echo '{permalink} - ', __('Link to the post, comment or other item referenced; context specific.', 'peepso'), '<br/>';

		echo '&nbsp;<br/>', __('These are referring to the user causing the alert, such as "{fromlogin} liked your post...":', 'peepso'), '<br/>';
		echo '{fromemail} - ', __('Message sender\'s email address.', 'peepso'), '<br/>';
		echo '{fromfullname} - ', __('Message sender\'s full name.', 'peepso'), '<br/>';
		echo '{fromfirstname} - ', __('Message sender\'s first name.', 'peepso'), '<br/>';
		echo '{fromlastname} - ', __('Message sender\'s last name.', 'peepso'), '<br/>';
		echo '{fromlogin} - ', __('Message sender\'s username.', 'peepso'), '<br/>';

		echo '&nbsp;<br/>', __('These are referring to the receiving user on all messages, such as "Welcome {userfirstname}...":', 'peepso'), '<br/>';
		echo '{useremail} - ', __('Message recipient\'s email address.', 'peepso'), '<br/>';
		echo '{userfullname} - Message recipient\'s full name.<br/>';
		echo '{userfirstname} - Message recipient\'s first name.<br/>';
		echo '{userlastname} - Message recipient\'s last name.<br/>';
		echo '{userlogin} - Message recipient\'s username.<br/>';

		echo			'</div>', PHP_EOL;
		echo		'</div>', PHP_EOL;
		echo	'</div>', PHP_EOL;
		echo '</div>';

		echo '<div id="peepso" class="col-xs-8 col-sm-8">', PHP_EOL;
		
		echo '<div id="left-sortables" class="meta-box-sortables">', PHP_EOL;

////////
		$emails = apply_filters('peepso_config_email_messages', $this->aEmails);
		foreach ($emails as $name => $aData) {
			echo '<div class="postbox">', PHP_EOL;

			echo '<div class="handlediv" title="Click to toggle"><br></div>', PHP_EOL;
			echo '<h3 class="hndle"><span>', $aData['title'], '</span></h3>', PHP_EOL;
			echo	'<div class="inside">', PHP_EOL;
			echo		'<div class="form-group">', PHP_EOL;
			echo			'<p>', $aData['description'], '</p>', PHP_EOL;
			echo			'<label id="', $name, '-label" for="', $name, '" class="form-label  control-label col-sm-3">', $aData['title'], ':</label>', PHP_EOL;
			echo			'<div class="form-field controls col-sm-8">', PHP_EOL;

			$data = 'Email contents';
			$data = get_option('peepso_' . $name, $data);

			echo			'<div xclass="col-sm-7">', PHP_EOL;
			echo				'<textarea name="', $name, '" class="email-content">', $data, '</textarea>', PHP_EOL;
			echo				'<span class="lbl"></span>', PHP_EOL;
			echo			'</div>', PHP_EOL;

			echo		'</div>', PHP_EOL;		// .form-group
			echo	'</div>', PHP_EOL;			// .inside
			echo	'<div class="clearfix"></div>', PHP_EOL;
			echo '</div>', PHP_EOL;				// .handlediv
			echo '</div>', PHP_EOL;				// .postbox
		}
//

		echo '<div width="100%" style="display:block; clear:both; text-align:center">', PHP_EOL;
		echo '<button name="save-email" class="btn btn-info" type="submit">';
		echo	'<i class="ace-icon fa fa-check bigger-110"></i>';
		echo	'Save';
		echo '</button>', PHP_EOL;
		echo '</div>', PHP_EOL;

//		echo '</div>', PHP_EOL;		// .postbox
////////

		echo '</div>', PHP_EOL;		// .meta-box-sortables

		echo '</div>', PHP_EOL;		// outer column
		echo '</form>', PHP_EOL;
	}

	// Return the singleton instance of PeepSoConfigEmails
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}

	// Saves config to options
	public function save_config()
	{
PeepSo::log(__METHOD__.'() called');
PeepSo::log('  POST: ' . var_export($_POST, TRUE));

		$input = new PeepSoInput();
		$updated = FALSE;

		if (isset($_POST['save-email'])) {
			foreach (array_keys($this->aEmails) as $email_name) {
				$contents = $input->post_raw($email_name);
				$contents = PeepSoSecurity::strip_content($contents);

PeepSo::log('  name=' . $email_name . ' contents=' . $contents);
				update_option('peepso_' . $email_name, $contents);
				$updated = TRUE;
			}
		}
PeepSo::log('-done');

		if ($updated) {
			$adm = PeepSoAdmin::get_instance();
			$adm->add_notice(__('Email contents updated.', 'peepso'), 'note');
		}
	}
}

// EOF

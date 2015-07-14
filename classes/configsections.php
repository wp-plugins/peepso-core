<?php

class PeepSoConfigSections extends PeepSoConfigSectionAbstract
{
	const SITE_ALERTS_SECTION = 'site_alerts_';

	// Builds the groups array
	public function register_config_groups()
	{
        if (NULL !== ($license_group = $this->_get_license_group()))
            $this->config_groups[] = $license_group;
		$this->config_groups[] = $this->_get_system_group();
		$this->config_groups[] = $this->_get_alerts_group();
		$this->config_groups[] = $this->_get_report_group();
		$this->config_groups[] = $this->_get_frontpage_group();
		$this->config_groups[] = $this->_get_socialbookmark_group();
//		$this->config_groups[] = $this->_get_messaging_group();
//		$this->config_groups[] = $this->_get_wall_group();
//		$this->config_groups[] = $this->_get_timezone_group();
		$this->config_groups[] = $this->_get_email_group();
		$this->config_groups[] = $this->_get_status_group();
//		$this->config_groups[] = $this->_get_profile_group();
//		$this->config_groups[] = $this->_get_filtering_group();
		$this->config_groups[] = $this->_get_uninstall_group();
//		$this->config_groups[] = $this->_get_advancedsearch_group();
		$this->config_groups[] = $this->_get_cronjob_group();
		$this->config_groups[] = $this->_get_registration_group();
		$this->config_groups[] = $this->_get_activity_group();
		$this->config_groups[] = $this->_get_likes_group();

		// check to see if any licenses need to be installed before adding to config groups

	}

	/**
	 * Returns field groups for the System section
	 * @return array An array of System section with group of fields used to configure the behavior of PeepSo such as enable/disable system logging
	 */
	private function _get_system_group()
	{
		return (array(
			'name' => 'system',
			'title' => __('System', 'peepso'),
			'context' => 'left',
			'description' => __('These settings are used to control system settings.', 'peepso'),
			'fields' => array(
				array(
					'name' => 'system_enable_logging',
					'label' => __('Enable Logging', 'peepso'),
					'type' => 'yesno_switch',
					'int' => TRUE,
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'value' => intval(PeepSo::get_option('system_enable_logging', 0)),
				),
				array(
					'name' => 'system_show_peepso_link',
					'label' => __('Show "Powered by PeepSo" link', 'peepso'),
					'type' => 'yesno_switch',
					'int' => TRUE,
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'value' => intval(PeepSo::get_option('system_show_peepso_link', 0)),
				),
				array(
					'name' => 'system_display_name_style',
					'label' => __('Display Name Style', 'peepso'),
					'type' => 'select',
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'options' => array(
						'real_name' => __('Display Real Names', 'peepso'),
						'username' => __('Display Usernames', 'peepso'),
					),
					'value' => PeepSo::get_option('system_display_name_style', 'username'),
				),
				array(
					'name' => 'system_override_name',
					'label' => __('Allow User to Override Name Setting', 'peepso'),
					'type' => 'yesno_switch',
					'int' => TRUE,
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'value' => intval(PeepSo::get_option('system_override_name', 0)),
				),
			),
		));
	}

	/**
	 * Returns field groups for the Reportings section
	 * @return array An array of Reporting section with group of fields used to enable/disable reporting and its configuration settings
	 */
	private function _get_report_group()
	{
		return (array(
			'name' => 'report',
			'title' => __('Reporting', 'peepso'),
			'context' => 'left',
			'description' => __('These settings are used to control users\' ability to report inappropriate content.', 'peepso'),
			'fields' => array(
				array(
					'name' => 'site_reporting_enable',
					'label' => __('Enable Reportings', 'peepso'),
					'type' => 'yesno_switch',
					'int' => TRUE,
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'value' => intval(PeepSo::get_option('site_reporting_enable', 0)),
				),
				array(
					'name' => 'site_reporting_types',
					'label' => __('Predefined Text (Separated by a New Line)', 'peepso'),
					'type' => 'textarea',
					'raw' => TRUE,
					'multiple' => TRUE,
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'value' => PeepSo::get_option('site_reporting_types')
				),
			),
		));
	}

	/**
	 * Returns field groups for the Frontpage section
	 * @return array An array of Front Page Settings section with group of fields used to control the appearance of the Front Page site.
	 */
	private function _get_frontpage_group()
	{
		$section = 'site_frontpage_';

		/*
		$title = array(
			'name' => $section . 'title',
			'label' => __('Front Page Title', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'title')
		);
		*/

		$options = array(
			'activity' => __('Activity Stream', 'peepso'),
			'profile' => __('Profile', 'peepso'),
		);
		$options = apply_filters('peepso_admin_login_redirect_options', $options);

		$redirectlogin = array(
			'name' => $section . 'redirectlogin',
			'label' => __('Redirect Successful Logins', 'peepso'),
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'type' => 'select',
			'options' => $options,
			'value' => PeepSo::get_option($section . 'redirectlogin')
		);

		$redirectlogout = array(
			'name' => $section . 'redirectlogout',
			'label' => __('Redirect Successful Logouts', 'peepso'),
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'type' => 'select',
			'options' => array(
				'profile' => __('Profile', 'peepso'),
				'frontpage' => __('Frontpage', 'peepso'),
				'videos' => __('Videos', 'peepso'),
				'photos' => __('Photos', 'peepso'),
				'friends' => __('Friends', 'peepso'),
				'apps' => __('Applications', 'peepso'),
				'inbox' => __('Inbox', 'peepso'),
				'groups' => __('Groups', 'peepso'),
				'events' => __('Events', 'peepso'),
			),
			'value' => PeepSo::get_option($section . 'redirectlogout')
		);

		return (array(
			'name' => 'frontpage',
			'title' => __('Front Page Settings', 'peepso'),
			'description' => __('These settings control the appearance of the Front Page of the site.', 'peepso'),
			'fields' => array(/*$title,*/ $redirectlogin),
			'context' => 'right'			
		));
	}

	/**
	 * Returns field groups for the Social Bookmark section
	 * @return array An array of Social Sharing section with group of fields used to control the social sharing features
	 */
	private function _get_socialbookmark_group()
	{
		$section = 'site_socialsharing_';

		$enable = array(
			'name' => $section . 'enable',
			'label' => __('Enable Social Sharing', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'enable'))
		);

		$shareemail = array(
			'name' => $section . 'shareemail',
			'label' => __('Share via Email', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'shareemail'))
		);

		return (array(
			'name' => 'socialsharing',
			'title' => __('Social Sharing', 'peepso'),
			'description' => __('These settings control the behavior of Social Sharing features.', 'peepso'),
			'fields' => array($enable),
			'context' => 'right'			
		));
	}

	/**
	 * Returns field groups for the Messaging section
	 * @return array An array of Messaging section to enable or disable messaging feature
	 */
	private function _get_messaging_group()
	{
		$section = 'site_messaging_';

		$enable = array(
			'name' => $section . 'enable',
			'label' => __('Enable Messaging', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'enable'))
		);

		return (array(
			'name' => 'messaging',
			'title' => __('Messaging', 'peepso'),
			'fields' => array($enable),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Walls section
	 * @return array An array of Walls section with group of fields used to configure the behavior of Walls feature
	 */
	private function _get_wall_group()
	{
		$section = 'site_walls_';

		$editcomment = array(
			'name' => $section . 'editcomment',
			'label' => __('Comment Editing', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'editcomment'))
		);

		$friendswrite = array(
			'name' => $section . 'friendswrite',
			'label' => __('Only Friends can Write on Profile.', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'friendswrite'))
		);

		$videofriendscomment = array(
			'name' => $section . 'videofriendscomment',
			'label' => __('Only Friends can Comment on Videos', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'videofriendscomment'))
		);

		$photofriendscomment = array(
			'name' => $section . 'photofriendscomment',
			'label' => __('Only friends can Comment on Photos', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'photofriendscomment'))
		);

		$groupsmemberswrite = array(
			'name' => $section . 'groupsmemberswrite',
			'label' => __('Only Members can Write in Groups', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'groupsmemberswrite'))
		);

		$eventsresponderswrite = array(
			'name' => $section . 'eventsresponderswrite',
			'label' => __('Only Responders can Write in Events', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'eventsresponderswrite'))
		);

		$autorefresh = array(
			'name' => $section . 'autorefresh',
			'label' => __('Auto Refresh', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'autorefresh'))
		);

		$refreshinterval = array(
			'name' => $section . 'refreshinterval',
			'label' => __('Interval Time to Check for Latest Status', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'refreshinterval')),
			'validation' => array('required', 'numeric')
		);

		return (array(
			'name' => 'walls',
			'title' => __('Walls', 'peepso'),
			'fields' => array(
				$editcomment,
				$friendswrite,
				$videofriendscomment,
				$photofriendscomment,
				$groupsmemberswrite,
				$eventsresponderswrite,
				$autorefresh,
				$refreshinterval
			),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Timezone section
	 * @return array An array of Time Offset (Daylight Savings Time) section to set the Time Offset / Daylight Savings Time
	 */
	private function _get_timezone_group()
	{
		$section = 'site_timezone_';

		$dstoffset = array(
			'name' => $section . 'dstoffset',
			'label' => __('Time Offset (Daylight Savings Time)', 'peepso'),
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'type' => 'select',
			'options' => array(
				'-4' => '-4',
				'-3' => '-3',
				'-2' => '-2',
				'-1' => '-1',
				'0' => '0',
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
			),
			'int' => TRUE,
			'value' => intval(PeepSo::get_option($section . 'dstoffset'))
		);

		return (array(
			'name' => 'time_offset',
			'title' => __('Time Offset (Daylight Savings Time)', 'peepso'),
			'fields' => array($dstoffset),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Email section
	 * @return array An array of Emails section with group of fields used to control the appearance of emails sent by PeepSo
	 */
	private function _get_email_group()
	{
		$section = 'site_emails_';

		$sender = array(
			'name' => $section . 'sender',
			'label' => __('Email sender', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'sender', get_option('blogname')),
			'validation' => array('required')
		);

		$admin_email = array(
			'name' => $section . 'admin_email',
			'label' => __('Admin Email', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'admin_email', get_option('admin_email')),
			'validation' => array('required', 'email')
		);

		$html = array(
			'name' => $section . 'html',
			'label' => __('HTML Emails', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'html'))
		);

		$copyright = array(
			'name' => $section . 'copyright',
			'label' => __('Copyright Text', 'peepso'),
			'type' => 'textarea',
			'raw' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'copyright')
		);

		$process_count = array(
			'name' => $section . 'process_count',
			'label' => __('Number of mails to process per run', 'peepso'),
			'type' => 'text',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'process_count')
		);

		return (array(
			'name' => 'emails',
			'title' => __('Emails', 'peepso'),
			'description' => __('These settings control the appearance of emails sent by PeepSo.', 'peepso'),
			'fields' => array($sender, $admin_email, $html, $copyright, $process_count),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Status section
	 * @return array An array of Status section with group of fields used to control the behavior of the PostBox used for creating content
	 */
	private function _get_status_group()
	{
		$section = 'site_status_';

		$limit = array(
			'name' => $section . 'limit',
			'label' => __('Maximum size of Post', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'limit')),
			'validation' => array('numeric')
		);

		return (array(
			'name' => 'status',
			'title' => __('Status', 'peepso'),
			'description' => __('These settings control the behavior of the PostBox used for creating content.', 'peepso'),
			'fields' => array($limit),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Profiles section
	 * @return array An array of Multiple Profiles section to enable/disable multiple profiles
	 */
	private function _get_profile_group()
	{
		$section = 'site_profiles_';

		$enablemultiple = array(
			'name' => $section . 'enablemultiple',
			'label' => __('Enable Multiple Profiles', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'enablemultiple'))
		);

		return (array(
			'name' => 'multiple_profiles',
			'title' => __('Multiple Profiles', 'peepso'),
			'fields' => array($enablemultiple),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Filtering section
	 * @return array An array of Friends Filtering section to enable/disable alphabet filtering
	 */
	private function _get_filtering_group()
	{
		$section = 'site_filtering_';

		$alpha = array(
			'name' => $section . 'alpha',
			'label' => __('Enable Alphabet Filtering', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'alpha'))
		);

		return (array(
			'name' => 'friends_filtering',
			'title' => __('Friends Filtering', 'peepso'),
			'fields' => array($alpha),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Uninstall section
	 * @return array An array of PeepSo Uninstall section with group of fields used to control the behavior of PeepSo when uninstalling / deactivating
	 */
	private function _get_uninstall_group()
	{
		$delposts = array(
			'name' => 'delete_post_data',
			'label' => __('Delete Post and Comment data', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8 danger',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option('delete_post_data'))
		);

		$deldata = array(
			'name' => 'delete_on_deactivate',
			'label' => __('Delete all data and settings', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8 danger',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option('delete_on_deactivate'))
		);

		return (array(
			'name' => 'peepso_uninstall',
			'description' => __('Control behavior of PeepSo when uninstalling / deactivating', 'peepso'),
			'summary' => __('When set to "YES", all <em>PeepSo</em> data will be deleted upon plugin Uninstall (but not Deactivation).<br/>Once deleted, <u>all data is lost</u> and cannot be recovered.', 'peepso'),
			'title' => __('PeepSo Uninstall', 'peepso'),
			'fields' => array($delposts, $deldata),
			'context' => 'right'
		));
	}

	/**
	 * Returns field groups for the Advanced Search section
	 * @return array An array of Advanced Search section with group of fields used to configure the behavior of PeepSo search
	 */
	private function _get_advancedsearch_group()
	{
		$section = 'site_advsearch_';

		$allowguest = array(
			'name' => $section . 'allowguest',
			'label' => __('Allow Guests to Perform Advanced Search', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'allowguest'))
		);

		$email = array(
			'name' => $section . 'email',
			'label' => __('Email Search', 'peepso'),
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'type' => 'select',
			'options' => array(
				'0' => __('Allowed', 'peepso'),
				'2' => __('Disallowed', 'peepso'),
				'1' => __('Respect Privacy', 'peepso'),
			),
			'int' => TRUE,
			'value' => intval(PeepSo::get_option($section . 'email'))
		);

		return (array(
			'name' => 'advanced_search',
			'title' => __('Advanced Search', 'peepso'),
			'fields' => array($allowguest, $email),
			'context' => 'left'
		));
	}

	/**
	 * Returns field groups for the Cron Job section
	 * @return array An array of Remove Old Content (Cron Job) section with group of fields used to control when content will be deleted
	 */
	private function _get_cronjob_group()
	{
		$section = 'site_contentpurge_';

//		$maxdays = array(
//			'name' => $section . 'maxdays',
//			'label' => __('Max days', 'peepso'),
//			'type' => 'text',
//			'field_wrapper_class' => 'controls col-sm-8',
//			'field_label_class' => 'control-label col-sm-4',
//			'value' => intval(PeepSo::get_option($section . 'maxdays')),
//			'validation' => array('numeric')
//		);

		$purge_after_days = array(
			'name' => $section . 'purge_after_days',
			'label' => __('Purge Content after how many days', 'peepso'),
			'type' => 'text',
			'descript' => __('Number of days to keep Activity Stream items before deleting', 'peepso'),
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'purge_after_days')),
			'validation' => array('numeric')
		);

//		$unarchivelimit = array(
//			'name' => $section . 'unarchivelimit',
//			'label' => __('Unarchive Limit', 'peepso'),
//			'type' => 'text',
//			'field_wrapper_class' => 'controls col-sm-8',
//			'field_label_class' => 'control-label col-sm-4',
//			'value' => intval(PeepSo::get_option($section . 'unarchivelimit')),
//			'validation' => array('numeric')
//		);
		
		return (array(
			'name' => 'cronjobs',
			'title' => __('Remove Old Content', 'peepso'),
			'description' => __('This controls when content will be deleted.', 'peepso'),
			'fields' => array($purge_after_days),
			'context' => 'left',
			'summary' => __('Set the number of days after which Activity Stream content will be deleted.', 'peepso'),
		));
	}

	/**
	 * Returns field groups for the Registration section
	 * @return array An array of Registration section with group of fields used to customize the Registration process
	 */
	private function _get_registration_group()
	{
		$section = 'site_registration_';

		$enableverif = array(
			'name' => $section . 'enableverification',
			'label' => __('Enable Account Verification', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'enableverification')),
		);

		$enableterms = array(
			'name' => $section . 'enableterms',
			'label' => __('Enable Terms &amp; Conditions', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'enableterms'))
		);

		$terms = array(
			'name' => $section . 'terms',
			'label' => __('Terms &amp; Conditions', 'peepso'),
			'type' => 'textarea',
			'raw' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'terms')
		);

		$enablesecure = array(
			'name' => $section . 'enable_ssl',
			'label' => __('Enable Secure Mode for Registration', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'enable_ssl'))
		);

		$allowdelete = array			(
			'name' => $section . 'allowdelete',
			'label' => __('Allow Profile Deletion', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'allowdelete'))
		);

		$header = array(
			'name' => $section . 'header',
			'label' => __('Callout Header', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'header')
		);

		$callout = array(
			'name' => $section . 'callout',
			'label' => __('Callout Text', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'callout')
		);

		$link = array(
			'name' => $section . 'buttontext',
			'label' => __('Button Text', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'buttontext')
		);

		return (array(
			'name' => 'registration',
			'title' => __('Registration', 'peepso'),
			'description' => __('These settings allow you to customize the Registration process.', 'peepso'),
			'fields' => array(
				$enableterms,
				$terms,
				$enablesecure,
				$allowdelete,
				$header,
				$callout,
				$link,
				$enableverif
			),
			'context' => 'left',
			'summary' => __('Setting "Enable Account Verification" to YES will send verification emails to new users when they Register. An Administrator will then need to approve the user before they can use the site. On approval, users will receive another email letting them know they can use the site.<br />Setting "Enable Account Verification" to NO, users will be automatically validated upon registration and can use the site immediately.', 'peepso'),
		));
	}

	/**
	 * Returns field groups for the Activity section
	 * @return array An array of Activity section with group of fields used to control how many posts and comments will be displayed in the Activity Stream
	 */
	private function _get_activity_group()
	{
		$section = 'site_activity_';

		$posts = array(
			'name' => $section . 'posts',
			'label' => __('Number of Posts', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'posts'),
			'validation' => array('required', 'numeric')
		);

		$comments = array(
			'name' => $section . 'comments',
			'label' => __('Number of Comments to display', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'comments'),
			'validation' => array('required', 'numeric')
		);

		$limit_comments = array(
			'name' => $section . 'limit_comments',
			'label' => __('Limit Number of Comments per Post', 'peepso'),
			'descript' => __('Select "No" for unlimited comments', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'limit_comments'))
		);

		$comments_allowed = array(
			'name' => $section . 'comments_allowed',
			'label' => __('Maximum number of Comments allowed per post', 'peepso'),
			'type' => 'text',
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => PeepSo::get_option($section . 'comments_allowed', 50),
			'validation' => array('required', 'numeric'),
		);

		$links = array(
			'name' => $section . 'open_links_in_new_tab',
			'label' => __('Open links in new tab', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'open_links_in_new_tab'))
		);

		$hide_stream = array(
			'name' => $section . 'hide_stream_from_guest',
			'label' => __('Hide Activity Stream from Non-logged in Users', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'hide_stream_from_guest'))
		);

		return (array(
			'name' => 'activity',
			'title' => __('Activity', 'peepso'),
			'description' => __('These settings control how many posts and comments will be displayed in the Activity Stream.', 'peepso'),
			'fields' => array($posts, $comments, $limit_comments, $comments_allowed, $links, $hide_stream),
			'context' => 'left'
		));
	}

	/**
	 * Returns field groups for the Likes section
	 * @return array An array of Likes Rating section with group of fields used to control what content the "Like" feature is enabled on
	 */
	private function _get_likes_group()
	{
		$section = 'site_likes_';

		$groups = array(
			'name' => $section . 'groups',
			'label' => __('Groups', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'groups'))
		);

		$events = array(
			'name' => $section . 'events',
			'label' => __('Events', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'events'))
		);

		$profile = array(
			'name' => $section . 'profile',
			'label' => __('Profile', 'peepso'),
			'type' => 'yesno_switch',
			'int' => TRUE,
			'field_wrapper_class' => 'controls col-sm-8',
			'field_label_class' => 'control-label col-sm-4',
			'value' => intval(PeepSo::get_option($section . 'profile'))
		);

		$fields = array($profile);
		$fields = apply_filters('peepso_admin_like_settings', $fields);

		return (array(
			'name' => 'likes',
			'title' => __('Likes Rating', 'peepso'),
			'description' => __('These settings control what content the "Like" feature is enabled on.', 'peepso'),
			'fields' => $fields,
			'context' => 'left'
		));
	}

	/**
	 * Returns field groups for the Likes section
	 * @return array An array of Likes Rating section with group of fields used to control what content the "Like" feature is enabled on
	 */
	private function _get_license_group()
	{
		$section = 'site_license_';

		$products = apply_filters('peepso_license_config', array());
		
		if (0 === count($products))
			return (NULL);

		$meta = array(
			'name' => 'license',
			'title' => __('License Key Configuration', 'peepso'),
			'description' =>
                '<a name="licensing"></a>'
                .__('This is where you configure the license keys for each PeepSo add-on. You can find your license numbers on <a target="_blank" href="http://peepso.com/orders/">My Orders</a> page. Please copy them here and click SAVE at the bottom of this page.', 'peepso'),
			'fields' => array(),
			'context' => 'right',
            'summary' => '',

        );



		foreach ($products as $prod) {

            $loading = ' <span class="license_status_check" id="'.$prod['plugin_slug'].'" data-plugin-name="'.$prod['plugin_edd'].'"><img src="images/loading.gif"></span>';
            $active = '<span class="dashicons dashicons-yes"></span>' . __(' ', 'peepso');
            $inactive = '<span class="dashicons dashicons-no" style="color:red"></span>' . __(' ', 'peepso');

			$option = $section . $prod['plugin_slug'];
			$status = PeepSoLicense::check_license($prod['plugin_name'], $prod['plugin_slug']);
			$setting = array(
				'name' => $option,
				'label' => $prod['plugin_name'] . ' ' . $prod['plugin_version'],
				'type' => 'text',
				'field_wrapper_class' => 'controls col-sm-8',
				'field_label_class' => 'control-label col-sm-4',
				'value' => PeepSo::get_option($option),
				//'suffixhtml' => $status ? $active : $inactive,
			);

            $setting['label'] = $setting['label'] . $loading;
			$meta['fields'][] = $setting;
		}

		return ($meta);
	}

	/**
	 * Returns field groups for the Notifications and Email Alerts section
	 * @return array An array of Notifications and Email Alerts section with group of fields used to allow user to override alerts settings
	 */
	private function _get_alerts_group()
	{
		$profile = PeepSoProfile::get_instance();
		$alerts = $profile->get_alerts_definition();
		$fields = array();
		foreach ($alerts as $key => $value) {
			if (!isset($value['items']))
				continue;
			foreach ($value['items'] as $item) {
				$field_name = self::SITE_ALERTS_SECTION . $item['setting'];
				$fields[] = array(
					'name' => $field_name,
					'label' => $item['label'],
					'type' => 'yesno_switch',
					'int' => TRUE,
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'value' => intval(PeepSo::get_option($field_name, 1)),
				);
			}
		}

		return (array(
			'name' => 'alerts',
			'title' => __('Notifications and Email Alerts', 'peepso'),
			'context' => 'right',
			'description' => __('These settings control what Alerts are created by PeepSo.', 'peepso'),
			'fields' => $fields,
			'summary' => __('Setting these to "YES" will cause PeepSo to generate an email or alert for the specific event.<br/>Setting these to "NO" means no Alert will be generated.<br/>Users can further control which Alerts they want to receive. For example, setting "Profile Likes" to "YES" means that PeepSo will generate a message for these Alerts, but a user can choose to ignore these Alerts.', 'peepso'),
		));
	}
}

// EOF

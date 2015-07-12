<?php

class PeepSoConfigSectionFilesystem extends PeepSoConfigSectionAbstract
{
	// Builds the groups array
	public function register_config_groups()
	{
		$this->config_groups[] = array(
			'name' => 'filesystem',
			'title' => __('File System', 'peepso'),
			'context' => 'full',
			'description' => __('PeepSo allows users to upload images that are stored on your server. Enter a location where these files are to be stored.<br/>This must be a directory that is writable by your web server and and is accessible via the web. If the directory specified does not exist, it will be created.', 'peepso'),
			'fields' => array(
				array(
					'name' => 'site_peepso_dir',
					'label' => __('Uploads Directory', 'peepso'),
					'type' => 'text',
					'field_wrapper_class' => 'controls col-sm-8',
					'field_label_class' => 'control-label col-sm-4',
					'class' => 'col-xs-10 col-sm-5',
					'value' => PeepSo::get_option('site_peepso_dir', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso'),
					'validation' => array('required', 'custom'),
					'validation_options' => array(
						'error_message' => __('Can not write to directory', 'peepso'),
						'function' => array($this, 'check_wp_filesystem')
					)
				),
			),
		);
	}

	/**
	 * Checks if the directory has been created, if not use WP_Filesystem to create the directories.
	 * @param  string $value The peepso upload directory
	 * @return boolean
	 */
	public function check_wp_filesystem($value)
	{
		$form_fields = array('site_peepso_dir');
		$url = wp_nonce_url('admin.php?page=peepso_config&tab=filesystem', 'peepso-config-nonce', 'peepso-config-nonce');

		if (FALSE === ($creds = request_filesystem_credentials($url, '', false, false, $form_fields))) {
		    return FALSE;
		}

		// now we have some credentials, try to get the wp_filesystem running
		if (!WP_Filesystem($creds)) {
		    // our credentials were no good, ask the user for them again
		    request_filesystem_credentials($url, '', true, false, $form_fields);
		    return FALSE;
		}

		global $wp_filesystem;

		if (!$wp_filesystem->is_dir($value) || !$wp_filesystem->is_dir($value . DIRECTORY_SEPARATOR . 'users')) {
			return $wp_filesystem->mkdir($value) && $wp_filesystem->mkdir($value . DIRECTORY_SEPARATOR . 'users');
		}

		return $wp_filesystem->is_writable($value);
	}
}
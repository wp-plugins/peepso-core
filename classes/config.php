<?php

class PeepSoConfig
{
	private static $instance = NULL;

	public static $slug = 'peepso_config';
	public $config_object = NULL;

	public $sections = NULL;
	public $form = NULL;

	private $tab_count = 0;
	private $curtab = NULL;

	public function __construct()
	{
	}

	public function render()
	{
		wp_enqueue_script('peepso-admin-config');

		$input = new PeepSoInput();
		$tab = $this->curtab = $input->get('tab', 'site');

		$options = PeepSoConfigSettings::get_instance();

		add_action('peepso_admin_config_save', array(&$this, 'config_save'));
		add_filter('peepso_render_form_field_type-radio', array(&$this, 'render_admin_radio_field'), 10, 2);

		// handle tabs within config settings page
		$curtab = $input->get('tab');

		$aTab = $this->get_tab($tab);

		if (!empty($curtab) && !isset($aTab['function'])) {
			switch ($curtab)
			{
			case 'email':
				PeepSoConfigEmails::get_instance();
				break;
			}

			if ('POST' === $_SERVER['REQUEST_METHOD'] && 
				wp_verify_nonce($input->post('peepso-' . $curtab . '-nonce'), 'peepso-' . $curtab . '-nonce')) 
				do_action('peepso_admin_config_save-' . $curtab);

			do_action('peepso_admin_config_tab-' . $curtab);
			return;
		}

		$aTab = $this->get_tab($tab);
		$this->config_object = new $aTab['function']();

		if (!($this->config_object instanceOf PeepSoConfigSectionAbstract)) {
			throw new Exception(__('Class must be instance of PeepSoConfigSectionAbstract', 'peepso'), 1);
		}

		$filter = 'peepso_admin_register_config_group-' . $aTab['tab'];

		$this->config_object->register_config_groups();
		$this->config_object->config_groups = apply_filters($filter, $this->config_object->config_groups);
		// Call build_form after all config_groups have been defined
		$this->config_object->build_form();

		add_filter('peepso_admin_config_form_open', array(&$this, 'set_form_args'));

		$this->prepare_metaboxes();

		if (isset($_REQUEST['peepso-config-nonce']) && 
			wp_verify_nonce($_REQUEST['peepso-config-nonce'], 'peepso-config-nonce')) {
			do_action('peepso_admin_config_save');
		}

		echo '<h2><img src="', PeepSo::get_asset('images/logo.png'), '" width="150" />';
		echo ' v', PeepSo::PLUGIN_VERSION, ' ', __('Configuration Settings', 'peepso'), '</h2>', PHP_EOL;

		$this->render_tabs();

		PeepSoTemplate::set_template_dir('admin');
		PeepSoTemplate::exec_template(
			'config',
			'options',
			array(
				'config' => $this
			)
		);
	}

	/*
	 * Display the tabs
	 */	
	public function render_tabs()
	{
		$input = new PeepSoInput();
		$curtab = $input->get('tab', 'site');

		echo '<ul class="nav nav-tabs">', PHP_EOL;
		$tabs = $this->get_tabs();
		foreach ($tabs as $tab) {
			$config_tab = '';
			if (isset($tab['tab']) && !empty($tab['tab']))
				$config_tab = $tab['tab'];
			$activeclass = '';
			if ($curtab === $config_tab)
				$activeclass = 'active';

			echo '<li class="', $activeclass, '">', PHP_EOL;
			echo '<a href="', admin_url('admin.php?page='), self::$slug;
			if (!empty($tab['tab']))
				echo '&tab=', $tab['tab'];
			echo '"';
			if (isset($tab['description']) && !empty($tab['description']))
				echo ' title="', esc_attr($tab['description']), '"';
			echo '>';
			echo	$tab['label'];
			echo	'</a>', PHP_EOL;

			echo '</li>';
		}
		echo '</ul>', PHP_EOL;
	}


	/*
	 * Opens config form, applies filters to <form> arguments
	 *
	 * @return string The opening form tag
	 */	
	public function form_open()
	{
		$form = apply_filters('peepso_admin_config_form_open', '', 10, array());

		return $this->config_object->get_form()->form_open($form);
	}

	public function set_form_args()
	{
		return array();
	}

	/*
	 * Creates a meta box for each config group item
	 */
	public function prepare_metaboxes()
	{
		foreach ($this->config_object->config_groups as $id => $group) {
			add_meta_box(
		        'peepso_config-' . $id, //Meta box ID
		        __($group['title'], 'peepso'), //Meta box Title
		        array(&$this, 'render_field_group'), //Callback defining the plugin's innards
		        'peepso_page_peepso-config', // Screen to which to add the meta box
		        isset($group['context']) ? $group['context'] : 'full', // Context
		        'default',
				array('group' => $group)
		    );
		}
	}

	/**
	 * Metabox callback - renders the field group
	 * @param  object $post An object containing the current post.
	 * @param  array $metabox Is an array with metabox id, title, callback, and args elements. 
	 * @return void Echoes the field group.
	 */
	public function render_field_group($post, $metabox)
	{
		$group = $metabox['args']['group'];

		if (isset($group['description']))
			echo '<p style="color:gray">', $group['description'], '</p>', PHP_EOL;

		foreach ($group['fields'] as $field) {
			$field = $this->config_object->form->fields[$field['name']];
			echo '<div class="form-group';
			if (!$field['valid']) {
				echo ' has-error';
			}
			echo '">';
			echo $this->config_object->form->render_field($field);
			echo '</div>';
			echo '<div class="clearfix"></div>';
		}

		if (isset($group['summary']))
			echo '<p style="color:gray">', $group['summary'], '</p>', PHP_EOL;
	}

	// Calls get_instance() to start
	public static function init()
	{
		$config = self::get_instance();
		$config->render();
	}

	// Return an instance of PeepSoConfig
	public static function get_instance()
	{
		if (NULL === self::$instance)
			self::$instance = new self();
		return self::$instance;
	}


	/*
	 * Build a list of tabs to display at the top of config pages
	 * @return array List of tabs to display on config pages
	 */
	public function get_tabs()
	{
		$tabs = array(
			'site' => array(
				'label' => __('Config Settings', 'peepso'),
				'tab' => 'site',
				'description' => __('General configuration settings for PeepSo', 'peepso'),
				'function' => 'PeepSoConfigSections'
			),
			'email' => array(
				'label' => __('Edit Emails', 'peepso'),
				'tab' => 'email',
				'description' => __('Edit content of emails sent by PeepSo to users and Admins', 'peepso'),
			),
			'filesystem' => array(
				'label' => __('File System', 'peepso'),
				'tab' => 'filesystem',
				'description' => __('File system settings for PeepSo', 'peepso'),
				'function' => 'PeepSoConfigSectionFilesystem'
			),
		);

		$tabs = apply_filters('peepso_admin_config_tabs', $tabs);
		return ($tabs);
	}


	public static function test()
	{
		$instance = self::get_instance();
	}

	/*
	 * Get a tab based on the associative key
	 *
	 * @param string $tab The tab's associative key
	 * @return array
	 */
	public function get_tab($tab)
	{
		$tabs = $this->get_tabs();

    	if (empty($tabs[$tab])) {
	 	   	wp_redirect('wp-admin/404');
    	}

		return $tabs[$tab];
	}

	/**
	 * 'peepso_admin_config_save' action callback. Maps the $_POST data to the form fields, 
	 * calls validation and saves data once validation is passed.
	 * 
	 * @return void Sets error or success messages to the 'peepso_config_notice' option.
	 */
	public function config_save()
	{
		$this->config_object->get_form()->map_request();

		do_action('peepso_config_before_save-' . $this->curtab);

		if ($this->config_object->get_form()->validate()) {
			$this->_save();
			$type = 'note';
			$message = __('Options updated', 'peepso');
		} else {
			$type = 'error';
			$message = __('Please correct the errors below', 'peepso');
		}

		$peepso_admin = PeepSoAdmin::get_instance();
		$peepso_admin->add_notice($message, $type);
	}

	/**
	 * Rendering function for radio buttons. Called from the filter - 'peepso_render_form_field_type-radio'.
	 * @param  string $sField The field's HTML.
	 * @param  object $field  The field object.
	 * @return string The radio button HTML.
	 */
	public function render_admin_radio_field($sField, $field)
	{
		$sField = '';

		foreach ($field->options as $val => $text) {
			$sField .= '<label>';
			$sField .= '<input type="radio" name="' . $field->name . '" value="' . $val . '" ';
			if ($val === $field->value)
				$sField .= ' checked ';
			$sField .= ' />';
			$sField .= '<span class="lbl"> ' . $text . '</span>';
			$sField .= '</label>';
		}

		return $sField;
	}

	/**
	 * Loops through the form object and saves the values as options via PeepSoConfigSettings.
	 * @return void
	 */
	private function _save()
	{
		foreach ($this->config_object->get_form()->fields as $field) {
			PeepSoConfigSettings::get_instance()->set_option(
				$field['name'],
				$field['value']
			);
		}

		do_action('peepso_config_after_save-' . $this->curtab);
	}
}

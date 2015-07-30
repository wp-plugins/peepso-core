<?php

abstract class PeepSoConfigSectionAbstract
{
	public $config_groups = array();
	public $groups = array();
	public $form;

	public function __construct()
	{
		$this->form = new PeepSoForm(array('class' => 'form-horizontal'));
	}

	/**
	 * Return this sections form object
	 * @return object An instance of PeepSoForm
	 */
	public function get_form()
	{
		return $this->form;
	}

	// Builds the groups array
	abstract public function register_config_groups();

	/**
	 * Adds all fields of each group to the $this->form
	 */
	public function build_form()
	{
		foreach ($this->config_groups as &$config_group) {
			foreach ($config_group['fields'] as &$field) {
				$field = $this->form->add_field($field);
			}
		}
	}

	/**
	 * Returns all groups defined
	 * @return array 
	 */
	public function get_groups()
	{
		return $this->groups;
	}

	/**
	 * Return a single group from the groups array
	 * @param  string $group Associative key of the group as defined in the groups array
	 * @return array        The group details
	 */
	public function get_group($group)
	{
		return $this->groups[$group];
	}
}
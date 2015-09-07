<?php

class PeepSoAdminSettings
{
	private $config_settings = array(
		'site' => array(
			'reporting' => array(
				'description' => 'Control how users can report inappropriate content.',
				'fields' => array(
					'enable' => array(
						'label' => 'Enable Reportings',
						'type' => 'yesno',
					),
					'defaulttask' => array(
						'label' => 'Execute Default Task When Reach',
						'type' => 'mediumedit',
						'afterlabel' => 'Reports',
					)
				)
			)
		)
	);

}

// EOF
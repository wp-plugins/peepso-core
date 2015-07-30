<?php
//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN'))
	exit;

require_once(plugin_dir_path(__FILE__) . 'peepso.php');
require_once(plugin_dir_path(__FILE__) . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
require_once(plugin_dir_path(__FILE__) . 'install' . DIRECTORY_SEPARATOR . 'deactivate.php');

PeepSoUninstall::plugin_uninstall();
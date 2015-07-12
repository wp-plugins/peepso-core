<?php
//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN'))
	exit;

/*
@package PeepSo
@copyright (c) 2014 iJoomla, Inc. All Rights Reserved.
@author PeepSo
@uri: http://www.peepso.com
@version 0.9.0
@license GNU General Public License, version 2 (http://www.gnu.org/license/gpl-20.0.html)

The PHP code portions are distributed under the GPL license. If not otherwise stated, all
images, manuals, cascading stylesheets and included JavaScript are NOT GPL, and are released
under the iJoomla Proprietary Use License 2.0
More information at: https://peepso.com/license-agreement
*/

require_once(plugin_dir_path(__FILE__) . 'peepso.php');
require_once(plugin_dir_path(__FILE__) . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
require_once(plugin_dir_path(__FILE__) . 'install' . DIRECTORY_SEPARATOR . 'deactivate.php');

PeepSoUninstall::plugin_uninstall();
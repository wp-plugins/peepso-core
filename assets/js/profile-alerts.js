/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

/*
 * User interactions for profile alerts page
 * @package PeepSo
 * @author PeepSo
 */

function PsAlerts()
{
}

PsAlerts.prototype.init = function()
{
};

/*
 * Toggles checkboxes by group or class
 * @param {string} class_name The name of the HTML class attribute
 * @param {boolean} checked State of the checkbox
 */
PsAlerts.prototype.toggle = function(class_name, checked)
{
	jQuery("." + class_name).each(function() {
		jQuery(this).attr("checked", checked);
	});
};

var ps_alerts = new PsAlerts();

jQuery(document).ready(function() {
	ps_alerts.init();
});

// EOF

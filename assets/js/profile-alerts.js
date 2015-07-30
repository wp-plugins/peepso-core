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

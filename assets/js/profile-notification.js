/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

/*
 * User interactions for profile notifications page
 * @package PeepSo
 * @author PeepSo
 */

//$PeepSo.log("profile-notification.js");

function PsProfileNotification()
{
}

PsProfileNotification.prototype.init = function()
{
}

/*
 * Send delete request for each selected checkbox
 * @returns {undefined}
 */
PsProfileNotification.prototype.delete_selected = function()
{
//console.log("inside PsProfileNotification.delete_selected()");
//	var ckbx = jQuery("#peepso-wrap input:checkbox");
	var ckbx = jQuery("#peepso-wrap .ps-profile-notifications input:checked");
	var del_list = [];
	for (var i = 0; i < ckbx.length; i++) {
		if (true) { // "checked" === jQuery(ckbx[i]).attr("checked")) {
			var id = ckbx[i].id;
			id = id.substr(id.indexOf("-") + 1);
//console.log("id=" + id);
			del_list.push(id);
		}
	}

	if (del_list.length > 0)
		var req = { "delete": del_list.join(",") };

	$PeepSo.postJson("profile.notification_delete", req, function(json) {
		if (json.success)
			window.location = window.location + "";
		else
			psmessage.show('', json.errors[0]);
	});
}

/*
 * turn all checkboxes on
 */
PsProfileNotification.prototype.select_all = function()
{
	var ckbx = jQuery("#peepso-wrap input:checkbox");
	for (var i = 0; i < ckbx.length; i++) {
		jQuery(ckbx[i]).attr("checked", "checked");
	}

	jQuery("#notifications-select-all").hide();
	jQuery("#notifications-unselect-all").show();
}

/*
 * turn all checkboxes off
 */
PsProfileNotification.prototype.unselect_all = function()
{
	var ckbx = jQuery("#peepso-wrap input:checkbox");
	for (var i = 0; i < ckbx.length; i++) {
		jQuery(ckbx[i]).removeAttr("checked");
	}

	jQuery("#notifications-select-all").show();
	jQuery("#notifications-unselect-all").hide();
}

var ps_profile_notification = new PsProfileNotification();

// EOF
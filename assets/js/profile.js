/*
 * Handlers for user profile page
 * @package PeepSo
 * @author PeepSo
 */

//$PeepSo.log("profile.js");

// declare class
function PsProfile()
{
	this.cover = {};
	this.cover.x_position_percent = 0;
	this.cover.y_position_percent = 0;

	this.initial_cover_position = jQuery("img#" + peepsodata.userid).attr('style');
}

var profile = new PsProfile();

/**
 * Initializes this instance's container and selector reference to a postbox instance.
 */
PsProfile.prototype.init = function()
{
	// initialize the "About Me" collapsible area
	var coll = jQuery(".js-collapse-about-btn");
	if (0 !== coll.length) {
		coll.on("click", function(e) {
			e.preventDefault();
			var about = jQuery(".js-collapse-about");
			var disp = about.css("display");
			if ("none" === disp) {
				about.show();
			} else {
				about.hide();
			}
		});
	}

	jQuery(".js-focus-cover").hover(
		function() {
			if (false === pswindow.is_visible)
				jQuery(".js-focus-change-cover").show();
		},
		function() {
			jQuery(".js-focus-change-cover").hide();
		}
	);

	// removed the jquery event handlers in favor of onclick= attributes
//	jQuery(".ps-tab__bar a").click(function(e) {
//		e.preventDefault();
//		jQuery(this).tab("show");
//	});
	// remove Divi event handlers from the activity/about me tabs
	jQuery(".ps-tab__bar").unbind("click");
};

/**
 * event callback for switching tabs between Activity Stream and About Me
 * @param Event e Current event
 * @param string name Name of tab to activate
 * @param string hide Name of tab to hide
 * @returns Boolean To prevent continuing execution
 */
PsProfile.prototype.activate_tab = function(e, name, hide)
{
	e.preventDefault();

	jQuery(e.target).addClass('active')
		.siblings('[data-toggle=tab]').removeClass('active');

	jQuery(hide).hide();
	jQuery(name).show();
	return (false);
};

/**
 * Likes a profile
 * @return {boolean} Always returns FALSE
 */
PsProfile.prototype.new_like = function()
{
//console.log("PsProfile.new_like()");
	var req = { likeid: peepsodata.userid, uid: peepsodata.currentuserid };
	$PeepSo.postJson("profile.like", req, function(json) {
		if (json.success)
			jQuery(".nav.profile-interactions").html(json.data.html);
		else
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
	});
//	likecount

	return (false);
};

/**
 * Performs block user operation
 */
PsProfile.prototype.block_user = function(user_id, elem)
{
	if (this.blocking_user) {
		return;
	}

	pswindow.confirm( peepsoprofiledata.label_confirm_block_user, jQuery.proxy(function() {
		pswindow.hide();

		if (elem) {
			elem = jQuery(elem);
			elem.find('img').css('display', 'inline');
		}

		var req = { uid: peepsodata.currentuserid, user_id: user_id };

		this.blocking_user = true;
		$PeepSo.postJson("activity.blockuser", req, jQuery.proxy(function(json) {
			this.blocking_user = false;
			if (json.success) {
				jQuery('.ps-js-focus--' + user_id).find('.ps-focus-actions, .ps-focus-actions-mobile').html(json.data.actions);
				psmessage.show(json.data.header, json.data.message, psmessage.fade_time);
			}
		}, this ));
	}, this ));
};

/**
 * Performs unblock user operation
 */
PsProfile.prototype.unblock_user = function(user_id, elem)
{
	if (this.unblocking_user) {
		return;
	}

	if (elem) {
		elem = jQuery(elem);
		elem.find('img').css('display', 'inline');
	}

	var req = { uid: peepsodata.currentuserid, user_id: user_id };

	this.unblocking_user = true;
	$PeepSo.postJson("activity.unblockuser", req, jQuery.proxy(function(json) {
		this.unblocking_user = false;
		if (json.success) {
			jQuery('.ps-js-focus--' + user_id).find('.ps-focus-actions, .ps-focus-actions-mobile').html(json.data.actions);
			psmessage.show(json.data.header, json.data.message, psmessage.fade_time);
		}
	}, this ));
};

/**
 * Reports profile as inappropriate
 */
PsProfile.prototype.report_user = function()
{
//console.log("PsProfile.report_user()");
	var title = jQuery("#activity-report-title").html();
	var content = jQuery("#activity-report-content").html();

	content = content.replace("{post-content}", "Profile");
	content = content.replace("{post-id}", peepsodata.userid + "");

	ps_observer.add_filter('activity_report_action', function(action) {
		return "profile.report";
	}, 10, 1);

	actions = jQuery("#activity-report-actions").html();
	pswindow.show(title, content).set_actions(actions).refresh();

	jQuery("#ps-window").one('pswindow.hidden', function() {
		ps_observer.remove_filter('activity_report_action', function(action) {
			return "profile.report";
		}, 10);
	});

	jQuery(window).one('report.submitted', function(e, response) {
		pswindow.show('Profile Reported', response.notices[0]);
	});
};

/**
 * Confirms remove cover photo request
 */
PsProfile.prototype.confirm_remove_cover_photo = function()
{
	var title = jQuery("#delete-confirmation #delete-title").html();
	var content = jQuery("#delete-confirmation #delete-content").html();

	pswindow.show(title, content);
};

/**
 * Performs remove cover photo operation
 */
PsProfile.prototype.remove_cover_photo = function(user_id)
{
	var req = { uid: peepsodata.currentuserid, user_id: user_id, _wpnonce: jQuery("#_covernonce").val() };
	$PeepSo.postJson("profile.remove_cover_photo", req, function(json) {
		if (json.success) {
			window.location.reload();
		}
	});
};

/**
 * Applies jquery draggable and saves the dragged position to this.cover
 */
PsProfile.prototype.reposition_cover = function()
{
	jQuery(".js-focus-gradient", ".js-focus-cover").hide();
	jQuery(".js-focus-change-cover > a", ".js-focus-cover").hide();
	jQuery(".reposition-cover-actions", ".js-focus-cover").show();
	jQuery(".js-focus-cover").addClass("ps-focus-cover-edit");

	var that = this;
	var g = jQuery(".js-focus-cover").height() - jQuery("img#" + peepsodata.userid).height();
	var w = jQuery(".js-focus-cover").width() - jQuery("img#" + peepsodata.userid).width();

	jQuery("img#" + peepsodata.userid).draggable({
		cursor: "move",
		drag: function (a, b) {
			b.position.top < g && (b.position.top = g), b.position.top > 0 && (b.position.top = 0)
			,
			b.position.left < w && (b.position.left = w), b.position.left > 0 && (b.position.left = 0);
		},
		stop: function (a, c) {
			var d = jQuery("img#" + peepsodata.userid),
				e = d.closest(".js-focus-cover"),
				x = 100 * c.position.top / e.height();
			x = Math.round(1e4 * x) / 1e4;
			y = (100 * c.position.left / e.width());
			y = Math.round(1e4 * y) / 1e4;

			that.cover.x_position_percent = x;
			that.cover.y_position_percent = y;
			d.css("top", x + "%");
			d.css("left", y + "%");
		}
	});
};

/**
 * Performs when reposition cover is cancelled
 */
PsProfile.prototype.cancel_reposition_cover = function()
{
	jQuery(".js-focus-gradient", ".js-focus-cover").show();
	jQuery(".js-focus-change-cover > a", ".js-focus-cover").show();
	jQuery(".reposition-cover-actions", ".js-focus-cover").hide();
	jQuery(".js-focus-cover").removeClass("ps-focus-cover-edit");

	jQuery("img#" + peepsodata.userid).attr('style', this.initial_cover_position);
};

/**
 * Saves the cover images position after repositioning
 */
PsProfile.prototype.save_reposition_cover = function()
{
	var req = {
		user_id: peepsodata.userid,
		x: this.cover.x_position_percent,
		y: this.cover.y_position_percent,
		_wpnonce: jQuery("#_photononce").val()
	};

	var that = this;

	jQuery(".reposition-cover-actions", ".js-focus-cover").hide();
	jQuery(".ps-reposition-loading", ".js-focus-cover").show();
	jQuery(".js-focus-cover").removeClass("ps-focus-cover-edit");

	$PeepSo.postJson("profile.reposition_cover", req, function(json) {
		jQuery(".ps-reposition-loading", ".js-focus-cover").hide();
		that.initial_cover_position = jQuery("img#" + peepsodata.userid).attr('style');
		that.cancel_reposition_cover();

		psmessage.show('', json.notices[0]).fade_out(psmessage.fade_time);
	});
};

/**
 * Confirms remove avatar photo request
 */
PsProfile.prototype.confirm_remove_avatar = function()
{
	var title = jQuery("#delete-confirmation #delete-title").html();
	var content = jQuery("#delete-confirmation #delete-content").html();

	pswindow.show(title, content);
};

/**
 * Performs remove avatar photo operation
 */
PsProfile.prototype.remove_avatar = function(user_id)
{
	var req = { uid: peepsodata.currentuserid, user_id: user_id, _wpnonce: jQuery("#_photononce").val() };
	$PeepSo.postJson("profile.remove_avatar", req, function(json) {
		if (json.success) {
			window.location.reload();
		}
	});
};

/**
 * Deletes profile operation
 */
PsProfile.prototype.delete_profile = function()
{
	var title = jQuery("#profile-delete-title").html();
	var content = jQuery("#profile-delete-content").html();

	pswindow.show(title, content);
};

/**
 * Performs the delete operation
 */
PsProfile.prototype.delete_profile_action = function()
{
	$req = { };
	var req = { uid: peepsodata.currentuserid };
	$PeepSo.postJson("profile.delete_profile", req, function(json) {
		if (json.success)
			window.location = json.data.url;
		else
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
	});
};

/**
 * Shows avatar dialog to upload/change avatar
 */
PsProfile.prototype.show_avatar_dialog = function()
{
	jQuery(".error-container").html("");	// clear any remaining error messages
	var $dialog = jQuery("#dialog-upload-avatar");
	var title = $dialog.find("#dialog-upload-avatar-title").html();
	var content = $dialog.find("#dialog-upload-avatar-content").html();
	var actions = $dialog.find(".dialog-action").html();

	var inst = pswindow.show(title, content).set_actions(actions);
	var elem = inst.$container.find('.ps-dialog');

	elem.addClass('ps-dialog-wide');
	ps_observer.add_filter('pswindow_close', function() {
		elem.removeClass('ps-dialog-wide');
	}, 10, 1);

	this.init_avatar_fileupload();

	jQuery("#ps-window").on('pswindow.hidden', function() {
		jQuery('.upload-avatar .fileupload:visible').fileupload('destroy');
	});
};

/**
 * Initializes avatar file upload
 */
PsProfile.prototype.init_avatar_fileupload = function()
{
	var that = this;

	jQuery(".upload-avatar .fileupload:visible").fileupload({
		formData: {user_id: peepsodata.userid, _wpnonce: jQuery("#_photononce").val()},
		dataType: 'json',
		url: peepsodata.ajaxurl + 'profile.upload_avatar?avatar',
		add: function(e, data) {
			var acceptFileTypes = /(\.|\/)(jpe?g|png)$/i;
			if (data.files[0]['type'].length && !acceptFileTypes.test(data.files[0]['type'])) {
				var error_filetype = jQuery("#profile-avatar-error-filetype").text();
				jQuery(".error-container").html(error_filetype);
			} else if (parseInt(data.files[0]['size']) > peepsodata.upload_size) {
				var error_filesize = jQuery("#profile-avatar-error-filesize").text();
				jQuery(".error-container").html(error_filesize);
			} else {
				jQuery("#ps-window .ps-loading-image").show();
				jQuery("#ps-window .show-avatar, #ps-window .show-thumbnail, #ps-window .upload-avatar").hide();
				pswindow.refresh();
				data.submit();
			}
		},
		done: function (e, data) {
			var response = data.result;

			if (response.success) {
				var content_html = jQuery('#dialog-upload-avatar-content', response.data.html).html();
				var actions = jQuery("#dialog-upload-avatar .dialog-action").html();
				jQuery(".js-focus-avatar img").attr("src", response.data.image_url + "?" + Math.random());
				jQuery("#ps-dialogs #dialog-upload-avatar-content").html(content_html);
				pswindow.set_content(content_html);
				pswindow.set_actions(actions);

				jQuery("#imagePreview img").one("load", function() {
					pswindow.refresh();
					profileavatar.refreshThumbnail();
				});

				that.init_avatar_fileupload();
			} else {
				jQuery("#ps-window .show-avatar, #ps-window .show-thumbnail, #ps-window .upload-avatar").show();
				jQuery(".error-container").html(response.errors);
				jQuery("#ps-window .ps-loading-image").hide();
			}
		}
	});
};

/**
 * Shows cover dialog to change/upload cover content
 */
PsProfile.prototype.show_cover_dialog = function()
{
	jQuery(".error-container").html("");	// clear any remaining error messages
	var $dialog = jQuery("#dialog-upload-cover");
	var title = $dialog.find("#dialog-upload-cover-title").html();
	var content = $dialog.find("#dialog-upload-cover-content").html();

	pswindow.show(title, content);

	this.init_cover_fileupload();

	jQuery("#ps-window").on('pswindow.hidden', function() {
		jQuery('.upload-cover .fileupload:visible').fileupload('destroy');
	});
};

/**
 * Initializes cover file upload
 */
PsProfile.prototype.init_cover_fileupload = function()
{
	var that = this;

	jQuery(".upload-cover .fileupload:visible").fileupload({
		formData: {user_id: peepsodata.userid, _wpnonce: jQuery("#_photononce").val()},
		dataType: 'json',
		url: peepsodata.ajaxurl + 'profile.upload_cover?cover',
		add: function(e, data) {
			var acceptFileTypes = /(\.|\/)(jpe?g|png)$/i;
			if (data.files[0]['type'].length && !acceptFileTypes.test(data.files[0]['type'])) {
				var error_filetype = jQuery("#profile-cover-error-filetype").text();
				jQuery(".error-container").html(error_filetype);
			} else if (parseInt(data.files[0]['size']) > peepsodata.upload_size) {
				var error_filesize = jQuery("#profile-cover-error-filesize").text();
				jQuery(".error-container").html(error_filesize);
			} else {
				jQuery("#ps-window .ps-loading-image").show();
				jQuery("#ps-window .upload-cover").hide();
				data.submit();
			}
		},
		done: function (e, data) {
			var response = data.result;
			jQuery("#ps-window .ps-loading-image").hide();
			jQuery("#ps-window .upload-cover").show();
			if (response.success) {
				jQuery(".cover-image")
					.attr("src", response.data.image_url + "?" + Math.random())
					.css("top", "0")
					.css("left", "0")
					.removeClass("default")
					.addClass("has-cover");

				pswindow.fade_out("slow");
				jQuery("#profile-reposition-cover").show();
				jQuery('#dialog-upload-cover-content').html(response.data.html);
				pswindow.set_content(jQuery('#dialog-upload-cover-content', response.data.html).html());
				that.init_cover_fileupload();
			} else {
				jQuery(".error-container").html(response.errors);
			}
		}
	});
};

jQuery(document).ready( function ()
{
	profile.init();
});

// EOF

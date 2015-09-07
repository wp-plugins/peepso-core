/*
 * Handles user interactions for the Activity Stream
 * @package PeepSo
 * @author PeepSo
 */
function PsActivity()
{
	this.current_post = null;
	this.page_loading = false;
	// used for multiple ajax checks
	this.is_ajax_loading = [];
}

var activity = new PsActivity();

/**
 * Initializes this instance's container and selector reference to a postbox instance.
 */
PsActivity.prototype.init = function()
{
	var _self = this;
	this.$post_container = jQuery("#ps-activitystream");

	jQuery(".comment-container").each(function(index, e) {
		if (0 === jQuery("div", e).size())
			jQuery(this).hide();
	});

	if (this.$post_container.size() > 0)
		jQuery(window).on("scroll", function() {
			if (jQuery(window).scrollTop() >= _self.$post_container.offset().top + _self.$post_container.outerHeight() - window.innerHeight) {
				if (false === _self.page_loading)
					jQuery("#show-more-posts").trigger("click");
			}
		});

	jQuery(".ps-stream-content .cstream-respond").each(function(index, e) {
		if (0 === jQuery("div", e).not(".comment-container").size())
			jQuery(this).hide();

		jQuery("textarea[name='comment']").autosize();
	});

	var hash = window.location.hash;
	// Loads the necessary comment when not visible on a permalink
	if ("" !== hash) {
		var hashes = hash.split("|");
		if ("#comment" === hashes[0] && 0 === jQuery("#comment-item-" + hashes[1]).size()) {
			jQuery(".comment-container[data-act-id=" + hashes[2] + "]").one("comments.shown", function() {
				jQuery("html, body")
					.delay(2000)
					.animate({ scrollTop: jQuery("#comment-item-" + hashes[1]).offset().top }, 2000);
			});

			jQuery("a[href='#showallcomments']", ".ps-js-activity--" + hashes[2]).trigger("click");
		}
	}

	jQuery(document).on("ps_activitystream_loaded peepso_repost_shown peepso_report_shown ps_activitystream_append", function() {
		_self.toggle_anchor_target();
		_self.setup_comment_textarea();
	});

	// Use this event to fire js specific to stream items
	jQuery(document).trigger("ps_activitystream_loaded");

	var evtResizeName = 'resize.activity-container';

	jQuery(window)
		.off(evtResizeName)
		.on(evtResizeName, _.debounce(function() {
			var screenSize = $PeepSo.screenSize();
			var $container = jQuery('.ps-stream-container');
			var narrowClass = 'ps-stream-container-narrow';
			if ( $container.width() <= 480 ) {
				$container.addClass( narrowClass );
			} else {
				$container.removeClass( narrowClass );
			}
		}, 500 ))
		.trigger(evtResizeName);
};

/**
 * Defines events for the comment textarea
 */
PsActivity.prototype.setup_comment_textarea = function()
{
	var _self = this;
	jQuery("[data-type='stream-newcomment'] textarea").off("keydown.peepso");
	jQuery("[data-type='stream-newcomment'] textarea").on("keydown.peepso", function(e) {
		return (_self.on_comment_keydown(e));
	});
};

PsActivity.prototype.on_comment_keydown = function(e) {
	if (e.shiftKey || e.ctrlKey) {
		return true;
	}

	var keycode = (e.keyCode ? e.keyCode : e.which);
	var $textarea = jQuery(e.target);
	var obj;

	if (13 === keycode && "" !== jQuery.trim($textarea.val())) {
		obj = { el: $textarea, can_submit: true };
		obj = ps_observer.apply_filters("comment_can_submit", obj);
		if (obj.can_submit) {
			this.comment_save($textarea.data("act-id"));
			e.preventDefault();
			return false;
		}
	}

	return true;
};

PsActivity.prototype.toggle_anchor_target = function()
{
	var regExp = new RegExp(location.host);

	var $external_links = jQuery(".ps-stream-content .cstream-attachment a, .ps-stream-content .content a, .ps-share-status-inner a")
		.filter(function() {
			var href = jQuery(this).attr("href");
			return (href.substring(0,4) === "http") ? !regExp.test(href) : false;
		});

	if (0 == peepsodata.open_in_new_tab)
		$external_links.removeAttr("target");
	else
		$external_links.attr("target", "_blank");

	return;
};

/**
 * Performs a like on a post
 * @param {obj} elem The clicked element
 * @param {int} act_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_like = function(elem, act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	$PeepSo.postJson("activity.like", req, function(json) {
		var $elem;

		if (json.success) {
			$elem = jQuery(".ps-js-act-like--" + act_id);
			if (json.data.count > 0) {
				$elem.html(json.data.count_html).show();
			} else {
				$elem.hide();
			}

			jQuery(elem).replaceWith(json.data.like_html);
		} else
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
	});
	return (false);
};

/**
 * Shares a comment
 * @param {int} act_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_repost = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid, user_id: peepsodata.user_id };
	var title = jQuery("#repost-dialog .dialog-title").html();

	content = jQuery("#ajax-loader-gif").html();
	pswindow.show(title, content);

	$PeepSo.getJson("activity.ajax_show_post", req, function(json) {
		if (0 == json.success) {
			pswindow.hide();
			return;
		}

		var content = jQuery("#repost-dialog .dialog-content").html();
		var actions = jQuery("#repost-dialog .dialog-action").html();
		var post = json.data.html.trim();

		post = post.replace("<p>", "<span>").replace("</p>", "<br/></span>").replace("<br/></span>", "</span>");

		content = content.replace("{post-content}", post);
		content = content.replace("{post-id}", act_id + "");
		pswindow.set_content(content).set_actions(actions).refresh();

		jQuery(document).trigger("peepso_repost_shown");

		jQuery("#share-post-box", "#ps-window").autosize();

		jQuery("#cWindowContent .ps-dropdown-toggle").on("click", dropdown_toggle_click)
			.on("mouseenter", dropdown_toggle_mouseenter);

		// setup privacy control
		jQuery("#cWindowContent .dropdown-menu li a").click(function(e) {
			var a = jQuery(e.target).closest("a");
			var btn = jQuery("#cWindowContent .ps-dropdown-toggle");
			var input = jQuery("#cWindowContent input[name='repost_acc']");
			var placeHolder = btn.find("a");
			var menu = a.closest(".ps-dropdown-toggle");

			btn.find("i").attr("class", a.find("i").attr("class"));
			input.val(a.attr("data-option-value"));

			placeHolder.html(a.html());
		});

		jQuery("#ps-window").on("pswindow.hidden", function() {
			jQuery("#cWindowContent .ps-dropdown-toggle").off("click").off("mouseenter");
		});

	});

	return (false);
};

/**
 * Sends/submits reposted Post
 */
PsActivity.prototype.submit_repost = function()
{
	var post_id = jQuery("#postbox-post-id", "#ps-window").val();

	var req = {
		content: jQuery("#share-post-box", "#ps-window").val(),
		id: peepsodata.currentuserid,
		uid: peepsodata.currentuserid,
		repost: post_id,
		acc: jQuery("#cWindowContent input[name='repost_acc']").val(),
		type: "activity"
	};
	// send req through filter
	req = ps_observer.apply_filters("postbox_req", req);

	$PeepSo.postJson("postbox.post", req, function(json) {
		if (json.success) {
			pswindow.set_content(json.notices[0]).refresh();
			pswindow.fade_out(3000, function() {
				if ("0" === peepsodata.userid || peepsodata.currentuserid === peepsodata.userid) {
					jQuery(json.data.html).hide().prependTo("#ps-activitystream").fadeIn("slow", function() {
						jQuery(document).trigger("peepso_repost_added");

						// hook up the drop-down menu within the new post
						var post_id = json.data.post_id;
						jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .ps-dropdown-toggle")
							.click(dropdown_toggle_click)
							.on("mouseenter", dropdown_toggle_mouseenter);
						jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .dropdown-menu a")
							.click(dropdown_toggle_a_click);
					});

					// Scroll to top to view new post.
					jQuery("html, body").animate({
						scrollTop: jQuery("#ps-activitystream").offset().top
					}, 2000);
				}
			});
		}
	});

	return (false);
};

/**
 * Reports a post as inappropriate content
 * @param {int} act_id of post content
 */
PsActivity.prototype.action_report = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid, user_id: peepsodata.user_id };
	var title = jQuery("#activity-report-title").html();

	content = jQuery("#ajax-loader-gif").html();
	pswindow.show(title, content);

	$PeepSo.getJson("activity.ajax_show_post", req, function(json) {
		var content = jQuery("#activity-report-content").html();
		var post = json.data.html.trim();

		post = post.replace("<p>", "<span>").replace("</p>", "<br/></span>").replace("<br/></span>", "</span>");

		content = content.replace("{post-content}", post);
		content = content.replace("{post-id}", act_id + "");

		actions = jQuery("#activity-report-actions").html();

		pswindow.set_content(content).set_actions(actions).refresh();

		jQuery(document).trigger("peepso_report_shown");
	});
};

/**
 * Submits report information to server
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.submit_report = function()
{
	jQuery("#cWindowContent .report-error-div").hide();

	var act_id = jQuery("#postbox-report-popup #postbox-post-id", "#ps-window").val();
	var reas = jQuery("#rep_reason option:selected", "#ps-window").val();

	if ("0" === reas) {
		// display message with reason not submitting
		var msg = jQuery("#report-error-select-reason").text();
		jQuery("#cWindowContent .report-error-div").text(msg).show();
		return (false);
	}

	var req = { act_id: act_id, uid: peepsodata.currentuserid, reason: reas };

	var report_action = ps_observer.apply_filters("activity_report_action", "activity.report");
	$PeepSo.getJson(report_action, req, function(json) {
		if (json.success) {
			jQuery(window).trigger("report.submitted", json);
			pswindow.set_content(json.notices[0]).fade_out(pswindow.fade_time);
		} else {
			pswindow.hide();
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
		}
	});

	return (false);
};

/**
 * Performs a delete action on a post
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.action_delete = function(post_id)
{
	var that = this;
	pswindow.confirm_delete(function() {
		var req = { postid: post_id, uid: peepsodata.currentuserid };
		$PeepSo.postJson("activity.delete", req, function(json) {
			that.toggle_comment_box(post_id, false);
			if (json.success)
				window.location.reload();
		});
		pswindow.hide();
		return (false);
	});
	return (false);
};

/**
 * Called when contents of comment box are changed to reset UI elements
 * @param {int} post_id ID of post content
 */
PsActivity.prototype.on_commentbox_change = function(textarea)
{
	var $sel = jQuery(textarea);

	if ($sel.val().length > peepsodata.postsize)
		$sel.val($sel.val().substring(0, peepsodata.postsize));

	if ("" !== jQuery.trim($sel.val())) {
		$sel.parents('.cstream-form-input').next('.cstream-form-submit').show();
		$sel.parents('.cstream-form-input').next('.cstream-form-submit').find('.ps-comment-actions').show();
		$sel.parents('.cstream-form-input').next('.cstream-form-submit').find('.ps-button-action').removeAttr("disabled");
	} else {
		$sel.parents('.cstream-form-input').next('.cstream-form-submit').find('.ps-comment-actions').hide();
		$sel.parents('.cstream-form-input').next('.cstream-form-submit').find('.ps-button-action').attr("disabled", "disabled");
	}
};

/**
 * Sends a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_save = function(act_id, elem)
{
	if (this.is_ajax_loading['save-comment-' + act_id])
		return;

	var that = this;
	var sel = jQuery("#act-new-comment-" + act_id + " .cstream-form-text");

	if (elem && elem.tagName) {
		sel = jQuery(elem).closest("#act-new-comment-" + act_id).find(".cstream-form-text");
	}

	jQuery("#act-new-comment-" + act_id + " .ps-comment-loading").show();
	jQuery("#act-new-comment-" + act_id + " .ps-comment-actions").hide();

	var comment_content = jQuery(sel).val();
	req = ps_observer.apply_filters("comment_req", {
		act_id: act_id,
		uid: peepsodata.currentuserid,
		content: comment_content,
		last: jQuery(".comment-container[data-act-id=" + act_id + "] .cstream-comment:last").data("comment-id")
	}, sel);

	this.is_ajax_loading['save-comment-' + act_id] = true;

	$PeepSo.postJson("activity.makecomment", req, function(json) {
		that.is_ajax_loading['save-comment-' + act_id] = false;
		if (json.success) {
			//.hide().fadeIn(2000)
			var $html = jQuery(json.data.html);
			jQuery(".comment-container[data-act-id=" + act_id + "]").show();
			$html.appendTo(".comment-container[data-act-id=" + act_id + "]").hide().fadeIn(2000);
			jQuery("#peepso-wrap").trigger("comment.saved", [act_id, sel, req, $html]);
		} else
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);

		activity.comment_cancel(act_id);

		activity.current_post = null;

		jQuery("#act-new-comment-" + act_id + " .ps-comment-loading").hide();
		jQuery("#act-new-comment-" + act_id + " .ps-comment-actions").hide();
		jQuery("#act-new-comment-" + act_id + " .ps-button-action").attr("disabled", "disabled");

		if ('undefined' !== typeof(json.data.has_max_comments))
			that.toggle_comment_box(act_id, json.data.has_max_comments);
	});
	return (false);
};

/**
 * Edits a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.comment_action_edit = function(post_id, elem)
{
	// Prevent further actions when an edit div is already present.
	if (jQuery("#comment-item-" + post_id + " .cstream-content .cstream-edit").size() > 0)
		return;

	var that = this;
	var $comment_container = jQuery(elem).closest("#comment-item-" + post_id);

	if (undefined === this.is_ajax_loading['comment-edit-' + post_id] ||
		false === this.is_ajax_loading['comment-edit-' + post_id]) {
		this.is_ajax_loading['comment-edit-' + post_id] = true;

		var req = { postid: post_id, uid: peepsodata.currentuserid };
		$PeepSo.postJson("activity.editcomment", req, function(json) {
			if (json.success) {
				var html = jQuery(json.data.html);
				// hide current container of post information
				jQuery("[data-type='stream-comment-content']", $comment_container).first().hide()
					.after(html);				// add new <div> with edit form

				jQuery("#peepso-wrap").trigger("comment_edit.shown", [post_id, html]);

				jQuery(".cstream-edit textarea", $comment_container)
					.on('input propertychange', function() {
						if (jQuery(this).val().length > peepsodata.postsize)
							jQuery(this).val(jQuery(this).val().substring(0, peepsodata.postsize));
					})
					.autosize();

				that.is_ajax_loading['comment-edit-' + post_id] = false;
			}
		});
	}

	return (false);
};

/**
 * Cancels button responder for editing a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.option_canceleditcomment = function(post_id, elem)
{
	var $ai = jQuery(elem).closest("#comment-item-" + post_id);
	if ($ai.length > 0) {
		jQuery(".cstream-edit", $ai).remove();					// remove the post edit form elements
		jQuery("[data-type='stream-comment-content']", $ai).show();				// show the original post content
	}

	return (false);
};

/**
 * Saves button responder for editing a comment
 * @param {int} post_id ID of post content
 * @param {object} elem The element nearest to the desired comment
 */
PsActivity.prototype.option_savecomment = function(post_id, elem)
{
	var $ai = jQuery(elem).closest("#comment-item-" + post_id);
	if ($ai.length > 0) {
		var contents = jQuery(".cstream-edit textarea", $ai).val();
		jQuery(".cstream-edit textarea", $ai).attr("disabled", "disabled");
		jQuery(".ps-edit-loading", $ai).show();
		jQuery(".cstream-edit button", $ai).hide();

		var req = ps_observer.apply_filters("comment_req", { postid: post_id, uid: peepsodata.currentuserid, post: contents }, jQuery(".cstream-edit textarea", $ai));

		$PeepSo.postJson("activity.savecomment", req, function(json) {
			if (json.success) {
				jQuery(".cstream-edit", $ai).remove();				// remove the post edit form elements
				jQuery("[data-comment-id=" + post_id + "] [data-type='stream-comment-content']").html(json.data.html);
				jQuery("[data-type='stream-comment-content']", $ai).show();	// reset contents of the activity stream item
				jQuery("[data-comment-id=" + post_id + "] .cstream-content > .cstream-attachments").html(json.data.attachments);
				jQuery(".cstream-content > .cstream-attachments", $ai).show();	// reset contents of the activity stream item
			} else {
				psmessage.show('', json.notices[0]);
				jQuery(".cstream-edit button", $ai).show();
				jQuery(".ps-edit-loading", $ai).hide();
				jQuery(".cstream-edit textarea", $ai).removeAttr("disabled");
			}
		});
	}

	return (false);
};

/**
 * Deletes a comment
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_action_delete = function(post_id)
{
	var that = this;
	pswindow.confirm_delete(function() {
		var req = { postid: post_id, uid: peepsodata.currentuserid };
		$PeepSo.postJson("activity.delete", req, function(json) {
			that.toggle_comment_box(post_id, false);
			if (json.success)
				jQuery(".ps-comment-item[data-comment-id=" + post_id + "]").remove();
			pswindow.hide();
		});
		return (false);
	});
};

/**
 * Reports comment as inappropriate
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.comment_action_report = function(act_id)
{
	var req = { act_id: act_id };
	$PeepSo.getJson("activity.ajax_show_comment", req, function(json) {
		var title = jQuery("#activity-report-title").html();
		var content = jQuery("#activity-report-content").html();

		var post = json.data.html.trim();
		post = post.replace("<p>", "<span>").replace("</p>", "<br/></span>").replace("<br/></span>", "</span>");

		content = content.replace("{post-content}", post);
		content = content.replace("{post-id}", act_id + "");

		ps_observer.add_filter("activitystream_notice_container", function(container, act_id) {
			return "#comment-item-" + act_id + " .cstream-more";
		}, 10, 2);

		actions = jQuery("#activity-report-actions").html();
		pswindow.show(title, content).set_actions(actions).refresh();

		jQuery("#ps-window").one("pswindow.hidden", function() {
			ps_observer.remove_filter("activitystream_notice_container", function(container, act_id) {
				return ("#comment-item-" + act_id + " .cstream-more");
			}, 10);
		});
	});

	return (false);
};

/**
 * Likes a comment
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_action_like = function(elem, act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	$PeepSo.postJson("activity.like", req, function(json) {
		var $elem;

		if (json.success) {
			$elem = jQuery(".ps-js-act-like--" + act_id);
			if (json.data.count > 0) {
				$elem.html(json.data.count_html).show();
			} else {
				$elem.hide();
			}

			jQuery(elem).replaceWith(json.data.like_html);
		} else
			psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
	});
	return (false);
};

/**
 * Cancels a comment; clears the comment form
 * @param {int} post_id ID of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.comment_cancel = function(post_id)
{
	var ct = jQuery("#act-new-comment-" + post_id);
	var sel = ct.find(".cstream-form-text");

	ct.find(".cstream-form-submit").hide();
	sel.val("");

	ps_observer.apply_filters("comment_cancel", sel);

	return (false);
};

/**
 * Displays details on post/comment likes
 * @param {int} act_id of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.show_likes = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	var _self = this;

	if (undefined === this.is_ajax_loading['likes-' + act_id]
		|| false === this.is_ajax_loading['likes-' + act_id]) {
		this.is_ajax_loading['likes-' + act_id] = true;
		$PeepSo.getJson("activity.get_like_names", req, function(json) {
			jQuery("#act-like-" + act_id + " a").replaceWith(json.data.html);
			_self.is_ajax_loading['likes-' + act_id] = false;
		});
	}
	return (false);
};

/**
 * Shows all comments for given post
 * @param {int} act_id of post content
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.show_comments = function(act_id)
{
	var req = {
		act_id: act_id,
		uid: peepsodata.currentuserid,
		first: jQuery(".comment-container[data-act-id=" + act_id + "] .cstream-comment:first").data("comment-id")
	};

	jQuery("#wall-cmt-" + act_id + " .comment-ajax-loader").toggleClass("hidden");
	$PeepSo.getJson("activity.show_previous_comments", req, function(json) {
		jQuery("#wall-cmt-" + act_id + " .ps-comment-more").fadeOut();
		jQuery("#wall-cmt-" + act_id + " .comment-ajax-loader").toggleClass("hidden");
		jQuery(".comment-container[data-act-id=" + act_id + "]").prepend(json.data.html);

		setTimeout(function() {
			jQuery(".comment-container[data-act-id=" + act_id + "]").trigger("comments.shown");
		}, 1);
	});

	jQuery(this).hide();
	return (false);
};

/**
 * Edits an existing post in the activity stream
 * @param {int} post_id ID of post content
 */
PsActivity.prototype.option_edit = function(post_id)
{
	var req = { postid: post_id, uid: peepsodata.currentuserid };
	var that = this;

	if (undefined === this.is_ajax_loading["edit-" + post_id]
		|| false === this.is_ajax_loading["edit-" + post_id]) {
		this.is_ajax_loading["edit-" + post_id] =  true;

		$PeepSo.postJson("activity.editpost", req, function(json) {
			if (json.success) {
				// hide current container of post information
				var html = jQuery(json.data.html);
				jQuery(".ps-js-activity--" + json.data.act_id + " .cstream-attachment").first().hide()
					.after(html);				// add new <div> with edit form
				jQuery("#peepso-wrap").trigger("post_edit.shown", [json.data.act_id, html]);

				var $act = jQuery(".ps-js-activity--" + json.data.act_id);
				var $textarea = $act.find('.cstream-edit textarea');
				var $charcount = $act.find('.ps-postbox-charcount');

				$textarea
					.on('input propertychange', function() {
						if (jQuery(this).val().length > peepsodata.postsize)
							jQuery(this).val(jQuery(this).val().substring(0, peepsodata.postsize));
						$charcount.html( peepsodata.postsize - this.value.length );
					})
					.triggerHandler('input')
					.autosize();
			}

			that.is_ajax_loading["edit-" + post_id] = false;
		});
	}

	return (false);
};

/**
 * Cancels button responder for editing a post
 * @param {int} post_id ID of post content
 */
PsActivity.prototype.option_canceledit = function(post_id)
{
	var $ai = jQuery(".ps-js-activity--" + post_id);
	if ($ai.length > 0) {
		jQuery(".cstream-edit", $ai).remove();					// remove the post edit form elements
		jQuery(".cstream-attachment", $ai).show();				// show the original post content
	}

	return (false);
};

/**
 * Cancels button responder for editing an activity
 * @param {int} act_id The activity ID
 */
PsActivity.prototype.option_cancel_edit_description = function(act_id)
{
	var $ai = jQuery(".ps-js-modal-attachment--" + act_id);
	if ($ai.length > 0) {
		jQuery(".cstream-edit", $ai).remove();					// remove the post edit form elements
		jQuery(".cstream-attachment", $ai).show();				// show the original post content
	}

	return (false);
};

/**
 * Saves button responder for editing a post
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.option_savepost = function(act_id)
{
	var $ai = jQuery(".ps-js-activity--" + act_id);
	if ($ai.length > 0) {
		var contents = jQuery(".cstream-edit textarea", $ai).val();
		jQuery(".cstream-edit textarea", $ai).attr("disabled", "disabled");
		jQuery(".ps-edit-loading", $ai).show();
		jQuery(".cstream-edit button", $ai).hide();

		var req = ps_observer.apply_filters("postbox_req_edit", { act_id: act_id, uid: peepsodata.currentuserid, post: contents }, jQuery(".cstream-edit textarea", $ai));

		$PeepSo.postJson("activity.savepost", req, function(json) {
			jQuery(".cstream-edit", $ai).remove();				// remove the post edit form elements

			if (json.success) {
				jQuery(".cstream-attachment", $ai).html(json.data.html);	// reset contents of the activity stream item
				jQuery(".cstream-attachments", $ai).html(json.data.attachments);	// reset contents of the activity stream item
			} else
				psmessage.show("", json.errors[0]);

			jQuery(".cstream-attachment, .cstream-attachments", $ai).show();

			jQuery(document).trigger("peepso_post_edit_saved");
		});
	}

	return (false);
};

/**
 * Saves button responder for editing a description
 * @param {int} act_id The activity ID
 * @param  {string} type The activity type.
 * @param  {int} object_id
 */
PsActivity.prototype.option_save_description = function(act_id, type, object_id)
{
	var $ai = jQuery(".ps-js-modal-attachment--" + act_id);

	if ($ai.length > 0) {
		var $textarea = jQuery(".cstream-edit textarea", $ai);
		var contents = $textarea.val();

		jQuery(".cstream-edit textarea", $ai).attr("disabled", "disabled");
		jQuery(".ps-edit-loading", $ai).show();
		jQuery(".cstream-edit button", $ai).hide();

		var req = { act_id: act_id, type: type, object_id: object_id, uid: peepsodata.currentuserid, description: contents };

		req = ps_observer.apply_filters("caption_req", req, $textarea);

		$PeepSo.postJson("activity.save_description", req, function(json) {
			jQuery(".cstream-edit", $ai).remove();				// remove the post edit form elements
			jQuery(".ps-stream-attachment", $ai).html(json.data.html).show();	// reset contents of the activity stream item
		});
	}

	return (false);
};

/**
 * Hides a post from user's view
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.option_hide = function(act_id)
{
	var req = { act_id: act_id, uid: peepsodata.currentuserid };
	$PeepSo.postJson("activity.hidepost", req, function(json) {
		if (json.success)
			jQuery(".ps-js-activity--" + act_id).remove();
	});

	return (false);
};

/**
 * Adds user to block list
 * @param {int} post_id ID of post content
 * @param {int} user_id ID of user
 */
PsActivity.prototype.option_block = function(post_id, user_id)
{
	var req = { uid: peepsodata.currentuserid, user_id: user_id };
	$PeepSo.postJson("activity.blockuser", req, function(json) {
		if (json.success)
			jQuery(".ps-js-activity--" + post_id).remove();
	});

	return (false);
};

/**
 * Loads more posts onto the page
 * @param {int} page The page number to load
 * @return {boolean} Always returns FALSE
 */
PsActivity.prototype.load_page = function(page)
{
	if ( !( +peepsodata.currentuserid > 0 ) ) {
		return;
	}

	var _self = this;
	var req = { uid: peepsodata.currentuserid, user_id: peepsodata.userid, page: page };
	var $container = this.$post_container;
	jQuery("#show-more-posts").hide();
	jQuery(".post-ajax-loader").toggleClass("hidden");
	this.page_loading = $PeepSo.getJson("activity.show_posts_per_page", req, function(json) {
		var $new_posts;
		if (json.data.found_posts > 0) {
			$new_posts = jQuery(json.data.posts);
			$new_posts.appendTo($container).hide().fadeIn(1000, function() {
				jQuery(document).trigger("ps_activitystream_loaded");
			});
			jQuery("textarea[name='comment']", $new_posts).autosize();
			jQuery("#div-show-more-posts").remove();
		}

		_self.page_loading = false;

		if (!$new_posts) {
			jQuery(document).trigger("ps_activitystream_loaded");
		}
	});

	jQuery(document).on("peepso_login_shown", function() {
		jQuery("#show-more-posts").show();
		jQuery(".post-ajax-loader").toggleClass("hidden");
	});
};

/**
 * Changes the privacy setting on a post
 * @param {object} a An <a> tag object that was clicked
 * @param {int} act_id ID of post content
 */
PsActivity.prototype.change_post_privacy = function(a, act_id)
{
	a = jQuery(a);

	var $container = jQuery(".ps-js-privacy--" + act_id);

	var btn = jQuery(".ps-dropdown-toggle", $container);
	var placeHolder = btn.find("a");

	var old_class = btn.find("i").attr("class");
	btn.find("i").attr("class", a.find("i").attr("class"));

	var req = {
		uid: peepsodata.currentuserid,
		user_id: peepsodata.userid,
		acc: a.attr("data-option-value"),
		act_id: act_id,
		_wpnonce: peepsodata.peepso_nonce
	};

	$PeepSo.postJson("activity.change_post_privacy", req, function(res) {
		if (res.has_errors) {
			psmessage.show('', res.errors[0]).fade_out(psmessage.fade_time);
			btn.find("i").attr("class", old_class); // reset the icon
		} else if (res.success)
			psmessage.show('', res.notices[0]).fade_out(psmessage.fade_time);
	});

	return (false);
};

/**
 * Shows/hides comment box
 * @param {int} post_id Post Id
 * @param {boolean} has_max_comments Either max comments reached or not
 */
PsActivity.prototype.toggle_comment_box = function(post_id, has_max_comments)
{
	var new_comment = jQuery('#act-new-comment-' + post_id);
	if (new_comment.length <= 0) {
		var item = jQuery("#comment-item-" + post_id);
		if (item.length <= 0)
			item = jQuery(".ps-js-activity--" + post_id);
		if (item.length > 0) {
			new_comment = item.parent();
			var id = (item.parent().attr('id') + '').split('-').pop();
			new_comment = jQuery('#act-new-comment-' + id);
		}
	}
	if (new_comment.length <= 0)
		return (false);

	if (has_max_comments)
		new_comment.hide();
	else
		new_comment.show();

	return (false);
};

/**
 * Deletes an activity via ajax
 * @param  {int} act_id The activity ID to delete
 */
PsActivity.prototype.delete_activity = function(act_id)
{
	var req = {
		act_id: act_id,
		uid: peepsodata.currentuserid,
		_wpnonce: jQuery("#_delete_nonce").val()
	};

	var $act_delete_div_msg = jQuery("[data-act-delete-id=" + act_id + "]");
	var confirm_delete_message = "";
	if ($act_delete_div_msg.size() > 0)
		confirm_delete_message = $act_delete_div_msg.text();

	pswindow.confirm_delete(
		function() {
			$PeepSo.postJson("activity.ajax_delete_activity", req, function(json) {
				if (json.success) {
					window.location.reload();
				} else {
					psmessage.show('', json.errors[0]).fade_out(psmessage.fade_time);
				}
			});
		},
		confirm_delete_message
	);

	return (false);
};

/**
 * Fetches the act_description from the server and shows the edit UI.
 * @param  {int} act_id The activity to add a description to.
 * @param  {string} type The activity type.
 * @param  {int} object_id
 */
PsActivity.prototype.edit_activity_description = function(act_id, type, object_id) {
	var $ai = jQuery(".ps-js-modal-attachment--" + act_id);

	if ($ai.find(".cstream-edit textarea").length > 0) return;

	var req = { act_id: act_id, type: type, object_id: object_id, uid: peepsodata.currentuserid };
	$PeepSo.postJson("activity.edit_description", req, function(json) {
		if (json.success) {
			// hide current container of post information
			var html = jQuery(json.data.html);
			$ai.find(".ps-stream-attachment").first().hide()
				.after(html);			// add new <div> with edit form
			jQuery("#peepso-wrap").trigger("post_edit.shown", [json.data.act_id, html]);
			$ai.find(".cstream-edit textarea")
				.on('input propertychange', function() {
					if (jQuery(this).val().length > peepsodata.postsize)
						jQuery(this).val(jQuery(this).val().substring(0, peepsodata.postsize));
				})
				.autosize()
				.focus();
		}
	});

	return (false);
};

jQuery(document).ready(function() {
	activity.init();
});

// EOF

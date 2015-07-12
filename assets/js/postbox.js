/*
 * @copyright Copyright (C) 2014-2015 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

/*
 * Implementation of PeepSo's PostBox
 * @package PeepSo
 * @author PeepSo
 */

(function($){
	$.fn.pspostbox = function(options) {
		if (this.size() <= 0)
			return;

		var _self = this;

		this.$textarea = null;
		this.$access = null;
		this.$charcount = null;
		this.$save_button = null;
		this.$posttabs = null;
		this.$privacy_dropdown = null;

		this.can_submit = false;


		/**
		 * Initialize this postbox instance
		 * @param  {array} opts Array of options to override the defaults
		 * @return {object}      The plugin instance
		 */
		this.init = function(opts)
		{
			var _self = this;

			_opts = {
				textarea: "textarea.ps-postbox-textarea",
				addons: ".ps-postbox-addons",
				access : "#postbox_acc",
				save_url: "postbox.post",
				charcount : ".post-charcount",
				save_button : ".postbox-submit",
				send_button_text: undefined,
				text_length : peepsodata.postsize,
				autosize: true // allows the textarea to adjust it's height based on the length of the content
			};

			this.opts = _opts;
			$.extend(true, this.opts, opts);

			this.$posttabs = $(".ps-postbox-tab-root", this).ps_posttabs({
				container: this
			});

			this.$textarea = jQuery(this.opts.textarea, this);
			this.$addons = jQuery(this.opts.addons, this);
			this.$access = $(this.opts.access, this);
			this._default_access = this.$access.val();
			this.$charcount = $(this.opts.charcount, this);
			this.$save_button = $(this.opts.save_button, this);

			if (!_.isUndefined(this.opts.send_button_text))
				this.$save_button.html(this.opts.send_button_text);

			this.$privacy = $("#privacy-tab", this);
			this.orig_height = this.$textarea.height();

			if (this.opts.autosize)
				this.$textarea.autosize();

			// Setup events
			this.$textarea
				.attr("maxlength", this.opts.text_length)
				.on("keypress", function(e) { _self.on_keypress(e); })
				.on("paste", function(e) { _self.on_paste(e); })
				.on("focus", function(e) { _self.on_focus(); })
				.on("keyup", function(e) { _self.on_change(); });

			this.$charcount.html(this.opts.text_length + "");

			this.$privacy_dropdown = $(".ps-privacy-dropdown", this.$privacy);

			// setup privacy control
			jQuery("li a", this.$privacy_dropdown).on("click", function(e) {
				var a = $(e.target).closest("a");
				var btn = jQuery(_self.$privacy.find(".interaction-icon-wrapper .pstd-secondary"));
				var input = jQuery("#postbox_acc");
				var menu = a.closest("#postbox-privacy");

				btn.find("i").attr("class", a.find("i").attr("class"));
				input.val(a.attr("data-option-value"));

				menu.hide();
			});

			this.$privacy.on("click", function(e) {
				_self.privacy(e);
			});

			jQuery("nav.ps-postbox-tab ul li a").click(this.clear_tabs);

			jQuery("#status-post", _self).hide();
			jQuery(this.$posttabs).on("peepso_posttabs_show-status", function() {
				jQuery("#status-post", _self).hide();
				jQuery(".ps-postbox-status").show();
			});

			jQuery("#status-post", _self).on("click", function() {
				jQuery(_self.$posttabs).find("[data-tab='status']").trigger("click");
			});

			this.$posttabs.on("peepso_posttabs_submit-status", function() {
				_self.save_post();
			});

			this.$posttabs.on("peepso_posttabs_cancel-status", function() {
				jQuery("#status-post", _self).show();
			});

			this.$posttabs.on("peepso_posttabs_cancel", function() {
				_self.cancel_post();
			});

			this.find(".interactions > ul > li > .interaction-icon-wrapper a").on("click", function(e, x) {
				if (x)
					return;

				_self.find(".interactions > ul > li > .interaction-icon-wrapper a").not(this).trigger("peepso.interaction-hide", [true]);
			});

			this._load_addons();
		};

		/**
		 * Allows addons to get a reference to this postbox instance
		 */
		this._load_addons = function()
		{
			var addons = ps_observer.apply_filters("peepso_postbox_addons", []);

			$(addons).each(function(index, addon) {
				addon.set_postbox(_self);
				addon.init();
			});
		};

		/**
		 * Applies filter for postbox clear tabs
		 * called when any <a> link within postbox is clicked
		 */
		this.clear_tabs = function()
		{
			ps_observer.apply_filters("postbox_clear_tabs", null);
//			jQuery(".ps-postbox div.ps-postbox-popup.active").hide();
		};

		/**
		 * Sets post privacy on mouse up
		 * @param {object} e Event triggered
		 */
		this.privacy = function(e)
		{
			var that = this;

			this.$privacy_dropdown.show();

			jQuery(document).on("mouseup.postbox-privacy", function(e) {
				if (!that.$privacy_dropdown.is(e.target) &&				// if the target of the click isn't the container...
					0 === that.$privacy_dropdown.has(e.target).length) {	// ... nor a descendant of the container
					that.$privacy_dropdown.hide();
					jQuery(document).off("mouseup.postbox-privacy");
				}
			});
		};

		/**
		 * Saves the post
		 * Invokes when Post button is saved
		 */
		this.save_post = function()
		{
			var req = {
				content: this.$textarea.val(),
				id: peepsodata.currentuserid,
				uid: peepsodata.userid,
				acc: this.$access.val(),
				type: "activity"
			};

			if (!_.isUndefined(this.opts.postbox_req) && (typeof(Function) === typeof(this.opts.postbox_req)))
				req = this.opts.postbox_req.apply(null, [req]);
			// send req through filter
			req = ps_observer.apply_filters(this.selector + "-postbox_req", req);

			// disable repeated post
			if ( this.save_post_progress )
				return;

			this.save_post_progress = true;
			jQuery(".ps-postbox-action", this).hide();
			jQuery(".ps-postbox-loading", this).show();

			// Set to async so our filters run in order.
			$PeepSo.disableAsync().postJson(this.opts.save_url, req, function(json) {
				if (json.success) {
					_self.on_save(json);
					jQuery(_self).trigger("postbox.post_saved", [req, json]);
					jQuery(".ps-postbox-tab.interactions .ps-button-cancel", _self).trigger("click");
				} else {
					_self.on_error(json);
				}

				jQuery(".ps-postbox-loading", _self).hide();
				jQuery(".ps-postbox-action", _self).show();
				_self.save_post_progress = false;
			});
		};

		/**
		 * Called on post save
		 * @param {object} json JSON object
		 */
		this.on_save = function(json) {
			if (typeof(Function) === typeof(this.opts.on_save))
				this.opts.on_save.apply(this, [json]);

			jQuery(this).trigger("postbox.post_saved", this);
		};

		/**
		 * Invoked when an error on posting has occured
		 * @param {object} json JSON object
		 */
		this.on_error = function(json) {
			if (typeof(Function) === typeof(this.opts.on_error))
				this.opts.on_error.apply(this, [json]);
			else if (false === _.isUndefined(json.errors[0]))
				// TODO: this needs translation
				psmessage.show("Error", json.errors[0]);

			return;
		};

		/**
		 * Called when Cancel post button is invoked
		 */
		this.cancel_post = function()
		{
			//resets the privacy setting
			this.$privacy.find("[data-option-value='" + this._default_access + "']").trigger("click");
			this.$textarea.val("");
			this.$textarea.css("height", this.orig_height);
			this.on_change();
			jQuery(this).trigger("postbox.post_cancel");
		};

		/**
		 * On focus event handler
		 * Called when onfocus event is triggered
		 */
		this.on_focus = function()
		{
			jQuery(".ps-postbox-tab-root", _self).hide();
			jQuery(".ps-postbox-tab.interactions", _self).attr("data-tab-shown", this.$posttabs.current_tab().data("tab"));
			jQuery(".ps-postbox-tab.interactions", _self).show();
		};


		/**
		 * Keypress events handler
		 * Called when key has been pressed
		 * @param {object} e Event triggered
		 */
		this.on_keypress = function(e)
		{
			if (this.$textarea.val().length >= this.opts.text_length)
				return (false);
		};

		/**
		 * Paste events handler
		 * Called when paste is tiggered
		 * @param {object} e Event triggered
		 */
		this.on_paste = function(e)
		{
			var _self = this;
		    e.originalEvent.clipboardData.getData("text/plain").slice(0, this.text_length);

		    setTimeout(function() {
		    	_self.on_change();
		    }, 100);
		};

		/**
		 * Updates the character counter
		 */
		this.on_change = function()
		{
			var val = this.$textarea.val();
			var len = val.length;

			len = this.opts.text_length - len;

			if (len < 0)
				len = 0;

			this.$charcount.html(len + "");

			if (len >= 50)
				this.$charcount.removeClass("alert-info").removeClass("alert-error");
			else if (0 === len)// TODO: localize
				pswindow.show("", "You may only enter up to " + this.opts.text_length + " characters");
			else {
				if (len < 30)
					this.$charcount.removeClass("alert-info").addClass("alert-error");
				else if (len < 50)
					this.$charcount.addClass("alert-info").removeClass("alert-error");
			}

			var can_submit = ps_observer.apply_filters("peepso_postbox_can_submit", { 
				hard: [], 
				soft: [ len < this.opts.text_length && "" !== jQuery.trim(val) ] 
			});
			
			if ( can_submit.hard.length )
				can_submit = can_submit.hard.indexOf(false) > -1 ? false : true;
			else
				can_submit = can_submit.soft.indexOf(true) > -1 ? true : false;

			if (can_submit)
				this.$save_button.show();
			else
				this.$save_button.hide();

			var list = ps_observer.apply_filters("peepso_postbox_addons_update", []);
			if ( list && list.length ) {
				this.$addons.html( "&mdash; " + list.join(" and ") );
				this.$addons.show();
			} else {
				this.$addons.hide().empty();
			}
		};

		this.init(options);

		return (this);
	};
})(jQuery);


// delcare class
function PsPostBox()
{
	this.can_submit = false;
	this.found_url = false;
	this.show_preview = true;
	this.$postbox = null;
	this.$url_preview_container = jQuery("<div class='url-preview-container'></div>");
}

/**
 * Initializes Postbox
 */
PsPostBox.prototype.init = function()
{
	var _self = this;

	ps_observer.add_filter("peepso_postbox_can_submit", function(can_submit) {
		if (can_submit)
			return (can_submit);
		return (_self.can_submit);
	}, 20, 1);

	this.$activity_stream = jQuery("#ps-activitystream");

	this.$postbox = jQuery("#postbox-main").pspostbox({
		postbox_req: function(req) {
			req.show_preview = (_self.show_preview) ? "1" : "0";
			return (req);
		},
		on_save: function(json) {
			// Resets the postbox to the "Status" post
			jQuery(this.$posttabs).find("[data-tab='status']").trigger("click");
			return (_self.append_to_stream(json));
		}
	});

	if (undefined !== this.$postbox) {
		this.$postbox
			.on("blur", function(e) { _self.check_url_preview(); })
			.on("keyup", function(e) {
				if (32 === e.keyCode)
					_self.check_url_preview();
			})
			.on("postbox.post_saved postbox.post_cancel", function() {
				_self.found_url = false;
				_self.show_preview = true;
				_self.can_submit = false;
				_self.$url_preview_container.empty().remove();
			});
	}

	return (this.$postbox);
};

// Checks if there's a URL in the post and display a preview of it
PsPostBox.prototype.check_url_preview = function()
{
	// Check if we're on the status tab
	if ("status" !== this.$postbox.$posttabs.current_tab().data("tab"))
		return;
	// Let's do this process just once, even if all content is removed and a new URL is entered
	if (this.found_url)
		return;

	var _self = this;
	var url_regex = /((?:(http|https|Http|Https|rtsp|Rtsp):\/\/(?:(?:[a-zA-Z0-9\$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,64}(?:\:(?:[a-zA-Z0-9\$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,25})?\@)?)?((?:(?:[a-zA-Z0-9][a-zA-Z0-9\-]{0,64}\.)+(?:(?:aero|arpa|asia|a[cdefgilmnoqrstuwxz])|(?:biz|b[abdefghijmnorstvwyz])|(?:cat|com|coop|c[acdfghiklmnoruvxyz])|d[ejkmoz]|(?:edu|e[cegrstu])|f[ijkmor]|(?:gov|g[abdefghilmnpqrstuwy])|h[kmnrtu]|(?:info|int|i[delmnoqrst])|(?:jobs|j[emop])|k[eghimnrwyz]|l[abcikrstuvy]|(?:mil|mobi|museum|m[acdghklmnopqrstuvwxyz])|(?:name|net|n[acefgilopruz])|(?:org|om)|(?:pro|p[aefghklmnrstwy])|qa|r[eouw]|s[abcdeghijklmnortuvyz]|(?:tel|travel|t[cdfghjklmnoprtvwz])|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw]))|(?:(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9])\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])))(?:\:\d{1,5})?)(\/(?:(?:[a-zA-Z0-9\;\/\?\:\@\&\=\#\~\-\.\+\!\*\'\(\)\,\_])|(?:\%[a-fA-F0-9]{2}))*)?(?:\b|$)/gi;
	var regex = new RegExp(url_regex);
	var matches = this.$postbox.$textarea.val().match(regex);
	var $postboxcontainer = this.$postbox.$textarea.parent();

	if (null !== matches && jQuery(matches).size() > 0) {
		// use POST to avoid conflicts in the URL
		var req = {url : matches[0]};
		$PeepSo.postJson(
			"postbox.get_url_preview",
			req,
			function(json) {
				// Show preview, attach the html
				if (json.success) {
					if (jQuery($postboxcontainer).find(".url-preview-container").length === 0) {			// No container for the post mood is defined
						// create the post mood container
						_self.$url_preview_container.html(json.data.html);
						jQuery($postboxcontainer).append(_self.$url_preview_container);
						_self.$url_preview_container.find(".remove-preview")
							.on("click", function(e) {
								_self.show_preview = false;
								e.preventDefault();
								_self.$url_preview_container.empty().remove();
								_self.can_submit = false;
							});

					}

					// Prevent calling this again
					_self.found_url = true;
					_self.can_submit = true;
					_self.$postbox.on_change();
				}
			}
		);
	}
};

/**
 * Appends a newly added post's HTML to the activity stream
 * @param  {array} json The AJAX response from add_post
 */
PsPostBox.prototype.append_to_stream = function(json) {
	if (jQuery("#ps-no-posts").length > 0) {
		// special case for stream/profile when no posts are showing
		jQuery(this.$activity_stream.css("display", "block"));
		jQuery("#ps-no-posts").remove();
	}
	// hook up the drop-down menu within the new post
	var post_id = json.data.post_id;

	jQuery(json.data.html).hide().prependTo(this.$activity_stream).fadeIn("slow", function() {
		jQuery(this).find(".comment-container").hide();
		jQuery(document).trigger("ps_activitystream_append", [jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .ps-dropdown-toggle")]);
	});

	jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .ps-dropdown-toggle")
			.click(dropdown_toggle_click)
			.on("mouseenter", dropdown_toggle_mouseenter);
	jQuery("#peepso-wrap .ps-js-activity--" + post_id + " .dropdown-menu a")
			.click(dropdown_toggle_a_click);

	ps_observer.apply_filters("peepso_posttabs_cancel-status");

	return;
};

var postbox = new PsPostBox();

jQuery(document).ready( function ()
{
	postbox.init();
});

/**
 * Workaround for IE11 placeholder support.
 */
jQuery(function() {
	jQuery.support.placeholder = false;
	webkit_type = document.createElement("input");
	if ("placeholder" in webkit_type)
		jQuery.support.placeholder = true;

	if (!jQuery.support.placeholder) {
		var active = document.activeElement;
		jQuery("textarea").focus(function () {
			if ((jQuery(this).attr("placeholder")) && (jQuery(this).attr("placeholder").length > 0) &&
				("" !== jQuery(this).attr("placeholder")) && jQuery(this).val() === jQuery(this).attr("placeholder"))
				jQuery(this).val("").removeClass("hasPlaceholder");
		}).blur(function () {
			if ((jQuery(this).attr("placeholder")) && (jQuery(this).attr("placeholder").length > 0) &&
				("" !== jQuery(this).attr("placeholder")) && ("" === jQuery(this).val() || jQuery(this).val() === jQuery(this).attr("placeholder")))
				jQuery(this).val(jQuery(this).attr("placeholder")).addClass("hasPlaceholder");
		});

		jQuery("textarea").blur();
		jQuery(active).focus();
		jQuery("form").submit(function () {
			jQuery(this).find(".hasPlaceholder").each(function() {
				jQuery(this).val("");
			});
		});
	}
});

// EOF

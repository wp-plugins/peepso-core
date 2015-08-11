/*
 * Implements a pop-up window object for dynamic UI window interactions
 * @package PeepSo
 * @author PeepSo
 */

//$PeepSo.log("pswindow.js");

function PsWindow()
{
	this.is_created = false;
	this.is_visible = false;
	this.close_callback = null;
	this.action_callback = null;
	this.delete_callback = null;
	this.confirm_callback = null;
	this.no_confirm_callback = null;
	this.title = null;
	this.winwidth = 0;
	this.width = 400;
	this.winheight = 0;
	this.height = "auto";
	this.is_modal = true;
	this.$container = null;
	this.fade_time = 2500; //2.5 seconds
}

var pswindow = new PsWindow();

/**
 * Creates the window
 */
PsWindow.prototype.create = function()
{
	if (jQuery("#ps-window").length === 0) {
		// create the <div> with the window
		var html = [
			'<div id="ps-window" class="ps-dialog-wrapper">',
				'<div class="ps-dialog-container">',
					'<div class="ps-dialog">',
						'<div class="ps-dialog-header">',
							'<div id="cWindowContentTop">',
								'<div id="cwin_logo" class="ps-dialog-title"></div>',
								'<a class="ps-dialog-close" href="javascript:void(0);" onclick="pswindow.hide();return false;" id="cwin_close_btn">',
									'<span class="ps-icon-remove"></span>',
								'</a>',
							'</div>',
						'</div>',
						'<div id="cWindowContentWrap" class="ps-dialog-body">',
							'<div id="cWindowContent"></div>',
						'</div>',
						'<div id="cWindowContentOuter" class="ps-dialog-footer"></div>',
					'</div>',
				'</div>',
			'</div>'
			].join("");

		var psWindow = jQuery(html);
		var that = this;
		psWindow.appendTo( document.body );
		that.is_created = true;
		that.$container = jQuery("#ps-window");

		jQuery(document).on("keyup.pswindow", function(e) { that.keyup(e); } );

	}
	this.winwidth = jQuery( document.body ).width();			// always recalculate at time of call
	this.winheight = jQuery(window).height();
	return (this);
};

/**
 * Handles keyup event, hide window if escape key is pressed
 * @param {object} e Event triggered
 */
PsWindow.prototype.keyup = function(e)
{
	if (this.is_visible && 27 === e.keyCode)
		this.hide();
	return (this);
};

/**
 * Mouse up handler
 * @param {object} e Event triggered
 */
PsWindow.prototype.mouse_up = function(e)
{
	// commented out because of conflict with cropping image from a pswindow
	/*if (!this.is_visible)
		return;
	if (!PsWindow.$container.is(e.target) &&				// if the target of the click isn't the container...
		0 === PsWindow.$container.has(e.target).length) {	// ... nor a descendant of the container
		this.hide();
	}*/
	return (this);
};

/**
 * Sets window content
 * @param {string} content HTML content definition
 */
PsWindow.prototype.set_content = function(content)
{
	jQuery("#cWindowAction").remove();
 	jQuery("#cWindowContent").html(content);
	return (this);
};

/**
 * Sets window title
 * @param {string} title Title of the window
 */
PsWindow.prototype.set_title = function(title)
{
	jQuery("#cwin_logo").html(title);
	return (this);
};

/**
 * Sets window class
 * @param {string} cls Name of the class attribute
 */
PsWindow.prototype.set_class = function(cls)
{
	jQuery("#ps-window").attr("class", cls);
	return (this);
};

/**
 * Shows window
 * @param {string} title Title of the window
 * @param {string} content HTML content definition
 * @param {int} fade Number of milliseconds to fade
 */
PsWindow.prototype.show = function(title, content, fade)
{
	if (this.is_visible)
		return (false);					// don't display a new window if one is already visible

	var that = this;

	this.create()
		.set_title(title)
		.set_content(content);
	this.$container.show();

	this.refresh();
	this.is_visible = true;

	if (undefined !== fade)
		this.fade_out(fade);

	return (this);
};

/**
 * Appends actions to window container
 * @param {string} actions HTML definitions
 */
PsWindow.prototype.set_actions = function(actions)
{
	jQuery("#cWindowAction").remove();
	jQuery('<div id="cWindowAction">')
		.html(actions)
		.appendTo("#cWindowContentOuter");

	return (this);
};

/**
 * Reinitializes window size/location
 */
PsWindow.prototype.refresh = function()
{
	this.$container.show();
	return (this);
};

/**
 * Hides window
 */
PsWindow.prototype.hide = function()
{
	jQuery("#ps-window").hide();

	jQuery("#cwin_logo").html("");				// hide the content in case not set on next show()
	jQuery("#cWindowContent").html("");
	jQuery("#cWindowAction").remove();

	this.is_visible = false;
	jQuery("#ps-window").trigger("pswindow.hidden");

	// notificy others that the window is closing
	if (undefined !== typeof(ps_observer))
		ps_observer.apply_filters("pswindow_close");

	return (this);
};

/**
 * Fades out the window after a specified interval
 * @param {int} speed The number of milliseconds for the window to fade
 * @param {function} callback Function to be called before fade
 */
PsWindow.prototype.fade_out = function(speed, callback)
{
	var that = this;

	this.$container.fadeOut(speed, function() {
		that.hide();

		if (typeof(callback) === typeof(Function))
			callback();
	});

	return (this);
};


/**
 * Confirms the delete process
 * @param {function} deleteCallback Function to be called before delete
 * @param {string} content String used to override the default message
 */
PsWindow.prototype.confirm_delete = function(deleteCallback, content)
{
	this.delete_callback = deleteCallback;

	var data = peepsowindowdata || {};
	var title = data.label_confirm_delete;
	var actions = [
		'<button type="button" class="ps-btn ps-btn-small ps-button-cancel" onclick="pswindow.hide(); return false;">', data.label_cancel, '</button>',
		'<button type="button" class="ps-btn ps-btn-small ps-button-action" onclick="pswindow.do_delete();">', data.label_delete, '</button>'
	].join(' ');

	content = content || data.label_confirm_delete_content;

	this.show(title, content).set_actions(actions);

	return (this);
};

/**
 * Shows a message dialog box for user acknowledgement
 * @param {string} message The message to display within the modal
 * @param {string} title The optional title to display on the modal window
 */
PsWindow.prototype.acknowledge = function(message, title)
{
	this.confirm_callback = null;
	this.no_confirm_callback = null;

	var data = peepsowindowdata || {};
	var actions = [
		'<button type="button" class="ps-btn ps-btn-small ps-button-action" onclick="pswindow.hide(); return false;">', data.label_okay, '</button>'
	].join(' ');

	title = title || data.label_confirm;
	content = message || '';
	
	this.show(title, content).set_actions(actions);

	return (this);
};

/**
 * Shows a confirmation dialog box
 * @param  {string}   message  The confirmation message
 * @param  {Function} callback A function to run when a user clicks 'yes'
 * @param  {Function} no_confirm_callback A function to run when a user clicks 'no'
 */
PsWindow.prototype.confirm = function(message, callback, no_confirm_callback)
{
	this.confirm_callback = callback;
	this.no_confirm_callback = no_confirm_callback;

	var title = peepsowindowdata.label_confirm;
	var content = '<div>{content}</div>';
	var actions = [
		'<button type="button" class="ps-btn ps-btn-small ps-button-cancel" onclick="return pswindow.do_no_confirm();">', peepsowindowdata.label_no, '</button>',
		'<button type="button" class="ps-btn ps-btn-small ps-button-action" onclick="return pswindow.do_confirm();">', peepsowindowdata.label_yes, '</button>'
	].join(' ');

	content = content.replace("{content}", message);

	this.show(title, content).set_actions(actions);

	return (this);
};

/**
 * Performs the delete callback function if any
 */
PsWindow.prototype.do_delete = function()
{
	if (typeof(this.delete_callback) === typeof(Function))
		this.delete_callback();			// it's a function, we can safely call it
	return (this);
};

/**
 * Performs the confirm callback function if any
 */
PsWindow.prototype.do_confirm = function()
{
	if (typeof(this.confirm_callback) === typeof(Function))
		this.confirm_callback();			// it's a function, we can safely call it
	return (this);
};

/**
 * Performs the confirm callback function if any
 */
PsWindow.prototype.do_no_confirm = function()
{
	if (typeof(this.no_confirm_callback) === typeof(Function))
		this.no_confirm_callback();			// it's a function, we can safely call it

	this.hide();
	return (this);
};

/*
 * Implementation of the PsMessage class
 */

function PsMessage()
{
	this.is_created = false;
	this.is_visible = false;
	this.title = null;
	this.winwidth = 0;
	this.winheight = 0;
	this.$container = null;
	this.wrap_id = null;
	this.fade_time = pswindow.fade_time;
}

var psmessage = new PsMessage();

/**
 * Creates the message dialog
 */
PsMessage.prototype.create = function()
{
	if (0 === jQuery("#ps-message").length) {
		// create the <div> with the message
		var html = [
			'<div id="ps-message" class="ps-dialog-wrapper">',
				'<div class="ps-dialog-container">',
					'<div class="ps-dialog">',
						'<div class="ps-dialog-header">',
							'<div id="c-message-content-top">',
								'<div id="cmsg_logo" class="ps-dialog-title"></div>',
								'<a class="ps-dialog-close" href="javascript:void(0);" onclick="psmessage.hide();return false;" id="c-msg-close-btn">',
									'<span class="ps-icon-remove"></span>',
								'</a>',
							'</div>',
						'</div>',
						'<div id="c-message-content-wrap" class="ps-dialog-body">',
							'<div id="c-message-content"></div>',
						'</div>',
						'<div id="c-message-content-outer" class="ps-dialog-footer"></div>',
					'</div>',
				'</div>',
			'</div>'
		].join("");

		var psMessage = jQuery(html);
		var that = this;
		// checks if peepso-wrap is not available and alternative wrapper is set and available
		// if (0 === jQuery("#peepso-wrap").length && null !== this.wrap_id && 0 !== jQuery('#' + this.wrap_id).length) {
		// 	// appends the peepso-wrap div element, preprending creates an extra space on top of the wrapper
		// 	jQuery('#' + this.wrap_id).append('<div id="peepso-wrap"></div>');
		// 	// set the position of peepso-wrap to its parent wrapper since style="position:absolute" or prepending is not working
		// 	var position = jQuery('#' + this.wrap_id).position();
		// 	jQuery('#peepso-wrap').css({
		// 		position: "absolute",
		// 		left: position.left + 'px',
		// 		top: position.top + 'px'
		// 	});
		// }
		psMessage.appendTo( document.body );
		that.is_created = true;
		that.$container = jQuery("#ps-message");

		jQuery(document).on("keyup.psmessage", function(e) { that.keyup(e); } );
	}
	this.winwidth = jQuery( document.body ).width(); // always recalculate at time of call
	this.winheight = jQuery(window).height();
	return (this);
};


/**
 * Handles the keyup event
 * @param {object} e Event triggered
 */
PsMessage.prototype.keyup = function(e)
{
	if (this.is_visible && 27 === e.keyCode)
		this.hide();
};

/**
 * Sets message dialog content
 * @param {string} content HTML content definition
 */
PsMessage.prototype.set_content = function(content)
{
	jQuery("#c-message-action").remove();
 	jQuery("#c-message-content").html(content);
	return (this);
};

/**
 * Sets message dialog title
 * @param {string} title Title of the window
 */
PsMessage.prototype.set_title = function(title)
{
	jQuery("#cmsg_logo").html(title);
	return (this);
};

/**
 * Sets message dialog class
 * @param {string} cls Name of the class attribute
 */
PsMessage.prototype.set_class = function(cls)
{
	jQuery("#ps-message").attr("class", cls);
	return (this);
};

/**
 * Shows message dialog
 * @param {string} title Title of the window
 * @param {string} content HTML content definition
 * @param {int} fade Number of milliseconds to fade
 */
PsMessage.prototype.show = function(title, content, fade)
{
	if (this.is_visible) {
		this.$container.finish();
		this.hide();
	}

	this.create()
		.set_title(title)
		.set_content(content);
	this.$container.show();

	this.refresh();
	this.is_visible = true;

	if (undefined !== fade)
		this.fade_out(fade);

	return (this);
};

/**
 * Hides message dialog
 */
PsMessage.prototype.hide = function()
{
	jQuery("#ps-message").hide();

	jQuery("#cmsg_logo").html(""); // hide the content in case not set on next show()
	jQuery("#c-message-content").html("");
	jQuery("#c-message-action").remove();

	this.is_visible = false;
	jQuery("#ps-message").trigger("psmessage.hidden");

	return (this);
};

/**
 * Fades out the message dialog after a specified interval
 * @param {int} speed The number of milliseconds for the window to fade
 * @param {function} callback Function to be called before fade
 */
PsMessage.prototype.fade_out = function(speed, callback)
{
	var that = this;

	this.$container.fadeOut(speed, function() {
		this.is_visible = false;
		that.hide();

		if (typeof(callback) === typeof(Function))
			callback();
	});

	return (this);
};

/**
 * Reinitializes message dialog size/location
 */
PsMessage.prototype.refresh = function()
{
	this.$container.show();
	return (this);
};

// EOF

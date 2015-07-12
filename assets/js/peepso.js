/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

/*
 * PeepSo AJAX class
 * @package PeepSo
 * @aithor PeepSo
 */

function PeepSo()
{
	// if global 'peepsodata' exists, use settings from it
	if ("undefined" !== typeof(peepsodata))
	{
		this.defaultUrl = peepsodata.ajaxurl;
		this.userId = parseInt(peepsodata.userid);
	}
}

PeepSo.prototype =
{
	error: false,						// true if error occured
	errorText: "",						// error message
	errorStatus: "",					// error status
	callback: null,						// callback function for successful requests
	trxComplete: 0,						// set to 1 when done with ajax call and transaction is complete
	errorCallback: null,				// callback function for error requests
	validationErrors: new Array(),		// validation error information
	url: "",							// url to send request to
	ret: 0,								// return value
	timeout: 0,							// timeout for request
	enableValidation: 1,				// 1 = enabled, 0 = disabled
	formElementType: "td",				// DOM element that wraps form elements (the <input> element)
	async: true,						// when true, do asynchronous calls
	defaultUrl: null,					// default server side script to connect to
	userId: null,						// user id
	action: "",							// url part of the function call
	authRequired: true,					// set to false if user is not required to be logged in for ajax request

	// initialize error and callback information
	init: function(ajaxCallback, sUrl)
	{
		this.error = false;
		this.errorText = "";
		this.errorStatus = "";
		this.callback = ajaxCallback;
		this.trxComplete = 0;
		this.errorCallback = null;
//if ("undefined" === typeof(sUrl) || null === sUrl)
//	this.url = $PeepSo.defaultUrl;
//else
//	this.url = sUrl;
		this.url = peepsodata.ajaxurl + sUrl;
		this.action = sUrl;
		this.timeout = 0;
		this.enableValidation = 1;
		this.async = true;
	},

	// default callback method for all PeepSo Ajax functions
	peepSoCallback: function(jsonData)
	{
		if (null === jsonData) {
			this.trxComplete = 1;
			return;
		}

		// the following sections assume certain data values are set within the
		// json data. Use the AjaxResponse class to create these.

		// check for '.session_timeout' and go to login page
		try {
			if (this.authRequired && "undefined" !== typeof(jsonData.session_timeout) && "auth.login" !== this.action) {
				jQuery(window).trigger("peepso_auth_required", [this, jsonData]);
				this.trxComplete = 1;
				return;
			}
		} catch (e) { }

		// check for setting focus
		if ("undefined" !== typeof(jsonData.focus) && null !== jsonData.focus) {
			// look for <input id=>
			if (document.getElementById(jsonData.focus) !== null)
				document.getElementById(jsonData.focus).focus();
			else {
				// for for <form id=><input name=>
				var sel = "#" + jsonData.form + ' [name="' + jsonData.focus + '"]';
				jQuery(sel).focus();
			}
		}
// response:
// {"session_timeout":1,"errors":["Invalid credentials",""],"has_errors":1,"success":0}

		// check for messages
		try {
			// look for '.errors' and display them

			if ("undefined" !== typeof(jsonData.errors)) {
				var errorMsg = "";
				if (jsonData.errors.length > 0) {
					for (x = 0; x < jsonData.errors.length; x++) {
						if ("undefined" !== typeof(jsonData.errors[x]["error"]))
							errorMsg += "<p>" + jsonData.errors[x]["error"] + "</p>";
					}

					if ("" !== errorMsg)
						pswindow.show(peepsodata.label_error, errorMsg);
				}
			}

			// look for '.notices' and display them
			if ("undefined" !== typeof(jsonData.notices)) {
				var noticeMsg = "";
				if (jsonData.notices.length > 0) {
					for (x = 0; x < jsonData.notices.length; x++) {
						if (typeof(jsonData.notices[x]["message"]) !== "undefined")
							noticeMsg += jsonData.notices[x]["message"] + "\n";
					}

					if ("" !== noticeMsg)
						pswindow.show(peepsodata.label_notice, noticeMsg);
				}
			}
		} catch (e) { }

		// check result for validation errors
		if ("undefined" !== typeof(jsonData.success) && 1 === jsonData.success &&
			"undefined" !== typeof(jsonData.form) && "" !== jsonData.form) {
			// no errors, clear any previos error messages
			this.clearValidation(jsonData.form);
		}

		// markup DOM with validation error messages
		try {
			if (this.enableValidation) {
				if ("undefined" !== typeof(jsonData.validation) &&
					"undefined" !== typeof(jsonData.form) && "" !== jsonData.form)
				{
					if (jsonData.validation.length > 0)
					{
						// clear the errors for this form:
						this.clearValidation(jsonData.form);

						// add a class to the form for displaying messages
						var form = jQuery("#" + jsonData.form);
						jQuery(form).addClass("validation-errors");

						// apply validation error messages to elements in the form:
						for (x = 0; x < jsonData.validation.length; x++)
						{
							var fieldName = jQuery('[name="' + jsonData.validation[x].fieldName + '"]');
							jQuery(fieldName).addClass("validation-error");

							// find the closes wrapping element, then append error message
							var fieldParent = fieldName.closest(this.formElementType);
							fieldParent.append('<div class="validation-msg">' + jsonData.validation[x].errorMessage  + "</div>");
						}
					}
				}
			}
		} catch (e) { }

		// if there is a callback function, call it
		if ("function" === typeof(this.callback)) {
			try {
				this.callback(jsonData);
			} catch (e) {
			}
		}

		this.trxComplete = 1;
	},

	// remove any validation messages within <form id=> DOM
	clearValidation: function(form)
	{
		form = jQuery("#" + form);

		jQuery(form).find("div.validation-msg").each (function() { jQuery(this).remove(); });
		jQuery(form).find("input, select, textarea").each (function() { jQuery(this).removeClass("validation-error"); });
		jQuery(form).find(this.formElementType + ".errorItem").each (function() { jQuery(this).removeClass("errorItem"); });
	},

	// perform ajax get operation
	get: function(request, data, success_callback, datatype)
	{
		var inst = new PeepSo();					// create a new PeepSo instance

		// setting a custom timeout
		var timeout = this.timeout;
		inst.async = this.async;

		target_url = peepsodata.ajaxurl + request;
$PeepSo.log("target=[" + target_url + "]");

		inst.init(success_callback, request); // target_url);
		if ("undefined" === typeof(datatype) || "" === datatype)
			datatype = "json";
		inst.ret = jQuery.get(inst.url, data, function(data) {
			inst.peepSoCallback(data);
		}, datatype, { timeout: timeout },
		{ async: inst.async } );

		return (inst);
	},

	// perform ajax get, forcing content type and data type to json
	getJson: function(target_url, data, success_callback)
	{
		var inst = new PeepSo();
		inst.init(success_callback, target_url);
		inst.async = this.async;
		inst.authRequired = this.authRequired;
		inst.errorDisabled = this.errorDisabled;

		var req = {
			type: "GET",
			url: inst.url,
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: data,
			success: function(data) { inst.peepSoCallback(data); },
			error: function(jqXHR, textStatus, errorThrown) {
				inst.ajaxError(jqXHR, textStatus, errorThrown);
			},
			async: inst.async
		};

		this.authRequired = true;
		this.errorDisabled = false;
		return (jQuery.ajax(req));
	},

	// perform ajax post operation
	post: function(targetUrl, data, successCallback, dataType)
	{
		var inst = new PeepSo();
		inst.init(successCallback, targetUrl);

		inst.errorCallback = this.errorCallback;
		inst.enableValidation = this.enableValidation;
		inst.async = this.async;
		//set a custom timeout
		inst.timeout = this.timeout;
		inst.authRequired = this.authRequired;

		if ("undefined" === typeof(dataType) || "" === dataType)
			dataType = "json";

		if (dataType === "json") {
			inst.ret = this.postJson(targetUrl, data, successCallback);
			return (inst);
		}
		inst.ret = jQuery.post(inst.url, data, function(data) {
			inst.peepSoCallback(data);
		}, dataType, { timeout : this.timeout },
		{ async: inst.async } );
		this.authRequired = true;
		return (inst);
	},

	// perform ajax post operation with all form elements within a container
	postElems: function(target_url, req, success_callback, datatype)
	{
		// req has the following properties:
		//		.container	- name of jQuery selector for form container
		//		.action		- name of 'action' property to include in post data
		//		.req		- name of 'req' property to include in post data

		var inst = new PeepSo();
		inst.init(success_callback, target_url);
		inst.async = this.async;
		if ("undefined" === typeof(datatype) || null === datatype)
			datatype = "json";

		// collect data from the container
		var data = jQuery(req.container).find("input").serializeArray();
		data = jQuery.merge(data, jQuery(req.container).find("select").serializeArray());
		data = jQuery.merge(data, jQuery(req.container).find("textarea").serializeArray());
		// add the action and call attributes
		data.push( { name: "action", value: req.action } );
		data.push( { name: "req", value: req.req } );

		inst.ret = jQuery.post(inst.url, data, function(data) {
			inst.peepSoCallback(data);
		}, datatype,
		{ async: inst.async });
		return (inst);
	},

	// perform ajax post, forcing content type and data type to json
	postJson: function(target_url, data, success_callback)
	{
		var inst = new PeepSo();
		inst.init(success_callback, target_url);
		inst.async = this.async;
		inst.authRequired = this.authRequired;
		inst.errorDisabled = this.errorDisabled;
		inst.errorCallback = this.errorCallback;
		inst.enableValidation = this.enableValidation;
		var req = {
			type: "POST",
			url: inst.url,
			dataType: "json",
			data: data,
			timeout: this.timeout,
			success: function(data) { inst.peepSoCallback(data); },
			error: function(jqXHR, textStatus, errorThrown) {
				inst.ajaxError(jqXHR, textStatus, errorThrown);
			},
			async: inst.async
		};

//		inst.ret = jQuery.ajax(req);
		inst.ret = jQuery.post(req.url, data, function(data) { req.success(data); }, "json"); // peepsoCallback(data) });
//			.done(function(e) { console.log("== sucess") })
//			.fail(function(e) { console.log("== failure") })
//			.always(function(e) { console.log("== always") });
		this.authRequired = true;
		this.errorDisabled = false;
		return (inst);
	},

	//sets an optional timeout
	setTimeout: function(seconds)
	{
		this.timeout = seconds;
		return (this);
	},

	// turns off or off validation
	setValidation: function(val)
	{
		if ("undefined" !== typeof(val)) {
			if (val)
				this.enableValidation = 1;
			else
				this.enableValidation = 0;
		}
		return (this);
	},

	// disables asynchronous calls for current instance
	disableAsync: function()
	{
		this.async = false;
		return (this);
	},

	// disables authentication for this instance
	disableAuth: function()
	{
		this.authRequired = false;
		return (this);
	},


	// sets the error callback function for this instance
	setErrorCallback: function(errCallback)
	{
		this.errorCallback = errCallback;
		return (this);
	},

	// sets the form element type
	setFormElement: function(sElemName)
	{
		// Used to set the form element type. This is the element type that wraps
		// the individual <form> elements and is used to add validation messages
		// to the DOM.
		// If you are using tables, this should be "td". If each element is wrapped
		// in a <div> use "div". If you're using <li>s then "li".
		this.formElementType = sElemName;
		return (this);
	},

	// standard handler for ajax errors
	ajaxError: function(XMLHttpReq, textStatus, errorThrown)
	{
		this.error = true;				// set error state to true
		this.errorStatus = textStatus || "";

		if ("undefined" === typeof(XMLHttpReq)) {
			this.errorText = "Undefined error.";
		} else {
			this.errorText = XMLHttpReq.responseText || "Connection timeout.";
		}

		if (this.errorDisabled) {
			this.log(this.errorStatus, this.errorText);
		} else {
			pswindow.show(this.errorStatus, this.errorText);
		}

		if ("function" === typeof(this.errorCallback))
			this.errorCallback();			// it's a function, we can safely call it
	},

	// enable error for particular instance
	enableError: function() {
		this.errorDisabled = false;
		return (this);
	},

	// disable error for particular instance
	disableError: function() {
		this.errorDisabled = true;
		return (this);
	},

	// perform console logging if console is available
	log: function() {
		if (window.console) {
			console.log.apply(console, arguments);
		}
	},

	// return window size
	screenSize: function() {
		var winwidth = window.innerWidth,
			size;

		if ( winwidth <= 360 ) {
			size = 'xsmall';
		} else if ( winwidth <= 480 ) {
			size = 'small';
		} else if ( winwidth <= 991 ) {
			size = 'medium';
		} else {
			size = 'large';
		}

		return size;
	}
};

ppso = peepso = $PeepSo = new PeepSo();				// create global instance
//$PeepSo.log("created global $PeepSo instance");

jQuery(document).ready(function() {
	/**
	 * Display the login dialog if a session_timeout is returned and authRequired is set to true
	 * @param  {object} e         The jQuery event
	 * @param  {object} peepso    instance of PeepSo
	 * @param  {object} jsonData  The response from the ajax request
	 */
	jQuery(window).on("peepso_auth_required", function(e, peepso, jsonData) {
		jQuery(".login-area input").attr('disabled', true);
		// Hide any open pswindows
		if (pswindow.is_visible)
			pswindow.hide();
		// TODO: string needs to be translatable
		pswindow.show("Please login to continue", jsonData.login_dialog);
		jQuery(document).trigger("peepso_login_shown");
		jQuery("#ps-window").one("pswindow.hidden", function() {
			jQuery(".login-area input").removeAttr('disabled');
		});

		return;
	});

	jQuery('.ps-tab-bar a[data-toggle=tab]').on('click.ps-tab', function (e) {
		jQuery( e.target ).addClass('active')
			.siblings('a[data-toggle=tab]').removeClass('active');
	});
});


ps_login = {};

/**
 * called on successful login
 * @param {object} resp Response object
 */
ps_login.post_callback = function(resp)
{
	if (resp.success) {
		document.location = resp.data.url;
	} else {
		if (resp.has_errors)
			jQuery("#errlogin").html(resp.errors[0]).css("display", "block");
	}
};

/**
 * called on login button click
 * @param {object} e Event triggered
 */
ps_login.form_submit = function(e)
{
	e.preventDefault();

	var form = jQuery(e.target);

	var data = {
		username: form.find("[name=username]").val(),
		password: form.find("[name=password]").val(),
		security: jQuery("#security").val(),
		remember: form.find("[name=remember]").is(':checked')
	};

	if (_.isEmpty(data.username) && _.isEmpty(data.password))
		return;

	var btnSubmit = form.find("[type=submit]");

	btnSubmit.attr("disabled", true);
	btnSubmit.find("img").show();

	$PeepSo.postJson('auth.login', data, function(response) {
		btnSubmit.removeAttr("disabled");
		btnSubmit.find("img").hide();

		if (response.success) {
			window.location = jQuery('input[name="redirect_to"]').val();
		} else if (false === pswindow.acknowledge(response.errors, response.data.dialog_title)) { // try to show a pswindow
			// Fallback to displaying on div
			form.find(".errlogin").html(response.errors[0]).css("display", "block");
		}
	});

	return (false);
};

// EOF

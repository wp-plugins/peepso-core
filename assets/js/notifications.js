// Available options:
// 	view_all_text, string
// 	view_all_link, string
// 	source, // string - the URL to retrieve the view
// 	request, // json - additional parameters to send to opts.source via ajax
// 	paging, // boolean - enables the scroll pagination
//

// TODO: reimplement using prototype

(function($){
	function PsPopoverNotification(elem, options)
	{
		var _self = this;
		this.popover = null;
		this.popover_list = null;
		this.popover_footer = null;
		this.popover_header = null;
		this._notifications = {}; // array of HTML to be inserted to the dropdown list

		this.init = function(opts) {
			_opts = {
				view_all_text: peepsodata.view_all_text,
				view_all_link: null,
				source: null, // the URL to retrieve the view
				request: { // additional parameters to send to opts.source via ajax
					per_page: 10,
					page: 1
				},
				header: null,  // HTML to be displayed on the top section of the notification
				paging: false, // set  this to true if you want to enable scrolling pagination
				fetch: null // Function used to modify the request data. Returning false will prevent the fetch operation
			};

			this.opts = ps_observer.apply_filters('peepso_notification_plugin_options', _opts);

			this._content_is_fetched = false;
			$.extend(true, this.opts, opts);

			$(elem).addClass("psnotification-toggle");
			this.popover = $("<div>");
			this.popover_list = $("<ul>");
			$(elem).append(this.popover);

			// Add header
			if (false === _.isNull(this.opts.header)) {
				this.popover_header = $("<div/>");
				this.popover_header
					.addClass("ps-popover-header app-box-header")
					.append(this.opts.header)
					.append("<div class='clearfix' />");
				this.popover.append(this.popover_header);
			}

			// Add list container
			this.popover.append(this.popover_list);
			this.popover_list.addClass("ps-popover-list empty");
			this.popover.addClass("ps-popover app-box").hide();

			if (this.opts.paging)
				this.init_pagination();

			// Add view all link
			if (false === _.isNull(this.opts.view_all_link)) {
				this.popover_footer = $("<div/>");
				this.popover_footer
					.addClass("ps-popover-footer app-box-footer")
					.append("<a href='" + this.opts.view_all_link + "'>" + this.opts.view_all_text + "</a>");

				this.popover.append(this.popover_footer);
			}
		};

		this.fetch = function() {
			var req = this.opts.request;

			// Allow scripts to customize the request further
			if (_.isFunction(this.opts.fetch)) {
				req = this.opts.fetch.call(this, req);

				if (false === req)
					return;
			}

			this._notifications = {};
			$PeepSo.disableAsync().getJson(this.opts.source, req, function(response) {
				if (response.success) {
					_self._content_is_fetched = true;
					_self._notifications = response.data.notifications;

					if (_self._notifications.length > 0)
						_self.opts.request.page++; // locks in to the last page that had available data, so when new data comes in we have the correct offset
				}
			});
		};

		this.refresh = function() {
			this.popover_list.find("li").remove();
			this._content_is_fetched = false;
			this.load_page(function() {
				if (_self.opts.paging)
					_self.popover_list.trigger("scroll");
			});
		};

		this.onClick = function(e) {
			if (_self.popover.has($(e.target)).length > 0)
				return;

			e.preventDefault();

			var isLazy = _self.opts.lazy;
			var isVisible = _self.popover.is(':visible');

			_self.show();
			!isLazy && !isVisible && _self.load_page(function() {
				if (_self.opts.paging)
					_self.popover_list.trigger("scroll");
			});
		};

		this.render = function() {
			$.each(this._notifications, function(i, not) {
				var notification = $("<li></li>");
				notification.html(not).hide();
				notification.appendTo(_self.popover_list).fadeIn('slow');
			});
			$(elem).trigger("notifications.shown", [$.extend(elem, this)]);
			this.popover_list.toggleClass("empty", 0 === this.popover_list.find('li').length);
		};

		this.show = function() {
			this.popover.slideToggle({
				duration: "fast",
				start: function() {
					_self.popover.position({
						my: "right top",
						at: "bottom",
						of: $('i', elem),
						within: "#peepso-wrap",
						using: function(position, data) {
							if ('right' === data.horizontal) {
								$(this).removeClass('flipped');
								position.left += 40;
							} else {
								$(this).addClass('flipped');
								position.left -= 40;
							}

							position.top += 10;

							$(this).css(position);
						}
					});
				},
				done: function() {
					$(document).on("mouseup.notification_click", function(e) {
						if (!$(elem).is(e.target) && 0 === $(elem).has(e.target).length) {
							_self.popover.hide();
							$(document).off("mouseup.notification_click");
						}
					});
				}
			});
		};

		this.init_pagination = function() {
			this.popover_list.scroll(function() {
				if (_self._content_is_fetched && $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
					_self._content_is_fetched = false;
					_self.load_page(function() {
						if (!_.isEmpty(_self._notifications))
							_self.popover_list.trigger("scroll");
					});
				}
			});
		};

		this.load_page = function(callback) {
			if (false === this._content_is_fetched) {
				var loading = $("<div class='ps-popover-loading'><img src='" + peepsodata.loading_gif + "'/></div>");
				this.popover_list.after(loading);

				setTimeout(
					function() {
						_self.fetch();
						loading.remove();
						_self.render();

						if (typeof(callback) === typeof(Function))
							callback();
					},
					500
				);
			}
		};

		this.init(options);
		$(elem).on("click", this.onClick);

		return this;
	}


	$.fn.psnotification = function(methodOrOptions) {
		return this.each(function () {
            if (!$.data(this, 'plugin_psnotification')) {
                $.data(this, 'plugin_psnotification',
                new PsPopoverNotification( this, methodOrOptions ));
            } else {
            	var _self = $.data(this, 'plugin_psnotification');

            	if (_.isFunction(_self[methodOrOptions]))
            		return _self[methodOrOptions].call(_self);
            }
        });
	};

	function get_latest_count() {
		if (peepsodata.currentuserid > 0) {
			$PeepSo.disableError().getJson("notificationsajax.get_latest_count", null, function(response) {
				var data, count, count_title, title, el;
				if (response.success && !response.session_timeout) {
					count_title = 0;
					jQuery.each( response.data, function( key, value ) {
						count = Math.max(0, value.count);
						if ( key.match(/ps-js-notifications|ps-js-friends-notification/) ) {
							count_title += count;
						}
						el = $("."+key);
						if (el.length) {
							el.find(".ps-js-counter")[ count ? "show" : "hide" ]().html(count);
						}
					});

					update_titlebar( count_title );
				}
			});
		}
	}

	function update_titlebar( count ) {
		var title = ( document.title || '' ).replace(/^\(\d+\)\s*/, '');
		if ( count > 0 ) {
			title = '(' + count + ') ' + title;
		}
		document.title = title;
	}

	get_latest_count();

	// Get notification counter loop.
	var get_latest_timer = setInterval(get_latest_count, 1000 * 30);
	$(window).on("peepso_auth_required", function() {
		clearInterval(get_latest_timer);
	});

})(jQuery);

// Working sample on how to use this extension.
// Dropdown for the notifications.
jQuery(".dropdown-notification").psnotification({
	view_all_link: peepsodata.notifications_page,
	source: 'notificationsajax.get_latest',
	request: {
		per_page: 5
	},
	paging: true
});

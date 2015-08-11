function PsMemberSearch()
{
	this.cur_req_string = "";
}

var ps_membersearch = new PsMemberSearch();

PsMemberSearch.prototype.init = function()
{
	var _self = this;

	jQuery(".member-search-toggle").each(function(index, e) {
		var $search_div = jQuery("#ps-member-search-html").clone();
		var $member_search_input = jQuery("input[name=query]", $search_div);

		var notif = jQuery(e).psnotification({
			view_all_link: "javascript: ps_membersearch.view_all();",
			source: "membersearch.search",
			request: { per_page: 10 },
			fetch: function(req) {
				_self.cur_req_string = this.popover_header.find("input").serialize();
				_self.query = this.popover_header.find("input[name=query]").val();
				req = _self.cur_req_string;

				return (req);
			},
			after_load: function() {
				if (notif._notifications && notif._notifications.length) {
					notif.popover_footer.find("a").html(notif._data.view_all_text);
					notif.popover_footer.show();
				} else {
					notif.popover_footer.hide();
				}
			},
			paging: false,
			lazy: true,
			header: $search_div
		}).on("notifications.shown", function(e, inst) {
			jQuery(".member-search-notice", $search_div)
				.toggleClass("hidden", (inst.popover_list.find("li").length > 0));
		});

		notif = notif.data("plugin_psnotification");
		notif.popover_footer.hide();

		$member_search_input.on("input", _.debounce(function(e) {
			var el = jQuery(e.target),
				val = jQuery.trim( el.val() );

			notif.popover_header.find(".member-search-notice").addClass("hidden");
			notif.popover_footer.hide();

			if ( !val ) {
				notif.popover_list.find("li").remove();
				return;
			}

			el.closest(".member-search-toggle").psnotification("refresh");
		}, 250 ));

		if ( $member_search_input.val() ) {
			$member_search_input.triggerHandler("input");
		}
	});
};

/**
 * View all members searched by refreshing or reloading the page
 */
PsMemberSearch.prototype.view_all = function()
{
	var redirect_url = peepsodata.members_page;
	if( null != this.query) {
		redirect_url = redirect_url + "?query=" + this.query;
	}

	window.location = redirect_url;
};

/**
 * TODO: docblock
 */
PsMemberSearch.prototype.sortby = function(method) {
	this.load_members_sort = method;
	this.load_members_page = 1;
	this.load_members_end = false;
	this.load_members();
};

/**
 * TODO: docblock
 */
PsMemberSearch.prototype.load_members = function() {
	var req = {
		uid: peepsodata.currentuserid,
		user_id: peepsodata.userid,
		is_page: 1,
		page: this.load_members_page,
		query: this.load_members_query || undefined
	};

	if (typeof this.load_members_ct === "undefined") {
		this.load_members_ct = jQuery(".ps-js-members--" + peepsodata.userid);
	}

	if (!this.load_members_ct.length) {
		return;
	}

	if (this.load_members_loading || this.load_members_end) {
		return;
	}

	this.load_members_loading = true;

	this.xhr && this.xhr.abort();
	this.xhr = $PeepSo.getJson("membersearch.search", req, jQuery.proxy(function(response) {
		var data = response.data || {};

		if (response.success) {
			if (req.page <= 1) {
				this.load_members_ct.empty();
			}

			this.load_members_ct.append( data.members.join('') );
			this.load_members_ct.find(".ps-js-beforeloaded").each(function() {
				jQuery(this).toggleClass("ps-js-beforeloaded loaded");
			});

			if (data.members_page >= 1) {
				this.load_members_page++;
			}
		} else {
			this.load_members_end = true;
			if (!(data.members_found >= 1)) {
				this.load_members_ct.html( response.errors.join('') );
			}
		}

		this.load_members_loading = false;
	}, this ));
};

/**
 * TODO: docblock
 */
function isElementInViewport(el) {
	var rect = el.getBoundingClientRect();
	return (
		rect.top >= 0 &&
		rect.left >= 0 &&
		rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
		rect.right <= (window.innerWidth || document.documentElement.clientWidth)
	);
}

/**
 * TODO: docblock
 */
PsMemberSearch.prototype.autoscroll = function() {
	var eventName = "scroll.ps-members",
		$trigger = jQuery(".ps-js-members-triggerscroll--" + peepsodata.userid),
		$win = jQuery(window);

	if (!$trigger.length) {
		return;
	}

	$win.off(eventName).on(eventName, jQuery.proxy(function() {
		if ( (!this.load_members_end) && isElementInViewport( $trigger[0] ) ) {
			this.load_members();
		}
	}, this ));
};

/**
 * TODO: docblock
 */
PsMemberSearch.prototype.search = function( data ) {
	if ( data && data.nodeType ) {
		data = jQuery( data ).find('[name=query]').val();
	}

	this.load_members_query = data;
	this.sortby();
};

jQuery(document).ready(function() {
	ps_membersearch.init();
	ps_membersearch.search( window.peepsomembersdata && peepsomembersdata.search || undefined );
	ps_membersearch.autoscroll();

	jQuery('.ps-js-members-query').on('input', _.debounce(function(e) {
		ps_membersearch.search( e.target.value );
	}, 250 ));
});

// EOF

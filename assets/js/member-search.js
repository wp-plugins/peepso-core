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
				req = _self.cur_req_string;

				return (req);
			},
			paging: false,
			lazy: true,
			header: $search_div
		}).on("notifications.shown", function(e, inst) {
			jQuery(".member-search-notice", $search_div)
				.toggleClass("hidden", (inst.popover_list.find("li").length > 0));
		});

		notif = notif.data("plugin_psnotification");

		$member_search_input.on("keyup", _.debounce(function(e) {
			var el = jQuery(e.target),
				val = jQuery.trim( el.val() );

			if ( !val ) {
				notif.popover_list.find("li").remove();
				return;
			}

			el.closest(".member-search-toggle").psnotification("refresh");
		}, 250 ));
	});
};

/**
 * View all members searched by refreshing or reloading the page
 */
PsMemberSearch.prototype.view_all = function()
{
	window.location = peepsodata.members_page + "?" + this.cur_req_string;
};

jQuery(document).ready(function() {
	ps_membersearch.init();
});

// EOF

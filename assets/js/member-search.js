/*
 * @copyright Copyright (C) 2014-15 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

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
		var $member_search_input = jQuery("input", $search_div);

		jQuery(e).psnotification({
			view_all_link: "javascript: ps_membersearch.view_all();",
			source: "membersearch.search",
			request: { per_page: 10 },
			fetch: function(req) {
				_self.cur_req_string = this.popover_header.find("input").serialize();
				req = _self.cur_req_string;

				return (req);
			},
			paging: false,
			header: $search_div
		}).on("notifications.shown", function(e, inst) {
			jQuery(".member-search-notice", $search_div)
				.toggleClass("hidden", (inst.popover_list.find("li").length > 0));
		});

		$member_search_input.on("keypress", function(e) {
			// check for enter key
			if (13 === e.keyCode)
				jQuery(".member-search-toggle").psnotification("refresh");
		});
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

/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

jQuery(document).ready(function($) {
	$("#peepso-wrap .ps-privacy-dropdown ul li a").click(function(e) {
		var a = $(e.target).closest("a"),
			btn = a.closest("ul").siblings("button"),
			input = btn.siblings("input"),
			placeHolder = btn.find("span#permission-placeholder");
		var menu = a.closest("ul.dropdown-menu");

		btn.find("i").attr("class", a.find("i").attr("class"));
		input.val(a.attr("data-option-value"));

		placeHolder.html(a.find("span").html())

		menu.css("display", "none");
	});

	$(".datepicker").datepicker(
		ps_observer.apply_filters('peepso_datepicker_options', 
			{
				format: peepsodata.date_format,
				multidateSeparator: false // Set this to false, to allow "," on date formats
			}
		)
	).on("changeDate", function(e) {
		var date = e.format('yyyy-mm-dd');
		var input_name = jQuery(this).data("input");
		jQuery("#" + input_name).val(date);
	});
});

// EOF
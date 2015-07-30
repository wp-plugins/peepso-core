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
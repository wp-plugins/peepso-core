//$PeepSo.log("dropdown.js");

function dropdown_toggle_click(e)
{
	var el = jQuery(this);
	var ct = el.closest(".ps-privacy-dropdown");
	var icon = ct.find(".ps-dropdown-toggle").find("i");
	var span = ct.prev(".ps-share-privacy");
	var menu = el.siblings("ul.dropdown-menu");

	jQuery("ul.dropdown-menu").not(menu).css("display", "none");

	if (menu.css("display") === "block") {
		el.removeClass("open");
		menu.css("display", "none");
		return;
	}

	el.addClass("open");
	menu.css("display", "block");

	if (icon && icon.length) {
		icon.attr("class", el.find("i").attr("class"));
	}

	if (span && span.length) {
		span.html(el.find("span").html());
	}

	jQuery(document).on("mouseup.peepso-dropdown", function(e) {
		if (!menu.is(e.target) && !el.is(e.target) && 0 === menu.has(e.target).length) {
			el.removeClass("open");
			menu.hide();
			jQuery(document).off("mouseup.peepso-dropdown");
		}
	});
}

function dropdown_toggle_mouseenter(e)
{
	var winsize = $PeepSo.screenSize();
	if (winsize !== 'small' && winsize !== 'xsmall') {
		// turn off any drop-downs when first entering a drop-down
		jQuery("#peepso-wrap .ps-dropdown-toggle").removeClass("open").siblings("ul.dropdown-menu").css("display", "none");
	}
}

function dropdown_toggle_a_click(e)
{
	var el = jQuery(this);

	el.parent().parent().css("display", "none")
		.siblings(".ps-dropdown-toggle").removeClass("open");

	var ct = el.closest(".ps-js-dropdown--privacy");
	var hidden = ct.children("[type=hidden]");
	var icon = ct.find(".ps-dropdown-toggle").find("i");
	var span = ct.find(".ps-dropdown-toggle").find(".ps-privacy-title");

	if (hidden && hidden.length) {
		hidden.val(el.data("option-value"));
	}

	if (icon && icon.length) {
		icon.attr("class", el.find("i").attr("class"));
	}

	if (span && span.length) {
		span.html(el.find("span").html());
	}
}

jQuery(document).ready(function($) {
	// set things up to handle general purpose drop-down menus
	$("#peepso-wrap").on("click", ".ps-dropdown-toggle", dropdown_toggle_click)
			.on("mouseenter", ".ps-dropdown-toggle", dropdown_toggle_mouseenter);
/*	$("#peepso-wrap .ps-dropdown-toggle").click( function(e) {
		var el = $(this);
		var ct = el.closest(".ps-privacy-dropdown");
		var icon = ct.find(".ps-dropdown-toggle").find("i");
		var span = ct.prev(".ps-share-privacy");
		jQuery("ul.dropdown-menu").css("display", "none");
		var menu = el.siblings("ul.dropdown-menu");
		menu.css("display", "block");

		if (icon && icon.length) {
			icon.attr("class", el.find("i").attr("class"));
		}

		if (span && span.length) {
			span.html(el.find("span").html());
		}
	}).on("mouseenter", function(e) {
		// turn off any drop-downs when first entering a drop-down
		jQuery("#peepso-wrap .ps-dropdown-toggle").siblings("ul.dropdown-menu").css("display", "none");
	}); */

	// hides dropdown menu after menu item is clicked
	$("#peepso-wrap").on("click", ".dropdown-menu a", dropdown_toggle_a_click);
/*	$("#peepso-wrap .dropdown-menu a").click(function(e) {
		jQuery(this).parent().parent().css("display", "none");
	}); */
});

// EOF
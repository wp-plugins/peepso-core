/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 *///$PeepSo.log("dropdown.js");
function dropdown_toggle_click(e){var t=jQuery(this),n=t.closest(".ps-privacy-dropdown"),r=n.find(".dropdown-toggle").find("i"),i=n.prev(".ps-share-privacy");jQuery("ul.dropdown-menu").css("display","none");var s=t.siblings("ul.dropdown-menu");s.css("display","block");r&&r.length&&r.attr("class",t.find("i").attr("class"));i&&i.length&&i.html(t.find("span").html())}function dropdown_toggle_mouseenter(e){jQuery("#peepso-wrap .dropdown-toggle").siblings("ul.dropdown-menu").css("display","none")}function dropdown_toggle_a_click(e){jQuery(this).parent().parent().css("display","none")}jQuery(document).ready(function(e){e("#peepso-wrap .dropdown-toggle").click(dropdown_toggle_click).on("mouseenter",dropdown_toggle_mouseenter);e("#peepso-wrap .dropdown-menu a").click(dropdown_toggle_a_click)});
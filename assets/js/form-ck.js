/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */jQuery(document).ready(function(e){e("#peepso-wrap .ps-privacy-dropdown ul li a").click(function(t){var n=e(t.target).closest("a"),r=n.closest("ul").siblings("button"),i=r.siblings("input"),s=r.find("span#permission-placeholder"),o=n.closest("ul.dropdown-menu");r.find("i").attr("class",n.find("i").attr("class"));i.val(n.attr("data-option-value"));s.html(n.find("span").html());o.css("display","none")})});
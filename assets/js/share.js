/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

/*
 * Interactions for share dialog box
 * @package PeepSo
 * @author PeepSo
 */

function escapeRegExp(string) {
    return string.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
}

function replaceAll(find, replace, str) {
  return str.replace(new RegExp(find, 'g'), replace);
}

function PsShare() {}

var share = new PsShare();

PsShare.prototype.share_url = function(url) {
	var title = jQuery("#share-dialog-title").html();
	var content = jQuery("#share-dialog-content").html();
	url = encodeURIComponent(url);
	content = replaceAll("{peepso-url}", url, content);

	pswindow.show(title, content);
	return (false);
};

// EOF
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
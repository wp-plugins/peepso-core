/*
 * User interactions for profile blocked user page
 * @package PeepSo
 * @author PeepSo
 */

//$PeepSo.log("profile-blocks.js");

function PsBlocks()
{
}

PsBlocks.prototype.init = function()
{
}

/*
 * Send delete request for each selected checkbox
 * @returns {undefined}
 */
PsBlocks.prototype.delete_selected = function()
{
//	var ckbx = jQuery("#peepso-wrap .cprofile-blocked input:checkbox");
	var ckbx = jQuery("#peepso-wrap .cprofile-blocked input:checked");
	
	if (ckbx.length > 0) {
		var del_list = [];
		for (var i = 0; i < ckbx.length; i++) {
			if (true) { //"checked" === jQuery(ckbx[i]).attr("checked")) {
				var id = ckbx[i].id;
				id = id.substr(id.indexOf("-") + 1);
				del_list.push(id);
			}
		}
	
		if (del_list.length > 0) {
			var req = { "delete": del_list.join(",") };
			$PeepSo.postJson("profile.block_delete", req, function(json) {
				window.location = window.location + "";
			});
		}
	} else {
		psmessage.show('', jQuery("#peepso-no-block-user-selected").html());
	}
}

var ps_blocks = new PsBlocks();

jQuery(document).ready(function()
{
	// get the max height
	var first_height = null;
	var max_height = 0;
	jQuery('ul.cstream-list.creset-list li').each(function(index, elem){
		var height = jQuery(elem).height();
		if (height > max_height)
			max_height = height;
		if (null === first_height)
			first_height = height;
	});

	// set the height of each item
	if (first_height !== max_height)
		jQuery('ul.cstream-list.creset-list li').each(function(index, elem){
			jQuery(elem).height(max_height);
		});
});

// EOF

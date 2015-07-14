function PsModalComments()
{
	this.current_object = null;
	this.objects = {}; // used to cache and store the state of each object in the gallery
}

var ps_comments = new PsModalComments();

/**
 * Initialize the class, setup event callbacks
 */
PsModalComments.prototype.init = function()
{
};

/**
 * Opens up the modal comment dialog
 * @param  {int} object_id The act_external_id of the activity item
 * @param  {string} type   An identifier used to determine which addon to use
 */
PsModalComments.prototype.open = function(object_id, type, options) {
	var _self = this;
	var req = { object_id: object_id, type: type };

	peepso.lightbox(function( callback ) {
		$PeepSo.getJson("modalcomments.get_object", req, function(response) {
			var data = [];

			options = options || {};

			if (response.success) {
				data = response.data.objects;
				options = jQuery.extend({
					index: response.data.index,
					afterchange: function( lightbox ) {
						jQuery(document).trigger("peepso_tags_init_comments");
						activity.setup_comment_textarea();
						ps_observer.apply_filters("modalcomments.afterchange", lightbox);
					}
				}, options );
			}

			callback( data, options );
		});
	});

	return (false);
};

/**
 * Displays the next object
 */
PsModalComments.prototype.next = function()
{
	this.save_object();
};

/**
 * Displays the previous object
 */
PsModalComments.prototype.prev = function()
{
	this.save_object();
};

/**
 * Keeps a copy of the current HTML so that it can be used when moving back and forth in the gallery,
 * keeps the likes and comments the same when the user left off.
 */
PsModalComments.prototype.save_object = function()
{
	if (undefined !== this.current_object)
		this.objects[this.current_object.ID] = this.current_object;
};

jQuery(document).ready(function() {
	ps_comments.init();
});
// EOF

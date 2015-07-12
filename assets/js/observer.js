/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 */

/* implements observer pattern
 * @package PeepSo
 * @author PeepSo
 *
 * example use:
 *
 *  // adds a filter named "filter-name", passing it a callback that will modify the contents
 * 	ps_observer.add_filter("filter-name", function(data) {
 * 	    // make modifications to data as necessary
 * 	    return (data);
 * 	});
 *
 *  // call the filter that was previously set up
 *  data = ps_observer.apply_filters("filter-name", data);
 */

//$PeepSo.log("observer.js");

function PsObserver()
{
	this.listeners = {};
}

var ps_observer = new PsObserver();

/*
 * Adds a callback for a filter to the list of observers
 * @param string name The name of the filter
 * @param fn fn_callback The function to call for the filter
 * @param int priority The priority on which to run the filter
 * @param int num_param The number of parameters for the funtion
 * @param obj this_obj Reference to "this" to be used in the callback
 * @returns void
 */
PsObserver.prototype.add_filter = function(name, fn_callback, priority, num_param, this_obj)
{
	if (undefined === priority)
		priority = 10;

	// Don't add the filter if the callback isn't a function
	if (typeof(fn_callback) !== typeof(Function))
		return;
//$PeepSo.log("add_filter('" + name + "')");
	filter = {
		callback: fn_callback,
		priority: priority,
		params: num_param,
		this_obj: this_obj
	};

	var listeners = this.listeners;

	if ("undefined" === typeof(this.listeners[name]))
		this.listeners[name] = {};
	
	cb_id = this.build_filter_unique_id(name, fn_callback);

	if ("undefined" === typeof(this.listeners[name][priority]))
		this.listeners[name][priority] = {};

	this.listeners[name][priority][cb_id] = filter;
};

/*
 * Removes a callback for a filter to the list of observers
 * @param string name The name of the filter
 * @param fn fn_callback The function to call for the filter 
 * @param int priority The priority on which to run the filter 
 * @returns void
 */
PsObserver.prototype.remove_filter = function(name, fn_callback, priority)
{
	if (undefined === priority)
		priority = 10;

	cb_id = this.build_filter_unique_id(name, fn_callback);

	delete this.listeners[name][priority][cb_id];
};

/*
 * Send data to each of the observers found
 * @param mixed data The data to pass through the filters
 * @return mixed The result of the data aftern being sent to all filters
 */
PsObserver.prototype.apply_filters = function(name)
{
	var args = arguments; // js arguments object
	data = "";
	try
	{
		var filters = this.listeners[name];
		if ("undefined" === typeof(filters)) {
			// if no filters for this [name] then return the default value
			return (args[1]);
		}

		jQuery.each(filters, function(i, _filters) {
			jQuery.each(_filters, function(cb_id, filter) {
				if (undefined !== filter.params) {
					callback_args = [];
					index = 1;
					while (index <= filter.params) {
						callback_args.push(args[index]);
						index++;
					}

					data = filter.callback.apply(filter.this_obj, callback_args);
					args[1] = data;
				} else {
					data = filter.callback();
				}
			});
		});
	}
	catch (e) {
		$PeepSo.log("!!Exception thrown in PsObserver.apply_filters():");
		$PeepSo.log(e);
	}

	// return the filtered data
	return (data);
};

/**
 * Create a unique key to identify the filter.
 * @param  string tag The name of the filter
 * @param  mixed callback The callback function 
 * @return string
 */
PsObserver.prototype.build_filter_unique_id = function(tag, callback) {
	var string_to_hash = tag + callback.toString();
	return (string_to_hash.hashCode());
};

/**
 * http://werxltd.com/wp/2010/05/13/javascript-implementation-of-javas-string-hashcode-method/ 
 */
String.prototype.hashCode = function(){
    var hash = 0;
    if (0 === this.length)
		return (hash);
    
    for (i = 0; i < this.length; i++) {
        char = this.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return (hash);
};

// EOF
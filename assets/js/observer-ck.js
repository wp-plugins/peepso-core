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
 *///$PeepSo.log("observer.js");
function PsObserver(){this.listeners={}}var ps_observer=new PsObserver;PsObserver.prototype.add_filter=function(e,t,n,r){undefined===n&&(n=10);filter={callback:t,priority:n,params:r};var i=this.listeners;"undefined"==typeof this.listeners[e]&&(this.listeners[e]={});cb_id=this.build_filter_unique_id(e,t);"undefined"==typeof this.listeners[e][n]&&(this.listeners[e][n]={});this.listeners[e][n][cb_id]=filter};PsObserver.prototype.remove_filter=function(e,t,n){undefined===n&&(n=10);cb_id=this.build_filter_unique_id(e,t);delete this.listeners[e][n][cb_id]};PsObserver.prototype.apply_filters=function(e){var t=arguments;try{var n=this.listeners[e];if("undefined"==typeof n)return t[1];jQuery.each(n,function(e,n){jQuery.each(n,function(e,n){if(undefined!==n.params){callback_args=[];index=1;while(index<=n.params){callback_args.push(t[index]);index++}data=n.callback.apply(null,callback_args)}else data=n.callback()})})}catch(r){$PeepSo.log("!!Exception thrown in PsObserver.apply_filters():");$PeepSo.log(r)}return data};PsObserver.prototype.build_filter_unique_id=function(e,t){var n=e+t.toString();return n.hashCode()};String.prototype.hashCode=function(){var e=0;if(this.length==0)return e;for(i=0;i<this.length;i++){char=this.charCodeAt(i);e=(e<<5)-e+char;e&=e}return e};
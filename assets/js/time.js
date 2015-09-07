(function( root, $, factory ) {

	var PsTime = factory( root, $ );
	ps_time = new PsTime();

})( window, jQuery, function( window, $ ) {

function time() {
	return Math.floor((new Date).getTime() / 1000);
}

function PsTime() {
	this.ts = time();
	this.diff = window.peepsotimedata.ts - this.ts;
}

// port from WordPress's human_time_diff function
PsTime.prototype.human_time_diff = function( from, to ) {
	var MINUTE_IN_SECONDS = 60,
		HOUR_IN_SECONDS = MINUTE_IN_SECONDS * 60,
		DAY_IN_SECONDS = HOUR_IN_SECONDS * 24,
		WEEK_IN_SECONDS = DAY_IN_SECONDS * 7,
		YEAR_IN_SECONDS = DAY_IN_SECONDS * 365,
		data = window.peepsotimedata || {},
		diff, mins, hours, days, weeks, months, years, since;

	from = +from;
	to = to ? to : time();
	to = to + this.diff;
	diff = Math.abs( to - from );

	if ( diff < MINUTE_IN_SECONDS ) {
		since = data.now;
	} else if ( diff < HOUR_IN_SECONDS ) {
		mins = Math.round( diff / MINUTE_IN_SECONDS );
		mins = mins <= 1 ? 1 : mins;
		since = ( mins > 1 ? data.mins : data.min ).replace( '%s', mins );
	} else if ( diff < DAY_IN_SECONDS && diff >= HOUR_IN_SECONDS ) {
		hours = Math.round( diff / HOUR_IN_SECONDS );
		hours = hours <= 1 ? 1 : hours;
		since = ( hours > 1 ? data.hours : data.hour ).replace( '%s', hours );
	} else if ( diff < WEEK_IN_SECONDS && diff >= DAY_IN_SECONDS ) {
		days = Math.round( diff / DAY_IN_SECONDS );
		days = days <= 1 ? 1 : days;
		since = ( days > 1 ? data.days : data.day ).replace( '%s', days );
	} else if ( diff < 30 * DAY_IN_SECONDS && diff >= WEEK_IN_SECONDS ) {
		weeks = Math.round( diff / WEEK_IN_SECONDS );
		weeks = weeks <= 1 ? 1 : weeks;
		since = ( weeks > 1 ? data.weeks : data.week ).replace( '%s', weeks );
	} else if ( diff < YEAR_IN_SECONDS && diff >= 30 * DAY_IN_SECONDS ) {
		months = Math.round( diff / ( 30 * DAY_IN_SECONDS ) );
		months = months <= 1 ? 1 : months;
		since = ( months > 1 ? data.months : data.month ).replace( '%s', months );
	} else if ( diff >= YEAR_IN_SECONDS ) {
		years = Math.round( diff / YEAR_IN_SECONDS );
		years = years <= 1 ? 1 : years;
		since = ( years > 1 ? data.years : data.year ).replace( '%s', years );
	}

	return ps_observer.apply_filters( 'human_time_diff', since, diff, from, to );
};

// Auto-update time label.
$(function() {
	setInterval(function() {
		var now = time();
		$('.ps-js-autotime').each(function() {
			var $el = $(this), ts = $el.data('timestamp');
			if (ts) {
				$el.html( ps_time.human_time_diff(ts, now) );	
			}
		});
	}, 40 * 1000);
});

return PsTime;

});
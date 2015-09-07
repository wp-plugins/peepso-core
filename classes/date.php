<?php
//===========================================================================
//  PeepSoDate
//
//    Author:        Steve Powell info@scrimpnet.com
//    Licensing:    GNU Lesser General Public License
//    Date:        Copyright (c) 2004 scrimpnet.com
//
//    Please feel free to email me with any questions, suggestions, or problems.
//
//    Public member functions.  See member function comments for more complete
//    documentation.
//
//    SetDate()    PeepSoDate    set class to a specific date/time
//    DatePart()    mixed        return specific part of the date class
//    Add()        adjust date by months, days, years, hours, minutes, seconds
//    Year()        integer        return class year
//    Month()        integer        return class month (1-12)
//    Day()        integer        return class day of month (1-31)
//    Hours()        integer        return class hour (0-23)
//    Minutes()    integer        return class minutes (0-59)
//    Seconds()    integer        return class seconds (0-59)
//    TimeStamp()    integer        return PHP base timestamp for class
//    BOW()        PeepSoDate    return first day of week in new PeepSoDate
//    EOW()        PeepSoDate    return last day of week in new PeepSoDate
//    BOM()        PeepSoDate    return first day of month in new PeepSoDate
//    EOM()        PeepSoDate    return last day of month in new PeepSoDate
//    Quarter()    Integer            return quarter (1-4) for class
//    BOQ()        PeepSoDate    return beginning of quarter in new PeepSoDate
//    EOQ()        PeepSoDate    return end of quarter in new PeepSoDate
//    BOY()        PeepSoDate    return beginning of year in new PeepSoDate
//    EOY()        PeepSoDate    return end of year in new PeepSoDate
//    ToString()    PeepSoDate    return a formatted date/time string
//    UTC()        PeepSoDate    return UTC date/time in new PeepSoDate
//
//    Most functions take an optional <$dateTime> parameter.  If this parameter is
//    used it will adjust the class value to <$dateTime> before performing the
//    requested action.  <$dateTime> can be:
//        string        14-JAN-2004        any format that can be parsed by PHP.strtotime()
//                    5/15/2004            using the GNU date syntax.  These are only
//                    2004-03-12 17:35    examples
//        integer        1072879687        PHP timestamp
//        object        (PeepSoDate)        existing date class
//
// CUSTOMIZATION HINTS/IDEAS
//    (1)    The _parseDate() function is responsible for taking input and converting
//        it into a PHP timestamp value.  Modify this routine to allow class to
//        be able to handle more date formats
//    (2)    Expand class to have better UTC or timezone awareness
//    (3)    Allow class to handle years that begin on different months
//        i.e. Fiscal year starting 4/1/xxxx or 10/1/xxxx
//
// NOTES
//    (1)    This source code contains many functions you may not need and it
//        contains thorough comments.  You are free to remove any you
//        don't need but you may not remove the licensing or copyright information.
//
// LICENSING
//    This library is free software; you can redistribute it and/or
//    modify it under the terms of the GNU Lesser General Public
//    License as published by the Free Software Foundation; either
//    version 2.1 of the License, or (at your option) any later version.
//
//    This library is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//    Lesser General Public License for more details.
//
//    You should have received a copy of the GNU Lesser General Public
//    License along with this library; if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
//=============================================================================

if (class_exists('DateTime')) {

class PeepSoDate
{
	//----------------------------
	// private member variables
	//----------------------------
	var $_Date;      //base timestamp value for this class
	var $_datePart; // array of returned by getdate($_Date)
	var $_format;   // last format passed to ToString()

	//========================================================
	// constructor
	//========================================================
	function PeepSoDate($dateTime = '')
	{
		$this->_format = 'Y-m-d H:i:s';
		if (!$dateTime)
			$this->SetDate(time());
		else
			$this->SetDate($dateTime);
	} //constructor()

	//========================================================
	//$this->SetDate
	//
	//    Take <$dateTime> and attempt to convert it into a
	//    timestamp variable.  If a string then string must
	//    be in standard format.  If an integer it must be
	//    a valid timestamp variable.  If nothing is passed
	//    defaults to current time.
	//
	//  NOTE:  the entire class depends on parsing $dateTime
	//           correctly.  This is the only place where the
	//           private timestamp $_Date is directly modified.
	//========================================================
	function SetDate($dateTime = '')
	{
		$oldStamp = $this->TimeStamp();

		//---------------------------------------------------
		// try to convert $dateTime to a time stamp
		//---------------------------------------------------
		if ($dateTime=="")
			$this->_Date=time();
		else
			$this->_Date = $this->_parseDate($dateTime);

		//---------------------------------------------------
		//  parse out date/time components if new timestamp
		//  is different than previous timestamp.  This is
		//    done only once per value so that repeated calls
		//    to member functions do no have to repeatedly
		//  reparse the timestamp
		//---------------------------------------------------
		if ($oldStamp != $this->TimeStamp())
		{
			$this->_datePart = getdate($this->TimeStamp());
		}
		return $this;
	} //SetDate()

	//========================================================
	//    _parseDate (private)
	//
	//    Convert $dateTime into a timestamp.
	//
	//    Return:    timestamp value of <$dateTime>
	//
	//  NOTE:  This is the only place where a date is parsed
	//         into a timestamp.  Modify this routine to
	//           accept other types of date/time formats
	//========================================================
	function _parseDate($dateTime = '')
	{
		$ts = 0; // timestamp of parsed date
		switch (gettype($dateTime))
		{
			case 'string': // <== modify to accomodate more date formats
				$ts=strtotime($dateTime);
				break;
			case 'integer':
				$ts=$dateTime;
				break;
			case 'object':
				if ('PeepSoDate' === get_class($dateTime) || 'DateTime' === get_class($dateTime)) {
					$ts = $dateTime->TimeStamp();
				}
		} //switch
		return $ts;
	} //_parseDate()

	//=========================================================
	// DatePart
	//
	//    Returns specific part of current date/time of class.
	//  If $dateTime is passed then $dateTime replaces value
	//    of class.
	//
	//    $datePart must conform to valid parameters of getdate()
	//        seconds        mday        year        yday
	//        minutes        wday        month
	//        hours        weekday        mon            timestamp
	//=========================================================
	function DatePart($datePart = '', $dateTime = '')
	{
		if ($dateTime)
			$this->SetDate($dateTime);
		if ('timestamp' === $datePart)
			$datePart = 0;
		return $this->_datePart[$datePart];
	} //DatePart()

	//=========================================================
	//  Retrieve parts of date structure
	//=========================================================
	function Year($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		return $this->DatePart('year');
	}
	function Month($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		return $this->DatePart('mon');
	}
	function Day($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		return $this->DatePart('mday');
	}
	function Hours($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		return $this->DatePart('hours');
	}
	function Minutes($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		return $this->DatePart('minutes');
	}
	function Seconds($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		return $this->DatePart('seconds');
	}

	//===========================================================
	//  Add()
	//
	//    Adjusts <$adjustPart> of class date by <$adustValue>.
	//
	//    Return a reference to this class that contains adjusted
	//    timestamp.
	//
	//    <$datePart>    string    part of date to adjust.  The parameter
	//                        can use any class defined variables
	//                        and most of corresponding keys from
	//                        the date() and getdate() functions.
	//
	//                        "year" | "years" | "y"
	//                        "months" | "month" | "mon"
	//                        "days" | "day" | "mday" | "d"
	//                        "hours" | "hour" | "g" |
	//                        "minutes" | "minute" | "i"
	//                        "seconds" | "second" | "s"
	//    <$adjustValue> int    Amount to adjust date by.  Use negative
	//                        to move backwards in time.
	//===========================================================
	function Add($datePart, $adjustValue, $dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		if (!is_int($adjustValue)) $adjustValue = 0;

		$year = $this->Year();
		$month = $this->Month();
		$day = $this->Day();
		$hour = $this->Hours();
		$min = $this->Minutes();
		$sec = $this->Seconds();

		switch(strtolower($datePart))
		{
		case 'months':
		case 'month': // month
		case 'mon':
			 $this->SetDate(mktime($hour,$min,$sec,$month+$adjustValue,$day, $year));
			break;
		case 'day': // day
		case 'days':
		case 'd':
		case 'mday':
			$this->SetDate(mktime($hour,$min,$sec,$month,$day+$adjustValue, $year));
			break;
		case 'year': // year
		case 'years':
		case 'y':
			$this->SetDate(mktime($hour,$min,$sec,$month,$day, $year+$adjustValue));
			break;
		case 'hours': // hour
		case 'hour':
		case 'g': case 'G': case 'H': case 'h':
			$this->SetDate(mktime($hour+$adjustValue,$min,$sec,$month,$day, $year));
			break;
		case 'minutes': // minute
		case 'minute':
		case 'i':
			$this->SetDate(mktime($hour,$min+$adjustValue,$sec,$month,$day, $year));
			break;
		case 'seconds': // seconds
		case 'second':
		case 's':
			$this->SetDate(mktime($hour,$min,$sec+$adjustValue,$month,$day, $year));
			break;
		} //switch
		return $this;
	} //Add()

	//===========================================================
	// TimeStamp()
	//
	//    Return timestamp value this class represents.
	//
	//    NOTE:    This is the only place the class reads _Date directly
	//===========================================================
	function TimeStamp($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was '$DateTime'
		return $this->_Date;
	} //TimeStamp()

	//===========================================================
	// BOW() - Beginning of Week
	//
	//    Return date for first day of week.  If class
	//    has time component that remains the same except it is
	//    now the first day of the week
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function BOW($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was: '$DateTime'

		$dt = new PeepSoDate($this->TimeStamp());
		$wday = $dt->DatePart('wday');
		$wday = $wday*-1;
		$dt->Add('mday',$wday);
		return $dt;
	} //BOW()

	//===========================================================
	// EOW() - End of Week
	//
	//    Return date for last day of the week.  If class
	//    has time component that remains the same except it is
	//    now the last day of the week
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function EOW($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);		// [dj] was: '$DateTime'
		$dt = new PeepSoDate($this->TimeStamp());
		return $dt->Add('day',6-$dt->DatePart('wday'));
	}

	//===========================================================
	// BOM() - Beginning of Month
	//
	//    Return first day of month for the class date.  If class
	//    has time component that remains the same except it is
	//    now the first day of the month
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function BOM($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was '$DateTime'
		$dt = new PeepSoDate($this->TimeStamp());

		$dt->Add('day',-1*($dt->Day()));
		return $dt;
	} //BOM()

	//===========================================================
	// EOM() - End of Month
	//
	//    Return last day of month for the class date.  If class
	//    has time component that remains the same except it is
	//    now the last day of the month
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function EOM($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was: '$DateTime'
		$dt = new PeepSoDate($this->TimeStamp());
		$dt->Add('month',1);
		$dtBOM=$dt->BOM();
		$dtBOM->Add('day',-1);
		return $dtBOM;
	} //EOM()

	//===========================================================
	// BOY() - Beginning of Year
	//
	//    Return first day of year.  If class
	//    has time component that remains the same except it is
	//    now the first day of the year
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function BOY($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was: '$DateTime'

		$dt = new PeepSoDate($this->TimeStamp());
		$dt->Add('month',-1*($dt->Month()-1));
		return $dt->BOM();
	} //BOY()

	//===========================================================
	// EOY() - End of Year
	//
	//    Return last day of year.  If class
	//    has time component that remains the same except it is
	//    now the last day of the year
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function EOY($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		$dt = new PeepSoDate($this->TimeStamp());
		$dt->Add('year',1);
		$dtEOY = $dt->BOY();
		$dtEOY->Add('day',-1);
		return $dtEOY;
	} //EOY()

	//===========================================================
	// Quarter()
	//
	//    Return the quarter (1-4) for current value of the class
	//===========================================================
	function Quarter($qDate = '', $dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		switch($this->Month())
		{
		case 1: case 2: case 3: return 1;break;
		case 4: case 5: case 6: return 2; break;
		case 7: case 8: case 9: return 3; break;
		case 10: case 11: case 12: return 4;break;
		}
	} //Quarter()

	//===========================================================
	// BOQ() - Beginning of Quarter
	//
	//    Return first day of <$quarter> or the quarter of the current
	//    value of the class.  If class
	//    has time component that remains the same except it is
	//    now the first day of the quarter.
	//
	//    <$quarter>    integer        (1-4)
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function BOQ($quarter = 0, $dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was: '$DateTime'
		if (!$quarter) $quarter = $this->Quarter();
		switch($quarter)
		{
			case 1:  $month = 1;break;
			case 2:  $month = 4; break;
			case 3:  $month = 7; break;
			case 4:  $month = 10;break;
		}
		$dt = new PeepSoDate(mktime($this->Hours(),$this->Minutes(),$this->Seconds(),$month,1,$this->Year()));
		return $dt;
	} //BOQ()

	//===========================================================
	// EOQ() - End of Quarter
	//
	//    Return last day of <$quarter> or the quarter of the current
	//    value of the class.  If class
	//    has time component that remains the same except it is
	//    now the last day of the quarter.
	//
	//    <$quarter>    integer        (1-4)
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//===========================================================
	function EOQ($quarter = 0, $dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);	// [dj] was: '$DateTime'

		if (!$quarter) $quarter = $this->Quarter();
		$month = $quarter*3;
		$dt = new PeepSoDate(mktime($this->Hours(),$this->Minutes(),$this->Seconds(),$month,1,$this->Year()));
		$month = $dt->EOM();
		return $month;
	} //EOQ()

	//===========================================================
	// ToString()
	//
	// Return a formated string of the current date.  Use
	// <$format> if provided otherwise use
	// the format last passed to this function or use default.
	//
	//    You can pass the following for commonly predefined <$format> types:
	//        RFC822        Mon, 17 May 2004 20:55:42 -0400
	//        RSS_1.0        2004-05-17T20:55:42-0400
	//        HTTP        Mon, 17 May 2004 20:55:42 GMT
	//
	// Note: <$format> must be compatible with the PHP.date() function
	//===========================================================
	function ToString($format = '', $dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		if('' != $format) $this->_format = $format;
		switch (strtoupper($format))
		{
			case 'RFC822': return date('r',$this->TimeStamp());
			case 'RSS_1.0': case 'dc:date':
				return date('Y-m-d\TH:i:sO',$this->TimeStamp());
			case 'HTTP': case 'RFC1123':
				return date('D, d M Y H:i:s GMT',$this->TimeStamp());
		}
		return date($this->_format,$this->TimeStamp());
	} //ToString()

	//===========================================================
	// UTC()
	//
	//    Return UTC equivelent time for class date value.
	//
	//    Returned value is in a new PeepSoDate instance and does
	//    not modify existing class value
	//============================================================
	function UTC($dateTime = '')
	{
		if ($dateTime) $this->SetDate($dateTime);
		$dt = date($this);
		$dt->Add('minutes',date('Z',$this->TimeStamp()));
		return $dt;
	} // UTC()

} //class PeepSoDate

} // endif (class_exists())

//===========================================================================
//  DateSpan Class
//
//    Author:        Steve Powell info@scrimpnet.com
//    Licensing:    GNU Lesser General Public License
//    Date:        (c) 2004 ScrimpNet.com
//
//    Please feel free to email me with any questions, suggestions, or problems.
//
//    This class determines the span of time between two different date/times.
//
//    Functions return positive value when StartDate < StopDate.  Functions
//    return negative values when StartDate > StopDate.
//
//    Generally date parameters can take the form of:
//    string        "3/14/2004"        any GNC compatible strtotime() format
//    integer        173218828        PHP time stamp
//    object        object            PeepSoDate
//
//    Prerequisites:    requires PeepSoDate
//
//    Public member functions. See member function comments for more complete
//    complete descriptions:
//    DateSpanClass()            constructor
//
//    StartDate                (property) return PeepSoDate of starting date in span
//    StopDate                (property) return PeepSoDate of stopping date in span
//
//    Years()                    return number of years covered in span
//    Quarters()                return number of quarters covered in span
//    Months()                return number of months covered in span
//    Weeks()                    return number of weeks covered in span
//    WeekDays()                return number of week days (M-F) in span
//    Days()                    return number of days covered in span
//    Hours()                    return number of hours covered in span
//    Minutes()                return number of minutes covered in span
//    Seconds()                return number of seconds covered in span
//    Span()                    return number of periods between two dates
//    ToString()                return formatted string representing span
//    TimeStamp()                return PHP timestamp for span (same of Seconds())
//
// NOTES:
//    (1)    This source code contains many functions you may not need and it
//        contains thorough comments.  You are free to remove any you
//        don't need but you may not remove the licensing or copyright information.
//    (2)    For performance reasons, multiple calls to the class with the same
//      span-start/span-stop dates (and/or times) are not recalculated.
//
// CUSTOMIZATION IDEAS
//    (1)    Expand class to be able to handle UTC and spans between time zones
//    (2)    Add capability to handle fiscal years that do not correlate
//        to calendar years. i.e. years that begin on 4/1 or 10/1 etc.
//
// LICENSING
//    This library is free software; you can redistribute it and/or
//    modify it under the terms of the GNU Lesser General Public
//    License as published by the Free Software Foundation; either
//    version 2.1 of the License, or (at your option) any later version.
//
//    This library is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//    Lesser General Public License for more details.
//
//    You should have received a copy of the GNU Lesser General Public
//    License along with this library; if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
//===========================================================================

if (!class_exists('DateSpanClass')) {
class DateSpanClass
{
	//------------------------------------------------------
	// public member variables (READ ONLY)
	//------------------------------------------------------
	var $StartDate;
	var $StopDate;

	//-------------------------------------------------------
	// private member variables
	//-------------------------------------------------------
	var    $_months;
	var $_days;
	var $_weekdays;
	var $_hours;
	var $_minutes;
	var $_seconds;
	var    $_weeks;
	var $_quarters;
	var $_format;
	var $_years;
	var $_spandir; // 1 = positive span, -1 = negative span

	//=========================================================
	// constructor()
	//
	//    Normally class is instantiated without any parameters.
	//    Span dates are usually supplied via Span() or property
	//    accessor functions.
	//=========================================================
	function DateSpanClass($dateStart = '', $dateStop = '')
	{
		$this->_reset();
		if (!$dateStart) $dateStart = new PeepSoDate();
		if (!$dateStop) $dateStop = new PeepSoDate();
		$this->Span('seconds',$dateStart,$dateStop);
	} //constructor()

	//==========================================================
	//    _reset    (private)
	//
	//    Set class member variables to known state
	//==========================================================
	function _reset()
	{
		$this->_format = 'm-d-y h:i:s';
		$this->_years=0;
		$this->_months=0;
		$this->_days=0;
		$this->_hours=0;
		$this->_minutes=0;
		$this->_seconds=0;
		$this->_weeks=0;
		$this->_quarters=0;
		$this->_weekdays=0;
		$this->_spandir=1;
	} //_reset()

	//==========================================================
	//  property accessor functions
	//
	//    optional parameters will calculate
	//    span between <$dateStart> and <$dateStop>.  The span of the
	//    class will change based on these parameters. Existing
	//    class values are used if parameters
	//    are not set.
	//
	//    $dateX parameter can be:
	//        string        "4/13/2004"        or any other GNC compatible format
	//        integer        PHP timestamp
	//        object        PeepSoDate
	//==========================================================
	function Years($dateStart = '',$dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_years==0)  $this->Span('years',$dateStart,$dateStop);
		return $this->_years;
	}
	function Months($dateStart = '',$dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_months==0) $this->Span('months',$dateStart,$dateStop);
		return $this->_months;
	}
	function WeekDays($dateStart = '', $dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_weekdays==0) $this->Span('weekdays',$dateStart,$dateStop);
		return $this->_weekdays;
	}
	function Days($dateStart = '', $dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_days==0) $this->Span('days',$dateStart,$dateStop);
		return $this->_days;
	}
	function Weeks($dateStart = '', $dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_weeks==0) $this->Span('weeks',$dateStart,$dateStop);
		return $this->_weeks;
	}
	function Quarters($dateStart = '', $dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_quarters==0) $this->Span('quarters',$dateStart,$dateStop);
		return $this->_quarters;
	}
	function Hours($dateStart = '', $dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_hours==0) $this->Span('hours',$dateStart,$dateStop);
		return $this->_seconds();
	}
	function Minutes($dateStart = '',$dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_minutes==0) $this->Span('minutes',$dateStart,$dateStop);
		return $this->_minutes();
	}
	function Seconds($dateStart = '', $dateStop = '')
	{
		if ($dateStart || $dateStop || $this->_seconds==0) $this->Span('seconds',$dateStart,$dateStop);
		return $this->_seconds;
	}

	//=========================================================
	// Span()
	//
	//    Calculate span between two dates.  Returned value is
	//    determined by <$datePart>.  Numbers could be integer
	//    or float depending on span result.
	//
	//    <$datePart>    string    part of date to adjust.  The parameter
	//                        can use any the class defined variables
	//                        and most of corresponding keys from
	//                        the date() and getdate() functions.
	//                        "year" | "years" | "y"
	//                        "quarters" | "q"
	//                        "months" | "month" | "mon"
	//                        "weeks" | "w"
	//                        "days" | "day" | "mday" | "d"
	//                        "hours" | "hour" | "g" |
	//                        "minutes" | "minute" | "i"
	//                        "seconds" | "second" | "s"
	//
	//    <$dateStart>,<$dateStop>    begin/end dates for time span
	//                        string "5/23/2004"        formated GNC date
	//                        integer    393922923        timestamp
	//                        object    PeepSoDate
	//=========================================================
	function Span($period, $dateStart = '', $dateStop = '')
	{
		//--------------------------------------------------
		//  create new span if parameters provided
		//--------------------------------------------------
		if ($dateStart)
			$this->StartDate = new PeepSoDate($dateStart);
		if ($dateStop)
			$this->StopDate = new PeepSoDate($dateStop);

		//--------------------------------------------------
		//    check to see if we are going to be using a
		//    negative span (stop date is before start date)
		//--------------------------------------------------
		$this->_spandir = 1;
		if ($this->StartDate->TimeStamp() > $this->StopDate->TimeStamp())
		{
			$this->_spandir = -1;
		}

		//--------------------------------------------------
		//    calculate fixed length intervals
		//--------------------------------------------------
		$this->_seconds = $this->StopDate->TimeStamp()-$this->StartDate->TimeStamp();
		$this->_minutes = $this->_seconds/60;
		$this->_hours = $this->_minutes/60;
		$this->_days = $this->_hours/24;
		$this->_years = $this->_days/365.25;

		//--------------------------------------------------
		//    return user requested period
		//--------------------------------------------------
		switch(strtolower($period))
		{
		case 'years': case 'year': case 'y':
			return $this->_years;break;
		case 'quarters': case 'q':
			return $this->_calcQuarters();break;
		case 'months': case 'mon': case 'm': case 'month':
			return $this->_calcMonths();break;
		case 'weeks': case 'w':
			return $this->_calcWeeks();break;
		case 'weekdays': case 'wdays':
			return $this->_calcWeekDays();break;
		case 'days': case 'd': case 'day':
			return $this->_days;break;
		case 'hours': case 'hour': case 'h': case 'g':
			return $this->_hours;break;
		case 'minutes': case 'minute': case 'i':
			return $this->_minutes;break;
		case 'seconds': case 'second': case 's':
			return $this->_seconds;break;
		}
		return 0; // invalid period parameter
	} //span()

	//========================================================
	// _calcQuarters  (private)
	//
	//    Return number of quarter boundries crossed in the
	//    span.  This is a private function and should only
	//    be called from within the class.  Use other accessor
	//    functions to access the information returned from
	//    here.
	//========================================================
	function _calcQuarters()
	{
		if ($this->_spandir==1) // start is before stop (positive span)
		{
			$dtStart = new PeepSoDate($this->StartDate);
			$dtStart = $dtStart->BOQ();
			$dtStop = $this->StopDate;
		}
		else
		{
			$dtStart = new PeepSoDate($this->StopDate);
			$dtStart = $dtStart->BOQ();
			$dtStop = $this->StartDate;
		}

		//--------------------------------------------------
		//    count times crossing period boundries
		//--------------------------------------------------
		$counter =0;
		while ($dtStart->TimeStamp() < $dtStop->TimeStamp())
		{
			$counter++;
			$dtStart->Add('month',3);
		}
		$this->_quarters = ($counter-1*$this->_spandir);

		return $this->_quarters;

	} //_calcQuarters()

	//===========================================================
	// _calcMonths()
	//
	//    Return number of month boundries crossed in the span.  This
	//    is a private function and should only be called from within
	//    the class.  Use other accessor functions to access the
	//    information returned from this function.
	//===========================================================
	function _calcMonths()
	{
		if ($this->_spandir==1) // start is before stop (positive span)
		{
			$dtStart = new PeepSoDate($this->StartDate);
			$dtStart = $dtStart->BOM();
			$dtStop = $this->StopDate;
		}
		else
		{
			$dtStart = new PeepSoDate($this->StopDate);
			$dtStart = $dtStart->BOM();
			$dtStop = $this->StartDate;
		}

		//--------------------------------------------------
		// count times crossing period boundries
		//--------------------------------------------------
		$counter =0;
		while ($dtStart->TimeStamp() < $dtStop->TimeStamp())
		{
			$counter++;
			$dtStart->Add('month',1);
		}
		$this->_months = ($counter-1)*$this->_spandir;

		return $this->_months;

	} //_calcMonths()

	//===========================================================
	// _calcWeeks()
	//
	//    Return number of week boundries crossed in the span. This
	//    means the number of Sunday's between span dates.
	//
	//    This is a private function and should only be called from within
	//    the class.  Use other accessor functions to access the
	//    information returned from this function.
	//===========================================================
	function _calcWeeks()
	{
		if ($this->_spandir==1) // start is before stop (positive span)
		{
			$dtStart = new PeepSoDate($this->StartDate);
			$dtStart = $dtStart->BOW();
			$dtStop = $this->StopDate;
		}
		else
		{
			$dtStart = new PeepSoDate($this->StopDate);
			$dtStart = $dtStart->BOW();
			$dtStop = $this->StartDate;
		}

		//--------------------------------------------------
		//    count times crossing period boundries
		//--------------------------------------------------
		$counter=0;
		while ($dtStart->TimeStamp() < $dtStop->TimeStamp())
		{
			$counter++;
			$dtStart->Add('day',7);
		}
		$this->_weeks = $counter-1;
		$this->_weeks *= $this->_spandir;

		return ($this->_weeks);

	} //_calcWeeks()

	//===========================================================
	// _calcWeekDays()
	//
	//    Return number of week boundries crossed in the span. This
	//    means the number of business days between span dates.
	//
	//    This is a private function and should only be called from within
	//    the class.  Use other accessor functions to access the
	//    information returned from this function.
	//===========================================================
	function _calcWeekDays()
	{
		if ($this->_spandir==1) // start is before stop (positive span)
		{
			$dtStart = new PeepSoDate($this->StartDate);
			$dtStop = $this->StopDate;
		}
		else
		{
			$dtStart = new PeepSoDate($this->StopDate);
			$dtStop = $this->StartDate;
		}

		//--------------------------------------------------
		//    count times crossing period boundries
		//--------------------------------------------------
		$counter=0;
		while ($dtStart->TimeStamp() < $dtStop->TimeStamp())
		{
			if ($dtStart->DatePart('wday') > 0 && $dtStart->DatePart('wday') < 5)
				$counter++;
			$dtStart->Add('day',1);
		}
		$this->_weekdays = $counter;
		$this->_weekdays *= $this->_spandir;

		return ($this->_weekdays);

	} //_calcWeekDays()
	//===========================================================
	// TimeStamp()
	//
	//    Return timestamp value this class represents.
	//
	//    This class uses the PHP timestamp for all calculations.
	//===========================================================
	function TimeStamp()
	{
		return $this->Seconds();
	}

	//=========================================================
	//    ToString()
	//  Return a formated string of the current timestamp.
	//    Function uses <$format> if provided otherwise it uses
	//  the format last passed to this function or use $_format.
	//
	// Note: <$format> must be compatible with the PHP.date() function
	//=========================================================
	function ToString($format = '')
	{
		if ($format) $this->_format = $format;
		return date($this->_format,$this->TimeStamp());
	} //ToString()

} //class DateSpan

} // endif (class_exists())

// EOF
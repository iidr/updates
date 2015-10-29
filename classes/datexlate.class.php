<?php
Class DateXLate
{	var $secInHour;
	var $secInDay;
	var $secInWeek;
	var $secInYear;
	var $months = array(1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec');
	public static $instance;
	
	function __construct() // constructor
	{	$this->secInHour = 60 * 60;
		$this->secInDay = $this->secInHour * 24;
		$this->secInWeek = $this->secInDay * 7;
		$this->secInYear = $this->secInDay * 365;
	} // end of fn __construct
	
	public static function GetInstance()
	{
		if (!isset(self::$instance))
		{	self::$instance = new DateXLate();	
		}
		
		return self::$instance;
	} // end of fn GetInstance

	function Sec2Time($seconds)
	{	$hours = floor($seconds / (60 * 60));
		return $hours . ':' . date('i:s', $seconds - ($hours * 60 * 60));
	} // end of fn Sec2Time

	function Date2Stamp($date)
	{	return mktime(0,0,0, substr($date, 3, 2), substr($date, 0, 2), substr($date, 6, 4));
	} // end of fn Date2Stamp

	function Stamp2Date($stamp = 0, $ycount = 4)
	{	if ($ycount == 4)
		{	$Y = 'Y';
		} else
		{	$Y = 'y';
		}
		return @date('d/m/' . $Y, $stamp);
	} // end of fn Stamp2Date

	function Date2SQL($date) // takes date in format dd?mm?yyyy and changes to yyyy-mm-dd
	{	$month = substr($date, 3, 2);
		$day = substr($date, 0, 2);
		$year = substr($date, 6, 4);
		return date('Y-m-d', mktime(0,0,0, $month, $day, $year));
	} // end of fn Date2SQL

	function SQL2Date($sqldate = "", $ycount = 4) // takes date in format yyyy-mm-dd and changes to dd?mm?yyyy
	{	$month = substr($sqldate, 5, 2);
		$day = substr($sqldate, 8, 2);
		$year = substr($sqldate, 0, 4);
		if ($year >= '1970')
		{	return $this->Stamp2Date(mktime(0,0,0, $month, $day, $year), $ycount);
		} else
		{	return $this->SQL2DateByStringOnly($sqldate, $ycount);
		}
	} // end of fn SQL2Date

	function SQL2DateByStringOnly($sqldate = '', $ycount = 4)
	{	$month = substr($sqldate, 5, 2);
		$day = substr($sqldate, 8, 2);
		$year = substr(substr($sqldate, 0, 4), -$ycount);
		return $day .'/' . $month . '/' . $yearout;
	} // end of fn SQL2DateByStringOnly
	
	function Date2SQLByStringOnly($date)
	{	$bits = explode('/', $date);
		$d = str_pad($bits[0], 2, '0', STR_PAD_LEFT);
		$m = str_pad($bits[1], 2, '0', STR_PAD_LEFT);
		$y = str_pad($bits[2], 4, '0', STR_PAD_LEFT);
		return '$y-$m-$d';
	} // end of fn Date2SQLByStringOnly
	
	function SQL2Stamp($date) // takes date in format yyyy-mm-dd and changes to 99999999
	{	return mktime(substr($date, 11, 2),substr($date, 14, 2), substr($date, 17, 2), substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
	} // end of fn SQL2Stamp

	function Stamp2SQL($stamp) // takes date in format yyyy-mm-dd and changes to 99999999
	{	return date('Y-m-d', $stamp);
	} // end of fn Stamp2SQL

	function Stamp2SQLFull($stamp) // takes date in format yyyy-mm-dd and changes to 99999999
	{	return date('Y-m-d H:i:s', $stamp);
	} // end of fn Stamp2SQL
	
	function GetYearList($ystart = 0, $ycount = 1)
	{	$yearlist = array();
		$direction = $ycount / abs($ycount);
		$ycount = (int)abs($ycount);
		$ystart = (int)$ystart;
		for ($i = 0; $i < $ycount; $i++)
		{	$y = $ystart + ($direction * $i);
			$yearlist[$y] = $y;
		}
		return $yearlist;
	} // end of fn GetYearList
	
	function SQLDate($datestamp = 0){	
		$datetime   = new DateTime;
		$datetime->setTimezone(new DateTimeZone('Europe/London'));
		
		if ($datestamp = $this->bigIntvalue($datestamp)){	
			//return date('Y-m-d', $datestamp);			
			$datetime->setTimestamp($datestamp);
			return $datetime->format('Y-m-d');
		}else{	
			//return date('Y-m-d');
			return $datetime->format('Y-m-d');
		}
	} // end of fn SQLDate
	
	function SQLDateTime($datestamp = 0){
		$datetime   = new DateTime;
		$datetime->setTimezone(new DateTimeZone('Europe/London'));
		if ($datestamp = $this->bigIntvalue($datestamp)){	
			//return date('Y-m-d H:i:s', $datestamp);
			$datetime->setTimestamp($datestamp);
			return $datetime->format('Y-m-d H:i:s');
		} else{	
			//return date('Y-m-d H:i:s');	
			return $datetime->format('Y-m-d H:i:s');
		}
	} // end of fn SQLDateTime
	
	function Age($dob = '')
	{	if ((int)$dob)
		{	return (time() - strtotime($dob)) / $this->secInYear;
		} else
		{	return 0;
		}
	} // end of fn Age

	function bigIntvalue($value){
		$value = trim($value);
		if(ctype_digit($value)) return $value;	
		$value = preg_replace("/[^0-9](.*)$/", '', $value);
		if(ctype_digit($value)) return $value;
		return 0;
	}
	
} // end of class definition DateXLate

?>
<?php
class Base
{	var $db;
	var $datefn;
	var $def_currency = 'GBP';
	var $link;
	protected $reviewperpage = 1;
	
	function __construct() // constructor
	{	$this->db = DbConnect::GetInstance();
		$this->datefn = DateXLate::GetInstance();
		$this->link = Link::GetInstance();
	} // end of fn __construct
	
	function DateFormat($dateformat = '', $timestamp = 0)
	{	if (!$timestamp)
		{	$timestamp = time();
		}
		return $this->TranslateDate(date($dateformat, $timestamp));
	} // end of fn DateFormat
	
	function TranslateDate($datestr = '')
	{	
		if (is_array($datestr))
		{	foreach ($datestr as $key=>$value)
			{	$datestr[$key] = $this->TranslateDate($value);
			}
		} else
		{	if ($this->lang != $this->def_lang)
			{	static $transtext = array();
				if (!$transtext)
				{	$sql = "SELECT deftext, transtext FROM datexlate WHERE lang='{$this->lang}'";
					if ($result = $this->db->Query($sql))
					{	while ($row = $this->db->FetchArray($result))
						{	$transtext[$row["deftext"]] = $row["transtext"];
						}
					}
				}
				
				if ($transtext && $datestr)
				{	foreach ($transtext as $old=>$new)
					{	$datestr = preg_replace("|\b$old\b|i", $new, $datestr);
					}
				}
			}
		}
		return $datestr;
		
	} // end of fn TranslateDate
	
	function GetTranslatedText($label, $lang = "")
	{	
		if (!$lang)
		{	$lang = $this->lang;
		}
		
		static $text_got = array();
		if (isset($text_got[$label]))
		{	return $text_got[$label];
		}
		
		$sql = "SELECT content FROM fptext WHERE fptlabel='" . $this->SQLSafe($label) . "' AND lang='$lang'";
		if ($result = $this->db->Query($sql))
		{	//if (($row = $this->db->FetchArray($result)) && ($text = $this->InputSafeString($row["content"])))
			if (($row = $this->db->FetchArray($result)) && ($text = stripslashes($row["content"])))
			{	return $text_got[$label] = $text;
			} else
			{	if (($lang == $this->def_lang))
				{	if ($lang != "en")
					{	// as last resort go for english
						$sql = "SELECT content FROM fptext WHERE fptlabel='" . $this->SQLSafe($label) . "' AND lang='en'";
						if ($result = $this->db->Query($sql))
						{	if ($row = $this->db->FetchArray($result))// && ($text = stripslashes($row["content"])))
							{	return $text_got[$label] = stripslashes($row["content"]);
							}
						}
					}
				} else
				{	return $this->GetTranslatedText($label, $this->def_lang);
				}
			}
		}
		return "";
	} // end of fn GetTranslatedText

	function GetParameter($field)
	{	if ($result = $this->db->Query('SELECT fieldvalue, fieldtype FROM parameters WHERE parname="' . $field . '"'))
		{	if ($row = $this->db->FetchArray($result))
			{	switch ($row['fieldtype'])
				{	case 'INT': return (int) $row['fieldvalue'];
					case 'BOOLEAN': return $row['fieldvalue'] ? 1 : 0;
					case 'PRICE': return round($row['fieldvalue'], 2);
					default: return $row['fieldvalue'];
				}
			}
		}
		return "";
	} // end of fn GetParameter
	
	public function FlagFileSet($filename = '')
	{	if ($filename)
		{	return file_exists(CITDOC_ROOT . '/flagfiles/' . $filename);
		}
	} // end of fn FlagFileSet
	
	public function ShortText($text = '', $length = 100)
	{	if ($text = html_entity_decode($text, ENT_QUOTES, 'utf-8'))
		{	if ($shortened = strlen($text) > $length)
			{	$text = substr($text, 0, $length) . ' ...';
			}
			return $this->InputSafeString($text);
		}
	} // end of fn ShortText
	
	function AcceptablePW($pw = "", $min = 8, $max = 20)
	{	//return preg_match("{^[A-Za-z0-9]{" . $min . "," . $max . "}$}i", $pw);
		return preg_match("{^[A-Za-z0-9\!\@\#\$\%\^\&\*\-\(\)]{" . $min . "," . $max . "}$}i", $pw);
	} // end of AcceptablePW
	
	function ConfirmCode($length = 5, $use_upper = true)
	{	$code = '';
		for ($i = 1; $i <= $length; $i++)
		{	$letter = rand(65, 108);
			if ($letter > 90)
			{	$code .= ceil(($letter - 90) / 2);
			} else
			{	if ($use_upper && rand(0, 1))
				{	$code .= chr($letter);
				} else
				{	$code .= chr($letter + 32);
				}
			}
		}
		return $code;
	} // end of fn ConfirmCode
	
	function ValidEMail($email)
	{	$pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-zA-Z0-9_]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';
		return preg_match($pattern, $email);
	} // end of fn ValidEMail

	function ValidPhoneNumber($pnumber)
	{	$pattern = '/^[\d-() +]{6,30}$/';
		return preg_match($pattern, $pnumber);
	} // end of fn ValidPhoneNumber

	function ValidMobileNumber($pnumber)
	{	$pattern = '/^[\d ]{6,30}$/';
		return preg_match($pattern, $pnumber);
	} // end of fn ValidMobileNumber

	function ValidIntPhoneCode($pnumber)
	{	$pattern = '/^(00|\+)[\d]{2,3}$/';
		return preg_match($pattern, $pnumber);
	} // end of fn ValidIntPhoneCode
	
	function SQLSafe($string)
	{	$sqlsafe = @mysql_real_escape_string(trim($string));
		return $sqlsafe;
	} // end of fn SQLSafe

	function BadCharacters($str)
	{	if ($str != htmlspecialchars($str, ENT_NOQUOTES))
		{	return true;
		}
		if (preg_match('{(%\d|\\\u|\\\x)}', $str))
		{	return true;
		}
		return false;
	} // end of BadCharacters
	
	function StripAllTags($data)
	{	if (is_array($data))
		{	$stripped = array();
			foreach ($data as $key=>$value)
			{	$stripped[$key] = $this->StripAllTags($value);
			}
		} else
		{	$stripped = "";
			if (!$this->BadCharacters($data)) $stripped = $data;
		}
		return $stripped;
	} // end of fn StripAllTags
	
	function ToInt($number)
	{	return (int)str_replace(array(",", " "), "", $number);
	} // end of fn ToInt
	
	function InputSafeString($string)
	{	return htmlentities(stripslashes($string), ENT_QUOTES, "utf-8", false);
	} // end of fn InputSafeString
	
	function SafeString($text)
	{	return trim(preg_replace("|[^\w\.\(\) ]|s", "", $text));
	} // end of fn SafeString	

	function Text2Para($text)
	{	$text = $this->InputSafeString($text);
		$newtext = "";
		foreach (explode("\n", $text) as $para)
		{	$newtext .= "<p>$para</p>\n";
		}
		return $newtext;
	} // end of fn Text2Para
	
	function StripJS($string)
	{   return preg_replace("|<script[^>]*?>.*?</script>|si", "", $string);
	} // end of fn StripJS
	
	function FormatYM($months, $mstring = "mth", $ystring = "yr")
	{	$str = "";
		if ($years = floor($months / 12))
		{	$str = $years . ($years == 1 ? " $ystring " : " {$ystring}s ");
		}
		if ($months = $months % 12)
		{	$str .= $months . ($months == 1 ? " $mstring" : " {$mstring}s");
		}
		return trim($str);
	} // end of fn FormatYM
	
	function FormatYMLong($months)
	{	$str = "";
		if ($years = floor($months / 12))
		{	$str = $years . ($years == 1 ? " year " : " years ");
		}
		if ($months = $months % 12)
		{	$str .= $months . ($months == 1 ? " month " : " months");
		}
		return $str;
	} // end of fn FormatYM
	
	function CSRFCheck()
	{	$host = substr(strstr($_SERVER["HTTP_REFERER"], "//"), 2);
		if ($slashpos = strpos($host, "/"))
		{	$host = substr($host, 0, $slashpos);
		}
		if ($host != $_SERVER["HTTP_HOST"])
		{	exit;
		}
	} // end of fn CSRFCheck
	
	function Ordinal($int)
	{	if ((floor($int / 10) % 10) == 1)
		{	return "th";
		} else
		{	switch ($int % 10)
			{	case 1: return "st";
				case 2: return "nd";
				case 3: return "rd";
				default: return "th";
			}
		}
	} // end of fn Ordinal
	
	function PmtOptions($type = "all", $lang = "")
	{	if (!$lang)
		{	$lang = $this->def_lang;
		}
		$options = array();
		$sql = "SELECT pmtoptions.*, pmtoptions_lang.optname, pmtoptions_lang.opttext, pmtoptions_lang.feetext  FROM pmtoptions, pmtoptions_lang WHERE pmtoptions.optvalue=pmtoptions_lang.optvalue AND pmtoptions_lang.lang='{$lang}'";
		switch ($type)
		{	case "payondoor": $sql .= " AND payondoor=1";
						break;
			case "cash": $sql .= " AND paypal=0";
						break;
			case "paypal": $sql .= " AND paypal=1";
						break;
		}
		$sql .= " ORDER BY optorder";
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$options[$row["optvalue"]] = $row["optname"];
			}
		}
		return $options;
	} // end of fn PmtOptions
	
	function GetAvailableCourses($schedule_only = false, $adminuser = false, $omitcourse = 0)
	{	$courses = array();
		if ($schedule_only)
		{	
			$sql = "SELECT coursescontent.*, coursescontent_lang.ctitle, courses.country, courses.city FROM coursescontent, coursescontent_lang, courses WHERE coursescontent.cid=coursescontent_lang.cid AND coursescontent.cid=courses.coursecontent AND coursescontent.live=1 AND courses.endtime>'" . $this->datefn->SQLDateTime() . "' AND courses.live=1 AND NOT courses.cid=" . (int)$omitcourse . " ORDER BY coursescontent_lang.ctitle";
		} else
		{	$sql = "SELECT coursescontent.*, coursescontent_lang.ctitle FROM coursescontent, coursescontent_lang WHERE coursescontent.cid=coursescontent_lang.cid AND live=1 ORDER BY coursescontent_lang.ctitle";
		}
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if (!$schedule_only || !$adminuser->userid || $adminuser->CanAccessCity($row["city"]))
				{	$courses[$row["cid"]] = $row["ctitle"];
				}
			}
		}
		
		return $courses;
	} // end of fn GetAvailableCourses
	
	function InstructorList($field = 'array', $liveonly = false, $extra_inst = 0)
	{	$instlist = array();
		$sql = 'SELECT * FROM instructors';
		if ($liveonly)
		{	$sql .= ' WHERE live=1';
			if ($extra_inst)
			{	$sql .= ' OR inid=' . (int)$extra_inst;
			}
		}
		$sql .= ' ORDER BY instname';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if (isset($row[$field]))
				{	$instlist[$row['inid']] = $row[$field];
				} else
				{	if ($field == 'array')
					{	$instlist[$row['inid']] = $row;
					} else
					{	$instlist[$row['inid']] = implode(',', $row);
					}
				}
			}
		}
		return $instlist;
	} // end of fn InstructorList
	
	function GetInstructor($inid = '', $field = '')
	{	
		if ($result = $this->db->Query('SELECT * FROM instructors WHERE inid=' . (int)$inid))
		{	if ($row = $this->db->FetchArray($result))
			{	if (isset($row[$field]))
				{	return $row[$field];
				} else
				{	return $row;
				}
			}
		}
		return false;
	} // end of fn GetInstructor
	
	function GetPostAuthor($inid = '', $field = '')
	{	
		if ($result = $this->db->Query('SELECT firstname, surname FROM adminusers WHERE auserid=' . (int)$inid))
		{	if ($row = $this->db->FetchArray($result))
			{	if (isset($row[$field]))
				{	return $row[$field];
				} else
				{	return $row;
				}
			}
		}
		return false;
	} // end of fn GetPostAuthor
	
	
	function CurrencyList($field = '')
	{	$currencies = array();
		if ($result = $this->db->Query('SELECT * FROM currencies ORDER BY curorder'))
		{	while ($row = $this->db->FetchArray($result))
			{	if (isset($row[$field]))
				{	$currencies[$row['curcode']] = $row[$field];
				} else
				{	if ($field == 'full')
					{	$currencies[$row['curcode']] = $row;
					} else
					{	$currencies[$row['curcode']] = $row['curcode'] . ' - ' . $row['cursymbol'];
					}
				}
			}
		}
		return $currencies;
	} // end of fn CurrencyList
	
	function GetCurrency($curcode = '')
	{	$currency = array();
		if ($result = $this->db->Query('SELECT * FROM currencies WHERE curcode="' . $curcode . '"'))
		{	if ($row = $this->db->FetchArray($result))
			{	$currency = $row;
			}
		}
		return $currency;
	} // end of fn GetCurrency
	
	function GetCountry($code = '', $field = 'shortname')
	{	
		if ($result = $this->db->Query('SELECT * FROM countries WHERE ccode="' . $code . '"'))
		{	if ($row = $this->db->FetchArray($result))
			{	if ($field == 'array')
				{	return $row;
				} else
				{	return $row[$field];
				}
			}
		}
		return false;
	} // end of fn GetCountry
	
		function GetCountries($field = 'shortname', $adminallowed_only = false)
	{	$countries = array();
		
		$sql = 'SELECT * FROM countries ORDER BY shortname ASC';
		//$sql = 'SELECT countries.*, IF(toplist > 0, 0, 1) AS istoplist FROM countries ORDER BY istoplist, toplist, shortname';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if (!$field || $field == 'array')
				{	$countries[$row['ccode']] = $row;
				} else
				{	$countries[$row['ccode']] = $row[$field];
				}
			}
		}
		return $countries;
	} // end of fn GetCountries
	
	function CityString($cityid = 0, $country = true)
	{	$name = "";
		if ($result = $this->db->Query('SELECT cities.cityname, countries.shortname FROM cities, countries WHERE cities.country=countries.ccode AND cityid=' . (int)$cityid))
		{	if ($row = $this->db->FetchArray($result))
			{	$name = $row['cityname'];
				if ($country)
				{	$name .= ' - ' . $row['shortname'];
				}
			}
		}
		return $name;
	} // end of fn CityString
	
	function StringToTime($string = '')
	{	
		$min = 0;
		$hour = (int)$string;
		
		if (strstr($string, ':'))
		{	$time = explode(':', $string);
			$min = (int)$time[1];
		} else
		{	if (strstr($string, '.'))
			{	$time = explode('.', $string);
				$min = (int)$time[1];
			}
		}
		
		if ($min >= 60)
		{	$hour += floor($min / 60);
			$min = $min % 60;
		}
		
		return str_pad($hour % 24, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
		
	} // end of fn StringToTime
	
	function TimeToNumber($time = '')
	{	$time = str_replace('.', ':', $time);
		$time_list = explode(':', $time);
		return (int)$time_list[0] + ((int)$time_list[1] / 60);
	} // end of fn TimeToNumber
	
	function NumberToTime($number = 0)
	{	$hours = (int)$number;
		$mins = ($number - $hours) * 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad(round($mins, 0), 2, '0', STR_PAD_LEFT);
	} // end of fn NumberToTime
	
	function ReSizePhoto($uploadfile, $file, $width, $height)
	{	
		$isize = getimagesize($uploadfile);
		$new_ratio = $height / $width;
		$iratio = $isize[1] / $isize[0];
		
		$h_offset = 0;
		$w_offset = 0;
		
		$w_fromold = $isize[0];
		$h_fromold = $isize[1];
		
		if ($iratio < $new_ratio) // old image is too wide, use full height of old image
		{	$w_fromold = $isize[1] / $new_ratio; // width of section to use
			$w_offset = ($isize[0] - $w_fromold) / 2;
		} else
		{	if ($iratio > $new_ratio) // old image is too high or same, use full width of old image
			{	$h_fromold = $isize[0] * $new_ratio;
				$h_offset = ($isize[1] - $h_fromold) / 2;
			}
		}
	//	echo ini_get("memory_limit"), "<br />";
		$newimage = imagecreatetruecolor($width, $height);
		if (!@$oldimage = imagecreatefromjpeg($uploadfile))
		{	$oldimage = imagecreatefrompng($uploadfile);
		}
		
		//echo "<p>", $w_fromold, "x", $h_fromold, "</p>";
		
		imagecopyresampled($newimage,$oldimage,0,0,$w_offset,$h_offset,$width, $height, $w_fromold, $h_fromold);
		
		imagedestroy($oldimage);
		
		ob_start();
		imagejpeg($newimage, NULL, 100);
		$final_image = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($newimage);
		
		file_put_contents($file, $final_image);
		chmod($file, 0777);
		
	} // end of fn ReSizePhoto
	
	function GetScriptPlus()
	{	
		$scriptplus = $_SERVER['REQUEST_URI'];
		if ($qarray = $_GET)
		{	if ($qarray)
			{	$q = array();
				foreach ($qarray as $key=>$value)
				{	$q[] = $key . '=' . $value;
				}
				$scriptplus = $_SERVER['SCRIPT_NAME'] . '?' . implode('&amp;', $q) . '&amp;';
			} else
			{	$scriptplus = $_SERVER['SCRIPT_NAME'] . '?';
			}
		} else
		{	$scriptplus .= '?';
		}
		return $scriptplus;
	} // end of fn GetScriptPlus
	
	function VarDump($var)
	{	echo '<pre>';
		print_r($var);
		echo '</pre>';
	} // end of fn VarDump
	
	function OutputDate($string = '', $format = 'd M Y')
	{
		if(!$string)
		{	$string = date('Y-m-d H:i:s');
		}
		return date($format, strtotime($string));	
	} // end of fn OutputDate
	
	function AddBackLinkHiddenField(&$form)
	{	if ($_POST["back_page"])
		{	$back_page = $_POST["back_page"];
		} else
		{	if (!$_GET["no_bl"] && strstr($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_HOST"]))
			{	$back_page = $_SERVER["HTTP_REFERER"]; 
			}
		}
		if ($back_page)
		{	$form->AddHiddenInput("back_page", $back_page);
		}
	} // end of fn AddBackLinkHiddenField
	
	function OptionsToRows($options = array(), $perrow = 4)
	{	$rows = array();
		$colcount = ceil(count($options) / $perrow);
		foreach ($options as $opt)
		{	$rows[$i++ % $colcount][] = $opt;
		}
		
		return $rows;
	} // end of fn OptionsToRows
	
	function GetAdminUser()
	{	static $adminuser;
		if (!is_a($adminuser, 'AdminUser'))
		{	$adminuser = new AdminUser((int)$_SESSION[SITE_NAME]['auserid']);
		}
		return $adminuser;
	} // end of fn GetAdminUser
	
	function CanAdminUserDelete()
	{	$adminuser = $this->GetAdminUser();
		return $adminuser->userid;
	//	return $this->CanAdminUser('deletions');
	} // end of fn CanAdminUserDelete
	
	function CanSeeHistory()
	{	return true;//$this->CanAdminUser('admin');
	} // end of fn CanSeeHistory
	
	function CanAdminUser($area)
	{	$adminuser = $this->GetAdminUser();
		return $adminuser->CanUserAccess($area);
	} // end of fn CanAdminUserDelete
	
	function DisplayHistoryLink($tablename = '', $tableid = '')
	{	ob_start();
		if ($this->CanSeeHistory() && $tablename && $tableid)
		{	echo "<a class='historyOpener' onclick='ShowAdminActions(\"", $this->InputSafeString($tablename), "\", \"", $this->InputSafeString($tableid), "\");'><img src='../img/template/history_icon.png' title='history' alt='history'/></a>";
		}
		return ob_get_clean();
	} // end of fn DisplayHistoryLink
	
	function DisplayHistoryDeletedLink($tablename = "")
	{	ob_start();
		if ($this->CanSeeHistory() && $tablename)
		{	echo "<a class='historyOpener' onclick='ShowAdminActionsDeleted(\"", $this->InputSafeString($tablename), "\");'>",
				//"<img src='../img/template/history_icon.png' title='history' alt='history'/>",
				"deleted history",
				"</a>";
		}
		return ob_get_clean();
	} // end of fn DisplayHistoryDeletedLink
	
	function RecordAdminAction($parameters = array())
	{	
		if (($area = $this->SQLSafe($parameters['area'])) && ($tablename = $this->SQLSafe($parameters['tablename'])) && ($tableid = $this->SQLSafe($parameters['tableid'])) && ($auserid = (int)$_SESSION[SITE_NAME]['auserid']))
		{	$action = $this->SQLSafe($parameters['action']);
			$actionto = $this->SQLSafe($parameters['actionto']);
			$actionfrom = $this->SQLSafe($parameters['actionfrom']);
			$actiontype = $this->SQLSafe($parameters['actiontype']);
			$linkmask = $this->SQLSafe($parameters['linkmask']);
			$deleteparentid = $this->SQLSafe($parameters['deleteparentid']);
			$deleteparenttable = $this->SQLSafe($parameters['deleteparenttable']);
			$actiontime = $this->datefn->SQLdateTime();
			$sql = "INSERT INTO adminactions SET area='$area', tablename='$tablename', tableid='$tableid', auserid=$auserid, actiontime='$actiontime', action='$action', actionto='$actionto', actionfrom='$actionfrom', actiontype='$actiontype', linkmask='$linkmask', deleteparentid='$deleteparentid', deleteparenttable='$deleteparenttable'";
			if (!$this->db->Query($sql))
			{	//echo "<p>", $this->db->Error(), "</p>\n";
			}
		}
		
	} // end of fn RecordAdminAction
	
	
	function CSVSafeString($text = '')
	{	return str_replace('"', "'", stripslashes($text));
	} // end of fn CSVSafeString
	
	public function SocialLinks()
	{	$links = array('facebook'=>'http://www.facebook.com/AlKauthar.Institute', 'twitter'=>'http://twitter.com/#!/AlKauthar');
		switch ($this->lang)
		{	case 'fr': $links['facebook'] = 'http://www.facebook.com/AlKautharFrance';
						$links['twitter'] = 'http://www.twitter.com/AlKautharFrance';
						break;
		}
		return $links;
	} // end of fn SocialLinks
	
	protected function SummaryDesc($text = '', $length = 0)
	{	return $this->InputSafeString(substr(strip_tags(html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8')), 0, $length));
	} // end of fn SummaryDesc
	
	public function GetProduct($id = 0, $type = 'store')
	{
		switch($type)
		{
			case 'course':
				return new CourseProduct($id);
			case 'store':	
				return new StoreProduct($id);
			case 'sub':	
				return new SubscriptionProduct($id);
		}
	} // end of fn GetProduct
	
	public function formatPrice($price = 0.00)
	{	return '&pound;' . number_format($price, 2, '.', '');
	} // end of fn formatPrice
	
	public function formatPricePlain($price = 0.00)
	{	return '£' . number_format($price, 2, '.', '');
	} // end of fn formatPricePlain
	
	public function CreateSlug($name = '')
	{	return $this->TextToSlug($name);
	} // end of fn CreateSlug
	
	public function GetSocialLinks($type = '1', $fblike = false)
	{	ob_start();
		switch ((int)$type)
		{	case 1:
				echo "<div class='hr'></div><div class='social-links-page'><script type='text/javascript'>var switchTo5x=false;</script><script type='text/javascript' src='http://w.sharethis.com/button/buttons.js'></script><script type='text/javascript'>stLight.options({publisher: 'ur-961a8a43-dd22-fcd3-9641-1f9cd9c3754'});</script>
							<span class='st_sharethis_hcount' displayText='ShareThis'></span>
							<span class='st_facebook_hcount' displayText='Facebook'></span>
							<span class='st_twitter_hcount' displayText='Tweet'></span>
							<span class='st_linkedin_hcount' displayText='LinkedIn'></span>
							<span class='st_email_hcount' displayText='Email'></span>
					<div class='clear'></div></div><div class='hr' style='margin-bottom:10px;'></div>";
				break;
			case 2:
				echo "<div class='hr'></div><div class='social-links-page'><script type='text/javascript'>var switchTo5x=false;</script><script type='text/javascript' src='http://w.sharethis.com/button/buttons.js'></script><script type='text/javascript'>stLight.options({publisher: 'ur-961a8a43-dd22-fcd3-9641-1f9cd9c3754'});</script>
					<span class='st_facebook_hcount' displayText='Facebook'></span>
							<span class='st_twitter_hcount' displayText='Tweet'></span>
							<span class='st_linkedin_hcount' displayText='LinkedIn'></span>
							<span class='st_email_hcount' displayText='Email'></span>
					<div class='clear'></div></div><div class='hr' style='margin-bottom:10px;'></div>";
				break;
			case 4:
				echo "<div class='social_links_insert'>";
				echo "<script type='text/javascript'>var switchTo5x=false;</script>";
				echo "<script type='text/javascript' src='http://w.sharethis.com/button/buttons.js'></script>";
				echo "<script type='text/javascript'>stLight.options({publisher: 'ur-961a8a43-dd22-fcd3-9641-1f9cd9c3754'});</script>";
				echo "<span class='st_sharethis_custom' displayText=''></span>";
				echo "</div>";
				break;
			case 3:
			case 5:
				$links = array();
				$links[] = '<span class="st_sharethis_custom" displayText="ShareThis"></span>';
				$links[] = '<span class="st_facebook_custom" displayText="Facebook"></span>';
				$links[] = '<span class="st_twitter_custom" displayText="Tweet"></span>';
				$links[] = '<span class="st_google_custom" displayText="Google"></span>';
				$links[] = '<span class="st_email_custom" displayText="Email"></span>';
				
				echo '<div class="social_links_insert">', 
				'<script type="text/javascript">var switchTo5x=false;</script>
				<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
				<script type="text/javascript">stLight.options({publisher: "ur-961a8a43-dd22-fcd3-9641-1f9cd9c3754"});</script>';
				foreach ($links as $link)
				{	echo $link;
				}
				echo '</div><div class="clear"></div>';

				break;
		}
		return ob_get_clean();
	} // end of fn GetSocialLinks
	
	public function GetSidebarQuote()
	{	ob_start();
		$sql = 'SELECT quotetext FROM pagequotes WHERE live=1 ORDER BY RAND() LIMIT 1';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	echo '<div class="sidebar-quote">', nl2br($this->InputSafeString($row['quotetext'])), '</div>';
			}
		}
		return ob_get_clean();
	} // end of fn GetSidebarQuote
	
	public function GetAllTaxRates()
	{	$rates = array();
		$sql = 'SELECT * FROM taxrates';
		if ($result = $this->db->Query('SELECT * FROM taxrates'))
		{	while ($row = $this->db->FetchArray($result))
			{	$rates[$row['id']] = $row;
			}
		}
		return $rates;
	} // end of fn GetAllTaxRates

	public function TaxRatesForDropDown()
	{	$list = array();
		if ($rates = $this->GetAllTaxRates())
		{	foreach ($rates as $rate)
			{	$list[$rate['id']] = $this->InputSafeString($rate['description']) . ' (' . round($rate['rate'], 2) . '%)';
			}
		}
		return $list;
	} // end of fn TaxRatesForDropDown
	
	public function TextToSlug($text = '')
	{	$text = preg_replace('|[^\d\w _-]|', '', $text);
		while (strstr($text, '  '))
		{	$text = str_replace('  ', ' ', $text);
		}
		return strtolower(str_replace(' ', '-', $text));
	} // end of fn TextToSlug
	
	function ReSizePhotoPNG($uploadfile = '', $file = '', $maxwidth = 0, $maxheight = 0, $imagetype = '')
	{	$isize = getimagesize($uploadfile);
		$ratio = $maxwidth / $isize[0];
		$h_ratio = $maxheight / $isize[1];
		if ($h_ratio > $ratio)
		{	$ratio = $h_ratio;
		}
		switch ($imagetype)
		{	case 'png': $oldimage = imagecreatefrompng($uploadfile);
							break;
			case 'jpg':
			case 'jpeg': $oldimage = imagecreatefromjpeg($uploadfile);
							break;
		}
		
		if ($oldimage)
		{	$w_new = ceil($isize[0] * $ratio);
			$h_new = ceil($isize[1] * $ratio);
			
			if ($maxwidth && $maxheight && $ratio != 1)
			{	$newimage = imagecreatetruecolor($w_new,$h_new);
				if ($imagetype == 'png')
				{	imagealphablending($newimage, false);
					imagesavealpha($newimage, true);
				}
				imagecopyresampled($newimage,$oldimage,0,0,0,0,$w_new, $h_new, $isize[0], $isize[1]);
			} else
			{	$newimage = $oldimage;
				if ($imagetype == 'png')
				{	imagealphablending($newimage, false);
					imagesavealpha($newimage, true);
				}
			}
			
			// now get middle chunk - horizontally
			if ($maxwidth && $maxheight && ($w_new > $maxwidth || $h_new > $maxheight))
			{	$resizeimg = imagecreatetruecolor($maxwidth, $maxheight);
				if ($imagetype == 'png')
				{	imagealphablending($resizeimg, false);
					imagesavealpha($resizeimg, true);
				}
				$leftoffset = floor(($w_new - $maxwidth) / 2);
				imagecopyresampled($resizeimg, $newimage, 0, 0, floor(($w_new - $maxwidth) / 2), floor(($h_new - $maxheight) / 2), $maxwidth, $maxheight, $maxwidth, $maxheight);
				$newimage = $resizeimg;
			}
			
			ob_start();
			imagepng($newimage, NULL, 3);
			return file_put_contents($file, ob_get_clean());
		}
	} // end of fn ReSizePhotoPNG

	public function ListProductReviews($reviews = array(), $ptype = 'store', $limit = 0)
	{	ob_start();
		if ($reviews)
		{	
			echo '<div class="reviews_wrapper">', count($reviews) > 1 ? '<div class="reviews_next"></div><div class="reviews_prev"></div>' : '', '<ul class="review_list">';
			foreach($reviews as $review_row)
			{	if ((++$count > $limit) && $limit)
				{	break;
				}
				$review = new ProductReview($review_row);
				echo '<li><div class="review_text_container">';
					//'<span class="rating"><span style="width:', $rating = (int)($review->details['rating'] * 100), '%">', $rating, '%</span></span>',
				if ($review->details['revtitle'])
				{	echo '<h4>', $this->InputSafeString($review->details['revtitle']), '</h4>';
				}
				echo '<p class="review_text">', nl2br($this->InputSafeString($review->details['review'])), '</p></div><p class="review_author">', 
					//$this->AgoDateString(strtotime($review->details['revdate'])), 
					'by ', $this->InputSafeString($review->details['reviewertext']), '</p></li>';
			}
			echo '</ul></div>';
			if (count($reviews) > 1)
			{	
				echo '<script>$(function() { $(".reviews_wrapper ul").cycle({ fx: "scrollHorz", timeout: 0, next: ".reviews_next", prev: ".reviews_prev" }); }); </script>';
			}
		}
		return ob_get_clean();
	} // end of fn ListProductReviews
	
	public function RatingDisplay($rating = 0)
	{	ob_start();
		echo '<span class="rating"><span style="width:', $rating_pc = (int)($rating * 100), '%">', $rating_pc, '%</span></span>';
		return ob_get_clean();
	} // end of fn RatingDisplay
	
	public function AgoDateString($date = '')
	{	$now = time();
		if ($this->datefn->SQLDate() == $this->datefn->SQLDate($date))
		{	return 'today';
		} else
		{	$days = ceil(($now - $date) / $this->datefn->secInDay);
			if (($days = ceil(($now - $date) / $this->datefn->secInDay)) > 1)
			{	return $days . ' days ago';
			} else
			{	return '1 day ago';
			}
		}
	} // end of fn AgoDateString
	
	public function GetStudentFromEmail($email = '', $field = '')
	{	$sql = 'SELECT ' . (($field = $this->SQLSafe($field)) ? $field : '*') . ' FROM students WHERE username="' . $this->SQLSafe($email) . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $field ? $row[$field] : $row;
			}
		}
		return false;
	} // end of fn GetStudentFromEmail
	
	public function FiletypeFromFilename($filename = '')
	{	if ($dot_pos = strrpos($filename, '.'))
		{	return substr($filename, $dot_pos + 1);
		}
		return '';
	} // end of fn FiletypeFromFilename
	
	public function SubMenuToggle($title = '', $menu = '', $menuid = '', $open = false)
	{	ob_start();
		echo '<h3 onclick="SubPageContainerToggle(\'', $menuid, '\');" id="subpage_menu_header_', $menuid, '" class="subpage_menu_header_', $open ? 'open' : 'closed', '">', $this->InputSafeString($title), '</h3><div id="subpage_menu_container_', $menuid, '" class="subpage_menu_container_', $open ? 'open' : 'closed', '">', $menu, '</div>';
		return ob_get_clean();
	} // end of fn SubMenuToggle
	
	public function DefaultImageSRC($dimensions = array(), $bordercolour = '', $borderwidth = 0) // needs array of ($width, $height) to be passed in
	{	if (($width = (int)$dimensions[0]) && ($height = (int)$dimensions[1]))
		{	ob_start();
			echo SITE_URL, 'default_image.php?width=', $width, '&height=', $height;
			if ($bordercolour && $this->ValidColourString($bordercolour))
			{	echo '&bcolour=', $bordercolour;
				if ($borderwidth = (int)$borderwidth)
				{	echo '&bwidth=', $borderwidth;
				}
			}
			return ob_get_clean();
		}
	} // end of fn DefaultIMageSRC
	
	public function ValidColourString($str)
	{	return preg_match('/^([a-fA-F0-9]{6})|([a-fA-F0-9]{3})$/', $str);
	} // end of fn ValidColourString
	
	public function FaceBookLikeLink()
	{	ob_start();
		echo '<div class="fbPageLike"><div class="fb-like" data-href="http://', $_SERVER['HTTP_HOST'], $_SERVER["REQUEST_URI"], '" data-width="50" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div></div>';
		return ob_get_clean();
	} // end of fn FaceBookLikeLink
	
} // end of defn Base
?>
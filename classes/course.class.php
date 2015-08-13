<?php
class Course extends Base implements Searchable
{	public $details = array();
	public $instructors = array();
	public $content = array();
	public $dates = array();
	public $tickets = array();
	public $venue;
	public $id = 0;
	protected $imagelocation = '';
	protected $imagedir = '';
	protected $liveonly = true;
	protected $slogan_styles = array('whi_gre'=>'white on green', 'red_yel'=>'white on orange/red', 'pnk_mar'=>'white on maroon');
	
	function __construct($id = 0, $liveonly = true)
	{	parent::__construct();
		$this->liveonly = (bool)$liveonly;
		// Images
		$this->imagelocation = SITE_URL . 'img/courses/';
		$this->imagedir = CITDOC_ROOT . '/img/courses/';
		
		$this->Get($id);
	} // fn __construct
	
	function Reset()
	{	$this->id = 0;
		$this->instructors = array();
		$this->tickets = array();
		$this->content = array();
		$this->dates = array();
		$this->venue = null;
	} // end of fn Reset
	
	function Get($id = 0)
	{	$this->Reset();
		if (is_array($id))
		{	$this->details = $id;
			$this->id = $id['cid'];
			$this->GetInstructors();
			$this->GetTickets();
			$this->GetContent();
			$this->GetDateRows();
		} else
		{	if ($result = $this->db->Query('SELECT * FROM courses WHERE cid=' . (int)$id))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
				}
			}
		}
		
	} // end of fn Get
	
	public function ProductID()
	{	return 'CE' . $this->id;
	} // end of fn ProductID
	
	function GetDateRows()
	{	$this->dates = array();
		
		$sql = 'SELECT * FROM coursedates WHERE cid='. (int)$this->id . ' ORDER BY startdate';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->dates[$row['cdid']] = $row;
			}
		}
		
	} // end of fn GetDateRows
	
	public function GetContent()
	{	$sql = 'SELECT * FROM coursecontent WHERE ccid=' . (int)$this->details['ccid'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$this->content = $row;
			}
		}
	} // end of fn GetContent
	
	function OverMultipleDays()
	{
		return strtotime($this->details['endtime']) - strtotime($this->details['starttime']);	
	} // end of fn OverMultipleDays
	
	public function GetGalleries()
	{	$galleries = array();
		
		$where = array('galleries.gid=gallerytocourse.gid', 'gallerytocourse.cid=' . (int)$this->id);
		
		if ($this->liveonly)
		{	$where[] = 'live=1';
		}
		
		$sql = 'SELECT galleries.* FROM galleries, gallerytocourse WHERE ' . implode(' AND ', $where) . ' ORDER BY galleries.gid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$galleries[$row['gid']] = $row;
			}
		}
		return $galleries;
	} // end of fn GetGalleries
	
	function GetInstructors()
	{	$this->instructors = array();
		
		$sql = 'SELECT instructors.*, courseinstructors.listorder AS cilistorder FROM courseinstructors, instructors WHERE courseinstructors.inid=instructors.inid AND cid = '. (int)$this->id . ' ORDER BY courseinstructors.listorder, instructors.instname';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->instructors[$row['inid']] = new Instructor($row);
			}
		}
		
	} // end of fn GetInstructors
	
	public function GetTickets()
	{	$this->tickets = array();
		$where = array('cid = '. (int)$this->id);
		
		if ($this->liveonly)
		{	$where[] = 'live=1';
			$today = $this->datefn->SQLDate();
			$where[] = 'startdate<="' . $today . '"';
			$where[] = '(enddate>="' . $today . '" OR enddate="0000-00-00")';
		}
		$sql = 'SELECT * FROM coursetickets WHERE ' . implode(' AND ', $where);
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->tickets[$row['tid']] = $row;
			}
		}
	} // end of fn GetTickets
	
	function GetAllCategories()
	{
		$categories = array();
		
		if ($result = $this->db->Query('SELECT * FROM coursecategories WHERE live=1 ORDER BY ctitle ASC'))
		{	if ($this->db->NumRows($result))
			{	while ($row = $this->db->FetchArray($result))
				{	$categories[$row['cid']] = $row['ctitle'];
				}
			}
		}
		
		return $categories;	
	} // end of fn GetAllCategories
	
	function GetAllCategoriesWithCourses()
	{
		$categories = array();
		
		$sql = 'SELECT coursecategories.* FROM coursecategories, coursetocats, coursecontent, courses WHERE coursetocats.catid=coursecategories.cid AND coursetocats.courseid=coursecontent.ccid AND coursecontent.ccid=courses.ccid AND courses.live=1 GROUP BY coursecategories.cid ORDER BY coursecategories.ctitle ASC';
		
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	while ($row = $this->db->FetchArray($result))
				{	$categories[$row['cid']] = $row['ctitle'];
				}
			}
		}
		
		return $categories;	
	} // end of fn GetAllCategoriesWithCourses
	
	function GetAllVenuesWithCourses()
	{
		$venues = array();
		
		$sql = 'SELECT coursevenues.vid, coursevenues.vcity FROM coursevenues,courses WHERE courses.cvenue = coursevenues.vid GROUP BY coursevenues.vcity';
		
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	while ($row = $this->db->FetchArray($result))
				{	$venues[$row['vid']] = $row['vcity'];
				}
			}
		}
		
		return $venues;	
	} // end of fn GetAllVenuesWithCourses
	
	function GetVenue()
	{
		if (is_null($this->venue))
		{	if ($result = $this->db->Query('SELECT * FROM coursevenues WHERE vid = '. (int)$this->details['cvenue']))
			{	if ($this->db->NumRows($result))
				{	while ($row = $this->db->FetchArray($result))
					{	return $this->venue = new Venue($row);	
					}
				}
			}
		} else
		{	return $this->venue;
		}
	} // end of fn GetVenue
	
	function GetGallery()
	{
		$g = new CourseGallery($this->id);
		
		if($g->id)
		{
			return $g;
		}
	} // end of fn GetGallery
	
	public function Is($name = '')
	{
		$status = new ProductStatus($this->details['cstatus']);
		
		if($status->details['name'] == $name)
		{
			return $status;
		}
	} // end of fn Is
	
	public function CanView()
	{
		return $this->details['live'];
	} // end of fn CanView
	
	public function GetDateVenue($sep = '<br />', $format = 'D jS F Y')
	{
		$line = date($format, strtotime($this->details['starttime']));
		
		if ($this->GetVenue())
		{	$line .= $sep . $this->InputSafeString($this->venue->details['vcity']);
		}
		
		return $line;
	} // end of fn GetDateVenue
	
	public function HasImage($size = '')
	{	return file_exists($this->GetImageFile($size)) ? $this->GetImageSRC($size) : false;
	} // end of fn HasImage
	
	public function GetImageFile($size = 'default')
	{	return $this->ImageFileDirectory($size) . '/' . (int)$this->details['ccid'] .'.png';
	} // end of fn GetImageFile
	
	public function ImageFileDirectory($size = 'default')
	{	return $this->imagedir . $this->InputSafeString($size);
	} // end of fn FunctionName
	
	public function GetImageSRC($size = 'default')
	{	return $this->imagelocation . $this->InputSafeString($size) . '/' . (int)$this->details['ccid'] .'.png';
	} // end of fn GetImageSRC
	
	public function GetDefaultImage($size = 'default')
	{	$content = new CourseContent($this->content);
		return $this->DefaultImageSRC($content->imagesizes[$size]);
	} // end of fn GetDefaultImage
	
	public function CourseFilter($id = null, $filter = null)
	{
		$courses = array();
		$where = array('courses.live=1');
		$tables = array('courses');
		
		switch ($filter)
		{	case 'date':
				$now = $this->datefn->SQLDate();
				switch($id)
				{	case 'upcoming':
						$where[] = 'courses.starttime>="' . $now . '"';
						break;
					case 'archive':
						$where[] = 'courses.endtime<"' . $now . '"';
						break;
				}
				break;
			case 'category':
				$tables[] = 'coursecontent';
				$tables[] = 'coursetocats';
				$where[] = 'coursetocats.courseid=coursecontent.ccid';
				$where[] = 'coursecontent.ccid=courses.ccid';
				$where[] = 'coursetocats.catid=' . (int)$id;
				break;
		}
		
		$sql = 'SELECT courses.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY courses.starttime ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$courses[] = new Course($row);
			}
		}
		
		return $courses;
	} // end of fn CourseFilter
	
	function CourseFilterMulti($categories = array(), $date = array(), $venues = array())
	{
		$courses = array();
		
		$where = array();
		$sql = 'SELECT c.* FROM courses c ';
		
		if(sizeof($categories))
		{	$where_cats = array();
			foreach($categories as $item)
			{	$where_cats[] = (int)$item;
			}
			
			$where[] = 'c.ccategory IN(' . implode(',', $where_cats) . ')';
		}
		
		if(sizeof($date))
		{
			if(in_array('upcoming', $date) && in_array('archive', $date))
			{
			} else
			{ 	if (in_array('upcoming', $date))
				{	$where[] = 'c.starttime >= NOW()';
				}
				if (in_array('archive', $date))
				{	$where[] = 'c.endtime < NOW()';
				}
			}
			
		}
		
		if (sizeof($venues))
		{
			$sql .= " LEFT JOIN coursevenues cv ON cv.vid = c.cvenue ";
			
			$where_str = 'cv.vcity IN(';
			
			foreach ($venues as $item)
			{
				$where_str .= "'" . $this->SQLSafe($item) . "',";
			}
			
			$where[] = substr($where_str, 0, -1). ')';
		}
		
		$where[] = 'c.live = 1';
		
		if ($where)
		{	$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$courses[] = new Course($row);
			}
		}
		
		return $courses;
	} // end of fn CourseFilterMulti
	
	public function UpdateQty($qty = 0)
	{
		$sql = 'UPDATE courses SET cavailableplaces = cavailableplaces ' . ($qty >= 0 ? '+ ' : '- ') . abs($qty) . ' WHERE cid = ' . (int)$this->id;	
				
		if ($result = $this->db->Query($sql))
		{
			if ($this->db->AffectedRows())
			{
				$this->Get($this->id);
				
				if (!$this->IsBookable() && ($this->details['cstockmethod'] == 1))
				{	$this->UpdateStatus('sold_out');
				}
			}
		}
	} // end of fn UpdateQty
	
	public function UpdateBookingQty($qty = 0)
	{
		$sql = 'UPDATE courses SET cbookings=cbookings ' . ($qty >= 0 ? '+ ' : '- ') . abs($qty) . ' WHERE cid = ' . (int)$this->id;	
				
		if ($result = $this->db->Query($sql))
		{
			if ($this->db->AffectedRows())
			{
				$this->Get($this->id);
				
				if (!$this->IsBookable() && ($this->details['cstockmethod'] == 1))
				{	$this->UpdateStatus('sold_out');
				}
			}
		}
	} // end of fn UpdateBookingQty
	
	public function GetUpcomingCourses($limit = 0)
	{
		$courses = array();
		
		$sql = 'SELECT * FROM courses WHERE endtime>"' . $this->datefn->SQLDateTime() . '" AND live=1 ORDER BY starttime ASC';
		
		if ($limit)
		{	$sql .= ' LIMIT ' . (int)$limit;
		}
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$courses[] = new Course($row);
			}
		}
		
		return $courses;
	} // end of fn GetUpcomingCourses
	
	public function UpdateStatus($status = '')
	{
		if(!$id = (int)$status)
		{	if ($result = $this->db->Query('SELECT * FROM productstatus WHERE name = "' . $this->SQLSafe($status) . '"'))
			{	if ($row = $this->db->FetchArray($result))
				{	$id = $row['id'];	
				}
			}
		}
		
		if($id)
		{	$this->db->Query('UPDATE courses SET cstatus=' . (int)$id . ' WHERE cid=' . (int)$this->id);
			return true;
		}
	} // end of fn UpdateStatus
	
	public function GetBookings()
	{	$bookings = array();
		$sql = 'SELECT * FROM coursebookings WHERE course=' . $this->id . ' ORDER BY id ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$bookings[$row['id']] = $row;
			}
		}
		return $bookings;
	} // end of fn GetBookings
	
	function CanAttend()
	{	return strtotime($this->details['starttime'] . ' 00:00:00') < time();
	} // end of fn CanAttend
	
	public function GetDates($passedonly = false)
	{	$dates = array();
		$now = time();
		foreach ($this->dates as $date_row)
		{	$start = strtotime($date_row['startdate']);
			$end = strtotime($date_row['enddate'] . ' 12:00:00');
			while ($start < $end)
			{	if ($passedonly && ($start > $now))
				{	break;
				}
				$dates[$start] = $this->datefn->SQLDate($start);
				$start += $this->datefn->secInDay;
			}
		}
		return $dates;
	} // end of fn GetDates
	
	public function DatesDisplay($format = 'd/m/Y')
	{	$text = date($format, strtotime($this->details['starttime']));
		if ($this->details['endtime'] > $this->details['starttime'])
		{	$text .= ' to ' . date($format, strtotime($this->details['endtime']));
		}
		return $text;
	} // end of fn DatesDisplay
	
	public function DateDisplayForDetails($sep = '<br />', $date_format = 'l jS F, Y', $date_sep = ' until ', $time_sep = ', ')
	{	$dates = array();
		foreach ($this->dates as $date)
		{	ob_start();
			echo date($date_format, strtotime($date['startdate']));
			if ($date['startdate'] != $date['enddate'])
			{	echo $date_sep, date($date_format, strtotime($date['enddate']));
			}
			if ($date['timetext'])
			{	echo $time_sep, $this->InputSafeString($date['timetext']);
			}
			$dates[] = ob_get_clean();
		}
		return implode($sep, $dates);
	} // end of fn DateDisplayForDetails
	
	public function IsBookable()
	{	if ($this->details['bookable'] && ($this->datefn->SQLDate() >= $this->details['starttime']) && ($this->datefn->SQLDate() <= $this->details['endtime']))
		{	switch ($this->details['cstockmethod'])
			{	case 0: // unlimited
						return true;
				case 1: // limited by overall course places
						return $this->details['cavailableplaces'] > $this->details['cbookings'];
				case 2: // limited by ticket, check for any tickets not sold out
						foreach ($this->tickets as $ticket)
						{	if ($ticket['tqty'] > $ticket['tbooked'])
							{	return true;
							}
						}
						
				default: return false;
			}
		}
	} // end of fn IsBookable
	
	public function GetReviews($exclude = 0)
	{	$reviews = array();
		$where = array('pid=' . (int)$this->id, 'ptype="course"');
		if ($exclude = (int)$exclude)
		{	$where[] = 'NOT sid=' . $exclude;
		}
		if ($this->liveonly)
		{	$where[] = 'suppressed=0';
		}
		$sql = 'SELECT * FROM productreviews WHERE ' . implode(' AND ', $where) . ' ORDER BY revdate DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$reviews[$row['prid']] = $row;
			}
		}
		return $reviews;
	} // end of fn GetReviews
	
	public function ReviewList($limit = 1, $exclude = 0)
	{	if ($reviewlist = $this->ListProductReviews($this->GetReviews($exclude), 'course', $limit))
		{	return '<h3>Course Reviews</h3>' . $reviewlist;
		} else
		{	return '<h3>Course Reviews</h3><p>There are no reviews of this course yet</p>';
		}
	} // end of fn ReviewList
	
	public function GalleryList()
	{	ob_start();
		$galleries = array();
		if ($raw_galleries = $this->GetGalleries())
		{	foreach ($raw_galleries as $gallery_row)
			{	$gallery = new Gallery($gallery_row);
				if ($gallery->photos)
				{	$galleries[] = $gallery;
				}
			}
		}
		if ($galleries)
		{	
			echo '<script type="text/javascript">courseID=', $this->id, '; $().ready(function(){$("body").append($(".jqmWindow")); $("#gal_modal_popup").jqm();});</script><!-- START gallery modal popup --><div id="gal_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="galModalInner"></div></div>',
				'<h3>', ucwords($this->content['ctype']), ' photos</h3>';
			foreach ($galleries as $gallery)
			{	$title = $this->InputSafeString($gallery->details['title']);
				$count = 0;
				echo '<div class="courseGalSelection" onclick="OpenGalleryLightBox(', $gallery->id, ');">';
				foreach ($gallery->photos as $photo_row)
				{	$photo = new GalleryPhoto($photo_row);
					if ($img = $photo->HasImage('thumbnail'))
					{	echo '<img src="', $img, '" alt="', $title = $this->InputSafeString($photo->details['title']), '" title="', $title, '" />';
						if (++$count >= 6)
						{	break;
						}
					}
				}
				echo '</div>';
			}
		} else
		{	//echo '<div id="galHolder">Gallery to follow</div>';
		}
		return ob_get_clean();
	} // end of fn GalleryList
	
	public function SubscriptionApplies($sub = array())
	{	if (is_a($sub, 'StudentSubscription'))
		{	$sub = $sub->details;
		}
		return ($this->details['starttime'] <= substr($sub['expires'], 0, 10)) && ($this->details['endtime'] >= substr($sub['created'], 0, 10));
	} // end of fn FunctionName
	
	public function Link()
	{	return $this->link->GetCourseLink($this);
	} // end of fn Link
	
	public function SpecialSlogan()
	{	if ($this->details['endtime'] < $this->datefn->SQLDate())
		{	return array('slogan'=>'ENDED', 'style'=>'pnk_mar');
		} else
		{	if ($this->details['so_slogan'])
			{	return array('slogan'=>$this->details['so_slogan'], 'style'=>$this->details['so_style'], 'text'=>$this->details['so_text']);
			}
		}
		return false;
	} // end of fn SpecialSlogan
	
	public function NextCourse($liveonly = true)
	{	$where = array('ccid=' . (int)$this->details['ccid'], 'NOT cid=' . (int)$this->id, 'starttime>"' . $this->datefn->SQLDate() . '"');
		$tables = array('courses');
		if ($liveonly)
		{	$where[] = 'live=1';
		}
		$sql = 'SELECT * FROM ' . implode(', ', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY starttime LIMIT 1';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row;
			}
		}
		return false;
	} // end of fn NextCourse
	
	/** Search Functions ****************/
	public function Search($term)
	{
		$match = ' MATCH(coursecontent.ctitle, coursecontent.cshortoverview, coursecontent.coverview) AGAINST("' . $this->SQLSafe($term) . '") ';
		$sql = 'SELECT courses.*, ' . $match . ' as matchscore FROM courses, coursecontent WHERE courses.ccid=coursecontent.ccid AND ' . $match . ' AND courses.live=1 ORDER BY matchscore DESC';
		
		$results = array();
		
		if($result = $this->db->Query($sql))
		{	while($row = $this->db->FetchArray($result))
			{	$results[] = new Course($row);
			}
		}
		
		return $results;
	} // end of fn Search
	
	public function SearchResultOutput()
	{
		echo '<h4><span>', ucwords($this->content['ctype']), '</span><a href="', $link = $this->link->GetCourseLink($this), '">', $this->InputSafeString($this->content['ctitle']), '</a></h4><p><a href="', $link, '">read more ...</a></p>';
	} // end of fn SearchResultOutput
	
	public function GoogleCalendarButton()
	{	ob_start();
		echo '<p class="googleButton"><a href="http://www.google.com/calendar/event?action=TEMPLATE&text=', urlencode(stripslashes($this->content['ctitle'])), '&dates=', date('Ymd', strtotime($this->details['starttime'])), '/', date('Ymd', strtotime($this->details['endtime'])), '&details=&location=';
		if ($venue = $this->GetVenue())
		{	echo urlencode(stripslashes($venue->details['vname']));
		}
		echo '&trp=true&sprop=', COMPANY_NAME, '&sprop=name:', SITE_URL, '" target="_blank"><img src="http://www.google.com/calendar/images/ext/gc_button6.gif" border=0></a></p>';
		return ob_get_clean();
	} // end of fn GoogleCalendarButton
	
} // end of defn Course
?>
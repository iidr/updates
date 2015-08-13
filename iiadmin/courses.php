<?php
include_once('sitedef.php');

class CoursesContentListPage extends AdminCoursesPage
{	private $startdate = '';
	private $enddate = '';
	private $perpage = 20;
	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function CoursesLoggedInConstruct()
	{	parent::CoursesLoggedInConstruct();
		// set up dates
		if (($ys = (int)$_GET['ystart']) && ($ds = (int)$_GET['dstart']) && ($ms = (int)$_GET['mstart']))
		{	$this->startdate = $this->datefn->SQLDate(mktime(0,0,0,$ms, $ds, $ys));
		} else
		{	$this->startdate = $this->datefn->SQLDate(strtotime('-6 months'));
		}
		
		if (($ys = (int)$_GET['yend']) && ($ds = (int)$_GET['dend']) && ($ms = (int)$_GET['mend']))
		{	$this->enddate = $this->datefn->SQLDate(mktime(0,0,0,$ms, $ds, $ys));
		} else
		{	$this->enddate = $this->datefn->SQLDate(strtotime('+6 months'));
		}
	} // end of fn CoursesLoggedInConstruct
	
	function CoursesBody()
	{	$this->FilterForm();
		$this->CoursesList();
	} // end of fn CoursesBody
	
	function FilterForm()
	{	class_exists('Form');
		$startfield = new FormLineDate('', 'start', $this->startdate, $this->datefn->GetYearList(2025, -26));
		$endfield = new FormLineDate('', 'end', $this->enddate, $this->datefn->GetYearList(2025, -26));
		$dummy_content = new CourseContent();
		echo '<form class="akFilterForm"><span>Type</span><select name="ctype"><option value="">-- show all --</option>';
		foreach ($dummy_content->types as $ctype=>$cvalue)
		{	echo '<option value="', $ctype, '"', $ctype == $_GET['ctype'] ? ' selected="selected"' : '', '>', $cvalue, '</option>';
		}
		echo '</select><span>From</span>';
		$startfield->OutputField();
		echo '<span>to</span>';
		$endfield->OutputField();
		echo '<input type="submit" class="submit" value="Get" /><div class="clear"></div></form>';
	} // end of fn FilterForm
	
	function CoursesList()
	{	
		echo '<table><tr class="newlink"><th colspan="11"><a href="courseedit.php">Create new course</a></th></tr><tr><th>Schedule<br />ID</th><th>Product<br />Code</th><th>Title</th><th>Type</th><th>Dates</th><th>Live?</th><th>Bookable?</th><th>Stock Control</th><th>Ticket<br />types</th><th>Categories</th><th>Actions</th></tr>';
		$content = array();
		
		$start = 0;
		if ($_GET['page'] > 1)
		{	$start = ($_GET['page'] - 1) * $this->perpage;
		}
		$end = $start + $this->perpage;
		
		foreach ($courses = $this->Courses() as $course_row)
		{	if (++$count > $start)
			{	if ($count > $end)
				{	break;
				}
				$course = new AdminCourse($course_row);
				if (!$content[$course->details['ccid']])
				{	$content[$course->details['ccid']] = new AdminCourseContent($course->details['ccid']);
				}
				echo '<tr class="stripe', $i++ % 2, '"><td>', $course->id, '</td><td>', $course->ProductID(), '</td><td><a href="coursecontentedit.php?id=', $course->details['ccid'], '">', $this->InputSafeString($course->content['ctitle']), '</a></td><td>', $this->InputSafeString($course->content['ctype']), '</td><td>', date('d-M-y', strtotime($course->details['starttime'])), ' to ', date('d-M-y', strtotime($course->details['endtime'])), '</td><td>', $course->details['live'] ? 'Yes' : 'No', '</td><td>', $course->details['bookable'] ? 'Yes' : 'No', '</td><td>', $course->StockControlText(), '</td><td>', $course->tickets ? count($course->tickets) : '', '</td><td>', $content[$course->details['ccid']]->CategoryDisplayList(), '</td><td><a href="courseedit.php?id=', $course->id, '">edit</a>';
				if ($histlink = $this->DisplayHistoryLink('courses', $course->id))
				{	echo '&nbsp;|&nbsp;', $histlink;
				}
				if ($course->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="courseedit.php?id=', $course->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
		}
		echo "</table>";
		
		if (count($courses) > $this->perpage)
		{	$pag = new Pagination($_GET['page'], count($courses), $this->perpage);
			echo '<div class="pagination">', $pag->Display(), '</div>';
		}
		
	} // end of fn CoursesList
	
	function Courses()
	{	$courses = array();
		$tables = array('courses'=>'courses');
		$where = array();
		
		if ($_GET['ctype'])
		{	$tables['coursecontent'] = 'coursecontent';
			$where['coursecontent/courses'] = 'coursecontent.ccid=courses.ccid';
			$where['ctype'] = 'coursecontent.ctype="' . $this->SQLSafe($_GET['ctype']) . '"';
		}
		
		if ((int)$this->startdate)
		{	$where['from'] = 'endtime>="' . $this->startdate . ' 00:00:00"';
		}
		if ((int)$this->enddate)
		{	$where['to'] = 'starttime<="' . $this->enddate . ' 23:59:59"';
		}
		
		$sql = 'SELECT courses.* FROM ' . implode(', ', $tables);
		if ($wstr = implode(' AND ', $where))
		{	$sql .= ' WHERE ' . $wstr;
		}
		$sql .= ' ORDER BY courses.starttime DESC';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$courses[] = $row;
			}
		} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		
		return $courses;
	} // end of fn Courses
	
} // end of defn CoursesContentListPage

$page = new CoursesContentListPage();
$page->Page();
?>
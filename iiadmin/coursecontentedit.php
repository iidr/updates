<?php
include_once('sitedef.php');

class CourseContentEditPage extends AdminCourseContentEditPage
{	var $course;

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function CoursesLoggedInConstruct()
	{	parent::CoursesLoggedInConstruct();
		
		$this->css[] = 'course_edit.css';
		$this->js[] = 'tiny_mce/jquery.tinymce.js';
		$this->js[] = 'pageedit_tiny_mce.js';
		$this->js[] = 'course_mm.js';

		$this->course  = new AdminCourseContent($_GET['id']);
		
		if (isset($_POST['ctitle']))
		{	$saved = $this->course->Save($_POST, $_FILES['bannerfile'], $_FILES['imagefile']);
			$this->successmessage = $saved['successmessage'];
			$this->failmessage = $saved['failmessage'];
		//	if ($this->successmessage && !$this->failmessage)
		//	{	$this->RedirectBack('coursescontent.php');
		//	}
		}
		
		if ($this->course->id && $_GET['delete'] && $_GET['confirm'])
		{	if ($this->course->Delete())
			{	$this->RedirectBack('coursescontent.php');
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}
		
		if ($this->course->id)
		{	$this->breadcrumbs->AddCrumb('coursecontentedit.php?id=' . $this->course->id, $this->InputSafeString($this->course->details['ctitle']).'(Product Code: CE'.$this->course->details['id'].')');
		} else
		{	$this->breadcrumbs->AddCrumb('coursecontentedit.php', 'Creating new');
		}
	} // end of fn CoursesLoggedInConstruct
	
	function CoursesBodyContent()
	{	echo $this->course->InputForm();
	} // end of fn CoursesBodyContent
	
} // end of defn CourseContentEditPage

$page = new CourseContentEditPage();
$page->Page();
?>
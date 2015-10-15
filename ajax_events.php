<?php
include_once('init.php');
class AjaxEvents extends CourseContentListingPage
{
	function __construct()
	{	parent::__construct('event');
		
		echo $this->EventsList();
		
	} // end of fn __construct
	
} // end of defn AjaxCourses

$page = new AjaxEvents();
?>
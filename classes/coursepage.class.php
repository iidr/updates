<?php 
class CoursePage extends BasePage
{	
	private $course;
	private $content;
	private $gallery;
	
	function __construct($course = 0)
	{	parent::__construct('courses');
		$this->js[] = 'jquery-ui-1.8.23.custom.min.js';
		$this->js[] = 'jquery.mousewheel.min.js';
		$this->js[] = 'http://maps.google.com/maps/api/js?sensor=false';
		$this->css[] = 'elastislide.css';
		$this->css[] = 'jqModal.css';
		$this->js[] = 'jqModal.js';
		$this->js[] = 'modernizr.custom.17475.js';
		$this->js[] = 'jquery.easing.1.3.js';
		$this->js[] = 'jquery.elastislide_modified.js';
		$this->js[] = 'googlemap.js';
		$this->js[] = 'accordion.js';
		$this->css[] = 'page.css';	
		$this->css[] = 'course.css';
		$this->css[] = 'jquery.mCustomScrollbar.css';
		$this->css[] = 'bundles.css';	
		$this->css[] = 'studentreviews.css';
		$this->js[] = 'productreview.js';
		$this->js[] = 'jquery.lightbox-0.5.js';
		$this->css[] = 'jquery.lightbox-0.5.css';
		$this->facebookLike = true;

		$this->course = new Course($course);
		$this->pageName = $this->course->content['ctype'] . 's';
		$this->page = new PageContent($this->pageName);
		$this->content = new CourseContent($this->course->content);
		if (!$this->course->id || !$this->course->CanView())
		{	$this->Redirect('courses.php');
		}
		$this->title .= ' - ' . $this->InputSafeString($this->course->details['ctitle']);
		
		switch ($this->course->content['ctype'])
		{	case 'course':
				$this->AddBreadcrumb('Courses', $this->link->GetLink('courses.php'));
				break;
			case 'event':
				$this->AddBreadcrumb('Events', $this->link->GetLink('events.php'));
				break;
		}
		$this->AddBreadcrumb($this->InputSafeString($this->course->content['ctitle']));
	} // end of fn __construct
	
	function CourseHeader()
	{	ob_start();
	//	if ($video = $this->content->GetVideo())
	//	{	echo '<div class="col2-wrapper">';
	//		if($src = $this->course->HasImage('default'))
	//		{	echo '<img src="', $src, '" alt="" />';
	//		}
	//		echo '</div><div class="col2-wrapper">',  $video->Output(465, 290), '</div>';
	//	} else
	//	{	
			echo '<div class="col4-wrapper">';
			if($src = $this->course->HasImage('banner'))
			{	echo '<img src="', $src, '" alt="" />';
			}
			echo '</div>';
	//	}
		
		echo '<div class="clear"></div>';
		
		return ob_get_clean();	
	} // end of fn CourseHeader
	
	function CourseHeaderTitle()
	{	ob_start();
		echo '<h1><span', $this->course->details['socialbar'] ? ' class="headertextWithSM"' : '', '>', $this->InputSafeString($this->content->details['ctitle']), '</span>', $this->course->details['socialbar'] ? $this->GetSocialLinks(3, true) : '', '</h1>';
		return ob_get_clean();
	} // end of fn CourseHeaderTitle
	
	function BookButton()
	{
		ob_start();
		static $buy_enabled;
		static $popup_done = false;
		static $tickets = array();
		if (is_null($buy_enabled))
		{	$buy_enabled = false;
			
			/*$this->course->IsBookable()*/
			if ($this->course->tickets)
			{	foreach($this->course->tickets as $ticket_row)
				{	$ticket = new CourseTicket($ticket_row);
					$status = $ticket->GetStatus();
					
					if($status->details['name'] == 'in_stock')
					{	$tickets[] = $ticket;
						$buy_enabled = true;
					}// else echo $status->details['name'];
				}
			}
		}
		
		if ($buy_enabled && $tickets)
		{	//echo '<a class="course_booknow">Book this ', $this->course->content['ctype'], ' now</a>';
			echo '<a class="course_booknow">',($this->course->content['ctype']=='course')?$this->GetParameter("book_course_txt"):$this->GetParameter("book_event_txt"),'</a>';
			if (!$popup_done)
			{	$popup_done = true;
				echo '<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow")); $("#course_book_modal_popup").jqm({trigger:".course_booknow"});});</script><div id="course_book_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">X</a><form class="courseBookButton" action="', $this->link->GetLink('cart.php'), '" method="post"><p><label>Ticket:</label><select name="add" required="required">';
				echo '<option value="">Please select your ticket…</option>';
				foreach($tickets as $ticket)
				{	
					echo '<option value="', (int)$ticket->id, '" >', $this->InputSafeString($ticket->details['tname']), " - ", $this->formatPrice($ticket->details['tprice']), '</option>';
					
				}
				echo '</select></p><p><label>Qty:</label><input type="text" name="qty" value="1" size="2" required="required" /></p><p><input type="hidden" name="type" value="course" /><input type="submit" name="add_cart" value="Book now" /></p><div class="clear"></div></form></div>';
			}
		}
		
		return ob_get_clean();
	} // end of fn BookButton
	
	public function BundleList()
	{	ob_start();
		// Make sure tickets exist and course is available to book
		if ($this->course->tickets && $this->course->IsBookable())
		{
			$bundles = array();
			$exc_bundles = array();
			foreach ($this->course->tickets as $ticket_row)
			{	$ticket = new CourseTicket($ticket_row);
				$status = $ticket->GetStatus();
				
				if ($status->details['name'] == 'in_stock')
				{
					$ticket_bundles = new ProductBundles($this->GetProduct($ticket, 'course'), 'course', $exc_bundles);
					if ($tbundle = $ticket_bundles->BundlesDisplay())
					{	$bundles[] = $tbundle;
						foreach ($ticket_bundles->bundles as $bid=>$bundle)
						{	$exc_bundles[$bid] = $bundle;
						}
					}
				}
			}
			echo implode('', $bundles);
		}
		
		return ob_get_clean();

	} // end of fn BundleList
	
	public function CourseDateDisplay()
	{	ob_start();
		if ($this->course->dates)
		{	echo '<div class="course_details_sidelist"><h4>Date(s)</h4><div class="course_details_sidelist_content">';
			foreach ($this->course->dates as $date_row)
			{	if ($date_row['startdate'] == $date_row['enddate'])
				{	echo '<p>', date('D j M \'y', strtotime($date_row['startdate'])), '</p>';
				} else
				{	$startstamp = strtotime($date_row['startdate']);
					$endstamp = strtotime($date_row['enddate']);
					echo '<p>', date(date('Y', $startstamp) == date('Y', $endstamp) ? 'D j M' : 'D j M \'y', $startstamp), ' - ', date('D j M \'y', $endstamp), '</p>';
				}
				
				if ($date_row['timetext'])
				{	echo '<p>', $this->InputSafeString($date_row['timetext']), '</p>';
				}
			}
			echo $this->course->GoogleCalendarButton(), '</div><div class="clear"></div></div>';
		}
		return ob_get_clean();
	} // end of fn CourseDateDisplay
	
	public function CourseVenueDisplay()
	{	ob_start();
		if ($venue = $this->course->GetVenue())
		{	echo '<div class="course_details_sidelist"><h4>Venue</h4><div class="course_details_sidelist_content"><p>', $this->InputSafeString($venue->details['vname']), '</p><p>', $venue->GetAddress(), '</p>';
			echo '</div><div class="clear"></div></div>';
			if (($venue->details['vlat'] != 0) || ($venue->details['vlng'] != 0))
			{	echo '<div id="coursemap"></div><script>showCourseMap("coursemap", ', $venue->details['vlng'], ', ', $venue->details['vlat'], ');</script>';	
			}
		}
		return ob_get_clean();
	} // end of fn CourseVenueDisplay
	
	public function CourseTicketsDisplay()
	{	ob_start();
		if ($this->course->tickets)
		{	echo '<div class="course_details_sidelist"><h4>Tickets</h4><div class="course_details_sidelist_content">';
			foreach ($this->course->tickets as $ticket_row)
			{	echo '<p>', $this->formatPrice($ticket_row['tprice']), ' ', $this->InputSafeString($ticket_row['tname']), '</p>';
			}
			echo '</div><div class="clear"></div></div>';
		}
		return ob_get_clean();
	} // end of fn CourseTicketsDisplay
	
	public function CourseSpecialOfferDisplay()
	{	ob_start();
		if ($slogan = $this->course->SpecialSlogan())
		{	echo '<div class="course_details_slogan cl_slogan_', $this->InputSafeString($slogan['style']), '"><h4>', $this->InputSafeString($slogan['slogan']), '</h4>';
			if ($slogan['text'])
			{	echo '<p>', $this->InputSafeString($slogan['text']), '</p>';
			}
			echo '</div>';
		}
		return ob_get_clean();
	} // end of fn CourseSpecialOfferDisplay
	
	public function InstructorsList()
	{	ob_start();
		if ($this->course->instructors)
		{	echo '<div id="course_instructors"><h2>Instructor', (count($this->course->instructors) > 1) ? 's' : '', '</h2><ul>';
			foreach ($this->course->instructors as $inst)
			{	if (++$icount > 2)
				{	$icount = 0;
					echo '</ul><ul>';
				}
				$link = $this->link->GetInstructorLink($inst);
				$name = $this->InputSafeString($rawname = $inst->GetFullName());
				echo '<li><div class="course_inst_image"><a href="', $link, '"><img src="', $inst->HasImage('thumbnail') ? $inst->GetImageSRC('default') : $inst->DefaultImageSRC('default'), '" alt="', $name, '" title="', $name, '" /></a></div><div class="course_inst_text"><h3><a href="', $link, '">', $name, '</a></h3><p>', $inst->ShortText(strlen($rawname) > 23 ? 80 : 120), '</p></div><div class="clear"></div></li>';
			}
			echo '</ul><div class="clear"></div></div>';
		}
		return ob_get_clean();
	} // end of fn InstructorsList

	function MainBody()
	{	$this->Messages();
		$this->MainBodyContent();
	} // end of fn MainBody
	
	function MainBodyContent()
	{	
		echo '<div class="wrapper">', $this->CourseHeader(), '</div>', $this->NextCourseHeader(), '<div class="wrapper">', $this->CourseHeaderTitle(), '<div id="course_detail_left">';
		if ($video = $this->content->GetVideo())
		{	echo $video->Output(590, 350);
		}
		echo '<div class="the-content" id="course_detail_overview">', stripslashes($this->course->content['coverview']), '</div></div><div id="course_detail_right">', $this->BookButton(), $this->CourseCodeDisplay(), $this->CourseDateDisplay(), $this->CourseVenueDisplay(), $this->CourseTicketsDisplay(), $this->CourseSpecialOfferDisplay(), $this->BookButton(), '</div><div class="clear"></div>', $this->BundleList(), $this->InstructorsList();
		$reviewlist = $this->content->ReviewList(0);
		$reviewform = $this->user->ReviewForm($this->content->id, 'course');
		$gallerylist = $this->course->GalleryList();
		if ($reviewlist || $reviewform || $gallerylist)
		{	echo '<div id="courseReviewContainer"><div id="reviewListContainer">', $reviewlist, $reviewform, '</div><div id="courseGalleryContainer">', $gallerylist, '</div><div class="clear"></div></div>';
		}
		echo '</div>';
	} // end of fn MainBodyContent
	
	public function CourseCodeDisplay()
	{	ob_start();
		echo '<div class="course_details_sidelist course_details_code"><p>Code: ', $this->course->ProductID(), '</p></div>';
		return ob_get_clean();
	} // end of fn CourseCodeDisplay
	
	private function NextCourseHeader()
	{	ob_start();
		if ($this->course->details['starttime'] <= $this->datefn->SQLDate())
		{	if ($next_row = $this->course->NextCourse())
			{	$next = new Course($next_row);
				echo '<div id="nextCourse"><div class="wrapper">This ', $next->content['ctype'], ' is on again on ', date('jS \o\f F Y', strtotime($next->details['starttime'])), '<a href="', $next->Link(), '">Find out more</a><div class="clear"></div></div></div>';
			}
		}
		return ob_get_clean();
	} // end of fn NextCourseHeader
	
} // end of defn CoursePage
?>
<?php 
require_once('init.php');

class BookingPage extends AccountPage
{	
	public $booking;
	
	function __construct()
	{	parent::__construct();
	} // end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct('bookings');
		$this->css[] = 'course.css';
		$this->css[] = 'myacbooking.css';
		$this->css[] = 'elastislide.css';
		$this->js[] = 'jquery-ui-1.8.23.custom.min.js';
		$this->js[] = 'jquery.mousewheel.min.js';
		$this->js[] = 'modernizr.custom.17475.js';
		$this->js[] = 'jquery.easing.1.3.js';
		$this->js[] = 'jquery.elastislide_modified.js';
		$this->js[] = 'http://maps.google.com/maps/api/js?sensor=false';
		$this->js[] = 'googlemap.js';
		$this->js[] = 'accordion.js';
		$this->css[] = 'multimedia.css';
		$this->css[] = 'jqModal.css';
		$this->js[] = 'jqModal.js';
		$this->css[] = 'studentreviews.css';
		$this->js[] = 'productreview.js';
		
		$this->booking = new CourseBooking($_GET['id']);
		$this->content = new CourseContent($this->booking->course->content);
	
		if (!$this->booking->id || $this->booking->student->id != $this->user->id)
		{	$this->Redirect('account.php');
		}
		$this->AddBreadcrumb($this->InputSafeString($this->content->details['ctitle']));
		
	} // end of fn LoggedInConstruct
	
	protected function PageHeaderText()
	{	return parent::PageHeaderText() . ' - ' . $this->content->details['ctitle'] . '<span class="prodItemCode">Code: ' . $this->booking->course->ProductID() . '</span>';
	} // end of fn PageHeaderText
	
	function LoggedInMainBody()
	{	echo '<div id="bookingDetails"><div><span>Order ID.</span><div class="bdContent">';
		if ($order = $this->booking->GetOrder())
		{	echo $order['id'];
			if (($gifter = $this->booking->IsGift()) && $gifter->id)
			{	echo ', Gifted by: ', $this->InputSafeString($gifter->GetName());
			}
		}
		
		$total_discount = $this->booking->order_item['discount_total'];
		$totalpricetax = $this->booking->order_item['totalpricetax'];
		$totalpricetax -= $total_discount;
			
		echo '</div><div class="clear"></div></div><div><span>Title</span><div class="bdContent"><a href="', $this->booking->course->Link(), '">',$this->booking->course->content['ctitle'],'</a></div><div class="clear"></div></div><div><span>Paid</span><div class="bdContent">&pound;', number_format($totalpricetax, 2), '</div><div class="clear"></div></div><div><span>Course/Event date/time</span><div class="bdContent">', $this->booking->course->DateDisplayForDetails('<br />', 'D. jS F, Y', $date_sep = ' - ', $time_sep = '<br />'), '</div><div class="clear"></div></div><div><span>Venue</span><div class="bdContent">', $this->booking->course->GetVenue()->details['vname'], '<br />', $this->booking->course->GetVenue()->GetAddress(), '<br />';
		if (($this->booking->course->GetVenue()->details['vlat'] != 0) || ($this->booking->course->GetVenue()->details['vlng'] != 0))
		{	echo '<div id="bdCourseMap"></div><script>showCourseMap("bdCourseMap", ', $this->booking->course->GetVenue()->details['vlng'], ', ', $this->booking->course->GetVenue()->details['vlat'], ');</script>';
		}
		echo '</div><div class="clear"></div></div></div>';
	} // end of fn LoggedInMainBody
	
	function CourseHeaderTitle()
	{	ob_start();
		echo '<div class="course-header-title"><h1>', $this->InputSafeString($this->booking->course->content['ctitle']), '<span>Code: ', $this->booking->course->ProductID(), '</span></h1>';
		
		if($this->booking->course->content['cslogan'])
		{	echo '<h2>', $this->InputSafeString($this->booking->course->content['cslogan']), '</h2>';
		}
		echo '</div>';
		return ob_get_clean();
	} // end of fn CourseHeaderTitle
	
	public function MultiMediaListing()
	{	ob_start();
		if ($multimedia = $this->content->GetMultiMedia())
		{	echo '<h3>Multimedia recommended for this course ...</h3><div class="mm_list_container"><ul><li><ul>';
			foreach ($multimedia as $mm_row)
			{	$mm = new MultiMedia($mm_row);
				echo $mm->DisplayInList();
			}
			echo '</ul></li></ul></div>';
		}
		return ob_get_clean();
	} // end of fn MultiMediaListing
	
	public function GalleryDisplay()
	{	ob_start();
		if ($galleries = $this->booking->course->GetGalleries())
		{	echo '<div><ul>';
			foreach ($galleries as $gallery_row)
			{	if (($gallery = new Gallery($gallery_row)) && $gallery->photos)
				{	echo '<li><h4>', $this->InputSafeString($gallery->details['title']), '</h4><ul>';
					foreach ($gallery->photos as $photo_row)
					{	$photo = new GalleryPhoto($photo_row);
						if ($src = $photo->HasImage('thumbnail'))
						{	echo '<li><img src="', $src, '" title="', $title = $this->InputSafeString($gallery->details['title']), '" alt="', $title, '" /></li>';
						}
					}
					echo '</ul></li>';
				}
			}
			echo '</ul></div>';
		}
		return ob_get_clean();
	} // end of fn GalleryDisplay
	
	function DetailsColumn()
	{	ob_start();
		echo '<h4>Date:</h4>';
		if($this->booking->course->OverMultipleDays())
		{	echo '<p>', $this->OutputDate($this->booking->course->details['starttime'], 'l jS F, Y'), ' until ', $this->OutputDate($this->booking->course->details['endtime'], 'l jS F, Y'),'</p>';
		} else
		{	echo '<p>', $this->OutputDate($this->booking->course->details['starttime'], 'l jS F, Y'), '</p>';
		}
		
		if ($this->booking->course->details['ctime'])
		{	echo '<h4>Time:</h4><p>', $this->InputSafeString($this->booking->course->details['ctime']), '</p>';
		}
		if (($venue = $this->booking->course->GetVenue()) && $venue->id)
		{	echo '<h4>Venue:</h4><p>', $this->InputSafeString($venue->details['vname']), '</p><p>', $venue->GetAddress(), '</p>';
			if ($venue->details['vlat'] || $venue->details['vlng'])
			{	echo '<p><a id="showmap" href="#">View on Map</a></p>';
			}
		}
		
		if ($this->booking->course->details['ctelephone'])
		{	echo '<h4>Telephone:</h4><p>', $this->InputSafeString($this->booking->course->details['ctelephone']), '</p>';
		}
		if ($this->booking->course->details['cemail'])
		{	echo '<h4>Email:</h4><p><a href="mailto:', $this->booking->course->details['cemail'], '">', $this->booking->course->details['cemail'], '</p>';
		}
		
		//echo  '<a href="', SITE_SUB, '/vevent.php?course_id=', $this->booking->course->details['cid'], '" title="Add to Calendar" class="add-to-calendar" >Add to Calendar</a>';

		return ob_get_clean();
	} // end of fn DetailsColumn
	
	function InstructorsColumn()
	{	ob_start();
		echo '<div class="col course-instructor">';
		if ($this->booking->course->instructors)
		{	echo '<div id="accordion-container"> ';
			foreach ($this->booking->course->instructors as $key => $inst)
			{	echo '<h4 class="accordion-header">', $name = $this->InputSafeString($inst->GetFullName()), '</h4><div class="accordion-content"> ';
				if($inst->HasImage('thumbnail'))
				{	echo '<img src="', $inst->GetImageSRC('default'), '" alt="', $name, '" title="', $name, '" />';
				}
				echo '<p><a href="', $this->link->GetInstructorLink($inst), '">View Instructor Profile</a></p></div>';
			}
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	} // end of fn InstructorsColumn
	
} // end of defn BookingPage

$page = new BookingPage();
$page->Page();
?>
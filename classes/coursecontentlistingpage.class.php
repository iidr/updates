<?php
class CourseContentListingPage extends BasePage
{	protected $perpage = 10;
	protected $category;
	protected $ctype = 'course';
	protected $listed = false;

	function __construct($ctype = 'course')
	{	parent::__construct(($this->ctype = $ctype) . 's');
		$this->AddBreadcrumb(ucwords($this->ctype . 's'), $this->link->GetLink($this->ctype . 's.php'));
		$this->css[] = 'jquery.mCustomScrollbar.css';
		$this->js[] = 'jquery-ui-1.8.23.custom.min.js';
		$this->js[] = 'jquery.mousewheel.min.js';
		$this->js[] = 'testimonialslide.js';
		$this->css[] = 'elastislide.css';
		$this->js[] = 'jquery.mCustomScrollbar.min.js';
		$this->js[] = 'modernizr.custom.17475.js';
		$this->js[] = 'jquery.easing.1.3.js';
		$this->js[] = 'jquery.elastislide_modified.js';
	//	$this->js[] = 'courses.js';
		$this->css[] = 'page.css';	
		$this->css[] = 'course.css';
		$this->css[] = 'studentreviews.css';
		$this->js[] = 'productreview.js';
		if ($_GET['catid'] && ($cat = new CourseCategory($_GET['catid'])) && $cat->id)
		{	$this->category = $cat;
			$this->page_background_image = $this->category->HasImage();
			if ($this->category->details['cattype'] == 'series')
			{	//$this->perpage = 2;
			}
			$this->AddBreadcrumb($this->InputSafeString($this->category->details['ctitle']), $this->category->Link());
		}
		
		$this->perpage = $this->GetParameter('pag_' . ($this->category->details['cattype'] == 'series' ? 'series' : 'courses'));
		
	} // end of fn __construct

	function MainBodyContent()
	{	if ($this->category->id && ($this->category->details['cattype'] == 'series'))
		{	echo $this->SeriesCourseListing();
		} else
		{	echo $this->StandardCourseListing();
		}
	} // end of fn MemberBody
	
	function OutputBanner($container_id = 'homebanner', $width = 960, $height = 290)
	{	ob_start();
		if ($this->category->id && $this->category->details['banner'] && ($banner = new BannerSet($this->category->details['banner'])) && $banner->items)
		{	echo '<div class="wrapper">', $banner->OutputMultiSlider($container_id, $width, $height), '</div>';
		} else
		{	return parent::OutputBanner($container_id);
		}
		return ob_get_clean();	
	} // end of fn OutputBanner
	
	public function StandardCourseListing()
	{	ob_start();
		echo $this->OutputBanner(), '<div id="sidebar" class="col courselist_sidebar">', $this->SubmenuByDate(), $this->SubmenuByCat(), 
				//$this->SubmenuSubs(), 
				'</div><div class="col3-wrapper-with-sidebar courselist_main"><div id="courses_container">', $this->CoursesList(), '</div><div class="clear"></div></div><div class="clear"></div>', $this->FooterReviews();
		return ob_get_clean();
	} // end of fn StandardCourseListing
	
	public function SeriesCourseListing()
	{	ob_start();
		echo '<div class="col4-wrapper courselist_series">', $this->OutputBanner('seriesbanner', 880, 267), '<div id="courselist_series_inner">';
		if ($this->category->details['cattext'])
		{	echo '<div class="the-content">', stripslashes($this->category->details['cattext']), '</div>';
		}
		echo '<div id="courses_container">', $this->CoursesList(), '</div></div><div class="clear"></div></div><div class="clear"></div>', $this->FooterReviews();
		
		return ob_get_clean();
	} // end of fn SeriesCourseListing
	
	public function FooterReviews()
	{	ob_start();
		if ($this->listed)
		{	$reviews = $this->ReviewList();
			$gallery = $this->GalleryList();
			if ($reviews || $gallery)
			{	echo '<div id="courseReviewContainer"><div id="reviewListContainer">', $reviews, '</div><div id="courseGalleryContainer">', $gallery, '</div><div class="clear"></div></div>';
			}
		}
		return ob_get_clean();
	} // end of fn FooterReviews
	
	public function ReviewList()
	{	ob_start();
		if ($reviews = $this->GetReviews())
		{	echo '<div id="reviewListContainer"><h3>Testimonials</h3>', $this->ListProductReviews($reviews, 'course', 10), '</div>';
		}
		return ob_get_clean();
	} // end of fn ReviewList
	
	public function GetReviews()
	{	$reviews = array();
	
		$tables = array('productreviews', 'courses', 'coursecontent');
		$where = array('productreviews.pid=courses.ccid', 'productreviews.ptype="course"', 'productreviews.suppressed=0', 'coursecontent.ccid=courses.ccid', 'coursecontent.ctype="' . $this->ctype . '"');
		
		if ($_GET['catid'])
		{	$tables[] = 'coursetocats';
			$where[] = 'courses.ccid=coursetocats.courseid';
			$where[] = 'coursetocats.catid=' . (int)$_GET['catid'];
		} else
		{	if ($_GET['prev'])
			{	$where[] = 'courses.starttime<"' . $this->datefn->SQLDateTime() . '"';
			} else
			{	$where[] = 'courses.starttime>="' . $this->datefn->SQLDateTime() . '"';
			}
		}		

		$sql = 'SELECT productreviews.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY productreviews.revdate DESC LIMIT 10';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$reviews[$row['prid']] = $row;
			}
		}
		return $reviews;
	} // end of fn GetReviews
	
	public function GalleryList()
	{	ob_start();
		if ($gallery_row = $this->GetGallery())
		{	$gallery = new Gallery($gallery_row);
			echo '<script type="text/javascript">courseID=', (int)$this->id, '; $().ready(function(){$("body").append($(".jqmWindow")); $("#gal_modal_popup").jqm();});</script><!-- START gallery modal popup --><div id="gal_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="galModalInner"></div></div><h3>', ucwords($this->ctype), ' photos</h3>';
			$title = $this->InputSafeString($gallery->details['title']);
			//echo '<div class="courseGalCover"><img src="', $gallery->HasCoverImage('medium'), '" alt="', $title, '" title="', $title, '" /><a onclick="OpenGallery(', $gallery->id, ');">Photo Gallery Slideshow</a></div>';
			$count = 0;
			echo '<div class="courseGalSelection" onclick="OpenGallery(', $gallery->id, ');">';
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
		return ob_get_clean();
	} // end of fn GalleryList
	
	public function GetGallery()
	{	if ($galleries = $this->page->GetGalleries())
		{	foreach ($galleries as $gallery)
			{	return $gallery;
			}
		}
		$tables = array('galleries', 'gallerytocourse', 'courses', 'coursecontent');
		$where = array('galleries.gid=gallerytocourse.gid', 'gallerytocourse.cid=courses.cid', 'courses.live=1', 'coursecontent.ccid=courses.ccid', 'coursecontent.ctype="' . $this->ctype . '"');
		
		if ($_GET['catid'])
		{	$tables[] = 'coursetocats';
			$where[] = 'courses.ccid=coursetocats.courseid';
			$where[] = 'coursetocats.catid=' . (int)$_GET['catid'];
		} else
		{	if ($_GET['prev'])
			{	$where[] = 'courses.starttime<"' . $this->datefn->SQLDateTime() . '"';
			} else
			{	$where[] = 'courses.starttime>="' . $this->datefn->SQLDateTime() . '"';
			}
		}
		
		$sql = 'SELECT galleries.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY RAND() LIMIT 1';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row;
			}
		}
		return false;
	} // end of fn GetReviews
	
	public function CoursesList()
	{	ob_start();
		if ($courses = $this->GetCoursesToList())
		{	$this->listed = true;
			//$this->VarDump($courses);
			if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->perpage;
			
			echo '<ul>';
			foreach ($courses as $course_row)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
				
					$course = new Course($course_row);
					$title = $this->InputSafeString($course->content['ctitle']);
					$link = $this->link->GetCourseLink($course);
					if ($this->category->id && ($this->category->details['cattype'] == 'series'))
					{	if (++$licount > 2)
						{	echo '</ul><div class="clear"></div><ul>';
							$licount = 1;
						}
						echo '<li>',
							//'<h2><a href="', $link, '">', $title, '</a></h2>',
							'<div class="courselist_image">';
						if (($src = $course->HasImage('thumbnail')) || ($src = $course->GetDefaultImage('thumbnail-small')))
						{	echo '<a href="', $link, '"><img src="', $src, '" alt="', $title, '" title="', $title, '" /></a>';
						}
						echo '</div><div class="courselist_details"><div class="courselist_details_inner">',
							'<h2><a href="', $link, '">', $title, '</a></h2>','&nbsp;<span class="prodItemCode">Code: ', $course->ProductID(), '</span>',
							'<p class="series_slogan">', $this->InputSafeString($course->content['cslogan']), '</p><div class="clear"></div></div><p>', $course->GetDateVenue(', '), '</p><a class="series_button" href="', $link, '">View ', $this->ctype, '</a></div></li>';
					} else
					{	echo '<li><div class="courselist_image">';
						if (($src = $course->HasImage('thumbnail')) || ($src = $course->GetDefaultImage('thumbnail')))
						{	echo '<a href="', $link, '"><img src="', $src, '" alt="', $title, '" title="', $title, '" /></a>';
						}
						echo '</div><div class="courselist_details"><h2><a href="', $link, '">', $title, '</a></h2>','&nbsp;<span class="prodItemCode">Code: ', $course->ProductID(), '</span><p>', $this->InputSafeString($course->content['cslogan']), '</p></div><div class="courselist_date', ($slogan = $course->SpecialSlogan()) ? ' courselist_date_with_slogan' : '', '">', $course->GetDateVenue(', '), '</div>';
						if ($slogan)
						{	echo '<div class="courselist_slogan cl_slogan_', $this->InputSafeString($slogan['style']), '"><a href="', $link, '">', $this->InputSafeString($slogan['slogan']), '</a></div>';
						}
						echo '<div class="clear"></div></li>';
					}
				}
			}
			echo '</ul><div class="clear"></div>';

			if (count($courses) > $this->perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($courses), $this->perpage, 'courses_container', 'ajax_' . $this->ctype . 's.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
			
		}
		return ob_get_clean();
	} // end of fn CoursesList
	
	public function EventsList()
	{	ob_start();
		if ($courses = $this->GetCoursesToList())
		{	$this->listed = true;
			//$this->VarDump($courses);
			if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->perpage;
			
			echo '<ul>';
			foreach ($courses as $course_row)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
				
					$course = new Course($course_row);
					$title = $this->InputSafeString($course->content['ctitle']);
					$link = $this->link->GetCourseLink($course);
					if ($this->category->id && ($this->category->details['cattype'] == 'series'))
					{	if (++$licount > 2)
						{	echo '</ul><div class="clear"></div><ul>';
							$licount = 1;
						}
						echo '<li>',
							//'<h2><a href="', $link, '">', $title, '</a></h2>',
							'<div class="courselist_image">';
						if (($src = $course->HasImage('thumbnail')) || ($src = $course->GetDefaultImage('thumbnail-small')))
						{	echo '<a href="', $link, '"><img src="', $src, '" alt="', $title, '" title="', $title, '" /></a>';
						}
						echo '</div><div class="courselist_details"><div class="courselist_details_inner">',
							'<h2><a href="', $link, '">', $title, '</a></h2>',
							'<p class="series_slogan">', $this->InputSafeString($course->content['cslogan']), '</p><div class="clear"></div></div><p>', $course->GetDateVenue(', '), '</p><a class="series_button" href="', $link, '">View ', $this->ctype, '</a></div></li>';
					} else
					{	echo '<li><div class="courselist_image">';
						if (($src = $course->HasImage('thumbnail')) || ($src = $course->GetDefaultImage('thumbnail')))
						{	echo '<a href="', $link, '"><img src="', $src, '" alt="', $title, '" title="', $title, '" /></a>';
						}
						echo '</div><div class="courselist_details"><h2><a href="', $link, '">', $title, '</a></h2><p>', $this->InputSafeString($course->content['cslogan']), '</p></div><div class="courselist_date', ($slogan = $course->SpecialSlogan()) ? ' courselist_date_with_slogan' : '', '">', $course->GetDateVenue(', '), '</div>';
						if ($slogan)
						{	echo '<div class="courselist_slogan cl_slogan_', $this->InputSafeString($slogan['style']), '"><a href="', $link, '">', $this->InputSafeString($slogan['slogan']), '</a></div>';
						}
						echo '<div class="clear"></div></li>';
					}
				}
			}
			echo '</ul><div class="clear"></div>';

			if (count($courses) > $this->perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($courses), $this->perpage, 'courses_container', 'ajax_' . $this->ctype . 's.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
			
		}
		return ob_get_clean();
	} // end of fn CoursesList
	
	public function SubmenuByDate()
	{	ob_start();
		echo '<div class="sidebar-menu"><h2>Filter by date</h2><ul>';
		$livecount = $this->GetLivePrevCount();
		if ($livecount['upcoming'])
		{	echo '<li', !$_GET ? ' class="current-subpage"' : '', '><a href="', SITE_SUB, '/courses/"><span class="courseSideMenuLabel">Upcoming Courses</span><span class="courseSideMenuCount">', $livecount['upcoming'], '</span><div class="clear"></div></a></li>';
		}
		if ($livecount['previous'])
		{	echo '<li', $_GET['prev'] ? ' class="current-subpage"' : '', '><a href="', SITE_SUB, '/previous-courses/"><span class="courseSideMenuLabel">Previous Courses</span><span class="courseSideMenuCount">', $livecount['previous'], '</span><div class="clear"></div></a></li>';
		}
		echo '</ul></div>';
		return ob_get_clean();
	} // end of fn SubmenuByDate
	
	public function SubmenuSubs()
	{	ob_start();
		if ($subs = $this->GetSubscriptions())
		{	echo '<div class="sidebar-menu"><h2>Subscriptions</h2><ul>';
			foreach ($subs as $sub_row)
			{	$sub = new SubscriptionProduct($sub_row);
				echo '<li><a href="', $sub->GetLink(), '">', $this->InputSafeString($sub->details['title']), '</a></li>';
			}
			echo '</ul></div>';
		}
		return ob_get_clean();
	} // end of fn SubmenuSubs
	
	public function SubmenuByCat()
	{	ob_start();
		if ($cat_types = $this->GetAllCategoriesWithCourses())
		{	foreach ($cat_types as $cattype=>$categories)
			{	echo '<div class="sidebar-menu"><h2>Filter by ', $cattype, '</h2><ul>';
				foreach ($categories as $cat_id=>$cat_row)
				{	$cat = new CourseCategory($cat_row);
					echo '<li', $cat->id == $this->category->id ? ' class="current-subpage"' : '', '><a href="', $cat->Link($this->ctype), '">', $this->InputSafeString($cat->details['ctitle']), '</a></li>';
				}
				echo '</ul></div>';
			}
		}
		return ob_get_clean();
	} // end of fn SubmenuByCat
	
	public function GetCoursesToList()
	{	$courses = array();
		
		$tables = array('courses', 'coursecontent');
		$where = array('courses.live=1', 'coursecontent.ccid=courses.ccid', 'coursecontent.ctype="' . $this->ctype . '"');
		$order = 'courses.starttime ASC';
		
		if ($_GET['catid'])
		{	$tables[] = 'coursetocats';
			$where[] = 'coursetocats.courseid=coursecontent.ccid';
			$where[] = 'coursetocats.catid=' . (int)$_GET['catid'];
		//	if ($this->category->details['cattype'] == 'series')
		//	{	
				$order = 'courses.starttime DESC';
		//	}
		} else
		{	if ($_GET['prev'])
			{	$where[] = 'courses.endtime<="' . $this->datefn->SQLDate() . '"';
				$order = 'courses.starttime DESC';
			} else
			{	$where[] = 'courses.endtime>="' . $this->datefn->SQLDate() . '"';
			}
		}
		
		$where[] = 'courses.cvenue>0';
		
		$sql = 'SELECT DISTINCT courses.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$courses[$row['cid']] = $row;
			}
		}
		
		if (!$courses && !$_GET)
		{	$_GET['prev'] = 1;
			return $this->GetCoursesToList();
		}
		
		return $courses;
	} // end of fn GetCoursesToList
	
	public function GetLivePrevCount()
	{	$counts = array();
		$sql = 'SELECT IF(courses.endtime>="' . $this->datefn->SQLDate() . '", "upcoming", "previous") AS date_state, COUNT(courses.cid) AS courses_count FROM courses, coursecontent WHERE courses.ccid=coursecontent.ccid AND courses.live=1 AND courses.cvenue>0 AND coursecontent.ctype="' . $this->ctype . '" GROUP BY date_state';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$counts[$row['date_state']] = (int)$row['courses_count'];
			}
		}
		return $counts;
	} // end of fn GetLivePrevCount
	
	function GetAllCategoriesWithCourses()
	{
		$cat_types = array();
		
		$sql = 'SELECT coursecategories.* FROM coursecategories, coursetocats, coursecontent, courses WHERE coursetocats.catid=coursecategories.cid AND coursetocats.courseid=coursecontent.ccid AND coursecontent.ccid=courses.ccid AND courses.live=1 AND coursecontent.ctype="' . $this->ctype . '" GROUP BY coursecategories.cid ORDER BY coursecategories.ctitle ASC';
		
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	while ($row = $this->db->FetchArray($result))
				{	$cat_types[$row['cattype']][$row['cid']] = $row;
				}
			}
		}
		
		return $cat_types;	
	} // end of fn GetAllCategoriesWithCourses
	
/*	function TestimonialsColumn()
	{
		ob_start();
		$course = new Course;
		echo "<h3>Course Testimonials</h3>";
		
		$testimonials = $course->GetAllTestimonials();
		
		if(sizeof($testimonials))
		{
			$elements = count($testimonials);
			$listwidth = $elements * 235;
			echo '<div class="testimonial-container">';
			echo "<ul id='carousel' class='testimonial-list' style='width:".$listwidth."px;'>";
			
			foreach($testimonials as $t)
			{
				$student = $t->GetAuthor();
				echo "<li><div class='testimonial-inner'><h4>".$this->InputSafeString($t->details["subtitle"])."</h4>". nl2br($this->InputSafeString($t->details["testimonial"])) ."<p class='author'>". $this->InputSafeString($student->GetName()) .", ".$student->details['city'].", ".$this->GetCountry($student->details['country'])."</div></li>";
			}
			
			echo "</ul>";
			echo '<div style="clear"></div>';
			echo '<a href="#" class="less-move"><span>&lt;</span> Less</a> <a href="#" class="more-move">More <span>&gt;</span></a>';
			echo '</div>';
			
		}
		?>
        <script>
			$('.testimonial-list').boxSlide();
			$('.testimonial-list li').mCustomScrollbar({
				scrollButtons:{
					enable:true
				}	
			});
		</script>
		<?php
		return ob_get_clean();
	} // end of fn TestimonialsColumn*/
	
	public function GetSubscriptions()
	{	$subs = array();
		$sql = 'SELECT * FROM subproducts WHERE live=1 ORDER BY listorder, id';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$subs[$row['id']] = $row;
			}
		}
		return $subs;
	} // end of fn GetSubscriptions
	
} // end of defn CourseContentListingPage
?>
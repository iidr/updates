<?php 
require_once('init.php');

class NewsListingPage extends PostListingPage
{	
	function __construct()
	{	parent::__construct('news');
		
		$this->AddBreadcrumb('News', $this->link->GetLink('news.php'));
		if ($year = (int)$_GET['year'])
		{	$this->AddBreadcrumb('Archive ' . $year, SITE_URL . 'news-archive/' . $year . '/');
		} else
		{	if ($_GET['cat'] && ($cat = new PostCategory($_GET['cat'])) && $cat->id)
			{	$this->AddBreadcrumb($this->InputSafeString($cat->details['ctitle']), $cat->Link('news'));
			}
		}
	} // end of fn __construct
	
	public function PostsSideBar()
	{	ob_start();
		echo '<div id="sidebar" class="col">', 
			$this->GetCategorySubmenu(), 
			$this->GetArchiveSubmenu(), 
			$this->GetSidebarCourses(), 
			$this->GetSidebarQuote(), '</div>';
		return ob_get_clean();
	} // end of fn PostsSideBar
	
} // end of defn NewsListingPage

$page = new NewsListingPage();
$page->Page();
?>
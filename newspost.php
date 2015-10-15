<?php 
require_once('init.php');

class NewsPostPage extends PostListingPage
{	private $post;
	
	function __construct()
	{	parent::__construct('news');
		$this->post = new NewsPost($_GET['id']);
		
		$this->AddBreadcrumb('News', $this->link->GetLink('xnxexwxs.php'));
		if ($this->post->details['catid'] && ($cat = new PostCategory($this->post->details['catid'])) && $cat->id)
		{	$this->AddBreadcrumb($this->InputSafeString($cat->details['ctitle']), $cat->Link());
		}
		$this->AddBreadcrumb($this->InputSafeString($this->post->details['ptitle']));
		
		$this->title .= ' - ' . $this->InputSafeString($this->post->details['ptitle']);
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
	
	public function PostListing()
	{	ob_start();
		echo '<h1><span', $this->post->details['socialbar'] ? ' class="headertextWithSM"' : '', '>', $this->InputSafeString($this->post->details['ptitle']), '</span>', $this->post->details['socialbar'] ? $this->GetSocialLinks(3) : '', '</h1><div class="the-content"><p>', $this->post->GetAuthorDate(), '</p>';
		if ($img = $this->post->HasImage('default'))
		{	echo '<img src="', $img, '" alt="', $this->InputSafeString($this->post->details['ptitle']), '" class="post-image" />';
		}
		echo $this->post->details['pcontent'], '</div><div class="clear"></div>';
		
		if($this->post->AllowComments())
		{
			$clisting = new PostCommentListing($this->post);
			echo $clisting->Output();
		}
		return ob_get_clean();
	} // end of fn PostListing	
} // end of defn NewsPostPage

$page = new NewsPostPage();
$page->Page();
?>
<?php
include_once('sitedef.php');

class PostCommentsPage extends AdminPostComments
{	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function PostLoggedInConstruct()
	{	parent::PostLoggedInConstruct('comments');
		//$this->js[] = 'admin_postreviews.js';
		$this->css[] = 'adminreviews.css';
		$this->breadcrumbs->AddCrumb('postcomments.php?id=' . $this->post->id, 'Comments');
	} // end of fn ProductsLoggedInConstruct
	
	protected function PostBodyMain()
	{	//parent::PostBodyMain();
		echo $this->CommentsDisplay();
	} // end of fn ProductsBody
	
} // end of defn ProductEditPage

$page = new PostCommentsPage();
$page->Page();
?>
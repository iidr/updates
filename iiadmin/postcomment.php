<?php
include_once('sitedef.php');

class PostCommentPage extends AdminPost
{	var $comment;
	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function PostsLoggedInConstruct()
	{	parent::PostsLoggedInConstruct('comments');
		$this->css[] = 'adminreviews.css';
		$this->css[] = 'datepicker.css';
		$this->js[] = 'datepicker.js';
		
		if (isset($_POST['comment']))
		{	$saved = $this->comment->AdminSave($_POST, $this->post->id, 'store');
			$this->successmessage = $saved['successmessage'];
			$this->failmessage = $saved['failmessage'];
		}
		
		if ($this->comment->CanDelete() && $_GET['delete'] && $_GET['confirm'])
		{	if ($this->comment->Delete())
			{	header('location: postcomments.php?id=' . $this->post->id);
				exit;
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}
		
		$this->breadcrumbs->AddCrumb('postcomments.php?id=' . $this->post->id, 'Comments');
		if ($this->comment->id)
		{	$this->breadcrumbs->AddCrumb('postcomment.php?id=' . $this->comment->id, 'by ' . $this->InputSafeString($this->comment->details['reviewertext']));
		} else
		{	$this->breadcrumbs->AddCrumb('postcomment.php?prid=' . $this->post->id, 'Adding comment');
		}
	} // end of fn PostsLoggedInConstruct
	
	function PostsBody()
	{	parent::PostsBody();
		echo $this->comment->AdminInputForm($this->post->id);
	} // end of fn PostsBody
	
} // end of defn PostCommentPage

$page = new PostCommentPage();
$page->Page();
?>
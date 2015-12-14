<?php
class AdminPostsPage extends CMSPage
{	protected $post;
	protected $post_option = '';

	public function __construct()
	{	parent::__construct('CMS');
	} //  end of fn __construct

	public function CMSLoggedInConstruct()
	{	parent::CMSLoggedInConstruct();
		$this->PostLoggedInConstruct();
	} // end of fn CMSLoggedInConstruct

	protected function PostLoggedInConstruct($post_option = '')
	{	$this->post_option = $post_option;
		$this->css[] = 'adminpages.css';
		$this->css[] = 'admincoursepage.css';
		
		$this->AssignPost();
		$this->ConstructFunctions();

		$this->breadcrumbs->AddCrumb('posts.php', 'Posts');
		if ($this->post->id)
		{	$this->breadcrumbs->AddCrumb('postedit.php?id=' . $this->post->id, $this->InputSafeString($this->post->details['ptitle']));
		}
	} // end of fn PostLoggedInConstruct
	
	protected function ConstructFunctions(){}
	
	protected function AssignPost()
	{	$this->post = new AdminPost($_GET['id']);
	} // end of fn AssignPost
	
	public function CMSBodyMain()
	{	$this->PostBodyMain();
	} // end of fn CMSBodyMain
	
	protected function PostBodyMain()
	{	$this->PostBodyMenu();
	} // end of fn PostBodyMain
	
	private function PostBodyMenu()
	{	if ($this->post->id)
		{	echo '<div class="course_edit_menu"><ul>';
			foreach ($this->BodyMenuOptions() as $key=>$option)
			{	echo '<li', $this->post_option == $key ? ' class="selected"' : '', '><a href="', $option['link'], '">', $option['text'], '</a></li>';
			}
			echo '</ul><div class="clear"></div></div><div class="clear"></div>';
		}
	} // end of fn ProductsBodyMenu
	
	protected function BodyMenuOptions()
	{	$options = array();
		if ($this->post->id)
		{	$options['edit'] = array('link'=>'postedit.php?id=' . $this->post->id, 'text'=>'Edit Post');
			$options['people'] = array('link'=>'postpeople.php?id=' . $this->post->id, 'text'=>'People');
			$options['comments'] = array('link'=>'postcomments.php?id=' . $this->post->id, 'text'=>'Comments');
		}
		return $options;
	} // end of fn BodyMenuOptions
	
} // end of defn AdminPostsPage
?>
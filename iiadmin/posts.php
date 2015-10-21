<?php
include_once('sitedef.php');

class PostsPage extends AdminPostsPage
{
	public function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	protected function AssignPost(){}

	protected function PostBodyMain()
	{	parent::PostBodyMain();
		echo $this->FilterForm(), $this->PostsList();
	} // end of fn PostBodyMain
	
	private function FilterForm()
	{	ob_start();
		echo '<form class="akFilterForm" method="get" action="', $_SERVER['SCRIPT_NAME'], '"><span>Post Type</span><select name="ptype">';
		foreach ($this->PostTypeList() as $key=>$type_name)
		{	echo '<option value="', $key, '"', $key == $_GET['ptype'] ? ' selected="selected"' : '', '>', $type_name, '</option>';
		}
		echo '</select><input type="submit" class="submit" value="Apply Filter" /><div class="clear"></div></form><div class="clear"></div>';
		return ob_get_clean();
	} // end of fn FilterForm
	
	private function PostTypeList()
	{	$post_types = array(''=>'-- all --');
		$dummy_post = new Post();
		foreach ($dummy_post->types as $type_name=>$type)
		{	$post_types[$type_name] = $type_name;
		}
		return $post_types;
	} // end of fn PostTypeList
	
	private function GetPosts()
	{	$posts = array();
		$tables = array('posts');
		$where = array();
		
		if ($ptype = $_GET['ptype'])
		{	$where[] = 'posts.ptype="' . $ptype . '"';
		}
		
		$sql = 'SELECT posts.* FROM ' . implode(', ', $tables);
		if ($wstr = implode(' AND ', $where))
		{	$sql .= ' WHERE ' . $wstr;
		}
		$sql .= ' GROUP BY posts.pid ORDER BY posts.pdate DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$posts[$row['pid']] = $row;
			}
		}
		return $posts;
	} // end of fn GetPosts
	
	private function PostsList()
	{	ob_start();
		$perpage = 20;
		if ($_GET['page'] > 1)
		{	$start = ($_GET['page'] - 1) * $perpage;
		} else
		{	$start = 0;
		}
		$end = $start + $perpage;
		echo '<table id="pagelist"><tr class="newlink"><th colspan="9"><a href="postedit.php">new post</a></th></tr><tr><th></th><th>Type</th><th>Title</th><th>Live Link</th><th>Category</th><th>People</th><th>Live?</th><th>Posted</th><th>Actions</th></tr>';
		foreach ($posts = $this->GetPosts() as $post_row)
		{	if (++$count > $start)
			{	if ($count > $end)
				{	break;
				}
				echo $this->PostListLine($post_row);
			}
		}
		echo '</table>';
		if (count($postlist) > $perpage)
		{	$pagelink = $_SERVER['SCRIPT_NAME'];
			if ($_GET)
			{	$get = array();
				foreach ($_GET as $key=>$value)
				{	if ($value && ($key != 'page'))
					{	$get[] = $key . '=' . $value;
					}
				}
				if ($get)
				{	$pagelink .= '?' . implode('&', $get);
				}
			}
			$pag = new Pagination($_GET['page'], count($postlist), $perpage, $pagelink);
			echo '<div class="pagination">', $pag->Display(), '</div>';
		}
		return ob_get_clean();
	} // end of fn PostsList
	
	private function PostListLine($post_row = array())
	{	ob_start();
		static $cats = array();
		$post = new AdminPost($post_row);
		if ($post->details['catid'] && !$cats[$post->details['catid']])
		{	if (($cat = new PostCategory($post->details['catid'])) && $cat->id)
			{	$cats[$post->details['catid']] = '<a href="postcatedit.php?id=' . $cat->id . '">' . $this->InputSafeString($cat->details['ctitle']) . '</a>';
			}
		}
		echo '<tr><td>';
		if ($img_src = $post->HasImage('thumbnail'))
		{	echo '<img src="', $img_src, '?', time(), '" />';
		}
		echo '</td><td>', $post->details['ptype'], '</td><td class="pagetitle">', $this->InputSafeString($post->details['ptitle']), '</td><td><a href="', $link = $this->link->GetPostLink($post), '" target="_blank">', $link, '</a></td><td>', $cats[$post->details['catid']], '</td><td>', $post->GetAuthorDate(), '</td><td>', $post->details['live'] ? 'Yes' : 'No', '</td><td>', date('d-M-y @H:i', strtotime($post->details['pdate'])), '</td><td><a href="postedit.php?id=', $post->id, '">edit</a>';
		if ($post->CanDelete())
		{	echo '&nbsp;|&nbsp;<a href="postedit.php?id=', $post->id, '&delete=1">delete</a>';
		}
		if ($histlink = $this->DisplayHistoryLink('posts', $post->id))
		{	echo '&nbsp;|&nbsp;', $histlink;
		}
		echo '</td></tr>';
		return ob_get_clean();
	} // end of fn PostListLine
	
} // end of defn PostsPage

$page = new PostsPage();
$page->Page();
?>
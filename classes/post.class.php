<?php
class Post extends BlankItem implements Searchable
{	public $type = 'post';
	public $object_type = 'Post';
	public $types = array('post'=>array('object_type'=>'Post'), 'news'=>array('object_type'=>'NewsPost', 'image_sizes'=>array('default'=>array(580, 330))), 'opinions'=>array('object_type'=>'OpinionPost'));
	public $imagelocation = '';
	public $imagedir = '';
	public $imagesizes = array('default'=>array(600, 350), 'thumbnail'=>array(235, 137), 'smallthumbnail'=>array(75, 75));
	
	public function __construct($id = null, $type = null)
	{	parent::__construct($id, 'posts', 'pid');
		$this->SetType($type);
		$this->imagelocation = SITE_URL . 'img/posts/';
		$this->imagedir = CITDOC_ROOT . '/img/posts/';
	} // fn __construct
	
	public function SetType($type = '')
	{	if ($this->types[$type])
		{	$this->type = $type;
			$this->object_type = $this->types[$type]['object_type'];
			if ($this->types[$type]['image_sizes'])
			{	foreach ($this->types[$type]['image_sizes'] as $size_name=>$sizes)
				{	$this->imagesizes[$size_name] = $sizes;
				}
			}
		}
	} // end of fn SetType
	
	public function GetPeople($live_only = true)
	{	$people = array();
		$where = array('instructors.inid=postinstructors.inid', 'postinstructors.pid=' . $this->id);
		if ($live_only)
		{	$where[] = 'instructors.live=1';
		}
		$sql = 'SELECT instructors.* FROM instructors, postinstructors WHERE ' . implode(' AND ', $where) . ' ORDER BY instructors.showfront DESC, instructors.instname ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$people[$row['inid']] = $row;
			}
		}
		return $people;
	} // end of fn GetPeople
	
	public function GetExtra()
	{	$this->SetType($this->details['ptype']);
	} // end of fn GetExtra
	
	public function GetComments($posttype = '',$include_hidden = false)
	{
		$sql = 'SELECT * FROM comments WHERE pid = '. (int)$this->id;
		$sql .=($posttype!='')?"ptype='".mysql_real_escape_string($posttype)."'":"";
		
		if (!$include_hidden)
		{	$sql .= ' AND live=1';
		}
		$sql .= ' ORDER BY dateadded DESC';
		
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	$comments = array();
				
				while ($row = $this->db->FetchArray($result))
				{	$comments[] = new PostComment($row);
				}
				
				return $comments;
			}
		}
	} // fn GetComments
	
	public function GetCommentCount($posttype = '')
	{	$sql = 'SELECT COUNT(cid) as total FROM comments WHERE pid=' . (int)$this->id;
		$sql .=($posttype!='')?" AND ptype='".mysql_real_escape_string($posttype)."'":"";
	
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	if ($row = $this->db->FetchArray($result))
				{	return (int)$row['total'];
				}
			}
		}
		return 0;
	} // fn GetCommentCount
	
	public function GetMostPopular($posttype = '', $limit = 0)
	{
		$sql ='SELECT pid FROM comments';
		$sql .=($posttype!='')?" WHERE ptype='".mysql_real_escape_string($posttype)."'":"";
		$sql .=' GROUP BY pid ORDER BY count(*) DESC';

		$posts = array();
		$popular = array();
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	while ($row = $this->db->FetchArray($result))
				{	$posts[] = $row['pid'];
				}
			}
		}
		
		$i = 0;
		foreach ($posts as $p)
		{	if ($result = $this->db->Query('SELECT * FROM posts WHERE pid=' . (int)$p))
			{	
				if ($row = $this->db->FetchArray($result))
				{	
					if($posttype)
					{	if ($row['ptype'] == $posttype)
						{	$obj = $this->object_type;
							$popular[] = new $obj($row);
						}
					} else
					{
						$obj = $this->object_type;
						$popular[] = new $obj($row);
					}
				}
			}
			if ($limit > 0 && ($i++ >= $limit))
			{	break;	
			}
		}
		return $popular;
		
	} // fn GetMostPopular
	
	public function NewsPopular()
	{	ob_start();
		$posts = $this->GetMostPopular('news', 5);
		echo '<div class="mostpopularlisting"><h3>Most Popular</h3>';
		if ($posts)
		{	foreach ($posts as $p)
			{	
				echo '<div class="mostpopularitem clearfix"><div class="mostopularimage"><a href="', $this->link->GetPostLink($p), '"><img src="', ($img = $p->HasImage('smallthumbnail')) ? $img : (SITE_URL . 'img/posts/default.png'), '" /></a></div><div class="mostpopularcontent"><p class="mostpopulartitle"><a href="', $this->link->GetPostLink($p), '">', $p->details['ptitle'], '</a></p><p class="mostpopularauthor">', $p->GetAuthorDate(), '</p></div><br /></div>';

			}
		} else
		{	echo 'There are no news available at the moment';
		}
		
		echo '</div>';	
		return ob_get_clean();
	} // fn NewsPopular
	
	public function MostPopular($posttype = '', $numberofpost = 5)
	{	ob_start();
		if($posttype)
		{	$type = $posttype;
		} else
		{	$type = $this->type;
		}
		echo '<div class="mostpopularlisting"><h3>Most Popular</h3>';
		if ($posts = $this->GetMostPopular($type, $numberofpost))
		{	foreach ($posts as $p)
			{	
				echo '<div class="mostpopularitem clearfix"><div class="mostopularimage"><a href="', $this->link->GetPostLink($p), '"><img src="', ($img = $p->HasImage('smallthumbnail')) ? $img : (SITE_URL . 'img/posts/default.png'), '" /></a></div><div class="mostpopularcontent"><p class="mostpopulartitle"><a href="', $this->link->GetPostLink($p), '">'.$p->details['ptitle'], '</a></p><p class="mostpopularauthor">', $p->GetAuthorDate(), '</p></div><br /></div>';
			}
		} else
		{	echo 'There are no "', $this->type, '" available at the moment';
		}
			
		echo '</div>';
		return ob_get_clean();
	} // fn MostPopular
	
	public function GetAuthorDate()
	{	$by = array();
		if ($this->details['authortext'])
		{	$by[] = $this->InputSafeString($this->details['authortext']);
		}
		if ($people = $this->GetPeople())
		{	foreach ($people as $inst_row)
			{	$inst = new Instructor($inst_row);
				$by[] = '<a href="' . $inst->Link() . '">' . $this->InputSafeString($inst_row['instname']) . '</a>';
			}
		}
		if (!$by)
		{	$by[] = 'IIDR';
		}
		return 'By ' . implode(', ', $by) . ' | ' . date('l jS F Y', strtotime($this->details['pdate']));
	} // fn GetAuthorDate
	
	public function GetNewest()
	{	if ($posts = $this->GetAll(1))
		{	return $posts[0];
		}
	} // fn GetNewest
	
	public function GetAll($limit = 0, $order = 'pdate', $sort = 'DESC')
	{
		$posts = array();
		$sql = 'SELECT * FROM posts WHERE ptype = "' . $this->SQLSafe($this->type) . '" AND live = 1';
		
		if ($order && $sort)
		{	$sql .= ' ORDER BY '. $this->SQLSafe($order) . ' ' . $this->SQLSafe($sort);
		} 
		
		if ($limit)
		{	$sql .= ' LIMIT '. (int)$limit;
		}
		
		if ($result = $this->db->Query($sql))
		{	if ($this->db->NumRows($result))
			{	while ($row = $this->db->FetchArray($result))
				{	$obj = $this->object_type;
					$posts[] = new $obj($row);
				//	$posts[] = new Post($row);
				}
			}
		}
		
		return $posts;
	} // fn GetAll
	
	public function GetArchives($start_date = null, $months = 6)
	{
		$archives = array();
		
		$now = strtotime('01 ' . date('F') . ' ' . date('Y'));	
		
		if(is_null($start_date))
		{	$start_date = $now;	
		} else
		{	$start_date = strtotime('01 '. date('F Y', strtotime($start_date)));	
		}
		
		$future = ($months / 2) + 1;
		$end_date = strtotime('+' . (int)$future . ' months', $start_date);
		
		if($end_date > $now)
		{	$end_date = $now;
		}
		
		$sql = 'SELECT Year(pdate) AS year, Month(pdate) as month, p.* FROM `posts` p WHERE pdate <= "' . date('Y-m-d 23:59:59', strtotime("+1 month - 1 day", $end_date)) . '" AND ptype = "' . $this->SQLSafe($this->type) . '" AND live = 1 GROUP BY Year(pdate), Month(pdate) ORDER BY pdate DESC LIMIT ' . ($months + 1);

		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$row['monthname'] = date('F', mktime(0, 0, 0, $row['month'], 10)); 
				$archives[] = $row;	
			}
		}
		
		return $archives;
	} // fn GetArchives
	
	public function GetArchivePosts($year = 0, $month = 0)
	{
		$start_date = mktime(0, 0, 0, $month, 1, $year);
		$end_date = mktime(0, 0, 0, $month+1, 1, $year)-1;
		
		$sql = 'SELECT * FROM posts WHERE pdate >= "' . date('Y-m-d H:i:s', $start_date) . '" AND pdate <= "' . date('Y-m-d H:i:s', $end_date) . '" AND ptype = "' . $this->SQLSafe($this->type) . '" AND live = 1 ORDER BY pdate DESC';
				
		$posts = array();
				
		if ($result = $this->db->Query($sql))
		{	
			if($this->db->NumRows($result))
			{
				while($row = $this->db->FetchArray($result))
				{	
					$obj = $this->object_type;
					$posts[] = new $obj($row);
				}
			}
		}
		
		return $posts;
	} // fn GetArchivePosts
	
	public function CanView()
	{	return (int)$this->details['live'];
	} // fn CanView
	
	public function AllowComments()
	{	return (int)$this->details['pallowcomments'];
	} // fn AllowComments
	
	public function HasImage($size = '')
	{	return file_exists($this->GetImageFile($size)) ? $this->GetImageSRC($size) : false;
	} // fn HasImage
	
	public function GetImageFile($size = 'default')
	{	return $this->imagedir . $this->InputSafeString($size) . '/' . (int)$this->id . '.png';
	} // fn GetImageFile
	
	public function GetImageSRC($size = 'default')
	{	return $this->imagelocation . $this->InputSafeString($size) . '/' . (int)$this->id . '.png';
	} // fn GetImageSRC

	public function DefaultImageSRC($size = 'default')
	{	return parent::DefaultImageSRC($this->imagesizes[$size]);
	} // end of fn DefaultImageSRC
	
	public function SampleText($length = 200)
	{	$text = strip_tags(stripslashes($this->details['pcontent']));
		if (strlen($text) > $length)
		{	return $this->InputSafeString(substr($text, 0, (int)$length)) . ' ...';
		} else
		{	return $this->InputSafeString($text);
		}
	} // end of fn SampleText
	
	/** Search Functions ****************/
	public function Search($term)
	{
		$match = ' MATCH(ptitle, pcontent) AGAINST("'. $this->SQLSafe($term) .'") ';
		$sql = 'SELECT *, ' . $match . ' as matchscore FROM posts WHERE ' . $match . ' AND ptype = "'. $this->SQLSafe($this->type) .'" AND live = 1 ORDER BY matchscore DESC';
		
		$results = array();
		
		if($result = $this->db->Query($sql))
		{
			while($row = $this->db->FetchArray($result))
			{
				$obj = $this->object_type;
				$results[] = new $obj($row);	
			}
		}
		
		return $results;
	} // fn Search
	
	public function SearchResultOutput()
	{
		echo '<h4><span>', ucwords($this->details['ptype']), '</span><a href="', $link = $this->link->GetPostLink($this), '">', $this->InputSafeString($this->details['ptitle']), '</a></h4><p><a href="', $link, '">read more ...</a></p>';
	} // fn SearchResultOutput
	
} // end of class Post
?>
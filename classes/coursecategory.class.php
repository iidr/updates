<?php
class CourseCategory extends BlankItem
{	var $subcats = array();
	var $types = array('subject'=>'subject', 'series'=>'series');
	var $imagelocation = '';
	var $imagedir = '';

	function __construct($id = 0)
	{	parent::__construct($id, 'coursecategories', 'cid');
		$this->imagelocation = SITE_URL . 'img/courses/';
		$this->imagedir = CITDOC_ROOT . '/img/courses/';
		$this->Get($id); 
	} // fn __construct
	
	public function ResetExtra()
	{	$this->subcats = array();
	} // end of fn ResetExtra
	
	public function GetExtra()
	{	$this->subcats = array();
		if ($this->id)
		{	$sql = 'SELECT * FROM coursecategories WHERE parentcat=' . (int)$this->id;
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$this->subcats[$row['cid']] = $row;
				}
			}
		}
	} // end of fn GetExtra
	
	public function GetCourses($liveonly = true)
	{	$courses = array();
		
		if ($id = (int)$this->id)
		{	$where = array('coursetocats.catid=' . $id, 'coursetocats.courseid=courses.cid');
		
			if ($liveonly)
			{	$where[] = 'courses.live=1';
			}
			
			$sql = 'SELECT courses.* FROM coursetocats, courses WHERE ' . implode(' AND ', $where) . ' ORDER BY courses.starttime DESC';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$courses[$row['cid']] = $row;
				}
			}
		}
		
		return $courses;
	} // end of fn GetCourses
	
	public function GetAskImam($liveonly = true)
	{	$courses = array();
		
		if ($id = (int)$this->id)
		{	$where = array('askimamtocats.catid=' . $id, 'askimamtocats.askid=askimamtopics.askid');
			$tables = array('askimamtocats', 'askimamtopics');
		
			if ($liveonly)
			{	$where[] = 'askimamtopics.live=1';
			}
			
			$sql = 'SELECT askimamtopics.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY askimamtopics.startdate DESC';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$courses[$row['qid']] = $row;
				}
			} //else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		return $courses;
	} // end of fn GetAskImam
	
	public function CascadedName($cat = false, $sep = ' &raquo; ')
	{	if (!$cat)
		{	$cat = $this;
		}
		$name = $this->InputSafeString($cat->details['ctitle']);
		if ($parent = $cat->GetParent())
		{	return $this->CascadedName($parent) . $sep . $name;
		} else
		{	return $name;
		}
	} // end of fn CascadedName
	
	public function GetParent()
	{	if ($this->details['parentcat'] && ($parent = new CourseCategory($this->details['parentcat'])) && $parent->id)
		{	return $parent;
		}
	} // end of fn GetParent
	
	public function Link($ctype = 'course')
	{	return SITE_URL . $ctype . '-category/' . $this->id . '/' . $this->details['catslug'] . '/';
	} // end of fn Link
	
	public function HasImage()
	{	return file_exists($this->GetImageFile()) ? $this->GetImageSRC() : false;
	} // end of fn HasImage
	
	public function GetImageFile()
	{	return $this->imagedir . '/' . (int)$this->id . '.png';
	} // end of fn GetImageFile
	
	public function GetImageSRC()
	{	return $this->imagelocation . (int)$this->id . '.png';
	} // end of fn GetImageSRC
	
} // end of defn CourseCategory
?>
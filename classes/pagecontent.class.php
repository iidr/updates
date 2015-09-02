<?php
class PageContent extends Base implements Searchable
{	var $id = 0;
	var $details = array();
	var $subpages = array();
	var $parentpage = false;
	var $liveonly = false;
	var $includepath = '';
	var $imagelocation = '';
	var $imagedir = '';
	var $imagesizes = array();
	
	function __construct($id = 0, $liveonly = true)
	{	parent::__construct();
		$this->includepath = CITDOC_ROOT . '/pageinc/';
		
		$this->imagelocation = SITE_URL . 'img/pages/';
		$this->imagedir = CITDOC_ROOT . '/img/pages/';
		$this->imagesizes['default'] = array(695, 250);
		$this->imagesizes['thumbnail'] = array(340, 210);
		
		$this->liveonly = ($liveonly ? true : false);
		$this->Get($id);
	} //  end of fn __construct
	
	function Reset()
	{	$this->id = 0;
		$this->details = array();
		$this->subpages = array();
		$this->parentpage = false;
	} // end of fn Reset
	
	function Get($id = 0)
	{	$this->Reset();
		if (is_array($id))
		{	$this->details = $id;
			$this->id = $id['pageid'];
		
			$this->details['pagename'] = strtolower($this->details['pagename']);
			
			$this->GetSubPages();
			if ($this->details['parentid'])
			{	$sql = 'SELECT * FROM pages WHERE pageid=' . $this->details['parentid'];
				if ($result = $this->db->Query($sql))
				{	if ($row = $this->db->FetchArray($result))
					{	$this->parentpage = $row;
					}
				}
			}
		} else
		{	if ($id)
			{	if ((int)$id && (int)$id == $id)
				{	// get by id
					$sql = 'SELECT * FROM pages WHERE pageid=' . (int)$id;
				} else
				{	// try to get by name
					$sql = 'SELECT * FROM pages WHERE pagename="' . $this->SQLSafe($id) . '"';
				}
				if ($this->liveonly)
				{	$sql .= ' AND pagelive=1';
				}
				if ($result = $this->db->Query($sql))
				{	if ($row = $this->db->FetchArray($result))
					{	$this->Get($row);
					}
				}
			}
		}
		
	} // end of fn Get

	function IncludeFileExists($filename = '')
	{	return file_exists($this->includepath . $filename);
	} // end of fn IncludeFileExists
	
	function HTMLMainContent()
	{	ob_start();
		echo stripslashes($this->details['pagetext']);
		if ($this->details['includefile'] && $this->IncludeFileExists($this->details['includefile']))
		{	include($this->includepath . $this->details['includefile']);
		}
		foreach ($this->subpages as $subpage)
		{	if ($subpage->details['inparent'])
			{	echo '<h3 id="sub_', $subpage->details['pagename'], '">', $this->InputSafeString($subpage->details['pagetitle']), '<a href="#sidebar" onclick="SubPageUnsetHighlight();return true;" class="subPageTopLink">&laquo; back to top</a></h3>', stripslashes($subpage->details['pagetext']);
			}
		}
		return ob_get_clean();
	} // end of fn HTMLMainContent
	
	public function FullSubPages()
	{	$subpages = array();
		foreach ($this->subpages as $subpage)
		{	if (!$subpage->details['inparent'])
			{	$subpages[] = $subpage;
			}
		}
		return $subpages;
	} // end of fn FullSubPages
	
	function SideBarMenu($selected = '')
	{	ob_start();
		if ($this->parentpage)
		{	$menupage = $this->AssignPage($this->parentpage);
			return $menupage->SideBarMenu($selected);
		} else
		{	$highlight = $selected == $this->details['pagename'];
			echo '<ul><li', $highlight ? ' class="selected"' : '', '>';
			if ($this->details['headeronly'] || $highlight)
			{	echo '<span>', $this->InputSafeString($this->details['pagetitle']), '</span>';
			} else
			{	echo'<a href="', $this->Link(), '">', $this->InputSafeString($this->details['pagetitle']), '</a>';
			}
			echo $this->SubSideBarMenu($this->subpages, $selected), '</li></ul>';
		}
		return ob_get_clean();
	} // end of fn SideBarMenu
	
	function SubSideBarMenu($pages = array(), $selected = '')
	{	ob_start();
		if ($pages)
		{	echo '<ul>';
			foreach ($pages as $subpage)
			{	$subselected = $selected == $subpage->details['pagename'];
				echo '<li', $subselected ? ' class="selected"' : '', '>';
				if ($subselected || $subpage->details['headeronly'])
				{	echo '<span>', $this->InputSafeString($subpage->details['pagetitle']), '</span>';
				} else
				{	echo '<a href="', $subpage->Link(), '">', $this->InputSafeString($subpage->details['pagetitle']), '</a>';
				}
				echo $this->SubSideBarMenu($subpage->subpages, $selected), '</li>';
			}
			echo '</ul>';
		}
		return ob_get_clean();
	} // end of fn SubSideBarMenu
	
	function BodyName()
	{	if ($this->parentpage)
		{	return $this->parentpage['pagename'];
		} else
		{	return $this->details['pagename'];
		}
	} // end of fn BodyName
	
	function SampleText($tofind = '')
	{	$text = $this->RawText();
		if ($tofind)
		{	return $text;
		} else
		{	return $text;
		}
	} // end of fn SampleText
	
	function RawText()
	{	return strip_tags(str_replace(array('</h2>', '</h3>'), ' - ', stripslashes($this->details['pagetext'])));
	} // end of fn RawText
	
	function Link()
	{	if ($this->details['redirectlink'])
		{	if (strstr($this->details['redirectlink'], 'http') || strstr($this->details['redirectlink'], 'https'))
			{	return $this->details['redirectlink'];
			} else
			{	return SITE_SUB . '/' . $this->details['redirectlink'];
			}
		} else
		{	return $this->link->GetPageLink($this);
		}
	} // end of fn Link
	
	function GetSubPages()
	{	$this->subpages = array();
		if ($this->id)
		{	$sql = 'SELECT * FROM pages WHERE parentid=' . $this->id;
			if ($this->liveonly)
			{	$sql .= ' AND pagelive=1';
			}
			$sql .= ' ORDER BY pageorder';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$this->subpages[$row['pageid']] = $this->AssignPage($row);
				}
			}
		}
	} // end of fn GetSubPages
	
	function FirstSubPage()
	{	return array_shift($this->subpages);
	} // end of fn FirstSubPage
	
	function AssignPage($page = 0)
	{	return new PageContent($page);
	} // end of fn AssignSubPage
	
	// Check if a page was found
	function Found()
	{	return (isset($this->details['pagename']) && $this->details['pagename'] != '') ? true : false;	
	} // end of fn Found
	
	
	public function HasImage($size = '')
	{	return file_exists($this->GetImageFile($size)) ? $this->GetImageSRC($size) : false;
	} // end of fn HasImage
	
	public function GetImageFile($size = 'default')
	{	return $this->imagedir . $this->InputSafeString($size) . '/' . (int)$this->id .'.jpg';
	} // end of fn GetImageFile
	
	public function GetImageSRC($size = 'default')
	{	return $this->imagelocation . $this->InputSafeString($size) . '/' . (int)$this->id .'.jpg';
	} // end of fn GetImageSRC

	public function DefaultImageSRC($size = 'default')
	{	return parent::DefaultImageSRC($this->imagesizes[$size]);
	} // end of fn DefaultImageSRC
	
	
	/** Search Functions ****************/
	public function Search($term)
	{
		$results = array();
		$match = ' MATCH(pagetitle, pagetext) AGAINST("' . $this->SQLSafe($term) . '") ';
		$sql = 'SELECT *, ' . $match . ' as matchscore FROM pages WHERE ' . $match . ' AND pagelive=1 AND nosearch=0 ORDER BY matchscore DESC';
		if($result = $this->db->Query($sql))
		{	while($row = $this->db->FetchArray($result))
			{	$results[] = new PageContent($row);	
			}
		}
		
		return $results;
	} // end of fn Search
	
	public function SearchResultOutput()
	{	echo '<h4><a href="', $link = $this->Link(), '">', $this->CascadedTitle(), '</a></h4><p><a href="', $link, '">read more ...</a></p>';
	} // end of fn SearchResultOutput
	
	private function CascadedTitle($sep = ' &raquo; ')
	{	$title = $this->InputSafeString($this->details['pagetitle']);
		if ($this->parentpage && ($parent = new PageContent($this->parentpage)) && $parent->id && ($parent_title = $parent->CascadedTitle($sep)))
		{	$title = $parent_title . $sep . $title;
		}
		return $title;
	} // end of fn CascadedTitle
	
	public function GetGalleries()
	{	$galleries = array();
		$where = array('galleries.gid=gallerytopage.gid', 'gallerytopage.pageid=' . (int)$this->id);
		$sql = 'SELECT galleries.* FROM galleries, gallerytopage WHERE ' . implode(' AND ', $where) . ' ORDER BY galleries.gid DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$galleries[$row['gid']] = $row;
			}
		}
		return $galleries;
	} // end of fn GetGalleries
	
} // end of defn PageContent
?>
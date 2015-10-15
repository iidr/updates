<?php
class MultimediaPage extends BasePage
{	
	protected $category;
	protected $multimedia;
	protected $cats;
	protected $perpage = 4;
		
	function __construct()
	{	parent::__construct('multimedia');
		//print_r($_GET);
		$this->AddBreadcrumb('Multimedia', SITE_URL . 'multimedia.php');
		$this->css[] = 'page.css';
		$this->css[] = 'multimedia.css';
	//	$this->js[] = 'multimedia.js';
		$this->css[] = 'jqModal.css';
		$this->js[] = 'jqModal.js';
		
		$this->cats = $this->GetCategoriesToList();
		
		if ($_GET['mmid'])
		{	$this->multimedia = new Multimedia($_GET['mmid']);
			foreach ($this->multimedia->cats as $cat_row)
			{	foreach (array_reverse($this->CategoryBreadcrumbs($this->category = new LibCat($cat_row))) as $bcname=>$bclink)
				{	$this->AddBreadcrumb($bcname, $bclink);
				}
				break;
			}
			$this->facebookLike = true;
			$this->AddBreadcrumb($this->multimedia->details['mmname'], $this->multimedia->Link());
		} else
		{	if ($_GET['catid'])
			{	$this->category = new LibCat($_GET['catid']);
				foreach (array_reverse($this->CategoryBreadcrumbs($this->category)) as $bcname=>$bclink)
				{	$this->AddBreadcrumb($bcname, $bclink);
				}
			}
		}
		
	} // end of fn __construct
	
	public function CategoryBreadcrumbs(LibCat $libcat)
	{	$bc_cats = array($libcat->details['lcname']=>$libcat->Link());
		if ($libcat->details['parentid'] && ($parent = new LibCat($libcat->details['parentid'])) && $parent->id)
		{	foreach ($this->CategoryBreadcrumbs($parent) as $name=>$link)
			{	$bc_cats[$name] = $link;
			}
		}
		return $bc_cats;
	} // end of fn CategoryBreadcrumbs
	
	public function MainBodyContent()
	{	
		echo '<div id="sidebar" class="col">', $this->GetSubmenu(), '</div><div class="col3-wrapper-with-sidebar">';
		if ($this->multimedia->id)
		{	// display chosen multimedia
			$this->multimedia->RecordView();
			echo '<div class="mmDisplay"><h1><span', $this->multimedia->details['socialbar'] ? ' class="headertextWithSM"' : '', '>', $this->InputSafeString($this->multimedia->details['mmname']), '</span>', $this->multimedia->details['socialbar'] ? $this->GetSocialLinks(3, true) : '', '</h1>';
			if ($people_text = $this->multimedia->GetAuthorText())
			{	echo '<div class="clear"></div><h2 class="mmdDesc">', $people_text, '</h2>';
			}
			echo '<div class="mmdOutput">', $this->multimedia->Output(695), '</div><div class="mmdDesc">',$this->multimedia->DisplayFullDescHTML(400), '</div></div>';
		} else
		{	echo '<div id="cat_top_container">', $this->CategoryTopListing(), '</div><div class="clear"></div>';
		}
		echo '</div><div class="clear"></div>';
		
		$cat_disp = array();
		if ($this->cats)
		{	foreach ($this->cats as $cat_row)
			{	$cat_disp[] = $this->ListCategory($cat_row);
			}
		}
		
		if ($this->multimedia->id && $this->category->id && ($cat_media = $this->category->GetMultiMedia(true)))
		{	$limit = 20;
			$media_li = array();
			foreach ($cat_media as $media_row)
			{	$mm = new Multimedia($media_row);
				if ($mm->id != $this->multimedia->id)
				{	$media_li[] = $mm->DisplayInList();
				}
				if (++$count >= $limit)
				{	break;
				}
			}
			if ($media_li)
			{	echo '<div class="mm_list_container"><h2>More from ', $this->InputSafeString($this->category->details['lcname']), '</h2><ul><li><ul>', implode('', $media_li), '</ul><div class="clear"></div></li></ul></div>';
			}
		}
		
		if ($cat_disp)
		{	echo '<div class="mm_list_container"><h2>Video / Audio</h2><ul>', implode('', $cat_disp), '</ul></div>';
		}
		
	} // end of fn MainBodyContent
	
	public function CategoryTopListing()
	{	ob_start();
		if ($medialist = $this->GetMediaToList())
		{	if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->perpage;
			
			echo '<ul>';
			foreach ($medialist as $media_row)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
					
					if (++$licount > 2)
					{	echo '</ul><ul>';
						$licount = 0;
					}
				
					$media = new Multimedia($media_row);
					echo '<li><div class="toplist_image"><img src="', $media->Thumbnail(), '" title="', $title = $this->InputSafeString($media->details['mmname']), '" alt="', $title, '" /><a class="toplist_link toplist_link_', $media->ButtonType(), '" href="', $link = $media->Link(), '"></a></div><div class="toplist_desc"><div class="toplist_desc_text"><h4>', $title, '</h4><p>', $media->GetAuthorText(false), '<p></div><a class="toplist_link" href="', $link, '">', $media->ViewLabel(), '</a></div></li>';
				}
			}
			echo '</ul><div class="clear"></div>';

		/*	if (count($medialist) > $this->perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($medialist), $this->perpage, 'cat_top_container', 'ajax_multimedia.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}*/
		}
		return ob_get_clean();
	} // end of fn CategoryTopListing
	
	public function GetMediaToList()
	{	$medialist = array();
		
		if ($this->category->id)
		{	$medialist = $this->category->GetMultiMedia(true);
		} else
		{	$where = array('multimedia.live=1', 'multimedia.inlib=1');
			$tables = array('multimedia');
			
			$sql = 'SELECT multimedia.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY multimedia.posted DESC';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$medialist[$row['mmid']] = $row;
				}
			}
		}
		
		if ($_GET['mtform'] && (!$_GET['video'] || !$_GET['audio'] || !$_GET['othermedia']))
		{	$filtered = array();
			$video_types = array('vimeo', 'youtube', 'mp4');
			$audio_types = array('mp3');
			foreach ($medialist as $key=>$media_row)
			{	$media = new Multimedia($media_row);
				$mediatype = $media->MediaType();
				if ($_GET['video'])
				{	if (in_array($mediatype, $video_types))
					{	$filtered[$key] = $media_row;
						continue;
					}
				}
				if ($_GET['audio'])
				{	if (in_array($mediatype, $audio_types))
					{	$filtered[$key] = $media_row;
						continue;
					}
				}
				if ($_GET['othermedia'])
				{	if (!in_array($mediatype, $audio_types) && !in_array($mediatype, $video_types))
					{	$filtered[$key] = $media_row;
						continue;
					}
				}
			}
			return $filtered;
		}
		
		return $medialist;
		
	} // end of fn GetMediaToList
	
	public function GetSubmenu()
	{	ob_start();
		echo '<div class="sidebar-menu"><ul><li', (!$this->category->id && !$this->multimedia->id) ? ' class="current-subpage"' : '', '><a href="', SITE_URL, 'multimedia.php">Latest</a></li>';
		
		if ($this->cats)
		{	
			foreach($this->cats as $cat_row)
			{	$cat = new LibCat($cat_row);
				$classes = array();
				if ($cat->id == $this->category->id)
				{	$classes[] = 'current-subpage';
				}
				
				echo '<li';
				if ($classes)
				{	echo ' class="' . implode(' ', $classes) . '"';
				}
				echo '><a href="', $cat->Link(), '">', $this->InputSafeString($cat->details['lcname']), '</a></li>';
				
			}
		}
		
		echo '</ul></div>', $this->multimedia->id ? '' : $this->MediaTypeForm();
		
		if ($popular = $this->GetPriorityMultimedia())
		{	echo '<div id="sub_popular"><h3>Most Popular</h3><ul>';
			foreach ($popular as $mm_row)
			{	$mm = new Multimedia($mm_row);
				echo $mm->DisplayInList();
			}
			echo '</ul></div>';
		}
		return ob_get_clean();
	} // end of fn GetSubmenu
	
	public function MediaTypeForm()
	{	ob_start();
		echo '<div class="sidebar-menu"><h2>Media Types</h2><form id="mmTypeForm" method="get" action="', $_SERVER['SCRIPT_NAME'], '"><input type="hidden" name="mtform" value="1" />';
		if ($this->category->id)
		{	echo '<input type="hidden" name="catid" value="', $this->category->id, '" />';
		}
		echo '<p><input type="hidden" name="video" id="TickBoxValueVideo" value="', (!$_GET['mtform'] || $_GET['video']) ? '1' : '0', '" /><span class="TickBox', (!$_GET['mtform'] || $_GET['video']) ? '1' : '0', '" id="TickBoxVideo" onclick="TickBoxToggle(\'Video\'); document.getElementById(\'mmTypeForm\').submit();" ></span><label>Video</label><div class="clear"></div></p>',
			'<p><input type="hidden" name="audio" id="TickBoxValueAudio" value="', (!$_GET['mtform'] || $_GET['audio']) ? '1' : '0', '" /><span class="TickBox', (!$_GET['mtform'] || $_GET['audio']) ? '1' : '0', '" id="TickBoxAudio" onclick="TickBoxToggle(\'Audio\'); document.getElementById(\'mmTypeForm\').submit();" ></span><label>Audio</label><div class="clear"></div></p>',
			'</form></div>';
		return ob_get_clean();
	} // end of fn MediaTypeForm
	
	public function GetPriorityMultimedia($limit = 3)
	{	$media = array();
		$backcheck = $this->datefn->SQLDateTime(strtotime('-7 days'));
		$sql = 'SELECT multimedia.*, SUM(IF(ISNULL(multimediaviews.viewed) OR multimediaviews.viewed<"' . $backcheck . '", 0, 1)) AS viewcount FROM multimedia LEFT JOIN multimediaviews ON multimedia.mmid=multimediaviews.mmid WHERE multimedia.live=1 AND multimedia.inlib=1 GROUP BY multimedia.mmid ORDER BY viewcount DESC, multimedia.posted DESC LIMIT ' . (int)$limit;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$media[$row['mmid']] = $row;
			}
		}
		return $media;
	} // end of fn GetPriorityMultimedia
	
	public function ListCategory($cat_row = array(), $limit = 4)
	{	ob_start();
		static $lcCount = 0;
		$cat = new LibCat($cat_row);
		if ($multimedia = $cat->GetMultiMedia(true))
		{	ob_start();
			foreach ($multimedia as $media_row)
			{	$mm = new Multimedia($media_row);
				echo $mm->DisplayInList();
				if (!$link)
				{	$link = $mm->Link();
				}
				if (++$count >= $limit)
				{	break;
				}
			}
			$lines = ob_get_clean();
			if (!$link)
			{	$link = $cat->Link();
			}
			echo '<li class="stripe', $lcCount++ % 2, '"><ul><li class="mmlHeader"><h4>', $this->InputSafeString($cat->details['lcname']), '</h4><a href="', $link, '">View all</a></li>', $lines, '</ul><div class="clear"></div></li>';
		}
		return ob_get_clean();
	} // end of fn ListCategory
	
	public function GetCategoriesToList()
	{	$cats = array();
		if ($this->category->id)
		{	return $this->category->subcats;
		} else
		{	if ($this->multimedia->id)
			{	return $this->multimedia->cats;
			} else
			{	// get all top level categories with multimedia
				$sql = 'SELECT libcats.* FROM libcats WHERE libcats.parentid=0 ORDER BY libcats.lcorder';
				if ($result = $this->db->Query($sql))
				{	while ($row = $this->db->FetchArray($result))
					{	$cat = new LibCat($row);
						if ($cat->GetMultiMedia(true))
						{	$cats[$row['lcid']] = $row;
						}
					}
				}
			}
		}
		return $cats;
	} // end of fn GetCategoriesToList
	
} // end of defn MultimediaPage
?>
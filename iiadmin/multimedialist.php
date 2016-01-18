<?php
include_once('sitedef.php');

class MultimediaPage extends AdminMultimediaPage
{
	public function __construct()
	{	parent::__construct();
	} //  end of fn __construct

	protected function MMBodyMain()
	{	parent::MMBodyMain();
		echo $this->FilterForm(), $this->MultimediaList();
	} // end of fn MMBodyMain
	
	private function FilterForm()
	{	ob_start();
		$dummy_mm = new AdminMultimedia();
		echo '<form class="akFilterForm" method="get" action="', $_SERVER['SCRIPT_NAME'], '"><span>Category</span><select name="mmcat"><option value="">-- all categories --</option>';
		foreach ($dummy_mm->GetPossibleCats() as $catid=>$catname)
		{	echo '<option value="', $catid, '"', $catid == $_GET['mmcat'] ? ' selected="selected"' : '', '>', $catname, '</option>';
		}
		echo '</select><input type="submit" class="submit" value="Apply Filter" /><div class="clear"></div></form><div class="clear"></div>';
		return ob_get_clean();
	} // end of fn FilterForm
	
	private function GetMultmedia()
	{	$mm = array();
		$tables = array('multimedia');
		$where = array();
		
		if ($mmcat = (int)$_GET['mmcat'])
		{	$tables[] = 'multimediacats';
			$where[] = 'multimedia.mmid=multimediacats.mmid';
			$where[] = 'multimediacats.lcid=' . $mmcat;
		}
		
		$sql = 'SELECT multimedia.* FROM ' . implode(', ', $tables);
		if ($wstr = implode(' AND ', $where))
		{	$sql .= ' WHERE ' . $wstr;
		}
		$sql .= ' GROUP BY multimedia.mmid ORDER BY multimedia.mmorder ASC, multimedia.posted DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$mm[$row['mmid']] = $row;
			}
		}
		return $mm;
	} // end of fn GetMultmedia
	
	function MultimediaList()
	{	ob_start();
		$perpage = 20;
		if ($_GET['page'] > 1)
		{	$start = ($_GET['page'] - 1) * $perpage;
		} else
		{	$start = 0;
		}
		$end = $start + $perpage;
		echo '<table id="pagelist"><tr class="newlink"><th colspan="11"><a href="multimedia.php">new multimedia</a></th></tr><tr><th></th><th>Title</th><th>Media type</th><th>Categories</th><th>People</th><th>Live</th><th>Library</th><th>Posted</th><th>List Order</th><th>Times<br />Viewed</th><th>Actions</th></tr>';
		foreach ($mmlist = $this->GetMultmedia() as $mm_row)
		{	if (++$count > $start)
			{	if ($count > $end)
				{	break;
				}
				echo $this->MultimediaListLine($mm_row);
			}
		}
		echo '</table>';
		if (count($mmlist) > $perpage)
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
			$pag = new Pagination($_GET['page'], count($mmlist), $perpage, $pagelink);
			echo '<div class="pagination">', $pag->Display(), '</div>';
		}
		return ob_get_clean();
	} // end of fn MultimediaList
	
	private function MultimediaListLine($mm_row)
	{	ob_start();
		$mm = new AdminMultimedia($mm_row);
		echo '<tr><td>';
		if ($img_src = $mm->Thumbnail())
		{	echo '<img src="', $img_src, strstr($img_src, '?') ? '&' : '?', time(), '" width="100px" />';
		}
		echo '</td><td class="pagetitle">', $this->InputSafeString($mm->details['mmname']), $mm->details['frontpage'] ? '<br /><strong>FRONTPAGE VIDEO</strong>' : '', '</td><td>', $mm->MediaType(), '</td><td>', $mm->CatsList(), '</td><td>', $mm->GetAuthorText(), '</td><td>', $mm->details['live'] ? 'Yes' : 'No', '</td><td>', $mm->details['inlib'] ? 'Yes' : 'No', '</td><td>', date('d-M-y @H:i', strtotime($mm->details['posted'])), $mm->details['author'] ? ('<br />by ' . $this->InputSafeString($mm->details['author'])) : '', '</td><td>', (int)$mm->details['mmorder'], '</td><td>', $mm->ViewCount(), '</td><td><a href="multimedia.php?id=', $mm->id, '">edit</a>';
		
		//if ($mm->CanDelete()){	
			echo '&nbsp;|&nbsp;<a href="multimedia.php?id=', $mm->id, '&delete=1">delete</a>';
		//}
		
		if ($histlink = $this->DisplayHistoryLink('multimedia', $mm->id))
		{	echo '&nbsp;|&nbsp;', $histlink;
		}
		echo '</td></tr>';
		return ob_get_clean();
	} // end of fn MultimediaListLine
	
} // end of defn MultimediaPage

$page = new MultimediaPage();
$page->Page();
?>
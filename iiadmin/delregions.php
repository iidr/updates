<?php
include_once('sitedef.php');

class DelRegionsListPage extends AdminDelRegionsPage
{	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	public function DelRegionsLoggedInConstruct()
	{	parent::DelRegionsLoggedInConstruct();
		$this->breadcrumbs->AddCrumb('delregions.php', 'Regions');
	} // end of fn DelRegionsLoggedInConstruct
	
	function DelRegionsBodyMain(){	
		echo '<table><tr class="newlink"><th colspan="4"><a href="delregionedit.php">Create new region</a></th></tr><tr><th>Region name</th><th>No. of options</th><th>Countries</th><th>Actions</th></tr>';
		foreach ($this->GetRegions() as $region_row)
		{	$region = new AdminDeliveryRegion($region_row);
			echo '<tr class="stripe', $i++ % 2, '"><td>', $this->InputSafeString($region->details['drname']), '</td><td>', count($region->GetOptions()), '</td><td>', count($region->GetCountries()), '</td><td><a href="delregionedit.php?id=', $region->id, '">edit</a>';
			if ($histlink = $this->DisplayHistoryLink('delregions', $region->id))
			{	echo '&nbsp;|&nbsp;', $histlink;
			}
			if ($region->CanDelete())
			{	echo '&nbsp;|&nbsp;<a href="delregionedit.php?id=', $region->id, '&delete=1">delete</a>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	} // end of fn DelRegionsBody
	
	function GetRegions(){	
		$regions = array();
		$sql = "SELECT * FROM delregions ORDER BY drname, drid";
		if ($result = $this->db->Query($sql)){	
			while ($row = $this->db->FetchArray($result)){	
				$regions[] = $row;
			}
		}		
		return $regions;
	} // end of fn GetRegions
	
} // end of defn DelRegionsListPage

$page = new DelRegionsListPage();
$page->Page();
?>
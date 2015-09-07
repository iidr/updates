<?php
include_once('sitedef.php');

class DelRegionEditPage extends AdminDelRegionsPage
{	private $region;

	public function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	public function DelRegionsLoggedInConstruct()
	{	parent::DelRegionsLoggedInConstruct();
		
		$this->region = new AdminDeliveryRegion($_GET['id']);
		
		if (isset($_POST['drname']))
		{	$saved = $this->region->Save($_POST);
			$this->successmessage = $saved['successmessage'];
			$this->failmessage = $saved['failmessage'];
		}
		
		if ($this->region->id && $_GET['delete'] && $_GET['confirm'])
		{	if ($this->region->Delete()){	
				printf("<META HTTP-EQUIV=\"REFRESH\" CONTENT =\"0; URL=delregions.php\";>");
				exit;
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}
		
		$this->breadcrumbs->AddCrumb('delregions.php', 'Regions');
		$this->breadcrumbs->AddCrumb('delregionedit.php?id=' . (int)$this->region->id, $this->region->id ? $this->InputSafeString($this->region->details['drname']) : 'New region');
	} // end of fn DelRegionLoggedInConstruct
	
	public function DelRegionsBodyMain()
	{	echo $this->region->InputForm(), $this->region->ListCountries(), $this->region->ListOptions();
	} // end of fn DelRegionBody
	
} // end of defn DelRegionEditPage

$page = new DelRegionEditPage();
$page->Page();
?>
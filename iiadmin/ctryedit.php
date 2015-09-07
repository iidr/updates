<?php
include_once('sitedef.php');

class CountryEditPage extends AdminCountry
{	private $country;

	public function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	public function CountryLoggedInConstruct()
	{	
		$this->country = new Country($_GET['ctry']);		
		
		if (isset($_POST['shortname']))
		{	$saved = $this->country->Save($_POST);
			$this->successmessage = $saved['successmessage'];
			$this->failmessage = $saved['failmessage'];
		}
		
		if ($this->country->code && $_GET['delete'] && $_GET['confirm'])
		{	if ($this->country->Delete()){	
				printf("<META HTTP-EQUIV=\"REFRESH\" CONTENT =\"0; URL=countries.php\";>");
				exit;
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}
		
		$this->breadcrumbs->AddCrumb('countries.php', 'Countries');
		$this->breadcrumbs->AddCrumb('ctryedit.php?ctry=' . (int)$this->country->code, $this->country->code ? $this->InputSafeString($this->country->details['shortname']) : 'New country');
	} // end of fn DelRegionLoggedInConstruct
	
	public function CountryBodyMain()
	{	echo $this->country->InputForm();
	} // end of fn DelRegionBody
	
} // end of defn CountryEditPage

$page = new CountryEditPage();
$page->Page();
?>
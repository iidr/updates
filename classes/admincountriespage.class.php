<?php
class AdminCountriesPage extends AdminDelRegionsPage
{	
	function __construct()
	{	parent::__construct('ACCOUNTS');
	} //  end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct();
		if ($this->user->CanUserAccess('accounts'))
		{	$this->CountriesLoggedInConstruct();
		}
	} // end of fn LoggedInConstruct
	
	function CountriesLoggedInConstruct(){			
		$this->breadcrumbs->AddCrumb('delregions.php', 'Regions');
	} // end of fn DelOptionsLoggedInConstruct
	
	function CanDelete(){	
		return false;
	} // end of fn CanDelete
	
	function Countries()
	{	$countries = array();
		$query = "SELECT c.*, r.`drname` AS region FROM `countries` c LEFT JOIN `delregions` r ON c.`region`=r.`drid` ORDER BY c.`shortname`";
		if ($result = $this->db->Query($query))
		{	while ($row = $this->db->FetchArray($result))
			{	$countries[] = $row;
			}
		}
		return $countries;
	} // end of fn Countries
	
	function Delete($shortname){	
		if($shortname!=''){
			$sql = "DELETE FROM `countries` WHERE `shortname`='".$this->InputSafeString($shortname)."'";
			if ($result = $this->db->Query($sql)){	
				if ($this->db->AffectedRows()){	
					$this->successmessage = "$shortname deleted";
				}
			}
		}		
	} // end of fn Delete
	
	function CountriesBodyMain(){	
	
	} // end of fn DelOptionsBody
	
	function AdminBodyMain()
	{	if ($this->user->CanUserAccess('accounts'))
		{	$this->CountriesBodyMain();
		}
	} // end of fn AdminBodyMain
	
} // end of defn AdminDelOptionsPage
?>
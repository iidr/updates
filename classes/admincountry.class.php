<?php
class AdminCountry extends AdminCountriesPage //extends Country
{	
	function __construct()
	{	parent::__construct('ACCOUNTS');
		if ($this->user->CanUserAccess('accounts'))
		{	$this->CountryLoggedInConstruct();
		}
	} // fn __construct
	
	function CountryLoggedInConstruct(){
		$this->breadcrumbs->AddCrumb('countries.php', 'Countries');	
	} // end of fn DelRegionsLoggedInConstruct
	
	function CountryBodyMain()
	{	
	} // end of fn RegionsList
	
	function AdminBodyMain()
	{	if ($this->user->CanUserAccess('accounts'))
		{	$this->CountryBodyMain();
		}
	} // end of fn AdminBodyMain
	
} // end of defn AdminCountry
?>
<?php
class AdminDelRegionsPage extends AdminPage
{	
	function __construct()
	{	parent::__construct('ACCOUNTS');
	} //  end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct();
		if ($this->user->CanUserAccess('accounts')){				
			$this->DelRegionsLoggedInConstruct();
		}
	} // end of fn LoggedInConstruct
	
	function DelRegionsLoggedInConstruct(){	
		$this->breadcrumbs->AddCrumb('rawmenu.php?id=13', 'Accounts');
	} // end of fn DelRegionsLoggedInConstruct
	
	function DelRegionsBody(){	
		
	} // end of fn DelRegionsBody
	
	function AdminBodyMain()
	{	if ($this->user->CanUserAccess('accounts'))
		{	$this->DelRegionsBodyMain();
		}
	} // end of fn AdminBodyMain
	
} // end of defn AdminDelRegionsPage
?>
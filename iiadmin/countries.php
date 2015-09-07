<?php
include_once("sitedef.php");

class CountriesListPage extends AdminCountriesPage
{	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	public function CountriesLoggedInConstruct(){	
		parent::CountriesLoggedInConstruct();
		$this->breadcrumbs->AddCrumb('countries.php', 'Countries');
		
		if ($_GET["delete"]){	
			$this->Delete($_GET["delete"]);
		}
	} // end of fn DelOptionsLoggedInConstruct
	
	function CountriesBodyMain(){
		echo "<table><tr class='newlink'><th colspan='5'><a href='ctryedit.php'>Create new country</a></th></tr><tr><th>Display Name</th><th>Short Code</th><th>Long Code</th><th>Region</th><th>Actions</th></tr>";
		
		foreach ($this->Countries() as $country){
			echo "<tr class='stripe", $i++ % 2, "' id='tr", $country["shortname"], "'>", 
					"<td>",$this->InputSafeString($country["shortname"]), "</td>", 
					"<td>",$this->InputSafeString($country["shortcode"]), "</td>", 
					"<td>",$this->InputSafeString($country["longcode"]), "</td>", 
					"<td>",$this->InputSafeString($country["region"]), "</td>",
					"<td><a href='ctryedit.php?ctry=", $country["ccode"], "'>edit</a>";
					if ($this->CanDelete()){
						echo " | <a href='countries.php?delete=", $country["shortname"], "'>delete</a></td>";
					}
			echo "</tr>";
		}
		echo "</table>";
	} // end of fn CMSBodyMain	
	
} // end of defn CountriesListPage

$page = new CountriesListPage();
$page->Page();
?>
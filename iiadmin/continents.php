<?php
include_once("sitedef.php");

class ContinentsListPage extends CMSPage
{	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function CMSLoggedInConstruct()
	{	$this->breadcrumbs->AddCrumb("continents.php", "Continents List");
		
		if ($_GET["delete"])
		{	$this->Delete($_GET["delete"]);
		}
	} // end of fn CMSLoggedInConstruct
	
	function Delete($continent)
	{	$sql = "DELETE FROM continents WHERE continent='$continent'";
		if ($result = $this->db->Query($sql))
		{	if ($this->db->AffectedRows())
			{	$this->successmessage = "$continent deleted";
			}
		}
		
	} // end of fn Delete
	
	function CMSBodyMain()
	{	echo "<table><tr><th>Code</th><th>Display Name</th><th>Countries</th><th></th></tr>";
		foreach ($this->Continents() as $continent)
		{	echo "<tr class='stripe", $i++ % 2, "' id='tr", $continent["continent"], "'>\n<td>", 
					$this->InputSafeString($continent["continent"]), "</td>\n<td>", 
					$this->InputSafeString($continent["dispname"]), "</td>\n<td>", 
					(int)$continent["countrycount"], "</td>\n<td><a href='continentedit.php?continent=", 
					$continent["continent"], "'>edit</a>";
			if (!$continent["countrycount"])
			{	echo " | <a href='continents.php?delete=", $continent["continent"], "'>delete</a>";
			}
			echo "</td>\n</tr>\n";
		}
		echo "</table>\n<p><a href='continentedit.php'>new continent</a></p>\n";
	} // end of fn CMSBodyMain
	
	function Continents()
	{	$continents = array();
		if ($result = $this->db->Query("SELECT continents.*, COUNT(countries.ccode) AS countrycount 
									FROM continents LEFT JOIN countries ON continents.continent=countries.continent 
								GROUP BY continents.continent ORDER BY contorder"))
		{	while ($row = $this->db->FetchArray($result))
			{	$continents[] = $row;
			}
		}
		return $continents;
	} // end of fn Continents
	
} // end of defn CountriesListPage

$page = new ContinentsListPage();
$page->Page();
?>
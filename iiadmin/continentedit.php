<?php
include_once("sitedef.php");
class_exists("Form");

class ContinentEditPage extends CMSPage
{	var $continent = array();

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function CMSLoggedInConstruct()
	{	$this->GetContinent($_GET["continent"]);
		$this->breadcrumbs->AddCrumb("continents.php", "Continent List");
		$this->breadcrumbs->AddCrumb("continentedit.php?continent={$this->continent["continent"]}", 
						$this->InputSafeString($this->continent["dispname"]));
		
		if ($_POST["dispname"])
		{	$this->Save();
		}
	} // end of fn CMSLoggedInConstruct

	function Save()
	{	$fail = array();
		$fields = array();
		
		if ($this->continent)
		{	$continent = $this->continent["continent"];
		} else
		{	if ($continent = $_POST["continent"])
			{	$fields[] = "continent='$continent'";
			} else
			{	$fail[] = "continent code needed for new continent";
			}
		}
		
		if ($dispname = $this->SQLSafe($_POST["dispname"]))
		{	$fields[] = "dispname='$dispname'";
		} else
		{	$fail[] = "display name missing";
		}
		
		$fields[] = "contorder=" . (int)$_POST["contorder"];
		
		if ($fail)
		{	$this->failmessage = implode(", ", $fail);
		} else
		{	$set = implode(", ", $fields);
			if ($this->continent)
			{	$sql = "UPDATE continents SET $set WHERE continent='{$this->continent["continent"]}'";
			} else
			{	$sql = "INSERT INTO continents SET $set";
			}
			if ($this->db->Query($sql))
			{	
				header("location: continents.php#tr" . $continent);
				exit;
			} else
			{	$this->failmessage = $this->db->Error();
			}
		}
		
	} // end of fn Save
	
	function GetContinent($continent = "")
	{	$this->continent = array();
		if ($result = $this->db->Query("SELECT * FROM continents WHERE continent='$continent'"))
		{	if ($row = $this->db->FetchArray($result))
			{	$this->continent = $row;
			}
		}
		
	} // end of fn GetContinent
	
	function CMSBodyMain()
	{	$this->ContinentForm();
		$this->CountryList();
	} // end of fn CMSBodyMain
	
	function CountryList()
	{	if ($countries = $this->GetCountries())
		{	echo "<br class='clear' />\n<ul>\n";
			foreach ($countries as $country)
			{	echo "<li><a href='ctryedit.php?ctry=", $country["ccode"], "'>", $this->InputSafeString($country["shortname"]), 
						"</li>\n";
			}
			echo "</ul>\n";
		}
	} // end of fn CountryList
	
	function GetCountries()
	{	$countries = array();
		if ($result = $this->db->Query("SELECT * FROM countries WHERE continent='{$this->continent["continent"]}' ORDER BY shortname"))
		{	while ($row = $this->db->FetchArray($result))
			{	$countries[] = $row;
			}
		}
		return $countries;
	} // end of fn GetCountries
	
	function ContinentForm()
	{	
		$form = new Form($_SERVER["SCRIPT_NAME"] . "?continent=" . $this->continent["continent"], "crewcvForm");
		if (!$this->continent)
		{	$form->AddTextInput("Continent Code (AA)", "continent", "", "", 3, 1);
		}
		$form->AddTextInput("Display name", "dispname", $this->InputSafeString($this->continent["dispname"]), "", 30, 1);
		$form->AddTextInput("Display order", "contorder", (int)$this->continent["contorder"], "", 2);
		$form->AddSubmitButton("", "Save", "submit");
		$form->Output();
	} // end of fn ContinentForm
	
} // end of defn ContinentEditPage

$page = new ContinentEditPage();
$page->Page();
?>
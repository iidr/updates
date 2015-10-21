<?php
include_once('sitedef.php');

class AdminMembersListPage extends AdminMembersPage
{
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AdminMembersLoggedInConstruct()
	{	parent::AdminMembersLoggedInConstruct();
		$this->css[] = 'adminctry.css';
		$this->css[] = 'adminmembers.css';
		$this->js[] = 'admin_member_nonemail.js';
	} // end of fn AKMembersLoggedInConstruct
	
	function AdminMembersBody()
	{	$adminmembers = $this->GetAdminMembers();
		echo $this->ListAdminMembers($adminmembers);
	} // end of fn AKMembersBody
	
	function FilterForm()
	{	ob_start();
		echo "<form class='akFilterForm' method='get' action='", $_SERVER["SCRIPT_NAME"], "'>\n<span>Male or Female</span>\n<select name='morf'>\n";
		foreach (array(""=>"all", "M"=>"Male", "F"=>"Female") as $option=>$text)
		{	echo "<option value='", $option, "'", $option == $_GET["morf"] ? " selected='selected'" : "", ">", $text, "</option>\n";
		}
		echo "</select><span>Name (part)</span><input type='text' name='name' value='", $this->InputSafeString($_GET["name"]), "' />\n<input type='submit' class='submit' value='Apply Filter' />\n<div class='clear'></div></form><div class='clear'></div>";
		return ob_get_clean();
	} // end of fn FilterForm
	
	function OptionsList($membercount = 0)
	{	ob_start();
	
		// build list of filter options
		$filter_applied = array();
		$link_paras = array();
		
		if ($_GET['morf'])
		{	switch ($_GET['morf'])
			{	case 'M': $filter_applied[] = '<strong>Male only</strong>'; break;
				case 'F': $filter_applied[] = '<strong>Female only</strong>'; break;
			}
			$link_paras[] = 'morf=' . $_GET['morf'];
		}
		
		if ($_GET['name'])
		{	$filter_applied[] = 'in name or email <strong>"' . $this->InputSafeString($_GET['name']) . '"</strong>';
			$link_paras[] = 'name=' . $_GET['name'];
		}
		
		echo '<div class="cblFilterInfo"><div class="cblFilterInfoFilter">filter applied: ';
		if ($filter_applied)
		{	echo implode('; ', $filter_applied);
			$link_para_string = '?' . implode('&', $link_paras);
		} else
		{	echo 'none';
		}
		echo ' ... ', $membercount, ' admin members found</div>';
		echo '<div class="clear"></div></div>';
		return ob_get_clean();
	} // end of fn OptionsList
	
	function GetAdminMembers()
	{	$adminmembers = array();
		$where = array();
		$tables = array('adminusers');
		
		if ($name = $this->SQLSafe($_GET['name']))
		{	$where[] = '(CONCAT(adminusers.firstname, " ", adminusers.surname, "|", adminusers.username) LIKE "%' . $name . '%")';
		}
		
		$sql = 'SELECT students.* FROM ' . implode(',', $tables);
		if ($wstr = implode(' AND ', $where))
		{	$sql .= ' WHERE ' . $wstr;
		}
		
		echo 'Query: ',$sql .= ' GROUP BY adminusers.auserid ORDER BY adminusers.surname, adminusers.firstname LIMIT 0, 1000';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$adminmembers[] = $row;
			}
		}
		
		return $adminmembers;
	} // end of fn GetMembers
	
} // end of defn MembersListPage

$page = new AdminMembersListPage();
$page->Page();
?>
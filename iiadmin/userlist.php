<?php
include_once("sitedef.php");

class UserListPage extends AdminPage
{	
	function __construct()
	{	parent::__construct("ADMIN");
	} //  end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct();
		if ($this->user->CanUserAccess("administration"))
		{	$this->css[] = "adminusers.css";
			$this->breadcrumbs->AddCrumb("userlist.php", "Admin Users");
		}
	} // end of fn LoggedInConstruct
	
	function AdminBodyMain()
	{	if ($this->user->CanUserAccess("administration"))
		{	$this->UserList();
		}
	} // end of fn AdminBodyMain
	
	function UserList()
	{	$sql = "SELECT * FROM adminusers ORDER BY surname, firstname";
		echo "<table>\n<tr class='newlink'><th colspan='5'><a href='useredit.php'>create new user</a></th></tr>\n<tr><th>Name</th><th>Log in</th><th>Access to ...</th><th>Actions</th></tr>\n";
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$user = new AdminUser($row["auserid"], 1);
				if ($this->user->username==='iidr'){					
					if($this->user->userid != $user->userid){
						echo "<tr class='stripe", $i++ % 2, "'>\n<td>", $user->fullname, "</td>\n<td>", $user->username, "</td>\n<td>", $user->UserAccessList(), "</td>\n<td><a href='useredit.php?userid=", $row["auserid"], "'>edit</a>";
						echo "&nbsp;|&nbsp;", $user->DeleteLink("delete");					
					}
				}elseif($this->user->userid != $user->userid){
					if ($user->username!=='iidr'){
						echo "<tr class='stripe", $i++ % 2, "'>\n<td>", $user->fullname, "</td>\n<td>", $user->username, "</td>\n<td>", $user->UserAccessList(), "</td>\n<td><a href='useredit.php?userid=", $row["auserid"], "'>edit</a>";
					}
				}
				echo "</td>\n</tr>\n";
			}
		}
		echo "</table>\n";
	} // end of fn UserList
	
} // end of defn UserListPage

$page = new UserListPage();
$page->Page();
?>
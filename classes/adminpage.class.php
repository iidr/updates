<?php
class_exists('Form');

class AdminPage extends BasePage
{	var $usertypes = array();
	var $adminarea = '';

	function __construct($adminarea = '')
	{	parent::__construct();
		$this->adminarea = $adminarea;
		$this->SetUser();
		$this->css = array('adminpage.css');
		$this->title = $this->GetParameter('shorttitle') . ' admin';
		$this->breadcrumbs = new BreadCrumbs('', 'Admin');
		$this->breadcrumbs->AddRightLink('../index.php', 'Live Site');
		$this->headerMenuButtons = array();
		if ($this->user->loggedin)
		{	$this->LoggedInConstruct();
		}
	} //  end of fn __construct

	function LoggedInConstruct()
	{	$this->css[] = 'dropdown.css';
		$this->css[] = 'admindropdown.css';
		$this->css[] = 'jqModal.css';
		$this->js = array();
	//	$this->js[] = 'jquery.idTabs.min.js';
	//	$this->js[] = 'jquery.cycle.all.js';
		$this->js[] = 'global.js';
		$this->js[] = 'jqModal.js';
		$this->js[] = 'admin_actions.js';
	} // end of fn LoggedInConstruct
	
	function HeaderMenu(){}
	function SetDefaultCountry(){}
	function ShareThisJS(){}
	
	function Messages()
	{	
		if ($this->successmessage)
		{	echo '<div class="successmessage">', $this->successmessage, '</div>';
		}
		if ($this->failmessage)
		{	echo '<div class="failmessage">', $this->failmessage, '</div>';
		}
		if ($this->warningmessage)
		{	echo '<div class="warningmessage">', $this->warningmessage, '</div>';
		}
	} // end of fn Messages
	
	function AdminMenu()
	{	$adminmenu = new AdminMenu($this->user);
		if ($adminmenu->menuitems)
		{	echo "<div id='ddheader-menu'><ul class='dropdown dropdown-horizontal' id='ddmenu'>\n";
			foreach ($adminmenu->menuitems as $item)
			{	$this->AdminMenuButton($item);
			}
			echo "</ul><br class='clear' /></div>\n";
		}
	} // end of fn AdminMenu
	
	function AdminMenuButton(AdminMenuItem $item)
	{	echo "<li><a", $this->adminarea && (strtoupper($item->details["menuarea"]) == $this->adminarea) 
										? " class='selected'" : "", " href='", 
				$item->details["menulink"] ? $item->details["menulink"] : "rawmenu.php?id={$item->id}", "'>", 
				$this->InputSafeString($item->details["menutext"]), "</a>", $this->SubMenu($item->submenu), "</li>\n";
	} // end of fn AdminMenuButton
	
	function SubMenu($submenu = array())
	{	ob_start();
		if (is_array($submenu->menuitems) && count($submenu->menuitems))
		{	echo "<ul>";
			foreach ($submenu->menuitems as $item)
			{	echo "<li><a href='", $item->details["menulink"] ? $item->details["menulink"] : "rawmenu.php?id={$item->id}", 
					"'>", $this->InputSafeString($item->details["menutext"]), "</a>";
				if (is_array($item->submenu->menuitems) && count($item->submenu->menuitems))
				{	echo $this->SubMenu($item->submenu);
				}
				echo "</li>\n";
			}
			echo "</ul>";
		}
		return ob_get_clean();
	} // end of fn SubMenu
	
	function SetUser()
	{	if ($_GET["logout"])
		{	unset($_SESSION[SITE_NAME]["auserid"]);
		} else
		{	if ($_POST["ausername"] && $_POST["apass"])
			{	$_SESSION[SITE_NAME]["auserid"] = $this->LogIn();
			}
		}
		$this->user = $this->GetAdminUser();
	} // end of fn SetUser
	
	function LogIn()
	{	$userid = 0;
		$username = $_POST["ausername"];
		$pass = $_POST["apass"];
		if ($result = $this->db->Query($sql = "SELECT auserid FROM adminusers WHERE ausername='$username' AND upassword=MD5('$pass')"))
		{	if ($row = $this->db->FetchArray($result))
			{	$userid = (int)$row["auserid"];
			} else $this->failmessage = "log in failed";
		} else $this->failmessage = "log in failed db";
		return $userid;
	} // end of fn LogIn

	function MainBody() //
	{	
		if ($this->user->loggedin)
		{	$this->AdminMenu();
			$this->DisplayBreadcrumbs();
			$this->Messages();
		}
		if ($this->user->loggedin)
		{	$this->AdminBodyMainContainer();
			if ($this->CanSeeHistory())
			{	echo $this->HistoryPopUp();
			}
		}
	} // end of fn MainBody

	function HistoryPopUp()
	{	ob_start();
		echo '<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#aa_modal_popup").jqm({trigger:".historyOpener"});});</script>',
			"<!-- START user info modal popup -->\n<div id='aa_modal_popup' class='jqmWindow' style='padding-bottom: 5px; width: 840px; height: 470px; margin-left: -420px; top: 10px;'>\n<a href='#' class='jqmClose submit'>Close</a>\n<div id='aaModalInner'></div></div>\n<!-- EOF invite code modal popup -->\n";
		return ob_get_clean();
	} // end of fn HistoryPopUp
	
	function DisplayBreadcrumbs()
	{	if ($this->user->loggedin)
		{	$this->breadcrumbs->Display();
		}
	} // end of fn DisplayBreadcrumbs
	
	function AdminBodyMainContainer()
	{	echo "<div id='container'>\n";
		$this->AdminBodyMain();
		echo "<br class='clear' /></div>\n";
	} // end of fn AdminBodyMainContainer
	
	function AdminBodyMain()
	{	$this->HeaderMenu();
	} // end of fn AdminBodyMain
	
	function Header()
	{	echo '<div id="header"><h1>Administration Tools</h1>';
		if (!$this->user->loggedin)
		{	$this->LoginForm();
		}
		echo "<div class='clear'></div>\n</div>\n";
	} // end of fn Header
	
	function LogInForm() // overrides existing
	{	echo "<div id='login'>\n<div id='content'>\n<form action='index.php' method='post' name='login'>\n",
				//"<h2>Admin Login</h2>\n",
				"<label for='ausername'>Username:</label>\n",
				"<input name='ausername' id='ausername' type='text' size='40' />\n",
				"<label for='apass'>Password:</label>\n",
				"<input name='apass' id='apass' type='password' size='30' />\n",
				"<p>&nbsp;</p>\n",
				"<input name='submit' class='submit' type='submit' value='Log in' />\n",
			"</form>\n</div>\n</div>";
		
	} // end of fn LogInForm

	function LoggedInHeader()
	{	echo "<div id='login'>\n<div id='content'>\n<h2>Welcome ", $this->user->firstname, "</h2>\n";
		$this->LogOutLink();
		echo "</div>\n</div>";
	} // end of fn LoggedInHeader
	
	function GoogleAnalytics()
	{	// no analytics on admin
	} // end of fn GoogleAnalytics
	
	function TableFromQuery($sql = "")
	{	if ($result = $this->db->Query($sql))
		{	if ($numrows = $this->db->NumRows($result))
			{	echo "<table>\n";
				while ($row = $this->db->FetchArray($result))
				{	if (!$rcount++)
					{	// then do header row
						$fcount = count($row);
						echo "<tr>\n";
						foreach ($row as $field=>$value)
						{	echo "<th>", $field, "</th>\n";
						}
						echo "</tr>\n";
					}
					echo "<tr>\n";
					foreach ($row as $field=>$value)
					{	echo "<td>", $this->InputSafeString($value), "</td>\n";
					}
					echo "</tr>\n";
				}
				echo "<tr><td colspan='", $fcount, "'>Records found: ", (int)$numrows, "</td></tr>\n</table>\n";
			}
		}
	
	} // end of fn TableFromQuery
	
	function Footer()
	{	echo '<div id="footer">Websquare IT Solutions - Copyright &copy; ', @date('Y'), '</div>';
	} // end of fn Footer
	
	function ListMembers($memberlist, $start = "", $end = "", $country = "")
	{	ob_start();
		$perpage = 30;
		if ($_GET["page"] > 1)
		{	$start = ($_GET["page"] - 1) * $perpage;
		} else
		{	$start = 0;
		}
		$end = $start + $perpage;
		
		
		echo '<table class="akMemberList"><tr class="newlink"><th colspan="7"><a href="memberedit.php">register new member</a></th></tr><tr><th>Name</th><th>Email</th><th>Country</th><th>City</th><th>Male or<br />Female</th><th>Actions</th></tr>';
		foreach ($memberlist as $memberid)
		{	if (++$count > $start)
			{	if ($count > $end)
				{	break;
				}
				if (is_a($memberid, 'Student'))
				{	$member = $memberid;
				} else
				{	$member = new Student($memberid);
				}
				if (!$country = $this->GetCountry($member->details['country']))
				{	$country = '';
				}
				echo '<tr><td><a href="member.php?id=', $member->id, '">', $this->InputsafeString($member->details['firstname'] . ' ' . $member->details['surname']), '</a></td><td><a href="mailto:', $member->details['username'], '">', $this->InputsafeString($member->details['username']), '</a></td><td>', $country, '</td><td>', $this->InputsafeString($member->details['city']), '</td><td>', $member->details['morf'], '</td><td><a href="member.php?id=', $member->id, '">view</a>&nbsp;|&nbsp;<a href="memberedit.php?id=', $member->id, '">edit</a>';
				//'&nbsp;|&nbsp;<a href="memberbook.php?id=', $member->id, '">book</a>';
				if ($histlink = $this->DisplayHistoryLink('students', $member->id))
				{	echo '&nbsp;|&nbsp;', $histlink;
				}
				if ($member->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="member.php?id=', $member->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
		}
		echo '</table>';
		if (count($memberlist) > $perpage)
		{	$pagelink = $_SERVER['SCRIPT_NAME'];
			if ($_GET)
			{	$get = array();
				foreach ($_GET as $key=>$value)
				{	if ($value && ($key != "page"))
					{	$get[] = "$key=$value";
					}
				}
				if ($get)
				{	$pagelink .= "?" . implode("&", $get);
				}
			}
			$pag = new Pagination($_GET["page"], count($memberlist), $perpage, $pagelink);
			echo "<div class='pagination'>", $pag->Display(), "</div>";
		}
		
		return ob_get_clean();
	} // end of fn ListMembers
	
	function ListAdminMembers($memberlist, $start = "", $end = "", $country = "")
	{	ob_start();
		$perpage = 30;
		if ($_GET["page"] > 1)
		{	$start = ($_GET["page"] - 1) * $perpage;
		} else
		{	$start = 0;
		}
		$end = $start + $perpage;
		
		
		echo '<table class="akMemberList"><tr class="newlink"><th colspan="7"><a href="adminmemberedit.php">register new admin member</a></th></tr><tr><th>Name</th><th>Email</th><th>Actions</th></tr>';
		foreach ($memberlist as $memberid)
		{	if (++$count > $start)
			{	if ($count > $end)
				{	break;
				}
				echo '<tr><td><a href="adminmember.php?id=', $adminmembers->id, '">', $this->InputsafeString($adminmembers->details['firstname'] . ' ' . $adminmembers->details['surname']), '</a></td><td><a href="mailto:', $adminmembers->details['username'], '">', $this->InputsafeString($adminmembers->details['username']), '</a></td><td><a href="adminmember.php?id=', $adminmembers->id, '">view</a>&nbsp;|&nbsp;<a href="adminmemberedit.php?id=', $adminmembers->id, '">edit</a>';
				if ($adminmembers->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="adminmember.php?id=', $adminmembers->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
		}
		echo '</table>';
		if (count($memberlist) > $perpage)
		{	$pagelink = $_SERVER['SCRIPT_NAME'];
			if ($_GET)
			{	$get = array();
				foreach ($_GET as $key=>$value)
				{	if ($value && ($key != "page"))
					{	$get[] = "$key=$value";
					}
				}
				if ($get)
				{	$pagelink .= "?" . implode("&", $get);
				}
			}
			$pag = new Pagination($_GET["page"], count($memberlist), $perpage, $pagelink);
			echo "<div class='pagination'>", $pag->Display(), "</div>";
		}
		
		return ob_get_clean();
	} // end of fn ListMembers
	
	function Redirect($url = "")
	{	if (strstr($url, "?"))
		{	$url .= "&no_bl=1";
		} else
		{	$url .= "?no_bl=1";
		}
		header("location: " . SITE_SUB . "/iiadmin/" . $url);
		exit;
	} // end of fn Redirect
	
	public function JSIncludeInitiate()
	{	echo "<script>jsSiteRoot='", SITE_SUB, "/iiadmin/';</script>\n";
	} // end of fn JSIncludeInitiate
	
} // end of defn AdminPage
?>
<?php
class AdminUser extends Base
{	var $surname = '';		// user name
	var $firstname = '';
	var $fullname = '';
	var $email = '';
	var $username = '';
	var $usertype = 0;
	var $userid = 0;
	var $useraccess = array();
	var $loggedin = false;
	var $infoonly = false;
	//var $accessAreas = array('administration'=>0, 'web content'=>1, 'news'=>5, 'accounts'=>3, 'courses'=>4, 'members'=>8, 'site-emails'=>13, 'technical'=>2);
	var $accessAreas = array(0=>'administration', 1=>'web content', 5=>'news', 3=>'accounts', 4=>'courses', 8=>'members', 13=>'site-emails', 2=>'technical');
	
	function __construct($userid = 0, $infoonly = 0){
		parent::__construct();
		if($this->userid = (int)$userid){
			$this->infoonly = (int)$infoonly;
			$this->GetUserInfo();
		}
	} // fn __construct

	function GetUserInfo(){
		$sql = 'SELECT * FROM adminusers WHERE auserid=' . $this->userid;
		if($result = $this->db->Query($sql)){
			if($row = $this->db->FetchArray($result)){
				$this->username = $row['ausername'];
				$this->firstname = $row['firstname'];
				$this->surname = $row['surname'];
				$this->email = $row['email'];
				$this->fullname = trim($row['firstname'] . ' ' . $row['surname']);
				
				if($row['useraccess']!=''){
					$accessChunks = explode(',',$row['useraccess']);
					if(count($accessChunks)>0){
						foreach($accessChunks as $value){
							if($this->accessAreas[$value]!=''){
								$this->useraccess[$value] = $this->accessAreas[$value];
							}
						}
					}
				}
				
				if(!$this->infoonly) $this->loggedin = true;
			}
		}
	} // end of fn GetUserInfo

	function CanUserAccess($area = ''){
		if($area!=''){
			if(in_array($area,$this->useraccess)){
				return true;
			}
		}else{
			return false;
		}
	} // end of fn CanUserAccess
	
	function UserAccessList()
	{	$areas = array();
		foreach ($this->accessAreas as $area=>$digit)
		{	if($this->CanUserAccess($area))
			{	$areas[] = $area;
			}
		}
		return implode(", ", $areas);
	} // end of fn UserAccessList
	
	function Save($username = "", $password = "", $firstname = "", $surname = "", $access = array(), $email = "")
	{	//$accessnumber = (int)array_sum($access);
		$accessnumber = implode(',', array_keys($access));
		$setstr = "ausername='$username', firstname='$firstname', surname='$surname', useraccess='$accessnumber', email='$email'";
		if($password)
		{	$setstr .= ", upassword=MD5('$password')";
		}
		if($this->userid)
		{	$sql = "UPDATE adminusers SET $setstr WHERE auserid=$this->userid";
			if($result = $this->db->Query($sql))
			{	if($this->db->AffectedRows())
				{	$this->GetUserInfo();
					return true;
				}
			}
		} else // must be new user
		{	$sql = "INSERT INTO adminusers SET $setstr";
			if($result = $this->db->Query($sql))
			{	if($this->db->AffectedRows())
				{	$this->userid = $this->db->InsertID();
					$this->GetUserInfo();
					return true;
				}
			}
		}
		return false;
	} // end of fn Save
	
	function Delete()
	{	$sql = "DELETE FROM adminusers WHERE auserid=" . (int)$this->userid;
		if($result = $this->db->Query($sql))
		{	if($this->db->AffectedRows())
			{	$sql = "DELETE FROM adminuserctry WHERE auserid=" . (int)$this->userid;
				$this->db->Query($sql);
				return true;
			}
		}
	} // end of fn Delete
	
	function DeleteLink($text = "delete this user")
	{	ob_start();
		if($this->userid)
		{	echo "<a href='useredit.php?userid=", $this->userid, "&delete=1", $_GET["delete"] ? "&confirm=1" : "" , "'>", 
					$_GET["delete"] ? "confirm you want to " : "" , $text, "</a>";
		}
		return ob_get_clean();
	} // end of fn DeleteLink
	
	function SaveMemberDetails()
	{	
	} // end of fn SaveMemberDetails
	
	public function CanAccessCountry($country = '')
	{	return true;
	} // end of fn CanAccessCountry
	
} // end if class defn AdminUser
?>
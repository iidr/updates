<?php
include_once('sitedef.php');

class MemberDetailsPage extends MemberPage
{
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AKMembersLoggedInConstruct()
	{	parent::AKMembersLoggedInConstruct();
		$this->member_option = 'details';
		
		if ($_GET['delete'] && $_GET['confirm'] && $this->member->CanDelete())
		{	if ($this->member->Delete())
			{	$this->successmessage = 'Member deleted';
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}

	} // end of fn AKMembersLoggedInConstruct
	
	public function MemberViewBody()
	{	parent::MemberViewBody();
		$this->MemberDisplay();
	} // end of fn MemberViewBody
	
	function MemberDisplay()
	{	//print_r($this->member->details);
		$phones = array();
		if ($phone = $this->InputSafeString($this->member->details['phone']))
		{	$phones[] = $phone;
		}
		if ($phone = $this->InputSafeString($this->member->details['phone2']))
		{	$phones[] = $phone;
		}
		if (!$country = $this->GetCountry($this->member->details['country']))
		{	$country = '';
		}
		
		if ($this->member->CanDelete())
		{	echo '<p><a href="';
			echo ($_GET['delete'])?'javascript:;" onclick="getConfirmation(\'member.php?id='.$this->member->id.'&delete=1&confirm=1\');"':'member.php?id=', $this->member->id, '&delete=1';
			echo '">', $_GET['delete'] ? 'please confirm that you want to ' : '', 'delete this member</a></p>';
		}
		echo '<table class="adminDetailsHeader">',
				'<tr><td class="label">Name</td><td>', $this->InputSafeString($this->member->details['firstname'] . ' ' . $this->member->details['surname']), ' - <a href="memberedit.php?id=', $this->member->id, '">edit profile</a> &nbsp;', $this->DisplayHistoryLink('students', $this->member->id), '</td></tr>',
				'<tr><td class="label">Email / username</td><td>', $domaillink = $this->ValidEmail($this->member->details['username']) ? ('<a href="mailto:' . $this->member->details['username'] . '">') : '', $this->member->details['username'], $domaillink ? '</a>' : '';
		if ($this->member->details['emailfailed'])
		{	echo ' - email address marked as failed';
		}
		echo '</td></tr>',
				'<tr><td class="label">Phone</td><td>', implode('<br />', $phones), '</td></tr>',
				'<tr><td class="label">Address</td><td>', $this->InputSafeString($this->InputSafeString($this->member->details['address1'])), '</td></tr>',
				'<tr><td class="label">&nbsp;</td><td>', $this->InputSafeString($this->InputSafeString($this->member->details['address2'])), '</td></tr>',
				'<tr><td class="label">&nbsp;</td><td>', $this->InputSafeString($this->InputSafeString($this->member->details['address3'])), '</td></tr>',
				'<tr><td class="label">Town / City</td><td>', $this->InputSafeString($this->member->details['city']), '</td></tr>',
				'<tr><td class="label">Postcode</td><td>', $this->InputSafeString($this->member->details['postcode']), '</td></tr>',
				'<tr><td class="label">Country</td><td>', $country, '</td></tr>',
				'<tr><td class="label">Male or Female</td><td>', $this->InputSafeString($this->member->details['morf']), '</td></tr>',
				'<tr><td class="label">Date of birth</td><td>', (int)$this->member->details['dob'] ? date('d-M-Y', strtotime($this->member->details['dob'])) : '', '</td></tr>',
				'<tr><td class="label">Registered</td><td>', date('d M Y @H:i', strtotime($this->member->details['regdate'])), '</td></tr>';
		echo '</table>';
	} // end of fn MemberDisplay
	
} // end of defn MemberDetailsPage

$page = new MemberDetailsPage();
$page->Page();
?>
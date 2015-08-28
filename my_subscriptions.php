<?php 
require_once('init.php');

class MySubscriptionsPage extends AccountPage{	
	function __construct()
	{	parent::__construct();
	} // end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct('subs');
		if ($_GET['create_aff'] && !$this->user->GetAffilateRecord())
		{	$aff = new AffiliateStudent();
			$saved = $aff->Create($this->user);
			$this->failmessage = $saved['fail'];
			$this->successmessage = $saved['success'];
		}
	} // end of fn LoggedInConstruct
	
	function LoggedInMainBody(){	
		echo '<div class="register-page">', $this->user->SubscriptionDetails(),'</div>';
	} // end of fn LoggedInMainBody
	
} // end of defn EditProfilePage

$page = new MySubscriptionsPage();
$page->Page();
//$page = new AccountPage;
//$page->Page();
?>
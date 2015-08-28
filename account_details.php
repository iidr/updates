<?php 
require_once('init.php');

class EditProfilePage extends AccountPage
{	
	function __construct()
	{	parent::__construct();
	} // end of fn __construct
	
	function LoggedInConstruct()
	{	parent::LoggedInConstruct('details');
		$this->css[] = 'page.css';
		
		if (isset($_POST['username']))
		{	$saved = $this->user->SaveDetails($_POST);
			$this->failmessage = $saved['fail'];
			$this->successmessage = $saved['success'];
		}
		
		if ($_GET['show'])
		{	switch ($_GET['show'])
			{	case 'justreg': 
					$this->successmessage = 'Welcome to IIDR, you are now registered';
					break;
			}
		}
	} // end of fn LoggedInConstruct
	
	function LoggedInMainBody()
	{	
		echo '<div class="register-page">', $this->user->EditDetailsForm(), '</div>';
	} // end of fn LoggedInMainBody
	
} // end of defn EditProfilePage

$page = new EditProfilePage();
$page->Page();
//$page = new AccountPage;
//$page->Page();
?>
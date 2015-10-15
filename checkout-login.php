<?php 
require_once('init.php');

class CheckoutLoginRegister extends CheckoutPage
{	
	function __construct()
	{	
		parent::__construct();
		$this->css[] = 'page.css';
		
		$this->next_stage = 'checkout-gift.php';
	
		if ($this->GetStage() < 1)
		{	$this->RedirectToPreviousStage();
		}
		
		if ($this->user->id)
		{	$this->SetPreviousStage('checkout-login.php');
			$this->RedirectToNextStage();	
		}
	} // end of fn __construct
	
	public function MainBodyContent()
	{
		
		if($_GET['login'] == 'failed')
		{	echo '<div class="failmessage">Your login attempt was not successful. Please try again.</div>';
		}
		echo '<div class="register-page"><h3>Existing User</h3>', $this->user->loginForm(), '<h3>New User</h3>', $this->user->RegisterForm1('', 'Disclaimer: Fusce laoreet magna nec magna rhoncus non cursus leo interdum. Vivamus a est sed erat pellentesque tempor sit amet vitae velit.'), '</div>';
	} // end of fn MainBodyContent

} // end of defn CheckoutLoginRegister

$page = new CheckoutLoginRegister;
$page->Page();

?>
<?php
require_once('init.php');

class OrderCancelPage extends BasePage
{
	public function __construct()
	{
		parent::__construct();		
		unset($_SESSION['order_id']);
	}
	
	public function MainBodyContent()
	{
		echo "<h1>Order Cancelled</h1>";
		echo "<p>Your order has been cancelled and you have not been charged.</p>";
		/*echo "<p><a href='". $this->link->GetLink('') ."'>Return to homepage?</a></p>";*/	
	}
}

$page = new OrderCancelPage();
$page->Page();

?>
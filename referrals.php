<?php 
require_once('init.php');

class MyReferralsPage extends AccountPage
{	
	function __construct()
	{	parent::__construct();
	} // end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct('refer');
		$this->js[] = 'myacreferrals.js';
		if ($_GET['create_aff'] && !$this->user->GetAffilateRecord())
		{	$aff = new AffiliateStudent();
			$saved = $aff->Create($this->user);
			$this->failmessage = $saved['fail'];
			$this->successmessage = $saved['success'];
		}
	} // end of fn LoggedInConstruct
	
	function LoggedInMainBody(){	
		echo '<div id="myReferralsContainer">'; 
			//$this->AffRewardsTable(), 
			//$this->AffilitateContainer(),
			$this->ReferralsTable();
		echo '</div>';
	} // end of fn LoggedInMainBody
	
	protected function PageHeaderRightContent()
	{	ob_start();
		if ($this->user->CanSendReferral())
		{	if (($aff = $this->user->GetAffilateRecord()) && $aff->id)
			{	echo '<div id="affShareContainer"><a onclick="AffSharePopUpOpen();">Share with your friends</a><div class="clear"></div><div id="affSharePopup">', $aff->SharePopupList(), '</div></div>';
			} else
			{	echo '<a href="', $_SERVER["SCRIPT_NAME"], '?create_aff=1">Join the referral scheme</a>';
			}
		}
		return ob_get_clean();
	} // end of fn PageHeaderRightContent
	
} // end of defn OrdersPage

$page = new MyReferralsPage();
$page->Page();
?>
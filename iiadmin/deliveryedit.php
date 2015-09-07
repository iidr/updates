<?php
include_once('sitedef.php');

class DelOptionEditPage extends AdminDelOptionsPage
{	private $deloption;

	public function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	public function DelOptionsLoggedInConstruct()
	{	parent::DelOptionsLoggedInConstruct();
		
		$this->deloption = new AdminDeliveryOption($_GET['id']);
		
		if (isset($_POST['title']))
		{	$saved = $this->deloption->Save($_POST);
			$this->successmessage = $saved['successmessage'];
			$this->failmessage = $saved['failmessage'];
		}
		
		if ($this->deloption->id && $_GET['delete'] && $_GET['confirm'])
		{	if ($this->deloption->Delete())
			{	header('location: delivery.php');
				exit;
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}
		
		$this->breadcrumbs->AddCrumb('deliveryedit.php?id=' . (int)$this->deloption->id, $this->deloption->id ? $this->InputSafeString($this->deloption->details['title'] . ' [' . $this->deloption->GetRegionName() . ']') : 'New delivery option');
	} // end of fn DelOptionsLoggedInConstruct
	
	public function DelOptionsBody()
	{	echo $this->deloption->InputForm();
	} // end of fn DelOptionsBody
	
} // end of defn DelOptionEditPage

$page = new DelOptionEditPage();
$page->Page();
?>
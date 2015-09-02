<?php 
require_once('init.php');

class AjaxReferrals extends AccountPage
{	
	function __construct()
	{	parent::__construct();
	} // end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct();
		
		switch($_GET['action'])
		{	case 'sharepopupfill':
				switch ($_GET['filltype'])
				{	case 'email':
						echo $this->user->GetAffilateRecord()->SharePopupEmailForm();
						break;
					case 'sharelink':
						echo $this->user->GetAffilateRecord()->SharePopupLinkForm();
						break;
					case 'list':
					default:
						echo $this->user->GetAffilateRecord()->SharePopupList();
				}
				break;
			case 'emailsend':
				$alreadyRegistered = $this->user->GetByEmail($_POST['email']);
				
				if(count($alreadyRegistered)>0){
					echo '<div class="spListFailMessage">Your friend is already registered with us.</div>', $this->user->GetAffilateRecord()->SharePopupEmailForm($_POST);
				}else{
					$referafriend 			= new ReferAFriend();
					$aff 					= $this->user->GetAffilateRecord();
					
					$_POST['referemail'] 	= $_POST['email'];
					$_POST['refername']		= 'N/A';
					$_POST['refermessage']	= $_POST['message'];
					$_POST['trackcode']		= $aff->details['affcode'];
					
					$save = $referafriend->Create($this->user, $_POST);
					
					if ($save['successmessage'])
					{	echo $aff->ShareListBackLink(), '<div class="spListSuccessMessage">', $save['successmessage'], '</div>';
					} else
					{	if ($save['failmessage'])
						{	echo '<div class="spListFailMessage">', $save['failmessage'], '</div>', $aff->SharePopupEmailForm($_POST);
						}
					}
				}
				break;
		}
		
	} // end of fn LoggedInConstruct
	
} // end of defn AjaxReferrals

$page = new AjaxReferrals();
?>
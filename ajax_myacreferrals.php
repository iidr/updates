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
				if (!is_a($this->user, 'Student')){	
					$this->user = new Student($this->user);
				}
				
				if(!$referrerid = (int)$this->user->id){	
					echo '<div class="spListFailMessage">Referrer not found</div>',$aff->SharePopupEmailForm($_POST);				
				}elseif(!$this->user->CanSendReferral()){	
					echo '<div class="spListFailMessage">You must be over 18 to participate in the Refer-a-Friend scheme.</div>',$aff->SharePopupEmailForm($_POST);
				}else{		
					$friendsEMAILS  = $knownEmails = $errorMsgs = $knownMsgs = $filterEmails = array();
					$combinedEmails	= trim($_POST['email']);
					$combinedEmails	= str_replace(' ',',',$combinedEmails);
					$friendsEMAILS  = explode(',',$combinedEmails);
					$sendCount		= $errorCount	= 0;
					$respMessage	= '';
					$referafriend 	= new ReferAFriend();
					$aff 			= $this->user->GetAffilateRecord();
					
					foreach($friendsEMAILS as $key=>$email){
						$email = trim($email);
						if($email!='' && $this->ValidEMail($email)){
							$alreadyRegistered = $this->user->GetByEmail($email);
							
							if(count($alreadyRegistered)>0){
								$knownEmails[] = $email;
							}elseif($referafriend->AlreadyReferred($email)){	
								$knownEmails[] = $email;
							}else{	
								$data['referrerid'] 	= $referrerid;								
								$data['referemail'] 	= $email;
								$data['refername']		= 'N/A';
								$data['refermessage']	= trim($_POST['message']);
								$data['trackcode']		= $aff->details['affcode'];
								
								$save = $referafriend->createReferral($data);
								
								if($save['successmessage']){
									$sendCount++;	
								}elseif($save['failmessage']){
									$errorCount++;
									$errorMsgs[] = $save['failmessage'];
								}
							}
						}else{
							$errorCount++;
						}
					}
					
					if($errorCount>0 && count($errorMsgs)>0){
						echo '<div class="spListFailMessage">',implode('<br />',$errorMsgs),'</div>',$aff->SharePopupEmailForm($_POST);	
					}elseif($sendCount>0){
						$respMessage = '<div class="spListSuccessMessage">Thank you for recommending us</div>';
						
						if(count($knownEmails)>0){
							$respMessage .= '<div class="spListFailMessage">However your Following friend(s) already know about us.<br />'.implode('<br />',$knownEmails).'</div>';
						}
						echo $aff->ShareListBackLink(), $respMessage;
					}elseif(count($knownEmails)>0){
						echo $respMessage = '<div class="spListFailMessage">Your Following friend(s) already know about us.<br />',implode('<br />',$knownEmails),'</div>',$aff->SharePopupEmailForm($_POST);
					}else{
						echo '<div class="spListFailMessage">You must give your friend\'s email</div>',$aff->SharePopupEmailForm($_POST);
					}
				}
				break;
		}
		
	} // end of fn LoggedInConstruct
	
} // end of defn AjaxReferrals

$page = new AjaxReferrals();
?>
<?php
class ReferAFriend extends BlankItem
{	
	public function __construct($id = null)
	{	parent::__construct($id, 'referafriend', 'rfid');
	} // fn __construct
	
	public function GetByReferredID($regsid = 0)
	{	if ($regsid = (int)$regsid)
		{	$sql = 'SELECT * FROM referafriend WHERE regsid=' . $regsid;
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
					return $this->id;
				}
			}
		}
	} // end of fn GetByReferredID
	
	public function GetRewards()
	{	$rewards = array();
		$sql = 'SELECT * FROM referrewards WHERE rfid=' . (int)$this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$rewards[$row['rrid']] = $row;
			}
		}
		return $rewards;
	} // end of fn GetRewards
	
	public function GetRewardForUser($userid = 0)
	{	foreach ($this->GetRewards() as $reward)
		{	if ($reward['sid'] == $userid)
			{	return $reward;
			}
		}
		return false;
	} // end of fn GetRewardsForUser
	
	public function Create($referrer = 0, $data = array())
	{	$fail = array();
		$success = array();
		$fields = array('refertime="' . $this->datefn->SQLDateTime() . '"');
		
		if (!is_a($referrer, 'Student'))
		{	$referrer = new Student($referrer);
		}
		
		// check referrer is over 18
		if (!$referrer->CanSendReferral())
		{	$fail[] = 'You must be over 18 to participate in the Refer-a-Friend scheme';
		}
		
		if ($referrerid = (int)$referrer->id)
		{	$fields[] = 'referrer=' . $referrerid;
		} else
		{	$fail[] = 'Referrer not found';
		}
		
		if ($this->ValidEMail($referemail = $data['referemail']))
		{	if($this->AlreadyReferred($referemail))
			{	$fail[] = 'Your friend already knows about us';
			} else
			{	$fields[] = 'referemail="' . $referemail . '"';
			}
		} else
		{	$fail[] = 'You must give a valid email address for your friend';
		}
		
		if ($refername = $this->SQLSafe($data['refername']))
		{	$fields[] = 'refername="' . $refername . '"';
		} else
		{	$fail[] = 'You must give your friend\'s name';
		}
		$fields[] = 'refermessage="' . $this->SQLSafe($data['refermessage']) . '"';
		
		if (!$fail){
			if($data['trackcode']==''){
				do{
					$trackcode = $this->ConfirmCode(10, false);
				}while ($this->TrackCodeUsed($trackcode));
			}else{
				$trackcode = trim($data['trackcode']);
			}
			
			$fields[] = 'trackcode="' . $trackcode . '"';
			
			$sql = 'INSERT INTO referafriend SET ' . implode(', ', $fields);
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows() && ($id = $this->db->InsertID()))
				{	$this->Get($id);
					if ($this->SendReferral())
					{	$success[] = 'Thank you for recommending us';
					}
				} else
				{	$fail[] = 'Referral failed';
				}
			} else
			{	$fail[] = 'Referral has failed';
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Create
	
	public function createReferral($data){
		$fail = array();
		$success = array();
		$fields = array('refertime="' . $this->datefn->SQLDateTime() . '"');
		
		$fields[] 	= 'referrer='.$data['referrerid'];		
		$referemail = trim($data['referemail']);
		$fields[] 	= 'referemail="' . $referemail . '"';		
		$refername 	= $this->SQLSafe($data['refername']);
		$fields[] 	= 'refername="' . $refername . '"';
		$fields[] 	= 'refermessage="' . $this->SQLSafe($data['refermessage']) . '"';
		
		if($data['trackcode']==''){
			do{
				$trackcode = $this->ConfirmCode(10, false);
			}while ($this->TrackCodeUsed($trackcode));
		}else{
			$trackcode = trim($data['trackcode']);
		}
		
		$fields[] = 'trackcode="' . $trackcode . '"';
		
		$sql = 'INSERT INTO referafriend SET ' . implode(', ', $fields);
		
		if($result = $this->db->Query($sql)){	
			if ($this->db->AffectedRows() && ($id = $this->db->InsertID())){	
				$this->Get($id);
				if($this->SendReferral($referemail,$data['refermessage'],$refername)){	
					$success[] = 'Thank you for recommending us';
				}
			}else{	
				$fail[] = 'Referral failed';
			}
		}else{	
			$fail[] = 'Referral has failed';
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
	}
	
	public function TrackCodeUsed($trackcode = '')
	{	$sql = 'SELECT rfid FROM referafriend WHERE trackcode="' . $trackcode . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['rfid'];
			}
		}
		return false;
	} // end of fn TrackCodeUsed
	
	public function AlreadyReferred($email = '')
	{	// first check users
		$sql = 'SELECT userid FROM students WHERE username="' . ($username = $this->SQLSafe($email)) . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return true;
			}
		}
		// first check referrrals
		$sql = 'SELECT rfid FROM referafriend WHERE referemail="' . $username . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return true;
			}
		}
		return false;
	} // end of fn AlreadyReferred
	
	public function SendReferral($email = '', $message = '',$name = '')
	{	if (($referrer = new Student($this->details['referrer'])) && $referrer->id)
		{	$mailfields = array();
			$referToEmail	= ($email!='')?$email:$this->details['referemail'];
			$referToName	= ($name!='')?$name:$this->details['refername'];
			$referToMessage	= ($message!='')?$message:stripslashes($this->details['refermessage']);
			
			$mailfields['username'] = $referrer->details['username'];
			$mailfields['firstname'] = $referrer->details['firstname'];
			$mailfields['surname'] = $referrer->details['surname'];
			$mailfields['referral_message'] = $referToMessage;
			$mailfields['referral_message_html'] = nl2br($this->InputSafeString($referToMessage));
			$mailfields['referred_email'] = $referToEmail;
			$mailfields['referred_name'] = $referToName;
			$mailfields['site_url'] = $this->link->GetLink() . '?refertrack=' . $this->details['trackcode'];
			$mailfields['site_link_html'] = '<a href="' . $mailfields['site_url'] . '">visit us here</a>';
			$mailtemplate = new MailTemplate('refer_a_friend');
			$mail = new HTMLMail();
			$mail->SetFrom($referrer->details['username'], $referrer->GetName());
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($this->details['referemail'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	return true;
			}
		}
	} // end of fn SendReferral
	
	public function SendRewardToReferrer($student, $reward_amount = 0, $reward_expires = '')
	{	if (($referrer = new Student($this->details['referrer'])) && $referrer->id)
		{	$mailfields = array();
			$mailfields['firstname'] = $referrer->details['firstname'];
			$mailfields['surname'] = $referrer->details['surname'];
			$mailfields['referred_email'] = $this->details['referemail'];
			$mailfields['referred_name'] = $this->details['refername'];
			$mailfields['site_url'] = $this->link->GetLink();
			$mailfields['site_link_html'] = '<a href="' . $mailfields['site_url'] . '">visit IIDR</a>';
			$mailfields['reward_amount'] = number_format($reward_amount, 2);
			$mailfields['reward_expires'] = date('j M Y', strtotime($reward_expires));
			$mailtemplate = new MailTemplate('refer_a_friend_reward_to_referrer');
			$mail = new HTMLMail();
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($referrer->details['username'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	return true;
			}
		}
	} // end of fn SendRewardToReferrer
	
	public function SendRewardToReferral($student, $reward_amount = 0, $reward_expires = '')
	{	if (($referrer = new Student($this->details['referrer'])) && $referrer->id)
		{	$mailfields = array();
			$mailfields['firstname'] = $student->details['firstname'];
			$mailfields['surname'] = $student->details['surname'];
			$mailfields['referrer_firstname'] = $referrer->details['firstname'];
			$mailfields['referrer_surname'] = $referrer->details['surname'];
			$mailfields['site_url'] = $this->link->GetLink();
			$mailfields['site_link_html'] = '<a href="' . $mailfields['site_url'] . '">visit IIDR</a>';
			$mailfields['reward_amount'] = number_format($reward_amount, 2);
			$mailfields['reward_expires'] = date('j M Y', strtotime($reward_expires));
			$mailtemplate = new MailTemplate('refer_a_friend_reward_to_referrer');
			$mail = new HTMLMail();
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($student->details['username'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	return true;
			}
		}
	} // end of fn SendRewardToReferral
	
	public function InputForm($data = array())
	{	ob_start();
		if ($this->id)
		{	echo '<a href="', $_SERVER['SCRIPT_NAME'], '">Tell another friend about IIDR</a>';
		} else
		{
			echo '<form id="referForm" action="', $_SERVER['SCRIPT_NAME'], '" method="post"><p><label>Your friend\'s name</label><input type="text" name="refername" value="', $this->InputSafeString($data['refername']), '" /></p><p><label>Your friend\'s email address</label><input type="text" name="referemail" value="', $this->InputSafeString($data['referemail']), '" /></p><p><label>A message for your friend</label><textarea name="refermessage"></textarea></p><p><label>&nbsp;</label><input type="submit" value="Tell your friend about IIDR" /></p></form>';
		}
		return ob_get_clean();
	} // end of fn InputForm
	
	public function CreateRewards()
	{	$over18 = $this->datefn->SQLDate(strtotime('-18 years'));
		if (!$this->GetRewards() 
				&& ($referral_student = new Student((int)$this->details['regsid'])) && (int)$referral_student->details['dob'] && ($referral_student->details['dob'] <= $over18) 
				&& ($referrer_student = new Student((int)$this->details['referrer'])) && (int)$referrer_student->details['dob'] && ($referrer_student->details['dob'] <= $over18))
		{	$refer_months = (int)$this->GetParameter('refer_months');
			$refer_amount = (int)$this->GetParameter('refer_amount');
		
			// create reward for referred user
			if (!$referral_student->ReferralsOverLimit())
			{	// check for subscription and alter date if appropriate
				$sql = 'INSERT INTO referrewards SET rfid=' . $this->id . ', sid=' . $referral_student->id . ', referrer=0, created="' . $this->datefn->SQLDateTime() . '", expires="' . ($expires = date('Y-m-t', strtotime($referral_student->LastSubscribeDate() . ' +' . $refer_months . ' months'))) . '", amount=' . $refer_amount;
				$this->db->Query($sql);
				$this->SendRewardToReferrer($referral_student, $refer_amount, $expires);
			}
			// create reward for referrer
			if (!$referrer_student->ReferralsOverLimit())
			{	// check for subscription and alter date if appropriate
				$sql = 'INSERT INTO referrewards SET rfid=' . $this->id . ', sid=' . $referrer_student->id . ', referrer=1, created="' . $this->datefn->SQLDateTime() . '", expires="' . ($expires = date('Y-m-t', strtotime($referrer_student->LastSubscribeDate() . ' +' . $refer_months . ' months'))) . '", amount=' . $refer_amount;
				$this->db->Query($sql);
				$this->SendRewardToReferral($referrer_student, $refer_amount, $expires);
			}
		}
	} // end of fn CreateRewards
	
} // end of class defn ReferAFriend
?>
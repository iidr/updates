<?php
class AffiliateStudent extends BlankItem
{	
	public function __construct($id = null)
	{	parent::__construct($id, 'affiliateshare', 'asid');
	} // fn __construct
	
	public function GetByStudentID($sid = 0)
	{	if ($sid = (int)$sid)
		{	$sql = 'SELECT * FROM affiliateshare WHERE sid=' . $sid;
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
					return $this->id;
				}
			}
		}
	} // end of fn GetByStudentID
	
	public function ShareLink()
	{	return CIT_FULLLINK . 'shared/' . $this->details['affcode'] . '/';
	} // end of fn ShareLink
	
	public function SharePopupList()
	{	ob_start();
		echo '<ul id="spList"><li id="spListEmail"><a onclick="AffSharePopUpFill(\'email\');">Email</a></li><li id="spListFB">', $this->FBShareButton(), '</li><li id="spListTwitter"><a href="https://twitter.com/intent/tweet?text=Visit%20IIDR%20here&url=', $this->ShareLink(), '">Share IIDR on Twitter</a></li><li id="spListLink"><a onclick="AffSharePopUpFill(\'sharelink\');">Get link</a></li></ul>';
		return ob_get_clean();
	} // end of fn SharePopupList
	
	public function ShareListBackLink()
	{	ob_start();
		echo '<p><a onclick="AffSharePopUpFill();">&laquo; back</a></p>';
		return ob_get_clean();
	} // end of fn ShareListBackLink
	
	public function SharePopupEmailForm($data= array())
	{	ob_start();
		//echo $this->ShareListBackLink(), '<form class="spEmailForm" onsubmit="AffShareSendEmail(); return false;"><p class="spEmailFormHeader">Fill in your friend\'s email address and we will send them an email to tell them about IIDR</p><p><input type="text" id="spemailinput" value="', $this->InputSafeString($data['email']), '" /></p><p>A message for your friend</p><p><textarea id="spemailtext">', $this->InputSafeString($data['message']), '</textarea></p><p><input type="submit" value="Send" /></p></form>';
		echo $this->ShareListBackLink(), '<form class="spEmailForm" onsubmit="AffShareSendEmail(); return false;"><p class="spEmailFormHeader">Enter your friends\' email addresses separeted by commas or spaces</p><p><input type="text" id="spemailinput" value="', $this->InputSafeString($data['email']), '" /></p><p>You can use this suggested message or, better yet, write your own below:</p><p><textarea id="spemailtext">', ($this->InputSafeString($data['message'])!='')?$this->InputSafeString($data['message']):$this->GetParameter("share_email_txt"), '</textarea></p><p><input type="submit" value="Send" /></p></form>';
		return ob_get_clean();
	} // end of fn SharePopupEmailForm
	
	public function SendToEmail($data = array())
	{	$fail = array();
		$success = array();
		
		if ($this->ValidEMail($data['email']))
		{	
			$this->SendReferral($data['email'], $data['message']);
			
			$success[] = 'Your friend has been emailed';
			$success[] = 'Thank you for sharing IIDR';
		} else
		{	$fail[] = 'This email address is invalid, please check your typing';
		}
	
		return array('fail'=>implode(', ', $fail), 'success'=>implode('<br />', $success));
	} // end of fn SendToEmail
	
	public function SendReferral($email = '', $message = '')
	{	if (($referrer = new Student($this->details['sid'])) && $referrer->id)
		{	$mailfields = array();
			$mailfields['username'] = $referrer->details['username'];
			$mailfields['firstname'] = $referrer->details['firstname'];
			$mailfields['surname'] = $referrer->details['surname'];
			$mailfields['site_url'] = $this->ShareLink();
			$mailfields['referral_message'] = stripslashes($message);
			$mailfields['referral_message_html'] = nl2br($this->InputSafeString($message));
			$mailfields['site_link_html'] = '<a href="' . $mailfields['site_url'] . '">visit us here</a>';
			$mailtemplate = new MailTemplate('refer_a_friend');
			$mail = new HTMLMail();
			$mail->SetFrom($referrer->details['username'], $referrer->GetName());
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($email, $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	return true;
			}
		}
	} // end of fn SendReferral
	
	public function SharePopupLinkForm()
	{	ob_start();
		echo $this->ShareListBackLink(), '<p>click and copy the link below</p><form id="splLinkSelector" onsubmit="return false;"><input onclick="$(this).select();" type="text" value="', $this->ShareLink(), '" /></form>';
		return ob_get_clean();
	} // end of fn SharePopupLinkForm
	
	public function FBShareButton()
	{	ob_start();
		echo '<a target="_blank" href="http://www.facebook.com/sharer/sharer.php?u=', $this->ShareLink(), '">Share on Facebook</a>';
		return ob_get_clean();
	} // end of fn FBShareButton
	
	public function CreateRewardFor($sid = 0)
	{	// check reward nor already created for user
		if (!$this->GetRewardsForUser($sid) && ($referral_student = new Student((int)$sid)) && $referral_student->CanSendReferral() && ($referrer_student = new Student((int)$this->details['sid'])))
		{	$refer_months = (int)$this->GetParameter('refer_months');
			$refer_amount = (int)$this->GetParameter('refer_amount');
		
			// create reward for referred user
			if (!$referral_student->ReferralsOverLimit())
			{	// check for subscription and alter date if appropriate
				$sql = 'INSERT INTO affrewards SET asid=' . $this->id . ', sid=' . $referral_student->id . ', created="' . $this->datefn->SQLDateTime() . '", expires="' . ($expires = date('Y-m-t', strtotime($referral_student->LastSubscribeDate() . ' +' . $refer_months . ' months'))) . '", amount=' . $refer_amount;
				$this->db->Query($sql);
				$this->SendRewardToReferrer($referral_student, $refer_amount, $expires);
			}
			// create reward for referrer
			if (!$referrer_student->ReferralsOverLimit())
			{	// check for subscription and alter date if appropriate
				$sql = 'INSERT INTO affrewards SET asid=' . $this->id . ', sid=' . $referrer_student->id . ', created="' . $this->datefn->SQLDateTime() . '", expires="' . ($expires = date('Y-m-t', strtotime($referrer_student->LastSubscribeDate() . ' +' . $refer_months . ' months'))) . '", amount=' . $refer_amount;
				$this->db->Query($sql);
				$this->SendRewardToReferral($referrer_student, $refer_amount, $expires);
			}
		}
	} // end of fn CreateRewardFor
	
	public function GetRewards()
	{	$rewards = array();
		$sql = 'SELECT * FROM affrewards WHERE asid=' . (int)$this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$rewards[$row['awid']] = $row;
			}
		}
		return $rewards;
	} // end of fn GetRewards
	
	public function GetRewardsForUser($userid = 0)
	{	$rewards = array();
		$sql = 'SELECT * FROM affrewards WHERE asid=' . (int)$this->id . ' AND sid=' . (int)$userid;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$rewards[$row['awid']] = $row;
			}
		}
		return $rewards;
	} // end of fn GetRewardsForUser
	
	public function Create($student = 0)
	{	$fail = array();
		$success = array();
		$fields = array('created="' . $this->datefn->SQLDateTime() . '"');
		
		if (!is_a($student, 'Student'))
		{	$student = new Student($student);
		}
		
		// check referrer is over 18
		if (!$student->CanSendReferral())
		{	$fail[] = 'You must be over 18 to participate in the Refer-a-Friend scheme';
		}
		
		if ($sid = (int)$student->id)
		{	$fields[] = 'sid=' . $sid;
			if ($this->GetByStudentID($sid))
			{	$fail[] = 'You have already signed up to the affiliate scheme';
			}
		} else
		{	$fail[] = 'Student record not found';
		}
		
		if (!$fail)
		{
			do
			{	$affcode = $this->ConfirmCode(10, false);
			} while ($this->TrackCodeUsed($affcode));
			
			$fields[] = 'affcode="' . $affcode . '"';
			
			$sql = 'INSERT INTO affiliateshare SET ' . implode(', ', $fields);
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows() && ($id = $this->db->InsertID()))
				{	$this->Get($id);
					$success[] = 'Your share link has been created';
				} else
				{	$fail[] = 'Referral failed';
				}
			} else
			{	$fail[] = 'Referral has failed';
			}
		}
		
		return array('fail'=>implode(', ', $fail), 'success'=>implode(', ', $success));
		
	} // end of fn Create
	
	public function TrackCodeUsed($affcode = '')
	{	$sql = 'SELECT asid FROM affiliateshare WHERE affcode="' . $affcode . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['asid'];
			}
		}
		return false;
	} // end of fn TrackCodeUsed
	
	public function SendRewardToReferrer($student, $reward_amount = 0, $reward_expires = '')
	{	if (($referrer = new Student($this->details['sid'])) && $referrer->id)
		{	$mailfields = array();
			$mailfields['firstname'] = $referrer->details['firstname'];
			$mailfields['surname'] = $referrer->details['surname'];
			$mailfields['referred_email'] = $this->details['referemail'];
			$mailfields['referred_name'] = $this->details['refername'];
			$mailfields['site_url'] = $this->link->GetLink();
			$mailfields['site_link_html'] = '<a href="' . $mailfields['site_url'] . '">visit IIDR</a>';
			$mailfields['reward_amount'] = number_format($reward_amount, 2);
			$mailfields['reward_expires'] = date('j M Y', strtotime($reward_expires));
			$mailtemplate = new MailTemplate('affiliate_reward_to_referrer');
			$mail = new HTMLMail();
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($referrer->details['username'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	return true;
			}
		}
	} // end of fn SendRewardToReferrer
	
	public function SendRewardToReferral($student, $reward_amount = 0, $reward_expires = '')
	{	if (($referrer = new Student($this->details['sid'])) && $referrer->id)
		{	$mailfields = array();
			$mailfields['firstname'] = $student->details['firstname'];
			$mailfields['surname'] = $student->details['surname'];
			$mailfields['referrer_firstname'] = $referrer->details['firstname'];
			$mailfields['referrer_surname'] = $referrer->details['surname'];
			$mailfields['site_url'] = $this->link->GetLink();
			$mailfields['site_link_html'] = '<a href="' . $mailfields['site_url'] . '">visit IIDR</a>';
			$mailfields['reward_amount'] = number_format($reward_amount, 2);
			$mailfields['reward_expires'] = date('j M Y', strtotime($reward_expires));
			$mailtemplate = new MailTemplate('affiliate_reward_to_referred');
			$mail = new HTMLMail();
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($student->details['username'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	return true;
			}
		}
	} // end of fn SendRewardToReferral
	
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
	
} // end of class defn AffiliateStudent
?>
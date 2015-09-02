<?php
class Student extends Base
{	public $details = array();
	public $id = 0;
	public $bookings_perpage = 2;
	public $orders_perpage = 10;
	private $captcha = false;
	
	public function __construct($id = 0)
	{	parent::__construct();
		$this->orders_perpage = $this->GetParameter('pag_myac_orders');
		$this->bookings_perpage = $this->GetParameter('pag_myac_bookings');
		$this->Get($id);
	} // fn __construct
	
	public function Reset()
	{	$this->details = array();
		$this->id = 0;
	} // end of fn Reset
	
	public function Get($id = 0)
	{	$this->Reset();
		if (is_array($id))
		{	$this->details = $id;
			$this->id = $id['userid'];
		} else
		{	if ($result = $this->db->Query('SELECT * FROM students WHERE userid=' . (int)$id))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
				}
			}
		}
		
	} // end of fn Get
	
	public function GetByEmail($email = '')
	{	
		$row =  array();
		if($email != ''){	
			$selQeuery = 'SELECT * FROM students WHERE username="' . $email.'"';
			if ($result = $this->db->Query($selQeuery) && $this->db->NumRows($result)>0){	
				$row = $this->db->FetchArray($result);
			}
		}
		return $row;		
	} // end of fn Get
	
	public function LogIn($username = '', $password = '')
	{	
		if (!$this->id)
		{	
			$sql = 'SELECT * FROM students WHERE username="' . $this->SQLSafe($username) . '" AND upassword=MD5("' . $this->SQLSafe($password) . '")';
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
				}
			}
			return $this->id;
		}
	} // end of fn LogIn
	
	public function LogOut()
	{	$this->Reset();
		$_SESSION['stuserid'] = 0;
	} // end of fn LogOut
	
	public function SaveDetails($data = array(), $adminactions = false)
	{	//print_r($data);
		$fields = array();
		$fail = array();
		$success = array();
		$admin_actions = array();
		
		if (!$this->id)
		{	$fields[] = 'regdate="' . $this->datefn->SQLDateTime() . '"';
		}
		
		if ($data['pword'] || !$this->id)
		{	if ($data['pword'] !== $data['rtpword'])
			{	$fail[] = 'Passwords do not match';
			} else
			{	if ($this->AcceptablePW($data['pword']))
				{	$fields[] = 'upassword=MD5("' . $data['pword'] . '")';
					if ($adminactions)
					{	$admin_actions[] = array('action'=>'Password changed');
					}
				} else
				{	$fail[]= 'Password not acceptable';
				}
			}
		}
		
		if (isset($data['username']) || !$this->id)
		{	if (($data['username'] != $this->details['username']) || !$this->id) // i.e. only if changed, allowed to keep existing invalid username
			{	if ($this->ValidEMail($username = $data['username']))
				{	
					if ($this->GetStudentFromEmail($username, 'userid'))
					{	$fail[] = 'Email address is already registered';
					} else
					{	$fields[] = 'username="' . $username . '"';
						if ($adminactions && ($data['username'] != $this->details['username']))
						{	$admin_actions[] = array('action'=>'Email / username', 'actionfrom'=>$this->details['username'], 'actionto'=>$data['username']);
						}
					}
					
				} else
				{	$fail[] = 'Invalid Email address';
				}
			}
		}
		
		if (isset($data['firstname']) || !$this->id)
		{	if (($firstname = $this->SQLSafe($data['firstname'])) && ($surname = $this->SQLSafe($data['surname'])))
			{	$fields[] = 'surname="' . $surname . '"';
				if ($adminactions && ($data['surname'] != $this->details['surname']))
				{	$admin_actions[] = array('action'=>'Surname', 'actionfrom'=>$this->details['surname'], 'actionto'=>$data['surname']);
				}
				$fields[] = 'firstname="' . $firstname . '"';
				if ($adminactions && ($data['firstname'] != $this->details['firstname']))
				{	$admin_actions[] = array('action'=>'First name', 'actionfrom'=>$this->details['firstname'], 'actionto'=>$data['firstname']);
				}
			} else
			{	$fail[] = 'Please enter your name';
			}
		}
		
		if (isset($data['address1']) || !$this->id)
		{	if ($address1 = $this->SQLSafe($data['address1']))
			{	$fields[] = 'address1="' . $address1 . '"';
				if ($adminactions && ($data['address1'] != $this->details['address1']))
				{	$admin_actions[] = array('action'=>'Address 1', 'actionfrom'=>$this->details['address1'], 'actionto'=>$data['address1']);
				}
			} else
			{	$fail[] = 'Please enter your address';
			}
		}
		
		if (isset($data['address2']) || !$this->id)
		{	$address2 = $this->SQLSafe($data['address2']);
			$fields[] = 'address2="' . $address2 . '"';
			if ($adminactions && ($data['address2'] != $this->details['address2']))
			{	$admin_actions[] = array('action'=>'Address 2', 'actionfrom'=>$this->details['address2'], 'actionto'=>$data['address2']);
			}
		}
		
		if (isset($data['address3']) || !$this->id)
		{	$address3 = $this->SQLSafe($data['address3']);
			$fields[] = 'address3="' . $address3 . '"';
			if ($adminactions && ($data['address3'] != $this->details['address3']))
			{	$admin_actions[] = array('action'=>'Address 3', 'actionfrom'=>$this->details['address3'], 'actionto'=>$data['address3']);
			}
		}
		

		if (isset($data['country']) || !$this->id)
		{	if ($country = $this->SQLSafe($data['country']))
			{	$fields[] = 'country="' . $country . '"';
				if ($adminactions && ($data['country'] != $this->details['country']))
				{	$admin_actions[] = array('action'=>'Country', 'actionfrom'=>$this->details['country'], 'actionto'=>$data['country'], 'actiontype'=>'link', 'linkmask'=>'ctryedit.php?ctry={linkid}');
				}
			} else
			{	$fail[] = 'Please enter your country';
			}
		}
		
		if (($morf = $data['morf']) && ($morf == 'M' || $morf == 'F'))
		{	$fields[] = 'morf="' . $morf . '"';
			if ($adminactions && ($data['morf'] != $this->details['morf']))
			{	$admin_actions[] = array('action'=>'Male/female', 'actionfrom'=>$this->details['morf'], 'actionto'=>$data['morf']);
			}
		} else
		{	$fail[] = 'Please select your gender';
		}
		
		if (isset($data['phone']) || !$this->id)
		{	$phone = $this->SQLSafe($data['phone']);
			$phone2 = $this->SQLSafe($data['phone2']);
			if ($phone || $phone2)
			{	$fields[] = 'phone="' . $phone . '"';
				if ($adminactions && ($data['phone'] != $this->details['phone']))
				{	$admin_actions[] = array('action'=>'Phone1', 'actionfrom'=>$this->details['phone'], 'actionto'=>$data['phone']);
				}
				$fields[] = 'phone2="' . $phone2 . '"';
				if ($adminactions && ($data['phone2'] != $this->details['phone2']))
				{	$admin_actions[] = array('action'=>'Phone2', 'actionfrom'=>$this->details['phone2'], 'actionto'=>$data['phone2']);
				}
			} else
			{	$fail[] = 'Please enter your phone number';
			}
		}
		
		if (isset($data['ddob']) || !$this->id)
		{	if (($d = (int)$data['ddob']) && ($m = (int)$data['mdob']) && ($y = (int)$data['ydob']))
			{	$dob = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
				$fields[] = 'dob="' . $dob . '"';
				if ($adminactions && ($dob != $this->details['dob']))
				{	$admin_actions[] = array('action'=>'DoB', 'actionfrom'=>$this->details['dob'], 'actionto'=>$dob, 'actiontype'=>'date');
				}
			} else
			{	$fail[] = 'Please enter your date of birth';
			}
		}
		
		if (isset($data['city']) || !$this->id)
		{	if ($city= $this->SQLSafe($data['city']))
			{	$fields[] = 'city="' . $city . '"';
				if ($adminactions && ($data['city'] != $this->details['city']))
				{	$admin_actions[] = array('action'=>'City', 'actionfrom'=>$this->details['city'], 'actionto'=>$data['city']);
				}
			} else
			{	$fail[] = 'Please enter your city';
			}
		}
		
		if (isset($data['postcode']))
		{	if($postcode= $this->SQLSafe($data['postcode'])){
				$fields[] = 'postcode="' . $postcode . '"';
				if ($adminactions && ($data['postcode'] != $this->details['postcode']))
				{	$admin_actions[] = array('action'=>'Postcode', 'actionfrom'=>$this->details['postcode'], 'actionto'=>$data['postcode']);
				}
			}
			else
			{ $fail[] = 'Please enter your postcode';
			}
		}
		
		if (isset($data['howheard']) || !$this->id)
		{	if ($howheard = $this->SQLSafe($data['howheard']))
			{	$fields[] = 'howheard="' . $howheard . '"';
				if ($adminactions && ($data['howheard'] != $this->details['howheard']))
				{	$admin_actions[] = array('action'=>'How heard', 'actionfrom'=>$this->details['howheard'], 'actionto'=>$data['howheard']);
				}
			} else
			{	$fields[] = 'howheard=""';
				if ($adminactions && $this->details['howheard'])
				{	$admin_actions[] = array('action'=>'How heard', 'actionfrom'=>$this->details['howheard']);
				}
			}
		}
		
		if (!$adminactions)
		{	$tandc = ($data['tandc'] ? '1' : '0');
			$fields[] = 'tandc=' . $tandc;
			if (!$tandc)
			{	$fail[] = 'You must agree to the terms and conditions';
			}
		}
		
		if ($adminactions)
		{	$emailfailed = ($data['emailfailed'] ? '1' : '0');
			$fields[] = 'emailfailed=' . $emailfailed;
			$admin_actions[] = array('action'=>'Email marked as failed', 'actionfrom'=>$this->details['emailfailed'], 'actionto'=>$emailfailed, 'actiontype'=>'boolean');
		}
		
		$newsletter = ($data['newsletter'] ? '1' : '0');
		$fields[] = 'newsletter=' . $newsletter;
		if ($adminactions && ($newsletter != $this->details['newsletter']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['newsletter'], 'actionto'=>$newsletter, 'actiontype'=>'boolean');
		}
		
		if (isset($data['education']))
		{	
			$education = $this->SQLSafe($data['education']);
			$fields[] = 'education="' . $education . '"';
			if ($adminactions && ($data['education'] != $this->details['education']))
			{	$admin_actions[] = array('action'=>'Education', 'actionfrom'=>$this->details['education'], 'actionto'=>$data['education']);
			}
		}		

		if (isset($data['profession']))
		{	
			$profession = $this->SQLSafe($data['profession']);
			$fields[] = 'profession="' . $profession . '"';
			if ($adminactions && ($data['profession'] != $this->details['profession']))
			{	$admin_actions[] = array('action'=>'Profession', 'actionfrom'=>$this->details['profession'], 'actionto'=>$data['profession']);
			}
		}
		
		// check captcha
		if (!$this->id && !$fail)
		{	$captcha = $this->Captcha();
			if (!$captcha->VerifyInput())
			{	$fail[] = 'captcha code has not been entered correctly';
			}
		}
		
		if (!$fail || $this->id)
		{	$set = implode(', ', $fields);
			if ($this->id)
			{	$sql = 'UPDATE students SET ' . $set . ' WHERE userid=' . (int)$this->id;
			} else
			{	$sql = 'INSERT INTO students SET ' . $set;
			}
			if ($result = $this->db->Query($sql))
			{	//echo $sql;
				if ($this->db->AffectedRows())
				{	
					if ($this->id)
					{	if ($adminactions)
						{	$base_parameters = array('tablename'=>'students', 'tableid'=>$this->id, 'area'=>'users');
							if ($admin_actions)
							{	foreach ($admin_actions as $admin_action)
								{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
								}
							}
						}
						$success[] = 'Your details have been updated';
						$this->Get($this->id);
					} else
					{	$this->Get($this->db->InsertID());
						if ($this->id)
						{	$success[] = 'Your registration was successful';
							if ($adminactions)
							{	$this->RecordAdminAction(array('tablename'=>'students', 'tableid'=>$this->id, 'area'=>'users', 'action'=>'created'));
							} else
							{	// check for referrer track code
								$this->RecordRefererTrackCode();
							}
							$this->SendRegEmail();
						}
					}
				}
			//	if (SITE_TEST) $fail[] = $sql;
			} //else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';//if (SITE_TEST) $fail[] = $sql . "~~" . $this->db->Error();
		}
		
		return array('fail'=>implode(', ', $fail), 'success'=>implode(', ', $success));
		
	} // end of fn SaveDetails
	
	public function RecordRefererTrackCode()
	{	if ($trackcode = $_COOKIE['refertrack'])
		{	$sql = 'SELECT rfid FROM referafriend WHERE trackcode="' . $this->SQLSafe($trackcode) . '" AND regsid=0 AND referemail="' . $this->details['username'] . '"';
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	// then this has been found
					$sql = 'UPDATE referafriend SET regsid=' . $this->id . ' WHERE rfid=' . $row['rfid'];
					$this->db->Query($sql);
					return true;
				}
			}
		}
		if ($affcode = $_COOKIE['affcodetrack'])
		{	$sql = 'SELECT asid FROM affiliateshare WHERE affcode="' . $this->SQLSafe($affcode) . '"';
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	// then this has been found
					$sql = 'INSERT INTO affiliatereferred SET sid=' . $this->id . ', asid=' . $row['asid'] . ', created="' . $this->datefn->SQLDateTime() . '"';
					$this->db->Query($sql);
					return true;
				}
			}
		}
	} // end of fn RecordRefererTrackCode
	
	public function CreateFromAttendee($data = array(),$friendName='')
	{	
		if ($existing = $this->GetStudentFromEmail($data['email']))
		{	$this->Get($existing);
		} else
		{	$fields = array();
			$fields[] = 'regdate="' . $this->datefn->SQLDateTime() . '"';
			
			$pword = $this->ConfirmCode(8, false);
			$fields[] = 'upassword=MD5("' . $pword . '")';
			$fields[] = 'username="' . $this->SQLSafe($data['email']) . '"';
			$fields[] = 'surname="' . $this->SQLSafe($data['surname']) . '"';
			$fields[] = 'firstname="' . $this->SQLSafe($data['firstname']) . '"';
		
			$set = implode(', ', $fields);
			$sql = 'INSERT INTO students SET ' . $set;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->Get($this->db->InsertID());
					$this->SendCreatedRegEmail($pword,$friendName);
				}
			//	if (SITE_TEST) $fail[] = $sql;
			} //else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		return $this->id;
		
	} // end of fn CreateFromAttendee
	
	public function SendCreatedRegEmail($pword = '',$friendName=''){	
		if($this->ValidEmail($this->details['username'])){	
			$mailfields = array();
			$mailfields['password'] = $pword;
			$mailfields['username'] = $this->details['username'];
			$mailfields['firstname'] = $this->details['firstname'];
			$mailfields['surname'] = $this->details['surname'];
			$mailfields['site_url'] = $this->link->GetLink();
			$mailfields['friend_name'] = $friendName;
			$mailtemplate = new MailTemplate('attendee_reg_created');
			$mail = new HTMLMail();
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($this->details['username'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	//$this->MailSentRecord();
			}
			return;
		}
	} // end of fn SendCreatedRegEmail
	
	public function SendRegEmail()
	{	if ($this->ValidEmail($this->details["username"]))
		{	
			$mailfields = array();
			$mailfields['firstname'] = $this->details['firstname'];
			$mailfields['surname'] = $this->details['surname'];
			$mailfields['site_url'] = $this->link->GetLink();
			$mailtemplate = new MailTemplate('registration');
			$mail = new HTMLMail();
			$mail->SetSubject($mailtemplate->details['subject']);
		
			if ($mail->Send($this->details['username'], $mailtemplate->BuildHTMLEmailText($mailfields), $mailtemplate->BuildHTMLPlainText($mailfields)))
			{	//$this->MailSentRecord();
			}
			return;
		}
	} // end of fn SendRegEmail
	
/*	public function RegisterForm($submit_page = "")
	{	if (!$submit_page) $submit_page = $_SERVER['SCRIPT_NAME'];
		class_exists('Form');
		ob_start();
		if (($d = (int)$_POST['ddob']) && ($m = (int)$_POST['mdob']) && ($y = (int)$_POST['ydob']))
		{	$dob = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		}
		echo "<form action='", $submit_page, "' method='post' class='form' id='register_form' />\n",
				"<div class='register_form_group'>\n",
					"<p><label>First name</label><input type='text' class='text' name='firstname' value='", $this->InputSafeString($_POST["firstname"]), "' /></p>\n",
					"<p><label>Surname</label><input type='text' class='text' name='surname' value='", $this->InputSafeString($_POST["surname"]), "' /></p>\n",
					$this->DOBInputField($dob),
					"<p><label>Male</label><input type='radio' class='radio' name='morf' value='M' ", $_POST["morf"] == "M" ? "checked='checked' " : "", "/>",
					"<br class='clear' /><label>Female</label><input type='radio' class='radio' name='morf' value='F' ", $_POST["morf"] == "F" ? "checked='checked' " : "", "/>",
					"</p>\n",
				"</div>\n",
				"<div class='register_form_group'>\n",
					"<p><label>Email</label><input type='text' class='text' name='username' value='", $this->InputSafeString($_POST["username"]), "' /></p>\n",
					"<p><label>Password</label><input type='password' class='text' name='pword' value='' /></p>\n",
					"<p><label>Confirm password</label><input type='password' class='text' name='rtpword' value='' /></p>\n",
				"</div>\n",
				"<div class='register_form_group'>\n",
					"<p><label>Address</label><input type='text' class='text' name='address1' value='", $this->InputSafeString($_POST["address1"]), "' /></p>\n",
					"<p><label>&nbsp;</label><input type='text' class='text' name='address2' value='", $this->InputSafeString($_POST["address2"]), "' /></p>\n",
					"<p><label>&nbsp;</label><input type='text' class='text' name='address3' value='", $this->InputSafeString($_POST["address3"]), "' /></p>\n",
					"<p><label>Town / City</label><input type='text' class='text' name='city' value='", $this->InputSafeString($_POST["city"]), "' /></p>\n",
					"<p><label>Postcode</label><input type='text' class='text' name='postcode' value='", $this->InputSafeString($_POST["postcode"]), "' /></p>\n",
					
					"<p><label>Phone</label><input type='text' class='text' name='phone' value='", $this->InputSafeString($_POST["phone"]), "' /></p>\n",
					"<p><label>Alternative Phone</label><input type='text' class='text' name='phone2' value='", $this->InputSafeString($_POST["phone2"]), "' /></p>\n",
				"</div>\n",
				"<div class='register_form_group'>\n",
					"<p><label>How did you hear about us?</label><input type='text' class='text' name='howheard' value='", $this->InputSafeString($_POST["howheard"]), "' /></p>\n",
					"<p class='t_and_c'><input type='checkbox' class='check' name='newsletter' value='1' ", ($_POST["newsletter"] || !$_POST) ? "checked='checked' " : "", "/>I would like to be kept informed about IIDR</p>\n", $this->TAndCCheckBox($_POST["tandc"], true),
				"</div>\n",
				"<p><input type='submit' class='submit' value='Register' /></p></form>\n";
		return ob_get_clean();
	} // end of fn RegisterForm*/
	
	public function TAndCCheckBox($confirmed = false, $force_checkbox = false)
	{	ob_start();
		if ($confirmed && !$force_checkbox)
		{	echo '<input type="hidden" name="tandc" value="1" />';
		} else
		{	$legals_page = new PageContent('terms-and-policies');
			echo '<div class="t_and_c clearfix" style="">I have read and agree to the <a href="', $legals_page->Link(), '" target="_blank" alt="', $this->InputSafeString($legals_page->details['pagetitle']), '" >terms and conditions</a> *<input type="checkbox" class="check" name="tandc" value="1" ', $_POST['tandc'] ? 'checked="checked" ' : '', ' required="required" /></div>';
		}
		return ob_get_clean();
	} // end of fn TAndCCheckBox
	
	public function RegisterForm1($submit_page = "", $pagecontent = '')
	{	if (!$submit_page) $submit_page = $_SERVER["SCRIPT_NAME"];
		class_exists("Form");
		$captcha = $this->Captcha();
		ob_start();
		if (($d = (int)$_POST["ddob"]) && ($m = (int)$_POST["mdob"]) && ($y = (int)$_POST["ydob"]))
		{	$dob = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		}
		echo "<form action='", $submit_page, "' method='post' class='form clearfix' id='register_form' />\n",
				"<p class='clearfix' style='padding-bottom: 40px;'>Please enter your details below to sign up</p>\n",
				"<div class='register_form_group clearfix' style='margin-right:20px!important;'>\n",
					"<div class='clearfix'><label>First name:*</label><input type='text' class='text' name='firstname' value='", $this->InputSafeString($_POST["firstname"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>Surname:*</label><input type='text' class='text' name='surname' value='", $this->InputSafeString($_POST["surname"]), "' required='required' /></div>\n",
					$this->DOBInputField($dob),
					"<div class='clearfix'><label>Gender:*</label><label class='no-width'>Male</label><input type='radio' class='radio' name='morf' value='M' ", $_POST["morf"] == "M" ? "checked='checked' " : "", "/>",
					"<label class='no-width'>Female</label><input type='radio' class='radio' name='morf' value='F' ", $_POST["morf"] == "F" ? "checked='checked' " : "", "/>",
					"</div>\n",
					"<div class='clearfix'><label>Email:*</label><input type='text' class='text' name='username' value='", $this->InputSafeString($_POST["username"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>Password:* <span style='font-size:9px; color:#6D6D6D;'>(8-20 chars)</span></label><input type='password' class='text' name='pword' value='' required='required' /></div>\n",
					"<div class='clearfix'><label>Confirm password:*</label><input type='password' class='text' name='rtpword' value='' required='required' /></div>\n",
					"<div class='clearfix'><label>Address:*</label><input type='text' class='text' name='address1' value='", $this->InputSafeString($_POST["address1"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>&nbsp;</label><input type='text' class='text' name='address2' value='", $this->InputSafeString($_POST["address2"]), "' /></div>\n",
					"<div class='clearfix'><label>&nbsp;</label><input type='text' class='text' name='address3' value='", $this->InputSafeString($_POST["address3"]), "' /></div>\n",
					"<div class='clearfix'><label>Town / City:*</label><input type='text' class='text' name='city' value='", $this->InputSafeString($_POST["city"]), "' required='required' /></div>\n",
				"</div>\n",
				"<div class='register_form_group clearfix'>\n",
					
					
					"<div class='clearfix'><label>Postcode:*</label><input type='text' class='text' name='postcode' value='", $this->InputSafeString($_POST["postcode"]), "' required='required' /></div>\n",	
					
					"<div class='clearfix'><label>Country:*</label><select name='country' class='select-full' required='required'>
						<option value=''></option>\n";
			foreach ($this->GetCountries() as $ccode=>$country)
			{	
				echo "<option value='", $ccode, "'", $ccode == $_POST["country"] ? " selected='selected'" : "", ">", $this->InputSafeString($country), "</option>\n";
			}
			echo "</select></div>\n",
					"<div class='clearfix'><label>Phone Number:*</label><input type='text' class='text' name='phone' value='", $this->InputSafeString($_POST["phone"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>Mobile Number:</label><input type='text' class='text' name='phone2' value='", $this->InputSafeString($_POST["phone2"]), "' /></div>\n", $this->EducationField(),"\n", 
					 $this->ProfessionField(),"\n",
					"<div class='clearfix'><label>How did you hear about us?</label><input type='text' class='text' name='howheard' value='", $this->InputSafeString($_POST["howheard"]), "' /></div>\n",
					"<div class='t_and_c clearfix'>I would like to be kept informed about IIDR <input type='checkbox' class='check' name='newsletter' value='1' ", ($_POST["newsletter"] || !$_POST) ? "checked='checked' " : "", "/></div>\n", $this->TAndCCheckBox($_POST["tandc"], true),
				"</div>\n",
				"<div class='clear'></div><div class='clearfix'>", $captcha->OutputInForm(), "</div>",
				"<div class='clearfix'><input type='submit' class='submit' value='Sign up' /></div>";
			if ($pagecontent)
			{	echo "<p style='padding-top:40px; font-size:12px;'>", $pagecontent, "</p>";
			}
			echo "</form>\n";
		return ob_get_clean();
	} // end of fn RegisterForm1
	
	private function Captcha()
	{	if ($this->captcha === false)
		{	$this->captcha = new ReCaptcha();
		}
		return $this->captcha;
	} // end of fn Captcha
	
	public function loginForm($url = '')
	{	ob_start();
		echo '<form class="form" id="register_login" action="', $url, '" method="post">
            	<label>Email</label><input type="text" name="li_user" value="" required="required" />
                <label>Password</label><input type="password" name="li_pass" value="" required="required"  />
                <input type="submit" class="submit" value="Sign in" />
                <div class="clear"></div>
				<p class="lfForgotLink"><a href="', $this->link->GetLink('forgot.php'), '">Forgot your password?</a></p>
            </form>
            <div class="clear"></div>';
        return ob_get_clean();	
	} // end of fn loginForm
	
	public function ForgotPasswordForm($url = '')
	{	ob_start();
		echo '<form class="form" id="register_login" action="', $url, '" method="post">
            	<label>Email:</label><input type="text" name="fp_user" value="', $this->InputSafeString($_POST['fp_user']), '" required="required" />
                 <input type="submit" class="submit" value="Get New Password" />
                <div class="clear"></div>
            </form>
            <div class="clear"></div>';
        return ob_get_clean();	
	} // end of fn ForgotPasswordForm
	
	public function SendNewPassword()
	{
		$fail = array();
		$success = array();
	
		$newpassword = $this->ConfirmCode(8);
		$sql = 'UPDATE students SET upassword=MD5("' . $newpassword . '") WHERE userid=' . (int)$this->id;
		if ($result = $this->db->Query($sql))
		{	$mail = new HTMLMail();
			$mail->SetSubject('Important message from IIDR');
			$plainbody = 'Your new IIDR password is: ' . $newpassword;
			$htmlbody = '<p>Your new IIDR password is: ' . $newpassword . '</p>';
			$mail->Send($this->details['username'], $htmlbody, $plainbody);
			$success[] = 'Your new password has been emailed to you';
		}
		
		return array('fail'=>implode(', ', $fail), 'success'=>implode(', ', $success));
	} // end of fn SendNewPassword
	
	public function EducationField ()
	{	$options = array(''=>'', 'Elementary School'=>'Elementary School', 'Vocational School'=>'Vocational School', 'High School'=>'High School', 'College'=>'College', 'University some'=>'University some', 'University Graduate'=>'University Graduate', 'Postgraduate Degree'=>'Postgraduate Degree');
		return $this->RegisterFormSelectField('education', 'Education', $options);
	} // end of fn EducationField
	
	public function RegisterFormSelectField($name = '', $label = '', $options = array())
	{	ob_start();
		echo '<div class="clearfix"><label>', $label, ':</label><select name="', $name, '" class="select-full">';
		foreach ($options as $value=>$text)
		{	echo '<option value="', $value, '"', $value == $_POST[$name] ? ' selected="selected"' : '', '>', $text, '</option>';
		}
		echo '</select></div>';
		return ob_get_clean();
	} // end of fn RegisterFormSelectField
	
	public function ProfessionField()
	{	$options=array(''=>'', 'Administrative officer/Secretary'=>'Administrative officer/Secretary', 'Advertising/PR/Media specialist'=>'Advertising/PR/Media specialist', 'Architect/Interior designer'=>'Architect/Interior designer', 'Artist/Actor/Performer'=>'Artist/Actor/Performer', 'Executive/Manager'=>'Executive/Manager', 'Fashion designer/Model/Stylist'=>'Fashion designer/Model/Stylist', 'Financial analyst/Banker'=>'Financial analyst/Banker', 'Legal advisor/Lawyer/Judge'=>'Legal advisor/Lawyer/Judge', 'Marketing specialist'=>'Marketing specialist', 'Medical doctor/Dentist/Vet'=>'Medical doctor/Dentist/Vet', 'Physical worker'=>'Physical worker', 'Politician/Civil servant/Soldier'=>'Politician/Civil servant/Soldier', 'Retired/Pensioner'=>'Retired/Pensioner', 'Sales representative/Salesman'=>'Sales representative/Salesman', 'Self employed/Freelancer'=>'Self employed/Freelancer', 'Student'=>'Student', 'Teacher/Professor'=>'Teacher/Professor', 'IT specialist/Engineer'=>'IT specialist/Engineer', 'Travel agent'=>'Travel agent', 'Other profession'=>'Other profession', 'Temporarly unemployed'=>'Temporarly unemployed');
		return $this->RegisterFormSelectField('profession', 'Profession', $options);
	} // end of fn ProfessionField
	
	public function DOBInputField($default = '')
	{	ob_start();
		$dob_field = new DOBFormLineDate('', 'dob', $default, $this->datefn->GetYearList(date('Y') - 10, -90), array(), array(), true, true, date('Y') - 20);
		echo '<div><label>Date of Birth:*</label>';
		$dob_field->OutputField();
		echo '</div>';
		return ob_get_clean();
	} // end of fn DOBInputField
	
	public function EditDetailsForm($submit_page = "")
	{	if (!$submit_page) $submit_page = $_SERVER["SCRIPT_NAME"];
		class_exists("Form");
		ob_start();
		echo "<form action='", $submit_page, "' method='post' class='form clearfix' id='register_form' />\n",
				"<div class='register_form_group clearfix' style='margin-right:20px!important;'>\n",
					"<div class='clearfix'><label>First name:*</label><input type='text' class='text' name='firstname' value='", $this->InputSafeString($this->details["firstname"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>Surname:*</label><input type='text' class='text' name='surname' value='", $this->InputSafeString($this->details["surname"]), "' required='required' /></div>\n",
					$this->DOBInputField($this->details["dob"]),
					"<div class='clearfix'><label>Gender:*</label><label class='no-width'>Male</label><input type='radio' class='radio' name='morf' value='M' ", $this->details["morf"] == "M" ? "checked='checked' " : "", "/>",
					"<label class='no-width'>Female</label><input type='radio' class='radio' name='morf' value='F' ", $this->details["morf"] == "F" ? "checked='checked' " : "", "/>",
					"</div>\n",
					"<div class='clearfix'><label>Email:*</label><input type='text' class='text' name='username' value='", $this->InputSafeString($this->details["username"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>Rest my Password:</label><input type='password' class='text' name='pword' value='' /></div>\n",
					"<div class='clearfix'><label>Confirm password:</label><input type='password' class='text' name='rtpword' value='' /></div>\n",
				"</div>\n",
				"<div class='register_form_group clearfix'>\n",
					"<div class='clearfix'><label>Address:*</label><input type='text' class='text' name='address1' value='", $this->InputSafeString($this->details["address1"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>&nbsp;</label><input type='text' class='text' name='address2' value='", $this->InputSafeString($this->details["address2"]), "' /></div>\n",
					"<div class='clearfix'><label>&nbsp;</label><input type='text' class='text' name='address3' value='", $this->InputSafeString($this->details["address3"]), "' /></div>\n",
					"<div class='clearfix'><label>Town / City:*</label><input type='text' class='text' name='city' value='", $this->InputSafeString($this->details["city"]), "' required='required' /></div>\n",
					
				"<div class='clearfix'><label>Postcode:*</label><input type='text' class='text' name='postcode' value='", $this->InputSafeString($this->details["postcode"]), "' required='required' /></div>\n",	
					
					"<div class='clearfix'><label>Country:*</label><select name='country' class='select-full' required='required'>
						<option value=''></option>";
		foreach ($this->GetCountries() as $ccode=>$country)
		{	
			echo "<option value='", $ccode, "'", $ccode == $this->details["country"] ? " selected='selected'" : "", ">", $this->InputSafeString($country), "</option>";
		}
		echo "</select></div>",
					"<div class='clearfix'><label>Phone Number:*</label><input type='text' class='text' name='phone' value='", $this->InputSafeString($this->details["phone"]), "' required='required' /></div>\n",
					"<div class='clearfix'><label>Mobile Number:</label><input type='text' class='text' name='phone2' value='", $this->InputSafeString($this->details["phone2"]), "' /></div>\n",
					"<div class='t_and_c clearfix'>Yes, sign up to the Newsletter <input type='checkbox' class='check' name='newsletter' value='1' ", $this->details["newsletter"] ? "checked='checked' " : "", "/></div>\n", $this->TAndCCheckBox($this->details["tandc"], false),
				"</div>\n",
				"<div class='clearfix'>*Mandatory Fields <input type='submit' class='submit' value='Save changes' /></div></form>\n";
		return ob_get_clean();
	} // end of fn EditDetailsForm
	
	public function AlreadyBooked($course = 0)
	{	$sql = 'SELECT * FROM coursebookings WHERE student=' . $this->id . ' AND course=' . (int)$course;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return new CourseBooking($row);
			}
		}
		return false;
	} // end of fn AlreadyBookedthod
	
	public function GetBookings($future_only = false)
	{	
		$bookings = array();
		if($future_only)
		{	$sql = 'SELECT b.* FROM coursebookings b LEFT JOIN courses c ON b.course = c.cid WHERE b.student = '. (int)$this->id .' AND c.endtime > NOW()';
		} else
		{	$sql = 'SELECT * FROM coursebookings WHERE student=' . (int)$this->id;
		}
		
		if($result = $this->db->Query($sql))
		{	while($row = $this->db->FetchArray($result))
			{	$bookings[] = $this->AssignCourseBooking($row);	
			}
		}
		
		return $bookings;
	} // end of fn GetBookings
	
	public function GetBookedCourses($future_only = false)
	{	$courses = array();
		foreach ($this->GetBookings($future_only) as $booking)
		{	if (!$courses[$booking->details['course']])
			{	$courses[$booking->details['course']] = array('course'=>$booking->course, 'bookings'=>array());
			}
			$courses[$booking->details['course']]['bookings'][] = $booking;
		}
		
		if ($courses)
		{	usort($courses, array($this, 'USortCoursesByDate'));
		}
		return $courses;
	} // end of fn GetBookedCourses
	
	private function USortCoursesByDate($a, $b)
	{	if ($a->details['starttime'] == $b->details['starttime'])
		{	return $a->id > $b->id;
		} else
		{	return $a->details['starttime'] > $b->details['starttime'];
		}
	} // end of fn USortCoursesByDate
	
	protected function AssignCourseBooking($row = array())
	{	return new CourseBooking($row);
	} // end of fn AssignCourseBooking
	
	public function GetOrders($paid_only = true)
	{
		$orders = array();
		$where = array('sid='. (int)$this->id);
		
		if ($paid_only)
		{	$where[] = 'NOT pptransid=""';
		}
		
		$sql = 'SELECT * FROM storeorders WHERE ' . implode(' AND ', $where) . ' ORDER BY orderdate DESC';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$orders[] = new StoreOrder($row);
			}
		}
		
		return $orders;
	} // end of fn GetOrders
	
	public function GetDownloads()
	{	$downloads = array();
		foreach ($this->GetOrders(true) as $order)
		{	$order->GetItems();
			foreach ($order->items as $item)
			{	if ($item['ptype'] = 'store')
				{	$product = new StoreProduct($item['pid']);
					if ($p_downloads = $product->GetDownloads())
					{	foreach ($p_downloads as $download)
						{	$downloads[$download['pfid']] = $download;
						}
					}
				}
			}
		}
		return $downloads;
	} // end of fn GetDownloads
	
	public function CanDelete($justcheckbookings = false)
	{	
		if (!$this->id)
		{	return false;
		}
		
		$sql = 'SELECT id FROM coursebookings WHERE student=' . $this->id;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return false;
			}
		}
		
		return true;
		
	} // end of fn CanDelete
	
	public function GetName()
	{
		return trim($this->details['firstname'] . ' '. $this->details['surname']);
	} // end of fn CanDelete
	
	public function FindByEmail($email = '')
	{
		if($result = $this->db->Query('SELECT * FROM students WHERE username = "'. $this->SQLSafe($email) .'" '))
		{
			$row = $this->db->FetchArray($result);
			$this->Get($row);	
			return $this->id;
		}
	} // end of fn CanDelete
	
	public function CanReview($productid = 0, $producttype = 'store')
	{	return $this->id;
	/*	switch ($producttype)
		{	case 'store': return $this->StoreProductPurchased($productid);
			case 'course': return $this->CourseAttended($productid);
		}*/
		
	} // end of fn CanReview
	
	public function ReviewForm($productid = 0, $producttype = 'store')
	{	ob_start();
		
		if ($this->CanReview($productid, $producttype) && !$this->GetReviewForProduct($productid, $producttype))
		{	
			echo '<p class="reviewOpener"><a onclick="OpenReviewForm();">Write a ', $producttype == 'store' ? 'review' : 'testimonial', '</a></p><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow")); $("#review_modal_popup").jqm();});</script><!-- START gallery modal popup --><div id="review_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="reviewModalInner">', $this->ReviewFormInner($productid, $producttype), '</div></div>';
		}
		return ob_get_clean();
	} // end of fn ReviewForm
	
	public function ReviewFormInner($productid = 0, $producttype = 'store')
	{	ob_start();
		if ($this->CanReview($productid, $producttype) && !$this->GetReviewForProduct($productid, $producttype))
		{	$review = new ProductReview();
			echo $review->CreateForm($productid, $producttype, $this->CanReview($productid, $producttype), $this);
		}
		return ob_get_clean();
	} // end of fn ReviewFormInner
	
	public function ReviewPlaceHolder($producttype = 'store')
	{	switch ($producttype)
		{	case 'store': return 'You must have purchased the product and be logged in to leave a review of your own.';
			case 'course': return 'You must have attended the course and be logged in to leave a review of your own.';
		}
	} // end of fn ReviewPlaceHolder
	
	public function GetReviewForProduct($productid = 0, $producttype = 'store')
	{	$sql = 'SELECT * FROM productreviews WHERE sid=' . (int)$this->id . ' AND pid=' . (int)$productid . ' AND ptype="' . $this->SQLSafe($producttype) . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row;
			}
		}
		return array();
	} // end of fn GetReviewForProduct
	
	public function StoreProductPurchased($productid = 0)
	{	
		$sql = 'SELECT storeorderitems.* FROM storeorderitems, storeorders WHERE storeorderitems.orderid=storeorders.id AND storeorders.sid=' . (int)$this->id . ' AND NOT pptransid="" AND storeorderitems.ptype="store" AND storeorderitems.pid=' . (int)$productid;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return true;
			}
		}
		return false;
	} // end of fn StoreProductPurchased
	
	public function CourseAttended($courseid = 0)
	{	
		$sql = 'SELECT attendance.* FROM attendance, coursebookings WHERE attendance.bookid=coursebookings.id AND coursebookings.student=' . (int)$this->id . ' AND coursebookings.course=' . (int)$courseid;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return true;
			}
		}
		return false;
	} // end of fn CourseAttended

	public function BookedCoursesList()
	{	ob_start();
		echo '<div id="bookedcourses_container">', $this->BookedCoursesListTable(), '</div>';
		return ob_get_clean();
	} // end of fn BookedCoursesList
	
	public function BookedCoursesListTable()
	{	ob_start();
		if ($courses = $this->GetBookedCourses())
		{	if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->bookings_perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->bookings_perpage;
			
			$venues = array();
			echo '<table class="myacList"><tr><th colspan="2">Course</th><th>Dates and Location</th><th>Amount</th><th>&nbsp;</th></tr>';
			foreach ($courses as $course)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
					
					if (!$venues[$course['course']->details['cvenue']])
					{	$venue = new Venue($course['course']->details['cvenue']);
						$venues[$course['course']->details['cvenue']] = $venue->GetShortDesc();
					}
					$course_title = $this->InputSafeString($course['course']->content['ctitle']);
					$course_link = $this->link->GetCourseLink($course['course']);
					echo '<tr><td class="thumbnail" rowspan="', $rowspan = count($course['bookings']), '"><div>';
					if($src = $course['course']->HasImage('thumbnail-small'))
					{	echo '<a href="', $course_link, '"><img src="', $src, '" alt="', $course_title, '" title="', $course_title, '" /></a>';
					}
					echo '</div></td><td class="prodName" rowspan="', $rowspan, '"><a href="', $course_link, '">', $course_title, '</a><p class="prodItemCode">Code: ', $course['course']->ProductID(), '</p></td><td rowspan="', $rowspan, '">', $course['course']->DateDisplayForDetails('<br />', 'D. jS M y', ' - ', '<br />'), '<br />', $venues[$course['course']->details['cvenue']], '</td>';
					$bcount = 0;
					foreach ($course['bookings'] as $booking)
					{	echo $bcount++ ? '</tr><tr class="myacListSubRow">' : '', '<td>', $this->InputSafeString($booking->ticket->details['tprice']), '</td><td><a href="booking.php?id=', $booking->id, '">Order #', $booking->id, '</a></td>';
					}
					echo '</tr>';
				}
			}
			echo '</table>';

			if (count($courses) > $this->bookings_perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($courses), $this->bookings_perpage, 'bookedcourses_container', 'ajax_bookinglist.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
		} else
		{	echo '<p>No bookings</p>';
		}
		return ob_get_clean();
	} // end of fn BookedCoursesList

	public function OrdersList()
	{	echo '<div id="orders_container">', $this->OrdersListTable(), '</div>';
	} // end of fn OrdersList
	
	public function OrdersListTable()
	{	ob_start();
		if ($orders = $this->GetOrders(true))
		{	if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->orders_perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->orders_perpage;
			
			echo '<table class="myacList"><tr><th>Date</th><th>Items</th><th>Total Cost</th><th></th></tr>';
			foreach($orders as $o)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
					
					echo '<tr><td>', date('j M y', strtotime($o->details['orderdate'])), '</td><td class="orderItemList">', $this->ItemsList($o), '</td><td class="num orderTotal">&pound;', number_format($o->GetRealTotal(), 2), '</td><td><a href="order.php?id=', $o->id, '">Order #', $o->id, '</a></td></tr>';
				}
			}
			echo '</table>';

			if (count($orders) > $this->perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($orders), $this->orders_perpage, 'orders_container', 'ajax_orderslist.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
		} else
		{	echo '<p>No orders</p>';
		}
		return ob_get_clean();
	} // end of fn OrdersList
	
	private function ItemsList(StoreOrder $order)
	{	ob_start();
		echo '<table>';
		foreach ($order->GetItems() as $item)
		{	
			echo '<tr><td class="oilType">', $this->InputSafeString($item['ptype']), '<br /><span class="oilItemid">item #', $item['id'], '</span></td><td class="oilDesc">', (int)$item['qty'], ' &times; ', $this->InputSafeString($item['title']);
			switch ($item['ptype'])
			{	case 'store':
					$product = new StoreProduct($item['pid']);
					echo '<span class="prodItemCode">Code: ', $product->ProductID(), '</span>', $product->ListDownloads($this), $product->ListPurchasedMM($this);
					break;
				case 'course':
					$ticket = new CourseTicket($item['pid']);
					$course = new Course($ticket->details['cid']);
					echo '<span class="prodItemCode">Code: ', $course->ProductID(), '</span>';
					break;
			}
			echo '</td><td class="oilPrice num">&pound;', number_format($item['totalpricetax'], 2), '</td></tr>';
		}
		foreach ($order->GetAllReferrerRewards() as $reward)
		{	echo '<tr><td class="oilType">Reward</td><td class="oilDesc">Refer-a-Friend</td><td class="oilPrice num">&minus;&pound;', number_format($reward['amount'], 2), '</td></tr>';
		}
		foreach ($order->GetAllAffRewards() as $reward)
		{	echo '<tr><td class="oilType">Reward</td><td class="oilDesc">Affiliate scheme</td><td class="oilPrice num">&minus;&pound;', number_format($reward['amount'], 2), '</td></tr>';
		}
		foreach ($order->GetBundles() as $bundle)
		{	
			echo '<tr><td class="oilType">Discount</td><td class="oilDesc">', (int)$bundle['qty'], ' &times; ', $this->InputSafeString($bundle['bname']), '</td><td class="oilPrice num">&minus;&pound;', number_format($bundle['totaldiscount'], 2), '</td></tr>';
		}
		if ($order->details['discid'])
		{	$discount = new DiscountCode($order->details['discid']);
			echo '<tr><td class="oilType">Discount</td><td class="oilDesc">', $this->InputSafeString($discount->details['discdesc']), '</td><td class="oilPrice num">&minus;&pound;', number_format($order->details['discamount'], 2), '</td></tr>';
		}
		if ($order->details['delivery_price'] > 0)
		{	
			echo '<tr><td class="oilType">delivery</td><td class="oilDesc">', ($order->details['delivery_id'] && ($deloption = new DeliveryOption($order->details['delivery_id'])) && $deloption->id) ? $this->InputSafeString($deloption->details['title']) : '','</td><td class="oilPrice num">&pound;', number_format($order->details['delivery_price'], 2), '</td></tr>';
		}
		if ($order->details['txfee'] > 0)
		{	echo '<tr><td class="oilType"></td><td class="oilDesc">Transaction fee</td><td class="oilPrice num">&pound;', number_format($order->details['txfee'], 2), '</td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ItemsList

	public function LogInForceRegPopUp($failmessage = '')
	{	if (!$this->RegCompleted())
		{	ob_start();
			class_exists('Form');
			echo '<script type="text/javascript">productID=', $this->id, ';$().ready(function(){$("body").append($(".jqmWindow"));$("#user_reg_modal_popup").jqm({modal:true});$("#user_reg_modal_popup").jqmShow();});</script>',
				'<!-- START instructor list modal popup --><div id="user_reg_modal_popup" class="jqmWindow"><div class="register-page"><h4>Please fill in your details below to complete your registration</h4>';
			if ($failmessage)
			{	echo '<div id="user_reg_fail">', $failmessage, '</div>';
			}
			echo '<form action="" method="post" class="form clearfix" id="register_form" /><input type="hidden" name="reg_complete" value="1" />',
				'<div class="register_form_group clearfix" style="margin-right:20px!important;">',
					'<div class="clearfix"><label>First name:*</label><input type="text" class="text" name="firstname" value="', $this->InputSafeString($this->details['firstname']), '" required="required" /></div>',
					'<div class="clearfix"><label>Surname:*</label><input type="text" class="text" name="surname" value="', $this->InputSafeString($this->details['surname']), '" required="required" /></div>', $this->DOBInputField($this->details['dob']),
					'<div class="clearfix"><label>Gender:*</label><label class="no-width">Male</label><input type="radio" class="radio" name="morf" value="M" ', $this->details['morf'] == 'M' ? 'checked="checked" ' : '', '/>',
					'<label class="no-width">Female</label><input type="radio" class="radio" name="morf" value="F" ', $this->details['morf'] == 'F' ? 'checked="checked" ' : '', '/>',
					'</div>',
					'<div class="clearfix"><label>Email:*</label><input type="text" class="text" name="username" value="', $this->InputSafeString($this->details['username']), '" required="required" /></div>',
					'<div class="clearfix"><label>Password (if changed):</label><input type="password" class="text" name="pword" value="" /></div>',
					'<div class="clearfix"><label>Confirm password:</label><input type="password" class="text" name="rtpword" value="" /></div>',
				'</div>',
				'<div class="register_form_group clearfix">',
					'<div class="clearfix"><label>Address:*</label><input type="text" class="text" name="address1" value="', $this->InputSafeString($this->details['address1']), '" required="required" /></div>',
					'<div class="clearfix"><label>&nbsp;</label><input type="text" class="text" name="address2" value="', $this->InputSafeString($this->details['address2']), '" /></div>',
					'<div class="clearfix"><label>&nbsp;</label><input type="text" class="text" name="address3" value="', $this->InputSafeString($this->details['address3']), '" /></div>',
					'<div class="clearfix"><label>Town / City:*</label><input type="text" class="text" name="city" value="', $this->InputSafeString($this->details['city']), '" required="required" /></div>',
					
				'<div class="clearfix"><label>Postcode:*</label><input type="text" class="text" name="postcode" value="', $this->InputSafeString($this->details['postcode']), '" required="required" /></div>',	
					
					'<div class="clearfix"><label>Country:*</label><select name="country" class="select-full" required="required">
						<option value=""></option>';
			foreach ($this->GetCountries() as $ccode=>$country)
			{	
				echo '<option value="', $ccode, '"', $ccode == $this->details['country'] ? ' selected="selected"' : '', '>', $this->InputSafeString($country), '</option>';
			}
			echo '</select></div>',
					'<div class="clearfix"><label>Phone Number:*</label><input type="text" class="text" name="phone" value="', $this->InputSafeString($this->details['phone']), '" required="required" /></div>',
					'<div class="clearfix"><label>Mobile Number:</label><input type="text" class="text" name="phone2" value="', $this->InputSafeString($this->details['phone2']), '" /></div>',
					'<div class="t_and_c clearfix">Yes, sign up to the Newsletter <input type="checkbox" class="check" name="newsletter" value="1" ', $this->details['newsletter'] ? 'checked="checked" ' : '', '/></div>', $this->TAndCCheckBox($this->details["tandc"], true),
				'</div>',
				'<div class="clearfix">*Mandatory Fields <input type="submit" class="submit" value="Save changes" /></div></form>',
				'</div></div>';
			return ob_get_clean();
		}
	} // end of fn LogInForceRegPopUp
	
	public function RegCompleted()
	{	
		if (!$this->details['username'] || !$this->details['firstname'] || !$this->details['surname'] || !$this->details['address1'] || !$this->details['country'] || !$this->details['morf'] || !$this->details['city'] || !(int)$this->details['dob'] || !$this->details['postcode'] || !$this->details['tandc'] || !($this->details['phone'] || $this->details['phone2']))
		{	return false;
		}
		return true;
	} // end of fn RegCompleted
	
	public function ReferralsOverLimit()
	{	$sum = 0;
		$sql = 'SELECT SUM(amount) AS sum_amount FROM referrewards WHERE sid=' . (int)$this->id . ' AND created>"' . $this->datefn->SQLDateTime('-1 year') . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$sum += $row['sum_amount'];
			}
		}
		$sql = 'SELECT SUM(amount) AS sum_amount FROM affrewards WHERE sid=' . (int)$this->id . ' AND created>"' . $this->datefn->SQLDateTime('-1 year') . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$sum += $row['sum_amount'];
			}
		}
		
		return $sum >= $this->GetParameter('refer_limit');
	} // end of fn ReferralsOverLimit

	public function SubscriptionEnds()
	{	return false;
	} // end of fn SubscriptionEnds
	
	public function GetReferrals(){	
		$referrals = array();
		$sql = 'SELECT * FROM referafriend WHERE referrer="' . (int)$this->id . '" ORDER BY refertime DESC';
		$result = $this->db->Query($sql);
		if ($this->db->NumRows($result)>0){
			while ($row = $this->db->FetchArray($result)){	
				$referrals[$row['rfid']] = $row;
			}
		}
		return $referrals;
	} // end of fn GetReferrals
	
	public function GetAffRewards(){
		$rewards = array();
		$sql = 'SELECT * FROM affrewards WHERE sid="' . $this->id . '" ORDER BY created DESC';
		$result = $this->db->Query($sql);
		if ($this->db->NumRows($result)>0){	
			while ($row = $this->db->FetchArray($result)){
				$row['reward_used'] = array();
				$row['reward_left'] = $row['amount'];
				echo $used_sql = 'SELECT * FROM  affrewardsused WHERE awid=' . $row['awid'] . ' ORDER BY usedtime ASC';
				if ($used_result = $this->db->Query($used_sql) && $this->db->NumRows($used_sql)>0){
					while ($used_row = $this->db->FetchArray($used_result)){
						$row['reward_used'][$used_row['ruid']] = $used_row;
						$row['reward_left'] -= $used_row['usedamount'];
					}
				}				
				$rewards[$row['awid']] = $row;
			}
		}
		return $rewards;
	} // end of fn GetAffRewards
	
	public function GetReferrerRewardsAvailable()
	{	$rewards = array();
		$sql = 'SELECT referrewards.*, SUM(referrewardsused.usedamount) AS reward_used FROM referrewards LEFT JOIN referrewardsused ON referrewards.rrid=referrewardsused.rrid WHERE referrewards.sid=' . (int)$this->id . ' AND expires>"' . $this->datefn->SQLDateTime() . '" GROUP BY referrewards.rrid ORDER BY referrewards.created, referrewards.rrid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if ($row['amount'] > $row['reward_used'])
				{	$row['amount_available'] = $row['amount'] - $row['reward_used'];
					$rewards[$row['rrid']] = $row;
				}
			}
		}
		return $rewards;
	} // end of fn GetReferrerRewardsAvailable
	
	public function GetAffiliateRewardsAvailable()
	{	$rewards = array();
		$sql = 'SELECT affrewards.*, SUM(affrewardsused.usedamount) AS reward_used FROM affrewards LEFT JOIN affrewardsused ON affrewards.awid=affrewardsused.awid WHERE affrewards.sid=' . (int)$this->id . ' AND expires>"' . $this->datefn->SQLDateTime() . '" GROUP BY affrewards.awid ORDER BY affrewards.created, affrewards.awid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if ($row['amount'] > $row['reward_used'])
				{	$row['amount_available'] = $row['amount'] - $row['reward_used'];
					$rewards[$row['awid']] = $row;
				}
			}
		}
		return $rewards;
	} // end of fn GetAffiliateRewardsAvailable
	
	public function GetSubscriptions($liveonly = false, $livedate = '')
	{	$subs = array();
		$where = array('userid=' . (int)$this->id);
		
		if ($liveonly)
		{	$livedate = $this->datefn->SQLDateTime($livedate ? strtotime($livedate) : '');
			$where[] = 'created<="' . $livedate . '"';
			$where[] = 'expires>="' . $livedate . '"';
		}
		
		$sql = 'SELECT * FROM subscriptions WHERE ' . implode(' AND ', $where) . ' ORDER BY created DESC';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$subs[$row['subid']] = $row;
			}
		}
		
		return $subs;

	} // end of fn GetSubscriptions
	
	public function LastSubscribeDate()
	{	$date = $this->datefn->SQLDateTime();
		foreach ($this->GetSubscriptions() as $sub)
		{	if ($sub['expires'] > $date)
			{	$date = $sub['expires'];
			}
		}
		return $date;
	} // end of fn LastSubscribeDate
	
	public function GetCurrentSubs()
	{	$subs = array();
		$now = $this->datefn->SQLDateTime();
		if ($all_subs = $this->GetSubscriptions())
		{	foreach ($all_subs as $sub)
			{	if ($sub['expires'] > $now)
				{	$subs[$sub['subid']] = $sub;
				}
			}
		}
		return $subs;
	} // end of fn GetCurrentSubs
	
	public function SubscriptionDetails()
	{	ob_start();
		if ($subs = $this->GetCurrentSubs())
		{	echo '<div class="myacSubsList"><table><tr><th>Subscription</th><th>Subscription starts</th><th>Subscription expires</th></tr>';
			foreach ($subs as $sub)
			{	echo '<tr><td>Sub#', $sub['subid'], ' for ', (int)$sub['months'], ' months</td><td>', date('j M Y', strtotime($sub['created'])), '</td><td>', date('j M Y', strtotime($sub['expires'])), '</td></tr>';
			}
			echo '</table></div>';
		}
		return ob_get_clean();
	} // end of fn SubscriptionDetails
	
	public function CanHaveSubscription()
	{	return ($this->details['country'] == '826') && $this->details['dob'] && ($this->details['dob'] <= $this->datefn->SQLDate(strtotime('-16 years')));
	} // end of fn CanHaveSubscription
	
	public function CanSendReferral()
	{	return (int)$this->details['dob'] && ($this->details['dob'] <= $this->datefn->SQLDate(strtotime('-18 years')));
	} // end of fn CanSendReferral
	
	public function GetAffilateRecord()
	{	$aff = new AffiliateStudent();
		if ($aff->GetByStudentID($this->id))
		{	return $aff;
		}
		return false;
	} // end of fn GetAffilateRecord
	
} // end of defn Student
?>
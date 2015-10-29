<?php
class AdminStudent extends Student
{	
	function __construct($id = 0)
	{	parent::__construct($id);
	} // fn __construct
	
	protected function AssignCourseBooking($row = array())
	{	return new AdminCourseBooking($row);
	} // end of fn AssignCourseBooking
	
	function AdminInputForm()
	{	ob_start();
		
		if (!$data = $this->details)
		{	$data = $_POST;
			if (($d = (int)$data['ddob']) && ($m = (int)$data['mdob']) && ($y = (int)$data['ydob']))
			{	$data['dob'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, '');
		if ($this->id)
		{	$this->AddBackLinkHiddenField($form);
		}
		$form->AddTextInput('Email / username', 'username', $this->InputSafeString($data['username']), 'long', 255, 1);
		$form->AddCheckbox('Mark email address as failed', 'emailfailed', '1', $data['emailfailed']);
		$form->AddPasswordInput('Passsword (if changing)', 'pword', '', 20);
		$form->AddPasswordInput('Retype passsword', 'rtpword', '', 20);
		$form->AddTextInput('First name', 'firstname', $this->InputSafeString($data['firstname']), '', 255, 1);
		$form->AddTextInput('Surname', 'surname', $this->InputSafeString($data['surname']), '', 255, 1);
		$form->AddDateInputNoPicker('Date of birth', 'dob', $data['dob'], $this->datefn->GetYearList(date('Y') - 10, -90), array(), array(), false, true);
		$form->AddRadioGroup('morf', array('M'=>'Male', 'F'=>'or Female'), $data['morf']);
		$form->AddTextInput('Phone number', 'phone', $this->InputSafeString($data['phone']), '', 50, 1);
		$form->AddTextInput('Alternative phone number', 'phone2', $this->InputSafeString($data['phone2']), '', 50, 1);
	//	$form->AddTextArea('Address', 'address', $this->InputSafeString($data['address']), '', 0, 0, $rows = 5, $cols = 50);
		$form->AddTextInput('Address', 'address1', $this->InputSafeString($data['address1']), 'long', 255, 1);
		$form->AddTextInput('&nbsp;', 'address2', $this->InputSafeString($data['address2']), 'long', 255);
		$form->AddTextInput('&nbsp;', 'address3', $this->InputSafeString($data['address3']), 'long', 255);
		$form->AddTextInput('Town / City', 'city', $this->InputSafeString($data['city']), '', 100, 1);
		$form->AddTextInput('Postcode', 'postcode', $this->InputSafeString($data['postcode']), '', 30);
		$form->AddSelect('Country', $name = 'country', $data['country'], '', $this->GetCountries('shortname', true), 1, 1);
		$form->AddCheckbox('Newsletter', 'newsletter', '1', $data['newsletter']);
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create member', 'submit');
		$form->Output();
		return ob_get_clean();
	} // end of fn AdminInputForm
	
	function Delete($justcheckbookings = false)
	{	
		if ($this->CanDelete($justcheckbookings))
		{	if ($this->db->Query('DELETE FROM students WHERE userid=' . $this->id))
			{	if ($this->db->AffectedRows()){	
					$this->db->Query('DELETE sos.*,soi.*,sid.*,cbs.* FROM storeorders sos, storeorderitems soi,storeorderitemdiscounts sid, coursebookings cbs WHERE sos.id=soi.orderid AND soi.id = sid.itemid AND soi.id = cbs.orderitemid AND sos.sid='. $this->id);
					$this->RecordAdminAction(array('tablename'=>'students', 'tableid'=>$this->id, 'area'=>'users', 'action'=>'deleted', 'actiontype'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		}
		
	} // end of fn Delete
	
} // end of defn AdminStudent
?>
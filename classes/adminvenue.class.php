<?php
class AdminVenue extends Venue
{	
	function __construct($id = 0)
	{	parent::__construct($id);	
	} // fn __construct
	
	function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	if ($result = $this->db->Query('DELETE FROM coursevenues WHERE vid=' . $this->id))
			{	if ($this->db->AffectedRows())
				{	
					$this->RecordAdminAction(array('tablename'=>'coursevenues', 'tableid'=>$this->id, 'area'=>'venues', 'action'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		}
		return false;
	} // end of fn Delete

	public function AdminLabelUsed($adminlabel = '')
	{	$sql = 'SELECT vid FROM coursevenues WHERE adminlabel="' . $adminlabel . '"';
		if ($this->id)
		{	$sql .= ' AND NOT vid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['vid'];
			}
		}
	} // end of fn AdminLabelUsed
	
	function Save($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($adminlabel = $this->SQLSafe($data['adminlabel']))
		{	if ($this->AdminLabelUsed($adminlabel))
			{	$fail[] = '"' . $this->InputSafeString($adminlabel) . '" has been used elsewhere as an admin label';
			} else
			{	$fields[] = 'adminlabel="' . $adminlabel . '"';
				if ($this->id && ($data['adminlabel'] != $this->details['adminlabel']))
				{	$admin_actions[] = array('action'=>'Admin label', 'actionfrom'=>$this->details['adminlabel'], 'actionto'=>$data['adminlabel']);
				}
			}
		} else
		{	$fail[] = 'admin label missing';
		}
		
		if ($vname = $this->SQLSafe($data['vname']))
		{	$fields[] = 'vname="' . $vname . '"';
			if ($this->id && ($data['vname'] != $this->details['vname']))
			{	$admin_actions[] = array('action'=>'Name', 'actionfrom'=>$this->details['vname'], 'actionto'=>$data['vname']);
			}
		} else
		{	$fail[] = 'name missing';
		}
		
		$vaddress = $this->SQLSafe($data['vaddress']);
		$fields[] = 'vaddress="' . $vaddress . '"';
		if ($this->id && ($data['vaddress'] != $this->details['vaddress']))
		{	$admin_actions[] = array('action'=>'Address', 'actionfrom'=>$this->details['vaddress'], 'actionto'=>$data['vaddress']);
		}

		if ($vcity = $this->SQLSafe($data['vcity']))
		{	$fields[] = 'vcity="' . $vcity . '"';
			if ($this->id && ($data['vcity'] != $this->details['vcity']))
			{	$admin_actions[] = array('action'=>'City', 'actionfrom'=>$this->details['vcity'], 'actionto'=>$data['vcity']);
			}
		} else
		{	$fail[] = 'city missing';
		}
		
		$vpostcode = $this->SQLSafe($data['vpostcode']);
		$fields[] = 'vpostcode="' . $vpostcode . '"';
		if ($this->id && ($data['vpostcode'] != $this->details['vaddress']))
		{	$admin_actions[] = array('action'=>'Postcode', 'actionfrom'=>$this->details['vpostcode'], 'actionto'=>$data['vpostcode']);
		}
		
		if ($campus_link = $this->SQLSafe($data['campus_link']))
		{	$fields[] = 'campus_link="' . $campus_link . '"';			
		}		
		
		$vlat = round($data['vlat'], 6);
		$fields[] = 'vlat="' . $vlat . '"';
		if ($this->id && ($data['vlat'] != $this->details['vlat']))
		{	$admin_actions[] = array('action'=>'Map lat', 'actionfrom'=>$this->details['vlat'], 'actionto'=>$data['vlat']);
		}
		
		$vlng = round($data['vlng'], 6);
		$fields[] = 'vlng="' . $vlng . '"';
		if ($this->id && ($data['vlng'] != $this->details['vlng']))
		{	$admin_actions[] = array('action'=>'Map long', 'actionfrom'=>$this->details['vlng'], 'actionto'=>$data['vlng']);
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE coursevenues SET ' . $set . ' WHERE vid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO coursevenues SET ' . $set;
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	if ($this->id = $this->db->InsertID())
						{	$success[] = 'New venue created';
							$this->RecordAdminAction(array('tablename'=>'coursevenues', 'tableid'=>$this->id, 'area'=>'venues', 'action'=>'created'));
						}
					}
					$this->Get($this->id);
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'coursevenues', 'tableid'=>$this->id, 'area'=>'venues');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	function InputForm()
	{	
		ob_start();
		if (!$data = $_POST)
		{	$data = $this->details;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$form->AddTextInput('Venue name', 'vname', $this->InputSafeString($data['vname']), 'long', 255, 1);
		$form->AddTextInput('Admin label', 'adminlabel', $this->InputSafeString($data['adminlabel']), '', 255, 1);
		$form->AddTextArea('Address', 'vaddress', $this->InputSafeString($data['vaddress']), '', 0, 0, 4, 60);
		$form->AddTextInput('City', 'vcity', $this->InputSafeString($data['vcity']), '', 255);
		$form->AddTextInput('Postcode', 'vpostcode', $this->InputSafeString($data['vpostcode']), '', 255);
		$form->AddTextInput('Map longitude', 'vlng', round($data['vlng'], 6), '', 12);
		$form->AddTextInput('Map latitude', 'vlat', round($data['vlat'], 6), '', 12);
		$form->AddTextInput('Campus Link', 'campus_link', $this->InputSafeString($data['campus_link']), 'long', 255, 1);
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Venue', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this venue</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('coursevenues', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	public function InitAdminLabels()
	{	$names = array();
		$sql = 'SELECT vname, vid FROM coursevenues';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$adminlabel = $adminlabel_raw = preg_replace('|[^a-z0-9]|', '_', strtolower($row['vname']));
				while ($names[$adminlabel])
				{	$adminlabel = $adminlabel_raw . '_' . ++$count;
				}
				$updsql = 'UPDATE coursevenues SET adminlabel="' . $adminlabel . '" WHERE vid=' . $row['vid'];
				echo $updsql, '|', $row['vname'], '<br />';
				$this->db->Query($updsql);
				$names[$adminlabel] = $adminlabel;
			}
		}
	} // end of fn InitAdminLabels
	
} // end of class AdminVenue
?>
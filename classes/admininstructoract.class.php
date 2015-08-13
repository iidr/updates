<?php
class AdminInstructorAct extends InstructorAct
{
	function __construct($id = 0)
	{	parent::__construct($id);
	} //  end of fn __construct

	function Save($data = array(), $inid = 0)
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		// start date and time
		if (($d = (int)$data['dactdate']) && ($m = (int)$data['mactdate']) && ($y = (int)$data['yactdate']))
		{	$actdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			$fields[] = 'actdate="' . $actdate . '"';
			if ($this->id && ($actdate != $this->details['actdate']))
			{	$admin_actions[] = array('action'=>'Date', 'actionfrom'=>$this->details['actdate'], 'actionto'=>$actdate, 'actiontype'=>'date');
			}
		} else
		{	$fail[] = 'date missing';
		}
		
		if ($acttext = $this->SQLSafe($data['acttext']))
		{	$fields[] = 'acttext="' . $acttext . '"';
			if ($this->id && ($data['acttext'] != $this->details['acttext']))
			{	$admin_actions[] = array('action'=>'Main text', 'actionfrom'=>$this->details['acttext'], 'actionto'=>$data['acttext']);
			}
		} else
		{	$fail[] = 'text missing';
		}
		
		if ($acttitle = $this->SQLSafe($data['acttitle']))
		{	$fields[] = 'acttitle="' . $acttitle . '"';
			if ($this->id && ($data['acttitle'] != $this->details['acttitle']))
			{	$admin_actions[] = array('action'=>'Title', 'actionfrom'=>$this->details['acttitle'], 'actionto'=>$data['acttitle']);
			}
		} else
		{	$fail[] = 'title missing';
		}
		
		if (!$this->id)
		{	if ($inid = (int)$inid)
			{	$fields[] = 'inid=' . $inid;
			} else
			{	$fail[] = 'no instuctor id';
			}
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE instactivities SET ' . $set . ' WHERE iaid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO instactivities SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$base_parameters = array('tablename'=>'instactivities', 'tableid'=>$this->id, 'area'=>'instactivities');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New activity added';
						$this->RecordAdminAction(array('tablename'=>'instactivities', 'tableid'=>$this->id, 'area'=>'instactivities', 'action'=>'created'));
					}
					$this->Get($this->id);
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	function CanDelete()
	{	return true;
	} // end of fn CanDelete
	
	function InputForm($inid = 0)
	{	ob_start();
		
		$startyear = 2000;
		$endyear = 2025;

		if ($data = $this->details)
		{	if (($actdate_year = date('Y', strtotime($data['actdate']))) < $startyear)
			{	$startyear = $starttimeyear;
			}
			if ($actdate_year > $endyear)
			{	$endyear = $endtimeyear;
			}
		} else
		{	$data = $_POST;
			
			if (($d = (int)$data['dactdate']) && ($m = (int)$data['mactdate']) && ($y = (int)$data['yactdate']))
			{	$data['actdate'] = $this->datefn->SQLDate(mktime(0, 0, 0, $m, $d, $y));
			}
		}
		
		$years = array();
		for ($y = $startyear; $y <= $endyear; $y++)
		{	$years[] = $y;
		}

		if ($this->id)
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id);
		} else
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?inid=' . (int)$inid);
		}
		$form->AddDateInput('Date', 'actdate', $data['actdate'], $years, 0, 0, true, true, date('Y'));
		$form->AddTextInput('Title', 'acttitle', $this->InputSafeString($data['acttitle']), 'long');
		$form->AddTextArea('Text', 'acttext', $this->InputSafeString($data['acttext']), '', 0, 0, 10, 50);
		$form->AddCheckBox('Live', 'live', '1', $data['live']);
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Add Activity', 'submit');
		if ($histlink = $this->DisplayHistoryLink('instactivities', $this->id))
		{	echo '<p>', $histlink, '</p>';
		}
		if ($this->id)
		{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this activity</a></p>';
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
} // end of defn InstructorAct
?>
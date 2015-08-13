<?php
class AdminInstructorInterview extends InstructorInterview
{	
	public function __construct($id = null)
	{	parent::__construct($id);
	} // fn __construct
	
	public function CanDelete()
	{	return true;
	} // end of fn CanDelete
	
	function InputForm($inid = 0)
	{	ob_start();
		
		$startyear = 2000;
		$endyear = 2025;

		if ($data = $this->details)
		{	if (($ivdate_year = date('Y', strtotime($data['ivdate']))) < $startyear)
			{	$startyear = $starttimeyear;
			}
			if ($actdate_year > $endyear)
			{	$endyear = $endtimeyear;
			}
		} else
		{	$data = $_POST;
			
			if (($d = (int)$data['divdate']) && ($m = (int)$data['mivdate']) && ($y = (int)$data['yivdate']))
			{	$data['ivdate'] = $this->datefn->SQLDate(mktime(0, 0, 0, $m, $d, $y));
			}
		}
		
		$years = array();
		for ($y = $startyear; $y <= $endyear; $y++)
		{	$years[] = $y;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id . '&inid=' . (int)$inid, 'course_edit');
		$form->AddTextInput('Title', 'ivtitle', $this->InputSafeString($data['ivtitle']), 'long', 255);
		$form->AddTextArea('Interview text', 'ivtext', $this->InputSafeString($data['ivtext']), 'tinymce', 0, 0, 40, 80);
		$form->AddDateInput('Date', 'ivdate', $data['ivdate'], $years, 0, 0, true, true, date('Y'));
		
		$form->AddCheckBox('Live (in front-end)', 'live', '1', $data['live']);
		$form->AddCheckBox('Show social media links?', 'socialbar', 1, $data['socialbar']);
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Interview', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this interview</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('instinterviews', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm

	function Save($data = array(), $inid = 0)
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if (!$this->id)
		{	if (($instructor = new AdminInstructor($inid)) && $instructor->id)
			{	$fields[] = 'inid=' . $instructor->id;
			} else
			{	$fail[] = 'instructor not found';
			}
		}
		
		if ($ivtitle = $this->SQLSafe($data['ivtitle']))
		{	$fields[] = 'ivtitle="' . $ivtitle . '"';
			if ($this->id && ($data['ivtitle'] != $this->details['ivtitle']))
			{	$admin_actions[] = array('action'=>'Title', 'actionfrom'=>$this->details['ivtitle'], 'actionto'=>$data['ivtitle']);
			}
		} else
		{	$fail[] = 'title missing';
		}
		
		$ivtext = $this->SQLSafe($data['ivtext']);
		$fields[] = 'ivtext="' . $ivtext . '"';
		if ($this->id && ($data['ivtext'] != $this->details['ivtext']))
		{	$admin_actions[] = array('action'=>'Text', 'actionfrom'=>$this->details['ivtext'], 'actionto'=>$data['ivtext']);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$socialbar = ($data['socialbar'] ? '1' : '0');
		$fields[] = 'socialbar=' . $socialbar;
		if ($this->id && ($socialbar != $this->details['socialbar']))
		{	$admin_actions[] = array('action'=>'Social bar?', 'actionfrom'=>$this->details['socialbar'], 'actionto'=>$socialbar, 'actiontype'=>'boolean');
		}
		
		// start date and time
		if (($d = (int)$data['divdate']) && ($m = (int)$data['mivdate']) && ($y = (int)$data['yivdate']))
		{	$ivdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			$fields[] = 'ivdate="' . $ivdate . '"';
			if ($this->id && ($ivdate != $this->details['ivdate']))
			{	$admin_actions[] = array('action'=>'Date', 'actionfrom'=>$this->details['ivdate'], 'actionto'=>$ivdate, 'actiontype'=>'date');
			}
		} else
		{	$fail[] = 'date missing';
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE instinterviews SET ' . $set . ' WHERE ivid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO instinterviews SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$base_parameters = array('tablename'=>'instinterviews', 'tableid'=>$this->id, 'area'=>'instinterviews');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New interview created';
						$this->RecordAdminAction(array('tablename'=>'instinterviews', 'tableid'=>$this->id, 'area'=>'instinterviews', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
			} //else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
} // end of class AdminInstructorInterview
?>
<?php
class AdminCourseDate extends CourseDate
{
	function __construct($id = 0)
	{	parent::__construct($id);
	} //  end of fn __construct

	function Save($data = array(), $courseid = 0)
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		// start date and time
		if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
		{	$startdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		} else
		{	$fail[] = 'start date missing';
		}
		
		// end date and time
		if (($d = (int)$data['dend']) && ($m = (int)$data['mend']) && ($y = (int)$data['yend']))
		{	$enddate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		} else
		{	$fail[] = "end date missing";
		}
		
		if ($startdate && $enddate)
		{	if ($enddate >= $startdate)
			{	$fields[] = 'startdate="' . $startdate . '"';
				if ($this->id && ($startdate != $this->details['startdate']))
				{	$admin_actions[] = array('action'=>'Start', 'actionfrom'=>$this->details['startdate'], 'actionto'=>$startdate, 'actiontype'=>'date');
				}
				$fields[] = 'enddate="' . $enddate . '"';
				if ($this->id && ($enddate != $this->details['enddate']))
				{	$admin_actions[] = array('action'=>'End', 'actionfrom'=>$this->details['enddate'], 'actionto'=>$enddate, 'actiontype'=>'date');
				}
			} else
			{	$fail[] = 'End date must be on or after the start date, please check your dates';
			}
		}
		
		$timetext = $this->SQLSafe($data['timetext']);
		$fields[] = 'timetext="' . $timetext . '"';
		if ($this->id && ($timetext != $this->details['timetext']))
		{	$admin_actions[] = array('action'=>'Time text', 'actionfrom'=>$this->details['timetext'], 'actionto'=>$data['timetext']);
		}
		
		if (!$this->id)
		{	if ($cid = (int)$courseid)
			{	$fields[] = 'cid=' . $cid;
			} else
			{	$fail[] = 'no course id';
			}
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE coursedates SET ' . $set . ' WHERE cdid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO coursedates SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$base_parameters = array('tablename'=>'coursedates', 'tableid'=>$this->id, 'area'=>'coursedates');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New course dates added';
						$this->RecordAdminAction(array('tablename'=>'coursedates', 'tableid'=>$this->id, 'area'=>'coursedates', 'action'=>'created'));
					}
					$this->Get($this->id);
					// reset dates for course
					$course = new AdminCourse($this->details['cid']);
					if ($course->SetCourseDates())
					{	$success[] = 'Course dates amended';
					}
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	function CanDelete()
	{	return true;
	} // end of fn CanDelete
	
	protected function DeleteExtra()
	{	$course = new AdminCourse($this->details['cid']);
		$course->SetCourseDates();
	} // end of fn DeleteExtra
	
	function InputForm($courseid = 0)
	{	ob_start();
		
		$startyear = 2000;
		$endyear = 2025;

		if ($data = $this->details)
		{	if (($starttimeyear = date('Y', strtotime($data['startdate']))) < $startyear)
			{	$startyear = $starttimeyear;
			}
			if (($endtimeyear = date('Y', strtotime($data['enddate']))) > $endyear)
			{	$endyear = $endtimeyear;
			}
		} else
		{	$data = $_POST;
			
			if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
			{	$data['startdate'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
			
			if (($d = (int)$data['dend']) && ($m = (int)$data['mend']) && ($y = (int)$data['yend']))
			{	$data['enddate'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
		}
		
		$years = array();
		for ($y = $startyear; $y <= $endyear; $y++)
		{	$years[] = $y;
		}

		if ($this->id)
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id);
		} else
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?cid=' . (int)$courseid);
		}
		$form->AddDateInput('Start date', 'start', $data['startdate'], $years, 0, 0, true, true, date('Y'));
		$form->AddDateInput('End date', 'end', $data['enddate'], $years, 0, 0, true, true, date('Y'));
		$form->AddTextInput('Time text', 'timetext', $this->InputSafeString($data['timetext']), 'long');
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Add Dates', 'submit');
		if ($histlink = $this->DisplayHistoryLink('coursedates', $this->id))
		{	echo '<p>', $histlink, '</p>';
		}
		if ($this->id)
		{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', 
					$_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 
					'delete these dates for this course</a></p>';
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
} // end of defn NewsStory
?>
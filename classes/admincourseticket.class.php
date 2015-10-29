<?php
class AdminCourseTicket extends CourseTicket
{	
	function __construct($id = 0)
	{	parent::__construct($id);
	} // fn __construct
	
	public function CanDelete(){	
		//return $this->id && !count($this->GetBookings());
		return $this->id;
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	if ($result = $this->db->Query('DELETE FROM coursetickets WHERE tid=' . $this->id))
			{	if ($this->db->AffectedRows()){	
					$result = $this->db->Query('DELETE FROM coursebookings WHERE tid=' . $this->id);
					
					$this->RecordAdminAction(array('tablename'=>'coursetickets', 'tableid'=>$this->id, 'area'=>'coursetickets', 'action'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		}
		return false;
	} // end of fn Delete
	
	function InputForm($course_id = 0)
	{	
		ob_start();
		
		$startyear = 2000;
		$endyear = 2025;

		if ($data = $this->details)
		{	if ((int)$data['startdate'] && (($starttimeyear = date('Y', strtotime($data['startdate']))) < $startyear))
			{	$startyear = $starttimeyear;
			}
			if ((int)$data['enddate'] && (($endtimeyear = date('Y', strtotime($data['enddate']))) > $endyear))
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
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id . '&cid=' . (int)$course_id, 'course_edit');
		$this->AddBackLinkHiddenField($form);
		$form->AddTextInput('Name', 'tname', $this->InputSafeString($data['tname']), 'long', 255, 1);
		$form->AddTextInput('Description', 'tdesc', $this->InputSafeString($data['tdesc']), 'long', 255);
		$form->AddTextInput('Quantity' . ($this->id ? (' (booked: ' . (int)$this->details['tbooked'] . ')') : ''), 'tqty', (int)$data['tqty'], 'number', 6);
		
		$form->AddTextInput('Price', 'tprice', number_format($data['tprice'], 2, '.', ''), 'number', 11);
		$form->AddSelect('Tax rate', 'taxid', $this->details['taxid'], '', $this->TaxRatesForDropDown(), true, false);
		
		$form->AddCheckBox('Current (available to book)', 'live', '1', $data['live']);
		$form->AddCheckBox('Allow blank attendee?', 'no_reg', '1', $data['no_reg']);
		$form->AddDateInput('On sale from (blank to be on sale immediately)', 'start', $data['startdate'], $years, 0, 0, true, false, date('Y'));
		$form->AddDateInput('On sale to (blank to be on sale indefinitely)', 'end', $data['enddate'], $years, 0, 0, true, false, date('Y'));
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Ticket', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this ticket</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('ticket', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm

	function Save($data = array(), $course_id = 0)
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($tname = $this->SQLSafe($data['tname']))
		{	$fields[] = 'tname="' . $tname . '"';
			if ($this->id && ($data['tname'] != $this->details['tname']))
			{	$admin_actions[] = array('action'=>'Name', 'actionfrom'=>$this->details['tname'], 'actionto'=>$data['tname']);
			}
		} else
		{	$fail[] = 'name missing';
		}
		
		$tdesc = $this->SQLSafe($data['tdesc']);
		$fields[] = 'tdesc="' . $tdesc . '"';
		if ($this->id && ($data['tdesc'] != $this->details['tdesc']))
		{	$admin_actions[] = array('action'=>'Description', 'actionfrom'=>$this->details['tdesc'], 'actionto'=>$data['tdesc']);
		}
		
		$tqty = (int)$data['tqty'];
		$fields[] = 'tqty=' . $tqty ;
		if ($this->id && ($tqty != $this->details['tqty']))
		{	$admin_actions[] = array('action'=>'Quantity', 'actionfrom'=>$this->details['tqty'], 'actionto'=>$data['tqty']);
		}
		
		$tprice = round($data['tprice'], 2);
		$fields[] = 'tprice=' . $tprice ;
		if ($this->id && ($tprice != $this->details['tprice']))
		{	$admin_actions[] = array('action'=>'Price', 'actionfrom'=>$this->details['tprice'], 'actionto'=>$data['tprice']);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$no_reg = ($data['no_reg'] ? '1' : '0');
		$fields[] = 'no_reg=' . $no_reg;
		if ($this->id && ($no_reg != $this->details['no_reg']))
		{	$admin_actions[] = array('action'=>'Allow blank attendee?', 'actionfrom'=>$this->details['no_reg'], 'actionto'=>$no_reg, 'actiontype'=>'boolean');
		}
		
		if (!$this->id)
		{	if ($cid = (int)$course_id)
			{	$fields[] = 'cid=' . $cid;
			} else
			{	$fail[] = 'Course for new ticket not found';
			}
		}
		
		$taxrates = $this->TaxRatesForDropDown();
		if ($taxid = (int)$data['taxid'])
		{	if ($taxrates[$taxid])
			{	$fields[] = 'taxid=' . $taxid;
				if ($this->id && ($taxid != $this->details['taxid']))
				{	$admin_actions[] = array('action'=>'Tax rate', 'actionfrom'=>$taxrates[$this->details['taxid']], 'actionto'=>$taxrates[$taxid]);
				}
			} else
			{	$fail[] = 'Tax rate not found';
			}
		} else
		{	$fields[] = 'taxid=0';
			if ($this->id && $this->details['taxid'])
			{	$admin_actions[] = array('action'=>'Tax rate', 'actionfrom'=>$taxrates[$this->details['taxid']], 'actionto'=>'');
			}
		}
		
		// start date and time
		if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
		{	$startdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		} else
		{	$startdate = '0000-00-00';
		}
		
		// end date and time
		if (($d = (int)$data['dend']) && ($m = (int)$data['mend']) && ($y = (int)$data['yend']))
		{	$enddate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		} else
		{	$enddate = '0000-00-00';
		}
		
		if ((int)$startdate && (int)$enddate)
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
		} else
		{	$fields[] = 'startdate="' . $startdate . '"';
			if ($this->id && ($startdate != $this->details['startdate']))
			{	$admin_actions[] = array('action'=>'On sale from', 'actionfrom'=>$this->details['startdate'], 'actionto'=>$startdate, 'actiontype'=>'date');
			}
			$fields[] = 'enddate="' . $enddate . '"';
			if ($this->id && ($enddate != $this->details['enddate']))
			{	$admin_actions[] = array('action'=>'On sale to', 'actionfrom'=>$this->details['enddate'], 'actionto'=>$enddate, 'actiontype'=>'date');
			}
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE coursetickets SET ' . $set . ' WHERE tid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO coursetickets SET ' . $set;
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New course ticket created';
						$this->RecordAdminAction(array('tablename'=>'coursetickets', 'tableid'=>$this->id, 'area'=>'course tickets', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'coursetickets', 'tableid'=>$this->id, 'area'=>'course tickets');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
				
				$this->UpdateStatusFromBookings();
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	public function BundlesList()
	{	if ($this->id)
		{	ob_start();
			echo '<h2>Bundles involving this product</h2><table><tr class="newlink"><th colspan="6"><a href="bundleedit.php">Create new bundle</a></th></tr><tr><th>Title</th><th>Description</th><th>Products</th><th>Discount</th><th>Live?</th><th>Actions</th></tr>';
			foreach ($this->GetBundles() as $bundle_row)
			{	$bundle = new AdminBundle($bundle_row);
				echo '<tr class="stripe', $i++ % 2, '"><td>', $this->InputSafeString($bundle->details['bname']), '</td><td>', nl2br($this->InputSafeString($bundle->details['bdesc'])), '</td><td>', $bundle->ProductTextList('<br />'), '</td><td>',number_format($bundle->details['discount'], 2), '</td><td>', $bundle->details['live'] ? 'Yes' : 'No', '</td><td><a href="bundleedit.php?id=', $bundle->id, '">edit</a>';
				if ($histlink = $this->DisplayHistoryLink('bundles', $bundle->id))
				{	echo '&nbsp;|&nbsp;', $histlink;
				}
				if ($bundle->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="bundleedit.php?id=', $bundle->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo "</table>";
			return ob_get_clean();
		}
	} // end of fn BundlesList
	
	public function GetBundles()
	{	$bundles = array();
		$sql = 'SELECT bundles.* FROM bundles, bundleproducts WHERE bundles.bid=bundleproducts.bid AND pid=' . (int)$this->id . ' AND bundleproducts.ptype="course" ORDER BY bundles.bid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$bundles[$row['bid']] = $row;
			}
		}
		return $bundles;
	} // end of fn GetBundles

	public function OnSaleDatesText()
	{	ob_start();
		if ((int)$this->details['startdate'])
		{	echo 'from ', date('d M y', strtotime($this->details['startdate']));
		}
		if ((int)$this->details['enddate'])
		{	echo ' to ', date('d M y', strtotime($this->details['enddate']));
		}
		if (!$text = trim(ob_get_clean()))
		{	$text = 'not restricted';
		}
		return $text;
	} // end of fn OnSaleDatesText
	
} // end of defn AdminCourseTicket
?>
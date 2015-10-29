<?php
class AdminCourse extends Course
{	
	function __construct($id = 0)
	{	parent::__construct($id, false);
	} // fn __construct
	
	function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	if ($result = $this->db->Query('DELETE FROM courses WHERE cid=' . $this->id))
			{	if ($this->db->AffectedRows())
				{	$this->RecordAdminAction(array('tablename'=>'courses', 'tableid'=>$this->id, 'area'=>'courses', 'action'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		}
		return false;
	} // end of fn Delete

	function Save($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		$content = $this->CourseContentDropdownList();
		if ($content[$ccid = (int)$data['ccid']])
		{	$fields[] = 'ccid=' . $ccid;
			if ($this->id && ($data['ccid'] != $this->details['ccid']))
			{	$admin_actions[] = array('action'=>'Content', 'actionfrom'=>$this->details['ccid'], 'actionto'=>$data['ccid']);
			}
		} else
		{	$fail[] = 'you must select some course content';
		}
		
		$cvenue = (int)$data['cvenue'];
		$fields[] = 'cvenue=' . $cvenue;
		if ($this->id && ($data['cvenue'] != $this->details['cvenue']))
		{	$admin_actions[] = array('action'=>'Venue', 'actionfrom'=>$this->details['cvenue'], 'actionto'=>$data['cvenue']);
		}
		
		$cstockmethod = (int)$data['cstockmethod'];
		$fields[] = 'cstockmethod="' . $cstockmethod . '"';
		if ($this->id && ($data['cstockmethod'] != $this->details['cstockmethod']))
		{	$admin_actions[] = array('action'=>'Stock method', 'actionfrom'=>$this->details['cstockmethod'], 'actionto'=>$data['cstockmethod']);
		}
		
		$cavailableplaces = (int)$data['cavailableplaces'];
		$fields[] = 'cavailableplaces="' . $cavailableplaces . '"';
		if ($this->id && ($data['cavailableplaces'] != $this->details['cavailableplaces']))
		{	$admin_actions[] = array('action'=>'Available places', 'actionfrom'=>$this->details['cavailableplaces'], 'actionto'=>$data['cavailableplaces']);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$bookable = ($data['bookable'] ? '1' : '0');
		$fields[] = 'bookable=' . $bookable;
		if ($this->id && ($bookable != $this->details['bookable']))
		{	$admin_actions[] = array('action'=>'Bookable?', 'actionfrom'=>$this->details['bookable'], 'actionto'=>$bookable, 'actiontype'=>'boolean');
		}
		
		$socialbar = ($data['socialbar'] ? '1' : '0');
		$fields[] = 'socialbar=' . $socialbar;
		if ($this->id && ($socialbar != $this->details['socialbar']))
		{	$admin_actions[] = array('action'=>'Social bar?', 'actionfrom'=>$this->details['socialbar'], 'actionto'=>$socialbar, 'actiontype'=>'boolean');
		}
		
		if ($so_slogan = $this->SQLSafe($data['so_slogan']))
		{	if ($this->slogan_styles[$so_style = $data['so_style']])
			{	$fields[] = 'so_slogan="' . $so_slogan . '"';
				if ($this->id && ($data['so_slogan'] != $this->details['so_slogan']))
				{	$admin_actions[] = array('action'=>'SO Slogan', 'actionfrom'=>$this->details['so_slogan'], 'actionto'=>$data['so_slogan']);
				}
				$fields[] = 'so_style="' . $so_style . '"';
				if ($this->id && ($data['so_style'] != $this->details['so_style']))
				{	$admin_actions[] = array('action'=>'SO Style', 'actionfrom'=>$this->slogan_styles[$this->details['so_style']], 'actionto'=>$this->slogan_styles[$data['so_style']]);
				}
				$so_text = $this->SQLSafe($data['so_text']);
				$fields[] = 'so_text="' . $so_text . '"';
				if ($this->id && ($data['so_text'] != $this->details['so_text']))
				{	$admin_actions[] = array('action'=>'SO Text', 'actionfrom'=>$this->details['so_text'], 'actionto'=>$data['so_text']);
				}
			} else
			{	$fail[] = 'You must pick a style for a special offer';
			}
		} else
		{	$fields[] = 'so_slogan=""';
			$fields[] = 'so_text=""';
			$fields[] = 'so_style=""';
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE courses SET ' . $set . ' WHERE cid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO courses SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New course created';
						$this->RecordAdminAction(array('tablename'=>'courses', 'tableid'=>$this->id, 'area'=>'courses', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'courses', 'tableid'=>$this->id, 'area'=>'courses');
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
	
	public function SaveInstructors($instructors = array())
	{	$added = 0;
		$deleted = 0;
		
		if ($this->id)
		{	// delete existing
			if ($this->instructors)
			{	foreach ($this->instructors as $inid=>$instructor)
				{	if (!$instructors[$inid])
					{	$sql = 'DELETE FROM courseinstructors WHERE cid='. (int)$this->id . ' AND inid=' . (int)$inid;
						if ($result = $this->db->Query($sql))
						{	if ($this->db->AffectedRows())
							{	$deleted++;
							}
						}
					}
				}
			}
			
			// add any new
			if ($instructors)
			{	foreach ($instructors as $inid=>$is_set)
				{	if (!$this->instructors[$inid])
					{	$sql = 'INSERT INTO courseinstructors SET cid='. (int)$this->id . ', inid=' . (int)$inid;
						if ($result = $this->db->Query($sql))
						{	if ($this->db->AffectedRows())
							{	$added++;
							}
						}
					}
				}
			}
		}
		
		$return = array();
	
		if ($added)
		{	$return['success'] = $added . ' instructors added to course';
		}
		if ($deleted)
		{	$return['fail'] = $deleted . ' instructors removed from course';
		}
		
		if ($return)
		{	$this->GetInstructors();
		}
		
		return $return;
		
	} // end of fn SaveInstructors
	
	function InputForm()
	{	
		ob_start();
		
		$startyear = date('Y') - 3;
		$endyear = date('Y') + 3;
		
		if ($data = $this->details)
		{	if (($starttimeyear = date('Y', strtotime($this->details['starttime']))) < $startyear)
			{	$startyear = $starttimeyear;
			}
			if (($endtimeyear = date('Y', strtotime($this->details['endtime']))) > $endyear)
			{	$endyear = $endtimeyear;
			}
		} else
		{	$data = $_POST;
			
			if (!isset($_POST['ccid']) && $ccid = (int)$_GET['ccid'])
			{	$data['ccid'] = $ccid;
			}
			
			if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
			{	$data['starttime'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
			
			if (($d = (int)$data['dend']) && ($m = (int)$data['mend']) && ($y = (int)$data['yend']))
			{	$data['endtime'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
		}
		
		$years = array();
		for ($y = $startyear; $y <= $endyear; $y++)
		{	$years[] = $y;
		}
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$this->AddBackLinkHiddenField($form);
		$form->AddRawText('<label>Product Code: </label><label>CE'.$this->id.'</label><br />');
		$form->AddSelect('Course to schedule', 'ccid', $data['ccid'], '', $this->CourseContentDropdownList(), true, true);
		
		$form->AddSelect('Venue', 'cvenue', $data['cvenue'], '', $this->VenueList(), true, true);
		$form->AddTextInput('Special offer slogan', 'so_slogan', $this->InputSafeString($data['so_slogan']), '', 15);
		$form->AddTextInput('Special offer text', 'so_text', $this->InputSafeString($data['so_text']), 'long', 255);
		$form->AddSelect('Special offer style', 'so_style', $data['so_style'], '', $this->slogan_styles, true, false);
		
		$form->AddRawText('<h3>How should stock be managed?</h3>');
		$form->AddRawText("<label>Allow unlimited orders</label> <input type='radio' name='cstockmethod' value='0' " . ($data['cstockmethod'] == 0 ? "checked='checked'" : "") . " /><br />");
		$form->AddRawText("<label>Limit by available places</label> <input type='radio' name='cstockmethod' value='1' " . ($data['cstockmethod'] == 1 ? "checked='checked'" : "") . " />");
		$form->AddTextInput('Total available' . ($this->id ? (' (booked: ' . (int)$this->details['cbookings'] . ')') : ''), 'cavailableplaces', $this->InputSafeString($data['cavailableplaces']), 'short', 255);
		$form->AddRawText("<label>Tickets manage stock levels</label> <input type='radio' name='cstockmethod' value='2' " . ($data['cstockmethod']==2 ? "checked='checked'" : "") . " /><br /><br />");
		
		$form->AddCheckBox('Live (visible in front-end)', 'live', '1', $data['live']);
		$form->AddCheckBox('Available to book', 'bookable', '1', $data['bookable']);
		$form->AddCheckBox('Show social media links?', 'socialbar', 1, $data['socialbar']);
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Course', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this course</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('courses', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	function VenueList()
	{	$venues = array();
		
		if($result = $this->db->Query('SELECT * FROM coursevenues ORDER BY adminlabel ASC'))
		{	while($row = $this->db->FetchArray($result))
			{	$venues[$row['vid']] = $this->InputSafeString($row['adminlabel'] . ': ' . $row['vname']);
			}
		}
		
		return $venues;
	} // end of fn VenueList	
	
	public function CourseContentDropdownList()
	{	$content = array();
		$sql = 'SELECT * FROM coursecontent ORDER BY ctitle';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$content[$row['ccid']] = $this->InputSafeString($row['ctitle']);
			}
		}
		return $content;
	} // end of fn CourseContentDropdownList
	
	public function SetCourseDates()
	{	$starttime = '0000-00-00';
		$endtime = '0000-00-00';
		
		foreach ($this->dates as $date)
		{	if (!(int)$starttime || ($date['startdate'] < $starttime))
			{	$starttime = $date['startdate'];
			}
			if ($date['enddate'] > $endtime)
			{	$endtime = $date['enddate'];
			}
		}
		
		$sql = 'UPDATE courses SET starttime="' . $starttime . '", endtime="' . $endtime . '" WHERE cid=' . (int)$this->id;
		if ($result = $this->db->Query($sql))
		{	return $this->db->AffectedRows();
		}
		
		
	} // end of fn SetCourseDates
	
	public function TicketsList()
	{	ob_start();
		if ($this->id)
		{	echo '<table><tr class="newlink"><th colspan="10"><a href="courseticket.php?cid=', $this->id, '">Create new ticket</a></th></tr><tr><th>Ticket name</th><th>Description</th><th>Price</th><th>Qty</th><th>Booked</th><th>On sale</th><th>Tax Rate</th><th>Allow no<br />registration?</th><th>Live?</th><th>Actions</th></tr>';
			$taxrates = $this->TaxRatesForDropDown();
			foreach ($this->tickets as $ticket_row)
			{	$ticket = new AdminCourseTicket($ticket_row);
				echo '<tr><td>', $this->InputSafeString($ticket->details['tname']), '</td><td>', $this->InputSafeString($ticket->details['tdesc']), '</td><td>&pound;', number_format($ticket->details['tprice'], 2), '</td><td>', (int)$ticket->details['tqty'], '</td><td>', (int)$ticket->details['tbooked'], '</td><td>', $ticket->OnSaleDatesText(), '</td><td>', $taxrates[$ticket->details['taxid']], '</td><td>', $ticket->details['no_reg'] ? 'Yes' : '', '</td><td>', $ticket->details['live'] ? 'Live' : 'Offline', '</td><td><a href="courseticket.php?id=', $ticket->id, '">edit</a>';
				if ($ticket->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="courseticket.php?id=', $ticket->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		}
		return ob_get_clean();
	} // end of fn TicketsList
	
	public function InstructorsForm()
	{	ob_start();
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		
		// Do instructors section
		$form->AddRawText('<h2>Instructors</h2>');
		
		$instructors = new Instructors();
		
		$form->AddHiddenInput('inst_saved', '1');
		$form->AddRawText('<div id="instructorslist">');
		
		foreach ($instructors->instructors as $inst)
		{	$form->AddCheckBox($this->InputSafeString($inst->GetFullName()), 'instructors[' . $inst->id . ']', '1', isset($this->instructors[$inst->id]));
		}
		
		$form->AddRawText('</div>');
		
		$form->AddSubmitButton('', 'Save instructors', 'submit');
		$form->Output();
		return ob_get_clean();
	} // end of fn InstructorsForm
	
	public function GalleriesDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->GalleriesTable(), '</div><script type="text/javascript"> $().ready(function(){$("body").append($(".jqmWindow")); $("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn GalleriesDisplay
	
	public function GalleriesTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a onclick="GalleryPopUp(', $this->id, ');">Add gallery</a></th></tr><tr><th></th><th>Title</th><th>Description</th><th>Photos</th><th>Live?</th><th>Actions</th></tr>';
		foreach ($this->GetGalleries() as $gid=>$gallery_row)
		{	$gallery = new AdminGallery($gallery_row);
			echo '<tr><td>';
			if ($cover = $gallery->HasCoverImage('thumbnail'))
			{	echo '<img src="', $cover, '" />';
			}
			echo '</td><td>', $this->InputSafeString($gallery->details['title']), '</td><td>', $this->InputSafeString($gallery->details['description']), '</td><td>', count($gallery->photos), '</td><td>', $gallery->details['live'] ? 'Yes' : '', '</td><td><a href="gallery.php?id=', $gallery->id, '">edit</a>&nbsp;|&nbsp;<a onclick="GalleryRemove(', $this->id, ',', $gallery->id, ');">remove from course</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn GalleriesTable
	
	public function AddGallery($gid = 0)
	{	if ($this->id && ($gid = (int)$gid))
		{	if ((!$galleries = $this->GetGalleries()) || !$galleries[$gid])
			{	$sql = 'INSERT INTO gallerytocourse SET cid=' . $this->id . ', gid=' . $gid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn AddGallery
	
	public function RemoveGallery($gid = 0)
	{	if ($this->id && ($gid = (int)$gid))
		{	if (($galleries = $this->GetGalleries()) && $galleries[$gid])
			{	$sql = 'DELETE FROM gallerytocourse WHERE cid=' . $this->id . ' AND gid=' . $gid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn RemoveGallery
	
	public function HeaderInfo(){
		return $this->InputSafeString($this->content['ctitle'].' (Product Code: CE'. $this->details['cid'].')');
	} // end of fn HeaderInfo
	
	function ListBookings()
	{	ob_start();
		echo '<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#ui_modal_popup").jqm();$("#bd_modal_popup").jqm({trigger:".cblDelete"});});</script>', 
			"<div id='cb_bookings_list'>\n", $this->ListBookingsTable(), "</div>\n<!-- START user info modal popup -->\n<div id='ui_modal_popup' class='jqmWindow uipContainer' style='padding-bottom: 20px; width: 760px; margin-left: -380px; top: 10%;'>\n<a href='#' class='jqmClose submit'>Close</a>\n<div id='nb_popup'></div></div>\n<!-- EOF user info modal popup -->\n<!-- START booking delete modal popup -->\n<div id='bd_modal_popup' class='jqmWindow bdContainer' style='padding-bottom: 20px; width: 460px; margin-left: -230px; top: 10%;'>\n<a href='#' class='jqmClose submit'>Close</a>\n<div id='bd_popup'></div></div>\n<!-- EOF booking delete modal popup -->\n";
		return ob_get_clean();
	} // end of fn ListBookings

	function UserRowByID($userid = 0)
	{	$user = array();
		$usersql = 'SELECT * FROM students WHERE userid=' . (int)$userid;
		if ($userresult = $this->db->Query($usersql))
		{	if ($userrow = $this->db->FetchArray($userresult))
			{	$user = $userrow;
			}
		}
		return $user;
	} // end of fn UserRowByID
	
	function BookingArrayFilterOK($booking = array(), $filter = array())
	{	if ($filter)
		{	if ($filter['name'])
			{	
				if (!stristr($booking['user']['firstname'] . ' ' . $booking['user']['surname'], $filter['name']) && !stristr($booking['user']['username'], $filter['name']))
				{	return false;
				}
			}
			if ($filter['att_date'] && is_array($filter['att_date']) && count($filter['att_date']))
			{	$found = false;
				foreach ($filter['att_date'] as $date)
				{	if ($booking['attended'][$date])
					{	$found = true;
						break;
					}
				}
				if (!$found)
				{	return false;
				}
			}
			if ($bookid = (int)$filter['bookid'])
			{	if ($booking['id'] != $bookid)
				{	return false;
				}
			}
			if ($sex = $filter['sex'])
			{	if ($booking['user']['morf'] != $sex)
				{	return false;
				}
			}

		}
		return true;
	} // end of fn BookingArrayFilterOK
	
	public function GetBookingDetails($orderItemID='')
	{	$order_detail = array();
		if($orderItemID!=''){
			$sql = 'SELECT * FROM storeorderitems WHERE id=' . $orderItemID . ' AND is_cancelled_refund="0" ORDER BY id ASC';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$order_detail = $row;
				}
			}
		}
		return $order_detail;
	}
	
	function ListBookingsTable($filter = array())
	{	ob_start();
	
		// set list of bookings to show
		$bookings_toshow = array();
		foreach ($allbookings = $this->GetBookings() as $bookingrow)
		{	$bookingrow['user'] = $this->UserRowByID($bookingrow['student']);
			$bookingrow['attended'] = $this->BookingAttended($bookingrow['id']); //array();
			if ($this->BookingArrayFilterOK($bookingrow, $filter))
			{
				$bookingrow['user_firstname'] = strtolower($bookingrow['user']['firstname']);
				$bookingrow['user_surname'] = strtolower($bookingrow['user']['surname']);
				$bookingrow['detail_info'] = $this->GetBookingDetails($bookingrow['orderitemid']);
				$bookings_toshow[] = $bookingrow;
			}
		}

		if ($canattend = $this->CanAttend())
		{	$rowspantext = ' rowspan="2"';
		}
		$link_paras = array();
		if ($filter)
		{	foreach ($filter as $key=>$value)
			{	if (is_array($value))
				{	foreach ($value as $arr_value)
					{	if ($value)
						{	$link_paras[] = $key . '[]=' . $arr_value;
						}
					}
				} else
				{	if (($key != 'course') && $value)
					{	$link_paras[] = $key . '=' . $value;
					}
				}
			}
		}
		if ($link_paras)
		{	$link_para_string = '&' . implode('&', $link_paras);
		}
		$filter_applied = array();
		if ($filter['name'])
		{	$filter_applied[] = 'in name or email <strong>"' . $this->InputSafeString($filter['name']) . '"</strong>';
		}
		switch ($filter['sex'])
		{	case 'M':
				$filter_applied[] = '<strong>Male students only</strong>';
				break;
			case 'F':
				$filter_applied[] = ' <strong>Female students only</strong>';
				break;
		}
		if ($bookid = (int)$filter['bookid'])
		{	$filter_applied[] = 'booking number <strong>' . $bookid . '</strong>';
		}
		if ($filter['att_date'] && is_array($filter['att_date']))
		{	$fdates = array();
			foreach ($filter['att_date'] as $fdate)
			{	$fdates[] = date('D', strtotime($fdate));
			}
			if ($fdates)
			{	$filter_applied[] = 'attended ' . implode(' / ', $fdates);
			}
		}
		switch ($filter['sort'])
		{	case 'date': $filter_applied[] = 'sorted by date booked';
							usort($bookings_toshow, array($this, 'USortBookingsByDate'));
							break;
			case 'sname': $filter_applied[] = 'sorted by surname';
							usort($bookings_toshow, array($this, 'USortBookingsBySurname'));
							break;
			case 'name': 
			default:		$filter_applied[] = 'sorted by name';
							usort($bookings_toshow, array($this, 'USortBookingsByName'));
		}
		
		echo '<div class="cblFilterInfo"><div class="cblFilterInfoFilter">filter applied: ';
		if ($filter_applied)
		{	echo implode('; ', $filter_applied), ' ... ', count($bookings_toshow), ' / ', count($allbookings), ' bookings listed';
		} else
		{	echo 'none';
		}
		echo '</div><ul><li><a href="coursebookings_csv.php?id=', $this->id, $link_para_string, '" target="_blank">download csv of bookings</a></li>';
		//echo '<li><a href="coursebookings_setmaillist.php?id=', $this->id, $link_para_string, '" target="_blank">send email to these</a></li>';
		echo '</ul><div class="clear"></div></div><table><tr><th', $rowspantext, '>Order No.</th><th', $rowspantext, '>Name<br />Email</th><th', $rowspantext, '>Ticket</th><th', $rowspantext, '>Booked</th>',
			//'<th', $rowspantext, '>Pay method</th><th', $rowspantext, '>Paid</th><th', $rowspantext, '>Pay status</th><th', $rowspantext, '>Booking<br />type</th>',
			'<th', $rowspantext, '>Order Value</th>';
		if ($canattend)
		{	echo '<th colspan="', count($dates = $this->GetDates(true)), '">Attended?</th>';
		}
		echo '<th', $rowspantext, '>Actions</th></tr>';
		if ($canattend)
		{	echo '<tr>';
			foreach ($dates as $stamp=>$date)
			{	echo '<th>', date('D<b\r />j/n', $stamp), '</th>';
			}
			echo '</tr>';
		}
		
		$tickets = array();
		
		foreach ($bookings_toshow as $bookingrow)
		{	if (!$tickets[$bookingrow['ticket']])
			{	$tickets[$bookingrow['ticket']] = $this->GetTicketFromBooking($bookingrow['ticket']);
			}
			$order = $this->GetOrderFromBooking($bookingrow);
			echo '<tr class="stripe', $i++ % 2, '"><td>', $order['id'], '</td><td>', '<a href="member.php?id=', $bookingrow['student'], '">', $bookingrow['user']['firstname'], ' ', $bookingrow['user']['surname'], '</a>', '<br />';
			if ($this->ValidEmail($bookingrow['user']['username']))
			{	echo '<a href="mailto:', $bookingrow['user']['username'], '">', $bookingrow['user']['username'], '</a>';
			} else
			{	echo $bookingrow['user']['username'];
			}
			echo '</td><td>', $this->InputSafeString($tickets[$bookingrow['ticket']]['tname']), '</td><td><a href="order.php?id=', $order['id'], '">', date('d/m/y @ H:i', strtotime($order['orderdate'])), '</a></td><td>',number_format(($bookingrow['detail_info']['totalpricetax']-$bookingrow['detail_info']['discount_total']),2),'</td>';
			if ($canattend)
			{	foreach ($dates as $stamp=>$date)
				{	
					echo '<td class="tdBookAttend" id="dateatt_', $bookingrow['id'], '_', $date, '">', $this->AttendanceDateLink($bookingrow, $date), '</td>';
				}
			}
			echo '<td><a href="booking.php?id=', $bookingrow['id'], '">edit</a>';
		//	if (!$bookingrow['attended'] && !$bookingrow['booking_amountpaid'])
		//	{
		//		echo '&nbsp;|&nbsp;<a class="cblDelete" onclick="BookingDeletePopUp(', $this->id, ',', $bookingrow['id'], ');">X</a>';
		//	}
			if ($histlink = $this->DisplayHistoryLink('coursebookings', $bookingrow['id']))
			{	echo '&nbsp;|&nbsp;', $histlink;
			}
			echo '</td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ListBookingsTable
	
	public function GetBookingsPerDay()
	{	$days = array();
		$sql = 'SELECT COUNT(coursebookings.id) AS book_count, LEFT(storeorders.orderdate, 10) AS book_date FROM coursebookings, storeorderitems, storeorders WHERE coursebookings.orderitemid=storeorderitems.id AND storeorderitems.orderid=storeorders.id AND storeorderitems.is_cancelled_refund="0" AND coursebookings.course=' . $this->id . ' GROUP BY book_date ORDER BY book_date ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if ($nextday = $lastday)
				{	while (($nextday = $this->datefn->SQLDate(strtotime($nextday . ' +1 day'))) < $row['book_date'])
					{	$days[$nextday] = 0;
					}
				}
				$lastday = $row['book_date'];
				$days[$row['book_date']] = $row['book_count'];
			}
		}
		return $days;
	} // end of fn GetBookingsPerDay
	
	public function ListBookingsPerDay()
	{	ob_start();
		if ($perday = $this->GetBookingsPerDay())
		{	echo '<p><img src="coursebookingsperday_graph.php?id=', $this->id, '" /></p>';
		//	$this->VarDump($this->GetBookingsPerDay());
		} else
		{	echo '<h3>There are currently no bookings for this course</h3>';
		}
		return ob_get_clean();
	} // end of fn ListBookingsPerDay
	
	public function GetTicketFromBooking($ticketid = 0)
	{	$ticket = array();
		$sql = 'SELECT * FROM coursetickets WHERE tid=' . (int)$ticketid;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$ticket = $row;
			}
		}
		return $ticket;
	} // end of fn GetTicketFromBooking
	
	function BookingAttended($bookid = 0)
	{	$attended = array();
		$att_sql = 'SELECT * FROM attendance WHERE bookid=' . $bookid . ' ORDER BY attdate';
		if ($att_result = $this->db->Query($att_sql))
		{	while ($att_row = $this->db->FetchArray($att_result))
			{	$attended[$att_row['attdate']] = $att_row['attdate'];
			}
		}
		return $attended;
	} // end of fn BookingAttended
	
	function AttendanceDateLink($booking = array(), $date = "")
	{	ob_start();
		echo "<p class='booking_attendance_date'>", date("D", strtotime($date)), "<input type='checkbox' onclick='AdminBookAttendDate(", $booking["id"], ", \"", $date, "\", ", $booking["attended"][$date] ? "0" : "1", ");' ", $booking["attended"][$date] ? "checked='checked' " : "", "/></p>";
		return ob_get_clean();
	} // end of fn AttendanceDateLink
	
	function USortBookingsByDate($a, $b)
	{	return $a['id'] > $b['id'];
	} // end of fn USortBookingsByDate
	
	function USortBookingsByName($a, $b)
	{	if ($a['user_firstname'] == $b['user_firstname'])
		{	if ($a['user_surname'] == $b['user_surname'])
			{	return $a['bookid'] < $b['bookid'];
			} else
			{	return $a['user_surname'] > $b['user_surname'];
			}
		} else
		{	return $a['user_firstname'] > $b['user_firstname'];
		}
	} // end of fn USortBookingsByName
	
	function USortBookingsBySurname($a, $b)
	{	if ($a['user_surname'] == $b['user_surname'])
		{	if ($a['user_firstname'] == $b['user_firstname'])
			{	return $a['bookid'] < $b['bookid'];
			} else
			{	return $a['user_firstname'] > $b['user_firstname'];
			}
		} else
		{	return $a['user_surname'] > $b['user_surname'];
		}
	} // end of fn USortBookingsBySurname
	
	public function GetOrderFromBooking($booking = array())
	{	$order = array();
		$sql = 'SELECT storeorders.* FROM storeorders, storeorderitems WHERE storeorders.id=storeorderitems.orderid AND storeorderitems.is_cancelled_refund="0" AND storeorderitems.id=' . (int)$booking['orderitemid'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$order = $row;
			}
		}
		return $order;
	} // end of fn GetOrderFromBooking
	
	public function GetOrderItemFromBooking($booking = array())
	{	$orderitem = array();
		$sql = 'SELECT storeorderitems.* FROM storeorderitems WHERE storeorderitems.is_cancelled_refund="0" AND storeorderitems.id=' . (int)$booking['orderitemid'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$orderitem = $row;
			}
		}
		return $orderitem;
	} // end of fn GetOrderItemFromBooking
	
	public function DisplayDates()
	{	ob_start();
		echo date('d-M-y', strtotime($this->details['starttime'])), ' to ', date('d-M-y', strtotime($this->details['endtime']));
		return ob_get_clean();
	} // end of fn DisplayDates
	
	public function StockControlText()
	{	ob_start();
		switch ($this->details['cstockmethod'])
		{	case 0: // unlimited
					echo 'unlimited sales';
					break;
			case 1: // limited by overall course places
					echo 'by overall places (sold: ', (int)$this->details['cbookings'], '/', (int)$this->details['cavailableplaces'], ')';
					break;
			case 2: // limited by ticket, check for any tickets not sold out
					echo 'per ticket type';
					break;
		}
		return ob_get_clean();
	} // end of fn StockControlText
	
	public function DatesTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="4"><a href="coursedate.php?cid=', $this->id, '">Add new dates</a></th></tr><tr><th>Start date</th><th>End date</th><th>Time text</th><th>Actions</th></tr>';
		foreach ($this->dates as $cdid=>$date)
		{	echo '<tr><td>', date('d-M-Y', strtotime($date['startdate'])), '</td><td>', date('d-M-Y', strtotime($date['enddate'])), '</td><td>', $this->InputSafeString($date['timetext']), '</td><td><a href="coursedate.php?id=', $cdid, '">edit</a>&nbsp;|&nbsp;<a href="coursedate.php?id=', $cdid, '&delete=1">delete</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn DatesTable
		
	public function InstructorListContainer()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->InstructorListTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn InstructorListContainer
	
	public function InstructorListTable()
	{	ob_start();
		echo '<form action="courseinstructors.php?id=', $this->id, '" method="post"><table><tr class="newlink"><th colspan="5"><a onclick="InstructorsPopUp(', $this->id, ');">Add instructor</a></th></tr><tr><th></th><th>Name</th><th>Live?</th><th>List order</th><th>Actions</th></tr>';
		foreach ($this->instructors as $inid=>$instructor)
		{	echo '<tr><td>';
			if (file_exists($instructor->GetImageFile('thumbnail')))
			{	echo '<img height="50px" src="', $instructor->GetImageSRC('thumbnail'), '" />';
			} else
			{	echo 'no photo';
			}
			echo '</td><td>', $this->InputSafeString($instructor->GetFullName()), '</td><td>', $instructor->details['live'] ? 'Yes' : '', '</td><td><input type="text" name="listorder[', $inid, ']" value="', (int)$instructor->details['cilistorder'], '" class="number" /></td><td><a onclick="InstructorRemove(', $this->id, ',', $instructor->id, ');">remove from course</a>&nbsp;|&nbsp;<a href="instructoredit.php?id=', $instructor->id, '">view instructor</a></td></tr>';
		}
		if ($this->instructors)
		{	echo '<tr><td colspan="3"></td><td><input type="submit" class="submit" value="Save order" /></td><td></td></tr>';
		}
		echo '</table></form>';
		return ob_get_clean();
	} // end of fn InstructorListTable
	
	public function AddInstructor($inid = 0)
	{	if (!$this->instructors[$inid] && ($inst = new Instructor($inid)) && $inst->id)
		{	$sql = 'INSERT INTO courseinstructors SET cid=' . $this->id . ', inid=' . $inst->id . ', listorder=' . $this->NextInstListOrder();
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetInstructors();
					return true;
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		} else echo 'problem';
	} // end of fn AddInstructor
	
	public function NextInstListOrder()
	{	$lastlistorder = 0;
		foreach ($this->instructors as $inst)
		{	if ($inst->details['cilistorder'] > $lastlistorder)
			{	$lastlistorder = $inst->details['cilistorder'];
			}
		}
		return $lastlistorder + 10;
	} // end of fn NextInstListOrder
	
	public function RemoveInstructor($inid = 0)
	{	if ($this->instructors[$inid])
		{	$sql = 'DELETE FROM courseinstructors WHERE cid=' . $this->id . ' AND inid=' . $inid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetInstructors();
					return true;
				}
			}
		}
	} // end of fn RemoveInstructor

	public function SaveInstListOrder($saveorder = array())
	{	$changed = 0;
		foreach($saveorder as $inid=>$listorder)
		{	if ($this->instructors[$inid] && ($listorder == (int)$listorder))
			{	$sql = 'UPDATE courseinstructors SET listorder=' . (int)$listorder . ' WHERE cid=' . $this->id . ' AND inid=' . $inid;
				if ($result = $this->db->Query($sql))
				{	if ($this->db->AffectedRows())
					{	$changed++;
					}
				}
			}
		}
		if ($changed)
		{	$this->GetInstructors();
		}
		return $changed;
	} // end of fn SaveInstListOrder
	
} // end of defn AdminCourseContent
?>
<?php
class AdminCourseBooking extends CourseBooking
{
	public function __construct($id = null)
	{	parent::__construct($id);
	} // end of fn __construct
	
	public function SetStudent($sid = 0)
	{	return new AdminStudent($sid);
	} // end of fn SetStudent
	
	public function SetCourse($cid = 0)
	{	return new AdminCourse($cid);
	} // end of fn SetCourse

	function RecordAttendance($date = '', $attend = 0)
	{	
		if ($date == $this->datefn->SQLDate(strtotime($date)))
		{	
			if ($attend)
			{	if (!$this->attendance[$date])
				{	$sql = 'INSERT INTO attendance SET bookid=' . $this->id . ', attdate="' . $date . '"';
				} else echo 'already attended';
			} else
			{	if ($this->attendance[$date])
				{	$sql = 'DELETE FROM attendance WHERE bookid=' . $this->id . ' AND attdate="' . $date . '"';
				} else echo 'not attended';
			}
			
			if ($sql)
			{	if ($result = $this->db->Query($sql))
				{	if ($this->db->AffectedRows())
					{	$this->GetAttendance();
						if ($attend)
						{	$this->RecordAdminAction(array('tablename'=>'coursebookings', 'tableid'=>$this->id, 'area'=>'bookings', 'action'=>'attendance added', 'actionto'=>$date, 'actiontype'=>'date'));
							$this->AddRewards();
						} else
						{	$this->RecordAdminAction(array('tablename'=>'coursebookings', 'tableid'=>$this->id, 'area'=>'bookings', 'action'=>'attendance removed', 'actionfrom'=>$date, 'actiontype'=>'date'));
						}
						return true;
					}
				}
			}
		}
		
	} // end of fn RecordAttendance
	
	public function AddRewards()
	{	$referafriend = new ReferAFriend();
		if ($referafriend->GetByReferredID($this->details['student']))
		{	return $referafriend->CreateRewards();
		}
		// look for affiliate referred record for user
		$sql = 'SELECT asid FROM affiliatereferred WHERE sid=' . $this->details['student'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	if (($aff = new AffiliateStudent($row['asid'])) && $aff->id)
				{	$aff->CreateRewardFor($this->details['student']);
				}
			}
		}
	} // end of fn AddRewards
	
	function BookingInfo()
	{	
		$order = $this->GetOrder();
		echo '<p>', $this->DisplayHistoryLink('bookings', $this->id), '</p><table class="adminDetailsHeader"><tr><td class="label">Booking made by</td><td>', '<a href="member.php?id=', $this->student->id, '">', $this->InputSafeString($this->student->details['firstname'] . ' ' . $this->student->details['surname']), '</a>', ' (email: <a href="mailto:', $this->student->details['username'], '">', $this->student->details['username'], '</a>)</td></tr><tr><td class="label">Booked on</td><td>', date('d/m/y @ H:i', strtotime($order['orderdate'])), '</td></tr><tr><td class="label">Course</td><td>', $this->InputSafeString($this->course->content['ctitle']), '<br />', date('d/m/y', strtotime($this->course->details['starttime'])), ' to ', date('d/m/y', strtotime($this->course->details['endtime'])), '</td></tr><tr><td class="label">Price</td><td>', number_format($this->order_item['pricetax'], 2);
		if ($this->order_item['price'] != $this->order_item['pricetax'])
		{	echo ' (before tax: ', number_format($this->order_item['price'], 2), ')';
		}
		echo '</td></tr></table><p><a onclick="BookingXferPopup(', $this->id, ');">Transfer booking to another course</a></p><script>$().ready(function(){$("#xfer_modal_popup").jqm();});</script><!-- START watch list modal popup --><div id="xfer_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="xferModalInner"></div></div><!-- EOF invite code modal popup -->', $this->TransfersTable();
	} // end of fn BookingInfo
	
	function AmendForm()
	{	
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, '');
		$form->AddTextArea('Notes', 'adminnotes', $this->InputSafeString($this->details['adminnotes']), '', 0, 0, $rows = 3, $cols = 40);
		
		$form->AddSubmitButton('', 'Save Notes', 'submit');
		if ($this->CanDelete())
		{	
			echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this booking</a></p>';
		}
		$form->Output();
	} // end of fn AmendForm
	
	function Amend($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		$fields[] = 'adminnotes="' . $this->SQLSafe($data['adminnotes']) . '"';
		if ($this->id && ($data['adminnotes'] != $this->details['adminnotes']))
		{	$admin_actions[] = array('action'=>'Notes', 'actionfrom'=>$this->details['adminnotes'], 'actionto'=>$data['adminnotes']);
		}
		
		if ($set = implode(', ', $fields))
		{	$sql = 'UPDATE coursebookings SET ' . $set . ' WHERE id=' . (int)$this->id;
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$base_parameters = array('tablename'=>'coursebookings', 'tableid'=>$this->id, 'area'=>'bookings');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
					$success[] = 'changes saved';
					$this->Get($this->id);
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Amend
	
	public function TransferBooking($newcourse = 0, $newticket = 0){	
		$fields = array();
		$changefields = array();
		if(($newcourse = (int)$newcourse) && ($newticket = (int)$newticket)){	
			$fields[] = 'course=' . $newcourse;
			$fields[] = 'ticket=' . $newticket;
			
			$oldPrice	= $this->ticket->details['tprice'];
			$new_course = new Course($newcourse);
			$newPrice	= $new_course->tickets[$newticket]['tprice'];
			
			$outstandingPrice	= floatval($newPrice - $oldPrice);
			
			$changefields[] = 'course_new			='.(int)$newcourse;
			$changefields[] = 'ticket_new			='.(int)$newticket;
			$changefields[] = 'course_old			='.(int)$this->details['course'];
			$changefields[] = 'ticket_old			='.(int)$this->details['ticket'];
			$changefields[] = 'outstanding_balance	='.$outstandingPrice;
			$changefields[] = 'changedate			="'.$this->datefn->SQLDateTime() . '"';
			$changefields[] = 'adminuser			='.(int)$_SESSION[SITE_NAME]['auserid'];
			$changefields[] = 'bookid				='.(int)$this->id;
			
			$sql = 'UPDATE coursebookings SET '.implode(', ', $fields).' WHERE id='.(int)$this->id;
			if($result = $this->db->Query($sql)){	
				if($this->db->AffectedRows()){	
					$this->Get($this->id);
					$change_sql = 'INSERT INTO bookingxfers SET ' . implode(', ', $changefields);
					$this->db->Query($change_sql);
					
					if($this->ValidEmail($this->student->details["username"])){
						$fields = array();
						$fields['site_url'] = $this->link->GetLink();
						$fields['firstname'] = $this->student->details['firstname'];
						$fields['booking_link_plain'] = $this->link->GetLink('booking.php?id='.$this->id);
						$fields['booking_link'] = "<a href='". $fields['booking_link_plain'] ."'>". $fields['booking_link_plain'] ."</a>";
						
						$fields['old_course_title'] = $this->course->content['ctitle'].' '. $this->course->ticket->details['tname'];
						$fields['old_course_location'] = $this->course->GetVenue()->GetAddress(' ');
						$fields['old_course_date'] = $this->OutputDate($this->course->details['starttime']);
						
						$fields['course_title'] = $new_course->content['ctitle'].' '. $new_course->tickets[$newticket]['tname'];
						$fields['course_location'] = $new_course->GetVenue()->GetAddress(' ');;
						$fields['course_date'] = $this->OutputDate($new_course->details['starttime']);
						
						$fields['outstanding_amount'] 	= number_format($outstandingPrice, 2).'&pound;';
						$fields['outstanding_note'] 	= ($outstandingPrice > 0)?'Please pay your outstanding amount for given course.':'You will be returned back given amount.';
						
						$t = new MailTemplate('booking_transfer');
						$mail = new HTMLMail;
						$mail->SetSubject($t->details['subject']);
						$mail->Send($this->student->details["username"], $t->BuildHTMLEmailText($fields), $t->BuildHTMLPlainText($fields));
					}					
					return true;
				}
			}
		}
	} // end of fn TransferBooking
	
	public function GetCoursesToTransferTo()
	{	$courses = array();
		$sql = 'SELECT * FROM courses WHERE live=1 AND endtime>"' . $this->datefn->SQLDateTime(strtotime('-1 week')) . '" AND NOT cid=' . (int)$this->course->id . ' ORDER BY starttime ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$course = new Course($row);
				if ($course->tickets)
				{	ob_start();
					echo $this->InputSafeString($course->content['ctitle']), ' on ', date('d/m/y', strtotime($course->details['starttime']));
					if (($venue = $course->GetVenue()) && $venue->id)
					{	echo ' at ', $venue->GetShortDesc();
					}
					$courses[$course->id] = ob_get_clean();
				}
			}
		}
		return $courses;
	} // end of fn GetCoursesToTransferTo
	
	public function TransferBookingForm($course = 0)
	{	ob_start();
		if ($courses = $this->GetCoursesToTransferTo())
		{	echo '<form onsubmit="return false;"><label>Course</label><select id="xferCourseID" onchange="BookingXferCourseChange(', $this->id, ');"><option value="0">-- choose new course --</option>';
			foreach ($courses as $cid=>$ctext)
			{	echo '<option value="', $cid, '"', $cid == $course ? ' selected="selected"' : '', '>', $ctext, '</option>';
			}
			echo '</select><br /><div id="xferTicketsContainer">', $this->TransferBookingFormTickets($course), '</div><label>&nbsp;</label><input class="submit" type="submit" value="Change Booking" id="xferCourseSubmit" style="display: none;" onclick="BookingXferSubmit(', $this->id, ');" /></form>';
		} else
		{	echo '<p>There are currently no courses to transfer to</p>';
		}
		return ob_get_clean();
	} // end of fn TransferBookingForm
	
	public function TransferBookingFormTickets($cid = 0)
	{	ob_start();
		if ($cid && ($course = new Course($cid)) && $course->tickets)
		{	echo '<label>Tickets</label><select id="xferTicketID" onchange="BookingXferTicketChange();"><option value="0">-- choose ticket --</option>';
			foreach ($course->tickets as $ticketid=>$ticket)
			{	echo '<option value="', $ticketid, '">', $this->InputSafeString($ticket['tname']), '</option>';
			}
			echo '</select><br />';
		}
		return ob_get_clean();
	} // end of fn TransferBookingForm
	
	public function GetTransfers()
	{	$transfers = array();
		$sql = 'SELECT * FROM bookingxfers WHERE bookid=' . $this->id . ' ORDER BY changedate';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$transfers[$row['bxid']] = $row;
			}
		}
		return $transfers;
	} // end of fn GetTransfers
	
	public function TransfersTable(){	
		if($transfers = $this->GetTransfers()){		
			echo '<table><tr><th>Transferred</th><th>From</th><th>To</th><th>Outstanding Balance</th><th>By</th></tr>';
			foreach ($transfers as $transfer){	
				$old_course = new Course($transfer['course_old']);
				$new_course = new Course($transfer['course_new']);
				$adminuser = new AdminUser($transfer['adminuser']);
				//$this->VarDump($adminuser);
				echo '<tr><td>', date('d M y @H:i', strtotime($transfer['changedate'])), '</td><td>', $this->InputSafeString($old_course->content['ctitle']), ' on ', date('d/m/y', strtotime($old_course->details['starttime']));
				
				if (($venue = $old_course->GetVenue()) && $venue->id){
					echo '<br />at ', $venue->GetShortDesc();
				}
				
				echo '</td><td>', $this->InputSafeString($new_course->content['ctitle']), ' on ', date('d/m/y', strtotime($new_course->details['starttime']));
				
				if (($venue = $new_course->GetVenue()) && $venue->id){
					echo '<br />at ', $venue->GetShortDesc();
				}
				
				echo '</td><td>';
				
				if($transfer['outstanding_balance']>0){
				 	echo 'To Get : ',number_format($transfer['outstanding_balance'], 2);
				}else{
					echo 'To Pay : ',number_format(($transfer['outstanding_balance']*-1), 2);
				}
				
				echo '</td><td>', $adminuser->username, '</td></tr>';
			}
			
			echo '</table>';
		}
	} // end of fn TransfersTable
	
} // end of class AdminCourseBooking

?>
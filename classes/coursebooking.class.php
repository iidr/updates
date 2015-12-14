<?php
class CourseBooking extends BlankItem
{	public $student;
	public $attendance = array();
	public $course;
	public $ticket;
	public $order_item = array();
	
	public function __construct($id = null)
	{	parent::__construct($id, 'coursebookings', 'id');
	} // end of fn __construct
	
	protected function ResetExtra()
	{	$this->student = null;
		$this->ticket = null;
		$this->course = null;
		$this->attendance = array();
		$this->order_item = array();
	} // end of fn ResetExtra
	
	protected function GetExtra()
	{	$this->student = $this->SetStudent($this->details['student']);
		$this->course = $this->SetCourse($this->details['course']);
		$this->ticket = $this->SetTicket($this->details['ticket']);
		$this->order_item = $this->SetOrderItem($this->details['orderitemid']);
		$this->GetAttendance();
	} // end of fn GetExtra

	function GetAttendance()
	{	$this->attendance = array();
		$sql = 'SELECT * FROM attendance WHERE bookid=' . $this->id . ' ORDER BY attdate';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->attendance[$row['attdate']] = $row['attdate'];
			}
		}
		
	} // end of fn GetAttendance
	
	public function GetOrder()
	{	$order = array();
		$sql = 'SELECT storeorders.* FROM storeorders, storeorderitems WHERE storeorders.id=storeorderitems.orderid AND storeorderitems.is_cancelled_refund="0" AND storeorderitems.id=' . (int)$this->details['orderitemid'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$order = $row;
			}
		}
		return $order;
	} // end of fn GetOrderFromBooking
	
	public function SetStudent($sid = 0)
	{	return new Student($sid);
	} // end of fn SetStudent
	
	public function SetCourse($cid = 0)
	{	return new Course($cid);
	} // end of fn SetCourse
	
	public function SetTicket($tid = 0)
	{	return new CourseTicket($tid);
	} // end of fn SetTicket
	
	public function SetOrderItem($id = 0)
	{
		if(is_array($id))
		{	return $id;
		} else
		{	if($result = $this->db->Query('SELECT * FROM storeorderitems WHERE storeorderitems.is_cancelled_refund="0" AND id='. (int)$id))
			{	if($row = $this->db->FetchArray($result))
				{	return $this->SetOrderItem($row);	
				}
			}
		}
		
		return array();
		
	} // end of fn SetOrderItem
	
	public function SetQty($qty = 1)
	{
		$this->details['qty'] = $qty;
	} // end of fn SetQty
	
	public function Save()
	{
		$flds = array();
		
		if ($this->course->id && $this->ticket->id && $this->student->id)
		{
			$flds[] = 'course='. (int)$this->course->id;
			$flds[] = 'ticket='. (int)$this->ticket->id;
			$flds[] = 'student='. (int)$this->student->id;
			
			if(isset($this->order_item['id']))
			{	$flds[] = 'orderitemid='. (int)$this->order_item['id'];	
			}
			
			$sql = 'INSERT INTO coursebookings SET ' . implode(', ', $flds);
			
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($id = $this->db->InsertID())
					{	return $this->id = $id;	
					}
				}
			}
		}
	} // end of fn Save
	
	public function CreateFromOrderItem($student, $product, $item = false)
	{	$fields = array();
		$fail = array();
		$success = array();
		
		if ($student = (int)$student->id)
		{	$fields[] = 'student=' . $student;
		} else
		{	$fail[] = 'Student not found';
		}
		
		if ($course = (int)$product->course->id)
		{	$fields[] = 'course=' . $course;
		} else
		{	$fail[] = 'Course not found';
		}
		
		if ($ticket = (int)$product->ticket->id)
		{	$fields[] = 'ticket=' . $ticket;
		} else
		{	$fail[] = 'Ticket not found';
		}
		
		if ($orderitemid = (int)$item['id'])
		{	$fields[] = 'orderitemid=' . $orderitemid;
		}
		
		if (!$fail)
		{	$sql = 'INSERT INTO coursebookings SET ' . implode(', ', $fields);
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows() && ($id = $this->db->InsertID()))
				{	$this->Get($id);
					$product->UpdateBookingQty(1);
					$success[] = 'new booking created';
					// check for any referral rewards
					if ($orderitemid)
					{	$ref_sql = 'SELECT * FROM orderitemrewards WHERE itemid=' . $orderitemid;
						if ($ref_result = $this->db->Query($ref_sql))
						{	while ($ref_row = $this->db->FetchArray($ref_result))
							{	$usedref_sql = 'INSERT INTO referrewardsused SET rrid=' . $ref_row['rrid'] . ', usedtime="' . $this->datefn->SQLDateTime() . '", usedamount=' . $ref_row['amount'] . ', orderitem=' . $orderitemid;
								$this->db->Query($usedref_sql);
							}
						}
						$ref_sql = 'SELECT * FROM orderitemaffrewards WHERE itemid=' . $orderitemid;
						if ($ref_result = $this->db->Query($ref_sql))
						{	while ($ref_row = $this->db->FetchArray($ref_result))
							{	$usedref_sql = 'INSERT INTO affrewardsused SET awid=' . $ref_row['awid'] . ', usedtime="' . $this->datefn->SQLDateTime() . '", usedamount=' . $ref_row['amount'] . ', orderitem=' . $orderitemid;
								$this->db->Query($usedref_sql);
							}
						}
					}
				}
			}
		}

		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn CreateFromOrderItem
	
	public function IsPayOnDay()
	{
		if ($this->order_item['payonday'])
		{	return $this->order_item['totalpricetax'] / $this->order_item['qty'];	
		}
	} // end of fn IsPayOnDay
	
	public function IsGift()
	{
		$sql = 'SELECT giftedby FROM storegifts WHERE itemid = ' . (int)$this->order_item['id'] . ' AND student = ' . (int)$this->student->id;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return new Student($row['giftedby']);	
			}
		}
	} // end of fn IsGift
	
	public function SendStudentEmail($friendName='',$friendEmail=''){
		$friendName=trim($friendName);
		$friendEmail=trim($friendEmail);
			
		if($this->ValidEmail($this->student->details['username'])){
			$fields = array();
			$fields['site_url'] = $this->link->GetLink();
			$fields['firstname'] = $this->student->details['firstname'];
			$fields['course_title'] = $this->InputSafeString($this->course->content['ctitle'] .' (Code: CE'. $this->course->content['ccid'].')');
			$fields['course_location'] = $this->course->GetVenue()->GetAddress(' ');
			$fields['course_date'] = $this->OutputDate($this->course->details['starttime']);
			$fields['friend_name'] = $this->InputSafeString($friendName);
			
			$fields['booking_link_plain'] = $this->link->GetLink('booking.php?id='. $this->id);
			$fields['booking_link'] = '<a href="' . $fields['booking_link_plain'] . '">' . $fields['booking_link_plain'] . '</a>';			
			
			if($gift = $this->IsGift()){
				$fields['payment_price_plain'] = 'This booking was booked by ' . $this->InputSafeString($gift->GetName());
				$fields['payment_price'] = '<p>'. $fields['payment_price_plain'] .'</p>';
			}else{	
				if($price = $this->IsPayOnDay()){
					$fields['payment_price_plain'] = 'A payment of ' . $this->formatPrice($price) . ' is due on the day of the course.';
					$fields['payment_price'] = '<p>' . $fields['payment_price_plain'] . '</p>';
				}
			}
			
			if($friendName!='' && $friendEmail!='' && $this->student->details['username']!=$friendEmail){
				$templateName = 'booked_by_friend';
			}elseif($friendName=='' && $friendEmail!='' && $this->student->details['username']!=$friendEmail){
				$templateName = 'booked_by_friend_already_member';
			}else{
				$templateName = 'booking';					
			}
			
			$t = new MailTemplate($templateName);
			$mail = new HTMLMail;
			
			//$t = new MailTemplate('booking');
			//$mail = new HTMLMail;
			//$fields['firstname'] = $friendName;
			//$mail->SetSubject($t->details['subject']);
			//$mail->Send($friendEmail, $t->BuildHTMLEmailText($fields), $t->BuildHTMLPlainText($fields));
			$mail->SetSubject($t->details['subject']);	
			$mail->Send($this->student->details["username"], $t->BuildHTMLEmailText($fields), $t->BuildHTMLPlainText($fields));
		}	
	} // end of fn SendStudentEmail
	
	public function CanDelete()
	{	return $this->id && !$this->attendance;
	} // end of fn CanDelete
	
	public function StatusString()
	{	
	} // end of fn StatusString
	
} // end of class CourseBooking
?>
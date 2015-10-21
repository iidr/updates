<?php
class MemberPage extends AdminAKMembersPage
{	var $member;
	var $member_option = '';

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AKMembersLoggedInConstruct()
	{	parent::AKMembersLoggedInConstruct();
		$this->member = new AdminStudent($_GET['id']);
		
		$this->css[] = 'adminmembers.css';
		$this->css[] = 'admincoursepage.css';
		$this->js[] = 'admin_member_nonemail.js';

		if ($this->member->id)
		{	$this->breadcrumbs->AddCrumb('member.php?id=' . $this->member->id, $this->InputSafeString($this->member->details['firstname'] . ' ' . $this->member->details['surname']));
		}
	} // end of fn AKMembersLoggedInConstruct
	
	function AKMembersBody()
	{	if (!$this->member->details['country'] || $this->user->CanAccessCountry($this->member->details['country']))
		{	if ($this->member->id)
			{	$this->MemberViewBody();
			}
		}
	} // end of fn AKMembersBody
	
	public function MemberViewBody()
	{	$this->MembersBodyMenu();
	} // end of fn MemberViewBody
	
	public function BodyMenuOptions()
	{	$options = array();
		if ($this->member->id)
		{	
			$options['details'] = array('link'=>'member.php?id=' . $this->member->id, 'text'=>$this->InputSafeString($this->member->details['firstname'] . ' ' . $this->member->details['surname']));
			$options['edit'] = array('link'=>'memberedit.php?id=' . $this->member->id, 'text'=>'Edit');
			$options['bookings'] = array('link'=>'memberbookings.php?id=' . $this->member->id, 'text'=>'Bookings');
			$options['orders'] = array('link'=>'memberorders.php?id=' . $this->member->id, 'text'=>'Orders');
			$options['refer'] = array('link'=>'memberreferrals.php?id=' . $this->member->id, 'text'=>'Refer-a-Friend');
		}
		return $options;
	} // end of fn BodyMenuOptions
	
	function MembersBodyMenu()
	{	
		if ($this->member->id)
		{	echo '<div class="course_edit_menu"><ul>';
			foreach ($this->BodyMenuOptions() as $key=>$option)
			{	echo '<li', $this->member_option == $key ? ' class="selected"' : '', '><a href="', $option['link'], '">', $option['text'], '</a></li>';
			}
			echo '</ul><div class="clear"></div></div><div class="clear"></div>';
		}
	} // end of fn MembersBodyMenu
	
	function BookingsList()
	{	$bookings = $this->member->GetBookings();
	//	$this->VarDump($bookings);
		$can_course = $this->user->CanUserAccess('course-schedule');
		$venues = array();
		$orders = array();
		echo '<h3>Bookings made</h3><table><tr class="newlink"><th colspan="6"><a href="memberbook.php?id=', $this->member->id, '">create new booking</a></th></tr><tr><th>Course</th><th>Venue</th><th>Course dates</th><th>Ordered</th><th>Pay status</th><th>Actions</th></tr>';
		foreach ($bookings as $booking)
		{	if (!isset($venues[$booking->course->details['cvenue']]))
			{	$venues[$booking->course->details['cvenue']] = new Venue($booking->course->details['cvenue']);
			}
			$order = $this->GetOrderFromBooking($booking);
			echo '<tr><td>', $can_course ? ('<a href="courseedit.php?id=' . $booking->course->id . '">') : '', $this->InputSafeString($booking->course->content['ctitle']), $can_course ? '</a>' : '', '</td><td>', $venues[$booking->course->details['cvenue']]->GetAddress(', '), '</td><td>', date('d/m/y', strtotime($booking->course->details['starttime'])), ' to ', date('d/m/y', strtotime($booking->course->details['endtime'])), '</td><td><a href="order.php?id=', $order['id'], '">', date('d/m/y @ H:i', strtotime($order['orderdate'])), '</a></td><td>', (int)$order['paiddate'] ? date('p\a\i\d d/m/y @H:i', strtotime($order['paiddate'])) : 'not paid', '</td><td><a href="booking.php?id=', $booking->id, '">edit</a>';
			if ($booking->CanDelete())
			{	echo '&nbsp;|&nbsp;<a href="booking.php?id=', $booking->id, '&delete=1">delete</a>';
			}
			if ($histlink = $this->DisplayHistoryLink('bookings', $booking->id))
			{	echo '&nbsp;|&nbsp;', $histlink;
			}
			echo '</td></tr>';
		}
		echo '</table>';
	} // end of fn BookingsList
	
	public function GetOrderFromBooking($booking)
	{	$order = array();
		$sql = 'SELECT storeorders.* FROM storeorders, storeorderitems WHERE storeorders.id=storeorderitems.orderid AND storeorderitems.id=' . (int)$booking->details['orderitemid'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$order = $row;
			}
		}
		return $order;
	} // end of fn GetOrderFromBooking
	
} // end of defn MemberPage
?>
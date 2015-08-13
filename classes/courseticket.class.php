<?php
class CourseTicket extends Base
{	var $details = array();
	var $id = 0;
	
	function __construct($id = 0)
	{	parent::__construct();
		$this->Get($id);
	} // fn __construct
	
	function Reset()
	{	$this->details = array();
		$this->id = 0;
	} // end of fn Reset
	
	function Get($id = 0)
	{	$this->Reset();
		if (is_array($id))
		{	$this->details = $id;
			$this->id = $id['tid'];
		} else
		{	if ($result = $this->db->Query('SELECT * FROM coursetickets WHERE tid=' . (int)$id))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
				}
			}
		}
	} // end of fn Get
	
	public function GetStatus()
	{	return new ProductStatus($this->details['tstatus']);	
	} // end of fn GetStatus

	public function Is($name = '')
	{	$status = new ProductStatus((int)$this->details['tstatus']);
		
		if ($status->details['name'] == $name)
		{	return $status;
		}
	} // end of fn Is
	
	public function UpdateQty($qty = 0)
	{
		$sql = 'UPDATE coursetickets SET tqty = tqty ' . ($qty >= 0 ? '+ ' : '- ') . abs($qty) . ' WHERE tid = '. (int)$this->id;	
				
		if ($result = $this->db->Query($sql))
		{
			if ($this->db->AffectedRows())
			{
				$this->Get($this->id);
				
				if ($this->details['tqty'] <= 0)
				{	$this->UpdateStatus('sold_out');
				}
			}
		}
	} // end of fn UpdateQty
	
	public function UpdateBookingQty($qty = 0)
	{
		$sql = 'UPDATE coursetickets SET tbooked=tbooked ' . ($qty >= 0 ? '+ ' : '- ') . abs($qty) . ' WHERE tid='. (int)$this->id;	
				
		if ($result = $this->db->Query($sql))
		{
			if ($this->db->AffectedRows())
			{
				$this->Get($this->id);
				
				if (!$this->IsBookable())
				{	$this->UpdateStatus('sold_out');
				}
			}
		}
	} // end of fn UpdateBookingQty
	
	public function IsBookable()
	{	return $this->details['tqty'] > $this->details['tbooked'];
	} // end of fn IsBookable

	public function UpdateStatus($status = '')
	{
		if (!$id = (int)$status)
		{	if ($result = $this->db->Query('SELECT * FROM productstatus WHERE name = "'. $this->SQLSafe($status) .'"'))
			{	if ($row = $this->db->FetchArray($result))
				{	$id = $row['id'];	
				}
			}
		}
		
		if ($id)
		{	$this->db->Query('UPDATE coursetickets SET tstatus = '. (int)$id .' WHERE tid = '. (int)$this->id);
			return true;
		}
	} // end of fn UpdateStatus
	
	public function GetBookings()
	{	$bookings = array();
		$sql = 'SELECT * FROM coursebookings WHERE ticket=' . $this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$bookings[$row['id']] = $row;
			}
		}
		return $bookings;
	} // end of fn GetBookings
	
	public function UpdateStatusFromBookings()
	{	if ($this->id)
		{	$this->UpdateStatus(count($this->GetBookings()) >= $this->details['tqty'] ? 'sold_out' : 'in_stock');
		}
	} // end of fn UpdateStatusFromBookings
	
} // end of defn CourseTicket
?>
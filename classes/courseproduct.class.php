<?php
class CourseProduct extends Product
{	public $id;
	public $course;
	public $ticket;
	
	public function __construct($tid = null)
	{	parent::__construct();
		
		if (!is_null($tid))
		{	$this->Get($tid);
		}
	} // end of fn __construct
	
	public function Reset()
	{	$this->id = 0;
		$this->course = null;
		$this->ticket = null;	
	} // end of fn Reset
	
	public function Get($tid = 0)
	{
		if (is_a($tid, 'CourseTicket'))
		{	$this->id = $tid->id;
			$this->ticket = $tid;
			$this->course = new Course($tid->details['cid']);
		} else
		{	if (is_array($tid))
			{	$this->Get(new CourseTicket($tid));
			} else
			{	if ($result = $this->db->Query('SELECT * FROM coursetickets WHERE tid = '. (int)$tid))
				{	if ($row = $this->db->FetchArray($result))
					{	$this->Get($row);
				//		$this->id = $row['tid'];
				//		$this->ticket = new CourseTicket($row); 
				//		$this->course = new Course($row['cid']);
					}
				}
			}
		}
	} // end of fn Get
	
	public function ProductID()
	{	return $this->course->ProductID();
	} // end of fn ProductID
	
	public function GetBundles()
	{	return parent::GetBundles('course');
	} // end of fn GetBundles
	
	public function AllowPayOnDay()
	{	return $this->course->details['cpayonday'];
	} // end of fn AllowPayOnDay
	
	public function GetName($with_ticket = false)
	{	return $this->course->content['ctitle'] . ($with_ticket ? (' (' . $this->ticket->details['tname'] . ')') : '');
	} // end of fn GetName
	
	public function GetPrice()
	{	return $this->ticket->details['tprice'];
	} // end of fn GetPrice
	
	public function GetPriceWithTax()
	{
		if ($this->ticket->details['taxid'])
		{	
			if ($tax = new Tax($this->ticket->details['taxid']))
			{
				$price = $this->ticket->details['tprice'];
				$price += $tax->Calculate($this->ticket->details['tprice']);	
			}
		} else
		{
			$price = $this->GetPrice();	
		}
		
		return $price;
	} // end of fn GetPriceWithTax
	
	public function InStock()
	{
			switch ($this->course->details['cstockmethod'])
			{
				case '0': // unlimited
						return true;	
				case '1': // by available places on course
						return $this->course->details['cavailableplaces'] > $this->course->details['cbookings'];
				case '2': // tickets control stock
						return $this->ticket->IsBookable();
			}
		
		return false;
	} // end of fn InStock
	
	public function Is($name = '')
	{	return parent::Is($name, $this->ticket->details['tstatus']);	
	} // end of fn Is
	
	public function HasQty($qty = 0)
	{	
		if ($this->InStock())
		{
			switch($this->course->details['cstockmethod'])
			{
				case '0':
					return true;
				case '1':
					return (($this->course->details['cavailableplaces'] - $this->course->details['cbookings'] - $qty) >= 0);
				case '2':	
					return (($this->ticket->details['tqty'] - $this->ticket->details['tbooked'] - $qty) >= 0);
			}
		}
	} // end of fn HasQty
	
	public function HasShipping()
	{	return false;
	} // end of fn HasShipping
	
	public function HasTax()
	{	return $this->details['taxid'];	
	} // end of fn HasTax
	
	public function IsLive()
	{	return $this->course->details['live'] && $this->ticket->details['live'];	
	} // end of fn IsLive
	
	public function GetLink()
	{	return $this->link->GetCourseLink($this->course);
	} // end of fn GetLink
	
	public function GetStatus()
	{	return new ProductStatus($this->ticket->details['tstatus']);
	} // end of fn GetStatus
	
	public function HasImage($size = '')
	{	return $this->course->HasImage($size);	
	} // end of fn HasImage
	
	public function UpdateQty($qty = 0)
	{
		if ($this->course->details['cstockmethod'] == 1)
		{	$this->course->UpdateQty($qty);
		} else
		{	if ($this->course->details['cstockmethod'] == 2)
			{	$this->ticket->UpdateQty($qty);
			}
		}
	} // end of fn UpdateQty
	
	public function UpdateBookingQty($qty = 0)
	{	$this->course->UpdateBookingQty($qty);
		$this->ticket->UpdateBookingQty($qty);
	} // end of fn UpdateBookingQty

} // end of class CourseProduct
?>
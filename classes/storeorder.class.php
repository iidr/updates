<?php
class StoreOrder extends BlankItem
{	public $items = array();
	public $bundles = array();
	public $status_history = array();
	
	public function __construct($id = null)
	{	parent::__construct($id, 'storeorders', 'id');
	} // end of fn __construct
	
	protected function ResetExtra()
	{	$this->items = array();
		$this->bundles = array();
	} // end of fn ResetExtra
	
	public function GetItems()
	{	
		if (!$this->items)
		{	if ($result = $this->db->Query('SELECT * FROM storeorderitems WHERE orderid=' . (int)$this->id))
			{	while ($row = $this->db->FetchArray($result))
				{	$this->items[$row['id']] = $row;
					if ($sub = $this->GetItemSub($row['id']))
					{	$this->items[$row['id']]['sub'] = $sub;
					}
					$this->items[$row['id']]['discounts'] = array();
					$disc_sql = 'SELECT * FROM storeorderitemdiscounts WHERE itemid=' . $row['id'];
					if ($disc_result = $this->db->Query($disc_sql))
					{	while ($disc_row = $this->db->FetchArray($disc_result))
						{	$this->items[$row['id']]['discounts'][$disc_row['sidid']] = $disc_row;
						}
					}
				}
			}
		}
		
		return $this->items;
	} // end of fn GetItems
	
	public function GetItemSub($itemid = 0)
	{	$sql = 'SELECT * FROM storeordersubs WHERE orderitem=' . (int)$itemid;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row;
			}
		}
		return false;
	} // end of fn GetItemSub
	
	public function GetReferrerRewards($itemid = 0)
	{	$rewards = array();
		$sql = 'SELECT * FROM orderitemrewards WHERE itemid=' . (int)$itemid;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$rewards[$row['oirid']] = $row;
			}
		}
		return $rewards;
	} // end of fn GetReferrerRewards
	
	public function GetAllReferrerRewards()
	{	$rewards = array();
		foreach ($this->items as $itemid=>$item)
		{	foreach ($this->GetReferrerRewards($itemid) as $oirid=>$reward)
			{	$rewards[$oirid] = $reward;
			}
		}
		return $rewards;
	} // end of fn GetAllReferrerRewards
	
	public function GetAffRewards($itemid = 0)
	{	$rewards = array();
		$sql = 'SELECT * FROM orderitemaffrewards WHERE itemid=' . (int)$itemid;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$rewards[$row['oirid']] = $row;
			}
		}
		return $rewards;
	} // end of fn GetAffRewards
	
	public function GetAllAffRewards()
	{	$rewards = array();
		foreach ($this->items as $itemid=>$item)
		{	foreach ($this->GetAffRewards($itemid) as $oirid=>$reward)
			{	$rewards[$oirid] = $reward;
			}
		}
		return $rewards;
	} // end of fn GetAllAffRewards
	
	public function GetItemAttendees($itemid = 0)
	{	$attendees = array();
		$sql = 'SELECT * FROM orderattendees WHERE itemid=' . (int)$itemid;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$attendees[$row['aid']] = $row;
			}
		}
		return $attendees;
	} // end of fn GetItemAttendees
	
	public function GetAttendees()
	{	$attendees = array();
		$sql = 'SELECT * FROM orderattendees WHERE orderid=' . (int)$this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$attendees[$row['aid']] = $row;
			}
		}
		return $attendees;
	} // end of fn GetAttendees
	
	public function GetBookings()
	{	
		$bookings = array();
		foreach ($this->GetItems as $item)
		{	if ($result = $this->db->Query('SELECT * FROM storeorderitems WHERE orderid=' . (int)$this->id))
			{	while ($row = $this->db->FetchArray($result))
				{	$bookings[] = $row;	
				}
			}
		}
		
		return $bookings;
	} // end of fn GetBookings
	
	public function GetBundles()
	{	
		if (!$this->bundles)
		{	if ($result = $this->db->Query('SELECT * FROM storeorderbundles WHERE orderid=' . (int)$this->id))
			{	while ($row = $this->db->FetchArray($result))
				{	$this->bundles[] = $row;	
				}
			}
		}
		
		return $this->bundles;
	} // end of fn GetBundles
	
	public function Is($status = '')
	{
		$id = (int)$status;
		
		if ($id == 0)
		{	if ($result = $this->db->Query('SELECT * FROM storeorderstatus WHERE name = "' . $this->SQLSafe($status) . '" '))
			{	if ($row = $this->db->FetchArray($result))
				{	$id = $row['id'];	
				}
			}
		}
		
		if ($current = $this->GetStatus())
		{	return ($current['statusid'] == $id); 
		}
	} // end of fn Is
	
	public function IsPaid()
	{	
		// Free amount orders won't have PayPal payments
		if ($this->GetTotal(true) == 0)
		{	return true;
		} else
		{	// Make sure PayPal payment is paid
			if ($transaction = $this->GetLatestTransaction())
			{	return $transaction->IsPaid();
			}
		}
	} // end of fn IsPaid
	
	public function GetStatus()
	{	if($history = $this->GetStatusHistory())
		{	return $history[0];	
		}
	} // end of fn GetStatus
	
	public function GetStatusHistory()
	{	if(!$this->status_history)
		{	if($result = $this->db->Query('SELECT * FROM storeorderstatushistory WHERE orderid = ' . (int)$this->id . ' ORDER BY dateadded DESC'))
			{	while($row = $this->db->FetchArray($result))
				{	$this->status_history[] = $row;
				}
			}
		}
		
		return $this->status_history;
	} // end of fn GetStatusHistory
	
	// Get latest PayPal payment based on payment_date field
	public function GetLatestTransaction()
	{
		$latest = 0;
		$index = 0;
		
		if($transactions = $this->GetTransactions())
		{
			foreach($transactions as $key => $t)
			{
				if($t->GetDate() > $latest)
				{
					$latest = $t->GetDate();
					$index = $key;	
				}
			}
			
			return $transactions[$index];
		}
		
		return false;
	} // end of fn GetLatestTransaction
	
	// Get PayPal payment history
	public function GetTransactions()
	{
		$transactions = array();
		
		$sql = 'SELECT * FROM paypalpayments WHERE orderid = ' . (int)$this->id . ' ORDER BY payment_date DESC';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$transactions[] = new PayPalStandardPayment($row);	
			}
		}
		
		return $transactions;
	} // end of fn GetTransactions
	
	public function GetLastUpdated()
	{
		$last = $this->GetStatus();
		return $last['dateadded'];
	} // end of fn GetLastUpdated
	
	public function GetTotal($today = false)
	{
		$total = 0.00;
		
		foreach ($this->GetItems() as $item)
		{
			$total += $item['totalpricetax'];
			$total -= $item['discount_total'];
		}
		
		$total += $this->details['delivery_price'];
		
		return $total;
	} // end of fn GetTotal
	
	public function GetRealTotal()
	{	$total = $this->GetTotal();
		foreach ($this->GetBundles() as $bundle)
		{	$total -= $bundle['totaldiscount'];
		}
		if ($this->details['discid'])
		{	$total -= $this->details['discamount'];
		}
		if ($this->details['txfee'] > 0)
		{	$total += $this->details['txfee'];
		}
		return $total;
	} // end of fn GetRealTotal
	
	public function CreateFromSession(Student $s)
	{
		// create new order based on session
		if(isset($_SESSION['cart']) && $s->id)
		{
			$fields = array();
			
			$fields['sid'] = $s->id;
			$fields['orderdate'] = $this->datefn->SQLDateTime();
			$fields['currency'] = 'GBP';
			
			// customer
			$fields['title'] = $s->details['title'];
			$fields['firstname'] = $s->details['firstname'];
			$fields['surname'] = $s->details['surname'];
			$fields['email'] = $s->details['username'];
			$fields['phone'] = $s->details['phone'];
			$fields['phone2'] = $s->details['phone2'];
			$fields['discamount'] = 0;
			
			// payment address
			if(isset($_SESSION['order']['payment_address']))
			{
				$fields['payment_firstname'] = $_SESSION['order']['payment_address']['firstname'];
				$fields['payment_surname'] = $_SESSION['order']['payment_address']['surname'];
				$fields['payment_address1'] = $_SESSION['order']['payment_address']['address1'];
				$fields['payment_address2'] = $_SESSION['order']['payment_address']['address2'];
				$fields['payment_address3'] = $_SESSION['order']['payment_address']['address3'];
				$fields['payment_city'] = $_SESSION['order']['payment_address']['city'];
				$fields['payment_postcode'] = $_SESSION['order']['payment_address']['postcode'];
				$fields['payment_country'] = $_SESSION['order']['payment_address']['country'];
				$fields['payment_phone'] = $_SESSION['order']['payment_address']['phone'];
			}
			
			// delivery address (if set)
			if(isset($_SESSION['order']['delivery_address']))
			{
				$fields['delivery_firstname'] = $_SESSION['order']['delivery_address']['firstname'];
				$fields['delivery_surname'] = $_SESSION['order']['delivery_address']['surname'];
				$fields['delivery_address1'] = $_SESSION['order']['delivery_address']['address1'];
				$fields['delivery_address2'] = $_SESSION['order']['delivery_address']['address2'];
				$fields['delivery_address3'] = $_SESSION['order']['delivery_address']['address3'];
				$fields['delivery_city'] = $_SESSION['order']['delivery_address']['city'];
				$fields['delivery_postcode'] = $_SESSION['order']['delivery_address']['postcode'];
				$fields['delivery_country'] = $_SESSION['order']['delivery_address']['country'];
				$fields['delivery_phone'] = $_SESSION['order']['delivery_address']['phone'];
			}
			
			// delivery method
			if(isset($_SESSION['order']['delivery']))
			{
				$method = new DeliveryOption($_SESSION['order']['delivery']);
				
				if($method->id)
				{
					$fields['delivery_id'] = $method->id;
					$fields['delivery_price'] = $method->GetPrice();
				}	
			}
			
			// get items
			$items = array();
			$itemdiscounts = array();
			$rewards = array();
			$subsused = array();
			
			$cart = new StoreCart();
			$attendees = array();
			
			foreach ($cart->items as $rowid => $product)
			{
				// maybe store discounts in $_SESSION['discounts']?
				$item = array();
				$item['pid'] = $product['id'];
				$item['ptype'] = $product['type'];
				$item['title'] = $product['product']->GetName();
				$item['qty'] = $product['qty'];
				$item['price'] = $product['price'];
				$item['pricetax'] = $product['price_with_tax'];
				$item['totalprice'] = $product['total'];
				$item['totalpricetax'] = $product['total_with_tax'];
				$item['payonday'] = $product['is_pay_on_day'] ? '1' : '0';
				$item['discount_total'] = $cart->ItemDiscountSum($product['discounts']);
				$item['discamount'] = 0;
				
				if ($subid = (int)$product['sub'])
				{	$subsused[$rowid] = array('subid'=>$subid, 'amount'=>$product['price_with_tax']);
				}
			
				if ($product_rewards = $cart->GetRewardsUsedForProduct($rowid))
				{	$rewards[$rowid] = $product_rewards;
				}
			
				if ($product_affrewards = $cart->GetAffRewardsUsedForProduct($rowid))
				{	$affrewards[$rowid] = $product_affrewards;
				}
				
				if (is_array($cart->discount) && $cart->discount)
				{	foreach ($cart->discount as $cart_discount)
					{	if ($cart_discount['applied_amount'] && $cart_discount['applied_amount'][$rowid])
						{	$itemdiscounts[] = array('discid'=>$cart_discount['discid'],'ticket'=>$cart_discount['ticket'], 'rowid'=>$rowid, 'amount'=>$cart_discount['applied_amount'][$rowid]);
							$item['discamount'] += $cart_discount['applied_amount'][$rowid];
						}
					}
				}
			
				if (is_array($product['attendees']))
				{	$attendees[$rowid] = array();
					foreach ($product['attendees'] as $attendee)
					{	$attendees[$rowid][] = array('email'=>$attendee['att_email'], 'firstname'=>$attendee['att_firstname'], 'surname'=>$attendee['att_surname'], 'userid'=>(int)$this->GetStudentFromEmail($attendee['att_email'], 'userid'));
					}
				}
				
				$items[$rowid] = $item;
			}
			
			// get any bundles
			$bundles = array();
			if ($cart->bundles)
			{	foreach ($cart->bundles as $bundleid=>$bundle_qty)
				{	$bundle = new Bundle($bundleid);
					$bundles[] = array('bid'=>$bundle->id, 'bname'=>$bundle->details['bname'], 'qty'=>$bundle_qty, 'discount'=>$bundle->details['discount'], 'totaldiscount'=>$bundle->details['discount'] * $bundle_qty);
				}
			} else
			{	// get any discount
				if (is_array($cart->discount) && $cart->discount)
				{	foreach ($cart->discount as $cart_discount)
					{	$fields['discamount'] += array_sum($cart_discount['applied_amount']);
					}
				}
			}
			
			$fields['txfee'] = $cart->TransactionFee();
			
		//	$this->varDump($cart->discount);
		//	$this->varDump($fields);
		//	$this->varDump($items);
		//	$this->varDump($itemdiscounts);
		//	exit;
		//	return;
			
			// return success / failmessage?
			if (!$fail)
			{
				$set_fields = array();
				foreach($fields as $key => $value)
				{	
					$set_fields[] = $this->SQLSafe($key). '="' . $this->SQLSafe($value) . '"';
				}
				
				$sql = 'INSERT INTO storeorders SET ' . implode(', ', $set_fields);
				
				if ($this->db->Query($sql))
				{
					if ($this->id = $this->db->InsertID())
					{
						foreach ($items as $rowid => $item)
						{
							$item['orderid'] = $this->id;
							$set_fields = array();
							foreach($item as $key => $value)
							{	$set_fields[] =  $this->SQLSafe($key) . '="' . $this->SQLSafe($value) . '"';
							}
							
							$sql = 'INSERT INTO storeorderitems SET ' . implode(', ', $set_fields);
							
							if ($this->db->Query($sql))
							{	if ($id = $this->db->InsertID())
								{	// Record attendees if required
									if (is_array($attendees[$rowid]))
									{	foreach ($attendees[$rowid] as $attendee)
										{	
											$att_fields = array('orderid=' . $this->id, 'itemid=' . $id);
											$attendeeData = array();
											$attendeeData['orderid'] = $this->id;
											$attendeeData['itemid'] = $id;
											
											foreach ($attendee as $key=>$value)
											{	$att_fields[] =  $this->SQLSafe($key) . '="' . $this->SQLSafe($value) . '"';
												$attendeeData[$this->SQLSafe($key)] = $this->SQLSafe($value);
											}
											
											$att_sql = 'INSERT INTO orderattendees SET ' . implode(', ', $att_fields);
											if (!$result = $this->db->Query($att_sql)){
												//echo '<p>', $att_sql, ': ', $this->db->Error(), '</p>';
											}
										}
									}
									// Record referrer rewards if needed
									if (is_array($rewards[$rowid]) && $rewards[$rowid])
									{	foreach ($rewards[$rowid] as $rrid=>$reward)
										{	$reward_sql = 'INSERT INTO orderitemrewards SET itemid=' . $id . ', rrid=' . $rrid . ', amount=' . round($reward['amount_used'], 2);
											if (!$result = $this->db->Query($reward_sql))
											{	//echo '<p>', $reward_sql, ': ', $this->db->Error(), '</p>';
											}
										}
									}
									// Record affiliate rewards if needed
									if (is_array($affrewards[$rowid]) && $affrewards[$rowid])
									{	foreach ($affrewards[$rowid] as $awid=>$reward)
										{	$reward_sql = 'INSERT INTO orderitemaffrewards SET itemid=' . $id . ', awid=' . $awid . ', amount=' . round($reward['amount_used'], 2);
											if (!$result = $this->db->Query($reward_sql))
											{	//echo '<p>', $reward_sql, ': ', $this->db->Error(), '</p>';
											}
										}
									}
									// record sub saving if any
									if (is_array($subsused[$rowid]) && $subsused[$rowid])
									{	$sub_sql = 'INSERT INTO storeordersubs SET orderid=' . $this->id . ', orderitem=' . $id . ', subid=' . $subsused[$rowid]['subid'] . ', amount=' . $subsused[$rowid]['amount'];
										$this->db->Query($sub_sql);
									}
									// record discounts used on this item
									foreach ($itemdiscounts as $itemdiscount)
									{	if ($itemdiscount['rowid'] == $rowid)
										{	$disc_sql = 'INSERT INTO storeorderitemdiscounts SET itemid=' . $id . ', discid=' . $itemdiscount['discid'] . ', ticket=' . $itemdiscount['ticket'] . ', discamount=' . round($itemdiscount['amount'], 2);
											$this->db->Query($disc_sql);
										}
									}
								}
							}
						}
						
						foreach ($bundles as $bundle)
						{
							$bundle['orderid'] = $this->id;
							$set_fields = array();
							foreach($bundle as $key => $value)
							{	$set_fields[] =  $this->SQLSafe($key) . '="' . $this->SQLSafe($value) . '"';	
							}
							
							$sql = 'INSERT INTO  storeorderbundles SET ' . implode(', ', $set_fields);	
							$this->db->Query($sql);
						}
						
						// record order id against cart record
						if ($cartid = (int)$_SESSION['cartid'])
						{	$cart_sql = 'UPDATE carts SET orderid=' . $this->id . ' WHERE cartid=' . $cartid;
							$this->db->Query($cart_sql);
						}
						
						// Set default status
						$this->UpdateStatus(1);
					}
				}
			}
			
			return $this->id;
		}
	} // end of fn CreateFromSession
	
	public function ItemHasGifts($itemid)
	{
		$gifts = array();
		
		if($result = $this->db->Query("SELECT * FROM storegifts WHERE itemid = ". (int)$itemid ." "))
		{
			while($row = $this->db->FetchArray($result))
			{
				$gifts[] = $row['student'];	
			}
		}
		
		return $gifts;
	} // end of fn ItemHasGifts
	
	public function RecordPayment($pptransid = '')
	{	
		if (!$this->details['pptransid'] && $pptransid)
		{	
			$sql = 'UPDATE storeorders SET pptransid="' . $this->SQLSafe($pptransid) . '", paiddate="' . $this->datefn->SQLDateTime() . '" WHERE id=' . (int)$this->id;
			if ($result = $this->db->Query($sql))
			{	return $this->db->AffectedRows();
			}
		//	return true;
		}
		
	} // end of fn RecordPayment
	
	public function RecordPaypalPayment($data = array())
	{	$fail = array();
		$success = array();
		
		if (!$pptransid = $this->SQLSafe($data["txn_id"]))
		{	$fail[] = "no txn_id";
		}
		
		if (round($data['mc_gross'], 2) != round($this->GetRealTotal(), 2))
		{	$fail[] = 'amount does not match (sent=' . $data['mc_gross'] . ')';
		}

		if ($data['mc_currency'] != $this->details['currency'])
		{	$fail[] = 'mc_currency doesn"t match (sent=' . $data['mc_currency'] . ')';
		}
		
		if (!$fail)
		{	if ($this->MarkAsPaid($pptransid))
			{	$success[] = 'Payment recorded';
			} else
			{	$fail[] = 'Payment failed';
			}
		}

		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn RecordPaypalPayment
	
	public function RecordFreePayment()
	{	$fail = array();
		$success = array();
		
		if ($this->details['pptransid'])
		{	$fail[] = 'payment already made';
		} else
		{	if ($this->MarkAsPaid('FREE~' . str_pad($this->id, 12, '0', STR_PAD_LEFT)))
			{	$success[] = 'Payment recorded';
				$this->Get($this->id);
			}
		}

		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn RecordFreePayment
	
	public function MarkAsPaid($pptransid = '')
	{
		// update order status to complete or processing
		if ($pptransid && $this->UpdateStatus(2) && $this->RecordPayment($pptransid))
		{
			foreach ($this->GetItems() as $item)
			{
				if ($p = $this->GetProduct($item['pid'], $item['ptype']))
				{	$friendName = $friend_name = $friend_email = '';
					switch ($item['ptype'])
					{	case 'course': // if item is course, add bookings
							$qty = $item['qty'];
							
							// create bookings from attendees registered on order
							if ($attendees = $this->GetItemAttendees($item['id'])){									
								$ffName = trim($this->details['firstname']);
								$flName = trim($this->details['surname']);
								
								if($ffName!='' && $flName!='') $friendName = $ffName.' '.$flName;
								elseif($ffName!='' && $flName=='') $friendName = $ffName;
								elseif($ffName=='' && $flName!='') $friendName = $flName;
								
								$friend_email = $this->details['email'];
								
								foreach ($attendees as $attendee){	
									if(!$userid = (int)$attendee['userid']){	
										$student = new Student();
										
										$friend_name = ($attendee['email']!=$this->details['email'])?$friendName:'';
										$userid = $student->CreateFromAttendee($attendee,$friend_name);
									}
									
									if ($userid){
										if($bookid = $this->CreateBookingFromItem($item, $p, new Student($userid),$friend_name,$friend_email)){	
											$this->db->Query('UPDATE orderattendees SET bookid=' . $bookid . ', userid=' . $userid . ' WHERE aid=' . $attendee['aid']);
											
											if(!$result = $this->db->Query('SELECT * FROM `orderattendees` WHERE bookid=' . (int)$bookid)){
												//echo '<p>', $att_sql, ': ', $this->db->Error(), '</p>';
											}
											$qty--;
										}
									}
								}
							}
							
							while ($qty-- > 0){	
								echo '<br />While loop: ',$qty;
								$this->CreateBookingFromItem($item, $p, new Student($this->details['sid']));
							}
							break;
						case 'store': // just update qty for product, item itself is proof of purchase for fulfillment
							$p->UpdateQty(-$item['qty']);
							break;
						case 'sub': // create subscription for member
							$this->CreateSubFromItem($item, $p, new Student($this->details['sid']));
							exit();
							break;
					}
				}
			}			
			//$this->SendCompletedEmail();
			return true;
		}
	} // end of fn MarkAsPaid
	
	public function CreateSubFromItem($item = array(), $product = false, $student = false)
	{	$sub = new StudentSubscription();
		if($sub->CreateFromOrderItem($student, $product, $item))
		{	//$sub->SendStudentEmail();
			return $sub->id;
		}
	} // end of fn CreateSubFromItem
	
	public function CreateBookingFromItem($item = array(), $product = false, $student = false,$friendName='',$friendEmail=''){
		$booking = new CourseBooking();
		if($booking->CreateFromOrderItem($student, $product, $item)){	
			$booking->SendStudentEmail($friendName,$friendEmail);
			return $booking->id;
		}
	} // end of fn CreateBookingFromItem
	
	public function SendCompletedEmail(){
		$user = new Student($this->details['sid']);
		
		if ($this->ValidEmail($user->details["username"])){
			$fields = array();
			$fields['site_url'] = $this->link->GetLink();
			$fields['firstname'] = $user->details['firstname'];
			$fields['account_link_plain'] = $this->link->GetLink('account.php');
			$fields['account_link'] = "<a href='". $fields['account_link_plain'] ."'>". $fields['account_link_plain'] ."</a>";
			
			$fields['order_items'] = $this->GetOrderItemsHTML();
			$fields['order_items_plain'] = $this->GetOrderItemsPlain();
			
			$t = new MailTemplate('order');
			$mail = new HTMLMail;
			$mail->SetSubject($t->details['subject']);
			$mail->Send($user->details['username'], $t->BuildHTMLEmailText($fields), $t->BuildHTMLPlainText($fields));
		}
	} // end of fn SendCompletedEmail
	
	public function CancelOrder()
	{	if ($this->details['pptransid'])
		{	$adminnotes = array();
			if ($this->details['pmtnotes'])
			{	$adminnotes[0] = stripslashes($this->details['pmtnotes']);
			}
			$adminnotes[1] = '[Cancelled ' . date('d-m-Y @H:i') . ', previously paid (' . $this->details['pptransid'] . ')';
			if ((int)$this->details['paiddate'])
			{	$adminnotes[1] .= ' on ' . date('d-m-Y @H:i', strtotime($this->details['paiddate']));
			}
			$adminnotes[1] .= ']';
			$sql = 'UPDATE storeorders SET paiddate="0000-00-00 00:00:00", pptransid="", pmtnotes="' . implode("\n", $adminnotes) . '" WHERE id=' . (int)$this->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->Refresh();
					return true;
				}
			}
		}
	} // end of fn CancelOrder
	
	public function GetOrderItemsHTML()
	{
		ob_start();
		
		$discounts = array();
		$total_discounts = 0;
		
		echo '<table style="border-collapse: collapse;">';
		
		foreach ($this->GetItems() as $item)
		{	echo '<tr><td style="border: 1px solid #000; padding: 5px 10px;">', $this->InputSafeString($item['title']), '</td><td style="border: 1px solid #000; padding: 5px 10px;">', $item['qty'], 'x</td><td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">', $this->formatPrice($item['pricetax']), '</td></tr>';
			foreach ($item['discounts'] as $item_discount)
			{	if (!$discounts[$item_discount['discid']])
				{	$discounts[$item_discount['discid']] = new DiscountCode($item_discount['discid']);
				}
				echo '<tr><td style="border: 1px solid #000; padding: 5px 10px;">Discount: ', $this->InputSafeString($discounts[$item_discount['discid']]->details['discdesc']), '</td><td style="border: 1px solid #000; padding: 5px 10px;"></td><td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">&minus; ', $this->formatPrice($item_discount['discamount']), '</td></tr>';
				$total_discounts += $item_discount['discamount'];
			}
		}
		
		if ($total_discounts)
		{	echo '<tr><td style="border: 1px solid #000; padding: 5px 10px;"></td><td style="border: 1px solid #000; padding: 5px 10px;">Discounts</td><td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">&minus; ', $this->formatPrice($total_discounts), '</td></tr>';
		}
		
		if ($del = $this->details['delivery_price'])
		{	echo '<tr><td style="border: 1px solid #000; padding: 5px 10px;"></td><td style="border: 1px solid #000; padding: 5px 10px;">Delivery:</td><td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">', $this->formatPrice($del), '</td></tr>';	
		}
		
		$total = $this->GetTotal();
		$today = $this->GetTotal(true);
		
		if (($total != $price) && ($today > 0))
		{	echo '<tr><td style="border: 1px solid #000; padding: 5px 10px;"></td><td style="border: 1px solid #000; padding: 5px 10px;">Paid to date:</td><td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">', $this->formatPrice($today), '</td></tr>';
		}
		
		echo '<tr><td style="border: 1px solid #000; padding: 5px 10px;"></td><td style="border: 1px solid #000; padding: 5px 10px;">Total:</td><td style="border: 1px solid #000; padding: 5px 10px; text-align: right; font-weight: bold;">', $this->formatPrice($total), '</td></tr></table>';
		
		return ob_get_clean();
	} // end of fn GetOrderItemsHTML
	
	public function GetOrderItemsPlain()
	{
		ob_start();
		
		$discounts = array();
		$total_discounts = 0;
		
		foreach($this->GetItems() as $item)
		{	echo stripslashes($item['title']), ' ', $item['qty'], 'x ', $this->formatPricePlain($item['pricetax']), "\n";
			foreach ($item['discounts'] as $item_discount)
			{	if (!$discounts[$item_discount['discid']])
				{	$discounts[$item_discount['discid']] = new DiscountCode($item_discount['discid']);
				}
				echo 'Discount: ', stripslashes($discounts[$item_discount['discid']]->details['discdesc']), ' - ', $this->formatPricePlain($item_discount['discamount']), "\n";
				$total_discounts += $item_discount['discamount'];
			}
		}
		
		if ($total_discounts)
		{	echo 'Total Discounts: - ', $this->formatPricePlain($total_discounts), "\n";
		}
		
		if($del = $this->details['delivery_price'])
		{	echo "Delivery: ", $this->formatPricePlain($del), "\n";
		}
		
		$total = $this->GetTotal();
		$today = $this->GetTotal(true);
		
		if(($total != $price) && ($today > 0))
		{	echo "Paid to date: ", $this->formatPricePlain($today), "\n";
		}
		
		echo "Total: ", $this->formatPricePlain($total), "\n";
		
		return ob_get_clean();
	} // end of fn GetOrderItemsPlain
	
	public function UpdateStatus($status = null)
	{
		if((int)$status > 0)
		{	$id = (int)$status;	
			
			if($result = $this->db->Query("SELECT * FROM storeorderstatus WHERE id=". $id))
			{	if($row = $this->db->FetchArray($result))
				{	$id = $row['id'];	
				}
			}
		} else
		{	if($result = $this->db->Query("SELECT * FROM storeorderstatus WHERE name='". $this->SQLSafe($status) ."'"))
			{	if($row = $this->db->FetchArray($result))
				{	$id = $row['id'];	
				}
			}
		}
		
		if($id)
		{	
			if ($result = $this->db->Query("INSERT INTO storeorderstatushistory SET orderid=". (int)$this->id .", statusid=". (int)$id .", statusname='". $this->SQLSafe($row['disptitle']) ."', dateadded='" . $this->datefn->SQLDateTime() . "'"))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			}
		}
	} // end of fn UpdateStatus
	
	public function GetCart()
	{	if ($this->id)
		{	$sql = 'SELECT * FROM carts WHERE orderid=' . $this->id;
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	if (($cart = new CartRecord($row)) && $cart->id)
					{	return $cart;
					}
				}
			}
		}
		return false;
	} // end of fn GetCart
	
} // end of class StoreOrder
?>
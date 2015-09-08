<?php
class StoreCart extends Base
{	public $customer;
	public $sessionname = 'cart';
	public $shipping;
	public $items = array();
	public $discount = array();
	public $errors = array();
	public $bundles = array();
	
	public function __construct()
	{
		parent::__construct();	
		
		if (!isset($_SESSION[$this->sessionname]))
		{	$_SESSION[$this->sessionname] = array();	
		}
		
		if (!isset($_SESSION['order']))
		{	$_SESSION['order'] = array();	
		}
		
		if (isset($_SESSION['order']['delivery']))
		{	$this->SetShipping(new DeliveryOption($_SESSION['order']['delivery']));
		}
		$this->GetProducts();
		$this->SaveCart();
	} // end of fn __construct
	
	public function GetProducts()
	{	$this->items = array();
	//	$this->VarDump($_SESSION[$this->sessionname]);
		foreach($_SESSION[$this->sessionname] as $rowid=>$product)
		{	
			if (($p = $this->GetProduct($product['id'], $product['type'])) && $p->id)
			{
				if ($product['type'] == 'sub')
				{	if ($_SESSION['stuserid'] && ($student = new Student($_SESSION['stuserid'])) && $student->id && !$student->CanHaveSubscription())
					{	unset($_SESSION[$this->sessionname][$rowid]);
						$this->errors[] = 'You must be over 16 and live in the UK to order a subscription';
						continue;
					}
				}
				$item = array();
				$item['id'] = $product['id'];
				$item['sub'] = $product['sub'];
				$item['discounts'] = $product['discounts'];
				$item['product'] = $p;
				$item['qty'] = (int)$product['qty'];
				$item['type'] = $product['type'];
				$item['gift'] = (array)$product['gift'];
				$item['allow_pay_on_day'] = $p->AllowPayOnDay();
				$item['is_pay_on_day'] = ($item['allow_pay_on_day'] ? $product['pay_on_day'] : false);
				$item['in_stock'] = $p->InStock();
				$item['has_shipping'] = $p->HasShipping();
				$item['live'] = (bool)$p->IsLive();
				$item['has_qty'] = $p->HasQty($product['qty']);
				$item['price'] = $p->GetPrice();
				$item['price_with_tax'] = $p->GetPriceWithTax();
				$item['total'] = $item['price']*$item['qty'];
				$item['total_with_tax'] = $item['price_with_tax'] * $item['qty'];
				if ($product['attendees'])
				{	$item['attendees'] = $product['attendees'];
				}
				//$this->VarDump($product['attendees']);
				$this->items[$rowid] = $item;
				
				// in stock?
				if (!$item['in_stock'] || !$item['live'])
				{	$this->errors[] = $this->InputSafeString($p->GetName()) . ' is not available to buy.';	
				} else // has qty?
				{	if (!$item['has_qty'])
					{	$this->errors[] = $this->InputSafeString($p->GetName()) . ' does not have enough stock. Products available: ' . (int)$p->details['qty'];	
					}
				}
			} else
			{
				$this->Remove($rowid);	
			}
		}
		
		$this->ApplyAllDiscounts();
		
		if (!$this->items)
		{	$this->errors[] = 'Your cart contains no items - <a href="' . $this->link->GetLink('store.php') . '">Continue Shopping</a>';
		}
		
		return $this->items;
	} // end of fn GetProducts
	
	Private function IndividualItemsForBundles()
	{	$items = array();
		foreach ($this->items as $rowid=>$item)
		{	$subseen = 0;
			for ($i = 0; $i < $item['qty']; $i++)
			{	// exclude subscription products
				if ($i || !$item['sub'])
				{	$items[] = array('type'=>$item['type'], 'id'=>$item['id'], 'rowid'=>$rowid, 'amount'=>$item['price_with_tax']);
				}
			}
		}
		return $items;
	} // end of fn IndividualItemsForBundles
	
	public function GetBundles()
	{	$this->bundles = array();
		if ($this->items && (count($this->items) > 1) && ($items = $this->IndividualItemsForBundles()))
		{	foreach ($items as $itemkey=>$item)
			{	// if not already taken out (i.e. bundled)
				if (isset($items[$itemkey]) && ($bundles = $this->BundlesForProductItem($item)))
				{	//$this->VarDump($bundles);
					foreach ($bundles as $bundle)
					{	$bundle = new Bundle($bundle);
						$bundle_found = true;
						$items_found = array(); // those found for current bundle
						// check each product
						foreach ($bundle->products as $bproduct)
						{	// only need to check if not current item
							if (($bproduct['ptype'] != $item['type']) || ($bproduct['pid'] != $item['id']))
							{	// check if product is in cart and not already bundled
								$product_found = false;
								foreach ($items as $check_itemkey=>$check_item)
								{	if (($bproduct['ptype'] == $check_item['type']) && ($bproduct['pid'] == $check_item['id']))
									{	$items_found[$check_itemkey] = $check_itemkey;
										$product_found = true;
										break; // only find one of each product
									}
								}
								if (!$product_found)
								{	// then no need to check other products, go to next bundle
									$bundle_found = false;
									break;
								}
							}
						} // foreach product in bundle
						
						if ($bundle_found && $items_found) // i.e. we got here without a product failing to be found, and have some items to add
						{	// then add this bundle and remove items
							$this->bundles[$bundle->id]++;
							// now apply relevant discount to items
							//echo count($items_found);
							$disc_per = floor(($bundle->details['discount'] * 100) / (count($items_found) + 1)) / 100;
							$disc_per_balance = $bundle->details['discount'] - ($disc_per * (count($items_found) + 1));
							
							$this->items[$items[$itemkey]['rowid']]['discounts']['bundles'] += $disc_per;
							$this->items[$items[$itemkey]['rowid']]['discounts']['bundles'] += $disc_per_balance;
							
							unset($items[$itemkey]);
							foreach ($items_found as $item_found)
							{	$this->items[$items[$item_found]['rowid']]['discounts']['bundles'] += $disc_per;
								unset($items[$item_found]);
							}
							break; // go straight to next item in basket
						}
						
					} // foreach bundle for each item
				}
			} // foreach original items
			
		}
	} // end of fn GetBundles
	
	private function BundlesForProductItem($item = array())
	{	$bundles = array();
		$sql = 'SELECT bundles.* FROM bundles, bundleproducts WHERE bundles.bid=bundleproducts.bid AND pid=' . (int)$item['id'] . ' AND bundleproducts.ptype="' . $this->SQLSafe($item['type']) . '" AND bundles.live=1';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$bundles[$row['bid']] = $row;
			}
		}
		return $bundles;
	} // end of fn BundlesForProduct
	
	public function GetErrors()
	{	return $this->errors;	
	} // end of fn GetErrors
	
	public function DiscountAppliesToItem($item = array(), $discount = array()){
		if($discount['prodtype']){
			if ($discount['prodtype'] == $item['type']){
				if($discount['prodid']){
					switch ($item['type']){
						case 'course': 
						case 'event':
							if($discount['prodid'] == $item['product']->course->id){
								if($discount['ticket']!='0'){
									if($discount['ticket']==$item['product']->ticket->details['tid']){
										return true;
									}else{
										return false;
									}
								}else{
									return true;
								}
							}
						case 'store': 
							return $discount['prodid'] == $item['product']->id;
						default:
							return false;
					}
				}else{
					return true;
				}
			}
		}else{
			return true;
		}
	} // end of fn DiscountAppliesToItem
	
	public function GetDiscountCode()
	{	if ($this->discount)
		{	return $this->discount['disccode'];
		}
	} // end of fn GetDiscountCode
	
	public function GetSession()
	{	return $_SESSION[$this->sessionname];	
	} // end of fn GetSession
	
	public function Insert($product, $type, $qty = 1)
	{	if (($type == 'sub') && is_array($_SESSION[$this->sessionname]) && $_SESSION[$this->sessionname])
		{	foreach ($_SESSION[$this->sessionname] as $check_product)
			{	if ($product['type'] == 'sub')
				{	$this->errors[] = 'You cannot purchase more than one subscription';
					return false;
				}
			}
		}
		
		$rowid = $this->GenerateID($product, $type); 
		
		if(isset($_SESSION[$this->sessionname][$rowid]))
		{	$_SESSION[$this->sessionname][$rowid]['qty'] += $qty;
		} else
		{	$_SESSION[$this->sessionname][$rowid] = array('id' => $product, 'type' => $type, 'qty' => $qty, 'bundles'=>array());
		}
		$this->SaveCart();
		$this->RecordItemAdd($type, $product, $qty);
		$this->UpdateAttendeeListSize($rowid);
		$this->SetDefaultAttendee($rowid);
		return true;
	} // end of fn Insert

	public function ItemDiscountSum($discounts = array())
	{	$discount_sum = 0;
		if (is_array($discounts))
		{	foreach ($discounts as $disc_amount)
			{	$discount_sum += $this->ItemDiscountSum($disc_amount);
			}
		} else
		{	$discount_sum += $discounts;
		}
		return $discount_sum;
	} // end of fn ItemDiscountSum
	
	public function ApplyAllDiscounts()
	{	//$this->VarDump($_SESSION['order']);
		foreach ($this->items as $rowid=>$cart_product)
		{	$this->items[$rowid]['rewards'] = array();
			$this->items[$rowid]['affrewards'] = array();
			$this->items[$rowid]['sub'] = 0;
			$this->items[$rowid]['discounts'] = array('rewards'=>0, 'affrewards'=>0, 'sub'=>0, 'discount'=>array(), 'bundles'=>0);
		}
		// first check for any subscriptions to apply
		if (($student = new Student($_SESSION['stuserid'])) && $student->id && ($subs = $student->GetSubscriptions()))
		{	
			foreach ($this->items as $rowid=>$cart_product)
			{	if ($cart_product['type'] == 'course' || $cart_product['type'] == 'event')
				{	
					if (!$cart_product['product']->ticket->details['no_reg'] && $cart_product['attendees'] && !$student->AlreadyBooked($cart_product['product']->course->id))
					{	foreach ($cart_product['attendees'] as $attendee)
						{	if ($attendee['att_email'] == $student->details['username'])
							{	// check subs to see if one applies
								foreach ($subs as $subid=>$sub)
								{	if ($cart_product['product']->course->SubscriptionApplies($sub))
									{	$this->items[$rowid]['sub'] = $subid;
										$this->items[$rowid]['discounts']['sub'] = $cart_product['price_with_tax'];
									}
								}
							}
						}
					}
				}
			}
		}
		
		// apply any bundles
		$this->GetBundles();
		
		// apply any discount code
		$this->discount = array();		
		
		if (is_array($_SESSION['order']['discount']) && $_SESSION['order']['discount']){	
			if($this->bundles){	
				$this->errors[] = 'Discount codes cannot be used in conjunction with other offers';
				$this->discount = array();
				unset($_SESSION['order']['discount']);
			}else{	
				$this->discount = array();				
			
				foreach ($_SESSION['order']['discount'] as $discount_code){
					$discount = new DiscountCode();					
					$discount->GetByCode($discount_code);
					
					if($discount->id){	
						$discount_applied = $discount->details;
						
						$discountApplicable = true;
						
						if($discount_applied['uselimit'] > '0' && $discount_applied['usecount']>=$discount_applied['uselimit']){
							$discountApplicable = false;
						}
						
						if($discountApplicable){
							$discount_applied['applied_amount'] = array();
							$amount_to_apply = $discount_applied['discamount'];	
							
							foreach($this->items as $itemkey=>$item){	
								if($this->DiscountAppliesToItem($item, $discount_applied)){
									if($discount_applied['discpc'] > 0){
										$item_discount = (($item['total_with_tax'] - $this->ItemDiscountSum($item['discounts'])) * $discount_applied['discpc']) / 100;
										$discount_applied['applied_amount'][$itemkey] = $item_discount;
										$this->items[$itemkey]['discounts']['discount'][$discount->id] = $item_discount;
									}else{
										if($amount_to_apply){
											$item_balance = $item['total_with_tax'] - $this->ItemDiscountSum($this->items[$itemkey]['discounts']);
											if($amount_to_apply > $item_balance){
												$discount_applied['applied_amount'][$itemkey] = $item_balance;
												$this->items[$itemkey]['discounts']['discount'][$discount->id] = $item_balance;
												$amount_to_apply -= $item_balance;
											}else{
												$discount_applied['applied_amount'][$itemkey] = $amount_to_apply;
												$this->items[$itemkey]['discounts']['discount'][$discount->id] = $amount_to_apply;
												$amount_to_apply = 0;
											}
										}
									}
								}
							}
							
							if($discount_applied['applied_amount']){	
								$this->discount[$discount->id] = $discount_applied;
							}else{	
								$this->errors[] = 'Discount code ' . $this->InputSafeString($discount_code) . ' cannot be applied to any items in your cart';
								unset($_SESSION['order']['discount'][$discount_code]);
							}
						}else{
							$this->errors[] = 'Discount code ' . $this->InputSafeString($discount_code) . ' already applied';
							unset($_SESSION['order']['discount'][$discount_code]);
						}
					}else{	
						$this->errors[] = 'Discount code ' . $this->InputSafeString($discount_code) . ' not found';
						unset($_SESSION['order']['discount'][$discount_code]);
					}
				}
			}
		}
		
		if ($student && $student->id && ($rewards = $student->GetReferrerRewardsAvailable())){	
			$startprice = $this->GetTotalWithAllDiscounts();
			foreach ($this->items as $rowid=>$cart_product)
			{	if (!$cart_product['sub'] && $rewards && $this->RewardAppliesToProduct($cart_product) && $cart_product['attendees'])
				{	$amount_rewarded = 0;
					
					// check attendees and only apply to this user
					foreach ($cart_product['attendees'] as $attendee)
					{	if ($attendee['att_email'] == $student->details['username'])
						{	
							// calculate total price of tickets
							$total_price = $cart_product['total_with_tax'] - array_sum($cart_product['discounts']);
							
							foreach ($rewards as $rrid=>$reward)
							{	if ($reward['amount_available'] && (($amount_left = ($total_price - $amount_rewarded)) > 0)) // i.e. still can apply reward
								{	$amount_to_use = (($amount_left > $reward['amount_available']) ? $reward['amount_available'] : $amount_left);
									if ($amount_to_use > $startprice)
									{	$amount_to_use = $startprice;
									}
									if ($amount_to_use)
									{	$amount_rewarded += $amount_to_use;
										$this->items[$rowid]['rewards'][$rrid] = $reward;
										$this->items[$rowid]['rewards'][$rrid]['amount_used'] = $amount_to_use;
										$this->items[$rowid]['discounts']['rewards'] += $amount_to_use;
										$rewards[$rrid]['amount_available'] -= $amount_to_use;
										$startprice -= $amount_to_use;
										if ($rewards[$rrid]['amount_available'] <= 0)
										{	unset($rewards[$rrid]);
										}
									}
								}
							}
							
						}
					}
				}
			}
		}
		
		if ($student && $student->id && ($affrewards = $student->GetAffiliateRewardsAvailable()))
		{	$startprice = $this->GetTotalWithAllDiscounts();
			foreach ($this->items as $rowid=>$cart_product)
			{	if (!$cart_product['sub'] && $affrewards && $this->RewardAppliesToProduct($cart_product) && $cart_product['attendees'])
				{	$amount_rewarded = 0;
					
					// check attendees and only apply to this user
					foreach ($cart_product['attendees'] as $attendee)
					{	if ($attendee['att_email'] == $student->details['username'])
						{	
							// calculate total price of tickets
							$total_price = $cart_product['total_with_tax'] - array_sum($cart_product['discounts']);
							
							foreach ($affrewards as $rrid=>$reward)
							{	if ($reward['amount_available'] && (($amount_left = ($total_price - $amount_rewarded)) > 0)) // i.e. still can apply reward
								{	$amount_to_use = (($amount_left > $reward['amount_available']) ? $reward['amount_available'] : $amount_left);
									if ($amount_to_use > $startprice)
									{	$amount_to_use = $startprice;
									}
									if ($amount_to_use)
									{	$amount_rewarded += $amount_to_use;
										$this->items[$rowid]['affrewards'][$rrid] = $reward;
										$this->items[$rowid]['affrewards'][$rrid]['amount_used'] = $amount_to_use;
										$this->items[$rowid]['discounts']['affrewards'] += $amount_to_use;
										$affrewards[$rrid]['amount_available'] -= $amount_to_use;
										$startprice -= $amount_to_use;
										if ($affrewards[$rrid]['amount_available'] <= 0)
										{	unset($affrewards[$rrid]);
										}
									}
								}
							}
							
						}
					}
				}
			}
		}
		
		//$this->VarDump($_SESSION[$this->sessionname]);
	} // end of fn ApplyAllDiscounts
	
	public function RewardsTotalsUsedForProduct($rowid = '')
	{	$reward_used = 0;
		if (is_array($this->items[$rowid]['rewards']) && $this->items[$rowid]['rewards'])
		{	foreach ($this->items[$rowid]['rewards'] as $reward)
			{	$reward_used += $reward['amount_used'];
			}
		}
		if (is_array($this->items[$rowid]['affrewards']) && $this->items[$rowid]['affrewards'])
		{	foreach ($this->items[$rowid]['affrewards'] as $reward)
			{	$reward_used += $reward['amount_used'];
			}
		}
		return $reward_used;
	} // end of fn RewardsTotalsUsedForProduct
	
	public function GetRewardsUsedForProduct($rowid = '')
	{	return $this->items[$rowid]['rewards'];
	} // end of fn GetRewardsUsedForProduct
	
	public function GetAffRewardsUsedForProduct($rowid = '')
	{	return $this->items[$rowid]['affrewards'];
	} // end of fn GetRewardsUsedForProduct
	
	public function RewardsTotalsUsed()
	{	$reward_used = 0;
		if (is_array($this->items) && $this->items)
		{	foreach ($this->items as $rowid=>$product)
			{	$reward_used += $this->RewardsTotalsUsedForProduct($rowid);
			}
		}
		return $reward_used;
	} // end of fn RewardsTotalsUsed
	
	public function SubsTotalsUsed()
	{	$subs_used = 0;
		if (is_array($this->items) && $this->items)
		{	foreach ($this->items as $rowid=>$product)
			{	if ($product['sub'])
				{	$subs_used += $product['price_with_tax'];
				}
			}
		}
		return $subs_used;
	} // end of fn SubsTotalsUsed
	
	public function RewardAppliesToProduct($item = array())
	{	
		if($item['type'] == 'course' || $item['type'] == 'event'){
			return true;	
		}
	} // end of fn RewardAppliesToProduct
	
	public function SetDefaultAttendee($rowid = '')
	{	if ($_SESSION[$this->sessionname][$rowid]['attendees'] && $_SESSION[$this->sessionname][$rowid]['attendees'][0])
		{	if (($student = new Student($_SESSION['stuserid'])) && $student->id)
			{	// check any other tickets for same course
				$ticket = new CourseTicket($_SESSION[$this->sessionname][$rowid]['id']);
				if (!$this->AlreadyBooked($ticket->details['cid'], $student->details['username']))
				{	foreach ($_SESSION[$this->sessionname] as $checkrowid=>$product)
					{	if (($checkrowid != $rowid) && ($product['type'] == 'course' || $product['type'] == 'event') && $product['attendees'])
						{	$check_ticket = new CourseTicket($product['id']);
							if ($check_ticket->details['cid'] == $ticket->details['cid'])
							{	// check all attendees
								foreach ($product['attendees'] as $attendee)
								{	if ($attendee['att_email'] == $student->details['username'])
									{	return false;
									}
								}
							}
						}
					}
					// if here then not found so set as first register
					$_SESSION[$this->sessionname][$rowid]['attendees'][0] = array('att_email'=>$student->details['username'], 'att_firstname'=>$student->details['firstname'], 'att_surname'=>$student->details['surname']);
				}
			}
		}
	} // end of fn SetDefaultAttendee
	
	public function Update($product, $qty = 1)
	{	if (isset($_SESSION[$this->sessionname][$product]))
		{	if($qty == 0)
			{	$this->Remove($product);
			} else
			{	$this->RecordItemAdd($_SESSION[$this->sessionname][$product]['type'], $_SESSION[$this->sessionname][$product]['id'], $qty - $_SESSION[$this->sessionname][$product]['qty']);
				$_SESSION[$this->sessionname][$product]['qty'] = (int)$qty;
			}
			$this->UpdateAttendeeListSize($product);
		}
	} // end of fn Update
	
	public function UpdateAttendeeListSize($rowid = '')
	{	
		if ($_SESSION[$this->sessionname][$rowid]['attendees'])
		{	$existing = $_SESSION[$this->sessionname][$rowid]['attendees'];
		}
		unset($_SESSION[$this->sessionname][$rowid]['attendees']);
		if ($_SESSION[$this->sessionname][$rowid]['type'] == 'course' || $_SESSION[$this->sessionname][$rowid]['type'] == 'event')
		{	$ticket = new CourseTicket($_SESSION[$this->sessionname][$rowid]['id']);
			if (!$ticket->details['no_reg'])
			{	$_SESSION[$this->sessionname][$rowid]['attendees'] = array();
				if ($existing)
				{	foreach ($existing as $previous)
					{	if (count($_SESSION[$this->sessionname][$rowid]['attendees']) >= $_SESSION[$this->sessionname][$rowid]['qty'])
						{	break;
						} else
						{	$_SESSION[$this->sessionname][$rowid]['attendees'][count($_SESSION[$this->sessionname][$rowid]['attendees'])] = $previous;
						}
					}
				}
				while (count($_SESSION[$this->sessionname][$rowid]['attendees']) < $_SESSION[$this->sessionname][$rowid]['qty'])
				{	$_SESSION[$this->sessionname][$rowid]['attendees'][count($_SESSION[$this->sessionname][$rowid]['attendees'])] = array('att_email'=>'', 'att_firstname'=>'', 'att_surname'=>'');
				}
			}
		}
	} // end of fn UpdateAttendeeListSize
	
	public function AlreadyBooked($courseid = 0, $email = '')
	{	$sql = 'SELECT id FROM coursebookings, students WHERE coursebookings.student=students.userid AND coursebookings.course=' . (int)$courseid . ' AND students.username="' . $this->SQLSafe($email) . '"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['id'];
			}
		}
	} // end of fn AlreadyBooked
	
	public function UpdateAttendees($data = array())
	{	$fail = array();
		$success = array();		
		$courses_done = array();
		$emails_failed = array();		
		$tickets = array();	
			
		if(is_array($data['att_email'])){
			foreach($data['att_email'] as $key=>$att_email){	
				list($rowid, $attid) = explode('|', $key);
				if(isset($_SESSION[$this->sessionname][$rowid]['attendees'][$attid = (int)$attid])){	
					if($this->ValidEmail($att_email)){	
						if(!$tickets[$_SESSION[$this->sessionname][$rowid]['id']]){	
							$tickets[$_SESSION[$this->sessionname][$rowid]['id']] = new CourseTicket($_SESSION[$this->sessionname][$rowid]['id']);
						}
							
						if($courses_done[$tickets[$_SESSION[$this->sessionname][$rowid]['id']]->details['cid']] && $courses_done[$tickets[$_SESSION[$this->sessionname][$rowid]['id']]->details['cid']][$att_email]){	
							if(!$emails_failed[$att_email]){	
								$fail[] = '"' . $this->InputSafeString($att_email) . '" cannot be booked on this course more than once';
								$emails_failed[$att_email] = $att_email;
							}
						}else{	
							if($this->AlreadyBooked($tickets[$_SESSION[$this->sessionname][$rowid]['id']]->details['cid'], $att_email)){	
								$fail[] = '"' . $this->InputSafeString($att_email) . '" has already booked this course';
								$emails_failed[$att_email] = $att_email;
							}else{	
								$_SESSION[$this->sessionname][$rowid]['attendees'][$attid]['att_email'] = $att_email;
								
								if(!$courses_done[$tickets[$_SESSION[$this->sessionname][$rowid]['id']]->details['cid']]){	
									$courses_done[$tickets[$_SESSION[$this->sessionname][$rowid]['id']]->details['cid']] = array();
								}
								
								$courses_done[$tickets[$_SESSION[$this->sessionname][$rowid]['id']]->details['cid']][$att_email] = $att_email;
							}
						}						
					}else{	
						if($att_email && !$emails_failed[$att_email]){	
							$fail[] = '"' . $this->InputSafeString($att_email) . '" is not a valid email address';
							$emails_failed[$att_email] = $att_email;
						}
					}
					
					$_SESSION[$this->sessionname][$rowid]['attendees'][$attid]['att_firstname'] = $data['att_firstname'][$key];
					$_SESSION[$this->sessionname][$rowid]['attendees'][$attid]['att_surname'] = $data['att_surname'][$key];					
				} //else echo'not set';
				//	$this->VarDump($_SESSION[$this->sessionname][$rowid]);
			}
		}
		//$this->VarDump($_SESSION[$this->sessionname]);
		if(!$fail){			
			// check for all fields completed
			foreach($this->GetAttendeeProducts() as $rowid=>$product){	
				foreach($product['attendees'] as $attendee){
					if(!$attendee['att_email'] || !$attendee['att_firstname'] || !$attendee['att_surname']){	
						$fail[] = 'Details must be given for all course participants';
						break;
					}
				}
			}
		}
	
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
	} // end of fn UpdateAttendees
	
	public function GetAttendeeProducts()
	{	$attendee_products = array();
		if (is_array($_SESSION[$this->sessionname]))
		{	foreach ($_SESSION[$this->sessionname] as $rowid=>$product)
			{	if (($product['type'] = 'course' || $product['type'] = 'event') && $product['attendees'])
				{	$attendee_products[$rowid] = $product;
				}
			}
		}
		return $attendee_products;
	} // end of fn GetAttendeeProducts
	
	public function UpdateValue($product, $name, $value)
	{	if(isset($_SESSION[$this->sessionname][$product]))
		{	$_SESSION[$this->sessionname][$product][$name] = $value;	
		}
	} // end of fn UpdateValue
	
	public function UpdateAllItemsValue($name, $value)
	{	foreach ($_SESSION[$this->sessionname] as $id => $product)
		{	$this->UpdateValue($id, $name, $value);	
		}
	} // end of fn UpdateAllItemsValue
	
	public function Remove($product)
	{	$this->RecordItemAdd($_SESSION[$this->sessionname][$product]['type'], $_SESSION[$this->sessionname][$product]['id'], $_SESSION[$this->sessionname][$product]['qty'] * -1);
		unset($_SESSION[$this->sessionname][$product]);
		unset($this->items[$product]);
		if (!$this->HasShipping())
		{	unset($_SESSION['order']['delivery']);
		}
	} // end of fn Remove
	
	public function Destroy()
	{	$this->items = array();
		$this->order = array();
		$_SESSION[$this->sessionname] = array();
		
		unset($_SESSION['cartid']);
		unset($_SESSION['order']['delivery']);
	} // end of fn Destroy
	
	public function GetSubTotal($today = false)
	{	$total = 0;
		
		foreach ($this->items as $product)
		{	if (($today == false) || ($today && ($product['is_pay_on_day'] == false)))
			{	$total += $product['total'];	
			}
		}
		
		return $total;
	} // end of fn GetSubTotal
	
	public function GetTax($today = false)
	{	$tax = 0;
		
		foreach ($this->items as $rowid=>$product)
		{
			if (($today == false) || ($today && ($product['is_pay_on_day'] == false)))
			{	
				if ($product['total_with_tax'] > $product['total'])
				{	// work out real VAT taking into account discounts
					$tax += (($product['total_with_tax'] - $product['total']) / $product['total_with_tax']) * ($product['total_with_tax'] - array_sum($product['discounts']));
				}
			}
		}
		
		return $tax;
	} // end of fn GetTax
	
	public function GetTaxRaw($today = false)
	{	$tax = 0;
		
		foreach ($this->items as $rowid=>$product)
		{
			if (($today == false) || ($today && ($product['is_pay_on_day'] == false)))
			{	if ($product['total_with_tax'] > $product['total'])
				{	$tax += $product['total_with_tax'] - $product['total'];
				}
			}
		}
		
		return $tax;
	} // end of fn GetTaxRaw
	
	public function TaxForProduct()
	{	if ($product['total_with_tax'] > $product['total'])
		{	$taxrate = ($product['total_with_tax'] - $product['total']) / $product['total_with_tax'];
			echo $product_tax = $taxrate * ($product['total_with_tax'] - $this->RewardsTotalsUsedForProduct($rowid));
			$tax += $product['total_with_tax'] - $product['total'];
		}
	} // end of fn TaxForProduct
	
	public function GetShippingTotal()
	{
		if($this->HasShipping())
		{	if($this->shipping)
			{	return $this->shipping->GetPrice();	
			}
		}
		
		return false;
	} // end of fn GetShippingTotal
	
	public function GetDiscounts()
	{
	} // end of fn GetDiscounts
	
	public function GetTotal($today = false)
	{
		$total = 0;
		$total += $this->GetShippingTotal();
		$total += $this->GetTaxRaw($today);
		$total += $this->GetSubTotal($today);
		$total -= $this->SubsTotalsUsed();
		if (is_array($this->discount) && $this->discount)
		{	foreach ($this->discount as $discount)
			if (is_array($discount['applied_amount']))
			{	$total -= array_sum($discount['applied_amount']);
			}
		}
		
		if ($total < 0)
		{	$total = 0;
		}
		
		return $total;
	} // end of fn GetTotal
	
	public function GetTotalWithAllDiscounts($today = false)
	{	$total = $this->GetTotal($today);
		if ($rewards = $this->RewardsTotalsUsed())
		{	$total -= $rewards;
		}
		
		if ($this->bundles)
		{	foreach ($this->bundles as $bundleid=>$bundle_qty)
			{	$bundle = new Bundle($bundleid);
				$total -= ($bundle_qty * $bundle->details['discount']);
			}
		}		
		
		return $total;
	} // end of fn GetTotalWithAllDiscounts
	
	public function HasShipping()
	{
		foreach ($this->items as $product)
		{	if ($product['has_shipping'])
			{	return true;
			}
		}
	} // end of fn HasShipping
	
	public function HasStock()
	{
	} // end of fn HasStock
	
	public function SetShipping(DeliveryOption $o)
	{	$this->shipping = $o;
		$_SESSION['order']['delivery'] = $o->id;
	} // end of fn SetShipping
	
	public function GetShipping()
	{	return $this->shipping;
	} // end of fn GetShipping
	
	public function Count()
	{	$count = 0;
		
		if ($this->items)
		{	foreach ($this->items as $product)
			{	$count += $product['qty'];	
			}
		}
		
		return $count;
	} // end of fn Count
	
	private function GenerateID($product, $type)
	{	return md5($product . ':' . $type);
	} // end of fn GenerateID

	public function UpdateDiscount($disccodes = array())
	{	$_SESSION['order']['discount'] = array();
		if (is_array($disccodes) && $disccodes)
		{	foreach ($disccodes as $disccode)
			{	if ($disccode && !$_SESSION['order']['discount'][$disccode])
				{	$_SESSION['order']['discount'][$disccode] = $disccode;
				}
			}		
		}
	} // end of fn UpdateDiscount

	public function DeliveryOptions($countryID='',$productWeight=0.00)
	{	$country = new Country($countryID);
		$region = new DeliveryRegion($country->details['region']);
		$options = array();
		foreach ($region->GetOptions(true,$productWeight) as $option)
		{	$options[] = new DeliveryOption($option);
		}
		return $options;
	} // end of fn DeliveryOptions
	
	public function TransactionFee()
	{	if ($total = $this->GetTotalWithAllDiscounts())
		{	$txfee = $this->GetParameter('txfee_flat');
		
			if ($txfee_pc = $this->GetParameter('txfee_pc'))
			{	$txfee += round(($total * $txfee_pc) / 100, 2);
			}
			return round($txfee, 2);
		}
	} // end of fn TransactionFee
	
	public function TransactionFeeDescription()
	{	$bits = array();
		if ($txfee = $this->GetParameter('txfee_flat'))
		{	$bits[] = $this->formatPrice($txfee);
		}
		if ($txfee_pc = $this->GetParameter('txfee_pc'))
		{	$bits[] = round($txfee_pc, 2) . '%';
		}
		if ($bits)
		{	return 'calculated as ' . implode(' + ', $bits) . '<br />(rounded to the nearest 2 decimal places)';
		}
	} // end of fn TransactionFeeDescription
	
	public function SaveCart()
	{	//$this->VarDump($_SESSION);
		if (is_array($_SESSION[$this->sessionname]) && $_SESSION[$this->sessionname])
		{	if ($_SESSION['stuserid'] || !$_SESSION['cartid'])
			{	$fields = array();
				$fields['userid'] = 'userid=' . (int)$_SESSION['stuserid'];
				if ($cartid = (int)$_SESSION['cartid'])
				{	$sql = 'UPDATE carts SET ' . implode(', ', $fields) . ' WHERE cartid=' . $cartid;
					$this->db->Query($sql);
				} else
				{	$fields['created'] = 'created="' . $this->datefn->SQLDateTime() . '"';
					$fields['currency'] = 'currency="GBP"';
					$sql = 'INSERT INTO carts SET ' . implode(', ', $fields);
					if ($result = $this->db->Query($sql))
					{	if ($this->db->AffectedRows() && ($cartid = $this->db->InsertID()))
						{	$_SESSION['cartid'] = $cartid;
						}// else echo 'not affected';
					}// else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
				}
			}
		} else
		{	if ($_SESSION['cartid'])
			{	unset($_SESSION['cartid']);
			}
		}
		
		return;
	
	} // end of fn CreateFromSession
	
	public function RecordItemAdd($ptype = 'store', $pid = 0, $qty = 1)
	{	if (($cartid = (int)$_SESSION['cartid']) && ($pid = (int)$pid) && (($qty = (int)$qty) !== 0))
		{	$fields = array('ptype="' . $this->SQLSafe($ptype) . '"', 'pid=' . $pid, 'cartid=' . $cartid, 'qty=' . $qty, 'addtime="' . $this->datefn->SQLDateTime() . '"');
			$sql = 'INSERT INTO cartitems SET ' . implode(', ', $fields);
			$result = $this->db->Query($sql);
		}
	} // end of fn RecordItemAdd
	
} // end of class StoreCart
?>
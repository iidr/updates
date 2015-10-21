<?php
class AdminStoreOrder extends StoreOrder
{
	public function __construct($id = null)
	{	parent::__construct($id);
	} // end of fn __construct

	public function DisplayDetails()
	{	ob_start();
		$student = new Student($this->details['sid']);
		$phone = array();
		if ($this->details['phone'])
		{	$phone[] = $this->InputSafeString($this->details['phone']);
		}
		if ($this->details['phone2'])
		{	$phone[] = $this->InputSafeString($this->details['phone2']);
		}
		echo '<div class="orderDetails"><table class="adminDetailsHeader"><tr><td class="label">Order placed</td><td>', date('d-M-y @H:i', strtotime($this->details['orderdate'])), '</td></tr><tr><td class="label">Ordered by</td><td><a href="member.php?id=', $student->id, '">', $this->InputSafeString($student->GetName()), '</a></td></tr><tr><td class="label">Phone</td><td>', implode('<br />', $phone), '</td></tr>';
		if ($this->ValidEMail($this->details['email']))
		{	echo '<tr><td class="label">Email</td><td><a href="mailto:', $this->details['email'], '">', $this->details['email'], '</a></td></tr>';
		}
		echo '</table></div>';
		if (SITE_TEST)
		{	//$this->SendCompletedEmail();
		}
		return ob_get_clean();
	} // end of fn DisplayDetails

	public function DisplayDelivery()
	{	ob_start();
		echo '<div id="orderDelContainer">', $this->DisplayDeliveryContents(), '</div>';
		return ob_get_clean();
	} // end of fn DisplayDelivery

	public function DisplayDeliveryContents()
	{	ob_start();
		$address = array();
		if ($this->details['delivery_address1'])
		{	$address[] = $this->InputSafeString($this->details['delivery_address1']);
		}
		if ($this->details['delivery_address2'])
		{	$address[] = $this->InputSafeString($this->details['delivery_address2']);
		}
		if ($this->details['delivery_address3'])
		{	$address[] = $this->InputSafeString($this->details['delivery_address3']);
		}
		echo '<div class="orderDetails"><form onsubmit="return false;"><h3>Delivery</h3><table class="adminDetailsHeader"><tr><td class="label">Name</td><td>', $this->InputSafeString($this->details['delivery_firstname'] . ' ' . $this->details['delivery_surname']), '</td></tr><tr><td class="label">Address</td><td>', implode('<br />', $address), '</td></tr><tr><td class="label">City</td><td>', $this->InputSafeString($this->details['delivery_city']), '</td></tr><tr><td class="label">Postcode</td><td>', $this->InputSafeString($this->details['delivery_postcode']), '</td></tr><tr><td class="label">Phone</td><td>', $this->InputSafeString($this->details['delivery_phone']), '</td></tr><tr><td class="label">Delivery price</td><td>', number_format($this->details['delivery_price'], 2);
		if ($this->details['delivery_id'] && ($deloption = new DeliveryOption($this->details['delivery_id'])) && $deloption->id)
		{	echo '<br />', $this->InputSafeString($deloption->details['title']), '<br />', $this->InputSafeString($deloption->details['description']);
		}
		echo '</td></tr><tr><td class="label">Delivered?</td><td><input type="checkbox" name="delivered" id="delDelivered" value="1"', $this->details['delivered'] ? ' checked="checked"' : '', ' onchange="SaveDeliveryChanged();" /></td></tr><tr><td class="label">Delivery notes</td><td><textarea name="delnotes" id="delnotes" onkeyup="SaveDeliveryChanged();">', $this->InputSafeString($this->details['delnotes']), '</textarea></td></tr><tr><td class="label">&nbsp;</td><td><input type="submit" id="delSaveSubmit" class="submit" value="Save Delivery" style="display: none;" onclick="SaveDelivery(', $this->id, ');" /></td></tr></table></form></div>';
		return ob_get_clean();
	} // end of fn DisplayDeliveryContents
	
	public function SaveDelivery($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		$delivered = $data['delivered'] ? '1' : '0';
		$fields[] = 'delivered=' . $delivered;
		if ($delivered != $this->details['delivered'])
		{	$admin_actions[] = array('action'=>'Delivered', 'actionfrom'=>$this->details['delivered'], 'actionto'=>$delivered, 'actiontype'=>'boolean');
		}
		
		$delnotes = $this->SQLSafe($data['delnotes']);
		$fields[] = 'delnotes="' . $delnotes . '"';
		if ($this->id && ($data['delnotes'] != $this->details['delnotes']))
		{	$admin_actions[] = array('action'=>'Delivery notes', 'actionfrom'=>$this->details['delnotes'], 'actionto'=>$data['delnotes']);
		}
		
		if ($set = implode(', ', $fields))
		{	$sql = 'UPDATE storeorders SET ' . $set . ' WHERE id=' . (int)$this->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$success[] = 'Changes saved';
					$this->Get($this->id);
				}
			}
		}

		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn SaveDelivery
	
	public function SavePaymentNotes($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		$pmtnotes = $this->SQLSafe($data['pmtnotes']);
		$fields[] = 'pmtnotes="' . $pmtnotes . '"';
		if ($this->id && ($data['pmtnotes'] != $this->details['pmtnotes']))
		{	$admin_actions[] = array('action'=>'Payment notes', 'actionfrom'=>$this->details['pmtnotes'], 'actionto'=>$data['pmtnotes']);
		}
		
		if ($set = implode(', ', $fields))
		{	$sql = 'UPDATE storeorders SET ' . $set . ' WHERE id=' . (int)$this->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$success[] = 'Notes saved';
					$this->Get($this->id);
				}
			}
		}

		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn SavePaymentNotes
	
	public function RecordManualPayment()
	{	$fail = array();
		$success = array();
		
		if ($this->details['pptransid'])
		{	$fail[] = 'payment already made';
		} else
		{	if ($this->MarkAsPaid('MAN~' . str_pad($this->id, 13, '0', STR_PAD_LEFT)))
			{	$success[] = 'Payment recorded';
				$this->Get($this->id);
			}
		}

		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn RecordManualPayment
	
	public function DisplayPayment()
	{	ob_start();
		echo '<div id="orderPmtContainer">', $this->DisplayPaymentContents(), '</div>';
		return ob_get_clean();
	} // end of fn DisplayPayment
	
	public function DisplayPaymentContents()
	{	ob_start();
		
		$address = array();
		if ($this->details['payment_address1'])
		{	$address[] = $this->InputSafeString($this->details['payment_address1']);
		}
		if ($this->details['payment_address2'])
		{	$address[] = $this->InputSafeString($this->details['payment_address2']);
		}
		if ($this->details['payment_address3'])
		{	$address[] = $this->InputSafeString($this->details['payment_address3']);
		}
		echo '<div class="orderDetails"><form onsubmit="return false;"><h3>Payment</h3><table class="adminDetailsHeader"><tr><td class="label">Name</td><td>', $this->InputSafeString($this->details['payment_firstname'] . ' ' . $this->details['payment_surname']), '</td></tr><tr><td class="label">Address</td><td>', implode('<br />', $address), '</td></tr><tr><td class="label">City</td><td>', $this->InputSafeString($this->details['payment_city']), '</td></tr><tr><td class="label">Postcode</td><td>', $this->InputSafeString($this->details['payment_postcode']), '</td></tr><tr><td class="label">Phone</td><td>', $this->InputSafeString($this->details['payment_phone']), '</td></tr><td class="label">Payment made</td><td>';
		if ($this->details['pptransid'])
		{	echo $this->details['pptransid'], ' - on ', date('d-M-y @H:i', strtotime($this->details['paiddate']));
		} else
		{	echo 'not paid yet - record manual payment now<input type="checkbox" id="manPmtCheck" onchange="SavePaymentChanged();" />';
		}
		echo '</td></tr>';
		if ($this->details['pptransid'])
		{	echo '<tr><td class="label">&nbsp;</td><td>Cancel this order<input type="checkbox" id="cancelPmtCheck" onchange="SavePaymentChanged();" /><td></tr>';
		}
		echo '<tr><td class="label">Payment notes</td><td><textarea id="pmtnotes" onkeyup="SavePaymentChanged();">', $this->InputSafeString($this->details['pmtnotes']), '</textarea></td></tr><tr><td class="label">&nbsp;</td><td><input type="submit" id="paySaveSubmit" class="submit" value="Save Payment Details" style="display: none;" onclick="SavePayment(', $this->id, ');" /></td></tr></table></form></div>';
		return ob_get_clean();
	} // end of fn DisplayPaymentContents
	
	public function DisplayItems()
	{	ob_start();
		$discounts = array();
		$total_discounts = 0;
		echo '<table class="itemsTable"><tr><th rowspan="2">Type</th><th rowspan="2">Item name</th><th rowspan="2">Qty</th><th colspan="2">Unit Price</th><th colspan="2">Total Price</th></tr><tr><th class="num">Before tax</th><th class="num">After tax</th><th class="num">Before tax</th><th class="num">After tax</th></tr>';
		foreach ($this->GetItems() as $item)
		{	
			echo '<tr><td>', $this->InputSafeString($item['ptype']), '</td><td>', $this->InputSafeString(preg_replace("/\(\([^)]+\)\)/","",$item['title']));
			
			switch ($item['ptype'])
			{	case 'store':
					$product = new StoreProduct($item['pid']);
					echo '<span class="prodItemCode">Code: ', $product->ProductID(), '</span>';
					break;
				case 'course':
					$ticket = new CourseTicket($item['pid']);
					$course = new Course($ticket->details['cid']);
					echo '<span class="prodItemCode">Code: ', $course->ProductID(), '</span>';
					break;
			}
			
			echo '</td><td>', (int)$item['qty'], '</td><td class="num">', number_format($item['price'], 2), '</td><td class="num">', number_format($item['pricetax'], 2), '</td><td class="num">', number_format($item['totalprice'], 2), '</td><td class="num">', number_format($item['totalpricetax'], 2), '</td></tr>';
			$totalprice += $item['totalprice'];
			$totalpricetax += $item['totalpricetax'];
			foreach ($item['discounts'] as $item_discount)
			{	if (!$discounts[$item_discount['discid']])
				{	$discounts[$item_discount['discid']] = new DiscountCode($item_discount['discid']);
				}
				echo '<tr class="itemsTableSubRow"><td>Discount</td><td colspan="5">', $this->InputSafeString($discounts[$item_discount['discid']]->details['discdesc']), '</td><td class="num">&minus; ', number_format($item_discount['discamount'], 2), '</td></tr>';
				$total_discounts += $item_discount['discamount'];
			}
		}
		if ($attendees = $this->GetAttendees())
		{	foreach ($attendees as $attendee)
			{	if ($item = $this->items[$attendee['itemid']])
				{	echo '<tr class="itemsTableSubRow"><td>registration</td><td>for ', $this->InputSafeString($item['title']), '</td><td colspan="2">';
					if ($attendee['userid'])
					{	echo '<a href="member.php?id=', $attendee['userid'], '">', $this->InputSafeString($attendee['email']), '</a>';
					} else
					{	echo $this->InputSafeString($attendee['email']);
					}
					echo '<br />', $this->InputSafeString($attendee['firstname'] . ' ' . $attendee['surname']), '</td><td colspan="2">';
					if ($attendee['bookid'])
					{	echo '<a href="booking.php?id=', $attendee['bookid'], '">booking created</a>';
					}
					echo '</td><td></td></tr>';
				}
			}
		}
		foreach ($this->GetAllReferrerRewards() as $reward)
		{	echo '<tr><td>reward</td><td>for refer-a-friend</td><td></td><td class="num"></td><td class="num">&minus; ', number_format($reward['amount'], 2), '</td><td class="num"></td><td class="num">&minus; ', number_format($reward['amount'], 2), '</td></tr>';
			$totalprice -= $reward['amount'];
			$totalpricetax -= $reward['amount'];
		}
		foreach ($this->GetAllAffRewards() as $reward)
		{	echo '<tr><td>reward</td><td>alliliate scheme</td><td></td><td class="num"></td><td class="num">&minus; ', number_format($reward['amount'], 2), '</td><td class="num"></td><td class="num">&minus; ', number_format($reward['amount'], 2), '</td></tr>';
			$totalprice -= $reward['amount'];
			$totalpricetax -= $reward['amount'];
		}
		foreach ($this->GetBundles() as $bundle)
		{	echo '<tr><td>bundle</td><td>', $this->InputSafeString($bundle['bname']), '</td><td>', (int)$bundle['qty'], '</td><td class="num">&minus; ', number_format($bundle['discount'], 2), '</td><td class="num">&minus; ', number_format($bundle['discount'], 2), '</td><td class="num">&minus; ', number_format($bundle['totaldiscount'], 2), '</td><td class="num">&minus; ', number_format($bundle['totaldiscount'], 2), '</td></tr>';
			$totalprice -= $bundle['totaldiscount'];
			$totalpricetax -= $bundle['totaldiscount'];
		}
		if ($total_discounts)
		{	//echo '<tr><td>Discount</td><td colspan="5">', $this->InputSafeString($discount->details['discdesc']), '</td></td><td class="num">&minus; ', number_format($this->details['discamount'], 2), '</td></tr>';
			$totalprice -= $total_discounts;
			$totalpricetax -= $total_discounts;
		}
		if ($this->details['delivery_price'])
		{	echo '<tr><td>delivery</td><td colspan="5">', ($this->details['delivery_id'] && ($deloption = new DeliveryOption($this->details['delivery_id'])) && $deloption->id) ? $this->InputSafeString($deloption->details['title']) : '','</td><td class="num">', number_format($this->details['delivery_price'], 2), '</td></tr>';
			$totalpricetax += $this->details['delivery_price'];
			$totalprice += $this->details['delivery_price'];
		}
		if ($this->details['txfee'] > 0)
		{	echo '<tr><td></td><td colspan="5">Transaction fee</td><td class="num">', number_format($this->details['txfee'], 2), '</td></tr>';
			$totalpricetax += $this->details['txfee'];
			$totalprice += $this->details['txfee'];
		}
		echo '<tr><th colspan="5">Totals</th><th class="num">', number_format($totalprice, 2), '</th><th class="num">', number_format($totalpricetax, 2), '</th></tr></table>';
		return ob_get_clean();
	} // end of fn DisplayItems
	
	public function DisplayCart()
	{	ob_start();
		if ($cart = $this->GetCart())
		{	echo '<div class="orderDetails"><h3>Cart started ', date('H:i:s', strtotime($cart->details['created'])), '</h3><table><tr><th></th><th>Item</th><th>Quantity<br />added/removed</th></tr>';
			$products = array();
			foreach ($cart->items as $item)
			{	if (!$products[$prod_key = $item['ptype'] . '|' . $item['pid']])
				{	$p = $this->GetProduct($item['pid'], $item['ptype']);
					$products[$prod_key] = array('desc'=>$item['ptype'] . ' - ' . $p->GetName(), 'qty'=>0);
				}
				$products[$prod_key]['qty'] += $item['qty'];
				echo '<tr class="cartitem_', $item['qty'] > 0 ? 'plus' : 'minus', '"><td>', date('H:i:s', strtotime($item['addtime'])), '</td><td>', $products[$prod_key]['desc'], '</td><td>', $item['qty'] > 0 ? '+' : '', $item['qty'], ' (', $products[$prod_key]['qty'], ')</td></tr>';
			}
			echo '</table></div>';
		}
		return ob_get_clean();
	} // end of fn DisplayCart
	
	public function AdminDeliveryForm()
	{	ob_start();
		
		return ob_get_clean();
	} // end of fn AdminDeliveryForm
	
	public function CanDelete()
	{	return $this->id && !(int)$this->details['paiddate'];
	} // end of fn CanDelete
	
	protected function DeleteExtra()
	{	$this->db->Query('DELETE FROM storeorderbundles WHERE orderid=' . (int)$this->id);
	//	foreach ($this->GetItems() as $orderitem)
	//	{	$this->db->Query('DELETE FROM storegifts WHERE itemid=' . (int)$orderitem['id']);
	//	}
		$this->db->Query('DELETE FROM storeorderitems WHERE orderid=' . (int)$this->id);
		$this->db->Query('DELETE FROM orderattendees WHERE orderid=' . (int)$this->id);
	} // end of fn DeleteExtra
	
} // end of class AdminStoreOrder
?>
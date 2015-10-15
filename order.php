<?php 
require_once('init.php');

class OrderPage extends AccountPage
{	private $order;
	
	function __construct()
	{	parent::__construct();
	} // end of fn __construct

	function LoggedInConstruct()
	{	parent::LoggedInConstruct('orders');
		
		$this->css[] = 'store.css';
		
		$this->order = new StoreOrder($_GET['id']);
		
		if (!$this->order->id || $this->order->details['sid'] != $this->user->id)
		{	$this->Redirect('account.php');
		}
		$this->AddBreadcrumb('Order #' . $this->order->id);
		
	} // end of fn LoggedInConstruct
	
	function LoggedInMainBody()
	{	
		echo $this->DisplayItems(), '<div class="checkout-address-boxes clearfix">', $this->DisplayAddress(), '</div>';
		
	} // end of fn LoggedInMainBody
	
	protected function PageHeaderText()
	{	return parent::PageHeaderText() . ' - order #' . $this->order->id;
	} // end of fn PageHeaderText
	
	function DisplayItems()
	{
		ob_start();
		$discounts = array();
		$total_discounts = 0;
		echo '<table class="myacList"><tr><th>Type</th><th>Item name</th><th>Qty</th><th>Unit Price</th><th>Total Price</th></tr>';
		foreach ($this->order->GetItems() as $item)
		{	echo '<tr><td>', $this->InputSafeString($item['ptype']), '</td><td>', $this->InputSafeString(preg_replace("/\(\([^)]+\)\)/","",$item['title']));
			switch ($item['ptype'])
			{	case 'store':
					$product = new StoreProduct($item['pid']);
					echo '<span class="prodItemCode">Code: ', $product->ProductID(), '</span>', $product->ListDownloads($this->user), $product->ListPurchasedMM($this->user);
					break;
				case 'course':
					$ticket = new CourseTicket($item['pid']);
					$course = new Course($ticket->details['cid']);
					echo '<span class="prodItemCode">Code: ', $course->ProductID(), '</span>';
					break;
			}
			echo '</td><td>', (int)$item['qty'], '</td><td class="num">', number_format($item['pricetax'], 2), '</td><td class="num">', number_format($item['totalpricetax'], 2), '</td></tr>';
			$totalpricetax += $item['totalpricetax'];
			foreach ($item['discounts'] as $item_discount)
			{	if (!$discounts[$item_discount['discid']])
				{	$discounts[$item_discount['discid']] = new DiscountCode($item_discount['discid']);
				}
				echo '<tr class="itemsTableSubRow"><td>discount</td><td colspan="3">', $this->InputSafeString($discounts[$item_discount['discid']]->details['discdesc']), '</td><td class="num">&minus; ', number_format($item_discount['discamount'], 2), '</td></tr>';
				$total_discounts += $item_discount['discamount'];
			}
		}
		foreach ($this->order->GetAllReferrerRewards() as $reward)
		{	echo '<tr><td>reward</td><td colspan="2">for refer-a-friend</td><td class="num"></td><td class="num">&minus; ', number_format($reward['amount'], 2), '</td></tr>';
			$totalpricetax -= $reward['amount'];
		}
		foreach ($this->order->GetAllAffRewards() as $reward)
		{	echo '<tr><td>reward</td><td colspan="2">affiliate scheme</td><td class="num"></td><td class="num">&minus; ', number_format($reward['amount'], 2), '</td></tr>';
			$totalpricetax -= $reward['amount'];
		}
		foreach ($this->order->GetBundles() as $bundle)
		{	echo '<tr><td>bundle</td><td>', $this->InputSafeString($bundle['bname']), '</td><td>', (int)$bundle['qty'], '</td><td class="num">&minus; ', number_format($bundle['discount'], 2), '</td><td class="num">&minus; ', number_format($bundle['totaldiscount'], 2), '</td></tr>';
			$totalpricetax -= $bundle['totaldiscount'];
		}
		if ($total_discounts)
		{	echo '<tr><td colspan="4">Total discount</td></td><td class="num">&minus; ', number_format($total_discounts, 2), '</td></tr>';
			$totalpricetax -= $total_discounts;
		}
		if ($this->order->details['delivery_price'] > 0)
		{	
			echo '<tr><td>delivery</td><td colspan="3">', ($this->order->details['delivery_id'] && ($deloption = new DeliveryOption($this->order->details['delivery_id'])) && $deloption->id) ? $this->InputSafeString($deloption->details['title']) : '','</td><td class="num">', number_format($this->order->details['delivery_price'], 2), '</td></tr>';
			$totalpricetax += $this->order->details['delivery_price'];
		}
		if ($this->order->details['txfee'] > 0)
		{	echo '<tr><td colspan="4">Transaction fee</td><td class="num">', number_format($this->order->details['txfee'], 2), '</td></tr>';
			$totalpricetax += $this->order->details['txfee'];
		}
		echo '<tr><th colspan="4">Total</th><th class="num">', number_format($totalpricetax, 2), '</th></tr></table>';
		
		return ob_get_clean();
	} // end of fn DisplayItems
	
	function DisplayAddress()
	{	
		ob_start();
		
		if($this->order->details['delivery_address1'] != '')
		{
			echo '<div class="checkout-address-deliverybox"><h3>Delivery Address</h3><p>', $this->InputSafeString($this->order->details['delivery_firstname'] .' '. $this->order->details['delivery_surname']), '</p>';
			if ($this->order->details['delivery_address1'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['delivery_address1']), '</p>';
			}
			if ($this->order->details['delivery_address2'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['delivery_address2']), '</p>';
			}
			if ($this->order->details['delivery_address3'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['delivery_address3']), '</p>';
			}
			if ($this->order->details['delivery_city'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['delivery_city']), '</p>';
			}
			if ($this->order->details['delivery_postcode'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['delivery_postcode']), '</p>';
			}
			if ($this->order->details['delivery_phone'] != '')
			{	echo '<p>phone: ', $this->InputSafeString($this->order->details['delivery_phone']), '</p>';
			}
			echo '</div>';
		}
		
		if($this->order->details['payment_address1'] != '')
		{
			echo '<div class="checkout-address-deliverybox"><h3>Payment Address</h3><p>', $this->InputSafeString($this->order->details['payment_firstname'] . ' ' . $this->order->details['payment_surname']), '</p>';
			if ($this->order->details['payment_address1'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['payment_address1']), '</p>';
			}
			if ($this->order->details['payment_address2'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['payment_address2']), '</p>';
			}
			if ($this->order->details['payment_address3'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['payment_address3']), '</p>';
			}
			if ($this->order->details['payment_city'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['payment_city']), '</p>';
			}
			if ($this->order->details['payment_postcode'] != '')
			{	echo '<p>', $this->InputSafeString($this->order->details['payment_postcode']), '</p>';
			}
			if ($this->order->details['payment_phone'] != '')
			{	echo '<p>phone: ', $this->InputSafeString($this->order->details['payment_phone']), '</p>';
			}
			echo '</div>';
				
		}
		echo '<div class="clear"></div>';
		return ob_get_clean();
	} // end of fn DisplayAddress

	public function DisplayStatus()
	{	ob_start();
		
		return ob_get_clean();
	} // end of fn DisplayStatus
	
} // end of defn BookingPage

$page = new OrderPage();
$page->Page();
?>
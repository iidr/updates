<?php 
require_once('init.php');

class CheckoutReviewPayment extends CheckoutPage
{	protected $bc_to_show = array('course_reg'=>true, 'address'=>true, 'delivery'=>true, 'checkout'=>true);
	
	function __construct()
	{	parent::__construct();
		
		$this->next_stage = 'checkout-paypal.php';
		
		if(!$this->user-id || $this->GetStage() < 4)
		{	$this->RedirectToPreviousStage();	
		}
		
		if (isset($_POST['submit_payment']))
		{	if ($_POST['tandc_confirmed'])
			{
				if ($this->CreateOrder())
				{
					// If free order, process it
					if ($this->order->GetTotal(true) == 0.00)
					{	//echo 'zero price';
						//exit;
						$this->order->RecordFreePayment(); 
						$this->order->SendCompletedEmail();
						$this->Redirect('order-success.php');
					} else
					{	$this->SetPreviousStage('checkout-payment.php');
						$this->RedirectToNextStage();
					}
				} else
				{
					$this->failmessage = 'Could not save the order, please try again.';
				}
			} else
			{	$this->failmessage = 'You must agree to our terms and conditions.';
			}
		}
		
	} // end of fn MainBodyContent
	
	public function MainBodyContent()
	{
		$price_today = $this->cart->GetTotal(true);
		$total_price = $this->cart->GetTotal();
		echo '<h1>Please review and pay</h1><div class="checkout-address-boxes clearfix">', $this->DisplayAddress(), '</div><h2>Items:</h2>', $this->DisplayItems(), '<div class="cartTotals">';
		
		if($this->cart->HasShipping() && $this->cart->shipping)
		{	echo '<p class="cart-delivery clearfix">Delivery:<span>', $this->formatPrice($this->cart->GetShippingTotal()), '</span></p>';
		}
		
		if ($sub_savings = $this->cart->SubsTotalsUsed())
		{	echo '<p class="cart-bundle clearfix">Subscription savings:<span>&minus; ', $this->formatPrice($sub_savings), '</span></p>';
		}
		
		if ($rewards = $this->cart->RewardsTotalsUsed())
		{	echo '<p class="cart-bundle clearfix">Refer-a-Friend Rewards:<span>&minus; ', $this->formatPrice($rewards), '</span></p>';
			$total_price -= $rewards;
			$price_today -= $rewards;
		}
	
		if ($this->cart->bundles)
		{	foreach ($this->cart->bundles as $bundleid=>$bundle_qty)
			{	$bundle = new Bundle($bundleid);
				echo '<p class="cart-bundle clearfix">', $this->InputSafeString($bundle->details['bname']);
				if ($bundle_qty > 1)
				{	echo ' (', $bundle_qty, ' @ ', $this->formatPrice($bundle->details['discount']), ')';
				}
				echo '<span>&minus; ', $this->formatPrice($bprice = $bundle_qty * $bundle->details['discount']), '</span></p>';
				$total_price -= $bprice;
				$price_today -= $bprice;
			}
		}
		
		if (is_array($this->cart->discount) && $this->cart->discount)
		{	$discount_total = 0;
			foreach ($this->cart->discount as $cart_discount)
			{	if ($cart_discount['applied_amount'])
				{	$discount_total += array_sum($cart_discount['applied_amount']);
				}
			}
			if ($discount_total)
			{	echo '<p class="cart-vat cart-discount_total clearfix">Discount<span>&minus; ', $this->formatPrice($discount_total), '</span></p>';
			}
		}

		echo '<p class="cart-vat clearfix">VAT<span>', $this->formatPrice($this->cart->GetTax()), '</span></p>';
		if ($txfee = $this->cart->TransactionFee())
		{	echo '<p class="cart-vat clearfix">Transaction fee<span>', $this->formatPrice($txfee), '</span></p>';
			$total_price += $txfee;
			$price_today += $txfee;
		}
	
		if($price_today == $total_price)
		{	echo '<p class="cart-total clearfix">Total<span>', $this->formatPrice($total_price), '</span></p>';
		} else
		{
			echo '<p class="cart-total clearfix">Total<span>', $this->formatPrice($total_price), '</span></p><p class="cart-total clearfix">Total today:<span>', $this->formatPrice($price_today), '</span></p>';
		}
		$tandc = new PageContent('terms-and-policies');
		echo '<div class="clear"></div><form method="post" action=""><p class="tandc_checkbox"><label>Please confirm that you accept our <a href="', $tandc->Link(), '" target="_blank">terms &amp; conditions</a></label><input type="checkbox" name="tandc_confirmed" value="1" /></p>';
		if (!$price_today)
		{	echo '<p><input type="submit" name="submit_payment" value="Confirm Order" class="button-link checkout-link" /></p>';
		} else
		{	echo '<p><input type="submit" name="submit_payment" value="Continue to PayPal" class="button-link checkout-link" /></p>';
		}
		echo '<div class="clear"></div></div></form>';
	} // end of fn MainBodyContent
	
	public function DisplayItems()
	{	ob_start();
		if ($this->cart->items)
		{	$oos = new ProductStatus();
			$oos->GetByName('oos');
			foreach($this->cart->items as $rowid => $p)
			{
				if ($p['type'] == 'course')
				{	$course = 1;	
				}
				if ($p['type'] == 'store')
				{	$store = 1;	
				}
				if($p['type'] == 'sub')
				{	$subs = 1;	
				}
			}
			
			$disc_names = array('discount'=>'Voucher discount', 'sub'=>'Free with your subscription', 'rewards'=>'Your Refer-a-Friend rewards', 'bundles'=>'Discount for bundled offer');
			
			echo '<form action="" method="post">';
			if ($course)
			{	echo '<div class="coursecart clearfix"><h2>Courses / Events</h2><table class="cartlisting" border="0" cellspacing="0" cellpadding="0" ><tr><th colspan="2">Course / Event</th><th>Quantity</th><th class="number">Unit Price</th><th class="number">Discount</th><th class="number">Price</th></tr>';
				$coursesubtotal = '0';
				foreach ($this->cart->items as $rowid => $p)
				{
					if ($p['type'] == 'course')
					{	$p['product']->course->venue = $p['product']->course->GetVenue();
						echo '<tr class="productRow"><td class="prodImage"><div><a href="', $p['product']->GetLink(), '">';
						if($img = $p['product']->HasImage('thumbnail'))
						{	echo "<img src='", $img, "' alt='", $p['product']->GetLink(), "' />";	
						} else
						{	echo "<img src='", SITE_URL, "img/products/default.png' alt='", $p['product']->GetLink(), "' />";
						}
						echo '</a></div></td><td class="prodItemName"><h4><a href="', $p['product']->GetLink(), '">', $this->InputSafeString($p['product']->GetName(false)),'</a></h4><p>', date('jS F Y', strtotime($p['product']->course->details['starttime'])), '<br />', $p['product']->course->venue->details['vcity'], '</p><p class="prodItemCode">Code: ', $p['product']->ProductID(), '</p></td><td class="prodItemQty">', $p['qty'], '</td><td class="number prodUnitPrice">', $this->formatPrice($p['price_with_tax']), '</td><td class="number prodDiscount">', ($discount_item = $this->cart->ItemDiscountSum($p['discounts'])) ? $this->formatPrice($discount_item) : '', '</td><td class="number prodItemPrice"><strong>', $this->formatPrice($item_total = ($p['price_with_tax'] * $p['qty']) - $discount_item), '</strong></td></tr>';
						$coursesubtotal += $item_total;
					}
				}
				echo '<tr class="cartSubtotalRow"><td colspan="5" class="subtotal">Subtotal</td><td class="subtotalprice number">', $this->formatPrice($coursesubtotal), '</td></tr></table></div>';
			}
			
			if ($store)
			{	$storesubtotal = 0;
				echo '<div class="storecart clearfix"><h2>Products</h2><table class="cartlisting" border="0" cellspacing="0" cellpadding="0" ><tr><th colspan="2">Item</th><th>Quantity</th><th class="number">Unit Price</th><th class="number">Discount</th><th class="number">Price</th></tr>';
				foreach ($this->cart->items as $rowid => $p)
				{
					if ($p['type'] == 'store')
					{	echo '<tr class="productRow"><td class="prodImage"><div><a href="', $p['product']->GetLink(), '">';
						if($img = $p['product']->HasImage('thumbnail'))
						{	echo '<img src="', $img, '" alt="', $p["product"]->GetLink(), '" />';	
						} else
						{	echo '<img src="',  SITE_URL, 'img/products/default.png" alt="', $p['product']->GetLink(), '" />';
						}
						echo '</a></div></td><td class="prodItemName"><h4><a href="', $p['product']->GetLink(), '">', $this->InputSafeString($p['product']->GetName()), '</a></h4><p class="prodItemCode">Code: ', $p['product']->ProductID(), '</p></td><td class="prodItemQty">', $p['qty'], '</td><td class="number prodUnitPrice">', $this->formatPrice($p['price_with_tax']), '</td><td class="number prodDiscount">', ($discount_item = $this->cart->ItemDiscountSum($p['discounts'])) ? $this->formatPrice($discount_item) : '', '</td><td class="number prodItemPrice"><strong>', $this->formatPrice($item_total = ($p['price_with_tax'] * $p['qty']) - $discount_item), '</strong></td></tr>';
						$storesubtotal += $item_total;
					}
				}
				echo '<tr class="cartSubtotalRow"><td colspan="5" class="subtotal">Subtotal</td><td class="subtotalprice number">', $this->formatPrice($storesubtotal), '</td></tr></table></div>';
			}
			
			if ($subs)
			{	$subssubtotal = 0;
				echo '<div class="storecart clearfix"><h2>Subscription</h2><table class="cartlisting" border="0" cellspacing="0" cellpadding="0" ><tr><th colspan="2"></th><th>Quantity</th><th class="number">Unit Price</th><th class="number">Discount</th><th class="number">Price</th></tr>';
				foreach ($this->cart->items as $rowid => $p)
				{
					if ($p['type'] == 'sub')
					{	echo '<tr class="productRow"><td class="prodImage"><div><a href="', $p['product']->GetLink(), '">';
						if($img = $p['product']->HasImage('thumbnail'))
						{	echo '<img src="', $img, '" alt="', $p["product"]->GetLink(), '" />';	
						} else
						{	echo '<img src="',  SITE_URL, 'img/products/default.png" alt="', $p['product']->GetLink(), '" />';
						}
						echo '</a></div></td><td class="prodItemName"><h4><a href="', $p['product']->GetLink(), '">', $this->InputSafeString($p['product']->GetName()), '</a></h4></td><td class="prodItemQty">', $p['qty'], '</td><td class="number prodUnitPrice">', $this->formatPrice($p['price_with_tax']), '</td><td class="number prodDiscount">', ($discount_item = $this->cart->ItemDiscountSum($p['discounts'])) ? $this->formatPrice($discount_item) : '', '</td><td class="number prodItemPrice"><strong>', $this->formatPrice($item_total = ($p['price_with_tax'] * $p['qty']) - $discount_item), '</strong></td></tr>';
						$subssubtotal += $item_total;
					}
				}
				echo '<tr class="cartSubtotalRow"><td colspan="5" class="subtotal">Subtotal</td><td class="subtotalprice number">', $this->formatPrice($subssubtotal), '</td></tr></table></div>';
			}
			echo '</form>';
		}
		
		return ob_get_clean();
	} // end of fn DisplayItems
	
	public function DisplayAddress()
	{
		ob_start();
		echo '<div class="checkout-address-deliverybox">';
		if(isset($_SESSION['order']['delivery_address']))
		{
			$address = array('<strong>' . $this->InputSafeString($_SESSION['order']['delivery_address']['firstname'] . ' ' . $_SESSION['order']['delivery_address']['surname']) . '</strong><br />');
			if($_SESSION['order']['delivery_address']['address1']) 
			{	$address[] = nl2br($this->InputSafeString($_SESSION['order']['delivery_address']['address1']));
			}
			if($_SESSION['order']['delivery_address']['address2']) 
			{	$address[] = nl2br($this->InputSafeString($_SESSION['order']['delivery_address']['address2']));
			}
			if($_SESSION['order']['delivery_address']['address3']) 
			{	$address[] = nl2br($this->InputSafeString($_SESSION['order']['delivery_address']['address3']));
			}
			if($_SESSION['order']['delivery_address']['city'])
			{	$address[] = $this->InputSafeString($_SESSION['order']['delivery_address']['city']);
			}
			if($_SESSION['order']['delivery_address']['postcode'])
			{	$address[] = $this->InputSafeString($_SESSION['order']['delivery_address']['postcode']);
			}
			if($_SESSION['order']['delivery_address']['phone'])
			{	$address[] = 'phone: ' . $this->InputSafeString($_SESSION['order']['delivery_address']['phone']);
			}
			
			echo '<h2>Delivery Address</h2><div class="inner clearfix checkoutDelDisplay">', implode('<br />', $address), '</div>';
		}
		echo '</div><div class="checkout-address-paymentbox">';
		
		if(isset($_SESSION['order']['payment_address']))
		{
			$address = array('<strong>' . $this->InputSafeString($_SESSION['order']['payment_address']['firstname'] . ' ' . $_SESSION['order']['payment_address']['surname']) . '</strong><br />');
			if($_SESSION['order']['payment_address']['address1']) 
			{	$address[] = nl2br($this->InputSafeString($_SESSION['order']['payment_address']['address1']));
			}
			if($_SESSION['order']['payment_address']['address2']) 
			{	$address[] = nl2br($this->InputSafeString($_SESSION['order']['payment_address']['address2']));
			}
			if($_SESSION['order']['payment_address']['address3']) 
			{	$address[] = nl2br($this->InputSafeString($_SESSION['order']['payment_address']['address3']));
			}
			if($_SESSION['order']['payment_address']['city'])
			{	$address[] = $this->InputSafeString($_SESSION['order']['payment_address']['city']);
			}
			if($_SESSION['order']['payment_address']['postcode'])
			{	$address[] = $this->InputSafeString($_SESSION['order']['payment_address']['postcode']);
			}
			if($_SESSION['order']['payment_address']['phone'])
			{	$address[] = 'phone: ' . $this->InputSafeString($_SESSION['order']['payment_address']['phone']);
			}
			
			echo '<h2>Payment Address</h2><div class="inner clearfix checkoutDelDisplay">', implode('<br />', $address), '</div>';
		}
		echo '</div>';
		
		return ob_get_clean();
	} // end of fn DisplayAddress
	
	public function CreateOrder()
	{
		// create order from session;
		$this->order = new StoreOrder();
		
		if ($success = $this->order->CreateFromSession($this->user))
		{	$this->order = new StoreOrder($success);
			// unset order sessions
			$this->cart->Destroy();
			unset($_SESSION['order']);
			return $_SESSION['order_id'] = $success;
		}
	} // end of fn CreateOrder

} // end of defn CheckoutReviewPayment

$page = new CheckoutReviewPayment;
$page->Page();

?>
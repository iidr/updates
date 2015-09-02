<?php 
require_once('init.php');

class CartStartPage extends CheckoutPage//CartPage
{	public $store;
	public $cart;
	
	function __construct()
	{	
		parent::__construct();		
		
		$this->store = new Store();
		$this->cart = new StoreCart();
		$this->js[] = 'checkout.js';
		
		// Add product
		if(isset($_POST['add']) && isset($_POST['type']) && isset($_POST['qty']))
		{	if ($this->cart->Insert($_POST['add'], $_POST['type'], $_POST['qty']))
			{	$this->Redirect('cart.php');
			}
		}
		
		// add bundle
		if (is_array($_GET['badd']) && $_GET['badd'])
		{	foreach ($_GET['badd'] as $key=>$pid)
			{	if (($pid = (int)$pid) && ($ptype = $_GET['btype'][$key]))
				{	$this->cart->Insert($pid, $ptype, 1);
				}
			}
			$this->Redirect('cart.php');
		}
		
		// Update Qty
		if(isset($_POST['update']))
		{	
			foreach($_POST['update'] as $id => $qty)
			{	$this->cart->Update($id, $qty);	
			}
			
			if(isset($_POST['pay_on_day']))
			{	foreach($_POST['pay_on_day'] as $id => $pod)
				{	$this->cart->UpdateValue($id, 'pay_on_day', $pod);
				}
			} else
			{	$this->cart->UpdateAllItemsValue('pay_on_day', false);
			}
			
			$this->cart->UpdateDiscount($_POST['disccode']);

			$this->Redirect('cart.php');
		}
			
		// Remove products
		if(isset($_GET['remove']))
		{	$this->cart->Remove($_GET['remove']);
			$this->Redirect('cart.php');
		}
	} // end of fn __construct
	
	protected function RedirectIfEmpty(){}
	public function Handler(){}
	
	function MainBodyContent()
	{	$this->DisplayCartErrors();
		/*echo '<pre>';
		//print_r($products);
		//print_r($_SESSION);
		echo '</pre>';*/
		if ($this->cart->items)
		{
			$oos = new ProductStatus();
			$oos->GetByName('oos');
			foreach($this->cart->items as $rowid => $p)
			{
				if($p['type'] == 'course')
				{	$course = 1;	
				}
				if($p['type'] == 'store')
				{	$store = 1;	
				}
				if($p['type'] == 'sub')
				{	$subs = 1;	
				}
			}
			
			echo '<form action="" method="post" id="cartform">';
			
			if ($course)
			{	echo '<div class="coursecart clearfix"><h2>Courses / Events</h2><table class="cartlisting" border="0" cellspacing="0" cellpadding="0" ><tr><th colspan="2">Course / Event</th><th class="number">Quantity</th><th class="number">Unit Price</th><th class="number">Discount</th><th class="number">Price</th></tr>';
				$coursesubtotal = '0';
				foreach ($this->cart->items as $rowid => $p)
				{	if ($p['type'] == 'course')
					{	$p['product']->course->venue = $p['product']->course->GetVenue();
						
						echo '<tr class="productRow"><td class="prodImage"><div><a href="', $p['product']->GetLink(), '">';
						if ($img = $p['product']->HasImage('thumbnail'))
						{	echo "<img src='", $img, "' alt='". $p['product']->GetLink(), "' />";	
						} else
						{	echo "<img src='", SITE_URL, "img/products/default.png' alt='", $p['product']->GetLink(), "' />";
						}
						echo '</a></div></td><td class="prodItemName"><h4><a href="', $p['product']->GetLink(), '">', $this->InputSafeString($p['product']->GetName(false)), '</a></h4><p>', date('jS F Y', strtotime($p['product']->course->details['starttime'])), '<br />', $p['product']->course->venue->details['vcity'], '</p><p class="prodItemCode">Code: ', $p['product']->ProductID(), '</p></td><td class="prodItemQty">',
						//'<input type="submit" name="updatecart" value="Update" class="update" />',
						'<input type="text" name="update[', $rowid, ']" value="', $p['qty'], '" size="2" /><div class="clear"></div><a class="update_below" onclick="UpdateCartForm();">update</a><div class="clear"></div><span class="cartremove"><a href="', $this->link->GetLink('cart.php?remove=' . $rowid), '">x&nbsp;&nbsp;remove</a></span></td><td class="number prodUnitPrice">', $this->formatPrice($p['price_with_tax']), '</td><td class="number prodDiscount">', ($discount_item = $this->cart->ItemDiscountSum($p['discounts'])) ? $this->formatPrice($discount_item) : '', '</td><td class="number prodItemPrice"><strong>', $this->formatPrice($item_total = ($p['price_with_tax'] * $p['qty']) - $discount_item), '</strong></td></tr>';
						$coursesubtotal += $item_total;
					}
				}
				echo '<tr class="cartSubtotalRow"><td colspan="5" class="subtotal">Subtotal</td><td class="subtotalprice number">', $this->formatPrice($coursesubtotal), '</td></tr></table></div>';
			}
			
			if($store)
			{	$storesubtotal = 0;
				echo '<div class="storecart clearfix"><h2>Products</h2><table class="cartlisting" border="0" cellspacing="0" cellpadding="0" ><tr><th colspan="2">Item</th><th class="number">Quantity</th><th class="number">Unit Price</th><th class="number">Discount</th><th class="number">Price</th></tr>';
				foreach($this->cart->items as $rowid => $p)
				{	if ($p['type'] == 'store')
					{	
						echo '<tr class="productRow"><td class="prodImage"><div><a href="', $p['product']->GetLink(), '">';
						if($img = $p['product']->HasImage('thumbnail'))
						{	echo '<img src="', $img, '" alt="', $p["product"]->GetLink(), '" />';	
						} else
						{	echo '<img src="', SITE_URL, 'img/products/default.png" alt="', $p["product"]->GetLink(), '" />';
						}
						echo '</a></div></td><td class="prodItemName"><h4><a href="', $p['product']->GetLink(), '">', $this->InputSafeString($p['product']->GetName()), '</a></h4><p class="prodItemCode">Code: ', $p['product']->ProductID(), '</p></td><td class="prodItemQty">',
						//'<input type="submit" name="updatecart" value="Update" class="update"/>',
						'<input type="text" name="update[', $rowid, ']" value="', $p['qty'], '" size="2" /><div class="clear"></div><a class="update_below" onclick="UpdateCartForm();">update</a><div class="clear"></div><span class="cartremove"><a href="', $this->link->GetLink('cart.php?remove='. $rowid), '">x&nbsp;&nbsp;remove</a></span></td><td class="number prodUnitPrice">', $this->formatPrice($p['price_with_tax']), '</td><td class="number prodDiscount">', ($discount_item = $this->cart->ItemDiscountSum($p['discounts'])) ? $this->formatPrice($discount_item) : '', '</td><td class="number prodItemPrice"><strong>', $this->formatPrice($item_total = ($p['price_with_tax'] * $p['qty']) - $discount_item), '</strong></td></tr>';
						$storesubtotal += $item_total;
					}
				}
				echo '<tr class="cartSubtotalRow"><td colspan="5" class="subtotal">Subtotal</td><td class="subtotalprice number">', $this->formatPrice($storesubtotal), '</td></tr></table></div>';
			}
			
			if($subs)
			{	$subssubtotal = 0;
				echo '<div class="coursecart clearfix"><h2>Subscription</h2><table class="cartlisting" border="0" cellspacing="0" cellpadding="0" ><tr><th colspan="2"></th><th class="number">Quantity</th><th class="number">Unit Price</th><th class="number">Discount</th><th class="number">Price</th></tr>';
				foreach($this->cart->items as $rowid => $p)
				{
					if ($p['type'] == 'sub')
					{	echo '<tr class="productRow"><td class="prodImage"><div><a href="', $p['product']->GetLink(), '">';
						if($img = $p['product']->HasImage('thumbnail'))
						{	echo '<img src="', $img, '" alt="', $p["product"]->GetLink(), '" />';	
						} else
						{	echo '<img src="', SITE_URL, 'img/products/default.png" alt="', $p["product"]->GetLink(), '" />';
						}
						echo '</a></div></td><td class="prodItemName"><h4><a href="', $p['product']->GetLink(), '">', $this->InputSafeString($p['product']->GetName()), '</a></h4></td><td class="number prodItemQty">', $p['qty'], '<div class="clear"></div><span class="cartremove"><a href="', $this->link->GetLink('cart.php?remove=' . $rowid), '">x&nbsp;&nbsp;remove</a></span></td><td class="number prodUnitPrice">', $this->formatPrice($p['price_with_tax']), '</td><td class="number prodDiscount">', ($discount_item = $this->cart->ItemDiscountSum($p['discounts'])) ? $this->formatPrice($discount_item) : '', '</td><td class="number prodItemPrice"><strong>', $this->formatPrice($item_total = ($p['price_with_tax'] * $p['qty']) - $discount_item), '</strong></td></tr>';
						$subssubtotal += $item_total;
					}
				}
				echo '<tr class="cartSubtotalRow"><td colspan="5" class="subtotal">Subtotal</td><td class="subtotalprice number">', $this->formatPrice($subssubtotal), '</td></tr></table></div>';
			}
			
			echo '<div class="cartTotals">';
			
			$price_today = $this->cart->GetTotal(true);
			$total_price = $this->cart->GetTotal();

			if ($this->cart->HasShipping() && $this->cart->shipping)
			{	echo '<p class="cart-delivery clearfix">Delivery <span>', $this->formatPrice($this->cart->GetShippingTotal()), '</span></p>';
				
			}
			
			if ($sub_savings = $this->cart->SubsTotalsUsed())
			{	echo '<p class="cart-bundle clearfix">Subscription savings <span>&minus; ', $this->formatPrice($sub_savings), '</span></p>';
			//	$total_price -= $sub_savings;
			//	$price_today -= $sub_savings;
			}
			
			if ($rewards = $this->cart->RewardsTotalsUsed())
			{	echo '<p class="cart-bundle clearfix">Refer-a-Friend Rewards <span>&minus; ', $this->formatPrice($rewards), '</span></p>';
				$total_price -= $rewards;
				$price_today -= $rewards;
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
			} else
			{	echo '<div class="cart-discount clearfix"><span>Enter discount code</span><div id="discount_input_container">';
				if (is_array($this->cart->discount) && $this->cart->discount)
				{	foreach ($this->cart->discount as $discount)
					{	echo '<input type="text" name="disccode[]" value="', $discount['disccode'], '" />';
					}
				}
				echo '<input type="text" name="disccode[]" value="" /></div><input type="submit" name="updatecart" value="Activate" class="update"/><div class="clear"></div></div>';
			}
			
			$total_tax = $this->cart->GetTax();
			echo '<div id="cart_totals"><p class="cart-vat clearfix">VAT<span>', $this->formatPrice($total_tax), '</span></p>';
			if ($txfee = $this->cart->TransactionFee())
			{	echo '<p class="cart-vat clearfix">Transaction fee<span>', $this->formatPrice($txfee), '</span></p>';
				$total_price += $txfee;
				$price_today += $txfee;
			}
			if ($price_today == $total_price)
			{	
				echo '<p class="cart-total clearfix">Total<span>', $this->formatPrice($total_price), '</span></p>';
			} else
			{
				echo '<p class="cart-total clearfix">Total<span>', $this->formatPrice($total_price), '</span></p><p class="cart-total clearfix">Total today <span>', $this->formatPrice($price_today), '</span></p>';
			}
			echo '</div><div class="clear"></div>';
			
			//if(!$this->cart->GetErrors())
			if($subs || $store || $course)
			{	echo '<div class="cartlinks_container">';
				if ($txfee)
				{	//echo '<div class="txfee_desc">', $this->cart->TransactionFeeDescription(), '</div>';
				}
				echo '<div class="cart-links clearfix"><a href="', $this->link->GetLink('checkout.php'), '" class="button-link checkout-link">Go to checkout</a><a href="', $this->link->GetLink('store.php'), '" class="button-link continue-shopping">Continue Shopping</a></div></div>';
			}
			echo '<div class="clear"></div></div></form>';
		} else
		{	//echo '<p>Your cart contains no items.</p><p><a href="', $this->link->GetLink('store.php'), '">Continue Shopping</a></p>';
		}
		
	} // end of fn MainBodyContent
	
	function DisplayCartErrors()
	{
		if($errors = $this->cart->GetErrors())
		{
			echo '<div class="cart_failmessage"><ul>';
			foreach($errors as $error)
			{
				echo '<li>', $error, '</li>';
			}
			echo '</ul></div>';
		}
	} // end of fn DisplayCartErrors
	
} // end of defn CartStartPage

$page = new CartStartPage();
$page->Page();
?>
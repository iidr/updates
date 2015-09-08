<?php 
require_once('init.php');

class CheckoutDelivery extends CheckoutPage
{	private $option;	
	private $options = array();
	protected $bc_to_show = array('course_reg'=>true, 'address'=>true, 'delivery'=>true);
	
	function __construct()
	{	
		parent::__construct('store');
		$this->next_stage = 'checkout-payment.php';
		
		if (!$this->user-id || $this->GetStage() < 3)
		{	$this->RedirectToPreviousStage();	
		}
		
		// Bypass if shipping not required
		if (!$this->cart->HasShipping())
		{	$this->RedirectToNextStage();
		}
		
		$productWeight = 0.00;
		
		if(count($this->cart->items)>0){
			foreach($this->cart->items as $key=>$value){
				$tpWeight = 0.00;
				$pWeight = ($value['product']->details['weight']>0)?$value['product']->details['weight']:0.00;
				if($pWeight>0){
					$tpWeight = $pWeight*$value['qty'];
					$productWeight += $tpWeight;
				}
			}
		}	
		
		// Delivery methods
		$this->options = $this->cart->DeliveryOptions($this->user->details['country'],$productWeight);
		$shipping = $this->cart->GetShipping();
		
		if($shipping){	
			$this->option = $shipping;
		}else{
			if($this->options){	
				$this->option = $this->options[0];	
			}
		}
		
		if (isset($_POST['shipping_method']))
		{	foreach ($this->options as $id => $option)
			{	if ($option->id == $_POST['shipping_method'])
				{	$this->option = $option;
					$this->cart->SetShipping($option);
					$this->SetPreviousStage('checkout-delivery.php');
					$this->RedirectToNextStage();
				}
			}
		}
		
	} // end of fn __construct
	
	public function MainBodyContent()
	{
		echo '<div id="checkoutGiftContainer"><h2>Choose your delivery method</h2><form class="cartDelForm" action="" method="post"><table>';
		foreach ($this->options as $option)
		{
			echo '<tr class="productRow"><td><input type="radio" name="shipping_method" value="', (int)$option->id, '" ', ($this->option->id == $option->id ? 'checked="checked"' : ''), ' /></td><td><h4>', $this->InputSafeString($option->details['title']), '</h4><p>', $this->InputSafeString($option->details['description']), '</p></td><td class="number">', $this->formatPrice($option->GetPrice()), '</td></tr>';	
		}
		echo '</table><p><input type="submit" name="submit_delivery" value="Continue" class="button-link checkout-link" /></p></form><div class="clear"></div></div>';
	} // end of fn __construct

} // end of defn MainBodyContent

$page = new CheckoutDelivery;
$page->Page();
?>
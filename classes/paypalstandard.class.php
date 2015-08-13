<?php
class PayPalStandard extends Base
{	var $ourpaypalid = '';
	var $ourpaypalid_live = 'QGXSY768A6Q5Y';
	var $ourpaypalid_test = 'PHRWHN7P63BMN';
	var $ourpaypalemail = '';
	var $ourpaypalemail_live = 'info@iidr.org';
	var $ourpaypalemail_test = 'matsel_1268820520_biz@websquare.co.uk';
	var $returnurl = '';
	var $cancelurl = '';
	var $paypal_url = '';
	var $paypal_url_live = 'https://www.paypal.com/cgi-bin/webscr';
	var $paypal_url_test = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	var $notifyurl = '';
	var $notifyurl_live = '';
	var $notifyurl_test = '';
	var $verify_url = '';
	var $verify_url_live = 'https://www.paypal.com/cgi-bin/webscr';
	var $verify_url_test = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	var $live = false;
	var $paypal_lang = 'GB';

	function __construct($live = false)
	{	parent::__construct();
		$this->returnurl = SITE_URL . 'order-success.php';
		$this->cancelurl = SITE_URL . 'order-cancel.php';
		$this->notifyurl_live = SITE_URL . 'pp_ear.php';
		$this->notifyurl_test = SITE_URL . 'pp_ear.php';
		$this->SetLive($live);
		
	} // fn __construct
	
	function SetLive($live = false)
	{	if ($this->live = $live)
		{	$this->paypal_url = $this->paypal_url_live;
			$this->notifyurl = $this->notifyurl_live;
			$this->ourpaypalid = $this->ourpaypalid_live;
			$this->ourpaypalemail = $this->ourpaypalemail_live;
			$this->verify_url = $this->verify_url_live;
		} else
		{	$this->paypal_url = $this->paypal_url_test;
			$this->notifyurl = $this->notifyurl_test;
			$this->ourpaypalid = $this->ourpaypalid_test;
			$this->ourpaypalemail = $this->ourpaypalemail_test;
			$this->verify_url = $this->verify_url_test;
		}
	} // end of fn SetLive
	
	public function OutputOrderForm(StoreOrder $order)
	{	ob_start();
		echo '<form action="', $this->paypal_url, '" method="post" class="paypalform"><input type="hidden" name="cmd" value="_cart" /><input type="hidden" name="upload" value="1" /><input type="hidden" name="custom" value="', $order->id, '" /><input type="hidden" name="business" value="', $this->ourpaypalid, '" /><input type="hidden" name="return" value="', $this->returnurl, '" /><input type="hidden" name="cancel_return" value="', $this->cancelurl, '" /><input type="hidden" name="notify_url" value="', $this->notifyurl, '" /><input type="hidden" name="currency_code" value="GBP" /><input type="hidden" name="no_shipping" value="1" /><input type="hidden" name="rm" value="2" />';
		
		if ($order->details['delivery_price'])
		{	echo '<input type="hidden" name="shipping_1" value="', number_format($order->details['delivery_price'], 2, '.', ''), '" />';
		}
		
		$itemcount = 0;
		$items = array();

		foreach ($order->GetItems() as $itemid=>$item)
		{	$discount_total = floor(($item['discount_total'] * 100) / $item['qty']) / 100;
			if ($discount_total < $item['pricetax'])
			{	
				echo '<input type="hidden" name="item_name_', ++$itemcount, '" value="', $this->InputSafeString($item['title']),'" /><input type="hidden" name="amount_', $itemcount, '" value="', number_format($item['pricetax'], 2, '.', ''), '" /><input type="hidden" name="quantity_', $itemcount, '" value="', (int)$item['qty'], '" /><input type="hidden" name="discount_amount_', $itemcount, '" value="', number_format($discount_total, 2, '.', ''), '" />';
			}
		}
		if ($order->details['txfee'] > 0)
		{	echo '<input type="hidden" name="item_name_', ++$itemcount, '" value="Transaction fee" /><input type="hidden" name="amount_', $itemcount, '" value="', number_format($order->details['txfee'], 2, '.', ''), '" /><input type="hidden" name="quantity_', $itemcount, '" value="1" /><input type="hidden" name="discount_amount_', $itemcount, '" value="0.00" />';
		}
		
		echo '<noscript><input type="image" style="border:none;" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" alt="PayPal - The safer, easier way to pay online" /></noscript><img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" /></form>';
		
		return ob_get_clean();
	} // end of fn OutputOrderForm
	
	public function OutputOrderFormBuyNow(StoreOrder $order)
	{	ob_start();
		echo '<form action="', $this->paypal_url, '" method="post" class="paypalform">
				<input type="hidden" name="cmd" value="_xclick" />
				<input type="hidden" name="custom" value="', $order->id, '" />
				<input type="hidden" name="business" value="', $this->ourpaypalid, '" />
				<input type="hidden" name="return" value="', $this->returnurl, '" />
				<input type="hidden" name="cancel_return" value="', $this->cancelurl, '" />
				<input type="hidden" name="notify_url" value="', $this->notifyurl, '" />
				<input type="hidden" name="currency_code" value="GBP" />
				<input type="hidden" name="item_name" value="IIDR Order number ', $order->id, '" />
				<input type="hidden" name="amount" value="', number_format($order->GetRealTotal(), 2, '.', ''), '" />
				<input type="hidden" name="no_shipping" value="1" />
				<input type="hidden" name="no_note" value="1" />
				<input type="hidden" name="lc" value="GB" />
				<input type="hidden" name="rm" value="2" />
				<noscript><input type="image" style="border:none;" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" alt="PayPal - The safer, easier way to pay online" /></noscript><img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" /></form>';
		
		return ob_get_clean();
	} // end of fn OutputOrderFormBuyNow
	
	function PPDateToSQL($date)
	{	return $this->datefn->SQLDateTime(strtotime($date));
	} // end of fn PPDateToSQL
	
} // end if class defn PayPalStandard
?>
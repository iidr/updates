<?php
include_once('sitedef.php');

class ProductPurchasesPage extends AdminProductPage
{	var $startdate = '';
	var $enddate = '';
	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function ProductsLoggedInConstruct()
	{	parent::ProductsLoggedInConstruct('purchases');
		$this->css[] = 'adminmembers.css';
		$this->css[] = 'adminorders.css';
		$this->css[] = 'datepicker.css';
		$this->js[] = 'datepicker.js';
		
		// set up dates
		if (($ys = (int)$_GET['ystart']) && ($ds = (int)$_GET['dstart']) && ($ms = (int)$_GET['mstart']))
		{	$this->startdate = $this->datefn->SQLDate(mktime(0,0,0,$ms, $ds, $ys));
		} else
		{	$this->startdate = $this->datefn->SQLDate(strtotime('-1 month'));
		}
		
		if (($ys = (int)$_GET['yend']) && ($ds = (int)$_GET['dend']) && ($ms = (int)$_GET['mend']))
		{	$this->enddate = $this->datefn->SQLDate(mktime(0,0,0,$ms, $ds, $ys));
		} else
		{	$this->enddate = $this->datefn->SQLDate();
		}
		
		$this->breadcrumbs->AddCrumb('productpurchases.php?id=' . $this->product->id, 'Purchases');
	} // end of fn ProductsLoggedInConstruct
	
	function ProductsBody()
	{	parent::ProductsBody();
		$purchases = $this->product->GetPurchases($this->startdate, $this->enddate);
		echo $this->FilterForm(), $this->OptionsList(count($purchases)), $this->PurchasesList($purchases);
	} // end of fn ProductsBody
	
	function OptionsList($purchasescount = 0)
	{	ob_start();
	
		// build list of filter options
		$filter_applied = array();
		$link_paras = array('id=' . $this->product->id);
		
		if ($this->startdate)
		{	$link_paras[] = 'dstart=' . date('j', $start_stamp = strtotime($this->startdate));
			$link_paras[] = 'mstart=' . date('n', $start_stamp);
			$link_paras[] = 'ystart=' . date('Y', $start_stamp);
			$filter_applied[] = 'from <strong>' . date('d M Y', $start_stamp) . '</strong>';
		}
		if ($this->enddate)
		{	$link_paras[] = 'dend=' . date('j', $end_stamp = strtotime($this->enddate));
			$link_paras[] = 'mend=' . date('n', $end_stamp);
			$link_paras[] = 'yend=' . date('Y', $end_stamp);
			$filter_applied[] = 'to <strong>' . date('d M Y', $end_stamp) . '</strong>';
		}
		
		echo '<div class="cblFilterInfo"><div class="cblFilterInfoFilter">filter applied: ';
		if ($filter_applied)
		{	echo implode('; ', $filter_applied);
			$link_para_string = '?' . implode('&', $link_paras);
		} else
		{	echo 'none';
		}
		echo ' ... ', $purchasescount, ' orders found</div>';
		if ($purchasescount)
		{	echo '<ul><li><a href="productpurchases_csv.php', $link_para_string, '" target="_blank">download csv of these purchases</a></li></ul>';
		}
		echo '<div class="clear"></div></div>';
		return ob_get_clean();
	} // end of fn OptionsList
	
	public function PurchasesList($purchases = array())
	{	ob_start();
		if ($purchases)
		{	$students = array();
			echo '<table><tr><th>Date / time</th><th>Ordered by</th><th>Item qty</th><th>Item value</th><th>Item discount</th><th>Order value</th><th>Paid On</th><th>Delivered</th><th>Actions</th></tr>';
			foreach ($purchases as $order_row)
			{	$order = new AdminStoreOrder($order_row);
				if (!$students[$order->details['sid']])
				{	$students[$order->details['sid']] = new Student($order->details['sid']);
				}
				echo '<tr><td>', date('d-M-y @H:i', strtotime($order->details['orderdate'])), '</td><td><a href="member.php?id=', $students[$order->details['sid']]->id, '">', $this->InputSafeString($students[$order->details['sid']]->GetName()), '</a></td><td class="num">', $order->details['itemqty'], '</td><td class="num">', number_format($order->details['itemprice'], 2), '</td><td class="num">', number_format($order->details['itemdiscount'], 2), '</td><td class="num">', number_format($order->GetRealTotal(), 2), '</td><td>', date('d/m/y @H:i', strtotime($order->details['paiddate'])), '</td><td>', $order->details['delivered'] ? 'Yes' : '', '</td><td><a href="order.php?id=', $order->id, '">view full order</a></td></tr>';
			}
			echo '</table>';
			//$this->VarDump($purchases);
		} else
		{	echo '<p>no purchases found</p>';
		}
		return ob_get_clean();
	} // end of fn PurchasesList
	
	private function FilterForm()
	{	ob_start();
		class_exists('Form');
		$startfield = new FormLineDate('', 'start', $this->startdate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		$endfield = new FormLineDate('', 'end', $this->enddate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		echo '<form id="filter-form" class="akFilterForm" action="', $_SERVER['SCRIPT_NAME'], '" method="get"><input type="hidden" name="id" value="', $this->product->id, '" /><span>From</span>';
		$startfield->OutputField();
		echo '<span>to</span>';
		$endfield->OutputField();
		echo '<input type="submit" class="submit" value="Get" /><div class="clear"></div></form>';
		return ob_get_clean();
	} // end of fn FilterForm
	
} // end of defn ProductPurchasesPage

$page = new ProductPurchasesPage();
$page->Page();
?>
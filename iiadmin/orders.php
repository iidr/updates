<?php
include_once('sitedef.php');

class OrderListingPage extends AccountsMenuPage
{	var $showunpaid = 0;
	var $startdate = '';
	var $enddate = '';

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AccountsLoggedInConstruct()
	{	parent::AccountsLoggedInConstruct();
		$this->css[] = 'adminmembers.css';
		$this->css[] = 'adminorders.css';
		$this->css[] = 'datepicker.css';
		$this->js[] = 'datepicker.js';
		$this->showunpaid = $_GET['showunpaid'];
		
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
		
		$this->breadcrumbs->AddCrumb('orders.php', 'Orders');
	} // end of fn AccountsLoggedInConstruct
	
	function AccountsBody()
	{	$orders = $this->GetOrders();
		echo $this->FilterForm(), $this->OptionsList(count($orders)), $this->OrdersList($orders);
	} // end of fn AccountsBody
	
	function OptionsList($ordercount = 0)
	{	ob_start();
	
		// build list of filter options
		$filter_applied = array();
		$link_paras = array();
		
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

		if ($this->showunpaid)
		{	$filter_applied[] = '<strong>Include unpaid orders</strong>';
			$link_paras[] = 'showunpaid=1';
		}
		
		echo '<div class="cblFilterInfo"><div class="cblFilterInfoFilter">filter applied: ';
		if ($filter_applied)
		{	echo implode('; ', $filter_applied);
			$link_para_string = '?' . implode('&', $link_paras);
		} else
		{	echo 'none';
		}
		echo ' ... ', $ordercount, ' orders found</div>';
		if ($ordercount)
		{	echo '<ul><li><a href="orders_sum_csv.php', $link_para_string, '" target="_blank">download summary csv of these orders</a></li><li><a href="orders_det_csv.php', $link_para_string, '" target="_blank">download details csv of these orders</a></li></ul>';
		}
		echo '<div class="clear"></div></div>';
		return ob_get_clean();
	} // end of fn OptionsList
	
	private function OrdersList($orders = array())
	{	ob_start();
		if ($orders)
		{	$students = array();
			echo '<table><tr><th>Order No.</th><th>Date / time</th><th>Ordered by</th><th>Items</th><th>Order value</th><th>Paid</th><th>Delivered</th><th>Notes</th><th>Actions</th></tr>';
			foreach ($orders as $order_row)
			{	$order = new AdminStoreOrder($order_row);
				if (!$students[$order->details['sid']])
				{	$students[$order->details['sid']] = new Student($order->details['sid']);
				}
				echo '<tr><td>', $order->details['id'], '</td><td>', date('d-M-y @H:i', strtotime($order->details['orderdate'])), '</td><td><a href="member.php?id=', $students[$order->details['sid']]->id, '">', $this->InputSafeString($students[$order->details['sid']]->GetName()), '</a></td><td class="orderItemList">', $this->ItemsList($order), '</td><td class="num">', number_format($order->GetRealTotal(), 2), '</td><td>', (int)$order->details['paiddate'] ? date('d/m/y @H:i', strtotime($order->details['paiddate'])) : '', '</td><td>', $order->details['delivered'] ? 'Yes' : '', '</td><td>', $this->InputSafeString($order->details['pmtnotes']), '</td><td><a href="order.php?id=', $order->id, '">edit</a>';
				if ($order->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="order.php?id=', $order->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		} else
		{	echo '<p>No orders found</p>';
		}
		return ob_get_clean();
	} // end of fn OrdersList
	
	private function ItemsList(AdminStoreOrder $order)
	{	ob_start();
		echo '<table>';
		$discounts = array();
		$total_discounts = 0;
		foreach ($order->GetItems() as $item)
		{	echo '<tr><td class="oilType">', $this->InputSafeString($item['ptype']), '</td><td class="oilDesc">', (int)$item['qty'], ' &times; ', $this->InputSafeString($item['title']), '</td><td class="oilPrice num">', number_format($item['totalpricetax'], 2), '</td></tr>';
			foreach ($item['discounts'] as $item_discount)
			{	if (!$discounts[$item_discount['discid']])
				{	$discounts[$item_discount['discid']] = new DiscountCode($item_discount['discid']);
				}
				echo '<tr class="itemsTableSubRow"><td class="oilType">discount</td><td class="oilDesc">', $this->InputSafeString($discounts[$item_discount['discid']]->details['discdesc']), '</td><td class="oilPrice num">&minus; ', number_format($item_discount['discamount'], 2), '</td></tr>';
				$total_discounts += $item_discount['discamount'];
			}
		}

		foreach ($order->GetAllReferrerRewards() as $reward)
		{	echo '<tr><td class="oilType">reward</td><td class="oilDesc">refer-a-friend</td><td class="oilPrice num">&minus; ', number_format($reward['amount'], 2), '</td></tr>';
		}
		foreach ($order->GetAllAffRewards() as $reward)
		{	echo '<tr><td class="oilType">reward</td><td class="oilDesc">affiliate scheme</td><td class="oilPrice num">&minus; ', number_format($reward['amount'], 2), '</td></tr>';
		}
		foreach ($order->GetBundles() as $bundle)
		{	echo '<tr><td class="oilType">bundle</td><td class="oilDesc">', (int)$bundle['qty'], ' &times; ', $this->InputSafeString($bundle['bname']), '</td><td class="oilPrice num">&minus; ', number_format($bundle['totaldiscount'], 2), '</td></tr>';
		}
		if ($total_discounts)
		{	echo '<tr><td class="oilType"></td><td class="oilDesc">Total discounts</td><td class="oilPrice num">&minus; ', number_format($total_discounts, 2), '</td></tr>';
		}
		if ($order->details['delivery_price'] > 0)
		{	echo '<tr><td class="oilType">delivery</td><td class="oilDesc">', ($order->details['delivery_id'] && ($deloption = new DeliveryOption($order->details['delivery_id'])) && $deloption->id) ? $this->InputSafeString($deloption->details['title']) : '','</td><td class="oilPrice num">', number_format($order->details['delivery_price'], 2), '</td></tr>';
		}
		if ($order->details['txfee'] > 0)
		{	echo '<tr><td class="oilType"></td><td class="oilDesc">Transaction fee</td><td class="oilPrice num">', number_format($order->details['txfee'], 2), '</td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ItemsList
	
	private function GetOrders()
	{	$orders = array();
		$where = array();
		
		if ($this->startdate)
		{	$where[] = 'orderdate>="' . $this->startdate . ' 00:00:00"';
		}
		if ($this->enddate)
		{	$where[] = 'orderdate<="' . $this->enddate . ' 23:59:59"';
		}
		if (!$this->showunpaid)
		{	$where[] = 'NOT paiddate="0000-00-00 00:00:00"';
		}
		
		$sql = 'SELECT * FROM storeorders';
		if ($wstr = implode(' AND ', $where))
		{	$sql .= ' WHERE ' . $wstr;
		}
		$sql .= ' ORDER BY orderdate DESC';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$orders[$row['id']] = $row;
			}
		}
		
		return $orders;
	} // end of fn GetOrders
	
	private function FilterForm()
	{	ob_start();
		class_exists('Form');
		$startfield = new FormLineDate('', 'start', $this->startdate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		$endfield = new FormLineDate('', 'end', $this->enddate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		echo '<form id="filter-form" class="akFilterForm" action="', $_SERVER['SCRIPT_NAME'], '" method="get"><span>From</span>';
		$startfield->OutputField();
		echo '<span>to</span>';
		$endfield->OutputField();
		echo '<span>show unpaid</span><input type="checkbox" name="showunpaid" value="1"', $this->showunpaid ? ' checked="checked"' : '', ' /><input type="submit" class="submit" value="Get" /><div class="clear"></div></form>';
		return ob_get_clean();
	} // end of fn FilterForm
	
} // end of defn OrderListingPage

$page = new OrderListingPage();
$page->Page();
?>
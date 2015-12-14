<?php
include_once('sitedef.php');

class MemberDetailsPage extends MemberPage
{	var $member;

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AKMembersLoggedInConstruct()
	{	parent::AKMembersLoggedInConstruct();
		$this->member_option = 'orders';
		
		$this->js[] = 'adminbookings.js';
		
		$this->breadcrumbs->AddCrumb('memberorders.php?id=' . $this->member->id, 'orders');

	} // end of fn AKMembersLoggedInConstruct
	
	public function MemberViewBody()
	{	parent::MemberViewBody();
		$orders = $this->GetOrders();
		$this->OrdersList($orders);
	} // end of fn MemberViewBody	
	
	
	private function OrdersList($orders = array())
	{	if ($orders)
		{	echo '<table><tr><th>Order No.</th><th>Date / time</th><th>Items</th><th>Order value</th><th>Paid</th><th>Delivered</th><th>Notes</th><th>Actions</th></tr>';
			foreach ($orders as $order_row)
			{	$order = new AdminStoreOrder($order_row);
				echo '<tr><td>', $order->details['id'], '</td><td>', date('d-M-y @H:i', strtotime($order->details['orderdate'])), '</td><td class="orderItemList">', $this->ItemsList($order), '</td><td class="num">', number_format($order->GetRealTotal(), 2), '</td><td>', (int)$order->details['paiddate'] ? date('d/m/y @H:i', strtotime($order->details['paiddate'])) : '', '</td><td>', $order->details['delivered'] ? 'Yes' : '', '</td><td>', $this->InputSafeString($order->details['pmtnotes']), '</td><td><a href="order.php?id=', $order->id, '">edit</a>';
				if ($order->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="order.php?id=', $order->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		} else
		{	echo '<p>No orders found</p>';
		}
	} // end of fn OrdersList
	
	private function ItemsList(AdminStoreOrder $order)
	{	ob_start();
		echo '<table>';
		$discounts = array();
		$total_discounts = 0;
		
		if(!$user instanceof Student){
			$orderer = new Student($order->details['sid']);
		}
		
		foreach ($order->GetItems() as $item)
		{	
			echo '<tr><td class="oilType">', $this->InputSafeString($item['ptype']), '</td><td class="oilDesc">', (int)$item['qty'], ' &times; ', $this->InputSafeString(preg_replace("/\(\([^)]+\)\)/","",$item['title']));
			switch ($item['ptype'])
			{	case 'store':
					$product = new StoreProduct($item['pid']);
					echo '&nbsp;<span class="prodItemCode">Code: ', $product->ProductID(), '</span>', $product->ListCustomDownloads($orderer), $product->ListCustomPurchasedMM($orderer);
					break;
				case 'course':
					$ticket = new CourseTicket($item['pid']);
					$course = new Course($ticket->details['cid']);
					echo '&nbsp;<span class="prodItemCode">Code: ', $course->ProductID(), '</span>';
					break;
			}
		 	echo '</td><td class="oilPrice num">', number_format($item['totalpricetax'], 2), '</td></tr>';
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
		
		$where = array('sid='. (int)$this->member->id);
		
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
	
} // end of defn MemberDetailsPage

$page = new MemberDetailsPage();
$page->Page();
?>
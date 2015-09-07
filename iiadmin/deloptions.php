<?php
include_once('sitedef.php');

class DelOptionsListPage extends AdminDelOptionsPage
{	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function DelOptionsLoggedInConstruct()
	{	parent::DelOptionsLoggedInConstruct();
	} // end of fn DelOptionsLoggedInConstruct
	
	function DelOptionsBody()
	{	$this->OptionsList();
	} // end of fn DelOptionsBody
	
	function OptionsList(){	
		echo '<table><tr class="newlink"><th colspan="8"><a href="productedit.php">Create new product</a></th></tr><tr><th>&nbsp;</th><th>Title</th><th>Author</th><th>Category</th><th>Video</th><th>Live?</th><th>Price</th><th>Actions</th></tr>';
		foreach ($this->GetOptions() as $option_row)
		{	$product = new AdminStoreProduct($product_row);
			if (!is_array($cats))
			{	$cats = $product->GetAllCategories();
			}
			echo '<tr class="stripe', $i++ % 2, '"><td>';
			if ($img = $product->HasImage('tiny'))
			{	echo '<img src="', $img, '" />';
			}
			echo '</td><td>', $this->InputSafeString($product->details['title']), '</td><td>', $this->InputSafeString($product->details['author']), '</td><td>', $cats[$product->details['category']], '</td><td>', $product->VideoDescription(), '</td><td>', $product->details['live'] ? 'Yes' : 'No', '</td><td class="num">', number_format($product->details['price'], 2), '</td><td><a href="productedit.php?id=', $product->id, '">edit</a>';
			if ($histlink = $this->DisplayHistoryLink('storeproducts', $product->id))
			{	echo '&nbsp;|&nbsp;', $histlink;
			}
			if ($product->CanDelete())
			{	echo '&nbsp;|&nbsp;<a href="productedit.php?id=', $product->id, '&delete=1">delete</a>';
			}
			echo '</td></tr>';
		}
		echo "</table>";
	} // end of fn OptionsList
	
	function GetOptions()
	{	$products = array();
		$sql = "SELECT deloptions.* FROM deloptions LEFT JOIN delregions on deloptions.region=delregions.drid ORDER BY delregions.drname, deloptions.id";
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$products[] = $row;
			}
		}
		
		return $products;
	} // end of fn GetOptions
	
} // end of defn DelOptionsListPage

$page = new DelOptionsListPage();
$page->Page();
?>
<?php
class Store extends Base
{	private $categories = array();
	
	public function __construct()
	{	parent::__construct();
	} // fn __construct
	
	public function GetCategory($id)
	{
		if (!isset($this->categories[$id]))
		{	$this->categories[$id] = new StoreCategory($id);	
		}
		
		return $this->categories[$id];
	} // fn GetCategory
	
	public function GetCategories()
	{
		$this->categories = array();
		
		$tables = array('storecategories', 'storeproducts');
		$where = array('storecategories.cid=storeproducts.category');
		
		$sql = 'SELECT storecategories.* FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY storecategories.ctitle ASC';
		
		if($result = $this->db->Query($sql))
		{	while($row = $this->db->FetchArray($result))
			{	$this->categories[$row['cid']] = new StoreCategory($row);
			}
		}
		
		return $this->categories;
	} // fn GetCategories
	
	public function GetHomepageProducts($limit = 3)
	{
		$this->products = array();
		
		if ($result = $this->db->Query('SELECT * FROM storeproducts WHERE live=1 ORDER BY frontpage DESC, id DESC LIMIT 0,' . (int)$limit))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->products[$row['id']] = new StoreProduct($row);
			}
		}
		
		return $this->products;
	} // fn GetHomepageProducts
	
	public function GetBestSellingProducts($limit = 0)
	{	$products = array();
		
		$where = array('live=1');
		
		$sql = 'SELECT storeproducts.*, COUNT(storeorderitems.id) AS item_count FROM storeproducts LEFT JOIN storeorderitems ON storeproducts.id=storeorderitems.pid AND storeorderitems.ptype="store" WHERE ' . implode(' AND ', $where) . ' GROUP BY storeproducts.id ORDER BY item_count DESC';
		if ($limit = (int)$limit)
		{	$sql .= ' LIMIT ' . $limit;
		}
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$products[$row['id']] = $row;
			}
		}
		
		return $products;
	} // fn GetBestSellingProducts
	
	public function GetSpecialOfferProducts($limit = 0)
	{	$products = array();
		
		$where = array('live=1', 'spoffer=1');
		
		$sql = 'SELECT storeproducts.* FROM storeproducts WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC';
		if ($limit = (int)$limit)
		{	$sql .= ' LIMIT ' . $limit;
		}
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$products[$row['id']] = $row;
			}
		}
		
		return $products;
	} // fn GetSpecialOfferProducts
	
	public function GetLatestProducts($limit = 0)
	{	$products = array();
		
		$sql = 'SELECT * FROM storeproducts WHERE live=1 ORDER BY id DESC';
		if ($limit = (int)$limit)
		{	$sql .= ' LIMIT ' . $limit;
		}
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$products[$row['id']] = $row;
			}
		}
		
		return $products;
	} // fn GetLatestProducts
	
	public function GetCategoryProducts($catid = 0, $limit = 0, $sortby = '')
	{	$products = array();
		
		switch ($sortby)
		{	case 'price_asc':
				$orderby = 'price ASC';
				break;
			case 'price_desc':
				$orderby = 'price DESC';
				break;
			case 'az_asc':
			default:
				$orderby = 'title ASC';
				break;
		}
		
		$sql = 'SELECT * FROM storeproducts WHERE live=1 AND category=' . (int)$catid . ' ORDER BY ' . $orderby;
		if ($limit = (int)$limit)
		{	$sql .= ' LIMIT ' . $limit;
		}
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$products[$row['id']] = $row;
			}
		}
		
		return $products;
	} // fn GetCategoryProducts
	
	public function StoreMenu($cid = '')
	{	ob_start();
		echo '<div class="sidebar-menu"><ul><li', $cid ? '' : ' class="current-subpage"', '><a href="', $this->link->GetLink('store.php'), '">Latest</a>';
		if (!$cid && ($latest = $this->GetLatestProducts(3)))
		{	echo '<ul class="sideProdMenu">';
			foreach ($latest as $latest_row)
			{	echo '<li><a href="', $this->link->GetStoreProductLink($product = new StoreProduct($latest_row)), '">', $this->InputSafeString($product->details['title']), '</a></li>';
			}
			
			echo '</ul>';
		}
		echo '</li>';
		
		foreach($this->GetCategories() as $cat)
		{
			$classes = array();
			if ($cat->details['cid'] == $cid)
			{	$classes[] = 'current-subpage';
			}
			if (strlen(htmlspecialchars_decode(stripslashes($cat->details['ctitle']))) > 22)
			{	$classes[] = 'menu_long';
			}
			echo '<li';
			if ($classes)
			{	echo ' class="', implode(' ', $classes), '"';
			}
			echo '><a href="', $this->link->GetStoreCategoryLink($cat), '">', $this->InputSafeString($cat->details['ctitle']), '</a></li>';
			//$this->VarDump($cat->details);
		}


		echo '</ul></div>';
		return ob_get_clean();
	} // fn StoreMenu
	
	public function StoreSideMenuBestSellers()
	{	ob_start();
		if ($products = $this->GetBestSellingProducts(2))
		{	echo '<div id="storeSideBestSellers"><h2>Best Sellers</h2><ul>';
			foreach ($products as $product_row)
			{	$product = new StoreProduct($product_row);
				echo '<li><a href="', $link = $this->link->GetStoreProductLink($product), '"><img class="storeShelfImg" src="', ($img = $product->HasImage('thumbnail')) ? $img : (SITE_URL . 'img/products/thumbnail.png'), '" alt="', $title = $this->InputSafeString($product->details['title']), ' - Image" /></a><h4><a href="', $link, '">', $title, '</a></h4><div class="storeShelfAuthor">';
					if ($product->details['author'])
					{	echo 'by ', $this->InputSafeString($product->details['author']);
					}
					echo '</div><div class="storeShelfPrice">&pound;', number_format($product->GetPriceWithTax(), 2), '</div>', $this->CartLinkButton($product), '<div class="clear"></div></li>';
			}
			//$this->VarDump($products);
			echo '</ul></div>';
		}
		return ob_get_clean();
	} // end of fn StoreSideMenuBestSellers
	
} // end of defn Store
?>
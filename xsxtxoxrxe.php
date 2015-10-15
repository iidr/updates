<?php 
require_once('init.php');

class StoreListPage extends StorePage
{	
	function __construct()
	{	parent::__construct();
		$this->js[] = 'storepage.js';
	} // end of fn __construct
	
	function MainBodyContent(){	
		echo '<div id="sidebar" class="col">', $this->store->StoreMenu($this->category->id), $this->SideMenuSpecialOffers(), $this->SideMenuBestSellers(), '</div><div class="col3-wrapper-with-sidebar"><div class="storeShelf"><div id="storeshelf_header"><h1>Welcome to the IIDR Store', $this->SortForm($this->category->id), '</h1>', $this->OutputBanner(), '</div>';
		
		if ($html = $this->page->HTMLMainContent()){	
			echo '<div class="the-content">', $html, '</div>';	
		}
		
		echo $this->StoreListing();
		echo '</div></div>';
	} // end of fn MainBodyContent
	
	function OutputBanner()
	{	ob_start();
		if ($this->page->details['banner'] && ($banner = new BannerSet($this->page->details['banner'])) && $banner->items)
		{	echo $banner->OutputMultiSlider('homebanner', 695, 260);
		}
		return ob_get_clean();	
	} // end of fn OutputBanner
	
	public function StoreListing()
	{	ob_start();
		if ($this->category->id)
		{	if ($products = $this->store->GetCategoryProducts($this->category->id))
			{	$shelf = new StoreShelf($products);
				echo '<div class="storeshelf_shelf"><h2>', $this->InputSafeString($this->category->details['ctitle']), '<div class="clear"></div></h2>', $shelf->DisplayShelf('cat', $this->GetParameter('pag_store_cat') * 3), '<div class="clear"></div></div>';
			}
		} else
		{	if ($products = $this->store->GetLatestProducts($pcount = $this->GetParameter('pag_store_latest') * 3))
			{	$shelf = new StoreShelf($products);
				echo '<div class="storeshelf_shelf"><h2>Latest products</h2>', $shelf->DisplayShelf('latest', $pcount), '<div class="clear"></div></div>';
			}
			if ($products = $this->store->GetBestSellingProducts($pcount = $this->GetParameter('pag_store_best') * 3))
			{	$shelf = new StoreShelf($products);
				echo '<div class="storeshelf_shelf"><h2>Best Sellers</h2>', $shelf->DisplayShelf('bestsellers', $pcount), '<div class="clear"></div></div>';
			}
		}
		return ob_get_clean();
	} // end of fn StoreListing
	
	public function SortForm($catid = 0)
	{	if ($catid)
		{	ob_start();
			$sort_options = array('az_asc'=>'Title (A to Z)', 'price_asc'=>'Price (Low to High)', 'price_desc'=>'Price (High to Low)');
			echo '<form class="store_sort" onsubmit=""><label>Sort</label><select id="sortOrder" onchange="StoreCatSort(', $catid, ');">';
			foreach ($sort_options as $option=>$text)
			{	echo '<option value="', $option, '">', $text, '</option>';
			}
			echo '</select></form>';
			return ob_get_clean();
		}
	} // end of fn SortForm
	
} // end of defn StoreListPage

$page = new StoreListPage();
$page->Page();
?>
<?php
class StoreShelf extends Base
{	protected $products = array();
	public $perline = 3;
	
	function __construct($products = array())
	{	parent::__construct();
		if (is_array($products))
		{	$this->products = $products;
		}
	} // fn __construct
	
	public function DisplayShelf($shelfid = '', $perpage = 3)
	{	ob_start();
		echo '<div id="shelfcontainer_', $shelfid, '">', $this->DisplayShelfContents($shelfid, $perpage), '</div>';
		return ob_get_clean();
	} // end of fn DisplayShelf
	
	public function DisplayShelfContents($shelfid = '', $perpage = 3)
	{	ob_start();
		if ($this->products)
		{
			if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $perpage;
			
			echo '<ul class="storeShelfList">';
			foreach($this->products as $product_row)
			{	
				if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
					
					if (is_a($product_row, 'StoreProduct'))
					{	$product = $product_row;
					} else
					{	if (is_array($product_row))
						{	$product = new StoreProduct($product_row);
						} else
						{	continue;
						}
					}
					if (++$prodcount > $this->perline)
					{	echo '</ul><div class="storeShelfDivider"></div><ul class="storeShelfList">';
						$prodcount = 0;
					}
					echo '<li><a href="', $link = $this->link->GetStoreProductLink($product), '"><img class="storeShelfImg" src="', ($img = $product->HasImage('thumbnail')) ? $img : $product->DefaultImageSRC('thumbnail'), '" alt="', $title = $this->InputSafeString($product->details['title']), ' - Image" /></a><h4><a href="', $link, '">', $title, '</a></h4>','&nbsp;<span class="prodItemCode">Code: ', $product->ProductID(), '</span><div class="storeShelfAuthor">', $product->GetAuthorString(), '</div><div class="storeShelfPrice">&pound;', number_format($product->GetPriceWithTax(), 2), '</div>', $this->CartLinkButton($product), '<div class="clear"></div></li>';
				}
			}
			echo '</ul><div class="clear"></div>';

			if (count($this->products) > $perpage)
			{	$pag = new AjaxPagination($_GET['page'], count($this->products), $perpage, 'shelfcontainer_' . $shelfid, 'ajax_store_' . $shelfid . '.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
		} else
		{	echo '<div class="storeShelfNone">nothing found</div>';
		}
		return ob_get_clean();
	} // end of fn DisplayShelfContents
	
	public function DisplaySliderShelf()
	{	ob_start();
		if ($this->products)
		{
			echo '<div class="shelf_slider_wrapper">', count($this->products) > 1 ? '<div class="products_next"></div><div class="products_prev"></div>' : '', '<ul class="storeShelfList">';
			foreach($this->products as $product_row)
			{	
				if (is_a($product_row, 'StoreProduct'))
				{	$product = $product_row;
				} else
				{	if (is_array($product_row))
					{	$product = new StoreProduct($product_row);
					} else
					{	continue;
					}
				}
				echo '<li><a href="', $link = $this->link->GetStoreProductLink($product), '"><img class="storeShelfImg" src="', ($img = $product->HasImage('thumbnail')) ? $img : $product->DefaultImageSRC('thumbnail'), '" alt="', $title = $this->InputSafeString($product->details['title']), ' - Image" /></a><h4><a href="', $link, '">', $title, '</a></h4>','&nbsp;<span class="prodItemCode">Code: ', $product->ProductID(), '</span><div class="storeShelfAuthor">', $product->GetAuthorString(), '</div><div class="storeShelfPrice">&pound;', number_format($product->GetPriceWithTax(), 2), '</div>', $this->CartLinkButton($product), '<div class="clear"></div></li>';
			}
			echo '</ul></div>';
			if (count($this->products) > 1)
			{	
				echo '<script>$(function() { $(".shelf_slider_wrapper ul").cycle({ fx: "scrollHorz", timeout: 0, next: ".products_next", prev: ".products_prev" }); }); </script>';
			}
		}
		return ob_get_clean();
	} // end of fn DisplaySliderShelf
	
	public function CartLinkButton(StoreProduct $product)
	{	ob_start();
		if ($product->InStock())
		{
			echo '<form title="add to cart" class="storeShelfButton" method="post" action="', $this->link->GetLink('cart.php'), '" onclick="this.submit();"><input type="hidden" value="1" name="qty" /><input type="hidden" name="add" value="', (int)$product->id , '" /><input type="hidden" name="type" value="store" /><input type="hidden" name="addtocart" value="Add to Basket" /></p></form>';
		}
		return ob_get_clean();
	} // end of fn CartLinkButton
	
	public function StoreSideMenuBestSellers()
	{	ob_start();
		if ($this->products)
		{	echo '<ul class="storeShelfList">';
			foreach ($this->products as $product_row)
			{	$product = new StoreProduct($product_row);
				echo '<li><a href="', $link = $this->link->GetStoreProductLink($product), '"><img class="storeShelfImg" src="', ($img = $product->HasImage('thumbnail')) ? $img : $product->DefaultImageSRC('thumbnail'), '" alt="', $title = $this->InputSafeString($product->details['title']), ' - Image" /></a><h4><a href="', $link, '">', $title, '</a></h4><div class="storeShelfAuthor">', $product->GetAuthorString(), '</div><div class="storeShelfPrice">&pound;', number_format($product->GetPriceWithTax(), 2), '</div>', $this->CartLinkButton($product), '<div class="clear"></div></li>';
			}
			//$this->VarDump($products);
			echo '</ul>';
		}
		return ob_get_clean();
	} // end of fn StoreSideMenuBestSellers
	
} // end of class StoreShelf
?>
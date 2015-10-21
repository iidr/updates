<?php 
require_once('init.php');

class StoreProductPage extends StorePage
{	//public $store;
	//public $category;
	public $product;
	
	function __construct($id = 0)
	{	parent::__construct('store');
		$this->css[] = 'elastislide.css';
		$this->js[] = 'modernizr.custom.17475.js';
		$this->js[] = 'jquery.easing.1.3.js';
		$this->js[] = 'jquery.elastislide_modified.js';
		$this->css[] = 'store.css';	
		$this->css[] = 'product.css';	
		$this->css[] = 'bundles.css';	
		$this->css[] = 'studentreviews.css';
		$this->js[] = 'productreview.js';
		$this->facebookLike = true;
	
	} // end of fn __construct
	
	protected function AssignProductCatgeory()
	{	$this->product = $this->store->GetProduct($_GET['id'], 'store');
		if($this->product->id)
		{	$this->category = $this->store->GetCategory($this->product->details['category']);
		} else
		{	$this->Redirect('store.php');
		}
	} // end of fn AssignProductCatgeory

	protected function AssignBreadcrumbs()
	{	parent::AssignBreadcrumbs();
		if($this->product->id)
		{	$this->AddBreadcrumb($this->product->details['title'], $this->link->GetStoreProductLink($this->product));
		}
	} // end of fn AssignBreadcrumbs
	
	function MainBodyContent()
	{	
		echo '<div id="sidebar" class="col">', $this->store->StoreMenu($this->category->details['cid']), $this->SideMenuSpecialOffers(), $this->SideMenuBestSellers(), '</div><div class="col3-wrapper-with-sidebar">', $this->ProductListing(), '</div>';
	} // end of fn MainBodyContent
	
	function ProductListing()
	{	ob_start();
		echo '<div class="product-inner-page"><div id="pipContainer"><div id="pipHeaderImage"><div id="mainProdImageContainer">', $this->product->MainImageDisplay(), '</div><div id="gal_photo_modal_popup" class="jqmWindow"><a href="#" class="submit" onclick="CloseGalleryPhoto(); return false;">Close</a><div id="galPhotoModalInner"></div></div><script type="text/javascript"> $().ready(function(){$("body").append($(".jqmWindow")); $("#gal_photo_modal_popup").jqm({trigger:false});});</script>', $this->product->ImageChooser(), '</div><div id="pipHeaderDetails"><h1>', $this->InputSafeString($this->product->details['title']), '</h1><div id="pipHeaderAuthor">', $this->product->GetAuthorString(), '</div><div id="pipHeaderCode">Code: ', $this->product->ProductID(), '</div><div id="pipHeaderPrice"><p>', $this->formatPrice($this->product->GetPriceWithTax()), '</p>';
		if ($this->product->InStock())
		{	$bundles = new ProductBundles($this->product, 'store');
			echo '<form class="courseBookButton" method="post" action="', $this->link->GetLink('cart.php'), '"><input type="text" value="1" name="qty" /> <input type="hidden" name="add" value="', (int)$this->product->id , '" /><input type="hidden" name="type" value="store" /><input type="submit" name="addtocart" value="Add to Basket" class="addtobasket" /></form><div class="clear"></div>';
		}
		echo '</div>';
		if ($this->product->details['socialbar'])
		{	echo '<div id="pipHeaderSocial">', $this->GetSocialLinks(3, true), '</div>';
		}
		echo '</div><div class="clear"></div>';
		
		if ($mmlist = $this->product->GetMultiMedia())
		{	foreach ($mmlist as $mm_row)
			{	$mm = new Multimedia($mm_row);
				echo '<div class="mmdOutput">', $mm->Output(655), '</div>';
				//echo '<h3>',$this->InputSafeString($mm->details['mmname']), '</h3>';
				break;
			}
		}
		echo '<div class="clear"></div>';
		
		if ($tabs = $this->product->GetDisplayTabs($this->user))
		{	echo '<div id="pipTabs"><div id="pipTabsSelector"><ul>';
			foreach ($tabs as $tabid=>$tab)
			{	echo '<li><a href="#piptab_', $tabid, '">', $tab['label'], '</a></li>';
			}
			echo '</ul><div class="clear"></div></div><div id="pipTabsContent">';
			foreach ($tabs as $tabid=>$tab)
			{	echo '<div class="pipTabsContent" id="piptab_', $tabid, '">', $tab['content'], '</div>';
			}
			echo'</div></div><script type="text/javascript">my_id_tabs = $("#pipTabsSelector").idTabs();</script>';
		}
		echo '<div class="clear"></div></div>';
		if ($this->product->InStock())
		{	$bundles = new ProductBundles($this->product, 'store');
			echo $bundles->BundlesDisplay();
		}
		
		if ($products = $this->product->AlsoBoughtProducts())
		{	$shelf = new StoreShelf($products);
			echo '<div class="storeShelf storeshelf_shelf"><h2>Customers also bought</h2>', $shelf->DisplayShelf('alsobought', 3), '<div class="clear"></div></div>';
		}
		
		$reviewlist = $this->product->ReviewList(0, $this->user->id);
		$reviewform = $this->user->ReviewForm($this->product->id, 'store');
		if ($reviewlist['text'] || $reviewform)
		{	echo '<div id="prodReviewContainer"><h3><span>Product Reviews</span>';
			if ($reviewlist['rating'] && $reviewlist['count'])
			{	echo '<span id="prcRatingAv">', $reviewlist['count'], ' review', $reviewlist['count'] == 1 ? '' : 's', ' </span>', $this->RatingDisplay($reviewlist['rating']);
			}
			if ($reviewform)
			{	echo '<span id="prcYourReview">', $reviewform, '</span>';
			}
			echo '<div class="clear"></div></h3>';
			if ($reviewlist)
			{	echo '<div id="prodReviewListContainer">', $reviewlist['text'], '</div>';
			}
			echo '<div class="clear"></div></div>';
		}
		
		echo '</div>';
		
/*		echo 'Price: ', $this->product->OutputStarRating();
		if ($status = $this->product->GetStatus())
		{	echo '<p class="status-', $status->details['name'], '">', $status->details['disptitle'], '</p>';
		}
		
		*/
		
		return ob_get_clean();
	} // end of fn ProductListing
	
} // end of defn StoreProductPage

$page = new StoreProductPage();
$page->Page();
?>
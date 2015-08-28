<?php 
require_once('init.php');

class SubProductsPage extends BasePage
{	
	function __construct()
	{	parent::__construct('subscriptions');		
		$this->css[] = 'course.css';
		$this->css[] = 'page.css';	
		$this->css[] = 'subslist.css';
		$this->js[] = 'subslist.js';
		$this->AddBreadcrumb("Subscription offers", $this->link->GetLink('subscriptions.php'));
	
	} // end of fn __construct

	function MainBodyContent()
	{	echo $this->OutputBanner(), '<div id="sidebar" class="col courselist_sidebar">', $this->GetSubmenu(), $this->GetSidebarCourses(), $this->GetSidebarQuote(), '</div><div class="col3-wrapper-with-sidebar courselist_main">';
		$title = $this->InputSafeString($this->page->details['pagetitle']);
		if (!$this->page->details['hideheader'])
		{	echo '<h1>', $title, $this->page->details['socialbar'] ? $this->GetSocialLinks(3) : '', '</h1>';
		}
		if ($this->page->HasImage('default'))
		{	echo '<div class="image"><img src="', $this->page->GetImageSRC('default'), '" alt="', $title, '" title="', $title, '" /></div>';
		}
		if ($html = $this->page->HTMLMainContent())
		{	echo '<div class="the-content">', $html, '</div>';	
		}
		echo '<div id="courses_container">', $this->ProductListing(), '</div><div class="clear"></div></div><div class="clear"></div>';
	
	} // end of fn MemberBody
	
	function GetSubmenu()
	{
		ob_start();
		
		//if (!$this->page->subpages && $this->page->details['parentid'])
		if ($this->page->details['parentid'])
		{	$page = new PageContent($this->page->details['parentid']);
		} else
		{	$page = $this->page;
		}
		
		echo '<div class="sidebar-menu">';//<h2>', $this->InputSafeString($page->details['pagetitle']), '</h2>';
		
		if(sizeof($page->subpages))
		{
			echo '<ul>';
			
			foreach($page->subpages as $p)
			{	$classes = array();
				if ($this->page->details['pagename'] == $p->details['pagename'])
				{	$classes[] = 'current-subpage';
				}
				if ($p->details['inparent'])
				{	$classes[] = 'inPageLink';
				}
				if (strlen(htmlspecialchars_decode(stripslashes($p->details['pagetitle']))) > 22)
				{	$classes[] = 'menu_long';
				}
				
				echo '<li';
				if ($classes)
				{	echo ' class="' . implode(' ', $classes) . '"';
				}
				echo '><a onclick="SubPageHighlight(\'', $p->details['pagename'], '\'); return true;" href="', $p->Link(), '">', $this->InputSafeString($p->details['pagetitle']), '</a>', $this->SubSubMenu($p), '</li>';
				
			}
			
			echo '</ul>';	
		}
		
		echo '</div>';
		
		return ob_get_clean();
		
	} // end of fn GetSubmenu
	
	public function SubSubMenu(PageContent $page)
	{	if (($this->page->details['pagename'] == $page->details['pagename']) && $page->subpages)
		{	ob_start();
			echo '<ul>';
			foreach ($page->subpages as $subpage)
			{	$classes = array();
				if ($this->page->details['pagename'] == $subpage->details['pagename'])
				{	$classes[] = 'current-subpage';
				}
				if ($subpage->details['inparent'])
				{	$classes[] = 'inPageLink';
				}
				
				echo '<li';
				if ($classes)
				{	echo ' class="' . implode(' ', $classes) . '"';
				}
				echo '><a onclick="SubPageHighlight(\'', $subpage->details['pagename'], '\'); return true;" href="', $subpage->Link(), '">', $this->InputSafeString($subpage->details['pagetitle']), '</a>', $this->SubSubMenu($subpage), '</li>';
			}
			echo '</ul>';
			return ob_get_clean();
		}
	} // end of fn SubSubMenu
	
	public function ProductListing()
	{	ob_start();
		if ($products = $this->GetSubProducts())
		{	echo '<ul>';
			foreach ($products as $product_row)
			{	
				$product = new SubscriptionProduct($product_row);
				$title = $this->InputSafeString($product->details['title']);
				$link = $product->GetLink();
				echo '<li><div class="courselist_image"><img src="', ($src = $product->HasImage('thumbnail')) ? $src : $product->DefaultImageSRC('thumbnail'), '" alt="', $title, '" title="', $title, '" /></div><div class="courselist_details_container"><div class="courselist_details"><h2>', $title, '</h2><div class="subslist_showmore" id="subslist_showmore_', $product->id, '" style="display: block;"><a onclick="SubDetailsToggle(', $product->id, ');">... more details</a></div><div class="subslist_showless" id="subslist_showless_', $product->id, '" style="display: none;">', stripslashes($product->details['description']), '<a class="subslist_showless_link" onclick="SubDetailsToggle(', $product->id, ');">... show less</a></div>';
				
				echo '</div><div class="courselist_date courselist_date_with_slogan">&nbsp;</div><div class="courselist_slogan cl_slogan_pnk_mar">', $product->BuyButton(), '</div></div><div class="clear"></div></li>';
			}
			echo '</ul><div class="clear"></div>', 
				'<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#subdetails_popup").jqm();});</script>',
				'<div id="subdetails_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="subdetails_popup_inner"></div></div>';
		}
		return ob_get_clean();
	} // end of fn ProductListing

	public function GetSubProducts()
	{	$products = array();
		$sql = 'SELECT * FROM subproducts WHERE live=1 ORDER BY listorder, id';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$products[$row['id']] = $row;
			}
		}
		return $products;
	} // end of fn GetSubProducts
	
} // end of defn SubProductsPage

$page = new SubProductsPage();
$page->Page();
?>
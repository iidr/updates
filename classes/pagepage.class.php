<?php
class PagePage extends BasePage
{	
	function __construct($pageName = '')
	{	parent::__construct($pageName);
		$this->css[] = 'page.css';
	
		if(!$this->page->Found())
		{	//$this->VarDump($_GET);
			header('location: ' . SITE_SUB . '/404.php');
			exit;
		}
		
		$this->bodyOnLoadJS[] = 'SubPagePreHighlight()';
		$this->AddBreadcrumbs();
		
	} // end of fn __construct
	
	public function AddBreadcrumbs()
	{
		// Handle reverse Breadcrumbs
		$crumbs = array();
		$page = $this->page;
		
		while($page->details['parentid'])
		{	
			$page = new PageContent($page->details['parentid']);
			$crumbs[] = array('title' => $page->details['pagetitle'], 'link' => $page->Link());
		}
		
		foreach($crumbs as $crumb)
		{
			$this->AddBreadcrumb($crumb['title'], $crumb['link']);	
		}
		
		$this->AddBreadcrumb($this->page->details['pagetitle']);
	} // end of fn AddBreadcrumbs
	
	function MainBodyContent()
	{	$title = $this->InputSafeString($this->page->details['pagetitle']);
		echo $this->OutputBanner(), '<div id="sidebar" class="col">', $this->GetSubmenu(), $this->GetSidebarCourses(), $this->GetSidebarQuote(), '</div><div class="col3-wrapper-with-sidebar">';
		if (!$this->page->details['hideheader'])
		{	echo '<h1>', $title, $this->page->details['socialbar'] ? $this->GetSocialLinks(5) : '', '</h1>';
		}
		if ($this->page->HasImage('default'))
		{	echo '<div class="image"><img width="100%" src="', $this->page->GetImageSRC('default'), '" alt="', $title, '" title="', $title, '" /></div>';
		}
		if ($html = $this->page->HTMLMainContent())
		{	echo '<div class="the-content">', $html, '</div>';	
		}
		
		if ($subpages = $this->page->FullSubPages())
		{	echo '<ul class="subpage-grid">';
			foreach($subpages as $key => $p)
			{	if ($p->details['blocklink'])
				{	
					echo '<li', !($pcount++ % 2) ? ' style="clear:both;"' : '', '><h3><a href="', $p->Link(), '">', $title = $this->InputSafeString($p->details['pagetitle']), '</a></h3><div class="image"><img src="', $p->HasImage('thumbnail') ? $p->GetImageSRC('thumbnail') : $p->DefaultImageSRC('thumbnail'), '" alt="', $title, '" title="', $title, '" /></div><div class="content"><div class="page-intro">', $p->details['pageintro'], '</div><div class="clear"></div><a class="readmore_link" href="', $p->Link(), '">Read More</a></div></li>';
				}
			}
			echo '</ul>';
		}
		
		echo '</div><div class="clear"></div>'; 
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
	
	function FBSidebar()
	{	ob_start();
		$links = $this->SocialLinks();
		echo '<ul id="psbFacebook"><li><a id="psbFacebook" href="', $links['facebook'], '" target="_blank">facebook</a></li><li><a id="psbTwitter" href="', $links['twitter'], '" target="_blank">twitter</a></li></ul>';
		return ob_get_clean();
	} // end of fn FBSidebar

} // end of defn PagePage
?>
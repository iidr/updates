<?php
class BasePage extends BaseFunctions
{	var $title = '';
	var $pageName = '';
	var $page_background_image = '';
	var $user;
	var $page;
	var $bodyOnLoadJS = array();
	var $bodyOnUnLoadJS = array();
	
	function __construct($pageName = '', $title = '') // constructor
	{	parent::__construct();
		
		$this->css[] = 'global.css';
		$this->js[] = 'jquery.idTabs.min.js';
		$this->js[] = 'jquery.cycle.all.js';
		$this->js[] = 'global.js';
		$this->js[] = 'global_fe.js';
		$this->js[] = 'http://vjs.zencdn.net/c/video.js';
		$this->css[] = 'http://vjs.zencdn.net/c/video-js.css';
		$this->css[] = 'jqModal.css';
		$this->js[] = 'jqModal.js';
		$this->css[] = 'storeshelf.css';
	
		$this->pageName = $pageName;
		$this->page = new PageContent($this->pageName);
		$this->title = $this->InputSafeString($this->GetParameter('comptitle'));
		if ($title)
		{	$this->title .= ' - ' . $title;
		}
		
		$this->user = new Student($_SESSION['stuserid']);
		if ($this->user->id)
		{	if ($_GET['logout'])
			{	$this->user->LogOut();
			}
		} else
		{	if ($_POST['li_user'])
			{	if ($loginid = $this->user->LogIn($_POST['li_user'], $_POST['li_pass']))
				{	//echo 'logged in';
					$_SESSION['stuserid'] = $loginid;
				} else
				{	$this->failmessage = 'log in failed';
				}
			}
	
			if (isset($_POST['username']) && isset($_POST['pword']))
			{	$saved = $this->user->SaveDetails($_POST);
				if ($this->user->id)
				{	$_SESSION['stuserid'] = $this->user->id;
				}
				$this->failmessage = $saved['fail'];
				$this->successmessage = $saved['success'];
			}
			
			$this->RecordReferrerTrackCode();
		}
		
		if ($this->user->id && $_POST['reg_complete'])
		{	$saved = $this->user->SaveDetails($_POST);
			$this->failmessage = $saved['fail'];
			$this->successmessage = $saved['success'];
		}
		
	} // end of fn __construct, constructor
	
	protected function RecordReferrerTrackCode()
	{	if (!$this->user->id && ($trackcode = $_GET['refertrack']) && !$_COOKIE['trackcode'])
		{	// check valid trackcode
			$sql = 'SELECT rfid FROM referafriend WHERE trackcode="' . $this->SQLSafe($trackcode) . '" AND regsid=0';
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	// then record trackcode in cookie
					setcookie('refertrack', $trackcode, strtotime('+30 days'));
				}
			}
		}
		if (!$this->user->id && ($affcode = $_GET['affcode']) && !$_COOKIE['affcodetrack'])
		{	// check valid trackcode
			$sql = 'SELECT asid FROM affiliateshare WHERE affcode="' . $this->SQLSafe($affcode) . '"';
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	// then record trackcode in cookie
					setcookie('affcodetrack', $affcode, strtotime('+30 days'), '/');
				}
			}
		}
	} // end of fn RecordReferrerTrackCode
	
	function Header()
	{	
		$cart = new StoreCart;
		$cart_products = $cart->GetProducts();
		
		echo '<div id="header"><div class="wrapper"><div class="logo"><a href="', SITE_URL, '"><img src="', SITE_SUB, '/img/template/logo2.png" alt="', $this->InputSafeString($this->GetParameter('comptitle')), '" /></a></div><div id="header_search"><form id="topsearch" action="', SITE_SUB, '/search.php" method="get"><input type="text" name="term" value="', $this->InputSafeString($_GET['term']), '" placeholder="Search" /><input type="submit" name="search" value="" /></form></div><div id="header_menu">';
		if ($this->user->id)
		{
			echo '<ul><li><a href="', $this->link->GetLink('account.php'), '">My Account</a></li><li><a href="', $this->link->GetLink('index.php?logout=1'), '">Logout</a>', $this->user->LogInForceRegPopUp($this->failmessage), '</li></ul>';
		} else
		{	echo '<ul><li id="login-account"><a href="#">LOGIN or REGISTER</a>';
			echo '</li></ul><div id="loginbox"><a href="#" title="close" class="close-box">Close</a><form class="form" id="htop_login" action="', $_SERVER['REQUEST_URI'], '" method="post"><label>Email:</label><input type="text" name="li_user" onfocus="clearField(this, \'', $emailpropompt, '\')" value="', $emailpropompt, '" required="required" /><div class="clear"></div><label>Password:</label><input type="password" name="li_pass" onfocus="clearField(this, \'password\')" value="password" required="required" /><div class="clear"></div><input type="submit" class="submit" value="Login"/> <a href="', $this->link->GetLink('register.php'), '" class="register-link">Register Now</a><br /><a href="', $this->link->GetLink('forgot.php'), '" class="forgot-link">Forgot your password</a></form></div>';
		}
		$hmMenu = array();
		$page = new PageContent('baqs');
		if ($page->id)
		{	$hmMenu[] = array('text'=>'BAQS', 'link'=>$page->Link());
		}
		$page = new PageContent('ideas');
		if ($page->id)
		{	$hmMenu[] = array('text'=>'Ideas', 'link'=>$page->Link());
		}
		$page = new PageContent('contact-us');
		if ($page->id)
		{	$hmMenu[] = array('text'=>'Contact Us', 'link'=>$page->Link());
		}
		$page = new PageContent('faqs');
		if ($page->id)
		{	$hmMenu[] = array('text'=>'FAQ', 'link'=>$page->Link());
		}
		if ($hmMenu)
		{	echo '<ul id="hmMenu">';
			foreach ($hmMenu as $hmMenuItem)
			{	echo '<li><a href="', $hmMenuItem['link'], '">', $this->InputSafeString($hmMenuItem['text']), '</a></li>';
			}
			echo '</ul>';
		}
		$socialMenu = array();
		if ($link = $this->GetParameter('link_twitter'))
		{	$socialMenu[] = array('text'=>'', 'title'=>'Twitter', 'link'=>$link, 'id'=>'hmTwitter');
		}
		if ($link = $this->GetParameter('link_fb'))
		{	$socialMenu[] = array('text'=>'', 'title'=>'Facebook', 'link'=>$link, 'id'=>'hmFacebook');
		}
		
		if ($socialMenu)
		{	echo '<ul>';
			foreach ($socialMenu as $socialMenuItem)
			{	echo '<li id="', $socialMenuItem['id'], '"><a href="', $socialMenuItem['link'], '" title="', $socialMenuItem['title'], '">', $this->InputSafeString($socialMenuItem['text']), '</a></li>';
			}
			echo '</ul>';
		}
		echo'</div><div id="header_cart"><div class="topcarthover"><a href="#" title="close" class="close-box">Close</a><div class="topcarthover-title clearfix">Shopping Basket</div><span class="hr clearfix"></span>';
			
		if ($cart_products)
		{	
			foreach ($cart_products as $rowid => $p)
			{
				echo '<div class="topcarthover-item clearfix"><div class="topcarthover-image"><a href="', $p['product']->GetLink(), '">';
				if ($img = $p['product']->HasImage('thumbnail'))
				{	echo '<img src="', $img, '" alt="', $p["product"]->GetLink(), '" />';
				} else
				{	echo '<img src="',  SITE_URL, 'img/products/default.png" alt="', $p["product"]->GetLink(), '" />';
				}
				echo '</a></div><div class="topcarthover-desc"><a href="', $p['product']->GetLink(), '">'.$this->InputSafeString($p['product']->GetName()), '</a><br /> <strong>', $this->formatPrice($p['price_with_tax']), '</strong></div></div>';
			}
			
			echo '<span class="hr clearfix"></span>';

		}
		echo '<p class="topcarthover-total">Total: <span>', $cart_price = $this->formatPrice($cart->GetTotalWithAllDiscounts()), '</span> Items: <span>', $cart_count = $cart->Count(), '</span></p><a href="', $cartlink = $this->link->GetLink('cart.php'), '" title="Go to Checkout" class="button-link">Checkout Now</a><div class="clear"></div></div><a href="', $cartlink, '">Cart: ', $cart_count, ' item(s) - Total: ', $cart_price, '</a></div><div class="clear"></div></div></div>';
		
		$pages = new PageContents();
		$this->headerMenuButtons = $pages->GetHeaderMenu();
		$this->HeaderMenu();
		$this->Breadcrumbs();
		
		echo '<div class="page-content">';

	} // end of fn Header
	
	function Breadcrumbs()
	{
		if ($breadcrumbs = $this->GetBreadcrumbs())
		{
			echo '<div id="breadcrumbs"><div class="wrapper"><ul>';
			$last = count($breadcrumbs) - 1;
			foreach ($breadcrumbs as $bcount=>$b)
			{
				echo '<li', $b['suppress'] ? ' class="bc_suppress"' : '', '>';
				if ((($bcount != $last) || $b['force_link']) && isset($b['link']) && !$b['suppress'])
				{	echo '<a href="', $b['link'], '" ', isset($b['class']) ? ('class="' . $b['class'] . '"') : '', '>', $this->InputSafeString($b['title']), '</a>';
				} else
				{	echo $this->InputSafeString($b['title']);	
				}
				echo '</li>';
			}
			
			echo '</ul>', $this->BreadcrumbsRightContent(), '</div><div class="clear"></div></div>';
		}
			
	} // end of fn Breadcrumbs
	
	protected function BreadcrumbsRightContent()
	{	
	} // end of fn BreadcrumbsRightContent
	
	function HeaderMenu()
	{	if ($this->headerMenuButtons)
		{	echo '<div id="menu_container"><div class="wrapper"><ul class="menu">';
			foreach ($this->headerMenuButtons as $name=>$button)
			{	$this->HeaderMenuButton($button, $name);
			}
			echo '</ul><div class="clear"></div></div></div>';
		}
	} // end of fn HeaderMenu
	
	function HeaderMenuButton($button = array(), $name = '')
	{	echo '<li';
		if ($button['class'])
		{	
			$selected = '';
			if ($name == $this->pageName || $name == $this->page->parentpage['pagename'])
			{	
				$selected = 'selected';
			}
			echo ' class="', $button['class'], ' ', $selected ,' "';
		} else
		{	if ($name == $this->pageName || $name == $this->page->parentpage['pagename'])
			{	echo ' class="selected"';
			}
		}

		echo $button['id'] ? (' id="' . $button['id'] . '"') : '', '><a href="', $button['link'], '">', $button['text'], '</a>';
		if ($button['submenu'])
		{	//$this->VarDump($button['submenu']);
			echo '<ul class="submenu">';
			foreach ($button['submenu'] as $submenu){
				echo '<li><a href="'.$submenu['link'].'" title="'.$submenu['text'].'">'.$submenu['text'].'</a></li>';
			}
			echo '</ul>';
		}
		echo '</li>';
	} // end of fn HeaderMenuButton
	
	function FailMessage()
	{	if ($this->failmessage)
		{	echo '<div class="failmessage">', $this->failmessage, '</div><br />';
		}
	} // end of fn FailMessage
	
	function SuccessMessage()
	{	if ($this->successmessage)
		{	echo '<div class="successmessage">', $this->successmessage, '</div><br />';
		}
	} // end of fn SuccessMessage

	function MainBody()
	{	$this->Messages();
		echo $this->BackgroundImage(), '<div class="wrapper">';
		$this->MainBodyContent();
		echo '</div>';
	} // end of fn MainBody
	
	public function BackgroundImage()
	{	if ($this->page_background_image)
		{	echo '<div id="pagecontent_background"',
				//' style="background: url(\'', $this->page_background_image, '\') top center;"',
				'><img src="', $this->page_background_image, '" alt="" /></div>';
		}
	} // end of fn BackgroundImage
	
	function MainBodyContent()
	{	
		echo $this->OutputBanner(), $this->CoursesModule(), $this->MultimediaModule(), '<div class="clear"></div>', '<div id="col4-home" class="col4-wrapper">', $this->NewsModule(), $this->OpinionsModule(), $this->AskTheExpertModule(), '</div>', '<div class="clear"></div>';
		
	} // end of fn MemberBody
	
	function OutputBanner($container_id = 'homebanner', $width = 960, $height = 290)
	{	ob_start();
		if ($this->page->details['banner'] && ($banner = new BannerSet($this->page->details['banner'])) && $banner->items)
		{	echo '<div class="wrapper">', $banner->OutputMultiSlider($container_id, $width, $height), '</div>';
		}
		return ob_get_clean();	
	} // end of fn OutputBanner
	
	public function GetFrontPageVideo()
	{	$sql = 'SELECT * FROM multimedia WHERE live=1 AND frontpage=1';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return new Multimedia($row);
			}
		}
		// if not then get first video
		$sql = 'SELECT * FROM multimedia WHERE live=1 AND (mmtype="youtube" OR mmtype="vimeo") ORDER BY mmorder, mmid';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return new Multimedia($row);
			}
		}
		return false;
	} // end of fn GetFrontPageVideo
	
	function MultimediaModule()
	{	ob_start();
		echo '<div id="home-multimedia" class="col2-wrapper scheme-multimedia" style="height:393px;"><h2><a href="', $this->link->GetLink('multimedia.php'), '">Feature Video</a></h2><div class="homepage-video">', ($video = $this->GetFrontPageVideo()) ? $video->Output(475, 255) : '', '</div><div id="hpvideo_text"><div id="hpvideo_text_inner">', $this->InputSafeString($video->details['mmname']), '</div></div></div>';
		return ob_get_clean();	
	} // end of fn MultimediaModule
	
	function CoursesModule()
	{	ob_start();	
		echo '<div id="home-courses" class="col2-wrapper scheme-courses" style="height:393px;"><h2><a href="', $this->link->GetLink('courses.php'), '">Upcoming courses</a></h2>';
        
		if ($courses = Course::GetUpcomingCourses(2))
		{	foreach ($courses as $key=>$course)
			{	
				echo '<div class="box"><p class="image"><a href="', $link = $this->link->GetCourseLink($course), '"><img src="', ($img = $course->HasImage('thumbnail')) ? $img : $course->GetDefaultImage('thumbnail'), '" alt="', $title = $this->InputSafeString($course->content['ctitle']), '" title="', $title, '" width="245px" /></a></p><div class="boxcontent ', $key == 1 ? 'none' : '', '"><h3 class="hcoursetitle"><a href="', $link, '">', $title, '</a></h3><p class="coursedate">', $course->GetDateVenue(), '</p></div><div class="clear"></div></div>';	
			}
		}
		
		echo '</div>';
		
		return ob_get_clean();
	} // end of fn CoursesModule
	
	function AskTheExpertModule()
	{
		ob_start();
		
		
		if ($topics = $this->GetLatestAskImamTopics())
		{	
			echo '<div id="homepage_asktheexpert" class="col scheme-asktheexpert"><h2><a href="asktheexpert.php">Ask the Expert</a></h2><div class="box"><ul>';
				
			foreach ($topics as $topic_row)
			{	$topic = new AskImamTopic($topic_row);
				$link = $topic->Link();
				$title = $this->InputSafeString($topic->details['title']);
				echo '<li>';
				if ($imgsrc = $topic->HasImage('thumbnail'))
				{	echo '<div class="hpae_image"><a href="', $link, '"><img src="', $imgsrc, '" alt="', $title, '" title="', $title, '" /></div>';
				}
				echo '<div class="hpae_inner', $imgsrc ? ' hapae_inner_withimage' : '', '"><a class="hp_asklink" href="', $link, '">', $title, '</a>';
				if ($inst_text = $topic->DisplayInstructorText(false))
				{	echo '<div class="clear"></div><div class="hp_askpeople">', $inst_text, '</div>';
				}
				if ($qcount = count($topic->questions))
				{	echo '<a class="count_questions" href="', $link, '">', $qcount, '</a>';
				}
				echo '</div><div class="clear"></div></li>';
			}
		
			echo "</ul></div></div>";
		}
		
		return ob_get_clean();	
	} // end of fn AskTheExpertModule
	
	public function GetLatestAskImamTopics($limit = 3)
	{	$topics = array();
		$sql = 'SELECT * FROM askimamtopics WHERE live=1 AND startdate<="' . $this->datefn->SQLDate() . '" ORDER BY startdate DESC';
		if ($limit = (int)$limit)
		{	$sql .= ' LIMIT ' . $limit;
		}
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$topics[$row['askid']] = $row;
			}
		}
		return $topics;
	
	} // end of fn GetLatestAskImamTopics
	
	function NewsModule()
	{	ob_start();
	
		$post = new NewsPost(0, 'news');
		if ($newest = $post->GetNewest())
		{	$link = $this->link->GetPostLink($newest);
			$title = $this->InputSafeString($newest->details['ptitle']);
			echo '<div id="home-news" class="col scheme-news"><h2><a href="', SITE_SUB, '/news/">News</a></h2><div class="box">', $this->HomepagePostDisplay($newest), '</div></div>';
		}
		
		return ob_get_clean();	
	} // end of fn NewsModule
	
	function OpinionsModule()
	{	ob_start();
		
		$post = new OpinionPost(0);
		if ($newest = $post->GetNewest())
		{	echo '<div id="home-opinions" class="col scheme-news"><h2><a href="', SITE_SUB, '/opinions/">Opinions</a></h2><div class="box">', $this->HomepagePostDisplay($newest), '</div></div>';
		}
		
		return ob_get_clean();	
	} // end of fn OpinionsModule
	
	public function HomepagePostDisplay($post)
	{	ob_start();
		$title = $this->InputSafeString($post->details['ptitle']);
		$link = $this->link->GetPostLink($post);
		$txt_length = 320 - (ceil(strlen(stripslashes($post->details['ptitle'])) / 18) * 55);
		echo '<p class="image">';
		echo '<a href="', $link, '"><img src="', ($img = $post->HasImage('thumbnail')) ? $img : $post->DefaultImageSRC('thumbnail'), '" width="230px;" title="', $title, '" alt="', $title, '" /></a>';
		echo '</p><div class="boxcontent news_boxcontent"><h3><a href="', $link, '">', $title, '</a></h3>';
		if ($txt_length)
		{	echo '<p>', $post->SampleText($txt_length), '</p>';
		}
		echo '</div><p class="news_box_readmore"><a href="', $link, '" class="readmore">Read more</a></p>';
		return ob_get_clean();	
	} // end of fn HomepagePostDisplay
	
	function StoreModule($prod_count = 1)
	{	ob_start();
		$store = new Store();
		if ($products = $store->GetHomepageProducts($prod_count))
		{	$shelf = new StoreShelf($products);
			echo '<div id="footer_store" class="col3-wrapper scheme-store" style="width: ', 233 * $prod_count, 'px"><h2><a href="', $this->link->GetLink('store.php'), '">Store</a></h2>', $shelf->DisplayShelf('dummy', $prod_count), '</div>';
		}
		
		return ob_get_clean();
	} // end of fn StoreModule
	
	public function ReferAFriendModule()
	{	ob_start();
		$page = new PageContent('refer-a-friend');
		echo '<div id="footer_refer"><h2><a href="', $link = $page->Link(), '">Refer a Friend</a></h2><p>Tell your friends about us and earn big rewards!</p><div id="f_raf_sub"><div id="f_raf_sub_top">&pound;', $this->GetParameter('refer_amount'), ' OFF</div><div id="f_raf_sub_bottom">on IIDR courses</div></div><a id="f_raf_link" href="', $link, '">Join the program</a></div>';
		return ob_get_clean();
	} // end of fn ReferAFriendModule
	
	public function SubscriptionsFooterModule()
	{	ob_start();
		$page = new PageContent('subscriptions');
		echo '<div id="footer_sublink"><h2><a href="', $link = $page->Link(), '">Subscriptions</a></h2><p>You can now make huge savings!</p><div id="f_sub_sub"><div id="f_sub_sub_top">up to 40% OFF</div><div id="f_sub_sub_bottom">on all our courses</div></div><a id="f_sub_link" href="', $link, '">Subscribe now</a></div>';
		return ob_get_clean();
	} // end of fn ReferAFriendModule
	
	function MailingListModule()
	{
		ob_start();

		echo '<div class="scheme-mailing"><h2>Follow &amp; Share</h2>';

		$maillist = new MailList();
		
		$links = array();
		$links[] = '<span class="st_facebook_custom" displayText="Facebook"></span>';
		$links[] = '<span class="st_google_custom" displayText="Google"></span>';
		$links[] = '<span class="st_twitter_custom" displayText="Tweet"></span>';
		if ($link = $this->GetParameter('link_youtube'))
		{	$links[] = '<a class="st_youtube_custom" title="YouTube" href="' . $link . '"></a>';
		}
		$links[] = '<span class="st_sharethis_custom" displayText="ShareThis"></span>';
		
		echo $maillist->InputForm(), '<div class="clear"></div><div class="maillistsocialshare">', 
		//	$this->GetSocialLinks(3),
		'<script type="text/javascript">var switchTo5x=false;</script>
		<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
		<script type="text/javascript">stLight.options({publisher: "ur-961a8a43-dd22-fcd3-9641-1f9cd9c3754"});</script>';
		foreach ($links as $link)
		{	if ($linkcount++ >= 4)
			{	$linkcount = 0;
				//echo '<div style="width: 200px; height: 40px; clear: both;"></div>';
			}
			echo $link;
		}
		echo '</div><div class="clear"></div></div>';

		return ob_get_clean();
	} // end of fn MailingListModule

	function Page() // display actual page
	{	$this->HTMLHeader();
		$this->DisplayTitle();
		$this->BodyDefn();
		$this->Header();
		$this->MainBody();
		$this->Footer();
		if (!SITE_TEST)
		{	$this->GoogleAnalytics();
		}
		echo "</body>\n</html>\n";
	} // end of fn Page
	
	function Messages()
	{	
		if ($this->successmessage)
		{	echo '<div class="successmessage">', $this->InputSafeString($this->successmessage), '</div>';
		}
		if ($this->failmessage)
		{	echo '<div class="failmessage">', $this->InputSafeString($this->failmessage), '</div>';
		}
		if ($this->warningmessage)
		{	echo '<div class="warningmessage">', $this->InputSafeString($this->warningmessage), '</div>';
		}
	} // end of fn Messages
	
	function GoogleAnalytics()
	{	include(CITDOC_ROOT . '/google/analytics.html');
	} // end of fn GoogleAnalytics
	
	public function TopFooter()
	{	ob_start();
		echo '<div id="top_footer"><div class="wrapper"><div col4-wrapper">', $this->StoreModule(), $this->SubscriptionsFooterModule(), $this->ReferAFriendModule(), $this->MailingListModule(), '<div class="clear"></div></div></div><div class="clear"></div></div>';
		return ob_get_clean();
	} // end of fn TopFooter
	
	function Footer()
	{	
		$pages = new PageContents();
		echo '</div><div class="clear"></div>', $this->TopFooter(),'<div id="footer"><div class="wrapper">';
		$this->FooterMenu($pages->GetFooterMenu());
		echo '<div class="footer-right"><div id="footer_top_link"><a href="#header">Top</a></div>',
			//'<div class="footer-websquare">Design by MakeMeBelieve&nbsp;|&nbsp;<a href="http://www.websquare.co.uk" title="" target="_blank">Built by Websquare</a></div>',
			'<div class="clear"></div></div><div class="clear"></div></div></div><script>$(document).ready(function(){
$("#footer_top_link>a").click(function(){
goToByScroll($(this).attr("href"));
return false;
});
});</script>';
	} // end of fn Footer

	function FooterMenu($buttons = array())
	{	if ($buttons)
		{	echo '<ul class="footer-menu">';
			foreach ($buttons as $button)
			{	echo '<li><a href="', $button['link'], '">', $button['text'], '</a></li>';
			}
			echo '</ul>';
		}
	} // end of fn FooterMenu
	
 	function DisplayTitle()
	{	echo "<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n<title>", SITE_TEST ? "{dev} " : "", $this->title, "</title><link href='http://fonts.googleapis.com/css?family=Lato:400,700,300' rel='stylesheet' type='text/css'>";
		$this->MetaInclude();
		$this->CSSInclude();
		$this->JSInclude();
		echo "</head>\n";
	} // end of fn DisplayTitle
	
	public function MetaInclude()
	{	echo '<meta property="og:image" content="', CIT_FULLLINK, 'img/template/logo2.png" />';
	} // end of fn MetaInclude
	
	function Redirect($url = '')
	{	header('location: ' . SITE_SUB . '/' . $url);
		exit;
	} // end of fn Redirect
	
	function RedirectBack($url = '')
	{	if ($_POST['back_page'])
		{	$url = $_POST['back_page'];
			if (strstr($url, '?'))
			{	$url .= '&no_bl=1';
			} else
			{	$url .= '?no_bl=1';
			}
			header('location: ' . $url);
			exit;
		} else
		{	$this->Redirect($url);
		}
	} // end of fn RedirectBack
	
	function RedirectBackLink($url = '')
	{	ob_start();
		if ($_POST['back_page'])
		{	$backlink = $_POST['back_page'];
		} else
		{	if (!$_GET['no_bl'])
			{	if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']))
				{	$backlink = $_SERVER['HTTP_REFERER'];
				} else
				{	$backlink = $url;
				}
			}
		}
		if ($backlink)
		{	if (strstr($backlink, '?'))
			{	$backlink .= '&no_bl=1';
			} else
			{	$backlink .= '?no_bl=1';
			}
			echo '<p class="back_link"><a href="', $backlink, '">&laquo;&nbsp;back</a></p>';
		}
		return ob_get_clean();
	} // end of fn RedirectBackLink
	
	function GoogleMap($lat = 0, $long = 0, $location_title = "course location")
	{	ob_start();
		echo "<script type='text/javascript'>
			window.onload=function(){
			gminitialize(", $lat, ", ", $long, ", \"", $this->InputSafeString($location_title), "\");
			};
			</script>\n<div id='gmap' style='width: 500px; height: 420px; position:relative;'>&nbsp;</div>\n";
		return ob_get_clean();
	} // end of fn GoogleMap
	
	function GetSidebarCourses()
	{ 	ob_start();
		if($courses = Course::GetUpcomingCourses(5))
		{
			echo '<div class="sidebar-course-wrapper"><h3><a href="', $this->link->GetLink('courses.php'), '">Upcoming Courses</a></h3>';
			foreach($courses as $key => $course)
			{
				echo '<div class="sidebar-course"><a class="sb_c_link" href="', $this->link->GetCourseLink($course), '" title="', $this->InputSafeString($course->content['ctitle']), '">', $this->InputSafeString($course->content['ctitle']), '</a><p>', $course->GetDateVenue(', ', 'D jS M Y'), '</p><div class="clear"></div></div>';
			}
			echo '<div class="clear"></div></div>';
		}
		
		return ob_get_clean();
	} // end of fn GetSidebarCourses
	
} // end of defn BasePage
?>
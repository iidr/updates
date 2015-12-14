<?php
class BaseFunctions extends Base
{	var $css = array();
	var $cssroot = "";
	var $js = array();
	var $bodyOnLoadJS = array();
	var $failmessage = "";
	var $successmessage = "";
	var $warningmessage = "";
	var $meta = array("DESCRIPTION"=>"",  
					"KEYWORDS"=>"", 
					"RATING"=>"General", 
					"ROBOTS"=>"index,follow", 
					"REVISIT-AFTER"=>"7 days", 
					"DISTRIBUTION"=>"Global");
	var $headerMenuButtons = array();
	var $footerMenuButtons = array();
	var $breadcrumbs = array();
	
	function __construct() // constructor
	{	parent::__construct();
		$this->cssroot = CSS_ROOT;
	} // end of fn __construct, constructor
	
	function AddBreadcrumb($title = '', $link = '', $class = '', $suppress = false, $force_link = false)
	{
		if ($title)
		{	$b = array('title'=>$title);
			if ($link) $b['link'] = $link;
			if ($class) $b['class'] = $class;
			if ($suppress) $b['suppress'] = true;
			if ($force_link) $b['force_link'] = true;
			
			$this->breadcrumbs[] = $b;
			$this->breadcrumbs[] = array('title' => '&gt;', 'suppress'=>(bool)$suppress);
		}
	} // end of fn AddBreadcrumb
	
	function GetBreadcrumbs()
	{
		array_pop($this->breadcrumbs);
		return $this->breadcrumbs;
	} // end of fn GetBreadcrumbs
	
	function Header()
	{	
	} // end of fn Header
	
	function FailMessage() // 
	{	
	} // end of fn FailMessage
	
	function SuccessMessage() // 
	{	
	} // end of fn SuccessMessage

	function MainBody() //
	{	
	} // end of fn MainBody

	function BodyDefn() // display actual page
	{	$classes = array();
		if ($name = $this->page->details['pagename'])
		{
			$classes[] = $this->InputSafeString('page-'.$name);
			
			if($pid = $this->page->details['parentid'])
			{
				$parent = new PageContent($pid);
				$classes[] = $this->InputSafeString('page-'.$parent->details['pagename']);
			}
		}
		echo "<body";
		if ($classes)
		{	echo ' class="' . implode(' ', $classes) . '"';
		}
		if ($this->bodyOnLoadJS)
		{	echo ' onload="';
			foreach ($this->bodyOnLoadJS as $js)
			{	echo $js, ';';
			}
			echo '"';
		}
		echo ">\n";
		if ($this->facebookLike)
		{	echo '
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=283225601725648";
  fjs.parentNode.insertBefore(js, fjs);
}(document, \'script\', \'facebook-jssdk\'));</script>'
;
		}
	} // end of fn BodyDefn

	function Page() // display actual page
	{	$this->HTMLHeader();
		$this->DisplayTitle();
		$this->BodyDefn();
		$this->Header();
		$this->MainBody();
		$this->Footer();
		echo "</body>\n</html>\n";
	} // end of fn Page
	
	function Footer()
	{	
	} // end of fn Footer
	
	function SetTitle($title)
	{	$this->title = $title;
	} // end of fn SetTitle

	function HTMLHeader()
	{	
		echo "<!doctype html>\n<html lang='en'>\n";
	} // end of fn HTMLHeader
	
	function DisplayTitle()
	{	echo "<head>\n<meta charset='utf-8'>\n<title>", $this->title, "</title>\n</head>\n";
	} // end of fn DisplayTitle
	
	function CSSInclude()
	{	if ($force_refresh = $this->FlagFileSet('forcecss'))
		{	$timestamp = substr(time(), -10, 6);
		}
		foreach ($this->css as $css_file)
		{	if (substr($css_file, 0, 7) == "http://")
			{	if (!$_SERVER["HTTPS"])
				{	echo "<link rel='stylesheet' href='", $css_file, "' type='text/css' media='all' />\n";
				}
			} else
			{	echo "<link rel='stylesheet' href='", CSS_ROOT, $css_file;
				if ($force_refresh)
				{	echo strstr($css_file, "?") ? "&" : "?", $timestamp;
				}
				echo "' type='text/css' media='all' />\n";
			}
		}
		if (file_exists($_SERVER['DOCUMENT_ROOT'] . $lang_css = CSS_ROOT . 'lang_' . $this->lang . '.css'))
		{	echo "<link rel='stylesheet' href='", $lang_css, "?", $timestamp, "' />\n";
		}
		
		echo "<link href='http://fonts.googleapis.com/css?family=Belgrano' rel='stylesheet' type='text/css'>";
		echo "<!--[if lt IE 9]><script src='http://html5shiv.googlecode.com/svn/trunk/html5.js'></script><![endif]-->";
		
	} // end of fn CSSInclude
	
	function JSInclude()
	{	
		/*echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>\n";*/
		echo "<script src='/js/jquery.min.js'></script>";
		
		if ($force_refresh = $this->FlagFileSet('forcejs'))
		{	$timestamp = substr(time(), -10, 6);
		}
		foreach ($this->js as $script)
		{	if (substr($script, 0, 7) == "http://")
			{	if (!$_SERVER["HTTPS"])
				{	echo "<script src='", $script, "'></script>\n";
				}
			} else
			{	echo "\t<script type='text/javascript' src='", JS_ROOT, $script;
				if ($force_refresh)
				{	echo strstr($script, "?") ? "&" : "?", $timestamp;
				}
				echo "'></script>\n";
			}
		}
		
		$this->JSIncludeInitiate();
	} // end of fn JSInclude
	
	public function JSIncludeInitiate()
	{	echo "<script>jsSiteRoot='", SITE_SUB, "/';</script>\n";
	} // end of fn JSIncludeInitiate
	
	
	
	public function ShareButtons($title = '')
	{
		ob_start();
		
		echo "<div class='share-buttons'>\n<h4>". $this->InputSafeString($title) ."</h4>\n</div>";
		
		return ob_get_clean();	
	}
	
} // end of defn BaseFunctions
?>
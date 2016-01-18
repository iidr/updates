<?php
class StoreProduct extends Product implements Searchable
{
	public $rating;
	public $photos = array();
	
	public function __construct($id = null)
	{
		parent::__construct();
		
		if(!is_null($id))
		{	$this->Get($id);
		}
		
	} // end of fn __construct
	
	public function Get($id = 0)
	{
		$this->Reset();
		
		if (is_array($id))
		{	$this->id = $id['id'];
			$this->details = $id;
			$this->GetPhotos();
		} else
		{	if ($result = $this->db->Query('SELECT * FROM storeproducts WHERE id = '. (int)$id))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
				}
			}
		}
	} // end of fn Get
	
	public function GetPhotos()
	{	$this->photos = array();
		$sql = 'SELECT * FROM storeproducts_photos WHERE prodid=' . $this->id . ' ORDER BY listorder ASC, sppid DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->photos[$row['sppid']] = $row;
			}
		}
	} // end of fn GetPhotos
	
	public function GetBundles()
	{	return parent::GetBundles('store');
	} // end of fn GetBundles
	
	public function GetDownloads($liveonly = true)
	{	$downloads = array();
		$where = array('prodid=' . (int)$this->id);
		if ($liveonly)
		{	$where[] = 'live=1';
		}
		$sql = 'SELECT * FROM storeproductfiles WHERE ' . implode(' AND ', $where) . ' ORDER BY pfid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$downloads[$row['pfid']] = $row;
			}
		}
		return $downloads;
	} // end of fn GetDownloads
	
	public function ListDownloads($user = false)
	{	ob_start();
		if (($downloads = $this->GetDownloads()))
		{	if (($userid = (int)$user->id) && $user->StoreProductPurchased($this->id))
			{	echo '<div id="product_downloads"><h4>Downloads available for your purchase</h4><ul>';
				foreach ($downloads as $dl_row)
				{	$download = new StoreProductDownload($dl_row);
					echo '<li><a href="', $download->DownloadLink(), '" target="_blank">', $this->InputSafeString($download->details['filetitle']), '</a>';
					if ($download->details['filepass'])
					{	echo '<br />Password: ', $download->details['filepass'];
					}
					echo '</li>';
				}
				echo '</ul></div>';
			}
		}
		return ob_get_clean();
	} // end of fn ListDownloads
	
	public function ListPurchasedMM($user = false)
	{	ob_start();
		if (($mmpurchased = $this->GetMultiMediaPurchase()))
		{	if (($userid = (int)$user->id) && $user->StoreProductPurchased($this->id))
			{	echo '<div id="product_downloads"><h4>Multimedia available for your purchase</h4><ul>';
				foreach ($mmpurchased as $mm_row)
				{	$mm = new Multimedia($mm_row);
					echo '<li><a href="', $mm->Link(), '">', $this->InputSafeString($mm->details['mmname']), '</a></li>';
					//$download = new StoreProductDownload($dl_row);
					//echo '<li><a href="', $download->DownloadLink(), '" target="_blank">', $this->InputSafeString($download->details['filetitle']), '</a></li>';
					//$this->VarDump($mm->details);
				}
				echo '</ul></div>';
			}
		}
		return ob_get_clean();
	} // end of fn ListPurchasedMM
	
	public function ListCustomDownloads($user = false)
	{	ob_start();
		if (($downloads = $this->GetDownloads()))
		{	if (($userid = (int)$user->id) && $user->StoreProductPurchased($this->id))
			{	echo '<div style="clear:both;padding: 5px;"></div><div id="product_downloads" style="float:left !important;margin: 0px !important;padding:0px !important;"><h4 style="margin: 0px;padding:0px;">(Click to download)</h4><ul style="list-style:none;list-style-type:none;margin: 0px;padding:0px;">';
				foreach ($downloads as $dl_row)
				{	$download = new StoreProductDownload($dl_row);
					echo '<li style="float:left !important;margin: 0px !important;padding:0px !important;"><a href="', $download->DownloadLink(), '" target="_blank">', $this->InputSafeString($download->details['filetitle']), '</a>';
					if ($download->details['filepass'])
					{	echo '<br />Password: ', $download->details['filepass'];
					}
					echo '</li>';
				}
				echo '</ul></div><div style="clear:both;"></div>';
			}
		}
		return ob_get_clean();
	} // end of fn ListDownloads
	
	public function ListCustomPurchasedMM($user = false)
	{	ob_start();
		if (($mmpurchased = $this->GetMultiMediaPurchase()))
		{	if (($userid = (int)$user->id) && $user->StoreProductPurchased($this->id))
			{	echo '<div style="clear:both;padding: 5px;"></div><div id="product_downloads"><h4 style="margin: 0px;padding:0px;">(Click to view Multimedia)</h4><ul style="list-style:none;list-style-type:none;margin: 0px;padding:0px;">';
				foreach ($mmpurchased as $mm_row)
				{	$mm = new Multimedia($mm_row);
					echo '<li><a href="', $mm->Link(), '">', $this->InputSafeString($mm->details['mmname']), '</a></li>';
				}
				echo '</ul></div><div style="clear:both;"></div>';
			}
		}
		return ob_get_clean();
	} // end of fn ListPurchasedMM
	
	public function GetRating()
	{	
		if (is_null($this->rating))
		{	if ($result = $this->db->Query('SELECT AVG(rating) as rating FROM productreviews WHERE pid = ' . (int)$this->id . ' AND ptype="store"'))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->rating = $row['rating'] * 100;	
				}
			}
		}
		
		return $this->rating;
	} // end of fn GetRating
	
	public function OutputStarRating()
	{	return '<span class="rating"><span style="width:' . (int)$this->GetRating() . '%">' . (int)$this->GetRating() . '%</span></span>';	
	} // end of fn OutputStarRating
	
	public function GetName()
	{	return $this->details['title'];
	} // end of fn GetName
	
	public function GetPrice()
	{	return $this->details['price'];
	} // end of fn GetPrice
	
	public function GetTax()
	{	if ($this->details['taxid'])
		{	if ($taxx = new Tax($this->details['taxid']))
			{	$tax = $taxx->Calculate($this->details['price']);
			}
		} else
		{	$tax = 0;
		}
		return $tax;
	} // end of fn GetTax
	
	public function GetPriceWithTax()
	{
		if ($this->details['taxid'])
		{	if ($tax = new Tax($this->details['taxid']))
			{	$price = $this->details['price'];
				$price += $tax->Calculate($this->details['price']);	
			}
		} else
		{	$price = $this->GetPrice();	
		}
		
		return $price;
	} // end of fn GetPriceWithTax
	
	public function InStock()
	{	return $this->details['live'] && (int)$this->details['qty'];
		//return $this->Is('in_stock') && (int)$this->details['qty'];
	} // end of fn InStock
	
	public function HasDownload()
	{
		$downloads = array();
		
		if($result = $this->db->Query('SELECT * FROM storedownloads WHERE pid = ' . (int)$this->id))
		{
			while($row = $this->db->FetchArray($result))
			{
				$downloads[] = new StoreDownload($row);
			}
		}
		
		return $downloads;
	} // end of fn HasDownload
	
	public function Is($name = '')
	{	return parent::Is($name, $this->details['status']);	
	} // end of fn Is
	
	public function HasQty($qty = 0)
	{	return $this->InStock() && (($this->details['qty']-$qty) >= 0);	
	} // end of fn HasQty
	
	public function HasShipping()
	{	return $this->details['shipping'];
	} // end of fn HasShipping
	
	public function HasTax()
	{	return $this->details['taxid'];	
	} // end of fn HasTax
	
	
	public function HasImage($size = 'default', $sppid = 0)
	{	if (!$sppid)
		{	$sppid = $this->GetDefaultImageID();
		}
		if ($sppid && $this->photos[$sppid])
		{	$image = new ProductPhoto($this->photos[$sppid]);
			return $image->HasImage($size);
		}
		//return file_exists($this->GetImageFile($size)) ? $this->GetImageSRC($size) : false;
	} // end of fn HasImage

	public function DefaultImageSRC($size = 'default')
	{	$photo = new ProductPhoto();
		return parent::DefaultImageSRC($photo->imagesizes[$size]);
	} // end of fn DefaultImageSRC
	
	public function ImageChooser()
	{	if (count($this->photos) > 1)
		{	ob_start();
			echo '<div class="prodPhotoChooser"><ul class="elastislide-list">';
			foreach ($this->photos as $photo_row)
			{	$photo = new ProductPhoto($photo_row);
				if ($src = $photo->HasImage('tiny'))
				{	$title = $this->InputSafeString($photo->details['phototitle'] ? $photo->details['phototitle'] : $this->details['title']);
					echo '<li><a onclick="ShowAsMainImage(', $this->id, ',', $photo->id, ');"><img width="46px" src="', $src, '" alt="', $title, '" title="', $title, '" /></a></li>';
				}
			}
			echo '</ul><div class="clear"></div></div><script>$(".elastislide-list").elastislide();</script>';
			return ob_get_clean();
		}
	} // end of fn ImageChooser
	
	public function GetDefaultImageID()
	{	foreach ($this->photos as $photoid=>$data)
		{	return $photoid;
			break;
		}
		return 0;
	} // end of fn GetDefaultImageID
	
	public function MainImageDisplay($sppid = 0)
	{	if (!$sppid)
		{	$sppid = $this->GetDefaultImageID();
		}
		if ($sppid && $this->photos[$sppid])
		{	$photo = new ProductPhoto($this->photos[$sppid]);
			if ($src = $photo->HasImage('default'))
			{	$title = $this->InputSafeString($photo->details['phototitle'] ? $photo->details['phototitle'] : $this->details['title']);
				ob_start();
				echo $full = $photo->HasImage('full') ? ('<a onclick="DisplayFullProdImage(' . $this->id . ',' . $photo->id . ');">') : '', '<img id="storeProductMainImage" src="', $src, '" alt="', $title, '" title="', $title, '" />', $full ? '</a>' : '';
				return ob_get_clean();
			}
		}
		// get default if still here
		ob_start();
		echo '<img id="storeProductMainImage" src="', $this->DefaultImageSRC('default'), '" alt="', $title = $this->InputSafeString($this->details['title']), '" title="', $title, '" />';
		return ob_get_clean();
	} // end of fn MainImageDisplay
	
	public function ImageDisplay($sppid = 0, $size = 'default')
	{	if ($sppid && $this->photos[$sppid])
		{	$photo = new ProductPhoto($this->photos[$sppid]);
			if ($src = $photo->HasImage($size))
			{	$title = $this->InputSafeString($photo->details['phototitle'] ? $photo->details['phototitle'] : $this->details['title']);
				ob_start();
				echo '<img id="storeProductMainImage" src="', $src, '" alt="', $title, '" title="', $title, '" />';
				return ob_get_clean();
			}
		}
	} // end of fn ImageDisplay
	
	public function IsLive()
	{	return (bool)$this->details['live'];	
	} // end of fn IsLive
	
	public function GetAuthor()
	{	return $this->details['author'];	
	} // end of fn GetAuthor
	
	public function GetReviews($exclude = 0, $liveonly = true)
	{	$reviews = array();
		$where = array('pid=' . (int)$this->id, 'ptype="store"');
		if ($exclude = (int)$exclude)
		{	//$where[] = 'NOT sid=' . $exclude;
		}
		if ($liveonly)
		{	$where[] = 'suppressed=0';
		}
		$sql = 'SELECT * FROM productreviews WHERE ' . implode(' AND ', $where) . ' ORDER BY revdate DESC';

		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$reviews[$row['prid']] = $row;
			}
		}
		return $reviews;
	} // end of fn GetReviews
	
	public function ReviewList($limit = 0)
	{	$perpage = 5;
		
		if (!$limit)
		{	$limit = $perpage;
		}
		
		$results = array();
		if ($reviews = $this->GetReviews($exclude))
		//	$reviewlist = $this->ListProductReviews($this->GetReviews($exclude), 'store', $limit))
		{	$rating_total = 0;
			ob_start();
			echo '<ul>';
			foreach ($reviews as $review_row)
			{	//$this->VarDump($review_row);
				$rating_total += $review_row['rating'];
				if (++$count > $limit)
				{	if (!$lastdone++)
					{	echo '<li class="prodrevMore"><a onclick="GetMoreReviews(', $this->id, ',\'store\', ', $limit + $perpage, ');">... see more</a></li>';
					}
				} else
				{
					echo '<li><div class="revListLeft">';
					if ($heading = $this->InputSafeString($review_row['revtitle']))
					{	echo '<h4>', $heading, '</h4>';
					}
					echo $this->RatingDisplay($review_row['rating']), '<p class="review_author">', $this->AgoDateString(strtotime($review_row['revdate'])), ' by ', $this->InputSafeString($review_row['reviewertext']), '</p></div><div class="revListRight">', nl2br($this->InputSafeString($review_row['review'])), '</div><div class="clear"></div></li>';
				}
			}
			echo '</ul>';
			return array('text'=>ob_get_clean(), 'count'=>$revcount = count($reviews), 'rating'=>round($rating_total / $revcount, 2));
		}
		return $results;
	} // end of fn ReviewList
	
	public function GetMultiMedia($liveonly = true)
	{	$multimedia = array();
		
		$sql = 'SELECT multimedia.* FROM multimedia, storeproducts_mm WHERE multimedia.mmid=storeproducts_mm.mmid AND storeproducts_mm.prodid=' . $this->id;
		if ($liveonly)
		{	$sql .= ' AND multimedia.live=1';
		}
		$sql .= ' ORDER BY multimedia.mmorder, multimedia.posted';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$multimedia[$row['mmid']] = $row;
			}
		}
		
		return $multimedia;
	} // end of fn GetMultiMedia
	
	public function GetMultiMediaPurchase($liveonly = true)
	{	$multimedia = array();
		
		$sql = 'SELECT multimedia.* FROM multimedia, storeproducts_mmbuy WHERE multimedia.mmid=storeproducts_mmbuy.mmid AND storeproducts_mmbuy.prodid=' . $this->id;
		if ($liveonly)
		{	$sql .= ' AND multimedia.live=1';
		}
		$sql .= ' ORDER BY multimedia.mmorder, multimedia.posted';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$multimedia[$row['mmid']] = $row;
			}
		}
		
		return $multimedia;
	} // end of fn GetMultiMediaPurchase
	
	public function GetLink()
	{	return $this->link->GetStoreProductLink($this);
	} // end of fn GetLink
	
	public function GetStatus()
	{	return new ProductStatus($this->details['status']);
	} // end of fn GetStatus
	
	public function UpdateQty($qty = 0)
	{
		if ($result = $this->db->Query("UPDATE storeproducts SET qty = qty " . ($qty >= 0 ? '+' : '-') . abs($qty) . " WHERE id = " . (int)$this->id))
		{
			if($this->db->AffectedRows($result))
			{
				$this->Get($this->id);
				
				if($this->details['qty'] == 0)
				{
					// Set status to OOS	
				}
			}
		}
	} // end of fn UpdateQty
	
	public function GetDisplayTabs(Student $user)
	{	$tabs = array();
		
		if ($user->id && ($dl_display = $this->ListDownloads($user)))
		{	$tabs['downloads'] = array('label'=>'Downloads', 'content'=>$dl_display);
		}
		
		$tabs['about'] = array('label'=>'About', 'content'=>stripslashes($this->details['description']));
		if ($this->details['specs'])
		{	$tabs['specs'] = array('label'=>'Specifications', 'content'=>stripslashes($this->details['specs']));
		}
		/*if ($mmlist = $this->GetMultiMedia())
		{	ob_start();
			echo '<ul class="product_mm">';
			foreach ($mmlist as $mm_row)
			{	$mm = new Multimedia($mm_row);
				echo '<li><div class="mmdOutput">', $mm->Output(655), '</div><h3>', $this->InputSafeString($mm->details['mmname']), '</h3></li>';
			}
			echo '</ul>';
			$content = ob_get_clean();
			$tabs['multimedia'] = array('label'=>'Video' . (count($mmlist) > 1 ? 's' : ''), 'content'=>$content);
		}*/
		if ($this->details['instructions'])
		{	$tabs['instructions'] = array('label'=>'Instructions', 'content'=>stripslashes($this->details['instructions']));
		}
		
		return $tabs;
	} // end of fn GetDisplayTabs
	
	/** Search Functions ****************/
	public function Search($term)
	{
		
		$match = ' MATCH(title, description) AGAINST("' . $this->SQLSafe($term) . '") ';
		$sql = 'SELECT *, ' . $match . ' as matchscore FROM storeproducts WHERE ' . $match . ' AND live=1 ORDER BY matchscore DESC';
		
		$results = array();
		
		if($result = $this->db->Query($sql))
		{
			while($row = $this->db->FetchArray($result))
			{
				$results[] = new StoreProduct($row);	
			}
		}
		
		return $results;
	} // end of fn Search
	
	public function SearchResultOutput()
	{
		echo '<h4><span>Store</span><a href="', $link = $this->link->GetStoreProductLink($this), '">', $this->InputSafeString($this->details["title"]), '</a></h4><p><a href="', $link, '">read more ...</a></p>';
	} // end of fn SearchResultOutput
	
	public function AlsoBoughtProducts()
	{	$users = array();
		$products = array();
		
		// get users who bought this product
		$tables = array('storeorders', 'storeorderitems');
		$where = array('storeorders.id=storeorderitems.orderid', 'NOT storeorders.pptransid=""', 'storeorderitems.pid=' . (int)$this->id, 'storeorderitems.ptype="store"','storeorderitems.is_cancelled_refund="0"');
		$sql = 'SELECT DISTINCT storeorders.sid FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where);
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$users[$row['sid']] = $row['sid'];
			}
		}
		
		// get other items bought by these users
		if ($userlist = implode(',', $users))
		{	//echo $userlist;
			$tables = array('storeproducts', 'storeorders', 'storeorderitems');
			$where = array('storeproducts.id=storeorderitems.pid', 'storeproducts.live=1', 'storeorders.id=storeorderitems.orderid', 'NOT storeorders.pptransid=""', 'storeorderitems.ptype="store"', 'storeorderitems.is_cancelled_refund="0"','storeorders.sid IN (' . $userlist . ')', 'NOT storeproducts.id=' . (int)$this->id);
			$sql = 'SELECT storeproducts.*, COUNT(storeorderitems.id) AS bought_count FROM ' . implode(',', $tables) . ' WHERE ' . implode(' AND ', $where) . ' GROUP BY storeproducts.id ORDER BY bought_count DESC';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$products[$row['id']] = $row;
				}
			}// else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		return $products;
	} // end of fn AlsoBoughtProducts
	
	public function GetPeople($live_only = true)
	{	$people = array();
		$where = array('instructors.inid=productinstructors.inid', 'productinstructors.pid=' . $this->id);
		if ($live_only)
		{	$where[] = 'instructors.live=1';
		}
		$sql = 'SELECT instructors.* FROM instructors, productinstructors WHERE ' . implode(' AND ', $where) . ' ORDER BY instructors.showfront DESC, instructors.instname ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$people[$row['inid']] = $row;
			}
		}
		return $people;
	} // end of fn GetPeople
	
	public function GetAuthorString()
	{	$author = '';
		$by = array();
		if ($this->details['author'])
		{	$author = $this->InputSafeString($this->details['author']) . ' ';
		}
		if ($people = $this->GetPeople())
		{	foreach ($people as $inst_row)
			{	$by[] = '<a href="' . $this->GetAuthorStringLink($inst_row) . '">' . $this->InputSafeString($inst_row['instname']) . '</a>';
			}
		}
		return trim($author . implode(', ', $by));
	} // fn GetAuthorString
	
	public function GetAuthorStringLink($inst_row = array())
	{	$inst = new Instructor($inst_row);
		return $inst->Link();
	} // end of fn GetAuthorStringLink
	
	public function ProductID()
	{	return 'SP' . $this->id;
	} // end of fn ProductID
	
} // end of class StoreProduct

?>
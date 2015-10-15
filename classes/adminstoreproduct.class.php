<?php
class AdminStoreProduct extends StoreProduct
{
	
	public function __construct($id = null)
	{
		parent::__construct($id);
		
	} // end of fn __construct
	
	public function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete
	
	public function Delete()
	{	if ($this->CanDelete())
		{	$sql = 'DELETE FROM storeproducts WHERE id=' . (int)$this->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	//foreach ($this->imagesizes as $imagesize=>$size)
					//{	@unlink($this->GetImageFile($imagesize));
					//}
					$this->Reset();
					return true;
				}
			}
		}
	} // end of fn Delete
	
	public function InputForm()
	{	ob_start();
		
		if (!$data = $this->details)
		{	$data = $_POST;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$this->AddBackLinkHiddenField($form);
		$form->AddTextInput('Product name', 'title', $this->InputSafeString($data['title']), 'long', 255, 1);
		if ($this->id)
		{	$form->AddTextInput('Slug (for url)', 'slug', $this->InputSafeString($data['slug']), 'long', 255, 1);
		}
		$form->AddTextInput('Author text', 'author', $this->InputSafeString($data['author']), 'long', 255);
		$form->AddTextArea('About', 'description', $this->InputSafeString($data['description']), 'tinymce', 0, 0, 20, 60);
		$form->AddTextArea('Specifications', 'specs', $this->InputSafeString($data['specs']), 'tinymce', 0, 0, 15, 60);
		$form->AddTextArea('Instructions', 'instructions', $this->InputSafeString($data['instructions']), 'tinymce', 0, 0, 15, 60);
		
		$form->AddTextInput('Price', 'price', number_format($data['price'], 2, '.', ''), 'number', 11);
		$form->AddSelect('Tax rate', 'taxid', $data['taxid'], '', $this->TaxRatesForDropDown(), true, false);
		$form->AddCheckBox('Delivery needed?', 'shipping', '1', $data['shipping']);
		$form->AddTextInput('Weight (kgs)', 'weight', number_format($data['weight'], 2, '.', ''), 'number', 11);
		$form->AddTextInput('Quantity', 'qty', (int)$data['qty'], 'number', 8);
		$form->AddCheckBox('Current (available to buy)', 'live', '1', $data['live']);

		$form->AddSelect('Category', 'category', $data['category'], '', $this->GetAllCategories(), true, true);
		$form->AddCheckBox('In special offer list?', 'spoffer', '1', $data['spoffer']);
		$form->AddCheckBox('Show on front page?', 'frontpage', '1', $data['frontpage']);
		$form->AddCheckBox('Show social media links?', 'socialbar', 1, $data['socialbar']);
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Product', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this product</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('storeproducts', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm

	public function VideoPickPopUp()
	{	ob_start();
		echo '<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#mmc_modal_popup").jqm();});</script>',
			'<!-- START multimedia modal popup --><div id="mmc_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="mmcModalInner" style="height: 500px; overflow:auto;"></div></div>';
		return ob_get_clean();
	} // end of fn VideoPickPopUp
	
	public function VideoDescription($videoid = false)
	{	if ($videoid === false)
		{	$videoid = (int)$this->details['video'];
		}
		$mm = new AdminMultimedia($videoid);
		return $mm->AdminDescription();
	} // end of fn VideoDescription
	
	public function GetAllCategories()
	{	$cats = array();
		$sql = 'SELECT * FROM storecategories ORDER BY cid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$cats[$row['cid']] = $this->InputSafeString($row['ctitle']);
			}
		}
		return $cats;
	} // end of fn GetAllCategories

	public function SlugExists($slug = '')
	{	$sql = 'SELECT id FROM storeproducts WHERE slug="' . $this->SQLSafe($slug) . '"';
		if ($this->id)
		{	$sql .= ' AND NOT id=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return true;
			}
		}
		return false;
	} // end of fn SlugExists
	
	function Save($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($title = $this->SQLSafe($data['title']))
		{	$fields[] = 'title="' . $title . '"';
			if ($this->id && ($data['title'] != $this->details['title']))
			{	$admin_actions[] = array('action'=>'Name', 'actionfrom'=>$this->details['title'], 'actionto'=>$data['title']);
			}
		} else
		{	$fail[] = 'name missing';
		}
		
		if ($this->id)
		{	$slug = $this->TextToSlug($data['slug']);
		} else
		{	if ($title)
			{	$slug = $this->TextToSlug($title);
			}
		}
		
		if ($slug)
		{	$suffix = '';
			while ($this->SlugExists($slug . $suffix))
			{	$suffix++;
			}
			$slug .= $suffix;
			
			$fields[] = 'slug="' . $slug . '"';
			if ($this->id && ($slug != $this->details['slug']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['slug'], 'actionto'=>$slug);
			}
		} else
		{	if ($this->id || $title)
			{	$fail[] = 'slug missing';
			}
		}
		
		$author = $this->SQLSafe($data['author']);
		$fields[] = 'author="' . $author . '"';
		if ($this->id && ($data['author'] != $this->details['author']))
		{	$admin_actions[] = array('action'=>'Author', 'actionfrom'=>$this->details['author'], 'actionto'=>$data['author']);
		}
		
		$description = $this->SQLSafe($data['description']);
		$fields[] = 'description="' . $description . '"';
		if ($this->id && ($data['description'] != $this->details['description']))
		{	$admin_actions[] = array('action'=>'Description', 'actionfrom'=>$this->details['description'], 'actionto'=>$data['description'], 'actiontype'=>'html');
		}
		
		$specs = $this->SQLSafe($data['specs']);
		$fields[] = 'specs="' . $specs . '"';
		if ($this->id && ($data['specs'] != $this->details['specs']))
		{	$admin_actions[] = array('action'=>'Specs', 'actionfrom'=>$this->details['specs'], 'actionto'=>$data['specs'], 'actiontype'=>'html');
		}
		
		$instructions = $this->SQLSafe($data['instructions']);
		$fields[] = 'instructions="' . $instructions . '"';
		if ($this->id && ($data['instructions'] != $this->details['instructions']))
		{	$admin_actions[] = array('action'=>'Instructions', 'actionfrom'=>$this->details['instructions'], 'actionto'=>$data['instructions'], 'actiontype'=>'html');
		}
		
		$qty = (int)$data['qty'];
		$fields[] = 'qty=' . $qty ;
		if ($this->id && ($qty != $this->details['qty']))
		{	$admin_actions[] = array('action'=>'Quantity', 'actionfrom'=>$this->details['qty'], 'actionto'=>$data['qty']);
		}
		
		$price = round($data['price'], 2);
		$fields[] = 'price=' . $price ;
		if ($this->id && ($price != $this->details['price']))
		{	$admin_actions[] = array('action'=>'Price', 'actionfrom'=>$this->details['price'], 'actionto'=>$data['price']);
		}
		
		$shipping = ($data['shipping'] ? '1' : '0');
		$fields[] = 'shipping=' . $shipping;
		if ($this->id && ($shipping != $this->details['shipping']))
		{	$admin_actions[] = array('action'=>'Delivers?', 'actionfrom'=>$this->details['shipping'], 'actionto'=>$shipping, 'actiontype'=>'boolean');
		}
		
		$weight = round($data['weight'], 2);
		$fields[] = 'weight=' . $weight ;
		if ($this->id && ($weight != $this->details['weight']))
		{	$admin_actions[] = array('action'=>'Weight', 'actionfrom'=>$this->details['weight'], 'actionto'=>$data['weight']);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$spoffer = ($data['spoffer'] ? '1' : '0');
		$fields[] = 'spoffer=' . $spoffer;
		if ($this->id && ($spoffer != $this->details['spoffer']))
		{	$admin_actions[] = array('action'=>'Special offer?', 'actionfrom'=>$this->details['spoffer'], 'actionto'=>$spoffer, 'actiontype'=>'boolean');
		}
		
		$frontpage = ($data['frontpage'] ? '1' : '0');
		$fields[] = 'frontpage=' . $frontpage;
		if ($this->id && ($frontpage != $this->details['frontpage']))
		{	$admin_actions[] = array('action'=>'On front page?', 'actionfrom'=>$this->details['frontpage'], 'actionto'=>$frontpage, 'actiontype'=>'boolean');
		}
		
		$socialbar = ($data['socialbar'] ? '1' : '0');
		$fields[] = 'socialbar=' . $socialbar;
		if ($this->id && ($socialbar != $this->details['socialbar']))
		{	$admin_actions[] = array('action'=>'Social bar?', 'actionfrom'=>$this->details['socialbar'], 'actionto'=>$socialbar, 'actiontype'=>'boolean');
		}
		
		$taxrates = $this->TaxRatesForDropDown();
		if ($taxid = (int)$data['taxid'])
		{	if ($taxrates[$taxid])
			{	$fields[] = 'taxid=' . $taxid;
				if ($this->id && ($taxid != $this->details['taxid']))
				{	$admin_actions[] = array('action'=>'Tax rate', 'actionfrom'=>$taxrates[$this->details['taxid']], 'actionto'=>$taxrates[$taxid]);
				}
			} else
			{	$fail[] = 'Tax rate not found';
			}
		} else
		{	$fields[] = 'taxid=0';
			if ($this->id && $this->details['taxid'])
			{	$admin_actions[] = array('action'=>'Tax rate', 'actionfrom'=>$taxrates[$this->details['taxid']], 'actionto'=>'');
			}
		}
		
		$cats = $this->GetAllCategories();
		if ($category = (int)$data['category'])
		{	if ($cats[$category])
			{	$fields[] = 'category=' . $category;
				if ($this->id && ($category != $this->details['category']))
				{	$admin_actions[] = array('action'=>'Category', 'actionfrom'=>$cats[$this->details['category']], 'actionto'=>$cats[$category]);
				}
			} else
			{	$fail[] = 'Category not found';
			}
		} else
		{	$fields[] = 'category=0';
			if ($this->id && $this->details['category'])
			{	$admin_actions[] = array('action'=>'Category', 'actionfrom'=>$cats[$this->details['category']], 'actionto'=>'');
			}
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE storeproducts SET ' . $set . ' WHERE id=' . $this->id;
			} else
			{	$sql = 'INSERT INTO storeproducts SET ' . $set;
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New product created';
						$this->RecordAdminAction(array('tablename'=>'storeproducts', 'tableid'=>$this->id, 'area'=>'products', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'storeproducts', 'tableid'=>$this->id, 'area'=>'products');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	public function BundlesList()
	{	if ($this->id)
		{	ob_start();
			echo '<h2>Bundles involving this product</h2><table><tr class="newlink"><th colspan="6"><a href="bundleedit.php">Create new bundle</a></th></tr><tr><th>Title</th><th>Description</th><th>Products</th><th>Discount</th><th>Live?</th><th>Actions</th></tr>';
			foreach ($this->GetBundles() as $bundle_row)
			{	$bundle = new AdminBundle($bundle_row);
				echo '<tr class="stripe', $i++ % 2, '"><td>', $this->InputSafeString($bundle->details['bname']), '</td><td>', nl2br($this->InputSafeString($bundle->details['bdesc'])), '</td><td>', $bundle->ProductTextList('<br />'), '</td><td>',number_format($bundle->details['discount'], 2), '</td><td>', $bundle->details['live'] ? 'Yes' : 'No', '</td><td><a href="bundleedit.php?id=', $bundle->id, '">edit</a>';
				if ($histlink = $this->DisplayHistoryLink('bundles', $bundle->id))
				{	echo '&nbsp;|&nbsp;', $histlink;
				}
				if ($bundle->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="bundleedit.php?id=', $bundle->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo "</table>";
			return ob_get_clean();
		}
	} // end of fn BundlesList
	
	public function GetBundles()
	{	$bundles = array();
		$sql = 'SELECT bundles.* FROM bundles, bundleproducts WHERE bundles.bid=bundleproducts.bid AND pid=' . (int)$this->id . ' AND bundleproducts.ptype="store" ORDER BY bundles.bid';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$bundles[$row['bid']] = $row;
			}
		}
		return $bundles;
	} // end of fn GetBundles

	public function GetReviews($exclude = 0, $liveonly = true)
	{	return parent::GetReviews(0, false);
	} // end of fn GetReviews
	
	public function ReviewsDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->ReviewsTable(), '</div><script type="text/javascript">productID=', $this->id, ';$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner"></div></div></div>';
		return ob_get_clean();
	} // end of fn ReviewsDisplay
	
	public function ReviewsTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a href="productreview.php?pid=', $this->id, '">Create new review</a></th></tr><tr><th>Created by</th><th>Reviewer displayed</th><th>Date</th><th>Review</th><th>Status</th><th>Admin notes</th><th>Actions</th></tr>';
		$students = array();
		$adminusers = array();
		foreach ($this->GetReviews() as $review_row)
		{	$review = new AdminProductReview($review_row);
			echo '<tr><td>';
			if ($review->details['sid'])
			{	if (!$students[$review->details['sid']])
				{	$students[$review->details['sid']] = new Student($review->details['sid']);
				}
				echo 'Student: <a href="member.php?id=', $students[$review->details['sid']]->id, '">', $this->InputSafeString($students[$review->details['sid']]->GetName()), '</a>';
			} else
			{	if (!$adminusers[$review->details['admincreated']])
				{	$adminusers[$review->details['admincreated']] = new AdminUser($review->details['admincreated']);
				}
				echo 'Admin: <a href="useredit.php?userid=', $adminusers[$review->details['admincreated']]->userid, '">',  $adminusers[$review->details['admincreated']]->username, '</a>';
			}
			echo '</td><td>', $this->InputSafeString($review->details['reviewertext']), '</td><td>', date('d/m/y @H:i', strtotime($review->details['revdate'])), '</td><td>', nl2br($this->InputSafeString($review->details['review'])), '</td><td>', $review->StatusString(), '</td><td>', nl2br($this->InputSafeString($review->details['adminnotes'])), '</td><td><a href="productreview.php?id=', $review->id, '">edit</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ReviewsTable
/*	
	public function ReviewsTable()
	{	ob_start();
		echo '<table><tr><th>Student</th><th>Date</th><th>Review</th><th>Status</th><th>Admin notes</th><th>Actions</th></tr>';
		foreach ($this->GetReviews() as $review_row)
		{	$review = new AdminProductReview($review_row);
			$student = $review->GetAuthor();
			echo '<tr><td>', $this->InputSafeString($student->GetName()), '</td><td>', date('d/m/y @H:i', strtotime($review->details['revdate'])), '</td><td>', nl2br($this->InputSafeString($review->details['review'])), '</td><td>', $review->StatusString(), '</td><td>', nl2br($this->InputSafeString($review->details['adminnotes'])), '</td><td><a onclick="ReviewPopUp(', $review->id, ');">change status</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ReviewsTable*/
	
	public function DownloadsList()
	{	if ($this->id)
		{	ob_start();
			echo '<table><tr class="newlink"><th colspan="4"><a href="productdownload.php?prodid=', $this->id, '">Create new download</a></th></tr><tr><th>Title</th><th>File name (when downloaded</th><th>Live?</th><th>Actions</th></tr>';
			foreach ($this->GetDownloads() as $dl_row)
			{	$download = new AdminStoreProductDownload($dl_row);
				echo '<tr class="stripe', $i++ % 2, '"><td>', $this->InputSafeString($download->details['filetitle']), '</td><td>', $download->DownloadName(), '</td><td>', $download->details['live'] ? 'Yes' : 'No', '</td><td><a href="productdownload.php?id=', $download->id, '">edit</a>';
				if ($histlink = $this->DisplayHistoryLink('storeproductfiles', $download->id))
				{	echo '&nbsp;|&nbsp;', $histlink;
				}
				if ($download->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="productdownload.php?id=', $download->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo "</table>";
			return ob_get_clean();
		}
	} // end of fn DownloadsList
	
	public function AdminImagesList()
	{	if ($this->id)
		{	ob_start();
			echo '<table><tr class="newlink"><th colspan="4"><a href="productimage.php?prodid=', $this->id, '">Create new image</a></th></tr><tr><th></th><th>Title</th><th>List order</th><th>Actions</th></tr>';
			foreach ($this->photos as $image_row)
			{	$image = new AdminProductPhoto($image_row);
				echo '<tr class="stripe', $i++ % 2, '"><td><img src="', $image->HasImage('thumbnail'), '" /></td><td>';
				if ($image->details['phototitle'])
				{	echo $this->InputSafeString($image->details['phototitle']);
				} else
				{	echo '{', $this->InputSafeString($this->details['title']), '}';
				}
				echo '</td><td>', (int)$image->details['listorder'], '</td><td><a href="productimage.php?id=', $image->id, '">edit</a>';
				if ($histlink = $this->DisplayHistoryLink('storeproducts_photos', $image->id))
				{	echo '&nbsp;|&nbsp;', $histlink;
				}
				if ($image->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="productimage.php?id=', $image->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
			return ob_get_clean();
		}
	} // end of fn AdminImagesList
	
	public function GetDownloads($liveonly = false)
	{	return parent::GetDownloads(false);
	} // end of fn GetDownloads
	
	public function AddMultimedia($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if ((!$mmlist = $this->GetMultiMedia()) || !$mmlist[$mmid])
			{	$sql = 'INSERT INTO storeproducts_mm SET prodid=' . $this->id . ', mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn AddMultimedia
	
	public function RemoveMultimedia($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if (($mmlist = $this->GetMultiMedia()) && $mmlist[$mmid])
			{	$sql = 'DELETE FROM storeproducts_mm WHERE prodid=' . $this->id . ' AND mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn RemoveMultimedia
	
	public function GetMultiMedia()
	{	return parent::GetMultiMedia(false);
	} // end of fn GetMultiMedia
	
	public function MultiMediaDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->MultiMediaTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn MultiMediaDisplay
	
	public function MultiMediaTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a onclick="MultiMediaPopUp(', $this->id, ');">Add multimedia</a></th></tr><tr><th></th><th>Multimedia name</th><th>Type</th><th>Categories</th><th>Live?</th><th>Posted</th><th>Actions</th></tr>';
		foreach ($this->GetMultiMedia() as $mmid=>$mm_row)
		{	echo '<tr><td>';
			$mm = new AdminMultimedia($mm_row);
			if ($img_src = $mm->Thumbnail())
			{	echo '<img src="', $img_src, '" width="100px" />';
			}
			echo '</td><td class="pagetitle">', $this->InputSafeString($mm->details['mmname']), '</td><td>', $mm->MediaType(), '</td><td>', $mm->CatsList(), '</td><td>', $mm->details['live'] ? 'Yes' : 'No', '</td><td>', date('d-M-y @H:i', strtotime($mm->details['posted'])), $mm->details['author'] ? ('<br />by ' . $this->InputSafeString($mm->details['author'])) : '', '</td><td><a href="multimedia.php?id=', $mm->id, '">edit</a>&nbsp;|&nbsp;<a onclick="MultiMediaRemove(', $this->id, ',', $mmid, ');">remove from product</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn MultiMediaTable
	
	public function AddMultimediaPurchase($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if ((!$mmlist = $this->GetMultiMediaPurchase()) || !$mmlist[$mmid])
			{	$sql = 'INSERT INTO storeproducts_mmbuy SET prodid=' . $this->id . ', mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn AddMultimediaPurchase
	
	public function RemoveMultimediaPurchase($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if (($mmlist = $this->GetMultiMediaPurchase()) && $mmlist[$mmid])
			{	$sql = 'DELETE FROM storeproducts_mmbuy WHERE prodid=' . $this->id . ' AND mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn RemoveMultimedia
	
	public function GetMultiMediaPurchase()
	{	return parent::GetMultiMediaPurchase(false);
	} // end of fn GetMultiMediaPurchase
	
	public function MultiMediaPurchaseDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->MultiMediaPurchaseTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn MultiMediaPurchaseDisplay
	
	public function MultiMediaPurchaseTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a onclick="MultiMediaPopUp(', $this->id, ');">Add multimedia</a></th></tr><tr><th></th><th>Multimedia name</th><th>Type</th><th>Categories</th><th>Live?</th><th>Posted</th><th>Actions</th></tr>';
		foreach ($this->GetMultiMediaPurchase() as $mmid=>$mm_row)
		{	echo '<tr><td>';
			$mm = new AdminMultimedia($mm_row);
			if ($img_src = $mm->Thumbnail())
			{	echo '<img src="', $img_src, '" width="100px" />';
			}
			echo '</td><td class="pagetitle">', $this->InputSafeString($mm->details['mmname']), '</td><td>', $mm->MediaType(), '</td><td>', $mm->CatsList(), '</td><td>', $mm->details['live'] ? 'Yes' : 'No', '</td><td>', date('d-M-y @H:i', strtotime($mm->details['posted'])), $mm->details['author'] ? ('<br />by ' . $this->InputSafeString($mm->details['author'])) : '', '</td><td><a href="multimedia.php?id=', $mm->id, '">edit</a>&nbsp;|&nbsp;<a onclick="MultiMediaRemove(', $this->id, ',', $mmid, ');">remove from product</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn MultiMediaPurchaseTable
	
	public function GetPeople()
	{	return parent::GetPeople(false);
	} // end of fn GetPeople
	
	public function PeopleListContainer()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->PeopleListTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn PeopleListContainer
	
	public function PeopleListTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="4"><a onclick="InstructorsPopUp(', $this->id, ');">Add person to product</a></th></tr><tr><th></th><th>Name</th><th>Live?</th><th>Actions</th></tr>';
		foreach ($this->GetPeople() as $inid=>$instructor_row)
		{	$instructor = new AdminInstructor($instructor_row);
			echo '<tr><td>';
			if (file_exists($instructor->GetImageFile('thumbnail')))
			{	echo '<img height="50px" src="', $instructor->GetImageSRC('thumbnail'), '" />';
			} else
			{	echo 'no photo';
			}
			echo '</td><td>', $this->InputSafeString($instructor->GetFullName()), '</td><td>', $instructor->details['live'] ? 'Yes' : '', '</td><td><a onclick="InstructorRemove(', $this->id, ',', $instructor->id, ');">remove from product</a>&nbsp;|&nbsp;<a href="instructoredit.php?id=', $instructor->id, '">view instructor</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn PeopleListTable
	
	public function AddInstructor($inid = 0)
	{	if (((!$people = $this->GetPeople()) || !$people[$inid]) && ($inst = new Instructor($inid)) && $inst->id)
		{	$sql = 'INSERT INTO productinstructors SET pid=' . $this->id . ', inid=' . $inst->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		} else echo 'problem';
	} // end of fn AddInstructor
	
	public function RemoveInstructor($inid = 0)
	{	if (($people = $this->GetPeople()) && $people[$inid])
		{	$sql = 'DELETE FROM productinstructors WHERE pid=' . $this->id . ' AND inid=' . $inid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			}
		}
	} // end of fn RemoveInstructor
	
	public function GetAuthorStringLink($inst_row = array())
	{	return 'instructoredit.php?id=' . $inst_row['inid'];
	} // end of fn GetAuthorStringLink
	
	public function GetPurchases($start = '', $end = '')
	{	$purchases = array();
		$fields = array('storeorders.*', 'storeorderitems.qty AS itemqty', 'storeorderitems.totalpricetax AS itemprice', 'storeorderitems.discount_total AS itemdiscount');
		$tables = array('storeorders', 'storeorderitems');
		$where = array('storeorders.id=storeorderitems.orderid', 'storeorderitems.ptype="store"', 'storeorderitems.pid=' . $this->id, 'NOT pptransid=""');
		
		if ((int)$start)
		{	$where[] = 'storeorders.orderdate>="' . $start . ' 00:00:00"';
		}
		
		if ((int)$end)
		{	$where[] = 'storeorders.orderdate<="' . $end . ' 23:59:59"';
		}
		
		$sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . implode(', ', $tables) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY storeorders.orderdate DESC';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$purchases[] = $row;
			}
		} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		
		return $purchases;
	} // end of fn GetPurchases
	
} // end of class AdminStoreProduct
?>
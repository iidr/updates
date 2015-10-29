<?php
class AdminPageContent extends PageContent
{	var $admintitle = '';

	function __construct($id = 0, $adminuser = false)
	{	parent::__construct($id, false);
		$this->GetAdminTitle();
	} //  end of fn __construct
	
	function GetAdminTitle()
	{	if ($this->id)
		{	$this->admintitle = $this->details['pagetitle'];
		}
	} // end of fn GetAdminTitle
	
	function GetDefaultDetails()
	{	$sql = 'SELECT * FROM pages WHERE pageid=' . $this->id;
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row;
			}
		}
		return array();
	} // end of fn GetDefaultDetails
	
	function AssignPage($subpage = 0)
	{	return new AdminPageContent($subpage);
	} // end of fn AssignPage
	
	private function ValidSlug($slug = '')
	{	$rawslug = $slug = $this->TextToSlug($slug);
		while ($this->SlugExists($slug))
		{	$slug = $rawslug . ++$count;
		}
		return $slug;
	} // end of fn ValidSlug
	
	private function SlugExists($slug = '')
	{	$sql = 'SELECT pageid FROM pages WHERE pagename="' . $slug . '"';
		if ($this->id)
		{	$sql .= ' AND NOT pageid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['pageid'];
			}
		}
		return false;
	} // end of fn SlugExists
	
	function Save($data = array(), $imagefile = array())
	{	
		$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($pagetitle = $this->SQLSafe($data['pagetitle']))
		{	$fields[] = 'pagetitle="' . $pagetitle . '"';
			if ($this->id && ($data['pagetitle'] != $this->details['pagetitle']))
			{	$admin_actions[] = array('action'=>'Page title', 'actionfrom'=>$this->details['pagetitle'], 'actionto'=>$data['pagetitle']);
			}
		} else
		{	$fail[] = 'page title missing';
		}
	
		// create slug
		if ($pagename = $this->ValidSlug(($this->id && $data['pagename']) ? $data['pagename'] : $pagetitle))
		{	$fields[] = 'pagename="' . $pagename . '"';
			if ($this->id && ($pagename != $this->details['pagename']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['pagename'], 'actionto'=>$data['pagename']);
			}
		} else
		{	if ($pagetitle)
			{	$fail[] = 'slug missing';
			}
		}
		
		$pagetext = $this->SQLSafe($data['pagetext']);
		$fields[] = 'pagetext="' . $pagetext . '"';
		if ($this->id && ($data['pagetext'] != $this->details['pagetext']))
		{	$admin_actions[] = array('action'=>'Text', 'actionfrom'=>$this->details['pagetext'], 'actionto'=>$data['pagetext']);
		}
		
		$pageintro = $this->SQLSafe($data['pageintro']);
		$fields[] = 'pageintro="' . $pageintro . '"';
		if ($this->id && ($data['pageintro'] != $this->details['pageintro']))
		{	$admin_actions[] = array('action'=>'Intro', 'actionfrom'=>$this->details['pageintro'], 'actionto'=>$data['pageintro']);
		}
		
		if ($parentid = (int)$data['parentid'])
		{	if ($parents = $this->GetPossibleParents())
			{	if ($parents[$parentid])
				{	$fields[] = 'parentid=' . $parentid;
					if ($this->id && ($parentid != $this->details['parentid']))
					{	$admin_actions[] = array('action'=>'Parent page', 'actionfrom'=>$this->details['parentid'], 'actionto'=>$parentid, 'actiontype'=>'link', 'linkmask'=>'pageedit.php?id={linkid}');
					}
					$inparent = ($data['inparent'] ? '1' : '0');
					$fields[] = 'inparent=' . $inparent;
					if ($this->id && ($inparent != $this->details['inparent']))
					{	$admin_actions[] = array('action'=>'Section of parent?', 'actionfrom'=>$this->details['inparent'], 'actionto'=>$inparent, 'actiontype'=>'boolean');
					}
				} else
				{	$fail[] = 'parent not found';
				}
			}
		} else
		{	$fields[] = 'parentid=0';
			$fields[] = 'inparent=0';
			if ($this->id && $this->details['parentid'])
			{	$admin_actions[] = array('action'=>'Parent page', 'actionfrom'=>$this->details['parentid']);
			}
		}
		
		if ($this->CanAdminUser('technical'))
		{	if ($includefile = $this->SQLSafe($data['includefile']))
			{	if ($this->IncludeFileExists($includefile))
				{	$fields[] = 'includefile="' . $includefile . '"';
					if ($this->id && ($data['includefile'] != $this->details['includefile']))
					{	$admin_actions[] = array('action'=>'Include file', 'actionfrom'=>$this->details['includefile'], 'actionto'=>$data['includefile']);
					}
				} else
				{	$fail[] = 'file to include does not exist';
				}
			} else
			{	$fields[] = 'includefile=""';
				if ($this->id && $data['includefile'])
				{	$admin_actions[] = array('action'=>'Include file', 'actionfrom'=>$this->details['includefile'], 'actionto'=>'');
				}
			}
		}
		
		if (isset($data['menuclass']))
		{	$fields[] = 'menuclass="' . $this->SQLSafe($data['menuclass']) . '"';
			if ($this->id && ($data['menuclass'] != $this->details['menuclass']))
			{	$admin_actions[] = array('action'=>'Menu class', 'actionfrom'=>$this->details['menuclass'], 'actionto'=>$data['menuclass']);
			}
		}
		
		$fields[] = 'redirectlink="' . $this->SQLSafe($data['redirectlink']) . '"';
		if ($this->id && ($data['redirectlink'] != $this->details['redirectlink']))
		{	$admin_actions[] = array('action'=>'Redirect link', 'actionfrom'=>$this->details['redirectlink'], 'actionto'=>$data['redirectlink']);
		}
		
		$pageorder = (int)$data['pageorder'];
		$fields[] = 'pageorder=' . $pageorder;
		if ($this->id && ($pageorder != $this->details['pageorder']))
		{	$admin_actions[] = array('action'=>'Order', 'actionfrom'=>$this->details['pageorder'], 'actionto'=>$pageorder);
		}
		
		$banner = (int)$data['banner'];
		$fields[] = 'banner=' . $banner;
		if ($this->id && ($banner != $this->details['banner']))
		{	$admin_actions[] = array('action'=>'Banner', 'actionfrom'=>$this->details['banner'], 'actionto'=>$banner);
		}
		
		$pagelive = ($data['pagelive'] ? '1' : '0');
		$fields[] = 'pagelive=' . $pagelive;
		if ($this->id && ($pagelive != $this->details['pagelive']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['pagelive'], 'actionto'=>$pagelive, 'actiontype'=>'boolean');
		}
		
		$nosearch = ($data['nosearch'] ? '1' : '0');
		$fields[] = 'nosearch=' . $nosearch;
		if ($this->id && ($nosearch != $this->details['nosearch']))
		{	$admin_actions[] = array('action'=>'No search', 'actionfrom'=>$this->details['nosearch'], 'actionto'=>$nosearch, 'actiontype'=>'boolean');
		}
		
		$blocklink = ($data['blocklink'] ? '1' : '0');
		$fields[] = 'blocklink=' . $blocklink;
		if ($this->id && ($blocklink != $this->details['blocklink']))
		{	$admin_actions[] = array('action'=>'Show as block link?', 'actionfrom'=>$this->details['blocklink'], 'actionto'=>$blocklink, 'actiontype'=>'boolean');
		}
		
		$headermenu = ($data['headermenu'] ? '1' : '0');
		$fields[] = 'headermenu=' . $headermenu;
		if ($this->id && ($headermenu != $this->details['headermenu']))
		{	$admin_actions[] = array('action'=>'Header menu?', 'actionfrom'=>$this->details['headermenu'], 'actionto'=>$headermenu, 'actiontype'=>'boolean');
		}
		
		$footermenu = ($data['footermenu'] ? '1' : '0');
		$fields[] = 'footermenu=' . $footermenu;
		if ($this->id && ($footermenu != $this->details['footermenu']))
		{	$admin_actions[] = array('action'=>'Footer menu?', 'actionfrom'=>$this->details['footermenu'], 'actionto'=>$footermenu, 'actiontype'=>'boolean');
		}
		
		$headeronly = ($data['headeronly'] ? '1' : '0');
		$fields[] = 'headeronly=' . $headeronly;
		if ($this->id && ($headeronly != $this->details['headeronly']))
		{	$admin_actions[] = array('action'=>'Header only? (no content)', 'actionfrom'=>$this->details['headeronly'], 'actionto'=>$headeronly, 'actiontype'=>'boolean');
		}
		
		$socialbar = ($data['socialbar'] ? '1' : '0');
		$fields[] = 'socialbar=' . $socialbar;
		if ($this->id && ($socialbar != $this->details['socialbar']))
		{	$admin_actions[] = array('action'=>'Social bar?', 'actionfrom'=>$this->details['socialbar'], 'actionto'=>$socialbar, 'actiontype'=>'boolean');
		}
		
		$hideheader = ($data['hideheader'] ? '1' : '0');
		$fields[] = 'hideheader=' . $hideheader;
		if ($this->id && ($hideheader != $this->details['hideheader']))
		{	$admin_actions[] = array('action'=>'Hide title?', 'actionfrom'=>$this->details['hideheader'], 'actionto'=>$hideheader, 'actiontype'=>'boolean');
		}
		
		if ($this->CanAdminUser('technical'))
		{	$galleries = ($data['galleries'] ? '1' : '0');
			$fields[] = 'galleries=' . $galleries;
			if ($this->id && ($galleries != $this->details['galleries']))
			{	$admin_actions[] = array('action'=>'galleries?', 'actionfrom'=>$this->details['galleries'], 'actionto'=>$galleries, 'actiontype'=>'boolean');
			}
		}

		if ((!$fail || $this->id) && $set = implode(', ', $fields))
		{	
			if ($this->id)
			{	$sql = 'UPDATE pages SET ' . $set . ' WHERE pageid=' . (int)$this->id;
			} else
			{	$sql = 'INSERT INTO pages SET ' . $set;
			}
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if (!$this->id)
					{	$this->id = $this->db->InsertID();
						$success[] = 'New page created';
						$this->RecordAdminAction(array('tablename'=>'pages', 'tableid'=>$this->id, 'area'=>'pages', 'action'=>'created'));
					} else
					{	$record_changes = true;
						$success[] = 'Changes saved';
					}
					$this->Get($this->id);
					$this->GetAdminTitle();
				}
				
				if ($this->id)
				{	
					$this->Get($this->id);
					
					// banner upload
					if ($bannerfile['size'])
					{	$uploaded = $this->UploadPhoto($bannerfile);
						if ($uploaded['successmessage'])
						{	$success[] = $uploaded['successmessage'];
							$this->RecordAdminAction(array('tablename'=>'pages', 'tableid'=>$this->id, 'area'=>'pages', 'action'=>'New banner uploaded'));
						}
						if ($uploaded['failmessage'])
						{	$fail[] = $uploaded['failmessage'];
						}
						@unlink($bannerfile['tmp_name']);
					} else
					{	if ($data['delbanner'])
						{	@unlink($this->GetImageFile("default"));
							@unlink($this->GetImageFile("thumbnail"));
							$success[] = 'banner deleted';
							$this->RecordAdminAction(array('tablename'=>'pages', 'tableid'=>$this->id, 'area'=>'pages', 'action'=>'Banner deleted'));
						}
					}
					
				}
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'pages', 'tableid'=>$this->id, 'area'=>'pages');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
			
				if ($imagefile['size'])
				{	//print_r($imagefile);
					if ((!stristr($imagefile['type'], 'jpeg') && !stristr($imagefile['type'], 'jpg') && !stristr($imagefile['type'], 'png')) || $imagefile['error'])
					{	$fail[] = 'error uploading banner (jpegs, pngs only)';
					} else
					{	$photos_created = 0;
						foreach ($this->imagesizes as $size_name=>$size)
						{	
							if ($this->ReSizePhotoPNG($imagefile['tmp_name'], $this->GetImageFile($size_name), $size[0], $size[1], stristr($imagefile['type'], 'png') ? 'png' : 'jpg'))
							{	$photos_created++;
							}
						}
						unlink($imagefile['tmp_name']);
						if ($photos_created)
						{	$success[] = 'image uploaded';
						}
					}
				} else
				{	if ($data['delphoto'])
				{	
					$this->DeletePhotos();
					$success[] = 'photo deleted';
					$this->RecordAdminAction(array('tablename'=>'askimamtopics', 'tableid'=>$this->id, 'area'=>'askimamtopics', 'action'=>'Image deleted'));
				}
				}
			}
			
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	public function DeletePhotos()
	{	foreach ($this->imagesizes as $sizename=>$size)
		{	@unlink($this->GetImageFile($sizename));
		}
	} // end of fn DeletePhotos
	
	function UploadPhoto($file)
	{
		$fail = array();
		$successmessage = '';
		
		if($file['size'])
		{
			if((!stristr($file['type'], 'jpeg') && !stristr($file['type'], 'jpg') && !stristr($file['type'], 'png')) || $file['error'])
			{
				$fail[] = 'File type invalid (jpeg/png only)';
			} else
			{
				$this->ReSizePhoto($file['tmp_name'], $this->GetImageFile('default'), $this->imagesizes['default'][0], $this->imagesizes['default'][1]);
				$this->ReSizePhoto($file['tmp_name'], $this->GetImageFile('thumbnail'), $this->imagesizes['thumbnail'][0], $this->imagesizes['thumbnail'][1]);	
				unlink($file['tmp_name']);
				
				$successmessage = "New banner uploaded";
			}
		} else
		{
			$fail[] = "Banner not uploaded";	
		}
		
		return array("failmessage"=>implode(", ", $fail), "successmessage"=>$successmessage);
	}
	
	function Delete()
	{
		$fail = array();
		$success = array();
		
		if ($result = $this->db->Query("DELETE FROM pages WHERE pageid=$this->id"))
		{	if ($this->db->AffectedRows())
			{	
				$success[] = "page \"{$this->details["pagename"]}\" has been deleted";
				$this->RecordAdminAction(array("tablename"=>"pages", "tableid"=>$this->id, "area"=>"pages", "action"=>"deleted"));
				$this->Reset();
			}
		}
		
		if ($this->id)
		{	$fail[] = "delete failed";
		}
		
		return array("failmessage"=>implode(", ", $fail), "successmessage"=>implode(", ", $success));
		
	} // end of fn Delete
	
	function CanDelete()
	{	return !count($this->subpages) && $this->CanAdminUserDelete();
	} // end of fn CanDelete
	
	function InputForm()
	{	
		if (!$data = $this->details)
		{	$data = $_POST;
			if (!$data)
			{	$data = array('live'=>1);
			}
		}
		
		$form = new Form('pageedit.php?id=' . (int)$this->id, 'pageedit');
		$form->AddTextInput('Page title', 'pagetitle', $this->InputSafeString($data['pagetitle']), '', 50);
		$form->AddCheckBox('Hide title on page', 'hideheader', 1, $data['hideheader']);
		
		if ($parents = $this->GetPossibleParents())
		{	$form->AddSelectWithGroups('Parent page', 'parentid', $data['parentid'], '', $parents, 1, 0, '');
			$form->AddCheckBox('Display as section of parent', 'inparent', 1, $data['inparent']);
		}
		
		$form->AddTextInput('Order in menu', 'pageorder', (int)$data['pageorder'], 'num', 4);
		if ($this->CanAdminUser('technical'))
		{	$form->AddTextInput('Extra page to include', 'includefile', $this->InputSafeString($data['includefile']), '', 50);
			$form->AddCheckBox('Allow galleries', 'galleries', 1, $data['galleries']);
		}
		$form->AddCheckBox('Make live', 'pagelive', 1, $data['pagelive']);
		$form->AddCheckBox('Leave out of search results', 'nosearch', 1, $data['nosearch']);
		$form->AddCheckBox('Show social media links?', 'socialbar', 1, $data['socialbar']);
		$form->AddCheckBox('In header menu?', 'headermenu', 1, $data['headermenu']);
		if ($this->CanAdminUser('technical'))
		{	$form->AddTextInput('Class for header menu', 'menuclass', $this->InputSafeString($data['menuclass']), '', 50);
		}
		$form->AddCheckBox('In footer menu?', 'footermenu', 1, $data['footermenu']);
		$form->AddCheckBox('Header only (no content)', 'headeronly', 1, $data['headeronly']);
		$form->AddTextInput('Redirect link (full address if external)', 'redirectlink', $this->InputSafeString($data['redirectlink']), 'long', 255);
		
		if ($this->id)
		{	$form->AddTextInput('Slug (for URL)', 'pagename', $this->InputSafeString($data['pagename']), 'long', 255);
			if ($link = $this->Link())
			{	$form->AddRawText('<p><label>Link to page</label><span><a href="' . $link . '" target="_blank">' . $link . '</a></span><br /></p>');
			}
		}
		$form->AddCheckBox('Display block link in parent (if any)', 'blocklink', 1, $data['blocklink']);
		$form->AddTextArea('Block link text', $name = 'pageintro', stripslashes($data['pageintro']), 'tinymce', 0, 0, 10, 60);
		$form->AddTextArea('Page content', $name = 'pagetext', stripslashes($data['pagetext']), 'tinymce', 0, 0, 50, 60);
		$form->AddRawText('<p><label></label><a href="#" onclick="javascript:window.open(\'newsimagelist.php\', \'newsimages\', \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=550\'); return false;">view available images</a></p>');
		$form->AddFileUpload('Subpage image:', 'imagefile');
		if ($src = $this->HasImage('thumbnail'))
		{	$form->AddRawText('<label>Current image</label><img src="' . $src . '?' . time() . '" height="200px" /><br />');
			$form->AddCheckBox('Delete this', 'delphoto');
		}
		
		ob_start();
		echo '<label>Banner:</label><br /><label id="bannerPicked">', ($data['banner'] && ($banner = new BannerSet($data['banner'])) && $banner->id) ? $this->InputSafeString($banner->details['title']) : 'none','</label><input type="hidden" name="banner" id="bannerValue" value="', (int)$data['banner'], '" /><span class="dataText"><a onclick="BannerPicker();">change this</a></span><br />';
		$form->AddRawText(ob_get_clean());


		$form->AddSubmitButton('', $this->id ? 'Save' : 'Create', 'submit');
		echo $this->BannerPickerPopUp();
		
		$form->Output();
	} // end of fn InputForm

	public function BannerPickerPopUp()
	{	ob_start();
		echo '<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#banner_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="banner_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="bannerModalInner" style="height: 500px; overflow:auto;"></div></div>';
		return ob_get_clean();
	} // end of fn BannerPickerPopUp
	
	function GetPossibleParents($parentid = 0, $prefix = '')
	{
		$parents = array();
		$sql = 'SELECT * FROM pages WHERE parentid=' . (int)$parentid;
		if ($this->id)
		{	$sql .= ' AND NOT pageid=' . $this->id;
		}
		$sql .= ' ORDER BY pageorder';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if (!$this->subpages[$row['pageid']])
				{	$parents[$row['pageid']] = $prefix . $this->InputSafeString($row['pagetitle']);
					if ($children = $this->GetPossibleParents($row['pageid'], '-&nbsp;' . $prefix))
					{	foreach ($children as $pid=>$ptitle)
						{	$parents[$pid] = $ptitle;
						}
					}
				}
			}
		}
		
		return $parents;
		
	} // end of fn GetPossibleParents
	
	public function AddGallery($gid = 0)
	{	if ($this->id && ($gid = (int)$gid))
		{	if ((!$galleries = $this->GetGalleries()) || !$galleries[$gid])
			{	$sql = 'INSERT INTO gallerytopage SET pageid=' . $this->id . ', gid=' . $gid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn AddGallery
	
	public function RemoveGallery($gid = 0)
	{	if ($this->id && ($gid = (int)$gid))
		{	if (($galleries = $this->GetGalleries()) && $galleries[$gid])
			{	$sql = 'DELETE FROM gallerytopage WHERE pageid=' . $this->id . ' AND gid=' . $gid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn RemoveGallery
	
	public function GalleriesDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->GalleriesTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn GalleriesDisplay
	
	public function GalleriesTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a onclick="GalleryPopUp(', $this->id, ');">Add gallery</a></th></tr><tr><th></th><th>Title</th><th>Description</th><th>Photos</th><th>Live?</th><th>Actions</th></tr>';
		foreach ($this->GetGalleries() as $gid=>$gallery_row)
		{	$gallery = new AdminGallery($gallery_row);
			echo '<tr><td>';
			if ($cover = $gallery->HasCoverImage('thumbnail'))
			{	echo '<img src="', $cover, '" />';
			}
			echo '</td><td>', $this->InputSafeString($gallery->details['title']), '</td><td>', $this->InputSafeString($gallery->details['description']), '</td><td>', count($gallery->photos), '</td><td>', $gallery->details['live'] ? 'Yes' : '', '</td><td><a href="gallery.php?id=', $gallery->id, '">edit</a>&nbsp;|&nbsp;<a onclick="GalleryRemove(', $this->id, ',', $gallery->id, ');">remove from page</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn GalleriesTable
	
} // end of defn AdminPageContent
?>
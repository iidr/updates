<?php
class AdminMultimedia extends Multimedia
{	var $admintitle = '';
	
	function __construct($id = '')
	{	parent::__construct($id);
		$this->GetAdminTitle();
	} // fn __construct
	
	function GetAdminTitle()
	{	if ($this->id)
		{	$this->admintitle = $this->details['mmname'];
		}
	} // end of fn GetAdminTitle
	
	function CanDelete()
	{	
		if ($this->id && !$this->details['frontpage'])
		{	
			$sql = 'SELECT cid FROM courses_mm WHERE mmid=' . $this->id;
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	return false;
				}
			}
			$sql = 'SELECT cid FROM courses WHERE cvideo=' . $this->id;
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	return false;
				}
			}
			$sql = 'SELECT inid FROM instructors_mm WHERE mmid=' . $this->id;
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	return false;
				}
			}
			return true;
		}
		
		return false;

	} // end of fn CanDelete
	
	function Delete()
	{	//if ($this->CanDelete()){	
			$query = 'DELETE FROM multimedia WHERE mmid=' . (int)$this->id;
			if($result = $this->db->Query($query)){
				if ($this->db->AffectedRows()){	
					$subQuery = 'DELETE FROM multimediacats WHERE mmid=' . (int)$this->id;
					$this->db->Query($subQuery);					
					$this->RecordAdminAction(array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia', 'action'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		//}
		return false;
	} // end of fn Delete
	
	private function ValidSlug($slug = '')
	{	$rawslug = $slug = $this->TextToSlug($slug);
		while ($this->SlugExists($slug))
		{	$slug = $rawslug . ++$count;
		}
		return $slug;
	} // end of fn ValidSlug
	
	private function SlugExists($slug = '')
	{	$sql = 'SELECT mmid FROM multimedia WHERE mmslug="' . $slug . '"';
		if ($this->id)
		{	$sql .= ' AND NOT mmid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['mmid'];
			}
		}
		return false;
	} // end of fn SlugExists
	
	public function YoutubeCodeFromEmbed($embedcode = '')
	{	if ($text = stristr($embedcode, 'www.youtube.com/v/'))
		{	if ($text = str_replace('www.youtube.com/v/', '', $text))
			{	if ($amppos = strpos($text, '&'))
				{	return substr($text, 0, $amppos);
				}
			}
		}
		// not found try alternative youtube code
		if ($text = stristr($embedcode, 'www.youtube.com/embed/'))
		{	if ($text = str_replace('www.youtube.com/embed/', '', $text))
			{	if (($amppos = strpos($text, '?')) || ($amppos = strpos($text, '"')))
				{	return substr($text, 0, $amppos);
				}
			}
		}
		return '';
	} // end of fn YoutubeCodeFromEmbed
	
	public function VimeoCodeFromEmbed($embedcode = '')
	{	if ($text = stristr($embedcode, 'player.vimeo.com/video/'))
		{	if ($text = str_replace('player.vimeo.com/video/', '', $text))
			{	if ($amppos = strpos($text, '?'))
				{	return substr($text, 0, $amppos);
				}
			}
		}
		return '';
	} // end of fn VimeoCodeFromEmbed
	
	function Save($data = array(), $pdffile = array(), $poster = array(), $mp3file = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$mmtype = '';
		$admin_actions = array();
		
		if ($mmname = $this->SQLSafe($data['mmname']))
		{	$fields[] = 'mmname="' . $mmname . '"';
			if ($this->id && ($data['mmname'] != $this->details['mmname']))
			{	$admin_actions[] = array('action'=>'Title', 'actionfrom'=>$this->details['mmname'], 'actionto'=>$data['mmname']);
			}
		} else
		{	$fail[] = 'title missing';
		}
	
		if ($mmslug = $this->ValidSlug(($this->id && $data['mmslug']) ? $data['mmslug'] : $mmname))
		{	$fields[] = 'mmslug="' . $mmslug . '"';
			if ($this->id && ($data['mmslug'] != $this->details['mmslug']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['mmslug'], 'actionto'=>$data['mmslug']);
			}
		} else
		{	$fail[] = 'slug missing';
		}
		
		$author = $this->SQLSafe($data['author']);
		$fields[] = 'author="' . $author . '"';
		if ($this->id && ($data['author'] != $this->details['author']))
		{	$admin_actions[] = array('action'=>'Author', 'actionfrom'=>$this->details['author'], 'actionto'=>$data['author']);
		}
		
		if ($this->id)
		{	if (($d = (int)$data['dposted']) && ($m = (int)$data['mposted']) && ($y = (int)$data['yposted']))
			{	$posted = $this->datefn->SQLDate(mktime(0, 0, 0, $m, $d, $y)) . " " . $this->StringToTime($data['posted_time']) . ":00";
				$fields[] = 'posted="' . $posted . '"';
				if ($this->id && ($posted != $this->details['posted']))
				{	$admin_actions[] = array('action'=>'Posted', 'actionfrom'=>$this->details['posted'], 'actionto'=>$posted, 'actiontype'=>'datetime');
				}
			} else
			{	$fail[] = 'posted date is missing';
			}
		} else
		{	$fields[] = 'posted="' . $this->datefn->SQLDateTime() . '"';
		}
		
		$mmdesc = $this->SQLSafe($data['mmdesc']);
		$fields[] = 'mmdesc="' . $mmdesc . '"';
		if ($this->id && ($data['mmdesc'] != $this->details['mmdesc']))
		{	$admin_actions[] = array('action'=>'Description', 'actionfrom'=>$this->details['mmdesc'], 'actionto'=>$data['mmdesc']);
		}
		
		$embedcode = $this->SQLSafe($data['embedcode']);
		$fields[] = 'embedcode="' . $embedcode . '"';
		if ($this->id && ($data['embedcode'] != $this->details['embedcode']))
		{	$admin_actions[] = array('action'=>'Embedcode', 'actionfrom'=>$this->details['embedcode'], 'actionto'=>$data['embedcode']);
		}
		
		$mmtype = '';
		$videocode = '';
		if ($embedcode)
		{	// work out videocode and if youtube or vimeo
			if ($videocode = $this->YoutubeCodeFromEmbed(stripslashes($embedcode)))
			{	$mmtype = 'youtube';
			} else
			{	if ($videocode = $this->VimeoCodeFromEmbed(stripslashes($embedcode)))
				{	$mmtype = 'vimeo';
				}
			}
		}

		$fields[] = 'mmtype="' . $mmtype . '"';
		if ($this->id && ($data['mmtype'] != $this->details['mmtype']))
		{	$admin_actions[] = array('action'=>'Video type', 'actionfrom'=>$this->details['mmtype'], 'actionto'=>$data['mmtype']);
		}

		$fields[] = 'videocode="' . str_replace('\\', '', $videocode) . '"';
		if ($this->id && ($data['videocode'] != $this->details['videocode']))
		{	$admin_actions[] = array('action'=>'Video code', 'actionfrom'=>$this->details['videocode'], 'actionto'=>$data['videocode']);
		}
		
		$mmorder = (int)$data['mmorder'];
		$fields[] = 'mmorder=' . $mmorder;
		if ($this->id && ($data['mmorder'] != $this->details['mmorder']))
		{	$admin_actions[] = array('action'=>'Order', 'actionfrom'=>$this->details['mmorder'], 'actionto'=>$data['mmorder']);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
	
		$inlib = ($data['inlib'] ? '1' : '0');
		$fields[] = 'inlib=' . $inlib;
		if ($this->id && ($inlib != $this->details['inlib']))
		{	$admin_actions[] = array('action'=>'Library?', 'actionfrom'=>$this->details['inlib'], 'actionto'=>$inlib, 'actiontype'=>'boolean');
		}
		
		$socialbar = ($data['socialbar'] ? '1' : '0');
		$fields[] = 'socialbar=' . $socialbar;
		if ($this->id && ($socialbar != $this->details['socialbar']))
		{	$admin_actions[] = array('action'=>'Social bar?', 'actionfrom'=>$this->details['socialbar'], 'actionto'=>$socialbar, 'actiontype'=>'boolean');
		}

		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE multimedia SET ' . $set . ' WHERE mmid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO multimedia SET ' . $set;
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New multimedia created';
						$this->RecordAdminAction(array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia', 'action'=>'created'));
					}
					$this->Get($this->id);
				}
				
				if ($this->id)
				{	
					
					// save categories
					$delcat = 0;
					foreach ($this->cats as $catid=>$catrow)
					{	if (!$data['catid'][$catid])
						{	// then delete
							$sql = 'DELETE FROM multimediacats WHERE mmid=' . $this->id . ' AND lcid=' . (int)$catid;
							if ($result = $this->db->Query($sql))
							{	if ($this->db->AffectedRows())
								{	$delcat++;
								}
							}
						}
					}
					
					if ($delcat)
					{	$fail[] = 'removed from ' . $delcat . ' categories';
					}
					
					if (is_array($data['catid']))
					{	$addcat = 0;
						foreach ($data['catid'] as $catid=>$checked)
						{	if (!$this->cats[$catid])
							{	// then add
								$sql = 'INSERT INTO multimediacats SET mmid=' . $this->id . ', lcid=' . (int)$catid;
								if ($result = $this->db->Query($sql))
								{	if ($this->db->AffectedRows())
									{	$addcat++;
									}
								}
							}
						}
						
						if ($addcat)
						{	$success[] = 'added to ' . $addcat . ' categories';
						}
					}
					
					$this->Get($this->id);
					$this->GetAdminTitle();
					
					if ($pdffile && is_array($pdffile) && $pdffile['size'])
					{	if ($pdffile['error'])
						{	$fail[] = 'pdf upload failed';
						} else
						{	
							if (substr($pdffile['name'], -4) == '.pdf')
							{	//$this->VarDump($pdffile);
								if (!file_exists($this->pdf_dir) || !is_dir($this->pdf_dir))
								{	mkdir($this->pdf_dir);
									chmod($this->pdf_dir, 02775);
								}
								if (move_uploaded_file($pdffile['tmp_name'], $this->PDFFilename()))
								{	$success[] = 'new pdf for download uploaded';
								}
							} else
							{	$fail[] = 'pdf file is not a pdf';
							}
						}
					} else
					{	if ($data['delpdf'])
						{	if (@unlink($this->PDFFilename()))
							{	$success[] = 'pdf deleted';
								$this->RecordAdminAction(array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia', 'action'=>'PDF deleted'));
							}
						}
					}
					
					if ($mp3file && is_array($mp3file) && $mp3file['size'])
					{	if ($mp3file['error'])
						{	$fail[] = 'mp3/4 upload failed';
						} else
						{	
							if (substr($mp3file['name'], -4) == '.mp3')
							{	//$this->VarDump($pdffile);
								if (!file_exists($this->mp3_dir) || !is_dir($this->mp3_dir))
								{	mkdir($this->mp3_dir);
									chmod($this->mp3_dir, 02775);
								}
								if (move_uploaded_file($mp3file['tmp_name'], $this->MP3Filename()))
								{	$success[] = 'new mp3 for play/download uploaded';
								}
							} else
							{	if (substr($mp3file['name'], -4) == '.mp4')
								{	//$this->VarDump($pdffile);
									if (!file_exists($this->mp4_dir) || !is_dir($this->mp4_dir))
									{	mkdir($this->mp4_dir);
										chmod($this->mp4_dir, 02775);
									}
									if (move_uploaded_file($mp3file['tmp_name'], $this->MP4Filename()))
									{	$success[] = 'new mp4 for play/download uploaded';
									}
								} else
								{	$fail[] = 'audio/video file is not an mp3 or mp4';
								}
							}
						}
					} else
					{	if ($data['delmp3'])
						{	if (@unlink($this->MP3Filename()))
							{	$success[] = 'mp3 deleted';
								$this->RecordAdminAction(array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia', 'action'=>'MP3 deleted'));
							}
						}
					}
					
					if ($poster['size'])
					{	$uploaded = $this->UploadPhoto($poster);
						if ($uploaded['successmessage'])
						{	$success[] = $uploaded['successmessage'];
							$this->RecordAdminAction(array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia', 'action'=>'New poster uploaded'));
						}
						if ($uploaded['failmessage'])
						{	$fail[] = $uploaded['failmessage'];
						}
					} else
					{	if ($data['delposter'])
						{	@unlink($this->ImageFile());
							@unlink($this->ThumbFile());
							$success[] = 'poster deleted';
							$this->RecordAdminAction(array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia', 'action'=>'Poster deleted'));
						}
					}
					
					// check for front-page change
					if ($this->details['frontpage'])
					{	if (!$this->IsVideo()) // then remove as front page video, and give warning
						{	$sql = 'UPDATE multimedia SET frontpage=0 WHERE mmid=' . $this->id;
							if ($this->db->Query($sql))
							{	if ($this->db->AffectedRows())
								{	$fail[] = 'removed from front-page (no longer video) - THERE IS NOW NO FRONT PAGE VIDEO DEFINED';
									$this->Get($this->id);
								}
							}
						}
					} else
					{	if ($data['frontpage'])
						{	if ($this->IsVideo()) // then add as front-page video and remove from any others
							{	$sql = 'UPDATE multimedia SET frontpage=IF(mmid=' . $this->id . ', 1, 0)';
								if ($this->db->Query($sql))
								{	if ($this->db->AffectedRows())
									{	$success[] = 'added as front-page video';
										$this->Get($this->id);
									}
								}
							} else
							{	$fail[] = 'You cannot make a non-video the front-page video';
							}
						}
					}
				}
			
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'multimedia', 'tableid'=>$this->id, 'area'=>'multimedia');
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
	
	function UploadPhoto($file)
	{	$fail = array();
		$successmessage = '';

		if ($file['size'])
		{	if ((!stristr($file['type'], 'jpeg') && !stristr($file['type'], 'jpg') && !stristr($file['type'], 'png')) 
								|| $file['error'])
			{	$fail[] = 'File type invalid (jpeg or png only)';
			} else
			{	
				//$this->ReSizePhoto($file['tmp_name'], $this->ImageFile(), $this->image_w, $this->image_h);
				$this->ReSizePhoto($file['tmp_name'], $this->ThumbFile(), $this->thumb_w, $this->thumb_h);
				move_uploaded_file($file['tmp_name'], $this->ImageFile());
				//unlink($file['tmp_name']);
				
				$successmessage = 'New poster uploaded';
			}
		} else
		{	$fail[] = 'Poster not uploaded';
		}
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>$successmessage);

	} // end of fn UploadPhoto

	public function EmbedCodeForm($data = array())
	{	ob_start();
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id);
		
		$form->AddTextInput('Width', 'width', (int)$data['width'], 'short number', 4, 1);
		$form->AddTextInput('Height', 'height', (int)$data['height'], 'short number', 4, 1);
		//$form->AddCheckBox('Autoplay?', 'auto', '1', $data['auto']);
		
		$form->AddSubmitButton('', 'Get Embed Code', 'submit');
		$form->Output();
		return ob_get_clean();
	} // end of fn EmbedCodeForm
	
	function InputForm()
	{	
		ob_start();

		$cats_picked = array();
		if ($data = $this->details)
		{	$cats_picked = $this->cats;
			$posted_time = substr($data['posted'], 11, 5);
		} else
		{	$data = $_POST;
			$cats_picked = $_POST['catid'];
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id);
		if ($this->details['frontpage'])
		{	$form->AddRawText('<p><label>&nbsp;</label><strong>THIS IS CURRENTLY THE FRONT-PAGE VIDEO</strong><br class="clear" /></p>');
		}
		$form->AddTextInput('Title', 'mmname', $this->InputSafeString($data['mmname']), 'long', 255, 1);
		if ($this->id)
		{	$form->AddTextInput('Slug', 'mmslug', $this->InputSafeString($data['mmslug']), 'long', 255, 1);
		}
		
		$form->AddCheckBox('Live?', 'live', '1', $data['live']);
		$form->AddCheckBox('In library?', 'inlib', '1', $data['inlib']);
		$form->AddCheckBox('Show social media links?', 'socialbar', 1, $data['socialbar']);
		if ($this->details['live'])
		{	$form->AddRawText('<p><label>&nbsp;</label><a href="' . $this->Link() . '" target="_blank">view in front-end</a><br class="clear" /></p>');
		}
		if ($this->IsVideo() && !$this->details['frontpage'])
		{	$form->AddCheckBox('Use as front page video', 'frontpage', '1', $data['frontpage']);
		}
		$form->AddTextInput('Display order', 'mmorder', (int)$data['mmorder'], 'short number', 4, 1);
		$form->AddTextArea('Description', 'mmdesc', $this->InputSafeString($data['mmdesc']), 'tinymce', 0, 0, 10, 40);
		$form->AddTextInput('Author text', 'author', $this->InputSafeString($data['author']), 'long', 255, 0);
		if ($this->id)
		{	$form->AddMultiInput('Posted on', array(
						array('type'=>'DATE', 'name'=>'posted', 'value'=>$data['posted']), 
						array('type'=>'TEXT', 'name'=>'posted_time', 'value'=>$posted_time, 'css'=>'short', 'maxlength'=>5)), 
					true);
		}
		$form->AddFileUpload('PDF for download', 'pdfdownload');
		if ($this->PDFExists())
		{	$form->AddRawText('<p><label>Current PDF</label><a href="' . $this->PDFLink() . '" target="_blank">view</a><br class="clear" /></p>');
			$form->AddCheckBox('delete pdf', 'delpdf');
		}
		$form->AddFileUpload('or Audio/Video (MP3/4)', 'mp3download');
		if ($this->MP3Exists())
		{	$form->AddRawText('<p><label>Current MP3</label><a href="' . $this->MP3Link() . '&fd=1" target="_blank">download / play</a><br class="clear" /></p>');
			$form->AddCheckBox('delete mp3', 'delmp3');
		}
		if ($this->MP4Exists())
		{	$form->AddRawText('<p><label>Current MP4</label><a href="' . $this->MP4Link() . '&fd=1" target="_blank">download / play</a><br class="clear" /></p>');
			$form->AddCheckBox('delete mp4', 'delmp4');
		}
		$form->AddTextArea('or Embed code (vimeo or youtube)', 'embedcode', stripslashes($data['embedcode']), '', 0, 0, 10, 40);
		$form->AddFileUpload('Poster (thumbnail will be created)', 'poster');
		if (file_exists($this->ThumbFile()))
		{	$form->AddRawText('<p><label>Current poster</label><img src="' . $this->ThumbSRC() . '?' . time() . '" /><br /></p>');
			$form->AddCheckBox('Delete poster', 'delposter');
		}
		if ($cats = $this->GetPossibleCats())
		{	foreach ($cats as $catid=>$catname)
			{	$form->AddCheckBox($catname, 'catid['. $catid . ']', '1', $cats_picked[$catid]);
			}
		}
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Multimedia', 'submit');
		if ($histlink = $this->DisplayHistoryLink('multimedia', $this->id))
		{	echo '<p>', $histlink, '</p>';
		}
		
		if ($this->id){
			//if ($this->CanDelete()){	
				echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this multimedia</a></p>';
			//}
		}
		
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	function GetPossibleCats($parentid = 0, $pretext = '')
	{
		$cats = array();
		$sql = 'SELECT * FROM libcats WHERE parentid=' . $parentid . ' ORDER BY lcorder';
		
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if (!$this->subcats[$row['lcid']])
				{	$cats[$row['lcid']] = $pretext . $this->InputSafeString($row['lcname']);
					if ($subcats = $this->GetPossibleCats($row['lcid'], $cats[$row["lcid"]] . '&nbsp;-&nbsp;'))
					{	foreach ($subcats as $subid=>$subcat)
						{	$cats[$subid] = $subcat;
						}
					}
				}
			}
		}
		
		return $cats;
		
	} // end of fn GetPossibleCats
	
	public function CatsList()
	{	$cats = array();
		foreach ($this->cats as $cat_row)
		{	$cats[] = $this->InputSafeString($cat_row['lcname']);
		}
		return implode(', ', $cats);
	} // end of fn CatsList
	
	public function ViewedTable()
	{	if ($this->id)
		{	$times = array('Last day'=>'-1 day', 'Last week'=>'-1 week', 'Last month'=>'-1 month', 'All views'=>'');
			ob_start();
			echo '<table style="width: 200px; margin: 10px 0px 0px 200px"><tr><th colspan="2">Number of times viewed</th></tr>';
			foreach ($times as $text=>$datestring)
			{	echo '<tr><td>', $text, '</td><td>', $this->ViewCount($datestring), '</td></tr>';
			}
			echo '</table>';
			return ob_get_clean();
		}
	} // end of fn ViewedTable
	
	public function AdminDescription()
	{	if ($this->id)
		{	return $this->MediaType() . ': ' . $this->InputSafeString($this->details['mmname']);
		} else
		{	return 'none';
		}
	} // end of fn AdminDescription
	
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
		echo '<table><tr class="newlink"><th colspan="4"><a onclick="InstructorsPopUp(', $this->id, ');">Add person to multimedia</a></th></tr><tr><th></th><th>Name</th><th>Live?</th><th>Actions</th></tr>';
		foreach ($this->GetPeople() as $inid=>$instructor_row)
		{	$instructor = new AdminInstructor($instructor_row);
			echo '<tr><td>';
			if (file_exists($instructor->GetImageFile('thumbnail')))
			{	echo '<img height="50px" src="', $instructor->GetImageSRC('thumbnail'), '" />';
			} else
			{	echo 'no photo';
			}
			echo '</td><td>', $this->InputSafeString($instructor->GetFullName()), '</td><td>', $instructor->details['live'] ? 'Yes' : '', '</td><td><a onclick="InstructorRemove(', $this->id, ',', $instructor->id, ');">remove from multimedia</a>&nbsp;|&nbsp;<a href="instructoredit.php?id=', $instructor->id, '">view instructor</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn PeopleListTable
	
	public function AddInstructor($inid = 0)
	{	if (((!$people = $this->GetPeople()) || !$people[$inid]) && ($inst = new Instructor($inid)) && $inst->id)
		{	$sql = 'INSERT INTO multimediapeople SET mmid=' . $this->id . ', inid=' . $inst->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		} else echo 'problem';
	} // end of fn AddInstructor
	
	public function RemoveInstructor($inid = 0)
	{	if (($people = $this->GetPeople()) && $people[$inid])
		{	$sql = 'DELETE FROM multimediapeople WHERE mmid=' . $this->id . ' AND inid=' . $inid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			}
		}
	} // end of fn RemoveInstructor
	
	public function GetAuthorText()
	{	$by = array();
		if ($people = $this->GetPeople())
		{	foreach ($people as $inst_row)
			{	$inst = new Instructor($inst_row);
				$by[] = '<a href="instructoredit.php?id=' . $inst->id . '">' . $this->InputSafeString($inst_row['instname']) . '</a>';
			}
		}
		return implode(', ', $by);
	} // fn GetAuthorText
	
	public function GetInstructorsUsing()
	{	$instructors = array();
		$sql = 'SELECT instructors.* FROM instructors, instructors_mm WHERE instructors.inid=instructors_mm.inid AND instructors_mm.mmid=' . $this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$instructors[$row['inid']] = $row;
			}
		}
		return $instructors;
	} // end of fn GetInstructorsUsing
	
	public function GetCoursesUsing()
	{	$courses = array();
		$sql = 'SELECT coursecontent.* FROM coursecontent, courses_mm WHERE coursecontent.ccid=courses_mm.cid AND courses_mm.mmid=' . $this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$courses[$row['ccid']] = $row;
			}
		}
		return $courses;
	} // end of fn GetCoursesUsing
	
	public function ListInstructorsUsing()
	{	ob_start();
		if ($instructors = $this->GetInstructorsUsing())
		{	//$this->VarDump($instructors);
			echo '<h3>Instructors using this multimedia</h3><table style="width: 500px; margin: 0px;"><tr><th>Instructor</th><th>Actions</th></tr>';
			foreach ($instructors as $instructors_row)
			{	$course = new AdminCourseContent($course_row);
				echo '<tr><td>', $this->InputSafeString($instructors_row['instname']), '</td><td><a href="instructoredit.php?id=', $instructors_row['inid'], '">view</a></td></tr>';
			}
			echo '</table>';
		}
		return ob_get_clean();
	} // end of fn ListInstructorsUsing
	
	public function ListCoursesUsing()
	{	ob_start();
		if ($courses = $this->GetCoursesUsing())
		{	echo '<h3>Courses using this multimedia</h3><table style="width: 500px; margin: 0px;"><tr><th>Course</th><th>Actions</th></tr>';
			foreach ($courses as $course_row)
			{	$course = new AdminCourseContent($course_row);
				echo '<tr><td>', $this->InputSafeString($course->details['ctitle']), '</td><td><a href="coursecontentedit.php?id=', $course->id, '">view</a></td></tr>';
			}
			echo '</table>';
		}
		return ob_get_clean();
	} // end of fn ListCoursesUsing
	
} // end of defn AdminMultimedia
?>
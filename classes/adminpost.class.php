<?php
class AdminPost extends Post
{	
	public function __construct($id)
	{	parent::__construct($id);
	} // fn __construct
	
	public function InputForm()
	{	
		if ($data = $this->details)
		{	$data['pdate_time'] = substr($data['pdate'], 11, 5);
		} else
		{	if ($data = $_POST)
			{	if (($d = (int)$data['dpdate']) && ($m = (int)$data['mpdate']) && ($y = (int)$data['ypdate']))
				{	$data['pdate'] = $this->datefn->SQLDate(mktime(0,0,0,$m,$d,$y));
				}
			} else
			{	$data = array('pdate'=>$this->datefn->SQLDate(), 'pdate_time'=>date('H:i'));
			}
		}
		
		ob_start();
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'set_edit');
		
		//$form->AddHiddenInput('setid', (int)$setid);
		$form->AddSelect('Post type', 'ptype', $data['ptype'], '', $this->PTypesForDropdown(), true, false);
		$form->AddSelect('Category', 'catid', $data['catid'], '', $this->CatsForDropdown(), true, false);
		$form->AddTextInput('Title', 'ptitle', $this->InputSafeString($data['ptitle']), 'long', 255);
		if ($this->id)
		{	$form->AddTextInput('Slug', 'pslug', $this->InputSafeString($data['pslug']), 'long', 255, 1);
		}
		$form->AddTextInput('Author text', 'authortext', $this->InputSafeString($data['authortext']), 'long', 255);
		$form->AddCheckBox('Live', 'live', '1', $data['live']);
		if ($this->details['live'])
		{	$form->AddRawText('<p><label>&nbsp;</label><a href="' . $this->link->GetPostLink($this) . '" target="_blank">view in front-end</a><br class="clear" /></p>');
		}
		$form->AddTextInput('Slogan', 'pslogan', $this->InputSafeString($data['pslogan']), 'long', 255);
		$form->AddTextArea('Post content', 'pcontent', $this->InputSafeString($data['pcontent']), 'tinymce', 0, 0, 20, 60);
		$form->AddCheckBox('Show social media links?', 'socialbar', 1, $data['socialbar']);
		$form->AddCheckBox('Allow comments', 'pallowcomments', '1', $data['pallowcomments']);
		$form->AddMultiInput('Posted on', array(
						array('type'=>'DATE', 'name'=>'pdate', 'value'=>$data['pdate']), 
						array('type'=>'TEXT', 'name'=>'pdate_time', 'value'=>$data['pdate_time'], 'css'=>'short', 'maxlength'=>5)), 
					true);
		$form->AddFileUpload('Image (will be reduced to ' . $this->imagesizes['default'][0] . 'px &times; ' . $this->imagesizes['default'][1] . 'px and a thumbnail will be created for you)', 'imagefile');
		if ($this->id && ($image = $this->HasImage('thumbnail')))
		{	$form->AddRawText('<p><label>Current image</label><img src="' . $image . '" /><br /></p>');
			$form->AddCheckBox('Delete this', 'delposter');
		}
		
		$form->AddSubmitButton('', $this->id ? 'Save changes' : 'Create new post', 'submit');
		
		if ($this->CanDelete())
		{	echo '<p><a href="postedit.php?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you want to ' : '', 'delete this post</a></p>';
		}
		
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	public function PTypesForDropdown()
	{	$ptypes = array();
		foreach ($this->types as $typename=>$ptype)
		{	$ptypes[$typename] = $typename;
		}
		return $ptypes;
	} // end of fn PTypesForDropdown
	
	public function CatsForDropdown()
	{	$cats = array();
		$sql = 'SELECT * FROM postcategories ORDER BY ctitle';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$cats[$row['cid']] = $row['ctitle'];
			}
		}
		return $cats;
	} // end of fn CatsForDropdown
	
	private function ValidSlug($slug = '')
	{	$rawslug = $slug = $this->TextToSlug($slug);
		while ($this->SlugExists($slug))
		{	$slug = $rawslug . ++$count;
		}
		return $slug;
	} // end of fn ValidSlug
	
	private function SlugExists($slug = '')
	{	$sql = 'SELECT pid FROM posts WHERE pslug="' . $slug . '"';
		if ($this->id)
		{	$sql .= ' AND NOT pid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['pid'];
			}
		}
		return false;
	} // end of fn SlugExists
	
	public function Save($data = array(), $imagefile = array())
	{
		$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($this->types[$ptype = $data['ptype']])
		{	$fields[] = 'ptype="' . $ptype . '"';
			if ($this->id && ($data['coverview'] != $this->details['coverview']))
			{	$admin_actions[] = array('action'=>'Post type', 'actionfrom'=>$this->details['ptype'], 'actionto'=>$data['ptype']);
			}
		} else
		{	$fail[] = 'you must choose a post type';
		}
		
		if ($ptitle = $this->SQLSafe($data['ptitle']))
		{	$fields[] = 'ptitle="' . $ptitle . '"';
			if ($this->id && ($data['ptitle'] != $this->details['ptitle']))
			{	$admin_actions[] = array('action'=>'Post title', 'actionfrom'=>$this->details['ptitle'], 'actionto'=>$data['ptitle']);
			}
		} else
		{	$fail[] = 'post title missing';
		}
	
		if ($pslug = $this->ValidSlug(($this->id && $data['pslug']) ? $data['pslug'] : $ptitle))
		{	$fields[] = 'pslug="' . $pslug . '"';
			if ($this->id && ($data['pslug'] != $this->details['pslug']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['pslug'], 'actionto'=>$data['pslug']);
			}
		} else
		{	$fail[] = 'slug missing';
		}
		
		$authortext = $this->SQLSafe($data['authortext']);
		$fields[] = 'authortext="' . $authortext . '"';
		if ($this->id && ($data['authortext'] != $this->details['authortext']))
		{	$admin_actions[] = array('action'=>'Author text', 'actionfrom'=>$this->details['authortext'], 'actionto'=>$data['authortext']);
		}
		
		$pslogan = $this->SQLSafe($data['pslogan']);
		$fields[] = 'pslogan="' . $pslogan . '"';
		if ($this->id && ($data['pslogan'] != $this->details['pslogan']))
		{	$admin_actions[] = array('action'=>'Post slogan', 'actionfrom'=>$this->details['pslogan'], 'actionto'=>$data['pslogan']);
		}
		
		$pcontent = $this->SQLSafe($data['pcontent']);
		$fields[] = 'pcontent="' . $pcontent . '"';
		if ($this->id && ($data['pcontent'] != $this->details['pcontent']))
		{	$admin_actions[] = array('action'=>'Post content', 'actionfrom'=>$this->details['pcontent'], 'actionto'=>$data['pcontent']);
		}
		
		$pallowcomments = $this->SQLSafe($data['pallowcomments']);
		$fields[] = 'pallowcomments="' . $pallowcomments . '"';
		if ($this->id && ($data['pallowcomments'] != $this->details['pallowcomments']))
		{	$admin_actions[] = array('action'=>'Allow comments', 'actionfrom'=>$this->details['pallowcomments'], 'actionto'=>$data['pallowcomments']);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		} 
		
		$socialbar = ($data['socialbar'] ? '1' : '0');
		$fields[] = 'socialbar=' . $socialbar;
		if ($this->id && ($socialbar != $this->details['socialbar']))
		{	$admin_actions[] = array('action'=>'Social bar?', 'actionfrom'=>$this->details['socialbar'], 'actionto'=>$socialbar, 'actiontype'=>'boolean');
		}
		
		if ($catid = (int)$data['catid'])
		{	$cats = $this->CatsForDropdown();
			if ($cats[$catid])
			{	$fields[] = 'catid=' . $catid;
				if ($this->id && ($catid != $this->details['catid']))
				{	$admin_actions[] = array('action'=>'Category', 'actionfrom'=>$this->details['catid'], 'actionto'=>$catid);
				} 
			} else
			{	$fail[] = 'invalid category';
			}
		} else
		{	$fields[] = 'catid=0';
			if ($this->id && $this->details['catid'])
			{	$admin_actions[] = array('action'=>'Category', 'actionfrom'=>$this->details['catid'], 'actionto'=>$catid);
			} 
		}
	
		
		if ($this->id)
		{	
			if (($d = (int)$data['dpdate']) && ($m = (int)$data['mpdate']) && ($y = (int)$data['ypdate']))
			{	$pdate = $this->datefn->SQLDate(mktime(0, 0, 0, $m, $d, $y)) . " " . $this->StringToTime($data['pdate_time']) . ":00";
				$fields[] = 'pdate="' . $pdate . '"';
				if ($this->id && ($pdate != $this->details['pdate']))
				{	$admin_actions[] = array('action'=>'Posted', 'actionfrom'=>$this->details['pdate'], 'actionto'=>$pdate, 'actiontype'=>'datetime');
				}
			} else
			{	$fail[] = 'posted date is missing';
			}
		} else
		{	$fields[] = 'pdate=NOW()';
		}
	
		if($this->id || !$fail)
		{
			$set = implode(', ', $fields);
			
			if($this->id)
			{	$sql = 'UPDATE posts SET ' . $set . ' WHERE pid = '. (int)$this->id;
			} else
			{	$sql = 'INSERT INTO posts SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$base_parameters = array('tablename'=>'posts', 'tableid'=>$this->post->id, 'area'=>'posts');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New post created';
						$this->RecordAdminAction(array('tablename'=>'posts', 'tableid'=>$this->post->id, 'area'=>'posts', 'action'=>'created'));
					}
					$this->Get($this->id);
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'posts', 'tableid'=>$this->post->id, 'area'=>'posts');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
			} //else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		if ($this->id)
		{	if ($imagefile['size'])
			{	/*$uploaded = $this->UploadPhoto($imagefile);
				if ($uploaded['successmessage'])
				{	$success[] = $uploaded['successmessage'];
					$this->RecordAdminAction(array('tablename'=>'posts', 'tableid'=>$this->id, 'area'=>'posts', 'action'=>'New image uploaded'));
				}
				if ($uploaded['failmessage'])
				{	$fail[] = $uploaded['failmessage'];
				}*/
			
				//print_r($course_banner);
				if ((!stristr($imagefile['type'], 'jpeg') && !stristr($imagefile['type'], 'jpg') && !stristr($imagefile['type'], 'png')) || $imagefile['error'])
				{	$fail[] = 'error uploading image (jpegs, pngs only)';
				} else
				{	$photos_created = 0;
					foreach ($this->imagesizes as $size_name=>$size)
					{	
						if ($this->ReSizePhotoPNG($imagefile['tmp_name'], $this->GetImageFile($size_name), $size[0], $size[1], stristr($imagefile['type'], 'png') ? 'png' : 'jpg'))
						{	$photos_created++;
						}
					}
					@unlink($imagefile['tmp_name']);
					if ($photos_created)
					{	$success[] = 'image uploaded';
					}
				}
			} else
			{	if ($data['delposter'])
				{	$this->DeleteImages();
					$success[] = 'image deleted';
					$this->RecordAdminAction(array('tablename'=>'posts', 'tableid'=>$this->id, 'area'=>'posts', 'action'=>'Image deleted'));
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
	} // end of fn Save
	
	function UploadPhoto($file)
	{	$fail = array();
		$successmessage = "";

		if ($file['size'])
		{	if ((!stristr($file['type'], 'jpeg') && !stristr($file['type'], 'jpg') && !stristr($file['type'], 'png')) || $file['error'])
			{	$fail[] = 'File type invalid (jpeg or png only)';
			} else
			{	
				foreach ($this->types as $typename=>$size)
				{	
					if ($this->ReSizePhotoPNG($file['tmp_name'], $this->GetImageFile($typename), $size[0], $size[1], stristr($file['type'], 'png') ? 'png' : 'jpg'))
					{	$photos_created++;
					}
				}
				//$this->ReSizePhoto($file['tmp_name'], $this->ThumbFile(), $this->thumb_w, $this->thumb_h);
				unlink($file['tmp_name']);
				if ($photos_created)
				{	$successmessage = 'New image uploaded';
				}
			}
		} else
		{	$fail[] = 'image not uploaded';
		}
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>$successmessage);

	} // end of fn UploadPhoto
	
	public function DeleteExtra()
	{	$this->DeleteImages();
	} // end of fn DeleteExtra
	
	public function DeleteImages()
	{	foreach ($this->imagesizes as $sizename=>$size)
		{	@unlink($this->GetImageFile($sizename));
		}
	} // end of fn DeleteImages
	
	public function CanDelete()
	{	
		return $this->id && !$this->GetCommentCount();
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	$sql = "DELETE FROM posts WHERE pid = '". (int)$this->id."'";
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->db->Query("DELETE FROM comments WHERE pid='". (int)$this->id."'");					 
					$this->RecordAdminAction(array("tablename"=>"posts", "tableid"=>$this->id, "area"=>"posts", "action"=>"deleted"));
					$this->Reset();
					return true;
				}
			}
		}
	} // end of fn Delete
	
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
		echo '<table><tr class="newlink"><th colspan="4"><a onclick="InstructorsPopUp(', $this->id, ');">Add person to post</a></th></tr><tr><th></th><th>Name</th><th>Live?</th><th>Actions</th></tr>';
		foreach ($this->GetPeople() as $inid=>$instructor_row)
		{	$instructor = new AdminInstructor($instructor_row);
			echo '<tr><td>';
			if (file_exists($instructor->GetImageFile('thumbnail')))
			{	echo '<img height="50px" src="', $instructor->GetImageSRC('thumbnail'), '" />';
			} else
			{	echo 'no photo';
			}
			echo '</td><td>', $this->InputSafeString($instructor->GetFullName()), '</td><td>', $instructor->details['live'] ? 'Yes' : '', '</td><td><a onclick="InstructorRemove(', $this->id, ',', $instructor->id, ');">remove from post</a>&nbsp;|&nbsp;<a href="instructoredit.php?id=', $instructor->id, '">view instructor</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn PeopleListTable
	
	public function AddInstructor($inid = 0)
	{	if (((!$people = $this->GetPeople()) || !$people[$inid]) && ($inst = new Instructor($inid)) && $inst->id)
		{	$sql = 'INSERT INTO postinstructors SET pid=' . $this->id . ', inid=' . $inst->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		} else echo 'problem';
	} // end of fn AddInstructor
	
	public function RemoveInstructor($inid = 0)
	{	if (($people = $this->GetPeople()) && $people[$inid])
		{	$sql = 'DELETE FROM postinstructors WHERE pid=' . $this->id . ' AND inid=' . $inid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	return true;
				}
			}
		}
	} // end of fn RemoveInstructor
	
	public function GetAuthorDate()
	{	$by = array();
		if ($this->details['authortext'])
		{	$by[] = $this->InputSafeString($this->details['authortext']);
		}
		if ($people = $this->GetPeople())
		{	foreach ($people as $inst_row)
			{	$inst = new Instructor($inst_row);
				$by[] = '<a href="instructoredit.php?id=' . $inst->id . '">' . $this->InputSafeString($inst_row['instname']) . '</a>';
			}
		}
		return implode(', ', $by);
	} // fn GetAuthorDate
	
} // end of defn AdminPost
?>
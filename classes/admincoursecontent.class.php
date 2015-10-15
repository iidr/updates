<?php
class AdminCourseContent extends CourseContent
{	
	function __construct($id = 0)
	{	parent::__construct($id, false);
	} // fn __construct
	
	function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	if ($result = $this->db->Query('DELETE FROM coursecontent WHERE ccid=' . $this->id))
			{	if ($this->db->AffectedRows())
				{	$this->db->Query('DELETE FROM coursetocats WHERE courseid=' . $this->id);
					$this->db->Query('DELETE FROM courses WHERE ccid=' . $this->id);
					$this->RecordAdminAction(array('tablename'=>'coursecontent', 'tableid'=>$this->id, 'area'=>'coursecontent', 'action'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		}
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
	{	$sql = 'SELECT cid FROM coursecontent WHERE cslug="' . $slug . '"';
		if ($this->id)
		{	$sql .= ' AND NOT cid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['cid'];
			}
		}
		return false;
	} // end of fn SlugExists

	function Save($data = array(), $course_banner = array(), $course_image = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($ctitle = $this->SQLSafe($data['ctitle']))
		{	$fields[] = 'ctitle="' . $ctitle . '"';
			if ($this->id && ($data['ctitle'] != $this->details['ctitle']))
			{	$admin_actions[] = array('action'=>'Title', 'actionfrom'=>$this->details['ctitle'], 'actionto'=>$data['ctitle']);
			}
		} else
		{	$fail[] = 'title missing';
		}
	
		// create slug
		if ($cslug = $this->ValidSlug(($this->id && $data['cslug']) ? $data['cslug'] : $ctitle))
		{	$fields[] = 'cslug="' . $cslug . '"';
			if ($this->id && ($cslug != $this->details['cslug']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['cslug'], 'actionto'=>$data['cslug']);
			}
		} else
		{	if ($ctitle)
			{	$fail[] = 'slug missing';
			}
		}
		
		$cslogan = $this->SQLSafe($data['cslogan']);
		$fields[] = 'cslogan="' . $cslogan . '"';
		if ($this->id && ($data['cslogan'] != $this->details['cslogan']))
		{	$admin_actions[] = array('action'=>'Slogan', 'actionfrom'=>$this->details['cslogan'], 'actionto'=>$data['cslogan'], 'actiontype'=>'html');
		}
		
		$cshortoverview = $this->SQLSafe($data['cshortoverview']);
		$fields[] = 'cshortoverview="' . $cshortoverview . '"';
		if ($this->id && ($data['cshortoverview'] != $this->details['cshortoverview']))
		{	$admin_actions[] = array('action'=>'Short Overview', 'actionfrom'=>$this->details['cshortoverview'], 'actionto'=>$data['cshortoverview']);
		}
		
		$coverview = $this->SQLSafe($data['coverview']);
		$fields[] = 'coverview="' . $coverview . '"';
		if ($this->id && ($data['coverview'] != $this->details['coverview']))
		{	$admin_actions[] = array('action'=>'Overview', 'actionfrom'=>$this->details['coverview'], 'actionto'=>$data['coverview']);
		}
		
		$ctelephone = $this->SQLSafe($data['ctelephone']);
		$fields[] = 'ctelephone="' . $ctelephone . '"';
		if ($this->id && ($data['ctelephone'] != $this->details['ctelephone']))
		{	$admin_actions[] = array('action'=>'Telephone', 'actionfrom'=>$this->details['ctelephone'], 'actionto'=>$data['ctelephone']);
		}
		
		$cemail = $this->SQLSafe($data['cemail']);
		$fields[] = 'cemail="' . $cemail . '"';
		if ($this->id && ($data['cemail'] != $this->details['cemail']))
		{	$admin_actions[] = array('action'=>'Email', 'actionfrom'=>$this->details['cemail'], 'actionto'=>$data['cemail']);
		}
		
		if ($cvideo = (int)$data['cvideo'])
		{	if (($mm = new MultiMedia($cvideo)) && $mm->id)
			{	$fields[] = 'cvideo=' . $cvideo;
				if ($this->id && ($cvideo != $this->details['cvideo']))
				{	$admin_actions[] = array('action'=>'Video', 'actionfrom'=>$this->details['cvideo'], 'actionto'=>$cvideo);
				}
			} else
			{	$fail[] = 'Video not found';
			}
		} else
		{	$fields[] = 'cvideo=0';
			if ($this->details['cvideo'])
			{	$admin_actions[] = array('action'=>'Video', 'actionfrom'=>$this->details['cvideo'], 'actionto'=>'0');
			}
		}
		
		if ($this->types[$ctype = $data['ctype']])
		{	$fields[] = 'ctype="' . $ctype . '"';
			if ($this->id && ($ctype != $this->details['ctype']))
			{	$admin_actions[] = array('action'=>'Type', 'actionfrom'=>$this->details['ctype'], 'actionto'=>$ctype);
			}
		} else
		{	$fail[] = 'type missing';
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE coursecontent SET ' . $set . ' WHERE ccid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO coursecontent SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$base_parameters = array('tablename'=>'coursecontent', 'tableid'=>$this->id, 'area'=>'coursecontent');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New course content created';
						$this->RecordAdminAction(array('tablename'=>'coursecontent', 'tableid'=>$this->id, 'area'=>'coursecontent', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
			}
			
			if ($this->id && $course_banner['size'])
			{	//print_r($course_banner);
				if ((!stristr($course_banner['type'], 'jpeg') && !stristr($course_banner['type'], 'jpg') && !stristr($course_banner['type'], 'png')) || $course_banner['error'])
				{	$fail[] = 'error uploading banner (jpegs, pngs only)';
				} else
				{	$photos_created = 0;
					if (!file_exists($this->ImageFileDirectory('banner')))
					{	mkdir($this->ImageFileDirectory('banner'));
					}
					if ($this->ReSizePhotoPNG($course_banner['tmp_name'], $this->GetImageFile('banner'), $this->imagesizes['banner'][0], $this->imagesizes['banner'][1], stristr($course_banner['type'], 'png') ? 'png' : 'jpg'))
					{	$success[] = 'course banner uploaded';
					}
					unset($course_banner['tmp_name']);
				}
			}
			
			if ($this->id && $course_image['size'])
			{	//print_r($course_image);
				if ((!stristr($course_image['type'], 'jpeg') && !stristr($course_image['type'], 'jpg') && !stristr($course_image['type'], 'png')) || $course_image['error'])
				{	$fail[] = 'error uploading banner (jpegs, pngs only)';
				} else
				{	$photos_created = 0;
					foreach ($this->imagesizes as $size_name=>$size)
					{	if ($size_name != 'banner')
						{	if (!file_exists($this->ImageFileDirectory($size_name)))
							{	mkdir($this->ImageFileDirectory($size_name));
							}
							if ($this->ReSizePhotoPNG($course_image['tmp_name'], $this->GetImageFile($size_name), $size[0], $size[1], stristr($course_image['type'], 'png') ? 'png' : 'jpg'))
							{	$photos_created++;
							}
						}
					}
					unlink($course_image['tmp_name']);
					if ($photos_created)
					{	$success[] = 'course image uploaded';
					}
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	function InputForm()
	{	
		ob_start();
		
		if (!$data = $this->details)
		{	$data = $_POST;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$this->AddBackLinkHiddenField($form);
		$form->AddTextInput('Course title', 'ctitle', $this->InputSafeString($data['ctitle']), 'long', 255, 1);
		$form->AddTextInput('Slug (for url)', 'cslug', $this->InputSafeString($data['cslug']), 'long', 255);
		$form->AddSelect('Type', 'ctype', $data['ctype'], '', $this->types, false, true);
		$form->AddTextInput('Slogan', 'cslogan', $this->InputSafeString($data['cslogan']), 'long', 255);
		
		$form->AddTextArea('Short Overview', 'cshortoverview', $this->InputSafeString($data['cshortoverview']), '', 0, 0, 3, 60);
		$form->AddTextArea('Overview', 'coverview', $this->InputSafeString($data['coverview']), 'tinymce', 0, 0, 20, 60);
		$form->AddTextInput('Telephone', 'ctelephone', $this->InputSafeString($data['ctelephone']), "", 255);
		$form->AddTextInput('Email', 'cemail', $this->InputSafeString($data['cemail']), 'long', 255);
		
		$form->AddRawText('<h3>Upload images:</h3>');
		
		$form->AddFileUpload('Banner (' . $this->imagesizes['banner'][0] . ' &times; ' . $this->imagesizes['banner'][1] . 'px):', 'bannerfile');
		if ($src = $this->HasImage('banner'))
		{	$form->AddRawText('<label>Current banner</label><img src="' . $src . '?' . time() . '" height="200px" /><br />');
		}
		$form->AddFileUpload('Small Banner (' . $this->imagesizes['default'][0] . ' &times; ' . $this->imagesizes['default'][1] . 'px):', 'imagefile');
		if ($src = $this->HasImage('default'))
		{	$form->AddRawText('<label>Current small banner</label><img src="' . $src . '?' . time() . '" height="200px" /><br />');
		}
		
		ob_start();
		echo '<h3>Video for course page:</h3><label id="cvideoPicked">', ($data['cvideo'] && ($mm = new MultiMedia($data['cvideo'])) && $mm->id) ? $this->InputSafeString($mm->details['mmname']) : 'none','</label><input type="hidden" name="cvideo" id="cvideoValue" value="', (int)$data['cvideo'], '" /><span class="dataText"><a onclick="CVideoPicker();">change this</a></span><br />';
		$form->AddRawText(ob_get_clean());
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Course', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this course content</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('coursescontent', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		echo $this->CVideoPickerPopUp();
		return ob_get_clean();
	} // end of fn InputForm

	public function CVideoPickerPopUp()
	{	ob_start();
		echo '<script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#cvpp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="cvpp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="cvppModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn CVideoPickerPopUp
	
	public function GetMultiMedia()
	{	return parent::GetMultiMedia(false);
	} // end of fn GetMultiMedia
	
	public function ReviewsDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->ReviewsTable(), '</div><script type="text/javascript">courseID=', $this->id, ';$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner"></div></div></div>';
		return ob_get_clean();
	} // end of fn ReviewsDisplay
	
	public function ReviewsTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a href="coursereview.php?pid=', $this->id, '">Create new review</a></th></tr><tr><th>Created by</th><th>Reviewer displayed</th><th>Date</th><th>Review</th><th>Status</th><th>Admin notes</th><th>Actions</th></tr>';
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
			echo '</td><td>', $this->InputSafeString($review->details['reviewertext']), '</td><td>', date('d/m/y @H:i', strtotime($review->details['revdate'])), '</td><td>', $review->details['revtitle'] ? ('<strong>' . $this->InputSafeString($review->details['revtitle']) . '</strong><br />') : '', nl2br($this->InputSafeString($review->details['review'])), '</td><td>', $review->StatusString(), '</td><td>', nl2br($this->InputSafeString($review->details['adminnotes'])), '</td><td><a href="coursereview.php?id=', $review->id, '">edit</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ReviewsTable
	
	public function CategoriesDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->CategoriesTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn CategoriesDisplay
	
	public function CategoriesTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="2"><a onclick="CourseCatPopUp(', $this->id, ');">Add category</a></th></tr><tr><th>Category added</th><th>Actions</th></tr>';
		foreach ($this->cats as $cat_row)
		{	$cat = new AdminCourseCategory($cat_row);
			echo '<tr><td class="pagetitle">', $cat->CascadedName(), '</td><td><a href="coursecatedit.php?id=', $cat->id, '">edit</a>&nbsp;|&nbsp;<a onclick="CourseCatRemove(', $this->id, ',', $cat->id, ');">remove from course</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn CategoriesTable
	
	public function AddCategory($catid = 0)
	{	if ($this->id && !$this->cats[$catid] && ($cat = new CourseCategory($catid)) && $cat->id)
		{	$sql = 'INSERT INTO coursetocats SET courseid=' . $this->id . ', catid=' . $cat->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetCategories();
					return true;
				}
			}
		}
	} // end of fn AddMultimedia
	
	public function RemoveCategory($catid = 0)
	{	if ($this->cats[$catid])
		{	$sql = 'DELETE FROM coursetocats WHERE courseid=' . $this->id . ' AND catid=' . $catid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetCategories();
					return true;
				}
			}
		}
	} // end of fn RemoveCategory
	
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
			echo '</td><td class="pagetitle">', $this->InputSafeString($mm->details['mmname']), '</td><td>', $mm->MediaType(), '</td><td>', $mm->CatsList(), '</td><td>', $mm->details['live'] ? 'Yes' : 'No', '</td><td>', date('d-M-y @H:i', strtotime($mm->details['posted'])), $mm->details['author'] ? ('<br />by ' . $this->InputSafeString($mm->details['author'])) : '', '</td><td><a href="multimedia.php?id=', $mm->id, '">edit</a>&nbsp;|&nbsp;<a onclick="MultiMediaRemove(', $this->id, ',', $mmid, ');">remove from course</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn MultiMediaTable
	
	public function AddMultimedia($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if ((!$mmlist = $this->GetMultiMedia()) || !$mmlist[$mmid])
			{	$sql = 'INSERT INTO courses_mm SET cid=' . $this->id . ', mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn AddMultimedia
	
	public function RemoveMultimedia($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if (($mmlist = $this->GetMultiMedia()) && $mmlist[$mmid])
			{	$sql = 'DELETE FROM courses_mm WHERE cid=' . $this->id . ' AND mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn RemoveMultimedia
	
	public function HeaderInfo(){	
		return $this->InputSafeString($this->details['ctitle'].' (Product Code: CE'. $this->content['id'].')');
	} // end of fn HeaderInfo
	
	public function ScheduleListing()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="5"><a href="courseedit.php?ccid=', $this->id, '">Schedule new course</a></th></tr><tr><th>Schedule ID</th><th>Dates</th><th>Venue</th><th>Stock Control</th><th>Actions</th></tr>';
		foreach ($this->courses as $course_row)
		{	
			if($course_row['cvenue']> 0 && ($course_row['starttime']!='0000-00-00' || $course_row['endtime']!='0000-00-00')){
				$course = new AdminCourse($course_row);
				echo '<tr><td>', $course->id, '</td><td>', $course->DisplayDates(), '</td><td>', $course->GetVenue()->GetAddress(), '</td><td>', $course->StockControlText(), '</td><td><a href="courseedit.php?id=', $course->id, '">edit</a></td></tr>';
			}
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn ScheduleListing
	
} // end of defn AdminCourseContent
?>
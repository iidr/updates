<?php
class AdminAskImamTopic extends AskImamTopic
{	
	public function __construct($id = null)
	{	parent::__construct($id);
	} // fn __construct
	
	public function GetQuestions()
	{	parent::GetQuestions(false);
	} // end of fn GetQuestions
	
	public function GetInstructors()
	{	parent::GetInstructors(false);
	} // end of fn GetInstructors
	
	function InputForm()
	{	ob_start();
		
		$startyear = date('Y') + (2000-date("Y"));
		$endyear = date('Y') - (date("Y")-2025);
		
		if ($this->id)
		{	$data = $this->details;
			
			if (($starttimeyear = date('Y', strtotime($this->details['startdate']))) < $startyear)
			{	$startyear = $starttimeyear;
			}
			if ($starttimeyear > $endyear)
			{	$endyear = $starttimeyear;
			}
		} else
		{	$data = $_POST;
			
			if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
			{	$data['startdate'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
		}
		
		$years = array();
		for ($y = $startyear; $y <= $endyear; $y++)
		{	$years[] = $y;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$form->AddTextInput('Theme title', 'title', $this->InputSafeString($data['title']), 'long', 255, 1);
		if ($this->id)
		{	$form->AddTextInput('Slug (for url)', 'catslug', $this->InputSafeString($data['slug']), 'long', 255, 1);
		}
		$form->AddDateInput('Start date', 'start', $data['startdate'], $years, 0, 0, true, true, date('Y'));
		
		$form->AddCheckBox('Live (in front-end)', 'live', '1', $data['live']);
		$form->AddCheckBox('Closed (no more questions)', 'closed', '1', $data['closed']);
		$form->AddTextInput('"Answered by" text', 'anstext', $this->InputSafeString($data['anstext']), 'long', 255, 1);
		
		$form->AddFileUpload('Image (thumbnail will be created for you)', 'imagefile');
		if (file_exists($this->GetImageFile('thumbnail')))
		{	$form->AddRawText('<p><label>Current image</label><img src="' . $this->GetImageSRC('thumbnail') . '" /><br /></p>');
			$form->AddCheckBox('Delete this', 'delphoto');
		}
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Topic', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this topic</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('askimamtopics', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	private function ValidSlug($slug = '')
	{	$rawslug = $slug = $this->TextToSlug($slug);
		while ($this->SlugExists($slug))
		{	$slug = $rawslug . ++$count;
		}
		return $slug;
	} // end of fn ValidSlug
	
	private function SlugExists($slug = '')
	{	$sql = 'SELECT askid FROM askimamtopics WHERE slug="' . $slug . '"';
		if ($this->id)
		{	$sql .= ' AND NOT askid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['askid'];
			}
		}
		return false;
	} // end of fn SlugExists

	function Save($data = array(), $imagefile = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($title = $this->SQLSafe($data['title']))
		{	$fields[] = 'title="' . $title . '"';
			if ($this->id && ($data['title'] != $this->details['title']))
			{	$admin_actions[] = array('action'=>'Title', 'actionfrom'=>$this->details['title'], 'actionto'=>$data['title']);
			}
		} else
		{	$fail[] = 'title missing';
		}
	
		// create slug
		if ($slug = $this->ValidSlug(($this->id && $data['slug']) ? $data['slug'] : $title))
		{	$fields[] = 'slug="' . $slug . '"';
			if ($this->id && ($slug != $this->details['slug']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['slug'], 'actionto'=>$data['slug']);
			}
		} else
		{	if ($title)
			{	$fail[] = 'slug missing';
			}
		}
		
		// start date and time
		if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
		{	$startdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			$fields[] = 'startdate="' . $startdate . '"';
			if ($this->id && ($startdate != $this->details['startdate']))
			{	$admin_actions[] = array('action'=>'Start date', 'actionfrom'=>$this->details['startdate'], 'actionto'=>$startdate, 'actiontype'=>'datetime');
			}
		} else
		{	$fail[] = 'start date missing';
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$closed = ($data['closed'] ? '1' : '0');
		$fields[] = 'closed=' . $closed;
		if ($this->id && ($closed != $this->details['closed']))
		{	$admin_actions[] = array('action'=>'Closed?', 'actionfrom'=>$this->details['closed'], 'actionto'=>$closed, 'actiontype'=>'boolean');
		}
		
		$anstext = $this->SQLSafe($data['anstext']);
		$fields[] = 'anstext="' . $anstext . '"';
		if ($this->id && ($data['anstext'] != $this->details['anstext']))
		{	$admin_actions[] = array('action'=>'"Answered by" text', 'actionfrom'=>$this->details['anstext'], 'actionto'=>$data['anstext']);
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE askimamtopics SET ' . $set . ' WHERE askid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO askimamtopics SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New topic created';
						$this->RecordAdminAction(array('tablename'=>'askimamtopics', 'tableid'=>$this->id, 'area'=>'askimamtopics', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'askimamtopics', 'tableid'=>$this->id, 'area'=>'askimamtopics');
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
			{	$uploaded = $this->UploadPhoto($imagefile);
				if ($uploaded['successmessage'])
				{	$success[] = $uploaded['successmessage'];
					$this->RecordAdminAction(array('tablename'=>'askimamtopics', 'tableid'=>$this->id, 'area'=>'askimamtopics', 'action'=>'New image uploaded'));
				}
				if ($uploaded['failmessage'])
				{	$fail[] = $uploaded['failmessage'];
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
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	function UploadPhoto($file)
	{
		$fail = array();
		$successmessage = '';
		
		if($file['size'])
		{
			if((!stristr($file['type'], 'jpeg') && !stristr($file['type'], 'jpg') && !stristr($file['type'], 'png')) || $file['error'])
			{
				$fail[] = 'File type invalid (jpeg or png only)';
			} else
			{	foreach ($this->imagesizes as $sizename=>$size)
				{	$this->ReSizePhoto($file['tmp_name'], $this->GetImageFile($sizename), $size[0], $size[1]);
				}
				unlink($file['tmp_name']);
				
				$successmessage = 'New photo uploaded';
			}
		} else
		{
			$fail[] = 'Photo not uploaded';	
		}
		
		return array("failmessage"=>implode(", ", $fail), "successmessage"=>$successmessage);
	} // end of fn UploadPhoto
	
	public function DeletePhotos()
	{	foreach ($this->imagesizes as $sizename=>$size)
		{	@unlink($this->GetImageFile($sizename));
		}
	} // end of fn DeletePhotos

	public function QuestionsList()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="5"><a href="askimamquestion.php?topicid=', $this->id, '">Create new question for this theme</a></th></tr><tr><th>Question</th><th>Live?</th><th>Closed?</th><th>Display order</th><th>Actions</th></tr>';
		foreach ($this->questions as $question_row)
		{	$question = new AdminAskImamQuestion($question_row);
			echo '<tr><td>', $this->InputSafeString($question->details['qtext']), '</td><td>', $question->details['live'] ? 'live' : '', '</td><td>', $question->details['closed'] ? 'closed' : '', '</td><td>', (int)$question->details['listorder'], '</td><td><a href="askimamquestion.php?id=', $question->id, '">edit</a>';
			if ($question->CanDelete())
			{	echo '&nbsp;|&nbsp;<a href="askimamquestion.php?id=', $question->id, '&delete=1">delete</a>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn QuestionsList
	
	public function InstructorListContainer()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->InstructorListTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn InstructorListContainer
	
	public function InstructorListTable()
	{	ob_start();
		echo '<form action="askimaminstructors.php?id=', $this->id, '" method="post"><table><tr class="newlink"><th colspan="5"><a onclick="InstructorsPopUp(', $this->id, ');">Add instructor</a></th></tr><tr><th></th><th>Name</th><th>Live?</th><th>List order</th><th>Actions</th></tr>';
		foreach ($this->instructors as $inid=>$instructor_row)
		{	$instructor = new Instructor($instructor_row);
			echo '<tr><td>';
			if (file_exists($instructor->GetImageFile('thumbnail')))
			{	echo '<img height="50px" src="', $instructor->GetImageSRC('thumbnail'), '" />';
			} else
			{	echo 'no photo';
			}
			echo '</td><td>', $this->InputSafeString($instructor->GetFullName()), '</td><td>', $instructor->details['live'] ? 'Yes' : '', '</td><td><input type="text" name="listorder[', $inid, ']" value="', (int)$instructor->details['cilistorder'], '" class="number" /></td><td><a onclick="InstructorRemove(', $this->id, ',', $instructor->id, ');">remove from theme</a>&nbsp;|&nbsp;<a href="instructoredit.php?id=', $instructor->id, '">edit</a></td></tr>';
		}
		if ($this->instructors)
		{	echo '<tr><td colspan="3"></td><td><input type="submit" class="submit" value="Save order" /></td><td></td></tr>';
		}
		echo '</table></form>';
		return ob_get_clean();
	} // end of fn InstructorListTable
	
	public function AddInstructor($inid = 0)
	{	if (!$this->instructors[$inid] && ($inst = new Instructor($inid)) && $inst->id)
		{	$sql = 'INSERT INTO askimaminstructors SET askid=' . $this->id . ', inid=' . $inst->id . ', listorder=' . $this->NextInstListOrder();;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetInstructors();
					return true;
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		} else echo 'problem';
	} // end of fn AddInstructor
	
	public function NextInstListOrder()
	{	$lastlistorder = 0;
		foreach ($this->instructors as $inst)
		{	if ($inst['cilistorder'] > $lastlistorder)
			{	$lastlistorder = $inst['cilistorder'];
			}
		}
		return $lastlistorder + 10;
	} // end of fn NextInstListOrder
	
	public function RemoveInstructor($inid = 0)
	{	if ($this->instructors[$inid])
		{	$sql = 'DELETE FROM askimaminstructors WHERE askid=' . $this->id . ' AND inid=' . $inid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetInstructors();
					return true;
				}
			}
		}
	} // end of fn RemoveInstructor
	
	public function CategoriesDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->CategoriesTable(), '</div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn CategoriesDisplay
	
	public function CategoriesTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="2"><a onclick="CourseCatPopUp(', $this->id, ');">Add topic</a></th></tr><tr><th>Topics added</th><th>Actions</th></tr>';
		foreach ($this->cats as $cat_row)
		{	$cat = new AdminCourseCategory($cat_row);
			echo '<tr><td class="pagetitle">', $cat->CascadedName(), '</td><td><a href="coursecatedit.php?id=', $cat->id, '">edit</a>&nbsp;|&nbsp;<a onclick="CourseCatRemove(', $this->id, ',', $cat->id, ');">remove from theme</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn CategoriesTable
	
	public function AddCategory($catid = 0)
	{	if ($this->id && !$this->cats[$catid] && ($cat = new CourseCategory($catid)) && $cat->id)
		{	$sql = 'INSERT INTO askimamtocats SET askid=' . $this->id . ', catid=' . $cat->id;
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
		{	$sql = 'DELETE FROM askimamtocats WHERE askid=' . $this->id . ' AND catid=' . $catid;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->GetCategories();
					return true;
				}
			}
		}
	} // end of fn RemoveCategory

	public function SaveInstListOrder($saveorder = array())
	{	$changed = 0;
		foreach($saveorder as $inid=>$listorder)
		{	if ($this->instructors[$inid] && ($listorder == (int)$listorder))
			{	$sql = 'UPDATE askimaminstructors SET listorder=' . (int)$listorder . ' WHERE askid=' . $this->id . ' AND inid=' . $inid;
				if ($result = $this->db->Query($sql))
				{	if ($this->db->AffectedRows())
					{	$changed++;
					}
				}
			}
		}
		if ($changed)
		{	$this->GetInstructors();
		}
		return $changed;
	} // end of fn SaveInstListOrder
	
} // end of class AdminAskImamTopic
?>
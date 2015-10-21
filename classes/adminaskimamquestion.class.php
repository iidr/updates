<?php
class AdminAskImamQuestion extends AskImamQuestion
{	
	public function __construct($id = null)
	{	parent::__construct($id);
	} // fn __construct
	
	public function AdminTitle()
	{	if ($this->id)
		{	return '#' . $this->id . ': "' . $this->InputSafeString(substr($this->details['qtext'], 0, 20)) . '"';
		}
	} // end of fn AdminTitle
	
	public function GetTopic($admin = true)
	{	if ($admin)
		{	return new AdminAskImamTopic($this->details['askid']);
		} else
		{	return parent::GetTopic();
		}
	} // end of fn GetTopic
	
	function InputForm($askid = 0)
	{	ob_start();
		
		if (!$data = $this->details)
		{	$data = $_POST;
		}
		
		if ($askid = (int)$askid)
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id . '&topicid=' . (int)$askid, 'course_edit');
		} else
		{	$form = new Form($_SERVER['SCRIPT_NAME'], 'course_edit');
			$themes = array();
			$sql = 'SELECT * FROM askimamtopics ORDER BY startdate DESC';
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$themes[$row['askid']] = $this->InputSafeString($row['title']) . ' - ' . date('d-M-y', strtotime($row['startdate']));
				}
			}
			//$this->VarDump($themes);
			$form->AddSelect('Theme', 'topicid', '', '', $themes, true);
		}
		$form->AddTextArea('Question', 'qtext', $this->InputSafeString($data['qtext']), '', 0, 0, 2, 60);
		$form->AddTextArea('Answer', 'qanswer', $this->InputSafeString($data['qanswer']), 'tinymce', 0, 0, 20, 60);
		
		$form->AddTextInput('List order', 'listorder', (int)$data['listorder'], 'short num', 6);
		$form->AddCheckBox('Live (in front-end)', 'live', '1', $data['live']);
		$form->AddCheckBox('Closed (no more questions)', 'closed', '1', $data['closed']);
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Question', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this question</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('askimamquestions', $this->id))
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
	{	$sql = 'SELECT qid FROM askimamquestions WHERE slug="' . $slug . '"';
		if ($this->id)
		{	$sql .= ' AND NOT qid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['cid'];
			}
		}
		return false;
	} // end of fn SlugExists

	function Save($data = array(), $askid = 0)
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if (!$askid)
		{	$askid = (int)$data['topicid'];
		}
		if (!$this->id)
		{	if (($topic = new AskImamTopic($askid)) && $topic->id)
			{	$fields[] = 'askid=' . $topic->id;
			} else
			{	$fail[] = 'topic not found';
			}
		}
		
		if ($qtext = $this->SQLSafe($data['qtext']))
		{	$fields[] = 'qtext="' . $qtext . '"';
			if ($this->id && ($data['qtext'] != $this->details['qtext']))
			{	$admin_actions[] = array('action'=>'Question', 'actionfrom'=>$this->details['qtext'], 'actionto'=>$data['qtext']);
			}
		} else
		{	$fail[] = 'title missing';
		}
	
		// create slug
/*		if ($slug = $this->ValidSlug(($this->id && $data['slug']) ? $data['slug'] : $title))
		{	$fields[] = 'slug="' . $slug . '"';
			if ($this->id && ($slug != $this->details['slug']))
			{	$admin_actions[] = array('action'=>'Slug', 'actionfrom'=>$this->details['slug'], 'actionto'=>$data['slug']);
			}
		} else
		{	if ($title)
			{	$fail[] = 'slug missing';
			}
		}*/
		
		$qanswer = $this->SQLSafe($data['qanswer']);
		$fields[] = 'qanswer="' . $qanswer . '"';
		if ($this->id && ($data['qanswer'] != $this->details['qanswer']))
		{	$admin_actions[] = array('action'=>'Answer', 'actionfrom'=>$this->details['qanswer'], 'actionto'=>$data['qanswer']);
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
		
		$listorder = (int)$data['listorder'];
		$fields[] = 'listorder=' . $listorder;
		if ($this->id && ($listorder != $this->details['listorder']))
		{	$admin_actions[] = array('action'=>'Order', 'actionfrom'=>$this->details['listorder'], 'actionto'=>$listorder);
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE askimamquestions SET ' . $set . ' WHERE qid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO askimamquestions SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New question created';
						$this->RecordAdminAction(array('tablename'=>'askimamquestions', 'tableid'=>$this->id, 'area'=>'askimamquestions', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'askimamquestions', 'tableid'=>$this->id, 'area'=>'askimamquestions');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
			} //else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
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
			echo '</td><td class="pagetitle">', $this->InputSafeString($mm->details['mmname']), '</td><td>', $mm->MediaType(), '</td><td>', $mm->CatsList(), '</td><td>', $mm->details['live'] ? 'Yes' : 'No', '</td><td>', date('d-M-y @H:i', strtotime($mm->details['posted'])), $mm->details['author'] ? ('<br />by ' . $this->InputSafeString($mm->details['author'])) : '', '</td><td><a href="multimedia.php?id=', $mm->id, '">edit</a>&nbsp;|&nbsp;<a onclick="MultiMediaRemove(', $this->id, ',', $mmid, ');">remove from question</a></td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn MultiMediaTable
	
	public function AddMultimedia($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if ((!$mmlist = $this->GetMultiMedia()) || !$mmlist[$mmid])
			{	$sql = 'INSERT INTO askimam_mm SET qid=' . $this->id . ', mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn AddMultimedia
	
	public function RemoveMultimedia($mmid = 0)
	{	if ($this->id && ($mmid = (int)$mmid))
		{	if (($mmlist = $this->GetMultiMedia()) && $mmlist[$mmid])
			{	$sql = 'DELETE FROM askimam_mm WHERE qid=' . $this->id . ' AND mmid=' . $mmid;
				if ($result = $this->db->Query($sql))
				{	return $this->db->AffectedRows();
				}
			}
		}
	} // end of fn RemoveMultimedia
	
	public function CanDelete()
	{	if ($this->id && !$this->GetMultiMedia())
		{	if (($comments = new AdminStudentComments('askimamquestions', $this->id)) && $comments->comments)
			{	return false;
			}
			return true;
		}
		return false;
	} // end of fn CanDelete
	
} // end of class AdminAskImamQuestion
?>
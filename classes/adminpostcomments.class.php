<?php
class AdminPostComments extends AdminPostsPage
{	protected $ratings = array('0.2'=>'1 - lowest', '0.4'=>'2', '0.6'=>'3', '0.8'=>'4', '1'=>'5 - highest');

	public function __construct($id = null)
	{	parent::__construct($id);
	} // fn __construct
	
	protected function PostLoggedInConstruct($post_option = '')
	{	parent::PostLoggedInConstruct($post_option);
	} // end of fn PostLoggedInConstruct
	
	protected function PostBodyMain()
	{	parent::PostBodyMenu();
	} // end of fn PostBodyMain
	
	public function StatusString()
	{	ob_start();
		if ($this->details['suppressed'])
		{	if ($this->details['suppressedby'] && ($adminuser = new AdminUser($this->details['suppressedby'])) && $adminuser->userid)
			{	echo 'suppressed by <a href="useredit.php?userid=', $adminuser->userid, '">',  $adminuser->username, '</a>';
			} else
			{	echo 'not yet moderated';
			}
		} else
		{	if ($this->details['suppressedby'] && ($adminuser = new AdminUser($this->details['suppressedby'])) && $adminuser->userid)
			{	echo 'made live by <a href="useredit.php?userid=', $adminuser->userid, '">',  $adminuser->username, '</a>';
			} else
			{	echo 'live';
			}
		}
		return ob_get_clean();
	} // end of fn StatusString
	
	public function GetComments($exclude = 0)
	{	$comments = array();
		$where = array('pid=' . (int)$this->id, 'ptype="'.$this->details['ptype'].'"');
		
		if ($exclude = (int)$exclude)
		{	$where[] = 'NOT sid=' . $exclude;
		}
		
		if ($this->liveonly)
		{	$where[] = 'suppressed=0';
		}
		
		$sql = 'SELECT * FROM comments WHERE ' . implode(' AND ', $where) . ' ORDER BY revdate DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$comments[$row['prid']] = $row;
			}
		}
		return $comments;
	} // end of fn GetComments
	
	public function CommentsDisplay()
	{	ob_start();
		echo '<div class="mmdisplay"><div id="mmdContainer">', $this->CommentsTable(), '</div><script type="text/javascript">commentID=', $this->cid, ';$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner"></div></div></div>';
		return ob_get_clean();
	} // end of fn CommentsDisplay
	
	public function CommentsTable()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="7"><a href="postcomment.php?prid=', $this->id, '">Create new comment</a></th></tr><tr><th>Created by</th><th>Commenter displayed</th><th>Date</th><th>Comment</th><th>Status</th><th>Admin notes</th><th>Actions</th></tr>';
		$students = array();
		$adminusers = array();
		foreach ($this->GetComments() as $comment_row)
		{	$comment = new AdminPostComment($comment_row);
			echo '<tr><td>';
			if ($comment->details['sid'])
			{	if (!$students[$comment->details['sid']])
				{	$students[$comment->details['sid']] = new Student($comment->details['sid']);
				}
				echo 'Student: <a href="member.php?id=', $students[$comment->details['sid']]->id, '">', $this->InputSafeString($students[$comment->details['sid']]->GetName()), '</a>';
			} else
			{	if (!$adminusers[$comment->details['admincreated']])
				{	$adminusers[$comment->details['admincreated']] = new AdminUser($comment->details['admincreated']);
				}
				echo 'Admin: <a href="useredit.php?userid=', $adminusers[$comment->details['admincreated']]->userid, '">',  $adminusers[$comment->details['admincreated']]->username, '</a>';
			}
			echo '</td><td>', $this->InputSafeString($comment->details['commentertext']), '</td><td>', date('d/m/y @H:i', strtotime($comment->details['revdate'])), '</td><td>', $comment->details['revtitle'] ? ('<strong>' . $this->InputSafeString($comment->details['revtitle']) . '</strong><br />') : '', nl2br($this->InputSafeString($comment->details['comment'])), '</td><td>', $comment->StatusString(), '</td><td>', nl2br($this->InputSafeString($comment->details['adminnotes'])), '</td><td><a href="postcomment.php?id=', $comment->id, '">edit</a>';
			
			if($comment->CanDelete())
			{	echo '&nbsp;|&nbsp;<a href="postcomment.php?id=', $comment->cid, '&delete=1">delete</a>';
			}
			
			echo '</td></tr>';
		
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn CommentsTable
	
	public function AjaxForm()
	{	ob_start();
		//$this->VarDump($this->details);
		$student = $this->GetAuthor();
		echo '<h3>by ', $this->InputSafeString($student->GetName()), ' on ', date('d/m/y @H:i', strtotime($this->details['dateadded'])), '</h3><form onsubmit="return false;"><label>Suppressed?</label><input type="checkbox" name="suppress" id="revSuppress"', $this->details['suppressed'] ? ' checked="checked"' : '', ' /><br /><label>Admin notes</label><textarea id="revAdminNotes">', $this->InputSafeString($this->details['adminnotes']), '</textarea><br /><label>&nbsp;</label><a class="submit" onclick="CommentSave(', $this->id, ');">Save</a><br /></form><h3>Original comment</h3><p id="ajaxRevText">', nl2br($this->InputSafeString($this->details['comment'])), '</p>';
		
		return ob_get_clean();
	} // end of fn AjaxForm
	
	public function AdminSave($data = array(), $pid = 0, $ptype = '')
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if (isset($data['adminnotes']))
		{	$adminnotes = $this->SQLSafe($data['adminnotes']);
			$fields[] = 'adminnotes="' . $adminnotes . '"';
			if ($this->id && ($data['adminnotes'] != $this->details['adminnotes']))
			{	$admin_actions[] = array('action'=>'Admin notes', 'actionfrom'=>$this->details['adminnotes'], 'actionto'=>$data['adminnotes']);
			}
		}
		
		if (!$this->id || isset($data['comment']))
		{	if ($comment = $this->SQLSafe($data['comment']))
			{	$fields[] = 'comment="' . $this->SQLSafe($data['comment']) . '"';
				if ($this->id && ($data['comment'] != $this->details['comment']))
				{	$admin_actions[] = array('action'=>'Comment text', 'actionfrom'=>$this->details['comment'], 'actionto'=>$data['comment']);
				}
			} else
			{	$fail[] = 'Comment text is missing';
			}
		}
		if (!$this->id || isset($data['reviewertext']))
		{	if ($reviewertext = $this->SQLSafe($data['reviewertext']))
			{	$fields[] = 'reviewertext="' . $this->SQLSafe($data['reviewertext']) . '"';
				if ($this->id && ($data['reviewertext'] != $this->details['reviewertext']))
				{	$admin_actions[] = array('action'=>'Commenter name', 'actionfrom'=>$this->details['reviewertext'], 'actionto'=>$data['reviewertext']);
				}
			} else
			{	$fail[] = 'Commenter text is missing';
			}
		}
		
		$suppressed = ($data['suppressed'] ? 1 : 0);
		if ($this->details['suppressed'] != $suppressed)
		{	$fields[] = 'suppressed=' . $suppressed;
			$fields[] = 'suppressedby=' . (int)$_SESSION[SITE_NAME]['auserid'];
			if ($this->id)
			{	$admin_actions[] = array('action'=>'Suppressed?', 'actionfrom'=>$this->details['suppressed'], 'actionto'=>$suppressed, 'actiontype'=>'boolean');
			}
		}
		
		if (!$this->id || isset($data['dcommentdate']))
		{	if (($d = (int)$data['dcommentdate']) && ($m = (int)$data['mcommentdate']) && ($y = (int)$data['ycommentdate']))
			{	$dateadded = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y)) . ' ' . $this->StringToTime($data['commenttime']) . ':00';
				$fields[] = 'dateadded="' . $dateadded . '"';
				if ($this->id && ($dateadded != $this->details['dateadded']))
				{	$admin_actions[] = array('action'=>'Comment time', 'actionfrom'=>$this->details['dateadded'], 'actionto'=>$dateadded, 'actiontype'=>'datetime');
				}
			} else
			{	$fail[] = 'Comment date missing';
			}
		}
		
		if (!$this->id)
		{	if ($pid = (int)$pid)
			{	if ($ptype = $this->SQLSafe($ptype))
				{	$fields[] = 'pid=' . $pid;
					$fields[] = 'ptype="' . $ptype . '"';
				} else
				{	$fail[] = 'post type missing';
				}
			} else
			{	$fail[] = 'post id missing';
			}
			$fields[] = 'admincreated=' . (int)$_SESSION[SITE_NAME]['auserid'];
		}
		
		if ($this->ratings[$data['rating']])
		{	$fields[] = 'rating=' . $data['rating'];
			if ($this->id && ($data['rating'] != $this->details['rating']))
			{	$admin_actions[] = array('action'=>'Rating', 'actionfrom'=>$this->details['rating'], 'actionto'=>$data['rating']);
			}
		} else
		{	$fail[] = 'Rating invalid or missing';
		}
		
		if (!$fail)
		{	if ($this->id)
			{	$sql = 'UPDATE comments SET ' . implode(', ', $fields) . ' WHERE pid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO comments SET ' . implode(', ', $fields);
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$success[] = 'Changes saved';
						$base_parameters = array('tablename'=>'postcomments', 'tableid'=>$this->id, 'area'=>'post comments');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New comment created';
					}
					$this->Get($this->id);
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn AdminSave
	
	public function AdminInputForm($postid = 0)
	{	ob_start();
		
		if (!$data = $this->details)
		{	$data = $_POST;
			if (($d = (int)$data['dcommentdate']) && ($m = (int)$data['mcommentdate']) && ($y = (int)$data['ycommentdate']))
			{	$data['dateadded'] = $this->datefn->SQLDate(mktime(0, 0 , 0, $m, $d, $y)) . ' ' . $this->StringToTime($data['commenttime']);
			} else
			{	$data['dateadded'] = $this->datefn->SQLDateTime();
			}
		}
		
		$years = array();
		/*for ($y = 2000; $y <= date('Y'); $y++)
		{	$years[] = $y;
		}*/
		
		for ($y = 2000; $y <= 2025; $y++)
		{	$years[] = $y;
		}
		if ($this->id)
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'post_edit');
		} else
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?pid=' . (int)$postid, 'post_edit');
		}
		
		$ratings = array('0.2'=>'1 - lowest', '0.4'=>'2', '0.6'=>'3', '0.8'=>'4', '1'=>'5 - highest');
		
		$form->AddTextInput('Commenter name to display', 'reviewertext', $this->InputSafeString($data['reviewertext']), 'long', 255, 1);
		$form->AddTextArea('Comment', 'comment', $this->InputSafeString($data['comment']), '', 0, 0, 10, 40);
		$form->AddCheckBox('Suppressed', 'suppressed', '1', $data['suppressed']);
		$form->AddTextArea('Admin notes', 'adminnotes', $this->InputSafeString($data['adminnotes']), '', 0, 0, 5, 40);
		$form->AddDateInput('Comment date', 'commentdate', substr($data['dateadded'], 0, 10), $years, 0, 0, false, true, date('Y'));
		$form->AddTextInput('... and time', 'commenttime', substr($data['dateadded'], 11, 5), 'number', 5, 1);
		$form->AddSelect('rating', 'rating', $data['rating'], '', $this->ratings, false, true);

		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Comment', 'submit');
		if ($this->id)
		{	if ($histlink = $this->DisplayHistoryLink('postcomments', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		
		if ($this->CanDelete())
		{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this comment</a></p>';
		}

		$form->Output();
		
		return ob_get_clean();
	} // end of fn AdminInputForm
	
	public function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete
	
} // end of class defn AdminPostComment
?>
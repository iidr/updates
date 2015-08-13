<?php
class AdminProductReview extends ProductReview
{	protected $ratings = array('0.2'=>'1 - lowest', '0.4'=>'2', '0.6'=>'3', '0.8'=>'4', '1'=>'5 - highest');

	public function __construct($id = null)
	{	parent::__construct($id);
	} // fn __construct
	
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
	
	public function AjaxForm()
	{	ob_start();
		//$this->VarDump($this->details);
		$student = $this->GetAuthor();
		echo '<h3>by ', $this->InputSafeString($student->GetName()), ' on ', date('d/m/y @H:i', strtotime($this->details['revdate'])), '</h3><form onsubmit="return false;"><label>Suppressed?</label><input type="checkbox" name="suppress" id="revSuppress"', $this->details['suppressed'] ? ' checked="checked"' : '', ' /><br /><label>Admin notes</label><textarea id="revAdminNotes">', $this->InputSafeString($this->details['adminnotes']), '</textarea><br /><label>&nbsp;</label><a class="submit" onclick="ReviewSave(', $this->id, ');">Save</a><br /></form><h3>Original review</h3><p id="ajaxRevText">', nl2br($this->InputSafeString($this->details['review'])), '</p>';
		
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
		
		if (!$this->id || isset($data['review']))
		{	if ($review = $this->SQLSafe($data['review']))
			{	$fields[] = 'review="' . $this->SQLSafe($data['review']) . '"';
				if ($this->id && ($data['review'] != $this->details['review']))
				{	$admin_actions[] = array('action'=>'Review text', 'actionfrom'=>$this->details['review'], 'actionto'=>$data['review']);
				}
			} else
			{	$fail[] = 'Review text is missing';
			}
		}
		
		if (!$this->id || isset($data['revtitle']))
		{	$revtitle = $this->SQLSafe($data['revtitle']);
			$fields[] = 'revtitle="' . $this->SQLSafe($data['revtitle']) . '"';
			if ($this->id && ($data['revtitle'] != $this->details['revtitle']))
			{	$admin_actions[] = array('action'=>'Review title', 'actionfrom'=>$this->details['revtitle'], 'actionto'=>$data['revtitle']);
			}
		}
		
		if (!$this->id || isset($data['reviewertext']))
		{	if ($reviewertext = $this->SQLSafe($data['reviewertext']))
			{	$fields[] = 'reviewertext="' . $this->SQLSafe($data['reviewertext']) . '"';
				if ($this->id && ($data['reviewertext'] != $this->details['reviewertext']))
				{	$admin_actions[] = array('action'=>'Reviewer name', 'actionfrom'=>$this->details['reviewertext'], 'actionto'=>$data['reviewertext']);
				}
			} else
			{	$fail[] = 'Reviewer text is missing';
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
		
		if (!$this->id || isset($data['dreviewdate']))
		{	if (($d = (int)$data['dreviewdate']) && ($m = (int)$data['mreviewdate']) && ($y = (int)$data['yreviewdate']))
			{	$revdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y)) . ' ' . $this->StringToTime($data['reviewtime']) . ':00';
				$fields[] = 'revdate="' . $revdate . '"';
				if ($this->id && ($revdate != $this->details['revdate']))
				{	$admin_actions[] = array('action'=>'Review time', 'actionfrom'=>$this->details['revdate'], 'actionto'=>$revdate, 'actiontype'=>'datetime');
				}
			} else
			{	$fail[] = 'Review date missing';
			}
		}
		
		if (!$this->id)
		{	if ($pid = (int)$pid)
			{	if ($ptype = $this->SQLSafe($ptype))
				{	$fields[] = 'pid=' . $pid;
					$fields[] = 'ptype="' . $ptype . '"';
				} else
				{	$fail[] = 'product type missing';
				}
			} else
			{	$fail[] = 'product id missing';
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
			{	$sql = 'UPDATE productreviews SET ' . implode(', ', $fields) . ' WHERE 	prid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO productreviews SET ' . implode(', ', $fields);
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$success[] = 'Changes saved';
						$base_parameters = array('tablename'=>'productreviews', 'tableid'=>$this->id, 'area'=>'product reviews');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New review created';
					}
					$this->Get($this->id);
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn AdminSave
	
	public function AdminInputForm($productid = 0)
	{	ob_start();
		
		if (!$data = $this->details)
		{	$data = $_POST;
			if (($d = (int)$data['dreviewdate']) && ($m = (int)$data['mreviewdate']) && ($y = (int)$data['yreviewdate']))
			{	$data['revdate'] = $this->datefn->SQLDate(mktime(0, 0 , 0, $m, $d, $y)) . ' ' . $this->StringToTime($data['reviewtime']);
			} else
			{	$data['revdate'] = $this->datefn->SQLDateTime();
			}
		}
		
		$years = array();
		for ($y = 2000; $y <= date('Y'); $y++)
		{	$years[] = $y;
		}
		if ($this->id)
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		} else
		{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?pid=' . (int)$productid, 'course_edit');
		}
		
		$ratings = array('0.2'=>'1 - lowest', '0.4'=>'2', '0.6'=>'3', '0.8'=>'4', '1'=>'5 - highest');
		
		$form->AddTextInput('Reviewer name to display', 'reviewertext', $this->InputSafeString($data['reviewertext']), 'long', 255, 1);
		$form->AddTextInput('Review title (optional)', 'revtitle', $this->InputSafeString($data['revtitle']), 'long', 255, 1);
		$form->AddTextArea('Review', 'review', $this->InputSafeString($data['review']), '', 0, 0, 10, 40);
		$form->AddCheckBox('Suppressed', 'suppressed', '1', $data['suppressed']);
		$form->AddTextArea('Admin notes', 'adminnotes', $this->InputSafeString($data['adminnotes']), '', 0, 0, 5, 40);
		$form->AddDateInput('Review date', 'reviewdate', substr($data['revdate'], 0, 10), $years, 0, 0, false, true, date('Y'));
		$form->AddTextInput('... and time', 'reviewtime', substr($data['revdate'], 11, 5), 'number', 5, 1);
		$form->AddSelect('rating', 'rating', $data['rating'], '', $this->ratings, false, true);

		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Review', 'submit');
		if ($this->id)
		{	if ($histlink = $this->DisplayHistoryLink('productreviews', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		
		if ($this->CanDelete())
		{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this review</a></p>';
		}

		$form->Output();
		
		return ob_get_clean();
	} // end of fn AdminInputForm
	
	public function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete
	
} // end of class defn AdminProductReview
?>
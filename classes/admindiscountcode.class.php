<?php
class AdminDiscountCode extends DiscountCode
{	
	public function __construct($id = null)
	{	parent::__construct($id);
	} // fn __construct
	
	public function ProductTypeDetails($ptype = false, $prodid = false, $form = true,$ticketid=0)
	{	ob_start();
		if ($ptype = (($ptype === false) ? $this->details['prodtype'] : $ptype)){	
			echo $this->prodtypes[$ptype], ': ';
			$ticketid = (int)(($ticketid == '')?$this->details['ticket'] : $ticketid);
			
			if ($prodid = (int)(($prodid === false) ? $this->details['prodid'] : $prodid))
			{	switch ($ptype)
				{	case 'course':
						if (($course = new Course($prodid)) && $course->id)
						{	echo $this->InputSafeString($course->content['ctitle']), ' - ', date('j M Y', strtotime($course->details['starttime']));
						} else
						{	echo 'specific course #', $prodid, ' not found';
						}
						break;
					case 'event':
						if (($course = new Course($prodid)) && $course->id)
						{	echo $this->InputSafeString($course->content['ctitle']), ' - ', date('j M Y', strtotime($course->details['starttime']));
						} else
						{	echo 'specific event #', $prodid, ' not found';
						}
						break;
					case 'store':
						if (($product = new StoreProduct($prodid)) && $product->id)
						{	echo $this->InputSafeString($product->details['title']);
						} else
						{	echo 'specific product #', $prodid, ' not found';
						}
						break;
					default: 
				}
			} else
			{	echo 'any';
			}
			if ($form){	
				echo ' - <a onclick="DisplaySelectPTypePopUp(', (int)$this->id;
				if($ticketid!='0'){
					echo ',',(int)$ticketid;
				}
				echo ');">change this</a>';
			}
		} else
		{	echo 'applies to anything';
		}
		if ($form)
		{	echo '<input type="hidden" name="prodid" id="prodid" value="', (int)$prodid, '" />';
			echo '<input type="hidden" name="ticketid" id="ticketid" value="', (int)$ticketid, '" />';
		}
		return ob_get_clean();
	} // end of fn ProductTypeDetails
	
	function InputForm()
	{	ob_start();
		
		$startyear = 2000;
		$endyear = 2025;
		
		if ($this->id)
		{	$data = $this->details;
			
			if (date('Y', strtotime($this->details['startdate'])) < $startyear)
			{	$startyear = $starttimeyear;
			}
			if (date('Y', strtotime($this->details['enddate'])) > $endyear)
			{	$endyear = $starttimeyear;
			}
		} else
		{	$data = $_POST;
			
			if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
			{	$data['startdate'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
			if (($d = (int)$data['dend']) && ($m = (int)$data['mend']) && ($y = (int)$data['yend']))
			{	$data['enddate'] = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
			}
		}
		
		$years = array();
		for ($y = $startyear; $y <= $endyear; $y++)
		{	$years[] = $y;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$form->AddTextInput('Discount code', 'disccode', $this->InputSafeString($data['disccode']), '', 20, 1);
		$form->AddTextInput('Discount description', 'discdesc', $this->InputSafeString($data['discdesc']), 'long', 255, 1);
		$form->AddDateInput('Start date', 'start', $data['startdate'], $years, 0, 0, true, false, date('Y'));
		$form->AddDateInput('End date', 'end', $data['enddate'], $years, 0, 0, true, false, date('Y'));

		$form->AddTextInput('Discount percentage', 'discpc', round($data['discpc'], 2), 'short number', 5);
		$form->AddTextInput('... or amount (&pound;)', 'discamount', number_format($data['discamount'], 2, '.', ''), 'short number', 10);
		$form->AddTextInput('Maximum uses (0 for &infin;)', 'uselimit', (int)$data['uselimit'], 'short number', 10);
		
		$form->AddCheckBox('Live (useable)', 'live', '1', $data['live']);
		$form->AddCheckBox('One use only per customer', 'oneuseperuser', '1', $data['oneuseperuser']);
		
		$form->AddSelect('Product type', 'prodtype', $data['prodtype'], '', $this->prodtypes, true, false, 'onchange="ChangedPType(' . (int)$this->id . ','.(int) $data['ticketid'].');"');
		$form->AddRawText('<label>&nbsp;</label><span id="ptypeDetails">' . $this->ProductTypeDetails() . '</span><br />');
		
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Discount Code', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this discount code</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('askimamtopics', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		echo $this->ProductSelectPopUp();
		return ob_get_clean();
	} // end of fn InputForm
	
	public function ProductSelectPopUp()
	{	ob_start();
		echo '<div class="mmdisplay"><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow"));$("#rlp_modal_popup").jqm();});</script>',
			'<!-- START instructor list modal popup --><div id="rlp_modal_popup" class="jqmWindow" style="padding-bottom: 5px; width: 640px; margin-left: -320px; top: 10px; height: 600px; "><a href="#" class="jqmClose submit">Close</a><div id="rlpModalInner" style="height: 500px; overflow:auto;"></div></div></div>';
		return ob_get_clean();
	} // end of fn ProductSelectPopUp
	
	private function DiscCodeExists($disccode = '')
	{	$sql = 'SELECT discid FROM discountcodes WHERE disccode="' . $this->SQLSafe($disccode) . '"';
		if ($this->id)
		{	$sql .= ' AND NOT discid=' . $this->id;
		}
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['discid'];
			}
		}
		return false;
	} // end of fn SlugExists

	public function ValidDiscountCode($disccode = '')
	{	return preg_match('|^[\da-zA-Z]{4,20}$|', $disccode);
	} // end of fn ValidDiscountCode
	
	function Save($data = array())
	{	
	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($discdesc = $this->SQLSafe($data['discdesc'])){	
			$fields[] = 'discdesc="' . $discdesc . '"';
			if ($this->id && ($data['discdesc'] != $this->details['discdesc'])){	
				$admin_actions[] = array('action'=>'Description', 'actionfrom'=>$this->details['discdesc'], 'actionto'=>$data['discdesc']);
			}
		}else{	
			$fail[] = 'Description missing';
		}
		
		if ($this->ValidDiscountCode($disccode = $data['disccode']))
		{	if ($previd = $this->DiscCodeExists($data['disccode']))
			{	$fail[] = 'Discount code already exists - <a href="discountedit.php?id=' . $previd . '">' . $data['disccode'] . '</a>';
			} else
			{	$fields[] = 'disccode="' . $disccode . '"';
				if ($this->id && ($data['disccode'] != $this->details['disccode']))
				{	$admin_actions[] = array('action'=>'Code', 'actionfrom'=>$this->details['disccode'], 'actionto'=>$data['disccode']);
				}
			}
		}else{	
			$fail[] = 'Discount code invalid (4 to 20 letters and numbers only)';
		}
		
		// start date
		if (($d = (int)$data['dstart']) && ($m = (int)$data['mstart']) && ($y = (int)$data['ystart']))
		{	$startdate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		} else
		{	$startdate = '0000-00-00';
		}
		
		// end date
		if (($d = (int)$data['dend']) && ($m = (int)$data['mend']) && ($y = (int)$data['yend']))
		{	$enddate = $this->datefn->SQLDate(mktime(0,0,0,$m, $d, $y));
		} else
		{	$enddate = '0000-00-00';
		}
		
		if ($startdate && $enddate)
		{	if ($enddate >= $startdate)
			{	$fields[] = 'startdate="' . $startdate . '"';
				if ($this->id && ($startdate != $this->details['startdate']))
				{	$admin_actions[] = array('action'=>'Start date', 'actionfrom'=>$this->details['startdate'], 'actionto'=>$startdate, 'actiontype'=>'datetime');
				}
				$fields[] = 'enddate="' . $enddate . '"';
				if ($this->id && ($enddate != $this->details['enddate']))
				{	$admin_actions[] = array('action'=>'End date', 'actionfrom'=>$this->details['enddate'], 'actionto'=>$enddate, 'actiontype'=>'datetime');
				}
			} else
			{	$fail[] = 'Discount must end after it starts, if both start and end date are set';
			}
		} else
		{	$fields[] = 'startdate="' . $startdate . '"';
			if ($this->id && ($startdate != $this->details['startdate']))
			{	$admin_actions[] = array('action'=>'Start date', 'actionfrom'=>$this->details['startdate'], 'actionto'=>$startdate, 'actiontype'=>'datetime');
			}
			$fields[] = 'enddate="' . $enddate . '"';
			if ($this->id && ($enddate != $this->details['enddate']))
			{	$admin_actions[] = array('action'=>'End date', 'actionfrom'=>$this->details['enddate'], 'actionto'=>$enddate, 'actiontype'=>'datetime');
			}
		}
		
		$uselimit = (int)$data['uselimit'];
		$fields[] = 'uselimit=' . $uselimit;
		if ($this->id && ($uselimit != $this->details['uselimit']))
		{	$admin_actions[] = array('action'=>'Maximum uses', 'actionfrom'=>$this->details['uselimit'], 'actionto'=>$uselimit);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$oneuseperuser = ($data['oneuseperuser'] ? '1' : '0');
		$fields[] = 'oneuseperuser=' . $oneuseperuser;
		if ($this->id && ($oneuseperuser != $this->details['oneuseperuser']))
		{	$admin_actions[] = array('action'=>'One use only?', 'actionfrom'=>$this->details['oneuseperuser'], 'actionto'=>$oneuseperuser, 'actiontype'=>'boolean');
		}
		
		if (!$data['prodtype'] || $this->prodtypes[$data['prodtype']])
		{	$fields[] = 'prodtype="' . $data['prodtype'] . '"';
			if ($this->id && ($data['prodtype'] != $this->details['prodtype']))
			{	$admin_actions[] = array('action'=>'Product type', 'actionfrom'=>$this->details['prodtype'], 'actionto'=>$data['prodtype'], 'actiontype'=>'boolean');
			}
			if ($data['prodtype'])
			{	$prodid = (int)$data['prodid'];
				$fields[] = 'prodid=' . $prodid;
				if ($this->id && ($prodid != $this->details['prodid']))
				{	$admin_actions[] = array('action'=>'Product ID', 'actionfrom'=>$this->details['prodtype'] . ':' . $this->details['prodid'], 'actionto'=> $data['prodtype'] . ':' . $prodid);
				}
			} else
			{	$fields[] = 'prodid=0';
			}
		} else
		{	$fail[] = 'product type not found';
		}
		
		$discpc = round($data['discpc'], 2);
		if ($discpc > 100)
		{	$discpc = 0;
			$fail[] = '%age discount must be no more than 100';
		}
		$discamount = round($data['discamount'], 2);
		if ($discpc || $discamount)
		{	if ($discpc && $discamount)
			{	$fail[] = 'You can only apply a %age or an amount as discount';
			} else
			{	$fields[] = 'discpc=' . $discpc;
				if ($this->id && ($discpc != $this->details['discpc']))
				{	$admin_actions[] = array('action'=>'Discount %age', 'actionfrom'=>$this->details['discpc'], 'actionto'=>$discpc);
				}
				$fields[] = 'discamount=' . $discamount;
				if ($this->id && ($discamount != $this->details['discamount']))
				{	$admin_actions[] = array('action'=>'Discount amount', 'actionfrom'=>$this->details['discamount'], 'actionto'=>$discamount);
				}
			}
		} else
		{	$fail[] = 'You must apply some discount';
		}
		
		$ticketid = $this->SQLSafe($data['ticketid']);		
		$fields[] = 'ticket="' . (int)$ticketid . '"';
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE discountcodes SET ' . $set . ' WHERE discid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO discountcodes SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	$this->id = $this->db->InsertID();
						$success[] = 'New discount code created';
						$this->RecordAdminAction(array('tablename'=>'discountcodes', 'tableid'=>$this->id, 'area'=>'discountcodes', 'action'=>'created'));
					}
					$this->Get($this->id);
				
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'discountcodes', 'tableid'=>$this->id, 'area'=>'discountcodes');
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
				}
			} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	public function CanDelete()
	{	return $this->id && !$this->details['usecount'];
	} // end of fn CanDelete
	
} // end of class AdminDiscountCode
?>
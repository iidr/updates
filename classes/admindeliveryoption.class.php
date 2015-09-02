<?php
class AdminDeliveryOption extends DeliveryOption
{	
	function __construct($id = 0)
	{	parent::__construct($id);	
	} // fn __construct
	
	function CanDelete()
	{	return $this->id;
	} // end of fn CanDelete

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
		{	$fail[] = 'Option name missing';
		}
		
		$description = $this->SQLSafe($data['description']);
		$fields[] = 'description="' . $description . '"';
		if ($this->id && ($data['description'] != $this->details['description']))
		{	$admin_actions[] = array('action'=>'Description', 'actionfrom'=>$this->details['description'], 'actionto'=>$data['description']);
		}
		
		$price = round($data['price'], 2);
		$fields[] = 'price=' . $price;
		if ($this->id && ($price != $this->details['price']))
		{	$admin_actions[] = array('action'=>'Price', 'actionfrom'=>$this->details['price'], 'actionto'=>$price);
		}
		
		$from_weight = round($data['from_weight'], 2);
		$fields[] = 'from_weight=' . $from_weight;
		if ($this->id && ($from_weight != $this->details['from_weight']))
		{	$admin_actions[] = array('action'=>'From Weight', 'actionfrom'=>$this->details['from_weight'], 'actionto'=>$from_weight);
		}
		
		$to_weight = round($data['to_weight'], 2);
		$fields[] = 'to_weight=' . $to_weight;
		if ($this->id && ($to_weight != $this->details['to_weight']))
		{	$admin_actions[] = array('action'=>'To Weight', 'actionfrom'=>$this->details['to_weight'], 'actionto'=>$to_weight);
		}
		
		$listorder = (int)$data['listorder'];
		$fields[] = 'listorder=' . $listorder;
		if ($this->id && ($listorder != $this->details['listorder']))
		{	$admin_actions[] = array('action'=>'List order', 'actionfrom'=>$this->details['listorder'], 'actionto'=>$listorder);
		}
		
		$live = ($data['live'] ? '1' : '0');
		$fields[] = 'live=' . $live;
		if ($this->id && ($live != $this->details['live']))
		{	$admin_actions[] = array('action'=>'Live?', 'actionfrom'=>$this->details['live'], 'actionto'=>$live, 'actiontype'=>'boolean');
		}
		
		$region = (int)$data['region'];
		$fields[] = 'region=' . $region;
		if ($this->id && ($region != $this->details['region']))
		{	$admin_actions[] = array('action'=>'List order', 'actionfrom'=>$this->details['region'], 'actionto'=>$region);
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE deliveryoptions SET ' . $set . ' WHERE id=' . $this->id;
			} else
			{	$sql = 'INSERT INTO deliveryoptions SET ' . $set;
			}
			
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	if ($this->id = $this->db->InsertID())
						{	$success[] = 'New delivery option created';
							$this->RecordAdminAction(array('tablename'=>'deliveryoptions', 'tableid'=>$this->id, 'area'=>'delivery options', 'action'=>'created'));
						}
					}
					$this->Get($this->id);
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'deliveryoptions', 'tableid'=>$this->id, 'area'=>'delivery options');
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
	
	function InputForm()
	{	
		ob_start();
		
		if (!$data = $_POST)
		{	$data = $this->details;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$form->AddTextInput('Option text', 'title', $this->InputSafeString($data['title']), 'long', 255, 1);
		$form->AddTextInput('Description', 'description', $this->InputSafeString($data['description']), 'long', 255);
		$form->AddSelect('Region', 'region', $data['region'], '', $this->RegionOptions(), false, true);
		$form->AddTextInput('Price', 'price', number_format($data['price'], 2, '.', ''), 'short number', 10);
		$form->AddTextInput('From Weight (grams)', 'from_weight', number_format($data['from_weight'], 2, '.', ''), 'short number', 10);
		$form->AddTextInput('To Weight (grams)', 'to_weight', number_format($data['to_weight'], 2, '.', ''), 'short number', 10);
		$form->AddTextInput('List order', 'listorder', (int)$data['listorder'], 'short number', 5);
		$form->AddCheckBox('Live?', 'live', '1', $data['live']);
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Delivery Option', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this option</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('deliveryoptions', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	public function RegionOptions()
	{	$regions = array();
		$sql = 'SELECT * FROM delregions ORDER BY drname';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$regions[$row['drid']] = $row['drname'];
			}
		}
		return $regions;
	} // end of fn RegionOptions
	
} // end of class AdminDeliveryOption
?>
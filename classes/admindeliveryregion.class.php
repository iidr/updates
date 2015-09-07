<?php
class AdminDeliveryRegion extends DeliveryRegion
{	
	function __construct($id = 0)
	{	parent::__construct($id);	
	} // fn __construct
	
	public function GetOptions($liveonly = false)
	{	return parent::GetOptions(false);
	} // end of fn GetOptions
	
	public function GetFallbackOptions($liveonly = false)
	{	return array();
	} // end of fn GetFallbackOptions
	
	function CanDelete()
	{	return $this->id && !$this->options;
	} // end of fn CanDelete

	function Save($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if ($drname = $this->SQLSafe($data['drname']))
		{	$fields[] = 'drname="' . $drname . '"';
			if ($this->id && ($data['drname'] != $this->details['drname']))
			{	$admin_actions[] = array('action'=>'Name', 'actionfrom'=>$this->details['drname'], 'actionto'=>$data['drname']);
			}
		} else
		{	$fail[] = 'name missing';
		}
		
		if ($this->id || !$fail)
		{	$set = implode(", ", $fields);
			if ($this->id)
			{	$sql = 'UPDATE delregions SET ' . $set . ' WHERE drid=' . $this->id;
			} else
			{	$sql = 'INSERT INTO delregions SET ' . $set;
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->id)
					{	$record_changes = true;
						$success[] = 'Changes saved';
					} else
					{	if ($this->id = $this->db->InsertID())
						{	$success[] = 'New delivery region created';
							$this->RecordAdminAction(array('tablename'=>'delregions', 'tableid'=>$this->id, 'area'=>'delivery regions', 'action'=>'created'));
						}
					}
					$this->Get($this->id);
				} else
				{	if (!$this->id)
					{	$fail[] = 'Insert failed';
					}
				}
				
				if ($record_changes)
				{	$base_parameters = array('tablename'=>'delregions', 'tableid'=>$this->id, 'area'=>'delivery regions');
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
	
	function InputForm()
	{	ob_start();
		
		if (!$data = $_POST)
		{	$data = $this->details;
		}
		
		$form = new Form($_SERVER['SCRIPT_NAME'] . '?id=' . $this->id, 'course_edit');
		$form->AddTextInput('Region name', 'drname', $this->InputSafeString($data['drname']), 'long', 255, 1);
		$form->AddSubmitButton('', $this->id ? 'Save Changes' : 'Create New Delivery Region', 'submit');
		if ($this->id)
		{	if ($this->CanDelete())
			{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?id=', $this->id, '&delete=1', $_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 'delete this region</a></p>';
			}
			
			if ($histlink = $this->DisplayHistoryLink('coursecategories', $this->id))
			{	echo '<p>', $histlink, '</p>';
			}
		}
		$form->Output();
		return ob_get_clean();
	} // end of fn InputForm
	
	public function ListCountries()
	{	if ($this->id)
		{	ob_start();
			if ($countries = $this->GetCountries())
			{	echo '<h3>Countries in region</h3><ul>';
				foreach ($countries as $ctry_row)
				{	echo '<li><a href="ctryedit.php?ctry=', $ctry_row['ccode'], '">', $this->InputSafeString($ctry_row['shortname']), '</a></li>';
				}
				echo '</ul>';
			} else
			{	echo '<h3>No countries in this region</h3>';
			}
			return ob_get_clean();
		}
	} // end of fn ListCountries
	
	public function ListOptions()
	{	if ($this->id)
		{	ob_start();
			if ($options = $this->GetOptions())
			{	echo '<h3>Delivery options for region</h3><ul>';
				foreach ($options as $opt_row)
				{	echo '<li><a href="deliveryedit.php?id=', $opt_row['id'], '">', $this->InputSafeString($opt_row['title']), '</a></li>';
				}
				echo '</ul>';
			} else
			{	echo '<h3>No delivery options for this region</h3>';
			}
			return ob_get_clean();
		}
	} // end of fn ListCountries
	
} // end of class AdminDeliveryRegion
?>
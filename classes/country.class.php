<?php
class Country extends Base
{	var $details = array();
	var $code = "";
	
	function __construct($ctrycode = 0)
	{	parent::__construct();
		$this->Get($ctrycode);
	} // fn __construct
	
	function Reset()
	{	$this->details = array();
		$this->code = '';
	} // end of fn Reset
	
	function Get($ctrycode = 0)
	{	$this->Reset();
		if (is_array($ctrycode))
		{	$this->details = $ctrycode;
			$this->code = $ctrycode['ccode'];
		} else
		{	if ($result = $this->db->Query('SELECT * FROM countries WHERE ccode="' . $this->SQLSafe($ctrycode) . '"'))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->details = $row;
					$this->code = $row['ccode'];
				}
			}
		}
		
	} // end of fn Get
	
	function CanDelete(){	
		return false;
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	$sql = 'DELETE FROM countries WHERE ccode="' . $this->code . '"';
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$this->RecordAdminAction(array('tablename'=>'countries', 'tableid'=>$this->code, 'area'=>'countries', 'action'=>'deleted'));
					$this->Reset();
					return true;
				}
			}
		}
		return false;
	} // end of fn Delete

	function Save()
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if (!$this->code)
		{	$ccode = (int)$_POST['ccode'];
			if ($ccode && ($ccode == $_POST['ccode']))
			{	$fields[] = 'ccode="' . $ccode . '"';
				// check for duplicate
				if ($result = $this->db->Query('SELECT shortname FROM countries WHERE ccode="' . $ccode . '"'))
				{	if ($row = $this->db->FetchArray($result))
					{	$fail[] = 'code ' . $ccode . ' already used for <a href="ctryedit.php?ctry=$ccode">' . $row['shortname'] . '</a>';
					}
				}
			} else
			{	$fail[] = 'new code not numeric';
			}
		}
		
		$shortname = $this->SQLSafe($_POST['shortname']);
		if ($shortname)
		{	$fields[] = 'shortname="' . $shortname . '"';
			if ($this->code && ($_POST['shortname'] != $this->details['shortname']))
			{	$admin_actions[] = array('action'=>'Short name', 'actionfrom'=>$this->details['shortname'], 'actionto'=>$_POST['shortname']);
			}
		} else
		{	$fail[] = 'display name missing';
		}
		
		$longname = $this->SQLSafe($_POST['longname']);
		$fields[] = 'longname="' . $longname . '"';
		if ($this->code && ($_POST['longname'] != $this->details['longname']))
		{	$admin_actions[] = array('action'=>'Long name', 'actionfrom'=>$this->details['longname'], 'actionto'=>$_POST['longname']);
		}
		
		if ($shortcode = strtoupper($_POST['shortcode']))
		{	if (preg_match('|^[A-Z]{2}$|', $shortcode))
			{	// check for duplicates
				$ok = true;
				if ($result = $this->db->Query('SELECT ccode, shortname FROM countries WHERE shortcode="$shortcode" AND NOT ccode="{$this->code}"'))
				{	if ($row = $this->db->FetchArray($result))
					{	$fail[] = 'code ' . $shortcode . ' already used for <a href="ctryedit.php?ctry=' . $row['ccode'] . '">' . $row['shortname'] . '</a>';
						$ok = false;
					}
				}
				if ($ok)
				{	$fields[] = 'shortcode="' . $shortcode . '"';
					if ($this->code && ($shortcode != $this->details['shortcode']))
					{	$admin_actions[] = array('action'=>'Short code', 'actionfrom'=>$this->details['shortcode'], 'actionto'=>$shortcode);
					}
				}
			} else
			{	$fail[] = 'short code must be 2 characters A-Z';
			}
		} else
		{	$fail[] = 'short code missing';
		}
		
		if ($longcode = strtoupper($_POST['longcode']))
		{	if (preg_match('|^[A-Z]{3}$|', $longcode))
			{	// check for duplicates
				$ok = true;
				if ($result = $this->db->Query('SELECT ccode, shortname FROM countries WHERE longcode="' . $longcode . '" AND NOT ccode="' . $this->code . '"'))
				{	if ($row = $this->db->FetchArray($result))
					{	$fail[] = 'long code $longcode already used for <a href="ctryedit.php?ctry=' . $row['ccode'] . '">' . $row['shortname'] . '</a>';
						$ok = false;
					}
				}
				if ($ok)
				{	$fields[] = 'longcode="' . $longcode . '"';
					if ($this->code && ($longcode != $this->details['longcode']))
					{	$admin_actions[] = array('action'=>'Long code', 'actionfrom'=>$this->details['longcode'], 'actionto'=>$longcode);
					}
				}
			} else
			{	$fail[] = 'long code must be 3 characters A-Z';
			}
		}
		
		if ($continent = strtoupper($_POST['continent']))
		{	if (preg_match('|^[A-Z]{2}$|', $continent))
			{	$fields[] = 'continent="' . $continent . '"';
				if ($this->code && ($continent != $this->details['continent']))
				{	$admin_actions[] = array('action'=>'Continent', 'actionfrom'=>$this->details['continent'], 'actionto'=>$continent);
				}
			} else
			{	$fail[] = 'continent must be 2 characters A-Z';
			}
		}
		
		$region = (int)$_POST['region'];
		$fields[] = 'region=' . $region;
		if ($this->code && ($region != $this->details['region']))
		{	$admin_actions[] = array('action'=>'Region', 'actionfrom'=>$this->details['region'], 'actionto'=>$region);
		}
		
		if ($this->code || !$fail)
		{	$set = implode(', ', $fields);
			if ($this->code)
			{	$sql = 'UPDATE countries SET ' . $set . ' WHERE ccode="' . $this->code . '"';
			} else
			{	$sql = 'INSERT INTO countries SET ' . $set;
			}
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	if ($this->code)
					{	$base_parameters = array('tablename'=>'countries', 'tableid'=>$this->code, 'area'=>'countries');
						if ($admin_actions)
						{	foreach ($admin_actions as $admin_action)
							{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
							}
						}
						$success[] = 'Changes saved';
					} else
					{	$this->code = $ccode;
						$success[] = 'New country created';
						$this->RecordAdminAction(array('tablename'=>'countries', 'tableid'=>$this->code, 'area'=>'countries', 'action'=>'created'));
					}
					$this->Get($this->code);
				
				} else
				{	if ($this->code)
					{	//$fail[] = 'No changes made';
					} else
					{	$fail[] = 'Insert failed';
					}
				}
			}
		}
		
		return array('failmessage'=>implode(', ', $fail), 'successmessage'=>implode(', ', $success));
		
	} // end of fn Save
	
	function InputForm()
	{	$form = new Form($_SERVER['SCRIPT_NAME'] . '?ctry=' . $this->code, 'crewcvForm');
		if (!$this->code)
		{	$form->AddTextInput('Numeric code', 'ccode', '', '', 3, 1);
		}
		$form->AddTextInput('Short name', 'shortname', $this->InputSafeString($this->details['shortname']), '', 30, 1);
		$form->AddTextInput('Full name', 'longname', $this->InputSafeString($this->details['longname']), 'long', 60);
		$form->AddTextInput('Short code (2)', 'shortcode', $this->InputSafeString($this->details['shortcode']), 'short', 2);
		$form->AddTextInput('Long code (3)', 'longcode', $this->InputSafeString($this->details['longcode']), 'short', 3);
		$form->AddSelect('Delivery region', 'region', $this->details['region'], '', $this->RegionsList(), 1, 0);
		$form->AddSelect('Continent', 'continent', $this->details['continent'], '', $this->Continents(), true);
		$form->AddSubmitButton('', $this->code ? 'Save Changes' : 'Create New Country', 'submit');
		if ($histlink = $this->DisplayHistoryLink('countries', $this->code))
		{	echo '<p>', $histlink, '</p>';
		}
		if ($this->code && $this->CanDelete())
		{	echo '<p><a href="', $_SERVER['SCRIPT_NAME'], '?ctry=', $this->code, '&delete=1', 
					$_GET['delete'] ? '&confirm=1' : '', '">', $_GET['delete'] ? 'please confirm you really want to ' : '', 
					'delete this country</a></p>';
		}
		$form->Output();
	} // end of fn InputForm
	
	function RegionsList()
	{	$regions = array();
		if ($result = $this->db->Query('SELECT * FROM delregions ORDER BY drname'))
		{	while ($row = $this->db->FetchArray($result))
			{	$regions[$row['drid']] = $row['drname'];
			}
		}
		return $regions;
	} // end of fn RegionsList
	
	function Continents()
	{	$continents = array();
		if ($result = $this->db->Query('SELECT * FROM continents ORDER BY dispname'))
		{	while ($row = $this->db->FetchArray($result))
			{	$continents[$row['continent']] = $row['dispname'];
			}
		}
		return $continents;
	} // end of fn Continents
	
	public function GetRegionName()
	{	$sql = 'SELECT drname FROM delregions WHERE drid=' . (int)$this->details['region'];
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	return $row['drname'];
			}
		}
		return '';
	} // end of fn GetRegionName
	
	
	
} // end of defn Country
?>
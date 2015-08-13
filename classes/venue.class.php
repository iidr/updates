<?php
class Venue extends BlankItem
{	
	
	function __construct($id = 0)
	{	parent::__construct($id, 'coursevenues', 'vid');
		$this->Get($id); 
	} // fn __construct
	
	function GetAddress($separator = '<br />')
	{	
		$lines = array();
		if ($this->details['vaddress'])
		{	$lines[] = nl2br($this->InputSafeString($this->details['vaddress']));
		}
		if ($this->details['vcity'])
		{	$lines[] = $this->InputSafeString($this->details['vcity']);
		}
		if ($this->details['vpostcode'])
		{	$lines[] = $this->InputSafeString($this->details['vpostcode']);
		}
		return implode($separator, $lines);
		
	} // end of fn GetAddress	
	
	public function GetShortDesc($sep = ', ')
	{	ob_start();
		echo $this->details['vname'];
		if ($this->details['vcity'])
		{	echo $sep, $this->details['vcity'];
		}
		return $this->InputSafeString(ob_get_clean());
	} // end of fn GetShortDesc
	
	function GetAll()
	{
		$venues = array();
		
		if($result = $this->db->Query('SELECT * FROM coursevenues ORDER BY vname ASC'))
		{	while($row = $this->db->FetchArray($result))
			{	$venues[] = $row;
			}
		}
		
		return $venues;
	} // end of fn GetAll	
	
	function GetAllRaw()
	{	$raw = array();
		
		foreach ($this->GetAll() as $venue)
		{	$raw[$venue['vid']] = $venue['vname'];	
		}
		
		return $raw;
	} // end of fn GetAllRaw	
	
} // end of defn Venue
?>
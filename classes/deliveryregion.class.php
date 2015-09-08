<?php
class DeliveryRegion extends BlankItem
{
	function __construct($id = 0)
	{	parent::__construct($id, 'delregions', 'drid');
		$this->Get($id); 
	} // fn __construct
	
	public function GetCountries()
	{	$countries = array();
		if ($this->id)
		{	$sql = 'SELECT * FROM countries WHERE region=' . $this->id;
			if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$countries[] = $row;
				}
			}
		}
		return $countries;
	} // end of fn GetCountries
	
	public function GetOptions($liveonly = true,$product_weight=0.00)
	{	$options = array();
		if ($this->id){
			$sql = "SELECT * FROM deliveryoptions WHERE region='". $this->id."'";
			
			if($liveonly){	
				$sql .= " AND live='1'";
			}
			
			$sql .= " AND '".$product_weight."' >= from_weight AND '".$product_weight."' <= to_weight";
			
			$sql .= " ORDER BY listorder, id";
			
			if($result = $this->db->Query($sql)){	
				while ($row = $this->db->FetchArray($result)){	
					$options[] = $row;
				}
			}
			
			if (!$options){	
				$options = $this->GetFallbackOptions($liveonly);
			}
		}
				
		return $options;		
	} // end of fn GetOptions
	
	public function GetFallbackOptions($liveonly = true)
	{	$options = array();
		$sql = 'SELECT * FROM deliveryoptions WHERE region=0';
		if ($liveonly)
		{	$sql .= ' AND live=1';
		}
		$sql .= ' ORDER BY listorder, id';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$options[] = $row;
			}
		}
		
		return $options;
	} // end of fn GetFallbackOptions
	
} // end of defn DeliveryRegion
?>
<?php
class ReferAFriendReward extends BlankItem
{	
	public function __construct($id = null)
	{	parent::__construct($id, 'referrewards', 'rrid');
	} // fn __construct
	
	public function GetUsed()
	{	$used = array();
		$sql = 'SELECT * FROM referrewardsused WHERE rrid=' . (int)$this->id;
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$used[$row['ruid']] = $row;
			}
		}
		return $used;
	} // end of fn GetRewards
	
} // end of class defn ReferAFriendReward
?>
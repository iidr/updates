<?php
class PostComment extends Base
{
	public $id;
	public $details = array();	
	private $author;
	private $require_moderation = false;
	
	public function __construct($id = null)
	{
		parent::__construct();
		
		if(!is_null($id))
		{
			$this->Get($id);
		}
	}
	
	public function Reset()
	{
		$this->id = 0;
		$this->details = array();
	}
	
	public function Get($id)
	{
		$this->Reset();
		
		if (is_array($id))
		{	
			$this->details = $id;
			$this->id = $id["cid"];
		} 
		else
		{	
			if ($result = $this->db->Query("SELECT * FROM comments WHERE cid=" . (int)$id))
			{	
				if ($row = $this->db->FetchArray($result))
				{	
					$this->Get($row);
				}
			}
		}
	}
	
	public function GetAuthor()
	{
		if(is_null($this->author))
		{
			$this->author = new Student($this->details['sid']);
		}
		
		return $this->author;
	}
	
	public function GetAuthorName()
	{
		if($a = $this->GetAuthor())
		{	
			return $a->details['firstname'] .' '. $a->details['surname'];
		}
	}
	
	public function CanView()
	{
		return (int)$this->details['live'];	
	}
	
	public function Save()
	{
		if(!isset($this->details['dateadded']))
		{
			$this->details['dateadded'] = date('Y-m-d H:i:s');	
		}
		
		$this->details['live'] = !$this->require_moderation;
		
		$update = ''; 
			
		foreach($this->details as $key => $value)
		{
			$update .= $this->SQLSafe($key) ." = '". $this->SQLSafe($value) ."', ";
		}
		
		$update = substr($update, 0, -2);
		
		if(isset($this->details['cid']))
		{		
			$sql = "UPDATE comments SET ". $update ." WHERE cid = ". (int)$this->details['cid'];
		}
		else
		{	
			$sql = "INSERT INTO comments SET " . $update;
		}
		
		if($result = $this->db->Query($sql))
		{
			$this->Get($this->details['cid']);
			return true;
		}
		
	}
}

?>
<?php
class MailTemplate extends Base
{	public $id = 0;
	public $details = array();
	public $fields = array();

	function __construct($id = 0)
	{	parent::__construct();
		$this->Get($id);
	} //  end of fn __construct
	
	
	function Get($id = 0)
	{	$this->Reset();
		
		if (is_array($id))
		{	$this->id = (int)$id["mailid"];
			$this->details = $id;
			$this->GetFields();
		} else
		{	if ((int)$id)
			{	$sql = "SELECT * FROM mailtemplates WHERE mailid=" . (int)$id;
			} else
			{	$sql = "SELECT * FROM mailtemplates WHERE mailname='" . $this->SQLSafe($id) . "'";
			}
			if ($result = $this->db->Query($sql))
			{	if ($row = $this->db->FetchArray($result))
				{	$this->Get($row);
				}
			}
		}
		
	} // end of fn Get
	
	function GetFields()
	{	$this->fields = array();
		$sql = "SELECT mailfields.* FROM mailfields, mailtemplatefields WHERE mailfields.mfid=mailtemplatefields.mfid AND mailtemplatefields.mailid={$this->id} ORDER BY mailfields.fieldname";
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$this->fields[$row["fieldname"]] = $row;
			}
		}
	} // end of fn GetFields
	
	function Reset()
	{	$this->id = 0;
		$this->details = array();
		$this->fields = array();
	} // end of fn Reset
		
	function BuildHTMLEmailText($field_overrides = array()){	
		$text = trim(stripslashes($this->details["htmltext"]));
	
		if($text!='' && count($this->fields)>0){
			foreach($this->fields as $fieldname=>$fielddetails){	
				if(isset($field_overrides[$fieldname])){	
					$replace = $field_overrides[$fieldname];
				}else{	
					$replace = $fielddetails["fieldvalue"];
				}
				$text = str_replace("{" . $fieldname . "}", $replace, $text);
			}
		}
		return $text;
	} // end of fn BuildHTMLEmailText
		
	function BuildHTMLPlainText($field_overrides = array())
	{	$text = stripslashes($this->details["plaintext"]);
		foreach ($this->fields as $fieldname=>$fielddetails)
		{	
			if (isset($field_overrides[$fieldname]))
			{	$replace = $field_overrides[$fieldname];
			} else
			{	$replace = $fielddetails["fieldvalue"];
			}
			$text = str_replace("{" . $fieldname . "}", $replace, $text);
		}
		return $text;
	} // end of fn BuildHTMLPlainText

} // end of defn MailTemplate
?>
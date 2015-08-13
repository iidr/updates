<?php
class DiscountCode extends BlankItem
{	protected $prodtypes = array('course'=>'Courses', 'event'=>'Events','store'=>'Store products');

	public function __construct($id = null)
	{	parent::__construct($id, 'discountcodes', 'discid');
	} // fn __construct
	
	public function GetByCode($disccode = '')
	{	$this->Reset();
		$sql = 'SELECT * FROM discountcodes WHERE (startdate="0000-00-00" OR "'.$this->datefn->SQLDate().'" >=startdate) AND (enddate="0000-00-00" OR "'.$this->datefn->SQLDate().'" <= enddate) AND disccode="' . $this->SQLSafe($disccode) . '" AND live="1"';
		if ($result = $this->db->Query($sql))
		{	if ($row = $this->db->FetchArray($result))
			{	$this->Get($row);
			}
		}
	} // end of fn GetByCode
	
} // end of class DiscountCode
?>
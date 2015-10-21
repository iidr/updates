<?php
include_once('sitedef.php');

class VenuesListPage extends AdminVenuePage
{	private $filter = '';

	public function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	protected function VenuesLoggedInConstruct()
	{	parent::VenuesLoggedInConstruct();
		
		$this->filter = $_GET['filter'];
	} // end of fn VenuesLoggedInConstruct
	
	protected function VenueBody()
	{	echo $this->FilterForm(), $this->VenuesList();
	} // end of fn VenueBody
	
	function FilterForm()
	{	ob_start();
		echo '<form class="akFilterForm"><span>Filter</span><input type="text" name="filter" value="', $this->InputSafeString($this->filter), '" /><input type="submit" class="submit" value="Get" /><div class="clear"></div></form>';
		return ob_get_clean();
	} // end of fn FilterForm
	
	private function VenuesList()
	{	ob_start();
		echo '<table><tr class="newlink"><th colspan="6"><a href="venueedit.php">Create new venue</a></th></tr><tr><th>Venue</th><th>Name</th><th>Address</th><th>Map ref.</th><th>Campus Link</th><th>Actions</th></tr>';
		foreach ($this->Venues() as $venue_row)
		{	$venue = new AdminVenue($venue_row);
			echo '<tr class="stripe', $i++ % 2, '"><td>', $this->InputSafeString($venue->details['adminlabel']), '</td><td>', $this->InputSafeString($venue->details['vname']), '</td><td>', $venue->GetAddress(), '</td><td>';
			if ($venue->details['vlat'] || $venue->details['vlng'])
			{	echo 'Longitude: ', number_format($venue->details['vlng'], 6), '<br />Latitude: ', number_format($venue->details['vlat'], 6);
			}
			echo '</td><td>', $venue->details['campus_link'], '</td><td><a href="venueedit.php?id=', $venue->id, '">edit</a>';
			if ($histlink = $this->DisplayHistoryLink('coursevenues', $venue->id))
			{	echo '&nbsp;|&nbsp;', $histlink;
			}
			
			if ($venue->CanDelete())
			{	echo '&nbsp;|&nbsp;<a href="venueedit.php?id=', $venue->id, '&delete=1">delete</a>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
		return ob_get_clean();
	} // end of fn VenuesList
	
	private function Venues()
	{	$venues = array();
		$where = array();
		
		if ($filter = $this->SQLSafe($this->filter))
		{	$where[] = '(vname LIKE "%' . $filter . '%" OR vcity LIKE "%' . $filter . '%" OR vaddress LIKE "%' . $filter . '%")';
		}
		
		$sql = 'SELECT * FROM coursevenues';
		if ($where)
		{	$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' ORDER BY vname ASC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$venues[] = $row;
			}
		} else echo '<p>', $sql, ': ', $this->db->Error(), '</p>';
		
		return $venues;	
	} // end of fn Venues
	
} // end of defn VenuesListPage

$page = new VenuesListPage();
$page->Page();
?>
<?php
include_once('sitedef.php');

class AskImamSubmissionsPage extends AdminAskImamPage
{	var $unmarked = 0;
	var $startdate = '';
	var $enddate = '';

	function __construct()
	{	parent::__construct();
		$this->css[] = 'datepicker.css';
		$this->js[] = 'datepicker.js';

		$this->unmarked = $_GET['unmarked'] || !$_GET;
		
		// set up dates
		if (($ys = (int)$_GET['ystart']) && ($ds = (int)$_GET['dstart']) && ($ms = (int)$_GET['mstart']))
		{	$this->startdate = $this->datefn->SQLDate(mktime(0,0,0,$ms, $ds, $ys));
		} else
		{	$this->startdate = $this->datefn->SQLDate(strtotime('-2 weeks'));
		}
		
		if (($ys = (int)$_GET['yend']) && ($ds = (int)$_GET['dend']) && ($ms = (int)$_GET['mend']))
		{	$this->enddate = $this->datefn->SQLDate(mktime(0,0,0,$ms, $ds, $ys));
		} else
		{	$this->enddate = $this->datefn->SQLDate();
		}

		$this->breadcrumbs->AddCrumb('askimamsubmissions.php', 'Submissions');
	} //  end of fn __construct
	
	function AskImamBody()
	{	echo $this->FilterForm();
		if ($submissions = $this->GetSubmissions())
		{	echo '<table><tr><th>Submitted</th><th>By</th><th>Question</th><th>Admin notes</th><th>Actions</th></tr>';
			foreach ($submissions as $sub_row)
			{	$sub = new AdminAskImamSubmission($sub_row);
				echo '<tr><td>', date('d/m/y @H:i', strtotime($sub->details['asktime'])), '</td><td>', $this->InputSafeString($sub->details['subname']), ', <a href="mailto:', $this->InputSafeString($sub->details['subemail']), '">', $this->InputSafeString($sub->details['subemail']), '</a></td><td>', $sub->QuestionSample(),' </td><td>', $this->InputSafeString($sub->details['adminnotes']), '</td><td><a href="askimamsubmission.php?id=', $sub->id, '">view</a>';
				if ($sub->CanDelete())
				{	echo '&nbsp;|&nbsp;<a href="askimamsubmission.php?id=', $sub->id, '&delete=1">delete</a>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		} else
		{	echo '<h4>no submissions found for filter</h4>';
		}
	} // end of fn AskImamBody
	
	private function FilterForm()
	{	ob_start();
		class_exists('Form');
		$startfield = new FormLineDate('', 'start', $this->startdate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		$endfield = new FormLineDate('', 'end', $this->enddate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		echo '<form id="filter-form" class="akFilterForm" action="', $_SERVER['SCRIPT_NAME'], '" method="get"><span>Submitted from</span>';
		$startfield->OutputField();
		echo '<span>to</span>';
		$endfield->OutputField();
		echo '<span>only without admin notes</span><input type="checkbox" name="unmarked" value="1"', $this->unmarked ? ' checked="checked"' : '', ' /><input type="submit" class="submit" value="Get" /><div class="clear"></div></form>';
		return ob_get_clean();
	} // end of fn FilterForm
	
	public function GetSubmissions()
	{	$subs = array();
		
		$where = array();
		
		if ($this->startdate)
		{	$where[] = 'asktime>="' . $this->startdate . ' 00:00:00"';
		}
		if ($this->enddate)
		{	$where[] = 'asktime<="' . $this->enddate . ' 23:59:59"';
		}
		
		if ($this->unmarked)
		{	$where[] = 'adminnotes=""';
		}
		
		$sql = 'SELECT * FROM askimamsubmissions';
		if ($wstr = implode(' AND ', $where))
		{	$sql .= ' WHERE ' . $wstr;
		}
		$sql .= ' ORDER BY asktime DESC';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	$subs[] = $row;
			}
		}
		
		return $subs;
	} // end of fn GetSubmissions
	
} // end of defn AskImamSubmissionsPage

$page = new AskImamSubmissionsPage();
$page->Page();
?>
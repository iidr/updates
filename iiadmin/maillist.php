<?php
include_once('sitedef.php');

class MembersListPage extends AdminMailListPage
{
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function MailListLoggedInConstruct()
	{	parent::MailListLoggedInConstruct();
		$this->breadcrumbs->AddCrumb('maillist.php', 'Mailing list');
		$this->css[] = 'datepicker.css';
		$this->js[] = 'datepicker.js';
	} // end of fn MailListLoggedInConstruct
	
	function MailListBody()
	{	$maillist = $this->GetMailList();
		echo $this->FilterForm(), 
				$this->OptionsList(count($maillist)), 
				$this->ListMailingList($maillist);
	} // end of fn MailListBody
	
	public function ListMailingList($maillist = array())
	{	ob_start();
		if ($maillist)
		{	echo '<table><tr><th>Name</th><th>Email</th><th>When registered</th></tr>';
			foreach ($maillist as $row)
			{	echo '<tr><td>', $this->InputSafeString($row['listname']), '</td><td><a href="mailto:', $email = $this->InputSafeString($row['listemail']), '">', $email, '</td><td>', date('d-M-y @H:i', strtotime($row['registered'])), '</td></tr>';
			}
			echo '</table>';
		} else
		{	echo '<h4>Nobody has registered with this criteria</h4>';
		}
		return ob_get_clean();
	} // end of fn ListMailingList
	
	private function FilterForm()
	{	ob_start();
		class_exists('Form');
		$startfield = new FormLineDate('', 'start', $this->startdate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		$endfield = new FormLineDate('', 'end', $this->enddate, $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		echo '<form id="filter-form" class="akFilterForm" action="', $_SERVER['SCRIPT_NAME'], '" method="get"><span>Submitted from</span>';
		$startfield->OutputField();
		echo '<span>to</span>';
		$endfield->OutputField();
		echo '<span>in name / email</span><input type="text" name="regname" value="', $this->InputSafeString($this->regname), '" /><input type="submit" class="submit" value="Get" /><div class="clear"></div></form>';
		return ob_get_clean();
	} // end of fn FilterForm
	
	function OptionsList($membercount = 0)
	{	ob_start();
	
		// build list of filter options
		$filter_applied = array();
		$link_paras = array();
		
		if ($this->startdate)
		{	$filter_applied[] = 'from <strong>' . date('d-M-y', $start_stamp = strtotime($this->startdate)) . '</strong>';
			$link_paras[] = 'dstart=' . date('j', $start_stamp);
			$link_paras[] = 'mstart=' . date('n', $start_stamp);
			$link_paras[] = 'ystart=' . date('Y', $start_stamp);
		}
		
		if ($this->enddate)
		{	$filter_applied[] = 'to <strong>' . date('d-M-y', $end_stamp = strtotime($this->enddate)) . '</strong>';
			$link_paras[] = 'dend=' . date('j', $end_stamp);
			$link_paras[] = 'mend=' . date('n', $end_stamp);
			$link_paras[] = 'yend=' . date('Y', $end_stamp);
		}

		if ($this->regname)
		{	$filter_applied[] = 'in name / email <strong>' . $this->InputSafeString($this->regname) . '</strong>';
			$link_paras[] = 'regname=' . $this->InputSafeString($this->regname);
		}
		
		echo '<div class="cblFilterInfo"><div class="cblFilterInfoFilter">filter applied: ';
		if ($filter_applied)
		{	echo implode('; ', $filter_applied);
			$link_para_string = '?' . implode('&', $link_paras);
		} else
		{	echo 'none';
		}
		echo ' ... ', $membercount, ' registrations found</div>';
		if ($membercount)
		{	echo '<ul><li><a href="maillist_csv.php', $link_para_string, '" target="_blank">download csv of these</a></li>';
		/*	if ($this->CanAdminUser('site-emails'))
			{
				echo '<li><a href="members_setmaillist.php', $link_para_string, '" target="_blank">send email to these members</a></li>';
			}*/
			echo '</ul>';
		}
		echo '<div class="clear"></div></div>';
		return ob_get_clean();
	} // end of fn OptionsList
	
} // end of defn MembersListPage

$page = new MembersListPage();
$page->Page();
?>
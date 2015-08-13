<?php
include_once('sitedef.php');

class IncomePerMonthPage extends AccountsMenuPage
{	var $month= 0;
	var $year = 0;

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AccountsLoggedInConstruct()
	{	parent::AccountsLoggedInConstruct();
		$this->css[] = 'adminorders.css';
		
		if (!$this->month = (int)$_GET['mdate'])
		{	$this->month = date('n');
		}
		if (!$this->year = (int)$_GET['ydate'])
		{	$this->year = date('Y');
		}
		
		$this->breadcrumbs->AddCrumb('incomepermonth.php', 'Income per month');
		$this->breadcrumbs->AddCrumb('incomeperday.php', 'Income per day for ' . date('F Y', mktime(0,0,0,$this->month, 1, $this->year)));
	} // end of fn AccountsLoggedInConstruct
	
	function AccountsBody()
	{	echo $this->ChooseForm(), $this->PerDayList();
	} // end of fn AccountsBody
	
	private function PerDayList()
	{	ob_start();
		$ia = new IncomeAnalysis();
		if ($days = $ia->IncomePerDayForMonth($this->month, $this->year))
		{	$totals = array('income'=>0, 'orders'=>0, 'items'=>0);
			echo '<table><tr><th>Day</th><th>Total income</th><th>Number of orders</th><th>Number of items</th></tr>';
			foreach ($days as $day)
			{	$totals['income'] += $day['income'];
				$totals['orders'] += $day['orders'];
				$totals['items'] += $day['items'];
				echo '<tr><td>', $day['disp'], '</a></td><td class="num">', number_format($day['income'], 2), '</td><td>', (int)$day['orders'], '</td><td>', (int)$day['items'], '</td></tr>';
			}
			echo '<tr><th>Totals</th><th class="num">', number_format($totals['income'], 2), '</th><th>', (int)$totals['orders'], '</th><th>', (int)$totals['items'], '</th></tr></table><p><img src="incomeperday_graph.php?ydate=', $this->year, '&mdate=', $this->month, '" /></p>';
		//	$this->VarDump($days);
		}
		return ob_get_clean();
	} // end of fn PerDayList
	
	function ChooseForm()
	{	$startsql = date('Y-m-01', mktime(0, 0, 0, $this->mstart, 1, $this->ystart));
		class_exists('Form');
		$startfield = new MonthYearSelect('From', 'date', date('Y-m-d', mktime(0, 0, 0, $this->month, 1, $this->year)), $this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		echo '<form id="orderSelectForm" class="akFilterForm" action="', $_SERVER['SCRIPT_NAME'], '" method="get"><span>Show</span>';
		$startfield->OutputField();
		echo '<input type="submit" class="submit" value="Get Summary" /><div class="clear"></div></form>';
	} // end of fn ChooseForm
	
} // end of defn OrderListingPage

$page = new IncomePerMonthPage();
$page->Page();
?>
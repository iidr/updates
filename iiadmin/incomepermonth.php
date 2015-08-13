<?php
include_once('sitedef.php');

class IncomePerMonthPage extends AccountsMenuPage
{	var $ystart = 0;
	var $mstart = 0;
	var $yend = 0;
	var $mend = 0;

	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function AccountsLoggedInConstruct()
	{	parent::AccountsLoggedInConstruct();
		$this->css[] = 'adminorders.css';
		
		if (($ys = (int)$_GET['ystart']) && ($ms = (int)$_GET['mstart']))
		{	$this->ystart = $ys;
			$this->mstart = $ms;
		} else
		{	$this->ystart = date('Y', strtotime('-11 months'));
			$this->mstart = date('m', strtotime('-11 months'));
		}
		
		if (($ye = (int)$_GET['yend']) && ($me = (int)$_GET['mend']))
		{	$this->yend = $ye;
			$this->mend = $me;
		} else
		{	$this->yend = date('Y');
			$this->mend = date('m');
		}
		
		$this->breadcrumbs->AddCrumb('incomepermonth.php', 'Income per month');
	} // end of fn AccountsLoggedInConstruct
	
	function AccountsBody()
	{	echo $this->ChooseForm(), $this->PerMonthList();
	} // end of fn AccountsBody
	
	private function PerMonthList()
	{	ob_start();
		$ia = new IncomeAnalysis();
		if ($months = $ia->IncomePerMonth($this->mstart, $this->ystart, $this->mend, $this->yend))
		{	$totals = array('income'=>0, 'orders'=>0, 'items'=>0);
			echo '<table><tr><th>Month</th><th>Total income</th><th>Number of orders</th><th>Number of items</th></tr>';
			foreach ($months as $month)
			{	$totals['income'] += $month['income'];
				$totals['orders'] += $month['orders'];
				$totals['items'] += $month['items'];
				echo '<tr><td><a href="incomeperday.php?mdate=', date('n', $month['stamp']), '&ydate=', date('Y', $month['stamp']), '">', $month['disp'], '</a></td><td class="num">', number_format($month['income'], 2), '</td><td>', (int)$month['orders'], '</td><td>', (int)$month['items'], '</td></tr>';
			}
			echo '<tr><th>Totals</th><th class="num">', number_format($totals['income'], 2), '</th><th>', (int)$totals['orders'], '</th><th>', (int)$totals['items'], '</th></tr></table><p><img src="incomepermonth_graph.php?ystart=', $this->ystart, '&mstart=', $this->mstart, '&yend=', $this->yend, '&mend=', $this->mend, '" /></p>';
		//	$this->VarDump($months);
		}
		return ob_get_clean();
	} // end of fn PerMonthList
	
	function ChooseForm()
	{	class_exists('Form');
		$startfield = new MonthYearSelect('From', 'start', date('Y-m-d', mktime(0, 0, 0, $this->mstart, 1, $this->ystart)), 
													$this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		$endfield = new MonthYearSelect('to', 'end', date('Y-m-d', mktime(0, 0, 0, $this->mend, 1, $this->yend)), 
													$this->datefn->GetYearList(date('Y'), 1999-date('Y')));
		echo '<form id="orderSelectForm" class="akFilterForm" action="', $_SERVER['SCRIPT_NAME'], '" method="get"><span>From</span>';
		$startfield->OutputField();
		echo '<span>to</span>';
		$endfield->OutputField();
		echo '<input type="submit" class="submit" value="Get Summary" /><div class="clear"></div></form>';
	} // end of fn ChooseForm
	
} // end of defn OrderListingPage

$page = new IncomePerMonthPage();
$page->Page();
?>
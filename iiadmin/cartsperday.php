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
		
		$this->breadcrumbs->AddCrumb('cartspermonth.php', 'Cart conversion per month');
		$this->breadcrumbs->AddCrumb('cartsperday.php', 'Cart conversion per day for ' . date('F Y', mktime(0,0,0,$this->month, 1, $this->year)));
	} // end of fn AccountsLoggedInConstruct
	
	function AccountsBody()
	{	echo $this->ChooseForm(), $this->PerDayList();
	} // end of fn AccountsBody
	
	private function PerDayList()
	{	ob_start();
		$ia = new ConversionAnalysis();
		if ($days = $ia->CartsPerDayForMonth($this->month, $this->year))
		{	$totals = array('carts'=>0, 'orders'=>0, 'paid'=>0);
			echo '<table><tr><th>Day</th><th>Total carts</th><th colspan="2">Converted to orders</th><th colspan="2">Converted to paid</th></tr>';
			foreach ($days as $day)
			{	$totals['carts'] += $day['carts'];
				$totals['orders'] += $day['orders'];
				$totals['paid'] += $day['paid'];
				echo '<tr><td>', $day['disp'], '</a></td><td class="num">', (int)$day['carts'], '</td><td class="num">', (int)$day['orders'], '</td><td class="num">', $day['carts'] ? number_format((100 * $day['orders']) / $day['carts'], 1) : '0.0', '%</td><td class="num">', (int)$day['paid'], '</td><td class="num">', $day['carts'] ? number_format((100 * $day['paid']) / $day['carts'], 1) : '0.0', '%</td></tr>';
			}
			echo '<tr><th>Totals</th><th class="num">', (int)$totals['carts'], '</th><th class="num">', (int)$totals['orders'], '</th><th class="num">', $totals['carts'] ? number_format((100 * $totals['orders']) / $totals['carts'], 1) : '0.0', '%</th><th class="num">', (int)$totals['paid'], '</th><th class="num">', $totals['carts'] ? number_format((100 * $totals['paid']) / $totals['carts'], 1) : '0.0', '%</th></tr></table>';
			//'<p><img src="incomeperday_graph.php?ydate=', $this->year, '&mdate=', $this->month, '" /></p>';
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
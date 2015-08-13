<?php
include_once('sitedef.php');

class CartsPerMonthPage extends AccountsMenuPage
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
		
		$this->breadcrumbs->AddCrumb('cartspermonth.php', 'Cart conversion per month');
	} // end of fn AccountsLoggedInConstruct
	
	function AccountsBody()
	{	echo $this->ChooseForm(), $this->PerMonthList();
	} // end of fn AccountsBody
	
	private function PerMonthList()
	{	ob_start();
		$ia = new ConversionAnalysis();
		if ($months = $ia->CartsPerMonth($this->mstart, $this->ystart, $this->mend, $this->yend))
		{	
			$totals = array('carts'=>0, 'orders'=>0, 'paid'=>0);
			echo '<table><tr><th>Month</th><th>Total carts</th><th colspan="2">Converted to orders</th><th colspan="2">Converted to paid</th></tr>';
			foreach ($months as $month)
			{	$totals['carts'] += $month['carts'];
				$totals['orders'] += $month['orders'];
				$totals['paid'] += $month['paid'];
				echo '<tr><td><a href="cartsperday.php?mdate=', date('n', $month['stamp']), '&ydate=', date('Y', $month['stamp']), '">', $month['disp'], '</a></td><td class="num">', (int)$month['carts'], '</td><td class="num">', (int)$month['orders'], '</td><td class="num">', $month['carts'] ? number_format((100 * $month['orders']) / $month['carts'], 1) : '0.0', '%</td><td class="num">', (int)$month['paid'], '</td><td class="num">', $month['carts'] ? number_format((100 * $month['paid']) / $month['carts'], 1) : '0.0', '%</td></tr>';
			}
			echo '<tr><th>Totals</th><th class="num">', (int)$totals['carts'], '</th><th class="num">', (int)$totals['orders'], '</th><th class="num">', $totals['carts'] ? number_format((100 * $totals['orders']) / $totals['carts'], 1) : '0.0', '%</th><th class="num">', (int)$totals['paid'], '</th><th class="num">', $totals['carts'] ? number_format((100 * $totals['paid']) / $totals['carts'], 1) : '0.0', '%</th></tr></table>';//'<p><img src="incomepermonth_graph.php?ystart=', $this->ystart, '&mstart=', $this->mstart, '&yend=', $this->yend, '&mend=', $this->mend, '" /></p>';
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
	
} // end of defn CartsPerMonthPage

$page = new CartsPerMonthPage();
$page->Page();
?>
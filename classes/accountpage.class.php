<?php
class AccountPage extends BasePage
{	protected $ac_area = '';
	protected $ac_header = '';
	protected $ac_menu;
	protected $refer_perpage = 20;

	function __construct($pageName = '')
	{	if (!$pageName)
		{	$pageName = 'my-account';
		}
		
		parent::__construct($pageName);
		
		if ($this->user->id)
		{	$this->LoggedInConstruct();
		} else
		{	$this->RedirectToRegister();
		}
		
	} // end of fn __construct

	public function RedirectToRegister()
	{	$this->Redirect('register.php');//?login=failed');
	} // end of fn RedirectToRegister
	
	function LoggedInConstruct($ac_area = '')
	{	$this->AddBreadcrumb('My Account', $this->link->GetLink('account.php'));
		$this->css[] = 'page.css';
		$this->css[] = 'myaccount.css';
		$this->ac_menu = $this->MenuOptions();
		//$this->VarDump($this->ac_menu);
		if ($this->ac_menu[$this->ac_area = $ac_area])
		{	$this->AddBreadcrumb($this->ac_menu[$this->ac_area]['title'], $this->ac_menu[$this->ac_area]['link']);
		}
	} // end of fn LoggedInConstruct
	
	protected function PageHeaderText()
	{	return $this->ac_menu[$this->ac_area]['header'];
	} // end of fn PageHeaderText
	
	protected function BreadcrumbsRightContent()
	{	ob_start();
		if (true || $this->user->id)
		{	echo '<div id="dbCrumbsRight">', $this->InputSafeString($this->user->GetName()), '&nbsp;|&nbsp;<a href="', $this->link->GetLink('index.php?logout=1'), '">Logout</a></div><div class="clear"></div>';
		}
		return ob_get_clean();
	} // end of fn BreadcrumbsRightContent
	
	function MainBodyContent()
	{	if ($this->user->id)
		{	//echo $this->ac_area ? $this->MyAccountMenu() : '', $this->LoggedInMainBody();
			if ($this->ac_area)
			{	echo $this->MyAccountMenu(), '<div id="myAcContainer"><h1>', $this->PageHeaderText(), ($right_text = $this->PageHeaderRightContent()) ? ('<div id="myAcContainerRight">' . $right_text . '</div>') : '', '</h1>', $this->LoggedInMainBody(), '</div>';
			} else
			{	echo '<div id="myac_mainmenu"><ul>';
				foreach ($this->ac_menu as $option)
				{	if (++$count > 3)
					{	$count = 0;
						echo '</ul><div class="clear"></div><ul>';
					}
					echo '<li id="myac_main_li_', $option['name'], '"><div class="myac_main_text"><h3><a href="', $option['link'], '">', $option['title'], '</a></h3>';
					if ($option['text'])
					{	echo '<p>', $option['text'], '</p>';
					}
					echo '<a class="myac_main_button" href="', $option['link'], '">View</a></div></li>';
				}
				echo '</ul><div class="clear"></div>';
			}
		} else
		{	parent::MainBodyContent();
		}
	} // end of fn MemberBody
	
	protected function PageHeaderRightContent(){}
	function LoggedInMainBody(){}
	
	function MyAccountMenu()
	{	ob_start();
		
		echo '<div id="myacMenu"><ul>';
		
		foreach ($this->ac_menu as $option)
		{	
			echo '<li id="myac_sub_', $option['name'], '"', $option['name'] == $this->ac_area ? ' class="selected"' : '', '><a href="', $option['link'], '">', $option['title'], '<span class="myacMenuBG"></span></a></li>';
		}
		echo '</ul><div class="clear"></div></div>';
	
		return ob_get_clean();
	} // end of fn MyAccountMenu

	protected function MenuOptions()
	{	$options = array();
		$options['orders'] = array('name'=>'orders', 'link'=>SITE_URL . 'orders.php', 'title'=>'Purchase History', 'header'=>'My Purchases', 'text'=>'View detailed history of all your store purchases');
		$options['details'] = array('name'=>'details', 'link'=>SITE_URL . 'account_details.php', 'title'=>'Details', 'header'=>'My Details', 'text'=>'Edit your personal details');
		if ($this->user->CanSendReferral())
		{	$options['refer'] = array('name'=>'refer', 'link'=>SITE_URL . 'referrals.php', 'title'=>'Referral Records', 'header'=>'My Referrals', 'text'=>'Refer-a-friend referral scheme');
		}
		$options['bookings'] = array('name'=>'bookings', 'link'=>SITE_URL . 'bookings.php', 'title'=>'Courses Booked', 'header'=>'My Bookings', 'text'=>'View detailed history of all your course bookings');
		if ($this->user->GetCurrentSubs())
		{	$options['subs'] = array('name'=>'subs', 'link'=>SITE_URL . 'my_subscriptions.php', 'title'=>'Subscriptions', 'header'=>'My Subscriptions', 'text'=>'View your subscriptions');
		}
	//	if ($this->user->GetDownloads())
	//	{	$options['downloads'] = array('name'=>'downloads', 'link'=>SITE_URL . 'downloads.php', 'title'=>'Downloads', 'header'=>'My Downloads', 'text'=>'');
	//	}
		return $options;
	} // end of fn MenuOptions
		
	protected function ReferralsTable()
	{	ob_start();
		if ($referrals = $this->user->GetReferrals())
		{	if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->refer_perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->refer_perpage;
			
			echo '<table class="myacList"><tr><th>Sent to</th><th>Referred</th><th>Your reward</th><th>Created</th><th>Used by you</th></tr>';
		
			foreach ($referrals as $referral_row)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
					
					$referral = new ReferAFriend($referral_row);
					//$this->VarDump($referral);
					echo '<tr><td>', $this->InputSafeString($referral->details['refername']), ' (', $this->InputSafeString($referral->details['referemail']), ')</td><td>', date('d M Y', strtotime($referral->details['refertime'])), '</td>';
					if ($reward_row = $referral->GetRewardForUser($this->user->id))
					{	$reward = new ReferAFriendReward($reward_row);
						echo '<td>&pound;', number_format($reward->details['amount'], 2), '</td><td>', date('d M Y', strtotime($reward->details['created'])), '</td><td>';
						$used_amount = 0;
						$lines = array();
						if ($used = $reward->GetUsed())
						{	foreach ($used as $use)
							{	$lines[] = '&pound;' . number_format($use['usedamount'], 2) . ' on ' . date('d M Y', strtotime($use['usedtime']));
								$used_amount += $use['usedamount'];
							}
						}
						if ($used_amount < $reward->details['amount'])
						{	$lines[] = 'use by ' . date('d M Y', strtotime($reward->details['expires']));
						}
						echo implode('<br />', $lines), '</td>';
					} else
					{	echo '<td class="refTableNoReward">not generated</td><td></td><td></td>';
					}
					echo '</tr>';
				}
			}

			echo '</table>';
			if (count($referrals) > $this->refer_perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($referrals), $this->refer_perpage, 'myReferralsContainer', 'ajax_referralslist.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
		}
		return ob_get_clean();
		//print_r($this->user->GetReferrals());
	} // end of fn ReferralsTable
		
	protected function AffRewardsTable()
	{	ob_start();
		if ($rewards = $this->user->GetAffRewards())
		{	if ($_GET['page'] > 1)
			{	$start = ($_GET['page'] - 1) * $this->refer_perpage;
			} else
			{	$start = 0;
			}
			$end = $start + $this->refer_perpage;
			
			//$this->VarDump($rewards);
			
			echo '<table class="myacList"><tr><th>Reward earned</th><th class="num">Amount</th><th>Reward used</th><th class="num">Still available</th><th>Use by</th></tr>';
		
			foreach ($rewards as $reward_row)
			{	if (++$count > $start)
				{	if ($count > $end)
					{	break;
					}
					echo '<tr><td>', date('d M Y', strtotime($reward_row['created'])), '</td><td class="num">&pound;', number_format($reward_row['amount'], 2), '</td><td class="affTableUsed">', $this->AffRewardsTableUsedList($reward_row['reward_used']), '</td><td class="num">', $reward_row['reward_left'] ? ('&pound;' . number_format($reward_row['reward_left'], 2)) : '', '</td><td>', $reward_row['reward_left'] ? date('d M Y @H:i', strtotime($reward_row['expires'])) : '', '</td></tr>';
				}
			}

			echo '</table>';
			if (count($rewards) > $this->refer_perpage)
			{	
				$pag = new AjaxPagination($_GET['page'], count($rewards), $this->refer_perpage, 'myReferralsContainer', 'ajax_referralslist.php', $_GET);
				echo '<div class="pagination">', $pag->Display(), '</div><div class="clear"></div>';
			}
		}
		return ob_get_clean();
		//print_r($this->user->GetReferrals());
	} // end of fn AffRewardsTable
	
	public function AffRewardsTableUsedList($used = array())
	{	if ($used && is_array($used))
		{	//print_r($used);
			echo '<ul>';
			foreach ($used as $used_row)
			{	echo '<li>', date('d M Y', strtotime($used_row['usedtime'])), ' - &pound;', number_format($used_row['usedamount'], 2), '</li>';
			}
			echo '</ul>';
		}
	} // end of fn AffRewardsTableUsedList
	
} // end of defn DashboardPage
?>
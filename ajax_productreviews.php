<?php 
require_once('init.php');

class AjaxProductReviews extends BasePage
{		
	function __construct()
	{	parent::__construct();
		
		switch ($_GET['action'])
		{	case 'create': echo $this->CreateFromPost();
							break;
			case 'list': echo $this->ReviewList();
							break;
		}
		
	//	$this->VarDump($_GET);
	//	$this->VarDump($_POST);
	//	echo $this->user->id;
	
	} // end of fn __construct
	
	public function ReviewList()
	{	switch ($_GET['ptype'])
		{	case 'store': $product = new StoreProduct($_GET['prodid']);
						$reviews = $product->ReviewList((int)$_GET['plimit']);
						echo $reviews['text'];
						break;
			case 'course': $course = new CourseContent($_GET['prodid']);
						echo $course->ReviewList((int)$_GET['plimit'], $this->user->id);
						break;
		}
	} // end of fn ReviewList
	
	public function CreateFromPost()
	{	ob_start();
		$fail = array();
		$success = array();
		
		if ($this->user->CanReview($_GET['prodid'], $_GET['ptype']))
		{	if ($this->user->GetReviewForProduct($_GET['prodid'], $_GET['ptype']))
			{	$fail[] = 'You have already reviewed this';
			} else
			{	$fields = array('revdate="' . $this->datefn->SQLDateTime() . '"');
				
				if (!$reviewertext = $_POST['reviewertext'])
				{	$reviewertext = $this->user->GetName();
				}
				$fields[] = 'reviewertext="' . $this->SQLSafe($reviewertext) . '"';

				$fields[] = 'revtitle="' . $this->SQLSafe($_POST['revtitle']) . '"';
				
				if ($sid = (int)$this->user->id)
				{	$fields[] = 'sid=' . $sid;
				} else
				{	$fail[] = 'Reviewer not found';
				}
				
				if ($pid = (int)$_GET['prodid'])
				{	$fields[] = 'pid=' . $pid;
				} else
				{	$fail[] = 'Product not found';
				}
				
				if ($rating = round($_POST['rating'], 1))
				{	$fields[] = 'rating=' . $rating;
				} else
				{	$fail[] = 'You must give a rating';
				}
				
				if ($ptype = $this->SQLSafe($_GET['ptype']))
				{	$fields[] = 'ptype="' . $ptype . '"';
					$fields[] = 'suppressed=' . ($this->FlagFileSet('mod_' . $_GET['ptype']) ? '1' : '0');
				} else
				{	$fail[] = 'Product type not found';
				}
				
				if (($review = $this->SQLSafe($_POST['text'])) && (strlen($_POST['text']) >= 10))
				{	$fields[] = 'review="' . $review . '"';
				} else
				{	$fail[] = 'Please give more of a review (minimum 10 letters)';
				}
				//$fail[] = 'text';
				//$success[] = 'dsfsdfs';
				//print_r($_POST);
				if (!$fail)
				{	$sql = 'INSERT INTO productreviews SET ' . implode(', ', $fields);
					if ($result = $this->db->Query($sql))
					{	if ($this->db->AffectedRows())
						{	$success[] = 'Thank you for submitting your review';
							$this->ReviewAdminEmail();
						}
					}
				}
			}
		} else
		{	$fail[] = 'You do not have access to review this';
		}

		if ($fail)
		{	echo '<div class="revFail">', implode(', ', $fail), '</div>';
		}
		if ($success)
		{	echo '<div class="revSuccess">', implode(', ', $success), '</div>';
		}
		
		echo $this->user->ReviewFormInner($_GET['prodid'], $_GET['ptype']);
		return ob_get_clean();
	} // end of fn CreateFromPost
	
	public function ReviewAdminEmail(){	
		ob_start();
		$sep = "\n";
		$mail = new HTMLMail();
		
		switch ($_GET['ptype']){	
			case 'course':
			case 'event':
				$subject = 'IIDR course/event review needs moderating';
				echo 'A new course/event review has been submitted ...', $sep, 'admin link: ', SITE_URL, 'iiadmin/coursereviews.php?id=', intval($_GET['prodid']);
				$mail->SetSubject($subject);
				$mail->SendEMailForArea('COURSEREVIEW', '', ob_get_clean());
				break;
			case 'product':
			default:
				$subject = 'IIDR product review needs moderating';
				echo 'A new product review has been submitted ...', $sep, 'admin link: ', SITE_URL, 'iiadmin/productreviews.php?id=', intval($_GET['prodid']);
				$mail->SetSubject($subject);
				$mail->SendEMailForArea('PRODUCTREVIEW', '', ob_get_clean());
				break;
		}
	}	
}

$page = new AjaxProductReviews();
?>
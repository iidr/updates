<?php
include_once('sitedef.php');

class ProductEditPage extends AdminProductPage
{	var $review;
	
	function __construct()
	{	parent::__construct();
	} //  end of fn __construct
	
	function ProductsLoggedInConstruct()
	{	parent::ProductsLoggedInConstruct('reviews');
		$this->css[] = 'adminreviews.css';
		$this->css[] = 'datepicker.css';
		$this->js[] = 'datepicker.js';
		
		if (isset($_POST['review']))
		{	$saved = $this->review->AdminSave($_POST, $this->product->id, 'store');
			$this->successmessage = $saved['successmessage'];
			$this->failmessage = $saved['failmessage'];
		}
		
		if ($this->review->CanDelete() && $_GET['delete'] && $_GET['confirm'])
		{	if ($this->review->Delete())
			{	header('location: productreviews.php?id=' . $this->product->id);
				exit;
			} else
			{	$this->failmessage = 'Delete failed';
			}
		}
		
		$this->breadcrumbs->AddCrumb('productreviews.php?id=' . $this->product->id, 'Reviews');
		if ($this->review->id)
		{	$this->breadcrumbs->AddCrumb('productreview.php?id=' . $this->review->id, 'by ' . $this->InputSafeString($this->review->details['reviewertext']));
		} else
		{	$this->breadcrumbs->AddCrumb('productreview.php?pid=' . $this->course->id, 'Adding review');
		}
	} // end of fn ProductsLoggedInConstruct
	
	function AssignProduct()
	{	$this->review = new AdminProductReview($_GET['id']);
		if ($this->review->id)
		{	$this->product = new AdminStoreProduct($this->review->details['pid']);
		} else
		{	$this->product = new AdminStoreProduct($_GET['pid']);
		}
	} // end of fn AssignProduct
	
	function ProductsBody()
	{	parent::ProductsBody();
		echo $this->review->AdminInputForm($this->product->id);
	} // end of fn ProductsBody
	
} // end of defn ProductEditPage

$page = new ProductEditPage();
$page->Page();
?>
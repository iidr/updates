<?php 
require_once('init.php');

class ContactUsPage extends BasePage
{	private $captcha = false;

	function __construct()
	{	parent::__construct('contact-us');
		$this->AddBreadcrumb('Contact Us');
		$this->css[] = 'page.css';
		$this->js[] = 'webforms/webforms2-p.js';
		$this->SendMailTemp();
	} // end of fn __construct
	
	function MainBodyContent()
	{	$contactpage = new PageContent('contact-us');
		echo '<h1>Contact Us</h1>',
			//'<div class="col2-wrapper "><h3>Address Details</h3><div class="clear"></div></div>',
			'<div class="contactpage-wrapper"><div class="col2-wrapper "><div class="inner clearfix">', $this->ContactForm(), '</div><div class="clear"></div></div><div class="col2-wrapper "><div class="inner clearfix">', $contactpage->HTMLMainContent(), '</div><div class="clear"></div></div><div class="clear"></div></div>';
	
	} // end of fn MemberBody
	
	function ContactForm()
	{	ob_start();
		$captcha = $this->Captcha();
		echo '<form name="contactform" method="post" class="contactform">
			<div class="clearfix">
				<label>Name:*</label>
				<input name="field1" type="text" class="text" value="', $this->InputSafeString($_POST['field1']), '" required="required" />
			</div>
			<div class="clearfix">    
				<label>Email:*</label>
				<input name="field2" type="email" class="text" value="', $this->InputSafeString($_POST['field2']), '"  required="required"/>
				<input name="email" type="text" style="display:none;" >
			</div>
			<div class="clearfix">    
				<label>Phone Number:</label>
				<input name="field3" type="text" class="text" value="', $this->InputSafeString($_POST['field3']), '"/>
				<label style="display:none;">Subject:</label>
				<input name="subject" type="text" class="text" value="" style="display:none;"/>
			</div>
			<div class="clearfix">
				<label>Message:*</label>
				<textarea class="textarea" name="field5" required="required">', $this->InputSafeString($_POST['field5']), '</textarea>
			</div>
			<div class="clearfix"><div style="float: right;">', $captcha->OutputInForm(), '</div></div>
			<div class="clearfix"><input type="submit" name="submit" class="submit" value="Submit"></div>
		</form>';
        
        return ob_get_clean();
	} // end of fn ContactForm
	
	private function Captcha()
	{	if ($this->captcha === false)
		{	$this->captcha = new ReCaptcha();
		}
		return $this->captcha;
	} // end of fn Captcha
	
	function SendMailTemp()
	{	ob_start();
		if(isset($_POST['submit']))
		{	$errors = array();
			if (empty($_POST['field1']) || empty($_POST['field2']) || empty($_POST['field5']))
			{	$errors['name'] = 'Please fill in all required fields.'. "\n";
			}
			if ($_POST['subject'] || $_POST['email'])
			{	$errors['spam'] = 'Spam Protection.'. "\n";
			}
			
			// check captcha
			if (!$errors)
			{	$captcha = $this->Captcha();
				if (!$captcha->VerifyInput())
				{	$errors['captcha'] = 'captcha code has not been entered correctly';
				}
			}
			
			if (!$errors){				
				$subject= 'IIDR - Contact Form';
					
				$mail = new HTMLMail();			
				$mail->SetSubject($subject);
				
				$message = "A new contact request is received with the following details\n";
				$message .= "name: " . $_POST['field1'] . "\n";
				$message .= "email: " . $_POST['field2'] . "\n";
				$message .= "telephone: " . $_POST['field3'] . "\n";
				$message .= "details: " . $_POST['field5'] . "\n";
				$message .= "sent: " . date('d/m/Y @H:i');
				
				$mail->SendEMailForArea('CONTACTUS', '', $message);
				
				$this->successmessage = 'Thank you for your query. We will respond to your query as soon as possible.';
			} else 
			{	$this->failmessage = implode(', ', $errors);
			}
		 }
		return ob_get_clean();	 
	} // end of fn SendMailTemp
	
} // end of defn ContactUsPage

$page = new ContactUsPage();
$page->Page();
?>
<?php
class HTMLMail extends Base
{	var $headers = array();
	var $boundary= 'PHP-alt-';
	var $from = '';
	var $fromName = '';
	var $subject = 'from IIDR';
	
	function __construct()
	{	parent::__construct();
		//$this->from = SITE_EMAIL;
		$this->from = $this->GetParameter('emailfrom');
		$this->fromName = $this->GetParameter('shorttitle');
		$this->boundary .= md5(date('r'));
		$this->SetHeaders();
	} // end of fn __construct
	
	function SetFrom($from = '', $fromName = '')
	{	if ($from)
		{	$this->from = $from;
		}
		if ($fromName)
		{	$this->fromName = $fromName;
		}
		$this->SetHeaders();
	} // end of fn SetFrom
	
	function SetHeaders()
	{	$this->headers = array('From: "' . $this->fromName . '" <' . $this->from . '>', 'Reply-To: "' . $this->fromName . '" <' . $this->from . '>', 'X-Mailer: ' . phpversion(), 'MIME-Version: 1.0', 'Content-Type: multipart/alternative; boundary=' . $this->boundary);
	} // end of fn SetHeaders
	
	function SetHeadersPlain()
	{	$this->headers = array('From: "' . $this->fromName . '" <' . $this->from . '>', 'Reply-To: "' . $this->fromName . '" <' . $this->from . '>', 'X-Mailer: ' . phpversion(), 'MIME-Version: 1.0', 'Content-Type: text/plain');
	} // end of fn SetHeadersPlain
	
	function SetSubject($subject = "")
	{	$this->subject = $subject;
	} // end of fn SetSubject
	
	function Send($to = '', $htmlbody = '', $plainbody = '', $css = '')
	{	
		if (!$htmlbody)
		{	return $this->SendPlain($to, $plainbody);
		}
		
		ob_start();
		echo "--", $this->boundary, "\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n",$plainbody, "\n\n--", $this->boundary, "\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n<html>\n";
		if ($css)
		{	if (is_array($css))
			{	foreach ($css as $cssfile)
				{	$css_path = CITDOC_ROOT . CSS_ROOT . $cssfile;
					if (file_exists($css_path))
					{	if (!$headdone++)
						{	echo "<head>\n<style type=\"text/css\">\n";
						}
						include($css_path);
					}
				}
				if ($headdone)
				{	echo "\n</style>\n</head>\n";
				}
			} else
			{	$css_path = CITDOC_ROOT . CSS_ROOT . $css;
				if (file_exists($css_path))
				{	echo "<head>\n<style type=\"text/css\">\n";
					include_once($css_path);
					echo "\n</style>\n</head>\n";
				}
			}
		}
		echo $htmlbody, "</html>\r\n\r\n--{$this->boundary}--\r\n";
		$body = ob_get_clean();
		$headers = implode("\n", $this->headers);
		if ($this->FlagFileSet('logemails'))
		{	$this->LogMailBody($body, $to);
		}
	//	if (SITE_TEST)
	//	{	//echo $body;
	//	} else
	//	{	
			mail($to, $this->subject, $body, $headers);
	//	}
		return true;
	} // end of fn Send
	
	function SendPlain($to = '', $plainbody = '')
	{	
		$this->SetHeadersPlain();
		if ($this->FlagFileSet('logemails'))
		{	$this->LogMailBody($plainbody, $to);
		}
	//	if (SITE_TEST)
	//	{	//echo $body;
	//	} else
	//	{	
			mail($to, $this->subject, $plainbody, implode("\n", $this->headers));
	//	}
		return true;
	} // end of fn SendPlain
	
	function EMailsForArea($area = '')
	{	$emails = array();
		
		$sql = 'SELECT adminusers.* FROM emailsforarea, adminusers WHERE emailsforarea.userid=adminusers.auserid 
					AND areaname="' . $area . '"';
		if ($result = $this->db->Query($sql))
		{	while ($row = $this->db->FetchArray($result))
			{	if ($this->ValidEMail($row['email']))
				$emails[] = $row;
			}
		}
		return $emails;
		
	} // end of fn EMailsForArea

	function SendEMailForArea($area = '', $htmlbody = '', $plainbody = '', $css = ''){
		if ($addresses = $this->EMailsForArea($area)){	
			$tolist = array();
			foreach($addresses as $add){	
				$tolist[] = $add['email'];
			}
			
			$this->Send(implode(',', $tolist), $htmlbody, $plainbody, $css);
		}
	} // end of fn SendEMailForArea
	
	public function LogMailBody($body = '', $email = '')
	{	$namestr = $email . '_' . time();
		while (file_exists($filepath = CITDOC_ROOT . '/mail_log/' . $namestr . '.html'))
		{	$namestr = $email . '_' . time() . '_' . ++$i;
		}
		if ($file = fopen($filepath, 'w'))
		{	fputs($file, '<h2>' . $this->subject . ' [to ' . $email . ']</h2>' . "\n");
			fputs($file, $body);
			fputs($file, '<pre>');
			ob_start();
			print_r($this->headers);
			fputs($file, ob_get_clean());
			fputs($file, '</pre>');
			fclose($file);
		}
	} // end of fn LogMailBody
	
	function AddMailGreeting(&$html, &$plain, $name = "")
	{	$hi_text = "As-salamu 'alaikum ";
		$plain .= $hi_text . " " . $name . "\n\n";
		$html .= "<p class='mail-greeting'>" . $hi_text . " " . $name . "</p>\n";
	} // end of fn Greeting
	
	function AddMailFooter(&$html, &$plain)
	{	$footer_text = "Jazakumullahu Khairan\nWas-salamu 'alaikum wa rahmatullahi wa barakatuhu";
		$plain .= $footer_text;
		$html .= "<p class='mail-footer'>" . nl2br($footer_text) . "</p>\n";
	} // end of fn AddMailFooter
	
} // end of defn HTMLMail
?>
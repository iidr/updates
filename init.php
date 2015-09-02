<?php 
//error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '0');  
error_reporting(E_ALL ^(E_NOTICE|E_WARNING|E_STRICT));

require_once('config/config.php'); 
session_start();

// autoload classes
function __autoload($className) 
{	if(file_exists($file = CITDOC_ROOT . "/classes/" . strtolower($className) . '.class.php'))
	{	include_once($file);
	}
	elseif(file_exists($file = CITDOC_ROOT . "/classes/" . strtolower($className) . '.interface.php'))
	{	include_once($file);
	}
} // end of __autoload


//define('CONTACT_EMAIL', 'info@iidr.org'); No need now as it is getting this value from "Contact email address" field from backend
?>
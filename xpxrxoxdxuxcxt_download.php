<?php 
require_once('init.php');

class ProductDownloader extends Base
{	
	public function __construct()
	{	parent::__construct();
		$this->OutPutDownload();
	} // end of fn __construct
	
	function OutPutDownload(){	
		$download = new StoreProductDownload($_GET['id']);
		
		if($download->id && $download->FileExists() && ($filename = $download->FileLocation())){	
			if($download->CanDownload(new Student($_SESSION['stuserid']))){		
				
				list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));		
				
				$LoginSuccessful = true ;
				
				if($download->details['filepass']!='' && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
					$Username = strtolower($_SERVER['PHP_AUTH_USER']);
					$Password = $_SERVER['PHP_AUTH_PW'];
					
					if($Username!='iidr' && $Password != trim($download->details['filepass'])){
						$LoginSuccessful = false ;
					}
				}
					
				if (!$LoginSuccessful){
					header('WWW-Authenticate: Basic realm="Enter username = iidr. Password can be viewed from your account\'s Purchase History '.$Username.':'.$Password.':'.trim($download->details['filepass']).'"');
					header('HTTP/1.0 401 Unauthorized');
					print "Login Failed!\n";
				}else{
					if($fhandle = fopen($filename, 'r')){	
						//echo $download->valid_types[$download->details['filetype']]['Content-Type'];
						//exit;
						header('Pragma: ');
						header('Cache-Control: ');
						header('Content-Type: ' . $download->valid_types[$download->details['filetype']]['Content-Type']);
						header('Content-Disposition: attachment; filename="' . $download->DownloadName() . '"');
						header('Content-length: ' . filesize($filename));
						set_time_limit(0);
						while(!feof($fhandle) and (connection_status()==0)){	
							print(fread($fhandle, 1024*8));
							flush();
						}
						//fpassthru($fhandle);
						fclose($fhandle);
						exit;
					}else{
						echo '<h3>Error occured while download given file. Please contact admin for further assistance.</h3>';	
					}
				}
			}else{
				echo '<h3>you are not authorized to download given file. Please purchase its associated product first to download given file</h3>';	
			}
		}else{
			echo '<h3>file not found</h3>';
		}
	} // end of fn OutPutDownload
	
} // end of class ProductDownloader

$page = new ProductDownloader();
?>
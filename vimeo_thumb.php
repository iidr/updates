<?php
$url = 'http://vimeo.com/api/oembed.json?url=http%3A//vimeo.com/' . $_GET['vidid'];
if ($contents = file_get_contents($url)){
if (($array = json_decode(trim($contents))) && $array->thumbnail_url)
	{	if ($img_contents = file_get_contents(stripslashes($array->thumbnail_url)))
		{	header("Pragma: ");
			header("Cache-Control: ");
			header("Content-Type: image/jpeg");
			echo $img_contents;
			exit;
		}
		
	}
}
// fallback
require_once('init.php');
header('location: ' . SITE_URL . 'default_image.php?width=400&height=400');
exit;

?>

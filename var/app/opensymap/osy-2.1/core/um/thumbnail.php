<?
if (!empty($_REQUEST['f_name']) && !empty($_REQUEST['f_path']))
{
	chdir($_REQUEST['f_path']);
	$d_img = getimagesize($_REQUEST['f_name']);
	
	if ($d_img[0] <= 2800)
	{
		$fd = fopen ($_REQUEST['f_name'], "r");
		$image = fread ($fd, filesize($_REQUEST['f_name']));
		fclose ($fd);
	}
	 else
	{
		$image = (!empty($_REQUEST['f_name'])) ? @exif_thumbnail($_REQUEST['f_name'], $_REQUEST['width'], $_REQUEST['height'], $_REQUEST['type']) : false;
	
	}
}

if ($image!==false) 
{
   header('Content-type: ' .image_type_to_mime_type($type));
   echo $image;
   exit;
} 
 else 
{
   $debug = var_export($_REQUEST,true);
   // no thumbnail available, handle the error here
   header ("Content-type: image/png");
   $im = @imagecreate (400, 150) or die ("Cannot Initialize new GD image stream");
   $background_color = imagecolorallocate ($im, 255, 255, 255);
   $text_color = imagecolorallocate ($im, 0, 0, 0);
   imagestring ($im, 3, 12, 18,  "{$debug}", $text_color);
   imagepng ($im);
   imagedestroy ($im);
}
?> 

<?
class osy_otility 
{

public static function get_geo_coo($add)
    {
           //$add = urlencode($add);
           $geourl = "http://maps.googleapis.com/maps/api/geocode/xml?address={$add}&sensor=false&region=it";
            //mail('p.celeste@spinit.it','latlng',$geourl);
           
           // Create cUrl object to grab XML content using $geourl
           $c = curl_init();
           curl_setopt($c, CURLOPT_URL, utf8_encode($geourl));
           curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
           $xmlContent = trim(curl_exec($c));
           //$r = curl_getinfo($c);
           curl_close($c);
           // Create SimpleXML object from XML Content
           $xmlObject = simplexml_load_string($xmlContent);
           // Print out all of the XML Object
           if ($xmlObject->status)
		   {
               if ($xmlObject->status == 'OK')
			   {
                    return array((float)$xmlObject->result->geometry->location->lat,(float)$xmlObject->result->geometry->location->lng);
               }
           }
           return false;
    }

    public static function get_geo_dist($o,$d)
	{
           $o = implode(',',$o);
           $d = implode(',',$d);
           $geourl = "http://maps.googleapis.com/maps/api/distancematrix/xml?origins={$o}&destinations={$d}&sensor=false&region=it";
           
           // Create cUrl object to grab XML content using $geourl
           $c = curl_init();
           curl_setopt($c, CURLOPT_URL, utf8_encode($geourl));
           curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
           $xmlContent = trim(curl_exec($c));
           //$r = curl_getinfo($c);
           curl_close($c);
           // Create SimpleXML object from XML Content
           $xmlObject = simplexml_load_string($xmlContent);
           // Print out all of the XML Object
           if ($xmlObject->status){
               //mail('pietro.celeste@gmail.com','Distance',$geourl."\n".print_r($r,true));
               if ($xmlObject->status == 'OK'){
                    return (float)$xmlObject->row->element->distance->value;
               } /*else {
                   var_dump($xmlObject);
                 //  exit;
               }*/
           }
           return false;
    }
    
	public static function GetThumbnail($FilNam,$Dim)
    {
		$PathInfo = pathinfo($FilNam);
    	$ThuNam = str_replace(' ','_',"{$PathInfo['dirname']}/{$PathInfo['filename']}.thu{$Dim[0]}.{$PathInfo['extension']}");
		$ThuNam = str_replace('_(Custom)','',$ThuNam);
		if (!is_file($_SERVER['DOCUMENT_ROOT'].$ThuNam)){
			 //Effettuo il resize delle immagini al fine di creare le thumbneil
            $thumb = PhpThumbFactory::create($_SERVER['DOCUMENT_ROOT'].$FilNam);  
            $thumb->adaptiveResize($Dim[0], $Dim[1])->save($_SERVER['DOCUMENT_ROOT'].$ThuNam);
		}
		return $ThuNam;
	}
    
    public static function GetUniqueFileName($PathOnDisk)
    {
        if (empty($PathOnDisk)) return false;
        //Se il Path non eiste su disco lo restituisco.
        if (!file_exists($PathOnDisk)) return $PathOnDisk;
        $PathInfo = pathinfo($PathOnDisk);
        $i = 1;
        while (file_exists($PathOnDisk))
        {
              $PathOnDisk = $PathInfo['dirname'].'/'.$PathInfo['filename'].'_'.$i.'.'.$PathInfo['extension'];
              $i++;
        }
        return $PathOnDisk;
    }

}
?>

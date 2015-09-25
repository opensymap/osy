<?php
namespace Opensymap\Helper;

class GoogleService
{
    public static function geocodeAddress($add)
    {
        $add = urlencode($add);
        $geourl = "http://maps.googleapis.com/maps/api/geocode/xml?address={$add}&sensor=false&region=it";

        // Create cUrl object to grab XML content using $geourl
        $c = \curl_init();
        curl_setopt($c, CURLOPT_URL, utf8_encode($geourl));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        //curl_setopt($c, CURLOPT_CONNECTTIMEOUT ,2);
        //curl_setopt($c, CURLOPT_TIMEOUT, 5);
        $xmlContent = trim(curl_exec($c));
        //var_dump($xmlContent);
        //$r = curl_getinfo($c);
        curl_close($c);
        // Create SimpleXML object from XML Content
        $xmlObject = \simplexml_load_string($xmlContent);
        // Print out all of the XML Object
        if ($xmlObject->status) {
            if ($xmlObject->status == 'OK') {
                return array((float)$xmlObject->result->geometry->location->lat,
                             (float)$xmlObject->result->geometry->location->lng);
            }
        }
        return false;
    }
    
    public static function getDistance($o,$d)
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
}
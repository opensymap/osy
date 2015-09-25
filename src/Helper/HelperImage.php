<?php
namespace Opensymap\Helper;
//require_once(OSY_PATH_LIB_EXT.'/phpthu/ThumbLib.inc.php');

use Gregwar\Image\Image;

class HelperImage
{
    public $err = null;
    public $thm = null;

    public static function loadImage($file)
    {
        try {
             return Image::open($file);
        } catch (Exception $e) {
            self::$err = $e->getMessage();
            return false;
        }
    }

    public static function resize ($file,$w=640,$h=480) 
    {
        if (($th = self::loadImage($file)) !== false) {
             $th->resize($w, $h)->save($file);
        } else {
            env::resp('error','Errore : '.self::$err);
        }
    }
    
    public static function getThumbnail($fileName,$dimension)
    {
        $PathInfo = pathinfo($fileName);
        $thumbnailName = str_replace(' ','_',"{$PathInfo['dirname']}/{$PathInfo['filename']}.thu{$Dim[0]}.{$PathInfo['extension']}");
        $thumbnailName = str_replace('_(Custom)','',$thumbnailName);
        if (!is_file($_SERVER['DOCUMENT_ROOT'].$thumbnailName)) {
             //Effettuo il resize delle immagini al fine di creare le thumbneil
            //$thumb = PhpThumbFactory::create($_SERVER['DOCUMENT_ROOT'].$fileName);  
            //$thumb->adaptiveResize($Dim[0], $Dim[1])->save($_SERVER['DOCUMENT_ROOT'].$ThuNam);
            $thumb = Image::make($_SERVER['DOCUMENT_ROOT'].$fileName);
            $thumb->resize($dimension[0], $dimension[1]);
            $thumb->save($_SERVER['DOCUMENT_ROOT'].$thumbnailName);
        }
        return $thumbnailName;
    }
    
    public static function getUniqueFileName($PathOnDisk)
    {
        if (empty($PathOnDisk)) {
            return false;
        }
        //Se il Path non eiste su disco lo restituisco.
        if (!file_exists($PathOnDisk)) return $PathOnDisk;
        $PathInfo = pathinfo($PathOnDisk);
        $i = 1;
        while (file_exists($PathOnDisk)) {
              $PathOnDisk = $PathInfo['dirname'].'/'.$PathInfo['filename'].'_'.$i.'.'.$PathInfo['extension'];
              $i++;
        }
        return $PathOnDisk;
    }
}

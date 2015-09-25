<?
require_once(OSY_PATH_LIB_EXT.'/phpthu/ThumbLib.inc.php');

class ImageProcessor
{
    public $err = null;
    public $thm = null;

    public static function loadImage($file){
        try {
             return PhpThumbFactory::create($file);
        } catch (Exception $e) {
            self::$err = $e->getMessage();
            return false;
        }
    }

    public static function resize ($file,$w=640,$h=480){
        if (($th = self::load_image($file)) !== false){
             $th->adaptiveResize($w, $h);
             $th->save($file,'jpg');
        } else {
            env::resp('error','Errore : '.self::$err);
        }
    }
    
    public static function getThumbnail($FilNam,$Dim)
    {
        $PathInfo = pathinfo($FilNam);
        $ThuNam = str_replace(' ','_',"{$PathInfo['dirname']}/{$PathInfo['filename']}.thu{$Dim[0]}.{$PathInfo['extension']}");
        $ThuNam = str_replace('_(Custom)','',$ThuNam);
        if (!is_file($_SERVER['DOCUMENT_ROOT'].$ThuNam)) {
             //Effettuo il resize delle immagini al fine di creare le thumbneil
            $thumb = PhpThumbFactory::create($_SERVER['DOCUMENT_ROOT'].$FilNam);  
            $thumb->adaptiveResize($Dim[0], $Dim[1])->save($_SERVER['DOCUMENT_ROOT'].$ThuNam);
        }
        return $ThuNam;
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

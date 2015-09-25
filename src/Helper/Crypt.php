<?
namespace Opensymap\Helper;

class CryptHelper
{
    private $pri;
    private $pra;
    private $pub;

    function setPrivate($pri,$phrase)
    {
        $this->pri = $pri;
        $this->pra = $phrase;
    }
    function setPrivateFile($fname,$phrase)
    {
        $this->setPrivate(file_get_contents($fname,true),$phrase);
    }
    function setPublic($pub)
    {
        $this->pub = $pub;
    }
    function setPublicFile($fname)
    {
        $this->setPublic(file_get_contents($fname));
    }
    
    /**
     * Crittazione/Decrittazione simmetrica
     */
    function enc_sym($key,$dat) 
    {
        //return mcrypt_ecb(MCRYPT_DES, $key, $dat, MCRYPT_ENCRYPT);
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $dat, MCRYPT_MODE_CBC, $iv);
        return bin2hex($iv . $encrypted_string);
    }

    function dec_sym($key,$dat) {
        //return  mcrypt_ecb(MCRYPT_DES, $key, $dat, MCRYPT_DECRYPT);
        $iv=pack("H*" , substr($dat,0,16));
    //    $key=pack("H*" , $key);
        $x =pack("H*" , substr($dat,16)); 
        $decrypted_string = $res = mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $x , MCRYPT_MODE_CBC, $iv);
        return $decrypted_string;
    }

    function enc_pri($str)
    {
        $key = uniqid();
        $res = openssl_get_privatekey($this->pri,$this->pra);
        openssl_private_encrypt($key,$cry,$res);
        $ret = $this->enc_sym($key,$str);
        return base64_encode($cry).':'.base64_encode($ret);
    }
    function dec_pub($dat)
    {
        list($cry,$str) = array_map('base64_decode',explode(':',$dat));
        $res = openssl_get_publickey($this->pub);
        openssl_public_decrypt($cry,$key,$res);
        $ret = $this->dec_sym($key,$str);
        return trim($ret);
    }
}
header('Content-Type: text/plain');
$source = file_get_contents(__FILE__);


echo strlen($source)."\n";
echo "Source: $source\n";


$cry = new bcrypt();
$cry->setPrivateFile("pkey/pri_key",'aaaa');
$cry->setPublicFile("pkey/pub_key");


$str = $cry->enc_pri($source);
echo $str."\n";
echo $cry->dec_pub($str);

return;

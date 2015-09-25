<?php
namespace Opensymap\Utility;

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
//if (!isset($_SERVER['argc'])) {   exit;   }
include(__dir__.'/../lib/l.env.php');
include(__dir__.'/../lib/components/tag.php');
env::init(false);
env::$iid = "default";
$aid = 'spinit-srl://radarcrm/';
$sql = "";

class OpensymapDump
{
    private static $aid;
    private static $xml;
    private static $data;

    public static function init($aid)
    {
       self::$aid = $aid;
       env::$dba = env::dbcon_by_app($aid);
       self::$xml = tag::create('osy_dump');
       self::$xml->att('date',date('Y-m-d H:i:s'));
    }

    public static function database()
    {
        $tables = env::$dba->exec_query("SHOW TABLES",null,'NUM');

        $exp = self::$xml->add(tag::create('database'));
        foreach($tables as $k => $table)
        {
            $xtable = $exp->add(tag::create('table'))->att('name',$table[0]);
            $fields = env::$dba->exec_query("DESCRIBE  {$table[0]}",null,'ASSOC');
            foreach ($fields as $k => $field){
                $xfield =  $xtable->add(tag::create('field'));
                foreach($field as $j => $prop){
                     $xfield->att(strtolower($j),$prop);
                }
            }
        }
    }

    public static function loadData()
    {
        $properties = array();
        
        $db_prps = env::$dbo->exec_query("SELECT * FROM osy_obj_prp WHERE o_id LIKE ? ",array(self::$aid.'%'),'ASSOC');
        
        foreach($db_prps as $k => $prp)
        {
            $properties[$prp['o_id']][$prp['p_id']] = $prp['p_vl'];
        }
        $objs = env::$dbo->exec_query("SELECT * FROM osy_obj WHERE o_id = ? or o_own LIKE ?",array(self::$aid,self::$aid.'%'),'ASSOC');

        foreach($objs as $k => $obj)
        {
            if (array_key_exists($obj['o_id'],$properties)) {	
                $obj = array_merge($obj,$properties[$obj['o_id']]); 
            }
            self::$data[($obj['o_typ'] == 'app' ? 'root' : $obj['o_own'])][$obj['o_id']] = $obj;
        }
    }

    public static function objects($oid='root',$par=null)
    {
        if (!array_key_exists($oid,self::$data)) return;
        if (is_null($par)) $par = self::$xml;
        foreach(self::$data[$oid] as $k => $rec)
        {
            $obj = $par->add(tag::create($rec['o_typ']))->att('id',$rec['o_id']);
            foreach($rec as $pid => $pvl)
            {
                $pvl = (strpos($pvl,'&') === false && strpos($pvl,'<') === false && strpos($pvl,'>') === false) ? $pvl : '<![CDATA['.PHP_EOL.$pvl.PHP_EOL.']]>';
                $obj->add(tag::create('property'))
                    ->att('name',$pid)
                    ->add($pvl);
            }
            $sub = self::objects($rec['o_id'],$obj->add(tag::create('children')));
            //if (empty($sub)) $obj->add(tag::create('childs'))->add($sub);
        }
        return $par;
    }

    public static function get(){
        header('Content-Type: application/xml; charset=utf-8');
        return self::$xml;
    }
}


echo '<?xml version="1.0" encoding="UTF-8"?>';
OpensymapDump::init('spinit-srl://radarcrm/');
OpensymapDump::loadData();
OpensymapDump::objects();
OpensymapDump::database();
echo OpensymapDump::get();

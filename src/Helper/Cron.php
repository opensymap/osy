#!/usr/bin/php
<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
if (!isset($_SERVER['argc'])) {   exit;   }
include(__dir__.'/../lib/l.env.php');
include(__dir__.'/osy.data.align.php');
define("CRON_LIB_EXT",__dir__.'/../../../../../lib/');
env::init(false);
env::$iid = "default";

class osycron
{
    public static function exec_script($app,$cid,$cod)
    {
        env::$dba = env::dbcon_by_app($app);
        $msg = ($f = @create_function('',$cod)) ?  $f() : print_r(error_get_last(),true);
        //update last execution
        env::$dbo->exec_cmd("INSERT INTO osy_obj_prp (o_id,p_id,p_ord,p_vl)
                             VALUES
                             (?,'exec-last-date',10,NOW())
                             ON DUPLICATE KEY UPDATE p_vl = NOW();",array($cid));
       //update last message
       env::$dbo->exec_cmd("INSERT INTO osy_obj_prp (o_id,p_id,p_ord,p_vl)
                             VALUES
                             (?,'exec-last-message',10,?)
                             ON DUPLICATE KEY UPDATE p_vl = ?;",array($cid,$msg,$msg));
        return $msg;
    }
    
    public static function init()
    {
        $strSQL = "SELECT a.app_id,
                  a.str_cn,
                  a.crn_id,
                  a.cod
           FROM ( 
                    SELECT a.app_id,
                           a.crn_id,
                           a.str_cn,
                           a.cod,
                           tim_to_exe,
                           UNIX_TIMESTAMP(NOW()) AS _now,
                           UNIX_TIMESTAMP(a.exe_lst) AS lst,
                           CASE 
                                WHEN a.tim_to_exe > UNIX_TIMESTAMP(NOW()) THEN 0
                                WHEN a.tim_to_exe < UNIX_TIMESTAMP(a.exe_lst) THEN 0
                           ELSE 1
                           END AS exe
                       FROM (
                                SELECT distinct
                                       i.p_1    as str_cn,
                                       cr.o_own AS app_id,
                                       
                                       cr.o_id AS crn_id,
                                       cd.p_vl AS cod,
                                    UNIX_TIMESTAMP(STR_TO_DATE(CONCAT( IF(py.p_vl = '*',YEAR(NOW()),py.p_vl) , 
                                    '-',
                                    IF(pm.p_vl = '*',MONTH(NOW()),pm.p_vl) , 
                                    '-',
                                    IF(pd.p_vl = '*',DAY(NOW()),pd.p_vl) , 
                                    ' ',
                                    IF(ph.p_vl = '*',HOUR(NOW()),ph.p_vl),
                                    ':',
                                    IF(pmi.p_vl = '*',MINUTE(NOW()),pmi.p_vl),
                                    ':',
                                    IF(ps.p_vl = '*',MINUTE(NOW()),ps.p_vl)),'%Y-%m-%d %H:%i:%s'))  AS tim_to_exe,
                                    IFNULL(lex.p_vl,0) AS exe_lst
                                 FROM osy_obj cr
                                 INNER JOIN osy_obj_rel i ON (cr.o_own = i.o_2 AND i.r_typ = 'instance+application')
                                 INNER JOIN osy_obj_prp cd ON (cr.o_id = cd.o_id AND cd.p_id = 'code')
                                 INNER JOIN osy_obj_prp act ON (cr.o_id = act.o_id AND act.p_id = 'is-active')
                                 LEFT JOIN osy_obj_prp py ON (cr.o_id = py.o_id AND py.p_id = 'exec-year')
                                 LEFT JOIN osy_obj_prp pm ON (cr.o_id = pm.o_id AND pm.p_id = 'exec-month')
                                 LEFT JOIN osy_obj_prp pd ON (cr.o_id = pd.o_id AND pd.p_id = 'exec-day')
                                 LEFT JOIN osy_obj_prp ph ON (cr.o_id = ph.o_id AND ph.p_id = 'exec-hour')
                                 LEFT JOIN osy_obj_prp pmi ON (cr.o_id = pmi.o_id AND pmi.p_id = 'exec-minute')
                                 LEFT JOIN osy_obj_prp ps ON (cr.o_id = ps.o_id AND ps.p_id = 'exec-second')
                                 LEFT JOIN osy_obj_prp lex ON (cr.o_id = lex.o_id AND lex.p_id = 'exec-last-date')
                                 WHERE cr.o_typ = 'cron' AND act.p_vl = '1'
                        ) a
            ) a
            WHERE a.exe = 1";

        $res = env::$dbo->exec_query($strSQL);

        if (is_array($res))
        {
            foreach($res as $script)
            {
                self::exec_script($script['app_id'],$script['crn_id'],$script['cod']);
            }
        }
    }
}
switch ($_SERVER['argc']){
    case 3 :
             $aid = $argv[1];
             $cid = $argv[2];
             $code = env::$dbo->exec_unique("SELECT p_vl FROM osy_obj_prp WHERE o_id = ? AND p_id = 'code'",array($cid));
             if (!empty($code)){
                echo osycron::exec_script($aid,$cid,$code);
             } else {
                die("[error] - code is empty : cid = $cid & aid = $aid;");
             }
    default:
            osycron::init();
            break;
} 
?>

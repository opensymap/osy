<?
if (!isset($_SERVER['argc'])) {   exit;   }

include(__dir__.'/../lib/osy/l.env.php');
env::init(false);

class osycron
{
    public static function exec_script($app,$cid,$cod)
    {
        env::$DBApp = env::GetAppCn($app);
        $msg = ($f = @create_function('',$cod)) ?  $f() : print_r(error_get_last(),true);
        env::$DBOsy->execquery2("UPDATE osy_crn SET 
                                       exe_lst = NOW(),
                                       exe_msg = ?
                                WHERE app_id = ?
                                  AND crn_id = ?",array($msg,$app,$cid));
    }
    
    public static function init()
    {
        $strSQL = "SELECT a.app_id,
                  a.crn_id,
                  a.cod
           FROM ( 
                    SELECT a.app_id,
                           a.crn_id,
                           a.cod,
                           tim_to_exe,
                           UNIX_TIMESTAMP(NOW()) AS _now,
                           UNIX_TIMESTAMP(a.exe_lst) AS lst,
                           CASE 
                                WHEN a.tim_to_exe > UNIX_TIMESTAMP(NOW()) THEN 0
                                WHEN a.tim_to_exe < UNIX_TIMESTAMP(a.exe_lst) THEN 0
                           ELSE 1
                           END as exe
                       FROM (
                        		SELECT app_id,
                        			crn_id,
                        			cod,
                        			UNIX_TIMESTAMP(STR_TO_DATE(CONCAT( IF(exe_y = '*',YEAR(NOW()),exe_y) , 
                        			'-',
                        			IF(exe_m = '*',MONTH(NOW()),exe_m) , 
                        			'-',
                        			IF(exe_d = '*',DAY(NOW()),exe_d) , 
                        			' ',
                        			IF(exe_h = '*',HOUR(NOW()),exe_h),
                        			':',
                        			IF(exe_i = '*',MINUTE(NOW()),exe_i),
                        			':',
                        			IF(exe_s = '*',MINUTE(NOW()),exe_s)),'%Y-%m-%d %H:%i:%s'))  AS tim_to_exe,
                        			IFNULL(exe_lst,0) AS exe_lst
                        		 FROM osy_crn
                        ) a
            ) a
            WHERE a.exe = 1";

        $res = env::$DBOsy->GetAll($strSQL);

        if (is_array($res))
        {
            foreach($res as $script)
            {
                self::exec_script($script['app_id'],$script['crn_id'],$script['cod']);
            }
        }
    }
}

osycron::init();
?>

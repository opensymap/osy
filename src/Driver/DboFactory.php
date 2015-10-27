<?php
namespace Opensymap\Driver;

use Opensymap\Osy;

class DboFactory 
{
    public  static $dbo;
    private static $connectionPool;
    
    public static function init()
    {
        $dbOsyParameters = "sqlite:".OSY_PATH_VAR."/osy-setup.sqlite3";
        if (file_exists('../etc/config.ini')) {
            $dbOsyParameters = file_get_contents('../etc/config.ini');
        }
        self::$dbo = self::connection($dbOsyParameters);
        return self::$dbo;
    }
    
    /**
     * Exec a db connection via appid and return
     *
     * @param string $aid String contains application id
     *
     * @return object
     */
    public static function connectionViaAppId($instanceId, $applicationId)
    {
        $sql = "SELECT    r.p_1
                FROM      osy_obj_rel r 
                WHERE     r.o_1 = ?
                AND       r.o_2 = ?
                AND       r.r_typ = 'instance+application'";
        //var_dump(array('instance://'.$instanceId.'/', $applicationId));
        $cnString = self::$dbo->exec_unique($sql, array('instance://'.$instanceId.'/', $applicationId));
        return self::connection($cnString);
    }

    /**
     * Exec a db connection and return
     *
     * @param string $cnString String contains parameter
     *
     * @return object
     */
    public static function connection($parameters)
    {
        if (!empty(self::$connectionPool[$parameters])) {
            return self::$connectionPool[$parameters];
        }
        list($type, ) = explode(':', $parameters, 2);
 
        switch ($type) {
            case 'oracle':
                $cdb = new DboOci($parameters);
                break;
            default:
                $cdb = new Dbo($parameters);
                break;
        }
        //Exec connection
        $cdb->connect();
        return self::$connectionPool[$connectionString] = $cdb;
    }
}

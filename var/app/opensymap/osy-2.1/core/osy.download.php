<?php
/*
 +-----------------------------------------------------------------------+
 | osy/osy.download.php                                                  |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create a download page for blob field                               |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

*/
//die('ci sono');
header("Cache-Control: no-store, no-cache, must-revalidate");
require_once('../lib/l.chk.acc.php');

class download
{
    private static $__par = array();
    private static $__sql = '';
    private static $__dba = null;
    
    public static function init()
    {
        $strSQL = "SELECT ist.p_1  as APP_DB_CNF,
                          fpt.p_vl as TBL,
                          fpd.p_vl as FLD
                   FROM   ".OSY_FRM." frm
                   INNER JOIN ".OSY_FLD."     fld  ON (frm.o_id = fld.o_own)
                   INNER JOIN  ".OSY_FRM_PRP." fpt ON (frm.o_id = fpt.o_id)
                   INNER JOIN  ".OSY_FLD_PRP." fpd ON (fld.o_id = fpd.o_id)
                   INNER JOIN  ".OSY_IST_APP." ist ON (frm.o_own = ist.o_2)
                   WHERE  frm.o_id = ?
                     AND ist.o_1  = ?
                     AND ist.r_typ = 'instance+application'
                     AND fld.o_sty = 'field-blob'
                     AND fpt.p_id = 'db-table-linked'
                     AND fpd.p_id = 'db-field-connected'";
        self::$__par = env::$dbo->exec_unique($strSQL,array(env::$fid,'instance://'.env::$iid.'/'),'ASSOC');
        self::$__dba = env::dbcon_by_str(self::$__par['APP_DB_CNF']);
     }
     
     private static function build_where()
     {
        $sql = '';
        foreach($_REQUEST['pkey'] as $k => $v)
        {
             $sql .= (empty($strSQL) ? '' : ' AND ')."{$k} = '{$v}'";
        }
        return $sql;
     }
     
     public static function get_filename($mim)
     {
        $nam = null;
        $res = self::$__dba->exec_query("SHOW COLUMNS FROM ".self::$__par['TBL']." LIKE '".self::$__par['FLD']."_nam'");
        if (!empty($res[0]['Field']))
        {
            $nam = self::$__dba->exec_unique("SELECT ".self::$__par['FLD']."_nam FROM ".self::$__par['TBL']." WHERE ".(self::build_where()));
        }
        if (empty($nam))
        {
            $nam = uniqid();
        }
        return $nam;
     }
     
     public static function get_mimetype($buf)
     {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($buf);
     }
     
     public static function get_blob()
     {
        if (!is_array(self::$__par)) return;
        
        if (is_array($_REQUEST['pkey']))
        {
            $blb = self::$__dba->exec_unique("SELECT ".self::$__par['FLD']." FROM ".self::$__par['TBL']." WHERE ".(self::build_where()));
            $mim = self::get_mimetype($blb);
            $nam = self::get_filename($mim);
        }
         else 
        {
            $nam = 'error.txt';
            $blb = 'ERRORE NELLA GENERAZIONE DEL FILE';
        }
        header("Cache-Control: public");
        
        if ($_REQUEST['mod'] == 'online')
        {
           header("Content-type: {$mim}");
           header("Content-Disposition: inline; filename={$nam}");
        }
         else
        {
           header("Content-Description: File Transfer");
           header("Content-Disposition: attachment; filename= {$nam}");
           header("Content-Transfer-Encoding: binary");
        }

        echo $blb;
     }
}

download::init();
download::get_blob();
?>

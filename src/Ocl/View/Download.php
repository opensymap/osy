<?php
/*
 +-----------------------------------------------------------------------+
 | osy/odownload.php                                                     |
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
namespace Opensymap\Ocl\View;

use Opensymap\Driver\DboFactory;

header("Cache-Control: no-store, no-cache, must-revalidate");

class Download
{
    const OSY_FRM = 'osy_obj';
    const OSY_FLD = 'osy_obj';
    const OSY_FRM_PRP = 'osy_obj_prp';
    const OSY_FLD_PRP = 'osy_obj_prp';
    const OSY_IST_APP = 'osy_obj_rel';
    
    private static $param = array();
    private static $sql = '';
    private static $model = null;
    
    
    public static function init($model)
    {
        self::$model = $model;
        $strSQL = "SELECT ist.p_1  as APP_DB_CNF,
                          fpt.p_vl as TBL,
                          fpd.p_vl as FLD
                   FROM   ".self::OSY_FRM." frm
                   INNER JOIN ".self::OSY_FLD."     fld  ON (frm.o_id = fld.o_own)
                   INNER JOIN ".self::OSY_FRM_PRP." fpt ON (frm.o_id = fpt.o_id)
                   INNER JOIN ".self::OSY_FLD_PRP." fpd ON (fld.o_id = fpd.o_id)
                   INNER JOIN ".self::OSY_IST_APP." ist ON (frm.o_own = ist.o_2)
                   WHERE  frm.o_id = ?
                     AND ist.o_1  = ?
                     AND ist.r_typ = 'instance+application'
                     AND fld.o_sty = 'field-blob'
                     AND fpt.p_id = 'db-table-linked'
                     AND fpd.p_id = 'db-field-connected'";
        self::$param = self::$model->dbo->exec_unique(
            $strSQL,array(self::$model->request->get('input.osy.fid'),
            'instance://'.self::$model->request->get('instance.id').'/'),'ASSOC');
    }
     
    private static function buildWhere()
    {
        $sql = '';
        foreach ($_REQUEST['pkey'] as $k => $v) {
             $sql .= (empty($strSQL) ? '' : ' AND ')."{$k} = '{$v}'";
        }
        return $sql;
    }
     
    public static function getFileName($mim)
    {
        $nam = null;
        $res = self::$model->dba->exec_query(
            "SHOW COLUMNS FROM ".self::$param['TBL']." LIKE '".self::$param['FLD']."_nam'"
        );
        if (!empty($res[0]['Field'])) {
            $nam = self::$model->dba->exec_unique(
                "SELECT ".self::$param['FLD']."_nam FROM ".self::$param['TBL']." WHERE ".(self::buildWhere())
            );
        }
        if (empty($nam)) {
            $nam = uniqid();
        }
        return $nam;
    }
     
    public static function getMimeType($buf)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($buf);
    }
     
    public static function getBlob()
    {
        if (!is_array(self::$param)) {
            return;    
        } 

        if (is_array($_REQUEST['pkey'])) {
            $blb = self::$model->dba->exec_unique(
                "SELECT ".self::$param['FLD']." FROM ".self::$param['TBL']." WHERE ".(self::buildWhere())
            );
            
            $mim = self::getMimeType($blb);
            $nam = self::getFileName($mim);
        } else {
            $nam = 'error.txt';
            $blb = 'ERRORE NELLA GENERAZIONE DEL FILE';
        }
        header("Cache-Control: public");

        if ($_REQUEST['mod'] == 'online') {
            header("Content-type: {$mim}");
            header("Content-Disposition: inline; filename={$nam}");
        } else {
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename= {$nam}");
            header("Content-Transfer-Encoding: binary");
        }
        return $blb;
    }
}

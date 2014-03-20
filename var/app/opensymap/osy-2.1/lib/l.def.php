<?php
date_default_timezone_set('Europe/Rome');
define('OSY_VER','2.1');
define('OSY_ROOT',realpath(__DIR__.'/../')); //$_SERVER['DOCUMENT_ROOT'].'/osy/var/app/opensymap/osy'.OSY_VER.'/');
define('OSY_CORE',OSY_ROOT.'/core/');
define('OSY_WEB_ROOT',str_replace($_SERVER['DOCUMENT_ROOT'],'',OSY_ROOT));
define('OSY_PATH_LIB',__DIR__.'/');
define('OSY_PATH_DRV',OSY_PATH_LIB.'/drv/');
define('OSY_PATH_VAR',realpath(OSY_ROOT.'/../../../'));
define('OSY_PATH_CNF',OSY_PATH_VAR.'/.osy.ini');
define('MYSQLI_LIB',OSY_PATH_DRV.'d.mysqli.php');
define('ORACLE_LIB',OSY_PATH_DRV.'d.ora.php');
define('SQLITE_LIB',OSY_PATH_DRV.'d.sqlite.php');
//OSY DB TABLE MAPPING
define('OSY_APP','osy_obj');
define('OSY_FRM','osy_obj');
define('OSY_FRM_PRP','osy_obj_prp');
define('OSY_FRM_TRG','osy_app_frm_trg_opr_mom_rel');
define('OSY_FLD','osy_obj');
define('OSY_FLD_PRP','osy_obj_prp');
define('OSY_FLD_TYP','osy_res');
define('OSY_FLD_CHK','osy_res');
define('OSY_PRP_FLD','osy_obj_prp');
define('OSY_RES','osy_res');
define('OSY_IST_APP','osy_obj_rel');
define('OSY_TRG','osy_app_frm_trg');
define('OSY_TRG_EVT','osy_res');
if (!defined('CONTENT_TYPE')) { define('CONTENT_TYPE','text/html; charset=utf-8'); }
//Constant 
define('IS_POST',($_SERVER['REQUEST_METHOD'] == 'POST'));
define('IS_GET',($_SERVER['REQUEST_METHOD'] == 'GET'));
define('ROOT',str_repeat('../',count(explode('/',$_SERVER['REQUEST_URI']))-2)); 
define('USR_LNG','IT');

$err_msg[10]['IT'] = 'Condizione mancante';
$err_msg[10]['EN'] = 'Missing condition';

$err_msg[100]['IT'] = 'Il campo [label] &egrave; vuoto';
$err_msg[100]['EN'] = 'Field [label] is empty';

$err_msg[110]['IT'] = 'Il campo [label] non &egrave; numerico.';
$err_msg[110]['EN'] = 'Field [label] isn\'t numeric';
?>

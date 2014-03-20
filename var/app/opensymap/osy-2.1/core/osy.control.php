<?php
/*
 +-----------------------------------------------------------------------+
 | osy/osy.form.dm.xt.php                                                |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Generate sql data manipulation istruction from form data page       |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 | Date  : 2008-11-12                                                    |
 +-----------------------------------------------------------------------+

 $Id:  $

*/

/*
 * This function intercept fatal error which block the page
 * print error message and close html tag.
 */
ob_start();
define('CONTENT_TYPE',"application/json; charset=utf-8");
require_once('../lib/l.chk.acc.php');
require_once(OSY_PATH_LIB.'c.control.lib.php');
define('UPLOAD_PATH','../upl/');
define('@CURRENT_DATE',date('Y-m-d'));
define('@CURRENT_TIME', date('H:i:s'));
define('@CURRENT_DATETIME', date('Y-m-d H:i:s'));
define('@UID', $_REQUEST['_uid']);
include(OSY_PATH_LIB.'c.model.sql.php');

if (empty(env::$fid)) die('Il campo FrmID Non è stato valorizzato.');

class osy_control
{
    public  static $Debug;
    private static $__par = array();
    public static $__resp = array();
    private static $Sql = false;
    
    public static function init()
    {
        $result = env::$dbo->exec_unique("SELECT t.p_vl as tbl, 
                                                 i.p_1  as par_cn,
                                                 f.o_own as aid
                                          FROM       osy_obj f 
                                          INNER JOIN osy_obj_prp t ON (f.o_id = t.o_id AND t.p_id = 'db-table-linked')
                                          INNER JOIN osy_obj     a ON (f.o_own = a.o_id)
                                          INNER JOIN osy_obj_rel i ON (a.o_id = i.o_2 AND i.o_1 = ? AND i.r_typ = 'instance+application')
                                          WHERE f.o_id = ?",array('instance://'.env::$iid.'/',env::$fid),'ASSOC');
        env::$aid = $result['aid'];
        env::$dba = env::dbcon_by_str($result['par_cn']);
        self::$__par['field-list'] = array();
        if (!empty($result['tbl']))
        {
            self::$Sql = new Sql(env::$dba,$result['tbl']);
            self::__load_trigger__();
            if (self::load_primary_key()) self::__load_field__();
        }
         else
        {
            self::$__par['trigger'] = array();
            self::__load_field__();
            self::__load_trigger__();
        }
    }

    private static function __check_fields__()
    {
        $is_ok = true;
        foreach(self::$__par['field-list'] as $k => $field)
        {
            if (empty($field['component-label'])){ $field['component-label'] = $field['component-name'];}
            if ($field['value'] !== '0' && empty($field['value']))
            {
                    if (!empty($field['field-required'])) 
                    {
                        //$this->set_error_by_number(100,array($field['component-label'],$field['component-name'],$field['value'])); 
                        env::resp('error','Il campo '.env::nvl($field['component-label'],$field['component-name']).' &egrave; vuoto');
                        $is_ok = false;
                    }
                    continue;
            }
            //Se al campo è associato un controllo lo eseguo e rilevo l'eventuale errore
            if ($function = $field['field-control'])
            {
                if ($error = $function($field,$field))
                {
                    env::resp('error',$error);
                    $is_ok = false;
                }
            }
        }
        return $is_ok;
    }
    
    private static function __load_field__()
    {
        $query = "SELECT f.o_nam as cmp,
                          f.o_lbl as lbl,
                          f.o_sty as typ,
                          p.p_id  as pid,
                          p.p_vl  as pvl
                   FROM osy_obj f
                   INNER JOIN osy_obj_prp p ON (f.o_id = p.o_id)
                   WHERE f.o_typ = 'field' 
                     AND f.o_sty not in ('field-datagrid','field-tab') 
                     AND f.o_own = ?";
        //die($strSQL.env::$fid);
        $result = env::$dbo->exec_query($query,array(env::$fid),'ASSOC');
        $field_list = array();
        foreach ($result as $rec)
        {
            if (!key_exists($rec['cmp'],$field_list))
            {
                $field_list[$rec['cmp']]['component-name'] = $rec['cmp'];
                $field_list[$rec['cmp']]['component-label'] = $rec['lbl'];
                $field_list[$rec['cmp']]['component-type'] = $rec['typ'];
            }
            $field_list[$rec['cmp']][$rec['pid']] = $rec['pvl'];
        }
        //var_dump($field_list);
        foreach ($field_list as $field_name => $field)
        {
            if (empty($field['db-field-connected'])) { continue; }
            $add = true;
            switch($field['component-type'])
            {
                    case 'field-checkbox': 
                                if (empty($_REQUEST[$field['component-name']]))
                                {
                                    $_REQUEST[$field['component-name']] = '00';
                                }
                                break;
                    case 'field-date':
                                if (!empty($_REQUEST[$field['component-name']]))
                                {
                                    $a = explode('/',$_REQUEST[$field['component-name']]);
                                    if (count($a) == 3) $_REQUEST[$field['component-name']] = $a[2].'-'.$a[1].'-'.$a[0];
                                }
                                break;
                    case 'field-blob': 
                                if (!empty($_FILES[$field['component-name']]['name']))
                                {
									$_REQUEST[$field['component-name']] = file_get_contents($_FILES[$field['component-name']]['tmp_name']);
                                    $_REQUEST[$field['component-name'].'_nam'] = $_FILES[$field['component-name']]['name'];
                                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                                    $_REQUEST[$field['component-name'].'_typ'] = $finfo->buffer($_REQUEST[$field['component-name']]);
                                    $_REQUEST[$field['component-name'].'_dim'] = strlen($_REQUEST[$field['component-name']]);
                                } 
                                 elseif (empty($field['required']))
                                {
                                    unset($_REQUEST[$field['component-name']]);
                                    $add=false;
                                }
                                  else //Settaggio necessario nel caso il campo sia not null
                                {
                                    $_REQUEST[$field['component-name']] = null;
                                }
                                break;
                    case 'field-constant':
                                $add = true;
                                $field['required'] = 0;
                                if (!empty($field['visibility-condition']))
                                {
                                   eval('$add = '.str_replace('TEST','',$val).';');
                                }
                                if ($add)
                                {
                                    $_REQUEST[$field['component-name']] = constant('@'.$field['constant']);
                                }
                                break;
                    case 'field-file':
                                if (!empty($_FILES[$field['component-name']]['name']))
                                {
                                    $_REQUEST[$field['component-name']] = Env::GetUniqueFileName(UPLOAD_PATH.$_FILES[$field['component-name']]['name']);
									move_uploaded_file($_FILES[$field['component-name']]['tmp_name'],$_REQUEST[$field['component-name']]);
                                } 
                                 else 
                                {
                                    unset($_REQUEST[$field['component-name']]);
                                }
                                break;
            }
            
            if (key_exists($field['component-name'],$_REQUEST) && $add==true)
            {
                //Serve per poter inserire il valore null sul db (se passo null pdo inserisce null)
                if ($_REQUEST[$field['component-name']]==''){ $_REQUEST[$field['component-name']] = null; }
                $field['value'] =& $_REQUEST[$field['component-name']];
                if (self::$Sql) self::$Sql->add_field($field);
                self::$__par['field-list'][$field['component-name']] = $field;
            }
        }
    }

    private function load_primary_key()
    {
       // necessario il set_cmd per eseguire il trigger BEFORE
       if (!empty($_POST['pkey']))
       {
            foreach($_POST['pkey'] as $k => $v)
            {
                self::$Sql->add_key($k,$v);
            }
            if ($_POST['CMD'] == 'DELETE')
            {
                self::$Sql->set_command('delete'); 
                return false;
            }
       }
       return true;
    }

    private function __load_trigger__()
    {
        $sql = "SELECT trg.o_nam AS trg,
                       cod.p_vl  AS cod,
                       mom.p_id  AS mom,
                       CASE
                              WHEN mom.p_id = 'library' THEN 0
                              ELSE 1
                       END ORD
                FROM       osy_obj trg 
                INNER JOIN osy_obj_prp ctx ON (trg.o_id = ctx.o_id)
                INNER JOIN osy_obj_prp cod ON (trg.o_id = cod.o_id)
                INNER JOIN osy_obj_prp mom ON (trg.o_id = mom.o_id)
                WHERE trg.o_typ = 'trigger'
                AND   cod.p_id  = 'code'
                AND   mom.p_vl  = 'yes'
                AND   ctx.p_vl  = 'exec'
                AND   trg.o_own = ?
                ORDER BY 4,1";
        $rs = env::$dbo->exec_query($sql,array(env::$fid));
        foreach ($rs as $rec)
        {
            if (self::$Sql)
            {
                self::$Sql->add_trigger($rec['mom'],$rec['trg'],$rec['cod']);
            }
             else
            {
                self::$__par['trigger'][$rec['trg']] = $rec['cod'];
            }
        }
    }

    public function execute()
    {
        if (self::__check_fields__())
        {
           if (self::$Sql)
           {
              if (self::$Sql->execute())
              {
                 $lkey = self::$Sql->get_primary_key();
                 foreach($lkey as $k => $v){ env::resp('setpkey',$k,$v); }
              }
           }
            else
           {
                self::__exec_trigger__();
           }
        }
        return  !empty(self::$__resp['error']) ? false : true;
    }
    
    private function __exec_trigger__()
    {
        $noerror = true;
        foreach(self::$__par['trigger'] as $k => $code)
        {    
          $fnc = create_function('$Db,$Self',$code);
          $err = $fnc(env::$dba,self);
          if(!empty($err))
          { 
            $noerror = false;
            env::resp('error',$err);
          }
        }
        return $noerror;
    }
    
    public function par($key,$val=null)
    {
        if (is_null($val) && key_exists($key,self::$__par))
        {
            return self::$__par[$key];
        }
        if (key_exists($key,self::$__par) && is_array(self::$__par[$key]))
        {
            self::$__par[$key][] = $val;
        }
         else
        {
            self::$__par[$key] = $val;
        }
    }
    
    
}

osy_control::init();
osy_control::execute();
echo env::reply();
ob_end_flush();?>
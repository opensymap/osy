<?php
namespace Opensymap\User;

class UserFactory
{
   private $dbo;
   private $dba;
   private $request;
   private $instance;
   public  $tempUser = false;
   
   public function __construct($dbo, $dba, $request)
   {
        $this->dbo = $dbo;
        $this->dba = $dba;
        $this->request = $request;
        $this->instance = $this->request->get('instance.id');
   }

   public function storageBuild($app, $type = 'role')
   {
        switch($type){
            case 'role':
                $db->exec_cmd("create table osy_role (
                                id varchar(100) not null,
                                nam varchar (100),
                                lbl varchar(100),
                                primary key (id))");
                break;
           case 'user':
                $db->exec_cmd("create table osy_user (
                                id varchar(100) not null,
                                nam varchar(100) not null, 
                                add_add varchar(100),
                                add_cit varchar(100),
                                add_zip varchar(100),
                                add_prv varchar(100),
                                mob_1 varchar(20),
                                tel_1 varchar(20),
                                tel_2 varchar(20),
                                eml varchar(100),
                                lgn varchar (100),
                                rol varchar(100),
                                is_act integer,
                                par_1 varchar(20),
                                par_2 varchar(20),
                                par_3 varchar(20),
                                primary key (id))");  
                break;
       }
       $this->storageInit($app, $type);
   }
   
   public  function storageInit($app, $type='role')
   {
        switch ($type){
            case 'role':
                $role = $this->dbo->exec_query("SELECT o_id,o_nam,o_lbl
                                               FROM osy_obj
                                               WHERE o_typ = 'role' 
                                                 AND o_own = ? ", array($app), 'NUM');
                if (!empty($role)) {
                    $this->dba->exec_multi("INSERT INTO osy_role (id,nam,lbl) VALUES (?,?,?)", $role);
                }
                break;
            case 'user':
                $user = $this->dbo->exec_query("SELECT u.o_id,u.o_lbl,u.o_nam,r.o_4,e.p_vl AS email,i.p_vl AS is_active
                                               FROM osy_obj u
                                               INNER JOIN osy_obj_rel r ON (r.o_1 = u.o_own AND u.o_id = r.o_3)
                                               LEFT JOIN  osy_obj_prp e ON (u.o_id = e.o_id AND e.p_ord = 10 AND e.p_id = 'email')
                                               LEFT JOIN  osy_obj_prp i ON (u.o_id = i.o_id AND i.p_ord = 10 AND i.p_id = 'is-active')
                                               WHERE u.o_typ = 'user' 
                                                 AND u.o_own = ?
                                                 AND r.o_2 = ? ", array($ist,$app), 'NUM');
                if (!empty($user)) {
                     $this->dba->exec_multi("INSERT INTO osy_user (id,nam,lgn,rol,eml,is_act) VALUES (?,?,?,?,?,?)",$user);
                }
                break;
       }
   }
   
   public  function save_parameter()
   {
   }
   
   public  function password($uid,$pwd)
   {
   }
   
   public  function update($uid,$origin='app')
   {
       switch($origin){
            case 'osy':
                $user  = $this->dba->exec_unique("SELECT o_lbl AS nam,e.p_vl AS eml,o_nam AS lgn,r.o_4 as rul,a.p_vl AS is_act,u.o_id
                                                 FROM osy_obj u
                                                 INNER JOIN osy_obj_rel r ON (u.o_id = r.o_3)
                                                 LEFT JOIN osy_obj_prp e ON (u.o_id = e.o_id AND e.p_id = 'email')
                                                 LEFT JOIN osy_obj_prp a ON (u.o_id = a.o_id AND a.p_id = 'is-active')
                                                 WHERE u.o_id = ?",array($uid),'NUM');
                 break;
            default:
                $user  = $this->dba->exec_unique("SELECT nam,eml,lgn,rul,is_act,id
                                                 FROM osy_user
                                                 WHERE id = ?",array($uid),'NUM');
                break;
       }
       $stringConnection = $this->dbo->exec_query("SELECT a.p_1 as cn
                                                  FROM osy_obj_rel r
                                                  INNER JOIN osy_obj_rel a ON (r.o_1 = a.o_1 AND r.o_2 = a.o_2 AND a.r_typ = 'instance+application')
                                                  WHERE r.r_typ = 'user+role'
                                                    AND r.o_3 = ?",array($user[5]));
        foreach ($stringConnection as $rec) {
            $db = DbFactory::dbConnectionByString($rec['cn']);          
            if ($db && $db->get_type() != 'oracle') {
                try {
                    $n = $db->exec_unique("SELECT count(*) FROM osy_user WHERE id = ?",array($uid));
                    if ($n>0){
                        $db->exec_cmd("UPDATE osy_user SET
                                        nam = ?,
                                        eml = ?,
                                        lgn = ?,
                                        rol = ?,
                                        is_act = ?
                                       WHERE id = ?",$user);
                    } else {
                        $db->exec_cmd("INSERT INTO osy_user 
                                        (nam,eml,lgn,rol,is_act,id) 
                                        VALUES 
                                      (?,?,?,?,?,?)",$user);
                    }
                } catch(Exception $e) {               
                    switch ($e->getCode()) {
                        case '42S02':
                            //No break
                        case '42P01':
                            echo $e->getMessage();
                        break;
                    }
                    echo $e->getMessage();
                }
            }
        }
   }
   
   public  function add($uid, $nam, $aid, $rid, $pwd, $is_act='0')
   {
        $ist_raw = 'instance://'.$this->instance.'/';
        $uid_raw = $ist_raw.uniqid('user:',true).'/';
        if ($this->existUsername($uid_raw,$uid)) {
            return false;
        }
        $this->dbo->exec_cmd(
            "insert into osy_obj (o_own,o_id,o_nam,o_lbl,o_typ) value (?,?,?,?,'user')",
            array($ist_raw, $uid_raw, $uid, $nam)
        );
        $this->dbo->exec_cmd(
            "insert into osy_obj_rel(o_1,o_2,o_3,o_4,r_typ) value (?,?,?,?,'user+role')",
            array($ist_raw, $aid, $uid_raw, $rid)
        );
        $par = array(
            'password'  => sha1($pwd), 
            'is-active' => $is_act
        );
        foreach ($par as $k => $v) {
            $this->dbo->exec_cmd(
                "insert into osy_obj_prp (o_id,p_id,p_vl,p_ord) value (?,?,?,10)",
                array($uid_raw,$k,$v)
            );
        }
        return $uid_raw;
   }
   
   public function delete($uid)
   {
        $this->dbo->exec_cmd("DELETE FROM osy_obj WHERE o_id = ?", array($uid));
        $this->dbo->exec_cmd("DELETE FROM osy_obj_prp WHERE o_id = ?", array($uid));
        $this->dbo->exec_cmd("DELETE FROM osy_obj_rel WHERE o_3 = ?", array($uid));
   }
   
    public function exists($uid, $ist, $raw=true)
    {
        $fld = $raw ? 'o_id' : 'o_nam';
        return $this->dbo->exec_unique(
            'select count(*) from osy_obj where '.$fld.' = ? AND o_own = ?', 
            array($uid,$ist)
        );
    }
   
    public  function existUsername($uid, $usr)
    {
        if (empty($uid)) {
            $uid = 'default';
        }
        return $this->dbo->exec_unique(
            'select count(*) from osy_obj where o_id not in  (?) AND o_nam = ?',
            array($uid, $usr)
        );
    }
   
    public  function updateUsername($uid, $usr)
    {
        if (!$this->existUsername($uid, $usr)) {
            $this->dbo->exec_cmd('update osy_obj set o_nam = ? where o_id = ?', array($usr,$uid));
        }
    }
   
    public  function updateRole($uid, $rid, $aid)
    {
        $this->dbo->exec_cmd(
            "update osy_obj_rel set 
                o_4 = ? 
            where o_3 = ?
            and   o_2 = ?
            and   r_typ = 'user+role'",
            array($rid,$uid,$aid)
        );
    }
   
    public  function osyTmpUser()
    {
        if ($this->$temp_user) {
            return;
        }
        $this->dba->exec_cmd("CREATE TEMPORARY TABLE osy_user (id varchar(255),
                                                            nam varchar(255),
                                                            lbl varchar(255),
                                                            eml varchar(255))");
        $rs = $this->dbo->exec_query(
            "SELECT o_id,o_nam,o_lbl
             FROM osy_obj
             WHERE o_own = ? and o_typ ='user'",
            array('instance://'.Osy::$iid.'/'),
            'NUM'
        );
        $this->dba->exec_multi('INSERT INTO osy_user (id,nam,lbl) VALUES (?,?,?)', $rs);
        $this->$temp_user = true;
    }
   
    public  function login($username, $cleanPassword)
    {
        if (empty($username)) {
            return array('Il campo Username &egrave; vuoto',null);
        }
        if (empty($username)) {
            return array('Il campo Password &egrave; vuoto',null);
        }
        try {
            list($userId,$cryptPassword) = $this->dbo->exec_unique(
                "SELECT u.o_id,p.p_vl
                 FROM   osy_obj u
                 INNER JOIN osy_obj_prp p ON (u.o_id = p.o_id)
                 INNER JOIN osy_obj_prp a ON (u.o_id = a.o_id)
                 WHERE  a.p_id = 'is-active'
                   AND  a.p_vl = '1'
                   AND  p.p_id = 'password'
                   AND  u.o_own in (?,'instance://global/') 
                   AND  u.o_nam = ?",
                array('instance://'.$this->instance.'/', $username)
            );
            //var_dump(array('instance://'.$_REQUEST['iid'].'/', $username));
        } catch (Exception $e) {
            return array($e->getMessage(), null);
        }
        if (empty($userId)) {
            return array('Account inesistente',null);
        }
        //Controllo che lo sha1 della password inviata sia uguale allo sha1 memorizzato sul db
        if (sha1($cleanPassword) != $cryptPassword) {
            return array('Password errata ',null);
        }
        $sessionKey = md5('['.$userId.']['.$this->instance.']['.$this->request->get('server.REMOTE_ADDR').']['.time().']');
        $parameters = array(
            $sessionKey,
            $userId,
            $this->request->get('server.SERVER_NAME'),
            $this->request->get('instance.id'),
            $this->request->get('server.REMOTE_ADDR'),
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );
        $this->dbo->exec_cmd("INSERT INTO osy_log 
                                   (ses_id,usr_id,hst_id,ses_ist,ses_ip,ses_dat,lst_op_dat)
                              VALUES
                                   (?,?,?,?,?,?,?)", $parameters);
        return array('ok',$sessionKey);
    }
}

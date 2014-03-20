<?
class osy_user
{
   public static $temp_user = false;
   
   public static function storage_build($db,$ist,$app,$type='role')
   {
        switch($type)
        {
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
       self::storage_init($db,$ist,$app,$type);
   }
   
   public static function storage_init($db,$ist,$app,$type='role')
   {
        switch($type)
        {
            case 'role':
                        $role = env::$dbo->exec_query("SELECT o_id,o_nam,o_lbl
                                                       FROM osy_obj
                                                       WHERE o_typ = 'role' 
                                                         AND o_own = ? ",array($app),'NUM');
                        if (!empty($role))
                        {
                   		    $db->exec_multi("INSERT INTO osy_role (id,nam,lbl) VALUES (?,?,?)",$role);
                        }
                        break;
            case 'user':
                        $user = env::$dbo->exec_query("SELECT u.o_id,u.o_lbl,u.o_nam,r.o_4,e.p_vl AS email,i.p_vl AS is_active
                                                       FROM osy_obj u
                                                       INNER JOIN osy_obj_rel r ON (r.o_1 = u.o_own AND u.o_id = r.o_3)
                                                       LEFT JOIN  osy_obj_prp e ON (u.o_id = e.o_id AND e.p_ord = 10 AND e.p_id = 'email')
                                                       LEFT JOIN  osy_obj_prp i ON (u.o_id = i.o_id AND i.p_ord = 10 AND i.p_id = 'is-active')
					                    			   WHERE u.o_typ = 'user' 
        							                     AND u.o_own = ?
                      								     AND r.o_2 = ? ",array($ist,$app),'NUM');
                        if (!empty($user))
                        {
                       	     $db->exec_multi("INSERT INTO osy_user (id,nam,lgn,rol,eml,is_act) VALUES (?,?,?,?,?,?)",$user);
                        }
                        break;
       }
   }
   
   public static function save_parameter()
   {
   
   }
   
   public static function password($uid,$pwd)
   {
   
   }
   
   public static function update($uid,$origin='app')
   {
       switch($origin)
       {
            case 'osy':
                        $user  = env::$dba->exec_unique("SELECT o_lbl AS nam,e.p_vl AS eml,o_nam AS lgn,a.p_vl AS is_act,u.o_id
                                                         FROM osy_obj u
                                                         LEFT JOIN osy_obj_prp e ON (u.o_id = e.o_id AND e.p_id = 'email')
                                                         LEFT JOIN osy_obj_prp a ON (u.o_id = a.o_id AND a.p_id = 'is-active')
                                                         WHERE u.o_id = ?",array($uid),'NUM');
                         break;
            default:
                        $user  = env::$dba->exec_unique("SELECT nam,eml,lgn,is_act,id
                                                         FROM osy_user
                                                         WHERE id = ?",array($uid),'NUM');
                        break;
       }
       $strcn = env::$dbo->exec_query("SELECT a.p_1 as cn
                                       FROM osy_obj_rel r
                                       INNER JOIN osy_obj_rel a ON (r.o_1 = a.o_1 AND r.o_2 = a.o_2 AND a.r_typ = 'instance+application')
                                       WHERE r.r_typ = 'user+role'
                                         AND r.o_3 = ?",array($user[4]));
       foreach($strcn as $rec)
       {
          $db = env::dbcon_by_str($rec['cn']);
          if ($db)
          {
            try
            {
                $db->exec_query("SELECT count(*) FROM osy_user");
                $db->exec_cmd("UPDATE osy_user SET
                                  nam = ?,
                                  eml = ?,
                                  lgn = ?,
                                  is_act = ?
                                 WHERE id = ?",$user);
            }
             catch(Exception $e)
            {
                if (!$e->getCode() != '42S02')
                {
                    echo $e->getMessage();
                }
            }
          }
       }
   }
   
   public static function add($uid,$nam)
   {
        $ist_raw = 'instance://'.env::$iid.'/';
        $uid_raw = $ist_raw.'user:'.$uid.'/';
        env::$dbo->exec_cmd('insert into osy_obj 
                               (o_own,o_id,o_nam,o_lbl,o_typ) 
                             value (?,?,?,?)',
                             array($ist_raw,$uid_raw,$uid,$nam,'user'));
        return $uid_raw;
   }
   
   public static function exists($uid,$ist,$raw=true)
   {
       $fld = $raw ? 'o_id' : 'o_nam';
       return env::$dbo->exec_unique('select count(*) from osy_obj where '.$fld.' = ? AND o_own = ?',array($uid,$ist));
   }
   
   public static function osy_tmp_user()
   {
          if (self::$temp_user) return;
          env::$dba->exec_cmd("CREATE TEMPORARY TABLE osy_user (id varchar(255),
                                                                nam varchar(255),
                                                                lbl varchar(255),
                                                                eml varchar(255))");
          $rs = env::$dbo->exec_query("SELECT o_id,o_nam,o_lbl
          FROM osy_obj
          WHERE o_own = ? and o_typ ='user'",array('instance://'.env::$iid.'/'),'NUM');
          env::$dba->exec_multi('INSERT INTO osy_user (id,nam,lbl) VALUES (?,?,?)',$rs);
          self::$temp_user = true;
   }
   
   public static function login($usr,$pwd)
   {
        if (empty($usr)) {  return array('Il campo Username &egrave; vuoto',null);  }
        if (empty($pwd)) {  return array('Il campo Password &egrave; vuoto',null);  }
        try 
        {
            list($uid,$dpwd) = env::$dbo->exec_unique("SELECT u.o_id,p.p_vl
                                                       FROM   osy_obj u
                                                       INNER JOIN osy_obj_prp p ON (u.o_id = p.o_id)
                                                       INNER JOIN osy_obj_prp a ON (u.o_id = a.o_id)
                                                       WHERE  a.p_id = 'is-active'
                	                                   AND    a.p_vl = '1'
                                                       AND    p.p_id = 'password'
                                                       AND    u.o_own = ? 
                                                       AND    u.o_nam = ?",array('instance://'.$_REQUEST['iid'].'/', $usr));
        } 
         catch (Exception $e)
        {
           return array($e->getMessage(),null);
        }
        if (empty($uid)) 
        { 
            return array('Account inesistente',null);  
        }
        //Controllo che lo sha1 della password inviata sia uguale allo sha1 memorizzato sul db
        if (sha1($pwd) != $dpwd)
        { 
            return array('Password errata ',null); 
        }
        $ses =  md5('['.$uid.']['.$_REQUEST['iid'].']['.$_SERVER['REMOTE_ADDR'].']['.time().']');
        $par = array($ses,$uid,$_SERVER['SERVER_NAME'],$_REQUEST['iid'],$_SERVER['REMOTE_ADDR'],date('Y-m-d H:i:s'),date('Y-m-d H:i:s'));
        env::$dbo->exec_cmd("INSERT INTO osy_log 
                            	   (ses_id,usr_id,hst_id,ses_ist,ses_ip,ses_dat,lst_op_dat)
                              VALUES
                                   (?,?,?,?,?,?,?)",$par);
        return array('ok',$ses);
    }
}
?>

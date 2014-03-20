<?
//File contenente le costanti
require_once('l.def.php');
require_once('c.prs.php');
require_once(OSY_PATH_DRV.'d.pdo.php');
require_once(OSY_PATH_DRV.'d.sqlite.php');

class env
{
    //ID dell'applicazione attuale
    public static $aid;
    //ID della form attuale
    public static $fid;
    //ID dell'istanza attuale
    public static $iid;
    //ID dell'utente attualmente connesso
    public static $uid;
    //Connessione al db osy
    public static $dbo;
    //Connessione al db app
    public static $dba;
    //stringa di connessione al db osy
    private static $_sdb; 
    //response container
    private static $__resp;
    //Corrent user is authenticated;
    public  static $is_auth = false;
    
    //Inizializzazione della classe statica
    public static function init($auth=true)
    {
        self::$_sdb = file_exists(OSY_PATH_CNF) ? file_get_contents(OSY_PATH_CNF) : "sqlite:".OSY_PATH_VAR."/osy-setup.sqlite3";
        self::$dbo = self::dbcon_by_str();
        self::$iid = self::get_instance_name();
        self::$aid = self::get_array_value($_REQUEST,'osy','aid');
        self::$fid = self::get_array_value($_REQUEST,'osy','fid');
        if ($auth) self::check_auth();
    }

    public static function check_auth()
    {
        if (!key_exists('osy',$_REQUEST) or empty($_REQUEST['osy']['sid']))
        { 
            self::page_error(401,'Unauthorized');
        }
        $res = self::trace($_REQUEST['osy']['sid']);
        switch($res['cmd'])
        {
             case 'EXIT':
                           die($res['msg']);
                           exit;
             default    :
                           $_REQUEST['_uid']  = self::$uid; //Id dell'utente
                           $_REQUEST['_iid']  = self::$iid; //Id dell'istanza
                           break;
         }
         self::$is_auth = true;
    }

    public static function dbcon_by_app($aid)
    {
	    $sql = "SELECT    r.p_1
                FROM      osy_obj_rel r 
                WHERE     r.o_1 = ?
                AND       r.o_2 = ?
                AND       r.r_typ = 'instance+application'";
    	$con_str = self::$dbo->exec_unique($sql,array('instance://'.self::$iid.'/',$aid));
	    return self::dbcon_by_str($con_str);
    }

    public static function dbcon_by_str($cnstr=null)
    {
        if (empty($cnstr)){ $cnstr = self::$_sdb; }
        if ($cnstr == self::$_sdb and self::$dbo){ return self::$dbo; }
        /*list($type,) = explode(':',self::$_sdb,2);
        if ($type == 'sqlite')
        {
            $cdb = new dbs($cnstr);
        }
         else
        {*/
            $cdb = new dbo($cnstr);
            $cdb->connect();
        //}
        return $cdb;
    }

    public static function get_instance_name()
    {
        return (($_SERVER['REQUEST_URI']=='/') ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
    }

    public static function nvl($a,$b)
    {
        return ( $a !==0 && $a !=='0' && empty($a)) ? $b : $a;
    }

    public static function page_error($err_num=401,$err_msg='Unauthorized',$form=''){
           header('HTTP/1.1 $err_num $err_msg');
           ?><html>
             <title>Opensymap &raquo; Error <?=$err_num?></title>
             <style>
                body,td,th {font-family: Arial;}
                body       {background-color: white;}
             </style>
             <body>
                <br/><br/> 
                <table align="center">
                    <tr>
                        <th>OPENSYMAP</th>
                    </tr>
                    <tr>
                        <td><br/><br/> Error number: <?=$err_num?></td>
                    </tr>
                    <tr>
                        <th><br/><?=$err_msg?><br/><br/></th>
                    </tr>                    
                    <tr>
                        <td><?=$form?></td>
                    </tr>
                </table>
             </body>
             </html><?
           exit;
    }

    public static function ReplaceVariable($Res)
    {
            $ORes = $Res;
            // old $Pattern = "/<\[(.*)?\]>/";
            $Pattern = "/<\[([^ ,]*)\]>/";
            /*
             * Il pattern prevede che nella risorsa i parametri siano indicati nel formato
             * <[...]> questo per evitare problemi con il segno di > e < eventualmente presenti.
             * In caso di risultati della query errata controllare che i parametri rispettino
             * il formato sopra riportato.
             */
		    preg_match_all($Pattern , $Res, $LVar, PREG_PATTERN_ORDER);
            if (!is_array($LVar)) return $Res; //Se non ÅEun array il risultato del matching restituisco la risorsa senza sostituzioni;
            /*
             * Scorro la lista delle variabili trovate dall'espressione regolare;
             */
            foreach($LVar[1] as $Key => $Var)
            {
                if (empty($Var)) continue; //Se la variabile non ha nome continuo l'esecuzione
                $VarVal = '';
                if ($Var == 'osy_user')
                {
                    //osy_user::osy_tmp_user();
                    $VarVal = 'osy_user';
                }
                 elseif (array_key_exists($Var,$_REQUEST))
                {
                    $VarVal = $_REQUEST[$Var];
                } 
                 elseif(array_key_exists($Var,$_POST)) 
                {
                    $VarVal = $_POST[$Var];
                } 
                 elseif(array_key_exists($Var,$_GET))
                {
                    $VarVal = $_GET[$Var];
                }
            
                $VarTag = $LVar[0][$Key];
            
                if (empty($VarVal))
                {
                    //Se il valore della variabile ÅEvuoto controllo che il paramentro non sia una stringa
                    $VarTag = (strpos($ORes,"'{$VarTag}'") > 1) ? "'{$VarTag}'" : $VarTag; //Se ÅEuna stringa (cioÅEÅEracchiusa tra virgolette) modifico il tag da sostituire in '<tag>'
                    //Metto NULL nel parametro da ricercare.
                    $VarVal = 'NULL';
                }
                $Res = str_replace($VarTag,$VarVal,$Res);
            }
            //echo $Res;
            return $Res;
    }
    
    public static function send_email($from,$a,$subject,$body){
        $head = "From: $from\r\n".
				"Reply-To: $from\r\n".
				"X-Mailer: PHP/".phpversion();
        return mail($a,$subject,$body,$head," -f info@spinit.it");
    }
    
    public static function set_page_header($n,$v)
    {
        header("$n: $v");
    }
    
    public static function parse_string($str)
    {
        $p = new parser($str);
        $p = $p->parse();
		return $p;
    }
    
    public static function exec_string($par,$cod)
    {
        $fnc = create_function($par,$cod);
        if (empty($fnc)) die($cod);
        return $fnc();
    }
    
    public static function get_array_value()
    {
        $narg = func_num_args() - 1;
        $args = func_get_args();
        $arr  = array_shift($args);
        for ($i = 0; $i < $narg; $i++)
        {
            $fval = null;
            if (is_array($arr) && key_exists($args[$i],$arr))
            {
                $arr = $fval = $arr[$args[$i]];
            }
        }
        return $fval;
    }
    
    public static function test($str)
    {
        $cod = str_replace('TEST','$res = ',env::ReplaceVariable($str).';');
        eval($cod);
        return $res;
    }
	
    public static function trace($ses,$par=null)
   {
        $res = array('cmd'=>'EXIT','msg'=>'Accesso non autorizzato.');
        /*Controllo che la sessione passata come par sia presente sulla tabella.*/
        $usr = self::$dbo->exec_unique('SELECT usr_id,ses_ist
                                        FROM   osy_log
                                        WHERE  ses_id = ?
                                          AND  ses_ip = ?
                                          AND  hst_id = ?',array($ses,$_SERVER['REMOTE_ADDR'],$_SERVER['SERVER_NAME']));
        if (!empty($usr))
        {
	        /*Se esiste un solo record di sessione allora Ë ok il caricamento della pagina*/
            $res['cmd'] = 'LOAD';
            $res['msg'] = 'Ok';
            self::$uid = $usr[0];
            self::$iid = $usr[1];
            self::$dbo->exec_cmd("UPDATE osy_log SET
                                         lst_op_app = ?,
                                         lst_op_frm = ?,
                                         lst_op_par = ?,
                                         lst_op_dat = ?
                                  WHERE ses_id = ?",array(self::$aid,self::$fid,$par,date('Y-m-d H:i:s'),$ses));
        }
        return $res;
    }

    public function reply()
    {
          return json_encode(self::$__resp);
    }

    public function resp()
    {
        $args = func_get_args();
        switch (count($args))
        {
            case 0:
                    break;
            case 1:
                    return key_exists($args[1],self::$__resp) ? self::$__resp[$args[1]] : null;
                    break;
            default:
                    $type = array_shift($args);
                    self::$__resp[$type][] = $args;
                    break;
        }
    }

    public static function set_header($n,$v){
        header("$n: $v");
    }
}
?>
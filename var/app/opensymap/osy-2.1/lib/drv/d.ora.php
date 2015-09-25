<?
  define('DEBUG_SCREEN',true);
  class DBOracle{
    private $Cn = array();
    var $cn;

	var $_attr;
	var $_autocommit = true;
	var $_page_error = false;
	var $_start_time;
	var $_total_time;
    var $_controlla_se_attivo=true;
    var $_is_addr_abile=false;
	var $_rowid;

    const type = 'ORACLE';

    private function __build_error_msg($e,$par){
        $this->_page_error = true;
        $this->_attr["errore"] = $err;
        $data = date("d/m/Y");
        $ora = date("H:i:s");
        $err_msg .= "In data $data alle ore $ora si &egrave; verificato il seguente errore\n";
        $err_msg .= "IP        : {$_SERVER['REMOTE_ADDR']}\n";
		$err_msg .= "Codice SQL: {$e->getCode()}\n";
		$err_msg .= "Errore    : {$e->getMessage()}\n";
		$err_msg .= "File      : {$e->getFile()}\n";
		$err_msg .= "Linea     : {$e->getLine()}\n";
		$err_msg .= "----------------TRACE----------------\n";
		$err_msg .= $e->getTraceAsString();
        //Costante dichiarata nel file /lib/sit/srv.cnf.php
        if (DEBUG_SCREEN){
    	   die('<pre>'.$err_msg.'</pre>');
        } else {
           $obj = "[{$_SERVER[SERVER_NAME]}]: Si è verificato un errore";
           mail(DEBUG_EMAIL,$obj,$err_msg);
           die("Si &egrave; verificato un errore nel processare la pagina.<br>Una mail &egrave; stata inviata al supporto <a href=\"mailto:".DEBUG_EMAIL."\">tecnico</a>.<br>");
        }
	}
    
	function set_autocommit($value){
        $old = $this->_autocommit;
	    $this->_autocommit = $value;
        return $old;
	}

    public function Connect(){
        $this->cn = oci_connect($this->Cn['username'],
                                $this->Cn['password'],
                                $this->Cn['hostname']);
        if (!$this->cn) {
           $e = oci_error();
           trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
    }

    function ExecuteQuery($rs,$sql){
        $res = ($this->_autocommit == false) ? oci_execute($rs,OCI_DEFAULT) : oci_execute($rs);
        if ($err = oci_error($rs)){
            throw new exception("{$err['message']}\nQuery     : {$err['sqltext']} \n", $err['code']);
        }
        return $res;
    }
    
    function ExecQuery($sql,$par=true,$rrs=true){
        $rs = oci_parse($this->cn,$sql);
        if (is_array($par)){ // sono dei parametri;{
            foreach($par as $key=>$val){
                $$key = $val;
				if (empty($val)){
                  @oci_bind_by_name($rs,":".$key,$$key,32);
				} else {
                  @oci_bind_by_name($rs,":".$key,$$key,-1);
				}
            }
        }

        try {
            $result = $this->ExecuteQuery($rs,$sql);
            if ($rrs) return $rs;
        } catch(Exception $e) {
           $this->__build_error_msg($e,$par);
        }
        
        if (is_array($par)){ // recupero valori
            foreach($par as $key=>$val){
                $par[$key] = $$key;
            }
            oci_free_statement($rs);
            return $par;
        }
        
        if ($err = oci_error($rs)){
            return false;
        }
        
        return $rs;
    }

    /***************************************************/
	function get($attr)
	{
	  return $this->_attr[$attr];
	}
	
	function ExecUniqueQuery($sql,$pin=null){
        $rs = $this->ExecQuery($sql,$pin);
	    if ($this->GetFieldCount($rs) > 1){
		   	$res = $this->GetNextRecord($rs,'BOTH');
    	} else {
	    	list($res) = $this->GetNextRecord($rs);
		}
    	$this->FreeRs($rs);
	    return $res;
	}

    function ExecQueryInArray($strSQL,$mode='NUM'){
        if (!empty($strSQL))
        {
            $rs = $this->ExecQuery($strSQL);
            $nf = $this->get_field_count($rs);
            
            while ($rec = $this->get_next_record($rs,$mode))
            {
                $res[] = $nf > 1 ? $rec : $rec[0];
            }
            $this->free_rs($rs);
            return $res;            
        }
        return false;
    }
	
    function ExecProc($cmd,$par=array()){
		if (!empty($cmd)){
			$rs = $this->ExecQuery( "BEGIN {$cmd}; END;",$par,false);
		} else  {
			echo "Errore. Query vuota";
		}
		return $rs;
	}
    
    public function GetAll($sql,$pin=null,$mth='ASSOC'){
        $rs = $this->ExecQuery($sql,$pin);
        $nr = ($mth == 'ASSOC') ? oci_fetch_all($rs,$res,null,null,OCI_ASSOC|OCI_FETCHSTATEMENT_BY_ROW) : oci_fetch_all($rs,$res,null,null,OCI_NUM|OCI_FETCHSTATEMENT_BY_ROW);
        return $res;
    }
    
    // Restituisce il nome del campo     ####################  OK ################
    public function GetFieldName($rs,$i)
	{
       $f = new stdClass();
       $f->name = oci_field_name($rs,$i+1);
       $f->type = oci_field_type($rs,$i+1);
       $f->size = oci_field_size($rs,$i+1);
	   return $f;
    }
    
    public function GetFieldType($rs,$i){
        $type = oci_field_type($rs,$i);
        $size = ($type == 'NUMBER') ? oci_field_precision($rs,$i).','.oci_field_scale($rs,$i) : oci_field_size($rs,$i);
        return array($type,$size);
    }
    
    //Metodo che restituisce il numero di campi        ####################  OK ################
    function GetFieldCount($rs){
     	return OCINumCols($rs);
    }
	
	function GetRowCount($rs){
		return OCIRowCount($rs);
	}
    
	function GetNextRecord($rs,$mode=NULL){
         try{
             switch($mode){
                case 'ASSOC':
                              @ocifetchinto($rs,$record,OCI_ASSOC|OCI_RETURN_NULLS|OCI_RETURN_LOBS);
                              break;
                case 'BOTH':
                              @ocifetchinto($rs,$record,OCI_BOTH|OCI_RETURN_NULLS|OCI_RETURN_LOBS);
                              break;
                default:
                              @ocifetchinto($rs,$record,OCI_NUM|OCI_RETURN_NULLS|OCI_RETURN_LOBS);
                              break;
             }
             //echo $this->_rowid->read(1024);
             if ($err = @oci_error($rs)) throw new exception($err['message'], $err['code']);
         } catch(Exception $e) {
             $this->__build_error_msg($e,$mailing);
         }
         return $record;
    }

    function FreeRs($rs)
	{
        if ($rs)
        {
            @OCIFreeStatement($rs);
        }
    }

    function CloseCn(){
          OCICommit($this->cn);
          OCILogoff($this->cn);
    }

    function Commit(){
        OCICommit($this->cn);
    }
    
    public function GetType(){
        return self::type;
    }
    
    function Insert($tbl,$arg,$ret=null){
        $into = array();
        $values = array();
        foreach($arg as $k=>$v){
            $into [] = $k;
            switch(substr($k,0,4)){
                case 'DAT_' :
                              $values [] = "TO_DATE(:{$k},'DD/MM/YYYY')";
                              break;
                default     :
                              $values [] = ':'.$k;
                              break;
            }

        }
        $cmd = 'insert into '.$tbl.'('.implode(',',$into).') values ('.implode(',',$values).')';
        if (is_array($ret)){
            $cmd .= "RETURNING {$ret[0]} INTO :{$ret[1]}";
            $arg[$ret[1]] = null;
        }
        $arg = $this->ExecQuery($cmd,$arg,false);
        return $arg[$ret[1]];
    }

    function Update($tbl,$arg,$cnd){
        $fld = array();
        foreach($arg as $k=>$v){
            switch(substr($k,0,4)){
                case 'DAT_' :
                              $fld[] = "{$k} = TO_DATE(:{$k},'DD/MM/YYYY')";
                              break;
                default     :
                              $fld[] = "{$k} = :{$k}";
                              break;
            }
            $val[$k] = $v;
        }
        if (!is_array($cnd)) $cnd = array('id'=>$cnd);
        $whr = array();
        foreach($cnd as $k=>$v){
            $whr[] = "$k = :whr_{$k}";
            $val['whr_'.$k] = $v;
        }
        $cmd .= 'update '.$tbl.' set '.implode(', ',$fld).' where '.implode(' and ',$whr);
       
        $this->ExecQuery($cmd,$val);
    }

    function Delete($tbl,$cnd){
        $whr = array();
        if (!is_array($cnd)) $cnd = array('id'=>$cnd);
        foreach($cnd as $k=>$v)
        {
            $whr[] = "{$k} = :{$k}";
            $val[] = $v;
        }
        $cmd .= 'delete from '.$tbl.' where '.implode(' and ',$whr);
        $this->ExecQuery($cmd,$val);
    }
    
    function Rollback(){
      OCIRollback($this->cn);
    }
    
    public function Ora2EuroDate($d,$s='-'){
      if (strpos($d,$s) === false) return $d;
      $month =  array('JAN' => '01',
              'FEB' => '02',
              'MAR' => '03',
              'APR' => '04',
              'MAY' => '05',
              'JUN' => '06',
              'JUL' => '07',
              'AUG' => '08',
              'SEP' => '09',
              'OCT' => '10',
              'NOV' => '11',
              'DEC' => '12');
        list($gg,$mm,$aa) = explode($s,$d);
        if (strlen($aa)==2){
            $aa = '20'.$aa;
        }
        if (array_key_exists($mm,$month)){
            $mm = $month[$mm];
        }
        return "{$gg}/{$mm}/{$aa}";
    }
    //Metodo che setta il parametri della connessione
    function SetCnPar($p,$v){
      $this->Cn[$p] = $v;
    }
  }
  //creo la classe e la inizializzo.
  //$cdb = new db_query_ora($PHP_SELF,$REMOTE_ADDR);
  //$cdb->init();
?>
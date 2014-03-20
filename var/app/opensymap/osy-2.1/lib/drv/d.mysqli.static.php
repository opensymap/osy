<?
class DBMysqli
{
    private $Cn = array();
    private $CnSel = null;
    private $Err = array();
    public  $Type = 'MYSQL';

    //Metodo che setta la connessione al db.
    public static function Connect($nam='default'){
      self::$CnSel = $nam;
      self::$Cn[self::$CnSel] = @new mysqli($this->Cn['hostname'],
                                           $this->Cn['username'],
                                           $this->Cn['password'],
                                           $this->Cn['database']);
      if ( self::Cn[self::$CnSel]->connect_error) {
        die('Connect Error: ' .  self::Cn[self::$CnSel]->connect_error);
      }
    }

    //Esegue la query
    public function ExecQuery($sql,$par=null){
      if (!empty($par) && is_array($par)){
            if ($stmt = self::$Cn[self::$CnSel]->prepare($sql)){
				$tmp = array();
                array_unshift($par,str_repeat('s',count($par)));
                array_unshift($par,$stmt);
				//Workaround necessario per aggirare modifica fatta da php 5.3 in poi sulla funzione mysqli_stmt_bind_param
				foreach($par as $key => $val) $tmp[$key] = &$par[$key];
                call_user_func_array('mysqli_stmt_bind_param',$tmp);
                $stmt->execute();
            } else {
                     echo self::$Cn[self::$CnSel]->error;
                     return false;
            }
      } else {
                if (empty($sql)){
                   throw new Exception('La query &egrave: vuota');
                } elseif(!is_string($sql)) {
                   throw new Exception('La stringa $sql non &egrave; una stringa');
                }
                $stmt = self::$Cn[self::$CnSel]->query($sql);
                if (!$stmt){
                    $stmt = self::$Cn[self::$CnSel]->SetError(self::Cn[self::$CnSel]->errno,self::Cn[self::$CnSel]->error,$sql);
                    throw new Exception("<pre>$sql</pre>[{$this->Cn['connection']->errno}] - {$this->Cn['connection']->error}");
                }
      }
      return $stmt;
    }
    
    public static function ExecQuery2($cmd,$Par=null){
        if (is_array($Par)){
            echo 'ci sono';
            $strSQL = str_replace('?',"'%s'",$cmd);
            var_dump($cmd);
            exit;
        }
    }
    
    public static function ExecMany($cmd,$LPar){
        $stmt = self::$Cn[self::$CnSel]->prepare($sql);
        foreach($LPar as $key => $par){
            array_unshift($par,str_repeat('s',count($par)));
            array_unshift($par,$stmt);
            call_user_func_array('mysqli_stmt_bind_param',$par);
            $stmt->execute();
        }
    }
    
    public static function ExecProc($cmd,$par=null){
         if (!empty($par) && is_array($par)){
            foreach($par as $key => $val){
                if ($key[0] != '@'){
                    $iPar[] = $val;
                } else {
                    $cmdOutBind .= (empty($cmdOutBind) ? 'SELECT ' : ',').$key;
                    $oArg[] = &$par[$key];
                }
            }
         }
         
         if ($stmt = self::Cn[self::$CnSel]->ExecQuery("CALL $cmd",$iPar)){
             $stmt->close();
             if ($cmdOutBind){
                 $stmt = self::Cn[self::$CnSel]->prepare($cmdOutBind);
                 $stmt->execute();
                 array_unshift($oArg,$stmt);
                 call_user_func_array('mysqli_stmt_bind_result',$oArg);
                 $stmt->fetch();
                 $stmt->close();
                 return $par;
              }
          } else {
            return false;
          }
    }
    
    public static function ExecProc2($cmd,$par=null){
         if (!empty($par) && is_array($par)){
            foreach($par as $key => $val){
                if ($key[0] != '@'){
                    $iPar[] = $val;
                } else {
                    $cmdOutBind .= (empty($cmdOutBind) ? 'SELECT ' : ',').$key;
                    $oArg[] = &$par[$key];
                }
            }
         }
         
         if ($stmt = $this->ExecQuery("CALL $cmd",$iPar)){
             $stmt->close();
             if ($cmdOutBind){
                 $stmt = $this->Cn['connection']->prepare($cmdOutBind);
                 $stmt->execute();
                 $stmt->store_result();
                 //var_dump($stmt);
                 //exit;
                 array_unshift($oArg,$stmt);
                 call_user_func_array('mysqli_stmt_bind_result',$oArg);
                 $stmt->fetch();
                 $stmt->close();
                 return $par;
              }
          } else {
            return false;
          }
    }
    
    public static function ExecUniqueQuery($strSQL,$typ=''){
				$rs = self::ExecQuery($strSQL);
				if (self::GetFieldCount($rs) > 1){
					$v = self::GetNextRecord($rs,$typ);
				} else {
				   list($v) = self::GetNextRecord($rs);
				}
				$rs->free();
				return $v;
	  }
    
     public static function GetError(){
        if (!empty(self::$Err['num'])){
            return "[ERROR MYSQLi {$this->Err['num']}] - {$this->Err['dsc']}";
        }
        return false;
    }

    private static function SetError($n,$e,$s){
        self::Err['num'] = $n;
        self::Err['dsc'] = $e;
        self::Err['sql'] = $s;
        return $this->Err;
    }

	//Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
	public static function GetLastId(){
	  return self::$Cn[self::$CnSel]->insert_id;
	}
    
    public function GetAll($sql,$mth='ASSOC'){
        $rs = $this->ExecQuery($sql);
        while ($rec = $this->GetNextRecord($rs,$mth)){
            $res[] = $rec;
        }
        if (is_array($res) && count($res) == 1 && count($res[0]) == 1){
        		$res = $res[0][0];
        } 
        return $res;
	}
	
	public static function GetNextRecord($rs,$typ=''){
        if ($rs){
            switch($typ){
             case 'ASSOC' : return $rs->fetch_array(MYSQLI_ASSOC); break;
             case 'NUM'   : return $rs->fetch_array(MYSQLI_NUM);   break;
             default      : return $rs->fetch_array(MYSQLI_BOTH);  break;
           }
        } else {
            return array();
            // erm : 13/12/08
            //throw new Exception('Recordset vuoto!');
        }
    }

	
    // Restituisce il nome del campo    ######### OK ##############
    public static function GetFieldName($rs,$i){
		return $rs->fetch_field_direct($i);
    }

    
    //Metodo che restituisce il numero di campi  ######### OK ##############
    public static function GetFieldCount($rs){
      	return $rs->field_count;
    }

    //Metodo che restituisce il numero di record del recordset  ######### OK ##############
    public static function GetRecordCount($rs){
		return $rs->num_rows;
    }

    // Libera la memoria occupata da un recordset ######### OK ##############
    public static function FreeRs($rs){
      $rs->free();
    }

    //chiude la connessione ######### OK ##############
    public static function CloseCn(){
        self::$Cn[self::$CnSel]->close();
    }
    
    public static function SetAutocommit($b){
        self::$Cn[self::$CnSel]->autocommit($b);
    }
    
    public function Commit(){
        self::$Cn[self::$CnSel]->commit();
    }
    
    public function Rollback(){
        self::$Cn[self::$CnSel]->rollback();
    }
    
    //Metodo che setta il parametri della connessione
    function SetCnPar($p,$v){
      $this->Cn[$p] = $v;
    }
/*End class*/
}

  //creo la classe e la inizializzo.
  //$cdb = new DBConnector;
?>

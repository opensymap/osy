<?
class dbs extends SQLite3
{
    private $__par = array('type'=>'sqlite');
    public  $backticks = '"';
    
    public function __construct($str_cn='')
    {
        list($type,$path) = explode(':',$str_cn);
        $this->__par['filename'] = $path;
        parent::__construct($path);
    }
    
    public function begin()
    {
        $this->beginTransaction();
    }
    
    public function column_count()
    {
       return $this->__cur->columnCount();
    }
    
    public function connect()
    {
        
    }

	function get_type()
    {
	   return $this->__par['typ'];
	}

    //Metodo che setta il parametri della connessione
    function set_par($p,$v)
    {
      $this->__par[$p] = $v;
    }

	//Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
	function last_id()
    {
	  return $this->lastInsertRowID();
	}
    
    public function exec_cmd($cmd,$par=null)
    {
        if (!empty($par))
        {
            $s = $this->prepare($sql);
            if (is_array($par)) { foreach($par as $kpar => $vpar) $s->bindParam($kpar+1,$vpar); }
            $s->execute();
        }
         else
        {
            $this->exec($cmd);
        }
    }
    
    public function exec_multi($cmd,$par)
    {
        $this->beginTransaction();
        $s = $this->prepare($cmd);
        foreach($par as $rec)
        {
            $s->execute($rec);
        }
        $this->commit();
    }
    
    public function exec_query($sql,$par=null,$mth=null)
    {
        $s = $this->prepare($sql);
        if (is_array($par))
        {
            foreach($par as $kpar => $vpar)
            {
                echo $kpar.'-'.$vpar.'<br>';
                $val = $vpar;
                $s->bindParam($kpar,$val,SQLITE3_TEXT);
            }
        }
        $this->__cur = $s->execute();
        switch ($mth)
        {
            case 'NUM':
                            $mth = SQLITE3_NUM;
                            break;
            case 'ASSOC':
                            $mth = SQLITE3_ASSOC;
                            break;
            default :
                            $mth = SQLITE3_BOTH;
                            break;
        }
        $res = array();
        while($rec = $this->__cur->fetchArray($mth))
        { 
            $res[] = $rec;
        }
        var_dump($res);
        return $res;
    }

   public function exec_unique($sql,$par=null,$mth='NUM')
   {
       $res = $this->exec_query($sql,$par,$mth);
       if (empty($res)) return null;
       return (count($res)== 1 && count($res[0])==1) ? $res[0][0] : $res[0];
   }
   
   public function get_columns($stmt=null)
   {
      $stmt = is_null($stmt) ? $this->__cur : $stmt;
      $cols = array();
      $ncol = $stmt->numColumns();
      for($i = 0; $i < $ncol; $i++)
      {
        $cols[] = array('name' => $stmt->columnName($i),'type'=>$stmt->columnType($i));
      }
      return $cols;
   }
   
   public function insert($tbl,$arg)
   {
        $fld = $val = array();
        foreach($arg as $k=>$v)
        {
            $fld [] = $k;
            $val [] = '?';
            $arg2[] = $v;
        }
        $cmd = 'insert into '.$tbl.'('.implode(',',$fld).') values ('.implode(',',$val).')';
        $this->exec_cmd($cmd,$arg2);
        return $this->lastInsertId();
   }

    public function update($tbl,$arg,$cnd)
    {
        $fld = array();
        foreach($arg as $k=>$v)
        {
            $fld[] = "{$k} = ?";
            $val[] = $v;
        }
        if (!is_array($cnd))
        { 
          $cnd = array('id'=>$cnd);
        }
        $whr = array();
        foreach($cnd as $k=>$v)
        {
            $whr[] = "$k = ?";
            $val[] = $v;
        }
        $cmd .= 'update '.$tbl.' set '.implode(', ',$fld).' where '.implode(' and ',$whr);
        // mail('p.celeste@spinit.it','query',$cmd."\n".print_r($val,true));
        $this->exec_cmd($cmd,$val);
    }

    public function delete($tbl,$cnd)
    {
        $whr = array();
        if (!is_array($cnd)) $cnd = array('id'=>$cnd);
        foreach($cnd as $k=>$v)
        {
            $whr[] = "{$k} = ?";
            $val[] = $v;
        }
        $cmd .= 'delete from '.$tbl.' where '.implode(' and ',$whr);
        $this->exec_cmd($cmd,$val);
    }
    
    public function par($p)
    {
        return key_exists($p,$this->__par) ? $this->__par[$p] : null;
    }
    //For compatibility
    public function cast($field,$type)
    {
        return $field;   
    }
/*End class*/
}

class db_sqlite3
{
    private $__par = array();
    private $Err = array();
    public $Type = 'SQLITE';
        //Metodo che inizializza la classe
    
    public function __construct($str_cn='')
    {
        list($type,$path) = explode(':',$str_cn);
        $this->__par['filename'] = $str_cn;
        $this->__par['hostname'] =& $this->__par['filename'];
    }
    
    //Metodo che setta la connessione al db.       ######### OK ##############
    public function connect()
    {
       if (file_exists($this->__par['filename']))
       {
         $this->__par['connection'] = new SQLite3($this->__par['filename']);
       }
        else
       {
         echo $this->__par['filename'];
       }
    }

    public function GetError()
    {
        if (!empty($this->Err['num']))
        {
            return "[ERROR Sqlite3 {$this->Err['num']}] - {$this->Err['dsc']}";
        }
        return false;
    }

    //Metodo che setta il parametri della connessione
    function SetCnPar($p,$v)
    {
      $this->__par[$p] = $v;
    }
	
    private function SetError($n,$e,$s){
        $this->Err['num'] = $n;
        $this->Err['dsc'] = $e;
        $this->Err['sql'] = $s;
        return $this->Err;
    }

	//Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
	function GetLastId()
    {
        return $this->__par['connection']->lastInsertRowID();
	}
    
    public function exec($sql)
    {
        return $this->__par['connection']->exec($sql);
    }
    
    //Esegue la query                   ######### OK ##############
    public function ExecQuery($sql,$par=null)
    {
      if (empty($sql))
      {
         throw new Exception('La query &egrave: vuota');
      } 
        elseif(!is_string($sql)) 
      {
        throw new Exception('La stringa $sql non &egrave; una stringa');
      }
      if (!empty($par) && is_array($par))
      {
            if ($stmt = $this->__par['connection']->prepare($sql))
            {
                array_unshift($par,str_repeat('s',count($par)));
                array_unshift($par,$stmt);
                call_user_func_array('mysqli_stmt_bind_param',$par);
                $stmt->execute();
            } 
             else 
            {
                echo $this->__par['connection']->error;
                return false;
            }
      } 
       else 
      {
            $stmt = $this->__par['connection']->query($sql);
            if (!$stmt)
            {
                $stmt = $this->SetError(1,$err,$sql);
                throw new Exception("[Error] - " . $this->__par['connection']->lastErrorMsg());
            }
      }
      return $stmt;
    }

    public function ExecUniqueQuery($strSQL,$typ='')
    {
		$rs = $this->ExecQuery($strSQL);
		if ($this->GetFieldCount($rs) > 1)
        {
			$v = $this->GetNextRecord($rs,$typ);
		} 
         else 
        {
		   list($v) = $this->GetNextRecord($rs);
		}
		return $v;
	}

    public function GetAllRecord($rs,$type='')
    {
        $allrec = $rs->fetchAll();
		$this->FreeRs($rs);
		return $allrec;
	}
	
	public function GetNextRecord($rs,$typ='BOTH')
    {
        if (empty($rs)) throw new Exception('Recordset vuoto!');
        $get_method = array('ASSOC'=>SQLITE_ASSOC,'NUM'=>SQLITE_NUM,'BOTH'=>SQLITE_BOTH);
        return $rs->fetcharray($get_method[$typ]);
    }

    // Restituisce il nome del campo    ######### OK ##############
    public function GetFieldName($rs,$i){
		return $rs->column($i);
    }

    
    //Metodo che restituisce il numero di campi  ######### OK ##############
    public function GetFieldCount($rs){
      	return $rs->numFields;
    }

    //Metodo che restituisce il numero di record del recordset  ######### OK ##############
    public function GetRecordCount($rs){
		return $rs->numRows;
    }

    // Libera la memoria occupata da un recordset ######### OK ##############
    public function FreeRs(&$rs){
        $rs = null;
    }

    //chiude la connessione ######### OK ##############
    public function CloseCn()
    {
      $this->__par['connection']->close();
    }
/*End class*/
}
?>

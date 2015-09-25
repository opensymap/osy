<?
class DBMysqli
{
    private $Cn = array();
    private $Err = array();
    const  type = 'MYSQLI';

    //Metodo che setta la connessione al db.       ######### OK ##############
    public function connect()
    {
      $this->Cn['connection'] = @new mysqli($this->Cn['hostname'], $this->Cn['username'], $this->Cn['password'], $this->Cn['database']);
      if ($this->Cn['connection']->connect_error)
      { 
        throw new Exception('Connection Error: ' . $this->Cn['connection']->connect_error);
      }
      mysqli_set_charset($this->Cn['connection'], "utf8");
    }

    public function GetError($Array=false)
    {
        if (!empty($this->Err['num']))
		{
            return ($Array) ? $this->Err : "[ERROR MYSQLi {$this->Err['num']}] - {$this->Err['dsc']}";
        }
        return false;
    }

	function GetType()
    {
	   return self::type;
	}

    //Metodo che setta il parametri della connessione
    function SetCnPar($p,$v)
    {
      $this->Cn[$p] = $v;
    }

    private function SetError($n,$e,$s)
    {
        $this->Err['num'] = $n;
        $this->Err['dsc'] = $e;
        $this->Err['sql'] = $s;
        return $this->Err;
    }

	//Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
	function GetLastId()
    {
	  return $this->Cn['connection']->insert_id;
	}

    //Esegue la query                   ######### OK ##############
    public function ExecQuery($sql,$par=null)
    {
      if (!empty($par) && is_array($par))
      {
            if ($stmt = $this->Cn['connection']->prepare($sql))
            {
				$tmp = array();
                array_unshift($par,str_repeat('s',count($par)));
                array_unshift($par,$stmt);
				//Workaround necessario per aggirare modifica fatta da php 5.3 in poi sulla funzione mysqli_stmt_bind_param
				foreach($par as $key => $val) $tmp[$key] = &$par[$key];
                call_user_func_array('mysqli_stmt_bind_param',$tmp);
                $stmt->execute();
            } 
             else 
            {
                echo $this->Cn['connection']->error;
                return false;
            }
      } 
	   else 
	  {
            if (empty($sql))
            {
                throw new Exception('La query &egrave: vuota');
            } 
             elseif(!is_string($sql)) 
            {
                throw new Exception('La stringa $sql non &egrave; una stringa');
            }
            $stmt = $this->Cn['connection']->query($sql);
            if (!$stmt)
            {
                $stmt = $this->SetError($this->Cn['connection']->errno,$this->Cn['connection']->error,$sql);
                throw new Exception("<pre>$sql</pre>[{$this->Cn['connection']->errno}] - {$this->Cn['connection']->error}");
                die("<pre>$sql</pre>[{$this->Cn['connection']->errno}] - {$this->Cn['connection']->error}");
            }
      }
      return $stmt;
    }
    
    public function ExecQuery2($cmd,$raw,$bind=null)
    {
            if ($stmt = $this->Cn['connection']->prepare($cmd))
            {
                $nqst = substr_count($cmd,'?');
				$par = array_slice($raw,0,$nqst);
                $bnd = (is_array($bind)) ? $bind : array_slice($raw,$nqst);
				//Workaround necessario per aggirare modifica fatta da php 5.3 in poi sulla funzione mysqli_stmt_bind_param
				foreach($par as $key => $val)
                { 
                    $par2[$key] =& $par[$key];
                }
                array_unshift($par2,$stmt,str_repeat('s',count($par)));
				//var_dump($par2);
				call_user_func_array('mysqli_stmt_bind_param',$par2);
                $stmt->execute();
                if (!empty($stmt->error_list))
                {
                    die("\n<pre>$Cmd</pre>\n[{$this->Cn['connection']->errno}] - {$this->Cn['connection']->error}");
                }
                //Se non viene passato l'array di binding restituisco true;
                if (empty($bnd)) return true;
                foreach($bnd as $key => $val) { $bnd2[$key] =& $bnd[$key]; }
				array_unshift($bnd2,$stmt);
				
                call_user_func_array('mysqli_stmt_bind_result',$bnd2);
                $res = array();
                while($stmt->fetch())
                { 
                    $res[] = array_slice($bnd2,1);
                }
                return $res;
            } 
             else 
            {
              die("\n<pre>$Cmd</pre>\n[{$this->Cn['connection']->errno}] - {$this->Cn['connection']->error}");
            }
    }
    
    public function ExecMany($cmd,$LPar)
	{
        $stmt = $this->Cn['connection']->prepare($sql);
        foreach($LPar as $key => $par)
		{
            array_unshift($par,str_repeat('s',count($par)));
            array_unshift($par,$stmt);
            call_user_func_array('mysqli_stmt_bind_param',$par);
            $stmt->execute();
        }
    }

    public function ExecProc($cmd,$par=null)
	{
         if (!empty($par) && is_array($par))
		 {
            foreach($par as $key => $val)
			{
                if ($key[0] != '@')
				{
                    $iPar[] = $val;
                } 
				 else 
				{
                    $cmdOutBind .= (empty($cmdOutBind) ? 'SELECT ' : ',').$key;
                    $oArg[] = &$par[$key];
                }
            }
         }
         
         if ($stmt = $this->ExecQuery("CALL $cmd",$iPar))
		 {
             $stmt->close();
             if ($cmdOutBind)
			 {
                 //return $this->ExecUniqueQuery($cmdOutBind);
                 $stmt = $this->Cn['connection']->prepare($cmdOutBind);
                 $stmt->execute();
                 //var_dump($oArg);
				 $stmt->store_result();
                 array_unshift($oArg,$stmt);
                 call_user_func_array('mysqli_stmt_bind_result',$oArg);
                 //$stmt->bind_result($col1, $col2);
                 $stmt->fetch();
                 $stmt->close();
                 return array($col1,$col2);
              }
         } 
		   else 
		 {
            return false;
         }
    }
    
    public function ExecProc2($cmd,$par=null)
    {
         if (!empty($par) && is_array($par))
         {
            foreach($par as $key => $val)
            {
                if ($key[0] != '@')
                {
                    $iPar[] = $val;
                } 
                 else 
                {
                    $cmdOutBind .= (empty($cmdOutBind) ? 'SELECT ' : ',').$key;
                    $oArg[] = &$par[$key];
                }
            }
         }
         
         if ($stmt = $this->ExecQuery("CALL $cmd",$iPar))
         {
             $stmt->close();
             if ($cmdOutBind)
             {
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
          } 
            else 
          {
            return false;
          }
    }

   public function ExecUniqueQuery($sql,$par=null,$bnd=null)
   {
        if (is_array($par))
        {
            $res = $this->ExecQuery2($sql,$par,$bnd);
            return (count($res)==1) ? $res[0] : $res;
        }
        $rs = $this->ExecQuery($sql);
        if ($this->GetFieldCount($rs) > 1)
        {
        	$v = $this->GetNextRecord($rs,$par);
        } 
         else 
        {
           list($v) = $this->GetNextRecord($rs);
        }
        $rs->free();
        return $v;
	}

    public function GetAll($sql,$mth='ASSOC')
    {
        $rs = $this->ExecQuery($sql);
		$res = array();
        while ($rec = $this->GetNextRecord($rs,$mth))
        {
            $res[] = $rec;
        }
        return $res;
	}

	public function GetNextRecord($rs,$typ='')
    {
        if ($rs)
        {
            switch($typ)
            {
             case 'ASSOC' : 
                            return $rs->fetch_array(MYSQLI_ASSOC); 
                            break;
             case 'NUM'   : 
                            return $rs->fetch_array(MYSQLI_NUM);   
                            break;
             default      : 
                            return $rs->fetch_array(MYSQLI_BOTH);  
                            break;
           }
        } 
          else 
        {
            return array();
        }
    }

    // Restituisce il nome del campo    ######### OK ##############
    public function GetFieldName($rs,$i)
    {
		return $rs->fetch_field_direct($i);
    }
    
    public function GetFields($rs)
    {
        return $rs->fetch_fields();
    }
    
    //Metodo che restituisce il numero di campi  ######### OK ##############
    public function GetFieldCount($rs)
    {
      	return $rs->field_count;
    }

    //Metodo che restituisce il numero di record del recordset  ######### OK ##############
    public function GetRecordCount($rs)
    {
		return $rs->num_rows;
    }

    // Libera la memoria occupata da un recordset ######### OK ##############
    public function FreeRs($rs)
    {
      $rs->free();
    }

    function insert($tbl,$arg)
    {
        $into = array();
        $values = array();
        foreach($arg as $k=>$v)
        {
            $into [] = $k;
            $values [] = '?';
        }
        $cmd = 'insert into '.$tbl.'('.implode(',',$into).') values ('.implode(',',$values).')';
        $this->ExecQuery2($cmd,$arg);
        return $this->GetLastId();
    }

    function update($tbl,$arg,$cnd)
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
        $this->ExecQuery2($cmd,$val);
    }

    function delete($tbl,$cnd)
    {
        $whr = array();
        if (!is_array($cnd)) $cnd = array('id'=>$cnd);
        foreach($cnd as $k=>$v)
        {
            $whr[] = "{$k} = ?";
            $val[] = $v;
        }
        $cmd .= 'delete from '.$tbl.' where '.implode(' and ',$whr);
        $this->ExecQuery2($cmd,$val);
    }
    
    //chiude la connessione ######### OK ##############
    public function CloseCn()
	{
      $this->Cn['connection']->close();
    }
    
    public function SetAutocommit($b)
	{
        $this->Cn['connection']->autocommit($b);
    }
    
	public function SetCharset($c)
	{
	   $this->Cn['connection']->set_charset($c);
	}
	
    public function Commit()
	{
        $this->Cn['connection']->commit();
    }
    
    public function Rollback()
	{
        $this->Cn['connection']->rollback();
    }
    
    public function escape($val)
    {
        return addcslashes($val,"'");
    }
/*End class*/
}
?>

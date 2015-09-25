<?php
class dbo extends PDO
{
    private $__par = array();
    private $__cur = null;
    public  $backticks = '"';
    
    public function __construct($str)
    {
        $par = explode(':',$str);
        switch ($par[0])
        {
            case 'sqlite':
                            $this->__par['typ'] = $par[0];
                            $this->__par['db']  = $par[1];
                            break;
            case 'mysql' :
                            $this->backticks = '`';
            default:
                            $this->__par['typ'] = $par[0];
                            $this->__par['hst'] = $par[1];
                            $this->__par['db']  = $par[2];
                            $this->__par['usr'] = $par[3];
                            $this->__par['pwd'] = $par[4];
                            break;
        }
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
        switch($this->__par['typ'])
        {
            case 'sqlite':
                     parent::__construct("{$this->__par['typ']}:{$this->__par['db']}");
                     break;
            default:
                     parent::__construct("{$this->__par['typ']}:dbname={$this->__par['db']};host:{$this->__par['hst']}",$this->__par['usr'],$this->__par['pwd']);
                     break;
        }
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
	  return $this->lastInsertId();
	}
    
    public function exec_cmd($cmd,$par=null)
    {
        if (!empty($par))
        {
            $s = $this->prepare($cmd);
            $s->execute($par);
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
        $this->__cur = $this->prepare($sql);
        $this->__cur->execute($par);
        switch ($mth)
        {
            case 'NUM':
                            $mth = PDO::FETCH_NUM;
                            break;
            case 'ASSOC':
                            $mth = PDO::FETCH_ASSOC;
                            break;
            default :
                            $mth = PDO::FETCH_BOTH;
                            break;
        }
        $res = $this->__cur->fetchAll($mth);
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
      $ncol = $stmt->columnCount();
      for($i = 0; $i < $ncol; $i++)
      {
        $cols[] = $stmt->getColumnMeta($i);
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
    
    public function cast($field,$type)
    {
        $cast = $field;
        switch($this->get_type())
        {
            case 'pgsql':
                         $cast .= '::'.$type;
                         break;
        }
        return $cast;
    }
/*End class*/
}

  //creo la classe e la inizializzo.
  //$cdb = new DBConnector;
?>
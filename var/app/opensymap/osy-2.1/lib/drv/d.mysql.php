<?
class DBMysql
{
/*Start class*/  
    var $username;
    var $password;
    var $hostname;
    var $dbname;
    var $cn;

    //Metodo che setta il nome del db.
    function setDb($name_db)
	{
      $this->dbname = $name_db;
    }

    //Metodo che setta lo username.
    function setUsername($user)
	{
       $this->username = $user;
    }

    //Metodo che setta la password.
    function setPassword($pwd)
	{
      $this->password = $pwd;
    }

    //Metodo che setta l'hostname.
    function setHostname($host)
	{
      $this->hostname = $host;
    }

    //Metodo che setta la connessione al db.       ######### OK ##############
    function connectDb()
	{
      $this->cn = mysql_connect($this->hostname,$this->username,$this->password) or die("ERRORE: connessione al db $this->dbname non possibile");
    }
	
	//Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
	function getLastId()
	{
	  $last_id = mysql_insert_id($this->cn);
	  return $last_id;
	}
    
    function getSequence($name,$option='nextval')
    {
        if (!empty($name))
        {
            if ($option == 'nextval')
            {
                $sequence_value = $this->execUniqueQuery('SELECT next_value FROM sys_sequence WHERE UPPER(seq_name) = UPPER(\''.$name.'\')');
                $this->execQuery('UPDATE sys_sequence SET 
                                         next_value = next_value + increment,
                                         current_value = current_value + increment
                                  WHERE UPPER(seq_name) = UPPER(\''.$name.'\')');
            }
             elseif ($option == 'curval')
            {
                $sequence_value = $this->execUniqueQuery('SELECT curent_value FROM sys_sequence WHERE UPPER(seq_name) = UPPER(\''.$name.'\')');
            }
            
            return $sequence_value;
        }
         else
        {
            return false;
        }
    }
    
    //Esegue la query                   ######### OK ##############
    function ExecQuery($strSQL)
	{
      $rs = mysql_db_query($this->dbname,$strSQL) 
            or 
            die("Impossibile effettuare la query:<br><pre>".htmlentities($strSQL)."</pre><br><B>MYSQL Error : ".mysql_error()."</B>");
      return $rs;
    }

    function ExecQueryDebug($strSQL)
	{
     $rs = mysql_db_query($this->dbname,$strSQL) or
               die("<pre>ERRORE => Metodo execQuery : Non e' possibile eseguire la query. DB = $this->dbname
                    Testo query : <FONT COLOR=RED>".htmlentities($strSQL)."</FONT>>
                        <B>MYSQL Error : ".mysql_error()."</b></pre>");
      return $rs;
    }
	
    function execUniqueQuery($strSQL)
	{
		$rs = $this->execQuery($strSQL);
		if ($this->getFieldCount($rs) > 1)
		{
			$value = $this->getNextRecord($rs);
		}
		 else
		{
		   list($value) = $this->getNextRecord($rs);
		}
		$this->freeRs($rs);
		return $value;
	}

    function resetRs($rs)
	{
      mysql_data_seek($rs,0) or die("ERRORE metodo resetRs : ".mysql_error());
    }

    // Prende l' i-esimo record del recordset sotto forma di vettore    ############# OK #############
    function getRecord($rs,$i)
	{
      mysql_data_seek($rs,$i) or die ("ERRORE metodo getRecord: ".mysql_error());
      $record = mysql_fetch_array($rs);
      return $record;
    }

    function getNextRecord($rs,$type='')
	{
        if ($type=='ASSOC')
        {
           $row = mysql_fetch_array($rs,MYSQL_ASSOC);
        }
         elseif ($type=='NUM')
        {
           $row = mysql_fetch_array($rs,MYSQL_NUM);
        } 
         else
        {
           $row = mysql_fetch_array($rs,MYSQL_BOTH);
        }
        return $row;
    }
    
	// Prende il valore del campo numero $fieldId      ######### OK ##############
    function getField($rs,$nr,$fieldId)
	{
     array($app);
     $app = $this->getRecord($rs,$nr);
     return ($app[$fieldId]);
    }

    // Restituisce il nome del campo    ######### OK ##############
    function getFieldName($rs,$i)
	{
     $name=mysql_field_name($rs,$i);
     return $name;
    }

    //Metodo che restituisce il numero di campi  ######### OK ##############
    function getFieldCount($rs)
	{
      $num = mysql_num_fields($rs);
      return $num;
    }

    //Metodo che restituisce il numero di record del recordset  ######### OK ##############
    function getRecordCount($rs)
	{
     $linee=mysql_num_rows($rs);
     return $linee;
    }

    //Metodo che inizializza la classe
    function db_query()
    {
	  $this->setDb("gestionale");
    	  $this->setUsername("webuser");
          $this->setPassword("webpassword");
       	  $this->setHostname("localhost");
          $this->connectDb();
    }

    // Libera la memoria occupata da un recordset ######### OK ##############
    function freeRs($rs)
	{
      mysql_free_result($rs);
    }

    //chiude la connessione ######### OK ##############
    function closeCn()
	{
      mysql_close($this->cn);
    }
/*End class*/
}

  //creo la classe e la inizializzo.
  //$cdb = new db_query;
?>

<?
/*
 +-----------------------------------------------------------------------+
 | lib/c.sql.php                                                         |
 |                                                                       |
 | This file is part of the Gestional Framework                          |
 | Copyright (C) 2005-2014, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description : This page checks values which have been passed from     |
 |               "html form" and sends errors to user. If no error is    |
 |               present execute a sql data manipulation (insert, delete |
 |               or update)                                              |
 |-----------------------------------------------------------------------|
 | Creation date : 2013-07-19                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/

  class Sql
  {
     private $__db;  //Propieta contenente il riferimento alla connessione DB.
     private $__error = false;
     private $__error_list = array();
     private $__query = array();
     private $__trigger = array();
     
     public function __construct($d,$t)
     {
        $this->__db = $d;
        $this->__query = array('command'   => 'insert',
                               'field'     => array(),
                               'field_raw' => array(),
                               'key'       => array(),
                               'table'     => $t);
     }

     private function __check_value__()
     {
        //check if exist key in the update e delete operation
        switch($this->__query['command'])
        {
            case 'delete' :
            case 'update' :
                            if (count($this->__query['key']) == 0)
                            {
                                $this->set_error_by_number(10);
                            }
                            break;
        }
        //Se l'operazione da eseguire è il delete posso saltare i controlli dei valori contenuti nei campi.
        if ($this->__query['command'] == 'delete'){ return false; }
        
        return $this->has_error();
     }

     private function __execute_trigger__($event)
     {
         if (array_key_exists($event,$this->__trigger))
         {
            foreach($this->__trigger[$event] as $name => $code)
            {
              if ($function = @create_function('$Db,$Self',$code))
              {
                  $error = $function($this->__db,$this);
              }
               else
              {
                  $e = error_get_last();
                  $error  = "TRIGGER : {$name}\n";
                  $error .= "EVENT   : {$event}\n";
                  $error .= "LINE    : {$e['line']}\n";
                  $error .= "MESSAGE : {$e['message']}\n";
              }
              if(!empty($error)) $this->set_error($error);
            }
         }
         return $this->has_error();
     }

     //Aggiungo un campo alla query;
     public function add_field($field)
     {
        $this->__query['field'][$field['db-field-connected']] =& $field['value']; //Assegno il puntatore in modo che nei trigger si possa modificare il valore del campo puntando a REQUEST;
        $this->__query['field_raw'][$field['db-field-connected']] = $field;
		//Se il campo è chiave primaria  non e' gia' contenuto nell'array key lo aggiungo con valore null.
		if (!empty($field['db-field-is-pkey']) && !array_key_exists($field['db-field-connected'],$this->__query['key']))
		{
			$this->__query['key'][$field['db-field-connected']] =& $field['value'];
		}
     }

     //Aggiungo una chiave alla query
     public function add_key($f,$v)
     {
        if (empty($this->__query['command']) or ($this->__query['command'] == 'insert'))
        {
            $this->__query['command'] = 'update';
        }
        $this->__query['key'][$f] = $v;
     }

     //Aggiungo un trigger da eseguire $event = evento, $name = nome, $code = Codice da eseguire
     public function add_trigger($event,$name,$code)
     {
        $this->__trigger[$event][$name] = $code;
     }

     public function execute()
     {
        //Eseguo le procedure di tipo library
        if ($this->__execute_trigger__('library')) { return false; }
        //Effettuo il controllo dei valori passati
        if ($this->__check_value__()) { return false; }
        //Eseguo le procedure del tipo [operation]-before 
        if ($this->__execute_trigger__(strtolower($this->__query['command'].'-before'))) { return false; }
        try
        {
            switch($this->__query['command'])
            {
                case 'insert':
                                $this->__db->insert($this->__query['table'],$this->__query['field']);
                                foreach($this->__query['key'] as $field_name => $field_value)
                                {
                                    if (is_null($field_value) or $field_value === '')
                                    {
                                        $component_name = $this->__query['field_raw'][$field_name]['component-name'];
                                        if (empty($_REQUEST[$component_name]))
                                        {
                                            $_POST[$component_name] = $_REQUEST[$component_name] = $this->__query['key'][$field_name] = $this->__db->lastinsertid();
                                        }
                                    }
                                }
                                break;
                case 'update':
                                $this->__db->update($this->__query['table'],$this->__query['field'],$this->__query['key']);
                                break;
                case 'delete':
                                $this->__db->delete($this->__query['table'],$this->__query['key']);
                                break;
            }
        }
         catch(Exception $e)
        {
           $this->set_error($e->getMessage());
           return false;
        }
        //Eseguo le procedure di tipo [operation]-after
        if ($this->__execute_trigger__(strtolower($this->__query['command'].'-after'))) 
        {
           return false; 
        }
        return true;
     }

     public function get_error()
     {
        return $this->__error_list;
     }

     public function get_primary_key($nam=null)
     {
        if (empty($nam))
        {
            return $this->__query['key'];
        } 
         elseif (array_key_exists($nam,$this->__query['key']))
        {
            return $this->__query['key'][$nam];
        } 
        return false;
     }
     
     public function has_error()
     {
        return count($this->__error_list);
     }
     
     public function set_command($command)
     {
        $this->__query['command'] = $command;
     }

     public function set_error($error)
     {
        $this->__error_list[] = $error;
        if (class_exists('env')) env::resp('error',$error);
     }

     public function set_error_by_number($num,$par)
     {
        global $err_msg;
        $err = str_replace(array('[label]','[component]','[value]'),$par,$err_msg[$num][USR_LNG]);
        $this->__error_list[] = $err;
        if (class_exists('env')) env::resp('error',$err);
     }
  }
?>

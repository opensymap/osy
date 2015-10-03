<?php
/**
 * Sqlite3 wrap class
 *
 * PHP Version 5
 *
 * @category Driver
 * @package  Opensymap
 * @author   Pietro Celeste <p.celeste@opensymap.org>
 * @license  GPL http://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     http://docs.opensymap.org/ref/Osy
 */
namespace Opensymap\Driver;

class DboSqlite extends \SQLite3
{
    private $__par = array('type'=>'sqlite');
    public  $backticks = '"';
    
    public function __construct($str_cn = '')
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

    public function get_type()
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
    
    public function exec_cmd($cmd, $par = null)
    {
        if (!empty($par)) {
            $s = $this->prepare($sql);
            if (is_array($par)) { 
                foreach ($par as $kpar => $vpar) {
                    $s->bindParam($kpar+1,$vpar); 
                }
            }
            $s->execute();
        } else {
            $this->exec($cmd);
        }
    }
    
    public function exec_multi($cmd, $par)
    {
        $this->beginTransaction();
        $s = $this->prepare($cmd);
        foreach ($par as $rec) {
            $s->execute($rec);
        }
        $this->commit();
    }
    
    public function exec_query($sql, $par = null, $mth = null)
    {
        $s = $this->prepare($sql);
        if (is_array($par)) {
            foreach ($par as $kpar => $vpar) {
                echo $kpar.'-'.$vpar.'<br>';
                $val = $vpar;
                $s->bindParam($kpar,$val,SQLITE3_TEXT);
            }
        }
        $this->__cur = $s->execute();
        switch ($mth) {
            case 'NUM':
                $mth = SQLITE3_NUM;
                break;
            case 'ASSOC':
                $mth = SQLITE3_ASSOC;
                break;
            default:
                $mth = SQLITE3_BOTH;
                break;
        }
        $res = array();
        while ($rec = $this->__cur->fetchArray($mth)) { 
            $res[] = $rec;
        }        
        return $res;
    }

   public function exec_unique($sql, $par = null, $mth = 'NUM')
   {
       $res = $this->exec_query($sql, $par, $mth);
       if (empty($res)) return null;
       return (count($res[0])==1) ? $res[0][0] : $res[0];
   }
   
   public function get_columns($stmt = null)
   {
        $stmt = is_null($stmt) ? $this->__cur : $stmt;
        $cols = array();
        $ncol = $stmt->numColumns();
        for ($i = 0; $i < $ncol; $i++) {
            $cols[] = array('name' => $stmt->columnName($i),
                            'type' => $stmt->columnType($i));
        }
        return $cols;
   }
   
   public function insert($tbl,$arg)
   {
        $fld = $val = array();
        foreach ($arg as $k => $v) {
            $fld [] = $k;
            $val [] = '?';
            $arg2[] = $v;
        }
        $cmd = 'insert into '.$tbl.'('.implode(',', $fld).') values ('.implode(',', $val).')';
        $this->exec_cmd($cmd, $arg2);
        return $this->lastInsertId();
   }

    public function update($tbl, $arg, $cnd)
    {
        $fld = array();
        foreach ($arg as $k => $v) {
            $fld[] = "{$k} = ?";
            $val[] = $v;
        }
        if (!is_array($cnd)) { 
          $cnd = array('id'=>$cnd);
        }
        $whr = array();
        foreach ($cnd as $k=>$v) {
            $whr[] = "$k = ?";
            $val[] = $v;
        }
        $cmd .= 'update '.$tbl.' set '.implode(', ', $fld).' where '.implode(' and ', $whr);
        // mail('p.celeste@spinit.it','query',$cmd."\n".print_r($val,true));
        $this->exec_cmd($cmd,$val);
    }

    public function delete($tbl,$cnd)
    {
        $whr = array();
        if (!is_array($cnd)) {
            $cnd = array('id' => $cnd);
        }
        foreach ($cnd as $k => $v) {
            $whr[] = "{$k} = ?";
            $val[] = $v;
        }
        $cmd .= 'delete from '.$tbl.' where '.implode(' and ', $whr);
        $this->exec_cmd($cmd,$val);
    }
    
    public function par($p)
    {
        return key_exists($p,$this->__par) ? $this->__par[$p] : null;
    }

    //For compatibility
    public function cast($field, $type)
    {
        return $field;   
    }
}

<?php
/**
 * Oci wrap class
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

class DboOci
{
    private $__par = array();
    private $__cur = null;
    public  $backticks = '"';
    public  $cn = null;
    private $__transaction = false;
    //private $rs;

    public function __construct($str)
    {
        $par = explode(':',$str);
        $this->__par['typ'] = $par[0];
        $this->__par['hst'] = $par[1];
        $this->__par['db']  = $par[2];
        $this->__par['usr'] = $par[3];
        $this->__par['pwd'] = $par[4];
        $this->__par['query-parameter-dummy'] = 'pos';
    }
    
    public function begin()
    {
        $this->beginTransaction();
    }
    
    public function beginTransaction()
    {
        $this->__transaction = true;
    }

    public function column_count()
    {
       return $this->__cur->columnCount();
    }

    public function commit()
    {
        oci_commit($this->cn );
    }

    public function rollback()
    {
        oci_rollback($this->cn );
    }

    public function connect()
    {
        $this->cn = oci_connect($this->__par['usr'],
                                $this->__par['pwd'],
                                "{$this->__par['hst']}/{$this->__par['db']}");
        if (!$this->cn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } else {
            $this->exec_cmd("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
        }
    }

    function get_type()
    {
       return 'oracle';
    }

    //Metodo che setta il parametri della connessione
    function set_par($p,$v)
    {
      $this->__par[$p] = $v;
    }

    //Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
    public function last_id($arg)
    {
        foreach ($arg as $k => $v) {
            if (strpos('KEY_',$k) !== false) {
                return $v;
            }
        }
    }

    public function exec_multi($cmd, $par)
    {
        $this->beginTransaction();
        $s = $this->prepare($cmd);
        foreach ($par as $rec) {
            try {
                $s->execute($rec);
            } catch (Exception $e){
                echo $e;
                var_dump($rec);
                return;
            }
        }
        $this->commit();
    }

    public function exec_cmd($cmd, $par = null, $rs_return = true)
    {
        $rs = oci_parse($this->cn, $cmd);
        if (!$rs) {
            $e = oci_error($this->cn);  // For oci_parse errors pass the connection handle
            throw new Exception($e['message']);
            return;
        }
        if (!empty($par)) {
            foreach ($par as $k => $v) {
                $$k = $v;
                // oci_bind_by_name($rs, $k, $v) does not work
                // because it binds each placeholder to the same location: $v
                // instead use the actual location of the data: $$k
                $l = strlen($v) > 255 ? strlen($v) : 255;
                oci_bind_by_name($rs, ':'.$k, $$k, $l);
            }
        }
        
        $ok = $this->__transaction ? @oci_execute($rs, OCI_NO_AUTO_COMMIT) : @oci_execute($rs);
        //echo $cmd;
        if (!$ok) {
            $e = oci_error($rs);  // For oci_parse errors pass the connection handle
            throw new \Exception($e['message']);
            return;
        } elseif ($rs_return) {
            return $rs;
        } else {
            foreach ($par as $k=>$v) {
                $par[$k] = $$k;
            }
            oci_free_statement($rs);
            return $par;
        }
    }

    public function exec_query($sql, $par = null, $mth = null)
    {
        if (!empty($this->__cur)) {
            oci_free_statement($this->__cur);
        }
        $this->__cur = $this->exec_cmd($sql,$par);
        switch ($mth) {
            case 'BOTH':
                $mth = OCI_BOTH;
                break;
            case 'NUM':
                $mth = OCI_NUM;
                break;
            default:
                $mth = OCI_ASSOC;
                break;
        }
        oci_fetch_all($this->__cur,$res,null,null,OCI_FETCHSTATEMENT_BY_ROW|OCI_RETURN_NULLS|OCI_RETURN_LOBS|$mth);
        //oci_free_statement($this->__cur);
        return $res;
    }

    public function query($sql)
    {
        return $this->exec_cmd($sql);
    }

    public function fetch_all2($rs)
    {
        oci_fetch_all($rs,$res,null,null,OCI_FETCHSTATEMENT_BY_ROW|OCI_ASSOC|OCI_RETURN_NULLS|OCI_RETURN_LOBS);
        return $res;
    }

    public function fetch_all($rs)
    {
        $res = array();
        while ($rec = oci_fetch_array($rs, OCI_ASSOC|OCI_RETURN_NULLS)) {
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
        $ncol = oci_num_fields($stmt);
        for ($i = 1; $i <= $ncol; $i++) {
            $cols[] = array('native_type' => oci_field_type($stmt,$i),
                            'flags' => array(),
                            'name' => oci_field_name($stmt,$i),
                            'len' => oci_field_size($stmt,$i),
                            'pdo_type' => oci_field_type_raw($stmt,$i));
        }
        return $cols;
    }

    public function insert($tbl,$arg,$keys=array())
    {
        $cmd = 'INSERT INTO '.$tbl.'('.implode(',', array_keys($arg)).') VALUES (:'.implode(',:',array_keys($arg)).')';
        if (is_array($keys) && !empty($keys)) {
            $cmd .= ' RETURNING '.implode(',',array_keys($keys)).' INTO :KEY_'.implode(',:KEY_',array_keys($keys));
            foreach ($keys as $k => $v) {
                $arg['KEY_'.$k] = null;
            }
        }
        
        $arg = $this->exec_cmd($cmd,$arg,false);
        $res = array();
        foreach ($arg as $k => $v) {
            if (strpos($k,'KEY_') !== false) {
                $res[str_replace('KEY_','',$k)] = $v;
            }
        }
        return $res;
    }

    public function update($tbl,$arg,$cnd)
    {
        $fld = array();
        $val = array();
        $i = 0;
        foreach ($arg as $k => $v) {
            $fld[] = "{$k} = :{$k}";
            $i++;
        }
        if (!is_array($cnd)) { 
          $cnd = array('id'=>$cnd);
        }
        $whr = array();
        foreach ($cnd as $k=>$v) {
            $whr[] = "$k = :WHERE_{$k}";
            $arg['WHERE_'.$k] = $v;
            $i++;
        }
        $cmd .= 'update '.$tbl.' set '.implode(', ',$fld).' where '.implode(' and ',$whr);
        return $this->exec_cmd($cmd,$arg);
    }

    public function delete($tbl,$par)
    {
        $whr = array();
        if (!is_array($par)){ 
            $par = array('id'=>$cnd);
        }
        foreach($par as $k=>$v){
            $whr[] = "{$k} = :{$k}";
        }
        $cmd = 'delete from '.$tbl.' where '.implode(' and ',$whr);
        $this->exec_cmd($cmd,$par);
    }

    public function par($p)
    {
        return key_exists($p,$this->__par) ? $this->__par[$p] : null;
    }

    public function cast($field,$type)
    {
        $cast = $field;
        switch ($this->get_type()) {
            case 'pgsql':
                         $cast .= '::'.$type;
                         break;
        }
        return $cast;
    }

    public function free_rs($rs)
    {
        oci_free_statement($rs);
    }

    public function close()
    {
        oci_close($this->cn);
    }
/*End class*/
}

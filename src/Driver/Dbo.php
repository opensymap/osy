<?php
/**
 * Pdo wrap class
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

class Dbo extends \PDO 
{
    private $param = array();
    private $cursor = null;
    public $backticks = '"';
    
    /**
     * Constuctor
     *
     * @param string $connectionString  Contains parameter for connection
     *
     * @return void
     */
    public function __construct($connectionString)
    {
        $par = explode(':', $connectionString);
        switch ($par[0]) {
            case 'sqlite':
                $this->param['typ'] = $par[0];
                $this->param['db']  = $par[1];
                break;
            case 'mysql':
                $this->backticks = '`';
                //No break
            default:
                $this->param['typ'] = $par[0];
                $this->param['hst'] = $par[1];
                $this->param['db']  = $par[2];
                $this->param['usr'] = $par[3];
                $this->param['pwd'] = $par[4];
                $this->param['query-parameter-dummy'] = '?';
                break;
        }
    }
    
    /**
     * Start a transaction
     *
     * @return void
     */
    public function begin()
    {
        $this->beginTransaction();
    }
    
    /**
     * Return columns number of current query
     *
     * @return void
     */
    public function column_count()
    {
        return $this->cursor->columnCount();
    }
    
    /**
     * Exec connection to database
     *
     * @return void
     */
    public function connect()
    {
        switch ($this->param['typ']) {
            case 'sqlite':
                parent::__construct("{$this->param['typ']}:{$this->param['db']}");
                break;
            default:
                try {
                    $cnstr = "{$this->param['typ']}:host={$this->param['hst']};dbname={$this->param['db']}";
                    //var_dump($cnstr);
                    parent::__construct($cnstr, $this->param['usr'], $this->param['pwd']);
                } catch (Exception $e) {
                    die($cnstr.' '.$e);
                }
                break;
        }
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Return type of database
     *
     * @return string
     */
    public function get_type()
    {
        return $this->param['typ'];
    }

    /**
     * Set parameter of connection
     *
     * @param string $p Parameter name
     * @param string $v Parameter value
     *
     * @return void
     */
    public function set_par($p, $v)
    {
        $this->param[$p] = $v;
    }

    /**
     * Return last value generated by autoincrement
     *
     * @return mixed
     */
    public function last_id()
    {
        return $this->lastInsertId();
    }
    
    /**
     * Exec sql command (UPDATE, INSERT, DELETE)
     *
     * @param string $cmd Sql command
     * @param string $par Command parameters
     *
     * @return mixed
     */
    public function exec_cmd($cmd, $par = null)
    {
        if (!empty($par)) {
            $s = $this->prepare($cmd);
            return $s->execute($par);
        } else {
            return $this->exec($cmd);
        }
    }
    
    /**
     * Exec multiple sql command (UPDATE, INSERT, DELETE)
     *
     * @param string $cmd Sql command
     * @param string $par Command's parameter
     *
     * @return mixed
     */
    public function exec_multi($cmd, $par)
    {
        $this->beginTransaction();
        $s = $this->prepare($cmd);
        foreach ($par as $rec) {
            try {
                $s->execute($rec);
            } catch (Exception $e) {
                echo $cmd.' '.$e->getMessage().print_r($rec, true);
                $this->rollBack();
                return;
            }
        }
        $this->commit();
    }
    
    /**
     * Execute sql command (UPDATE, INSERT, DELETE) and return resultset in array
     *
     * @param string $cmd Sql command
     * @param array  $par Necessary parameters for execute sql command
     * @param string $mth Fetch style of result (ASSOC , NUMERIC, BOTH)
     *
     * @return array
     */
    public function exec_query($sql, $par = null, $mth = null)
    {
        $this->cursor = $this->prepare($sql);
        $this->cursor->execute($par);
        switch ($mth) {
            case 'NUM':
                $mth = \PDO::FETCH_NUM;
                break;
            case 'ASSOC':
                $mth = \PDO::FETCH_ASSOC;
                break;
            default:
                $mth = \PDO::FETCH_BOTH;
                break;
        }
        $res = $this->cursor->fetchAll($mth);
        return $res;
    }
    
    /**
     * Exec sql command (UPDATE, INSERT, DELETE) and return first row of result set
     *
     * @param string $cmd Sql query
     * @param string $par Necessary parameters for execute sql query
     * @param string $mth Fetch style of result (ASSOC , NUMERIC, BOTH)
     *
     * @return array
     */
    public function exec_unique($sql, $par = null, $mth = 'NUM')
    {
        $res = $this->exec_query($sql, $par, $mth);
        if (empty($res)) {
            return null;
        }
        return (count($res)== 1 && count($res[0])==1) ? $res[0][0] : $res[0];
    }
    
    /**
     * Return array result
     *
     * @param string $rs Resultset
     *
     * @return array
     */
    public function fetch_all($rs)
    {
        return $rs->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Return array of columns
     *
     * @param string $stmt Valid statement
     *
     * @return array
     */
    public function get_columns($stmt = null)
    {
        $stmt = is_null($stmt) ? $this->cursor : $stmt;
        $cols = array();
        $ncol = $stmt->columnCount();
        for ($i = 0; $i < $ncol; $i++) {
            $cols[] = $stmt->getColumnMeta($i);
        }
        return $cols;
    }

    /**
     * Execute insert on database
     *
     * @param string $tbl Table name
     * @param array  $arg Parameters of insert
     *
     * @return array
     */
    public function insert($tbl, $arg)
    {
        $fld = $val = array();
        foreach ($arg as $k => $v) {
            $fld [] = $k;
            $val [] = '?';
            $arg2[] = $v;
        }
        $cmd = 'insert into '.$tbl.'('.implode(',', $fld).') values ('.implode(',', $val).')';
        $this->exec_cmd($cmd, $arg2);
        return $this->last_id();
    }

    /**
     * Execute update on database
     *
     * @param string $tbl Table name
     * @param array  $arg Parameters of update
     * @param array  $cnd Parameters of where
     *
     * @return boolean
     */
    public function update($tbl, $arg, $cnd)
    {
        $fld = array();
        foreach ($arg as $k => $v) {
            $fld[] = "{$k} = ?";
            $val[] = $v;
        }
        if (!is_array($cnd)) {
            $cnd = array('id' => $cnd);
        }
        $whr = array();
        foreach ($cnd as $k => $v) {
            $whr[] = "$k = ?";
            $val[] = $v;
        }
        $cmd .= 'update '.$tbl.' set '.implode(', ', $fld).' where '.implode(' and ', $whr);
        return $this->exec_cmd($cmd, $val);
    }

    /**
     * Execute delete on database
     *
     * @param string $tbl Table name
     * @param array  $cnd Parameters of where
     *
     * @return boolean
     */
    public function delete($tbl, $cnd)
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
        $this->exec_cmd($cmd, $val);
    }
    
    /**
     * Get parameter value
     *
     * @param string $p Parameter name
     *
     * @return mixed
     */
    public function par($p)
    {
        return key_exists($p, $this->param) ? $this->param[$p] : null;
    }
    
    /**
     * Cast field if necessary
     *
     * @param string $field Field name
     * @param string $type  Type of field
     *
     * @return mixed
     */
    public function cast($field, $type)
    {
        $cast = $field;
        switch ($this->get_type()) {
            case 'pgsql':
                $cast .= '::'.$type;
                break;
        }
        return $cast;
    }

    /**
     * Free corrent recordset
     *
     * @param string $rs Recordset
     *
     * @return void
     */
    public function free_rs($rs)
    {
        unset($rs);
    }
   
    /**
     * Close connection (if possible)
     *
     * @return void
     */
    public function close()
    {
    }
}
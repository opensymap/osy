<?php
namespace Opensymap\Datasource;

use Opensymap\Driver\DboHelper;

class DatasourceDbo extends Datasource
{
    use DboHelper;
    
    private $error = array();
    private $query;
    private $queryParams;
    private $orderBy = 1;
    private $fetchMethod;
    private $rowForPage = 0;
    private $rowTotal = 0;
    private $where = array();
    private $page = array(
        'total'   => 0,
        'current' => 0,
        'command' => null
    );

    public function setQuery($query, $params=array(), $fetchMethod='ASSOC')
    {
        $this->query = $query;
        $this->queryParams = $params;
        $this->fetchMethod = $fetchMethod;
    }
    
    private function buildQuery()
    {
        $sql = trim($this->replacePlaceholder($this->query));
        $orderby = '';

        if (empty($sql)) {
            return;
        }
        //Check if the query is a select query
        if (stripos($sql,'select') !== false) {
             $sql = 'SELECT a.*
                    FROM (
                        '.$sql.'
                    ) a ';
            $this->orderBy = false;
        }
        if (!empty($this->where)) {
            foreach($this->where as $k => $filter) {
                $where .= (empty($where) ? ''  : ' AND ') . "a.{$filter[0]} {$filter[1]['opr']} '".str_replace("'","''",$filter[1]['val'])."'";
            }
            $sql .= " WHERE " .$where;
        }

        if (!empty($this->orderBy)) {
            $orderby = ' ORDER BY ' . str_replace(
                array('][','[',']'),
                array(',','',''),
                $this->orderBy
            );
        }
        
        if ($this->rowForPage > 0) {
            $sql = $this->buildQueryPaging($sql,$orderby);
        } else {
            if (strtolower(strtok($sql,' ')) == 'select') {
                $sql .= $orderby;
            }
        }
        //die ($sql);
        //var_dump($this->page['current'],$this->rowTotal);
        $this->query = $sql;
    }
    
    public function buildQueryPaging($sql,$orderby)
    {
        try {
            $this->rowTotal = $this->source->exec_unique(
                "SELECT COUNT(*) 
                FROM ({$sql}) a ".
                $where
            );
            //$this->att('data-row-num',$this->rowTotal);
        } catch(Exception $e) {
            $this->error[] = $sqlCount."\n".$e->getMessage();
            return;
        } finally {
            $sql .= ' '.$orderby;
        }
        
        $this->page['total'] = ceil($this->rowTotal / $this->rowForPage);
        
        if (empty($this->page['current']) || $this->page['current'] > $this->page['total']) {
            $this->page['current'] = $this->page['total'];
        } 
        
        switch ($this->page['command']) {
            case '<<':
            case 'first':
                $this->page['current'] = 1;
                break;
            case '<':
            case 'prev':
                if ($this->page['current'] > 1) {
                    $this->page['current']--;
                }
                break;
            case '>':
            case 'next':
                if ($this->page['current'] < $this->page['total']) {
                    $this->page['current']++;
                }
                break;
            case '>>':
            case 'last':
                $this->page['current'] = $this->page['total'];
                break;
        }
        
        $row_sta = max(($this->page['current'] - 1) * $this->rowForPage,0);
        
        switch ($this->source->get_type()) {
            case 'oracle':
                $row_sta = (($this->page['current'] - 1) * $this->rowForPage) + 1 ;
                $row_end = ($this->page['current'] * $this->rowForPage);
    
                $sql = 'SELECT a.*
                        FROM (
                            SELECT b.*,rownum as "_rnum"
                            FROM ( 
                                '.$sql.'
                            ) b
                        ) a 
                        WHERE "_rnum" BETWEEN '.$row_sta.' AND '.$row_end;
                break;
            case 'pgsql':
                $sql .= ' LIMIT '.$this->rowForPage.' OFFSET '.$row_sta;
                break;
            default:
                $sql .= ' LIMIT '.$row_sta.' , '.$this->rowForPage;
                break;
        }
    }
    
    public function getStatistics()
    {
        //Calcolo statistiche
        if ($sql_stat = $this->get_par('datasource-sql-stat')) {
            try {
                $sql_stat = $this->replacePlaceholder(str_replace('<[datasource-sql]>',$sql,$sql_stat).$whr);
                $stat = $this->source->exec_unique($sql_stat,null,'ASSOC');
                if (!is_array($stat)) $stat = array($stat);
                $dstat = tag::create('div')->att('class',"osy-datagrid-stat");
                $tr = $dstat->add(tag::create('table'))->att('align','right')->add(tag::create('tr'));
                foreach($stat as $k=>$v) {
                    $v = ($v > 1000) ? number_format($v,2,',','.') : $v;
                    $tr->add(new Tag('td'))->add('&nbsp;');
                    $tr->add(new Tag('td'))->att('title',$k)->add($k);
                    $tr->add(new Tag('td'))->add($v);
                }
                $this->__par['div-stat'] = $dstat;
            } catch(Exception $e) {
                $this->par('error-in-sql-stat','<pre>'.$sql_stat."\n".$e->getMessage().'</pre>');
            }
        }
    }
    
    public function fill()
    {
        $this->buildQuery();
        try {
            $rs = $this->source->query($this->query);
            $this->columns = $this->source->get_columns($rs);
            $this->recordsetRaw = $this->source->fetch_all($rs);
        } catch (PDOException $e) {
            $this->recordsetRaw = array(
                array($e->getMessage())
            );
        }
    }
    
    public function orderBy($val)
    {
        $this->orderBy = $val;
    }

    public function setPage($current, $command = null)
    {
        $this->page['current'] = $current;
        $this->page['command'] = $command;
     }

    public function setPageCommand($command)
    {
        $this->page['command'] = $command;
    }

    public function RowForPage($n)
    {
        $this->rowForPage = $n;
    }

    public function getTotalPage()
    {
        return $this->page['total'];
    }
}

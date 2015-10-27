<?php
namespace Opensymap\Datasource;

use Opensymap\Driver\DboHelper;

class DatasourceDbo extends Datasource
{
    use DboHelper;
    
    private $error = array();
    private $query;
    private $queryParams;
    private $orderBy = false;
    private $fetchMethod;
    private $rowForPage = 0;
    private $rowTotal = 0;
    private $where = array();
    private $page = array(
        'total'   => 0,
        'current' => 0,
        'command' => null
    );

    public function setQuery($query, array $params=array(), $fetchMethod='ASSOC')
    {
        $this->query = $query;
        $this->queryParams = $params;
        $this->fetchMethod = $fetchMethod;
        return $this;
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
        } else {
            $this->orderBy = false;
        }
        
        if (!empty($this->where)) {
            $i = 0;
            foreach($this->where as $k => $filter) {
                $placeholder = $this->source->get_type() == 'oracle' ? ':'.$i : '?';
                $where .= empty($where) ? ''  : ' AND ';
                //$where .= "a.{$filter[0]} {$filter[1]['opr']} '".str_replace("'","''",$filter[1]['val'])."'";
                $where.= "a.".$filter[0]." ".$filter[1]['opr']." ".$placeholder;
                $this->queryParams[] = $filter[1]['val'];
                $i++;
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
            $sql = $this->buildQueryPaging($sql, $orderby);
        } else {
            if (empty($this->orderBy) && strtolower(strtok($sql,' ')) == 'select') {
                $sql .= $orderby;
            }
        }
        $this->query = $sql;
    }
    
    public function buildQueryPaging($sql, $orderby)
    {
        try {
            $this->rowTotal = $this->source->exec_unique(
                "SELECT COUNT(*) 
                 FROM (
                    {$sql}
                ) a ",
                $this->queryParams
            );
        } catch(Exception $e) {
            $this->error[] = $sql."1\n".$e->getMessage();
            return;
        }
        
        $sql .= ' '.$orderby;
        
        //Compute total page (total rows / rows for page)
        $this->page['total'] = ceil($this->rowTotal / $this->rowForPage);
        
        if (
            empty($this->page['current']) || 
            $this->page['current'] > $this->page['total']
        ) {
            $this->page['current'] = $this->page['total'];
        } 
        
        //Check if user has send pagination command
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
    
                $sql = 'SELECT c.*
                        FROM (
                            SELECT b.*,rownum as "_rnum"
                            FROM ( 
                                '.$sql.'
                            ) b
                        ) c 
                        WHERE "_rnum" BETWEEN '.$row_sta.' AND '.$row_end;
                break;
            case 'pgsql':
                $sql .= ' LIMIT '.$this->rowForPage.' OFFSET '.$row_sta;
                break;
            default:
                $sql .= ' LIMIT '.$row_sta.' , '.$this->rowForPage;
                break;
        }
        
        return $sql;
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
            $this->recordsetRaw = $this->source->exec_query(
                $this->query,
                $this->queryParams,
                'ASSOC'
            );
            $this->columns = $this->source->get_columns();
        } catch (PDOException $e) {
            $this->recordsetRaw = array(
                array($this->query.'<br>1'.$e->getMessage())
            );
        }
    }
    
    public function addFilter($field, $keySearch, $operator = '=')
    {
        if (empty($field) || empty($operator)) {
            return false;
        } 
        $b = $this->source->backticks;
        $this->where[] = array(
            $b.$field.$b,
            array(
                'val'=>$keySearch,
                'opr'=>$operator
            )
        );
        return true;
    }
    
    public function orderBy($val)
    {
        $this->orderBy = empty($val) ? 1 : $val;
    }

    public function setPage($current, $command = null, $rowForPage = 10)
    {
        $this->page['current'] = $current;
        $this->page['command'] = $command;
        $this->rowForPage  = $rowForPage;
    }

    public function getPage($key = 'current')
    {
        return $this->page[$key];
    }
}

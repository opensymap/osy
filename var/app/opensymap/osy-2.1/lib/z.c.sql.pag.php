<?
/*
 +-----------------------------------------------------------------------+
 | lib/c.sql.pag.php                                                     |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description :                                                         |
 |-----------------------------------------------------------------------|
 | Creation date : 2003-07-20                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/
class Paging
{
  private $Head;
  private $Rows;
  private $Properties;
  private $OrderByNum = 2;

  public function __construct($btn,$pag,$record_for_page=35){
     $this->Query = new stdClass();
     $this->Pages = new stdClass();
     $this->Records = new stdClass();
     $this->Head    = Array();
     $this->Rows    = Array();
     $this->Pages->Current = $pag;
     $this->Records->ForPage = $record_for_page;
     $this->PressedButton = $btn;
  }
  
  public function __get($p){
      return $this->Properties[$p];
  }
  
  public function __set($p,$v){
      $this->Properties[$p] = $v;
  }
  
  public function AddQueryPart($sec,$val,$fld=Null,$opr=' = '){
    /*
     * Se sono settati il valore fld (campo) e il valore opr (operatore)
     * aggiungo la condizione come parte della query
     */
    if (!empty($fld) && !empty($opr)){
        if (!empty($val)){
            switch(strtoupper($opr)){
               case 'LIKE' : 
                             $val = " `{$fld}` LIKE '%$val%'";
                             break;
               default     :
                             $val = " `{$fld}` {$opr} '{$val}'";
                             break;
            }
        } else {
          $val = " `{$fld}` IS NULL ";
        }
    }
    if (!empty($val)){ 
        $this->Query->Part[$sec][] = $val;
    }
  }

  private function BuildQuery(){
      if (is_array($this->Query->Part['WHERE'])){
          $this->Query->SqlCount = "SELECT Count(*)
                                    FROM ({$this->Query->Sql}) a
                                    WHERE ".implode(' AND ',$this->Query->Part['WHERE']);
          
          $this->Query->Sql = "SELECT a.*
                               FROM ({$this->Query->Sql}) a
                               WHERE ".implode(' AND ',$this->Query->Part['WHERE']);
      }

      if (is_array($this->Query->Part['ORDER BY'])){
        $this->Query->Sql .= ' ORDER BY '.implode(',',$this->Query->Part['ORDER BY'])."\n";
      } else {
        $this->Query->Sql .= " ORDER BY {$this->OrderByNum} \n";
      }
      //echo '<!--'.$this->Query->Sql.'-->';
  }
  
  private function BuildQueryPaging(){
      //Tramite la query di count mi recupero il numero di record che la query restituisce.
      try{
        $this->Records->Total = env::$dba->exec_unique($this->Query->SqlCount);
      } catch (Exception $e){
        $this->SetError('[ERRORE]',"La query:\n\n {$this->__QueryCount}\n\nha generato il seguente errore:\n\n{$e}");
      }
      $this->Pages->Total   = ceil($this->Records->Total / $this->Records->ForPage);
      $this->Pages->Current = (empty($this->Pages->Current) || $this->Pages->Current > $this->Pages->Total) ? $this->Pages->Total : $this->Pages->Current;

      switch(trim($this->PressedButton)){
		case ">>" : $this->Pages->Current = $this->Pages->Total; 
                    break;
		case "<<" : $this->Pages->Current = 1; 
                    
                    break;
		case ">"  : $this->Pages->Current = ($this->Pages->Current < $this->Pages->Total) ? $this->Pages->Current + 1 : $this->Pages->Total; 
                    break;
		case "<"  : $this->Pages->Current = ($this->Pages->Current > 1) ? $this->Pages->Current - 1 : 1; 
                    break;
	  }
      $this->Records->From = ($this->Pages->Current-1) * $this->Records->ForPage;
      $this->Records->From = $this->Records->From < 1 ? 0 : $this->Records->From;
      $this->Records->To   = $this->Records->From + ($this->Records->ForPage - 1);
       switch(Env::$dba->get_type())
       {
                case 'ORACLE':
                              $this->Query->Sql = "SELECT l2.*
                                                   FROM (
                                                           SELECT l1.*,rownum as \"_rownumber\"
                                                           FROM (
                                                                   {$this->Query->Sql}
                                                                ) l1
                                                         ) l2 
                                                   WHERE l2.\"_rownumber\" BETWEEN {$this->Records->From} AND {$this->Records->To}";
                               break;
                default:
                               $this->Query->Sql = "SELECT a.*
                                                    FROM (
                                                            {$this->Query->Sql}
                                                         ) a LIMIT {$this->Records->From},{$this->Records->ForPage}";
                               break;
       }
      //die("<pre>{$this->Query->Sql}</pre>");
      //echo "<pre>{$this->Query->Sql}</pre>";
  }

  public function ExecQuery(){
    $this->SetFormPkField();
    $this->BuildQuery();
    $this->BuildQueryPaging();
    try
    {
        $rs = Env::$dba->exec_query($this->Query->Sql,null,'ASSOC');
        $cols = env::$dba->get_columns();
        //Carico l'intestazione delle colonne
        foreach ($cols as $col)
        {
            if (in_array($col['name'],$this->Query->PkField))
            {
                $this->Head[0][$col['name']] = $col;
            } 
              else 
            {
                $this->Head[] = $col;
            }
        }
        //Carico le righe.
        $j = 0;
        foreach ($rs as $rec)
        {
              foreach($rec as $k => $v)
              {
                  if (in_array($k,$this->Query->PkField))
                  {
                      $this->Rows[$j][0][$k] = $v;
                  } 
                    else 
                  {
                      $this->Rows[$j][$k] = $v;
                  }
               }
               $j++;
    	}
        //Eliminio il recordset.
    } 
      catch(Exception $e)
    {
        $this->SetError('ERRORE',$e);
    }
    
   // echo $this->_query;
  }

  public function GetHead(){
    return $this->Head;
  }

  public function GetRows(){
    return $this->Rows;
  }
  
  public function GetPagCur(){
  	return $this->Pages->Current;
  }
  
  public function GetPagMax(){
  	return $this->Pages->Total;
  }
  
  private function SetError($t,$e)
  {
        $this->Head[1] = array();
        $this->Head[1]['name'] = $t;
        $this->Rows[0] = array('',"<pre style=\"font-size: 12px;\">$e</pre>");
  }
  
  private function SetFormPkField(){
        $this->Query->PkField = Array();
        if (!empty($this->Form)) return;
        $rs = env::$dbo->exec_query("SELECT distinct fld_lnk_db 
                                      FROM   osy_app_frm_fld 
                                      WHERE  app_id = ? AND
                                             frm_id = ? AND
                                             fld_lnk_db_is_pk = 1",array($this->Form['app_id'],$this->Form['frm_id']),'NUM');
        $this->OrderByNum=1;
        foreach ($rs as $rec)
        {
               $this->Query->PkField[] = $rec[0];
               $this->OrderByNum++;
        }
  }
  
  public function SetFormLinked($a,$f){
    $this->Form = array('app_id' => $a,'frm_id' => $f);
  }
  
  public function SetQuery($q){
    $this->Query->Sql      = $this->Query->SqlOriginal = env::ReplaceVariable($q);
    $this->Query->SqlCount = "SELECT COUNT(*) FROM ({$this->Query->Sql}) a";
  }
  
  public function SetQueryCount($q){
      if (!empty($q)){
        $this->Query->SqlCount = $q;
      }
  }
}
?>



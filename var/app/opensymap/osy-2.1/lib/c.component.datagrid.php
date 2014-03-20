<?
/*
 +-----------------------------------------------------------------------+
 | lib/osy/2.c.cmp.dat.grd.php                                           |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create page form for generate datagrid and treegrid                 |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   28/08/2013
 * @date-update     28/08/2013
 */
 
class data_grid extends component
{
    private $__att = array();
    private $__cmd = array();
    private $__col = array();
    private $__dat = array();
    private $__grp = array(); //array contenente i dati raggruppati
    private $__sta = array();
    private $__db  = null;
    
    public function __construct($name,$db=null)
    {
        parent::__construct('div',$name);
        $this->__db = (empty($db) ? env::$dba : $db);
        $this->att('class','datagrid-2');
        $this->__par['type'] = 'datagrid';
    	$this->__par['row-num'] = 0;
	    $this->__par['pkey'] = array();
    	$this->__par['max_wdt_per'] = 96;
	    $this->__par['sql_filter'] = array();
    	$this->__par['paging'] = true;
        $this->__par['REL_FRM_ID'] = false;
        $this->__par['REL_APP_ID'] = false;
        $this->__par['error-in-sql'] = false;
        $this->__par['record-add'] = null;
        $this->__par['record-update'] = null;
        $this->__par['sql_filter'] = array();
    	$this->__sta['col_len'] = array();
        $this->man('oninsert','frm_rel',function($val)
        {
            $this->par('REL_FRM_ID',$val);
            $this->par('REL_APP_ID',$_REQUEST['AppID']);
        });
        //Add javascript manager;
        osy_view::$page->add_script('../js/osy.datagrid.js');
    }
    
    protected function __build_extra__()
    {
        if ($this->rows) $this->__par['row-num'] = $this->rows;
        if ($this->get_par('form-related')) $this->__set_ext_form__();
        if ($this->get_par('datasource-sql')) $this->__data_load__();
        
        //Aggiungo il campo che conterrà i rami aperti dell'albero.
    	$this->add(new hidden_box($this->id.'_open'))->att('class','req-reinit');
        
        //Aggiungo il campo che conterrà il ramo selezionato.
        $this->add(new hidden_box($this->id,$this->id.'_sel'))->att('class','req-reinit');
	    $tbl_cnt = $this->add(tag::create('div'))
                        ->att('id',$this->id.'-body')
                        ->att('class','datagrid-2-body'); 
        $hgt = $this->get_par('cell-height');
	    if (!empty($this->__par['row-num']) && !empty($hgt))
        {
            $hgt = str_replace('px','', $hgt);
            $tbl_cnt->att('style','height : '. ($hgt-30) . 'px',true);
        } elseif(!empty($hgt)) {
            $hgt = str_replace('px','', $hgt);
            $tbl_cnt->att('style','height : '. $hgt . 'px',true);
        }
        $tbl = $tbl_cnt->add(tag::create('table'));
        if ($err = $this->get_par('error-in-sql'))
        {
            $tbl->add(tag::create('tr'))->add(tag::create('td'))->add($err);
            return;
        }
        if (is_array($this->get_par('cols')))
        {
          	$tbl_hd = $tbl->add(tag::create('thead'));
            $this->__build_head__($tbl_hd);
        }
		if (is_array($this->__dat) && !empty($this->__dat))
		{
			$tbl_bod = $tbl->add(tag::create('tbody'));
			$lev = ($this->get_par('type') == 'datagrid') ? null : 0;
			$this->__build_body__($tbl_bod,$this->__dat,$lev);
		} 
		 else 
		{
            $tbl->add(tag::create('td'))->att('class','no-data')->att('colspan',$this->__par['cols_vis'])->add('Nessun dato presente');
        }
		$t = array_sum($this->__sta['col_len']);
		foreach ($this->__sta['col_len'] as $k => $l)
		{
			$p = ($this->__par['max_wdt_per'] * $l) / max($t,1);
			//$tbl_hd->Child(0)->Child($k)->style = "width: ".round($p)."%";
		}
        //Setto il tipo di componente come classe css in modo da poterlo testare via js.
        $this->att('class',$this->get_par('type'),true);
		$this->__build_paging__();
		//$this->add($html);
    }
   
   private function __build_body__($container,$data,$lev,$ico_arr=null)
   {
   		if (!is_array($data)) return;
		$i = 0;
		$l = count($data);
        $ico_tre = null;
		foreach($data as $k => $row)
		{
			if (!is_null($lev))
			{
				if (($i+1) == $l) 
				{
					$ico_tre = 3;
					$ico_arr[$lev] = null;
				}
				 elseif(empty($i))
				{
					$ico_tre = empty($lev) ? 1 : 2;
					$ico_arr[$lev] = (($i+1) != $l) ? '4' : null;
				}
				 else
				{
					$ico_tre = 2;
					$ico_arr[$lev] = (($i+1) != $l) ? '4' : null;
				}
			}
			$this->__build_row($container,$row,$lev,$ico_tre,$ico_arr);
			if ($this->get_par('type') == 'treegrid')
			{
                @list($item_id,$group_id) = explode(',',$row['_tree']);
				$this->__build_body__($container,@$this->__grp[$item_id],$lev+1,$ico_arr);
			}
			$i++;
		}
   }
   
   private function __build_row(&$grd,$row,$lev=null,$pos=null,$ico_arr=null)
   {
		$t = $i = 0;
		$orw = tag::create('tr');
		$pk = $tree_id = null;
        $prefix = array();
		foreach ($row as $k => $v)
		{
        	$print_col = true;
            $k = str_replace('pk_','',$k);

			if (key_exists('pkey',$this->__par) && is_array($this->__par['pkey']) && in_array($k,$this->__par['pkey']))
			{
				if (!empty($v))
				{
					$pk = $v;
					$orw->att('__k',"pkey[$k]=$v",'&');
                    if (!$orw->oid) $orw->att('oid',$v);
				}
				 else
				{
				   $orw->__k = str_replace('pkey','fkey',$orw->__k);
				}
				$t++;
				continue;
			}
			$cel = tag::create('td');
			if ($k[0] == '_')
			{
				$print_col = false;
				@list($cmd,$nam,$par) = explode(',',$k);
				switch($cmd)
				{
  					case '_button' :
            						list($v,$sel) = explode('[,]',$v);
                                    if (!empty($v))
                                    {
                                        $v = "<input type=\"button\" name=\"btn_row\" class=\"$v\" value=\"$v\">";
                                        $cel->att('class','center');
                                    }
                                     else
                                    {
                                        $v = '&nbsp;';
                                    }
									$print_col = true;
									break;
					case '_chk'	  :
            						list($v,$sel) = explode('[,]',$v);
                                    $v = "<input type=\"checkbox\" name=\"chk_{$this->id}[]\" value=\"$pk\"".(empty($sel) ? '' : ' checked').">".$v;
									$print_col = true;
									break;
					case '_tree' :
                                    //Il primo elemento deve essere l'id dell'item il secondo l'id del gruppo di appartenenza
								    @list($tree_id,$tree_group) = explode(',',$v);
                                   // var_dump($v);
                                	$orw->att('oid',base64_encode($tree_id))->att('gid',base64_encode($tree_group));
									if (array_key_exists($this->id,$_REQUEST) && $_REQUEST[$this->id] == '['.$tree_id.']')
									{
										$orw->att('class','sel',true);
									}
                                    if (empty($pk)) $pk = $tree_id;
                                    if (!is_null($lev))
			                        {
                        				$ico = '';
                        				for($ii = 0; $ii < $lev; $ii++)
                        				{
                        					$cls  = empty($ico_arr[$ii]) ? 'tree-null' : ' tree-con-'.$ico_arr[$ii];
                        					$ico .= '<span class="tree '.$cls.'">&nbsp;</span>';
                        				}
                        				$ico .= array_key_exists($tree_id,$this->__grp) 
                        				       ? '<span class="tree tree-plus-'.$pos.'">&nbsp;</span>' 
                        					   : '<span class="tree tree-con-'.$pos.'">&nbsp;</span>';
                        				$prefix[] = $ico;
                        				if (!empty($lev)){	$orw->att('class','hide',true);	}
			                        }
									break;
                   case '_html' :
                                    $print_col = true;
                                    break;
                   case '_form' :
                                    $orw->att('__f',base64_encode($v))->att('class','__f',true);
                                    break;
                   case '_ico'  :
                                    $prefix[] = "<img src=\"$v\" class=\"osy-treegrid-ico\">";
                                    break;
                   case '_pk'   :   
                                    $orw->att('_pk',$v);
                                    break;
				}
			}
             else
            {
                switch($k['0'])
                {
                    case '$':
                              $v = is_numeric($v) ? number_format($v,2,',','.') : $v;
                              $cel->att('class','right');
                              break;
                }
                $v = htmlentities($v);
            }
            switch ($this->__par['cols'][$t]['pdo_type'])
			{
				case 12	:
							if (!empty($v))
                            {
                                list($date,$hour) = explode(' ',$v);
    							list($y,$m,$d) = explode('-',$date);
	    						$v = "$d/$m/$y ".$hour;
		    					$cel->att('class','center',true);
                        	}
							break;
			}
            $t++; //Incremento l'indice generale della colonna
			if (!$print_col) { continue; } 
			//Formatto tipi di dati particolari 
            if (!empty($prefix))
            {
                $cel->add2($prefix);
                $prefix = array();
            }
			
			if (!empty($this->__col[$i]) && is_array($this->__col[$i]))
			{
				$this->__build_attr($cel,$this->__col[$i]);
			}
            if (empty($i) && $this->get_par('record-add'))
            {
                $cel->att('colspan',2);
            }
			if (array_key_exists($i,$this->__sta['col_len']))
			{
				$this->__sta['col_len'][$i] = max(strlen($v),$this->__sta['col_len'][$i]);
			} else {
			    $this->__sta['col_len'][$i] = strlen($v);
			}
			/*if (!is_null($lev) && empty($i))
			{
				$ico = '';
				for($ii = 0; $ii < $lev; $ii++)
				{
					$cls  = empty($ico_arr[$ii]) ? 'tree-null' : ' tree-con-'.$ico_arr[$ii];
					$ico .= '<span class="tree '.$cls.'">&nbsp;</span>';
				}
				$ico .= array_key_exists($tree_id,$this->__grp) 
				       ? '<span class="tree tree-plus-'.$pos.'">&nbsp;</span>' 
					   : '<span class="tree tree-con-'.$pos.'">&nbsp;</span>';
				$v = $ico.$v;
				if (!empty($lev))
				{
					$orw->att('class','hide',true);
				}
			}*/
			$cel->add(($v !== '0' and empty($v)) ? '&nbsp;' : nl2br($v));
            $orw->add($cel);
			$i++;//Incremento l'indice delle colonne visibili
		}
         //exit;
        /*if ($this->get_par('record-add'))
        {
            $orw->add(tag::create('td'))->add('&nbsp;');
        }*/
        $grd->add($orw.'');
   }
   
   private function __build_head__($thead)
   {
        foreach($this->get_par('cols') as $k => $col)
        {
            if (is_array($this->get_par('pkey')))
            {
                if (in_array(str_replace('pk_','',$col['name']),$this->get_par('pkey')))
                {
                    continue;
                }
            }
            $print = true;
            $title = $col['name'];
            switch($title[0])
            {
                case '_': 
                            $print = false;
                            @list($cmd,$nam,$par) = explode(',',$title);
                            switch($cmd)
                            {
                                case '_tree':
                                                $this->__data_group__();
                                                break;
                                case '_button':
                                case '_chk'   :
                                case '_html'  :
                                case '_text'  :
                                                $title = $nam;
                                                $print = true;
                                                break;
                                case '_pk'  :
                								$this->par('pkey',$k);
                                                break;
                            }
                            break;
               case '$':
                          $title = str_replace(array('$','€','#'),array('','',''),$title);
                          break;
            }
            if ($print)
            {
                if (empty($this->__par['cols_vis']) && !empty($this->__par['record-add']))
                {
                    $thead->add(tag::create('th'))->att('class','add-cnt')->add('<span class="cmd-add">+</a>');
                    $this->__par['cols_vis'] += 1;
                }
                $this->__par['cols_vis'] += 1;
                $cel = $thead->add(tag::create('th'))->att('real_name',$col['name']);
                $cel->add($title);
            }
        }
        
   }
   
   private function __build_paging__()
   {
   		if (empty($this->__par['row-num'])) return '';
		$fot = '<div class="datagrid-2-foot">';
		$fot .= '<input type="button" name="btn_pag" value="&lt;&lt;" class="osy-datagrid-2-paging">';
		$fot .= '<input type="button" name="btn_pag" value="&lt;" class="osy-datagrid-2-paging">';
		$fot .= '<span>&nbsp;<input type="hidden" name="'.$this->id.'_pag" value="'.$this->__par['pag_cur'].'" class="req-reinit"> Pagina '.$this->__par['pag_cur'].' di <span id="_pag_tot">'.$this->__par['pag_tot'].'</span>&nbsp;</span>';
		$fot .= '<input type="button" name="btn_pag" value="&gt;" class="osy-datagrid-2-paging">';
		$fot .= '<input type="button" name="btn_pag" value="&gt;&gt;" class="osy-datagrid-2-paging">';
		$fot .= '</div>';
    	$this->add($fot);
   }
   
   private function __data_load__()
   {
        $sql = env::ReplaceVariable($this->get_par('datasource-sql'));
        $sql = env::parse_string($sql);
        //echo $sql;
        if (empty($sql)) { return; }
		$whr = '';
		
		if (!empty($this->__par['sql_filter']))
		{
			foreach($this->__par['sql_filter'] as $k => $flt)
			{
				$whr .= (empty($whr) ? ''  : ' AND ') . "a.{$flt[0]} {$flt[1]['opr']} '{$flt[1]['val']}'";
			}
			$whr = " WHERE " .$whr;
		}
        try 
        {
            $this->__par['rec_num'] = $this->__db->exec_unique("SELECT COUNT(*) FROM ({$sql}) a ".$whr);
        } 
          catch(Exception $e) 
        {
            $this->par('error-in-sql','<pre>'.print_r($e,true).'</pre>');
            return;
        }
		if ($this->__par['row-num'] > 0)
		{
			$this->__par['pag_tot'] = ceil($this->__par['rec_num'] / $this->__par['row-num']);
			$this->__par['pag_cur'] = !empty($_REQUEST[$this->id.'_pag']) ? min($_REQUEST[$this->id.'_pag'],$this->__par['pag_tot']) : $this->__par['pag_tot'];
			if (!empty($_REQUEST['btn_pag']))
			{
    			switch($_REQUEST['btn_pag'])
    			{
    				case '<<' :
    							$this->__par['pag_cur'] = 1;
    							break;
    				case '<'  :
    							if ($this->__par['pag_cur'] > 1){
    								$this->__par['pag_cur']--;
    							}
    							break;
    				case '>'  :
    							if ($this->__par['pag_cur'] < $this->__par['pag_tot']){
    								$this->__par['pag_cur']++;
    							}
    							break;
    				case '>>' :
    							$this->__par['pag_cur'] = $this->__par['pag_tot'];
    							break;
    			}
			}
		}
		switch($this->__db->get_type())
		{
			case 'ORACLE':
							$this->__par['sql'] = "SELECT f.*
							                       FROM (
												         SELECT a.*,rownum as \"_rnum\"
														  FROM (
																 SELECT a.*
																 FROM ({$this->__opt['sql_raw']}) a
																
																) a
														) f ";
							if (!empty($this->__par['row-num']) && array_key_exists('pag_cur',$this->__par))
							{
								$row_sta = (($this->__par['pag_cur'] - 1) * $this->__par['row-num']) + 1 ;
								$row_end = ($this->__par['pag_cur'] * $this->__par['row-num']);
								$this->__par['sql'] .= "WHERE \"_rnum\" BETWEEN $row_sta AND $row_end";
							}
							if (!empty($_REQUEST['hdn_ord_by']))
							{
								if (strpos($_REQUEST['hdn_ord_by'],' DESC') > -1)
								{
									$ord = '"'.str_replace('DESC','',$_REQUEST['hdn_ord_by'])."' DESC";
								} else {
									$ord = '"'.$_REQUEST['hdn_ord_by']."'";
								}
								$this->__par['sql'] .= 'ORDER BY '.$_REQUEST['hdn_ord_by'];
							}
							break;
			default :
							$sql = "SELECT a.* FROM ({$sql}) a {$whr} ";
                            //echo $sql;
							if (!empty($_REQUEST['hdn_ord_by']))
							{
								if (strpos($_REQUEST['hdn_ord_by'],' DESC') > -1)
								{
									$ord = '"'.str_replace('DESC','',$_REQUEST['hdn_ord_by'])."' DESC";
								} else {
									$ord = '"'.$_REQUEST['hdn_ord_by']."'";
								}
								$sql .= ' ORDER BY '.$_REQUEST['hdn_ord_by'];
							}
							if (!empty($this->__par['row-num']) && array_key_exists('pag_cur',$this->__par))
							{
								$row_sta = (($this->__par['pag_cur'] - 1) * $this->__par['row-num']);
                                $row_sta =  $row_sta < 0 ? 0 : $row_sta;
                                $sql .= ($this->__db->get_type() == 'pgsql') 
                                       ? "\nLIMIT $row_sta OFFSET ".$this->get_par('row-num')
                                       : "\nLIMIT $row_sta , ".$this->get_par('row-num');
							}
						break;
		}
        //Eseguo la query
       
        $rs = $this->__db->query($sql);

        //Salvo le colonne in un option
        $this->__par['cols'] = $this->__db->get_columns($rs);
        $this->__par['cols_tot'] = count($this->__par['cols']);
        $this->__par['cols_vis'] = 0;
        if (is_array($this->__par['cols']))
        {
            $this->__par['cols_tot'] = count($this->__par['cols']);
        }
        //Scorro il recordset
        /*while($rec = $rs->fetch(PDO::FETCH_ASSOC))
        {
		    $this->__dat[] = $rec;
        }*/
        $this->__dat = $rs->fetchAll(PDO::FETCH_ASSOC);

        //Libero memoria annullando il recordset
        unset($rs);
   }

   private function __data_group__()
   {
		$this->par('type','treegrid');
        $dat = [];
		foreach ($this->__dat as $k => $v)
		{
            @list($oid,$gid) = explode(',',$v['_tree']);
			if (!empty($gid))
			{
				$this->__grp[$gid][] = $v;
			} 
			 else 
			{
				$dat[] = $v;
			}
		}
        //array_multisort($this->__grp[$gid]);
		$this->__dat = $dat;
        //var_dump($this->__dat);
        //var_dump($this->__grp);
   }

   private function __set_ext_form__()
   {
        $add = $this->get_par('record-add');
        if (is_null($add)) $this->par('record-add',true);
        $this->par('record-update',true);
        /*echo sprintf("SELECT frm.o_id  AS form_id,
                                             fty.p1    AS form_man,
                                             dfld.p_vl AS field_pkey,
                                             hprp.p_vl AS height,
                                             wprp.p_vl AS width
                                      FROM  osy_obj frm
                                      INNER JOIN osy_obj      fld  ON (frm.o_id = fld.o_own)
                                      INNER JOIN osy_obj_prp  pfld ON (fld.o_id = pfld.o_id AND pfld.p_id = 'db-field-is-pkey')
                                      INNER JOIN  osy_obj_prp dfld ON (fld.o_id = dfld.o_id AND dfld.p_id = 'db-field-connected')
                                      LEFT JOIN  osy_obj_prp  hprp ON (frm.o_id = hprp.o_id AND hprp.p_id = 'height')
                                      LEFT JOIN  osy_obj_prp  wprp ON (frm.o_id = wprp.o_id AND wprp.p_id = 'width')
                                      LEFT JOIN  osy_res      fty  ON (frm.o_sty = fty.v_id AND fty.k_id = 'osy-object-subtype')
                                      WHERE frm.o_id = '%s' AND pfld.p_vl = '1'
                                      UNION
                                      SELECT frm.o_id  AS form_id,
                                             fty.p1    AS form_man,
                                             null      AS field_pkey,
                                             hprp.p_vl AS height,
                                             wprp.p_vl AS width
                                      FROM  osy_obj frm
                                      LEFT JOIN osy_obj_prp hprp ON (frm.o_id = hprp.o_id AND hprp.p_id = 'height')
                                      LEFT JOIN osy_obj_prp wprp ON (frm.o_id = wprp.o_id AND wprp.p_id = 'width')
                                      LEFT JOIN osy_res      fty  ON (frm.o_sty = fty.v_id AND fty.k_id = 'osy-object-subtype')
                                      WHERE frm.o_id = '%s'",$this->get_par('form-related'),$this->get_par('form-related-ins'));*/
        $res = env::$dbo->exec_query("SELECT frm.o_id  AS form_id,
                                             fty.p1    AS form_man,
                                             dfld.p_vl AS field_pkey,
                                             hprp.p_vl AS height,
                                             wprp.p_vl AS width
                                      FROM  osy_obj frm
                                      INNER JOIN osy_obj      fld  ON (frm.o_id = fld.o_own)
                                      INNER JOIN osy_obj_prp  pfld ON (fld.o_id = pfld.o_id AND pfld.p_id = 'db-field-is-pkey')
                                      INNER JOIN  osy_obj_prp dfld ON (fld.o_id = dfld.o_id AND dfld.p_id = 'db-field-connected')
                                      LEFT JOIN  osy_obj_prp  hprp ON (frm.o_id = hprp.o_id AND hprp.p_id = 'height')
                                      LEFT JOIN  osy_obj_prp  wprp ON (frm.o_id = wprp.o_id AND wprp.p_id = 'width')
                                      LEFT JOIN  osy_res      fty  ON (frm.o_sty = fty.v_id AND fty.k_id = 'osy-object-subtype')
                                      WHERE frm.o_id = ? AND pfld.p_vl = '1'
                                      UNION
                                      SELECT frm.o_id  AS form_id,
                                             fty.p1    AS form_man,
                                             null      AS field_pkey,
                                             hprp.p_vl AS height,
                                             wprp.p_vl AS width
                                      FROM  osy_obj frm
                                      LEFT JOIN osy_obj_prp hprp ON (frm.o_id = hprp.o_id AND hprp.p_id = 'height')
                                      LEFT JOIN osy_obj_prp wprp ON (frm.o_id = wprp.o_id AND wprp.p_id = 'width')
                                      LEFT JOIN osy_res      fty  ON (frm.o_sty = fty.v_id AND fty.k_id = 'osy-object-subtype')
                                      WHERE frm.o_id = ?",array($this->get_par('form-related'),$this->get_par('form-related-ins')),'NUM');
        $pkey = array();
        foreach($res as $k => $rec)
        {
            if ($this->get_par('form-related') == $rec[0])
            {
                $pkey[] = $rec[2];
                $this->att('oform',base64_encode(OSY_WEB_ROOT.$rec[1].'[::]'.$rec[0].'[::]'.nvl($rec[4],'640').'[::]'.nvl($rec[3],'480')));
            }
             elseif($this->get_par('form-related-ins') == $rec[0])
            {
                $this->att('oform-insert',base64_encode(OSY_WEB_ROOT.$rec[1].'[::]'.$rec[0].'[::]'.nvl($rec[4],'640').'[::]'.nvl($rec[3],'480')));
            }
        }
       
        $this->par('pkey',$pkey);
   }
   
   public function get_columns()
   {
        return $this->__col;
   }
   
   public function add_filter($field,$value,$operator='=')
   {
        $b = $this->__db->backticks;
        $this->__par['sql_filter'][] = array($b.$field.$b,array('val'=>$value,'opr'=>$operator));
   }
}
?>

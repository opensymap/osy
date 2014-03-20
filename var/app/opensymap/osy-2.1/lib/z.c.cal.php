<?
 require_once('c.cmp.php');
 require_once('c.tag.php');
 
 class Calendar {
        
        public $LEvent = array();
        public $LEventColor = array();
        private $Dim;
  		var $_current_day;
		var $_current_month;
		var $_current_year;
		var $_first_day_of_month;
		var $_number_of_day_month;
		var $_days = array(1 => "&nbsp;");

		var $LDayOfWeek = array('Luned&igrave;',
                                'Marted&igrave;',
                                'Mercoled&igrave;',
                                'Gioved&igrave;',
                                'Venerd&igrave;',
                                'Sabato',
                                'Domenica');

        var $LMonth = array('01' => 'Gennaio',
                            '02' => 'Febbraio',
                            '03' => 'Marzo',
                            '04' => 'Aprile',
                            '05' => 'Maggio',
                            '06' => 'Giugno',
                            '07' => 'Luglio',
                            '08' => 'Agosto',
                            '09' => 'Settembre',
                            '10' => 'Ottobre',
                            '11' => 'Novembre',
                            '12' => 'Dicembre');
        
        public function __construct($mm,$aa){
              $this->Dim = array('width'  => 640,
                                 'height' => 480);
              switch($mm){
                 case 0:
                         $mm=12;
                         $aa--;
                         break;
                 case 13:
                         $mm = 1;
                         $aa++;
                         break;
              }
              $this->GetToday($mm,$aa);
    		  $this->_days = array_pad($this->_days,43,"&nbsp;");
      		  for ($i = 0; $i < $this->_number_of_day_month; $i++){
    		  	$this->_days[$this->_first_day_of_month + $i] = $i + 1;
    		  }
		}

		private function GetFirstDayOfMonth($mm,$aa){
            $app = jddayofweek(GregorianToJD ($mm,1,$aa),0);
			if ($app == 0){	
                $app = 7;
			}
			return $app;
		}

   		private function GetNumberOfDayInMonth($mm,$aa){
            return cal_days_in_month(CAL_GREGORIAN,$mm,$aa);
		}
        
		private function GetToday($mm,$aa){
		  if (($mm != "")&&($aa != "")){
			  $this->_current_day = 1;
			  $this->_current_month = $mm;
			  $this->_current_year = $aa;
		  } else {
		  	  $today = getdate();
			  $this->_current_day = $today["mday"];
			  $_POST['mm'] = $this->_current_month = $today["mon"];
			  $_POST['aa'] = $this->_current_year = $today["year"];
		  }
		  $this->_number_of_day_month = $this->GetNumberOfDayInMonth($this->_current_month,$this->_current_year);
		  $this->_first_day_of_month  = $this->GetFirstDayOfMonth($this->_current_month,$this->_current_year);
		}

        private function BuildCalendar($Bc){
		  $k        = $h = 1;
          $data     = '/'.$this->GetMonth().'/'.$this->GetYear();
          $data_raw = $this->GetYear().$this->GetMonth();

          for ($j = 0; $j < 6; $j++){
               $Bc->Row();
               for ($i = 0; $i < 7; $i++){
                    $CurCel = $Bc->Cell();
                    $CurCel->active  = 'yes';
                    $CurCel->valign  = 'top';
                    //Eventi

                    
                    $CurCel->style  = "height: {$this->Dim['cell']['height']}px;";
                    $CurCel->style .= " width: {$this->Dim['cell']['width']}px;";
                    
                    switch($i){
                        case 6:
                                $CurCel->style .= 'color: red;';
                                break;
                    }
                    if ($this->_days[$k] == "&nbsp;"){
                        $CurCel->style .= ' background-color: #eeeeee';
                        $CurCel->active = 'no';
                        $CurCel->Add('&nbsp');
                    } else {
                        $value='';
                        $data_ciclo     = str_pad($this->_days[$k],2,'0',STR_PAD_LEFT).$data;
                        $data_ciclo_raw = $data_raw.str_pad($this->_days[$k],2,'0',STR_PAD_LEFT);
                        $CurCel->ondblclick     = "DayOpen('{$data_ciclo}')";
                        $CurCel->onmouseover    = "cday(this,'on')";
                        $CurCel->onmouseout     = "cday(this,'off')";
                        $CurCel->onmousedown    = "return false";
                        $CurCel->onselectstart  = "return false";
                        if ($data_ciclo == date('d/m/Y')){
                            $CurCel->style .= "border: 2px solid orange;";
                        }
                        $DivDay = $CurCel->Add(new Tag('div'));
                        $DivDay->style  = "text-align: right;";
                        $DivDay->style .= "font-size: 12px";
                        $DivDay->Add($this->_days[$k]);
                        if (array_key_exists($data_ciclo,$this->LEvent)){
                           $DivDay->style  .= 'font-weight: bold';
                           $CurCel->bgcolor = "#e7eff7";
                           if (is_array($this->LEvent[$data_ciclo])){
                                $CurCnt = $CurCel->Add(new Tag('div'));
                                $CurCnt->style = 'white-space: nowrap;';
                                $CurCnt->style = 'width: '.$this->Dim['cell']['width'].'px;';
                                $CurCnt->style = 'overflow: hidden;';
                                for ($t = 0; $t < count($this->LEvent[$data_ciclo]); $t++){
                                    //$CurCnt->Add('<li>'.(implode("</li><li style=\"white-space: nowrap;\">",array_slice($this->LEvent[$data_ciclo],0,5)['NOT'])).'</li>');
                                    if ($t>5) break;
                                    $CurCnt->Add('<li'.(!empty($this->LEventColor[$data_ciclo][$t]) ? ' style="color: '.$this->LEventColor[$data_ciclo][$t].'"' : '').'>'.$this->LEvent[$data_ciclo][$t].'</li>');
                                }
                                
                           }
                        }
                    }
                    $CurCel->Add(nl2br($value));
//                    $list .= "<td  style=\"$style\">".nl2br($value)."&nbsp;</td>\n";
                    $k++;
               }
               //$list .= "</tr>\n";
           }
		   //$list .= "\n";
           //return $list;
	     }

        function GetEvent($dat){
            return $this->LEvent[$dat];
        }

        function GetMonth(){
            return str_pad($this->_current_month, 2, "0", STR_PAD_LEFT);
        }

        function GetYear(){
            return $this->_current_year;
        }
        private function CalcCellDim(){
            $this->Dim['cell']['height'] = floor(($this->Dim['height'] - 100) / 6);
            //echo $this->Dim['cell']['height'];
            $this->Dim['cell']['width']  = floor($this->Dim['width'] / 7)-2;
        }
        
        public function GetCalendar(){
            $this->CalcCellDim();?>
            <div style="font-size: 12px; padding: 3px; font-weight: bold; text-align: center; border-top:1px solid white; border-bottom: 1px solid silver; height: 20px;">
                <input type="submit" name="btn_indietro" value="&lt;" class="conferma" onClick="document.forms[0].mm.value = '<?=($this->_current_month - 1)?>'" style="width: 25px;">&nbsp;&nbsp;&nbsp;
                <?=$this->LMonth[date("m",mktime(0,0,0,$this->_current_month,1,$this->_current_year))];?>
               	<?=$this->_current_year?>&nbsp;&nbsp;&nbsp;
                <input type="submit" name="btn_indietro" value="&gt;" class="conferma" onClick="document.forms[0].mm.value = '<?=($this->_current_month + 1)?>'" style="width: 25px;">
            </div>         
            <?
              $BodyCal = new Table();
              $BodyCal->cellspacing = "0";
              $BodyCal->cellpadding = "3";
              $BodyCal->class       = "OsyDataGrid";
              foreach($this->LDayOfWeek as $day){
                      $BodyCal->Head($day)->width = $this->Dim['cell']['width'];
              }
              $this->BuildCalendar($BodyCal);
              echo $BodyCal->Get();
        }
      
      public function LoadEventFromDb($Sql,$Db){
            if (!empty($Sql)){
                $rs = $Db->ExecQuery($Sql);
                while ($rec = $Db->GetNextRecord($rs,'ASSOC')){
                    if (!array_key_exists('DAY',$rec)){
                        die('Field day not present');
                    }
                    $this->SetEvent($rec['DAY'],$rec['TYP'],$rec['COLOR']);
                }
                $Db->FreeRs($rs);
            }
      }
      
      public function SetDim($w,$h){
         if (!empty($w)) $this->Dim['width'] = $w;
         if (!empty($w)) $this->Dim['height'] = $h;
      }
      
      public function SetEvent($d,$e,$c=null){
          $Event = strlen($e) > 25 ? substr($e,0,22).'...' : $e;
          if (!is_array($this->LEvent[$d]) || array_search($Event,$this->LEvent[$d]) === false){
            $this->LEvent[$d][] = $Event;
            $this->LEventColor[$d][] = $c;
          }
      }
}?>

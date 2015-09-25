<?
/*
 +-----------------------------------------------------------------------+
 | osy/osy.form.map.php                                                  |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create form map data page                                           |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @title           GMap Data Managemente
 * @email           pietro.celeste@opensymap.org
 * @date-creation   15/01/2012
 * @date-update     15/01/2012
 * @description     La pagina in questione gestisce la creazione automatica dei form dati su una mappa GMap
 */
  include('../lib/osy/l.chk.acc.php');
  include('../lib/osy/c.frm.cmp.php');
  
  class Form extends ComponentBuilder{
  	
	private $AppID;
	private $FrmID;
	private $Dat;

	//Connessione al db OSY
	private $Dbo;
	//Connessione al db dell'applicazione
	private $Dba;
	
	private $Prop;
	private $Ajax;
    
	public function __construct($aid,$fid,$ajax){
		$this->AppID = $aid;
		$this->FrmID = $fid;
        $this->Ajax = $ajax;
		$this->Dbo   = env::$DBOsy;
		$this->__load_param__();
		$this->__load_data__();
        if (!empty($this->Ajax)){
            $this->__send_res__();
        } else {
    		$this->__init_page__();
        }
	}
	//Inizializzo la pagina
	private function __init_page__(){
		 $this->Page = new Page();
         $this->Page->SetTitle($this->Prop['ttl']);
         $this->Page->AddCss('../css/osy/style.css');
         $this->Page->AddScript('./js/jquery.tools.min.js');
         $this->Page->AddScript('./js/jmaps.js');
         $this->Page->AddScript('./js/Calendar.js');
         $this->Page->BODY->Att('class','fncfrm');
         $this->Page->BODY->Att('onload','InitPage()');
         $this->Page->AddPart('Form','form','BODY');
         $this->Page->Form->method = 'post';
         $this->Page->Form->enctype = $this->Form->Enctype;
         $this->Page->Form->Add(new Tag('input'))->Att('type','hidden')
                                                 ->Att('name','AppID')
                                                 ->Att('value',$_REQUEST['AppID']);
         $this->Page->Form->Add(new Tag('input'))->Att('type','hidden')
                                                 ->Att('name','FrmID')
                                                 ->Att('value',$_REQUEST['FrmID']);
         $this->Page->Form->Add(new Tag('input'))->Att('type','hidden')
                                                 ->Att('name','SesID')
                                                 ->Att('value',$_REQUEST['SesID']);
	}
	
	private function __load_data__(){
        $this->Prop['sql_cmd'] = str_replace('UNION','union',$this->Prop['sql_cmd']);
        list($Sql1,$Sql2) = explode('union',$this->Prop['sql_cmd']);
        $Sql = empty($this->Ajax) ? Env::ReplaceVariable($Sql1) : Env::ReplaceVariable($Sql2);
        $Rs = $this->Dba->ExecQuery($Sql);
        /*$this->Dat = array('sql'=>$Sql);
        return;*/
		while($rec = $this->Dba->GetNextRecord($Rs,'ASSOC')){
			$this->Dat[] = $rec;
		}
		$this->Dba->FreeRs($Rs);
	}
	
	private function __load_param__(){
		$this->Prop = $this->Dbo->ExecUniqueQuery("SELECT f.frm_id     ,
                                                          f.frm_ttl   as ttl ,
                                                          f.frm_lnk_tbl,
														  f.frm_dtv_qry as sql_cmd,
                                                          ifnull(i.app_dbcn,a.app_db_cnf) as app_dbcn,
                                                          f.cmd_del,
                                                          f.cmd_ins,
														  f.frm_dbg,
                                                          f.frm_rel_app_id as rel_app,
                                                          f.frm_rel_frm_id as rel_frm
                                                   FROM   osy_app_frm f 
                                                   INNER JOIN osy_app     a ON (f.app_id = a.app_id)
                                                   LEFT JOIN  osy_ist_app i ON (a.app_id = i.app_id AND i.ist_id = '".Env::$IstID."')
												   LEFT JOIN osy_ist i2 ON (i.ist_id = i2.ist_id)
                                                   WHERE  f.frm_id = '{$this->FrmID}' AND
                                                          f.app_id = '{$this->AppID}'",'ASSOC');
		$this->Dba = Env::GetConnection($this->Prop['app_dbcn']);
		//var_dump($this->Prop);
	}
	
    private function __send_res__(){
        //die(print_r($_POST,true));
        die(json_encode($this->Dat));
    }
    
	public function GetGMapKey(){
		return $this->Prop['gkey'];
	}
	
    public function GetProp($p){
        if (array_key_exists($p,$this->Prop)){
            return $this->Prop[$p];
        }
    }
    
	public function GetData($i='all'){
		if ($i === 'all'){
			$list = '';
			foreach ($this->Dat as $k => $Add){
				if (!empty($Add['lat']) && !empty($Add['lng'])){
				    $List .= (empty($List) ? '' : ",\n").'{id:\''.($Add['id']).'\',latitude:\''.($Add['lat']).'\',longitude:\''.($Add['lng']).'\',html:\''.addslashes($Add['address']).',apri scheda '.'\'}';
                } else {
                    $List .= (empty($List) ? '' : ",\n").'{id:\''.($Add['id']).'\',latitude:\''.($Add['lat']).'\',longitude:\''.($Add['lng']).'\',html:\''.addslashes($Add['address']).',apri scheda '.'\'}';
                    //                    $List .= (empty($List) ? '' : ",\n").'{address:"'.$Add['address'].'",html:"'.$Add['address'].'"}';
                }
			}
			return $List;
		} else {
			return $this->Dat[$i];
		}
	}
  }
  
  $Frm = new Form($_POST['AppID'],$_POST['FrmID'],$_POST['ajax']);
  $MrkCenter = $Frm->GetData(0);
?>
<!DOCTYPE HTML>

<html>
<head>
	<title>Untitled</title>
    <link rel="stylesheet" href="/css/osy/style.css"/>
    <link rel="stylesheet" href="/css/osy/jquery-ui-1.8.13.custom.css"/>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?v=3&sensor=false"></script>
	<script type="text/javascript" src="js/jquery.tools.min.js"></script>
	<script type="text/javascript" src="js/jmaps.js"></script>
	<script type="text/javascript">
    var OSWindow = window.frameElement.Win;
    function RefreshPage(){
    
    }
    
    function RefreshDataGrid(res){
        var fldhdn = new Array('_pos','html','latitude','longitude','icon');
        var bod = '';
        var hd  = '';
        for (i in res){
            bod += '<tr>';
            for(j in res[i]){
               if ($.inArray(j,fldhdn) == -1){
                   if (i == 0){ hd += '<th>' + j + '</th>'; }
                   bod += '<td>' + res[i][j] + '</td>';
              }
            }
            bod += '</tr>';
        }
        $('#mapgrd_head').html(hd);
        $('#mapgrd_body').html(bod);
    }
	$(function() {
		var options = {
                       address:'<?=(empty($_POST['txt_src_add']) ? $MrkCenter['address'] : $_POST['txt_src_add']);?>',
					   zoom:11,
					   markers :[<?=$Frm->GetData('all');?>]}
	    $("#map").gMap(options);
        $("#map").gMap.onInit = function(){
            var Vne = $("#map").gMap.vertex.getNorthEast();
            var Vso = $("#map").gMap.vertex.getSouthWest();
            //alert(' ' +Vne.lat()+'\n'+Vso.lng()+ ' ' +Vne.lng());
            //alert($("#map").gMap.vertex[0][0]);

            $.ajax({
                type : 'POST',                
                dataType: 'json',
                data : 'AppID='+$('#AppID').val()+
                       '&FrmID='+$('#FrmID').val()+
                       '&SesID='+$('#SesID').val()+
                       '&VLat0='+Vso.lat()+
                       '&VLat1='+Vne.lat()+
                       '&VLng0='+Vso.lng()+
                       '&VLng1='+Vne.lng()+
                       '&ajax=1',
                success: function(res){  
                    //$('#map').html(res.sql);
                                        RefreshDataGrid(res);
                    $.each(res,function(k,v){                         
                         $("#map").gMap.marker(v,$('#main').attr('rel_app'),$('#main').attr('rel_frm'),$('#SesID').val());
                         //$("#map").gMap.marker(v);
                    });

                }                       
            });
        }
	});
	</script>
	<style>
	  #map {
		width: 640px;
  		height: 560px;
		margin: 0px;
        border-right: 1px solid silver;
	  }
	  body{
	  padding:0px;
	  margin: 0px;
      background-color: white;
	  }
      
      table.map{
        width: 100%;
        padding: 0px;
        margin:0px;
        border-top: 1px solid white;
        border-left: 1px solid white;
      }
	</style>
</head>

<body>
<form id="main" method="post" rel_app="<?=$Frm->GetProp('rel_app')?>" rel_frm="<?=$Frm->GetProp('rel_frm')?>">
<input type="hidden" id="AppID" name="AppID" value="<?=$_POST['AppID']?>">
<input type="hidden" id="FrmID" name="FrmID" value="<?=$_POST['FrmID']?>">
<input type="hidden" id="SesID" name="SesID" value="<?=$_POST['SesID']?>">

<div style="padding: 5px; border-bottom: 1px solid gray; background-color: #CEDDEF;">Indirizzo <input id="txt_src_add" type="text" name="txt_src_add" style="width: 400px;" value="<?=$_POST['txt_src_add']?>"> <input type="button" name="btn_cnt" value="Cerca indirizzo" onclick="$('#map').gMap.setCenter($('#txt_src_add').val());"></div>
<table class="map" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 640px;"><div id="map"></div></td>
        <td valign="top">
            <div  style="overflow: auto; height: 540px;">
            <table cellspacing="0" cellpadding="3" id="mapgrd" class="OsyDataGrid" style="width: 100%">
                 <thead id="mapgrd_head">
                    
                 </thead>
                 <tbody id="mapgrd_body">
                 
                 </tbody>
            </table>
            </div>
        </td>
    </tr>
</table>
</form>
</body>
</html>

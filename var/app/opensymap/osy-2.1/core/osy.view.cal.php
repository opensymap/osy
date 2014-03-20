<?
  include('../lib/osy/l.chk.acc.php');
  include('../lib/osy/c.cal.php');
  
  if (empty($_POST['FrmID']) or empty($_POST['AppID'])){
     die("Parametri mancanti");
  }
  $PFrm = Env::$cdb->ExecUniqueQuery("SELECT f.frm_id     ,
                                             f.frm_ttl    ,
                                             a.app_db_cnf ,
                                             f.frm_dtv_qry,
                                             ifnull(frm_width,640) as w,
                                             ifnull(frm_height,480) as h
                                      FROM   sys_app_frm f INNER JOIN sys_app a on (f.app_id=a.app_id)
                                      WHERE  f.frm_id = '{$_POST['FrmID']}' AND
                                             f.app_id = '{$_POST['AppID']}'",'ASSOC');
  $Dba = Env::GetConnection($PFrm['app_db_cnf']);
  $Cal = new Calendar($_POST['mm'],$_POST['aa']);
  //var_dump($_POST);
  $Cal->LoadEventFromDb($PFrm['frm_dtv_qry'],$Dba);
  $Cal->SetDim($PFrm['w'],$PFrm['h']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
        <title>Calendario</title>
        <link rel="stylesheet" href="../css/osy/style.css">
        <script>        
            OSWindow = null;
                
            function cday(o,p){
                if (o.getAttribute('active') == 'yes'){
                    if (p == 'on'){
                        o.setAttribute('def_bgcolor',o.style.backgroundColor);
                        o.style.backgroundColor = '#efefef';
                    } else {
                        o.style.backgroundColor = o.getAttribute('def_bgcolor');
                    }
                }
            }
            
            function DayOpen(d){
                var f = document.forms[0];
                PageToLoad='./osy/FormCalDay.php?AppID='+f.AppID.value+'&FrmID='+f.FrmID.value+'&SesID='+f.SesID.value+'&Day='+d;
                OSWindow.OpenChild(PageToLoad,'<?=$PFrm['frm_ttl']?> del giorno '+d,640,480);
            }
            
            function PageRefresh(){
                var f = document.forms[0];
                    f.submit();
            }
            function Init(){
                setTimeout('PageRefresh()',60000);
                OSWindow = window.frameElement.Win;
            }
        </script>
        <style>
            body{
                background-color: #ceddef;
                margin: 0px;
            }
        </style>        
</head>

<body onload="Init()">    
<form method="post">
    <input type="hidden" name="AppID" value="<?=$_POST['AppID']?>">
    <input type="hidden" name="FrmID" value="<?=$_POST['FrmID']?>">
    <input type="hidden" name="SesID" value="<?=$_POST['SesID']?>">
    <input type="hidden" name="mm" value="<?=$Cal->GetMonth()?>">
    <input type="hidden" name="aa" value="<?=$Cal->GetYear()?>">                        
    <?=$Cal->GetCalendar();?>
</form>    
</body>
</html>
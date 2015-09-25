<?
  include('../lib/osy/l.chk.acc.php');
  include('../lib/osy/c.tag.php');
  
  if (empty($_POST['FrmID']) or 
      empty($_POST['AppID']) or
      empty($_POST['Day'])){
     die("Parametri mancanti");
  }
  $DayITA = $_POST['Day'];
  $DayARR = explode('/',$_POST['Day']);
  $DaySQL = "{$DayARR[2]}-{$DayARR[1]}-{$DayARR[0]}";
  $PFrm = Env::$cdb->ExecUniqueQuery("SELECT f.frm_id              as fid,
                                             f.frm_ttl             as ftt,
                                             a.app_db_cnf          as cdb,
                                             f.frm_dtv_qry         as qry,
                                             ifnull(frm_width,640) as w,
                                             ifnull(frm_height,480)as h
                                      FROM   sys_app_frm f INNER JOIN sys_app a on (f.app_id=a.app_id)
                                      WHERE  f.frm_id = '{$_POST['FrmID']}' AND
                                             f.app_id = '{$_POST['AppID']}'",'ASSOC');
  $Dba = Env::GetConnection($PFrm['cdb']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
    <link rel="stylesheet" href="../css/osy/style.css">
    <style>
       body{background-color: white;
            margin: 0px;}
       hr {color: #ceddef; border: 1px solid #ceddef}
       h3 {background-color: #ceddef; text-align: center; padding: 3px;}
       li {font-size: 12px; color: #555555}
       a  {text-decoration: none};
    </style> 
    <script>
        var OSWindow = window.frameElement.Win;       
    </script>
</head>

<body>
<form method="post">
<input type="hidden" name="AppID" value="<?=$_POST['AppID']?>">
<input type="hidden" name="FrmID" value="<?=$_POST['FrmID']?>">
<input type="hidden" name="SesID" value="<?=$_POST['SesID']?>">
<input type="hidden" name="Day" value="<?=$_POST['Day']?>">
<div style="margin: 5px; overflow: auto; height: 410px; width: 99%;">
<?
$rs = $Dba->ExecQuery("SELECT a.* FROM ({$PFrm['qry']}) a WHERE a.day = '$DayITA' ORDER BY a.typ");
$Ap = '';
while ($rec = $Dba->GetNextRecord($rs,'ASSOC')){
    if ($Ap != $rec['TYP']){
        if (!empty($Ap)) echo '</ul><br/>';
        ?><h3><?=$rec['TYP']?></h2>
          <ul><?$Ap = $rec['TYP'];
    }
    if (!empty($rec['FORM'])){?>
    <li style="color: <?=$rec['COLOR']?>"><a href="javascript:void(0);" onclick="OSWindow.OpenChild('<?=$rec['FORM']?>&SesID='+document.forms[0].SesID.value,'prova',640,480);" style="color: <?=empty($rec['COLOR']) ? 'black' : $rec['COLOR']?>"><?=$rec['NOTA']?></a></li>
   <?} else {?>
     <li style="color: <?=$rec['COLOR']?>"><?=$rec['NOTA']?></li>
<?   }
}?>
</form>
</ul>
</div>
</body>
</html>

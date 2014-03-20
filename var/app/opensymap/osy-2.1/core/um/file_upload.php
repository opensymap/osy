<?
  if (!empty($_FILES['txt_file']['name']))
  {
  	chdir($_REQUEST['a_path']);
	if (move_uploaded_file($_FILES['txt_file']['tmp_name'], $_FILES['txt_file']['name'])) 
	{ 
    ?>
			<script>
				window.opener.refresh_page();
				window.close();
			</script>
		<?
	} 
	 else 
	{
		$err_msg = 'Impossibile caricare il file '.$_FILES['txt_file']['name'];
	}
  }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Filemanager - Upload file</title>
	<LINK REL="stylesheet" TYPE="text/css" HREF="../../css/style.css">
	<style>
		
		fieldset
		{
		 font-family: Arial; 
		 font-size: 10px
		}
		
		legend
		{
		 font-size: 11px; 
		 font-weight: bold;
		}
	</style>
	<script>
		function check_form()
		{
			if (document.forms[0].txt_file.value != '')
			{
				document.forms[0].btn_invio.disabled = true;
				return true;
			}
			 else
			{
				alert('Field "Field to upload" is Empty.');
				return false;
			}
		}
	</script>
</head>

<body style="background-color: #ECE9D8" onLoad="<?=(!empty($err_msg) ? "alert('$err_msg')" : '');?>">
<form enctype="multipart/form-data" method="post" onSubmit="return check_form()">
<input type="hidden" name="a_path" value="<?=$_REQUEST['a_path']?>">
<fieldset>
<legend><b>Upload file</b></legend>
	<br>
	Upload  in : <?=$_REQUEST['a_path']?>
	<br><br>
	File to upload : <input type="file" name="txt_file" size="55">
	<div style="margin: 10px">
		<div style="float: left"><input type="button" name="btn_invio" value="Invia" onClick="submit(this)"></div>
		<div style="float: right"><input type="button" name="btn_chiudi" value="Chiudi" onClick="window.close()"></div>
		<div style="clear:both"></div>
	</div>
</fieldset>
</form>

</body>
</html>

<?
  if (!empty($_POST['btn_invio']) && !empty($_POST['txt_directory']))
  {
  	chdir($_REQUEST['a_path']);
	if(@mkdir(trim($_REQUEST['txt_directory'])))
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
		$err_msg = 'Impossibile creare la directory';
	}
  }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
	<LINK REL="stylesheet" TYPE="text/css" HREF="../../css/style.css">
	<style>
		fieldset{font-family: Arial; font-size: 10px}
		legend{font-size: 11px; font-weight: bold;}
	</style>
	<script>
		function check_form()
		{
			if (document.forms[0].txt_directory.value != '')
			{
				return true;
			}
			 else
			{
				alert('Field "Directory name" is Empty.');
				return false;
			}
		}
	</script>
</head>

<body style="background-color: #ECE9D8" onLoad="<?=(!empty($err_msg) ? "alert('$err_msg')" : '');?>">
<form method="post" onSubmit="return check_form()">
<input type="hidden" name="a_path" value="<?=$_REQUEST['a_path']?>">
<fieldset>
<legend><b>Create directory</b></legend>
	Create is in : <?=$_REQUEST['a_path']?>
	<br><br>
	Directory name : <input type="text" name="txt_directory" value="<?=$_POST['txt_directory'];?>" size="30" maxlength="50">	
	<div style="margin: 10px">
		<div style="float: left"><input type="submit" name="btn_invio" value="Crea"></div>
		<div style="float: right"><input type="button" name="btn_chiudi" value="Chiudi" onClick="window.close()"></div>
		<div style="clear:both"></div>
	</div>
</fieldset>
</form>

</body>
</html>

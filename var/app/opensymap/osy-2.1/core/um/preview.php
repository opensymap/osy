<?
 
  if (empty($_POST['hdn_path_actual']))
  {
  	$_POST['hdn_path_actual'] = $_SERVER['DOCUMENT_ROOT'];
  }
  chdir($_POST['hdn_path_actual']);
  define('PATH_WEB',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_POST['hdn_path_actual']));
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
	<style>
		body{margin: 5px; background-color: #ECE9D8}
		th,td,legend,fieldset{font-family: Arial}
		th,legend,fieldset{font-size: 12px}
		legend{font-weight: bold}
		td{font-size: 10px}
	</style>
	<script>
		function insert_image() 
		{
			if (parent.opener) 
			{
				frm = document.forms[0];
				
				/*for (var i = 0; i<frm.elements.length; i++)
				{
				   alert (frm.elements[i].name + ' = ' + frm.elements[i].value);
				}*/
				var src = document.forms[0].src.value;
				var alt = ''; 
				var border = 1;
				var vspace = 0;
				var hspace = 0;
				var width = frm.width.value;
				var height = frm.height.value;
				var align = '';
				var title = '';
				var onmouseover = '';
				var onmouseout = ''; 
				alert(document.forms[0].width.value);
				//opener.tinyMCE.insertImage(src, alt, border, hspace, vspace, width, height, align);
				
				var html = "";

					html += '<img src="' + src + '" alt="' + alt + '"';
					html += ' border="' + border + '" hspace="' + hspace + '"';
					html += ' vspace="' + vspace + '" width="' + width + '"';
					html += ' height="' + height + '" align="' + align + '" title="' + title + '" onmouseover="' + onmouseover + '" onmouseout="' + onmouseout + '" />';

				parent.opener.tinyMCE.execCommand("mceInsertContent", false, html);
				
				//top.close();
			}			 
		}
	</script>
</head>

<body>
<form>
	<? 
	  if(!empty($_POST['hdn_file_name']))
	  {
	    $owner = posix_getpwuid(fileowner($_POST['hdn_file_name']));
		$gowner = posix_getgrgid(filegroup($_POST['hdn_file_name']));
		$is_image = exif_imagetype($_POST['hdn_file_name']);
	 ?>
	 	<input type="hidden" name="src" value="<?=(PATH_WEB.'/'.$_POST['hdn_file_name']);?>">
	 <fieldset>
		<legend>File information</legend>
	   <table width="100%">
		<tr>
			<th colspan="2" align="left">File name</td>
		</tr>
		<tr>
			<td colspan="2"><?=$_POST['hdn_file_name'];?></td>			
		</tr>
		<tr>
			
			<th align="left"width="50%">Modification date</td>
			<th align="left" width="50%">Last access</td>
		</tr>
		<tr>
			<td><?=date ("Y-m-d H:i:s", filemtime($_POST['hdn_file_name']))?></td>
			<td><?=date ("Y-m-d H:i:s", fileatime($_POST['hdn_file_name']))?></td>			
		</tr>
		<tr>
			<th align="left">Owner</td>
			<th align="left">Group</td>
		</tr>
		<tr>
			<td><?=$owner['name']?></td>
			<td><?=$gowner['name']?></td>
		</tr>
		<tr>
			<th align="left">Permission</td>
			<th align="left">File size</td>
		</tr>
		<tr>
			<td><?=substr(sprintf('%o',fileperms($_POST['hdn_file_name'])), -4);?></td>
			<td><?=number_format(filesize($_POST['hdn_file_name']),0,',','.');?> bytes</td>
		</tr>
		<?
		  if ($is_image)
		  {
		  	$d_img = getimagesize($_POST['hdn_file_name']);
		?>	
			<tr>
				<th align="left">Dimensioni (in px)</th>
			</tr>
			<tr>
				<td>
					<input type="hidden" name="width" value="<?=$d_img['0']?>">
			    	<input type="hidden" name="height" value="<?=$d_img['1']?>">
					<?="$d_img[0] x $d_img[1]";?>					
				</td>
			</tr>
		<?
		  }
		?>
		</table>
		</fieldset>
	<?
	    if ($is_image)
		{
			?>
			<br>
			<fieldset style="text-align: center; padding: 3px">
				<legend>Preview</legend>			
				<div>
					<img src="thumbnail.php?f_path=<?=getcwd()?>&f_name=<?=$_POST['hdn_file_name'];?>" border="0" style="margin: 3px">
				</div>		
				<button type="button" onclick="insert_image()" name="btn_add_img">Seleziona immagine</button>
			</fieldset>
			<?
		}
	  }
	   else
	  {
	    ?>
		  <fieldset>
		   <legend>Directory information</legend>
			   <table width="100%">
				<tr>
					<th colspan="2" align="left">Directory</td>
				</tr>
				<tr>
					<td colspan="2"><?=getcwd();?></td>			
				</tr>
				<tr>			
					<th align="left"width="50%">Modification date</td>
					<th align="left" width="50%">Last access</td>
				</tr>
				<tr>
					<td><?=date ("Y-m-d H:i:s", filemtime($_POST['hdn_path_actual']))?></td>
					<td><?=date ("Y-m-d H:i:s", fileatime($_POST['hdn_path_actual']))?></td>			
				</tr>
				</table>
			</fieldset>	
		<?
	  }
	?>
</form>
</body>
</html>

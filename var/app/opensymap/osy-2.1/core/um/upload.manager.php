<?
  define('PATH_TO_START',$_SERVER['DOCUMENT_ROOT']);
  define('PATH_ACTUAL',(!empty($_REQUEST['hdn_path_actual']) ? $_REQUEST['hdn_path_actual'] : PATH_TO_START));
  define('UP_FOLDER','<img src="./img/up_folder.gif" border="0">');
  define('FOLDER','<img src="./img/folder.gif" border="0">');
  define('HTML','<img src="./img/html.gif" border="0">');
  define('TXT','<img src="./img/txt.gif" border="0">');
  define('IMG','<img src="./img/image.gif" border="0">');
  define('FILEDEL','<img src="./img/file_del.gif" border="0">');

  class file_manager
  {
  	var $_list_subdirs;
	var $_list_filed;
	
	function get_list_dir_content()
	{
		if (is_array($this->_list_subdirs))
		{
			asort($this->_list_subdirs);
			foreach($this->_list_subdirs as $description)
			{
				switch($description)
				{
					case '..' : 
								$icona = (str_replace('-03','',getcwd()).'/' == PATH_TO_START) ? '&nbsp;' : '<a href="javascript:chdir(\''.$description.'\')">'.UP_FOLDER.'</a>';
								$check_disabled = 'disabled="true"';
								break;
				    default : 
							  $icona = '<a href="javascript:chdir(\''.$description.'\')">'.FOLDER.'</a>';
							  $check_disabled = '';
							  break;
				}
				
				$list .= '<tr>
						  	<td class="body_cell"><input type="checkbox" name="arr_dir_sel[]" value="'.$description.'" '.$check_disabled.'></td>
							<td class="body_cell" align="center">'.$icona.'</td>
							<td class="body_cell">'.$description.'</td>
							<td class="body_cell">&nbsp;</td>
							<td class="body_cell">&nbsp;</td>
						  </tr>';
			}
		}
		
		if (is_array($this->_list_files))
		{
			asort($this->_list_files);
			foreach($this->_list_files as $f_name)
			{
				switch(@exif_imagetype($f_name))
				{
					case false :
								$arr_ext = array_reverse(explode('.',$f_name));
								switch(strtolower($arr_ext[0]))
								{
									case 'php' :
									case 'htm' :
									case 'html' :
									case 'asp' :
									case 'xml' :
												$icona = HTML;
												break;
									default: 
											 $icona = TXT;
											 break;
								}	   
								break;
					
					default: $icona = IMG;
							 break;
				}
				$list .= '<tr>
						  	<td class="body_cell"><input type="checkbox" name="arr_file_sel[]" value="'.$f_name.'"></td>
							<td class="body_cell" align="center"><a href="javascript:void(0)" onClick="preview_file(\''.$f_name.'\')">'.$icona.'</a></td>
							<td class="body_cell">'.$f_name.'</td>
							<td class="body_cell">'.filesize($f_name).' bytes</td>
							<td class="body_cell" align="center">'.date ("Y-m-d H:i:s", filemtime($f_name)).'</td>
						  </tr>';
			}
		}
		return $list;
	}
	
	function read_directory()
	{
		unset($this->_list_subdirs,$this->_list_files);
		$pdir = opendir('.');
		while ($file = readdir($pdir))
		{
		   if($file !='.' && $file !='')
	   	   {
	      	 $fit = filesize($file);
		  	 if (is_dir($file))
		  	 {
		  	  $this->_list_subdirs[] = $file;
		  	 }
		   	  elseif(is_file($file))
		  	 {
		  	  $this->_list_files[] = $file;
		     }
	   	 }
		}
	 	clearstatcache();
		closedir($pdir);
	}
	
	function file_manager()
	{
       chdir(PATH_ACTUAL);
	   $this->read_directory();
	}
  }

  $fm = new file_manager();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
	<LINK REL="stylesheet" TYPE="text/css" HREF="../../css/style.css">
	<style>
		body{margin: 0px; }
		th {font-family: Arial; font-size: 12px}
		td {font-family: Arial; font-size: 10px}
		th.path{color: blue}
		td.body_cell {background-color: white;}
	</style>
	<script language="javascript" type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script>		
		function mkdir()
		{
			var w_add_folder = window.open('mkdir.php?a_path=<?=getcwd();?>','mkdir','width=300,height=200');
		}
		
		function file_upload()
		{
			var w_add_folder = window.open('file_upload.php?a_path=<?=getcwd();?>','mkdir','width=480,height=200');
		}
		
		function file_delete()
		{
			if (confirm('Sei sicuro di voler eliminare i file selezionati'))
			{
				document.forms[0].action = 'file_delete.php';
                document.forms[0].target = 'main';
				document.forms[0].submit();
			}
		}
		
		function refresh_page()
		{
			document.forms[0].action = '<?=$PHP_SELF?>';
			document.forms[0].target = 'main';
			document.forms[0].submit();
		}
		
		function chdir(dir_name)
		{
            var frm = document.forms[0];
			    frm.action = 'preview.php';
			    frm.target = 'preview';
			    frm.hdn_path_actual.value += '/'+dir_name;
                frm.hdn_file_name.value = '';
			    frm.submit();
    			refresh_page();
		}
		
		function preview_file(file_name)
		{
			document.forms[0].hdn_file_name.value = file_name;
			document.forms[0].action = 'preview.php';
			document.forms[0].target = 'preview';
			document.forms[0].submit();
		}
	</script>
</head>

<body>
<form action="<?=$PHP_SELF?>" method="post">
	<input type="hidden" name="hdn_path_actual" value="<?=PATH_ACTUAL?>">
	<input type="hidden" name="hdn_file_name">

	<table cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td class="head_cell">
				<button type="button" style="width: 30px" onClick="mkdir()" title="Create directory"><img src="./img/add_folder.gif" alt="Crea una nuova directory" border="0"></button>
				<button type="button" style="width: 30px" onClick="refresh_page()" title="Refresh dir"><img src="./img/refresh_dir.gif" alt="Refresh della directory attuale" border="0"></button>
				<button type="button" style="width: 30px" onClick="file_upload()" title="Upload file"><img src="./img/upload_file.gif" alt="Upload file" border="0"></button>
				<button type="button" style="width: 30px" onClick="file_delete()" title="Delete file"><?=FILEDEL?></button>
			</td>
		</tr>
		<tr height="20">
			<th class="body_cell" style="color: blue; text-align: left">
					<?=getcwd();?>
			</th>
		</tr>
		<tr>
			<td height="510">
				<div style="overflow: auto; height: 510; background-color: white;">
					<table cellspacing="0" cellpadding="2" width="100%">
						<tr height="20">
							<th class="head_cell" width="20"><img src="./img/box.gif" border="0" align="absmiddle"></th>
							<th class="head_cell" width="20">&nbsp;</th>
							<th class="head_cell" width="300">File name</th>
							<th class="head_cell" width="80">Size</th>
							<th class="head_cell" width="120">Modified</th>
						</tr>
						<?=$fm->get_list_dir_content();?>
					</table>
				</div>
			</td>
		</tr>				
	</table>
	
</form>
</body>
</html>

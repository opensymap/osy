<?
class table 
{
   var $_table;
   var $_arr_cells;
   var $_table_header;
   var $_table_body;
   var $_cells;
   var $_class;
   var $_bgcolor;
   var $_cellpadding;
   var $_cellspacing;
   var $_width;
   var $_height;
   var $_border;
   var $_td_class;
   var $_td_bgcolor;
   var $_th_class;
   var $_th_bgcolor;
   
   function set_bgcolor($value)
   {
    $this->_bgcolor = " bgcolor=\"$value\"";
   }
   
   function set_border($value)
   {
    $this->_border = " border=\"$value\"";
   }
   
   function set_cellpadding($value)
   {
    $this->_cellpadding = " cellpadding=\"$value\"";
   }
   
   function set_cellspacing($value)
   {
    $this->_cellspacing = " cellspacing=\"$value\"";
   }
   
   function set_width($value)
   {
    $this->_width = " width=\"$value\"";
   }
   
   function set_height($value)
   {
    $this->_height = " height=\"$value\"";
   }
   
   function set_td_bgcolor($value)
   {
    $this->_td_bgcolor = " bgcolor=\"$value\"";
   }
   
   function set_th_background($value)
   {
    $this->_th_bgcolor = " bgcolor=\"$value\"";
   }
   
   function set_class($value)
   {
    $this->_class = " class=\"$value\"";
   }
   
   function set_th_class($value)
   {
    $this->_th_class = " class=\"$value\"";
   }
   
   function set_td_class($value)
   {
    $this->_td_class = " class=\"$value\"";
   }
   
   function add_row()
   {
    $this->_table_body .= "  <tr>\n".$this->_cells."  </tr>\n";
	unset($this->_cells);
   }
   
   function add_cells($type,$value,$align = '',$col_span = '',$row_span = '',$class = '',$width = '', $height = '', $style='')
   {
    $col_span = (!empty($col_span)) ? " colspan=\"$col_span\"" : '';
	$row_span = (!empty($row_span)) ? " rowspan=\"$row_span\"" : '';
	$align = (!empty($align)) ? " align=\"$align\"" : '';
	$class = (!empty($class)) ? " class=\"$class\"" : $this->_td_class;
	$width = (!empty($width)) ? " width=\"$width\"" : '';
	$height = (!empty($height)) ? ' height="'.$height.'"' : '';
	$style= (!empty($style)) ? ' style="'.$style.'"' : '';
    $this->_cells .= "    <$type".$align.''.$col_span.''.$row_span.''.$class.''.$width.''.$height.''.$style.">$value</$type>\n";
   }
   
   function get_table()
   {
    $this->_table_header = "<table".$this->_width."".$this->_height."".$this->_border."".$this->_cellspacing."".$this->_cellpadding."".$this->_class.">\n";
	$this->_table = $this->_table_header;
	$this->_table .= $this->_table_body;
    $this->_table .= "</table>\n";
    return $this->_table;
   }
   
   function put_cell($x,$y,$cell)
   {
    $this->_arr_cells[$x][$y] = $cell;
   }
   
   function make_cell($type,$value,$x,$y,$option = '')
   {
    $this->put_cell($x,$y,'<'.$type.' '.$option.'>'.$value.'</'.$type.'>');
   }
   
   function make_table()
   {
    $this->_table = '<table'.$this->_width.''.$this->_height.''.$this->_border.''.$this->_cellspacing.''.$this->_cellpadding.''.$this->_class.">\n";
    foreach($this->_arr_cells as $riga)
	{
	 $list = '<tr>';
	 foreach($riga as $cella)
	 {
	  $list .= $cella;
	 }
	 $list .= '</tr>'."\n";
	 $this->_table .= $list;
	}
	$this->_table .= '</table>'."\n";
	return $this->_table;
   }
}
  
class upload_manager 
{
	var $_error = false;
	var $_error_msg;
	var $_current_directory;
	var $_current_directory_web;
	var $_list_subdirs;
	var $_list_files;
	var $_icons = array('generic' => 'text.gif', 'jpeg' => 'image2.gif', 'jpg' => 'image2.gif', 'gif' => 'image2.gif');
	var $_path_start_server = '/var/www/virtual/org.opensymap/osy/img/';
	var $_path_start_web = '/upl';
	
	function action($action,$dir,$file)
	{
        switch(strtolower($action))
		{
			case 'rmdir'   : $this->delete_dir($dir); break;
			case 'mkdir'   : $this->create_dir($dir); break;
			case 'delete'  : $this->delete_file($file); break;
			case 'upload'  : $this->upload_file(); break;
			case 'incolla' : $this->paste_file(); break;
			default: $this->set_error('Errore azione sconosciuta'); break;
		}
	}
	
	function upload_file()
	{
	  global $file_upload,$file_upload_name;
	  if (!empty($file_upload_name))
	  {
      		if (!file_exists($file_upload_name))
			{
		  		if (move_uploaded_file($file_upload, $file_upload_name))
		  		{
		  			$this->set_error('Il file è stato ricevuto correttamente');
		  		}     	
		   		else		   		   
		  		{
		    		$this->set_error('Si è verificato un errore nella ricezione del file');
		  		}
			}
		 		else
			{
		  		$this->set_error('Il file ha un nome già in uso');
			}
	  }
	   else
	  {
	    $this->set_error('INome del file vuoto');
	  }
	}
	
	//Elimina il file specificato
	function delete_file($file,$path_complete=false)
	{
	  if ($path_complete == false) $file = './'.$file;
	  $this->set_error((@unlink($file)) ? "Il file $file è stato eliminato": "Il file $file non è stato eliminato");
	}
	
	//Copia a muove il file specificato
	function copy_file($file_to_move,$delete_source=false)
	{
	   if (!empty($file_to_move))
	   {
			$destination = basename($file_to_move);
			if (!file_exists($destination))
			{
				if (!empty($destination))
				{
	   		 		$this->set_error((copy($file_to_move,$destination)) ? 'Copia riuscita' : 'Errore copia non eseguita');
			 		//Se l'operazione non è una copy ma una move elimino il file sorgente
			 		if ($delete_source == true) $this->delete_file($file_to_move,true);
				}
			} 
			 else 
			{
				$this->set_error('Impossibile eseguire la copia. Il file di destinazione non è vuoto');
			}
	   }
	     else
	   {
			$this->set_error("ERRORE. non posso spostare il file nella directory richiesta.");
	   }
	}
	
	function paste_file()
	{
	  global $hdn_cache_operation, $hdn_file_in_cache;
	  if (!empty($hdn_cache_operation) && !empty($hdn_file_in_cache))
	  {
	    if ($hdn_cache_operation == 'CUT')
		{
		 	$this->copy_file($hdn_file_in_cache,true);
		}
		 else if ($hdn_cache_operation == 'COPY')
		{
		 	$this->copy_file($hdn_file_in_cache,false);
		}
		$hdn_cache_operation = $hdn_file_in_cache = '';
	  }
	}
	
	function create_dir($dir)
	{
	 	if (isset($dir) && ($dir != ""))
	 	{
 		 	$this->set_error((@mkdir("./$dir",0777)===true) ? "Directory creata" : "Creazione fallita della directory $dir"); 		
	 	}
	  	  else
	 	{
		 	$this->set_error("La directory non può essere creata perchè non hai specificato alcun nome.");
	 	}
	}
	
	//Elimina la directory specificata nel path corrente.
	function delete_dir($dir)
	{
	  	if (isset($dir) && ($dir != ""))
	  	{		  
		  $this->set_error((@rmdir($dir) ? 'La directory '.$dir.' è stata rimossa.' : 'ERRORE. La directory non è stata rimossa'));
	  	}
	   	  else
	  	{
	  	  $this->set_error('Non hai specificato la directory da eliminare.');
	  	}
	}
	
	function get_error()
	{
	  if ($this->_error == true)
	  {
	    foreach($this->_error_msg as $value)
		{
		 $msg .= $value.'\n';
		}
	  } 
	   else
	  {
	    $msg = false;
	  }
	  return $msg;
	}
	
	function get_icona($filename)
	{
	  if (!empty($filename))
	  {
	    $ext = strtolower(substr(strrchr($filename,'.'),1));
		if ($icona = $this->_icons[$ext])
		{
		 return $icona;
		}
		 else
		{
		 return $this->_icons['generic'];
		}
	  }
	}	
	
	function get_path_internet($file_name)
	{
	  return $this->_current_directory_web.'/'.$file_name;
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
	
	function set_error($msg)
	{
	  if (!empty($msg))
	  {
	   $this->_error = true;
	   $this->_error_msg[] = ' - '.$msg;
	  }
	}
	
	function set_path($current_path)
	{
	  if (!empty($current_path))
	  {
			//Soluzione temporanea da modificare con un controllo + efficace
			$current_path = str_replace("\\\\","/",$current_path);
			if ((strpos($current_path,$this->_path_start_server) === false) || ($this->_path_start_server.'..' == $current_path))
			{
		 		$this->_current_directory = $this->_path_start_server;
				//$this->set_error("Non puoi salire oltre questo livello");
			}
		 		else
			{
	  	  		$this->_current_directory = $current_path;				
			}
	  }
	    else
	  {
		$this->_current_directory = $this->_path_start_server;		
	  }
      echo $this->_current_directory;
      chdir($this->_current_directory);
	  $this->_current_directory = str_replace('\\','/',getcwd());
	  $this->_current_directory_web = substr($this->_current_directory,strpos($this->_current_directory,$this->_path_start_web));
	}
	
	function upload_manager($current_path)
	{
        $this->set_path($current_path);
	    $this->read_directory();
	}
}

  $um = new upload_manager($path1);

   if (isset($btn_action))
   {
	   $um->action($btn_action,$dir1,$file1);
	   $um->upload_manager($path1);
   }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<LINK REL="stylesheet" TYPE="text/css" HREF="../css/style.css">
<html>
<head>
	<title><?=getcwd();?></title>
	<style>
	 
	  td,a,th {font-family: Verdana; font-size: 10px}
	</style>
	<script language="JavaScript" src="./js/toolbar.js"></script>
	<script>
	  var file_old = false;
	  var dir_old = false;
	  var ogg_old = false;
	  		  
	  function change_dir(dir)
	  {
	    	document.forms[0].path1.value += '/'+dir;
			document.forms[0].submit();
	  }
	  
	  function delete_file()
	  {
		return confirm("Sei sicuro di eliminare il file selezionato ?"); 					 
	  }
	  
	  function delete_dir()
	  {
	    	if (document.forms[0].dir1.value != "")
			{
		  		return confirm("Sei sicuro di voler rimuovere la directory " + document.forms[0].dir1.value);
			} 
		 		else 
			{
		  		return false;
			}
	  }
	  
	  function create_dir()
	  {
	  		var risp = prompt("Inserisci il nome della directory da creare");
			if (risp != '')
			{
		  		document.forms[0].dir1.value = risp;			
			} 
		 		else 
			{
		  		return false;
			}
	  }
	  
	  function select_file(file1,ogg)
	  {
	   		document.forms[0].file1.value = file1;
			ogg.style.backgroundColor = "blue";
			ogg.style.color = "white";	
			if (ogg_old)
			{
				ogg_old.style.backgroundColor = "white";
				ogg_old.style.color = "black";
			}
	    	ogg_old = ogg;   
	  }
	    
       <?if (!empty($field_name)):?>
	   function visualizza_file(file)
	   {
	      window.opener.document.forms[0].<?=$field_name?>.value = file;
		  window.close();
	   }
	   <?else:?> 
	   function visualizza_file(file)
	   {
	      open(file,"win4","width=640,height=400");
	   }
	   <?endif;?>
	   	   	  
	   function select_dir(dir1,ogg)
	   {
	    	document.forms[0].dir1.value = dir1;
			ogg.style.backgroundColor = "blue";
			ogg.style.color = "white";	 
			if (ogg_old)
			{
				ogg_old.style.backgroundColor = "white";
				ogg_old.style.color = "black";
			}
	    	ogg_old = ogg;   		
	   }
	  
	   function view_error()
	   {
	    	var error_msg = '<?=$um->get_error();?>';
			if (error_msg)
			{
		 		alert(error_msg);
			}
	   }
	  
	   function copy()
	   {
	    	document.forms[0].hdn_cache_operation.value = 'COPY';
			document.forms[0].hdn_file_in_cache.value = document.forms[0].path1.value + '/' + document.forms[0].file1.value;
			document.all.label_file_cache.innerHTML = "Operazione: COPIA - File: " + document.forms[0].path1.value + '/' + document.forms[0].file1.value;
	   }
	  
  	   function cut()
	   {
	    	document.forms[0].hdn_cache_operation.value = 'CUT';
			document.forms[0].hdn_file_in_cache.value = document.forms[0].path1.value.replace('\\','/') + '/' + document.forms[0].file1.value;
			document.all.label_file_cache.innerHTML = "Operazione: TAGLIA - File: " + document.forms[0].path1.value + '/' + document.forms[0].file1.value;
	   }
	</script>
</head>

<body style="margin: 0px" onLoad="view_error()">
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="new_dir">
<input type="hidden" name="dir1">
<input type="hidden" name="file1">
<table cellspacing="0" cellpadding="2" border=0 width="100%" height="100%" bgcolor="#ECE9D8">
  <tr>
   		<td colspan="2" bgcolor="silver" class="head_cell">
   		<?
   	 	$form_tool_bar = new table();
		$form_tool_bar->set_cellpadding("0");
		$form_tool_bar->set_cellspacing("0");
		$form_tool_bar->make_cell('td','&nbsp;',0,0,'class="btn_toolbar_right"');
		$form_tool_bar->make_cell('td','<input type="submit" name="btn_action" value="rmdir" onClick="delete_dir()" class="submitter" style="width: 50px" onMouseOver="accendi(this)" onMouseOut="spegni(this)">',0,1,'class="btn_toolbar"');
		$form_tool_bar->make_cell('td','<input type="submit" name="btn_action" value="mkdir" onClick="return create_dir()" class="submitter" style="width: 50px" onMouseOver="accendi(this)" onMouseOut="spegni(this)">',0,2,'class="btn_toolbar"');
		$form_tool_bar->make_cell('td','<input type="submit" name="btn_action" value="delete" class="submitter" style="width: 50px" onClick="return delete_file()" onMouseOver="accendi(this)" onMouseOut="spegni(this)">',0,3,'class="btn_toolbar"');
		$form_tool_bar->make_cell('td','<input type="button" name="btn_action" value="copia" class="submitter" style="width: 50px" onClick="copy()" onMouseOver="accendi(this)" onMouseOut="spegni(this)">',0,4,'class="btn_toolbar"');
		$form_tool_bar->make_cell('td','<input type="button" name="btn_action" value="taglia" class="submitter" style="width: 50px" onClick="cut()" onMouseOver="accendi(this)" onMouseOut="spegni(this)">',0,5,'class="btn_toolbar"');
		$form_tool_bar->make_cell('td','<input type="submit" name="btn_action" value="incolla" class="submitter" style="width: 50px" onMouseOver="accendi(this)" onMouseOut="spegni(this)">',0,6,'class="btn_toolbar"');
		$form_tool_bar->make_cell('td','<button onClick="window.close()" onMouseOver="accendi(this)" onMouseOut="spegni(this)">Chiudi</button>',0,7,'class=btn_toolbar');
		echo $form_tool_bar->make_table();
		?>
    	</td>
  </tr>
  <tr>
  		<td bgcolor="silver" class="testata">Indirizzo</td>
		<td class="testata"><input type="text" name="path1" value="<?=str_replace('\\','/',getcwd());?>" size=80 readonly ></td>
  </tr>    
  <tr>      
   		<td colspan="2" valign="top" align="left" width="100%">
   			<fieldset>   	
   				<div style="width: 624; height: 310; overflow: auto; border: 0px; background-color: white;">
     				<?
						$file_panel = new table();
						$file_panel->make_cell('td','La directory selezionata &egrave; vuota',0,0,'colspan="4" align="center"');
	 					$c = $r = 0;
						$num_col = 4;

						if (count($um->_list_subdirs) > 0)
						{
	    						foreach($um->_list_subdirs as $value)
								{
		  							$select_dir = ($value != '..') ? "onMouseDown=\"select_dir('$value',this)\"" : "";
									$file_panel->make_cell('td','<img src="../image/icons/folder.gif" border="0"><br>'.$value,$r,$c,'width="100" align="center" '.$select_dir.' onDblClick="change_dir(\''.$value.'\')"');
									$c++;
									if ($c > $num_col)
									{
										$c = 0;
										$r++;
									}
								 }
						}

					 	if (count($um->_list_files) > 0)
						{
							foreach($um->_list_files as $value)
							{
									$icona = $um->get_icona($value);
									$nome_file = (strlen($value) > 16) ? substr($value,0,16)."..." : $value;
									$file_panel->make_cell('td','<image src="../image/icons/'.$icona.'" border="0" alt="'.$value.'"><br><span>'.$nome_file.'</span>',$r,$c,'width="100" align="center" onMouseDown="select_file(\''.$value.'\',this)" onDblClick="visualizza_file(\''.$um->get_path_internet($value).'\')"');
									$c++;
									if ($c > $num_col)
									{
										$c = 0;
										$r++;
									}
							}
						}
						echo $file_panel->make_table();
					 ?>
				</div>
			</fieldset>
   		</td>
  	</tr>							
	<tr>
		<td align="center" colspan="2">
			<fieldset>
				<legend accesskey=U>File upload</legend>
				 <input type="file" name="file_upload" size="75">&nbsp;<input type="submit" name="btn_action" value="Upload">
			 </fieldset>
		</td>
	</tr>
	<tr>
		<th height="45" colspan="2">
			<fieldset>
				<legend accesskey="F">File in cache</legend>						
				<input type="hidden" name="hdn_cache_operation" value="<?=$hdn_cache_operation?>" size=4>
				<input type="hidden" name="hdn_file_in_cache" value="<?=$hdn_file_in_cache?>" size=60>
				<span id="label_file_cache">
				<?
					if ($hdn_cache_operation == 'COPY')
					{
						echo 'Operazione: COPIA - File: '.$hdn_file_in_cache;
					}
					 else if ($hdn_cache_operation == 'CUT')
					{
						echo 'Operazione: TAGLIA - File: '.$hdn_file_in_cache;
					}
				?>
				&nbsp;
				</span>
			</fieldset>					
		</th>
	 </tr>
</table>
</form>
</body>
</html>

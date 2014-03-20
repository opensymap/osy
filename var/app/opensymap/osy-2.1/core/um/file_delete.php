<?
  chdir($_POST['hdn_path_actual']);

  if (is_array($_POST['arr_file_sel']))
  {
  		foreach($_POST['arr_file_sel'] as $file)
		{
			unlink($file);
			//echo $_POST['a_file'].$file;
		}
  }

  if (is_array($_POST['arr_dir_sel']))
  {
  	foreach($_POST['arr_dir_sel'] as $key => $dir)
	{
		@rmdir($dir);
	}
  }

  Header('Location: upload.manager.php?hdn_path_actual='.$_POST['hdn_path_actual']);
?>
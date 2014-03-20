<?
class tree 
{
	private $__db = null;
	private $__sql = null;
	private $__data_raw = array();
	private $__data_res = array();
	private $__gid_app = array();
	
	public function __construct($sql,$db,$gid_ena)
	{
		$this->__db = $db;
		$this->__sql = $sql;
		$this->__gid_app = is_array($gid_ena) ? $gid_ena : explode(',',$gid_ena);
		$this->__build();
	}
	
	private function __build()
	{
	    $res = $this->__db->exec_query($this->__sql,null,'NUM');
		foreach($res as $k => $rec)
		{
			//Pos 0 => id
		    //Pos 1 => parent 
			//Pos 2 => id che hanno accesso al livello;
			$rec[1] = empty($rec[1]) ? '0' : $rec[1];
			$this->__data_raw[$rec[1]][] = $rec;
		}
		$this->__build_tree_cond();
	}
	
	private function __build_tree_cond($fid='0',$p_acc='all',$p_dis=null)
	{
		//echo $fid.'<br>';
		if (!array_key_exists($fid,$this->__data_raw))
		{
			return;
		}
		
		foreach($this->__data_raw[$fid] as $k => $rec)
		{
			$ena = true;
			if ($rec[2] == 'parent') $rec[2] = $p_acc;
			if ($rec[3] == 'parent') $rec[3] = $p_dis;
			if ($rec[2] != 'all')
			{
				$a = explode(',',$rec[2]);
				$d = array_intersect($a,$this->__gid_app);
				if (empty($d)) $ena = false;
			}
			if (!empty($ena) && !empty($rec[3]))
			{
			    $a = explode(',',$rec[3]);
				$d = array_diff($a,$this->__gid_app);
				if (empty($d)) $ena = false;
			}
			if ($ena)
			{
				$this->__data_res[] = $rec[0];
				$this->__build_tree_cond($rec[0],$rec[2],$rec[3]);
			}
		}
		
	}
	
	public function get()
	{
		return $this->__data_res;
	}
}
?>

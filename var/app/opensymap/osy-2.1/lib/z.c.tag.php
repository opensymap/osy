<?
  $LTag = Array();
  
  class Tag
  {
    protected $Cnt = Array(); 
    public $CurCnt = null;
    public $Att = Array();
    public $Raw = '';
    private $LTag;
    
    public function __set($p,$v)
	{
           $this->Att[$p] = $v;
    }
    
    public function __get($pnam)
	{
        return $this->Att[$pnam];
    }
    
    public function __construct($tag,$id=Null,$content=null)
	{
        $this->Att[0] = $tag;
        if (!empty($id))
		{
            $this->Att['id'] = $id;
        }
        if (!is_null($content))
        {
            $this->Add($content);
        }
    }
    
    public function Add($a,$iCnt=Null)
	{
        $iCnt = ($iCnt!==0 && empty($iCnt)) ? count($this->Cnt) : $iCnt;
     
        return $this->Cnt[$iCnt] = $a;
    }
    
    public function Add2($a,$iCnt=Null)
	{
        if (is_array($a))
		{
            foreach($a as $t)
			{
                $iCnt = ($iCnt!==0 && empty($iCnt)) ? count($this->Cnt) : $iCnt;
                $this->Cnt[$iCnt] = $t;
            }                         
            return $t;
        } 
		 else 
		{
            $iCnt = ($iCnt!==0 && empty($iCnt)) ? count($this->Cnt) : $iCnt;
            return $this->Cnt[$iCnt] = $a;
        }
    }
    
    public function Build($depth)
	{
        $Tag = array_shift($this->Att);
        $Spc = $depth > 0 ? str_repeat('   ',$depth) : '';
        if (!empty($Tag))
		{
            $Raw = "{$Spc}<{$Tag}";
            foreach($this->Att as $KAtt => $VAtt)
			{
                $Raw .= " ".$KAtt.'="'.(($KAtt="value" or VAtt==='0' or $VAtt === '' or !empty($VAtt)) ? $VAtt : $KAtt).'"';
            }
            $Raw .= !empty($this->Cnt) ? ">" : "/>\n";
        }
        foreach($this->Cnt as $KCnt => $Cnt)
		{
            $Raw .= (is_object($Cnt) && empty($KCnt) ? "\n" : '');
            //$Raw .= (is_object($Cnt) && method_exists($Cnt,'Get')) ? $Cnt->Get($depth+1) : $Cnt; //str_replace("\n","\n{$Spc}",$Cnt);
            $Raw .= $Cnt;
        }
        if (!empty($this->Cnt) && !empty($Tag))
		{
            $Raw .= (is_object($Cnt) ? $Spc : '')."</{$Tag}>\n";
        }
        return str_replace("\n\n","\n",$Raw);
    }

    public function Get($depth=-1)
	{
        return $this->Build($depth);
    }
    
    public function GetCnt($i)
	{
        return $this->Cnt[$i];
    }
    
	public function Child($i=0)
	{
		if (is_null($i))
		{
			return $this->Cnt;
		}
		if (array_key_exists($i,$this->Cnt))
		{
			return $this->Cnt[$i];
		}
		return false;
	}
	
    public function IsEmpty()
	{
        return count($this->Cnt) > 0 ? false : true;
    }
    
    public function Att($p,$v='',$Concat=false)
	{
        if (is_array($p))
		{
            foreach ($p as $k => $v)
			{
              $this->Att[$k] = $v;
            }
        } 
		 else
		{
            if ($Concat && !empty($this->Att[$p]))
			{
                $concat_car = ($Concat===true) ? ' ' : $Concat;
				$this->Att[$p] .= "{$concat_car}{$v}";
            } 
			 else 
			{
                $this->Att[$p] = $v;
            }
        }
        return $this;
    }
    
    public function __toString()
	{
        return $this->Get();
    }
    
    public static function create($tag)
    {
        $tag = new Tag($tag);
        return $tag;
    }
  }
  
  class Page extends Tag
  {

     private $Part = Array();

     public function __construct(){
        $this->Part['DOCTYPE'] = 'html';
		//$this->Part['DOCTYPE'] = ' html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"';
        $this->AddPart('HTML','html');
        $this->AddPart('HEAD','head','HTML');
        $this->AddPart('BODY','body','HTML');
     }
     
     public function __get($p){
        return $this->Part[$p];
     }
     
     public function AddBody($o){
        return $this->Part['BODY']->Add($o);
     }

     public function AddCss($path){
        $s = $this->Part['HEAD']->Add(new Tag('link'));
        $s->rel  = 'stylesheet';
        $s->href = $path;
     }
     
     public function AddMeta(){
        $m = $this->Part['HEAD']->Add(new Tag('meta'));
        return $m;
     }
     
     public function AddPart($p,$t,$par=null){
          $t = is_object($t) ? $t : new Tag($t);
          $this->Part[$p] = !empty($par) ? $this->Part[$par]->Add($t) : $this->Add($t);
     }
     
     public function AddScript($src,$cod=''){
        $s = $this->Part['HEAD']->Add(new Tag('script'));
        if ($src){
            $s->src = $src;
        }
        $s->Add($cod);
     }

     public function Get(){
        return trim("<!DOCTYPE {$this->Part['DOCTYPE']}>\n".parent::Get());
     }
     
     public function Part($p){
        return $this->Part[strtoupper($p)];
     }

     public function SetDocType($d){
        $this->Part['DOCTYPE'] = $d;
     }
     
     public function SetTitle($t){
        $this->Part['HEAD']->Add(new Tag('title'))->Add($t);
     }
  }

  class Table extends Tag{
     
     private $CurRow;
     private $Part = array();
     
     public function __construct($typ='table'){
        parent::__construct($typ);
        if ($typ!='table'){
            $this->Part[strtolower($typ)] = $this;
        }
     }
     
     public function Row($v=null,$part='tbody'){
        $part = strtolower($part);
        if (!empty($v)){
            $this->CurRow->Add($v);
        } else {
            if (!array_key_exists(strtolower($part),$this->Part)){
                $this->Part[$part] = $this->Add(new Tag($part));
            }
            $this->CurRow = $this->Part[$part]->Add(new Tag('tr'));
        }
        return $this->CurRow;
     }
     
     public function Head($val=null){
        return $this->Cell($val,'th');
     }
     
     public function GetRow(){
        return $this->CurRow;
     }
     
     public function Body(){
        $this->Add(new Tag('tbody'));
     }
     
     public function Cell($val=null,$typ='td'){
        if (empty($this->CurRow)){
            $this->Row();
        }
        $c = $this->CurRow->Add(new Tag($typ));
        if ($val === '0' or !empty($val)){
            $c->Add($val);
        }
        return $c;
     }
     
     public function CellXY($val=null,$xr,$yc)
     {
        if (!is_object($this->Cnt[$xr])){
            $this->CurRow = $this->Cnt[$xr] = new Tag('tr');
        }
        $t = $this->Cnt[$xr]->Add(new Tag('td'),$yc)->Add($val);
        return $t;
     }
     
     
  }
/*  echo memory_get_usage() . "\n"; // 57960
  $p = new Page();
  $p->SetTitle('HelloWord! page');
  $p->AddScript('ciao');
  
  $t = $p->AddBody(new Tag('form'))->Add(new Table());
  $t->Head('Hello')->Att('style','font-size: 20px;');
  $t->Row();
  $t->Cell('Word!sdgdfgdfgdfgdfg');
  $t->Row();
  $t->Cell('Word!sdgdfgdfgdfgdfg');
  echo memory_get_usage() . "\n"; // 57960
  echo $p->Get();
  $prova = <<<EOF
Ciao sto provando HEREDOC
EOF;

echo $prova;*/
?>
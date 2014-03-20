<?
include("../lib/l.chk.acc.php");
include('../lib/fpdf16/fpdf.php');

class FormPdf extends FPDF{
    public $Form;
    private $Dbo;
    
    public function __construct($Cdb,$App,$Frm){
        parent::__construct();
        $this->Dbo = $Cdb;
        $this->Form = new stdClass();
        $this->Form->AppId = $App;
        $this->Form->Id = $Frm;
        $this->LoadField();
        $this->Build(); 
    }
    
    private function Build(){
        
    }
    
    private function BuildCell(){
    
    }
    
    public function LoadField(){
        $rs = $this->Dbo->ExecQuery("SELECT    f.fld_id            ,
                                               f.fld_typ_id      as TYPE,
                                               f.fld_lbl         as LABEL,
                                               t.typ_cst           ,
                                               r.prp_id            ,
                                               r.prp_val           ,
                                               r.prp_val_lar       ,
                                               p.prp_cat           
                                    FROM       sys_app_frm_fld f 
                                    INNER JOIN sys_fld_typ             t ON (f.fld_typ_id = t.typ_id AND t.typ_cst is not null)
                                    LEFT JOIN  sys_app_frm_fld_prp_rel r ON (f.app_id = r.app_id AND f.frm_id = r.frm_id AND f.fld_id = r.fld_id)
                                    LEFT JOIN  sys_fld_prp             p ON (r.prp_id = p.prp_id)
                                    WHERE   f.app_id = '{$this->Form->AppId}' AND 
                                            f.frm_id = '{$this->Form->Id}' AND 
                                            f.fld_typ_id NOT IN ('COK','SES','CST')
                                    ORDER BY f.fld_id");
        $i=0;
        while ($rec = $this->Dbo->GetNextRecord($rs,'ASSOC')){
              //var_dump($rec);
		      if (!isset($_REQUEST[$rec['fld_id']])){
			  	  $this->Form->Field->Value[$rec['fld_id']] = $_REQUEST[$rec['fld_id']] = $this->Form->Field->Value[$rec['fld_lnk_db']];
			  }
              $this->SetComponentDefinition($rec);
              $i++;
        }
        $this->Form->NumField = $i;
        $this->Dbo->FreeRs($rs);
    }
    
    private function SetComponentDefinition($fld){
		$KCmp = $fld['fld_id'];        //Identificatifo del componente
		$PCmp = array_splice($fld,-4); //Propietà del componente
        if ($fld['TYPE'] == 'FIL'){
           $this->Form->Enctype = 'enctype="multipart/form-data"';
        }
		if (!is_array($this->Form->Component) or !array_key_exists($KCmp,$this->Form->Component)){
		   $this->Form->Component[$KCmp] = $fld;
		   $this->Form->Component[$KCmp]['Prp'] = Array();
		}
		if (!empty($PCmp['prp_id'])){
			$this->Form->Component[$KCmp][$PCmp['prp_id']] = array($PCmp['prp_val'],$PCmp['prp_val_lar'],$PCmp['prp_cat']);
		}
    }
}

$Form = new FormPdf(Env::$cdb,$_POST['AppID'],$_POST['FrmID']);
$Form->AddPage();
$Form->SetFont('Arial','B',16);
$Form->Cell(40,10,'Hello World!');
//$Form->Output();
var_dump($Form->Form->Component);
?>

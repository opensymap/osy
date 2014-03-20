<?
require_once(OSY_PATH_LIB.'c.tag.php');
require_once(OSY_PATH_LIB.'c.component.php');
require_once(OSY_PATH_LIB.'c.component.datagrid.php');
$GLOBALS['osy://form/error'] = null;
/**
 * osy_form e' la classe base che permette di costruire "FORM HTML" caricandone i metadati descrittivi da
 * alcune tabelle base memorizzate su un DB. Tali tabelle rappresentano il cuore del sistema.
 * La 2 TABLE fondamentali sono "osy_app_frm" e "osy_app_frm_fld", in queste 2 tabelle
 * verranno interfacciate la struttura fisica del db alle form HTML necessarie a
 * popolare le tabelle medesime.
 *
 * @author PIETRO CELESTE
 * @version 1.0
 **/

class osy_view
{
    protected static $__fld = array();
    protected static $__par = array();
    
    public static $form = null;
    public static $page = null;
    public static $js_fnc = null;
    private static $par_man = array();

    public function init()
    {
        self::__init__();
        self::__trigger_load__();
        self::__event_exec__('form-init');
        static::__init_extra__();
        self::__init_end__();
        self::__load_fields_value__();
        self::__load_field_parameters__();
        self::__event_exec__('form-before-load-component');
        self::__load_fields_on_form__();
    }
    
    private static function __init__()
    {
        $sql = "SELECT a.o_id   AS app_id, 
                       i.p_1    AS db_con_str,
                       f.o_id   AS form_id, 
                       COALESCE(l1.p_vl,f.o_lbl) AS form_ttl, 
                       f.o_sty  AS form_typ,
                       p.p_id   AS par_id,
                       p.p_ord  AS par_ord,
                       p.p_vl   AS par_vl,
                       i.p_1,
                       l1.p_vl AS lang
                 FROM   osy_obj_rel     i
                 INNER JOIN osy_obj_rel l ON (i.o_1 = l.o_1 AND i.o_2 = l.o_2 AND l.o_3 = ?)
                 INNER JOIN osy_obj     a ON (i.o_2  = a.o_id)
                 INNER JOIN osy_obj     f ON (a.o_id = f.o_own )
                 LEFT JOIN  osy_obj_prp p ON (f.o_id = p.o_id AND p.p_id NOT IN ('language'))
                 LEFT JOIN  osy_obj_prp l1 ON (f.o_id = l1.o_id AND l1.p_id IN ('language') AND ".env::$dbo->cast('l.p_1','integer')." = l1.p_ord)
                 WHERE  i.o_1 = ?
                 AND    i.r_typ  = 'instance+application'
                 AND    f.o_id = ?";
        $rs = env::$dbo->exec_query($sql,array(trim(env::$uid),'instance://'.env::$iid.'/',env::$fid),'ASSOC');
      /*
       *  Se si verifica la seguente condizione qualcuno ha richiamato una form che richiede l'autenticazione
       *  senza essere autenticato. In questo caso mostro l'errore di access denied
       */
       foreach($rs as $k => $par)
       {
            if ($k == 0)
            {
                self::$__par['app-id'] = $par['app_id'];
                self::$__par['check-label-view'] = array();
                self::$__par['check-visibility'] = array();
                self::$__par['database-connection-string'] = $par['db_con_str'];
                self::$__par['form-id'] = $par['form_id'];
                self::$__par['form-title'] = $par['form_ttl'];
                self::$__par['form-type'] = $par['form_typ'];
                self::$__par['frm_dbg'] = 0;
                self::$__par['width'] = 640;
                self::$__par['height'] = 480;
                self::$__par['mode'] = 'FORM';
                self::$__par['command'] = array();
                self::$__par['after-exec'] = 'close';
            }
            self::$__par[trim($par['par_id'])] = $par['par_vl'];
       }

       if (empty(self::$__par['is-public']) && empty(env::$is_auth))  env::page_error(401,'Unauthorized');
       env::$dba = env::dbcon_by_str(self::$__par['database-connection-string']);
       self::$page = new page();
       self::$page->set_title(self::$__par['form-title']);

       self::$form = self::$page->body->att('class','osy-body')->add(new form('osy-form'));
       self::$form->att('method','post');
    }
    
    private static function __init_end__()
    {
       //Se ci sono script da caricare sulla pagina li aggiungo 
       //E' necessario posizionare html-script in questo metodo al fine di permettere prima il caricamento delle librerie
       if (!empty(self::$__par['html-script'])) { self::$page->Part('HEAD')->add(self::$__par['html-script']); }
       
       if (!empty($_REQUEST['osy']['prev']) && $_REQUEST['osy']['prev'] != self::get_par('rel_frm_ins_id'))
       {
          self::$form->add(new hidden_box('osy-form-prev'),'first')->att('class','req-reinit');
       }
        else
       {
          unset($_REQUEST['osy-form-prev']);
       }
       foreach($_REQUEST['osy'] as $par => $val)
       {
            self::$form->add(new hidden_box('osy['.$par.']'),'first')->att('class','req-reinit');
       }
    }
    
    protected static function __init_extra__(){ }

    protected static function __build__()
    {
        //Controllo che l'array par sia settato (valori provenienti dalla finestra parent)
       if (key_exists('par',$_REQUEST) && is_array($_REQUEST['par']))
       {
          foreach($_REQUEST['par'] as $k => $v)
          {
              if(!key_exists("par[$k]",self::$__fld))
              {
                self::$form->add(new hidden_box("par[$k]"),'first')->att('class','req-reinit');
              }
          }
       }
       self::__event_exec__('form-build');
       self::__build_command__();
       self::__build_foot__();
       self::__event_exec__('form-show');
    }

    /**
     ** @abstract Metodo che inizializza i tasti comando della Form.
     ** @private
     ** @return void
     **/
    private static function __build_command__()
    {
        if (self::$__par['mode'] != 'VIEW')
        {
            switch(self::$__par['form-type'])
            {
               case 'form-wizard':
                          self::$__par['command']['save'] = new Button('BtnSave','cmd_save');
                          self::$__par['command']['save']->Att('style','color: blue; font-weight: bold;');
                          if (!empty(self::$__par['next-form']))
                          {
                            $par = array('label' => 'Avanti','after-exec'=> 'next', 'next' => self::$__par['next-form']);
                          }
                           else
                          {
                            $par = array('label'=>'Termina','after-exec'=>'close');
                          }
                          self::$__par['command']['save']->att($par);
                          if (!empty(self::$__par['previous-form']))
                          {
                            self::$__par['command']['prev'] = new Button('BtnPrev','cmd_prev');
                            self::$__par['command']['prev']->att('label','Indietro')->Att('class','extra');
                            self::$__par['command']['prev']->att('previous',self::$__par['previous-form']);
                          }
                          break;
               default:
                        if (!empty(self::$__par['button-save']))
                        {
    	                    self::$__par['command']['save'] = new Button('BtnSave','cmd_save');
            	            self::$__par['command']['save']->att('style','min-width: 70px; color: blue; font-weight: bold;')
                                                           ->att('label','Salva')
                                                           ->att('after-exec',self::get_par('after-exec'));
                            if (self::$__par['button-save'] == '-1') self::$__par['command']['save']->Att('class','extra');
                        }
                        break;
            }
            if (!empty($_REQUEST['pkey']) && !empty(self::$__par['button-delete']))
            {
	            self::$__par['command']['delete'] = new Button('BtnDelete',"cmd_delete");
	            self::$__par['command']['delete']->att('style','width: 70px; color: red;')->att('label','Elimina');
                if (self::$__par['button-delete'] == '-1') self::$__par['command']['delete']->att('class','extra');
	        }
        }
        //Pulsante stampa
        if (!empty($_REQUEST['pkey']) && !empty(self::$__par['button-pdf']))
        {
            self::$__par['command']['print'] = new Button('BtnPrint','cmd_print');
            self::$__par['command']['print']->Att('onclick',"osyview.print('".self::$__par['button-pdf']."')")->att('label','Stampa');
        }
        //Pulsante chiudi
        if (!empty(self::$__par['button-close']))
        {
            self::$__par['command']['close'] = new Button('BtnClose','cmd_close');
            self::$__par['command']['close']->att('style','width: 70px')->att('label','Chiudi');
            if (self::$__par['button-close'] == '-1')  self::$__par['command']['close']->att('class','extra');
        }
        
    }
    
    private static function __build_foot__()
    {
        self::$form->corner->att('style',"padding: 0px; overflow: auto; height: ".(self::$__par['height'] - 82)."px;");
        //->Add(self::$Form->MainPanel);
        $foot = self::$form->Add(new Tag('div'))->Att('class','osy-form-footer');
        if (is_array(self::$__par['command']))
        {
            foreach(self::$__par['command'] as $cmd)
            { 
                $foot->Add($cmd);
            }
        }
        
        $foot->Add(new Tag('span'))->Att('style','visibility: hidden')
              ->Add(new Tag('iframe'))
              ->Att('id','msgbox')
              ->Att('name','msgbox')
              ->Att('debug',(self::get_par('frm_dbg') == '1' ? 'on' : 'off'))
              ->Att('style','height: 15px; width: 50px;')
              ->Add('');
    }
    
    private static function __event_exec__($evt)
    {
        if (key_exists($evt,self::$__par['trigger']))
        {
           foreach(self::$__par['trigger'][$evt] as $t_nam => $t_cod)
           {
               self::__trigger_exec__($t_nam,$t_cod);
           }
        }
    }
    
    /**
     ** @abstract Metodo che recupera i parametri necessari alla costruzione della form dal DB.
     **           Il recordset, una volta caricato nella variabile d'appoggio $fields_raw viene 
     **           scorso al fine di immagazzinare i dati in un nuova forma (maggiormente adatta
     **           ad essere lavorata). Viene anche caricato il valore dei campi (dal DB o dall'array
     **           globale $_REQUEST se disponibile).
     ** @private
     ** @return void
     **/
    private function __load_field_parameters__()
    {
        $sql = "SELECT   fld.o_nam            as fld_id,
                         fld.o_sty            as type,
                         case
                              when lng.p_vl is not null
                              then lng.p_vl
                              else fld.o_lbl 
                         end                as label,
                         t.p1               as typ_cst,
                         r.p_id             as prp_id,
                         r.p_vl             as prp_val,
                         rt.p2              as prp_cat
                FROM osy_obj           frm
                INNER JOIN osy_obj_rel rol ON (frm.o_own = rol.o_2)
                INNER JOIN osy_obj     fld ON (frm.o_id  = fld.o_own)
                INNER JOIN osy_res     t   ON (fld.o_sty = t.v_id AND t.k_id = 'osy-object-subtype')
                LEFT JOIN  osy_obj_prp r   ON (fld.o_id = r.o_id)
                LEFT JOIN  osy_res     rt  ON (r.p_id = rt.v_id AND rt.k_id = 'osy-propertie-field')
                LEFT JOIN  osy_obj_prp lng ON (fld.o_id = lng.o_id AND lng.p_id = 'language' AND lng.p_ord = ".env::$dba->cast('rol.p_1','integer').")
                WHERE      frm.o_id = ?  
                 AND       rol.o_1   = ?
                 AND       rol.o_3   = ?
                 AND       fld.o_sty not in ('field-constant')
                ORDER BY fld.o_id";
        $fields_raw = env::$dbo->exec_query($sql,array(env::$fid,'instance://'.env::$iid.'/',trim(env::$uid)),'ASSOC');

        $pkey = array();
        foreach($fields_raw as $i => $raw)
        {
            $key = $raw['fld_id'];
           /*
            * Questo controllo serve a verificare che il campo $key non abbia un proprio valore
            * nell'array globale $_REQUEST (vedi caso refresh pagina) in questo caso viene controllato
            * che dentro l'array values, riempito con i valori provenienti dal db, sia presente
            * un valore per il campo in corso. Se così è viene creato un elemento nell'array 
            * $_REQUEST con nome = al nome del campo in corso. (Per garantire il meccanismo 
            *  dell'aderenza).
            */
            if (!key_exists($key,self::$__fld))
            {
                self::$__fld[$key] = array($raw['typ_cst'],$raw['label'],$raw['fld_id'],-1,0,0,'prop'=>array());
            }
            self::$__fld[$key]['prop'][$raw['prp_cat']][] = array(strtolower($raw['prp_id']),$raw['prp_val']);
            switch($raw['prp_id'])
            {
                case 'check-label-view':
                                    self::$__par['check-label-view'][$key] = 'return ('.str_replace('TEST','',$raw['prp_val']).');';
                                    break;
                case 'db-field-connected':
                      if (key_exists('pkey',$_REQUEST) && is_array($_REQUEST['pkey']) && key_exists($raw['prp_val'],$_REQUEST['pkey']))
                      {
                          $_REQUEST[$key] = $_POST[$key] = $_REQUEST['pkey'][$raw['prp_val']];
                      }
                      if (!isset($_REQUEST[$key]) && is_array(self::$__par['values']) && key_exists($raw['prp_val'],self::$__par['values']))
                      {
                          self::$__par['values'][$key] = $_REQUEST[$key] = self::$__par['values'][$raw['prp_val']];
                      }
                      break;
                case 'position-row':
                      self::$__fld[$key][3] = $raw['prp_val'];
                      break;
                case 'position-column':
                      self::$__fld[$key][4] = $raw['prp_val'];
                      break;
                case 'position-panel-parent':;
                      self::$__fld[$key][5] = $raw['prp_val'];
                      break;
                case 'visibility-condition':
                      self::$__par['check-visibility'][$key] = 'return ('.str_replace('TEST','',$raw['prp_val']).');';
                      break;
            }
        }
        //var_dump(self::$__fld);
        //exit;
    }

    /**
     ** @abstract Metodo che si occupa di caricare i parametri dei diversi campi prelevati dal db
     **           all'interno dell'oggetto form al fine di instanziarli e visualizzarli.
     ** @private
     ** @return void
     **/
    private static function __load_fields_on_form__()
    {
        /*
         * Eseguo il codice php collegato agli elementi per testare se l'elemento e da visualizzare o meno.
         * Nel caso il test dia come risultato false il componente viene eliminato dall'array $__fld in modo
         * che non sia costruito.
         */
        foreach(self::$__par['check-visibility'] as $fid => $code)
        {
            if (!env::exec_string('',$code))  unset(self::$__fld[$fid]);
        }

        foreach(self::$__par['check-label-view'] as $fid => $code)
        {
            if (env::exec_string('',$code))
            {
                switch (self::$__fld[$fid][0])
                {
                    case 'date_box':
                                    $_REQUEST[$fid] = date_box::convert($_REQUEST[$fid]);
                                    break;
                }
                self::$__fld[$fid][0] = 'label';
            }
        }
        //var_dump(self::get_par('mode'));
        //exit;
        foreach(self::$__fld as $id => $f)
        {
          /*
           * Controllo che la classe che deve costruire il componente esiste.
           */
           if (!class_exists($f[0])){ echo "La classe {$f[0]} non è stata implementata"; continue; }
           
           if (self::get_par('mode') == 'VIEW')
           {
               switch($f[0])
               {
                 case 'panel':
                 case 'hidden_box':
                 case 'data_grid':
                 case 'tab':
                                 break;
                  default:
                           $f[0] = 'label';
                           break;
               }
           }
           /*Istanzio il componente*/
           self::$__fld[$id]['object'] = new $f[0]($f[2]);
           if (!is_object(self::$__fld[$id]['object'])){ continue; }
           self::$__fld[$id]['object']->label = $f[1];
           //self::$__fld[$id]['object'] = self::$form->put($f[0],$f[1],$f[2],$f[3],$f[4],$f[5]);
           //if (!is_object(self::$__fld[$id]['object'])) continue;

           if (! key_exists('prop',$f) || !is_array($f['prop'])) 
           {
                continue;
           }
           foreach($f['prop'] as $cat => $parameters)
           {
                 foreach($parameters as $i => $param)
                 {
                    if ($cat == 'attribute')
                    {
                        self::$__fld[$id]['object']->att($param[0],$param[1],true);
                        continue;
                    }
                    if ($param[0] == 'in-command-panel' && !empty($param[1]))
                    {
                        $f['prop']['in-command-panel'] = 1;
                    }
                    //Se al parametro è collegata una funziona la carica insieme al parametro nel componente in modo che sia eseguita.
                    $fnc = key_exists($param[0],self::$par_man) ? self::$par_man[$param[0]] : null;
                    self::$__fld[$id]['object']->par($param[0],$param[1],$fnc);
                 }
           }
           if (!empty($f['prop']['in-command-panel']))
           {
             self::$__fld[$id]['object']->att('class','extra');
             self::$__par['command'][] = self::$__fld[$id]['object'];
           }
            else
           {
             self::$form->put(self::$__fld[$id]['object'],$f[1],$f[2],$f[3],$f[4],$f[5]);
           }
            /*
             * Se mi arriva una richiesta ajax per aggiornare un singolo componente
             * fermo l'esecuzione, inviandola alla pagina che ne ha fatto richiesta
             * non appena il cliclo incontra il componente.
             */
            
            if (key_exists('ajax',$_REQUEST) && $_REQUEST['ajax'] == $f[2])
            {
                die('<div>'.self::$__fld[$id]['object'].'</div>');
            }
            //$o->trigger();
        }
        //Controllo se ci sono funzioni javascript da scrivere sulla pagina.
        if (is_array(self::$js_fnc))
        {
            $script = self::$page->head->add(tag::create('script'));
            $fnc = 'function osyview_init(){';
            foreach(self::$js_fnc as $name => $code)
            {
                if (!key_exists($name,self::$__fld)) continue;
                if (get_class(self::$__fld[$name]['object']) == 'check_box') $name = 'chk_'.$name;
                $fnc .= PHP_EOL."osycommand.eventpush(document.getElementById('".$name."'),'".$code[0]."',function(){\n";
                $fnc .= $code[1];
                $fnc .= PHP_EOL."});".PHP_EOL;
            }
            $fnc .= '}'.PHP_EOL;
            $fnc .= "if (window.addEventListener){ window.addEventListener('load',osyview_init);} else { window.attachEvent('onload', osyview_init);}";
            $script->add($fnc);
        }
    }

    /**
     ** @abstract Metodo che recupera i dati necessari a valorizzare i diversi campi della form.
     **           I dati vengono ripresi dal DB e nel successivo metodo __load_fields__ viene deciso
     **           se deve essere visualizzato il valore ripreso dal DB o quello presente nell'array
     **           globale $_REQUEST.
     ** @private
     ** @return void
     **/
    private static function __load_fields_value__()
    {
        if (!key_exists('pkey',$_REQUEST))
        {
            self::$__par['values'] = $_REQUEST; 
            return;
        }
         else
        {
            $pkey = env::$dbo->exec_query("SELECT d.p_vl as fld
                                           FROM osy_obj f
                                           inner join osy_obj_prp d ON (f.o_id = d.o_id AND d.p_id = 'db-field-connected')
                                           inner join osy_obj_prp p ON (f.o_id = p.o_id AND p.p_id = 'db-field-is-pkey')
                                           WHERE f.o_own = ?
                                             AND p.p_vl = '1'",array(env::$fid),'NUM');
            foreach($pkey as $fld)
            {
                if (!key_exists($fld[0],$_REQUEST['pkey']) || empty($_REQUEST['pkey'][$fld[0]]))
                {
                    self::$__par['values'] = $_REQUEST; 
                    return;
                }
            }
        }
        foreach($_REQUEST['pkey'] as $k => $v)
        {
            self::$form->add(new hidden_box("pkey[$k]"),'first')->att('value',$v)->att('class','req-reinit');
        }
        //Costruisco il filtro che mi permettera di recuperare i dati da mostrare nei campi dal db
        $str_whr = '';
        foreach($_REQUEST['pkey'] as $k => $v)
        {
		   $str_whr .= empty($str_whr) ? '' : ' AND ';
           $str_whr .= " {$k} = '{$v}'";
		}
        if (empty(self::$__par['db-table-linked']))
        {
            return;
            die('La propiet&agrave; db-table-linked della form '.env::$fid.' &egrave; vuota. Impossibile proseguire.');
        }
        self::$__par['values'] = env::$dba->exec_unique("SELECT * 
                                                         FROM  ".self::$__par['db-table-linked']." 
                                                         WHERE ".$str_whr,null,'ASSOC');
        if (!is_array(self::$__par['values']))
        {
            $_REQUEST['pkey'] = $_POST['pkey'] = null;
        }
    }
    
    private static function __trigger_exec__($name,$code)
    {
        if ($function = @create_function('$Db',$code))
          {
              if ($resp = $function(env::$dba))
              {
                //$GLOBALS['osy://form/error'][] = $err;
                return $resp;
              }
          }
           else
          {
              $e = error_get_last();
              $error  = "TRIGGER : {$name}\n";
              $error .= "EVENT   : {$event}\n";
              $error .= "LINE    : {$e['line']}\n";
              $error .= "MESSAGE : {$e['message']}\n";
              echo nl2br($error);
              return $error;
          }
    }
    
    private static function __trigger_load__()
    {
        self::$__par['trigger'] = array();
        $sql = "SELECT trg.o_id  as tid,
                       trg.o_nam AS trg,
                       cod.p_vl  AS cod,
                       mom.p_id  AS evt,
                       CASE
                              WHEN mom.p_id = 'library' THEN 0
                              ELSE 1
                       END ord
                FROM       osy_obj trg 
                INNER JOIN osy_obj_prp ctx ON (trg.o_id = ctx.o_id)
                INNER JOIN osy_obj_prp cod ON (trg.o_id = cod.o_id)
                INNER JOIN osy_obj_prp mom ON (trg.o_id = mom.o_id)
                WHERE trg.o_typ = 'trigger'
                AND   cod.p_id  = 'code'
                AND   mom.p_vl  = 'yes'
                AND   ctx.p_vl  = 'form'
                AND   trg.o_own = ?
                ORDER BY 4,1";
        $rs = env::$dbo->exec_query($sql,array(env::$fid));
        foreach ($rs as $rec)
        { 
            self::$__par['trigger'][$rec['evt']][$rec['trg']] = $rec['cod'];
            if (!empty($_REQUEST['ajax'])
                && ($rec['evt'] == 'form-ajax') 
                && ($_REQUEST['ajax'] == sha1($rec['tid'])))
            {
                $rsp = self::__trigger_exec__($rec['trg'],$rec['cod']);
                if (!empty($rsp)) { env::resp('error',$rsp);  }
                echo env::reply();
                exit;
            }
             elseif($rec['evt'] == 'library')
            {
                $rsp = self::__trigger_exec__($rec['trg'],$rec['cod']);
            }
        }
        //var_dump(self::$__par['trigger']);
    }

    public static function add_par_man($par,$fnc)
    {
        if (!is_callable($fnc)) die("[ERRORE] - Al parametro {$par} devi associare una funzione anonima!!!!");
        self::$par_man[$par] = $fnc;
    }

    public static function get()
    {
        static::__build__();
        self::$form->status = array_key_exists('osy://form/status',$GLOBALS) ? implode(' + ',$GLOBALS['osy://form/status']) : '';
        if (!empty($GLOBALS['osy://form/error']))
        {
            self::$page->body->add(tag::create('div'))->att('class','hidden')->att('id','error')->add(implode('<br>',$GLOBALS['osy://form/error']));
        }
        return self::$page->get();
    }
    
    public static function field($k,$obj=true)
    {
        return self::$__fld[$k]['object'];
    }
    
    public static function get_par($key)
    {
        return key_exists($key,self::$__par) ? self::$__par[$key] : null;
    }
    
    public static function set_par($key,$val)
    {
        self::$__par[$key] = $val;
    }
}

/*
 * TODO
 *
 * Questa parte va sposta dentro il database nella tabella che contiene i parametri.
 *
 */
//Gestore del paramentro database-field
/*osy_view::add_par_man('datasource_sql',function($par,$val,&$self)
{
    $self->par('datasource-sql',env::ReplaceVariable($val));
});*/
//Gestore del paramentro database-field
osy_view::add_par_man('database-field',function($par,$val,$self)
{
     if (key_exists('pkey',$_REQUEST) && is_array($_REQUEST['pkey']) && key_exists($val,$_REQUEST['pkey']))
     {
        $_REQUEST[$self->id] = $_POST[$self->id] = $_REQUEST['pkey'][$val];
     }
});
//Gestore del paramentro colspan
osy_view::add_par_man('height',function($key,$val,$self)
{
   if (strpos($val,'px') === false) $val .= 'px';
   $self->att('style',"height : {$val};",true);
});
//Gestore del paramentro colspan
osy_view::add_par_man('cell-class',function($key,$val,$self)
{
    $self->man('onbuild','cell-class',function($val,$self)
    {
        if ($self->parent)
        {
            $self->parent->att('class',$val);
        }
    });
});
//Gestore del paramentro colspan
osy_view::add_par_man('colspan',function($key,$val,$self)
{
    $self->man('onbuild','colspan',function($val,$self)
    {
        $cel = $self->closest('td,th');
        if (!is_object($cel)) return;
        $cel->att('colspan',$val);
    });
});
//Gestore del paramentro rowspan
osy_view::add_par_man('rowspan',function($par,$val,$self)
{
    $self->man('onbuild','rowspan',function($val,$self)
    {
        $cel = $self->closest('td,th');
        if (!is_object($cel)) return;
        $cel->att('rowspan',$val);
    });
});
//Gestore del paramentro onclick
osy_view::add_par_man('onclick',function($par,$val,$self)
{
   if (!empty($val))
   { 
      osy_view::$js_fnc[$self->id] = array('click',$val);
   }
});

osy_view::add_par_man('onclick-trigger',function($par,$val,$self)
{
   if (!empty($val))
   { 
      osy_view::$js_fnc[$self->id] = array('click',"osyview.exec(this,'".sha1($val)."')");
   }
});
//Gestore del paramentro onchange
osy_view::add_par_man('onchange',function($par,$val,$self)
{
   if (!empty($val))
   { 
      osy_view::$js_fnc[$self->id] = array('change',$val);
   }
});
//Gestore del paramentro pos_cellwidth
osy_view::add_par_man('cell-width',function($key,$val,$self)
{
    $self->man('onbuild','cell-width',function($val,$self)
    {
        $o = $self->closest('td,th');
        if (empty($o)) return;
        $o->att('style',"width: $val;",true);
    });
});
//Gestore del paramentro pos_cellheight
osy_view::add_par_man('cell-height',function($par,$val,$self) 
{
    $self->man('onbuild','cell-height',function($val,$self)
    {
        $o = $self->closest('td,th');
        if (!empty($o)) $o->att('style',"height : {$val};",true);
        $self->att('style',"height : {$val};",true);
        $self->par('height',$val[0]);
    });
});
//Gestore del paramentro pos_cellwidth
osy_view::add_par_man('cell-style',function($key,$val,$self)
{
    $self->man('onbuild','cell-style',function($val,$self)
    {
       $self->parent->att('style',$val,true);
    });
});

//Gestore del paramentro foreign-key
osy_view::add_par_man('foreign-key',function($key,$val,$self)
{
    $context = array('fkey','par','osy');
    foreach($context as $ctx)
    {
        if (key_exists($ctx,$_REQUEST) && !empty($_REQUEST[$ctx][$val]))
        {
            $_REQUEST[$self->id] = $_REQUEST[$ctx][$val];
            return;
        }
    }
});

//Gestore del paramentro default value
osy_view::add_par_man('default-value',function($key,$val,$self)
{
    $_REQUEST[$self->id] = $val;
});
?>
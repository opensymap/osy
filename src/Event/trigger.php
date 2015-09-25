if (OSY_VER >= 3){
  env::$page->add_css('/lib/codemirror-4.2/lib/codemirror.css');
  env::$page->add_script('/lib/codemirror-4.2/lib/codemirror.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/xml/xml.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/javascript/javascript.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/clike/clike.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/php/php.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/css/css.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/sql/sql.js');
  env::$page->add_script('/lib/codemirror-4.2/mode/htmlmixed/htmlmixed.js');  
} else {
  $response->addCss('/lib/codemirror-2.20/lib/codemirror.css');
  $page->add_script('/lib/codemirror-2.20/lib/codemirror.js');
  $page->add_script('/lib/codemirror-2.20/mode/xml/xml.js');
  oform::$page->add_script('/lib/codemirror-2.20/mode/javascript/javascript.js');
  oform::$page->add_script('/lib/codemirror-2.20/mode/css/css.js');
  oform::$page->add_script('/lib/codemirror-2.20/mode/mysql/mysql.js');
  oform::$page->add_script('/lib/codemirror-2.20/mode/htmlmixed/htmlmixed.js');
}
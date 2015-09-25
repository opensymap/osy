<?php 
namespace Opensymap\Helper;

function sendEmail($from,$a,$subject,$body,$html=false,$header=false)
{
    $head = "From: $from\r\n".
            "Reply-To: $from\r\n".
            "X-Mailer: PHP/".phpversion()."\n";
    if (!empty($header)) {
        $head .= $header;
    }
    if ($html) {
      $head .= "MIME-Version: 1.0\n";
      $head .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
      $head .= "Content-Transfer-Encoding: 7bit\n\n";
    }
    return mail($a,$subject,$body,$head," -f info@spinit.it");
}

function mailDebug($msg,$trace=true)
{
    if (is_array($msg)) {
        $msg = print_r($msg,true);
    }
    $htm = '<html>';
    $htm .= '<body>';
    if ($trace) {
        $htm .= '<h1>Debug backtrace</h1>';
        $htm .= '<table style="background-color: #eee;">';
        $htm .= '<tr><th style="background-color: white; padding: 5px;">Step</th>'
                . '<th style="background-color: white; padding: 5px;">File</th>'
                . '<th style="background-color: white; padding: 5px;">Line</th>'
                . '<th style="background-color: white; padding: 5px;">Class</th>'
                . '<th style="background-color: white; padding: 5px;">Method/Function</th>'
                . '<th style="background-color: white; padding: 5px;">Params</th></tr>';
        $trc = debug_backtrace();

        foreach(array_reverse($trc) as $key => $rec) {
            $htm .= '<tr>';
            $htm .= '<td style="background-color: white; padding: 5px;">'.($key).'</td>';
            foreach($rec as $k => $v){
                 $htm .= '<td style="background-color: white; padding: 5px;">'.($v ? $v : '&nbsp;').'</td>';
            }
            $htm .='</tr>';
        }
        $htm .= '</table>';
    }
    $htm .= '<pre>';        
    $htm .= $msg;
    $htm .= '</pre>';
    self::sendEmail('info@spinit.it','pietro.celeste@gmail.com','Mail debug - '.date('Y-m-d H:i:s'),$htm,true);
}

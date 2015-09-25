<?php
namespace Opensymap\Helper;

use Opensymap\Osy;

class HelperModel
{
    public function preSaveDateBox($field, $request)
    {
        if (empty(!$_REQUEST[$field['name']])) {
            $arrDate = explode('/',$_REQUEST[$field['name']]);
            if (count($arrDate) == 3) {
               $_REQUEST[$field['name']] = $arrDate[2].'-'.$arrDate[1].'-'.$arrDate[0];
            }
        }
    }
    
    public function preSaveFileBox($field, $request)
    {
        $fieldName = $field['name'];
        if (empty($_FILES[$fieldName]['name'])) {
            if (empty($field['required'])) {
                unset($_REQUEST[$fieldName]);
            } else {
                //Settaggio necessario nel caso il campo sia not null
                $_REQUEST[$fieldName] = null;
            }
            return;
        } elseif ($field['subtype'] == 'field-blob') {
            $field['store-in-blob'] = 1;
        }
        //Se &egrave; necessario immagazzinare il file in un blob recupero il file e lo inserisco in $_REQUEST
        if (!empty($field['store-in-blob'])) {
            $_REQUEST[$fieldName] = file_get_contents($_FILES[$fieldName]['tmp_name']);
            $_REQUEST[$fieldName.'_nam'] = $_FILES[$fieldName]['name'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $_REQUEST[$fieldName.'_typ'] = $finfo->buffer($_REQUEST[$fieldName]);
            $_REQUEST[$fieldName.'_dim'] = strlen($_REQUEST[$fieldName]);
        } else {
            //Inserisco in $_REQUEST il filepath e sposto il file temporaneo nella directory definitiva
            $_REQUEST[$fieldName] = HelperImage::getUniqueFileName(UPLOAD_PATH.$_FILES[$fieldName]['name']);
            move_uploaded_file($_FILES[$fieldName]['tmp_name'], $_REQUEST[$fieldName]);
        }
    }

    public function preSaveConstant($field, $request)
    {
        //define costant value
        $constant = array('UID' => $request->get('instance.userid'),
                          'CURRENT_DATE'=> date('Y-m-d'),
                          'CURRENT_TIME'=> date('H:i:s'),
                          'CURRENT_DATETIME'=> date('Y-m-d H:i:s'),
                          'UPLOAD_PATH' => OSY_VAR.'/upload/');
        $add = true;
        if (!empty($field['visibility-condition'])) {
            eval('$add = '.str_replace('TEST', '', $field['visibility-condition']).';');
        }
        if ($add) {
            $_REQUEST[$field['name']] = $constant[$field['constant']];
        }
    }

    public function preSaveImageBox($field, $request)
    {
        $fieldName = $field['name'];
        if (empty($_FILES[$fieldName]['name'])) {
            if (empty($field['required'])) {
                unset($_REQUEST[$fieldName]);
            } else { //Settaggio necessario nel caso il campo sia not null
                $_REQUEST[$fieldName] = null;
            }
            return;
        }
        if (array_key_exists('max-dimension', $field) && !empty($field['max-dimension'])) {
            $dimension = explode(',', $field['max-dimension']);
            HelperImage::resize($_FILES[$fieldName]['tmp_name'], $dimension[0], $dimension[1]);
        }
        //Se &egrave; necessario immagazzinare il file in un blob recupero il file e lo inserisco in $_REQUEST
        if (!empty($field['store-in-blob'])) {
            $_REQUEST[$fieldName] = file_get_contents($_FILES[$fieldName]['tmp_name']);
            $_REQUEST[$fieldName.'_nam'] = $_FILES[$fieldName]['name'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $_REQUEST[$fieldName.'_typ'] = $finfo->buffer($_REQUEST[$fieldName]);
            $_REQUEST[$fieldName.'_dim'] = strlen($_REQUEST[$fieldName]);
            return;
        } 
        //Inserisco in $_REQUEST il filepath e sposto il file temporaneo nella directory definitiva
        $_REQUEST[$fieldName] = HelperImage::getUniqueFileName(UPLOAD_PATH.$_FILES[$fieldName]['name']);
        move_uploaded_file($_FILES[$fieldName]['tmp_name'], $_REQUEST[$fieldName]);
    }
}

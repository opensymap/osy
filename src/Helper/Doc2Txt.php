<?php
/* 
this class is used to convert any doc,docx file to simple text format.

author: Gourav Mehta
author's email: gouravmehta@gmail.com
author's phone: +91-9888316141
*/ 
namespace Opensymap\Utility;

class Doc2Txt 
{
    private $filename;
    
    public function __construct($filePath)
    {
        $this->filename = $filePath;
    }
    
    public function read_doc2() 
    {
        $fileHandle = fopen($this->filename, "r");
        $word_text = @fread($fileHandle, filesize($this->filename));
        $line = "";
        $tam = filesize($this->filename);
        $nulos = 0;
        $caracteres = 0;
        /*for($i=1536; $i<$tam; $i++)
        {
            $line .= $word_text[$i];

            if( $word_text[$i] == 0){
                $nulos++;
            } else {
                $nulos=0;
                $caracteres++;
            }
            if( $nulos>1996)
            {   
                break;  
            }
         }*/

    //echo $caracteres;

        $lines = explode(chr(0x0D),$line);
        //$outtext = "<pre>";
    
        $outtext = "";
        foreach($lines as $thisline)
        {
            $tam = strlen($thisline);
            if( !$tam )
            {
                continue;
            }
    
            $new_line = ""; 
            for($i=0; $i<$tam; $i++)
            {
                $onechar = $thisline[$i];
                if( $onechar > chr(240) )
                {
                    continue;
                }
    
                if( $onechar >= chr(0x20) )
                {
                    $caracteres++;
                    $new_line .= $onechar;
                }
    
                if( $onechar == chr(0x14) )
                {
                    $new_line .= "</a>";
                }
    
                if( $onechar == chr(0x07) )
                {
                    //$new_line .= "\t";
                    if( isset($thisline[$i+1]) )
                    {
                        if( $thisline[$i+1] == chr(0x07) )
                        {
                            $new_line .= "\n";
                        }
                    }
                }
            }
            //troca por hiperlink
            $new_line = str_replace("HYPERLINK" ,"<a href=",$new_line); 
            $new_line = str_replace("\o" ,">",$new_line); 
            $new_line .= "\n";
    
            //link de imagens
            $new_line = str_replace("INCLUDEPICTURE" ,"<br><img src=",$new_line); 
            $new_line = str_replace("\*" ,"><br>",$new_line); 
            $new_line = str_replace("MERGEFORMATINET" ,"",$new_line); 
    
    
            //$outtext .= nl2br($new_line);
            $outtext .= trim($new_line)."\n";
        }

     return $outtext;
    } 

    private function read_doc() {
        $fileHandle = fopen($this->filename, "r");
        $line = @fread($fileHandle, filesize($this->filename));   
        $lines = explode(chr(0x0D),$line);
        $outtext = "";
        foreach($lines as $thisline)
          {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE)||(strlen($thisline)==0))
              {
              } else {
                $outtext .= $thisline."\n ";
              }
          }
         $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/"," ",$outtext);
        return $outtext;
    }
    
    private function read_docx(){

        $striped_content = '';
        $content = '';

        $zip = zip_open($this->filename);

        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {

            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }// end while

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);

        return $striped_content;
    }
    
    public function convertToText($extforce='doc') {
    
        if(isset($this->filename) && !file_exists($this->filename)) {
            return "File Not exists";
        }
        
        $fileArray = pathinfo($this->filename);
        $file_ext  = empty($fileArray['extension']) ? $extforce : $fileArray['extension'];
        switch($file_ext)
        {
            case 'doc' :
                         return $this->read_doc();
                         break;
            case 'docx' :
                         return $this->read_docx();
                         break;
        } 
        return "Invalid File Type";
    }
}

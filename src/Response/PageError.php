<?php
namespace Opensymap\Response;

/**
     *  Send page error
     *
     *  @param string $errNum number of error message
     *  @param string $errMsg message to show
     *  @param string $form    ???
     *
     *  @return void
     */
class PageError extends PageHtmlResponse
{
    private $errNo;
    private $message;
    
    public function __construct($errNo, $message)
    {
        parent::__construct();
        $this->errNo = $error;
        $this->message = $message;
        $serverProtocol = $_SERVER["SERVER_PROTOCOL"];
        $request = $_REQUEST;
        header($serverProtocol." ".$errNum." ".$message, true, $errNum);
        $this->setHeader('Status',$errNo." ".$message);
        
        $this->setTitle('Opensymap &raquo; Error '.$errNo);
        $this->getHead()->add(
            '<style>
                body,td,th {font-family: Arial;}
                body       {background-color: white;}
            </style>'
        );
        $this->addBody(
            '<br/><br/> 
            <table align="center">
                <tr>
                    <th>OPENSYMAP</th>
                </tr>
                <tr>
                    <td><br/><br/> Error number: '.$errNo.'</td>
                </tr>
                <tr>
                    <th><br/>'.$message.'<br/><br/></th>
                </tr>                    
                <tr>
                    <td>'.print_r($request, true).'</td>
                </tr>
            </table>'
        );
    }
}
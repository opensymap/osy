<?php
namespace Opensymap\Router;

use Opensymap\Osy;

class Router
{
    public $dbo;
    private $request;
    
    public function __construct($dbo, $request)
    {
        $this->dbo = $dbo;
        $this->request = $request;
    }
    
    /**
     * Decode the route content in the route
     *
     * @return bool (true = route decoded, false no route on db)
     */
    public function getInstance()
    {
        $requestUri = $this->request->get('server.REQUEST_URI');
        $serverName = $this->request->get('server.SERVER_NAME');
        list($requestUri,) = explode('?',$requestUri);
        $uri = in_array($uri, array('/','/index.php')) ? $serverName : $serverName.$requestUri;
        $instance = $this->dbo->exec_unique(
            "SELECT i.o_nam as id, 
                    i.o_lbl as title,
                    c.p_vl as css,
                    case 
                        when o.o_typ = 'form' 
                        then r.o_2 
                        else null 
                    end as formid
            FROM osy_obj_rel r 
            INNER JOIN osy_obj     o ON (r.o_2 = o.o_id) 
            INNER JOIN osy_obj     i ON (r.o_1 = i.o_id)  
            LEFT JOIN osy_obj_prp  c ON (i.o_id = c.o_id AND c.p_id = 'css-path') 
            WHERE ? LIKE CONCAT(r.p_1,'%') OR r.p_1 = '*' 
            ORDER BY 
                CASE 
                    WHEN o.o_typ IN ('form','page') 
                        THEN LENGTH(r.p_1)
                    WHEN r.p_1 = '*' 
                        THEN 0
                    ELSE 1 
                END DESC",
            array($uri.'%'),
            'BOTH'
        );
        $instance['uri'] = $uri;
        return $instance;
    }
}

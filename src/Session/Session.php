<?php
namespace Opensymap\Session;

class Session extends \SessionHandler
{
    private $key;
    private $dbo;
    
    public function __construct($key, $dbo)
    {
        $this->key = $key;
        $this->dbo = $dbo;
    }

    public function dummy()
    {
        return true;
    }

    public function destroy($id)
    {
        $this->dbo->exec_cmd("DELETE FROM osy_ses WHERE sid = ?", array($id));
    }

    public function gc($max)
    {
        $validityLimit = time() - $max;
        $this->dbo->exec_cmd("DELETE FROM osy_ses WHERE dat_upd < ?", array($validityLimit));
    }

    public function read($id)
    {
        return $this->dbo->exec_unique("SELECT dat FROM osy_ses WHERE sid = ?", array($id));
    }

    public function write($id, $data)
    {
        $this->dbo->exec_cmd("REPLACE INTO osy_ses (sid, dat_upd, dat) VALUES (?, ?, ?)",array($id, time(), $data));
        return true;
    }
    
    /**
     * Check if user is authenticated
     *
     * @return void
     */
    public function isAuthenticated()
    {
        if (empty($this->key)) {
            return false;
        }
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        $serverNm = $_SERVER['SERVER_NAME'];
        /*Controllo che la sessione passata come par sia presente sulla tabella.*/
        $usr = $this->dbo->exec_unique(
            'SELECT usr_id,ses_ist
             FROM   osy_log
             WHERE  ses_id = ?
               AND  ses_ip = ?
               AND  hst_id = ?',
            array($this->key, $remoteIp , $serverNm)
        );
        if ($usr) {
            return $usr[0];
        }
        return false;
    }
}

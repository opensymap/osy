<?php
namespace Opensymap\Event;

use Opensymap\Osy;

class Dispatcher
{
    private $dbc;
    private $listeners = array();
    private $context;
    private $response;
    private $errorApp = array();
    
    public function __construct($dbc, $response = null, $context = null)
    {
        $this->dbc = $dbc;
        $this->response = $response;
        $this->context = $context;
    }
    
    /**
     * Store function to exec on dispatch Event
     *
     * @param string $listener String contains event id to dispatch
     *
     * @return Listener $listener
     */
    public function addListener($listener)
    {
        $eventId = $listener->getEvent();
        $this->listeners[$eventId][] = $listener;
        return $listener;
    }
    
    /**
     * Exec all listener associated to event
     *
     *  @param string $dispatchEvent    Id event
     *  @param string $dispatchListener Name of listener to dispatch (optional)
     *  @param bool   $dispAtEnd        dispatch the message and exit at end
     *
     * @return integer number of execution
     */
    public function dispatch($dispatchEvent, $dispatchListener = null, $eventOwner = null)
    {
        $nexec = 0;
        $neven = count($this->listeners[$dispatchEvent]);
        if (!empty($this->listeners[$dispatchEvent])) {
            foreach ($this->listeners[$dispatchEvent] as $listener) {
                if (!empty($dispatchListener) && ($dispatchListener != $listener->getSha1Id())) {
                    continue;
                }
                if (!empty($eventOwner) && ($eventOwner != $listener->getOwner())) {
                    continue;
                }
                try {
                    if ($errorMessage = $listener->execute($this->dbc, $this->response, $this->context)) {
                        $this->addError( $errorMessage );
                    }
                    $nexec++;
                } catch (Exception $e) {
                    $this->addError($e->getMessage());
                }
            }
        }
        return $nexec;
    }
    
    public function setResponse($response)
    {
        $this->response = $response;
        if (!empty($this->errorApp)) {
            foreach($this->errorApp as $k => $v){
                $this->response->error($k,$v);
            }
        }
    }
    
    public function setContext($context)
    {
        $this->context = $context;
    }
    
    public function addError($errorMessage)
    {
        $this->errorApp[] = $errorMessage;
        if ($this->response && is_object($this->response)) {
            $this->response->error('alert', $errorMessage);
        }
    }
}

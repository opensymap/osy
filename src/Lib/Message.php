<?php
namespace Opensymap\Lib;

class Message 
{
    private $envelope = array();
    
    public function __construct($recipient, $message = null, $sender = null)
    {
        $this->envelope = array (
            'id'     => sha1($sender.$recipient.$message),
            'sender' => $sender,
            'recipient' => $recipient,
            'message' => $message
        );
    }
    
    public function __get($key)
    {
        return array_key_exists($key,$this->envelope) ? $this->envelope[$key] : null;
    }
    
    public function setMessage($message)
    {
        $this->envelope['message'] = $message;
    }
    
    public function get()
    {
        return $this->envelope;
    }
    
    public function __toString()
    {
        return empty($this->envelope['message']) ? 'Message is empty' : $this->envelope['message'];
    }
    
    public function __call($remoteMethod, $arguments)
    {
        $this->envelope['recipient'] = $remoteMethod;
        $this->envelope['message'] = $arguments;
    }
}

<?php
namespace Models\FrontEnd\WebSocket;

class MyUser extends WebSocketUser {
  public $myId;
  function __construct($id, $socket, $myId=1) {
    parent::__construct($id, $socket);
    $this->myId = $myId;
  }
}
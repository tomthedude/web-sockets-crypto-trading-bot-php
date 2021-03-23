#!/usr/bin/env php
<?php
require_once ('init.php');


$filesCls = new Models\BotComponent\FilesWork();

$filesCls->register_pid_to_file("I am WebSocketServer");
$filesCls->addContent("phpBot///WebSocketServer/// WebSocket Server Started.");


//get this server ip
$externalContent = file_get_contents('http://checkip.dyndns.com/');
preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
$_SERVER['SERVER_ADDR'] = $m[1];



$echo = new \Models\FrontEnd\WebSocket\EchoServer($_SERVER['SERVER_ADDR'],"1337",1048576);
while(true){
  try {
  $echo->run();
}
catch (Exception $e) {
  $echo->stdout($e->getMessage());
}  
}

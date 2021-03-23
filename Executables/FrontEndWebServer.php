<?php
namespace Executables;
//require_once ('init.php');
if(!file_exists("logs/".getmypid().".pid.log")){
    file_put_contents("logs/".getmypid().".pid.log", "I am webserver");
// $filesCls = new Models\BotComponent\FilesWork();
// $filesCls->register_pid_to_file("I am WebServer");
// $filesCls->addContent("phpBot///WebServer/// Web Server Started.");
}
return false;
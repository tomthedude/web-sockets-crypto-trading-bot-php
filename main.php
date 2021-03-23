<?php

//Main file, here it all starts
$kill = function($pid){ return stripos(php_uname('s'), 'win')>-1 
                        ? exec("taskkill /F /PID $pid") : exec("kill -9 $pid");
};
require_once('init.php');

$filesCls = new Models\BotComponent\FilesWork();

$filesCls->register_pid_to_file("I am main.php instance");
echo $colors->info("started main.php".PHP_EOL, "purple", "yellow"); 
//echo $colors->getColoredString(, "red", "black");//kills color
//echo $colors->getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow"); regular info
function remove_pid_from_file($pid,$file){
    global $settings;
    $pids = json_decode(file_get_contents($file),true);
    unset($pids[$pid]);
    file_put_contents($file,json_encode($pids));
} 
register_shutdown_function('remove_pid_from_file',getmypid(),$bot_settings->all_pids_file);

$flag = true;
//fire up bot_inst.php
//create a loop for contsant run.

/*start loop{
sleep(60) // cehck every minute(or less..?) for certain things.. like:

*is waiting for sell signal already sold (api calls to binance to get latest closed orders, see if uuid is therethere)? if so, change status in db

*check if bot instance is still running (grab pid from all_pids.json file){
if not start a new instance.
}

*check if something major changed (config file.. coins etc.. and update the bot(terminate and reboot Executables".DIRECTORY_SEPARATOR."BotInstance.php))

//check if there is terminate command waiting in terminate.me file, if so, kill bot pid, and all other pids and exit.


// if run simultaion command, start bot instance with simulation settings
}

*/


//get this server ip
$externalContent = file_get_contents('http://checkip.dyndns.com/');
preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
$_SERVER['SERVER_ADDR'] = $m[1];
$finished_signals=0;

$pids_cls = new Models\PIDs($bot_settings->all_pids_file);
$filename = $bot_settings->all_pids_file;
$main = new Models\Console\Main();

//start ws
$main->bgExec("php Executables".DIRECTORY_SEPARATOR."FrontEndWebSocketServer.php");//
echo 'before web server start';
//start web server
$main->bgExec("php -S ".$_SERVER['SERVER_ADDR'].":8080 -t interface/ Executables".DIRECTORY_SEPARATOR."FrontEndWebServer.php > /dev/null 2>&1");


//start bot instances
echo 'before instances start';
$main->startInstances();
echo 'after instances start, before loop';
$timer = 0;
while(true){
    //echo "loop start, loops: $timer";
    //check for termination file
    $main->checkForProgramEnd();
    //echo "checked program end";
    //get messages from bots pids and print;
    $main->printToConsoleFromBots();
    //echo "checked print to console.";
    //execute commands from font-end
    $main->execCommandsFromFE();
    //echo "chekced print to console";
    //create_instances on the fly
    $main->startOnTheFlyInst();
    //echo "check on the fly instnaces";
    // add btc balance to db each 30 minutes
	$main->addBtcBalanceToDBPeri();
	//echo "some db in main";
    if($timer > 30 && !$main->checkSocketStatus()){//reload websocket if connection dropped
        foreach($pids_cls->pids as $pid=>$name){
            if(strpos(strtolower($name),"websocketserver")!==false){
                $kill($pid);
                $main->remove_pid_from_file($pid, $filename);
            }
        }
        sleep(1);
        $main->bgExec("php Executables".DIRECTORY_SEPARATOR."FrontEndWebSocketServer.php");
        $timer=0;
        
    } 
    //$timer++;
    sleep(0.5);
    gc_collect_cycles();
    
}

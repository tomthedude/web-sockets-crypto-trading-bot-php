<?php
//killl all php.  ps -efw | grep php | grep -v grep | awk '{print $2}' | xargs kill
require_once('init.php');
use Models\Exchanges\Bittrex\SignalR;


//register_shutdown_function('remove_pid_from_file',getmypid(),$bot_settings->all_pids_file);


$cli_args = [];
foreach($argv as $cli_setting){
    $temp = explode("=",$cli_setting);
    if(isset($temp[1])){
        $cli_args[$temp[0]] = $temp[1];
        
    }
}

$filesCls = new Models\BotComponent\FilesWork();

//put all php runtime wranings and erros into pid.log

$simulation_finished_trades = [];
$latest_scan_results=[];
$api;
$client;
$count = 0;
$empry_arr_count = 0;
print_r($cli_args);

//to web: i am working on this coins: cins array, with these starts: startegy latest scan score and status (2/3 2/4 etc..)


//["BNBBTC","NEOBTC","ETHBTC","XLMBTC","XRPBTC","ARKBTC","TUSDBTC","GASBTC","ONGBTC","LTCBTC","HOTBTC","STORJBTC","KMDBTC","MTLBTC","ADABTC"];

$bot = new Models\BotComponent\Bot();
$cmnds = new Models\BotComponent\Commands();
$bot->init();
$cli_args = $bot->cli_args;
$filesCls = $bot->filesCls;
$cmnds = $bot->cmnds;
$api = $bot->getApi();
$bot->run();
// bwlo this point move to class bot
//count is used to reload cinfig file from time to time, when count is 1000000, updates are bieng made;
$filesCls->debug(var_dump($api));
if($bot->xchnage!=="bittrex" && $api->loop != null){ // 15/05/2020 - not working needs to be fixed and into $bot->run()
$api->loop->addPeriodicTimer(60*60, function() use (&$bot,$api) {
	global $filesCls;
	             //$api->loop->stop(); 
                //$bot->fillCoinsArr();
                //$filesCls->addContent("Reloaded coins (once an hour)");
}); //reload coins once an hour - for now only binance, bittrex start to run once more each stop, after 3 hours each rade is checked 3 times?
}
?>
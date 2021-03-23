<?php

ini_set( 'trader.real_precision', '8' );
ini_set( 'max_execution_time', -1);

require 'vendor/autoload.php';
require 'autoload.php';
//add verbose mode, if true, output more data to pid files etc..
//delete all log files from log dir on OK shutdown

$colors = new \Models\Console\TerminalColors();
$data = file_get_contents('my_conf.json');
$settings = json_decode($data);
$bot_settings = $settings->bot_settings;
$instances_on_start = json_decode(json_encode($settings->bot_instances_on_start),true);
//print_R($instances_on_start);
$user_settings = $settings->user_data;

$on_the_fly_file = "instancesOnTheFly.json";


$GLOBALS['active_bots_file'] = $settings->bot_settings->active_bots_file;
//print_r($settings);


use Medoo\Medoo;

// Initialize
$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $bot_settings->db->db_name,
    'server' => $bot_settings->db->hostname,
    'username' => $bot_settings->db->username,
    'password' => $bot_settings->db->password
]);

$db_stat  = $database->info();
if($db_stat['server']===null || $db_stat['server']===""){
		die();
}

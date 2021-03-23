	<?php

require_once("init.php");


/*$database->insert('account', [
    'user_name' => 'foo',
    'email' => 'foo@bar.com'
]);

$data = $database->select('account', [
    'user_name',
    'email'
], [
    'user_id' => 50
]);*/



//echo $database->count('signals');
$filesCls = new Models\BotComponent\FilesWork();

//this code supposed to run 1 time on main.php startup
$active_s = $database->select('signals', "*",['status' => 'active']);
foreach($active_s as $signal ){
    $active_signals[$signal["strategy"]][$signal['market']][$signal['exchange']] = $signal;
    
}
file_put_contents("active_unass_signals.json",json_encode($active_signals));
///


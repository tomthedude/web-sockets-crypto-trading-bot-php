<?php
function bgExec($cmd) {
        if(stripos(php_uname('s'), 'win')>-1){
            echo "hi";
            exec("start /B ". $cmd ); //not working well on windows
        }else {
            exec($cmd . " > /dev/null &"); 
        }
    }
    
echo "What you wish to do?".PHP_EOL."1. Start Bot in background".PHP_EOL."2. Kill Bot";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
switch(trim($line)){
    case '1':
        bgExec("php main.php");
	echo "started bot, bye";
        break;
    case '2':
        file_put_contents("terminate.all","kill all");
        break;
}
fclose($handle);
?>

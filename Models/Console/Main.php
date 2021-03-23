<?php
namespace Models\Console;

class Main{
    private $bot_messages;
	public $binance_api;
	public $bittrex_api;
	private $db;
    
    function __construct(){
		global $database,$user_settings;
        $this->bot_messages = [];
		$this->binance_api = null;
		$this->bittrex_api = null;
		if(isset($user_settings->binance->bnkey) && $user_settings->binance->bnkey !== ""){
			$this->binance_api = new \Binance\API($user_settings->binance->bnkey,$user_settings->binance->bnsecret);		
		}
		if(isset($user_settings->bittrex->btkey) && $user_settings->bittrex->btkey !== ""){
			$this->bittrex_api = new \Models\Exchanges\Bittrex\ClientBittrexAPI ($user_settings->bittrex->btkey,$user_settings->bittrex->btsecret);
		}
		$this->db = $database;
        
    }
    
    public function bgExec($cmd) {
        if(stripos(php_uname('s'), 'win')>-1){
            echo "hi";
            exec("start /B ". $cmd ); //not working well on windows
        }else {
            exec($cmd . " > /dev/null &"); 
        }
    }
    
    public function remove_pid_from_file($pid,$file){
    global $settings;
    $pids = json_decode(file_get_contents($file),true);
    unset($pids[$pid]);
    file_put_contents($file,json_encode($pids));
    }      

    public function checkForProgramEnd(){
        global $bot_settings,$colors,$kill,$filesCls,$filename;
        if(file_exists("terminate.all")){
        sleep(3);//fix problem if file already exist before fireup, let new intance to register to pid first, then kill it
        $pids_cls = new \Models\PIDs($bot_settings->all_pids_file);
        foreach($pids_cls->pids as $pid=>$data){
            $data_arr = json_decode($data,true);
            $name = $data_arr['name'];
            if($pid!==getmypid()){
                while(posix_getpgid($pid)!==false){
                    echo $colors->warning("Killing $name($pid) ... ".PHP_EOL);
                    $kill($pid);
                    sleep(1);
                }
                echo $colors->success("Killed $name($pid) X_X ...".PHP_EOL);
                $this->remove_pid_from_file($pid, $filename);
                echo $colors->warning("Removing $name($pid) from pids file ... ".PHP_EOL);
                unlink($bot_settings->pids_log_folder."/".$pid.".pid.log");
                echo $colors->warning("Deleting $name($pid) log file ... ".PHP_EOL);
            }
        }
        echo $colors->warning("Unregistering myself from pid file ...".PHP_EOL);
        $this->remove_pid_from_file(getmypid(), $filename);
        echo $colors->warning("Deleting Log file ...".PHP_EOL);
        $filesCls->deleteLogFile();
        unlink("terminate.all");
        echo $colors->warning("Killing mysel X_X ...".PHP_EOL);
        die();
    }
    
        
    }
    
    public function printToConsoleFromBots(){
        global $bot_settings,$filesCls,$colors;
        $pids_cls = new \Models\PIDs($bot_settings->all_pids_file);
        unset($pids_cls->pids[getmypid()]);
        foreach($pids_cls->pids as $pid=>$name){
            $file_content = file_get_contents($bot_settings->pids_log_folder."/".$pid.".pid.log");
            $tmp = explode("///",$file_content);
            $bot_name = $tmp[1];
            // $bot_messages[$pid]
            if(!isset($this->bot_messages[$pid])){
                $this->bot_messages[$pid] = 0;
            }
            if(strlen($tmp[2]) > $this->bot_messages[$pid]){
                echo $colors->info("$bot_name Says:".PHP_EOL);
                echo $colors->bot_instance_message(substr($tmp[2],$this->bot_messages[$pid]).PHP_EOL);
                $this->bot_messages[$pid] = strlen($tmp[2]);
            }
        } //print to console all messages from bots
    
   
    }
    
    public function execCommandsFromFE(){
        global $filesCls,$bot_settings,$finished_signals;
        $pids_cls = new \Models\PIDs($bot_settings->all_pids_file);
        unset($pids_cls->pids[getmypid()]);
    $msgs = glob($filesCls->msgs_from_web_folder.'*.{msg}', GLOB_BRACE);
    if(count($msgs)>0){
        foreach($msgs as $msg) {
        $commands = json_decode(file_get_contents($msg),true);
        foreach($commands as $id=>$command){
            switch($command){
				case 'btcValue':
					$btcValue = 0;
					if($this->binance_api!==null){
						$ticker = $this->binance_api->prices(); // Make sure you have an updated ticker object for this to work
			  			$balances = $this->binance_api->balances($ticker);	
						$btcValue += $this->binance_api->btc_total;
					}
					if($this->bittrex_api!==null){
						$tmp = $this->bittrex_api->getBalances();
						$tmp = json_decode(json_encode($tmp), true);
						$prices = json_decode(json_encode($this->bittrex_api->getMarketSummaries()), true);
						foreach($prices as $id => $data){
							$prices_arr[$data["MarketName"]] = $data["Last"];
						}
						foreach($tmp as $id => $data){
							if($data["Currency"]==="BTC"){
								$btcValue += $data["Balance"];
							}
							else if($data["Balance"]>0 && isset($prices_arr["BTC-".$data["Currency"]])){
								//$filesCls->addContent($data["Currency"].": ".$data["Balance"].", Price: ".$prices_arr["BTC-".$data["Currency"]].", Total: ".$prices_arr["BTC-".$data["Currency"]]/$data["Balance"]);
											$btcValue += ($prices_arr["BTC-".$data["Currency"]]*$data["Balance"]);				
							}

						}
						
						
						
					}
					$tmp = $this->db->select("trackngs","*");
					$btcValueArr=[];
					foreach($tmp as $id=>$data ){
    						$btcValueArr[$id]=$data['btcValue'];
    
					}
					$filesCls->msgToweb(json_encode(['btcValue',$btcValue]));
					$filesCls->msgToweb(json_encode(['btcValueArr',$btcValueArr]));
					$this->db->insert("trackngs",["btcValue"=>$btcValue]);
					break;
				case 'balances':
					//echo "BTC owned: ".$balances['BTC']['available'].PHP_EOL;
			  		//echo "ETH owned: ".$balances['ETH']['available'].PHP_EOL;
					$filesCls->msgToweb(json_encode(['balances',$balances]));
					
					break;
				case (strpos($command,'sellSignal')===0):
					$tmp  = explode("|",$command);
					$pid = $tmp[1];
					$strat['name'] = $tmp[2];
					$strat['market'] = $tmp[3];
					$cmnd = "sellSignal|".$strat['name']."|".$strat['market'];
					$command = str_replace(".","",$command);
					$filesCls->writeCmndsToPid([$cmnd],$pid,$command); 
					break;
				case (strpos($command,'DCA')===0):
					$tmp  = explode("|",$command);
					$pid = $tmp[1];
					$strat['name'] = $tmp[2];
					$strat['market'] = $tmp[3];
					$cmnd = "DCA|".$strat['name']."|".$strat['market'];
					$command = str_replace(".","",$command);
					$filesCls->writeCmndsToPid([$cmnd],$pid,$command); 
					break;
                case 'openSignalsToWeb':
                    foreach($pids_cls->pids as $pid=>$name){
                        if(strpos(strtolower($name),"server")===false && strpos(strtolower($name),"died")===false){//send command only to bot instnaces and not to server intances (websocket...web etc..)
   $filesCls->writeCmndsToPid(["openSignalsToWeb"],$pid,$command);                          
                        }
                        //sleep(3);
                    }

                    break;
                case 'closedSignalsToWeb':
                    foreach($pids_cls->pids as $pid=>$name){
                        if(strpos(strtolower($name),"server")===false && strpos(strtolower($name),"died")===false){
                            $filesCls->writeCmndsToPid(["closedSignalsToWeb"],$pid,$command);
                        }
                        //sleep(3);
                    }
					
					
					$finished_s = $this->db->select('signals', "*",['status' => 'finished',"ORDER" => ["id" => "DESC"]]);
					$i=0;
					$finished_signals_arr=[];
					$finished_signals=0;
					$finished_signals_arr2=[];
					if(count($finished_s) > $finished_signals){
						foreach($finished_s as $signal ){
							$finished_signals_arr=[];
							//if($i>$finished_signals){
$finished_signals_arr[$signal['market']] = [["buy"=>["price"=>$signal['buy_price'],"market"=>$signal['market'],"opened"=>$signal["opened"]],"sell"=>["close_time"=>$signal["closed"],"price"=>$signal["sell_price"]],"profit_loss_percent"=>($signal['sell_price']-$signal['buy_price'])/$signal['buy_price']*100,"quantity"=>$signal['quantity']]];
								
$finished_signals_arr[$signal['market']]['details'] = ["strategy"=>$signal['strategy'],"timeframe"=>$signal["timeframe"],"exchange"=>$signal["exchange"],"total_closed_signals"=>2,"sim"=>false];
								
$filesCls->msgToWeb(json_encode(["closedSignalsToWeb",$finished_signals_arr,"sim"=>false,"timeframe"=>$signal["timeframe"],"exchange"=>$signal["exchange"],"strategy"=>$signal['strategy']]),mktime().rand());
$finished_signals_arr2[$signal['market']][] = array_merge($finished_signals_arr[$signal['market']][0],['details'=>$finished_signals_arr[$signal['market']]['details']]);
								$finished_signals++;
							//}
							$i++;
						}	
					}		
					file_put_contents("finished_singnals.json",json_encode($finished_signals_arr2));
                    break;
				case 'usageRamCpu':
					$total_cpu = 0;
					$total_ram=0;
                foreach($pids_cls->pids as $pid=>$name){
                        //if(strpos(strtolower($name),"server")===false && strpos(strtolower($name),"died")===false){
                                 
	$output = preg_replace('!\s+!', ' ', shell_exec("top -b -n 1 -p $pid |grep $pid"));
					$arr = explode(" ",$output);
					
					if(!is_numeric($arr[8]) || !is_numeric($arr[9])){
$usage[$pid]['cpu'] = 0;
					$usage[$pid]['ram'] = 0;
					}
					else{
						$usage[$pid]['cpu'] = $arr[8];
					$usage[$pid]['ram'] = $arr[9];
					}
					
					
					//echo $output;
                 $total_cpu+=$usage[$pid]['cpu'];
					$total_ram+=$usage[$pid]['ram'];
                        //}
                    //sleep(1);
                    }    
					$usage['total_cpu'] =  $total_cpu;
					$filesCls->msgToweb(json_encode(['usageRamCpu','total_cpu'=>$total_cpu,"total_ram"=>$total_ram]),"cpuusage");
					break;
                case 'latestScansData':
                foreach($pids_cls->pids as $pid=>$name){
                        if(strpos(strtolower($name),"server")===false && strpos(strtolower($name),"died")===false){
                                 
       $filesCls->writeCmndsToPid(["latestScansData"],$pid,$command);                     
                        }
                    //sleep(1);
                    }                   
                    break;
                case "workingInstancesToWeb":
                    $instances = [];
                    foreach($pids_cls->pids as $pid=>$name){
                        if(strpos(strtolower($name),"server")===false && strpos(strtolower($name),"died")===false){
                $instances[$pid]=[$name];
                        }
                    }
        $filesCls->msgToweb(json_encode(['workingInstances',$instances]),"instances");
                    break;
                case "stopInstances":
                    $instances = [];
                    foreach($pids_cls->pids as $pid=>$name){
                        if(strpos(strtolower($name),"server")===false && strpos(strtolower($name),"died")===false){
                $filesCls->writeCmndsToPid(["stop"],$pid,'stopIns'); 
                        }
                    }
        $filesCls->msgToweb(json_encode(['workingInstances',$instances]),"instances");
                    break;
					
                case "startInstances":
					$this->startInstances();
					 $filesCls->msgToweb(json_encode(['workingInstances',$instances]),"instances");
                    break;
					case (strpos($command,'finishedBacktestingsList')===0):
					$filter = '';
					if((strpos($command,' ')>0)){
						$tmp  = explode(" ",$command);
						$filter = $tmp[1]; //BNBBTC.pSARadx
					}

					$files = glob("backtesting_results/*{$filter}*.{json}", GLOB_BRACE);
        			foreach($files as $file) {
        		//do your work here
						$tmp = explode("/",$file);
						$file_name = 'backtesting_results/'.$tmp[count($tmp)-1];
						$data = json_decode(file_get_contents($file),true);
						$to_web = ['file_name'=>$file_name,'details'=>"backtesting"];
						$filesCls->msgToweb(json_encode(['finishedBacktestingsList',$to_web]));
            
            
        }
					$files = glob("simulations/*.{json}", GLOB_BRACE);
        			foreach($files as $file) {
        		//do your work here
						$tmp = explode("/",$file);
						$file_name = 'simulations/'.$tmp[count($tmp)-1];
						$data = json_decode(file_get_contents($file),true);
						if($filter!==""){
							foreach($data as $coin_name=>$coin_data){
								if(strpos($coin_name,$filter)!==false || strpos($coin_data['details']['startegy'],$filter)!==false){
									$to_web = ['file_name'=>$file_name,'details'=>count($data)];
									$filesCls->msgToweb(json_encode(['finishedBacktestingsList',$to_web]));
								}
								
							}
						}
						else{
						$data['details'] = count($data);
						$to_web = ['file_name'=>$file_name,'details'=>$data['details']];
						$filesCls->msgToweb(json_encode(['finishedBacktestingsList',$to_web]));							
						}

            
            
        }
					$to_web = ['file_name'=>'finished_singnals.json','details'=>'Finished Real Mode Signals'];
					$filesCls->msgToweb(json_encode(['finishedBacktestingsList',$to_web]));
								
					break;
					case (strpos($command,'backtestingResult')===0):
					if((strpos($command,' ')>0)){
						$tmp  = explode(" ",$command);
						$filename = $tmp[1]; //BNBBTC.pSARadx
						$filesCls->msgToweb(json_encode(['backtestingResult',json_decode(file_get_contents($filename),true)]));
					}
					break;
            }
        }
        }
        unlink($msg);
    }
    
    }
    
    public function startOnTheFlyInst(){
        global $on_the_fly_file,$colors;
        //echo "on the fly out";
            if(file_exists($on_the_fly_file)){
        //echo "on the fly in";
    
        $instances_on_the_fly = json_decode(file_get_contents($on_the_fly_file),true);    
        //check for onthe fly instances status/fire up
        foreach($instances_on_the_fly as $name => $instance){
            if(isset($instance['backtesting'])){
                if($instance['backtesting']==true){
                    echo $colors->info("starting backtesting mode instance $name...".PHP_EOL); 
                    $this->bgExec("php -q Executables".DIRECTORY_SEPARATOR."BotInstance.php --name=$name --onTheFly=1 --backtesting=1");
                }
            }
            else{
                echo $colors->info("starting $name...".PHP_EOL); 
                $this->bgExec("php -q Executables".DIRECTORY_SEPARATOR."BotInstance.php --name=$name --onTheFly=1");              
            }

            sleep(5);//wait for instance to fully intilize before next one.
        }
        unlink($on_the_fly_file);
            
        
    }
  //echo "on the fly finish";
    }
    
    public function checkSocketStatus(){
    /* $fp = fsockopen("www.example.com", 80, $errno, $errstr, 30);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    fwrite($fp, "Your message");
    while (!feof($fp)) {
        echo fgets($fp, 128);
    }
    fclose($fp);
}*/
    $externalContent = file_get_contents('http://checkip.dyndns.com/');
    preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
    $_SERVER['SERVER_ADDR'] = $m[1];

    $fp = fsockopen($_SERVER['SERVER_ADDR'], 1337, $errno, $errstr, 5);
    if (!$fp) {
        return false;
    }
    fclose($fp);
    return true;
}
	
	public function startInstances(){
		global $instances_on_start,$colors,$filesCls,$bot_messages;
$active_signals=[];
$active_s = $this->db->select('signals', "*",['status[!]' => 'finished']);
foreach($active_s as $signal ){
    $active_signals[$signal["strategy"].$signal["timeframe"].$signal['exchange']][$signal['market']] = $signal;
    
}


file_put_contents("active_unass_signals.json",json_encode($active_signals));
//start instances
foreach( $instances_on_start as $name => $instance){
    echo $colors->info("starting $name...".PHP_EOL); 
    
    
    $this->bgExec("php -q Executables".DIRECTORY_SEPARATOR."BotInstance.php --name=$name");
    sleep(3);
}
$untouched_sigs = count(json_decode("active_unass_signals.json",true) ?? []);
if($untouched_sigs>0){
$filesCls->addContent($colors->warning("There are $untouched_sigs untouched active signals,they ARE NOT BEING PROCCESSED due to lack of apropriate strategy file q bot instance to deal with them!"));
    }
$bot_messages = [];
	}
	
	public function addBtcBalanceToDBPeri(){
		global $flag;
		$minutes = date("i",mktime()) ;
		if(($minutes == "30" || $minutes=="00") && $flag){
					$btcValue = 0;
					if($this->binance_api!==null){
						$ticker = $this->binance_api->prices(); // Make sure you have an updated ticker object for this to work
			  			$balances = $this->binance_api->balances($ticker);	
						$btcValue += $this->binance_api->btc_total;
					}
					if($this->bittrex_api!==null){
						$tmp = $this->bittrex_api->getBalances();
						$tmp = json_decode(json_encode($tmp), true);
						$prices = json_decode(json_encode($this->bittrex_api->getMarketSummaries()), true);
						foreach($prices as $id => $data){
							$prices_arr[$data["MarketName"]] = $data["Last"];
						}
						foreach($tmp as $id => $data){
							if($data["Currency"]==="BTC"){
								$btcValue += $data["Balance"];
							}
							else if($data["Balance"]>0 && isset($prices_arr["BTC-".$data["Currency"]])){
							$btcValue += ($prices_arr["BTC-".$data["Currency"]]*$data["Balance"]);				
							}

						}
						
						
						
					}
				
			$this->db->insert("trackngs",["btcValue"=>$btcValue]);
			$flag=false;
			return $btcValue;
		}
		if($minutes==="31"){
			$flag=true;
		}
		return false;

	}

}
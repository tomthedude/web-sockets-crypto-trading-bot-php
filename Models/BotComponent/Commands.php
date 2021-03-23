<?php
namespace Models\BotComponent;

class Commands{
    private $commands_file = "";
    private $myPid = "";
    public $myCommands = [];
    private $fileSy = "";
    private $botSettings="";
    
    
    function __construct(){//get parent inst file system
        global $filesCls;
        global $settings;
        $this->myPid = getmypid();
        $this->commands_file = getmypid().".cmnd";
        $this->myCommands=[];
        $this->fileSy = $filesCls;
        $this->botSettings = &$settings;
        //$this->checkAndexecNewCommands();
        
    }
    
    public function checkAndexecNewCommands(){
        $msgs = glob($this->fileSy->cmnds_folder.$this->myPid.'*.{cmnd}', GLOB_BRACE);
        if(count($msgs)>0){//execute commands from font-end
        foreach($msgs as $msg) {
            $this->commands_file = $msg;
        if(file_exists($this->commands_file)){
            
            $tmp_cmnds = json_decode(file_get_contents($this->commands_file),true);
            foreach($tmp_cmnds as $cmnd){
                $this->addCommand($cmnd);
              
            }
          
            
        }
        else{
          return false;  
        } 
            unlink($this->commands_file);
                
    }
            $this->execAllCommands();
        }
        return false;
    }
    
    public function addCommand($cmnd){
        array_push($this->myCommands,$cmnd);
        
    }
    
    public function removeCommand(){//[0]=>first command, [1]=> second command etc
        array_shift($this->myCommands);
        
    }
    
    private function execAllCommands(){
        global $colors,$api,$bot,$instances_on_start,$cli_args,$simulation_finished_trades;
        $files = $this->fileSy;
        foreach($this->myCommands as $command){
            $files->addContent($colors->info("Executing '$command' command from file ...")); 
            switch($command){
                case 'stop':
                    //$files->remove_pid_from_file();
                    //$files->deleteLogFile();
                    $files->addContent($colors->warning("Died from command ..."));
                    $files->register_pid_to_file("died with stop (natural causes ;) )");
                    $this->removeCommand();
                    unlink($this->commands_file);
                    die('died from command');
                    break;
                case 'reload conf.json':
                    $this->botSettings = file_get_contents('conf.json');
                   /* if(isset($bot)){
                       $instances_on_start = json_decode(json_encode($settings->bot_instances_on_start),true);
                        $bot->thisInstance = $instances_on_start[$cli_args['--name']];
                    }*/

                    $files->addContent($colors->warning("Reloaded conf.json"));
                    break;
                case 'reload coins':
                    $api->loop->stop();
                    $bot->fillCoinsArr();
                    $files->addContent($colors->warning("Reloaded coins"));
                    break;
                case 'startSOM':
                    $bot->sell_only_mode = true;
                    $files->addContent($colors->warning("Strated SOM."));
					$pids_data = $files->getPIDFileCon();
					$pids_data1 = json_decode($pids_data[getmypid()],true);
					$pids_data1['SOM'] = "true";
					$files->register_pid_to_file(json_encode($pids_data1));
					$files->writeMsgFromWeb(["workingInstancesToWeb"]); 
					
                    break;
               case 'stopSOM':
                    $bot->sell_only_mode = false;
                    $files->addContent($colors->warning("Stopped SOM."));
					$pids_data = $files->getPIDFileCon();
					$pids_data1 = json_decode($pids_data[getmypid()],true);
					if(isset($pids_data1['SOM'])){
						unset($pids_data1['SOM']);
					}
					$files->register_pid_to_file(json_encode($pids_data1));
					$files->writeMsgFromWeb(["workingInstancesToWeb"]); 
                    break;
                case "openSignalsToWeb":
                   $files->msgToWeb(json_encode(["openSignalsToWeb",$bot->signals,"timeframe"=>$bot->timeframe,"exchange"=>$bot->xchnage,"sim"=>$bot->simulator,"pid"=>$this->myPid]),$command.$this->myPid);
                    break;
                case "closedSignalsToWeb":
                    
                   $files->msgToWeb(json_encode(["closedSignalsToWeb",json_decode(file_get_contents("simulations/".$bot->sim_id.".json")),"sim"=>$GLOBALS['isSimulator'],"timeframe"=>$bot->timeframe,"exchange"=>$bot->xchnage]),$command);
                    break;
				case (strpos($command,'sellSignal')===0):
					$tmp  = explode("|",$command);
					$strat['name'] = $tmp[1];
					$strat['market'] = $tmp[2];
					$bot->signals[$strat['name']][$strat['market']]['forceSell'] = "yes";
					break;
				case (strpos($command,'DCA')===0):
					$tmp  = explode("|",$command);
					$strat['name'] = $tmp[1];
					$strat['market'] = $tmp[2];
					$bot->signals[$strat['name']][$strat['market']]['DCA'] = "yes";
					break;
                case 'latestScansData':
                    $files->msgToWeb(json_encode(['latestScansData',$bot->latest_scans()]),$command);
                    break;
                    
                case 'stop ws-server':
                    die();
                    break;
                default:
                    echo "$command - command unknown";
                    
            }
            $this->removeCommand();
        }
    }
    
}
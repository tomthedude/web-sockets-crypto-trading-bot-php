<?php
namespace Models\BotComponent;

class FilesWork{
    public $pids_file_path;
    public $log_file_path;
    public $msgs_to_web_folder;
    public $msgs_from_web_folder;
    private $pid;
    public $cmnds_folder;
    
    function __construct(){
        global $bot_settings, $colors;
        $this->pid = getmypid();
        $this->pids_file_path = $bot_settings->all_pids_file;
        $this->bot_settings = $bot_settings;
        $this->log_file_path = $bot_settings->pids_log_folder."/".$this->pid.".pid.log";
        $this->msgs_to_web_folder = "msgs_to_web/";
        $this->msgs_from_web_folder="msgs_from_web/";
        $this->cmnds_folder="cmnds/";
        $this->colors = $color ?? new \Models\Console\TerminalColors();
        
        @file_put_contents($this->log_file_path,"");
        ini_set("error_log", $this->log_file_path);
    }
    
    public function getLogFileCon(){
        return file_exists($this->log_file_path) ? file_get_contents($this->log_file_path) : "";
    }
    public function getPIDFileCon(){
        return file_exists($this->pids_file_path) ? json_decode(file_get_contents($this->pids_file_path),true) : null;
    }
    public function deleteLogFile(){
        return unlink($this->log_file_path);
    }
    //logs_file
    public function addContent($new_content){//add content to pid.log file
        //@file_put_contents(, $new_content); 
        $this->msgToWeb(date("H:i d/m/Y").": ".$new_content.PHP_EOL);
        return @file_put_contents($this->log_file_path,file_get_contents($this->log_file_path).PHP_EOL.date("H:i d/m/Y").": ".$new_content);
    }

    public function debug($msg){
        if(!($this->bot_settings->debug ?? false)){
            return;
        }
        $this->addContent($this->colors->info($msg));
    }
    
    //msgs to web
    public function msgToWeb($msg,$unique_id=0){
        $unique_id === 0 ? $unique_id = rand(): false;
        return @file_put_contents($this->msgs_to_web_folder.$this->pid.".".mktime().".".$unique_id.".msg",$msg);
        
    }
    public function writeMsgFromWeb($msg_arr=[],$unique_id=0){
        $unique_id === 0 ? $unique_id = rand(): false;
        return @file_put_contents($this->msgs_from_web_folder.mktime().$unique_id.'.msg',json_encode($msg_arr));
    }
    
    public function writeCmndsToPid($cmnds_arr=[],$pid=0,$unique_id=0){
        $pid === 0 ? $pid=$this->pid : false;
        $unique_id === 0 ? $unique_id = rand(): false;
        @file_put_contents($this->cmnds_folder.$pid.".".mktime().".".$unique_id.".cmnd",json_encode($cmnds_arr));     
    }
    
    //pids_file
    public function register_pid_to_file($comment){
        global $settings;
        $pid = getmypid();
        $pids = json_decode(file_get_contents($this->pids_file_path),true);
        $pids[$pid] = $comment;
        return @file_put_contents($this->pids_file_path,json_encode($pids));
    }
    public function remove_pid_from_file(){
        global $settings;
        $pid = getmypid();
        $pids = json_decode(file_get_contents($this->pids_file_path),true);
        unset($pids[$pid]);
        return @file_put_contents($this->pids_file_path,json_encode($pids));
    }

}
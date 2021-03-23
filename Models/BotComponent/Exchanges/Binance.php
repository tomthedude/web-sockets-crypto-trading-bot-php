<?php

namespace Models\BotComponent\Exchanges;

class Binance extends Base{

    public function __construct($botObject){
        parent::__construct($botObject);
    }

    public function fillCandles(){
        
    }

    public function runStreamLoop(){
        global $count,$bot,$settings,$data,$empry_arr_count,$cli_args,$bot_settings,$filesCls,$cmnds,$api;
        $banned = false;
        $api = $this->api;
        $bot = $this->botObject;
        $this->api->chart($this->botObject->coins_array, $this->botObject->timeframe, function($api, $symbol, $chart) use(&$count,$bot,&$settings,&$data,&$empry_arr_count,&$banned) {
            global $cli_args,$bot_settings,$filesCls,$cmnds,$api;
            $reply = $bot->checkOHLVCforSignals($chart,$symbol);
            if($reply===null){
                $empry_arr_count++;
            }
            else{
            if($banned){
                $pids_data = $filesCls->getPIDFileCon();
                $pids_data1 = json_decode($pids_data[getmypid()],true);
                unset($pids_data1['no_ws_connection']);
                $filesCls->register_pid_to_file(json_encode($pids_data1));						
            }
            $banned = false;
            $empry_arr_count = 0;
        }
        if($empry_arr_count > 20){
            $filesCls->addContent(" sleeping for 35 minutes, probaly banned from binance, 20+ empy arrays ... ");
            $empry_arr_count = 0;
            if($bot->telegram!==null){
                $bot->telegram->sendMessage(" sleeping for 5 minutes and then connecting again, probaly banned from binance, 20+ empy arrays ... ");
                
            }
            //die();//temp solution
            $api->loop->stop(); 
            if(!$banned){
                $pids_data = $filesCls->getPIDFileCon();
                $pids_data1 = json_decode($pids_data[getmypid()],true);
                $pids_data1['no_ws_connection'] = "true";
                $filesCls->register_pid_to_file(json_encode($pids_data1));				
            }

            sleep(60*10);
         $bot->fillCoinsArr();
            $filesCls->addContent("Tried to reconnect");
        }
        if($count>100){
            //if coin list has changed, stop loop and the restart it later in code to   update subscriptions;
            // stop bot, reload conf.json, reload coins etc..
            $active_coins = count($bot->coins_array);
            $filesCls->addContent("  still running ($count live trades proccessed, $active_coins active coins) ... ");
            //$settings = json_decode($data);//reload settings
            $count=0;
            $active_coins = null;
gc_collect_cycles();
            
        }
        $reply = null;
        $count++;
        //echo $reply !== null ? $reply : "nohing to do here.. keep moving\n";
        },2);
        sleep(30); 	
    }
    
    public function getCoinOHLVC($coinName){
        return $this->api->candlesticks($coinName, $this->botObject->timeframe,2000);
    }

    public function addTimerReloadOHLVC(){

    }

    public function getOrderStatus($orderId, $market){
        return $this->api->orderStatus($market, $orderId);
    }

    public function initApi(){
        $this->api = new \Binance\API($this->botObject->user_settings->binance->bnkey,$this->botObject->user_settings->binance->bnsecret);
        return $this->api;
    }
    public function initClient(){
        return (object)[];
    }

    public function getCoinsByVolume($volume){
        $vol = $volume;
        $nonce=time();
        $uri="https://api.binance.com/api/v1/ticker/24hr";
        $sign= @hash_hmac('sha512',$uri);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $execResult = curl_exec($ch);
        $obj = json_decode($execResult, true);
        //$filesCls->addContent(json_encode($obj));
        $j=0;
        for($i=0;$i<count($obj);$i++){
            $coins[$i] = $obj[$i];
        }
        $coinsa=[];
        for($i=0;$i<count($coins);$i++){
            if($coins[$i]["quoteVolume"] >= $vol && strpos($coins[$i]["symbol"],$this->botObject->mainCoinToTradeVersus) > 2){
                $ignore = false;
                foreach($this->botObject->ignore_coins as $key=>$coin){
                    if(strpos($coins[$i]["symbol"],$coin)){
                        $ignore=true;
                        //unset($this->ignore_coins[$key]);
                    }
                }
                if(!$ignore){
                    $coinsa[$j] = $coins[$i]["symbol"];
                    $j++;
                }

            }
        }
        return $coinsa;         
    }
}

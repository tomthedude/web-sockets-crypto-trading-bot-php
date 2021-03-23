<?php

namespace Models\BotComponent\Exchanges;

class Bittrex extends Base{

    public function __construct($botObject){
        $this->btrx_candles = [];
        parent::__construct($botObject);
    }

    public function fillCandles(){
        $this->fill_btrx_candles();
        
    }

    public function getCoinOHLVC($coinName){
        return $this->btrx_candles[$coinName];
    }

    public function initApi(){
        $this->api = new \Models\Exchanges\Bittrex\SignalR\ClientR("wss://socket.bittrex.com/signalr", ["corehub"]);
        return $this->api;
    }

    public function initClient(){
        $this->client = new \Models\Exchanges\Bittrex\ClientBittrexAPI($this->botObject->user_settings->bittrex->btkey,$this->botObject->user_settings->bittrex->btsecret);
        return $this->client;
    }

    public function runStreamLoop(){
        global $data, $filesCls,$bot;
        $banned = false;
        $bot = $this->botObject;
        $this->api->on("corehub", "updateSummaryState", function($data) {
            global $filesCls,$bot;
            //print_r($data);
            //die();
            //print_r($bot->btrx_coins_format);
            $latest_trades_all_markets = $data->Deltas;
            $latest_trade_per_market = [];
            foreach($latest_trades_all_markets as $trade){
                if(isset($bot->btrx_coins_format[$trade->MarketName])){// check if the trade is in the bot coin list
                    $latest_trade_per_market[$trade->MarketName] = $trade;		//check only the latest trade sent per cin.. old ones doesnt matter..			
                }
                
            }
            foreach($latest_trade_per_market as $market=>$trade){
                $tmp = $bot->exchangeObject->to_market_format("binance",[$market]);
                $market = $tmp[0];
                $latest_trade = [];
                //print_r(array_keys($bot->btrx_candles));
                //die();
                if(isset($bot->btrx_candles[$market])){
                    $bot->btrx_candles[$market][count($bot->btrx_candles[$market])-1]['close'] = $trade->Last;
                    $reply = $bot->checkOHLVCforSignals($bot->btrx_candles[$market],$market);
                    print_r($reply);
                //die();					
                }
                else{
                    echo "no candle array yet";
                }
            }
            //print_r($latest_trade_per_market);
            echo "Checked ".count($latest_trade_per_market)." Trades";
            $filesCls->addContent("Checked ".count($latest_trade_per_market)." Trades");
            });
            $this->api->run();
    }

    public function getCoinsByVolume($volume){
        $vol = $volume;
        $nonce=time();
        $uri="https://bittrex.com/api/v1.1/public/getmarketsummaries";
        $sign=@hash_hmac('sha512',$uri);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $execResult = curl_exec($ch);
        $obj = json_decode($execResult, true);
        $obj = $obj['result'];
        $j=0;
        for($i=0;$i<count($obj);$i++){
            $coins[$i] = $obj[$i];
        }
        $coinsa=[];		
        for($i=0;$i<count($coins);$i++){
            if($coins[$i]["BaseVolume"] >= $vol && strpos($coins[$i]["MarketName"],$this->botObject->mainCoinToTradeVersus)===0){
                $ignore = false;
                foreach($this->botObject->ignore_coins as $key=>$coin){
                    if(strpos($coins[$i]["MarketName"],$coin)){
                        $ignore=true;
                        //unset($this->ignore_coins[$key]);
                    }
                }
                if(!$ignore){
                    $tmp_name = explode("-",$coins[$i]["MarketName"]);
                    $coins[$i]["MarketName"] = $tmp_name[1].$tmp_name[0];
                    $coinsa[$j] = $coins[$i]["MarketName"];
                    $j++;
                }
            }
        }
        return $coinsa;
    }

    public function getOrderStatus($orderId, $market){
        $orderstatus = [];
        $tmp = json_decode(json_encode($this->client->getOrder($orderId)), true);
        $order['status'] =$tmp['Closed'];
        if($order['status']!==null){
            $orderstatus['orderStatus'] = "FILLED";
        }
        //$order['clientOrderId'] = $order['uuid'];
        //do the magic here.. check if order is closed and update accordingy
        return $orderstatus;
    }

    public function addTimerReloadOHLVC(){
        $bot = $this->botObject;
        $filesCls = $this->botObject->filesCls;
        $this->api->loop->addPeriodicTimer(60,function() use (&$bot, $filesCls) {
            $bot->exchangeObject->fill_btrx_candles();
            $filesCls->addContent("Reloaded OHLVC for all bittrex coins");
        });
    }
    
    private function fill_btrx_candles(){
		//$this->btrx_candles pass this var to  intiate_OHLVC when its time to check this coin latest trade
		$names = $this->to_market_format("bittrex",$this->botObject->coins_array);
		//print_r($names);
		$timeframes_unifier = ["1m" => "oneMin", "5m" => "fiveMin", "30m" => "thirtyMin", "1h" => "hour", "1d"=>"day"];
		foreach($names as $id=>$market){
			$this->btrx_coins_format[$market] = $id;
        	$nonce=time();
    		$uri="https://international.bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=$market&tickInterval=".$timeframes_unifier[$this->botObject->timeframe];
            echo $uri;
            $this->botObject->filesCls->debug('tried to open '.$uri);
    		$sign=@hash_hmac('sha512',$uri);
    
    		$ch = curl_init($uri);
        	curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $execResult = curl_exec($ch);
            $this->botObject->filesCls->debug('tried to open, result: '.$execResult);
    		$obj = json_decode($execResult, true);
			//print_r($obj);
			$obj = $obj['result'];
            if (!is_array($obj)) {
                var_dump($execResult);
                continue;
                //die();
            }
            $this->botObject->filesCls->debug('result: '.print_r($obj, true));
			foreach($obj as $index=>$candle){
				$this->btrx_candles[$this->botObject->coins_array[$id]][$index]['open'] = $candle['O'];
				$this->btrx_candles[$this->botObject->coins_array[$id]][$index]['close'] = $candle['C'];
				$this->btrx_candles[$this->botObject->coins_array[$id]][$index]['high'] = $candle['H'];
				$this->btrx_candles[$this->botObject->coins_array[$id]][$index]['low'] = $candle['L'];
				$this->btrx_candles[$this->botObject->coins_array[$id]][$index]['volume'] = $candle['BV'];
			}
        }
        $this->botObject->filesCls->addContent('total coins added: '. count($this->btrx_candles));
        if(!is_array($this->btrx_candles)){
            return;
        }
					print_r(array_keys($this->btrx_candles));
			//die();
	}
}
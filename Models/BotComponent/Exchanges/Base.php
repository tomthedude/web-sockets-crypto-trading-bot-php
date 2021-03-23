<?php
namespace Models\BotComponent\Exchanges;

abstract class Base{

    public static function getExchangeObject($exchange,$botObject){
        $exchange = "\Models\BotComponent\Exchanges\\".ucfirst($exchange);
        return new $exchange($botObject);
    }

    public function __construct($botObject){
        $this->api = $botObject->getApi();
        $this->botObject = $botObject;
    }

    abstract public function addTimerReloadOHLVC();

    abstract public function getOrderStatus($orderId, $market);

    abstract public function initApi();

    abstract public function initClient();

    abstract public function runStreamLoop();

    abstract public function getCoinsByVolume($volume);

    public function to_market_format($xchange,$names_arr){
		switch ($xchange){
			case 'bittrex':
				foreach($names_arr as $id => $name){
                    $tmp_name = explode($this->botObject->mainCoinToTradeVersus,$name);
                    $baseCoin =$this->botObject->mainCoinToTradeVersus;
						$names_arr[$id] = "$baseCoin-".$tmp_name[0];
					}
				
				break;
			case 'binance':
				foreach($names_arr as $id => $name){
					$tmp_name = explode("-",$name);
						$names_arr[$id] = $tmp_name[1].$tmp_name[0];
					}	
				break;
		}
		return $names_arr;
	}

    abstract public function fillCandles();

    abstract public function getCoinOHLVC($coinName);
}
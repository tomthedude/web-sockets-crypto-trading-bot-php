<?php
namespace Models\BotComponent;


class Signals{
    public $signals = [];
    private $bot_inst;
    private $files = "";
    public $db;
    
    //__construct (){ fill signals arr with all active signals from DB (do not load ALL signals to not overload the bot with uneccesary data)}
    
    //select * from signals where `status`="active" AND (`startegy` = 'pSARswtich.json' OR `startegy` = 'ema2050_cross.json'..
    function __construct(){
        global $bot, $filesCls,$database;
        $this->bot_inst = $bot;
        $this->files =  $filesCls;
        $this->db = $database;
    }
    public function getOpeningSignals($bot_strats,$timeframe,$xchange){
        global $colors,$bot;
        if($GLOBALS['isSimulator']===true){//if simulation do not load signals
            return [];
        }
        $total_added = 0;
        $remaining_signals = json_decode(file_get_contents("active_unass_signals.json"),true);
        foreach($bot_strats as $strat){
            foreach($remaining_signals as $strategy_name => $signals){
				foreach($signals as $market=>$signal){
                	if($strat['name'].$timeframe.$xchange === $strategy_name){
						$timef= $signal['timeframe'];
                    	$this->signals[$strat['name']][$market] = $remaining_signals[$strategy_name][$market];
						$this->signals[$strat['name']][$market]["price"] = $this->signals[$strat['name']][$market]["buy_price"];
						$this->signals[$strat['name']][$market]['DCAed'] = substr_count($this->signals[$strat['name']][$market]["log"],"DCAed");
                    	$this->files->addContent("Added 1 signals from $strategy_name");
                    	$total_added ++;
                    	unset($remaining_signals[$strategy_name][$market]);
					}
                }
            }    
        }
        file_put_contents("active_unass_signals.json",json_encode($remaining_signals));
        $this->files->addContent($colors->info("Added $total_added signals from file"));
        return $this->signals;

        
    }
    public function proccess_buySignal($latest_candle){
        global $bot_settings,$api,$colors,$bot,$client,$cli_args;
		$msg = '';
        if($bot->sell_only_mode){
			if($bot_settings->debug){
            $this->files->addContent($colors->info("new signal but SOM."));
			}
            return null;
        }
        $market = $latest_candle['market'];
		$latest_candle['exchange'] = $bot->xchnage;
        if(!$this->isOpenPositionMarket($market,$latest_candle['strat'])){
            //check if position is already open
			//count signals
			$i=0;
			foreach($bot->signals as $strat_name=>$signal){
				foreach($signal as $som){
					$i++;
				}
				
				
			}
            if($bot->max_open_signals > $i || $GLOBALS['isSimulator']===true){
				//$this->files->addContent(json_encode($bot->signals));
				if($GLOBALS['isSimulator']===false){
					if($bot->xchnage === "binance"){
						$ticker = $api->prices(); // Make sure you have an updated ticker object for this to work
						$balances = $api->balances($ticker);				
					}

					else if($bot->xchnage === "bittrex"){
						$tmp = $client->getBalance("BTC");
						$tmp = json_decode(json_encode($tmp), true);
						$balances['BTC']['available'] = $tmp["Available"];
					}
					
					if($balances['BTC']['available'] < $bot->max_btc_per_trade){
						if($bot_settings->debug){
						$this->files->addContent($colors->info("Can not buy, insufficent funds".$bot->max_open_signals." : $i"));
						}
						return null;
					}
					
				}
//add here price pull for bittrex, store in some array while initiating to unified OHLVC
                //check if balance is enough to purchase
				if(isset($cli_args["--backtesting"])){
					$buy_price = $latest_candle['price'];
				}
				   else{
					if($bot->xchnage === "binance"){
                $all_prices = $api->bookPrices();
                $buy_price =  $all_prices[$market]['ask'];
			}
				else if($bot->xchnage === "bittrex"){
					$coin_arr = $bot->exchangeObject->to_market_format("bittrex",[$market]);
					$bp = $client->getTicker($coin_arr[0]);
					$bp = json_decode(json_encode($bp), true);
					$buy_price = $bp['Ask'];
				}
             			   
				   }
   $latest_candle['spread_price_ask'] = $this->checkSpreadPriceAskorBid($buy_price,$latest_candle['price']);
                //spread is between 0 and minus(x); meaning actual purchase price is x precent above last trade;
                if($latest_candle['spread_price_ask'] > $latest_candle['max_spread']){
					if($bot_settings->debug){
                    $this->files->addContent($colors->info("Wanted to buy, but spread is too big".json_encode($latest_candle)));
					}
                                          return null;
                    
                }
                
            
                $msg = '';
               if($GLOBALS['isSimulator']===true){
				   $msg .= "----SIMULATION TRADE OPENED / BotV2-----\n";
                   $latest_candle['opened'] = date("H:i:s, d/m");
                   //file_put_contents('sigs4.json',file_get_contents('sigs4.json').date("h:i d/m/y").": ".json_encode($latest_candle).PHP_EOL);
                   $latest_candle['status'] = 'active';
				   $bot->signals[$latest_candle['strat']][$market] = $latest_candle;
				   return true;
               }
				else{
				   $msg .= "----REAL TRADE OPENED / BotV2-----\n";
                   $quantity = round($bot->max_btc_per_trade/$buy_price);//change to quantity calculation..
				   if($quantity <= 0){
					   if($bot_settings->debug){
					   $this->files->addContent("Failed with buy: quantity is 0");
					   }
					   return null;
				   }
                   //$order['status'] = "FILLED";
                   //$order['clientOrderId']="4";
                    $latest_candle['quantity'] = $quantity;
                   //$order['orderId'] = 1;
				   if($bot->xchnage === "binance"){
					   $order = $api->marketBuy($market, $quantity);
				   
				   }
 				   else if($bot->xchnage === "bittrex"){
					   $tmp = $bot->exchangeObject->to_market_format("bittrex",[$market]);
					   $order = $client->buyLimit($tmp[0], $quantity,$buy_price);
					    $order = json_decode(json_encode($order), true);
					   $tmp =  json_decode(json_encode($client->getOrder($order['uuid'])), true);
					   $order['status'] =$tmp['Closed'];
					   if($order['status']!==null){
						    $order['status'] = "FILLED";
					   }
					   $order['orderId'] = $order['uuid'];
				   
				   }
                                     
				   if(!isset($order['status'])){
					   if($bot_settings->debug){
					   $this->files->addContent("Failed with buy: ".json_encode($order));
					   }
					   return null;
				   }
                   $latest_candle['status'] = 'WaitingForPurchase';
                   $to_db = [];
                   $to_db = ['market'=>$market,
                          "exchange"=>$bot->xchnage,
                          "buy_price"=>$buy_price,
                          "opened"=>mktime(),
                          "status"=>"WaitingForPurchase",
                          "strategy"=>$latest_candle['strat'],
                          "log"=>"CREATED",
						  "uuid"=>$order['orderId'],
						  "quantity"=>$quantity,
						  "timeframe"=>$bot->timeframe
                         ]; 
                   $latest_candle['orderId'] = $to_db['uuid'];
					$latest_candle['price'] = $buy_price;
                   if($order['status']==="FILLED"){
                       $to_db['status'] = "active";
                       $latest_candle['status'] = 'active';
                   }
                    $latest_candle['timeframe'] = $bot->timeframe;
                 $latest_candle['opened'] = $to_db["opened"];
                   $this->db->insert('signals', $to_db);
                   //print_r($to_db);
                   $latest_candle['id'] = $this->db->id();
                   //die();
                   $bot->signals[$latest_candle['strat']][$market] = $latest_candle;
                              //insert to db, everything db related will fire up an exec() command that will do its work in order not to block or delay the price scanning because of db  
				   //$this->sendTelegram("");
				   if($bot->telegram !== null){
					               $msg .= $to_db["market"]." - ".$to_db["exchange"]."\n";
            $msg .= "Strategy:".$to_db["strategy"]."\n";
            $msg .= "Buy price: ".$to_db["buy_price"]."\n";
            //$msg .= "Sell price: ".$signals[$i]["sellPrice"]."\n";
            $msg .= "Status: ".$to_db['status']."\n";
            $msg .= "Candles: ".$to_db["timeframe"]."\n";
            //$msg .= "Total Time: ".$minutes."m\n";
            //$msg .= "Total Profit/Loss %: ".$olp."%\n";
						$bot->telegram->sendMessage($msg);
					}
				echo 'proccess and send buy order'.PHP_EOL;
                return true;
               }
               
                //print_r($this->signals);
                //die();
                
               // file_put_contents("cmnds/".getmypid().mktime()."postOpen.cmnd",'["openSignals"]');


                
            }
            else{
				if($bot_settings->debug){
					$this->files->addContent($colors->info("Can not purchase, too many open signals."));
				}
				//
                return null;
            }
            
        }
        else {
			echo "open signal, checking dca...";
			if(isset($latest_candle['DCA'])){
					if($bot->xchnage === "binance"){
                $all_prices = $api->bookPrices();
                $buy_price =  $all_prices[$market]['ask'];
			}
				else if($bot->xchnage === "bittrex"){
					$coin_arr = $bot->exchangeObject->to_market_format("bittrex",[$market]);
					$bp = $client->getTicker($coin_arr[0]);
					$bp = json_decode(json_encode($bp), true);
					$buy_price = $bp['Ask'];
				}
             	
				$total_invested = $bot->signals[$latest_candle['strat']][$market]['quantity']*$bot->signals[$latest_candle['strat']][$market]['buy_price'];
				$buy_quanity = $total_invested*2;
				$buy_quanity = round($buy_quanity / $buy_price);
				$total_quan = $buy_quanity + $bot->signals[$latest_candle['strat']][$market]['quantity'];
				
				
				
				/////////////////////////////////////////////////////
				
			   $msg .= "----REAL TRADE DCA / BotV2-----\n";
                   $quantity = $buy_quanity;
				   if($quantity <= 0){
					   if($bot_settings->debug){
					   $this->files->addContent("Failed with buy: quantity is 0");
					   }
					   return null;
				   }
                   //$order['status'] = "FILLED";
                   //$order['clientOrderId']="4";
                    $latest_candle['quantity'] = $quantity;
                   //$order['orderId'] = 1;
				   if($bot->xchnage === "binance"){
					   $order = $api->marketBuy($market, $quantity);
				   
				   }
 				   else if($bot->xchnage === "bittrex"){
					   $tmp = $bot->exchangeObject->to_market_format("bittrex",[$market]);
					   $order = $client->buyLimit($tmp[0], $quantity,$buy_price);
					    $order = json_decode(json_encode($order), true);
					   $tmp =  json_decode(json_encode($client->getOrder($order['uuid'])), true);
					   $order['status'] =$tmp['Closed'];
					   if($order['status']!==null){
						    $order['status'] = "FILLED";
					   }
					   else{//cancel if not filled, bittrex
						   $client->cancel($order['uuid']);
						   return null;
					   }
					   $order['orderId'] = $order['uuid'];
				   
				   }
                                     
				   if(!isset($order['status'])){
					   if($bot_settings->debug){
					   $this->files->addContent("Failed with DCA buy: ".json_encode($order));
					   }
					   return null;
				   }
                   //$latest_candle['status'] = 'WaitingForPurchase';
				$total_invested += $buy_quanity*$buy_price;
				$new_avg_price = $total_invested/$total_quan;
			$to_db=[];
				$to_db = [
                          "buy_price"=>$new_avg_price,
					"quantity"=>$total_quan,
                          "log"=>"DCAed|".$bot->signals[$latest_candle['strat']][$market]["log"],"uuid"=>$order['orderId']
                         ]; 
                   $latest_candle['orderId'] = $to_db['uuid'];
                   if($order['status']==="FILLED"){
                       $to_db['status'] = "active";
                       $latest_candle['status'] = 'active';
                   }
				else{//cancel if not filled, binance
					$api->cancel($market,$order['orderId']);
					return null;
					
				}
				
				$id = $bot->signals[$latest_candle['strat']][$market]['id'];
				 $this->db->update('signals', $to_db,["id" => $id]);
				   if($bot->telegram !== null){
					               $msg .= $market." - ".$bot->xchnage."\n";
            $msg .= "Strategy:".$latest_candle['strat']."\n";
					    $msg .= "Buy price: ".$buy_price."\n";
            $msg .= "New Avg. Price (old): ".$to_db["buy_price"]."(".$bot->signals[$latest_candle['strat']][$market]['buy_price'].")\n";
			$abc = $total_invested-$buy_quanity*$buy_price;
           $msg .= "Total Invested so far (now): ".$total_invested." (".$abc.")\n";
            $msg .= "Candles: ".$bot->timeframe."\n";
            //$msg .= "Total Time: ".$minutes."m\n";
            //$msg .= "Total Profit/Loss %: ".$olp."%\n";
						$bot->telegram->sendMessage($msg);
					}
				$bot->signals[$latest_candle['strat']][$market]['price'] = $to_db["buy_price"];
				$bot->signals[$latest_candle['strat']][$market]['buy_price'] = $to_db["buy_price"];
				$bot->signals[$latest_candle['strat']][$market]['quantity'] = $to_db["quantity"];
				$bot->signals[$latest_candle['strat']][$market]["log"] = $to_db['log'];
				$bot->signals[$latest_candle['strat']][$market]["DCAed"]++;
				unset($bot->signals[$latest_candle['strat']][$market]['DCA']);
				echo 'proccessed DCA and filled buy order'.PHP_EOL;				
				
				
	////////////////////////////////////////////////////

	  return true;
				

			}
            //check and do DCA; case no DCA return null;
            
          
        }
        
    }
    private function checkSpreadPriceAskorBid($bid_ask_price,$price){
        return abs(($price-$bid_ask_price)/$bid_ask_price*100);
    }//"maxSpreadCheckedPriceToAsk": 0.3 //maybe put this function in Bot or Strategy class, and use it inside the buy_sell_nothing function in bot class.
    
    
    
    public function proccess_sellSignal($latest_candle){
        global $api,$cli_args,$bot,$colors,$simulation_finished_trades,$client,$bot_settings;
        $market = $latest_candle['market'];
		$latest_candle['exchange'] = $bot->xchnage;
        if($this->isOpenPositionMarket($market,$latest_candle['strat']) && $bot->signals[$latest_candle['strat']][$market]['status']==="active"){
             //send sell order, ask for db change
							if(isset($cli_args["--backtesting"])){
					$sell_price = $latest_candle['price'];
				}
				   else{
			if($bot->xchnage === "binance"){
                $all_prices = $api->bookPrices();
                $sell_price =  $all_prices[$market]['bid'];
			}
				else if($bot->xchnage === "bittrex"){
					$coin_arr = $bot->exchangeObject->to_market_format("bittrex",[$market]);
					$bp = $client->getTicker($coin_arr[0]);
					$bp = json_decode(json_encode($bp), true);
					$sell_price = $bp['Bid'];
				}
				   }
                $latest_candle['spread_price_bid'] = $this->checkSpreadPriceAskorBid($sell_price,$latest_candle['price']);
            
                if($latest_candle['spread_price_bid'] > $latest_candle['max_spread'] && !isset($latest_candle['forceSell'])){
					if($bot_settings->debug){
                    $this->files->addContent($colors->info("Wanted to sell, but spread is too big".json_encode($latest_candle)));
					}
                                          return null;
                    
                }
			$msg = '';
            if($GLOBALS['isSimulator']===true){
				$msg .= "----SIMULATION TRADE ENDED / BotV2-----\n";
				if(file_exists("simulations/".$bot->sim_id.".json")){
					$simulation_finished_trades = json_decode(file_get_contents("simulations/".$bot->sim_id.".json"), true);
				}
				else if(file_exists("backtesting_results/backtesting.".$bot->sim_id.".json")){
					$simulation_finished_trades = json_decode(file_get_contents("backtesting_results/backtesting.".$bot->sim_id.".json"), true);
				}
				$latest_candle['close_time'] = date("H:i:s d/m");
				$simulation_finished_trades[$market] = $simulation_finished_trades[$market] ?? [];
 $simulation_finished_trades[$market]['details'] = ['startegy'=>$latest_candle['strat'], 'timeframe'=>$bot->timeframe, "exchange"=>$bot->xchnage,'total_closed_signals' => count($simulation_finished_trades[$market])];
                
$simulation_finished_trades[$market][] = ['buy'=>$bot->signals[$latest_candle['strat']][$market],"sell"=>$latest_candle, "profit_loss_percent"=> ($latest_candle['price']-$bot->signals[$latest_candle['strat']][$market]['price'])/$bot->signals[$latest_candle['strat']][$market]['price']*100];
                
$simulation_finished_trades[$market]['details'] = ['startegy'=>$latest_candle['strat'], 'timeframe'=>$bot->timeframe, "exchange"=>$bot->xchnage, 'total_closed_signals' => count($simulation_finished_trades[$market])];
                //$bot->simulation_finished_trades = $simulation_finished_trades;
				$bot->simulation_finished_trades=[];
               if(isset($cli_args['--backtesting'])){
                   file_put_contents("backtesting_results/backtesting.".$bot->sim_id.".json",json_encode($simulation_finished_trades, JSON_PRETTY_PRINT).PHP_EOL);
				   $simulation_finished_trades= [];
               } 
                else{
                    file_put_contents("simulations/".$bot->sim_id.".json",json_encode($simulation_finished_trades, JSON_PRETTY_PRINT).PHP_EOL);
					$simulation_finished_trades= [];
                }
                unset($bot->signals[$latest_candle['strat']][$market]);
                
                
            }else{
				
			$msg .= "----REAL TRADE ENDED / BotV2-----\n";
                
				
				
				
                    $quantity = $bot->signals[$latest_candle['strat']][$market]['quantity'];//change to quantity calculation..
                   //$order['status'] = "FILLED";
                   //$order['clientOrderId']="4";
                    
				
				   if($bot->xchnage === "binance"){
					   $order = $api->marketSell($market, $quantity);
				   
				   }
 				   else if($bot->xchnage === "bittrex"){
					   $tmp = $bot->exchangeObject->to_market_format("bittrex",[$market]);
					   $order = $client->sellLimit($tmp[0], $quantity,$sell_price);
					   $order = json_decode(json_encode($order), true);
					   $tmp = json_decode(json_encode($client->getOrder($order['uuid'])), true);
					   $order['status'] =$tmp['Closed'];
					   if($order['status']!==null){
						    $order['status'] = "FILLED";
					   }
					   $order['clientOrderId'] = $order['uuid'];
				   
				   }
                    				
				
				
				
				if(!isset($order['clientOrderId'])){
					//unset($this->signals[$latest_candle['strat']][$market]);
					if($bot_settings->debug){
					$this->files->addContent("Failed with sell: ".json_encode($order));
					}
					return null;
				}
                   $to_db = [];
                   $to_db = [
                          "sell_price"=>$sell_price,
                          "closed"=>mktime(),
                          "status"=>"WaitingForSell",
                          "strategy"=>$latest_candle['strat'],
                          "log"=>"SentSellOrder|".$bot->signals[$latest_candle['strat']][$market]["log"],"uuid"=>$order['clientOrderId']
                         ]; 
				$id = $bot->signals[$latest_candle['strat']][$market]['id'];
				$buy_data = $bot->signals[$latest_candle['strat']][$market];
                   if($order['status']==="FILLED"){
					   
                       $to_db['status'] = "finished";
					   
					   	$bot->simulation_finished_trades[$market]['details'] = ['startegy'=>$latest_candle['strat'], 'timeframe'=>$bot->timeframe, 'total_closed_signals' => count($simulation_finished_trades[$market])];
                $bot->simulation_finished_trades[$market][] = ['buy'=>$bot->signals[$latest_candle['strat']][$market],"sell"=>$latest_candle, "profit_loss_percent"=> ($latest_candle['price']-$bot->signals[$latest_candle['strat']][$market]['price'])/$bot->signals[$latest_candle['strat']][$market]['price']*100];
					   
          
					   
					   
					   
                       unset($bot->signals[$latest_candle['strat']][$market]);
					   
                   }
					   	if($bot->telegram !== null){
							//$buy_data = $bot->signals[$latest_candle['strat']][$market];
							$sell_data = $latest_candle;
				$minutes = round(($to_db['closed']-$buy_data['opened'])/60,2);
				$olp = round(($sell_price-$buy_data["price"])/$buy_data["price"]*100,2);
							$btc_pl = round($buy_data['quantity']*$buy_data["price"]*($olp/100),8);
					               $msg .= $market." - ".$to_db["exchange"]."\n";
            $msg .= "Strategy:".$to_db["strategy"]."\n";
            $msg .= "Buy price: ".$buy_data["price"]."\n";
            $msg .= "Sell price: ".$sell_price."\n";
            //$msg .= "Score: ".$signals[$i]["uuid"]."\n";
            $msg .= "Candles: ".$bot->timeframe."\n";
			$msg .= "Status: ".$to_db['status']."\n";
            $msg .= "Total Time: ".$minutes."m\n";
            $msg .= "Total Profit/Loss %: ".$olp."%\n";
			$msg .= "Total Profit/Loss BTC: ".$btc_pl." btc\n";
			$bot->telegram->sendMessage($msg);
					}
                //if order is not filled
                   $this->db->update('signals', $to_db,["id" => $id]);
				
				$to_db = array_merge($to_db,$latest_candle);
        }
            
          
            //reload coins to make sure coins from singels and not from vol are stopped scanning
            if(!isset($cli_args['--backtesting'])){
                //$api->loop->stop(); 
                //$bot->fillCoinsArr();check what to do, possible binance ban coz of this
                //$this->files->addContent($colors->warning("Reloaded coins"));           
            }

            //end reload
            return true;
            //spread is between 0 and +x; meaning actual sell price is x precent above below last trade price;
        }
        else{
            echo ' nothing to sell';
            return false;
            
        }
        
    }
    
    public function isOpenPositionMarket($market,$strat){
		global $bot;
        //print_r($this->signals[$strat][$market]);
        return isset($bot->signals[$strat][$market]);
    }
}
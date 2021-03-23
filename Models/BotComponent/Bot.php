<?php
namespace Models\BotComponent;
//to do: genrealize exhange functions and  extact each excahnge to diffrent class, with base, baseExchange, all functions should be abstract
class Bot {
    public $strategies = [];
    public $opens=[];
    public $closes=[];
    public $highs=[];
    public $lows=[];
    public $min_vol=700;
    public $signals_cls;
    public $strategies_cls;
    public $OHLVC=[];
    public $coins_array = [];
    public $ignore_coins =[];
    public $thisInstance = [];
    public $timeframe;
    public $simulator = true;
    public $colors = "";
	public $xchnage = "";
    public $simulation_finished_trades =[];
	public $btrx_candles;
	public $btrx_coins_format;
	public $sim_id;
    public $telegram=null;
    public $api;
    public $filesCls;
    public $mainCoinToTradeVersus = "BTC";

    public function run(){
        $this->filesCls->debug("run start");
        $this->addApiLoopTimers();
        $this->filesCls->debug("timers added");
        if($this->isBacktesting){
            $this->filesCls->debug("backtesting start");
            $this->runBackTesting();
            die();
        }
        $this->filesCls->debug("before loop start");
        $this->startLoop();
    }

    private function runBackTesting(){
        $this->filesCls->addContent($this->colors->info("Starting backtesting on ".count($this->coins_array)." coins ... "));
        foreach($this->coins_array as $coinName){//$coinName is market
            $OHLVC = $this->exchangeObject->getCoinOHLVC($coinName);
            for($i=100;$i<count($OHLVC);$i++){ //change 100 to $candlesToTest and get data from json
                $reply = $this->checkOHLVCforSignals(array_slice($OHLVC,0,$i),$coinName);
            }
            $this->filesCls->addContent($this->colors->info("DONE: $coinName, candles checked:".count($OHLVC)));
        }
        $this->filesCls->addContent($this->colors->info("Finished backtesting on ".count($this->coins_array)." coins ... Bye now."));
        die();
    }

    private function startLoop(){
        global $cli_args,$bot_settings,$filesCls,$cmnds,$api,$data,$bot,$settings,$banned;
        //if simulation or real trade mode
        $empry_arr_count = 0;
        $count = 0;
        while(true){
            $this->exchangeObject->runStreamLoop();
        }
    }

    private function addApiLoopTimers(){
        //api loop
        if(is_null($this->api->loop)){
            $this->filesCls->addContent("no loop for api, exchnage: $this->xchnage, api: ".print_r($this->api, true));
            return;
        }
        $this->addTimerCheckOpenOrders();
        $this->addTimerExecCommands();
        $this->addTimerReloadOHLVC();
        // api loop end
    }

    private function addTimerReloadOHLVC(){
        $this->exchangeObject->addTimerReloadOHLVC();
    }

    private function addTimerExecCommands(){
        $cmnds = $this->cmnds;
        $this->api->loop->addPeriodicTimer(2, function() use ($cmnds) { //echk every 60 seconds 
            $cmnds->checkAndexecNewCommands();
        });
    }

    private function addTimerCheckOpenOrders(){
        global $bot,$api,$client;
        if ($GLOBALS['isSimulator']===true  || is_null($this->api->loop)) {
            return;
        }
        $client = $this->client;
        $api = $this->api;
        $bot = $this;
        $this->api->loop->addPeriodicTimer(60, function() use (&$bot,$api,$client) { //echk every 60 seconds if open signals waiting for purchase or sell are filled
        //maybe add also checkAndexecNewCommands in diffrent timer, this one runs only on real trade mode
            foreach($bot->signals as $strategy=>$signals){
                foreach($signals as $market_name=>$signal){
                    if ($signal['status']=='active') {
                        continue;
                    }
                    if(!isset($signal['orderId']) && isset($signal['uuid'])){
                        $signal['orderId'] = $signal['uuid'];
                        // i dont remeber if i use uuid or orderId and when :\
                    }
                    $orderstatus = $this->exchangeObject->getOrderStatus($signal['orderId'], $market_name);
                    if(isset($orderstatus['orderStatus'])){
                        continue;
                                //unset($bot->signals[$strategy][$market_name]);
                    }
                    if ($orderstatus['orderStatus'] !== "FILLED") {
                        continue;
                    }
                    if($bot->telegram!==null){
                        $bot->telegram->sendMessage(" Order executed @  ".$bot->xchnage." Details: ".print_r($bot->signals[$strategy][$market_name]));						
                    }
            
                    $bot->signals[$strategy][$market_name]['status'] = $bot->signals[$strategy][$market_name]['status'] === 'WaitingForPurchase' ? 'active' : 'finished';
                            //add here the actual price the trade ended and update into db the sell price, $final_price = $orderstatus['price']; $signal['status'] = $final_price; 
                    if($bot->signals[$strategy][$market_name]['status']==="active"){
                            //send update to db about signal status, $signal['status']="active";
                            
                    }
                    else if($bot->signals[$strategy][$market_name]['status']==="finished"){
                        //send update to db before unsetting the var, $signal['status']="finished"
                        unset($bot->signals[$strategy][$market_name]);
                    }                 
                }
            }
        });
    }

    public function init(){
        global $user_settings, $settings;
        if(isset($user_settings->comm->telegram->bot_toekn) && isset($user_settings->comm->telegram->tele_user_id)){
			$this->telegram = new \Models\Messaging\Telegram($user_settings->comm->telegram->bot_toekn,$user_settings->comm->telegram->tele_user_id);	
        }
        $this->cmnds = new Commands();
        $this->filesCls->debug("init start");
        $this->initBotDataFromCliArgs();
        $this->initInstanceSettingsFromFile();
        $this->exchangeObject = Exchanges\Base::getExchangeObject($this->xchnage, $this);
        $this->initApiAndSocket();
        $this->fillStrategies();
        $this->signals = $this->signals_cls->getOpeningSignals($this->strategies,$this->timeframe,$this->xchnage);
        $this->fillCandles();
        $this->registerPid();
        if($this->telegram!==null){
			$this->telegram->sendMessage("started ".$this->cli_args['--name']);
		}
		
        echo "started ".$this->cli_args['--name'].PHP_EOL;
        
    }

    private function registerPid(){
        $to_pid['coins'] = $this->coins_array;
        $to_pid['strats'] = array_keys($this->strategies);
        $to_pid['name'] = $this->cli_args['--name'];
		$to_pid['exchange'] = $this->xchnage;
		$to_pid['isSimulator'] = $GLOBALS['isSimulator'];
		$to_pid['timeframe'] = $this->timeframe;
		if($this->sell_only_mode){
			$to_pid['SOM'] = true;
		}
        $this->filesCls->register_pid_to_file(json_encode($to_pid));
        $this->filesCls->addContent($this->colors->success("Bot instance created successfully ..."));
    }

    private function fillCandles(){
        $this->fillCoinsArr();
        $this->exchangeObject->fillCandles();
    }

    private function fillStrategies(){
        $this->filesCls->addContent($this->colors->info("Loading Strategies ... "));
        foreach($this->thisInstance["strategy"] as $sName){
            if($sName==="allActive"){
                $this->strategies = $this->strategies_cls->active_strategies; 
                //print_r($this->strategies);
                //die();
            }else{
                if(isset($this->strategies_cls->strategies[$sName]) && !isset($this->strategies[$sName])){
                  $this->strategies[$sName] = $this->strategies_cls->strategies[$sName];        
                   // $this->filesCls->addContent($this->colors->info("Loaded Strategy:  $sName".PHP_EOL));
                } 
                else if(isset($this->strategies[$sName])){
                    $this->filesCls->addContent($this->colors->warning("Already loaded $sName ..."));
                }
                else{
                    $this->filesCls->addContent($this->colors->error("Could not load strategy $sName ... "));
                }

            }
        }
        foreach($this->strategies as $strat_name=>$sta){
            $this->filesCls->addContent($this->colors->info("Loaded Strategy:  $strat_name".PHP_EOL));
        }
    }

    private function initApiAndSocket(){
        $this->api = $this->exchangeObject->initApi();
        $api = $this->api;
        $this->client = $this->exchangeObject->initClient();
        $client = $this->client;
        $this->filesCls->addContent($this->colors->info("Exchange: ".$this->xchnage));
    }
    
    function __construct(){
        global $cli_args,$instances_on_start,$bot_settings,$filesCls,$colors,$api,$client,$user_settings;
        $this->signals_cls = new Signals;
        $this->strategies_cls= new Strategies;
        $this->filesCls = new FilesWork;
        $this->colors = $colors;
        $this->cli_args = $cli_args;
        $this->instances_on_start = $instances_on_start;
        $this->user_settings = $user_settings;
    }

    public function fillCoinsArr(){
        global $filesCls,$cli_args;
        $this->coins_array = [];
        $this->coins_array = $this->get_top_x_coins($this->min_vol);
        //add all binance coins with volume over x
        //add all include coins fron conf file
        //add all coins that have active signals
        //print_r($this->signals);
        //die();
        foreach($this->signals as $strategy_name => $signal){
            $this->coins_array = array_merge($this->coins_array, array_keys($signal));
        }
        foreach($this->include_coins as $coin){
            $this->coins_array[]=$coin.$this->mainCoinToTradeVersus;
        }
		foreach($this->ignore_coins as $coin){
			if (($key = array_search($coin.$this->mainCoinToTradeVersus, $this->coins_array)) !== false) {
    			unset($this->coins_array[$key]);
			}
		}
        //remove coins already running on other instances with the exact strategy
        if(isset($cli_args['--backtesting'])){
            return null; // if backtesting, do not take out coins
        }
        ////backtesting doesnt go below this poing in the function!!!!! /////
        $this->removeCoinsWithSameStretageyAndnTimeFrame();
    }

    private function removeCoinsWithSameStretageyAndnTimeFrame(){
        $pids_arr =$this->filesCls->getPIDFileCon();
        if($pids_arr == null){
            return;
        }
        foreach($pids_arr as $pid_num=>$pid_data){
            if ($pid_num==getmypid()) {
                continue;
            }
            $pid_data = json_decode($pid_data,true);
            //print_r($pid_data);
            //die();
            if (!isset($pid_data["strats"])) {
                continue;
            }
            foreach($pid_data["strats"] as $strategy){
                if (!isset($this->strategies[$strategy])) {
                    continue;
                }
                foreach($pid_data["coins"] as $coin_name){
                    foreach($this->coins_array as $index => $coin_n){
                        if($coin_n !== $coin_name){
                            continue;
                        }
                        if($pid_data["timeframe"] !== $this->timeframe){
                            continue;
                        }
                        if($pid_data["exchange"] !== $this->xchnage){
                            continue;

                        }
                        if($GLOBALS['isSimulator'] !== $pid_data['isSimulator'] ){
                            $this->filesCls->addContent("Ignoring $coin_name, running with same '$strategy' strategy on other instance named '".$pid_data['name']."'. ");
                            unset($this->coins_array[$index]);
                        }
                    }
                }
            }                                 
        }
    }

    public function get_top_x_coins($vol){
        global $bot_settings;
        $coinsa = $this->exchangeObject->getCoinsByVolume($vol);
        return count($coinsa) > 0 ? $coinsa : [];
    }

    public function initBotDataFromCliArgs(){
        if(!isset($this->cli_args['--name'])){
            $this->filesCls->addContent($this->colors->error("Instance must have a --name argument"));
            die();
        }
        if (!isset($this->instances_on_start[$this->cli_args['--name']]) && !isset($this->cli_args['--onTheFly'])) {
            $this->filesCls->addContent($this->colors->error("could not find instance name on conf.json or other instance file"));
            die();
        }
        if(isset($cli_args['--backtesting'])){
            $this->thisInstance["simulator"] = true;
            register_shutdown_function('remove_pid_from_file',getmypid(),$bot_settings->all_pids_file);
        }
        $this->name=$this->cli_args['--name'];
        $this->isBacktesting = isset($this->cli_args['--backtesting']);
        $this->filesCls->addContent("phpCryptoTradingBotV0.1///$this->name///");
    }

    private function initInstanceSettingsFromFile(){
        global $on_the_fly_file;
        if(isset($this->cli_args['--onTheFly'])){
            if(!file_exists($on_the_fly_file)){
                $this->filesCls->addContent($this->colors->error("file '$on_the_fly_file' does not exist, new instance shutdown, cannot load --onTheFly"));
                die();                   
            }
            $instances_on_the_fly = json_decode(file_get_contents($on_the_fly_file),true);
            $this->thisInstance = $instances_on_the_fly[$this->name];
            $this->filesCls->addContent($this->colors->info("ON THE FLY Fired up with these settings: ".json_encode($instances_on_the_fly[$this->name], JSON_PRETTY_PRINT)));
        }else{
            $this->thisInstance = $this->instances_on_start[$this->name];
        }
        if(empty($this->thisInstance)){
            $this->filesCls->addContent('instance not found');die();
        }
        $this->ignore_coins = $this->thisInstance["ignore_coins"];

        $this->include_coins = $this->thisInstance["include_coins"];
        $this->min_vol = $this->thisInstance["min_vol"];
        $this->timeframe = $this->thisInstance["timeframe"];
        $this->sell_only_mode = $this->thisInstance["sell_only_mode"];
        $this->max_open_signals = $this->thisInstance["max_open_signals"];
        $this->max_btc_per_trade = $this->thisInstance["max_btc_per_trade"];
        $this->simulator = $this->thisInstance["simulator"];
        $this->xchnage = $this->thisInstance["exchange"];
        $this->sim_id = mktime().rand().$this->xchnage;
        $this->mainCoinToTradeVersus = $this->thisInstance['baseCoin'] ?? $this->mainCoinToTradeVersus;
        $GLOBALS['isSimulator'] = $this->thisInstance["simulator"];
        $this->filesCls->addContent($GLOBALS['isSimulator'] ? $this->colors->warning("SIMULATION MODE") : $this->colors->info("REAL TRADES MODE"));
    }
   
    private function intiate_OHLVC($OHLVC){
        $this->opens = [];
        $this->closes = [];
        $this->highs= [];
        $this->lows= [];
        $this->vols= [];
        $fixedIndexes = array_values($OHLVC);
        //print_r($fixedIndexes);
        foreach($fixedIndexes as $arr){
            $this->opens[] = $arr['open'];
            $this->closes[] = $arr['close'];
            $this->highs[]=$arr['high'];
            $this->lows[]=$arr['low']; 
            $this->vols[]=$arr['volume'];   
        
        }
        $this->OHLVC['opens'] = $this->opens;
        $this->OHLVC['closes'] = $this->closes;
        $this->OHLVC['highs'] = $this->highs;
        $this->OHLVC['lows'] = $this->lows;
        $this->OHLVC['vols'] = $this->vols;
    }
    
    public function latest_scans(){
        global $latest_scan_results;
        return $latest_scan_results;
        
    }
    
    function checkOHLVCforSignals($OHLVC,$market){
        global $bot_settings,$filesCls,$latest_scan_results;
        $this->intiate_OHLVC($OHLVC);
        $respond = [];
        foreach($this->strategies as $starteg){
            $start = $starteg['name'];
			$starteg['market'] = $market;
            $what_to_do1 = $this->buy_sell_nothing($starteg);
            $what_to_do=$what_to_do1[0];
            if($what_to_do==="no array error"){
				if($bot_settings->debug){
                $this->filesCls->addContent($this->colors->error("$market passed empty array for indicators/OHLVC.")); 
				}
				return null;
            }
            $what_to_do1['timeframe'] = $this->timeframe;
			$what_to_do1['exchange'] = $this->xchnage;
            $latest_scan_results[$start][$market]=$what_to_do1;
            $latest_scan_results[$start][$market]['price'] = $this->closes[count($this->closes)-1];
            $dca_data=[null,null];
            //exmpale dca settings: DCA: 10|instant|max_dca_times or DCA: 10|strat|max_dca_times
            /* how buy signal_arr looks  add scheme here, for buy ans sell */
            if($starteg['DCA'] !== false){ //
				$dca_data = explode("|",$starteg['DCA']);
				//if all condidons met{
				if(isset($this->signals[$strat['name']][$strat['market']]['profit'])){
					if($this->signals[$strat['name']][$strat['market']]['profit'] < $dca_data[0]){
						$this->signals[$start][$market]['DCA']=true; //DCA when there is buy signal and loss is greater than DCA setting
					}
					else{
						//if loss was greater then dca and became lower without purchase, cancel dca
						unset($this->signals[$start][$market]['DCA']);
					}
				}
				
				//}
			
			}
            
            $signal_arr = ["price" => $this->closes[count($this->closes)-1],"market" => $market, "strat" => $start];
            //change price to bidqask intead of close;
            if ($what_to_do==='buy'  || isset($this->signals[$start][$market]['DCA'])){
				if(isset($this->signals[$start][$market]['DCA'])){
					if(isset($dca_data[2])){
						if($dca_data[2] >= $this->signals[$strat['name']][$market]['DCAed']){
							unset($this->signals[$start][$market]['DCA']);
							return null;
							
						}
					}
					if($dca_data[1]==="instant"){
						$signal_arr['DCA'] = "yes";
					}
					else if($dca_data[1]==="strat"){
						if($what_to_do==='buy'){
							$signal_arr['DCA'] = "yes";
						}
						else{
							return null;
						}
					}
					else{
						$signal_arr['DCA'] = "yes";
					}
				}
                $respond[$market][$start]['action'] = 'buy';
                $signal_arr['type'] = 'buy';
                $signal_arr['max_spread'] = $starteg['buyCon']["maxSpreadCheckedPriceToAsk"];
                if($this->signals_cls->proccess_buySignal($signal_arr)){
                    $this->filesCls->addContent($this->colors->buy("BUY SIGNAL Proccessed".json_encode($signal_arr)));
					
                }

// check if i already have open signal for this market, if so check DCA or skup
                
            }
            else if ($what_to_do==='sell' || isset($this->signals[$start][$market]['forceSell'])){
				if(isset($this->signals[$start][$market]['forceSell'])){
					$signal_arr['forceSell'] = "yes";
				}
                $respond[$market][$start]['action'] = 'sell';
                $signal_arr['type'] = 'sell';
                $signal_arr['max_spread'] = $starteg['sellCon']["maxSpreadCheckedPriceToBid"];
                if($this->signals_cls->proccess_sellSignal($signal_arr)){
                    $this->filesCls->addContent($this->colors->sell("SELL SIGNAL Proccessed".json_encode($signal_arr)));                   
                }

                
            }
            else{
                $respond[$market][$start]['action'] = 'nothing';
                //print_r($respond);
            }
            $start = null;
        }
        $this->filesCls->msgToWeb(json_encode(['latestScansData',$this->latest_scans()]),$market.$start);
        return $respond;
    }
    
    private function buy_sell_nothing($strat){
        //echo $strat;$latest_scan_results
        if(!is_array($this->closes)){
            return ["no array error",[],[]];
            
        }
        //maybe add buy_price built-in indicator, pass it in $strat var
        $price = array_reverse($this->closes);
		$vol = array_reverse($this->vols);
        $buy_min_score = $strat['buyCon']['min_score'];
        $sell_min_score = $strat['sellCon']['min_score'];
		$profit = [0,-500];
		//added profit var, check if signal is open, if yes, check for profit and check against max profit, for trailing purposes, if max profit if larger then profit meaning down?
		if($this->signals_cls->isOpenPositionMarket($strat['market'],$strat['name'])){
			$profit = ($price[0]-$this->signals[$strat['name']][$strat['market']]['price'])/$this->signals[$strat['name']][$strat['market']]['price']*100;
			$this->signals[$strat['name']][$strat['market']]['profit'] = $profit;
			if(!isset($this->signals[$strat['name']][$strat['market']]['max_profit'])){
				$this->signals[$strat['name']][$strat['market']]['max_profit'] = $profit;
			}
			else{
				if($this->signals[$strat['name']][$strat['market']]['max_profit'] < $profit){
					$this->signals[$strat['name']][$strat['market']]['max_profit'] = $profit;
					
				}
			}
			$profit = [$profit,$this->signals[$strat['name']][$strat['market']]['max_profit']];
		}
        $buy_cons_results=[];
        $sell_cons_results=[];
        foreach($strat['indicators'] as $indicator_name=>$value){
            $indicators_arr[$indicator_name] = $this->fill_indicators([$indicator_name=>$value],$this->OHLVC);
            if(!is_array($indicators_arr[$indicator_name])){
                return ["no array error",[],[]];
                
            }
            $$indicator_name = array_reverse($indicators_arr[$indicator_name]);
            //$indicator_name_prev = $indicator_name."_prev";
            //$$indicator_name_prev = $indicators_arr[$indicator_name][count($indicators_arr[$indicator_name])-2];
        }
        
        $score=0;
        //print_r($indicators_arr);
        $cons_results = [];
        $indicators_arr = null;
		if($profit[1]===-500){// if there is no open signal, check for buy
        foreach($strat['buyCon']['conditions'] as $condition){
            //ho $condition;
            $tmp = $condition;
            $buy_cons_results[$tmp] = false;
            
            $condition = "return $condition;";
            if(eval($condition)){
                $buy_cons_results[$tmp] = true;
               $score++; 
            }
        }
        if($buy_min_score <= $score){
            return ['buy',$buy_cons_results,[]];
        }
		}
        else {//if there is open signal, check for sell
        $score=0;
        foreach($strat['sellCon']['conditions'] as $condition){
            //echo $condition;
            $tmp = $condition;
            $sell_cons_results[$tmp] = false;
            
            $condition = "return $condition;";
            if(eval($condition)){
                $sell_cons_results[$tmp] = true;
               $score++; 
            }
        }
        if($sell_min_score <= $score){
            return ['sell',[],$sell_cons_results];
        }
        			
		}

        return [null,$buy_cons_results,$sell_cons_results];
        //echo $score;
        
        
        /*switch($strat){
            case 'pSARswtich':
                $pSAR_arr = trader_sar($this->highs,$this->lows);
                $pSAR_latest = $pSAR_arr[count($pSAR_arr)-1];
                $pSAR_latest_prev = $pSAR_arr[count($pSAR_arr)-2];
                if($pSAR_latest < $latest_close && $pSAR_latest_prev > $latest_close_prev){
                    return 'buy';
                }
                elseif($pSAR_latest > $latest_close && $pSAR_latest_prev < $latest_close_prev){
                    return 'sell';
                }
                else{
                    return null;
                }
                
                break;
            case 'ema2050_cross':
                $pSAR_arr = trader_ema($this->closes,20);
                $pSAR_arr1 = trader_ema($this->closes,50);
                $pSAR_latest = $pSAR_arr[count($pSAR_arr)-1];
                $pSAR_latest_prev = $pSAR_arr[count($pSAR_arr)-2];
                $pSAR_latest1 = $pSAR_arr[count($pSAR_arr1)-1];
                $pSAR_latest_prev1 = $pSAR_arr[count($pSAR_arr1)-2];
                //echo "$latest_close, $pSAR_latest < $pSAR_latest_prev, $pSAR_latest1 > $pSAR_latest_prev1";
                if($pSAR_latest < $pSAR_latest1 && $pSAR_latest_prev > $pSAR_latest_prev1){
                    return 'sell';
                }
                elseif($pSAR_latest > $pSAR_latest1 && $pSAR_latest_prev < $pSAR_latest_prev1){
                    return 'buy';
                }
                else{
                    return null;
                }
                break;
            default:
                break;
        }*/
            
    }

    private function fill_indicators($indi,$candles_data){

        $highest_prices = $candles_data['highs'];
        $lowest_prices= $candles_data['lows'];
        $close_prices = $candles_data['closes'];
        $volumes = $candles_data['vols'];
        //print_r($close_prices);
        $open_prices = $candles_data['opens'];  
    $last_candle_open_price = $close_prices[count($close_prices)-1];
    $last_candle_close_price = $open_prices[count($close_prices)-1];
        foreach($indi as $name=>$setting){
            $name= preg_replace('/[0-9]+/', '', $name);;
            $filled = [];
            switch($name){
case 'open':
                    return $open_prices;
                    break;                
                case 'high':
                    return $highest_prices;
                    break;
                case 'pSAR':
                    return trader_sar($highest_prices,$lowest_prices,0.02,0.2);
                    break;
                case 'obv':
                    return trader_obv($close_prices,$volumes);
                    break;
                case 'adx':
                    if($setting===null || $setting < 1){
                        $setting = 14;// set default to 20;
                    }
                    return trader_adx($highest_prices,$lowest_prices,$close_prices,$setting);
                    break;
                case 'adx_plus_di':
                    if($setting===null || $setting < 1){
                        $setting = 14;// set default to 20;
                    }
                    return trader_plus_di($highest_prices,$lowest_prices,$close_prices,$setting);
                    break;
                case 'adx_minus_di':
                    if($setting===null || $setting < 1){
                        $setting = 14;// set default to 20;
                    }
                    return trader_minus_di($highest_prices,$lowest_prices,$close_prices,$setting);
                    break;
                case 'avgV':
                     $total_vol = 0;
                    $vol_avg_period = $setting;
                    $count1=0;
                    $ca = count($volumes)-1;
                    for($lki=$ca;$lki>=$ca-$vol_avg_period;$lki--){
                        $total_vol += $volumes[$lki];
                        //$candles_data[$ca]["avgV"] = array_sum($foo) / count($foo)
                        $count1++;
                    }
                    $avgV = $total_vol / $count1;  	
                    return array($avgV,$avgV);
                    
                    break;
                case 'candleColor':
                    $clr =  $last_candle_close_price - $last_candle_open_price;
                    // $cl > 0 = green, cl<0 = red
                    return array($clr,$clr);
                    break;
                case 'ema':
                    if($setting===null || $setting < 1){
                        $setting = 20;// set default to 20;
                    }
                    return trader_ema($close_prices,$setting);
                    break;
                case 'emaSpread':
                    $emas = explode("|",$setting);
                    $ema0 = $emas[0];
                    $ema1 = $emas[1];
                    $ema_spreads=[];
                    $ema0_arr = trader_ema($close_prices,$ema0);
                    $ema1_arr = trader_ema($close_prices,$ema1);
                    $j=count($ema1_arr)-1;
                    $po=0;
                    for($i=count($ema0_arr)-1;$i>=0 && $j>=0;$i--){
                        $ema0_t = array_pop($ema0_arr);
                        array_unshift($ema_spreads,($ema0_t-array_pop($ema1_arr))/$ema0_t*100);
                        $po++;
                        if($po>100){
                            $i=-1;//stop the loop after 100 spreads...
                        }
                        $j--;
                    }//returns an array of spread % between ema0 and ema1, last var of array is latest spread
                    //print_r($ema_spreads);
                    return $ema_spreads;
                case 'emaLastCrossover':
                    $emas = explode("|",$setting);
                    $ema0 = $emas[0];
                    $ema1 = $emas[1];
                    $ema_lastcrossover=[];
                    $ema_spreads=[];
                    $ema0_arr = trader_ema($close_prices,$ema0);
                    $ema1_arr = trader_ema($close_prices,$ema1);
                    $cnadles_ago = 0;
                    $j=count($ema1_arr)-1;
                    $i=count($ema0_arr)-1;
                    if($ema0_arr[$i]-$ema1_arr[$j]>=0){
                        $weare = 'pos';
                    }
                    else{
                        $weare = 'neg';
                    }
                    $po=0;
                    for($i=count($ema0_arr)-1;$i>=0 && $j>=0;$i--){
                        $ema0_t = array_pop($ema0_arr);
                        array_push($ema_spreads,($ema0_t-array_pop($ema1_arr))/$ema0_t*100);
                        $j--;
                        if($ema_spreads[count($ema_spreads)-1]>=0 && $weare ==='neg'){
                            $i=-1;
                        }
                        else if($ema_spreads[count($ema_spreads)-1]<0 && $weare ==='pos'){
                            $i=-1;
                        }
                        $cnadles_ago++;
                    }//return last crossover of ems0 and ems1
                    //print_r($ema_spreads);
                    //echo $cnadles_ago;
                    //die();
                    return array($cnadles_ago,$cnadles_ago);
                    break;
                case 'rsi':
                    if($setting===null || $setting < 1){
                        $setting = 14;// set default to 20;
                    }
                    return trader_rsi($close_prices,$setting);
                    break;
                case 'StochasticOscillator':
                    $result = trader_stoch($highest_prices,$lowest_prices,$close_prices, 14, 3);
                    return ['D' => $result[1], 'K' => $result[0]];
                break;
                case 'bbands':
                    $bbands =  trader_bbands($close_prices,20,2,2);
                    switch($setting){
                        case 'upper':
                            return $bbands[0];
                            break;
                        case 'middle':
                            return $bbands[1];
                            break;
                        case 'lower':
                            return $bbands[2];
                            break;
                        default: 
                            return null;
                    }
                    break;
                default:
                    return null;
                    
                
            }
               
        }
    
       /* switch($indi){
            case 'rsi':
          $macds = trader_rsi($close_prices,14);
        foreach($macds as $candle_id => $indicator_value){
            $candles_data[$candle_id]["rsi"] = $indicator_value;
            
        }
                break;
            case 'ema':
          $macds = trader_ema($close_prices,200);
          $macds2 = trader_ema($close_prices,50);
          $macds3 = trader_ema($close_prices,10);
        //$stoch = $macds[0];
        //print_r($macds);
        foreach($macds as $candle_id => $indicator_value){
            $candles_data[$candle_id]["ema_200"] = $indicator_value;
            
        }
        foreach($macds2 as $candle_id => $indicator_value){
            $candles_data[$candle_id]["ema_50"] = $indicator_value;
            
        }
        foreach($macds3 as $candle_id => $indicator_value){
            $candles_data[$candle_id]["ema_10"] = $indicator_value;
            
        }                 
                break;
            case 'trend_line':
                $ang = trader_ht_trendline($close_prices);
                $lin_ang = $ang;
                print_r($lin_ang);
                break;
            case 'stoch':
          $macds = trader_stoch($highest_prices,$lowest_prices,$close_prices,5,3,TRADER_MA_TYPE_SMA,3,TRADER_MA_TYPE_SMA);
        $stoch = $macds[0];
        //print_r($macds);
        foreach($stoch as $candle_id => $indicator_value){
            $candles_data[$candle_id]["stoch"] = $indicator_value;
            
        }          
                break;
        case 'pSAR':
            //print_r($highest_prices);
            //$h_prices_pSAR[$i] = $candles_data[$i]["H"]*1000000;
        $macds = trader_sar($h_prices_pSAR,$l_prices_pSAR,0.02,0.2);
       // print_r($macds);
        $pSAR = $macds;
        foreach($pSAR as $candle_id => $indicator_value){
            $candles_data[$candle_id]["pSAR"] = $indicator_value/1000000;
            
        }
            break;
        case 'DMI':
    $macds1 = trader_plus_di($highest_prices,$lowest_prices,$close_prices,12);
    $macds2 = trader_minus_di($highest_prices,$lowest_prices,$close_prices,28);
    $macds3 = trader_adx($highest_prices,$lowest_prices,$close_prices,28);
    $all['plus_di'] = $macds1;
    $all['minus_di'] = $macds2;
    $all['adx'] = $macds3;
        foreach($all['plus_di'] as $candle_id => $indicator_value){
            $candles_data[$candle_id]["adx_plus_di"] = $indicator_value;
            
        }
        foreach($all['minus_di'] as $candle_id => $indicator_value){
            $candles_data[$candle_id]["adx_minus_di"] = $indicator_value;
            
        }
        foreach($all['adx'] as $candle_id => $indicator_value){
            $candles_data[$candle_id]["adx"] = $indicator_value;
            
        }
    //print_r($macds);
    //echo json_encode($all);        
            break;
        case 'BBands':
    $macds1 = trader_bbands($close_prices_bollinger,20,2,2);
    //echo $close_prices_bollinger;
    //print_r($macds1);
        foreach($macds1[0] as $candle_id => $indicator_value){
            $candles_data[$candle_id]["bband_upper"] = $indicator_value/1000000;
            
        }
        foreach($macds1[1] as $candle_id => $indicator_value){
            $candles_data[$candle_id]["bband_middle"] = $indicator_value/1000000;
            
        }
        foreach($macds1[2] as $candle_id => $indicator_value){
            $candles_data[$candle_id]["bband_lower"] = $indicator_value/1000000;
            
        }
    //print_r($macds1);
    //echo json_encode($all);        
            break;
            default:
                break;
        }
        return  $candles_data;*/
        
    }

    public function getApi(){
        return $this->api;
    }
}

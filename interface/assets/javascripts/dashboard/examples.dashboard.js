latestCmndRcvd = Date.now();
total_trades=[];
type="";
function changeType(chTo){
	type=chTo;
}
setInterval(function(){
	if(Date.now() - latestCmndRcvd > 60000*2){
		addBotMsg('main',"Lost Connection with web socket server / bots crashed");
		//alert('Lost Connection with Bot (Last cmnd recieved > 2 minutes)');
		
		
	}
},60000);
function addBotMsg(inst,msg){
	times = new Date().valueOf();
	getElmByID("bot_msgs").innerHTML+='	<li id='+inst+times+'>										<a href="#" class="clearfix" onclick="removeMsg(\''+inst+times+'\')" >											<figure class="image">												<img src="assets/images/!sample-user.jpg" alt="Joseph Doe Junior" class="img-circle" />											</figure>											<span class="title">'+inst+'</span>											<span class="message">'+msg+'</span>										</a>									</li>';
									
}
function isMobileDevice() {
    return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
};
function convert(timestamp){

 // Unixtimestamp
 var unixtimestamp = timestamp;

 // Months array
 var months_arr = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

 // Convert timestamp to milliseconds
 var date = new Date(unixtimestamp*1000);

 // Year
 var year = "0" +  date.getFullYear();

 // Month
 //var month = months_arr[date.getMonth()];
var month = "0" + (date.getMonth()+1);
 // Day
 var day = "0" +date.getDate();

 // Hours
 var hours = "0" + date.getHours();

 // Minutes
 var minutes = "0" + date.getMinutes();

 // Seconds
 var seconds = "0" + date.getSeconds();

 // Display date time in MM-dd-yyyy h:m:s format
 var convdataTime = hours.substr(-2) + ':' + minutes.substr(-2) + ':' + seconds.substr(-2)+' '+day.substr(-2)+'/'+month.substr(-2)+'/'+year.substr(-2);
 
 return convdataTime;
 
}
function sleep(delay) {
         var start = new Date().getTime();
         while (new Date().getTime() < start + delay);
     }
     var socketBot;
     scansDataGlobal = {};
     var binanceSocket;
     var coins1 = "";

     function IsValidJSONString(str) {
         try {
             JSON.parse(str);
         } catch (e) {
             return false;
         }
         return true;
     }
function removeAlert(elementId) {
    // Removes an element from the document
    var element = document.getElementById(elementId);
    element.parentNode.removeChild(element);
	getElmByID("noti_count").innerHTML = document.getElementById('notifications').childElementCount;
	getElmByID("noti_count2").innerHTML = document.getElementById('notifications').childElementCount;
}
function removeMsg(elementId) {
    // Removes an element from the document
    var element = document.getElementById(elementId);
    element.parentNode.removeChild(element);
	getElmByID("msg_count").innerHTML = document.getElementById('bot_msgs').childElementCount;
	getElmByID("msg_count2").innerHTML = document.getElementById('bot_msgs').childElementCount;
}
function rmvAlerts(){
	getElmByID("notifications").innerHTML = "";
											 getElmByID("noti_count").innerHTML = 0;
										 getElmByID("noti_count2").innerHTML = 0;
}
     function addElement() {
         // create a new div element 
         var newDiv = document.createElement("div");
         // and give it some content 
         var newContent = document.createTextNode("Hi there and greetings!");
         // add the text node to the newly created div
         newDiv.appendChild(newContent);

         // add the newly created element and its content into the DOM 
         var currentDiv = document.getElementById("div1");
         document.body.insertBefore(newDiv, currentDiv);
     }
     global_prices = [];
total_pl=0;
jkl2=0;
										 getElmByID("noti_count").innerHTML = document.getElementById('notifications').childElementCount;
										 getElmByID("noti_count2").innerHTML = document.getElementById('notifications').childElementCount;

										 getElmByID("msg_count").innerHTML = document.getElementById('notifications').childElementCount;
										 getElmByID("msg_count2").innerHTML = document.getElementById('notifications').childElementCount;
     function initBinanceSocket() {
         var host1 = 'wss://stream.binance.com:9443/ws/' + coins1; // SET THIS TO YOUR 
         //alert(host1);
         try {
             binanceSocket = new WebSocket(host1);
             log('Binance Socket - status ' + binanceSocket.readyState);
             binanceSocket.onopen = function (msg) {
                 log("Binance Socket - status " + this.readyState);

             };
             binanceSocket.onmessage = function (msg) {
                 //console.log(msg);

                 coin = JSON.parse(msg.data);
                 updatePrice(Number.parseFloat(coin.p).toFixed(8), coin.s);
             };
             binanceSocket.onclose = function (msg) {
                 initBinanceSocket();
                 log("Disconnected Binance - status " + this.readyState);
             };
         } catch (ex) {
             log(ex);
         }
     }
     jkl = "";

     function initBotSocket() {
		 coins_names_old = [];
         var host = "ws://185.229.226.154:1337"; // SET THIS TO YOUR SERVER
         try {
             socketBot = new WebSocket(host);
			  if(page_n ==='main'){
             log('WebSocket - status ' + socketBot.readyState);
			  }

             socketBot.onopen = function (msg) {
				  if(page_n ==='main'){
                 log("Bot Socket - status " + this.readyState);
				  }
				 if(jkl===""){
					 if(page_n ==='main'){
						                  setInterval(function () {
										  jkl2 = 1;
										  if(document.hasFocus()){
										
									
					
											   socketBot.send('initWeb');
											  
							
										  }
                    
                 }, 60000);
				 setInterval(function () {
						 socketBot.send('btcValue');
                     
                 }, 60000*20);
						
						 
														
	
                 socketBot.send('initWeb');
				 socketBot.send('btcValue');
				 window.onfocus =  function(){
					 											 

			
					 socketBot.send('initWeb');
				 };
					 }
				 else if(page_n === 'lab'){
					 //alert('sent');
					 socketBot.send("finishedBacktestingsList");
				 }
					 setInterval(function () {
					 if(document.hasFocus()){
						socketBot.send('usageRamCpu');
					 }
                     
                 }, 5000);
					 jkl =1;
				 }


                 //socketBot.send('latestScansData'); 
             };
             socketBot.onmessage = function (msg) {
				 if(this.readyState > 0){
					 latestCmndRcvd = Date.now();
				 }
				 if(document.hasFocus()){
				 	 if(page_n ==='main'){
                 getElmByID("log").scrollTop = getElmByID("log").scrollHeight;
					 }
                 //console.log(msg);
                 
//alert(msg.data);
                     console.log(msg.data);
                     console.log("isValidJson", IsValidJSONString(msg.data));
                 if (IsValidJSONString(msg.data)) {
                     //$('latestScans').innerHTML="";
                     data_jsoned = JSON.parse(msg.data);
                     cmnd_sent = data_jsoned[0];
                     //alert(msg.data);
                     cmnd_answr = data_jsoned[1];
                     data_array = cmnd_answr;
					 	 if(page_n ==='main'){
                     if (cmnd_sent === 'latestScansData') {
						 if(isMobileDevice()){
							                         coins1 = "";
                         //data_array = JSON.parse(cmnd_answr);
                         coins = Object.values(data_array);

                         stratsNames = Object.keys(data_array);
                         stratsNames.forEach(function (stratName) {
                             conditions_spans = "";
                             coins_data = Object.values(coins);
                             coins_names = Object.keys(data_array[stratName]);
                             coins_names.forEach(function (coinName) {
							 global_prices[coinName] = parseFloat(data_array[stratName][coinName]['price']).toFixed(8);
							 }
												 );
						 });
							 return null;
						 }
                         coins1 = "";
                         //data_array = JSON.parse(cmnd_answr);
                         coins = Object.values(data_array);

                         stratsNames = Object.keys(data_array);
                         stratsNames.forEach(function (stratName) {
                             conditions_spans = "";
                             coins_data = Object.values(coins);
                             coins_names = Object.keys(data_array[stratName]);
                             coins_names.forEach(function (coinName) {
                                 //sleep(10);
                                 if (typeof coinName === "string") {
                                     coins1 += coinName + '@trade/';
                                 }

                                 conditions_spans = "";
                                 /*    <tr>
      <td id="BNBBTC">BNBBTC</td>
      <td id="conditions"><span class="con1_color">ema1 > ema0</span><br />
        <span class="con2_color">ema1 > ema0</span></td>
    <td id="timeframe">5m</td>
    </tr>*/
                                 style = "";
                                 if (data_array[stratName][coinName][0] === null) {
                                     //style=' style="background-color:gray"';
                                     style = 'label label-warning';
                                 } else if (data_array[stratName][coinName][0] === "buy") {
                                     style = 'label label-success';
                                 } else {
                                     style = 'label label-danger';
                                 }
                                 i = 0;
                                 trues = 0;
                                 cond_name_buys = Object.keys(data_array[stratName][coinName][1]);
                                 cond_name_sells = Object.keys(data_array[stratName][coinName][2]);
                                 //alert(cond_name_sells);
                                 conditions_spans = '<span style="margin-left: 10px;" class="label label-dark">' + data_array[stratName][coinName]['timeframe'] + "</span><br />";
                                 while (i < cond_name_buys.length) { // alert(cond_name_buys[i]);
                                     //alert(data_array[stratName][coinName][1][cond_name_buys[i]]);

                                     result = data_array[stratName][coinName][1][cond_name_buys[i]];
                                     //alert(result);
                                     if (result) {
                                         trues++;
                                         bgcolor = "#007c00";
                                     } else {
                                         bgcolor = "#800000";

                                     }
                                     //conditions_spans += '<div  class="cons" style="background-color:'+bgcolor+'">'+cond_name_buys[i]+'</div>';
                                     i++;
                                 }

                                 conditions_spans += '<div class="progress progress-striped m-sm"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="' + trues + '" aria-valuemin="0" aria-valuemax="' + i + '" style="width:' + trues / i * 100 + '%;">' + trues + '/' + i + '</div></div>';
                                 i = 0;
                                 trues = 0;
                                 while (i < cond_name_sells.length) {
                                     result = data_array[stratName][coinName][2][cond_name_sells[i]];
                                     //alert(result);
                                     if (result === true) {
                                         trues++;
                                         bgcolor = "#007c00";
                                     } else if (result === false) {
                                         bgcolor = "#800000";

                                     } else {
                                         //alert('fsd');
                                     }
                                     //conditions_spans += '<div class="cons" style="background-color:'+bgcolor+';">'+cond_name_sells[i]+'</div>';
                                     i++;
                                 }
                                 if (i === trues + 1 && getElmByID('trail' + coinName + stratName + data_array[stratName][coinName]['timeframe'] +data_array[stratName][coinName]['exchange']) !== null) {
                                     getElmByID('trail' + coinName + stratName + data_array[stratName][coinName]['timeframe'] +data_array[stratName][coinName]['exchange']).innerHTML = " Trailing ...";
                                 } else if (getElmByID('trail' + coinName + stratName + data_array[stratName][coinName]['timeframe'] +data_array[stratName][coinName]['exchange']) !== null) {
                                     getElmByID('trail' + coinName + stratName + data_array[stratName][coinName]['timeframe'] +data_array[stratName][coinName]['exchange']).innerHTML = "";
                                 }
                                 conditions_spans += '<div class="progress progress-striped m-sm"><div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="' + trues + '" aria-valuemin="0" aria-valuemax="' + i + '" style="width:' + trues / i * 100 + '%;">' + trues + '/' + i + '</div></div><div class="clearfix"></div> <hr />';

                                 if (!getElmByID('1' + coinName)) {
                                     //alert(data_array[stratName][coinName][0]);
                                     // console.log(data_array[stratName][coinName])    ;
                                     //alert(data_array[stratName][coinName]);
                                     //setTimeout(function(){},10000000);

                                     getElmByID('latestScans').innerHTML += ' <tr   id="1' + coinName + '"><td id="' + coinName + data_array[stratName][coinName]['timeframe'] + stratName + '" data-title="Symbol">' + coinName + '</td><td id="' + coinName + '" data-title="Price">' + parseFloat(data_array[stratName][coinName]['price']).toFixed(8) + '</td>      <td id="conditions' + coinName + '" data-title="Strategies" ><div id="' + coinName + stratName + data_array[stratName][coinName]['timeframe']+data_array[stratName][coinName]['exchange'] + '"><span class="' + style + '">' + stratName + '</span>' + conditions_spans + '</div></td>     </tr>';

                                 } else {
									 getElmByID(coinName).innerHTML = parseFloat(data_array[stratName][coinName]['price']).toFixed(8);
									 global_prices[coinName] = parseFloat(data_array[stratName][coinName]['price']).toFixed(8);
                                     if (!getElmByID(coinName + stratName + data_array[stratName][coinName]['timeframe']+data_array[stratName][coinName]['exchange'])) {
                                         getElmByID("conditions" + coinName).innerHTML += '<div id="' + coinName + stratName + data_array[stratName][coinName]['timeframe']+data_array[stratName][coinName]['exchange'] + '">' + stratName + conditions_spans + '</div>';



                                     } else {
                                         //data_array[stratName][coinName]['timeframe']
                                         getElmByID(coinName + stratName + data_array[stratName][coinName]['timeframe']+data_array[stratName][coinName]['exchange']).innerHTML = '<span class="' + style + '">' + stratName + '</span>' + conditions_spans;
                                     }

                                 }

                             })
                         })

                         if (jkl == "") {
                             coins1 = coins1.toLocaleLowerCase();
                             //binanceSocket.close();
                             //initBinanceSocket();
                             jkl = 1;
                         }
                     } 
					 else if (cmnd_sent === "openSignalsToWeb") {
                         //getElmByID('simulator_trades_open').innerHTML="";
                         //typeSim = data_jsoned.type; //if simulation trades or real mode trades
						 console.log(data_jsoned);
                         timefr = data_jsoned.timeframe;
						 exchange = data_jsoned.exchange;
                         signals_starts = data_jsoned[1]; //the signals arrya
						 sim= "simulator";
						 sig_pid = data_jsoned.pid;
						 if(data_jsoned.sim === true){
							 sim = "simulator";
						 }
						 else if(data_jsoned.sim === false){
							 sim = "real";
						 }
						 
                         //if(typeSim===true){

                         //}

                         starts = Object.keys(signals_starts);
                         starts.forEach(function (strategyName) {
ik=0;
                             coins_names = Object.keys(signals_starts[strategyName]);
                             coins_names.forEach(function (coinName) {
                                 if (typeof coins_names_old[sig_pid] !== "undefined" && coins_names_old[sig_pid][timefr] !== "undefined" && typeof coins_names_old[sig_pid][timefr][strategyName] !== "undefined" && coins_names_old[sig_pid][timefr][strategyName][coinName] !== "undefined") {
                                     delete coins_names_old[sig_pid][timefr][strategyName][coinName];
                                 }
                                 coinData = signals_starts[strategyName][coinName];
                                 coinName = coinData.market;
                                 //console.log(coinData);

                                 if (typeof global_prices[coinName] === "undefined") {
                                     global_prices[coinName] = coinData.price;
									 
                                 }
								 if(exchange!=="binance" && getElmByID(coinName+jkl)){
									 updatePrice(Number.parseFloat(coinData.price).toFixed(8), coinName+jkl);
								 }
								 
                                 pl_percent = ((global_prices[coinName] - coinData.price) / coinData.price * 100).toFixed(2);
                                 if (global_prices[coinName] > coinData.price) {
                                     bg = "label label-success";
                                 } else if (global_prices[coinName] < coinData.price) {
                                     bg = "label label-danger";
                                 } else {
                                     bg = "label label-warning";
                                 }
								 									 dca  = '';
									 if(coinData.DCAed > 0){
										 dca = coinData.DCAed + " x DCA";
									 }
                                 if (getElmByID('openTradesDiv' + coinName + strategyName + timefr+ exchange + sim)) {
                                     //div already exist, update data
                                     getElmByID('openTradesDiv' + coinName + strategyName + timefr+ exchange + sim + 'price').innerHTML = global_prices[coinName];
									 getElmByID('openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'profitloss').innerHTML = pl_percent;
									 getElmByID('openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'dca').innerHTML =dca;
									 getElmByID('openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'profitloss').className=bg;
									 getElmByID('openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'profitlossbtc').innerHTML = (pl_percent/100*coinData.price*coinData.quantity).toFixed(8)+' btc';
                                 } else {
                                     //create div into trades
									 if(jkl2===1){
										 
									 if(sim==="real"){
										 bg2 = "alert alert-success";
									 }
										 else{
											  bg2 = "alert alert-warning";
										 }
									 getElmByID("notifications").innerHTML+='<li id="'+coinName + strategyName + timefr + exchange + sim+'alert" onclick="removeAlert(\''+coinName + strategyName + timefr + exchange + sim+'alert\')" class="'+bg2+'">	<a class="clearfix">											<div class="image">												<i class="fa fa-shopping-cart bg-success"></i>											</div>											<span class="title">'+coinName+' / '+exchange+'</span>											<span class="message">'+sim.toLocaleUpperCase()+'</span>										</a>									</li>';
										 
										 getElmByID("noti_count").innerHTML = document.getElementById('notifications').childElementCount;
										 getElmByID("noti_count2").innerHTML = document.getElementById('notifications').childElementCount;
										 }
									 actions = '';
									 date_td =coinData.opened;
									 if(sim==="real"){
										 actions = '<td data-title="Actions"><button class="label label-danger" onclick="send_var(\'sellSignal ' + sig_pid+' '+ strategyName +' '+ coinName +  '\')">Force Sell</button>&nbsp;&nbsp;<button class="label label-warning" onclick="send_var(\'DCA ' + sig_pid+' '+ strategyName +' '+ coinName +  '\')">DCA</button></td>';
										 date_td ='<td data-title="Date">' + convert(coinData.opened) + '</td>';
									 }
									 else{
										 actions = '<td data-title="Actions"></td>';
										 date_td ='<td data-title="Date">' + coinData.opened + '</td>';
									 }

                                     getElmByID(sim+'_trades_open').innerHTML += '<tr id="openTradesDiv' + coinName + strategyName + timefr + exchange + sim + '">	'+actions+date_td+'<td data-title="Timeframe">' + timefr + '</td><td data-title="Market">' + coinName + '</td>				<td data-title="Profit/Loss %"><span class="' + bg + '" id="openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'profitloss">' + pl_percent + '</span><span class="label label-warning" id="openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'dca" style="margin-left:5px;">' + dca + '</span>' +'<span id="trail' + coinName + strategyName + timefr + exchange + '"></span><br /><span style="dispaly:none;" class="' + bg + '" id="openTradesDiv' + coinName + strategyName + timefr + exchange + sim + 'profitlossbtc">0</span></td>																													<td data-title="Strategy">' + strategyName + '</td><td data-title="Price" id= "openTradesDiv' + coinName + strategyName + timefr + exchange  + sim +  'price">' + global_prices[coinName] + '</td><td data-title="Purchase Price">' + coinData.price + '</td><td data-title="Exchange">' + exchange + '</td></tr>';
                                 }
                            ik++;
				
                             })
                             

                         });
						 // add simulation ,ode bg color to instnace, not here, just writing here ;)
						 
						 
						 
						 
                         getElmByID("openSigsCount").innerHTML = document.getElementById('simulator_trades_open').childElementCount + document.getElementById('real_trades_open').childElementCount;
                         if (typeof coins_names_old[sig_pid] !== "undefined" && typeof coins_names_old[sig_pid][timefr] !== "undefined") {
                             starts = Object.keys(coins_names_old[sig_pid][timefr]);
                             starts.forEach(function (strategyName) {

                                 coins_names = Object.keys(coins_names_old[sig_pid][timefr][strategyName]);
                                 coins_names.forEach(function (coinName) {

                                     if (getElmByID("openTradesDiv" + coinName + strategyName + timefr + exchange + sim) !== null) {
                                         getElmByID("openTradesDiv" + coinName + strategyName + timefr + exchange + sim).parentNode.removeChild(getElmByID("openTradesDiv" + coinName + strategyName + timefr + exchange + sim));
                                     }

                                 })
                             });

                         }
						 ///some problem up or down here causes 2 instnaces from the same excahnge to nbot be shown, probably on the code below, need to add strategu doimension to the old cons array
                         if (typeof signals_starts !== "undefined") {
							 if(typeof coins_names_old[sig_pid] === "undefined"){
								coins_names_old[sig_pid] = [];
								 coins_names_old[sig_pid][timefr] = {};
								}
                             coins_names_old[sig_pid][timefr] = JSON.parse(JSON.stringify(signals_starts));;
                         } 
                         //for each signals
                         //if signals already exist in #simulator_trades
                         //if yes, change data in #openTradesDivBNBBTCpSARSwitch5m
                         //if no, add{
                         /*
                                
                                    <tr>
<td data-title="Date">coinData.opened</td>
<td data-title="Market">Market</td>
<td data-title="Profit/Loss %">Profit/Loss %</td>
<td data-title="Strategy">Strategy</td>
<td data-title="Price">Price</td>
<td data-title="Purchase Price">Purchase Price</td>
</tr>
                                
                                
                                <div class="col-md-3" >
							<section class="panel panel-success">
								<header class="panel-heading">
									<div class="panel-actions">
										<a href="#" class="fa fa-caret-down"></a>
										<a href="#" class="fa fa-times"></a>
									</div>

									<h2 class="panel-title">Title</h2>
								</header>
								<div class="panel-body" id="openTradesDivBNBBTCpSARSwitch5m">
									<code>.panel-success</code>
								</div>
							</section>
						</div>
                        
                        change panel-success according to profit/loss stat
                                */
                         //log(msg.data);
                     } 
					 else if (cmnd_sent === "closedSignalsToWeb") {
						 console.log(cmnd_answr);
						 instance_closed_sigs=[];
                         //getElmByID('simulator_trades_closed').innerHTML="";
                         //alert(cmnd_answr);
						 if(typeof cmnd_answr === "undefined" || cmnd_answr === null ){
							 console.log(data_jsoned);
							 return false;
						 }
                         coin_names = Object.keys(cmnd_answr);
                         console.log(coin_names);

                         coin_names.forEach(function (name) {
ik=0;
                             signal = cmnd_answr[name];
                             console.log(signal);
                             strategyName = signal.details.strategy;
                             timefr = signal.details.timeframe;
							 exchange = signal.details.exchange;
							 sim="simulator";
							 if(signal.details.sim === true){
							 sim = "simulator";
						 }
						 else if(signal.details.sim === false){
							 sim = "real";
						 }
                             i = 0;
                             while (i < signal.details.total_closed_signals - 1) {
                                 coinName = signal[i].buy.market;

                                 pl_percent = signal[i].profit_loss_percent.toFixed(2);

                                 if (getElmByID('closedTradesDiv' + coinName + strategyName + timefr + signal[i].sell.close_time + sim)) {
                                     //closed signal alreay in list, ignore

                                 } else {
								if(sim==="real"){
									if(exchange === "binance"){
										commis = 0.3;
									}
									else if(exchange === "bittrex"){
										commis = 0.5;
									}
									pl_percent -= commis;
									
									       
									
									pl_btc = pl_percent/100*signal[i].quantity*signal[i].buy.price;
									total_pl += pl_btc;
									total_pl > 0 ? bg1 = "panel panel-featured-left panel-featured-tertiary" : bg1 = "panel panel-featured-left panel-featured-secondary";
									getElmByID("total_pl").parentElement.parentElement.parentElement.parentElement.parentElement.parentElement.className=bg1;
									 getElmByID("total_pl").innerHTML  = total_pl.toFixed(8); //change the vlue of each trade here..
									 pl_percent = pl_percent.toFixed(2);
								 }
									                           if (pl_percent > 0) {
                                     bg = "label label-success";
                                 } else if (pl_percent < 0) {
                                     bg = "label label-danger";
                                 } else {
                                     bg = "label label-warning";
                                 }
 if(jkl2===1){
										 
									 if(sim==="real"){
										
										 bg2 = "alert alert-danger";
									 }
										 else{
											  bg2 = "alert alert-warning";
										 }
									 getElmByID("notifications").innerHTML+='<li id="'+coinName + strategyName + timefr + exchange + sim+'alertSell" onclick="removeAlert(\''+coinName + strategyName + timefr + exchange + sim+'alertSell\')" class="'+bg2+'">	<a class="clearfix">											<div class="image">												<i class="fa fa-shopping-cart bg-danger"></i>											</div>											<span class="title">'+coinName+' / '+exchange+'</span>											<span class="message">SOLD! '+sim.toLocaleUpperCase()+', Profit/Loss: '+pl_percent+'</span>										</a>									</li>';
										 
										 getElmByID("noti_count").innerHTML = document.getElementById('notifications').childElementCount;
										 getElmByID("noti_count2").innerHTML = document.getElementById('notifications').childElementCount;
										 }
									
                                     	 	  if(sim==="real"){
												  btc_pl = pl_btc.toFixed(8)+' btc ';
										time_r = convert(signal[i].buy.opened) + '- ' + convert(signal[i].sell.close_time) ;
									  }
									 else{
										  btc_pl = '';
										 time_r = signal[i].buy.opened + '- ' + signal[i].sell.close_time ;
									 }
                                     getElmByID(sim+'_trades_closed').innerHTML += '<tr id="closedTradesDiv' + coinName + strategyName + timefr + signal[i].sell.close_time + sim + '">	<td data-title="Date (Total Time)">'+time_r+'</td><td data-title="Timeframe">' + timefr + '</td><td data-title="Market">' + coinName + '</td>				<td data-title="Profit/Loss %"><span class="' + bg + '" id="' + coinName + 'pl">' + pl_percent + '</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '+  btc_pl+'</td>																													<td data-title="Strategy">' + strategyName + '</td><td data-title="Purchase Price" >' + signal[i].buy.price + '</td><td data-title="Sell Price">' + signal[i].sell.price + '</td><td data-title="Exchange">' + exchange + '</td></tr>';
                                 }

                                 i++;
                                 ik++;
                             }
								 
						 });
getElmByID("closedSigsCount").innerHTML = document.getElementById("simulator_trades_closed").childElementCount + document.getElementById("real_trades_closed").childElementCount;
                         /*
                              {
    "BTTBTC": {
        "details": {
            "startegy": "BTTBTC",
            "0": "pSARswitch",
            "timeframe": "5m",
            "total_closed_signals": 16
        },
        "0": {
            "buy": {
                "price": 2.1e-7,
                "market": "BTTBTC",
                "strat": "pSARswitch",
                "type": "buy",
                "max_spread": 1,
                "spread_price_ask": 0,
                "opened": "13:07:26, 02\/03",
                "status": "active"
            },           
            "sell": {
                "price": 2.0e-7,
                "market": "BTTBTC",
                "strat": "pSARswitch",
                "type": "sell",
                "max_spread": 1,
                "spread_price_bid": 0
            },
            "profit_loss_percent": -4.761904761904766
        },


                              */
                     } 
						 }
					if(cmnd_sent === "usageRamCpu"){
						 if(isMobileDevice()){
							 return null;
						 }
						 //data12 = JSON.parse(cmnd_answr);
							   getElmByID('allCPUusage').innerHTML =  data_jsoned.total_cpu+'%';
						 getElmByID('inj_cpu_prog').innerHTML='	<div class="progress progress-xs light">											<div class="progress-bar" role="progressbar" aria-valuenow="'+data_jsoned.total_cpu+'" aria-valuemin="0" aria-valuemax="100" style="width: '+data_jsoned.total_cpu+'%;"></div>										</div>';
							   getElmByID('allRAMusage').innerHTML =  data_jsoned.total_ram+'%';
						 getElmByID('inj_ram_prog').innerHTML='	<div class="progress progress-xs light">											<div class="progress-bar" role="progressbar" aria-valuenow="'+data_jsoned.total_ram+'" aria-valuemin="0" aria-valuemax="100" style="width: '+data_jsoned.total_ram+'%;"></div>										</div>';
							   } 
					 
					 	 if(page_n ==='main'){
							  if (cmnd_sent === "workingInstances") {
						 k=0;
                         console.log(cmnd_answr);
                         gg = Object.keys(cmnd_answr);
                         //console.log(gg);
                         getElmByID("accordion2").innerHTML = "";
instances_modals = '								';
                         gg.forEach(function (pid) {
							 k++;
							 if (IsValidJSONString(cmnd_answr[pid])) {
                             data = JSON.parse(cmnd_answr[pid]);
							 if(data.isSimulator === false){
								 accbg = "success";
							 }
								 else{
									 accbg = "warning";
								 }
								if(data.hasOwnProperty("SOM")){
									 accbg = "danger";
							
								}
								 if(data.hasOwnProperty("no_ws_connection")){
									 accbg = "dark";
							
								}
                             getElmByID("accordion2").innerHTML += '			<div class="panel panel-accordion panel-accordion-'+accbg+'">									<div class="panel-heading">										<h4 class="panel-title">											<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion2" href="#' + data.name + '" aria-expanded="false">												<i class="fa fa-star"></i> ' + data.name + '										</a>										</h4><p class="panel-subtitle">'+data.exchange+'</p>									</div>									<div id="' + data.name + '" class="accordion-body collapse" aria-expanded="false" style="height: 0px;">										<div class="panel-body">											'+data.coins+'	<br />'+data.strats+'								</div>									</div><div class="panel-footer panel-footer-btn-group">												<a href="javascript:void(0);"><i class="fa fa-user mr-xs"></i> Settings</a>												<a href="javascript:void(0);" id="startStopSOMAction'+pid+'" ><i class="fa fa-lock mr-xs"></i><span id="startStopSOMText'+pid+'"> Strat/Stop SOM</span></a><a class="mb-xs mt-xs mr-xs modal-with-zoom-anim " href="#modalAnim' + pid + '">										<i class="fa fa-power-off mr-xs"></i> Stop</a>											</div>								</div>';
                             
                             instances_modals += '								<div id="modalAnim' + pid + '" class="zoom-anim-dialog modal-block modal-block-primary mfp-hide">										<section class="panel">											<header class="panel-heading">												<h2 class="panel-title">Are you sure?</h2>											</header>											<div class="panel-body">												<div class="modal-wrapper">													<div class="modal-icon">														<i class="fa fa-question-circle"></i>													</div>													<div class="modal-text">														<p>Are you sure that you want to delete this image?</p>													</div>												</div>											</div>											<footer class="panel-footer">												<div class="row">													<div class="col-md-12 text-right">														<button class="btn btn-primary modal-confirm" onclick="send_var(\'stopIns \'+' + pid + ')">Confirm</button>														<button class="btn btn-default modal-dismiss">Cancel</button>													</div>												</div>											</footer>										</section>									</div>';
                             getElmByID("inst_modals").innerHTML=instances_modals;
								if(data.hasOwnProperty("SOM")){
									changeBtnTostopSOM(pid);
								}
								 else{
									changeBtnTostartSOM(pid);
								 }

                         }
						 });
						 if(k>0){
							 changeToStop();
						 }
						 else{
							 changeToRun();
						 }
                     } 
					 else if(cmnd_sent === "btcValue"){
						 console.log("btc answr" + cmnd_answr);
						 getElmByID("btcValue").innerHTML=cmnd_answr.toFixed(8);
					 }
				 else if(cmnd_sent === "btcValueArr"){
						 console.log("btc answr arr" + cmnd_answr);
						 var sparklineLineData = cmnd_answr;
					 	$("#sparklineLine").sparkline(sparklineLineData, {
							type: 'line',
							width: '100',
							height: '30',
							lineColor: '#0088cc'
						});
	
					 }
						 }
					 else if(page_n === 'lab'){
						 if(cmnd_sent === "finishedBacktestingsList"){
							 console.log(cmnd_answr);
							 /*
							 simulations/15527563611940879995binance.json
1backtesting_results/backtesting.ENJBTC.pSARswitch.json
							 */
							 if(cmnd_answr.file_name.includes("simulations")){
								 the_type = "Simulation";
							 }
							 else 		 if(cmnd_answr.file_name.includes("backtesting")){
								 the_type = "Backtesting";
							 }
							 getElmByID('links').innerHTML +='									<tr>											<td data-title="Type" class="pt-md pb-md">												<i class="fa fa-bug fa-fw text-muted text-md va-middle"></i> '+the_type+'											</td>											<td data-title="Date" class="pt-md pb-md">												-											</td>											<td data-title="Message" class="pt-md pb-md">									<a onclick="socketBot.send(\'backtestingResult '+cmnd_answr.file_name+'\');changeType(\''+the_type+'\')">View</a><br />'+JSON.stringify(cmnd_answr.details)+'							 </td>										</tr>';
							 
						 }
						 else if(cmnd_sent === 'backtestingResult'){
							 console.log(cmnd_answr);
							 getElmByID('theLab').style.display = "none";
							 getElmByID('theLabPage').style.display = "block";
							 getElmByID('theLabPage').innerHTML = '<a onclick="getElmByID(\'theLabPage\').style.display = \'none\';							 getElmByID(\'theLab\').style.display = \'block\';">Close</a><br />'+generateLabResult(cmnd_answr);
							 
						 }
					 }

else {
		 if(page_n ==='main'){
                         log("Received: " + msg.data);
		 }
                     }




                 } else {
					 if(page_n ==='main'){
                     log("Received: " + msg.data);
					 }
                 }



             
			 }};
             socketBot.onclose = function (msg) {
                 initBotSocket();
                 log("Disconnected Bot - status " + this.readyState);
             };
         } catch (ex) {
			 if(page_n ==='main'){
             log(ex);
			 }
         }
		 if(page_n ==='main'){
         getElmByID("msg").focus();
		 }
         setInterval(function () {
             //socketBot.send('latestScansData'); 
         }, 300000);
     }

     function send_var(msg) {
		 if(msg.indexOf('DCA') || msg.indexOf('stopIns') || msg.indexOf('startSOM')|| msg.indexOf('stopSOM') || msg.indexOf('sellSignal')){
			 var r = confirm("Are you sure?");
if (r !== true) {
  return null;
}
		 }
         try {
             socketBot.send(msg);
			  if(page_n ==='main'){
             log('Sent: ' + msg);
			  }
         } catch (ex) {
			  if(page_n ==='main'){
             log(ex);
			  }
         }
     }

     function send() {
         var txt, msg;
         txt = getElmByID("msg");
         msg = txt.value;
         if (!msg) {
             alert("Message can not be empty");
             return;
         }
         txt.value = "";
         txt.focus();
         try {
             socketBot.send(msg);
             log('Sent: ' + msg);
         } catch (ex) {
             log(ex);
         }
     }

     function init() {
         //initBinanceSocket();
         initBotSocket();

     }

     function quit() {
         if (socketBot != null) {
             log("Goodbye!");
             socketBot.close();
             socketBot = null;
         }
     }

     function reconnect() {
         quit();
         init();
     }
     // Utilities
     function getElmByID(id) {
         return document.getElementById(id);
     }


	function changeToRun(){
		getElmByID('startStop').innerHTML = "Start";
		getElmByID("startStopModalBtn").onclick =null;
		getElmByID("startStopModalBtn").onclick = function(){send_var('startIns');			changeToStop();};
	}
	function changeToStop(){
		getElmByID('startStop').innerHTML = "Stop";
		getElmByID("startStopModalBtn").onclick =null;
		getElmByID("startStopModalBtn").onclick = function(){send_var('stopIns ALL');			changeToRun();};
	}
	function changeBtnTostopSOM(pid){
		getElmByID("startStopSOMText"+pid).innerHTML = "Stop SOM";
		getElmByID("startStopSOMAction"+pid).onclick =null;
		getElmByID("startStopSOMAction"+pid).onclick = function(){
			send_var('stopSOM ' + pid)			;
			changeBtnTostartSOM(pid);
		};
	}
	function changeBtnTostartSOM(pid){
		getElmByID("startStopSOMText"+pid).innerHTML = "Start SOM";
		getElmByID("startStopSOMAction"+pid).onclick =null;
		getElmByID("startStopSOMAction"+pid).onclick = function(){
			send_var('startSOM ' + pid)			;
			changeBtnTostopSOM(pid);
		};
	}
     function updateAllClasses(cls_name, txt) {
         //var x = document.getElementsByClassName(cls_name);
         //var i;
         //for (i = 0; i < x.length; i++) {
         //x[i].innerHTML = txt;
         //}
         divs = document.getElementsByClassName(cls_name);

    [].slice.call(divs).forEach(function (div) {
             alert(div.innerHTML + ", " + txt);
             div.innerHTML = txt;
         });
     }

     function log(msg) {
         getElmByID("log").innerHTML += "<br>" + msg;
     }

     function updatePrice(msg, market) {
         getElmByID(market).innerHTML = msg;
         global_prices[market] = msg;
         //getElmByID(market+"sims").innerHTML=msg;
         //getElmByID(market+"pl").innerHTMLmsg = ((global_prices[coinName] - coinData.price)/coinData.price*100).toFixed(2);;

     }

     function onkey(event) {
         if (event.keyCode == 13) {
             send();
         }
     }


     init();

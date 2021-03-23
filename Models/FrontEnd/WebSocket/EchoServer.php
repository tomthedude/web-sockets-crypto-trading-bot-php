<?php
namespace Models\FrontEnd\WebSocket;

class EchoServer extends WebSocketServer {
  function __construct($addr, $port, $bufferLength) {
    parent::__construct($addr, $port, $bufferLength);
    $this->userClass = '\Models\FrontEnd\WebSocket\MyUser';
  }
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.
  
  protected function process ($user, $message) {
      global $filesCls;
    //$this->send($user,$message);
      $xtra_data = explode(" ",$message);
      if(isset($xtra_data[1])){
          $message = $xtra_data[0];
      }
      switch($message){
		  case 'balances':
			  $filesCls->writeMsgFromWeb(["allBalances"],$message);
			  break;
		  case 'btcValue':
			  $filesCls->writeMsgFromWeb(["btcValue"],$message);
			  break;
          case 'openSignals':
             $filesCls->writeMsgFromWeb(["openSignalsToWeb"],$message);
              break;
         case 'closedSignalsToWeb':
             $filesCls->writeMsgFromWeb(["closedSignalsToWeb"],$message); 
              break;
          case 'latestScansData':
             $filesCls->writeMsgFromWeb(["latestScansData"],$message); 
              break;
          case 'workingInstances':
              $filesCls->writeMsgFromWeb(["workingInstancesToWeb"],$message); 
              break;
          case 'initWeb':
             $filesCls->writeMsgFromWeb(["openSignalsToWeb","closedSignalsToWeb","workingInstancesToWeb"]); 
              break;
          case 'startSOM':
              if(isset($xtra_data[1])){
                  $pid = $xtra_data[1];
                 $filesCls->writeCmndsToPid(["startSOM"],$pid,'startSOM');
              }
              else{
                  $pid = 'all';
              }
              break;
          case 'stopSOM':
              if(isset($xtra_data[1])){
                  $pid = $xtra_data[1];
                 $filesCls->writeCmndsToPid(["stopSOM"],$pid,'stopSOM');
              }
              else{
                  $pid = 'all';
              }
              break;
          case 'stopIns':
              if(isset($xtra_data[1])){
                  $pid = $xtra_data[1];
				  if($pid!=="ALL"){
					   $filesCls->writeCmndsToPid(["stop"],$pid,'stopIns');
				  }
				  else{
					  $filesCls->writeMsgFromWeb(["stopInstances"]); 
				  }
                 
              }
              break;
          case 'sellSignal':
             $filesCls->writeMsgFromWeb(["sellSignal|".$xtra_data[1]."|".$xtra_data[2]."|".$xtra_data[3]]); 
              break;
          case 'DCA':
             $filesCls->writeMsgFromWeb(["DCA|".$xtra_data[1]."|".$xtra_data[2]."|".$xtra_data[3]]); 
              break;
		  case 'startIns':
			  $filesCls->writeMsgFromWeb(["startInstances"]); 
			  break;
		  case 'usageRamCpu':
			  $filesCls->writeMsgFromWeb(["usageRamCpu"]); 
			  break;
		  case 'finishedBacktestingsList':
			  $pass = "";
			  if(isset($xtra_data[1])){
				  $pass = " ".$xtra_data[1];
			  }
			  $filesCls->writeMsgFromWeb(["finishedBacktestingsList$pass"]); 
			  break;
          case 'backtestingResult':
             $filesCls->writeMsgFromWeb(['backtestingResult '.$xtra_data[1]]); 
              break;
          default:
              break;
      }
  }
  
  protected function connected ($user) {
    // Do nothing: This is just an echo server, there's no need to track the user.
    // However, if we did care about the users, we would probably have a cookie to
    // parse at this step, would be looking them up in permanent storage, etc.
  }
  
  protected function closed ($user) {
    // Do nothing: This is where cleanup would go, in case the user had any sort of
    // open files or other objects associated with them.  This runs after the socket 
    // has been closed, so there is no need to clean up the socket itself here.
  }

} 
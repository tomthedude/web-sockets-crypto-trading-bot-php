<?php
namespace Models\BotComponent;


class Strategies{
    public $strategies;
    public $active_strategies;
    /*
    Array
(
    [0] => Array
        (
            [strName] => pSAR Swtich
            [strDesc] => Buy when pSAR is switching
        )

)
*/
    function __construct(){
        $files = glob('strategies/*.{json}', GLOB_BRACE);
        foreach($files as $file) {
        //do your work here
            $tmp = explode("/",$file);
            $this->strategies[$tmp[count($tmp)-1]] = json_decode(file_get_contents($file),true);
            print_r(json_decode(file_get_contents($file),true));
            
            
        }
        //die();
        $this->activeStrats();
        //print_r($this->strategies);
    }
    private function activeStrats(){
        foreach($this->strategies as $start){
            if($start['isActive'] == true){
                //$start['name'] = str_replace(".json","",$start['name']);
                $this->active_strategies[$start['name']] = $start;
            }
        } 
        
    }
}
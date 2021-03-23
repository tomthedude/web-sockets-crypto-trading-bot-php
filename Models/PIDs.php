<?php

namespace Models;

class PIDs{
    public $pids=[];
    
    function __construct($filename){
        $this->pids = json_decode(file_get_contents($filename),true);
        
    }
}
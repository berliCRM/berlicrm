<?php

class iCal { 

    var $folders;
    
    function __construct() {
        $this->folders = 'cache/import/';
    }
    
    function iCalReader($filename,$root_directory='') {
        $iCaltoArray = $this->iCalDecoder($filename,$root_directory);
        return $iCaltoArray;
    }
    
    function iCalDecoder($file,$root_directory) {
        $ical = file_get_contents($root_directory.$this->folders.$file);
        preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/si', $ical, $eventresult, PREG_PATTERN_ORDER);
        preg_match_all('/BEGIN:VTODO.*?END:VTODO/si', $ical, $todoresult, PREG_PATTERN_ORDER);
        // new. ics file have in thunderbird-calendar a vtimezone and new keys for DTSTART and DTEND with timezonename
        preg_match_all('/BEGIN:VTIMEZONE.*?END:VTIMEZONE/si', $ical, $vtimezoneresult, PREG_PATTERN_ORDER);
        $tzoffsetNumberOfHours = '0';
        $tzoffsetNumberOfMinutes = '0';
        $tzoffsetfrom = '0';
        $minusOrPlus = '+';
        for ($i = 0; $i < count($vtimezoneresult[0]); $i++) {
            $tmpbyline = preg_split('/\r\n|\r|\n/', $vtimezoneresult[0][$i]);
            $begin = false;
            $key=NULL;
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(":",$item,2);
                if (count($tmpholderarray) >1) {
                    // TZOFFSETFROM 
                    if($tmpholderarray[0]=='TZOFFSETFROM'){
                        $tzoffsetfrom = $tmpholderarray[1]; // zb +0200
                    }
                }
            }
        }
        // TZOFFSETFROM  must allways be 5 Chars long: // + 0 2 0 0 //
        $tzoffsetfromCharArray = str_split($tzoffsetfrom); 
        if( count($tzoffsetfromCharArray) > 4  ){
            $minusOrPlus = $tzoffsetfromCharArray[0];
            $tzoffsetNumberOfHours = $tzoffsetfromCharArray[1].$tzoffsetfromCharArray[2];
            $tzoffsetNumberOfMinutes = $tzoffsetfromCharArray[3].$tzoffsetfromCharArray[4];
        }

        for ($i = 0; $i < count($eventresult[0]); $i++) {
            $tmpbyline = preg_split('/\r\n|\r|\n/', $eventresult[0][$i]);
            $begin = false;
            $key=NULL;

            $statusExist = false; 
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(":",$item,2);

                if (count($tmpholderarray) >1) { 
                    if($tmpholderarray[0]=='STATUS'){
                        $statusExist = true;
                    }
                    if($tmpholderarray[0]=='BEGIN'){
                 		if($begin==false){
                 			$begin = true;
                 			$majorarray['TYPE']=$tmpholderarray[1];
                 		} else {
                 			$majorarray[$tmpholderarray[1]]=array();
                 			$key = $tmpholderarray[1];
                 		}
                    } else if($tmpholderarray[0]=='END'){
                    	if(!empty($key)){
                    		$key = NULL;
                    	}
                    } else {
                    	if(!empty($key)){
                    		$majorarray[$key][$tmpholderarray[0]] = $tmpholderarray[1];
                    	} else {
                            // example :  $tmpholderarray[0] = DTSTART;TZID=Europe/Berlin  |  $tmpholderarray[1] => 20210427T145000
                            $tmpholderKeysInKeyArray = explode(";",$tmpholderarray[0]);
                            // here begin make correctur of key and of time
                            if(  count($tmpholderKeysInKeyArray) > 1 ){ 
                                $tmpholderTimeToCorrectArray = explode("T",$tmpholderarray[1]);
                                if(  count($tmpholderTimeToCorrectArray) > 1 ){
                                    // timecorrectur it can be  14:50  ~ 145000  or it can be 5:30 ~ 053000. (The first 0 leading null problem)
                                    $yearWithTZ = '';
                                    $monthWithTZ = '';
                                    $dayWithTZ = '';
                                    
                                    $tmTimeArray = str_split($tmpholderTimeToCorrectArray[0]);
                                    if( count($tmTimeArray) > 1 ){
                                        for($a = 0; $a < count($tmTimeArray); $a++ ){
                                            if($a < 4){
                                                $yearWithTZ = $yearWithTZ.$tmTimeArray[$a];
                                            }else if($a < 6){
                                                $monthWithTZ = $monthWithTZ.$tmTimeArray[$a];
                                            }else{
                                                $dayWithTZ = $dayWithTZ.$tmTimeArray[$a];
                                            }
                                        }
                                    }

                                    $hoursWithTZ = '';
                                    $minutesWithTZ = '';
                                    $secundsWithTZ = '';

                                    $tmTimeArray = str_split($tmpholderTimeToCorrectArray[1]);
                                    if( count($tmTimeArray) > 1 ){
                                        for($a = 0; $a < count($tmTimeArray); $a++ ){
                                            if($a < 2){
                                                $hoursWithTZ = $hoursWithTZ.$tmTimeArray[$a];
                                            }else if($a < 4){
                                                $minutesWithTZ = $minutesWithTZ.$tmTimeArray[$a];
                                            }else{
                                                $secundsWithTZ = $secundsWithTZ.$tmTimeArray[$a];
                                            }
                                        }
                                    }

                                    // timezones are in hours and minutes, prepare hours and minutes. (leading zero problem ( 05 or 14) )
                                    $hoursWithTZArray = str_split($hoursWithTZ);
                                    if($hoursWithTZArray[0] == 0){
                                        $hoursWithTZ = $hoursWithTZArray[1];
                                    }
                                    $minutesWithTZArray = str_split($minutesWithTZ);
                                    if($minutesWithTZArray[0] == 0){
                                        $minutesWithTZ = $minutesWithTZArray[1];
                                    }
                                    // now minus or plus. If + we must subtract, else add.
                                    $correctMktime = '';
                                    if($minusOrPlus == '+'){
                                        // mktime can give a correct result of time-data calculation.
                                        // mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
                                        // mktime(hour, minute, second, month, day, year, is_dst)
                                        $correctMktime = mktime( 
                                            ($hoursWithTZ - $tzoffsetNumberOfHours) , 
                                            ($minutesWithTZ - $tzoffsetNumberOfMinutes) , 
                                            0, 
                                            $monthWithTZ  , 
                                            $dayWithTZ, 
                                            $yearWithTZ 
                                        );
                                    }else{
                                        $correctMktime = mktime( 
                                            ($hoursWithTZ + $tzoffsetNumberOfHours) , 
                                            ($minutesWithTZ + $tzoffsetNumberOfMinutes) , 
                                            0, 
                                            $monthWithTZ  , 
                                            $dayWithTZ, 
                                            $yearWithTZ 
                                        );
                                    }
                                    // example: // echo date("Y-m-d H:i:s",mktime(14,05,00,1,1,99))  // 1999-01-01 14:05:00
                                    $dateCorrect = date("Ymd His",$correctMktime);
                                    $dateCorrectArr = explode(" ",$dateCorrect);
                                    $dateCorrect = $dateCorrectArr[0]."T".$dateCorrectArr[1];

                                }
                                $majorarray[$tmpholderKeysInKeyArray[0]] = $dateCorrect;
                            }else{
                                // and here is the old code.
                                $majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
                            }
                    	}
                    }
                }
            }
            // if status not exist, so set it, because it is a required field. So make default: 'Nicht angegeben.'
            if(!$statusExist){
                $majorarray['STATUS'] = 'Nicht angegeben.';
            }

            $icalarray[] = $majorarray;
            unset($majorarray);
        }
        
        for ($i = 0; $i < count($todoresult[0]); $i++) {
            $tmpbyline = preg_split('/\r\n|\r|\n/', $todoresult[0][$i]);
            $begin = false;
            $key=NULL;
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(":",$item);
                
                if (count($tmpholderarray) >1) { 
                    if($tmpholderarray[0]=='BEGIN'){
                 		if($begin==false){
                 			$begin = true;
                 			$majorarray['TYPE']=$tmpholderarray[1];
                 		} else {
                 			$majorarray[$tmpholderarray[1]]=array();
                 			$key = $tmpholderarray[1];
                 		}
                    } else if($tmpholderarray[0]=='END'){
                    	if(!empty($key)){
                    		$key = NULL;
                    	}
                    } else {
                    	if(!empty($key)){
                    		$majorarray[$key][$tmpholderarray[0]] = $tmpholderarray[1];
                    	} else {
                    		$majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
                    	}
                    }
                }
                
            }
            $icalarray[] = $majorarray;
            unset($majorarray);
        }
        return $icalarray;
    }
}
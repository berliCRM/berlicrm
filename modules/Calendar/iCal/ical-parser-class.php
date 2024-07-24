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
    
        foreach ($eventresult[0] as $event) {
            $tmpbyline = preg_split('/\r\n|\r|\n/', $event);
            $begin = false;
            $key=NULL;
            $statusExist = false;
            $majorarray = [];
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(":",$item,2);
                if (count($tmpholderarray) > 1) {
                    if ($tmpholderarray[0] == 'STATUS') {
                        $statusExist = true;
                    }
                    // if($tmpholderarray[0]=='RESOURCES'){
                    //     $activityTypeExist = true;
                    // }
                    if ($tmpholderarray[0] == 'BEGIN') {
                        if (!$begin) {
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
    
            if (!$statusExist) {
                $majorarray['STATUS'] = 'Planned';
            }
            // if (!$activityTypeExist) {
            //     $majorarray['RESOURCES'] = 'Ort';
            // }
    
            if (isset($majorarray['DTSTART;VALUE=DATE'])) {
                $majorarray['DTSTART'] = $majorarray['DTSTART;VALUE=DATE'] . 'T000000';
            }
            if (isset($majorarray['DTEND;VALUE=DATE'])) {
                $majorarray['DTEND'] = $majorarray['DTEND;VALUE=DATE'] . 'T235959';
            }
            $icalarray[] = $majorarray;
        }
    
        foreach ($todoresult[0] as $todo) {
            $tmpbyline = preg_split('/\r\n|\r|\n/', $todo);
            $begin = false;
            $key=NULL;
            $majorarray = [];
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(":",$item);
                if (count($tmpholderarray) > 1) {
                    if ($tmpholderarray[0] == 'BEGIN') {
                        if (!$begin) {
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
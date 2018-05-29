<?php

/*********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 *********************************************************************************/

include_once'libraries/csrf-magic/csrf-magic.php';
$GLOBALS['csrf']['rewrite-js'] = 'libraries/csrf-magic/csrf-magic.js';

function requestValidateReadAccess(){
    if(isset($_SERVER['HTTP_REFERER'])){
        global $Authenticate_Path;
        if(stripos($_SERVER['HTTP_REFERER'],$Authenticate_Path)!==0){
            echo 'Illegal request';die;
        }
    }
    return true;
}

function requestValidateWriteAccess(){
    if($_SERVER['REQUEST_METHOD']!='POST'){ echo'Invalid request'; die;}
    requestValidateReadAccess();
    if(!csrf_check(false)){echo'Unsupported request';die;}
    return true;
}

<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */
chdir(dirname(__FILE__) . "/../../../");
include_once "include/utils/VtlibUtils.php";
include_once "include/utils/CommonUtils.php";
include_once "includes/Loader.php";
include_once 'includes/runtime/BaseModel.php';
include_once 'includes/runtime/Viewer.php';
include_once "includes/http/Request.php";
include_once "include/Webservices/Custom/ChangePassword.php";
include_once "include/Webservices/Utils.php";
include_once "includes/runtime/EntryPoint.php";
require_once 'include/utils/utils.php';
require_once 'modules/Emails/mail.php';
require_once 'modules/Vtiger/helpers/ShortURL.php';

class Users_RequestPassword_Action{
    public function process($request) {
        global $adb;
        $adb = PearDatabase::getInstance();

        if (isset($request['user_name']) && isset($request['emailId'])) {
            $username = vtlib_purify($request['user_name']);
            $result = $adb->pquery('select email1, language from vtiger_users where user_name= ? ', array($username));
            if ($adb->num_rows($result) > 0) {
                $email = $adb->query_result($result, 0, 'email1');
                $lang = $adb->query_result($result, 0, 'language');
            }

            if (vtlib_purify($request['emailId']) == $email) {
                $time = time();
                $options = array(
                    'handler_path' => 'modules/Users/handlers/ForgotPassword.php',
                    'handler_class' => 'Users_ForgotPassword_Handler',
                    'handler_function' => 'changePassword',
                    'handler_data' => array(
                        'username' => $username,
                        'email' => $email,
                        'time' => $time,
                        'hash' => md5($username . $time)
                    )
                );
                $trackURL = Vtiger_ShortURL_Helper::generateURL($options);

                $langFile = 'languages/' . $lang . '/Users.php';
                require_once $langFile;

                $content = $languageStrings['LBL_PASSWORD_RESET_REQUEST_HELLO'];
                $content .= ' '.$username.',<br><br>';
                $content .= $languageStrings['LBL_PASSWORD_RESET_REQUEST_FIRST'];
                $content .= ' <br><br><a target="_blank" href=' . $trackURL . '>'.$languageStrings['LBL_PASSWORD_RESET_REQUEST_SUBJECT'].'</a> <br><br>';
                $content .= $languageStrings['LBL_PASSWORD_RESET_REQUEST_SECOND'];

                $query = "select from_email_field,server_username from vtiger_systems where server_type=?";
                $params = array('email');
                $result = $adb->pquery($query,$params);
                $from = $adb->query_result($result,0,'from_email_field');
                if($from == '') {$from =$adb->query_result($result,0,'server_username'); }
                $subject=$languageStrings['LBL_PASSWORD_RESET_REQUEST_SUBJECT'];
                
                $status = send_mail('', $email, $username, $from, $subject, $content);
                if ($status === 1)
                    header('Location: /../../../index.php?modules=Users&view=Login&status=1');
                else
                    header('Location: /../../../index.php?modules=Users&view=Login&statusError=1');
            } else {
                header('Location: /../../../index.php?modules=Users&view=Login&fpError=1');
            }
        }
    }

    public static function run($request) {
        $instance = new self();
        $instance->process($request);
    }
}

Users_RequestPassword_Action::run($_REQUEST);
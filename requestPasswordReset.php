<!-- /*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * modified by crm-now
*
********************************************************************************/ -->
<!DOCTYPE html>
<html>
    <head>
        <title>CRM Passwordreset</title>
        <link id="favicon" rel="SHORTCUT ICON" href="layouts/vlayout/skins/images/favicon.ico">
		<link rel="stylesheet" href="libraries/bootstrap/css/bootstrap.css" media="screen">
    </head>
    <body>
        <div style="position:fixed;top:50%;left:50%;width:60em;height:30em;margin-top:-15em;margin-left:-30em;">
            <table>
                <tr>
                    <td>
                        <img src="test/logo/start_main.jpg" style="float:left;margin:3px;width:440px;">
                    <td>
                    <form class="login-form" style="margin:0;" action="modules/Users/actions/RequestPassword.php" method="POST">
                        <div class="control-group">
                            <div class="controls">
                                <label class="control-label" for="username"><b>Username</b></label>
                                <input type="text" id="user_name" name="user_name" placeholder="Username">
                            </div>
                        </div>
                        <div class="control-group">
                            <div class="controls">
                                <label class="control-label" for="password"><b>E-Mail</b></label>
                                <input type="email" id="emailId" name="emailId" placeholder="E-Mail">
                            </div>
                        </div>
                        <div class="control-group signin-button">
                            <div class="controls pull-right">
                                <a href="index.php" class="btn btn-link">Login</a>
                            </div>
                            <div class="controls" id="login">
                                <button type="submit" class="btn btn-primary sbutton">Send</button>
                            </div>
                        </div>
                    </form>
                </tr>
            </table>
        </div>
    </body>
</html>
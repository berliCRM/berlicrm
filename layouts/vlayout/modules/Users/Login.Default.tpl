{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * modified by crm-now
*
 ********************************************************************************/
-->*}
{strip}
<div style="position:fixed;top:50%;left:50%;width:60em;height:30em;margin-top:-15em;margin-left:-30em;">
    <table>
        <tr>
            <td>
                <img src="test/logo/start_main.jpg" style="float:left;margin:3px;width:440px;">
            <td>
                <form class="login-form" style="margin:0;" action="index.php?module=Users&action=Login" method="POST">
                    {if isset($LOGIN_ERROR) && $LOGIN_ERROR neq ''}
                    <div class="alert alert-error">
                        <p>{$LOGIN_ERROR}</p>
                    </div>
                    {/if}
                    <div class="control-group">
                        <div class="controls">
                            <label class="control-label" for="username"><b>Nutzername / User Name</b></label>
                            <input type="text" id="username" name="username" placeholder="Username">
                        </div>
                    </div>
                    <div class="control-group">
                        <div class="controls">
                            <label class="control-label" for="password"><b>Passwort / Password </b></label>
                            <input type="password" id="password" name="password" placeholder="Password">
                        </div>
                    </div>
                    <div class="control-group signin-button">
                        <div class="controls" id="login">
                            <button type="submit" class="btn btn-primary sbutton">Login</button>
                        </div>
                    </div>
                </form>
            </tr>
    </table>
</div>
{/strip}

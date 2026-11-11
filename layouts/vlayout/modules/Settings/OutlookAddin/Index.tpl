{strip}
<div class="container-fluid" id="OutlookAddinSettings">
    <div class="widget_header row-fluid">
        <div class="span8"><h3>{vtranslate('LBL_OUTLOOK_ADDIN', $QUALIFIED_MODULE)}</h3></div>
        <div class="span4"><a class="btn pull-right" href="index.php?module=OutlookAddin&amp;parent=Settings&amp;view=Index"><i class="icon-refresh" aria-hidden="true"></i> {vtranslate('LBL_REFRESH', $QUALIFIED_MODULE)}</a></div>
    </div>
    <hr>
    <p>{vtranslate('LBL_INTRO', $QUALIFIED_MODULE)}</p>
    {if $SETUP_SUCCESS}<div class="alert alert-success" role="status">{vtranslate('LBL_SETUP_SUCCESS', $QUALIFIED_MODULE)}</div>{/if}
    {if $SETUP_ERROR || $OPERATION_ERROR}<div class="alert alert-error" role="alert">{vtranslate('LBL_OUTLOOK_DATABASE_ERROR', $QUALIFIED_MODULE)}</div>{/if}
    {if $MANIFEST_ERROR}<div class="alert alert-error" role="alert">{vtranslate($MANIFEST_ERROR, $QUALIFIED_MODULE)|escape:'html'}</div>{/if}
    <table class="table table-bordered table-condensed themeTableColor detailview-table">
        <thead><tr class="blockHeader"><th colspan="2" class="{$WIDTHTYPE|escape:'html'}">{vtranslate('LBL_SERVER_CHECK', $QUALIFIED_MODULE)}</th></tr></thead>
        <tbody>
            <tr><td class="fieldLabel"><span class="muted">{vtranslate('LBL_CRM_URL', $QUALIFIED_MODULE)}</span></td><td>{$CRM_URL|escape:'html'}</td></tr>
            <tr><td class="fieldLabel">{vtranslate('LBL_SERVER_FILES', $QUALIFIED_MODULE)}</td><td>
                {if $MISSING_FILES}<span class="text-error">{vtranslate('LBL_FILES_MISSING', $QUALIFIED_MODULE)}</span><ul>{foreach $MISSING_FILES as $FILE}<li>{$FILE|escape:'html'}</li>{/foreach}</ul>
                {else}<span class="label label-success">{vtranslate('LBL_OK', $QUALIFIED_MODULE)}</span>{/if}
            </td></tr>
            <tr><td class="fieldLabel">{vtranslate('LBL_CONFIG_STATUS', $QUALIFIED_MODULE)}</td><td>
                {if $CONFIG_ERROR}<span class="text-error">{vtranslate($CONFIG_ERROR, $QUALIFIED_MODULE)}</span>
                {elseif $CONFIGURED}<span class="label label-success">{vtranslate('LBL_CONFIG_PRESENT', $QUALIFIED_MODULE)}</span>
                {else}<span class="label label-warning">{vtranslate('LBL_CONFIG_MISSING', $QUALIFIED_MODULE)}</span>{/if}
            </td></tr>
            <tr><td class="fieldLabel">{vtranslate('LBL_CHECK_CONNECTION', $QUALIFIED_MODULE)}</td><td>
                <div id="outlookConnectionCheck"
                    data-language="{$ADDIN_LANGUAGE|escape:'html'}"
                    data-auth-url="{if $ADDIN_URLS}{$ADDIN_URLS.redirect|escape:'html'}{/if}"
                    data-signing-in="{vtranslate('LBL_CHECK_SIGNING_IN', $QUALIFIED_MODULE)|escape:'html'}"
                    data-reading="{vtranslate('LBL_CHECK_READING', $QUALIFIED_MODULE)|escape:'html'}"
                    data-success="{vtranslate('LBL_CHECK_SUCCESS', $QUALIFIED_MODULE)|escape:'html'}"
                    data-failed="{vtranslate('LBL_CHECK_FAILED', $QUALIFIED_MODULE)|escape:'html'}"
                    data-denied="{vtranslate('LBL_CHECK_DENIED', $QUALIFIED_MODULE)|escape:'html'}"
                    data-http-error="{vtranslate('LBL_CHECK_HTTP_ERROR', $QUALIFIED_MODULE)|escape:'html'}"
                    data-bad-reply="{vtranslate('LBL_CHECK_BAD_REPLY', $QUALIFIED_MODULE)|escape:'html'}"
                    data-blocked="{vtranslate('LBL_CHECK_BLOCKED', $QUALIFIED_MODULE)|escape:'html'}"
                    data-closed="{vtranslate('LBL_CHECK_CLOSED', $QUALIFIED_MODULE)|escape:'html'}"
                    data-timed-out="{vtranslate('LBL_CHECK_TIMEOUT', $QUALIFIED_MODULE)|escape:'html'}"
                    data-cancelled="{vtranslate('LBL_CHECK_CANCELLED', $QUALIFIED_MODULE)|escape:'html'}">
                    <button id="outlookCheckConnection" type="button" class="btn" {if !$CONFIGURED || !$ADDIN_URLS}disabled="disabled"{/if}><i class="icon-refresh" aria-hidden="true"></i> {vtranslate('LBL_CHECK_CONNECTION', $QUALIFIED_MODULE)}</button>
                    <span id="outlookCancelConnection" hidden="hidden" style="margin-left:8px"><button type="button" class="btn">{vtranslate('LBL_CHECK_CANCEL', $QUALIFIED_MODULE)}</button></span>
                    <p class="muted marginTop10px">{vtranslate('LBL_CHECK_HINT', $QUALIFIED_MODULE)}</p>
                    <p id="outlookConnectionStatus" role="status" aria-live="polite" hidden="hidden"></p>
                </div>
            </td></tr>
        </tbody>
    </table>
    <table class="table table-bordered themeTableColor">
        <thead><tr class="blockHeader"><th class="{$WIDTHTYPE|escape:'html'}">1. {vtranslate('LBL_MICROSOFT_SETUP', $QUALIFIED_MODULE)}</th></tr></thead>
        <tbody><tr><td>
            <p>{vtranslate('LBL_MICROSOFT_HINT', $QUALIFIED_MODULE)}</p>
            <ol>
                <li><a href="https://entra.microsoft.com" target="_blank" rel="noopener noreferrer">Microsoft Entra Admin Center</a>: {vtranslate('LBL_ENTRA_STEP_1', $QUALIFIED_MODULE)}</li>
                <li>{vtranslate('LBL_ENTRA_STEP_2', $QUALIFIED_MODULE)}</li>
                <li>{vtranslate('LBL_ENTRA_STEP_3', $QUALIFIED_MODULE)}
                    {if $ADDIN_URLS}<p><code>{$ADDIN_URLS.redirect|escape:'html'}</code></p>{/if}
                </li>
                <li>{vtranslate('LBL_ENTRA_STEP_4', $QUALIFIED_MODULE)}</li>
                <li>{vtranslate('LBL_ENTRA_STEP_5', $QUALIFIED_MODULE)}</li>
            </ol>
            <p><a href="https://learn.microsoft.com/entra/identity-platform/how-to-add-redirect-uri" target="_blank" rel="noopener noreferrer">{vtranslate('LBL_REDIRECT_GUIDE', $QUALIFIED_MODULE)}</a>
                &nbsp;|&nbsp; <a href="https://learn.microsoft.com/en-us/graph/api/user-post-contacts?view=graph-rest-1.0" target="_blank" rel="noopener noreferrer">{vtranslate('LBL_PERMISSION_GUIDE', $QUALIFIED_MODULE)}</a></p>
            <div class="alert alert-info">{vtranslate('LBL_PUBLIC_IDS_HINT', $QUALIFIED_MODULE)}</div>
            <form method="post" action="index.php" class="form-horizontal">
                <input type="hidden" name="module" value="OutlookAddin">
                <input type="hidden" name="parent" value="Settings">
                <input type="hidden" name="action" value="DownloadConfig">
                <div class="control-group">
                    <label class="control-label" for="outlookClientId"><span class="redColor">*</span> {vtranslate('LBL_CLIENT_ID', $QUALIFIED_MODULE)}</label>
                    <div class="controls"><input class="input-xxlarge" id="outlookClientId" name="client_id" type="text" value="{$MICROSOFT_IDS.client_id|escape:'html'}" required="required" maxlength="36" pattern="{$GUID_PATTERN|escape:'html'}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autocomplete="off">
                    <p class="muted">{vtranslate('LBL_CLIENT_HINT', $QUALIFIED_MODULE)}</p></div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="outlookTenantId"><span class="redColor">*</span> {vtranslate('LBL_TENANT_ID', $QUALIFIED_MODULE)}</label>
                    <div class="controls"><input class="input-xxlarge" id="outlookTenantId" name="tenant_id" type="text" value="{$MICROSOFT_IDS.tenant_id|escape:'html'}" required="required" maxlength="36" pattern="{$GUID_PATTERN|escape:'html'}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autocomplete="off">
                    <p class="muted">{vtranslate('LBL_TENANT_HINT', $QUALIFIED_MODULE)}</p></div>
                </div>
                <div class="controls"><button type="submit" class="btn btn-success" {if !$ADDIN_URLS}disabled="disabled"{/if}><i class="icon-download-alt icon-white" aria-hidden="true"></i> {vtranslate('LBL_DOWNLOAD_CONFIG', $QUALIFIED_MODULE)}</button></div>
            </form>
            <p class="muted marginTop10px">{vtranslate('LBL_CONFIG_DEPLOY', $QUALIFIED_MODULE)}</p>
        </td></tr></tbody>
    </table>
    <table class="table table-bordered themeTableColor">
        <thead><tr class="blockHeader"><th class="{$WIDTHTYPE|escape:'html'}">2. {vtranslate('LBL_MANIFEST', $QUALIFIED_MODULE)}</th></tr></thead>
        <tbody><tr><td>
            <p>{vtranslate('LBL_MANIFEST_HINT', $QUALIFIED_MODULE)}</p>
            {if !$MANIFEST_ERROR}<a class="btn btn-success" href="index.php?module=OutlookAddin&amp;parent=Settings&amp;action=DownloadManifest"><i class="icon-download-alt icon-white" aria-hidden="true"></i> {vtranslate('LBL_DOWNLOAD_MANIFEST', $QUALIFIED_MODULE)}</a>{/if}
        </td></tr></tbody>
    </table>
    <table class="table table-bordered themeTableColor">
        <thead><tr class="blockHeader"><th colspan="2" class="{$WIDTHTYPE|escape:'html'}">3. {vtranslate('LBL_REGISTER_SERVICES', $QUALIFIED_MODULE)}</th></tr></thead>
        <tbody>{foreach $OPERATIONS as $NAME => $VALID}<tr><td>{$NAME|escape:'html'}</td><td>
            {if $VALID}<span class="label label-success">{vtranslate('LBL_REGISTERED', $QUALIFIED_MODULE)}</span>{else}<span class="label label-important">{vtranslate('LBL_NOT_REGISTERED', $QUALIFIED_MODULE)}</span>{/if}
        </td></tr>{/foreach}
        <tr><td colspan="2"><form method="post" action="index.php">
            <input type="hidden" name="module" value="OutlookAddin"><input type="hidden" name="parent" value="Settings"><input type="hidden" name="action" value="Install">
            <button class="btn" type="submit" {if $MISSING_FILES}disabled="disabled"{/if}><i class="icon-wrench" aria-hidden="true"></i> {vtranslate('LBL_REGISTER_SERVICES', $QUALIFIED_MODULE)}</button>
        </form><p class="muted">{vtranslate('LBL_UPDATE_HINT', $QUALIFIED_MODULE)}</p></td></tr></tbody>
    </table>
    <div class="well"><h4>{vtranslate('LBL_OUTLOOK_INSTALL', $QUALIFIED_MODULE)}</h4><p>{vtranslate('LBL_OUTLOOK_INSTALL_HINT', $QUALIFIED_MODULE)}</p>
        <p><strong>{vtranslate('LBL_IMPORTANT', $QUALIFIED_MODULE)}</strong> {vtranslate('LBL_PRIVACY_HINT', $QUALIFIED_MODULE)}</p>
        <p>{vtranslate('LBL_PINNING_HINT', $QUALIFIED_MODULE)}</p>
    </div>
</div>
<script src="outlook-addin/settings-connection.js?v=20261111.7"></script>
{/strip}

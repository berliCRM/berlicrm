{strip}
<div class="container-fluid" id="WordAddinSettings">
    <div class="widget_header row-fluid">
        <div class="span8"><h3>{vtranslate('LBL_WORD_ADDIN', $QUALIFIED_MODULE)}</h3></div>
        <div class="span4"><div class="pull-right">
            <a class="btn" href="index.php?module=WordAddin&amp;parent=Settings&amp;view=Index"><i class="icon-refresh" aria-hidden="true"></i>&nbsp; {vtranslate('LBL_REFRESH', $QUALIFIED_MODULE)}</a>
        </div></div>
    </div>
    <hr>
    <div class="contents row-fluid">
        <p>{vtranslate('LBL_INTRO', $QUALIFIED_MODULE)}</p>
        {if $SETUP_SUCCESS}<div class="alert alert-success" role="status">{vtranslate('LBL_SETUP_SUCCESS', $QUALIFIED_MODULE)}</div>{/if}
        {if $SETUP_ERROR}<div class="alert alert-error" role="alert">{vtranslate('LBL_WORD_ADDIN_DATABASE_ERROR', $QUALIFIED_MODULE)}</div>{/if}
        <table class="table table-bordered table-condensed themeTableColor detailview-table">
            <thead><tr class="blockHeader"><th colspan="2" class="{$WIDTHTYPE|escape:'html'}"><span class="alignMiddle">1. {vtranslate('LBL_SERVER_CHECK', $QUALIFIED_MODULE)}</span></th></tr></thead>
            <tbody>
                <tr>
                    <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">{vtranslate('LBL_CRM_URL', $QUALIFIED_MODULE)}</label></td>
                    <td class="fieldValue {$WIDTHTYPE|escape:'html'}"><span>{$CRM_URL|escape:'html'}</span></td>
                </tr>
                <tr>
                    <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">{vtranslate('LBL_HTTPS_MANIFEST', $QUALIFIED_MODULE)}</label></td>
                    <td class="fieldValue {$WIDTHTYPE|escape:'html'}">
                        {if $MANIFEST_ERROR}<span class="text-error">{vtranslate($MANIFEST_ERROR, $QUALIFIED_MODULE)}</span>
                        {else}<span class="label label-success"><i class="icon-ok icon-white" aria-hidden="true"></i>&nbsp; {vtranslate('LBL_OK', $QUALIFIED_MODULE)}</span>{/if}
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">{vtranslate('LBL_SERVER_FILES', $QUALIFIED_MODULE)}</label></td>
                    <td class="fieldValue {$WIDTHTYPE|escape:'html'}">
                        {if $MISSING_FILES}<span class="text-error">{vtranslate('LBL_FILES_MISSING', $QUALIFIED_MODULE)}</span><ul>
                            {foreach $MISSING_FILES as $FILE}<li>{$FILE|escape:'html'}</li>{/foreach}</ul>
                        {else}<span class="label label-success"><i class="icon-ok icon-white" aria-hidden="true"></i>&nbsp; {vtranslate('LBL_OK', $QUALIFIED_MODULE)}</span>{/if}
                    </td>
                </tr>
                {foreach $OPERATIONS as $NAME => $VALID}
                <tr>
                    <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">
                        {if $NAME == 'getWordMailMergeData'}{vtranslate('LBL_SERVICE_READ', $QUALIFIED_MODULE)}{else}{vtranslate('LBL_SERVICE_WRITE', $QUALIFIED_MODULE)}{/if}
                    </label></td>
                    <td class="fieldValue {$WIDTHTYPE|escape:'html'}">
                        {if $VALID}<span class="label label-success">{vtranslate('LBL_REGISTERED', $QUALIFIED_MODULE)}</span>
                        {else}<span class="label label-important">{vtranslate('LBL_NOT_REGISTERED', $QUALIFIED_MODULE)}</span>{/if}
                    </td>
                </tr>
                {/foreach}
                <tr>
                    <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">{vtranslate('LBL_REGISTER_SERVICES', $QUALIFIED_MODULE)}</label></td>
                    <td class="fieldValue {$WIDTHTYPE|escape:'html'}">
                        <form class="form-horizontal" method="post" action="index.php">
                            <input type="hidden" name="module" value="WordAddin">
                            <input type="hidden" name="parent" value="Settings">
                            <input type="hidden" name="action" value="Install">
                            <button class="btn" type="submit" {if $MISSING_FILES}disabled="disabled"{/if}><i class="icon-wrench" aria-hidden="true"></i>&nbsp; <strong>{vtranslate('LBL_REGISTER_SERVICES', $QUALIFIED_MODULE)}</strong></button>
                        </form>
                    </td>
                </tr>
                {if $ADDIN_URLS}
                <tr>
                    <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">{vtranslate('LBL_ASSET_LINKS', $QUALIFIED_MODULE)}</label></td>
                    <td class="fieldValue {$WIDTHTYPE|escape:'html'}">
                        <a class="btn btn-small" href="{$ADDIN_URLS.taskpane|escape:'html'}" target="_blank" rel="noopener noreferrer">{vtranslate('LBL_OPEN_TASKPANE', $QUALIFIED_MODULE)} <i class="icon-share-alt" aria-hidden="true"></i></a>
                        &nbsp;<a class="btn btn-small" href="{$ADDIN_URLS.icon32|escape:'html'}" target="_blank" rel="noopener noreferrer">Icon 32</a>
                        &nbsp;<a class="btn btn-small" href="{$ADDIN_URLS.icon80|escape:'html'}" target="_blank" rel="noopener noreferrer">Icon 80</a>
                    </td>
                </tr>
                {/if}
            </tbody>
        </table>
        <p class="muted marginBottom10px">{vtranslate('LBL_CHECK_HINT', $QUALIFIED_MODULE)}</p>
        <table class="table table-bordered table-condensed themeTableColor detailview-table">
            <thead><tr class="blockHeader"><th colspan="2" class="{$WIDTHTYPE|escape:'html'}"><span class="alignMiddle">2. {vtranslate('LBL_MANIFEST', $QUALIFIED_MODULE)}</span></th></tr></thead>
            <tbody><tr>
                <td class="fieldLabel {$WIDTHTYPE|escape:'html'}"><label class="muted pull-right marginRight10px">{vtranslate('LBL_MANIFEST_FOR', $QUALIFIED_MODULE)}</label></td>
                <td class="fieldValue {$WIDTHTYPE|escape:'html'}">
                    <p>{vtranslate('LBL_MANIFEST_HINT', $QUALIFIED_MODULE)}</p>
                    {if !$MANIFEST_ERROR}
                        <a class="btn btn-success" href="index.php?module=WordAddin&amp;parent=Settings&amp;action=DownloadManifest"><i class="icon-download-alt icon-white" aria-hidden="true"></i>&nbsp; <strong>{vtranslate('LBL_DOWNLOAD_MANIFEST', $QUALIFIED_MODULE)}</strong></a>
                    {/if}
                </td>
            </tr></tbody>
        </table>
        {if !$READY}<div class="alert alert-warning" role="status">{vtranslate('LBL_SETUP_INCOMPLETE', $QUALIFIED_MODULE)}</div>{/if}
        <table class="table table-bordered table-condensed themeTableColor">
            <thead><tr class="blockHeader"><th class="{$WIDTHTYPE|escape:'html'}"><span class="alignMiddle">3. {vtranslate('LBL_WORD_INSTALL', $QUALIFIED_MODULE)}</span></th></tr></thead>
            <tbody><tr><td class="{$WIDTHTYPE|escape:'html'}">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#wordAddinMicrosoft365" data-toggle="tab">{vtranslate('LBL_CENTRAL_TAB', $QUALIFIED_MODULE)}</a></li>
                    <li><a href="#wordAddinWeb" data-toggle="tab">{vtranslate('LBL_WEB_TAB', $QUALIFIED_MODULE)}</a></li>
                    <li><a href="#wordAddinWindows" data-toggle="tab">{vtranslate('LBL_WINDOWS_TAB', $QUALIFIED_MODULE)}</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="wordAddinMicrosoft365">
                        <h4>{vtranslate('LBL_CENTRAL_INSTALL', $QUALIFIED_MODULE)}</h4>
                        <ol>
                            <li>{vtranslate('LBL_CENTRAL_STEP_1', $QUALIFIED_MODULE)}</li>
                            <li>{vtranslate('LBL_CENTRAL_STEP_2', $QUALIFIED_MODULE)}</li>
                            <li>{vtranslate('LBL_CENTRAL_STEP_3', $QUALIFIED_MODULE)}</li>
                        </ol>
                        <p><a href="https://learn.microsoft.com/en-us/microsoft-365/admin/manage/manage-deployment-of-add-ins?view=o365-worldwide" target="_blank" rel="noopener noreferrer">{vtranslate('LBL_MICROSOFT_GUIDE', $QUALIFIED_MODULE)} <i class="icon-share-alt" aria-hidden="true"></i></a></p>
                    </div>
                    <div class="tab-pane" id="wordAddinWeb">
                        <h4>{vtranslate('LBL_WEB_INSTALL', $QUALIFIED_MODULE)}</h4>
                        <p>{vtranslate('LBL_WEB_STEPS', $QUALIFIED_MODULE)}</p>
                    </div>
                    <div class="tab-pane" id="wordAddinWindows">
                        <h4>{vtranslate('LBL_WINDOWS_INSTALL', $QUALIFIED_MODULE)}</h4>
                        <ol>
                            <li>{vtranslate('LBL_WINDOWS_STEP_1', $QUALIFIED_MODULE)}</li>
                            <li>{vtranslate('LBL_WINDOWS_STEP_2', $QUALIFIED_MODULE)}</li>
                            <li>{vtranslate('LBL_WINDOWS_STEP_3', $QUALIFIED_MODULE)}</li>
                        </ol>
                        <p><a href="https://learn.microsoft.com/en-us/office/dev/add-ins/testing/create-a-network-shared-folder-catalog-for-task-pane-and-content-add-ins" target="_blank" rel="noopener noreferrer">{vtranslate('LBL_MICROSOFT_GUIDE', $QUALIFIED_MODULE)} <i class="icon-share-alt" aria-hidden="true"></i></a></p>
                    </div>
                </div>
            </td></tr></tbody>
        </table>
        <div class="well">
            <h4>{vtranslate('LBL_FIRST_LOGIN', $QUALIFIED_MODULE)}</h4>
            <p>{vtranslate('LBL_LOGIN_HINT', $QUALIFIED_MODULE)}</p>
            {if $ADDIN_URLS}<p><strong>{vtranslate('LBL_CRM_URL', $QUALIFIED_MODULE)}:</strong>&nbsp; {$ADDIN_URLS.crm|escape:'html'}</p>{/if}
        </div>
        <p class="muted">{vtranslate('LBL_UPDATE_HINT', $QUALIFIED_MODULE)}</p>
    </div>
</div>
{/strip}

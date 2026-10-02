<div class="container-fluid" id="backupSettings" data-version="{$BACKUP_VERSION|escape:'html'}">
    <h3>{vtranslate('LBL_BACKUP', $QUALIFIED_MODULE)}</h3>
    <hr>
    <p class="alert alert-info">{vtranslate('LBL_BACKUP_NOTICE', $QUALIFIED_MODULE)}</p>
    <div class="well" id="backupSpace" data-unknown="{vtranslate('LBL_BACKUP_SPACE_UNKNOWN', $QUALIFIED_MODULE)|escape:'html'}">
        <dl class="dl-horizontal">
            <dt>{vtranslate('LBL_BACKUP_SPACE_FREE', $QUALIFIED_MODULE)}</dt>
            <dd data-space="free">{vtranslate('LBL_BACKUP_SPACE_LOADING', $QUALIFIED_MODULE)}</dd>
            <dt>{vtranslate('LBL_BACKUP_SPACE_ARCHIVE', $QUALIFIED_MODULE)}</dt>
            <dd data-space="archive">{vtranslate('LBL_BACKUP_SPACE_LOADING', $QUALIFIED_MODULE)}</dd>
            <dt>{vtranslate('LBL_BACKUP_SPACE_REQUIRED', $QUALIFIED_MODULE)}</dt>
            <dd data-space="required">{vtranslate('LBL_BACKUP_SPACE_LOADING', $QUALIFIED_MODULE)}</dd>
            <dt>{vtranslate('LBL_BACKUP_SPACE_RESERVE', $QUALIFIED_MODULE)}</dt>
            <dd data-space="reserve">{vtranslate('LBL_BACKUP_SPACE_LOADING', $QUALIFIED_MODULE)}</dd>
        </dl>
        <p>{vtranslate('LBL_BACKUP_SPACE_NOTICE', $QUALIFIED_MODULE)}</p>
        <p class="alert alert-warning hide" id="backupSpaceWarning">{vtranslate('LBL_BACKUP_SPACE_INSUFFICIENT', $QUALIFIED_MODULE)}</p>
    </div>
    <p class="alert alert-error {if !$BACKUP_ERROR}hide{/if}" id="backupError">{$BACKUP_ERROR|escape:'html'}</p>
    <button type="button" class="btn btn-success" id="requestBackup" {if $BACKUP_ERROR}disabled{/if}>{vtranslate('LBL_BACKUP_CREATE', $QUALIFIED_MODULE)}</button>
    <a class="btn" href="index.php?module=Vtiger&amp;parent=Settings&amp;view=Backup">{vtranslate('LBL_BACKUP_REFRESH', $QUALIFIED_MODULE)}</a>
    <br><br>
    <table class="table table-bordered">
        <thead><tr>
            <th>{vtranslate('LBL_BACKUP_CREATED', $QUALIFIED_MODULE)}</th>
            <th>{vtranslate('LBL_BACKUP_STATUS', $QUALIFIED_MODULE)}</th>
            <th>{vtranslate('LBL_BACKUP_SIZE', $QUALIFIED_MODULE)}</th>
            <th>{vtranslate('LBL_BACKUP_MAIL', $QUALIFIED_MODULE)}</th>
            <th>{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}</th>
        </tr></thead>
        <tbody>
        {foreach $BACKUP_JOBS as $JOB}
            <tr>
                <td>{$JOB.created|date_format:'%Y-%m-%d %H:%M:%S'}</td>
                <td>{vtranslate('LBL_BACKUP_STATE_'|cat:$JOB.status, $QUALIFIED_MODULE)}
                    {if $JOB.error}<br>{vtranslate($JOB.error, $QUALIFIED_MODULE)|escape:'html'}{/if}
                </td>
                <td>{($JOB.size / 1048576)|string_format:'%.1f'} MiB</td>
                <td>{vtranslate('LBL_BACKUP_MAIL_'|cat:$JOB.mail, $QUALIFIED_MODULE)}</td>
                <td>
                    {if $JOB.status == 'ready'}
                        <a class="btn" href="index.php?module=Vtiger&amp;parent=Settings&amp;action=BackupDownload&amp;id={$JOB.id|escape:'html'}">{vtranslate('LBL_BACKUP_DOWNLOAD', $QUALIFIED_MODULE)}</a>
                    {/if}
                    {if $JOB.status == 'ready' || $JOB.status == 'failed'}
                        <button type="button" class="btn deleteBackup" data-id="{$JOB.id|escape:'html'}" data-confirm="{vtranslate('LBL_BACKUP_DELETE_CONFIRM', $QUALIFIED_MODULE)|escape:'html'}">{vtranslate('LBL_DELETE', $QUALIFIED_MODULE)}</button>
                    {/if}
                </td>
            </tr>
        {foreachelse}
            <tr><td colspan="5">{vtranslate('LBL_BACKUP_EMPTY', $QUALIFIED_MODULE)}</td></tr>
        {/foreach}
        </tbody>
    </table>
</div>

<div class="container-fluid" id="gdprConfigDetails">
	<div class="widget_header row-fluid">
		<div class="span8"><h3>{vtranslate('LBL_ENABLE_COMMENTS_FOR_MODULE', $QUALIFIED_MODULE)}</h3></div>
	</div>
    <hr>
    <div class="widget_header row-fluid">
        
        {if $smarty.get.saved ==1}
        <div class="span4 alert alert-info">
            {vtranslate('LBL_SAVE_SUCCESS')}
        </div>
        {/if}
        
        <form action="index.php?parent=Settings&module=ModComments&action=save" method="POST">
            <table class="table table-bordered table-condensed themeTableColor">
            <thead>
                <tr>
                    <th>{vtranslate('LBL_MODULE')}</td>
                    <th>{vtranslate('LBL_COMMENTS_ENABLED')}</td>
                </tr>
            </thead>
            <tbody>
            {foreach key=NAME_TRANSLATED item=MODEL from=$ALL_MODULES}
            <tr>
                <td>
                    {$NAME_TRANSLATED|escape}
                </td>
                <td>
                    <input type="checkbox" name='commentsenabled[{$MODEL->name}]' {if $MODEL->isCommentEnabled}checked{/if}>
            </tr>
            {/foreach}
            <tr>
                <td colspan="2"> 
                    <button class="btn btn-success pull-right" type="submit">{vtranslate('LBL_SAVE')}</button>
                </td>	
            </tr>
            </tbody>
            </table>
        </form>
    </div>
</div>
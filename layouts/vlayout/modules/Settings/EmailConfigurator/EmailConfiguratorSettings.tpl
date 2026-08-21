<div class="container-fluid">

    <div class="widget_header row-fluid">
        <h3>{vtranslate('LBL_EMAILCON_ADDRESSES',$MODULE)}</h3>
    </div>

    <HR>

    <div class="contents row-fluid">
        <p>
            {vtranslate('LBL_EMAILCON_ADD_DESC',$MODULE)}
        </p>

        <table class="table table-bordered">
            <tr>
                <th>{vtranslate('LBL_EMAILCON_FIRSTNAME',$MODULE)}
                <th>{vtranslate('LBL_EMAILCON_LASTNAME',$MODULE)}
                <th>{vtranslate('LBL_EMAILCON_ADDRESS',$MODULE)}
                <th>{vtranslate('LBL_EMAILCON_NOTE',$MODULE)}
            </tr>

            {foreach from=$EMAILADDRESSES item=addr}
            <tr>
                {*$addr['emailid']*}
                <td>{$addr['email_firstname']|escape:"html"}
                <td>{$addr['email_lastname']|escape:"html"}
                <td>{$addr['email_address']|escape:"html"}
                <td>{$addr['email_desc']|escape:"html"}
                <div class="actions pull-right">
                    <a href="?module=EmailConfigurator&parent=Settings&view=Edit&record={$addr['emailid']}">
                        <i title="Bearbeiten" class="icon-pencil alignMiddle"></i>
                    </a>
                    <a class="deleteRecordButton" href="#" data-id="{$addr['emailid']}">
                        <i title="Löschen" class="icon-trash alignMiddle"></i>
                    </a>
                </div>
            </tr>
            {/foreach}

        </table>
        
    </div>
    <br>

    <button class="btn addButton" onClick="location.href='index.php?module=EmailConfigurator&parent=Settings&view=Edit';">
        {vtranslate('LBL_EMAILCON_ADD',$MODULE)}
    </button>

</div>

{foreach from=$records item=row}
    <tr class="listViewEntries" data-id="{$row.crmid}" data-recordurl="index.php?module={$moduleName}&amp;view=Detail&amp;record={$row.crmid}">
        <td class="fulltextSearchCheckboxTD"><input type="checkbox" class="fulltextSearchCheckbox" value="{$row.crmid}" /></td>
        {foreach from=$row.data item=dataField}
        <td class="listViewEntryValue medium" nowrap=""><a href="index.php?module={$moduleName}&amp;view=Detail&amp;record={$row.crmid}">{$dataField|highlight:$q}</a></td>
        {/foreach}
    </tr>
{foreachelse}
    <tr class="listViewEntries" data-id="{$row.crmid}" data-recordurl="index.php?module={$moduleName}&amp;view=Detail&amp;record={$row.crmid}">
        <td colspan="{count($row.data)}">{vtranslate('no Records', 'FulltextSearch')}</td>
    </tr>
{/foreach}

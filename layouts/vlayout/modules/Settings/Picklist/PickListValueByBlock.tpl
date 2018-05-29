<div class="row-fluid">
    <div class="alert alert-info">{vtranslate('LBL_DYNAMIC_BLOCKS_INFO',$QUALIFIED_MODULE)}</div>
    <strong>{vtranslate('LBL_SELECT_PICKLIST_ENTRY',$QUALIFIED_MODULE)} </strong>
        <select class="chzn-select" name="picklistentry" id="picklistentry" style="margin-left:10px">
        <optgroup label='{vtranslate('LBL_SINGLEVIEW',$QUALIFIED_MODULE)}'>
        {foreach key=PICKLIST_KEY item=PICKLIST_VALUE from=$PICKLIST_VALUES}
            <option value={$PICKLIST_KEY}>{vtranslate($PICKLIST_VALUE,$SELECTED_MODULE_NAME)|escape}</option>
        {/foreach}
        </optgroup>
        <optgroup label='{vtranslate('LBL_OVERVIEW',$QUALIFIED_MODULE)}'>
        <option value='_showall'>{vtranslate('LBL_SHOWALL',$QUALIFIED_MODULE)}</option>
        </optgroup>
        </select>
    <button id='saveDynamicBlocks' class="btn btn-success" style="margin:0 0 8px 20px">{vtranslate('LBL_SAVE',$QUALIFIED_MODULE)}</button>
</div>
<br>
<input type="hidden" name="picklistid" value="{$PICKLISTID}">
<input type="hidden" name="moduleid" value="{$MODULEID}">
{foreach key=PICKLIST_KEY item=PICKLIST_VALUE from=$PICKLIST_VALUES}
<div class="pltables" id="picklisttable{$PICKLIST_KEY}" style="display:none;margin-bottom:20px">
  <table style="width:600px;" class="table table-bordered">
  <caption>{vtranslate('LBL_OPTIONS_FOR_ENTRY',$QUALIFIED_MODULE)} »<b>{vtranslate($PICKLIST_VALUE,$SELECTED_MODULE_NAME)|escape}</b>«</caption>
    <thead><tr class="listViewHeaders"><th style='text-align:left'>{vtranslate('LBL_BLOCK_NAME',$QUALIFIED_MODULE)}<th>{vtranslate('LBL_INITIAL_HIDE',$QUALIFIED_MODULE)}<th>{vtranslate('LBL_HIDDEN',$QUALIFIED_MODULE)}</tr></thead>
    {foreach item=BLOCK from=$BLOCKS}
        <tr>
            <td>{vtranslate($BLOCK->get("label"),$SELECTED_MODULE_NAME)|escape}</td>
            <td><input type="checkbox" name="dynblock[{$PICKLIST_KEY}][{$BLOCK->get("id")}][hidden]" {if $DYNAMIC_BLOCKS[{$PICKLIST_KEY}][{$BLOCK->get("id")}]["initialstatus"]}checked{/if}></td>
            <td>
            {if $SELECTED_PICKLIST_FIELDMODEL->block->id == $BLOCK->get("id")}
                <input type="checkbox" name="dynblock[{$PICKLIST_KEY}][{$BLOCK->get("id")}][blocked]" disabled>
                <i class="icon-info-sign pull-right" style="margin:3px" rel="popover" data-placement="top" data-trigger="hover" data-content="{vtranslate('LBL_UNAVAILABLE',$QUALIFIED_MODULE)}" data-original-title="Info"></i>
            {else}
                <input type="checkbox" name="dynblock[{$PICKLIST_KEY}][{$BLOCK->get("id")}][blocked]" {if $DYNAMIC_BLOCKS[{$PICKLIST_KEY}][{$BLOCK->get("id")}]["blocked"]}checked{/if}>
            {/if}
            </td>
        </tr>
    {/foreach}
  </table>
</div>
{/foreach}
<script>
jQuery('[rel=popover]').popover();
</script>
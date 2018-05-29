<div class="container-fluid" id="fulltextSearchContent">
    <input type="hidden" id="search_value" value="{$q}" />
    <input type="hidden" id="search_limit" value="{$search_limit}" />

    <div class="widget_header row-fluid">
        <div class="span12">
            <h3>
                <b>
                    {vtranslate('Search Results', 'FulltextSearch')}
                </b>
            </h3>
        </div>
    </div>
    <hr>

    <div>
        {if $show_index_error eq true}
            <div class="alert alert-error">
              <h4 style="color: #b94a48;">{vtranslate('ERROR', 'FulltextSearch')}!</h4>
              {vtranslate('Your index is empty. Please configure the module in the CRM Settings and wait until the index was fully created the very first time.', 'FulltextSearch')}
            </div>
        {/if}

        {if $foundAny eq false}
            <p class="alert alert-info">{vtranslate('no records found', 'FulltextSearch')}</p>
        {/if}
        {foreach from=$modules item=module}
            {assign var="moduleName" value=$module.module}
            {if !empty($searchResults[$module.module].records)}
                <h4 style="margin:20px 0 0 0;">{vtranslate($module.module,$module.module)}&nbsp;&nbsp;<span style="font-size: 12px;font-weight: normal;">({$searchResults[$module.module].totals} {vtranslate('Search Results', 'FulltextSearch')})</span></h4>
                <hr/>
                <table class="table table-bordered listViewEntriesTable" data-module="{$module.module}">
                    <thead>
                    <tr class="listViewHeaders">
                        <th width="5">&nbsp;</th>
                        {foreach from=$module.labels item=field}
                            <th nowrap="">{vtranslate($field, $module.module)}</th>
                        {/foreach}
                    </tr>
                    <tbody id="searchResultEntries{$module.module}">
                    {include file='modules/FulltextSearch/SearchResultEntries.tpl' records=$searchResults[$module.module].records}
                    </thead>
                </table>
                {if $searchResults[$module.module].more eq true}
                <div style="text-align:center;">
                    <span style="display: none;" id="loadingResults{$module.module}">{vtranslate('loading...', 'FulltextSearch')}</span>
                    <input type="button" class="btn btn-success loadMoreLink loadMoreLink{$module.module}" data-offset="{count($searchResults[$module.module].records)}" data-limit="{$search_limit}" data-total="{$searchResults[$module.module].totals}" data-module="{$module.module}" value="&dArr; {vtranslate('load more', 'FulltextSearch')} &dArr;" />
                    <input type="button" class="btn loadMoreLink loadMoreLink{$module.module}" data-offset="{count($searchResults[$module.module].records)}" data-limit="{$searchResults[$module.module].totals}" data-total="{$searchResults[$module.module].totals}" data-module="{$module.module}" value="&dArr; {vtranslate('load all results', 'FulltextSearch')} &dArr;" />
                </div>
                {/if}
            {/if}
        {/foreach}
    </div>
</div>
<script type="text/javascript">
    jQuery('#globalSearchValue').val('{$q}');
</script>
<style type="text/css">
    span.hilight {
        background-color:#fff792;
        padding:2px 0;
        border-right:1px dashed red;
        border-left:1px dashed red;
    }
</style>

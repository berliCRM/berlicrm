/**
 * Global Search Script
 */

jQuery(function () {
    jQuery("#globalSearchValue").off("keypress").on("keypress", function(event) {

        if (event.which == 13) {
            event.preventDefault();
            event.stopPropagation();

            var val = jQuery("#globalSearchValue").val();

            var singleModule = jQuery('#basicSearchModulesList').val();

            window.location.href = "index.php?module=FulltextSearch&view=SearchResult&q=" + encodeURIComponent(val) + "&s=" + singleModule;
        }
    });

    if(jQuery('#fulltextSearchContent').length > 0) {
        jQuery('#leftPanel').addClass('hide');
        jQuery('#rightPanel').removeClass('span10').addClass('span12');

        jQuery('#fulltextSearchContent .fulltextSearchCheckboxTD').on('click', function(event) {
            jQuery(this).find('input').trigger('click');
            event.stopPropagation();
        });
        jQuery('#fulltextSearchContent .fulltextSearchCheckbox').on('click', function(event) {
            event.stopPropagation();
        });
        jQuery('.fulltextPDFMakerEditLink').on('click', function() {
            var values = [];
            var table = jQuery(this).closest('table');
            var moduleName = table.data('module');

            table.find('.fulltextSearchCheckbox:checked').each(function(index, value) {
                values.push(value.value);
            });

            if(values.length == 0) {
                return;
            }

            var params = {
                'module': 'PDFMaker',
                'view': 'EditAndExport',
                'formodule': moduleName,
                'mode': 'composeMailData',
                'record': values[0],
                'language': jQuery('body').data('language'),
                'commontemplateid': jQuery('.pdfmakerTemplate', table).val()
            };
            var popupInstance = Vtiger_Popup_Js.getInstance();
            popupInstance.show(params, "", "EditAndExport");

        });

    }

});
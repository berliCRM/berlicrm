    /*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  crm-now CRM Open Source
 * The Initial Developer of the Original Code is crm-now.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("ToolWidgets_popupMenueCopyAndPaste_Js", {
    /**
     * Flag to track if the modal is being built.
     */
    isModalBuilding: false,

    /**
     * Function to register button event.
     */
    registerCopyPasteButtonEvent: function () {
        jQuery('#copypasteButton').on('click', function (e) {
            ToolWidgets_popupMenueCopyAndPaste_Js.showmenu(jQuery(e.currentTarget));
        });
    },

    /**
     * Function to display the menu in a modal window.
     * Ensures only one modal instance is created per click, even after closing.
     * @param {Object} element - The DOM element triggering the event.
     */
    showmenu: function (element) {
        if (ToolWidgets_popupMenueCopyAndPaste_Js.isModalBuilding) {
            // Prevent multiple parallel calls to build the modal
            return;
        }

        ToolWidgets_popupMenueCopyAndPaste_Js.isModalBuilding = true; // Set flag to prevent duplicate requests

        var recordid = jQuery('#recordid').val();
        var sourcemodule = jQuery('#sourcemodule').val();

        var params = {
            module: 'ToolWidgets',
            view: 'createCopyAndPasteMenu',
            sourcemodule: sourcemodule,
            recordid: recordid
        };

        AppConnector.request(params).then(
            function (data) {
                app.showScrollBar(jQuery('#transferPopupScroll'), {
                    height: '400px',
                    railVisible: true,
                    size: '6px'
                });

                var callbackFunction = function (data) {
                    app.showScrollBar(jQuery('#transferPopupScroll'), {
                        height: '400px',
                        railVisible: true,
                        size: '6px'
                    });
                };

                app.showModalWindow(data, function (data) {
                    $("#copied").hide();

                    const copyButton = document.getElementById("copy-button");
                    const textToCopy = document.getElementById("copy-text").value;

                    if (typeof callbackFunction === 'function' && jQuery('#transferPopupScroll').height() > 400) {
                        callbackFunction(data);
                    }

                    copyButton.addEventListener("click", function () {
                        navigator.clipboard.writeText(textToCopy)
                            .then(() => $("#copied").show())
                            .catch(err => console.log("Could not copy text: ", err));
                    });

                    // Reset the flag when the modal is closed
                    jQuery('.modal').on('hidden.bs.modal', function () {
                        ToolWidgets_popupMenueCopyAndPaste_Js.isModalBuilding = false;
                    });
                });

                // Reset the flag after successful creation
                ToolWidgets_popupMenueCopyAndPaste_Js.isModalBuilding = false;
            },
            function (error) {
                alert('Error Ajax: ' + error.toString());
                // Reset the flag in case of error
                ToolWidgets_popupMenueCopyAndPaste_Js.isModalBuilding = false;
            }
        );
    },

    /**
     * Function to register all required events.
     */
    registerEvents: function () {
        this.registerCopyPasteButtonEvent();
    }
}, {});
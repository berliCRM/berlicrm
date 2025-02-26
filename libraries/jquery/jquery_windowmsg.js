/*
 * WindowMsg jquery plugin
 *
 * Copyright (c) 2008 Peter Hulst (sfpeter.com / hollice.com)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Version 1.0
 */
(function ($) {

    // this array keeps the list of event handlers and names
    $.windowMsgHandlers = [];

    // this init method must be called in the parent window upon page load
    $.initWindowMsg = function () {
        $('body').append($('<form name="windowComm"></form>')
            .append($('<input type="hidden" name="windowCommEvent">'))
            .append($('<input type="hidden" name="windowCommData">'))
            .append($('<input id="myinput" type="button" name="windowCommButton" value="" style="display:none">')));

        // register event listener
        $('#myinput').click(function () {
            eventType = $('[name=windowCommEvent]').val();
            data = $('[name=windowCommData]').val();

            if (data == undefined || data == "undefined" || data == null) {

            }
            else {
                // build a object
                let dataObj = JSON.parse(data);

                for (const [key, value] of Object.entries(dataObj)) {
                    // decode entity: It works by creating a <textarea> element and injecting your encoded HTML into it. 
                    let decodedValue = $('<textarea />').html(value.name).text();
                    value.name = decodedValue;
                }
                // build again a string
                data = JSON.stringify(dataObj);
            }

            for (let i = 0; i < $.windowMsgHandlers.length; i++) {
                h = $.windowMsgHandlers[i];
                if (h.event == eventType) {
                    h.callback.call(null, data); // call the callback method
                    break;
                }
            }
        });
    };

    // triggers an event in the parent window. Returns true if the 
    // message was succesfully sent, otherwise false. 
    $.triggerParentEvent = function (event, msg) {
        $.triggerWindowEvent(window.opener, event, msg);
    };

    // triggers an event in a window that was opened by the current window
    $.triggerWindowEvent = function (otherWindow, event, msg) {
        if (typeof otherWindow == "object") {
            form = otherWindow.document.forms["windowComm"];
            if (form) {
                form.windowCommEvent.value = event;
                form.windowCommData.value = msg;
                form.windowCommButton.click();
                return true;
            }
        }
        return false;
    }

    // adds a handler for a message from child window    
    $.windowMsg = function (event, callback) {
        $.windowMsgHandlers.push({ event: event, callback: callback });
    }

})(jQuery);

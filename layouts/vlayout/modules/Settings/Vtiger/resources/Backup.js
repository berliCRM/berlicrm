jQuery.Class('Settings_Vtiger_Backup_Js', {}, {
    registerEvents: function () {
        var container = jQuery('#backupSettings');
        var space = container.find('#backupSpace');
        function spaceUnavailable() {
            space.find('[data-space]').text(space.data('unknown'));
        }
        AppConnector.request({module: 'Vtiger', parent: 'Settings', action: 'Backup', mode: 'space'}).then(
            function (response) {
                if (!response.success) { spaceUnavailable(); return; }
                space.find('[data-space]').each(function () {
                    var field = jQuery(this);
                    field.text(response.result[field.data('space')]);
                });
                container.find('#backupSpaceWarning').toggleClass('hide', !response.result.insufficient);
            }, spaceUnavailable
        );
        function submit(mode, id, button) {
            button.prop('disabled', true);
            AppConnector.request({module: 'Vtiger', parent: 'Settings', action: 'Backup', mode: mode, id: id}).then(
                function (response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        container.find('#backupError').text(response.error.message).removeClass('hide');
                        button.prop('disabled', false);
                    }
                }, function () {
                    window.location.reload();
                }
            );
        }
        container.find('#requestBackup').on('click', function () {
            submit('create', '', jQuery(this));
        });
        container.find('.deleteBackup').on('click', function () {
            var button = jQuery(this);
            if (window.confirm(button.data('confirm'))) {
                submit('delete', button.data('id'), button);
            }
        });
        function poll() {
            if (!container.length || !jQuery.contains(document, container[0])) {
                return;
            }
            if (document.hidden) {
                window.setTimeout(poll, 15000);
                return;
            }
            AppConnector.request({module: 'Vtiger', parent: 'Settings', action: 'Backup', mode: 'status'}).then(
                function (response) {
                    if (response.success && response.result.version !== container.data('version')) {
                        window.location.reload();
                    } else {
                        window.setTimeout(poll, 15000);
                    }
                }, function () {
                    window.setTimeout(poll, 30000);
                }
            );
        }
        if (!container.find('#requestBackup').prop('disabled')) {
            window.setTimeout(poll, 15000);
        }
    }
});

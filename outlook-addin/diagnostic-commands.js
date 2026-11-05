/* global Office */
// Diagnostic function command, following OfficeDev's TaskPane-JS example.
// Displays an Outlook notification without reading or changing message content.
Office.onReady(function () { globalThis.BerliI18n.initialize(); });

function diagnosticCommand(event) {
  var completed = false;
  function complete() {
    if (!completed) {
      completed = true;
      event.completed();
    }
  }

  console.info(globalThis.BerliI18n.t('DIAGNOSTIC_HANDLER_STARTED'));
  try {
    Office.context.mailbox.item.notificationMessages.replaceAsync(
      'berliCrmCommandDiagnostic',
      {
        type: Office.MailboxEnums.ItemNotificationMessageType.InformationalMessage,
        message: globalThis.BerliI18n.t("BERLICRM_COMMAND_EXECUTED") + new Date().toISOString() + ').',
        icon: 'icon32',
        persistent: true
      },
      function (result) {
        try {
          if (result.status === Office.AsyncResultStatus.Failed) {
            console.error(globalThis.BerliI18n.t('DIAGNOSTIC_MESSAGE_FAILED'), result.error);
          } else {
            console.info(globalThis.BerliI18n.t('DIAGNOSTIC_MESSAGE_SHOWN'));
          }
        } finally {
          complete();
        }
      }
    );
  } catch (error) {
    console.error(globalThis.BerliI18n.t("BERLICRM_COMMAND_TEST_ERROR"), error);
    complete();
  }
}

Office.actions.associate('diagnosticCommand', diagnosticCommand);

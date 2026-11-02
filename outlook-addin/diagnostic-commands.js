/* global Office */
// Diagnostic function command, following OfficeDev's TaskPane-JS example.
// Displays an Outlook notification without reading or changing message content.
Office.onReady(function () {});

function diagnosticCommand(event) {
  var completed = false;
  function complete() {
    if (!completed) {
      completed = true;
      event.completed();
    }
  }

  console.info('berliCRM Befehlstest: handler gestartet.');
  try {
    Office.context.mailbox.item.notificationMessages.replaceAsync(
      'berliCrmCommandDiagnostic',
      {
        type: Office.MailboxEnums.ItemNotificationMessageType.InformationalMessage,
        message: 'berliCRM: Befehl ausgeführt (' + new Date().toISOString() + ').',
        icon: 'icon32',
        persistent: true
      },
      function (result) {
        try {
          if (result.status === Office.AsyncResultStatus.Failed) {
            console.error('berliCRM Befehlstest: Meldung fehlgeschlagen.', result.error);
          } else {
            console.info('berliCRM Befehlstest: Meldung angezeigt.');
          }
        } finally {
          complete();
        }
      }
    );
  } catch (error) {
    console.error('berliCRM Befehlstest: Fehler.', error);
    complete();
  }
}

Office.actions.associate('diagnosticCommand', diagnosticCommand);

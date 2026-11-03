# berliCRM Outlook Add-in

[Deutsch / original documentation](README.md) | **English**

The current add-in interface includes German labels. Where a German label is
quoted below, it identifies the control shown in that interface; the explanatory
text is in English.

This Office.js add-in is served by the berliCRM installation and uses a
classic XML Outlook manifest. It supports CRM contact search, recent events,
and adding CRM contacts to the recipient fields of an Outlook email draft.
Contacts in a berliCRM Verteiler can also be added in one step.
When opened from an Outlook appointment, the same add-in instead shows a
calendar form for explicitly copying or updating that appointment in the CRM.
The link **CRM- und Outlook-Kontakte verwalten** opens a separate contacts view
with CRM login, selected CRM-to-Outlook contact creation and selected
Outlook-to-CRM import. It checks the user's CRM mandatory fields before creation.
See [CONTACTS.en.md](CONTACTS.en.md) for Microsoft Entra setup, update files and usage.
After validating the CRM session, the contacts view first attempts Microsoft SSO
without opening a window. The manual sign-in menu appears only if that attempt
fails. A successful connection shows the connected account and the contact-folder
controls. The existing Entra SPA redirect is reused; no extra registration is
needed for this update. Deploy both `assets/contacts-auth.bundle.js` and the new
`assets/contacts-sso.bundle.js` together with the updated contacts/auth HTML and
`contacts.js`; `npm run build:auth` in `outlook-addin/` builds both bundles.
Received emails now show a separate sign-in/archive view with exact sender lookup,
contact multi-selection and storage in CRM Emails. See [MAIL-ARCHIVE.en.md](MAIL-ARCHIVE.en.md).

## IMPORTANT: Optional connected experiences

> **IMPORTANT for installation from a manifest file:** In Outlook on the web,
> **Optional connected experiences must be enabled** to use manually installed
> add-ins. This applies to **Edge and Firefox**. This is an Outlook setting,
> not a browser setting.

In Outlook, open **Settings (gear icon) → General → Privacy and data →
Privacy settings → Optional connected experiences**. Enable the option and
save if required. If the organization manages the value and it cannot be
changed, ask the Microsoft 365 administrator to check it.

Then close all Outlook tabs in the affected browser, reopen Outlook and test
berliCRM twice: open it, close the task pane, then reopen it on the same email.
This settings change requires neither reinstallation nor CRM webservice
registration.

**Firefox:** Also check the setting in the Outlook session opened in Firefox.
It may already be enabled after changing it in Edge; in that case, refresh
Outlook in Firefox or close and reopen it. No additional Firefox setting is
universally required. Only if the add-in remains blocked, temporarily disable
Enhanced Tracking Protection **for the Outlook website only**, using the shield
icon to the left of the address bar. Re-enable it if this does not help;
evaluate this browser test separately from the Outlook setting. See
[Mozilla's instructions](https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop).

Microsoft documents that manually installed add-ins cannot be used in Outlook
on the web when optional connected experiences are disabled; add-ins centrally
deployed by administrators remain available. This applies even if manual
installation initially succeeds. See
[Microsoft's documentation](https://learn.microsoft.com/en-us/office/dev/add-ins/concepts/privacy-and-security#optional-connected-experiences-in-outlook).

In the case reported on November 2, 2026, berliCRM initially opened once in both
Edge and Firefox. After closing the pane, it could not be reopened and sometimes
disappeared from the Apps menu. The failure also occurred in an Edge guest
window. The Outlook setting was disabled. After enabling it, the user reported
an improvement in Edge. Retesting in Firefox remains pending. A single successful
launch in a guest window therefore does not establish that the issue is limited
to the normal browser profile.

## Requirements

- Microsoft 365 / Exchange Online or Exchange 2016+ with Outlook Mailbox 1.3
  support.
- Calendar transfer needs Mailbox 1.7 or later to check recurrence. Older
  clients can still use the mail features; the calendar form reports that its
  recurrence check is unavailable.
- A public HTTPS URL for this berliCRM installation and a trusted TLS
  certificate.
- Each user needs their berliCRM username and Access Key.
- The contacts manager additionally needs an Exchange Online mailbox, a
  Microsoft Entra SPA registration with delegated `Contacts.ReadWrite`, and
  Office Dialog API support when used inside Outlook. See [CONTACTS.en.md](CONTACTS.en.md).
- Users run the installed add-in with their normal Microsoft 365 account;
  neither a Windows administrator account nor a Microsoft 365 administrator
  role is required for everyday use.
- A Microsoft 365 administrator is required for organization-wide deployment.

## Pinning and CRM login when reading or composing mail

Outlook keeps task pane pinning separate for Message Read and Message Compose.
Pinning while reading does not open the pane automatically when composing.
Open berliCRM once in a new message and pin it there as well. See
[Microsoft's description of supported pinning modes](https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/pinnable-taskpane#supported-outlook-modes).

The CRM session is reused when the pane is reopened in the same browser context
and its saved session is available. While signed in, all modes show the compact
login status and **Anders anmelden**, including the compose view. The login
fields are shown when signed out, when an expired session is detected, or after
choosing **Anders anmelden**. The Access Key is not saved with the session.

## Server installation

### Administrator setup page

After deploying the updated files, run the normal CRM `db_update.php` process,
or use `php outlook-addin/build/registerOutlookAddinOperation.php` for a targeted
Outlook setup. Both initialize the six services and the repeatable settings menu
entry under **Settings → Integration → Outlook Add-in**. Direct route:
`index.php?module=OutlookAddin&parent=Settings&view=Index`.

The administrator page checks local files and service registrations, explains
the Microsoft Entra setup and shows the exact SPA redirect URI for this CRM.
Both **Application (client) ID / client_id** and **Directory (tenant) ID / tenant_id**
are mandatory GUIDs, checked in the browser and on the server. Existing public IDs
are prefilled if available; legacy CRM credentials are never displayed or exported.
Without an existing configuration, both inputs remain empty and GUID-format
examples appear only as placeholders.

**Test Microsoft connection** opens the existing sign-in flow using the deployed
server configuration, then makes one read-only Graph request for at most one
personal contact ID. The result confirms sign-in and read access, including an
empty contact folder; it does not claim to have tested writes. No contacts are
modified and no contact IDs or access tokens are displayed or stored in the CRM.
Changes entered in the download form must be uploaded as configuration before
they can be tested. Deploy `outlook-addin/settings-connection.js` together with
the settings template, model and translations.

The page provides two downloads:

- `outlook.config.php`: includes the entered IDs, the configured CRM webservice
  URL and SPA redirect, a 30-second timeout and the 500-recipient limit. Upload it
  to `outlook-addin/rest/outlook.config.php`. Compare any existing custom settings
  before replacing that file. The page itself never overwrites the server file.
- `berliCRM-Outlook-Addin-manifest.xml`: uses the installation's configured HTTPS
  `site_URL`, preserving its subpath and port. The manifest template, identity and
  version stay unchanged. Install the download in Outlook or deploy it centrally.

The page is restricted to CRM administrators. Configuration downloads and service
repairs require authenticated POST requests with the CRM's CSRF validation. The
service repair uses the same initializer as the CRM updater. No client secret or
additional CRM API user is required. Detailed setup instructions are also in
[CONTACTS.en.md](CONTACTS.en.md).

Deploy these files while preserving the directory structure:

- `outlook-addin/`
- `outlook-addin/rest/outlook.php`
- `outlook-addin/rest/OutlookWebserviceClient.php`
- `outlook-addin/rest/OutlookContactsConfig.php`
- `outlook-addin/rest/outlook.config.example.php`
- `include/Webservices/Custom/OutlookSendMail.php`
- `include/Webservices/Custom/OutlookGetVerteilerContacts.php`
- `include/Webservices/Custom/OutlookGetEventDisplay.php`
- `include/Webservices/Custom/OutlookCalendarEvent.php`
- `include/Webservices/Custom/OutlookContactTransfer.php`
- `include/Webservices/Custom/OutlookArchiveEmail.php`
- `modules/Settings/OutlookAddin/models/Module.php`
- `outlook-addin/build/registerOutlookAddinOperation.php`
- `outlook-addin/build/generateOutlookManifest.php`
- `db_update.php` (the normal CRM version updater)

### CRM version updates and Outlook setup

The shared Outlook update routine is `outlook-addin/update.php`. The normal CRM
`db_update.php` calls it before advancing `vtiger_version.tag_version`. On every
run it registers or repairs all six Outlook webservices and creates any missing
add-in link tables. A registration error stops the update before the CRM release
tag is advanced. Repeated runs keep the existing contact/calendar/mail links.

For a normal CRM version update, upload the updated files and run `db_update.php`
through the existing CRM update process. A separate Outlook registration command
is not needed. The CLI script below calls the same routine and remains available
for a targeted Outlook installation or repair.

All five Outlook setup/diagnostic tools now live in `outlook-addin/build/`:

- `registerOutlookAddinOperation.php`: initialize/repair webservices and link tables.
- `generateOutlookManifest.php`: generate the installation-specific manifest.
- `debugOutlookWebservice.php`: diagnose the CRM HTTP webservices.
- `checkVerteilerWebservice.php`: inspect local Verteiler module permissions.
- `checkOutlookAddinRegistration.ps1`: inspect the Exchange add-in registration.

The PHP tools resolve the CRM root from their own location. They can be launched
from any working directory by passing the absolute script path, or from the
`outlook-addin` directory as `php build/registerOutlookAddinOperation.php` and
`php build/generateOutlookManifest.php`. The examples below use the CRM root.
The PowerShell tool runs in an existing Exchange Online PowerShell session.

After uploading these tools, obsolete copies of these five scripts in the old
root-level `build/` directory can be removed. Other CRM build tools stay there.
The REST configuration remains `outlook-addin/rest/outlook.config.php`.

Generating a manifest is a separate, explicit step. This directory relocation
does not change the manifest contents or require reinstallation in Outlook.

### Migration from the former root-level `rest/` directory

The Outlook HTTP endpoint and its PHP helpers now live in `outlook-addin/rest/`.
Upload the updated add-in files together, including the Microsoft authentication
bundle and `outlook-addin/build/debugOutlookWebservice.php`.

**For an existing installation, move the private server configuration from
`rest/outlook.config.php` to `outlook-addin/rest/outlook.config.php`, preserving
its contents and file permissions.** Do not replace it with the example. If the
destination already contains a configuration, compare the files before moving
anything; do not overwrite it blindly.

The new endpoint is `/outlook-addin/rest/outlook.php`. After verifying login,
mail/calendar operations and the contacts view, remove the obsolete Outlook PHP
files from the old root-level directory. Remove that directory only when empty
and unused by other applications.

The configured CRM `webservice.php` URL and the Microsoft redirect URI
`/outlook-addin/contacts-auth.html` stay the same. This relocation needs no new
webservice registration or manifest installation. Reload existing task panes
after deploying the complete update.

### First installation

Create the local configuration from the example and set the URL of this CRM's
`webservice.php`. Do not add credentials to this file; users authenticate
through the add-in with their own CRM credentials.

On the CRM server, run:

```sh
cp outlook-addin/rest/outlook.config.example.php outlook-addin/rest/outlook.config.php
php outlook-addin/build/registerOutlookAddinOperation.php
php outlook-addin/build/generateOutlookManifest.php
```

On Windows, use:

```powershell
Copy-Item .\outlook-addin\rest\outlook.config.example.php .\outlook-addin\rest\outlook.config.php
php .\outlook-addin\build\registerOutlookAddinOperation.php
php .\outlook-addin\build\generateOutlookManifest.php
```

The normal berliCRM `db_update.php` process also registers the Outlook operations
`outlook_send_mail`, `outlook_get_verteiler_contacts`, and
`outlook_get_event_display`, plus `outlook_calendar_event` and
`outlook_contact_transfer`, plus `outlook_archive_email`. The mail operation is
separate from the existing template-based `send_mail_with_attachments` service.

When updating an existing installation, deploy `outlook-addin/rest/outlook.php`,
`include/Webservices/Custom/OutlookGetVerteilerContacts.php`, and the task pane
HTML/JavaScript.
Distribution-list search continues to use the standard `query` web service,
filtered by `assigned_user_id` using the CRM user ID returned by login. Sign in
again once in the updated task pane so stored sessions include this user ID.
No new web-service registration or manifest update is needed for those
distribution-list filter changes. The contacts manager requires the additional
registration and Microsoft configuration documented in [CONTACTS.en.md](CONTACTS.en.md).

The manifest generator reads the configured HTTPS `site_URL` and writes
`outlook-addin/manifest.generated.xml`. It does not modify the generic
`manifest.xml` template. Confirm that these resources are reachable without
certificate warnings:

- `/outlook-addin/taskpane.html`
- `/outlook-addin/manifest.generated.xml`
- `/outlook-addin/assets/icon-32.png`
- `/outlook-addin/assets/icon-80.png`

`outlook-addin/rest/outlook.php` accepts POST only; opening its URL directly should return
HTTP 405.

## Install for testing

1. Sign in to Outlook on the web with the test Microsoft 365 account.
2. Open **My Add-ins** → **Add a custom add-in** → **Add from file**.
3. Select `outlook-addin/manifest.generated.xml` from the CRM server. To
   update an existing custom add-in, remove the previous installation first
   and then add the newly generated manifest.
4. Open or compose an email and launch berliCRM from the **Apps** menu.
5. Sign in with the user's CRM username and Access Key.
   The add-in stores the CRM session token and username in this browser profile
   so reopening the task pane does not require another sign-in. The Access Key
   is never stored. Sign in again if the CRM session expires or browser data is
   cleared.
6. Search for a contact or Verteiler and add its contacts to the Outlook
   **An**, **Cc**, or **Bcc** field. Outlook retains control of the subject,
   message body, attachments, and sending.
7. In compose mode, open the **Nachricht** ribbon tab and select
   **berliCRM öffnen** in the berliCRM group. In supported Outlook on the web
   clients, use the pin icon in the task pane header to keep it open while
   moving between messages. Reading and composing are separate modes; pin the
   pane separately in each mode.

In classic Outlook for Windows, Microsoft caching can delay a manually
sideloaded add-in by up to 24 hours.

## Organization-wide deployment

In the Microsoft 365 Admin Center, open **Settings** → **Integrated apps**,
upload `manifest.generated.xml`, and assign it to a test group first. Expand
the assignment only after successful testing.

## Current scope

- Sign-in through the berliCRM web service.
- Search CRM contacts.
- Search berliCRM Verteiler and add their contact recipients to the active
  Outlook draft. Only lists assigned directly to the signed-in CRM user are
  offered, including for administrators. Lists assigned to groups or other users
  are excluded. The existing contact-expansion operation checks ownership against
  its authenticated CRM user and checks read permissions before returning
  contacts. The operation returns only readable contacts with a non-empty
  primary `email` field, trimmed of surrounding whitespace. Missing, null,
  hidden, and whitespace-only email values are excluded on the server; the
  task pane also checks for non-empty email addresses before adding recipients.
- List recent CRM events.
- In an Outlook single appointment, show CRM login status (login fields only
  when signed out) and a form for subject, start, end, location and description.
  Explicitly create the CRM event or update its existing link. The mail search
  sections are hidden in calendar context.
- Outlook handles composing and sending; the add-in does not send emails.

In read mode the compose controls are hidden. The **E-Mail im CRM ablegen**
action finds Contacts by the exact primary `email` matching the Outlook From
address. Users explicitly select one or more contacts before the message is
stored once in CRM Emails and linked to them. Attachments can be included.
See [MAIL-ARCHIVE.en.md](MAIL-ARCHIVE.en.md) for installation, permissions and limits.

### Calendar transfer in the existing add-in

The calendar feature uses manifest version **1.0.15.0**, keeping the existing
add-in ID. Both organizer and attendee appointment surfaces open the same task
pane. Appointments show only login status and the calendar form; mail continues
to show the existing mail sections. A restored CRM session is checked by the
calendar request; an expired session brings back the login fields.

Subject, start, end, location and plain-text body are read from Outlook and
prefilled. Start/end display in the authenticated CRM user's date format,
12/24-hour format and timezone. Fields are editable for the **CRM copy**; the
add-in never writes these edits back into Outlook. **Aus Outlook neu laden**
replaces the preview with the current Outlook values. If Outlook is changed
after the preview, saving asks the user to reload rather than copy stale values.

On **Im CRM ablegen**, an organizer appointment is first saved in Outlook to
obtain its item ID. This can persist a new appointment in the user's calendar;
it does not press Outlook's Send button. A saved attendee appointment can be
copied without saving it again. Only single appointments are supported: series
and occurrences are rejected. All-day appointments are copied as their start/end
time span; a separate all-day marker is not implemented. Invitations, reminders,
attachments and participant-to-CRM relations are not copied.

The authenticated custom operation `outlook_calendar_event` wraps standard
`vtws_create('Events', ...)`, `vtws_retrieve` and `vtws_revise`. It assigns new
events directly to the authenticated CRM user as private, planned meetings.
Standard CRM create/read/update permissions and field validation still apply.
Updates change only the transferred fields and duration, preserving other CRM
fields, status, ownership and relations. CRM workflows associated with ordinary
event saving can still run. CRM-specific extra mandatory fields may require
additional configuration before creating events.

The installer creates **only the add-in-owned table**
`vtiger_outlook_calendar_link`, keyed by authenticated CRM user and a hash of
Outlook mailbox + item ID. Reopening/retrying the same item updates the linked
CRM event. No changes to core PHP or existing profile rights are required.
A MySQL/MariaDB advisory lock serializes concurrent requests for that link.
Before creating an event, a durable `pending` reservation is stored. If creation
or storing its ID has an uncertain outcome, later attempts stop instead of
blindly creating duplicates. An administrator must inspect any pending link
against the CRM event before repairing/removing it; do not automatically remove
pending rows. Existing deleted or inaccessible linked events produce an error
instead of silently creating replacements.

The link is scoped to the CRM user and the Outlook item ID. Moving a calendar
item can change its Outlook ID; cross-calendar moves and shared/delegated mailbox
scenarios have not been verified. Changes and deletions are not synchronized in
the background. An explicit second click updates the CRM copy.

For an existing installation, the calendar update needs:

- `outlook-addin/calendar.js`
- `outlook-addin/taskpane.js`
- `outlook-addin/taskpane.html`
- `outlook-addin/manifest.xml`
- `outlook-addin/manifest.generated.xml` (or regenerate on the server)
- `outlook-addin/rest/outlook.php`
- `include/Webservices/Custom/OutlookCalendarEvent.php`
- `include/Webservices/Custom/OutlookGetEventDisplay.php`
- `modules/Settings/OutlookAddin/models/Module.php`

Keep the existing Outlook handlers and REST configuration in place. From the
CRM root, run:

```sh
php outlook-addin/build/registerOutlookAddinOperation.php
php outlook-addin/build/generateOutlookManifest.php
```

This registers the calendar operation and creates its link table. Then update
the installed add-in using the newly generated manifest (remove/re-add it for a
sideloaded installation, or update the central deployment), and reload Outlook.
A browser refresh alone does not add appointment command surfaces to the old
installed manifest. Open a single appointment, load the add-in, sign in, review
the preview and copy it to the CRM. Change it and repeat: the existing CRM event
should be updated. A live mailbox/CRM test remains necessary.

Local checks, without connecting to the CRM:

```sh
node --test outlook-addin/tests/calendar.test.cjs outlook-addin/tests/events.test.cjs outlook-addin/tests/recipients.test.cjs outlook-addin/tests/verteiler-counts.test.cjs
php outlook-addin/tests/calendar-server.php
php outlook-addin/tests/calendar-manifest.php
```

An anonymized copy of the manifest (neutral names/ID and public Microsoft sample
URLs) passed Microsoft's `office-addin-manifest` validation service. Only that
copy was submitted; production endpoint reachability and actual Outlook runtime
behavior are not covered by this result.

#### Calendar activation comparison manifest

The reported mailbox behavior is that berliCRM initially appears in message
compose after a clean sign-in, is missing in appointment compose, and is also
missing in message compose after switching back from the calendar. The cause
has not been established. Reinstalling version 1.0.15.0 has already been tried;
this symptom alone does not prove a stale installation or a browser setting.

`manifest.calendar-compatibility.xml` is a controlled comparison for that
installation, version **1.0.15.1**, displayed as **berliCRM Outlook (Kalendertest)**.
It retains the production app ID, URLs, permissions and all four mail/calendar
command surfaces, and omits the nested VersionOverridesV1_1 block. Only the
VersionOverridesV1_0 commands remain, without task-pane pinning. These surfaces
match Microsoft's documented extension points and its Script Lab manifest:

- https://learn.microsoft.com/en-us/office/dev/add-ins/reference/manifest/extensionpoint
- https://github.com/OfficeDev/script-lab/blob/main/manifests/sideload.outlook.xml

Replace the existing sideloaded registration with this comparison file for the
test; it uses the same app identity and is not an additional production add-in.
The file contains this installation's URLs and does not need a server upload.
It is not a portable template for other installations. Compare message compose,
appointment compose, then message compose again in one session without clearing
the cache between those steps. If it works, that narrows the difference to the
override/pinning configuration; it does not prove which element caused the
problem. If it still fails, compare Outlook's returned catalog manifests before
and after the calendar switch rather than assuming another manifest edit fixes
it. The normal template/generated manifest and the 1.0.15.0 ZIP are unchanged.

An anonymized copy of this comparison also passed Microsoft's manifest
validation service; actual activation in the affected mailbox remains untested.

The subsequently captured `acquisitions/get` response contains the comparison
app as acquired and not catalog-blocked, version 1.0.15.1, with both appointment
surfaces and the base appointment read/edit rules intact. Its XML matches the
local comparison file structurally. This rules out a stale or stripped manifest
in that response, but does not establish why the calendar UI omits the app.
The older **berliCRM Befehlstest** 1.0.14.6 remains installed under a different
app ID and has only mail commands; it must not be mistaken for the calendar
comparison app when checking menu visibility. With browser caching disabled,
mail menu visibility was reported to survive the calendar switch, while the
calendar menu still omitted berliCRM. The captured My Templates manifest has an
appointment organizer command with the same Mailbox 1.3 override requirement,
and no FunctionFile, so these properties alone are not evidence of a defect.

#### Calendar-only minimal manifest

`manifest.calendar-minimal.xml` is a separate diagnostic app named **berliCRM
Kalender Minimaltest**, with its own ID `c20becc2-18ca-45a3-b0bc-9ebfd4e7c648`
and initial version **1.0.0.0**. It has one appointment-compose activation rule,
one AppointmentOrganizerCommandSurface button and a single VersionOverridesV1_0
block. It requests only Restricted permission. There are no mail commands,
attendee/read commands, function commands, pinning, authentication or CRM calls.

Install this XML as an additional custom add-in from file, then open a new
appointment in the user's own calendar and check its Apps menu for **berliCRM
Kalender Minimaltest** / **Kalender Minimaltest**. Clicking its button opens the
existing `outlook-addin/diagnostic-taskpane.html`, which displays the page and
Office.js initialization status without reading or changing item data. That
HTML file and the existing icons must be available on the CRM server. No backend
operation registration or database update is needed. The manifest contains this
installation's HTTPS URLs and is not processed by generateOutlookManifest.php.

Local XML checks confirmed calendar compose only, exactly one command, resolved
resource references, the single override and Restricted permission. An
anonymized copy passed Microsoft's validation service. Visibility and launch in
the affected live calendar still require the user's test.

The user subsequently reported that this calendar-only minimal app and the
comparison app both appeared on the first opening of the calendar Apps menu,
then disappeared from that menu on later openings. The user explicitly
confirmed that the menu entries disappear, rather than remaining visible with
a task-pane launch failure. A separate appointment window had already been
tested without resolving the visibility problem. This confirms initial calendar
recognition but does not establish the cause of the later disappearance. The
catalog XML alone cannot show whether the effective ExtensionPointCollection or
Enabled state differs when Outlook prepares its activation data.

Microsoft documentation used for the integration:

- https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/get-or-set-the-time-of-an-appointment
- https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/get-and-set-recurrence
- https://learn.microsoft.com/en-us/javascript/api/requirement-sets/outlook/outlook-item-object-model

### Event labels and personal date format

The event list uses the authenticated CRM user's field labels from the standard
`describe` metadata and their `date_format` preference. It does not use the
browser's locale or print technical field names such as `date_start` and
`due_date`. Date-only values are rearranged as text without timezone conversion;
subjects remain unchanged. Missing/zero dates are omitted. The small read-only
`outlook_get_event_display` operation exposes only the relevant event field
labels and this user's date format; it accepts no user ID, and standard event
metadata access checks apply. The events themselves still use standard `query`.
If metadata cannot be read, the task pane displays the error instead of silently
using another date format.

For this update, deploy the task pane HTML/JavaScript, `outlook-addin/rest/outlook.php`,
`include/Webservices/Custom/OutlookGetEventDisplay.php`, and
`modules/Settings/OutlookAddin/models/Module.php`. Then run the existing installer:

```sh
php outlook-addin/build/registerOutlookAddinOperation.php
```

This registers the new read-only operation alongside the existing Outlook
operations without changing CRM core code or profile permissions. No manifest
update is needed. Personal date format and labels are read again whenever events
are loaded, including with a restored CRM session.

### Email contact counts in distribution-list search

Each distribution-list name shows the number of readable contact records with
a non-empty primary email address, for example `Testverteiler (42 Kontakte mit
E-Mail)`. Counts load after the search results, using at most two concurrent
requests. The REST action `countVerteilerContacts` calls the existing
`outlook_get_verteiler_contacts` operation and applies the same email filter as
contact expansion. Ownership and contact read permissions therefore also apply
to counts. This needs no new CRM operation registration.

Missing, null and whitespace-only email values are excluded. Separate contact
records sharing an email address each count once; recipient insertion still
deduplicates their email addresses. A count failure displays `Anzahl nicht
verfügbar` instead of zero; its error detail is available on hover. Counts are
a snapshot: search again to refresh after changing the list in the CRM. Opening
a list for insertion reads its contacts again so changed permissions and list
membership are respected.

Deploy `outlook-addin/rest/outlook.php`, `outlook-addin/taskpane.js`, and
`outlook-addin/taskpane.html` together. Test the count display with:

```sh
node --test outlook-addin/tests/verteiler-counts.test.cjs
```

### Recipient batches and mailbox limit

Recipient transfers use consecutive batches of at most 100 addresses. Before
adding any addresses, the add-in reads the existing recipients in **An**, **Cc**,
and **Bcc** and checks their combined count plus the new recipients against the
configured limit. If that would exceed the limit, it displays an error without
adding a partial list. A list of 500 usable addresses fits an otherwise empty
draft with the default limit of 500. Addresses without email are skipped;
duplicates within the incoming list and addresses already in the target field
are skipped. Existing entries across all three fields are counted as returned
by Outlook. Recipient totals are checked again between batches, and overlapping
add-in transfers are rejected while a transfer is running.

Set `'recipient_limit' => 500` in `outlook-addin/rest/outlook.config.php` to the effective
Exchange recipient limit. Existing configurations without this key default to
500. This setting changes only the add-in check; it does not change Exchange
settings. Office.js does not expose the configured Exchange mailbox limit.
For installations with different limits across mailboxes, use the lowest
applicable limit; per-mailbox configuration is not implemented.

Deploy `outlook-addin/rest/outlook.php`, `outlook-addin/taskpane.js`, and
`outlook-addin/taskpane.html` together for this update. The settings endpoint is
part of the REST bridge and needs no custom CRM web-service registration.

If a later batch fails, the add-in stops, reports the number of recipients
confirmed as added, and keeps the existing recipients. Check the draft before
retrying; a retry skips addresses already present in the selected target field.
Outlook's `getAsync` only returns resolved recipients, so unresolved manually
entered addresses and Exchange distribution-group expansion cannot be fully
counted by this client-side check. Exchange still enforces its actual sending
limits; the add-in does not intercept sending.

Microsoft API documentation:
https://learn.microsoft.com/en-us/javascript/api/outlook/office.recipients?view=outlook-js-preview

Run the isolated recipient-transfer tests with Node.js:

```sh
node --test outlook-addin/tests/recipients.test.cjs
```

## Troubleshooting

- **Invalid manifest:** Generate it again and check that all HTTPS URLs are
  reachable and the server certificate is trusted.
- **Blank pane or command does nothing:** Ensure the installed manifest is the
  current generated manifest and that `taskpane.html` is reachable. Task pane
  buttons use the manifest's `ShowTaskpane` action directly and do not require
  a function-command page.
- **Empty task pane:** Check browser/Outlook developer tools for HTTP or
  certificate errors. Ensure the web server's CSP and `X-Frame-Options` allow
  Office to frame the task pane.
- **Task pane closes when switching messages:** Outlook may close the pane
  when the selected message or mode changes. Reopen it from the add-in command
  for the current message.
- **Sign-in fails:** Check the CRM username, Access Key, configured web-service
  URL and availability of `/webservice.php`.
- **Unknown Outlook web-service operation:** Run
  `php outlook-addin/build/registerOutlookAddinOperation.php` or rerun `db_update.php`.
- **Distribution-list search returns HTTP 403 for `query`:** The standard web
  service requires `Verteiler` to be included in `listtypes` for this CRM user.
  Check the server's `vtiger_ws_entity` registration for `Verteiler`, module
  activation, and the user's effective profile/cache permissions. Visibility in
  the CRM interface alone does not prove availability through `listtypes`.
  Filtering by `assigned_user_id` limits results but does not change module
  permissions. The Outlook add-in does not modify these core checks.

### Diagnose the standard web service from a terminal

Deploy `outlook-addin/build/debugOutlookWebservice.php` and run it from the CRM root:

```sh
php outlook-addin/build/debugOutlookWebservice.php --username CRM-USER --search test
```

The script reads the endpoint from `outlook-addin/rest/outlook.config.php`. Alternatively,
specify `--url https://crm.example.org/webservice.php`. The PHP CLI needs cURL.
It prompts for the Access Key with hidden input; for non-interactive use it
also accepts the `BERLICRM_DEBUG_ACCESS_KEY` environment variable.

The report includes HTTP status and CRM error code/message for login,
`listtypes`, `describe`, a Contacts control query, an unfiltered Verteiler
query, the current user's `SELECT *` query, and the exact add-in search query.
It reports record counts without printing returned record contents. Access
Keys, challenge values, login hashes, and session tokens are masked. The
separate diagnostic session is logged out afterwards. Exit code 0 means all
diagnostic queries succeeded; 2 means a service check failed; 1 means the
tool could not start. The script changes no CRM records, permissions, or
registrations and is unavailable over HTTP.

If `Verteiler` is missing from `listtypes`, deploy the additional local check and
run it on the CRM server with the numeric user ID from login (`19x3` means `3`):

```sh
php outlook-addin/build/checkVerteilerWebservice.php --user-id 3
```

This reads the module row, its `vtiger_ws_entity` registration, and only the
relevant effective module/profile cache flags, including their PHP types. It
also checks `getPermittedModuleNames()` for the same CRM user. It performs no
repairs, does not register anything, and does not print credentials.

### Compare a function command with the failing task pane

`manifest.diagnostic-command-check.xml` (version `1.0.14.6`) adds **Befehl
testen** to the read and compose commands of the minimal diagnostic add-in.
It follows the function-command pattern in Microsoft's
[TaskPane-JS example](https://github.com/OfficeDev/Office-Addin-TaskPane-JS).
The command displays an Outlook information bar with a UTC timestamp and calls
`event.completed()` after the notification callback, including failure paths.
It uses no CRM login, storage, or network requests beyond loading Office.js and
the command page. It does not read or modify message content.

1. Deploy `diagnostic-commands.html` and `diagnostic-commands.js` beside the
   already deployed `diagnostic-taskpane.html` under `outlook-addin/`.
2. Remove the existing **berliCRM Registrierungstest** diagnostic installation
   and install `manifest.diagnostic-command-check.xml`. It reuses that test
   add-in's ID and appears as **berliCRM Befehlstest**.
3. Open a received message and select **Befehl testen** from the test add-in's
   commands. Confirm the message **berliCRM: Befehl ausgeführt (...)** appears.
4. Open the minimal pane with **berliCRM öffnen**, close it, try reopening it,
   then select **Befehl testen** again. Note whether the timestamp updates.
5. Create a draft and try both commands there. Record the result separately
   for **Befehl testen** and **berliCRM öffnen**, without reloading Outlook
   between steps 3 through 5.

A successful notification after the pane fails shows that a function command
still activates at that point; investigate the task-pane path separately.
No notification is inconclusive: command activation, loading its page, or the
notification API can fail. If needed, filter browser network requests for
`diagnostic-commands` and look for `berliCRM Befehlstest` in the console. The
handler logs when it starts and when the notification succeeds or fails.
Browser caching or runtime reuse means no new network request alone does not
prove that the command was never invoked.

This manifest has local structural checks only. It has not been submitted to
Microsoft's validator or tested in a live Outlook mailbox.

### Task pane opens only once

`manifest.diagnostic-fresh-registration.xml` is an installation-specific
diagnostic manifest for the separate **berliCRM Registrierungstest** add-in.
Version `1.0.14.5` opens `diagnostic-taskpane.html` instead of the CRM task pane.
Deploy `diagnostic-taskpane.html` to the same `outlook-addin/` directory on the
server before installing this manifest. The production manifest is separate.

The minimal page loads Office.js and displays its initialization status. It
does not load `taskpane.js`, use browser storage, call the CRM, or access mail
content. The existing command page remains the empty Office.js initializer.

Open the diagnostic add-in, note its Office initialization status, close the
pane with its X button, and reopen it on the same message without reloading
Outlook. Then try it in a new draft. If this page also opens only once, the CRM
task-pane JavaScript is not required to reproduce the problem; investigate
Outlook activation, registration and the client environment. If it reopens
normally, compare the CRM page's initialization with this minimal page. A
successful XML/schema validation alone does not verify repeated activation.

The reported test result is that both the CRM page and this minimal page open
only once, whether first opened in read or compose mode. The diagnostic app
was also reported missing from the message Apps menu after reloading Outlook,
while still listed as installed. This reproduces the failure without the CRM
task-pane code, but does not by itself establish a Microsoft service defect
or rule out manifest activation, account settings or organization policy.

All diagnostic variants above still use the custom manifest structure. For an
independent comparison, `diagnostics/microsoft-reference/manifest.xml` is an
unmodified copy of Microsoft's Outlook Hello World XML manifest, distributed
with its MIT license. Its task pane and icons are hosted by OfficeDev GitHub
Pages, so no CRM server deployment is needed. The sample source is:

https://github.com/OfficeDev/Office-Add-in-samples/tree/main/Samples/hello-world/outlook-hello-world

Install this reference manifest with the same user account. It appears as
**Hello world** in message compose mode. Open its task pane in an empty draft,
close the pane and reopen it without reloading Outlook, then check it again
after a browser reload. Only opening and closing the pane is needed; the
sample's **Say hello** button writes to the draft body. If the reference
reopens normally, continue comparing the custom manifest and hosting. If it
fails too, the symptom also occurs independently of the berliCRM manifest and
server. The reference is a diagnostic control, not a replacement for the
berliCRM add-in.

In the reported comparison, the independent Hello World sample did not open,
while the existing Viva Insights task pane continued working normally. This
does not show a general inability to display task panes or prove a conflict
with Viva Insights. Check the distinction between sideloaded add-ins and
Microsoft/admin-deployed add-ins. The subsequent privacy-setting finding is
documented in [IMPORTANT: Optional connected experiences](#important-optional-connected-experiences).

As an optional account-level diagnostic, an Exchange administrator can run the
read-only registration checks in an
already connected Exchange Online PowerShell session:

```powershell
.\outlook-addin\build\checkOutlookAddinRegistration.ps1 -MailboxIdentity 'user@example.org' |
    Format-List
```

The script reads the organization-wide `AppsForOfficeEnabled` value, the
mailbox registration and enabled state of the two berliCRM add-in IDs, and
the mailbox's assigned user roles. It does not change settings and is not a
prerequisite for normal installation or use of the add-in. Also check
whether optional connected experiences are permitted and enabled for the
affected user. Microsoft documents that disabling them prevents sideloaded
add-ins from being used in Outlook on the web, even if installation is
possible. In the reported case this setting was subsequently found to be off;
enabling it reportedly improved Edge behavior. Retesting Firefox is pending.
The script does not query that privacy setting or the Microsoft 365 service
health dashboard. `Get-App -Mailbox` does not enumerate add-ins deployed from
Microsoft 365 Integrated Apps.

Microsoft documentation:

- https://learn.microsoft.com/office/dev/add-ins/outlook/sideload-outlook-add-ins-for-testing
- https://learn.microsoft.com/microsoft-365/admin/manage/manage-deployment-of-add-ins
- https://learn.microsoft.com/office/dev/add-ins/testing/troubleshoot-manifest
- https://learn.microsoft.com/office/dev/add-ins/concepts/privacy-and-security#optional-connected-experiences-in-outlook
- https://learn.microsoft.com/exchange/clients-and-mobile-in-exchange-online/add-ins-for-outlook/specify-who-can-install-and-manage-add-ins
- https://learn.microsoft.com/powershell/module/exchangepowershell/get-app

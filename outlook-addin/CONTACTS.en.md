# Transfer CRM and Outlook contacts

## Languages in Word 1.4.0.0 / Outlook 1.1.0.0

After sign-in, both add-ins use the CRM user language (`de_de` or `en_us`). Before sign-in, the Office/browser language is used. Translations are maintained in keyed PHP arrays. This update requires registration of `office_addin_user_preferences`. See the [language guide](../wordplugin-officejs/LANGUAGES.en.md) for deployment and maintenance.

[Deutsch](CONTACTS.md) | **English**

In the berliCRM add-in, **CRM- und Outlook-Kontakte verwalten** (Manage CRM and
Outlook contacts) opens the contacts view. This link is also available in the
email read task pane, including when selecting an email in the message list.
The view has its own CRM sign-in; an existing CRM session is checked and reused.
The Access Key is not stored. An expired session returns the user to sign-in.
The instructions below use the current German add-in labels where needed to
identify controls; their meanings are explained in English.

> **IMPORTANT when opened from a manually installed Outlook add-in:**
> **Optional connected experiences must be enabled** in Outlook. This also
> applies when using Firefox. See the [installation documentation](README.en.md#important-optional-connected-experiences).

The view is also available directly at `/outlook-addin/contacts.html`.
Outlook web add-ins activate on messages and appointments. This add-in cannot
access the selection in Outlook's native Contacts menu, so contacts are selected
using checkboxes in this separate view.

## Update an existing installation

**“Unknown action: getUserPreferences”** indicates that the browser UI and PHP
gateway versions do not match. Deploy the full Outlook language update,
including `outlook-addin/rest/`, `include/utils/OfficeAddinI18n.php`, language
catalogs and the updated Settings model. Then run
`php outlook-addin/build/registerOutlookAddinOperation.php` from the CRM root,
or use the Outlook add-in Settings service setup/repair button. Replacing
browser files alone does not resolve this mismatch.

The contacts view validates a saved CRM session with the login form hidden.
After a technical failure, **Try again** repeats validation with the same session.
An expired session restores the login form. The saved language is used as a hint
until the authenticated preference response arrives. This update does not require
reinstalling the Outlook manifest.

1. Extract the contacts update package into the CRM root directory. Keep the
   existing `outlook-addin/rest/outlook.config.php` and add the configuration
   block shown below. The examples contain no production credentials.
   In older installations the file is still at `rest/outlook.config.php`:
   move it to `outlook-addin/rest/outlook.config.php` without changing its contents
   or file permissions. Compare any configuration already at the destination
   before replacing it. Upload all updated add-in files together, including the
   Microsoft sign-in bundle. Moving the files alone requires no registration;
   the setup steps below are for installations without contact transfer yet.
2. During a normal CRM version update, `db_update.php` initializes the webservices
   automatically through `outlook-addin/update.php`; separate registration is
   unnecessary. For a targeted setup or repair, run this on the CRM server from
   the CRM root directory:

   ```sh
   php outlook-addin/build/registerOutlookAddinOperation.php
   ```

   This also registers `outlook_contact_transfer` and creates the add-in's own
   `vtiger_outlook_contact_link` mapping table. The other Outlook operations
   remain registered. No core files or existing user/profile permissions need
   to be changed.
3. Configure the Microsoft connection as described below.
4. Reload the add-in page and open **CRM- und Outlook-Kontakte verwalten**.
   Reinstalling the manifest is not required for this link.

## One-time Microsoft Entra setup

### Setup through the CRM Settings menu

After uploading the update, run `db_update.php` through the normal CRM update
process, or run `php outlook-addin/build/registerOutlookAddinOperation.php` for
a targeted setup. CRM administrators can then open **Settings → Integration →
Outlook Add-in**. Direct route:
`index.php?module=OutlookAddin&parent=Settings&view=Index`.

The page includes the Entra steps below, shows the exact SPA redirect URI for
this installation, and requires **Application (client) ID / client_id** and
**Directory (tenant) ID / tenant_id**. Both values must be valid GUIDs; empty or
invalid values are also rejected on the server. IDs already present in the
server configuration are displayed. Otherwise the fields stay empty, with
GUID-format placeholders only. Example IDs are never inserted as actual values.
No client secret, CRM username or CRM Access Key is needed in this configuration.

**Download outlook.config.php** generates a configuration with these IDs, the
matching CRM and redirect URLs, a 500-recipient limit and a 30-second timeout.
Upload this file to `outlook-addin/rest/outlook.config.php` on the server.
The Settings page does not overwrite an existing server configuration; compare
custom settings before replacing it. The other download generates the
installation-specific XML manifest without modifying its template.

The Settings page also checks files and webservices and offers registration
repair. The normal CRM updater installs the menu and webservices as well.
The following sections describe the Entra portal steps and the alternative
manual configuration.

### Test the Microsoft connection from Settings

**Test Microsoft connection** uses the configuration already saved on the server
at `outlook-addin/rest/outlook.config.php`. Changes to the form fields must first
be downloaded as configuration and uploaded to the server. The test button is
disabled unless valid IDs are already saved there.

Clicking the button opens the existing Microsoft sign-in flow. After successful
sign-in, the Settings page makes exactly one read-only Graph request to
`/v1.0/me/contacts?$top=1&$select=id`. An empty personal address book also counts
as success. No contacts are created or modified, and no contact IDs or access
tokens are displayed in the result. The token stays in the browser only during
the request and is not sent to the CRM.

The success message confirms **Microsoft sign-in and read access to contacts**.
Write access is not tested by creating a sample contact. Missing consent, a
blocked sign-in window or denied Graph access is reported as an error or
cancellation. The test can then be started again; **Cancel test** ends a running
attempt. This does not test the separate CRM user sign-in.

For this update, upload `outlook-addin/settings-connection.js`, the Outlook
Settings template, module model and both Settings language files together.
Existing IDs remain in the configuration file. If the Outlook Settings menu
already exists, another `db_update.php` run is unnecessary.

### App registration and Microsoft IDs

The Entra app registration is currently required to access personal Outlook
contacts through Microsoft Graph. Email archiving and calendar transfer use
the existing CRM sign-in and do not require this Graph connection.

This feature requires an Exchange Online mailbox. On-premises Exchange mailboxes
without Microsoft Graph contact access are not supported.

An existing suitable registration can be reused. Otherwise, open the
[Microsoft Entra admin center](https://entra.microsoft.com) and go to
**Entra ID → App registrations → New registration**:

1. Use a name such as `berliCRM Outlook`, choose **Accounts in this organizational
   directory only**, then select **Register**.
2. Under **Authentication → Add a platform → Single-page application (SPA)**,
   register the exact HTTPS address:
   `https://YOUR-CRM-HOST/outlook-addin/contacts-auth.html`.
   For an installation at `unsercrm.i1.crm-now.de`, this is
   `https://unsercrm.i1.crm-now.de/outlook-addin/contacts-auth.html`.
   Include any CRM installation subdirectory. Do not register this as the
   **Web** platform. Implicit token flows are not required. The platform and
   exact redirect URI must match; see
   [Microsoft's redirect URI instructions](https://learn.microsoft.com/en-us/entra/identity-platform/how-to-add-redirect-uri).
3. Under **API permissions → Add a permission → Microsoft Graph → Delegated
   permissions**, add `Contacts.ReadWrite`. Depending on tenant policy, an
   administrator must grant consent. The application uses no application
   permissions and no client secret. This delegated permission allows reading
   and creating personal Outlook contacts; see
   [Microsoft's contact creation documentation](https://learn.microsoft.com/en-us/graph/api/user-post-contacts?view=graph-rest-1.0).
4. Under **Overview**, copy the **Application (client) ID** and **Directory
   (tenant) ID**. These IDs are needed for the following configuration and can
   be given to the person maintaining the server configuration.
5. Add the following block to the existing
   `outlook-addin/rest/outlook.config.php`:

   Place it **inside the existing `return array(...)`**, before its closing
   `);`. Older installations initially lack this block: updates do not
   automatically overwrite the local `outlook.config.php`. Keep other settings,
   such as the CRM URL and timeout. The add-in does not need `api_username` or
   `api_access_key`, so these can be omitted. Users sign in to the CRM within
   the add-in; `client_id` and `tenant_id` are public Microsoft app registration
   IDs, not CRM credentials.

   ```php
   'contacts_microsoft' => array(
       'client_id' => 'APPLICATION-ID-AS-GUID',
       'tenant_id' => 'DIRECTORY-ID-AS-GUID',
       'redirect_uri' => 'https://YOUR-CRM-HOST/outlook-addin/contacts-auth.html'
   ),
   ```

   Both IDs must be real GUIDs. An empty `redirect_uri` is derived from the
   configured `crm_webservice_url`. If internal and public CRM addresses differ,
   explicitly set the public address.

The dialog starts on the same website as the add-in. MSAL 5.25.1 uses the
authorization code flow with PKCE and handles the redirect directly with
`handleRedirectPromise`; no MSAL popup is used within the Office iframe.
The add-in uses the Office Dialog API inside Outlook, and a browser window
outside Outlook. If the server sets a CSP, it must allow Microsoft sign-in,
Microsoft Graph and the existing Office.js. The `contacts-auth.html` redirect
page must not receive a `Cross-Origin-Opener-Policy` header, so the MSAL 5 redirect
bridge retains its communication channel. This includes headers added by a
reverse proxy; see [Microsoft's redirect bridge guidance](https://github.com/AzureAD/microsoft-authentication-library-for-js/blob/msal-browser-v5.25.1/lib/msal-browser/docs/redirect-bridge.md).

From **20261108.7**, the Microsoft access token also remains in the current browser
tab's `sessionStorage`, so closing and reopening the contacts view reuses a valid
connection. Reuse is bound to the same validated CRM session, CRM user and Entra
configuration. Expired tokens and tokens rejected by Graph are discarded.
**Disconnect** and CRM logout also clear this session cache. No refresh tokens
are stored, and no tokens are persistently saved in `localStorage` or CRM.
Only the last successful Microsoft username is kept in `localStorage` as an
optional account hint. The temporary MSAL cache in the sign-in window is cleared
before returning the result; the silent attempt still uses an in-memory MSAL
cache. There is no automatic background token renewal. Client ID, tenant ID and
redirect URI are public configuration; CRM credentials are not exposed by it.

## Usage

### Automatic Microsoft connection

After CRM sign-in or validation of a saved CRM session, the contacts view
automatically tries to reuse an existing Microsoft sign-in for
`Contacts.ReadWrite`. Initially, no sign-in window opens. During this check, the manual
sign-in menu remains hidden and the status is **Microsoft-Verbindung wird
automatisch hergestellt …** (Connecting to Microsoft automatically …).

On success, the view shows **Microsoft-Konto verbunden: …** (Microsoft account
connected). The **Mit Microsoft verbinden** (Connect to Microsoft) button and
sign-in explanation stay hidden; contact folder controls and transfer functions
become available. The displayed account determines whose personal Outlook
address book is used. **Verbindung trennen** (Disconnect) brings back the menu
so a different account can be selected if needed.

From **build 20261111.3**, clicking **Manage CRM and Outlook contacts** in the
signed-in email task pane reserves the Microsoft sign-in window during that
user action. The contacts page reuses it after navigation, so this launch path
does not require a popup exception. A valid cached connection skips preparation;
a window is closed if silent sign-in succeeds without needing it. The Outlook
platform is passed across navigation so delayed `Office.onReady()` cannot skip
the fallback.

`login_required` or `AADSTS50058` in Outlook on the web
triggers one attempt through the existing Microsoft sign-in popup with `prompt=none`.
Microsoft can use its top-level session cookies even when they are unavailable in
the hidden iframe. Opening the contacts view directly without the original click
may still cause the browser to block a newly requested automatic popup. If the
browser blocks the popup or no usable Microsoft session exists there, **Connect to
Microsoft** remains available. There is no automatic retry loop.

If `prompt=none` also fails in the sign-in window, one normal Microsoft sign-in
attempt follows in that same window without forced account selection. Microsoft
decides whether the existing session suffices or user input/consent is needed.
A further failure ends the attempt. Errors from this window are explicitly
labelled **Microsoft sign-in window** in the technical details.

If the automatic connection fails or takes too long, the manual sign-in menu
appears. **Connect to Microsoft** opens the Office dialog or browser window and
allows Microsoft to reuse its session without forcing account selection.
**Disconnect** explicitly enables account selection for the next connection.
Manual sign-in may be necessary when consent is missing, several
Microsoft accounts are available, or cookies are blocked. The automatic attempt
alone does not grant permissions. See
[Microsoft's SSO guidance](https://learn.microsoft.com/en-us/entra/msal/javascript/browser/single-sign-on)
and [sign-in in embedded applications](https://learn.microsoft.com/en-us/entra/msal/javascript/browser/iframe-usage).
Microsoft documents the popup workaround under
[interactive requests with prompt=none](https://learn.microsoft.com/en-us/entra/msal/javascript/browser/prompt-behavior#interactive-requests-with-promptnone).

The existing Entra app registration and SPA redirect URI
`https://YOUR-CRM-HOST/outlook-addin/contacts-auth.html` are reused. No additional
Entra entries are required by this update; NAA is not used. The installed manifest
and service registration remain. After CRM validation, a valid token from the
current tab is reused first. Otherwise, MSAL tries silent iframe SSO without
waiting for `Office.onReady()`. The preferred account hint is the last successfully
connected Microsoft username, falling back to an available Outlook mailbox address.
Blocked third-party cookies, missing consent or an expired Microsoft session may
still require manual sign-in.

**Technical details** shows the actual failure and possible cookie/sign-in
limitations. When loaded in MSAL's
hidden SSO iframe, the redirect page does not start its own
sign-in. It uses the bundled MSAL 5 redirect bridge to deliver the response.
An interactive sign-in callback framed by Outlook is processed normally;
having a parent window alone does not identify a silent sign-in.

From **build 20261111.4**, technical error information appears in a collapsed
**Technical details** section. A click reveals the full Microsoft message,
including error codes and subcodes. A new error collapses the section again;
the general error message remains visible. The build is shown at the bottom of
the contacts view. The confirmed Firefox and Edge sign-in flow is unchanged.

**Upload these update files together:**

- `outlook-addin/contacts.html`
- `outlook-addin/contacts.js`
- `outlook-addin/contacts-auth.html`
- `outlook-addin/assets/contacts-auth.bundle.js` and its `.LEGAL.txt` file
- `outlook-addin/assets/contacts-sso.bundle.js` and its `.LEGAL.txt` file

The new SSO bundle is required. Keep the existing
`outlook-addin/rest/outlook.config.php`. Close and reopen the contacts view after
uploading. The updated scripts use cache version `20261102.10`. If old messages
or behavior remain, clear the browser cache for the CRM add-in page.

For developers, `npm run build:auth` in `outlook-addin/` generates both Microsoft
sign-in bundles from `contacts-auth-entry.js` and `contacts-sso-entry.js`.
The MSAL license files remain part of the distribution.

### Microsoft sign-in and error messages

For **Please start Microsoft sign-in from the berliCRM contacts view**, the
browser may have severed `window.opener` after the Microsoft redirect. Cache
version **20261108.5** returns the response independently of that connection:
the browser encrypts it with AES-GCM; the server temporarily holds only ciphertext
in a private PHP temp directory outside the webroot. Only the initiating,
authenticated CRM session can collect it; the decryption key stays in the browser.
The handover expires after five minutes and can be cancelled with **Cancel
Microsoft sign-in**. A popup reported as closed because of COOP no longer
prematurely ends the handover.
Expired replies cannot be collected. Orphaned encrypted files are cleaned on
subsequent requests; completion and cancellation remove them directly.

Deploy `outlook-addin/rest/outlook.php`, `outlook-addin/rest/OutlookAuthRelay.php`,
`contacts-auth-relay.js`, both language catalogs and generated `language.js`
together with the HTML/authentication scripts. No new CRM operation or database
table is required when the language update is registered already. PHP needs a
writable private temp directory; the private configuration can optionally set
`contacts_auth_relay_directory` to another private location outside the webroot.

If the page remains on **Anmeldung wird vorbereitet** (Preparing sign-in) after
successful Microsoft sign-in, update `outlook-addin/contacts-auth.html`,
`outlook-addin/assets/contacts-auth.bundle.js` and its `.LEGAL.txt` file together.
The authentication bundle now uses cache version `20261108.4`. This fix distinguishes the interactive callback
from MSAL's hidden SSO iframe. Administrator consent does not replace callback
processing.

In **Outlook on the web**, manual Microsoft sign-in uses the existing popup
channel. The Microsoft sign-in window opens directly when **Connect to Microsoft**
is clicked. It does not load Office.js or wait for `Office.onReady()`. The contacts
manager remains in the existing add-in. Classic Outlook continues to use the
Office dialog and its Office initialization. The silent SSO callback does not
require Office.js either.

For **Verbindung zum Outlook-Anmeldefenster wird hergestellt … Zeitüberschreitung**
(Outlook dialog connection timeout), update the files listed above together to
cache version `20261108.4`. This bypasses the stalled Office dialog handshake in
Outlook on the web. Allow the Microsoft sign-in popup if the browser blocks it.
Responses are accepted only from the expected window and exact CRM origin;
Microsoft tokens stay in the contacts view and its tab session. No new administrator
consent is required for this code update.

The page now shows each stage: loading configuration, initializing Microsoft,
processing the response and passing it to Outlook. Preparation and Outlook
handoff each have a 20-second timeout; Microsoft response processing and
redirection each have a 60-second timeout. A timeout displays an error with the
affected stage under **Technische Details** (Technical details); restart the
connection afterwards. These limits apply to our local page, not the time spent
entering credentials on Microsoft's sign-in page. The manifest, IDs, redirect
URI and webservice registration remain unchanged.

The sign-in window displays berliCRM status messages for preparation, successful
connection and errors. An `access_denied` result shows an explanation; the
original message is available under **Technische Details** (Technical details).
**Erneut versuchen** (Try again) starts a new sign-in attempt. Alternatively,
close the window and select **Mit Microsoft verbinden** in the contacts view
again. A denial can result from cancellation or missing consent; the page design
does not change Microsoft permissions.

The preceding **“… wants to display a new window”** prompt with **Allow/Ignore**
comes from Outlook itself and cannot be styled by the add-in. It remains enabled
so the Office dialog can open reliably with popup blockers. See
[Microsoft's dialog guidance](https://learn.microsoft.com/en-us/office/dev/add-ins/develop/dialog-best-practices#handle-pop-up-blockers-with-office-on-the-web).

For the new layout, upload `outlook-addin/contacts-auth.html`,
`outlook-addin/contacts-auth.css` and `outlook-addin/assets/contacts-auth.bundle.js`
together. The manifest, Entra configuration and webservice registration stay
unchanged.

### Select and transfer contacts

1. Sign in to the CRM and wait for the automatic Microsoft connection. Only if
   it fails, select **Mit Microsoft verbinden** in the menu that appears.
   The displayed Microsoft account determines the personal Outlook address book.
2. Initially, the default contacts folder is both source and destination.
   **Weitere Ordner laden** (Load more folders) also offers the user's other
   contact folders, including subfolders.
3. **CRM → Outlook:** Search by name/email, select results, and click
   **Ausgewählte als Outlook-Kontakte anlegen** (Create selected Outlook contacts).
   Each search shows at most 50 CRM results; narrow the search if necessary.
4. **Outlook → CRM:** Select **Outlook-Kontakte laden** (Load Outlook contacts),
   then **Weitere Kontakte laden** (Load more contacts) if needed. Select complete
   contacts and click **Ausgewählte ins CRM übernehmen** (Import selected into
   CRM). Loading additional pages preserves the selection. “All complete
   contacts” refers to the pages already loaded.

The view loads up to 100 Outlook contacts per page and processes selected
contacts individually. Each contact shows success, skipped or error status.
There is no automatic synchronization, deletion or overwriting of existing
contacts. Switching folders or accounts clears the current selection.
The organization directory/GAL, distribution groups and shared mailboxes are
outside the scope of this contacts view.

## Mandatory fields and field mapping

The add-in handler uses the standard `vtws_describe`, `vtws_query`,
`vtws_retrieve` and `vtws_create` operations for CRM data. It adds server-side
field mapping and links to prevent duplicate transfers.

The standard `describe('Contacts')` operation provides the signed-in CRM user's
visible fields, write permissions, translated labels and mandatory fields.
Incomplete Outlook contacts are disabled in the selection; the server checks
again immediately before each CRM creation. Additional mandatory fields are
also respected. An unmappable mandatory field causes the contact to be skipped
with its field label shown. Last names are not invented from display names.
The assigned owner is always the signed-in CRM user.

| CRM field | Outlook contact field |
| --- | --- |
| `firstname`, `lastname` | `givenName`, `surname` |
| `email`, `secondaryemail` | First and second email address |
| `phone`, `mobile` | First business phone number, mobile phone |
| `title`, `department` | Job title, department |
| `description` | Personal notes |
| `mailingstreet/city/state/zip/country` | Business address |
| `otherstreet/city/state/zip/country` | Home address |

Additional phone numbers/email addresses, birthdays, photos, categories and
custom fields are not transferred. A company name is not automatically converted
into a CRM account reference. For example, if the account reference is mandatory,
the contact is skipped because the reference is missing. Invalid email addresses
and overlong field values are reported as errors. Whether an email address is
mandatory is determined by the CRM field configuration.

## Retries and errors

Version **1.0.15.3** fixes **Failed to execute 'fetch' on 'Window': Illegal
invocation** when reading and creating Outlook contacts. The shared Graph client
now binds native `fetch` to the browser window. Upload
`outlook-addin/contact-graph.js` and `outlook-addin/contacts.html` together; the
contacts view loads the script with cache version `20261103.3`. Close and reopen
the contacts view afterwards. Existing Microsoft permissions and server
configuration are unchanged. No webservice registration is needed for this fix.

- Export checks the selected Outlook folder for the stable CRM marker and then
  for each transferred email address. A match is skipped. The marker contains
  a hash of the CRM installation URL and the CRM contact ID.
- Import prevents repeated creation through a link combining the signed-in CRM
  user, Microsoft account and immutable Outlook contact ID. A reimported CRM
  export is recognized by its marker. Without an existing link, the first
  Outlook email address is also compared with readable CRM contacts' primary
  `email` field: one match is skipped, and multiple matches are reported as
  ambiguous. Permissions are not bypassed.
- Contacts without an email address are recognized through the link/marker;
  a matching name alone is not considered proof of duplication.
- If a connection failure leaves creation success uncertain, creation is not
  blindly retried. Outlook exports keep a local pending entry until a later
  confirmed match resolves it. CRM imports keep a `pending` entry in
  `vtiger_outlook_contact_link`. An administrator must inspect the target record
  and correct the specific link. Remove a `pending` entry only after confirming
  that creation did not occur. Do not clear the whole table.
- Contacts are created individually, not in a shared transaction across both
  systems. Previously successful contacts remain if later contacts fail.
  The view blocks concurrent clicks. Avoid parallel exports of the same contacts
  from multiple browsers, as Graph does not enforce a unique CRM key constraint
  for contacts.

## Development and tested scope

The Microsoft library and its license texts are shipped locally. Node.js is not
required on the server. To rebuild in `outlook-addin/`:

```sh
npm ci --ignore-scripts --no-audit --no-fund
npm run build:auth
```

Run tests from the CRM root directory:

```sh
node --test outlook-addin/tests/*.test.cjs
php outlook-addin/tests/contacts-server.php
php outlook-addin/tests/contacts-config.php
php outlook-addin/tests/calendar-server.php
php outlook-addin/tests/calendar-manifest.php
```

The tests use simulated CRM/Graph responses and databases. Real Entra sign-in,
consent and contact creation in a real mailbox must be tested with test contacts
after configuration. Also test reimport, a repeated click, a contact without a
last name, and a custom CRM mandatory field.

## Microsoft documentation

- [Supported Outlook add-in contexts](https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/outlook-add-ins-overview)
- [Configure a Microsoft Entra SPA](https://learn.microsoft.com/en-us/entra/identity-platform/scenario-spa-app-configuration)
- [Read Outlook contacts and filter by email](https://learn.microsoft.com/en-us/graph/api/user-list-contacts?view=graph-rest-1.0)
- [Create Outlook contacts and required permission](https://learn.microsoft.com/en-us/graph/api/user-post-contacts?view=graph-rest-1.0)
- [Immutable Outlook IDs](https://learn.microsoft.com/en-us/graph/outlook-immutable-id)
- [MSAL 5: direct redirect handling](https://github.com/AzureAD/microsoft-authentication-library-for-js/blob/msal-browser-v5.25.1/lib/msal-browser/docs/redirect-bridge.md)

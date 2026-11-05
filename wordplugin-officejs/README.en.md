# berliCRM Word Add-in

## Languages in Word 1.4.0.0 / Outlook 1.1.0.0

After sign-in, both add-ins use the CRM user language (`de_de` or `en_us`). Before sign-in, the Office/browser language is used. Translations are maintained in keyed PHP arrays. This update requires registration of `office_addin_user_preferences`. See the [language guide](LANGUAGES.en.md) for deployment and maintenance.

This Office.js add-in replaces the previous VB6/32-bit COM plugin. It inserts
Word `MERGEFIELD`s from berliCRM fields and creates mail-merge letters from
saved CRM list views.

## Version 1.3.0.4: native DOCX output with a fallback

Fixed: bound Word content controls no longer restore old placeholders over
merged CRM names when a letter is opened. Empty Word footnote/endnote parts
also no longer prevent combined output.

Under **Output > File format** (**Ausgabe > Dateiformat**), **Word-Dokument
(.docx)** is selected by default. **altes Word-Dokument (.doc/HTML)** remains
available as a fallback and contains HTML with a `.doc` extension. DOCX creates native Word
files from the complete open template, including multiple pages, pictures,
headers, footers and sections. Both formats offer a combined document or
individual letters as a ZIP, with optional storage linked to CRM records.

Switch back by selecting **altes Word-Dokument (.doc/HTML)**; no database change
is required.
Back up the server files before deployment; see the
[update and rollback instructions](./DOKUMENTATION.en.md#updating-to-1304-and-switching-back).

## Setup in berliCRM v8x

Fresh CRM installations automatically register the administration menu and Word
web services during the final installation step.

After the regular CRM database update (`db_update.php`), administrators can find
the page under **CRM Settings > Integration > Word Add-in**.
The database update automatically registers the menu entry and both Word web
services, including their parameters. The page checks server files and web
service registrations, provides a button to repeat setup or repair the
registrations, and generates an XML manifest for the HTTPS address configured
in `$site_URL`. The `manifest.xml` template contains example addresses only;
use the manifest download on the administration page.

For initial setup without a full database update, the server administrator can
run the following command instead. This additional command is not required
after a regular database update:

```sh
php wordplugin-officejs/build/registerWordMailMergeOperation.php
```

The script can be run repeatedly and registers the administration menu and
both web services. An existing menu entry under “Other Settings” is moved to
“Integration”, preserving its ID and pinned status.
Direct page URL for administrators:
`index.php?module=WordAddin&parent=Settings&view=Index`.

The installation guide distinguishes between centralized Microsoft 365
deployment, individual tests in Word on the web, and a Windows test catalog
without Exchange. It links to the relevant Microsoft instructions. The local
file check does not replace checking certificate trust and connectivity on a
Word workstation.

The complete guide to server installation, installation in Word without
Exchange, centralized deployment through Microsoft 365/Exchange, and daily use
is available in [DOKUMENTATION.en.md](./DOKUMENTATION.en.md).

## Technical notes

- PHP server code targets the CRM's supported **PHP 8.2–8.5** range. Syntax,
  PHPDoc and isolated server contracts are checked with 8.2.35, 8.3.32,
  8.4.27 and 8.5.12, treating warnings and deprecations as failures.
  Manifest generation requires
  DOM/XML; DOCX storage requires ZIP. Other extensions and libraries follow
  the CRM's requirements.

- An animated spinner next to the status message indicates that a record page
  is loading. It disappears when the request succeeds or fails.
- Saved list views for Contacts, Accounts, Leads and Tickets can be used as
  record sources. User fields can be inserted into templates, but Users cannot
  be used as a recipient source.
- A maximum of 1,000 records can be selected per export. Server-side and
  client-side requests use pages or batches of no more than 100 records.
- Output can be legacy HTML with a `.doc` extension or native DOCX. Each
  recipient starts on a new page in a combined document; an individual letter
  may itself contain multiple pages.
- Individual letters can optionally also be saved as CRM documents linked to
  their respective records. This requires `saveWordMailMergeDocument.php` to
  be installed and its web service operation to be registered.
- Native DOCX storage additionally requires `WordMailMergeDocx.php`, both
  updated Word handlers and the PHP ZIP extension. Existing web-service
  registrations remain valid. DOCX downloads alone do not require PHP ZIP.
  The fflate JavaScript library and its MIT license are bundled locally in
  `src/vendor/`; the server does not need npm.
- The CRM server checks module, list-view and record permissions.
- The connection uses `getchallenge`, `login`, `listtypes` and `describe` on the
  berliCRM web service. The API key and session are not stored persistently.

## Development and tests

The project files are static and require no build step. Node.js is required
for the REST, field-name and DOCX tests:

```powershell
npm ci --ignore-scripts
npm test
```

DOCX tests cover multiple pages, pictures, headers, footers, field replacement
and the available legacy fallback. Additional offline PHP validation and an optional test
using installed Word are in `tests/docx-server.php` and
`tests/validate-docx-word.ps1`.

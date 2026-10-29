# berliCRM Word Add-in

This Office.js add-in replaces the previous VB6/32-bit COM plugin. It inserts
Word `MERGEFIELD`s from berliCRM fields and creates mail-merge letters from
saved CRM list views.

## Setup in berliCRM v8x

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

Run the administration integration tests without a CRM database:

```sh
php build/tests/word-addin-settings.php
```

The complete guide to server installation, installation in Word without
Exchange, centralized deployment through Microsoft 365/Exchange, and daily use
is available in [DOKUMENTATION.en.md](./DOKUMENTATION.en.md).

## Technical notes

- Saved list views for Contacts, Accounts, Leads and Tickets can be used as
  record sources. User fields can be inserted into templates, but Users cannot
  be used as a recipient source.
- A maximum of 1,000 records can be selected per export. Server-side and
  client-side requests use pages or batches of no more than 100 records.
- Output can be a combined `.doc` document with one record per page, or
  individual letters bundled in a ZIP archive.
- Individual letters can optionally also be saved as CRM documents linked to
  their respective records. This requires `saveWordMailMergeDocument.php` to
  be installed and its web service operation to be registered.
- The CRM server checks module, list-view and record permissions.
- The connection uses `getchallenge`, `login`, `listtypes` and `describe` on the
  berliCRM web service. The API key and session are not stored persistently.

## Development and tests

The project files are static and require no build step. Node.js is required
for the REST and field-name tests:

```powershell
npm test
```

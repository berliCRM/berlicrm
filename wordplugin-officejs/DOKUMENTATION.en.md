# berliCRM Word Add-in — Installation and User Guide

This document describes deployment of the Office.js add-in on the berliCRM
server, installation in Word without Exchange, centralized deployment through
Microsoft 365/Exchange, and daily use.

For a project overview and test commands, see [README.en.md](./README.en.md).

## Setup through berliCRM v8x administration

The installation guide integrated into v8x is available under **CRM Settings >
Integration > Word Add-in**. After the files have been deployed, the regular
`db_update.php` registers the menu entry and both Word web services, including
their parameters. No additional CLI command is required afterwards.
Alternatively, `php wordplugin-officejs/build/registerWordMailMergeOperation.php`
sets up just the menu and both web services.

1. Open the administration page and check the listed server files.
2. Check the web service registrations. If necessary, select
   **Set up / repair web services**. This registers both `getWordMailMergeData`
   and `saveWordMailMergeDocument`, including their parameters, again. The
   regular database update has already performed this step.
3. Check the linked add-in and icon addresses on a Word workstation.
4. Download the manifest using **Download manifest (.xml)**. It uses the
   configured HTTPS address `$site_URL`, including the subdirectory and port,
   so no manual URL replacements are necessary.
5. Follow the installation instructions on the administration page for your
   Word environment.

Uploading a custom manifest directly is a test method for Word on the web and
is not available in every desktop version of Word. According to Microsoft, the
network share catalog is a test method for Windows. Use the Microsoft 365 admin
center for centralized deployment. The administration page links to the current
Microsoft instructions. The manual steps below describe file deployment. In
v8x, the manifest download replaces manual editing of the example addresses,
and the administration button replaces CLI web service registration.

## 1. Installation on the berliCRM server

Installation consists of two parts:

1. The add-in files are served over HTTPS.
2. An additional REST operation is registered in berliCRM so that the add-in
   can retrieve list views and records.

### Prerequisites

- Administrative access to the berliCRM web server and berliCRM files.
- PHP CLI in a version compatible with the berliCRM installation.
- A valid HTTPS certificate. Word loads add-in content over HTTPS for security
  reasons.
- A permanent URL for the add-in. The add-in and berliCRM must use the same
  origin: protocol, hostname and port. For example,
  `https://crm.example.org/berliCRM/` and
  `https://crm.example.org/berliCRM/wordplugin-officejs/`.
- The CRM user must have access to the berliCRM REST web services and read
  access to the required modules, list views and records.

### Step 1: Deploy the add-in files

1. Back up any existing installation of the `wordplugin-officejs` directory.
2. Copy the complete `wordplugin-officejs` directory from this package to a
   publicly accessible path within the same berliCRM web address, for example:

   ```text
   <CRM-Webroot>/wordplugin-officejs/
   ```

   The following URL must then be accessible, for example:
   `https://crm.example.org/berliCRM/wordplugin-officejs/taskpane.html`.
3. Open `wordplugin-officejs/manifest.xml` and replace the example or previous
   server addresses with those of the actual installation:
   - `SourceLocation` must point to `taskpane.html`.
   - `IconUrl` and `HighResolutionIconUrl` must point to the two icon files
     under `assets/`.
   - `AppDomain` must contain the berliCRM server origin, without a subdirectory.
4. Open `taskpane.html` and both icon URLs over HTTPS in a browser. Also check
   that berliCRM is accessible under the same domain.

Example for an installation at `https://crm.example.org/berliCRM/`:

```text
SourceLocation:
https://crm.example.org/berliCRM/wordplugin-officejs/taskpane.html

AppDomain:
https://crm.example.org
```

If berliCRM uses a different URL or port, adjust the origin accordingly. The
add-in and CRM must not be hosted on different hosts unless the CRM installation
explicitly and securely configures cross-origin requests.

### Step 2: Install the REST operations on the server

1. Copy `include/Webservices/Custom/getWordMailMergeData.php` and
   `include/Webservices/Custom/saveWordMailMergeDocument.php` from this package
   to these exact paths relative to the berliCRM web root:

   ```text
   include/Webservices/Custom/getWordMailMergeData.php
   include/Webservices/Custom/saveWordMailMergeDocument.php
   ```

   The first handler reads the list views, records and selected field values
   visible to the user. The second saves individual mail-merge documents in
   CRM and links them to their respective records.
2. On the server, change to the berliCRM web root and run the registration
   script once with PHP CLI:

   ```sh
   cd /path/to/berliCRM
   php wordplugin-officejs/build/registerWordMailMergeOperation.php
   ```

   On Windows, this could look like:

   ```powershell
   Set-Location C:\Path\to\berliCRM
   php .\wordplugin-officejs\build\registerWordMailMergeOperation.php
   ```

   Launch the script from the berliCRM web root so that it can find
   `config.inc.php` and the database connection. The German success message is:
   `Word-Seriendruck-Webservices wurden registriert.`

The registration script can be run repeatedly: it updates the
`getWordMailMergeData` and `saveWordMailMergeDocument` operations and rewrites
their parameters. Run it again when a handler or registered parameters change.
Changes limited to the add-in's HTML, CSS or JavaScript do not require
re-registration.

### Step 3: Verify the installation

- `taskpane.html` and the icons are accessible through the HTTPS addresses
  specified in the manifest.
- The REST operation has been registered successfully.
- A CRM user can sign in using a username and API key.
- The user can see the intended modules and saved list views in berliCRM.

The add-in shows only modules and views accessible to the signed-in CRM user.
Mail-merge record sources support Contacts, Accounts, Leads and Tickets. User
fields can be inserted into templates, but Users cannot be used as a recipient
source for mail merge.

### Updates and caching

- When JavaScript, CSS or HTML changes, copy the changed add-in files to the
  server. Re-registration is not required.
- When a local JavaScript file changes, update its cache-busting version in
  `taskpane.html` so that Word and browsers do not keep using an older version.
- When the manifest URL, icons or manifest metadata change, deploy the updated
  manifest again in Word or through the central administration portal.
- When a PHP handler changes, copy the updated file to the CRM server.
  Re-registration is only necessary if the operation or its parameters have
  also changed.

## 2. Install the add-in in Word

There are two deployment approaches. For one user or a small test, use local
installation without Exchange. For an organization with centralized Microsoft
365/Exchange administration, centralized deployment is recommended.

### A. Individual tests without Exchange

In Word on the web, the manifest can be uploaded directly for testing. The exact
menu wording depends on the Word version and organizational policies.

1. Make sure server installation from section 1 is complete and `manifest.xml`
   contains the correct URLs.
2. Open Word on the web and a document.
3. Select **Insert > Add-ins** or **Get Add-ins**.
4. Open **My Add-ins**.
5. Select **Upload My Add-in**.
6. Select the supplied `manifest.xml` file and confirm the upload.
7. Open the add-in through **Insert > My Add-ins**. It appears as
   **berliCRM Word Plugin**.

Word on Windows can use a shared network folder as an add-in catalog for
testing. Microsoft supports this approach as a test method:

1. Place `manifest.xml` in a network folder that users can read.
2. In Word, open **File > Options > Trust Center > Trust Center Settings >
   Trusted Add-in Catalogs**.
3. Add the network path as a catalog and enable **Show in Menu**, if available.
4. Restart Word.
5. Open **Insert > My Add-ins > Shared Folder** and add the berliCRM add-in.

If an updated manifest version does not appear, remove the add-in entry from
Word, close Word completely, and load the updated manifest again. Changes to
the served JavaScript/HTML files normally do not require uploading the manifest
again as long as its URLs remain the same.

### B. With Exchange/Microsoft 365: centralized deployment

For organizations using Microsoft 365, centralized deployment through the
Microsoft 365 administration portal is the recommended approach. The
administrator assigns the add-in to individual users or groups; users do not
need to upload the manifest themselves.

1. Check that `manifest.xml` points to the production HTTPS addresses and that
   the add-in files are accessible from the Microsoft 365/Word client.
2. Sign in to the Microsoft 365 admin center with an administrator account.
3. Open **Settings > Integrated apps**. In some portals, this is under **Apps**
   or **Centralized Deployment**.
4. Select **Upload custom apps**.
5. Select **Office Add-in** and upload `manifest.xml`.
6. Assign the add-in to the required users or groups and complete deployment.
7. Wait for distribution to finish. Users open Word with their assigned
   organizational account and start the add-in through **Insert > My Add-ins**
   or the section for add-ins provided by their organization.

Exchange administration for Outlook add-ins does not replace deployment of
this Word add-in. Use the Microsoft 365 admin center for this purpose.

With centralized deployment, the administrator distributes manifest updates
through the administration portal. Changes to files on the server become
available when clients load the updated HTML/JavaScript version. To refresh
quickly, close Word completely and restart it.

## 3. User guide: mail merge step by step

The add-in currently displays German interface labels. The instructions below
provide English descriptions with the corresponding German labels.

### Before you start

- You need a Word document to use as a letter template.
- A saved list view containing the required recipients must exist in berliCRM.
- Sign in with a berliCRM user who has permission to read those records.
- A maximum of **1,000 records** can be processed per export. If a view contains
  more records, narrow it down in berliCRM.

### Step 1: Open the add-in and connect to berliCRM

1. Open the Word template.
2. Start **berliCRM Word Plugin** through **Insert > My Add-ins**.
3. In the CRM address field (**CRM-Adresse**), enter the base address of your
   berliCRM instance, for example `https://crm.example.org/berliCRM/`. Do not
   enter the URL of the `wordplugin-officejs` subdirectory.
4. Enter your berliCRM username and its API key. The API key is your CRM user's
   web service key, not the normal CRM password.
5. Click Connect (**Verbinden**).

After a successful sign-in, the add-in displays the available modules and
mail-merge fields. The add-in does not store the access key or session
persistently. Disconnect (**Trennen**) ends the CRM session.

### Step 2: Insert mail-merge fields into the template

1. In the Module field (**Modul**), select the appropriate CRM module, for
   example Contacts, Accounts, Leads or Tickets.
2. In the Fields list (**Felder**), select the required entries. Hold **Ctrl**
   (**Cmd** on macOS) to select multiple individual fields. Fields are sorted
   alphabetically by label.
3. Place the text cursor in Word where the value should appear. To replace
   existing text with the field, select that text first.
4. In the add-in, click Insert into Word (**In Word einfügen**).
5. Repeat these steps for all required fields and place them at the appropriate
   positions in the letter.

Insert fields using the button; do not type the visible field codes manually.
The add-in inserts actual Word mail-merge fields. The fields selected when
creating the document must match the fields in the template.

### Step 3: Select a list view and recipients

1. Under Saved list view (**Gespeicherte Listenansicht**), select the required
   berliCRM view. Only views visible to your CRM user are shown.
2. Select individual recipients in the record list. Hold **Ctrl** (**Cmd** on
   macOS) to select multiple individual entries. Depending on Word and your
   operating system, hold **Shift** to select a continuous range.
3. Use Previous (**Zurück**) and Next (**Weiter**) to display additional pages
   of the view. Previously selected records remain selected.
4. Alternatively, choose Select all records (**Alle Datensätze auswählen**) to
   select every record in the list view. This button is available only when
   the total is no more than 1,000 records.
5. Clear selection (**Auswahl aufheben**) removes the entire current selection.

The preview shows values from the selected fields rather than internal
berliCRM record IDs. Reference and owner fields are displayed as the name of
the linked CRM record rather than its internal reference ID. An empty field
in the preview does not necessarily mean that the record is missing; the
corresponding CRM field value may be empty.

### Step 4: Choose an output format and create documents

Under Output (**Ausgabe**), choose one of the two formats. Independently of
this choice, you can also enable Save individual letters as documents linked
to their CRM records (**Individuelle Briefe zusätzlich als Dokument beim
jeweiligen CRM-Datensatz speichern**). The add-in then creates a separate
Word-compatible document in CRM for each recipient and links it to the record.
This works with both a combined download and a ZIP archive.

The document title consists of the fixed prefix `Serienbrief`, the creation
date, and the selected field values for that record. For example:
`Serienbrief 29.10.2026: Hold, Trevor` (last name, first name). berliCRM limits
document titles to 50 characters, so titles with long field values are
truncated at the end.

In addition to read access, the user needs permission to create CRM documents.
Each saved file must be no larger than 5 MB. berliCRM stores files in
date-based subdirectories under `storage/`, so check the year, month and week
subdirectories as well as the root directory.

Then choose the required download format:

#### One combined mail-merge document

- Select One combined mail-merge document, all records in one document
  (**Ein gemeinsamer Serienbrief (alle Datensätze in einem Dokument)**).
- Click Create mail-merge documents (**Seriendruck-Dokumente erstellen**).
- The add-in creates a `.doc` file containing all selected recipients. Each
  record starts on a new page.
- After generation, click Download combined mail merge
  (**Gemeinsamen Serienbrief herunterladen**).

Use this format when all letters should be reviewed, printed or processed
together in one document.

#### Individual letters in a ZIP archive

- Select Individual letters in a ZIP archive (**Einzelne Briefe als ZIP-Datei**).
- Click Create mail-merge documents (**Seriendruck-Dokumente erstellen**).
- The add-in creates a separate Word-compatible `.doc` document for each
  selected record and bundles the files in a ZIP archive.
- Click Download ZIP archive containing individual letters
  (**ZIP-Datei mit den Einzelbriefen herunterladen**) and extract the archive
  to open the letters individually.

Use this format when each letter should be saved or distributed separately.
Filenames are based on the record name; duplicate names receive a sequential
number.

The generated documents use the HTML content of the open Word document as the
template. Check the result before sending or printing, especially the page
layout, headers, footers, images and formatting.

### Available options and limitations

- **Recipients:** select individual records manually or select all records in
  the currently selected list view.
- **Output:** one combined document with one recipient per page, or individual
  files in a ZIP archive.
- **Data source:** saved list views in Contacts, Accounts, Leads and Tickets.
  Users can be used as a field module for merge fields, but not as a recipient
  source.
- **Volume:** a maximum of 1,000 recipients per export. Internally, data is
  processed in smaller pages and batches.
- **Permissions:** the signed-in berliCRM user's module, list-view and record
  permissions continue to apply.

## Troubleshooting

### Sign-in fails

- Check the CRM base address, without `/wordplugin-officejs/`.
- Check the username and API key. The API key is not the normal CRM password.
- Check that the CRM user is allowed to use the berliCRM web service and that
  the CRM address is accessible over HTTPS.

### List views or records are missing

- In berliCRM, check that the view is visible to the signed-in user and
  contains records.
- Check that the user has read access to the module and records.
- Select at least one field first if the preview or mail merge requires
  field values.

### HTTP 500 or invalid response when loading records

- Check that the current version of
  `include/Webservices/Custom/getWordMailMergeData.php` is on the CRM server.
- Check that the registration script completed successfully. Run it again if
  the REST parameters have changed.
- Check the PHP/web server error log immediately after the error. Remove API
  keys, session tokens and personal data before sharing log excerpts.

### Individual letters are not saved in CRM

- Check that `include/Webservices/Custom/saveWordMailMergeDocument.php` is on
  the CRM server and that the registration script completed successfully after
  copying the file.
- To store files and link them to records, the CRM server must provide the
  standard document attachment storage function and
  `vtws_update_document_relations`.
- The user needs permission to create documents and access to the associated
  record.
- Each document file must be no larger than 5 MB. Individual save errors appear
  in the add-in status after the download file has been generated. Letters
  already saved successfully are retained.
- If errors persist, check the PHP/web server error log. Remove credentials,
  session tokens and personal data before sharing logs.

### The add-in still shows old content after an update

- Close Word completely and restart it.
- Check that the new `taskpane.html` and the JavaScript files it references
  are on the server.
- Check that the cache-busting version in `taskpane.html` has been updated for
  the changed JavaScript file.
- With centralized deployment, distribution of a changed manifest may take
  some time.

## Privacy and security

The API key is used only for sign-in and is removed from the input field after
a successful sign-in. The add-in stores neither the API key nor the session
persistently. The CRM server checks the available modules, list views and
access to requested records. Do not share the API key, and remove confidential
values from error logs before sharing them.

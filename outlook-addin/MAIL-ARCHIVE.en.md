# Archive received Outlook emails in the CRM

[Deutsch](MAIL-ARCHIVE.md) | **English**

When an existing email is opened, the berliCRM add-in shows a dedicated read view:
CRM sign-in or sign-in status, **E-Mail im CRM ablegen** (Archive email in CRM),
and the **CRM- und Outlook-Kontakte verwalten** (Manage CRM and Outlook contacts)
link. This link is also visible when reading from the message list. Recipient
search, distribution lists and recent events belong to email composition and
are hidden in the read view. Instructions below include the current German
add-in labels so the corresponding controls can be identified.

> **IMPORTANT for Edge and Firefox:** When installing manually from a manifest,
> **Optional connected experiences must be enabled** in Outlook. Otherwise the
> app may initially appear but cannot be used. The exact settings path and the
> repeated-opening test are in the [installation documentation](README.en.md#important-optional-connected-experiences).

## Usage

1. Open an email in Outlook and launch the berliCRM add-in.
2. If necessary, sign in with your CRM username and Access Key. A saved CRM
   session is checked; the sign-in form reappears if it has expired.
3. Select **E-Mail im CRM ablegen**. The search finds readable Contacts records
   whose primary `email` field exactly matches the address in Outlook's **From**
   field. The field's visible label (for example, “Primary email”) is read from
   `describe('Contacts')` for the signed-in user and refreshed on initialization
   and each search. Renamed labels are also used in the no-results message.
   A different technical sender address used for delegated sending is not used
   for this lookup. Secondary email addresses, Leads and Accounts are not searched.
4. The results show first name, last name and associated company name where
   available and readable. Select one or more contacts. No contact is selected
   automatically.
5. If attachments are present, leave **Anhänge übernehmen** (Include attachments)
   selected or explicitly disable it. Then select **E-Mail bei ausgewählten
   Kontakten ablegen** (Archive email for selected contacts). The result appears
   directly below the list.

A CRM record is created in **Emails** and linked to all selected Contacts through
the CRM email relationship. It is then available among those contacts' related
emails. The CRM email has status `SAVED`. No email is sent, and the Outlook
message is not changed.

Transferred data includes subject, sanitized HTML body, From address, To/Cc
addresses supplied by Office.js, and the Outlook message creation date and time
in the CRM database timezone. An empty subject is stored as “(Ohne Betreff)”
(No subject). Attachments are stored as CRM email attachments. No additional
`.msg`/`.eml` file of the whole message is created. Depending on the Outlook
client and view, Office.js may return only part of very large recipient lists;
the stored headers are therefore not a complete MIME export.

## Update

Extract `outlook-addin/build/outlook-update-20261102.12.zip` into the CRM root.
It contains the required runtime files for the existing mail, calendar and
contacts views. The HTTP endpoint is now `outlook-addin/rest/outlook.php`.
The private configuration is not included:
**Move the existing server file `rest/outlook.config.php` to
`outlook-addin/rest/outlook.config.php`, preserving its contents and file
permissions.** Compare any configuration already at the destination before
replacing it. Upload all package files together; the Microsoft sign-in bundle
and contacts view also use the new path. After successful testing, the obsolete
Outlook PHP files under `/rest/` can be removed; remove the directory only if
it is empty.

The move requires no new webservice registration. The CRM `webservice.php` URL
and Microsoft redirect URI stay unchanged. During a CRM version update,
`db_update.php` automatically calls the shared `outlook-addin/update.php` routine
to initialize the six webservices and any missing mapping tables before advancing
the CRM version number. The five Outlook tools now live in
`outlook-addin/build/`; their old root-level `/build/` location is no longer used.
The updater also registers **Settings → Integration → Outlook Add-in**, where
administrators can download the manifest and configuration with mandatory
client ID and tenant ID. Upload the new Settings page, template and language
files as well. See [CONTACTS.en.md](CONTACTS.en.md#setup-through-the-crm-settings-menu).
The additional **Test Microsoft connection** button checks the saved
configuration through sign-in and read-only contact access.
`outlook-addin/settings-connection.js` is required for this feature.
For a separate setup or repair, run from the CRM root:

```sh
php outlook-addin/build/registerOutlookAddinOperation.php
```

This also registers the POST operation `outlook_archive_email` with the JSON
parameter `input`. The add-in's own tables `vtiger_outlook_mail_link` and
`vtiger_outlook_mail_file` hold confirmed and unconfirmed archives.
The installer can be run repeatedly. A total of six Outlook operations are
registered afterwards. Archiving uses the standard `describe`, `query`,
`retrieve` and `create` webservices internally; the custom operation adds safe
multi-contact linking and retry tracking. Core files and user/profile
permissions are not modified.

The existing manifest activation for read messages is sufficient; this change
needs no new manifest. Reload the task pane after uploading. If the old view
still appears, clear the cache for the CRM add-in page. Its HTML must load
`mail-read.js?v=20261102.8`, `calendar.js?v=20261102.6` and
`taskpane.js?v=20261102.4`. `contacts.js` and both Microsoft sign-in bundles use
cache version `20261102.10`; `contacts-auth.css` still uses `20261102.9`.

The contacts view now attempts the Microsoft connection automatically. The
manual sign-in menu appears only when that attempt fails. Upload the new
`assets/contacts-sso.bundle.js` and updated redirect page together; the complete
file list and flow are in
[CONTACTS.en.md](CONTACTS.en.md#automatic-microsoft-connection).

The contacts link now also stays visible in the email read view. Update
`outlook-addin/mail-read.js` and `outlook-addin/taskpane.html` together for this
addition. No webservice registration or manifest installation is required.

While composing an email with an existing CRM session, the view now shows only
sign-in status and **Anders anmelden** (Sign in as another user). For this display
fix, update `outlook-addin/calendar.js` and `outlook-addin/taskpane.html` together;
no webservice registration or manifest installation is required. Outlook manages
pinning separately for reading and composing: open the add-in once in compose
mode and pin it there too. See
[Microsoft's pinning documentation](https://learn.microsoft.com/en-us/office/dev/add-ins/outlook/pinnable-taskpane#supported-outlook-modes).

The package also fixes email field length validation. No webservice registration
or database change is required for that fix. Negative `fieldsize` values such as
`-1` for TEXT columns are treated as unspecified. Only positive values are
checked as character limits; Cc/To addresses are not truncated.

This feature reads the current email through Office.js and requires no
additional Entra/Graph configuration. The separate Outlook contact transfer
continues to use its Graph connection as described in [CONTACTS.en.md](CONTACTS.en.md).

## Permissions and error handling

- The signed-in user needs read access to `Contacts.email`, the selected
  contacts and the Emails module, and create permission for Emails. The assigned
  owner is always the signed-in CRM user. Before saving, read permissions and
  each selected contact's email match against the From address are checked again.
  If a contact has changed in the meantime, the user must search again.
- Company names are read only through a permitted Accounts query. Without
  account read permission, the additional company name remains empty.
- Search loads all exact matches in pages of 100 records. At most 1,000 contacts
  can be selected per archive. They are linked to the same CRM email; existing
  links remain intact.
- Switching emails or CRM sign-ins clears results and selection. Late search
  responses are ignored. Switching during message reading prevents the
  subsequent CRM request. Save requests already sent may finish on the server
  and are not attributed to a new selection.
- Repeated archiving by the same user reuses the existing CRM email and adds
  missing links. This uses the mailbox and Internet Message ID, falling back to
  the Outlook item ID if necessary. Moving an email may change its item ID.
  Without an Internet Message ID, reliable recognition across folder moves is
  not guaranteed.
- Linking an already archived email does not overwrite its contents. Additional
  contacts or attachments not yet transferred can be added by starting the
  search again. Changes also require write permission on the existing CRM email.
- Uncertain creation failures leave `pending` links in place so a repeated click
  does not create a second email or attachment. An administrator must inspect
  the CRM record/attachment and link before correcting these entries. Confirmed
  emails with failed contact links are reused on retry. Partially successful
  archives are not automatically deleted.

## Attachments

File attachment transfer requires Office.js Mailbox 1.8 or later and
`getAttachmentContentAsync`. Base64 files and attached emails/calendar files
returned by Office.js are supported. Outlook's restrictions on reading items
attached by drag and drop still apply.

Cloud attachments returned only as URLs are not downloaded automatically.
If a requested attachment cannot be read, the view stops with a message before
archiving in the CRM. To deliberately save only subject and body, disable the
attachment option.

Limits: 100 attachments, 10 MiB per file, 20 MiB total, and 2 MiB message body.
For large messages, PHP `post_max_size` and webserver/proxy limits must allow
the JSON request including Base64 overhead, for example 40M. Filenames are
sanitized using the existing CRM filename checks; blocked extensions are
neutralized according to CRM rules. Inline images are stored as files;
embedded `cid:` references are not currently rewritten.

## Validation

```sh
node --test outlook-addin/tests/*.test.cjs
php outlook-addin/tests/mail-archive-server.php
php outlook-addin/tests/calendar-manifest.php
```

The tests use simulated CRM/Office responses and an isolated database simulation.
Message bodies are also checked with the actual CRM HTMLPurifier library.
These tests do not modify real CRM records or mailboxes. After deployment, test
an email against two contacts sharing the From address: company names, multiple
selection, visibility under both contacts, repeated clicks, a file attachment,
and switching to a different email.

The Office.js interfaces are documented in Microsoft's
[MessageRead reference](https://learn.microsoft.com/en-us/javascript/api/outlook/office.messageread?view=outlook-js-1.16)
and [AttachmentContent reference](https://learn.microsoft.com/en-us/javascript/api/outlook/office.attachmentcontent?view=outlook-js-1.8).

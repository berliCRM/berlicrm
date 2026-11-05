# Office add-in languages

[Deutsch](LANGUAGES.md)

Starting with **Word 1.4.0.0** and **Outlook 1.1.0.0**, the add-ins use the
**signed-in CRM user's language**. Select it in the CRM user's **My Preferences /
Language** setting. Supported languages are `de_de` and `en_us`; unsupported
languages fall back to English. Reopen the add-in or sign in to CRM again after
changing the preference.

Before CRM sign-in, the Office display language is used, with the browser
language as fallback. Restored Outlook sessions fetch the user's current CRM
language again. The contacts view and Microsoft sign-in dialog use that language
as well.

Menu text rendered by Office itself follows the Office language. The manifests
provide German defaults and English overrides. CRM field labels still come
from CRM metadata. Personal calendar date formats and time zones are preserved.
Field aliases support existing Word templates across the two languages.

## Server update

Deploy the catalogs, browser files, updated handlers and
`include/utils/OfficeAddinI18n.php` together. Retain your private
`outlook-addin/rest/outlook.config.php`.

**Important:** Register the new authenticated, read-only operation
`office_addin_user_preferences`. It reads only the current session user's
language and accepts no target user ID. The existing Settings installers and
`db_update.php` already use the extended registration. For a standalone add-in
update, repair the installation in CRM Settings or run from the CRM webroot:

```sh
php wordplugin-officejs/build/registerWordMailMergeOperation.php
php outlook-addin/build/registerOutlookAddinOperation.php
```

Registration is repeatable. Either installer also registers the shared language
operation on its own. Generate fresh manifests in the respective Settings pages
and update the add-ins afterwards.

## Maintaining translations

The editable text sources are PHP arrays with stable keys:

- `wordplugin-officejs/languages/de_de.php` and `en_us.php`
- `outlook-addin/languages/de_de.php` and `en_us.php`

Example:

```php
'USERNAME' => 'Benutzername', // de_de.php
'USERNAME' => 'Username',     // en_us.php
```

Add each new key to both catalogs. Positional parameters such as `{0}` and `{1}`
must match across languages. Values are plain text, not HTML. Keep keys stable
when editing the translated wording.

After editing translations, run from the repository root using Node.js and PHP CLI:

```sh
node wordplugin-officejs/build/build-languages.mjs
```

Set `PHP_BINARY` if needed. On Windows, the default is `C:/php/php.exe`.
The builder validates keys and parameters, then generates both `language.js`
files and manifests from `manifest.template.xml`. Commit and deploy the generated
files as well. Changes to Microsoft sign-in logic additionally require
`npm run build:auth --prefix outlook-addin`. Production servers do not need Node.js.

```sh
node --test wordplugin-officejs/tests/*.test.js
node --test outlook-addin/tests/*.test.cjs
```

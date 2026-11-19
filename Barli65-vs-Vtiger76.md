# berliCRM (vtiger 6.5 fork) vs. vtiger CRM 7.5.0 — Comparison Report

| | |
|---|---|
| **Subject** | `berliCRM` at commit `0422696` (2026-09-04), release tag `berlicrm-1.0.49` |
| **Compared against** | vtiger CRM **7.5.0** (patch `20221124`), and vtiger CRM **6.5.0** (patch `20160714`) as the common ancestor |
| **Report date** | 2026-09-24 |
| **Method** | Static, file-by-file three-way comparison + targeted code review. No running instance (no database available). |

---

## Table of contents

1. [Executive summary](#1-executive-summary)
2. [Sources and methodology](#2-sources-and-methodology)
3. [Code lineage: where berliCRM sits between 6.5 and 7.5](#3-code-lineage-where-berlicrm-sits-between-65-and-75)
4. [Security findings](#4-security-findings)
5. [Areas where berliCRM is ahead of 7.5](#5-areas-where-berlicrm-is-ahead-of-75)
6. [Third-party libraries](#6-third-party-libraries)
7. [PHP 8 compatibility](#7-php-8-compatibility)
8. [berliCRM-specific defects and concerns](#8-berlicrm-specific-defects-and-concerns)
9. [Feature comparison](#9-feature-comparison)
10. [Database schema and migration path](#10-database-schema-and-migration-path)
11. [Recommendations and remediation plan](#11-recommendations-and-remediation-plan)
12. [Appendices](#12-appendices)

---

## 1. Executive summary

- **berliCRM is a vtiger 6.5.0 fork. No 7.x core code has been merged into it.** `vtigerversion.php` still reports `6.5.0`. Of the 6,055 files that exist in all three code bases, only 138 match 7.5 but not 6.5, and nearly all of those are Smarty or ADOdb library files. 571 files are still exactly the 6.5 version where upstream changed them, and 834 were changed on both sides, so a straight merge would conflict.
- **The UI is still the 6.x `layouts/vlayout`.** 7.5's `layouts/v7` (1,732 files) is absent.
- **Most important security gap:** 7.5 checks permissions on every request (`checkPermission()` is always called, and 147 handlers declare their requirements through `requiresPermission()`). berliCRM keeps 6.5's skip-list, and two access-control holes follow from it that I confirmed in the code:
  - any logged-in user can **create, edit or delete any price book**;
  - any logged-in user can **delete any other user's list filters**.
- **Security issues berliCRM introduced itself:**
  - the referer check is disabled (`!= 0` instead of `!== 0`);
  - an unauthenticated account-lockout that can lock the admin out permanently;
  - cron can be triggered from the web under `cgi-fcgi`;
  - cron-failure alerts are hard-coded to a vendor email address;
  - a hard-coded OAuth2 redirect URI pointing at a test host.
- **Where berliCRM is ahead of 7.5:** newer Smarty, HTMLPurifier, TCPDF, CKEditor and ADOdb; stricter image-upload validation; brute-force protection; and cleaner PHP 8 support. Its only PHP 8 parse errors are in the Calendar iCal library, which breaks `.ics` import and export.
- **Recommendation:** rebasing onto 7.5 is not realistic (834 conflicting files, and 16 custom modules would need v7 templates). Port the specific 7.x security fixes and fix the berliCRM-specific defects, following the prioritised plan in [§11](#11-recommendations-and-remediation-plan).

### Findings at a glance

| ID | Severity | Finding | Origin |
|---|---|---|---|
| S-1 | **High** | Permission checks skipped for `PriceBooks`, `CustomView`, `Vtiger`, `Import`, `Inventory`, `Home` (confirmed: price-book tampering, deleting other users' filters) | 7.x fix missing |
| S-2 | Medium | Referer check never rejects anything | Introduced by berliCRM |
| S-3 | Medium | Anyone can lock any account, including admin, permanently; the webservice login leaks which usernames exist | Introduced by berliCRM + 7.x fix missing |
| S-4 | Medium | Event-handler XSS blocklist is behind 7.5 (tested bypasses); `description`/`reportname` skip input cleaning | 7.x fix missing + introduced by berliCRM |
| S-5 | Medium* | `vtigercron.php` runs from the web under `cgi-fcgi`; `cron/` not protected by `.htaccess` | Introduced by berliCRM + 7.x fix missing |
| S-6 | Medium | PHPMailer 5.2.6 still used by `forgotPassword.php` | 7.x fix missing |
| S-7 | Low | Password hashing uses SHA-512 crypt (not bcrypt); legacy MD5 hashes are never upgraded | 7.x fix missing |
| S-8 | Low | Record type not checked against the module in `Vtiger_Save_Action` | 7.x fix missing |
| B-1 | Medium | Cron-timeout alerts hard-coded to `mb@crm-now.de` (privacy/GDPR) | berliCRM |
| B-2 | Medium | OAuth2 callback hard-codes a test host as `redirectUri` | berliCRM |
| B-3 | Medium | Calendar iCal import/export fails to parse on PHP 8 | berliCRM (7.5 fixed it) |
| B-4 | Low | `composer.lock` out of sync with `composer.json`; PHPUnit in `require` | berliCRM |
| B-5 | Low | `db_update.php` has no authentication; `installComposer.php` runs on page load | berliCRM |

\* Only on servers running PHP as `cgi-fcgi` (php-cgi or mod_fcgid).

---

## 2. Sources and methodology

### 2.1 Sources

`code.vtiger.com` was blocked by the analysis environment's network policy, so the official release tarballs from SourceForge were used instead. These are the same code that is tagged in the vtiger repository.

| Version | File | SHA-1 | `$patch_version` |
|---|---|---|---|
| 6.5.0 | `vtigercrm6.5.0.tar.gz` | `2a62121e5f7de9938cf8e5339200b163ff7b028b` | `20160714` |
| 7.5.0 | `vtigercrm7.5.0.tar.gz` | `593669cd150dd44f48e902602bf2575ceb1c9c9c` | `20221124` |

Download URLs: `https://downloads.sourceforge.net/project/vtigercrm/vtiger%20CRM%20<ver>/Core%20Product/vtigercrm<ver>.tar.gz`

### 2.2 Methodology

1. **Three-way file comparison.** Every file in berliCRM (`git ls-files`) was compared byte-for-byte against 6.5.0 and 7.5.0. Each file present in all three was then put into one of five lineage classes (see [§3.1](#31-file-level-lineage)).
2. **Missing security primitives.** Functions defined in the 7.5 core (`include/`, `includes/`, `vtlib/`, `modules/Vtiger`, `modules/Users`, `modules/Settings/Vtiger`) but absent from both 6.5 and berliCRM were listed, and the security-relevant ones reviewed.
3. **Targeted review.** Request handling, the front controller, controllers, CSRF, input cleaning, uploads, authentication, sessions, webservices, cron and berliCRM-only entry points were diffed and read.
4. **Runtime tests of isolated functions (PHP 8.4 CLI).** berliCRM's `vtlib_purify()` / `purifyHtmlEventAttributes()` were run against XSS payloads, and the referer comparison was run with sample referers.
5. **PHP 8.4 syntax check** (`php -l`) of every `.php` file in both berliCRM and 7.5.
6. **Heuristic scan** for upstream security changes berliCRM lacks ([Appendix 12.3](#123-upstream-security-hunks-not-present-in-berlicrm-heuristic)).

### 2.3 Limitations

- No database or web server was available, so the application was not run end to end. The findings marked *confirmed* come from reading the code, backed by an isolated PHP test where one is noted.
- The heuristic in 12.3 compares code line by line. berliCRM may have fixed some of those items in a different way, so treat it as an estimate of the gap, not a list of vulnerabilities.

---

## 3. Code lineage: where berliCRM sits between 6.5 and 7.5

### 3.1 File-level lineage

| Metric | Count |
|---|---|
| Files in berliCRM (git) | 10,743 |
| Files in vtiger 7.5.0 | 11,096 |
| Files in vtiger 6.5.0 | 8,753 |
| Shared by berliCRM and 7.5 | 6,244 |
| Only in berliCRM (not in 7.5) | 4,499 |
| Only in 7.5 (not in berliCRM) | 4,852 |
| Shared by all three | 6,055 |

Lineage of the 6,055 files present in all three code bases:

| Class | Count | Meaning |
|---|---|---|
| identical in all three | 3,721 | untouched vtiger code |
| **berliCRM = 6.5, upstream changed** | **571** | 7.x changes berliCRM does not have at all |
| **all three differ** | **834** | changed by both berliCRM and upstream (merge conflicts) |
| berliCRM changed, upstream unchanged | 791 | berliCRM-only customisations of stable files |
| berliCRM = 7.5 (backported) | 138 | 68 in `libraries/adodb`, 58 in `libraries/Smarty`, 6 in `layouts/vlayout`, plus `include/utils/encryption.php`, `modules/Campaigns/models/ListView.php`, `modules/Vtiger/uitypes/Url.php`, `vtlib/Vtiger/Menu.php`, `tabdata.php`, 1 nusoap file |

**Conclusion:** berliCRM is still built on the 6.5 core. Apart from library upgrades, it has taken essentially nothing from vtiger 7.0–7.5.

### 3.2 Where the gaps are concentrated

Most-affected directories among the files berliCRM still has at the 6.5 version while upstream changed them:

| Files | Directory |
|---|---|
| 128 | `pkg/vtiger/modules` (bundled extension modules) |
| 52 | `libraries/bootstrap/js` |
| 17 | `modules/Settings/Vtiger` |
| 17 | `libraries/PHPExcel` |
| 15 | `layouts/vlayout/modules` |
| 10 each | `modules/Vtiger/views`, `modules/Vtiger/models` |
| 9 | `modules/Settings/Workflows` |
| 8 each | `modules/Vtiger/actions`, `modules/Users/views`, `modules/Settings/PickListDependency` |
| 7 each | `modules/Users/actions`, `modules/Settings/Roles`, `modules/Settings/Profiles`, `modules/Settings/Groups` |

### 3.3 UI layer

| | berliCRM | 7.5 |
|---|---|---|
| Layouts | `vlayout` only | `v7` (default) + `vlayout` |
| `Vtiger_Viewer::DEFAULTLAYOUT` | `vlayout` | `v7` |
| Viewer base class | `Smarty` | `SmartyBC` |

berliCRM templates and JavaScript are written for the 6.x UI. Moving to the 7.x UI would mean porting every berliCRM customisation into `layouts/v7`.

### 3.4 Top-level differences

- **Only in berliCRM:** `OAuth2-Mail/`, `customerportal/`, `kundenportal/`, `soap/`, `swisspdf/`, `composer.json`, `composer.lock`, `connection.php`, `db_update.php`, `installComposer.php`, `nexmoWebhookEndpoint.php`, `requestPasswordReset.php`, `vtigerservice.php`, `copyright_en.html`, `README.txt`
- **Only in 7.5:** `public.php` (public file links), `config_override.sample.php`, `cron/.htaccess`; `config.inc.php` ships in the tarball (berliCRM generates it at install time)

### 3.5 Modules

- **Core modules (`modules/`):** the same set, except that berliCRM also keeps `ProjectTask` and `Services` in the tree. In 7.5 these come from packages.
- **Settings sub-modules:** only in berliCRM: `CustomerPortal`, `EmailConfigurator`. Only in 7.5: `Potentials` (Potential → Project conversion mapping) and `Tags`.
- **Extension modules (`pkg/vtiger/modules`):** only in 7.5: `ExtensionStore`. Only in berliCRM (16):

| Module | Version | Purpose |
|---|---|---|
| CWC | 3.0.1 | CRM Word Connector |
| ConfigEditor | 1.9 | Configuration editor |
| CronTasks | 1.2 | Cron task management |
| ListViewColors | 1.1 | Coloured list views |
| Mailchimp | 4.05 | Mailchimp integration |
| Pdfsettings | 2.3 | PDF output settings |
| Search | 3.12 | Global search |
| ToolWidgets | 1.2 | Tool widgets |
| Tooltip | 1.2 | Tooltips |
| Verteiler | 1.6 | Distribution lists |
| berliCleverReach | 0.98 | CleverReach integration |
| berliSoftphones | 1.2 | Softphone integration |
| berliWidgets | 1.0.3 | Dashboard widgets |
| berlimap | 3.0 | Map |
| crmtogo | 4.15 | Mobile client |
| gdpr | 1.1 | GDPR tooling |

- **Packages (`packages/vtiger`):** 7.5 ships 15 language packs (Arabic, Brazilian Portuguese, British English, German, Dutch, French, Hungarian, Italian, Mexican Spanish, Polish, Romanian, Russian, Spanish, Swedish, Turkish). berliCRM ships only the German one (`Deutsch.zip`), but adds its own module packages.

### 3.6 Webservices

- **Only in 7.5:** `AddRelated.php`, `ConvertPotential.php`, `Custom/DeleteUser.php`, `FileRetrieve.php`, `VtigerProductOperation.php`
- **Only in berliCRM:** `Custom/NewParser.php`, `Custom/ProductRelation.php`, `Custom/getMultiRelations.php`, `Custom/getNewMultiRelations.php`, `Custom/getRelatedDocuments.php`, `DeleteUser.php`, `RetrieveDocAttachment.php`
- 26 webservice files were changed by both berliCRM and upstream (including `Login.php`, `QueryParser.php`, `VTQL_Parser.php`, `Utils.php`, `Revise.php`, `Update.php`, `DataTransform.php`).

---

## 4. Security findings

### S-1 (High): The 6.5 permission-check skip-list is still in place

**7.5 behaviour.** `includes/main/WebUI.php` calls `$handler->checkPermission($request)` on every request. The base controller (`includes/runtime/Controller.php`) adds a declarative framework: every handler lists what it needs in `requiresPermission()`, and the base `checkPermission()` enforces it through `Users_Privileges_Model::isPermitted()`. 147 handlers in 7.5 implement `requiresPermission()`.

**berliCRM behaviour** (`includes/main/WebUI.php:210`), unchanged from 6.5:

```php
$skipList = array('Users', 'Home', 'CustomView', 'Import', 'Export', 'Inventory', 'Vtiger','PriceBooks','Migration','Install');

if(!in_array($module, $skipList) && stripos($qualifiedModuleName, 'Settings') === false) {
    $this->triggerCheckPermission($handler, $request);
}
if(stripos($qualifiedModuleName, 'Settings') === 0 || ($module=='Users')) {
    $handler->checkPermission($request);
}
```

For requests with `module=` any of the skip-listed values (other than `Users`), **no handler's `checkPermission()` ever runs.** berliCRM has 0 `requiresPermission()` implementations. Across the skip-listed modules there are 86 action/view handlers; 59 of them have no permission check inside `process()` either.

**Confirmed consequences:**

1. **Price books: create, edit and delete for every user.** `PriceBooks_Save_Action` extends `Vtiger_Save_Action`, and `PriceBooks` has no delete action of its own, so it falls back to `Vtiger_Delete_Action`. Both base classes check permissions only in `checkPermission()`, which the skip-list bypasses. So any authenticated user, whatever their profile says about the PriceBooks module, can create, modify or delete any price book and link it to products (`relationOperation`), as long as they send a valid CSRF token for their own session.
2. **List filters (Custom Views): delete any user's filter.** berliCRM's `modules/CustomView/actions/Delete.php` and `DeleteAjax.php`:

   ```php
   public function process(Vtiger_Request $request) {
       $customViewModel = CustomView_Record_Model::getInstanceById($request->get('record'));
       $customViewModel->delete();
   }
   ```

   7.5 adds a permission requirement on the source module and an owner-or-admin check:

   ```php
   $customViewOwner = $customViewModel->getOwnerId();
   $currentUser = Users_Record_Model::getCurrentUserModel();
   if ((!$currentUser->isAdminUser()) && ($customViewOwner != $currentUser->getId())) {
       throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
   }
   ```

   `CustomView_Record_Model::delete()` in berliCRM has no check either, so any user can delete other users' private or public filters by ID, along with the dashboard mini-list widgets that use them.

**Other exposed handlers:** the whole list is in [Appendix 12.4](#124-skip-listed-handlers-without-in-body-permission-checks). The ones to review first are the `Inventory` save actions, the `Vtiger` mass, save and relation handlers reachable with `module=Vtiger`, and the `Import` actions.

**Recommendation:** port 7.5's unconditional `checkPermission()` and the `requiresPermission()` framework. As a minimum stop-gap: remove `PriceBooks`, `CustomView`, `Inventory` and `Import` from the skip-list, and add owner/admin checks to the CustomView delete and save actions.

---

### S-2 (Medium): Referer validation is ineffective (introduced by berliCRM)

`includes/http/Request.php:217`:

```php
$site_CRMNOW_ALT_URL_1 = 'crm-now.de';
$site_CRMNOW_ALT_URL_2 = 'illuminetic.com';
...
if ((((stripos($_SERVER['HTTP_REFERER'], $site_URL) != 0) AND
      (stripos($_SERVER['HTTP_REFERER'], $site_CRMNOW_ALT_URL_1) != 0) AND
      (stripos($_SERVER['HTTP_REFERER'], $site_CRMNOW_ALT_URL_2) != 0) )) && ...) {
    throw new Exception('Illegal request');
}
```

6.5.0 and 7.5.0 both use `stripos(...) !== 0` against `$site_URL` alone. `stripos()` returns `false` when the text is not found, and in PHP `false != 0` is `false`. A foreign referer therefore never triggers the exception. It would only trigger if the site URL **and** both vendor domains all appeared in the referer at a position other than 0. Tested with PHP 8.4:

| Referer | berliCRM rejects | 6.5/7.5 rejects |
|---|---|---|
| `https://evil.example/attack.html` | no | **yes** |
| `https://crm.example.com/index.php` | no | no |
| `https://evil.example/?x=https://crm.example.com/` | no | **yes** |

**Impact:** `validateReadAccess()` does nothing, and `validateWriteAccess()` loses its second layer of defence. csrf-magic tokens still protect state-changing requests, so this is a loss of defence in depth, not an open CSRF hole by itself. The check also hard-codes vendor domains into every customer installation.

**Recommendation:** restore `stripos($_SERVER['HTTP_REFERER'], $site_URL) !== 0`. If other origins really need to be trusted, make that a configurable list of full origins, compared exactly.

---

### S-3 (Medium): Permanent account lockout by anyone, plus username leak

**Lockout** (`include/utils/utils.php:2521`, `crmnow_login_protection()`): every failed login increments `berli_failed_logins.failed_count`. At 5 failures:

```php
$query = "UPDATE vtiger_users SET status = ?, date_modified=? WHERE user_name = ?;";
$result = $adb->pquery($query, array('Inactive', ...));
```

The time-based reactivation block is commented out. The function runs **before authentication**, from both the web login and `include/Webservices/Login.php`. As a result:

- **anyone who knows or guesses a username (e.g. `admin`) can disable that account permanently** with 5 requests;
- if every admin account gets locked, recovery needs direct database access.

The protection is keyed on the username, so spoofing `X-Forwarded-For` does not bypass it. The function also runs a `CREATE TABLE IF NOT EXISTS` on every failed login.

**Username leak:** `include/Webservices/Login.php:51` still throws `'Given user is inactive'`. 7.5 replaced it with the generic `"Invalid username or password"` and a comment about enumeration attacks. Together with the lockout, this confirms to an attacker both that a username exists and that it has been locked.

**Recommendation:** make the lockout temporary (for example exponential back-off, or auto-unlock after N minutes), consider per-IP throttling as well, keep at least one break-glass admin path, and use 7.5's generic error message.

---

### S-4 (Medium): XSS input cleaning is behind 7.5

**Event-handler blocklist.** `vtlib_purify()` runs HTMLPurifier and then `purifyHtmlEventAttributes()`. That second step handles values that are not HTML but end up inside HTML attributes. berliCRM (`include/utils/VtlibUtils.php:649`) still has the 6.5 list of about 40 handlers. 7.5 extended it to about 120 (pointer, animation, transition, touch, focus-in/out, toggle, media, …) and added `purifyScript()` and `purifyJavascriptAlert()`, applied through the new `$replaceAll` mode.

berliCRM's actual `vtlib_purify()` run under PHP 8.4:

| Input | berliCRM output |
|---|---|
| `x" onmouseover="alert(1)` | `x" onmouseover&equals;"alert(1)` (neutralised) |
| `x" onmouseenter="alert(1)` | **unchanged** |
| `x" onfocusin="alert(1)" autofocus="` | **unchanged** |
| `x" onanimationstart="alert(1)" style="animation-name:a` | **unchanged** |
| `x" onpointerover="alert(1)` | **unchanged** |
| `x" ontoggle="alert(1)` | **unchanged** |

Whether this becomes a working XSS depends on output encoding at each place the value is displayed. Several edit templates print raw values into attributes, for example `layouts/vlayout/modules/Vtiger/uitypes/Email.tpl`, `Url.tpl` and `Phone.tpl`: `value="{$FIELD_MODEL->get('fieldvalue')}"`. Values read from the database usually pass through `to_html()`, which reduces the risk.

**No cleaning for `description` and `reportname`** (introduced by berliCRM, `includes/http/Request.php:69`):

```php
$noPurifyKeys = ['reportname', 'description'];
if(!empty($value) && $purify && !in_array($key, $noPurifyKeys)){
    $value = vtlib_purify($value);
}
```

The detail view escapes `description` (`Vtiger_Text_UIType::getDisplayValue()` uses `htmlspecialchars`). Other places that output it (list views, related lists, email and workflow merge fields, PDFs, portal) should be checked, or cleaning restored with an explicit per-sink exception instead.

**Recommendation:** port 7.5's `purifyHtmlEventAttributes()` (with `purifyScript` and `purifyJavascriptAlert`), escape attribute output in the `uitypes/*.tpl` templates, and remove the global `noPurifyKeys` exception.

---

### S-5 (Medium, depends on server setup): Cron can run from the web; `cron/` is exposed

`vtigercron.php:41` (berliCRM):

```php
if ((PHP_SAPI === "cgi-fcgi" && empty($_SESSION)) || empty($_SERVER['REMOTE_ADDR']) || (isset($_SESSION["authenticated_user_id"]) && ...)) {
```

`vtigercron.php` never starts a session, so `$_SESSION` is always empty. **When PHP runs as `cgi-fcgi` (php-cgi or mod_fcgid), any unauthenticated HTTP request to `/vtigercron.php` runs all due cron tasks** (workflows, mail scanner, scheduled reports, …). Exception messages are echoed back in the response. 6.5 required `PHP_SAPI === "cli"`, and 7.5 uses `vtigercron_detect_run_in_cli()`.

In addition, 7.5 ships `cron/.htaccess` (`deny from all`) and berliCRM does not. `cron/intimateTaskStatus.php` has no access check and sends delayed-task notification emails when requested. `cron/class.phpmailer.php` and `cron/send_mail.php` are an ancient PHPMailer copy.

**Recommendation:** accept only `PHP_SAPI === 'cli'`, or `cgi-fcgi` together with a shared secret token that is compared in constant time. Add `cron/.htaccess` (and the nginx equivalent to the documentation), and delete the unused `cron/*.php` mailer copies.

---

### S-6 (Medium): Outdated PHPMailer still in use

| Location | Version | Used by |
|---|---|---|
| `modules/Emails/class.phpmailer.php` | **5.2.6** (as in 6.5; 7.5 ships 5.2.27) | `forgotPassword.php:13` |
| `cron/class.phpmailer.php` | legacy (≈2003 code base) | `cron/send_mail.php`, `cron/intimateTaskStatus.php` |
| `modules/Emails/PHPMailer/src/` | 6.1.4 | fallback for `vtlib/Vtiger/Mailer.php` when `vendor/autoload.php` is missing |
| `vendor/` (Composer) | not pinned (`"*"`, and missing from `composer.lock`) | `vtlib/Vtiger/Mailer.php` |

PHPMailer 5.2.6 is older than the 5.2.18 remote-code-execution fix (CVE-2016-10033) and several later 5.2.x security fixes. Its risk depends on whether an attacker can influence the sender address, but it should not be shipped. The bundled 6.1.4 fallback is older than the 6.1.6 and 6.5.0 security releases. `modules/Emails/PHPMailer/get_oauth_token.php`, a PHPMailer example script, is also shipped in the web root.

**Recommendation:** switch `forgotPassword.php` to `Vtiger_Mailer`, delete `modules/Emails/class.phpmailer.php`, `cron/class.phpmailer.php` and `get_oauth_token.php`, and pin PHPMailer ≥ 6.9 in Composer.

---

### S-7 (Low): Password hashing

| | berliCRM | 7.5 |
|---|---|---|
| Default for new passwords | `SHA512` (`crypt()` with `$6$`, default 5,000 rounds, salt from `mt_rand()`) | `PHASH` = `password_hash(PASSWORD_DEFAULT)` (bcrypt) |
| Verification | `crypt($pw, $stored) != $stored` (not constant-time) | `password_verify()` |
| Legacy `MD5` / `PHP5.3MD5` hashes (salt from the first 2 characters of the username) | still accepted, never upgraded | still accepted, never upgraded |

`modules/Users/Users.php:277`. **Recommendation:** adopt `password_hash()`/`password_verify()`, and upgrade legacy hashes at the next successful login (`password_needs_rehash()`).

---

### S-8 (Low): No check that a record belongs to the requested module

7.5's `Vtiger_Save_Action::checkPermission()` rejects a request whose `record` belongs to a different module (`getSalesEntityType($record) !== $moduleName`). It also checks against `source_module` through `requiresPermission()`. berliCRM only calls `isPermitted($moduleName, 'Save', $record)`. Port the 7.5 check so a record cannot be written through another module's handler, whose field set and permissions differ.

---

### 4.9 Security checks with no gap found

| Area | Result |
|---|---|
| CSRF (csrf-magic) | Present in both; berliCRM has the same library, minus 7.5's small `IP_ADDRESS` guard |
| Session fixation | Both call `session_regenerate_id(true)` on login (`modules/Users/actions/Login.php:29`) |
| Users save privilege escalation | berliCRM has its own guards for `is_admin`, `roleid`, `user_name`, `status` in `Users_Save_Action`/`Users_SaveAjax_Action`, equivalent to 7.5 |
| Image upload | berliCRM's validation is **stronger** than 7.5's (see §5) |
| Calendar feed (`Calendar_Feed_Action`) | Dates are normalised through `DateTimeField`; behaviour matches 7.5 |
| CSV formula injection in exports | Not handled in either version |

---

## 5. Areas where berliCRM is ahead of 7.5

1. **Newer libraries** (see §6): Smarty 4.1.0, HTMLPurifier 4.14.0, TCPDF 6.4.4, CKEditor 4.14.1, ADOdb 5.23-dev.
2. **Image-upload validation** (`vtlib/Vtiger/Functions.php:605`, `validateImage()`): requires `is_uploaded_file()`, a clean upload error code and a positive size; allows only `jpg/jpeg/png/gif` extensions; checks the real type with `finfo` (the type the browser claims is ignored); checks the structure with `getimagesize()`; and **re-encodes every image in place with GD**, which removes metadata and polyglot payloads. 7.5 trusts the browser-supplied type for its first check, scans for `<?`, and re-encodes only JPEG.
3. **Brute-force protection** on the web and webservice logins (7.5 has none). It needs rework to prevent the lockout abuse described in S-3.
4. **Login history** for webservice logins (`WebService Login` rows in `vtiger_loginhistory`).
5. **PHP 8 readiness** is generally good: `var` declarations removed, `split()` compatibility shim, `get_magic_quotes_gpc()` calls removed, constructors modernised (see §7).
6. **Webservice session handling** refactored (`include/Webservices/SessionManager.php`) so an already-active session is not reconfigured.

---

## 6. Third-party libraries

| Library | berliCRM | vtiger 7.5.0 | vtiger 6.5.0 | Comment |
|---|---|---|---|---|
| Smarty | **4.1.0** | 3.1.39 | 3.1.7 | berliCRM ahead |
| HTMLPurifier | **4.14.0** (`libraries/htmlpurifier`) | 4.10.0 (`libraries/htmlpurifier410`) | 3.3.0 | berliCRM ahead |
| ADOdb | **5.23.0-dev** | 5.21.2 | 5.19 | berliCRM ahead (unreleased dev snapshot) |
| TCPDF | **6.4.4** | 4.6.012 | 4.6.012 | berliCRM ahead |
| CKEditor | **4.14.1** | 4.3.1 | 4.3.1 | berliCRM ahead (CKEditor 4 is end-of-life upstream) |
| jQuery (core) | 1.7 | **2.2.4** | 1.7 | berliCRM behind; both are affected by known jQuery XSS advisories fixed in 3.5.0 |
| Bootstrap | 2.0.1 / 2.1.0 | 1.5.0 / 2.0.1 / 2.1.0 (+ 3.x in v7 layout) | 2.0.1 / 2.1.0 | both outdated |
| PHPMailer (legacy) | 5.2.6 | **5.2.27** | 5.2.6 | berliCRM behind (S-6) |
| PHPMailer (namespaced) | 6.1.4 bundled + Composer `*` | — | — | berliCRM only |
| PHPExcel | 1.7.7 | 1.7.7 | 1.7.7 | abandoned upstream (successor: PhpSpreadsheet) |
| Zend (Gdata, Json, …) | legacy ZF1 | legacy ZF1 | legacy ZF1 | same |
| csrf-magic | same as 7.5 minus one guard | — | — | — |

**Composer dependencies (berliCRM only):** `horstoeko/zugferd`, `symfony/intl`, `league/oauth2-client`, `stevenmaguire/oauth2-keycloak`, `phpmailer/phpmailer`, `thenetworg/oauth2-azure`, `webklex/php-imap`, `phpunit/phpunit`. See B-4 for the lock-file problem.

---

## 7. PHP 8 compatibility

Every `.php` file was syntax-checked with PHP 8.4 (`php -n -l`):

| Code base | PHP files | Parse failures on PHP 8.4 |
|---|---|---|
| berliCRM | 4,472 | **2** |
| vtiger 7.5.0 | 4,338 | 1 |

**berliCRM failures:**

```
modules/Calendar/iCal/iCalendar_properties.php:704   return ($value{0} != '-');
modules/Calendar/iCal/iCalendar_rfc2445.php:134      $ch = $value{$i};
```

Curly-brace string offsets were removed in PHP 8.0. These files are loaded by `modules/Calendar/actions/ExportData.php` (**iCal export**), `modules/Calendar/views/Import.php` (**iCal import**), `modules/Calendar/iCalExport.php` and `modules/Calendar/iCalImport.php`. On PHP 8 these features stop with a fatal parse error. 7.5 already uses `$value[0]`. **Fix:** replace `{…}` with `[…]` in both files.

**vtiger 7.5 failure** (for context only): `modules/Reports/ReportRun.php:3348`, "Duplicate declaration of static variable", which is a compile error from PHP 8.3 onwards.

Both code bases still declare PHP ≥ 5.4 as the minimum in the installer checks (`modules/Install/models/Utils.php`). berliCRM should update its installer requirement to the PHP versions it actually supports.

---

## 8. berliCRM-specific defects and concerns

### B-1 (Medium): Cron-timeout alerts hard-coded to a vendor address

`vtigercron.php:76`:

```php
$cronMailNotificationEmail = 'mb@crm-now.de';
```

This value **overrides** the configurable `notification_email` parameter. `modules/Settings/CronTasks/models/Config.php:20` also uses `mb@crm-now.de` as that parameter's default. Whenever a cron task runs for more than 24 hours and a sender address is configured, every installation emails its **site URL and cron task name** to this external address. Customers are not told, so this is a privacy/GDPR concern.

**Fix:** remove the hard-coded assignment and use the configured value, with an empty default.

### B-2 (Medium): OAuth2 callback uses a hard-coded test host

`OAuth2-Mail/callback.php:35`:

```php
'redirectUri' => 'https://alexberli48.i1.crm-now.de/OAuth2-Mail/callback.php',
```

The authorization link (`modules/Settings/Vtiger/actions/CreateoAuthLink.php:38`) uses `$site_URL.'OAuth2-Mail/callback.php'`. The Microsoft identity platform requires the `redirect_uri` in the token request to match the one used for authorization. **Microsoft 365 OAuth2 SMTP setup is therefore expected to fail on every installation except that test host** (not tested live). The callback also sets `display_errors` to on and echoes exception messages.

**Fix:** build the URI from `$site_URL`, and turn off `display_errors`.

### B-3 (Medium): Calendar iCal import/export fails on PHP 8

See §7.

### B-4 (Low): Composer set-up

- `composer.lock` contains only PHPUnit and its dependencies. `phpmailer/phpmailer`, `horstoeko/zugferd`, `symfony/intl`, the OAuth2 clients and `webklex/php-imap` from `composer.json` are missing. `composer install` installs what is in the lock file, so these packages will not be installed.
- `phpunit/phpunit` is under `require`, so test tooling gets installed in production. It belongs in `require-dev`.
- Several packages use the `"*"` version constraint, so builds cannot be reproduced.
- `index.php:14` includes `installComposer.php` on every request. On the first request without `vendor/`, it downloads `https://getcomposer.org/installer` **without the published SHA-384 check** and runs `composer install` through `exec()` as the web-server user.

**Fix:** run `composer update` and commit the resulting lock file, move PHPUnit to `require-dev`, pin version ranges, and move the Composer install step to the deployment or installer documentation (or at least verify the installer checksum).

### B-5 (Low): Update and utility entry points without authentication

- `db_update.php` needs no login and sets `display_errors` to on. Anyone who requests it runs the full schema and module upgrade whenever the installed tag differs from `$current_release_tag`, and sees any errors. Restrict it to the command line or a logged-in admin.
- `modules/Emails/PHPMailer/get_oauth_token.php` (a library example script) is in the web root.
- `Request::validateReferer()` and `WebUI.php` hard-code vendor-specific behaviour (`crm-now.de`, `illuminetic.com`, and forced `https://` together with trusting `X-Forwarded-Host` for the canonical-URL redirect).

### B-6 (Info): Filter owner reassignment

`CustomView_Save_Action` accepts `viewUsersSelect` and saves the filter under another user's ID (`CustomView_Record_Model::save()` uses `newuserid`). Every user can use this "copy filter to another user" feature. Confirm this is intended, because it lets any user create filters that show up in other users' accounts.

---

## 9. Feature comparison

### 9.1 Features in vtiger 7.x that berliCRM lacks

| Area | 7.x feature | Evidence in 7.5 |
|---|---|---|
| UI | New v7 UI, app menu (Marketing/Sales/Support/Inventory/Projects), quick previews | `layouts/v7`, `vtiger_app2tab`, `getAppMenuList()` |
| Dashboard | Multiple dashboard tabs | `vtiger_dashboard_tabs`, `addTab()/renameTab()` |
| Tags | Shared/private tags with Settings UI | `modules/Settings/Tags`, `getAllAccessibleTags()` |
| Data quality | Duplicate prevention, unique fields | `vtiger_tab.allowduplicates`, `vtiger_field.isunique`, `isDuplicatesAllowed()` |
| Inventory | Additional charges, tax regions, compound taxes | `vtiger_inventorycharges`, `vtiger_taxregions`, `getCompoundTaxesInfoForInventoryRecord()` |
| Sales | Potential → Project conversion with field mapping | `modules/Settings/Potentials`, `vtiger_convertpotentialmapping`, `vtws_convertpotential` |
| Collaboration | Rolled-up comments, starred records | `vtiger_rollupcomments_settings`, `getRollupComments()`, `isStarredEnabled()` |
| Email | Email lookup index, reply/reply-all, recipient preferences | `vtiger_emailslookup`, `vtiger_emails_recipientprefs`, `emailReply()` |
| Sharing | Filter sharing to users/groups/roles; report sharing | `vtiger_cv2users/cv2group/cv2role/cv2rs`, `vtiger_report_share*` |
| Customer portal | Portal configured from the CRM, REST-based | `vtiger_customerportal_settings`, `vtiger_customerportal_relatedmoduleinfo` |
| Webforms | File upload fields | `vtiger_webform_file_fields` |
| Calendar | Recurring-event info, Google Calendar mapping | `vtiger_activity_recurring_info`, `vtiger_google_event_calendar_mapping` |
| Files | Public file links, encrypted storage file names | `public.php`, `getFilePublicURL()`, `getEncryptedFileName()` |
| Users | Change username, calendar settings pages | `changeUsername()`, `getCalendarSettingsEditViewUrl()` |
| Webservices | `add_related`, `convertpotential`, `file_retrieve`, `delete_user`, product operation | `include/Webservices/*.php` (see §3.6) |
| Security | Declarative permissions, request parameter validation, extended XSS filter | `requiresPermission()`, `Vtiger_Functions::validateRequestParameters()`, `purifyScript()` |
| Extensions | Extension Store | `pkg/vtiger/modules/ExtensionStore` |
| Localisation | 14 additional language packs (berliCRM ships German only) | `packages/vtiger/optional/*` |

`Vtiger_Functions::validateRequestParameters()` is called in 7.5's `Vtiger_Request` constructor. It validates ID-type parameters and blocks SQL keywords in `keyword`-type parameters. berliCRM has no equivalent.

### 9.2 Features only in berliCRM

| Area | Feature | Location |
|---|---|---|
| Extension modules | 16 modules (see §3.5): Word connector, Mailchimp, CleverReach, softphones, maps, GDPR, distribution lists, global search, list colours, PDF settings, mobile client (crmtogo), … | `pkg/vtiger/modules/*`, `packages/vtiger/*` |
| Customer portal | Two bundled PHP customer portals (German/English), SOAP based | `kundenportal/`, `customerportal/`, `soap/`, `vtigerservice.php` |
| Email | OAuth2 SMTP (Azure/Microsoft 365, Keycloak), IMAP via `webklex/php-imap`, email configurator | `OAuth2-Mail/`, `modules/Settings/EmailConfigurator`, `vtlib/Vtiger/Mailer.php` |
| E-invoicing | ZUGFeRD/Factur-X | `horstoeko/zugferd`, `modules/*/pdfcreator.php` |
| Payments | Swiss QR bill | `swisspdf/` |
| SMS | Nexmo/Vonage webhook | `nexmoWebhookEndpoint.php` |
| Help desk | Mail-converter extensions (ticket updates from replies, attachments stored in the database) | `modules/Settings/MailConverter` |
| Security | Brute-force protection, hardened image upload, hardened Users save | see §5 |
| Operations | Own updater, cron-task configuration and alerting | `db_update.php`, `modules/Settings/CronTasks` |
| SQL tooling | Bundled SQL parser library | `include/QueryParser/` |

---

## 10. Database schema and migration path

### 10.1 Schema format

- berliCRM installs from a SQL dump: `schema/DatabaseSchema.sql` (600 KB, 420 `CREATE TABLE` statements, including `*_seq` tables and module tables).
- vtiger ships an ADOdb XML schema, `schema/DatabaseSchema.xml` (298 core tables in 7.5, 296 in 6.5). Most 7.x schema changes are applied by migration scripts instead.

### 10.2 Core tables missing from berliCRM

- In the 7.5 XML but not in berliCRM's dump: `vtiger_app2tab`, `vtiger_dashboard_tabs`, `vtiger_audit_trial`. berliCRM also dropped `vtiger_audit_trial` from 6.5.
- Tables created by the 7.x migration scripts, none of which exist anywhere in berliCRM:

| Migration step | Size | Tables created |
|---|---|---|
| `650_to_660` | 90 lines | — |
| `660_to_700` | 2,227 lines | `vtiger_activity_recurring_info`, `vtiger_app2tab`, `vtiger_convertpotentialmapping`, `vtiger_customerportal_relatedmoduleinfo`, `vtiger_customerportal_settings`, `vtiger_cv2group`, `vtiger_cv2role`, `vtiger_cv2rs`, `vtiger_cv2users`, `vtiger_dashboard_tabs`, `vtiger_emails_recipientprefs`, `vtiger_emailslookup`, `vtiger_inventorycharges`, `vtiger_inventorychargesrel`, `vtiger_projecttask_status_color`, `vtiger_report_sharegroups`, `vtiger_report_sharerole`, `vtiger_report_sharers`, `vtiger_report_shareusers`, `vtiger_rollupcomments_settings`, `vtiger_taxregions`, `vtiger_wsapp_logs_basic`, `vtiger_wsapp_logs_details` |
| `700_to_701` | 43 lines | `vtiger_mailscanner` changes |
| `701_to_710` | 431 lines | `vtiger_google_event_calendar_mapping`, `vtiger_webform_file_fields` |
| `710_to_711` … `740_to_750` | 21–299 lines each | column, field and data changes |

berliCRM's `modules/Migration/schema/` stops at `640_to_650.php`. berliCRM's own schema changes are applied through `db_update.php` instead.

### 10.3 Implications for a 7.5 upgrade

Running vtiger's 6.5 → 7.5 migration against a berliCRM database would need, at minimum:

1. an audit of the berliCRM-only tables and columns (`berli_*`, `berlicrm_*`, module tables) against the 7.x migration steps;
2. porting the 16 berliCRM modules and the 791 berliCRM-changed files to the 7.x APIs and `layouts/v7`;
3. resolving the 834 files changed on both sides.

That is effectively a re-implementation of berliCRM on 7.5, not an upgrade.

---

## 11. Recommendations and remediation plan

### 11.1 Strategy

Keep the 6.5-based berliCRM code base and **cherry-pick 7.x security fixes**. A full rebase is not realistic. Treat the vtiger 6.5 → 7.5 changes to `modules/Vtiger`, `modules/Users`, `modules/Settings`, `include/`, `includes/` and `vtlib/` as a checklist (see [Appendix 12.3](#123-upstream-security-hunks-not-present-in-berlicrm-heuristic)).

### 11.2 Prioritised actions

| Priority | Action | Findings |
|---|---|---|
| P1 | Call `checkPermission()` on every request; port the `requiresPermission()` framework, or at minimum drop `PriceBooks`/`CustomView`/`Inventory`/`Import` from the skip-list and add owner/admin checks to CustomView delete/save | S-1 |
| P1 | Restore `!== 0` in `validateReferer()` and remove the hard-coded vendor domains | S-2 |
| P1 | Make the login lockout temporary, add a break-glass admin path, use the generic webservice error message | S-3 |
| P1 | Remove the hard-coded `mb@crm-now.de`; fix the OAuth2 `redirectUri` | B-1, B-2 |
| P2 | Port 7.5's `purifyHtmlEventAttributes()`/`purifyScript()`/`purifyJavascriptAlert()`; escape attribute output in `uitypes/*.tpl`; remove `noPurifyKeys` | S-4 |
| P2 | Restrict `vtigercron.php` to the command line (or `cgi-fcgi` plus a secret token); add `cron/.htaccess`; delete legacy cron mailers | S-5 |
| P2 | Remove PHPMailer 5.2.6 and legacy copies; pin PHPMailer ≥ 6.9 | S-6 |
| P2 | Fix the iCal `{}` string offsets for PHP 8 | B-3 |
| P3 | Regenerate `composer.lock`, move PHPUnit to `require-dev`, pin version ranges, remove the page-load Composer installer | B-4 |
| P3 | Restrict `db_update.php` to the command line or admin; remove `get_oauth_token.php` | B-5 |
| P3 | Switch to `password_hash()`/`password_verify()` with rehash on login | S-7 |
| P3 | Port the record-type/module match check in `Vtiger_Save_Action` | S-8 |
| P4 | Upgrade jQuery (1.7 → 3.x, which needs vlayout JavaScript regression testing); plan replacements for CKEditor 4 and PHPExcel | §6 |
| P4 | Evaluate the 7.x features worth backporting (duplicate prevention, tax regions/charges, filter sharing) | §9.1 |

---

## 12. Appendices

### 12.1 Reproduction

```bash
# Upstream releases
curl -L -o vtigercrm7.5.0.tar.gz "https://downloads.sourceforge.net/project/vtigercrm/vtiger%20CRM%207.5.0/Core%20Product/vtigercrm7.5.0.tar.gz"
curl -L -o vtigercrm6.5.0.tar.gz "https://downloads.sourceforge.net/project/vtigercrm/vtiger%20CRM%206.5.0/Core%20Product/vtigercrm6.5.0.tar.gz"
mkdir vt75 vt65 && tar -xzf vtigercrm7.5.0.tar.gz -C vt75 && tar -xzf vtigercrm6.5.0.tar.gz -C vt65

# File lists
(cd berlicrm && git ls-files | sort) > b.files
(cd vt75/vtigercrm && find . -type f | sed 's|^\./||' | sort) > v7.files
(cd vt65/vtigercrm && find . -type f | sed 's|^\./||' | sort) > v6.files
comm -12 b.files v7.files | comm -12 - v6.files > common3.files   # then cmp -s each file pair

# PHP 8 syntax check
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 -P8 php -n -l | grep -E 'Parse error|Fatal error'
```

### 12.2 Security-related functions in 7.5 core that berliCRM does not have

`validateRequestParameters`, `validateRequestParameter`, `validateTypeEmail`, `validateFieldValue`, `getValidationRegex`, `requiresPermission` (framework), `getAllPurified`, `purifyScript`, `purifyJavascriptAlert`, `purifyCkeditorField`, `strip_base64_data`, `stripInlineOffice365Image`, `escapeSqlString`, `realEscapeString`, `escapeCssSpecialCharacters`, `sanitizeElementForInsert`, `sanitizeFileFieldsForIds`, `getEncryptedFileName`, `getFilePublicURL`, `isPasswordStrong`, `isProtectedText`, `toProtectedText`, `fromProtectedText`, `validateImageMetadata` (berliCRM re-encodes images instead), `jwtDecode`.

### 12.3 Upstream security hunks not present in berliCRM (heuristic)

Method: for each PHP file that upstream changed between 6.5 and 7.5 (excluding libraries and translations), take the lines 7.5 **added** that match security-related patterns (`LBL_PERMISSION_DENIED`, `isPermitted`, `vtlib_purify`, `validateWriteAccess`, `validateReadAccess`, `isAdminUser`, `requiresPermission`, `htmlspecialchars`, `purifyHtml`, `escape`, `sanitize`, `intval(`, …), and count how many do not appear word for word in berliCRM's copy.

- Files with security-related upstream additions: **320**
- Files where at least one of those additions is missing in berliCRM: **265**

Top entries (missing / total matching added lines):

| Missing / total | File |
|---|---|
| 23/23 | `include/utils/utils.php` |
| 17/17 | `modules/Reports/ReportRun.php` |
| 17/20 | `modules/Vtiger/helpers/Util.php` |
| 15/16 | `modules/Emails/class.phpmailer.php` |
| 13/13 | `modules/Calendar/actions/ExportData.php` |
| 11/11 | `modules/Vtiger/models/Module.php` |
| 11/25 | `include/utils/InventoryUtils.php` |
| 10/10 | `include/Webservices/LineItem/VtigerInventoryOperation.php` |
| 10/10 | `kcfinder/lib/helper_file.php` |
| 7/10 | `modules/Users/actions/SaveAjax.php` |
| 7/7 | `data/CRMEntity.php` |
| 7/7 | `modules/Calendar/actions/Feed.php` |
| 6/6 | `include/QueryGenerator/QueryGenerator.php` |
| 6/6 | `modules/Vtiger/views/List.php` |
| 5/5 | `kcfinder/core/uploader.php` |
| 5/5 | `modules/Home/models/Module.php` |
| 5/5 | `modules/Users/actions/DeleteAjax.php` |
| 5/5 | `modules/Users/actions/Save.php` |
| 5/5 | `vtlib/Vtiger/Functions.php` |
| 5/6 | `modules/Vtiger/actions/Save.php` |
| 4/4 | `include/Webservices/LineItem/VtigerTaxOperation.php` |
| 4/4 | `includes/runtime/Controller.php` |
| 4/4 | `modules/Calendar/models/Module.php` |
| 4/4 | `modules/CustomView/actions/DeleteAjax.php` |
| 4/4 | `modules/Reports/views/ChartDetail.php` |
| 4/4 | `modules/Reports/views/Detail.php` |
| 4/4 | `modules/Reports/views/Edit.php` |
| 4/4 | `modules/Users/actions/ExportData.php` |
| 4/4 | `modules/Vtiger/models/RelationListView.php` |
| 4/4 | `modules/Vtiger/views/MassActionAjax.php` |
| 4/4 | `pkg/vtiger/modules/Projects/Project/modules/Project/Project.php` |
| 4/4 | `pkg/vtiger/modules/Webforms/modules/Webforms/capture.php` |
| 4/4 | `vtlib/Vtiger/Utils.php` |
| 4/5 | `modules/CustomView/actions/Delete.php` |

Many of these hits are `requiresPermission()` declarations, which only work once the S-1 framework is ported. Others (for example `kcfinder`, `Webforms/capture.php`, `ReportRun.php`, `QueryGenerator.php`) should be reviewed one by one.

### 12.4 Skip-listed handlers without in-body permission checks

These handlers belong to skip-listed modules, and their files contain no `isPermitted`/`hasModulePermission`/`isAdminUser` call. When they are requested with their own module name, nothing checks permissions:

- **Vtiger actions:** `BasicAjax`, `DeleteAjax`, `EditLocksAjax`, `ExportData`, `Mass`, `NoteBook`, `RelationAjax`, `RemoveWidget`, `SaveAjax`, `SaveWidgetPositions`
- **Vtiger views:** `AddNotePad`, `Basic`, `BasicAjax`, `EmailsRelatedModulePopup`, `EmailsRelatedModulePopupAjax`, `Export`, `FindDuplicatesAjax`, `Footer`, `IndexAjax`, `ListAjax`, `MergeRecord`, `MiniListWizard`, `PopupAjax`, `RelatedList`, `ShowTagCloud`, `ShowWidget`, `TagCloudSearchAjax`, `TooltipAjax`, `UI5Embed`
- **CustomView:** actions `Delete`, `DeleteAjax`, `Save`; view `EditAjax`
- **PriceBooks:** actions `RelationAjax`, `Save`, `SaveAjax`; views `Detail`, `Edit`, `Popup`, `PopupAjax`, `ProductPriceBookPopup`, `ProductPriceBookPopupAjax`, `QuickCreateAjax`
- **Home:** views `DashBoard`, `Index`
- **Inventory:** actions `GetTaxes`, `Save`, `SaveAjax`; views `Detail`, `Edit`, `List`, `Popup`, `ProductsPopup`, `ProductsPopupAjax`, `SendEmail`, `ServicesPopup`, `ServicesPopupAjax`, `SubProductsPopup`, `SubProductsPopupAjax`

Some of these are harmless (for example `Footer`, or popups that only list records the user can already see through the query generator), or fail when `module=` is not a real entity module. Each should still be checked, or covered at once by making `checkPermission()` unconditional as in 7.5.

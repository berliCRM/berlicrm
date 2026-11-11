<?php
/**
 * English labels, diagnostics and deployment instructions for Word add-in settings.
 *
 * @package berliCRM.WordAddin
 */

/** @var array<string, string> Server-rendered settings-page translations. */
$languageStrings = array(
    'LBL_WORD_ADDIN' => 'Word Add-in',
    'LBL_INTRO' => 'Set up the berliCRM Word Add-in, download its manifest and deploy it to your Word users.',
    'LBL_SERVER_CHECK' => 'Check server installation',
    'LBL_CRM_URL' => 'Configured CRM address',
    'LBL_MANIFEST' => 'Generate manifest',
    'LBL_SERVER_FILES' => 'Add-in files and web service handlers',
    'LBL_OK' => 'OK',
    'LBL_FILES_MISSING' => 'These files are missing or unreadable. Copy them to the CRM server:',
    'LBL_REGISTERED' => 'Registered correctly',
    'LBL_NOT_REGISTERED' => 'Missing or needs an update',
    'LBL_REGISTER_SERVICES' => 'Set up / repair web services',
    'LBL_REFRESH' => 'Check again',
    'LBL_SETUP_SUCCESS' => 'Both Word web services have been configured.',
    'LBL_CHECK_HINT' => 'This check inspects local files and web service registrations. Also open the following links on a Word workstation to verify HTTPS certificate trust and connectivity.',
    'LBL_OPEN_TASKPANE' => 'Open add-in page',
    'LBL_MANIFEST_HINT' => 'The download generates a manifest for this CRM address, preserving subdirectories and ports. The shipped manifest file remains unchanged; no credentials are included.',
    'LBL_DOWNLOAD_MANIFEST' => 'Download manifest (.xml)',
    'LBL_SETUP_INCOMPLETE' => 'Setup is incomplete. Resolve the issues above before using the add-in in Word.',
    'LBL_WORD_INSTALL' => 'Deploy in Word',
    'LBL_SERVICE_READ' => 'Retrieve customer data for mail merge',
    'LBL_SERVICE_WRITE' => 'Save generated letters in CRM',
    'LBL_HTTPS_MANIFEST' => 'HTTPS address and manifest template',
    'LBL_ASSET_LINKS' => 'Check connectivity from a workstation',
    'LBL_MANIFEST_FOR' => 'Manifest for this CRM installation',
    'LBL_CENTRAL_TAB' => 'Microsoft 365',
    'LBL_WEB_TAB' => 'Word on the web',
    'LBL_WINDOWS_TAB' => 'Windows / without Exchange',
    'LBL_CENTRAL_INSTALL' => 'Central deployment with Microsoft 365',
    'LBL_CENTRAL_STEP_1' => 'Sign in to the Microsoft 365 admin center as the responsible administrator. Open Settings > Integrated apps and upload a custom Office app.',
    'LBL_CENTRAL_STEP_2' => 'Select the downloaded XML manifest. Alternatively, use Add-ins > Deploy Add-in > Upload custom add-in if this option is available.',
    'LBL_CENTRAL_STEP_3' => 'Assign users or groups and complete deployment. After distribution, users can find the add-in in Word under Home/Insert > Add-ins. Distribution may take some time.',
    'LBL_WEB_INSTALL' => 'Individual test in Word on the web',
    'LBL_WEB_STEPS' => 'Open a document in Word on the web. Choose Home/Insert > Add-ins > More options/My Add-ins > Upload My Add-in and select the downloaded manifest. Available menus depend on your Word version and organizational policies.',
    'LBL_WINDOWS_INSTALL' => 'Without Exchange: test catalog for Word on Windows',
    'LBL_WINDOWS_STEP_1' => 'Place the downloaded XML manifest in a shared network folder readable by your test users. Microsoft supports this method for testing.',
    'LBL_WINDOWS_STEP_2' => 'In Word, open File > Options > Trust Center > Trust Center Settings > Trusted Add-in Catalogs. Add the UNC network path and enable Show in Menu.',
    'LBL_WINDOWS_STEP_3' => 'Restart Word. Choose Home/Insert > Add-ins > More options/My Add-ins > Shared Folder and add berliCRM Word Plugin.',
    'LBL_MICROSOFT_GUIDE' => 'Open Microsoft instructions',
    'LBL_FIRST_LOGIN' => 'First sign-in in the add-in',
    'LBL_LOGIN_HINT' => 'Open berliCRM Word Plugin and enter your CRM address, CRM username and personal API key from My Preferences. Users need read access to the desired modules and list views, and permission to create Documents when saving letters to CRM.',
    'LBL_UPDATE_HINT' => 'After changes to the CRM address or manifest metadata, download and update the manifest in Word or Microsoft 365. HTML/JavaScript updates usually only require updating the server files and their cache version.',
    'LBL_WORD_ADDIN_INVALID_URL' => 'The CRM configuration $site_URL must be a valid HTTPS address without credentials, query parameters or a fragment.',
    'LBL_WORD_ADDIN_MANIFEST_ERROR' => 'The shipped manifest template is missing or invalid.',
    'LBL_WORD_ADDIN_DATABASE_ERROR' => 'The web service or menu registration could not be read or saved. Check the CRM server log.',
    'LBL_WORD_ADDIN_FILES_MISSING' => 'The Word web service handlers are missing or unreadable.'
);
/** @var array<string, string> Reserved client-side translations; currently empty. */
$jsLanguageStrings = array();

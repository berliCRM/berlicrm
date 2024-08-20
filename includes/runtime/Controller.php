<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Abstract Controller Class
 */
abstract class Vtiger_Controller {

    protected array $exposedMethods = [];

    public function __construct() { }

    public function loginRequired(): bool {
        return true;
    }

    abstract public function process(Vtiger_Request $request);

    abstract public function validateRequest(Vtiger_Request $request): bool;
    abstract public function preProcess(Vtiger_Request $request): void;
    abstract public function postProcess(Vtiger_Request $request): void;

    protected function exposeMethod(string $name): void {
        if (!in_array($name, $this->exposedMethods)) {
            $this->exposedMethods[] = $name;
        }
    }

    public function isMethodExposed(string $name): bool {
        return in_array($name, $this->exposedMethods);
    }

    public function invokeExposedMethod(string $name, ...$parameters) {
        if (!empty($name) && $this->isMethodExposed($name)) {
            return call_user_func_array([$this, $name], $parameters);
        }
        throw new Exception('LBL_NOT_ACCESSIBLE');
    }
}

/**
 * Abstract Action Controller Class
 */
abstract class Vtiger_Action_Controller extends Vtiger_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function validateRequest(Vtiger_Request $request): bool {
        return $request->validateReadAccess();
    }

    public function preProcess(Vtiger_Request $request): void {
        // pre-processing logic
    }

    protected function preProcessDisplay(Vtiger_Request $request): void {
        // pre-process display logic
    }

    protected function preProcessTplName(Vtiger_Request $request): string {
        return 'Header.tpl';
    }

    public function postProcess(Vtiger_Request $request): void {
        // post-processing logic
    }
}

/**
 * Abstract View Controller Class
 */
abstract class Vtiger_View_Controller extends Vtiger_Action_Controller {

    protected ?Vtiger_Viewer $viewer = null;

    public function __construct() {
        parent::__construct();
    }

    public function getViewer(Vtiger_Request $request): Vtiger_Viewer {
        if (!$this->viewer) {
            global $vtiger_current_version;
            $viewer = new Vtiger_Viewer();
            $viewer->assign('APPTITLE', getTranslatedString('APPTITLE'));
            $viewer->assign('VTIGER_VERSION', $vtiger_current_version);
            if (isset($_SESSION['svn_tag'])) {
				// consider login page (no proper $_SESSION contents exists)
                $viewer->assign('SVN_TAG', $_SESSION['svn_tag']);
            }
            $viewer->assign('MODULE_NAME', $request->getModule());
            $this->viewer = $viewer;
        }
        return $this->viewer;
    }

    public function getPageTitle(Vtiger_Request $request): string {
        if ($request->get('module') == 'Vtiger' && $request->get('parent') == 'Settings') {
            return vtranslate('LBL_CRM_SETTINGS', $request->get('module'));
        }
        elseif ($request->get('view') == 'Login') {
            return vtranslate('LBL_TO_CRM', $request->get('module'));
        }
        else {
            return vtranslate($request->getModule(), $request->get('module'));
        }
    }

    public function preProcess(Vtiger_Request $request, bool $display = true): void {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);

        $signatureUnformatted = $currentUser->get('signature');
        if (strpos($signatureUnformatted, '\n') !== false) {
            $signaturetext = str_replace(['\r\n', '\n'], '', $signatureUnformatted);
        }
        else {
            $signaturetext = $signatureUnformatted;
        }
        $signaturetext = '&lt;br /&gt;' . $signaturetext;
        $viewer->assign('SIGNATURETEXT', $signaturetext);

        $viewer->assign('PAGETITLE', $this->getPageTitle($request));
        $viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
        $viewer->assign('STYLES', $this->getHeaderCss($request));
        $viewer->assign('SKIN_PATH', Vtiger_Theme::getCurrentUserThemePath());
        $viewer->assign('LANGUAGE_STRINGS', $this->getJSLanguageStrings($request));
        $viewer->assign('LANGUAGE', $currentUser->get('language'));

        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    protected function preProcessDisplay(Vtiger_Request $request): void {
        $viewer = $this->getViewer($request);
        $displayed = $viewer->view($this->preProcessTplName($request), $request->getModule());
    }

    public function postProcess(Vtiger_Request $request): void {
        $viewer = $this->getViewer($request);
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer->assign('ACTIVITY_REMINDER', $currentUser->getCurrentUserActivityReminderInSeconds());
        $viewer->view('Footer.tpl');
    }

	/**
	 * Retrieves headers scripts that need to loaded in the page
	 * @param Vtiger_Request $request - request model
	 * @return <array> - array of Vtiger_JsScript_Model
	 */
    public function getHeaderScripts(Vtiger_Request $request): array {
        $headerScriptInstances = [];
        $languageHandlerShortName = Vtiger_Language_Handler::getShortLanguageName();
        $fileName = "libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-$languageHandlerShortName.js";
        if (!file_exists($fileName)) {
            $fileName = "~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-en.js";
        }
        else {
            $fileName = "~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-$languageHandlerShortName.js";
        }
        $jsFileNames = [$fileName];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($jsScriptInstances, $headerScriptInstances);
        return $headerScriptInstances;
    }

    public function checkAndConvertJsScripts(array $jsFileNames): array {
        $fileExtension = 'js';
        $jsScriptInstances = [];
        foreach ($jsFileNames as $jsFileName) {
            $jsScript = new Vtiger_JsScript_Model();

            if (strpos($jsFileName, 'http://') === 0 || strpos($jsFileName, 'https://') === 0) {
                $jsScriptInstances[$jsFileName] = $jsScript->set('src', $jsFileName);
                continue;
            }

            $completeFilePath = Vtiger_Loader::resolveNameToPath($jsFileName, $fileExtension);

            if (file_exists($completeFilePath)) {
                if (strpos($jsFileName, '~') === 0) {
                    $filePath = ltrim(ltrim($jsFileName, '~'), '/');
                    if (substr_count($jsFileName, "~") == 2) {
                        $filePath = "../" . $filePath;
                    }
                }
                else {
                    $filePath = str_replace('.', '/', $jsFileName) . '.' . $fileExtension;
                }

                $jsScriptInstances[$jsFileName] = $jsScript->set('src', $filePath);
            }
            else {
                $fallBackFilePath = Vtiger_Loader::resolveNameToPath(Vtiger_JavaScript::getBaseJavaScriptPath() . '/' . $jsFileName, 'js');
                if (file_exists($fallBackFilePath)) {
                    $filePath = str_replace('.', '/', $jsFileName) . '.js';
                    $jsScriptInstances[$jsFileName] = $jsScript->set('src', Vtiger_JavaScript::getFilePath($filePath));
                }
            }
        }
        return $jsScriptInstances;
    }

    public function checkAndConvertCssStyles(array $cssFileNames, string $fileExtension = 'css'): array {
        $cssStyleInstances = [];
        foreach ($cssFileNames as $cssFileName) {
            $cssScriptModel = new Vtiger_CssScript_Model();

            if (strpos($cssFileName, 'http://') === 0 || strpos($cssFileName, 'https://') === 0) {
                $cssStyleInstances[] = $cssScriptModel->set('href', $cssFileName);
                continue;
            }

            $completeFilePath = Vtiger_Loader::resolveNameToPath($cssFileName, $fileExtension);
            if (file_exists($completeFilePath)) {
                if (strpos($cssFileName, '~') === 0) {
                    $filePath = ltrim(ltrim($cssFileName, '~'), '/');
                    if (substr_count($cssFileName, "~") == 2) {
                        $filePath = "../" . $filePath;
                    }
                } else {
                    $filePath = str_replace('.', '/', $cssFileName) . '.' . $fileExtension;
                    $filePath = Vtiger_Theme::getStylePath($filePath);
                }
                $cssStyleInstances[] = $cssScriptModel->set('href', $filePath);
            }
        }
        return $cssStyleInstances;
    }

	/**
	 * Retrieves css styles that need to loaded in the page
	 * @param Vtiger_Request $request - request model
	 * @return <array> - array of Vtiger_CssScript_Model
	 */
    public function getHeaderCss(Vtiger_Request $request): array {
        return [];
    }

	/**
	 * Function returns the Client side language string
	 * @param Vtiger_Request $request
	 */
    public function getJSLanguageStrings(Vtiger_Request $request): array {
        $moduleName = $request->getModule(false);
        if ($moduleName === 'Settings:Users') {
            $moduleName = 'Users';
        }
        return Vtiger_Language_Handler::export($moduleName, 'jsLanguageStrings');
    }
}
<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'include/Webservices/Query.php';

class Pdfsettings  extends CRMEntity {

	static function checkModuleWriteAccessForCurrentUser($module) {
		global $current_user;
		if (isPermitted($module, 'EditView') == "yes" && vtlib_isModuleActive($module)) {
            return true;
		}
		return false;
	}

	/**
	 * function to check the read access for the current user
	 * @global Users Instance $current_user
	 * @param String $module - Name of the module
	 * @return Boolean
	 */
	static function checkModuleReadAccessForCurrentUser($module) {
		global $current_user;
		if (isPermitted($module, 'DetailView') == "yes" && vtlib_isModuleActive($module)) {
            return true;
		}
		return false;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String $modulename - Module name
	 * @param String $event_type - Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		require_once('include/utils/utils.php');
		global $adb;
		if($event_type == 'module.postinstall') {
			// TODO Handle actions when this module is installed.
			require_once('vtlib/Vtiger/Module.php');
			
			// fill the settings tables 
			$adb->pquery("INSERT INTO `berli_pdf_fields` (`pdffieldid`, `pdffieldname`, `pdftablename`, `quotes_g_enabled`, `quotes_i_enabled`, `po_g_enabled`, `po_i_enabled`, `so_g_enabled`, `so_i_enabled`, `invoice_g_enabled`, `invoice_i_enabled`) VALUES 
			(1, 'Position', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(2, 'OrderCode', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(3, 'Description', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(4, 'Qty', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(5, 'Unit', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(6, 'UnitPrice', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(7, 'Discount', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
			(8, 'Tax', 'crmnow_pdfcolums', 0, 1, 1, 1, 1, 1, 1, 1),
			(9, 'LineTotal', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1)",array());

			$adb->pquery("INSERT INTO `berli_pdfcolums_active` (`pdfcolumnactiveid`, `pdfmodulname`, `pdftaxmode`, `position`, `ordercode`, `description`, `qty`, `unit`, `unitprice`, `discount`, `tax`, `linetotal`) VALUES 
			(1, 'Quotes', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(2, 'Quotes', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(3, 'PurchaseOrder', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(4, 'PurchaseOrder', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(5, 'SalesOrder', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(6, 'SalesOrder', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(7, 'Invoice', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
			(8, 'Invoice', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled')",array());

			$adb->pquery("INSERT INTO `berli_pdfcolums_sel` (`pdfcolumnselid`, `pdfmodul`, `pdftaxmode`, `position`, `ordercode`, `description`, `qty`, `unit`, `unitprice`, `discount`, `tax`, `linetotal`) VALUES 
			(1, 'Quotes', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(2, 'Quotes', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(3, 'PurchaseOrder', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(4, 'PurchaseOrder', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(5, 'SalesOrder', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(6, 'SalesOrder', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(7, 'Invoice', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
			(8, 'Invoice', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked')",array());

			$adb->pquery("INSERT INTO `berli_pdfconfiguration` (`pdfid`, `pdfmodul`, `fontid`, `fontsizebody`, `fontsizeheader`, `fontsizefooter`, `fontsizeaddress`, `dateused`, `spaceheadline`, `summaryradio`, `gprodname`, `gproddes`, `gprodcom`, `iprodname`, `iproddes`, `iprodcom`, `pdflang`, `footerradio`, `logoradio`, `pageradio`, `owner`, `ownerphone`, `poname`, `clientid`, `carrier`) VALUES 
			(0, 'Quotes', 0, 8, 8, 7, 9, 1, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'false', 'false', 'false', 'false', 'false', 'false', 'false', 'false'),
			(1, 'PurchaseOrder', 0, 8, 8, 7, 9, 1, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'true', 'true', 'true', 'true', 'true', 'false', 'false', 'false'),
			(2, 'SalesOrder', 0, 8, 8, 7, 9, 1, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'true', 'true', 'true', 'false', 'false', 'false', 'false', 'false'),
			(3, 'Invoice', 0, 8, 8, 7, 9, 0, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'false')",array());

			$adb->pquery("INSERT INTO `berli_pdffonts` (`fontid`, `tcpdfname`, `namedisplay`) VALUES 
			(0, 'dejavusans', 'Dejavu Sans'),
			(1, 'dejavusansb', 'Dejavu Sans Bold'),
			(2, 'dejavusansi', 'Dejavu Sans Italic'),
			(3, 'dejavusansbi', 'Dejavu Sans Bold Italic'),
			(4, 'dejavusanscondensed', 'Dejavu Sans Condensed'),
			(5, 'dejavusanscondensedb', 'Dejavu Sans Condensed Bold'),
			(6, 'dejavusanscondensedi', 'Dejavu Sans Condensed Italic'),
			(7, 'dejavusanscondensedbi', 'Dejavu Sans Condensed Bold Italic'),
			(8, 'dejavusans-extralight', 'Dejavu Sans Extra Light'),
			(9, 'dejavusansi', 'Dejavu Sans Italic'),
			(10, 'dejavusansmono', 'Dejavu Sans Mono'),
			(11, 'dejavusansmonob', 'Dejavu Sans Mono Bold'),
			(12, 'dejavusansmonoi', 'Dejavu Sans Mono Italic'),
			(13, 'dejavusansmonobi', 'Dejavu Sans Bold Italic'),
			(14, 'dejavuserif', 'Dejavu Serif'),
			(15, 'dejavuserifb', 'Dejavu Serif Bold'),
			(16, 'dejavuserifi', 'Dejavu Serif Italic'),
			(17, 'dejavuserifbi', 'Dejavu Serif Bold Italic'),
			(18, 'dejavuserifcondensed', 'Dejavu Serif Condensed'),
			(19, 'dejavuserifcondensedb', 'Dejavu Serif Condensed Bold'),
			(20, 'dejavuserifcondensedi', 'Dejavu Serif Condensed Italic'),
			(21, 'dejavuserifcondensedbi', 'Dejavu Serif Condensed Bold Italic'),
			(22, 'vera', 'Vera'),
			(23, 'verab', 'Vera Bold'),
			(24, 'verai', 'Vera Italic'),
			(25, 'verabi', 'Vera Bold Italic'),
			(26, 'freesans', 'Free Sans'),
			(27, 'freesansb', 'Free Sans Bold'),
			(28, 'freesansi', 'Free Sans Italic'),
			(29, 'freesansbi', 'Free Sans Bold Italic'),
			(30, 'freeserif', 'Free Serif'),
			(31, 'freeserifb', 'Free Serif Bold'),
			(32, 'freeserifi', 'Free Serif Italic'),
			(33, 'freeserifbi', 'Free Serif Bold Italic'),
			(34, 'helvetica', 'Helvetica'),
			(35, 'helveticab', 'Helvetica Bold'),
			(36, 'helveticai', 'Helvetica Italic'),
			(37, 'helveticabi', 'Helvetica Bold Italic')",array());

			$adb->pquery("INSERT INTO `berli_pdfsettings` (`pdfieldid`, `pdffieldname`, `pdfeditable`, `pdfmodul`) VALUES 
			(1, 'pdflang', 0, 'Quotes'),
			(2, 'fontid', 0, 'Quotes'),
			(3, 'fontsizeheader', 0, 'Quotes'),
			(4, 'fontsizeaddress', 0, 'Quotes'),
			(5, 'fontsizebody', 0, 'Quotes'),
			(6, 'fontsizefooter', 0, 'Quotes'),
			(7, 'logoradio', 0, 'Quotes'),
			(8, 'dateused', 0, 'Quotes'),
			(9, 'spaceheadline', 0, 'Quotes'),
			(10, 'footerradio', 0, 'Quotes'),
			(11, 'pageradio', 0, 'Quotes'),
			(12, 'summaryradio', 0, 'Quotes'),
			(13, 'gprodname', 0, 'Quotes'),
			(14, 'gproddes', 0, 'Quotes'),
			(15, 'gprodcom', 0, 'Quotes'),
			(16, 'iprodname', 0, 'Quotes'),
			(17, 'iproddes', 0, 'Quotes'),
			(18, 'iprodcom', 0, 'Quotes'),
			(19, 'gcolumns', 0, 'Quotes'),
			(20, 'icolumns', 0, 'Quotes'),
			(21, 'owner', 0, 'Quotes'),
			(22, 'ownerphone', 0, 'Quotes'),
			(23, 'pdflang', 0, 'Invoice'),
			(24, 'fontid', 0, 'Invoice'),
			(25, 'fontsizeheader', 0, 'Invoice'),
			(26, 'fontsizeaddress', 0, 'Invoice'),
			(27, 'fontsizebody', 0, 'Invoice'),
			(28, 'fontsizefooter', 0, 'Invoice'),
			(29, 'logoradio', 0, 'Invoice'),
			(30, 'poname', 0, 'Invoice'),
			(31, 'spaceheadline', 0, 'Invoice'),
			(32, 'footerradio', 0, 'Invoice'),
			(33, 'pageradio', 0, 'Invoice'),
			(34, 'summaryradio', 0, 'Invoice'),
			(35, 'gprodname', 0, 'Invoice'),
			(36, 'gproddes', 0, 'Invoice'),
			(37, 'gprodcom', 0, 'Invoice'),
			(38, 'iprodname', 0, 'Invoice'),
			(39, 'iproddes', 0, 'Invoice'),
			(40, 'iprodcom', 0, 'Invoice'),
			(41, 'gcolumns', 0, 'Invoice'),
			(42, 'icolumns', 0, 'Invoice'),
			(43, 'owner', 0, 'Invoice'),
			(44, 'ownerphone', 0, 'Invoice'),
			(45, 'pdflang', 0, 'PurchaseOrder'),
			(46, 'fontid', 0, 'PuchaseOrder'),
			(47, 'fontsizeheader', 0, 'PuchaseOrder'),
			(48, 'fontsizeaddress', 0, 'PuchaseOrder'),
			(49, 'fontsizebody', 0, 'PuchaseOrder'),
			(50, 'fontsizefooter', 0, 'PuchaseOrder'),
			(51, 'logoradio', 0, 'PuchaseOrder'),
			(52, 'dateused', 0, 'PuchaseOrder'),
			(53, 'spaceheadline', 0, 'PuchaseOrder'),
			(54, 'footerradio', 0, 'PuchaseOrder'),
			(55, 'pageradio', 0, 'PuchaseOrder'),
			(56, 'summaryradio', 0, 'PuchaseOrder'),
			(57, 'gprodname', 0, 'PuchaseOrder'),
			(58, 'gproddes', 0, 'PuchaseOrder'),
			(59, 'gprodcom', 0, 'PuchaseOrder'),
			(60, 'iprodname', 0, 'PuchaseOrder'),
			(61, 'iproddes', 0, 'PuchaseOrder'),
			(62, 'iprodcom', 0, 'PuchaseOrder'),
			(63, 'gcolumns', 0, 'PuchaseOrder'),
			(64, 'icolumns', 0, 'PuchaseOrder'),
			(65, 'owner', 0, 'PuchaseOrder'),
			(66, 'ownerphone', 0, 'PuchaseOrder'),
			(67, 'poname', 0, 'PuchaseOrder'),
			(68, 'clientid', 0, 'PuchaseOrder'),
			(69, 'carrier', 0, 'PuchaseOrder'),
			(70, 'pdflang', 0, 'SalesOrder'),
			(71, 'fontid', 0, 'SalesOrder'),
			(72, 'fontsizeheader', 0, 'SalesOrder'),
			(73, 'fontsizeaddress', 0, 'SalesOrder'),
			(74, 'fontsizebody', 0, 'SalesOrder'),
			(75, 'fontsizefooter', 0, 'SalesOrder'),
			(76, 'logoradio', 0, 'SalesOrder'),
			(77, 'dateused', 0, 'SalesOrder'),
			(78, 'spaceheadline', 0, 'SalesOrder'),
			(79, 'footerradio', 0, 'SalesOrder'),
			(80, 'pageradio', 0, 'SalesOrder'),
			(81, 'summaryradio', 0, 'SalesOrder'),
			(82, 'gprodname', 0, 'SalesOrder'),
			(83, 'gproddes', 0, 'SalesOrder'),
			(84, 'gprodcom', 0, 'SalesOrder'),
			(85, 'iprodname', 0, 'SalesOrder'),
			(86, 'iproddes', 0, 'SalesOrder'),
			(87, 'iprodcom', 0, 'SalesOrder'),
			(88, 'gcolumns', 0, 'SalesOrder'),
			(89, 'icolumns', 0, 'SalesOrder'),
			(90, 'owner', 0, 'SalesOrder'),
			(91, 'ownerphone', 0, 'SalesOrder'),
			(92, 'paperf', 0, 'Quotes'),
			(93, 'paperf', 0, 'Invoice'),
			(94, 'paperf', 0, 'PuchaseOrder'),
			(95, 'paperf', 0, 'SalesOrder')
			",array());
			
			// Mark the module as custom module
			$adb->pquery('UPDATE vtiger_tab SET customized=1 WHERE name=?', array($moduleName));
			
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} 
		else if($event_type == 'module.postupdate') {
			// TODO Handle actions when this module is updated.
			// check for table trouble caused by faulty version
			require_once('vtlib/Vtiger/Module.php');
			$tablecontentsResult = $adb->pquery("SELECT * FROM berli_pdf_fields",array());
			$numOfRows = $adb->num_rows($tablecontentsResult);
			if ($numOfRows == 0) {
				
				// fill the settings tables 
				$adb->pquery("INSERT INTO `berli_pdf_fields` (`pdffieldid`, `pdffieldname`, `pdftablename`, `quotes_g_enabled`, `quotes_i_enabled`, `po_g_enabled`, `po_i_enabled`, `so_g_enabled`, `so_i_enabled`, `invoice_g_enabled`, `invoice_i_enabled`) VALUES 
				(1, 'Position', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(2, 'OrderCode', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(3, 'Description', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(4, 'Qty', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(5, 'Unit', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(6, 'UnitPrice', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(7, 'Discount', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1),
				(8, 'Tax', 'crmnow_pdfcolums', 0, 1, 1, 1, 1, 1, 1, 1),
				(9, 'LineTotal', 'crmnow_pdfcolums', 1, 1, 1, 1, 1, 1, 1, 1)",array());

				$adb->pquery("INSERT INTO `berli_pdfcolums_active` (`pdfcolumnactiveid`, `pdfmodulname`, `pdftaxmode`, `position`, `ordercode`, `description`, `qty`, `unit`, `unitprice`, `discount`, `tax`, `linetotal`) VALUES 
				(1, 'Quotes', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(2, 'Quotes', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(3, 'PurchaseOrder', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(4, 'PurchaseOrder', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(5, 'SalesOrder', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(6, 'SalesOrder', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(7, 'Invoice', 'group', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled'),
				(8, 'Invoice', 'individual', '', '', 'disabled', 'disabled', '', 'disabled', '', 'disabled', 'disabled')",array());

				$adb->pquery("INSERT INTO `berli_pdfcolums_sel` (`pdfcolumnselid`, `pdfmodul`, `pdftaxmode`, `position`, `ordercode`, `description`, `qty`, `unit`, `unitprice`, `discount`, `tax`, `linetotal`) VALUES 
				(1, 'Quotes', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(2, 'Quotes', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(3, 'PurchaseOrder', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(4, 'PurchaseOrder', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(5, 'SalesOrder', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(6, 'SalesOrder', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(7, 'Invoice', 'group', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked'),
				(8, 'Invoice', 'individual', 'checked', '', 'checked', 'checked', '', 'checked', '', 'checked', 'checked')",array());

				$adb->pquery("INSERT INTO `berli_pdfconfiguration` (`pdfid`, `pdfmodul`, `fontid`, `fontsizebody`, `fontsizeheader`, `fontsizefooter`, `fontsizeaddress`, `dateused`, `spaceheadline`, `summaryradio`, `gprodname`, `gproddes`, `gprodcom`, `iprodname`, `iproddes`, `iprodcom`, `pdflang`, `footerradio`, `logoradio`, `pageradio`, `owner`, `ownerphone`, `poname`, `clientid`, `carrier`) VALUES 
				(0, 'Quotes', 0, 8, 8, 7, 9, 1, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'false', 'false', 'false', 'false', 'false', 'false', 'false', 'false'),
				(1, 'PurchaseOrder', 0, 8, 8, 7, 9, 1, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'true', 'true', 'true', 'true', 'true', 'false', 'false', 'false'),
				(2, 'SalesOrder', 0, 8, 8, 7, 9, 1, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'true', 'true', 'true', 'false', 'false', 'false', 'false', 'false'),
				(3, 'Invoice', 0, 8, 8, 7, 9, 0, 0, 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'de_de', 'true', 'true', 'true', 'true', 'true', 'true', 'true', 'false')",array());

				$adb->pquery("INSERT INTO `berli_pdffonts` (`fontid`, `tcpdfname`, `namedisplay`) VALUES 
				(0, 'dejavusans', 'Dejavu Sans'),
				(1, 'dejavusansb', 'Dejavu Sans Bold'),
				(2, 'dejavusansi', 'Dejavu Sans Italic'),
				(3, 'dejavusansbi', 'Dejavu Sans Bold Italic'),
				(4, 'dejavusanscondensed', 'Dejavu Sans Condensed'),
				(5, 'dejavusanscondensedb', 'Dejavu Sans Condensed Bold'),
				(6, 'dejavusanscondensedi', 'Dejavu Sans Condensed Italic'),
				(7, 'dejavusanscondensedbi', 'Dejavu Sans Condensed Bold Italic'),
				(8, 'dejavusans-extralight', 'Dejavu Sans Extra Light'),
				(9, 'dejavusansi', 'Dejavu Sans Italic'),
				(10, 'dejavusansmono', 'Dejavu Sans Mono'),
				(11, 'dejavusansmonob', 'Dejavu Sans Mono Bold'),
				(12, 'dejavusansmonoi', 'Dejavu Sans Mono Italic'),
				(13, 'dejavusansmonobi', 'Dejavu Sans Bold Italic'),
				(14, 'dejavuserif', 'Dejavu Serif'),
				(15, 'dejavuserifb', 'Dejavu Serif Bold'),
				(16, 'dejavuserifi', 'Dejavu Serif Italic'),
				(17, 'dejavuserifbi', 'Dejavu Serif Bold Italic'),
				(18, 'dejavuserifcondensed', 'Dejavu Serif Condensed'),
				(19, 'dejavuserifcondensedb', 'Dejavu Serif Condensed Bold'),
				(20, 'dejavuserifcondensedi', 'Dejavu Serif Condensed Italic'),
				(21, 'dejavuserifcondensedbi', 'Dejavu Serif Condensed Bold Italic'),
				(22, 'vera', 'Vera'),
				(23, 'verab', 'Vera Bold'),
				(24, 'verai', 'Vera Italic'),
				(25, 'verabi', 'Vera Bold Italic'),
				(26, 'freesans', 'Free Sans'),
				(27, 'freesansb', 'Free Sans Bold'),
				(28, 'freesansi', 'Free Sans Italic'),
				(29, 'freesansbi', 'Free Sans Bold Italic'),
				(30, 'freeserif', 'Free Serif'),
				(31, 'freeserifb', 'Free Serif Bold'),
				(32, 'freeserifi', 'Free Serif Italic'),
				(33, 'freeserifbi', 'Free Serif Bold Italic'),
				(34, 'helvetica', 'Helvetica'),
				(35, 'helveticab', 'Helvetica Bold'),
				(36, 'helveticai', 'Helvetica Italic'),
				(37, 'helveticabi', 'Helvetica Bold Italic')",array());

				$adb->pquery("INSERT INTO `berli_pdfsettings` (`pdfieldid`, `pdffieldname`, `pdfeditable`, `pdfmodul`) VALUES 
				(1, 'pdflang', 0, 'Quotes'),
				(2, 'fontid', 0, 'Quotes'),
				(3, 'fontsizeheader', 0, 'Quotes'),
				(4, 'fontsizeaddress', 0, 'Quotes'),
				(5, 'fontsizebody', 0, 'Quotes'),
				(6, 'fontsizefooter', 0, 'Quotes'),
				(7, 'logoradio', 0, 'Quotes'),
				(8, 'dateused', 0, 'Quotes'),
				(9, 'spaceheadline', 0, 'Quotes'),
				(10, 'footerradio', 0, 'Quotes'),
				(11, 'pageradio', 0, 'Quotes'),
				(12, 'summaryradio', 0, 'Quotes'),
				(13, 'gprodname', 0, 'Quotes'),
				(14, 'gproddes', 0, 'Quotes'),
				(15, 'gprodcom', 0, 'Quotes'),
				(16, 'iprodname', 0, 'Quotes'),
				(17, 'iproddes', 0, 'Quotes'),
				(18, 'iprodcom', 0, 'Quotes'),
				(19, 'gcolumns', 0, 'Quotes'),
				(20, 'icolumns', 0, 'Quotes'),
				(21, 'owner', 0, 'Quotes'),
				(22, 'ownerphone', 0, 'Quotes'),
				(23, 'pdflang', 0, 'Invoice'),
				(24, 'fontid', 0, 'Invoice'),
				(25, 'fontsizeheader', 0, 'Invoice'),
				(26, 'fontsizeaddress', 0, 'Invoice'),
				(27, 'fontsizebody', 0, 'Invoice'),
				(28, 'fontsizefooter', 0, 'Invoice'),
				(29, 'logoradio', 0, 'Invoice'),
				(30, 'poname', 0, 'Invoice'),
				(31, 'spaceheadline', 0, 'Invoice'),
				(32, 'footerradio', 0, 'Invoice'),
				(33, 'pageradio', 0, 'Invoice'),
				(34, 'summaryradio', 0, 'Invoice'),
				(35, 'gprodname', 0, 'Invoice'),
				(36, 'gproddes', 0, 'Invoice'),
				(37, 'gprodcom', 0, 'Invoice'),
				(38, 'iprodname', 0, 'Invoice'),
				(39, 'iproddes', 0, 'Invoice'),
				(40, 'iprodcom', 0, 'Invoice'),
				(41, 'gcolumns', 0, 'Invoice'),
				(42, 'icolumns', 0, 'Invoice'),
				(43, 'owner', 0, 'Invoice'),
				(44, 'ownerphone', 0, 'Invoice'),
				(45, 'pdflang', 0, 'PurchaseOrder'),
				(46, 'fontid', 0, 'PuchaseOrder'),
				(47, 'fontsizeheader', 0, 'PuchaseOrder'),
				(48, 'fontsizeaddress', 0, 'PuchaseOrder'),
				(49, 'fontsizebody', 0, 'PuchaseOrder'),
				(50, 'fontsizefooter', 0, 'PuchaseOrder'),
				(51, 'logoradio', 0, 'PuchaseOrder'),
				(52, 'dateused', 0, 'PuchaseOrder'),
				(53, 'spaceheadline', 0, 'PuchaseOrder'),
				(54, 'footerradio', 0, 'PuchaseOrder'),
				(55, 'pageradio', 0, 'PuchaseOrder'),
				(56, 'summaryradio', 0, 'PuchaseOrder'),
				(57, 'gprodname', 0, 'PuchaseOrder'),
				(58, 'gproddes', 0, 'PuchaseOrder'),
				(59, 'gprodcom', 0, 'PuchaseOrder'),
				(60, 'iprodname', 0, 'PuchaseOrder'),
				(61, 'iproddes', 0, 'PuchaseOrder'),
				(62, 'iprodcom', 0, 'PuchaseOrder'),
				(63, 'gcolumns', 0, 'PuchaseOrder'),
				(64, 'icolumns', 0, 'PuchaseOrder'),
				(65, 'owner', 0, 'PuchaseOrder'),
				(66, 'ownerphone', 0, 'PuchaseOrder'),
				(67, 'poname', 0, 'PuchaseOrder'),
				(68, 'clientid', 0, 'PuchaseOrder'),
				(69, 'carrier', 0, 'PuchaseOrder'),
				(70, 'pdflang', 0, 'SalesOrder'),
				(71, 'fontid', 0, 'SalesOrder'),
				(72, 'fontsizeheader', 0, 'SalesOrder'),
				(73, 'fontsizeaddress', 0, 'SalesOrder'),
				(74, 'fontsizebody', 0, 'SalesOrder'),
				(75, 'fontsizefooter', 0, 'SalesOrder'),
				(76, 'logoradio', 0, 'SalesOrder'),
				(77, 'dateused', 0, 'SalesOrder'),
				(78, 'spaceheadline', 0, 'SalesOrder'),
				(79, 'footerradio', 0, 'SalesOrder'),
				(80, 'pageradio', 0, 'SalesOrder'),
				(81, 'summaryradio', 0, 'SalesOrder'),
				(82, 'gprodname', 0, 'SalesOrder'),
				(83, 'gproddes', 0, 'SalesOrder'),
				(84, 'gprodcom', 0, 'SalesOrder'),
				(85, 'iprodname', 0, 'SalesOrder'),
				(86, 'iproddes', 0, 'SalesOrder'),
				(87, 'iprodcom', 0, 'SalesOrder'),
				(88, 'gcolumns', 0, 'SalesOrder'),
				(89, 'icolumns', 0, 'SalesOrder'),
				(90, 'owner', 0, 'SalesOrder'),
				(91, 'ownerphone', 0, 'SalesOrder'),
				(92, 'paperf', 0, 'Quotes'),
				(93, 'paperf', 0, 'Invoice'),
				(94, 'paperf', 0, 'PuchaseOrder'),
				(95, 'paperf', 0, 'SalesOrder')
				",array());
				
				// Mark the module as custom module
				$adb->pquery('UPDATE vtiger_tab SET customized=1 WHERE name=?', array($moduleName));
			}

		}
	}
}

?>

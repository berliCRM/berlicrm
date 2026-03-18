<?php
/*+***********************************************************************************
 * crm-now stuff																	 *
 *************************************************************************************/

 //inherit display functions of owner uitype
class Vtiger_UserPicklist_UIType extends Vtiger_Owner_UIType {

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/UserPicklist.tpl';
	}
}
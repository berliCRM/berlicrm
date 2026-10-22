<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

//Same as Accounts Detail View
class Contacts_DetailView_Model extends Accounts_DetailView_Model {
	protected $detailViewLinksCache = array();

	/**
	 * Cache detail-view links for this request. The framework requests the same
	 * list during preProcess(), summary rendering and postProcess(), while the
	 * parent implementation also counts every related module.
	 */
	public function getDetailViewLinks($linkParams) {
		$cacheKey = md5(serialize($linkParams));
		if (isset($this->detailViewLinksCache[$cacheKey])) {
			return $this->detailViewLinksCache[$cacheKey];
		}

		$this->detailViewLinksCache[$cacheKey] = parent::getDetailViewLinks($linkParams);
		return $this->detailViewLinksCache[$cacheKey];
	}
}

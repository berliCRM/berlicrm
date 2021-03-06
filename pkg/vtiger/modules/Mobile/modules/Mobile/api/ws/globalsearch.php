<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once dirname(__FILE__) . '/../api/ws/ListModuleRecords.php';
include_once dirname(__FILE__) . '/models/SearchFilter.php';

		
class Mobile_UI_ListModuleRecords extends Mobile_WS_ListModuleRecords {
	
	function cachedModule($moduleName) {
		$modules = $this->sessionGet('_MODULES'); // Should be available post login
		foreach($modules as $module) {
			if ($module->name() == $moduleName) return $module;
		}
		return false;
	}
	
	function getPagingModel(Mobile_API_Request $request) {
		$pagingModel =  Mobile_WS_PagingModel::modelWithPageStart($request->get('page'));
		$pagingModel->setLimit(Mobile::config('Navigation.Limit', 100));
		return $pagingModel;
	}
	
	/** For search capability */
	function cachedSearchFields($module) {
		$cachekey = "_MODULE.{$module}.SEARCHFIELDS";
		return $this->sessionGet($cachekey, false);
	}
	
	function getSearchFilterModel($module, $search) {
		$searchFilter = false;
		if (!empty($search)) {
			$criterias = array('search' => $search, 'fieldnames' => $this->cachedSearchFields($module));
			$searchFilter = Mobile_UI_SearchFilterModel::modelWithCriterias($module, $criterias);
			return $searchFilter;
		}
		return $searchFilter;
	}
	/** END */
	
	function process(Mobile_API_Request $request) {
		$wsResponse = parent::process($request);

		$response = false;
		if($wsResponse->hasError()) {
			$response = $wsResponse;
		} else {
			$wsResponseResult = $wsResponse->getResult();
			$tabid = getTabid($wsResponseResult['module']);
          	$CATEGORY = getParentTabFromModule($wsResponseResult['module']);
			//serch
			$total_record_count = 0;

$query_string = trim($_REQUEST['query_string']);

$curModule = 'Home';
$search_tag  = vtlib_purify($_REQUEST['search_tag']);

function getSearchModules($filter = array()){
	global $adb;
	// vtlib customization: Ignore disabled modules.
	//$sql = 'select distinct vtiger_field.tabid,name from vtiger_field inner join vtiger_tab on vtiger_tab.tabid=vtiger_field.tabid where vtiger_tab.tabid not in (16,29)';
	$sql = 'select distinct vtiger_field.tabid,name from vtiger_field inner join vtiger_tab on vtiger_tab.tabid=vtiger_field.tabid where vtiger_tab.tabid not in (16,29) and vtiger_tab.presence != 1 and vtiger_field.presence in (0,2)';
	// END
	$result = $adb->pquery($sql, array());
	while($module_result = $adb->fetch_array($result)){
		$modulename = $module_result['name'];
		// Do we need to filter the module selection?
		if(!empty($filter) && is_array($filter) && !in_array($modulename, $filter)) {
			continue;
		}
		// END
		if($modulename != 'Calendar'){
			$return_arr[$modulename] = $modulename;
		}else{
			$return_arr[$modulename] = 'Activity';
		}
	}
	return $return_arr;
}
if(isset($query_string) && $query_string != ''){
	// Was the search limited by user for specific modules?
	$search_onlyin = $_REQUEST['search_onlyin'];
	if(!empty($search_onlyin) && $search_onlyin != '--USESELECTED--') {
		$search_onlyin = explode(',', $search_onlyin);
	} else if($search_onlyin == '--USESELECTED--') {
		$search_onlyin = $_SESSION['__UnifiedSearch_SelectedModules__'];
	} else {
		$search_onlyin = array();
	}
	// Save the selection for futur use (UnifiedSearchModules.php)
	$_SESSION['__UnifiedSearch_SelectedModules__'] = $search_onlyin;
	// END
	
	$object_array = getSearchModules($search_onlyin);

	global $adb;
	global $current_user;
	global $theme;
	$theme_path="themes/".$theme."/";
	$image_path=$theme_path."images/";
	
	$search_val = $query_string;
	$search_module = $_REQUEST['search_module'];
	
	
	$i = 0;
	$moduleRecordCount = array();
	foreach($object_array as $module => $object_name){
	
		 
		if ($curModule == 'Home' || ($curModule == $module && !empty($_REQUEST['ajax']))) {
			$focus = CRMEntity::getInstance($module);
			
			if(isPermitted($module,"index") == "yes"){
				$smarty = new vtigerCRM_Smarty;

				global $app_strings;

				$smarty->assign("APP", $app_strings);
				$smarty->assign("THEME", $theme);
				$smarty->assign("IMAGE_PATH",$image_path);
				$smarty->assign("MODULE",$module);
				$smarty->assign("TAG_SEARCH",$search_tag);
				$smarty->assign("SEARCH_MODULE",vtlib_purify($_REQUEST['search_module']));
				$smarty->assign("SINGLE_MOD",$module);
				$smarty->assign("SEARCH_STRING",htmlentities($search_val, ENT_QUOTES, $default_charset));
		
				$listquery = getListQuery($module);
				$oCustomView = '';
	
				$oCustomView = new CustomView($module);
				//Instead of getting current customview id, use cvid of All so that all entities will be found
				//$viewid = $oCustomView->getViewId($module);
				$cv_res = $adb->pquery("select cvid from vtiger_customview where viewname='All' and entitytype=?", array($module));
				$viewid = $adb->query_result($cv_res,0,'cvid');
				
				$listquery = $oCustomView->getModifiedCvListQuery($viewid,$listquery,$module);
	            if ($module == "Calendar"){
	                if (!isset($oCustomView->list_fields['Close'])){
	                	$oCustomView->list_fields['Close']=array ( 'activity' => 'status' );
	                }
	                if (!isset($oCustomView->list_fields_name['Close'])){
	                	$oCustomView->list_fields_name['Close']='status';
	                }
	            }
				
				if($search_module != '' || $search_tag != ''){//This is for Tag search
					$where = getTagWhere($search_val,$current_user->id);					
					$search_msg =  $app_strings['LBL_TAG_SEARCH'];
					$search_msg .=	"<b>".to_html($search_val)."</b>";
				}else{			//This is for Global search
					$where = getUnifiedWhere($listquery,$module,$search_val);
					$search_msg = $app_strings['LBL_SEARCH_RESULTS_FOR'];
					$search_msg .=	"<b>".htmlentities($search_val, ENT_QUOTES, $default_charset)."</b>";
				}
	
				if($where != ''){
					$listquery .= ' and ('.$where.')';
				}
				
				if(!(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '')) {
					$count_result = $adb->query($listquery);
					$noofrows = $adb->num_rows($count_result);
				} else {
					$noofrows = vtlib_purify($_REQUEST['recordCount']);
				}
				$moduleRecordCount[$module]['count'] = $noofrows;
				
				global $list_max_entries_per_page;
				if(!empty($_REQUEST['start'])){
					$start = $_REQUEST['start'];
					if($start == 'last'){
						$count_result = $adb->query( mkCountQuery($listquery));
						$noofrows = $adb->query_result($count_result,0,"count");
						if($noofrows > 0){		
							$start = ceil($noofrows/$list_max_entries_per_page);
						}
					}
					if(!is_numeric($start)){
						$start = 1;
					} elseif($start < 0){
						$start = 1;
					}
					$start = ceil($start);
				}else{
					$start = 1;
				}
				
				$navigation_array = VT_getSimpleNavigationValues($start, $list_max_entries_per_page, $noofrows);
				$limitStartRecord = ($navigation_array['start'] - 1) * $list_max_entries_per_page;
				
				if( $adb->dbType == "pgsql"){
					$listquery = $listquery. " OFFSET $limitStartRecord LIMIT $list_max_entries_per_page";
				}else{
				    $listquery = $listquery. " LIMIT $limitStartRecord, $list_max_entries_per_page";
				}
				$list_result = $adb->query($listquery);
				
				$listview_entries = $adb->pquery($listquery ,array());
				
				
				$entity="select id from vtiger_ws_entity where ismodule=1 and name =?";
			$ws_entity=$adb->pquery($entity, array($module));
			$ws_entity2= $adb->query_result($ws_entity,0,'id');
						
			$filde="select fieldname,entityidfield from vtiger_entityname where modulename =?";
			$ws_entity1=$adb->pquery($filde, array($module));
			$fieldname= $adb->query_result($ws_entity1,0,'fieldname');
			$entityidfield= $adb->query_result($ws_entity1,0,'entityidfield');
			
			$firstname = explode(',', $fieldname);
			
						
				$noofrows = $adb->num_rows($listview_entries);
				$lstresult = array();
				for($i=0;$i<$noofrows;$i++)
				{
					$lstresult[$i]['firstname'] = $adb->query_result($listview_entries,$i,$firstname[0]);
					$lstresult[$i]['lastname'] = $adb->query_result($listview_entries,$i,$firstname[1]);
					$lstresult[$i]['id']= $ws_entity2."x".$adb->query_result($listview_entries,$i,'crmid');
				    
				}
			
			
				//Do not display the Header if there are no entires in listview_entries
				if(count($listview_entries) > 0){
					$display_header = 1;
				}else{
					$display_header = 0;
				}
				
				
				$smarty->assign("LISTHEADER", $listview_header);
				$smarty->assign("LISTENTITY", $lstresult);
				$smarty->assign("DISPLAYHEADER", $display_header);
				$smarty->assign("HEADERCOUNT", count($listview_header));
				
			  $smarty->assign("searchstring", $query_string);
				
				$smarty->assign("SEARCH_CRITERIA","( $noofrows )".$search_msg);
				
				$smarty->display("UnifiedSearchAjax1.tpl");
				
				unset($_SESSION['lvs'][$module]);
				$i++;
			}
		}
	}
	//Added to display the Total record count
	
}
			//end search
			
			
			$viewer = new Mobile_UI_Viewer();
			
			
			
			$viewer->assign('_MODULE', $this->cachedModule($wsResponseResult['module']) );
			$viewer->assign('_RECORDS', Mobile_UI_ModuleRecordModel::buildModelsFromResponse($wsResponseResult['records']) );
			
			
		}
		return $response;
	}

}?>
<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'modules/berlimap/lib/GeoCoder.inc.php';

class berlimap_ListAjax_Action extends Vtiger_BasicAjax_Action {

    public function __construct() {
        parent::__construct();
        $this->exposeMethod('getGeoData');
        $this->exposeMethod('getGeoDistance');
    }

    public function process(Vtiger_Request $request): void {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    /**
     * Function to get related Records count from this relation
     * @param Vtiger_Request $request
     * @return void
     */
    public function getGeoData(Vtiger_Request $request): void {
        global $current_user;
        $db = PearDatabase::getInstance();
        
        $moduleName = $request->getModule();
        $viewId = $request->get('vid');
        $targetModule = $request->get('targetModule');
        
        $queryGenerator = new QueryGenerator($targetModule, $current_user);
        $queryGenerator->initForCustomViewById($viewId);

        $fieldsToSet = $this->getFieldsToSet($targetModule);

        $queryGenerator->setFields(array_merge(['id'], $fieldsToSet));
        $query = $queryGenerator->getQuery();

        $queryResult = $db->pquery($query, []);
        $locations = [];
        $limitwarning = 0;
        
        $GeoCoder = new GeoCoder();
        $targetModuleModel = Vtiger_Module_Model::getInstance($targetModule);
        
        while ($record = $db->fetchByAssoc($queryResult)) {
            set_time_limit(0);
            
            $city = $record[$fieldsToSet['city']] ?? '';
            $code = $record[$fieldsToSet['zip']] ?? '';
            $state = $record[$fieldsToSet['state']] ?? '';
            $country = $record[$fieldsToSet['country']] ?? '';
            $street = $record[$fieldsToSet['street']] ?? '';
            $id = $record[$fieldsToSet['id']] ?? '';
            
            $name = trim(($record[$fieldsToSet['name2']] ?? '') . ' ' . ($record[$fieldsToSet['name1']] ?? ''));
            $lat = $record[$fieldsToSet['latitude']] ?? '';
            $lng = $record[$fieldsToSet['longitude']] ?? '';
            
            if (!empty($lat) && !empty($lng)) {
                $geodata = new GeoCode($lat, $lng);
            } else {
                $geodata = ($targetModule !== 'Locations') ? $GeoCoder->getGeoCode($id, $state, $city, $code, $street, $country) : [];
            }

            if ($geodata === 'JS_OVER_24H_LIMIT') {
                $limitwarning++;
                continue;
            }

            if (empty($geodata)) {
                continue;
            }

            $locations[$id] = $geodata;
            $locations[$id]->targetModule = $targetModule;
            $locations[$id]->name = $name;
            $locations[$id]->targetURL = $targetModuleModel->getDetailViewUrl($id);
            $locations[$id]->iconpath = $this->getLocationIcon($id, $db);
        }

        $results = ['locations' => $locations, 'limitwarning' => $limitwarning];
        $response = new Vtiger_Response();
        $response->setResult($results);
        $response->emit();
    }

    /**
     * Get fields to set based on the target module
     * @param string $targetModule
     * @return array
     */
    private function getFieldsToSet(string $targetModule): array {
        switch ($targetModule) {
            case 'Accounts':
                return [
                    'city' => 'bill_city',
                    'zip' => 'bill_code',
                    'state' => 'bill_state',
                    'country' => 'bill_country',
                    'street' => 'bill_street',
                    'id' => 'accountid',
                    'name1' => 'accountname'
                ];
            case 'Contacts':
                return [
                    'city' => 'mailingcity',
                    'zip' => 'mailingzip',
                    'state' => 'mailingstate',
                    'country' => 'mailingcountry',
                    'street' => 'mailingstreet',
                    'id' => 'contactid',
                    'name1' => 'lastname',
                    'name2' => 'firstname'
                ];
            case 'Leads':
                return [
                    'city' => 'city',
                    'zip' => 'code',
                    'state' => 'state',
                    'country' => 'country',
                    'street' => 'lane',
                    'id' => 'leadid',
                    'name1' => 'lastname',
                    'name2' => 'firstname'
                ];
            default:
                return [];
        }
    }

    /**
     * Calculate the distance between two points
     * @param Vtiger_Request $request
     * @return void
     */
    public function getGeoDistance(Vtiger_Request $request): void {
        $lat1 = $request->get('currentloclatt');
        $lon1 = $request->get('currentloclong');
        $lat2 = $request->get('targetloclatt');
        $lon2 = $request->get('targetloclong');
        $unit = $request->get('unit');

        // Ensure all coordinates are floats
        $lat1 = (float)$lat1;
        $lon1 = (float)$lon1;
        $lat2 = (float)$lat2;
        $lon2 = (float)$lon2;

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        $unit = strtoupper($unit);

        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($unit === "K") {
            $response->setResult(round($miles * 1.609344, 2));
        } elseif ($unit === "N") {
            $response->setResult(round($miles * 0.8684, 2));
        } else {
            $response->setResult(round($miles, 2));
        }
        $response->emit();
    }
    
    /**
     * Get the icon path for a location based on its record ID
     * @param string $recordId
     * @param PearDatabase $db
     * @return string
     */
    private function getLocationIcon(string $recordId, PearDatabase $db): string {
        $defaultIcon = 'modules/berlimap/icons/blueIcon.png';
        $availableIcons = [
            'modules/berlimap/icons/Icon1.png',
            'modules/berlimap/icons/Icon2.png',
            'modules/berlimap/icons/Icon3.png',
            'modules/berlimap/icons/Icon4.png',
            'modules/berlimap/icons/Icon5.png',
            'modules/berlimap/icons/Icon6.png',
            'modules/berlimap/icons/Icon7.png',
            'modules/berlimap/icons/Icon8.png',
            'modules/berlimap/icons/Icon9.png',
            'modules/berlimap/icons/Icon10.png',
        ];
        
        // Query to fetch the location strategy partner
        $query = "SELECT locstratpartner FROM vtiger_locations WHERE locationsid = ?";
        $result = $db->pquery($query, [$recordId]);
        $locstratpartner = '';
        
        if ($db->num_rows($result) > 0) {
            $locstratpartner = $db->query_result($result, 0, 'locstratpartner');
        }
        
        // Determine the icon path based on the location strategy partner
        $icon = $defaultIcon;
        if (!empty($locstratpartner)) {
            switch ($locstratpartner) {
                case 'Cable4':
                    $icon = $availableIcons[0];
                    break;
                case 'Telekom':
                    $icon = $availableIcons[1];
                    break;
                case 'Volkswohnung':
                    $icon = $availableIcons[2];
                    break;
                case 'Bundesimmobilien':
                    $icon = $availableIcons[3];
                    break;
                case 'Haus und Grund':
                    $icon = $availableIcons[4];
                    break;
                case 'GWK1921':
                    $icon = $availableIcons[5];
                    break;
                default:
                    $icon = $defaultIcon;
                    break;
            }
        }

        return $icon;
    }
}

<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_Cache_Connector_Memory {
    private $cache = [];

    public function set($key, $value) {
        $this->cache[$key] = $value;
    }

    public function get($key) {
        return isset($this->cache[$key]) ? $this->cache[$key] : false;
    }

    public function flush() {
        $this->cache = [];
        return true;
    }

    public function delete($key) {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }
    }

    public static function getInstance() {
        static $singleton = null;
        if ($singleton === null) {
            $singleton = new self();
        }
        return $singleton;
    }
}

<?php

namespace CR\tools;

class rest
{

    public $data = false;
    public $url = "http://nourl.com";

    public $postFormat = "json";
    public $returnFormat = "json";

    public $authMode = false;
    public $authModeSettings = false;

    public $debugValues = false;

    public $checkHeader = true;
    public $throwExceptions = true;
    public $header = false;
    public $error = false;

    public function __construct($url = "http://nourl.com")
    {
        $this->url = rtrim($url, '/');
        $this->authModeSettings = new \stdClass;
        $this->debugValues = new \stdClass;

    }

    /**
     * sets AuthMode (jwt, webauth, etc)
     * @param string    jwt, webauth,none
     * @param mixed
     */
    public function setAuthMode($mode = "none", $value = false)
    {
        switch ($mode) {
            case 'jwt':
                $this->authMode = "jwt";
                $this->authModeSettings->token = $value;
                break;

            case 'bearer':
                $this->authMode = "bearer";
                $this->authModeSettings->token = $value;
                break;

            case 'webauth':
                $this->authMode = "webauth";
                $this->authModeSettings->login = $value->login;
                $this->authModeSettings->password = $value->password;

                break;

            default:
                # code...
                break;
        }
    }

    ################################################################################################

    /**
     * makes a GET call
     * @param  array
     * @param  string   get/put/delete
     * @return mixed
     */
    public function get($path, $data = false, $mode = "get")
    {
        $this->resetDebug();
        if (is_string($data)) {
            if (!$data = json_decode($data)) {
                throw new \Exception("data is string but no JSON");
            }
        }

        $url = sprintf("%s?%s", $this->url . $path, ($data ? http_build_query($data) : ""));
        $this->debug("url", $url);

        $curl = curl_init($url);
        $this->setupCurl($curl);

        switch ($mode) {
            case 'delete':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($mode));
                $this->debug("mode", strtoupper($mode));
                break;

            default:
                $this->debug("mode", "GET");
                break;
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
		
		if ($curl_response == "false" || empty($curl_response))  $curl_response = curl_exec($curl); // retry once if API fails
		
        $headers = curl_getinfo($curl);
        curl_close($curl);

        $this->debugEndTimer();

        return $this->returnResult($curl_response, $headers);

    }

    /**
     * makes a DELETE call
     * @param  array
     * @return mixed
     */
    public function delete($path, $data = false)
    {
        return $this->get($path, $data, "delete");
    }

    /**
     * makes a put call
     * @param  array
     * @return mixed
     */
    public function put($path, $data = false)
    {
        return $this->post($path, $data, "put");
    }

    /**
     * does POST
     * @param  [type]
     * @return [type]
     */
    public function post($path, $data, $mode = "post")
    {
        $this->resetDebug();
        $this->debug("url", $this->url . $path);
        if (is_string($data)) {
            if (!$data = json_decode($data)) {
                throw new \Exception("data is string but no JSON");
            }
        }
        $curl_post_data = $data;

        $curl = curl_init($this->url . $path);
        $this->setupCurl($curl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        switch ($mode) {
            case 'put':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                break;

            default:
                curl_setopt($curl, CURLOPT_POST, true);
                break;
        }

        $this->debug("mode", strtoupper($mode));

        if ($this->postFormat == "json") {
            $curl_post_data = json_encode($curl_post_data);
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        $curl_response = curl_exec($curl);
		
		if ($curl_response == "false" || empty($curl_response))  $curl_response = curl_exec($curl); // retry once if API fails
		
        $headers = curl_getinfo($curl);
        curl_close($curl);

        $this->debugEndTimer();
        return $this->returnResult($curl_response, $headers);

    }

    ##########################################################################

    /**
     * [resetDebug description]
     * @return [type]
     */
    private function resetDebug()
    {
        $this->debugValues = new \stdClass;
        $this->error = false;
        $this->debugStartTimer();
    }

    /**
     * set debug keys
     * @param  string
     * @param  mixed
     * @return [type]
     */
    private function debug($key, $value)
    {
        $this->debugValues->$key = $value;
    }

    private function debugStartTimer()
    {
        $this->debugValues->time = $this->microtime_float();
    }

    private function debugEndTimer()
    {
        $this->debugValues->time = $this->microtime_float() - $this->debugValues->time;
    }

    /**
     * prepapres curl with settings amd ein object
     * @param  pointer_curl
     */
    private function setupCurl(&$curl)
    {

        $header = array();

        switch ($this->postFormat) {
            case 'json':
                $header['content'] = 'Content-Type: application/json';
                break;

            default:
                $header['content'] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
                break;
        }

        switch ($this->authMode) {
            case 'webauth':
                curl_setopt($curl, CURLOPT_USERPWD, $this->authModeSettings->login . ":" . $this->authModeSettings->password);
                break;

            case 'jwt':
                $header['token'] = 'X-ACCESS-TOKEN: ' . $this->authModeSettings->token;
                // $header['token'] = 'Authorization: Bearer ' . $this->authModeSettings->token;
                break;

            case 'bearer':
                $header['token'] = 'Authorization: Bearer ' . $this->authModeSettings->token;
                break;

            default:
                # code...
                break;
        }

        $this->debugValues->header = $header;
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    }

    /**
     * returls formated based on given obj settings
     * @param  string
     * @return mixed
     */
    private function returnResult($in, $header = false)
    {
        $this->header = $header;

        if ($this->checkHeader && isset($header["http_code"])) {
            if ($header["http_code"] < 200 || $header["http_code"] >= 300) {
                //error!?
                $this->error = $in;
                $message = var_export($in, true);
                if ($tmp = json_decode($in)) {
                    if (isset($tmp->error->message)) {
                        $message = $tmp->error->message;
                    }
                }
                if ($this->throwExceptions) {
                    throw new \Exception('' . $header["http_code"] . ';' . $message);
                }
                $in = null;

            }

        }

        switch ($this->returnFormat) {
            case 'json':
                return json_decode($in);
                break;

            default:
                return $in;
                break;
        }
        return $in;
    }

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

}

<?php

namespace App\Extensions\Facades\MSG91;

use Exception;

/**
 * Class to handle all http connection to msg91 APIs using curl
 */
class Api
{

    /**
     * Auth key provided by msg91 itself to use their APIs
     *
     * @var $_auth_key
     */
    private $_auth_key;

    /**
     * API host url
     */
    private $_api_host = "http://api.msg91.com/";

    /**
     * HTTP method to call API endpoint
     */
    private $_api_method;

    /**
     * HTTP Full requests timeout in seconds. 0 = No Timeout
     * @var integer
     */
    private $http_timeout = 30;

    /**
     * HTTP connect timeout in seconds.  0 = No Timeout
     * @var integer
     */
    private $http_connect_timeout = 10;
    

    /**
     * Construct a new Api instance
     *
     * @param string $auth_key auth key of your MSG91 application
     */
    public function __construct($auth_key)
    {
        if (empty($auth_key)) {
            throw new Exception("Auth key is necessary to call MSG91 APIs");
        }

        if (!function_exists('curl_version')) {
            throw new Exception("This API Wrapper requires PHP CURL extension to be enabled (http://php.net/curl)");
        }

        $this->_auth_key = $auth_key;
    }


    public function buildUrl($endpoint, $queryString)
    {
        $query_string = $queryString . "&authkey=" . $this->_auth_key;
        return $this->_api_host . $endpoint . "?" . $query_string;
    }


    /**
     * HTTP call using GET method
     *
     * @param string $endpoint API endpoint to call
     * @param string http url queryparameter
     */
    public function get($endpoint, $queryString)
    {
        $this->_api_method = 'GET';
        $url = $this->buildUrl($endpoint, $queryString);

        return $this->call($url);
    }


    /**
     * HTTP call using POST method
     *
     * @param $endpoint API endpoint to call
     * @param string http url queryparameter
     * @param array $smsData http request body
     */
    public function post($endpoint, $queryString, $smsData)
    {
        $this->_api_method = 'POST';
        $url = $this->buildUrl($endpoint, $queryString);

        return $this->call($url, $smsData);
    }


    /**
     * calling the API. This is the main method which actually calling the api
     *
     * @param $url call
     * @param optional $smsData a array for bulk sms
     */
    public function call($url, $smsData = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->_api_method);
        if ($this->_api_method === "POST") {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "authkey: " . $this->_auth_key,
                "content-type: application/json"
            ));
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($smsData));
        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->http_connect_timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->http_timeout);

        $response = curl_exec($curl);
        $response_info = curl_getinfo($curl);
        $response_errno = curl_errno($curl);
        $response_error = curl_error($curl);
        curl_close($curl);

        if (isset($response_info['content_type']) && strpos($response_info['content_type'], 'application/json') !== false) {
            try {
                $data = json_decode($response, true);
            } catch (Exception $e) {
                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new Exception('Error parsing JSON response');
                }
                throw new Exception($e);
            }

            return $data;
        } elseif ($response_errno === 0) {
            return $response;
        } else {
            throw new Exception($response_error. ": " . $response_errno);
        }
    }
}

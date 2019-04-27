<?php

namespace App\Extensions\Facades\MSG91;

use Exception;

/**
 * API wrapper class
 */
class Msg91
{

    /**
     * Instance of Api class
     * @var $_api
     */
    private $_api;

    /**
     * 0 for international,1 for USA, 91 for India
     *
     * @var $_country_code
     */
    private $_country_code = 91;

    /**
     * Receiver will see this as sender's ID
     *
     * @var string $_sender_id
     */
    private $_sender_id = "HireAJackal";

    /**
     * route=1 for promotional, route=4 for transactional SMS
     *
     * @var number $_route
     */
    private $_route = 4;

    /**
     * By default you will get response in string, either way it can be json, xml
     *
     * @var string $_response
     */
    private $_response = "json";

    /**
     * want to schedule the SMS to be sent. Time format could be of your choice you
     * can use Y-m-d h:i:s (2020-01-01 10:10:00) Or Y/m/d h:i:s (2020/01/01 10:10:00)
     * Or you can send unix timestamp (1577873400)
     *
     * @var string $_schTime
     */
    private $_schTime = null;

    /**
     * Time in minutes after which you want to send sms.
     *
     * @var number $_afterMinutes
     */
    private $_afterMinutes = null;

    /**
     * If you wish to encrypt the SMS content (encrypt=1, for encrypted content)
     *
     * @var number $_encrypt
     */
    private $_encrypt = null;

    /**
     * Construct a new msg91 instance
     *
     * @param string $auth_key auth key of your MSG91 application
     * @param array $settings class properties
     */
    public function __construct($auth_key, $settings = null)
    {
        $this->_api = new Api($auth_key);
        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                $this->__set($key, $value);
            }
        }
    }


    /**
     * This method is used to set wrapper config variables, i.e.:
     * $msg_91->__set('_auth_key','long-string')
     *
     * @param string $property one of the wrapper private property
     * @param string $value the value of the property
     * @return mixed
     *
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }


    /**
     * Send message to a single user
     *
     * @param string $mobile
     * @param string $message
     */
    public function sendMessageToOne($mobile, $message)
    {
        $query_string = $this->buildQueryParams($mobile, $message);
        $endpoint = "api/sendhttp.php/";
        return $this->_api->get($endpoint, $query_string);
    }


    /**
     * Send message in bulk
     *
     * @param array $sms
     */
    public function sendBulkMessage($sms)
    {
        $sms = $this->paseMultiArray($sms);
        $sms_data = [
            "sender" => $this->_sender_id,
            "route" => $this->_route,
            "country" => $this->_country_code,
            "sms" => $sms
        ];
        $endpoint = "/api/v2/sendsms";
        $query_string = $this->buildQueryParams();
        return $this->_api->post($endpoint, $query_string, $sms_data);
    }


    /**
     * Convert associative array to url ready query parameter
     *
     * @param optional string $mobile
     * @param optional string $message
     */
    public function buildQueryParams($mobile = null, $message = null)
    {
        $query = [
            "country" => $this->_country_code,
            "sender"  => $this->_sender_id,
            "route"   => $this->_route,
            "afterminutes" => $this->_afterMinutes,
            "schtime" => $this->_schTime,
            "response" => $this->_response,
            "encrypt" => $this->_encrypt,
        ];

        if (! empty($mobile)) {
            $query["mobiles"] = $mobile;
        }

        if (! empty($message)) {
            $query["message"] = $message;
        }

        return http_build_query($query);
    }


    public function paseMultiArray($sms)
    {
        return array_map(function ($a) {
            try {
                $temp = [];
                foreach ($a as $key => $value) {
                    if ($key === "message") {
                        $temp['message'] = $value;
                    } elseif ($key === "phone_number") {
                        if (is_array($value)) {
                            $b = $value;
                        } else {
                            $b[] = $value;
                        }
                        $temp['to'] = $b;
                    }
                }
                return $temp;
            } catch (Exception $e) {
                throw new Exception("Array key mismatch:" + $e);
            }
        }, $sms);
    }
}

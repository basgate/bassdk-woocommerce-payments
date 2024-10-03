<?php
/** 
 * BasgateHelper Class 
 */
require_once __DIR__."/BasgateConstants.php";
if(!class_exists('BasgateHelper')) :
    class BasgateHelper 
    {
        /* 
         * Include timestap with order id 
         */
        public static function getBasgateOrderId($order_id)
        {
            if($order_id && BasgateConstants::APPEND_TIMESTAMP) {
                return BasgateConstants::ORDER_PREFIX.$order_id . '_' . date("YmdHis");
            } else {
                return BasgateConstants::ORDER_PREFIX.$order_id;
        }
    }
        /**
         * Exclude timestap with order id
         */
        public static function getOrderId($order_id)
        {
            $timestamp = BasgateConstants::APPEND_TIMESTAMP;
            if (($pos = strrpos($order_id, '_')) !== false && $timestamp) {
                $order_id = substr($order_id, 0, $pos);
            }
            $orderPrefix = BasgateConstants::ORDER_PREFIX;
            if (substr($order_id, 0, strlen($orderPrefix)) == $orderPrefix) {
                $order_id = substr($order_id, strlen(BasgateConstants::ORDER_PREFIX));
            } 
            return $order_id;
        }
        /**
         * Implements getBasgateURL() with params $url and $isProduction.
         */
        public static function getBasgateURL($url = false, $isProduction = 0)
        {
            if (!$url) return false; 
            if ($isProduction == 1) {
                return BasgateConstants::PRODUCTION_HOST . $url;
            } else {
                return BasgateConstants::STAGING_HOST . $url;
            }
        }
        /**
         * Implements getBasgateURL() with params $url and $isProduction.
         */
        public static function getBasgateSDKURL($url = false, $isProduction = 0)
        {
            if (!$url) return false; 
            if ($isProduction == 1) {
                return BasgateConstants::BASGATE_SDK_URL_PRODUCTION . $url;
            } else {
                return BasgateConstants::BASGATE_SDK_URL_STAGING . $url;
            }
        }
        /**
         * Exclude timestamp with order id pass Environment param
         */
        public static function getTransactionStatusURL($isProduction = 0) 
        {
            if ($isProduction == 1) {
                return BasgateConstants::TRANSACTION_STATUS_URL_PRODUCTION;
            } else {
                return BasgateConstants::TRANSACTION_STATUS_URL_STAGING;
            }
        }
        /**
        * Check and test cURL is working or able to communicate properly with basgate
        */
        public static function validateCurl($transaction_status_url = '')
        {
            if (!empty($transaction_status_url) && function_exists("curl_init")) {
                $ch = curl_init(trim($transaction_status_url));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

                $res = curl_exec($ch);
                curl_close($ch);
                return $res !== false;
            }
            return false;
        }

        public static function getcURLversion()
        {
            if (function_exists('curl_version')) {
                $curl_version = curl_version();
                if (!empty($curl_version['version'])) {
                    return $curl_version['version'];
                }
            }
            return false;
        }

        /* public static function executecUrlOld($apiURL, $requestParamList) //not in use
        {
            $jsonResponse = wp_remote_post(
                $apiURL, array(
                'headers'     => array("Content-Type"=> "application/json"),
                'body'        => json_encode($requestParamList, JSON_UNESCAPED_SLASHES),
                ) 
            );

            //$response_code = wp_remote_retrieve_response_code( $jsonResponse );
            $response_body = wp_remote_retrieve_body($jsonResponse);
            $responseParamList = json_decode($response_body, true);
            $responseParamList['request'] = $requestParamList;
            return $responseParamList;
        }*/

        public static function executecUrl($apiURL, $requestParamList, $method ='POST', $extraHeaders = array()){
            $headers = array("Content-Type"=> "application/json");
            if (!empty($extraHeaders)) {
                $headers = array_merge($headers, $extraHeaders);
            }                
            $args = array(
                'headers' => $headers,
                'body'      => json_encode($requestParamList, JSON_UNESCAPED_SLASHES),
                'method'    => $method,
            );

            $result =  wp_remote_request( $apiURL, $args );
            $response_body = wp_remote_retrieve_body($result);
            return $responseParamList['request'] = json_decode($response_body, true);
        }

    }
endif;
?>
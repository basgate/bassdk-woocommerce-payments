<?php

/** 
 * BasgateHelper Class 
 */
require_once __DIR__ . "/BasgateConstants.php";
if (!class_exists('BasgateHelper')) :
    class BasgateHelper
    {
        /* 
         * Include timestap with order id 
         */
        public static function getBasgateOrderId($order_id)
        {
            if ($order_id && BasgateConstants::APPEND_TIMESTAMP) {
                return BasgateConstants::ORDER_PREFIX . $order_id . '_' . gmdate("YmdHis");
            } else {
                return BasgateConstants::ORDER_PREFIX . $order_id;
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
            return $url;
            // if ($isProduction == 1) {
            //     return BasgateConstants::BASGATE_SDK_URL_PRODUCTION . $url;
            // } else {
            //     return BasgateConstants::BASGATE_SDK_URL_STAGING . $url;
            // }
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


        // public static function executecUrl($apiURL, $requestParamList, $method = 'POST', $extraHeaders = array())
        // {
        //     $headers = array("Content-Type" => "application/json");
        //     if (!empty($extraHeaders)) {
        //         $headers = array_merge($headers, $extraHeaders);
        //     }
        //     $args = array(
        //         'headers' => $headers,
        //         'body'      => $requestParamList,
        //         'method'    => $method,
        //     );

        //     $result =  wp_remote_request($apiURL, $args);
        //     $response_code = wp_remote_retrieve_response_code($result);

        //     if (is_wp_error($result)) {
        //         error_log(
        //             sprintf(
        //                 /* translators: 1: Url, 2: Error code, 3: Error message, 4: Event data. */
        //                 __('executecUrl error for url: %1$s, Error code: %2$s, Error message: %3$s, Data: %4$s', 'bassdk-woocommerce-payments'),
        //                 $apiURL,
        //                 $result->get_error_code(),
        //                 $result->get_error_message(),
        //                 wp_json_encode($args)
        //             )
        //         );
        //         throw new Exception(esc_attr__('Could not retrieve the access token, please try again!!!.', 'bassdk-woocommerce-payments'));
        //     }

        //     if (200 !==  $response_code) {
        //         $error = wp_remote_retrieve_response_message($result);
        //         $resp = wp_remote_retrieve_body($result);
        //         error_log(
        //             sprintf(
        //                 /* translators: 1: Url, 2: Response code, 3: Event data, 4: ErrorMsg ,5:Response Body. */
        //                 __('executecUrl error status!=200 for url: %1$s, Response code: %2$s,Data: %3$s , ErrorMsg: %4$s, Response Body:%5$s', 'bassdk-woocommerce-payments'),
        //                 $apiURL,
        //                 $response_code,
        //                 wp_json_encode($args),
        //                 $error,
        //                 $resp
        //             )
        //         );
        //         throw new Exception(esc_attr__('Could not retrieve the access token, please try again.', 'bassdk-woocommerce-payments'));
        //     } else {
        //         $response_body = wp_remote_retrieve_body($result);
        //         return json_decode($response_body, true);
        //     }
        // }


        // static function httpPost($url, $data, $header)
        // {
        //     try {
        //         $curl = curl_init($url);
        //         curl_setopt($curl, CURLOPT_POST, true);
        //         curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //         curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //         curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        //         curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        //         $response = curl_exec($curl);
        //         $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //         $error = curl_error($curl);

        //         if ($httpCode == 400) {
        //             try {
        //                 $data = json_decode($response, true);
        //                 if (array_key_exists('messages', $data)) {
        //                     wc_add_notice("Error: " . implode(' -- ', $data['messages']), 'error');
        //                 }
        //             } catch (\Throwable $th) {
        //                 //throw $th;
        //             }
        //         } else if ($httpCode != 200) {
        //             $msg = "Return httpCode is {$httpCode} \n"
        //                 . curl_error($curl) . "URL: " . $url;
        //             if ($error) {
        //                 wc_add_notice("Error: " . $error, 'error');
        //             }
        //             error_log(
        //                 sprintf(
        //                     /* translators: 1: Url, 2: Response code, 3: Event data, 4: ErrorMsg. */
        //                     __('executecUrl error status!=200 for url: %1$s, Response code: %2$s,Data: %3$s , ErrorMsg: %4$s', 'bassdk-woocommerce-payments'),
        //                     $url,
        //                     $httpCode,
        //                     $data,
        //                     $error
        //                 )
        //             );
        //             curl_close($curl);
        //             return new Exception(__('Could not retrieve the access token, please try again.', 'bassdk-woocommerce-payments'));
        //             // return $msg;
        //             //return $response;
        //         } else {
        //             curl_close($curl);
        //             return json_decode($response, true);
        //         }
        //     } catch (\Throwable $th) {
        //         return new Exception("ERROR On httpPost :" . $th->getMessage());
        //     }
        // }


        public static function executecUrl($apiURL, $requestParamList, $method = 'POST', $extraHeaders = array())
        {

            self::basgate_log("===== STARTED executecUrl " . $method . " url:" . $apiURL);
            $headers = array("Accept" => "*");
            if (!empty($extraHeaders)) {
                $headers = array_merge($headers, $extraHeaders);
            }
            $args = array(
                'headers' => $headers,
                'body'      => $requestParamList,
                'method'    => $method,
            );

            $result =  wp_remote_request($apiURL, $args);
            $response_code = wp_remote_retrieve_response_code($result);
            $error = wp_remote_retrieve_response_message($result);

            if (is_wp_error($result)) {
                $msg = sprintf(
                    /* translators: 1: Url, 2: Error code, 3: Error message, 4: Event data. */
                    __('executecUrl error for url: %1$s, Error code: %2$s, Error message: %3$s, Data: %4$s', 'bassdk-woocommerce-payments'),
                    $apiURL,
                    $result->get_error_code(),
                    $result->get_error_message(),
                    wp_json_encode($args)
                );
                // error_log($msg);
                BasgateHelper::basgate_log($msg);
                return self::errorResponse($msg);
                // throw new Exception(__('Could not retrieve the access token, please try again!!!.', BasgateConstants::ID));
            }

            $response_body = wp_remote_retrieve_body($result);

            if (200 !==  $response_code) {
                $msg = sprintf(
                    /* translators: 1: Url, 2: Response code, 3: Event data, 4: ErrorMsg ,5:Response Body. */
                    __('executecUrl error status!=200 for url: %1$s, Response code: %2$s,Data: %3$s , ErrorMsg: %4$s, Response Body:%5$s', 'bassdk-woocommerce-payments'),
                    $apiURL,
                    $response_code,
                    wp_json_encode($args),
                    $error,
                    $response_body
                );
                BasgateHelper::basgate_log($msg);

                return self::errorResponse($msg);
            } else {
                $data = json_decode($response_body, true); // Decode JSON response
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return self::errorResponse('Error decoding JSON: ' . json_last_error_msg());
                }
                self::basgate_log("===== executecUrl Success: " . wp_json_encode($data));

                return self::successResponse($data);
            }
        }

        public static function errorResponse($msg)
        {
            self::basgate_log("ERROR errorResponse msg: " . $msg);
            if (!empty($msg)) {
                return array('success' => false, 'error' => $msg);
            } else {
                return array('success' => false, 'error' => 'Something went wrong');
            }
        }

        public static function successResponse($data)
        {
            if (!empty($data)) {
                return array('success' => true, 'body' => $data);
            } else {
                return array('success' => true, 'body' => array());
            }
        }

        static function basgate_log($message)
        {
            $settings = get_option(BasgateConstants::OPTION_DATA_NAME);
            if (!array_key_exists('debug', $settings)) {
                $is_debug = 'yes';
            } else {
                $is_debug = $settings['debug'];
            }

            if ((! defined('WP_DEBUG') || ! WP_DEBUG) && $is_debug == 'no') {
                return; // Only log if WP_DEBUG is enabled
            }

            if ($is_debug == 'yes') {
                error_log($message);
            }

            if (! function_exists('get_filesystem_method')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $log_file = plugin_dir_path(plugin_root()) . 'bassdk-woocommerce-payments.log'; // Specify the log file path
            $timestamp = current_time('Y-m-d H:i:s');
            $log_entry = "[$timestamp] $message\n";

            // Initialize the filesystem
            WP_Filesystem();

            global $wp_filesystem;

            // Check if the file exists
            if ($wp_filesystem->exists($log_file)) {
                $existing_contents = $wp_filesystem->get_contents($log_file);
                $new_contents = $existing_contents . PHP_EOL . $log_entry;
            } else {
                $new_contents = $log_entry;
            }

            $wp_filesystem->put_contents(
                $log_file,
                $new_contents,
                FS_CHMOD_FILE // predefined mode settings for WP files
            );
        }
    }
endif;

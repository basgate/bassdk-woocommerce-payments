<?php
class BasgateConstants{
    CONST TRANSACTION_STATUS_URL_PRODUCTION= "https://api.basgate.com:4950/api/v1/merchant/secure/transaction/status";
    CONST TRANSACTION_STATUS_URL_STAGING= "https://api-tst.basgate.com:4951/api/v1/merchant/secure/transaction/status";

    CONST PRODUCTION_HOST= "https://api.basgate.com:4950/";
    CONST STAGING_HOST= "https://api-tst.basgate.com:4951/";

    CONST ORDER_PROCESS_URL= "api/v1/merchant/secure/transaction/initiate";
    CONST ORDER_STATUS_URL= "api/v1/merchant/secure/transaction/status";
    CONST INITIATE_TRANSACTION_URL= "theia/api/v1/initiateTransaction";
    CONST CHECKOUT_JS_URL= "merchantpgpui/checkoutjs/merchants/MID.js";


    CONST SAVE_BASGATE_RESPONSE= true;
    CONST CHANNEL_ID= "WEB";
    CONST APPEND_TIMESTAMP= true;
    CONST ORDER_PREFIX= "";
    CONST X_REQUEST_ID= "PLUGIN_WOOCOMMERCE_";
    CONST PLUGIN_DOC_URL= "https://basgate.github.io/pages.html";

    CONST MAX_RETRY_COUNT= 3;
    CONST CONNECT_TIMEOUT= 10;
    CONST TIMEOUT= 10;

    CONST LAST_UPDATED= "20241003";
    CONST PLUGIN_VERSION= "0.1.5";
    CONST PLUGIN_VERSION_FOLDER= "015";

    CONST CUSTOM_CALLBACK_URL= "";


    CONST ID= "basgate";
    CONST METHOD_TITLE= "الدفع عبر منصة بس";
    CONST METHOD_DESCRIPTION= "افضل وسيلة منصة تجميع اغلب المحافظ والبنوك";

    CONST TITLE= "Basgate Payment Gateway";
    CONST DESCRIPTION= "افضل وسيلة منصة تجميع اغلب المحافظ والبنوك";

    CONST FRONT_MESSAGE= "Thank you for your order, please click the button below to pay with basgate.";
    CONST NOT_FOUND_TXN_URL= "Something went wrong. Kindly contact with us.";
    CONST BASGATE_PAY_BUTTON= "الدفع عبر منصة بس";
    CONST CANCEL_ORDER_BUTTON= "Cancel order & Restore cart";
    CONST POPUP_LOADER_TEXT= "Thank you for your order. We are now redirecting you to basgate to make payment.";

    CONST TRANSACTION_ID= "<b>Transaction ID:</b> %s";
    CONST BASGATE_ORDER_ID= "<b>Basgate Order ID:</b> %s";

    CONST REASON= " Reason: %s";
    CONST FETCH_BUTTON= "Fetch Status";

    //Success
    CONST SUCCESS_ORDER_MESSAGE= "Thank you for your order. Your payment has been successfully received.";
    CONST RESPONSE_SUCCESS= "Updated <b>STATUS</b> has been fetched";
    CONST RESPONSE_STATUS_SUCCESS= " and Transaction Status has been updated <b>PENDING</b> to <b>%s</b>";
    CONST RESPONSE_ERROR= "Something went wrong. Please again'";

    //Error
    CONST PENDING_ORDER_MESSAGE= "Your payment has been pending!";
    CONST ERROR_ORDER_MESSAGE= "Your payment has been failed!";
    CONST ERROR_SERVER_COMMUNICATION= "It seems some issue in server to server communication. Kindly connect with us.";
    CONST ERROR_CHECKSUM_MISMATCH= "Security Error. Checksum Mismatched!";
    CONST ERROR_AMOUNT_MISMATCH= "Security Error. Amount Mismatched!";
    CONST ERROR_INVALID_ORDER= "No order found to process. Kindly contact with us.";
    CONST ERROR_CURL_DISABLED= "cURL is not enabled properly. Please verify.";
    CONST ERROR_CURL_WARNING= "Your server is unable to connect with us. Please contact to Basgate Support.";

    CONST WEBHOOK_STAGING_URL= "https://api-tst.basgate.com:4951/";
    CONST WEBHOOK_PRODUCTION_URL= "https://api.basgate.com:4950/";

}

?>
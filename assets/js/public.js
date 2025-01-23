
/////////// Basgate SDK for Login ///////////

//#region BAS SDK Client Side 
var isJSBridgeReadyPayments = false
var isBasInDebug = false
var isBasAuthTokenReturned = false

console.log("Start Basgate-ClientSDK Script initBas() - Payments");

function initBas() {
    console.log("initBas() STARTED - Payments");
    window.addEventListener("JSBridgeReady", async (event) => {
        console.log("JSBridgeReady fired");
        isJSBridgeReadyPayments = true
        await getBasConfig();
    }, false);
}

////// FROM ALIBABA SDK //////

function isBasSupperApp(callback) {
    // Invoke directly if JSBridge is already injected
    if (window.JSBridgeReady) {
        callback && callback();
    } else {
        // Otherwise listen to JSBridgeReady event
        document.addEventListener('JSBridgeReady', callback, false);
    }
}


isBasSupperApp(function () {
    alert('bridge ready');
});



// // console.log("isBasSupperApp()");
// const isBasSupperApp = () => {
//     return isJSBridgeReadyPayments;
// }

// // console.log("getBasConfig()");
// /*  @getBasConfig()
//     Dont call this method while your application in init mode
//     return {
//             'status': string,
//             'locale': string,
//             'isInBasSuperApp': bool,
//             'messages': string[],
//             'envType': string,
//         };
// */
// const getBasConfig = async () => {
//     console.log("getBasConfig() STARTED");
//     return window.JSBridge.call('basConfigs').then(function (result) {
//         console.log("basConfigs Result:", JSON.stringify(result));
//         if (result) {
//             if ("isInBasSuperApp" in result) {
//                 isJSBridgeReadyPayments = true;
//             }

//             if ("envType" in result) {
//                 isBasInDebug = result.envType == "stage"
//             }
//             return result;
//         } else {
//             return null
//         }
//     });
// }

// // console.log("getBasAuthCode()");
// const getBasAuthCode = async (clientId) => {
//     if (!isJSBridgeReadyPayments) await initBas();
//     if ("JSBridge" in window) {
//         if (isBasInDebug) console.log("BasSDK getBasAuthCode STARTED")
//         return window.JSBridge.call('basFetchAuthCode',
//             {
//                 clientId: clientId
//             }).then(function (result) {
//                 /****** Response Example ******/
//                 /*
//                 {
//                     "status":1,
//                     "data":{
//                         "auth_id":"FD268ED889B7DFB008093D04809E8B7FC26B821421B278",
//                         "authid":"FD268ED889B7DFB008093D04809E8B7FC26B821421B278",
//                         "openid":"null",
//                         "return_url":"null"},
//                     "messages":["تمت العملية بنجاح"]
//                 }
//                 */
//                 /****** End Response Example ******/
//                 // alert(JSON.stringify(result))
//                 if (isBasInDebug) console.log("BasSDK getBasAuthCode result:", JSON.stringify(result))
//                 if (result) {
//                     isBasAuthTokenReturned = true;
//                     return result;
//                 } else {
//                     return null
//                 }
//             });
//     } else {
//         console.error("JSBridge not Existing in window");
//         return null;
//     }

// }

/****** Response Example ******/
/*{
"merchantId": "",
"orderId": "",
"transactionId": "",
"amount": {
"value": 0,
"currency": "YER"
},
"paymentType": "",
"date": "",
"status":1
}*/
/****** End Response Example ******/
console.log("getBasPayment()");
const getBasPayment = async (data) => {
    if (isBasInDebug) console.log("BasSDK getBasPayment STARTED")
    let paymentParams = {
        "amount": {
            "value": data.amount ?? '0',
            "currency": data.currency ?? 'YER',
        },
        "orderId": data.orderId ?? '111',
        "trxToken": data.trxToken,
        "appId": data.appId
    }
    if (isBasInDebug) console.log("BasSDK getBasPayment Params :", JSON.stringify(paymentParams))
    return window.JSBridge.call('basPayment', paymentParams).then(function (result) {
        if (isBasInDebug) console.log("BasSDK getBasPayment result:", JSON.stringify(result))
        if (result) {
            return result;
        } else {
            return null
        }
    });

}

//#endregion
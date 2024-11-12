(function () {
    jQuery(document).ready(function () {
        try {
            console.log("===== STARTED basgate-check.js basgate_ajax_object :", basgate_ajax_object?.ajaxurl_payments ?? '');

        } catch (error) {
            console.error("===== STARTED basgate-check.js ERROR :", error);

        } var ajaxurl_payments = basgate_ajax_object ? basgate_ajax_object?.ajaxurl_payments : '';
        var nonce_payments = basgate_ajax_object ? basgate_ajax_object?.nonce_payments : '';
        // eslint-disable-next-line
        function basgateCheck() { // jshint ignore:line
            var $ = jQuery;
            console.log("===== STARTED basgateCheck:")
            $.post(ajaxurl_payments, {
                action: 'process_basgate_payments',
                nonce: nonce_payments,
            }, function (data, textStatus) {

                // window.removeEventListener("JSBridgeReady");
                console.log("basgateCheck() textStatus :", textStatus)
                window.eve
                console.log("basgateCheck() data :", data)
            });

        }

        try {
            console.log("===== STARTED basgate-check.js 222")
            window.addEventListener("JSBridgeReady",
                (event) => {
                    console.log("===== basgate-check.js JSBridgeReady READY")
                    try {
                        basgateCheck();
                    } catch (error) {
                        console.error("ERROR window.addEventListener(JSBridgeReady) 111:", error)
                    }
                }, false);
        } catch (error) {
            console.error("ERROR on basgate-check.js:", error)
        }
    });
})();
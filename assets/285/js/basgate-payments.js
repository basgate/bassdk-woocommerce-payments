(function() { 
    if (jQuery(".msg-by-basgate")[0]){
        document.getElementsByClassName('msg-by-basgate')[0].innerHTML = '';
    }
        jQuery(document).ready(function () {
            jQuery( 'body' ).on( 'updated_checkout', function() {
				let str = jQuery("label[for=payment_method_basgate]").html(); 
				/* let res = str.replace(/Basgate/, "");
				 jQuery("label[for=payment_method_basgate]").html(res); */
				jQuery("label[for=payment_method_basgate]").css("visibility","visible");
            });
            
        });
})();
 
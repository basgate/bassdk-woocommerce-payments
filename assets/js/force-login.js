
console.log('===== forceLogin.js STARTED');
window.addEventListener("JSBridgeReady", (event) => {
    alert('JSBridgeReady event fired ');
    console.log('JSBridgeReady event fired ');
    var login_url = 'wp-login.php';
    console.log('===== validate_add_cart_item login_url:' + login_url);
    window.location.href = login_url;
}, false);

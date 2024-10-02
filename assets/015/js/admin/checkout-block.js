const basgatesettings = window.wc.wcSettings.getSetting( 'basgate_data', {} ); 
const basgatelabel = window.wp.htmlEntities.decodeEntities( basgatesettings.title ) || window.wp.i18n.__( 'Basgate WooCommerce Payment Gateway', 'basgate' );
const basgateContent = () => {
    return window.wp.htmlEntities.decodeEntities( basgatesettings.description || '' );
}; 
 const BasgateBlock_Gateway = {
    name: 'basgate',
    label: basgatelabel,
    content: Object( window.wp.element.createElement )( basgateContent, null ),
    edit: Object( window.wp.element.createElement )( basgateContent, null ), 
    canMakePayment: () => true,
    ariaLabel: basgatelabel,
     supports: {
        features: basgatesettings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( BasgateBlock_Gateway ); 
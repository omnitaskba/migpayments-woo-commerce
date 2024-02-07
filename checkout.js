const settings = window.wc.wcSettings.getSetting( 'migpaymentspayments_data', {} );
const title = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'PayByCrypto', 'migpaymentspayments' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};
const Label = () => {
    
    return wp.element.createElement(
        'span',
        null,
        title, // Add your text here
        wp.element.createElement('img', {
            src: window.wp.htmlEntities.decodeEntities( settings.icon || '' ),
            alt: 'PayByCrypto',
            style: {
                'margin-left':'10px'
            }
        })
    );
};

 
const Block_Gateway = {
    name: 'migpaymentspayments',
    label: Object( window.wp.element.createElement )( Label, null ),
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: title,
    supports: {
        features: settings.supports,
    },
 };
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );
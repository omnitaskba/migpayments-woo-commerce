<?php
  
    global $cryptoCurrencies;
    global $migpayments;
?>
   
 <!DOCTYPE html>
 <html lang="en">
    <head>
        <title><?php echo the_title() ?></title>
       
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
                integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
                crossorigin="anonymous">
         <link rel="stylesheet" href="<?php echo  plugin_dir_url(__FILE__) . '/assets/css/migpayments_style.css';?>">
          
     
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500&display=swap" rel="stylesheet">

        <style>
            
            .full-width-bg::before{
                background: url('<?php  echo $migpayments && $migpayments->redirectBackgroundUrl ?  $migpayments->redirectBackgroundUrl : ($migpayments->redirectBackgroundColor ? 'none' : plugin_dir_url(__FILE__) . '/assets/img/bg-landing.png'); ?>') no-repeat center center;
                background-size: cover;
            }
        </style>
   

        <?php if($migpayments && $migpayments->redirectBackgroundColor &&   !$migpayments->redirectBackgroundUrl ) : ?>
            <style>
            
                .full-width-bg::before{
                    background-color: <?php echo  $migpayments->redirectBackgroundColor; ?> !important;
                }
            </style>
        <?php endif; ?>
        
        
          
    </head>
    
    <?php
        $order_id = $_GET['orderId'];
        $order = new WC_Order( $order_id );
        
        $order_data = $order->get_data();
        $subtotal = $order->get_subtotal();
        $discountPrice = $order->get_discount_total();
        $currencyCode = get_woocommerce_currency_symbol($order->get_currency());
        $total = $order->get_total();
        $items = $order->get_items();
    
    ?>
    <body>
        <!-- Main -->
        <div id="wc-payment-wrapper" class="full-width-bg"  >
                <?php if($migpayments && $migpayments->redirectLogoUrl) : ?>
                    <img id="wc-migpayments-logo" width="120" src="<?php echo  $migpayments->redirectLogoUrl; ?>" alt="Merchant Logo">
                <?php endif;?>
             
            <div  id="wc-overpaid-modal" data-url="<?php echo admin_url( 'admin-ajax.php' );?>" data-order_id="<?php echo $order_id;?>" class="wc-overpaid-modal" >
                
                <div class="wc-overpaid-modal-content">
                    <div class="wc-overpaid-modal-header">
                            <h5>Order Overpaid</h5>
                    </div>
                    <div class="wc-overpaid-modal-body">
                        <p>
                            Our system has detected an  overpayment, and your order has been set to be manually processed. We have been notified and are working on it actively.
                        </p>
                    </div>
                    <div class="wc-overpaid-modal-footer">
                        <button class="wc-overpaid-modal-close" id="wc-overpaid-modal-close" onclick="hideOverpaidModal()" href="#">Okay</button>
                    </div>
                </div>
            
            </div>
            <div id="wc-payment-data-container">
                <div class="wc-main">
                    <div class="wc-card">
                        <div class="wc-migpayments-order-title">
                            <h5>Order Summary</h5>
                            
                            <p class="price"><?php echo $currencyCode;?><?php echo wc_format_decimal($order->get_total(), 2); ?></p>
                            <p>Order <span class="text-muted">#</span><b><?php echo $order_id;?></b></p>
                        </div>
                       
                            
                        <div class="wc-migpayments-order-card ">
                            <?php foreach($items as $item): ?>
                            <div class="wc-migpayments-order-item">
                                <div class="wc-migpayments-order-item-title">
                                    <span class="text-muted font-weight-light">x <?php echo $item->get_quantity(); ?></span>
                                    <a href="<?php echo get_permalink( $item->get_product_id());?>">
                                        <?php echo $item->get_name(); ?>
                                    </a>
                                </div>
                                <div class="wc-migpayments-product-price"><?php echo $currencyCode; ?><?php echo wc_format_decimal($item->get_total(), 2);  ?></div>
                            </div>
                            <?php endforeach;?>
                            
                            <div class="">
                                <?php if( $item->get_meta( 'pa_broker', true )) : ?>
                                    <div class="wc-migpayments-meta">Broker: <?php echo $item->get_meta( 'pa_broker', true ); ?></div>
                                <?php endif;?>
                                <?php if( $item->get_meta( 'pa_platform', true )) : ?>
                                    <div class="wc-migpayments-meta">Platform: <?php echo $item->get_meta( 'pa_platform', true ); ?></div>
                                <?php endif;?>

                                <?php if($item->get_meta('pa_account')) :?>

                                    <div class="meta">Account Size: <?php echo $item->get_meta( 'pa_account', true ); ?></div>
                                <?php endif; ?>
                                
                            </div>
                          
                            
                            <div class="wc-migpayments-hr"></div>
                            <div id="wc-migpayments-subtotal" class="wc-migpayments-order-item subtotal">
                                <div class="wc-migpayments- product-title">Subtotal</div>
                                <div class="wc-migpayments-product-price"><span class="text-muted"><?php echo $currencyCode;?></span><?php echo wc_format_decimal($order->get_subtotal(), 2) ?></div>
                            </div>
                            
                            <div class="wc-migpayments-order-item subtotal">
                                <div class="wc-migpayments- product-title"><b>Total</b></div>
                                <div class="wc-migpayments-product-price"><b><span class="text-muted"><?php echo $currencyCode;?></span><?php echo wc_format_decimal($order->get_total(), 2) ?></b></div>
                            </div>
                                
                        </div>
                        
                        <div  id="wc-migpayments-payment-options">
                            <h5>Payment Options</h5>
                            <div class="wc-migpayments-estimate">
                                <p class="text-dark"><b>Estimated Amounts</b></p>
                                <div id="wc-migpayments-estimate">
                                    <div class="wc-migpayments-loading"></div>
                                </div>
                            </div>
                            <div class="wc-migpayments-currency-select">
                                <div class="wc-migpayments-payment-currency">
                                    <h6>Payment Currency</h6>
                                    <div>
                                        <select name="currency_code" id="wc-migpayments-currency-code">
                                            <option value="">Please select currency</option>
                                        
                                            <?php foreach($cryptoCurrencies as $key => $val): ?>
                                            <option value=" <?php echo $val;?>"> <?php echo $val;?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                    
                                </div>
                                <div id="wc-migpayments-error"></div>
                                
                                <button class="wc-migpayments-primary-btn" onclick="getPaymentData()">
                                    Get Payment Data
                                </button>
                            </div>
                        </div>
                        
                        
                        <div id="wc-migpayments-payment-data"> <!-- JS --> </div>
                        
                        <div id="wc-mipgpayments-partial-payments">
                            <div id="wc-actions">
                                <a class="wc-migpayments-cancel-btn" id="wc-migpayments-cancel-btn"
                                    href="<?php echo $order->get_cancel_order_url();?>">
                                        Cancel Payment
                                    </a>
                            </div>
                            
                        </div>
                    </div>
                  
                    
                </div>
                
            </div>
            <div class="wc-migpayments-footer">
                            <small>Powered By</small> <br> <a target="_blank" href="https://cryptoorange.com"><img src="https://cryptoorange.com/img/logo.svg" alt=""></a>
                        </div>
        </div>

        <script src="<?php echo  plugin_dir_url(__FILE__) . '/assets/js/migpayments_scripts.js';?>"></script>
    </body>
</html>

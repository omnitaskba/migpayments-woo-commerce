<?php use chillerlan\QRCode\QRCode;  ?>
   
 <!DOCTYPE html>
 <html>
    <head>
        <title><?php echo the_title() ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
         <link rel="stylesheet" href="<?php echo  plugin_dir_url(__FILE__) . '/assets/css/migpayments_style.css';?>">
          
        </head>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
        <style> 
            * {
            font-family: 'Montserrat', sans-serif;
            }
        </style>
        <style>
            .full-width-bg::before{
                background: url('<?php  echo  plugin_dir_url(__FILE__) . '/assets/img/bg-image.jpg';?>') no-repeat center center;
                background-size: cover;
            }
        </style>
    <body>
       
      
            <!-- Main -->
            <div id="wc-payment-wrapper" class="full-width-bg" >
            <img id="wc-migpayments-logo" width="150" src="<?php  echo  plugin_dir_url(__FILE__) . '/assets/img/logo.svg';?>" alt="The funded trader logo">

                <?php if(isset($_GET['address']) && $_GET['address'] && isset($_GET['amount']) && $_GET['amount'] && isset($_GET['currency'])  && $_GET['currency']): ?>
                    <?php
                        $order_id = $_GET['orderId'];
                        $order = new WC_Order( $order_id );
                        
                        $order_data = $order->get_data();
                        $expiresAt = $order->get_meta('_migpayments_worder_crypto_expires_at');
                        $get_subtotal_price = $order->get_subtotal();
                        $get_discount_total_price = $order->get_discount_total();
                        $orderCurrencySymbol = get_woocommerce_currency_symbol($order->get_currency());
                        $get_total_amount = $order->get_total();
                        $items = $order->get_items();
                    
                    ?>
                

                    <div  id="wc-overpaid-modal" data-url="<?php echo admin_url( 'admin-ajax.php' );?>" data-order_id="<?php echo $order_id;?>" class="wc-overpaid-modal" >
                        
                        <div class="wc-overpaid-modal-content">
                            <div class="wc-overpaid-modal-header">
                                    <h5>Order Overpaid</h5>
                            </div>
                            <div class="wc-overpaid-modal-body">
                                
                                            <p>
                                                Our system has detected an  overpayment, and your order has been set to be manually processed. We have been notified and are working on it actively.</p>
                                            
                                
                            </div>
                            <div class="wc-overpaid-modal-footer">
                                <button class="wc-overpaid-modal-close" id="wc-overpaid-modal-close" onclick="hideOverpaidModal()" href="#">Okay</button>
                            </div>		
                        </div>
                    
                    </div>


                    <div id="wc-payment-data-container"   >
                        <div class="wc-main">
                            <div class="wc-card">
                                <div class="wc-migpayments-order-title">
                                    <h5>Your Order</h5>
                                    <p class="price"><?php echo $orderCurrencySymbol;?><?php echo wc_format_decimal($order->get_total(), 2); ?></p>
                                </div>
                                <hr class="wc-migpayments-hr">
                                
                                <div class="wc-migpayments-order-card ">
                                    <?php foreach($items as $item): ?>
                                    <div class="wc-migpayments-order-item">
                                        <div class="wc-migpayments-order-item-title">
                                            <span class="text-muted font-weight-light">x <?php echo $item->get_quantity(); ?> </span> 
                                            <a href="<?php echo get_permalink( $item->get_product_id());?>">
                                                <?php echo $item->get_name(); ?>
                                            </a>
                                        </div>
                                        <div class="wc-migpayments-product-price"><?php echo $orderCurrencySymbol; ?><?php echo wc_format_decimal($item->get_total(), 2);  ?></div>
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
                                    <div id="wc-migpayments-subtotal" class="wc-migpayments-order-item subtotal">
                                        <div class="wc-migpayments- product-title">Subtotal</div>
                                        <div class="wc-migpayments-product-price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span><?php echo wc_format_decimal($order->get_subtotal(), 2) ?></div>
                                    </div>
                                    <hr class="wc-migpayments-hr">
                                    <div class="wc-migpayments-order-item subtotal">
                                        <div class="wc-migpayments- product-title">Total</div>
                                        <div class="wc-migpayments-product-price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span><?php echo wc_format_decimal($order->get_total(), 2) ?></div>
                                    </div>
                                        
                                </div>
                                <h5 style="text-align: left;">Send Payment</h5>
                                
                                <div id="wc-migpayments-payment-data">
                                    <div>  
                                        <img id="wc-migpayments-address-qr-code" style="width:130px;" src="<?php echo (new QRCode())->render($_GET['address'])?>" alt="QR Code" /> 
                                    </div>
                                    <div class="wc-migpayments-payment-items">
                                            <div class="wc-migpayments-payment-item" >
                                                <div class="wc-payment-data-item">
                                                    <?php echo  $_GET['currency'];?> <?php migpayments_wc_get_cryptoAddressLabelTxt();?> 
                                                    <p> <?php echo $_GET['address'];  ?></p>
                                                </div>
                                                
                                                
                                                <button class="wc-migpayments-copy-btn" onclick="copyToClipboard('<?php echo $_GET['address'];  ?>')">Copy</button>

                                            </div>
                                            <div class="wc-migpayments-payment-item">
                                                <div class="wc-payment-data-item" >
                                                    <?php migpayments_wc_get_cryptoAmountLabelTxt();?>
                                                    <p id="total_crypto_amount" data-amount="<?php echo $_GET['amount'];  ?>">
                                                    <?php echo $_GET['amount'];  ?>
                                                    </p> 
                                                </div>
                                                <button class="wc-migpayments-copy-btn"  onclick="copyToClipboard('<?php echo $_GET['amount'];  ?>')">Copy</button>

                                            </div>
                                    </div>
                                    
                                </div>
                                
                                <div id="wc-mipgpayments-partial-payments">
                                    <div id="wc-actions">
                                        <a class="wc-migpayments-cancel-btn" id="wc-migpayments-cancel-btn" class="button" href="<?php echo $order->get_cancel_order_url(); //$_GET['success_url'];?>">
                                                <?php //migpayments_wc_get_redirectBtnText();?>
                                                Cancel Payment
                                            </a>
                                    </div>
                                    
                                </div>

                            
                                
                                </div>
                            </div>
                            
                        
                    </div>
                    <?php else: ?>

                        <div id="wc-payment-data-container"   >
                            <div class="wc-main">
                                <div class="wc-card">
                                <div id="wc-migpayments-payment-data-error" class="woocommerce-error"><?php migpayments_wc_get_paymentDataErrorTxt();?></div>
                                </div>
                                    <hr class="wc-migpayments-hr">
                                    <a class="wc-migpayments-cancel-btn" href="<?php echo esc_url(wp_get_referer());?>">
                                        Back
                                    </a>

                            </div>
                        </div>
                    <?php endif; ?>
            </div>
          
        
    
		
		




<script>
async function copyToClipboard(text) {
   
  try {
      await navigator.clipboard.writeText(text);
      console.log('Content copied to clipboard');
    } catch (err) {
      console.error('Failed to copy: ', err);
    }
}
  
</script>
<script src="<?php echo  plugin_dir_url(__FILE__) . '/assets/js/migpayments_scripts.js';?>"></script>
    </body>
</html>
 
<?php
 
// get_footer();

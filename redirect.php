<?php use chillerlan\QRCode\QRCode;  ?>
   
 <!DOCTYPE html>
 <html>
    <head>
        <title><?php echo the_title() ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
       
    </head>
    <body>
        <?php get_header(); ?>
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
            <div class="wc-overpaid-modal" id="wc-overpaid-modal" style="opacity:0;">
               	
                <div class="wc-overpaid-modal-content">
                    <div class="wc-overpaid-modal-header">
                            <h3>Order Overpaid</h3>
                    </div>
                    <div class="wc-overpaid-modal-body">
                    <img src="<?php echo  plugin_dir_url(__FILE__) . '/assets/img/logo-wp.png';?>" alt="Logo" width="100">
                                                 
                                    <p>
                                        Our system has detected an <b>overpayment</b>, and your order has been set to be <b>manually processed</b>.  <br>    Please contact <a target="_blank" href="https://help.thefundedtraderprogram.com/">support</a> for further assistance. Thank you for your order, and we apologize for any inconvenience!</p>
                                 
                    	
                    </div>
                    <div class="wc-overpaid-modal-footer">
                        <button class="wc-overpaid-modal-close" id="wc-overpaid-modal-close" onclick="hideOverpaidModal()" href="#">Close</button>
                    </div>		
                </div>
            
            </div>
                <!-- Main -->
                <div id="mug-main">
                    <div class="container-no">
                        <div class="row">
                            <div class="col-md-5 col-sm-12" id="wc-migpayments-left-column">
                                <div class="wc-migpayments-order-details">
                                   
                                    <div class="wc-migpayments-order-title">
                                        <p class="label">Your Order</p>
                                        <p class="price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span> <?php echo wc_format_decimal($order->get_total(), 2); ?></p>
                                    </div>

                                  
                                    <div class="wc-migpayments-order-card">
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
                                        
                                        <div class="wc-migpayments-card-meta">
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
                                        <div class="wc-migpayments-order-item subtotal">
                                            <div class="wc-migpayments- product-title">Subtotal</div>
                                            <div class="wc-migpayments-product-price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span><?php echo wc_format_decimal($order->get_subtotal(), 2) ?></div>
                                        </div>
                                    </div>
                                    <div class="wc-migpayments-order-total">
                                        <div class="wc-migpayments-order-item-title">Total</div>
                                        <div class="wc-migpayments-product-price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span><?php echo wc_format_decimal($order->get_total(), 2) ?></div>
                                    </div>
                                    
                                </div>
                                <div class="wc-migpayments-footer">
                                    <div class="wc-migpayments-footer-links">
                                        <a target="_blank" href="https://thefundedtraderprogram.com/privacy-policy/">Privacy Policy - </a>
                                        <a target="_blank" href="https://thefundedtraderprogram.com/terms-of-use/">Terms and Conditions - </a>
                                        <a target="_blank" href="https://thefundedtraderprogram.com/refund-policy/">Refunds Policy</a>
                                    </div>
                            </div>
                            </div>
                            <div class="col-md-7  col-sm-12" id="wc-migpayments-right-column">
                            
                                <div class="wc-migpayments-payment-data-title">
                                    <h4>Send Payment</h4>
                                    <div class="wc-migpayments-description-timer-wrapper">
                                        <p class="description">To make a payment, send payment by using the QR code<br>or buttons below</p>
                                        
                                        <p class="wc-migpayments-timer" id="expireTimer" data-expires_at="<?php echo $expiresAt;?>"></p>
                                    </div>
                                </div>
                               
                                <div  id="wc-migpayments-address-qr-code-wrapper">  
                                    <img id="wc-migpayments-address-qr-code" style="width:300px;" src="<?php echo (new QRCode())->render($_GET['address'])?>" alt="QR Code" /> 
                                </div>
                                    <div id="wc-migpayments-payment-data">
                                        <div class="wc-migpayments-crypto-address-wrapper">
                                            <div class="wc-migpayments-crypto-address-label">
                                                <?php migpayments_wc_get_cryptoAddressLabelTxt();?>
                                            </div> 
                                            <div> 
                                                <span class="wc-migpayments-crypto-address">
                                                     <?php echo $_GET['address'];  ?>
                                                </span>
                                                <button class="wc-migpayments-copy-btn" onclick="copyToClipboard('<?php echo $_GET['address'];  ?>')"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button>
                                            </div>
                                        </div>  
                                        <div class="wc-migpayments-crypto-amount-wrapper" id="wc-migpayments-crypto-amount-wrapper">
                                            <div class="wc-migpayments-crypto-amount-label">
                                                <?php migpayments_wc_get_cryptoAmountLabelTxt();?>
                                            </div>
                                                
                                                <span class="wc-migpayments-crypto-amount">
                                                    <?php echo $_GET['amount'];  ?></span> <span class="currency_name font-italic"><?php echo  $_GET['currency'];?> 
                                                </span>
                                                
                                             <button class="wc-migpayments-copy-btn"  onclick="copyToClipboard('<?php echo $_GET['amount'];  ?>')"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button>
                                            
                                        </div>
                                    </div>
                                    

                                    <div  id="wc-migpayments-review-order-button-wrapper">
                                        <a id="wc-migpayments-cancel-order-button" class="button" href="<?php echo $order->get_cancel_order_url(); //$_GET['success_url'];?>">
                                            <?php //migpayments_wc_get_redirectBtnText();?>
                                            Cancel Payment
                                        </a>
                                        <a id="wc-migpayments-review-order-button" disabled style="background-color:#f7941e !important ;" class="button" >
                                            
                                            <?php migpayments_wc_get_redirectBtnText();?>
                                        </a>
                                    </div>
                                    <div id="total_crypto_amount" data-amount="<?php echo $_GET['amount'];?>"></div>
                                    <div class="text-center">
                                        <img src="<?php echo  plugin_dir_url(__FILE__) . '/assets/img/logo-wp.png';?>" alt="Logo" width="100" class="wc-migpayments-thefunded-trader-logo">
                                    </div>
                                    <div id="wc-migpayments-info-box">
                                    
                                        <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /> </svg>

                                        Note: If you have sent payment and have not received an order confirmation after 10 minutes or have sent partial payment and are unable to complete the entire transaction, please contact <a href="https://help.thefundedtraderprogram.com">support</a>.
                                    </div>
                                    <div id="wc-mipgpayments-partial-payments">
                                    </div>

                                <?php else: ?>
                                    <div id="wc-migpayments-payment-data-error" class="woocommerce-error"><?php migpayments_wc_get_paymentDataErrorTxt();?></div>
                                <?php endif; ?>
                               

                            </div>
                        </div>
                    </div>
                </div>
 
            </main> 
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
 
countdownExpireTime();
    
function countdownExpireTime(e){
  
    var countDownDate = new Date(document.getElementById("expireTimer").dataset.expires_at).getTime();

    // Update the count down every 1 second
    var x = setInterval(function() {

        // Get todays date and time
        var now = new Date().getTime();
        
        // Find the distance between now an the count down date
        var distance = countDownDate - now;
        
        // Time calculations for days, hours, minutes and seconds
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        if(seconds < 10){
            seconds = '0' + seconds;
        }
        // Output the result in an element with id="expireTimer"
        document.getElementById("expireTimer").innerHTML = minutes + ": " + seconds;
        
        // If the count down is over, write some text 
        if (distance < 0) {
            clearInterval(x);
            document.getElementById("expireTimer").innerHTML = "EXPIRED";
        }
    }, 1000);
}
</script>

    </body>
</html>
 
<?php
 
get_footer();

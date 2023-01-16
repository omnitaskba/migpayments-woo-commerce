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

 

     // foreach ( $items as $item ) {
	// 	/* echo"<pre>";
	// 	print_r($item);
	// 	echo"</pre>"; */
    //     $product_name = $item->get_name();
    //     $total = $item->get_total();
    //     $sub_total = $item->get_subtotal();
    //     $product_id = $item->get_product_id();
    //     $getproduct_detail = wc_get_product( $product_id );
    //     $product_variation_id = $item->get_variation_id();
    //     $Item_quantity = $item['quantity'];
    //     $get_itemImage = get_the_post_thumbnail_url($product_id);
    //     $item_price  = $getproduct_detail->get_attribute('price');
    //     $terms = get_the_terms($product_id, 'product_cat');
    //     $item_url = get_permalink( $product_id );
    //     $get_account_size = $item->get_meta( 'pa_account', true );
    //     $get_account_pa_eightcap = $item->get_meta( 'pa_broker', true );
    //     $get_account_pa_platform = $item->get_meta( 'pa_platform', true );
    //     $categoryname = $terms['0']->name;
    
    // }
?>
        <div id="wc-migpayments-primary"  >
            <main id="wc-migpayments-main" class="site-main" role="main">
				
				

                <!-- Mughees -->
                <div id="mug-main">
                    <div class="container-no">
                        <div class="row">
                            <div class="col-md-5 col-sm-12" >
                                <div class="order-details">
                                    <!-- <div class="back-button">
                                        <a href="/"><i class="fa-sharp fa-solid fa-arrow-left"></i></a>
                                        <a href="<?php echo get_site_url(); //$_GET['success_url'];  ?>">Back to Payment Page</a>
                                    </div> -->
                                    <div class="order-title">
                                        <p class="name">Your Order</p>
                                        <p class="price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span> <?php echo wc_format_decimal($order->get_total(), 2); ?></p>
                                    </div>
                                    <div class="outer-card">
                                        <div class="order-card">
                                            <?php foreach($items as $item): ?>
                                            <div class="card-title">
                                                <div class="product-title">
                                                    <span class="text-muted font-weight-light">x <?php echo $item->get_quantity(); ?> </span> 
                                                    <a href="<?php echo get_permalink( $item->get_product_id());?>">
                                                        <?php echo $item->get_name(); ?>
                                                    </a>
                                                </div>
                                                <div class="product-price"><?php echo $orderCurrencySymbol; ?><?php echo wc_format_decimal($item->get_total(), 2);  ?></div>
                                            </div>
                                            <?php endforeach;?>
                                           
                                            <div class="card-meta">
                                                <?php if( $item->get_meta( 'pa_broker', true )) : ?>
                                                    <div class="meta">Broker: <?php echo $item->get_meta( 'pa_broker', true ); ?></div>
                                                <?php endif;?>
                                                <?php if( $item->get_meta( 'pa_platform', true )) : ?>
                                                    <div class="meta">Platform: <?php echo $item->get_meta( 'pa_platform', true ); ?></div>
                                                <?php endif;?>

                                                <?php if($item->get_meta('pa_account')) :?>

                                                    <div class="meta">Account Size: <?php echo $item->get_meta( 'pa_account', true ); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-title subtotal">
                                                <div class="product-title">Subtotal</div>
                                                <div class="product-price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span><?php echo wc_format_decimal($order->get_subtotal(), 2) ?></div>
                                            </div>
                                        </div>
                                        <div class="card-title total">
                                            <div class="product-title">Total</div>
                                            <div class="product-price"><span class="text-muted"><?php echo $orderCurrencySymbol;?></span><?php echo wc_format_decimal($order->get_total(), 2) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <footer>
                                    <div class="footer-links">
                                        <a target="_blank" href="https://thefundedtraderprogram.com/privacy-policy/">Privacy Policy - </a>
                                        <a target="_blank" href="https://thefundedtraderprogram.com/terms-of-use/">Terms and Conditions - </a>
                                        <a target="_blank" href="https://thefundedtraderprogram.com/refund-policy/">Refunds Policy</a>
                                    </div>
                                </footer>
                            </div>
                            <div class="col-md-7  col-sm-12" >
                                <div id="afg_maindiv">
                                    <div id="wc-migpayments-page-content">
                                        <div class="send-payment-top">
                                            <p>Send Payment</p>
                                            <div class="timer-main">
                                                <p class="meta">To make a payment, send payment by using the QR code<br>or buttons below</p>
                                                
                                                <p class="time meta" id="expireTimer" data-expires_at="<?php echo $expiresAt;?>"></p>
                                            </div>
                                        </div>
                                    </div> 
                                    <div  id="wc-migpayments-address-qr-code-wrapper">  
                                        <img id="wc-migpayments-address-qr-code" style="width:300px;" src="<?php echo (new QRCode())->render($_GET['address'])?>" alt="QR Code" /> 
                                    </div>
                                        <div id="wc-migpayments-payment-data">
                                            <div class="wc-migpayments-crypto-address-wrapper">
                                               <div class="afg_crypto_address"> <span class="wc-migpayments-crypto-address-label"><?php migpayments_wc_get_cryptoAddressLabelTxt();?></span></div> 
                                                <div class="afg_cryptocode"> 
                                                    <span class="wc-migpayments-crypto-address">
                                                        
                                                        <span  id="address" readonly><?php echo $_GET['address'];  ?></span>
                                                    </span>
                                                    <span class="copy_clipbord"><button class="afg_bntcopy" onclick="copyToClipboard('<?php echo $_GET['address'];  ?>')"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button></span>
                                                </div>
                                            </div>  
                                            <div class="wc-migpayments-crypto-amount-wrapper">
                                                <div class="afg_currency_text"> <span class="wc-migpayments-crypto-amount-label"><?php migpayments_wc_get_cryptoAmountLabelTxt();?></span></div>
                                                    <div class="afg_amountdata">
                                                    <span class="wc-migpayments-crypto-amount">
                                                    <span id="amount"> <?php echo $_GET['amount'];  ?></span> <span class="currency_name font-italic"><?php echo  $_GET['currency'];?></span>
                                                </span>
                                                <span class="copy_clipbord"><button class="afg_bntcopy"  onclick="copyToClipboard('<?php echo $_GET['amount'];  ?>')"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button></span>
                                                </div>
                                            </div>
                                        </div>
                                        

                                        <div  id="wc-migpayments-review-order-button-wrapper" class="afg_databtn">
                                            <a id="wc-migpayments-cancel-order-button" class="button" href="<?php echo $order->get_cancel_order_url(); //$_GET['success_url'];?>">
                                                <?php //migpayments_wc_get_redirectBtnText();?>
                                                Cancel Payment
                                            </a>
                                            <a id="wc-migpayments-review-order-button" disabled style="background-color:#f7941e !important ;" class="button" >
												
                                                <?php migpayments_wc_get_redirectBtnText();?>
                                            </a>
                                        </div>
                                        <div id="total_crypto_amount" data-amount="<?php echo $_GET['amount'];?>"></div>
                                        <div id="wc-mipgpayments-partial-payments">
                                        <!-- <img src="https://devops.thefundedtraderprogram.com/wp-content/uploads/2022/12/giphy.gif" style="height:100px;">  -->
                                        </div>

                                    <?php else: ?>
                                        <div id="wc-migpayments-payment-data-error" class="woocommerce-error"><?php migpayments_wc_get_paymentDataErrorTxt();?></div>
                                    <?php endif; ?>
                                </div>
                                      

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

<?php use chillerlan\QRCode\QRCode;  ?>
   
 <!DOCTYPE html>
 <html>
    <head>
        <title><?php echo the_title() ?></title>
    </head>
    <body>
        <?php get_header(); ?>

        <div id="wc-migpayments-primary" class="content-area">
            <main id="wc-migpayments-main" class="site-main" role="main">
                <header>
                    <h1 id="wc-migpayments-payment-data-title" class="entry-title"> <?php echo the_title() ?></h1>
                </header>
        
                <?php if(isset($_GET['address']) && $_GET['address'] && isset($_GET['amount']) && $_GET['amount'] && isset($_GET['currency'])  && $_GET['currency']): ?>
    
                    <div id="wc-migpayments-page-content">
                        <p>
                            Please send whole amount in ONE transaction.
                            <br>
                            Please add the mining fee on top of the displayed amount.
                        </p>

                    </div> 
                 
                    <div id="wc-migpayments-payment-data">
                        <div class="wc-migpayments-crypto-address-wrapper">
                            <span class="wc-migpayments-crypto-address-label"><?php migpayments_wc_get_cryptoAddressLabelTxt();?></span>  
                            <span class="wc-migpayments-crypto-address">
                                <?php echo  $_GET['address'];?>
                            </span>
                        </div>  
                        <div class="wc-migpayments-crypto-amount-wrapper">
                            <span class="wc-migpayments-crypto-amount-label"><?php migpayments_wc_get_cryptoAmountLabelTxt();?></span>  
                            <span class="wc-migpayments-crypto-amount">
                                <?php echo  $_GET['amount'];?>  <?php echo  $_GET['currency'];?>
                            </span>
                        </div>
                    </div>
                    
                    <div  id="wc-migpayments-address-qr-code-wrapper">  
                        <img id="wc-migpayments-address-qr-code" style="width:300px;" src="<?php echo (new QRCode())->render($_GET['address'])?>" alt="QR Code" /> 
                    </div>
                    <div  id="wc-migpayments-review-order-button-wrapper">
                        <a id="wc-migpayments-review-order-button" class="button" href="<?php echo $_GET['success_url'] ;?>">
                            <?php migpayments_wc_get_redirectBtnText();?>
                        </a>
                    </div>

                <?php else: ?>
                    <div id="wc-migpayments-payment-data-error" class="woocommerce-error"><?php migpayments_wc_get_paymentDataErrorTxt();?></div>
                <?php endif; ?>

               
            </main> 
        </div>
    </body>
</html>
 
<?php
 
get_footer();

 
 <!DOCTYPE html>
 <html>
 <head>
     <title>Migpayments</title>
  </head>
 <body >
  
  <?php

  use chillerlan\QRCode\QRCode;
  
  get_header();
  ?>

<div id="wc-migpayments-primary" class="content-area">
			<main id="wc-migpayments-main" class="site-main" role="main">
        <header>
          <h1 class="entry-title">Payment instructions</h1>
        </header>

        <?php if(isset($_GET['address']) && $_GET['address'] && isset($_GET['amount']) && $_GET['amount'] && isset($_GET['currency'])  && $_GET['currency']): ?>
          
        <strong>Please send whole amount in ONE transaction.</strong></br>
        <strong>Please add the mining fee on top of the displayed amount.</strong></br><hr>


          Send transaction to this address: <strong> <?php echo  $_GET['address'];?></strong> <br>
          Send this exact amount: <strong> <?php echo  $_GET['amount'];?>  <?php echo  $_GET['currency'];?> </strong><br>
          
          <div text-align="center">  
            <img id="wc-migpayments-address-qr-code" style="width:300px;" src="<?php echo (new QRCode())->render($_GET['address'])?>" alt="QR Code" /> 
        </div>
        <?php else: ?>
          <div class="woocommerce-error">Failed to get crypto payment data.</div>
        <?php endif; ?>

          <div  id="wc-review-order-button-wrapper">
            <a class="button" href="<?php echo $_GET['success_url'] ;?>">
              Review Order
            </a>
          </div>
      
      
    </main><!-- #main -->
</div>
</body>
        </html>
 
  <?php
  get_sidebar();
  get_footer();
 
<?php use chillerlan\QRCode\QRCode;  ?>
   
 <!DOCTYPE html>
 <html>
    <head>
        <title><?php echo the_title() ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <style>
            .back-button a{
                font-size: 13px;
                font-weight: 600;
            }
            .order-details {
                background-color: #f4f4f4;
                text-align: left;
                /* padding: 20px 100px; */
                padding:20px;
                height: 100vh;
                position: relative;
            }
            /* .outer-card{
                position: absolute;
                top: 40%;
                transform: translateY(-50%);
                left: 18%;
            } */
            .order-card {
                background-color: #eaeaea;
                padding: 10px 20px;
                border-top-right-radius: 10px;
                border-top-left-radius: 10px;
                /* width: 400px; */
            }
            .card-title {
                display: flex;
                justify-content: space-between;
            }
            .order-title p.name{
                margin: 0;
                font-weight: 600;
                color: #aaa;
                font-size: 20px;
            }
            .order-title .price{
                font-weight: bold;
                font-size: 30px;
                position: relative;
                top: -10px;
            }
            .product-title,.product-price {
                font-weight: 600;
            }
            .card-meta{
                margin-bottom: 30px;
            }
            .meta {
                font-size: 12px;
                font-weight: 600;
                color: #aaa;
            }
            .card-title.total {
                padding: 10px 20px;
                border-top: 1px solid #a8a8a8;
                background-color: #eaeaea;
                border-bottom-right-radius: 10px;
                border-bottom-left-radius: 10px;
            }
            #afg_maindiv{
                width: 80% !important;
                /* margin: 70px auto !important; */
                border-radius: 10px;
				background: #eeeeee;
				padding-top: 20px;
				padding-bottom: 15px;
            }
			input#myInput {
                color: black;
                width: 60%;
                background-color: #eeee;
                border: 1px;
                height: 25px;
            }
            .send-payment-top{
                text-align: left;
                padding: 0 20px;
            }
            .send-payment-top > p{
                font-weight: bold;
                margin-bottom: 2px;
            }
            .timer-main{
                display: flex;
                justify-content: space-between;
            }
            .time.meta{
                border-radius: 50%;
                border: 1px solid black;
                width: 40px;
                height: 40px;
                text-align: center;
                vertical-align: middle;
                padding-top: 10px;
                padding-left: 5px;
                padding-right: 5px;
                font-size: 10px;
            }
            .afg_crypto_address,.afg_currency_text{
                padding: 0 20px;
            }
            #wc-migpayments-review-order-button{
                background-color: black !important;
                color: white;
            }
            .back-button+.order-title{
                margin-top: 50px;
            }
            .order-details+footer{
                background-color: #f4f4f4;
                padding-bottom: 50px;
                text-align: left;
                padding-left: 50px;
            }
            .footer-links a{
                color: #aaa;
                font-size: 14px;
            }
            @media(max-width: 600px){
                #afg_maindiv{
                margin: 0px !important;
                }
                .order-details{
                    height: auto;
                    padding: 20px 60px;
                }
                .order-card{
                    width: 300px;
                }
                .outer-card {
                    top: auto;
                    left: auto;
                    position: unset;
                    transform: none;
                }
            }
			.afg_databtn{
				text-align:right !important;
            }
            button.afg_bntcopy {
                font-size: 12px !important;
                padding: unset;
                color: black !important; 
                background: #eeee !important;
                text-transform: capitalize !important;
                padding: 4px !important;
            }		
                        
            .afg_crypto_address, .afg_currency_text, .afg_amountdata, .afg_cryptocode{
                text-align: left;
            }

            span.copy_clipbord {
                text-align: end !important;
                float: right;
            }
            span.copy_clipbord:hover {
                cursor: pointer;
            }
            .afg_cryptocode {
                margin-top: 5px;
                margin-left: 10px;
            }
            .afg_amountdata {
                margin-left: 10px;
                margin-top: 5px;
            }
            span.wc-migpayments-crypto-amount-label {
                font-size: 16px;
                font-weight: 600;
            }
            span.wc-migpayments-crypto-address-label {
                font-size: 16px;
                font-weight: 600;
            }
            div#afg_maindiv {
                width: 60%;
                margin: 0px auto;
                background: #eeeeee;
                padding-top: 20px;
                padding-bottom: 15px;
            }
            div#wc-migpayments-address-qr-code-wrapper {
                border-top: 1px solid #6d6d6d6d;
                border-bottom: 1px solid #6d6d6d6d;
            }
            div#wc-migpayments-payment-data {
                margin: unset;
            }
            .wc-migpayments-crypto-address-wrapper {
                border-bottom: 1px solid #6d6d6d6d;
                padding-bottom: 10px;
            }
            .wc-migpayments-crypto-amount-wrapper {
                padding-bottom: 10px;
                border-bottom: 1px solid #6d6d6d6d;}
            button.afg_bntcopy{
                font-size: 12px;
                padding: unset;
                color: black;
                background: #eeee;	
                text-transform: capitalize;
                padding: 4px;
            }
            input#myInput {
                color: black;
                width: 60%;
                background-color: #eeee;
                border:1px;
                height:25px;
            }
            input#myInput:focus {
                background-color: #eeee !important;
                border: 1px;
            }
            input#myInput_price {
                color: black;
                width: 15%;
                background-color: #eeee;
                border: 1px;
                padding-right: unset;
                    height:25px;
            }
            input#myInput_price:focus {
                background-color: #eeee !important;
                border: 1px;
                border-color:unset;
            }
            button.afg_bntcopy:hover {
                background: #f7941e;
                color: white;
                text-transform: capitalize;
            border-color:#f7941e;
            }
            i.afg_copyicon.fa.fa-clone {
                margin-right: 7px;
            }
            span.copy_clipbord {
                /* text-align: end !important; */
                float: right;
                margin-right: 20px;
            }
            span.wc-migpayments-crypto-address-label {
                padding-left: 10px;
            }
            span.wc-migpayments-crypto-amount-label {
                padding-left: 10px;
            }
        </style>
    </head>
    <body>
        <?php get_header(); ?>
        <?php if(isset($_GET['address']) && $_GET['address'] && isset($_GET['amount']) && $_GET['amount'] && isset($_GET['currency'])  && $_GET['currency']): ?>
<?php
 $order_id = $_GET['orderId'];
$order = new WC_Order( $order_id );
    $order_data = $order->get_data();
	
	$get_subtotal_price = $order->get_subtotal();
$get_discount_total_price = $order->get_discount_total();

$get_total_amount = $order->get_total();
    $items = $order->get_items();
    foreach ( $items as $item ) {
		/* echo"<pre>";
		print_r($item);
		echo"</pre>"; */
        $product_name = $item->get_name();
        $total = $item->get_total();
        $sub_total = $item->get_subtotal();
        $product_id = $item->get_product_id();
        $getproduct_detail = wc_get_product( $product_id );
        $product_variation_id = $item->get_variation_id();
        $Item_quantity = $item['quantity'];
        $get_itemImage = get_the_post_thumbnail_url($product_id);
        $item_price  = $getproduct_detail->get_attribute('price');
        $terms = get_the_terms($product_id, 'product_cat');
        $item_url = get_permalink( $product_id );
        $get_account_size = $item->get_meta( 'pa_account', true );
        $get_account_pa_eightcap = $item->get_meta( 'pa_broker', true );
        $get_account_pa_platform = $item->get_meta( 'pa_platform', true );
        $categoryname = $terms['0']->name;
    }
?>
        <div id="wc-migpayments-primary"  >
            <main id="wc-migpayments-main" class="site-main" role="main">
				
				

                <!-- Mughees -->
                <div id="mug-main">
                    <div class="container-no">
                        <div class="row">
                            <div class="col-md-5 col-sm-12" style="padding: 0;">
                                <div class="order-details">
                                    <div class="back-button">
                                        <a href="/"><i class="fa-sharp fa-solid fa-arrow-left"></i></a>
                                        <a href="<?php echo get_site_url(); //$_GET['success_url'];  ?>">Back to Payment Page</a>
                                    </div>
                                    <div class="order-title">
                                        <p class="name">Your Order</p>
                                        <p class="price"><?php echo $_GET['amount']; ?></p>
                                    </div>
                                    <div class="outer-card">
                                        <div class="order-card">
                                            <div class="card-title">
                                                <div class="product-title">x1 <?php echo $product_name; ?></div>
                                                <div class="product-price">$<?php echo $_GET['amount'];  ?></div>
                                            </div>
                                            <div class="card-meta">
                                                <div class="meta">Broker: <?php echo $get_account_pa_eightcap; ?></div>
                                                <div class="meta">Platform: <?php echo $get_account_pa_platform; ?></div>
                                            </div>
                                            <div class="card-title subtotal">
                                                <div class="product-title">Subtotal</div>
                                                <div class="product-price">$<?php echo $_GET['amount']; ?></div>
                                            </div>
                                        </div>
                                        <div class="card-title total">
                                            <div class="product-title">Total</div>
                                            <div class="product-price">$<?php echo $_GET['amount']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <footer>
                                    <div class="footer-links">
                                        <a href="/privacy-policy">Privacy Policy - </a>
                                        <a href="/terms-of-use">Terms and Conditions - </a>
                                        <a href="/refund-policy">Refunds Policy</a>
                                    </div>
                                </footer>
                            </div>
                            <div class="col-md-7 col-sm-12" style="padding: 0;">
                                <div id="afg_maindiv">
                                    <div id="wc-migpayments-page-content">
                                        <div class="send-payment-top">
                                            <p>Send Payment</p>
                                            <div class="timer-main">
                                                <p class="meta">To make a payment, send payment by using the QR code<br>or buttons below</p>
                                                <p class="time meta" id="demo"></p>
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
                                                        
                                                        <input type="text" value="<?php echo $_GET['address'];  ?>" id="myInput" readonly>
                                                    </span>
                                                    <span class="copy_clipbord"><button class="afg_bntcopy" onclick="myFunction()"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button></span>
                                                </div>
                                            </div>  
                                            <div class="wc-migpayments-crypto-amount-wrapper">
                                                <div class="afg_currency_text"> <span class="wc-migpayments-crypto-amount-label"><?php migpayments_wc_get_cryptoAmountLabelTxt();?></span></div>
                                                    <div class="afg_amountdata">
                                                    <span class="wc-migpayments-crypto-amount">
                                                    <input type="text" value="<?php echo $_GET['amount'];  ?>" id="myInput_price" readonly>  <span class="currency_name"><?php echo  $_GET['currency'];?></span>
                                                </span>
                                                <span class="copy_clipbord"><button class="afg_bntcopy"  onclick="myFunction_price()"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button></span>
                                                </div>
                                            </div>
                                        </div>
                                        

                                        <div  id="wc-migpayments-review-order-button-wrapper" class="afg_databtn">
                                            <a id="wc-migpayments-review-order-button" class="button" href="<?php echo $order->get_cancel_order_url(); //$_GET['success_url'];?>">
                                                <?php //migpayments_wc_get_redirectBtnText();?>
                                                Cancel Payment
                                            </a>
                                            <a id="wc-migpayments-review-order-button" disabled style="background-color:#f7941e !important ;" class="button" >
												
                                                <?php migpayments_wc_get_redirectBtnText();?>
                                            </a>
                                        </div>

                                    <?php else: ?>
                                        <div id="wc-migpayments-payment-data-error" class="woocommerce-error"><?php migpayments_wc_get_paymentDataErrorTxt();?></div>
                                    <?php endif; ?>
                                </div>
								<div  id="wc-migpayments-review-order-button-wrapper" style="text-align-last:center;" class="afg_databtn">
                                    <div id="wc-mipgpayments-partial-payments">
                                        <img src="https://devops.thefundedtraderprogram.com/wp-content/uploads/2022/12/giphy.gif" style="height:100px;">        
                                    </div>
									
											
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <header>
                    <!-- <h1 id="wc-migpayments-payment-data-title" class="entry-title"> <?php //echo the_title() ?></h1> -->
                </header>
        
                



                






				
               
            </main> 
        </div>
		
		
		




<script>
function myFunction() {
  /* Get the text field */
  var copyText = document.getElementById("myInput");

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /* For mobile devices */

  /* Copy the text inside the text field */
  navigator.clipboard.writeText(copyText.value);
  
  /* Alert the copied text */
  //alert("Copied the text: " + copyText.value);
}

function myFunction_price() {
  /* Get the text field */
  var copyText = document.getElementById("myInput_price");

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /* For mobile devices */

  /* Copy the text inside the text field */
  navigator.clipboard.writeText(copyText.value);
  
  /* Alert the copied text */
  //alert("Copied the text: " + copyText.value);
}



</script>
       
<script>
var myTimer;
function clock() {
    myTimer = setInterval(myClock, 1000);
    var c = 3600; 


    function myClock() {
        --c
        var seconds = c % 60; 
        var secondsInMinutes = (c - seconds) / 60; 
        var minutes = secondsInMinutes % 60; 
        var hours = (secondsInMinutes - minutes) / 60;
        // console.clear();
          document.getElementById("demo").innerHTML = minutes + ":" + seconds;
        // console.log(minutes + ":" + seconds)
        if (c == 0) {
         // document.getElementById("demo").innerHTML = "time end";
            clearInterval(myTimer);
        }
    }
}

clock();

    

</script>

    </body>
</html>
 
<?php
 
get_footer();

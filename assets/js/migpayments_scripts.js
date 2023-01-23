setInterval(function(){ 
    
    const data = new FormData();
    var partialPaymentsEl =  document.getElementById('wc-mipgpayments-partial-payments')
    var infoBoxEl =  document.getElementById('wc-migpayments-info-box')
    var cancelBtn = document.getElementById('wc-migpayments-cancel-order-button');
    var reviewBtn = document.getElementById('wc-migpayments-review-order-button');
    var amountEl = document.getElementById('wc-migpayments-crypto-amount-wrapper');

    data.append( 'action', 'migpayments_wc_check_payment_status' );
    data.append( 'order_id', ajaxObj.orderId);
    const params = new URLSearchParams(data);
    const settings = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Cache-Control': 'no-cache',
           },
        body: params,
        credentials: 'same-origin',
    };

    const fetchResponse = fetch(ajaxObj.ajaxurl, settings).then(response => response.json()).then(data => {
        // console.log(data);
        //Payment confirmed
        if(data.redirect == true){
            window.location.href = ajaxObj.redirectUrl;
        } else{ 
             
            if(data.status == 'Overpaid'){
                var partialAmountEl = document.getElementById('wc-migpayments-remaining-amount');
                if(partialAmountEl){
                    partialAmountEl.remove();
                }
                var html = '<p>';
                html += '<svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /> </svg> ';

                html += 'Our system has detected an overpayment, and your order has been set to be manually processed. Please contact <a target="_blank" href="https://help.thefundedtraderprogram.com/">support</a> for further assistance. Thank you for your order, and we apologize for any inconvenience!</p>';
              
                infoBoxEl.innerHTML = html;
                if(cancelBtn)
                     cancelBtn.remove();
                if(reviewBtn)
                    reviewBtn.remove();
                
                amountEl.remove();
            
            } else{

                if(data.status != 'Pending'){
                    
                    amountEl.remove();
                    infoBoxEl.remove();
                    //PartialPayment
                    var totalAmount =  document.getElementById('total_crypto_amount').dataset.amount;
                    var totalPartialsAmount = 0;
                    var currencyCode = null;
                    if(data.partial_payments && data.partial_payments.length > 0){
                        var html = '<h5 class="text-left">Partial Payments</h5><div class="wc-migpayments-order-info-box"> <span>Order ID: ' + data.order_number + '</span> <span class="copy_clipbord"><div onclick="copyToClipboard('+ data.order_number + ')"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</div></span> </div> <table id="wc-migpayments-partial-payments-list" class="table">';
                        html += '<thead><th>Amount</th><th>Received At</th></thead><tbody>'

                        let payments = data.partial_payments;
                        for (let i = 0; i < payments.length; i++) {
                            currencyCode = payments[i].currency_code;
                            html += '<tr><td> <span class="text-success">'+ payments[i].amount  + '</span> ' +  payments[i].currency_code + '</td><td> ' + payments[i].received_at + '</b></tr>'
                            totalPartialsAmount = parseFloat(payments[i].amount) + parseFloat(totalPartialsAmount);
                            
                        }

                        console.log(totalPartialsAmount);
                        html += '</tbody></table>';
                        amountDifference = parseFloat(totalAmount) - parseFloat(totalPartialsAmount);
                        var decimals = currencyCode == 'USDT' ? 6 : 8;
                        var remainingAmount = parseFloat(amountDifference).toFixed(decimals);
                        html += '<div class="wc-migpayments-remaining-amount">';
                        
                        html += '<div class="text-left">';
                        html += 'Remaining Payment Amount: <b>' + remainingAmount  + ' '+ currencyCode  +'</b> </div><span class="copy_clipbord"><button class="afg_bntcopy"  onclick="copyToClipboard('+ remainingAmount + ')"><i class="afg_copyicon fa fa-clone" aria-hidden="true"></i>Copy</button></span>';
                        html += '</div>';
                        // each payments
                        partialPaymentsEl.innerHTML = html;
                    
                    }

                    if(cancelBtn)
                        cancelBtn.remove();
                    if(reviewBtn)
                        reviewBtn.remove();
                
                }
                
             
            }
           
        }
           
    }).catch(e => {
        console.log(e)
    });
       
  
 }, 5000);

  
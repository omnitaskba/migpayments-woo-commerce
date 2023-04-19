setInterval(function(){ 
   
    const data = new FormData();
    var partialPaymentsEl =  document.getElementById('wc-mipgpayments-partial-payments')
    var infoBoxEl =  document.getElementById('wc-migpayments-info-box')
    var paymentDataEl =  document.getElementById('wc-payment-data-container')
    
    var reviewBtn = document.getElementById('wc-migpayments-review-order-button');
    var amountEl = document.getElementById('wc-migpayments-crypto-amount-wrapper');
    var overpaidModal =  document.getElementById('wc-overpaid-modal');
    var cancelBtn = document.getElementById('wc-migpayments-cancel-btn');
    var ajaxUrl  = overpaidModal.dataset.url;

    data.append( 'action', 'migpayments_wc_check_payment_status' );
    data.append( 'order_id' , overpaidModal.dataset.order_id);
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

    const fetchResponse = fetch(ajaxUrl, settings).then(response => response.json()).then(data => {
        // console.log(data);
        //Payment confirmed
        if(data.redirect == true){
            window.location.href = data.redirectUrl;
        } else{ 
             
            if(data.status == 'Overpaid'){
                if(overpaidModal)
                    overpaidModal.classList.add('active');
                
                 
                var paymentDataContainer = document.getElementById('wc-payment-data-container');
                paymentDataContainer.classList.add('hide');
                
                if(cancelBtn)
                    cancelBtn.remove();
            } else{

                if(data.status != 'Pending'){

                    if(cancelBtn)
                    cancelBtn.remove();

                    if(amountEl)
                    amountEl.remove();
                    if(infoBoxEl)
                    infoBoxEl.remove();
                    //PartialPayment
                    var totalAmount =  document.getElementById('total_crypto_amount').dataset.amount;
                    var totalPartialsAmount = 0;
                    var currencyCode = null;
                    if(data.partial_payments && data.partial_payments.length > 0){
                        var html = '<hr><h5 class="text-left">Partial Payments</h5> <table id="wc-migpayments-partial-payments-list" class="table" style="font-size:14px;">';
                        html += '<thead><th style="text-align:left;padding-left:0px;">Amount</th><th style="text-align:right;padding-right:0px;">Received At</th></thead><tbody>'

                        let payments = data.partial_payments;
                        for (let i = 0; i < payments.length; i++) {
                            currencyCode = payments[i].currency_code;
                            html += '<tr style="border-bottom:none;"><td style="border:none;text-align:left;padding-left:0px;"> <h6 class="text-success">'+ payments[i].amount  + ' <span style="color:#000000" >' +  payments[i].currency_code + '</span></h6> </td><td style="border:none;text-align:right;padding-right:0px;"> ' + payments[i].received_at + '</b></tr>'
                            totalPartialsAmount = parseFloat(payments[i].amount) + parseFloat(totalPartialsAmount);
                            
                        }

                        console.log(totalPartialsAmount);
                        html += '</tbody></table>';
                        amountDifference = parseFloat(totalAmount) - parseFloat(totalPartialsAmount);
                        var decimals = currencyCode == 'USDT' ? 6 : 8;
                        var remainingAmount = parseFloat(amountDifference).toFixed(decimals);
                        html += '<div class="wc-migpayments-remaining-amount">';
                        
                        html += '<div class="text-left">';
                        html += 'Remaining payment amount: <b>' + remainingAmount  + ' '+ currencyCode  +'</b> </div><span class="wc-migpayments-copy-to-clipboard"><button class="wc-migpayments-copy-btn"  onclick="copyToClipboard('+ remainingAmount + ')">Copy</button></span>';
                        html += '</div>';
                        // each payments
                        partialPaymentsEl.innerHTML = html;
                    
                    }
 
                
                }
                
             
            }
           
        }
           
    }).catch(e => {
        console.log(e)
    });
       
  
 }, 5000);

  
   
 function hideOverpaidModal(){
    
    var overpaidModal =  document.getElementById('wc-overpaid-modal');
    overpaidModal.classList.remove('active');
    overpaidModal.remove();

    var paymentDataContainer = document.getElementById('wc-payment-data-container');
    paymentDataContainer.classList.remove('hide');
    
   
 }
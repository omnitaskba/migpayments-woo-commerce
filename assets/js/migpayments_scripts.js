
var paymentOptionsEl =  document.getElementById('wc-migpayments-payment-options');
var overpaidModal =  document.getElementById('wc-overpaid-modal');
var paymentDataEl =  document.getElementById('wc-migpayments-payment-data');
var currencyCodeEl =  document.getElementById('wc-migpayments-currency-code');
var errorElement =  document.getElementById('wc-migpayments-error');
var blockchainEl =  document.getElementById('wc-migpayments-blockchain');
var orderSummary =  document.getElementById('wc-migpayments-order-summary');
var iconWarning = '<svg style="width:30px;height:30px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#FF0000" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /> </svg> ';

var partialPaymentsEl =  document.getElementById('wc-mipgpayments-partial-payments')
var infoBoxEl =  document.getElementById('wc-migpayments-info-box')

var amountEl = document.getElementById('wc-migpayments-crypto-amount-wrapper');
var overpaidModal =  document.getElementById('wc-overpaid-modal');
var cancelBtn = document.getElementById('wc-migpayments-cancel-btn');
var overpaidModal =  document.getElementById('wc-overpaid-modal');
var estimateEl =  document.getElementById('wc-migpayments-estimate');
var expiryEl = document.getElementById('wc-payment-expiry-item');

var ajaxUrl  = overpaidModal.dataset.url;

function checkOrderStatus(){
    
    const data = new FormData();
   
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
    fetch(ajaxUrl, settings).then(response => response.json()).then(data => {
        // console.log(data);
        //Payment confirmed
        if(data.redirect == true){
           
              
            if(data.redirectUrl)
                 window.location.href = data.redirectUrl;
        } 
        
        if(data.status == 'Overpaid'){
            if(overpaidModal)
                overpaidModal.classList.add('active');
            
             
            var paymentDataContainer = document.getElementById('wc-payment-data-container');
            paymentDataContainer.classList.add('hide');
            
            if(cancelBtn)
                cancelBtn.remove();
        } else{

            if(data.amount && data.crypto_address){
               
                paymentOptionsEl.remove();

                if(cancelBtn)
                    cancelBtn.remove();

                if(amountEl)
                    amountEl.remove();
                
                if(infoBoxEl)
                    infoBoxEl.remove();

                if(data.status != 'Pending'){

                    //Expired order
                    if(data.order_status == 'cancelled' && data.status == 'Expired'){
                        paymentDataEl.innerHTML = '<div class="card" style="padding:10px; text-align:center;color:red;">No payment received.  This order has expired.</div>';
                        return;
                    }
                  
                    //Partial Payments
                    var totalAmount = data.amount;
                    var totalPartialsAmount = 0;
                    var currencyCode = data.currency_code;
                    
                    if(data.partial_payments && data.partial_payments.length > 0){

                        if(orderSummary)
                            orderSummary.remove();
                        
                        let payments = data.partial_payments;
                        for (let i = 0; i < payments.length; i++) {
                        
                            totalPartialsAmount = parseFloat(payments[i].amount) + parseFloat(totalPartialsAmount);
                            
                        }
                        
                        var html = '<div class="wc-migpayments-alert-box"><h3>' + iconWarning + ' Insufficient Payment</h3><p>The payment amount is too low. Please send a remaining amount of cryptocurrency to proceed.</p></div>';
                        console.log(totalAmount)
                        console.log(totalPartialsAmount)
                        amountDifference = parseFloat(totalAmount) - parseFloat(totalPartialsAmount);
                        var decimals = currencyCode == 'USDT' ? 6 : 8;
                        var remainingAmount = parseFloat(amountDifference).toFixed(decimals);
                        html += '<div class="wc-migpayments-remaining-amount">';
                        
                        html += '<div class="text-left wc-migpayments-alert-box">';
                        html += '<label>Remaining payment amount:&nbsp;</label><b>' + remainingAmount  + ' '+ currencyCode  +'</b> </div>';
                        html += '</div>';
 
                        html += '<hr><h5 class="text-left">Received Payment(s)</h5> <table id="wc-migpayments-partial-payments-list" class="table" style="font-size:14px;">';
                        html += '<thead><th style="text-align:left;padding-left:0px;">Amount</th><th style="text-align:right;padding-right:0px;">Received At</th></thead><tbody>'
                        // html += '</div>';
                        for (let i = 0; i < payments.length; i++) {
                        
                            html += '<tr style="border-bottom:none;"><td style="border:none;text-align:left;padding-left:0px;"> <h6 class="text-success">'+ payments[i].amount  + ' <span style="color:#000000" >' +  payments[i].currency_code + '</span></h6> </td><td style="border:none;text-align:right;padding-right:0px;"> ' + payments[i].received_at + '</b></tr>'
                            
                        }

                        html += '</tbody></table>';

                        partialPaymentsEl.innerHTML = html;
                        getExistingPaymentDataHtml();
                    } 
            }
            else {
                getExistingPaymentDataHtml();
            }
                

            
            }  
         
        }
       
           
    }).catch(e => {
        console.log(e)
    });
       
  
}
setInterval(function(){ 
    checkOrderStatus();

}, 5000);

checkOrderStatus();

function getCryptoEstimate(){
     
    const data = new FormData();
    
    var overpaidModal =  document.getElementById('wc-overpaid-modal');
    var estimateEl =  document.getElementById('wc-migpayments-estimate');

    ;

    data.append( 'action', 'migpayments_wc_get_crypto_estimate' );
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

    const fetchResponse = fetch(ajaxUrl, settings).then(function (response) {
        // The API call was successful!
        return response.text();
    }).then(function (html) {
        if(html)
            estimateEl.innerHTML = html;
           
    }).catch(e => {
        console.log(e)
    });
       
}

function getExistingPaymentDataHtml(){
     
    const data = new FormData();
    
    data.append( 'action', 'migpayments_wc_get_existing_payment_data_html' );
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

    const fetchResponse = fetch(ajaxUrl, settings).then(function (response) {
        // The API call was successful!
        return response.text();
    }).then(function (html) {
        if(html)
        {
            paymentDataEl.innerHTML = html;
            expiryCountdown();
        }
    }).catch(e => {
        console.log(e)
    });
       
}

function getCurrencyBlockchains(){
     
    const data = new FormData();
    
    var overpaidModal =  document.getElementById('wc-overpaid-modal');
    var blockchainSelect =  document.getElementById('wc-migpayments-blockchain');
    var blockChainWrapper = document.querySelector('.wc-migpayments-payment-blockchain');
    var loadingEl = blockChainWrapper.querySelector('.wc-migpayments-loading-sm');
 
    loadingEl.style.display = 'block';
    blockChainWrapper.style.display = 'flex';
    blockchainSelect.style.display = 'none';
 
    var currencyCode = document.getElementById('wc-migpayments-currency-code').value;
    console.log(currencyCode);
    ;

    data.append( 'action', 'migpayments_wc_get_currency_blockchains' );
    data.append( 'currency_code' , currencyCode);
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
    blockchainSelect.innerHTML = '';
    const fetchResponse = fetch(ajaxUrl, settings).then(function (response) {
        // The API call was successful!
        return response.json();
    }).then(function (blockchains) {
        console.log(blockchains);
        loadingEl.style.display = 'none';
        blockchainSelect.style.display = 'block';
        if(blockchains ){
            
            Object.entries(blockchains).forEach(([key, value]) => {
               
                const optionElement = document.createElement('option');
                optionElement.textContent = value;
                optionElement.value = key;
                blockchainSelect.appendChild(optionElement);
              });
              blockChainWrapper.style.display = 'flex';
        }
        
           
    }).catch(e => {
        loadingEl.style.display = 'none';
        console.log(e)
    });
       
}


const currencySelect = document.getElementById('wc-migpayments-currency-code');

currencySelect.addEventListener('change', getCurrencyBlockchains);

function expiryCountdown() {
    const countdownElement = document.getElementById('wc-migpayments-expiry-countdown');
    const targetDateStr = countdownElement.getAttribute('data-target-date');
    const targetDate = new Date(targetDateStr * 1000).getTime();
    const expiryEl = document.getElementById('wc-payment-expiry-item');

    if(targetDate &&  !isNaN(targetDate)){
        function updateCountdown() {
            const now = new Date().getTime();
            const timeLeft = targetDate - now;
    
            if (timeLeft <= 0) {
               
                var expiredHtml = '<div class="wc-migpayments-alert-box">' + iconWarning + ' Order status is expired.</div>';

                expiryEl.innerHTML = expiredHtml;

                clearInterval(timer);
                return;
            }
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            var html = '';
    
            if(hours > 0){
                html += hours + 'h ';
            }
            if(minutes > 0){
                html += minutes + 'm ';
            }
            html  += seconds + 's';
            countdownElement.innerHTML = html;
        }
    
        const timer = setInterval(updateCountdown, 1000);
        updateCountdown(); // initial call to display immediately
    } else {
        expiryEl.remove();
    }
   
}

const submitBtn = document.getElementById('wc-migpayments-get-payment-data');
 
submitBtn.addEventListener('click', function() {
    submitBtn.disabled = true;
    getPaymentData();
});

function getPaymentData(){
     
    const data = new FormData();
    
    var currencyCode =  currencyCodeEl.value;
    var blockchainCode =  blockchainEl.value;
    errorElement.innerHTML = '';
    
    if(!currencyCode || currencyCode == '' || currencyCode == ' '){
        
        errorElement.innerHTML = '<div class="wc-migpayments-error">Please choose crypto currency.</div>';
        submitBtn.disabled = false;
        return false;

    }

    if(!blockchainCode || blockchainCode == '' || blockchainCode == ' '){
        
        errorElement.innerHTML = '<div class="wc-migpayments-error">Please choose blockchain.</div>';
        submitBtn.disabled = false;
        return false;

    }



    submitBtn.disabled = true;
   
    paymentOptionsEl.style.display = 'none';
    paymentDataEl.innerHTML = ' <div class="wc-migpayments-loading"></div>';
    
    

    data.append('action', 'migpayments_wc_get_payment_data' );
    data.append('order_id' , overpaidModal.dataset.order_id);
    data.append('currency_code' , currencyCode);
    data.append('block_chain_code', blockchainCode);
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

    const fetchResponse = fetch(ajaxUrl, settings).then(function (response) {
        return response.text();
    }).then(function (html) {
       
        paymentDataEl.innerHTML = html;
           
        expiryCountdown();
    }).catch(e => {
        console.log(e)
    });
       
}
   
 function hideOverpaidModal(){
    //redirect to base URL
    window.location.href = '/';
    
    
 }

 getCryptoEstimate();

 async function copyToClipboard(text) {
   
    try {
        await navigator.clipboard.writeText(text);
        console.log('Content copied to clipboard');
      } catch (err) {
        console.error('Failed to copy: ', err);
      }
  }
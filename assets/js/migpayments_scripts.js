setInterval(function(){ 
    
    const data = new FormData();
    var partialPaymentsEl =  document.getElementById('wc-mipgpayments-partial-payments')
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
        } else{  //PartialPayment
          
            if(data.partial_payments && data.partial_payments.length > 0){
                var html = '<h5>Partial Payments</h5><table id="wc-migpayments-partial-payments-list" class="table">';
                html += '<thead><th>Amount</th><th>Received At</th></thead><tbody>'

                let payments = data.partial_payments;
                for (let i = 0; i < payments.length; i++) {
                    console.log(payments[i])
                    html += '<tr><td> <span class="text-success">'+ payments[i].amount  + '</span> ' +  payments[i].currency_code + '</td><td> ' + payments[i].received_at + '</b></tr>'
                  }
                html += '</tbody></table>';
                // each payments
                partialPaymentsEl.innerHTML = html;
                var cancelBtn = document.getElementById('wc-migpayments-cancel-order-button');
                var reviewBtn = document.getElementById('wc-migpayments-review-order-button');
                cancelBtn.remove();
                reviewBtn.remove();
            }
           
        }
           
    }).catch(e => {
        console.log(e)
    });
       
  
 }, 5000);

  
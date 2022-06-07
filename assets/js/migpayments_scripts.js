setInterval(function(){ 
    
    const data = new FormData();
   
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
        if(data.redirect == true)
            window.location.href = ajaxObj.redirectUrl;
    }).catch(e => {
        console.log(e)
    });
       
  
 }, 5000);

  
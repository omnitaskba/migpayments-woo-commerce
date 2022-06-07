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
    try {
       
        const fetchResponse = fetch(ajaxObj.ajaxurl, settings);
       

        const data = fetchResponse.json();
        console.log(data)
        if(data.redirect)
            window.location.href = ajaxObj.redirectUrl;
      
    } catch (e) {
        
    }       
 }, 5000);

  
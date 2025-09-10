(function($) {
  'use strict';
  
  $('#restaler-notify-form').on( 'submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const nonce = $form.data('nonce');
    const email = $form.find('input[name="email"]').val();
    const product = $form.data('product-id');
    const product_type = $form.data('productType');
    const variation_id = $form.data('variationId');

    console.log(variation_id);
    

    var data = {
      email,
      product,
      product_type,
      variation_id,
      nonce,
      action : 'restaler_save_notify_email'
    };

    $.ajax({
      url: woocommerce_params.ajax_url,
      method: 'POST',
      data,
      success: function(data) {
        $form.find('input[name="email"]').val('');
        $form.append(`<span class="panw-notice">${data}</span>`);
        
        setTimeout( () => {
          $('.panw-notice').remove();
        }, 2000 );
      }
    });
  });
})(jQuery);
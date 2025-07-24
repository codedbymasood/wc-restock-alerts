(function($) {
  'use strict';
  
  $('#prodavno-notify-form').on( 'submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const nonce = $form.data('nonce');
    const email = $form.find('input[name="email"]').val();
    const product = $form.data('product-id');

    var data = {
      email,
      product,
      nonce,
      action : 'prodavno_save_notify_email'
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
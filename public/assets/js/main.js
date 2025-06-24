(function($) {
  'use strict';

  
  $('#paw-notify-form').on( 'submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const nonce = $form.data('nonce');
    const email = $form.find('input[name="email"]').val();
    const product = $form.data('product-id');

    var data = {
      email,
      product,
      nonce,
      action : 'paw_save_notify_email'
    };

    $.ajax({
      url: woocommerce_params.ajax_url,
      method: 'POST',
      data,
      success: function(data) {
        // Add loader.
        // Print notice here once AJAX request completes.
      }
    });

  }); 
  
})(jQuery);
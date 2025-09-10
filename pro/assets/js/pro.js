(function($) {
  'use strict';
  const $form = $('form.variations_form');
  const $notifyContainer = $('#restaler-notify-form');
  const $thresholdMessage = $('#restaler-stock-threshold-message');
  
  // When a variation is found/selected
  $form.on('found_variation', function(event, variation) {
    event.preventDefault();

    $notifyContainer.data( 'variation-id', variation.variation_id );    
    
    if (variation.is_in_stock === false || ( restaler && '1' === restaler.enable_stock_threshold && variation.max_qty <= parseInt( restaler.stock_threshold_count ) ) ) {
      if ( $thresholdMessage.length ) {
        $thresholdMessage.removeClass('hidden');
      }
      $notifyContainer.removeClass('hidden');
    } else {
      if ( $thresholdMessage.length ) {
        $thresholdMessage.addClass('hidden');
      }
      $notifyContainer.addClass('hidden');
    }
  });

  $form.on('reset_data', function() {
    $notifyContainer.addClass('hidden');
  });
})(jQuery);
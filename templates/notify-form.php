<?php
$html    = $args['html'];
$product = $args['product'];

$availability = $product->get_availability();

if ( 'Out of stock' === $availability['availability'] ) {
  $nonce = wp_create_nonce( 'restaler-save-email' );

  $form  = '<form id="restaler-notify-form" method="POST" data-product-id="' . esc_attr( $product->get_id() ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
  $form .= '<input name="email" type="text" placeholder="' . esc_attr__( 'Enter your email address', 'restock-alerts-for-woocommerce' ) . '">';
  $form .= '<button type="submit">' . esc_html__( 'Notify Me', 'restock-alerts-for-woocommerce' ) . '</button>';
  $form .= '</form>';
  echo $html . $form;
}

echo $html;
<?php
/**
 * Notify form template.
 *
 * @package plugin-slug\template\email\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

use STOBOKIT\Utils as Core_Utils;

defined( 'ABSPATH' ) || exit;

$product      = $args['product'];
$product_type = $args['type'];
$hide         = ( $args['hide'] ) ? 'hidden' : '';

$show_signup_message = get_option( 'restaler_show_signup_message', '0' );
$signup_message      = get_option( 'restaler_signup_message', esc_html__( 'Want a reminder when more arrives? Sign up below.', 'plugin-slug' ) );

$nonce = wp_create_nonce( 'restaler-save-email' );

$form = '';
if ( Core_Utils::string_to_bool( $show_signup_message ) ) {
	$form .= '<p id="restaler-stock-threshold-message" class="hidden">' . esc_html( $signup_message ) . '</p>';
}

$form .= '<form id="restaler-notify-form" class="form-product-type-' . esc_attr( $product_type ) . ' ' . esc_attr( $hide ) . '" method="POST" data-product-id="' . esc_attr( $product->get_id() ) . '" data-variation-id data-product-type="' . esc_attr( $product_type ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
$form .= '<input name="email" type="text" placeholder="' . esc_attr__( 'Enter your email address', 'plugin-slug' ) . '">';
$form .= '<button type="submit">' . esc_html__( 'Notify Me', 'plugin-slug' ) . '</button>';
$form .= '</form>';

$allowed_tags = array(
	'form'   => array(
		'id'                => true,
		'class'             => true,
		'method'            => true,
		'data-product-id'   => true,
		'data-product-type' => true,
		'data-variation-id' => true,
		'data-nonce'        => true,
	),
	'input'  => array(
		'name'        => true,
		'type'        => true,
		'placeholder' => true,
		'value'       => true,
	),
	'button' => array(
		'type' => true,
	),
	'p'      => array(
		'id'    => true,
		'class' => true,
	),
);

echo wp_kses( $form, $allowed_tags );

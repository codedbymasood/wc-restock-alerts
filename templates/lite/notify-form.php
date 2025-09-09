<?php
/**
 * Notify form template.
 *
 * @package plugin-slug\template\email\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$product      = $args['product'];
$product_type = $args['type'];
$hide         = ( $args['hide'] ) ? 'hidden' : '';

$nonce = wp_create_nonce( 'restaler-save-email' );

$form  = '<form id="restaler-notify-form" class="form-product-type-' . esc_attr( $product_type ) . ' ' . esc_attr( $hide ) . '" method="POST" data-product-id="' . esc_attr( $product->get_id() ) . '" data-product-type="' . esc_attr( $product_type ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
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
);

echo wp_kses( $form, $allowed_tags );

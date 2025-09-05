<?php
/**
 * Notify form template.
 *
 * @package plugin-slug\template\email\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$html    = $args['html'];
$product = $args['product'];

$availability = $product->get_availability();

if ( 'Out of stock' === $availability['availability'] ) {
	$nonce = wp_create_nonce( 'restaler-save-email' );

	$form  = '<form id="restaler-notify-form" method="POST" data-product-id="' . esc_attr( $product->get_id() ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
	$form .= '<input name="email" type="text" placeholder="' . esc_attr__( 'Enter your email address', 'plugin-slug' ) . '">';
	$form .= '<button type="submit">' . esc_html__( 'Notify Me', 'plugin-slug' ) . '</button>';
	$form .= '</form>';

	$allowed_tags = array(
		'form'   => array(
			'id'              => true,
			'method'          => true,
			'data-product-id' => true,
			'data-nonce'      => true,
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

	return;
}

echo wp_kses_post( $html );

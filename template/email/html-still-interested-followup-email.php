<?php
/**
 * Still interested folowup email template.
 *
 * @package product-availability-notifier-for-woocommerce\template\email\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$product = isset( $args['product'] ) ? $args['product'] : 0;

if ( ! $product ) {
	die();
}

$discount_type     = isset( $args['discount_type'] ) ? $args['discount_type'] : 'percentage';
$amount            = isset( $args['amount'] ) ? $args['amount'] : 20;
$coupon_expires_in = isset( $args['coupon_expires_in'] ) ? $args['coupon_expires_in'] : 3;
?>
<html>
	<head>
		<title>Still on your mind?</title>
	</head>
	<body>
		<div>
			<p>Hi,</p>
			<p>We just wanted to check in - did you get a chance to explore <?php echo esc_html( $product->get_title() ); ?>?</p>

			<p>If you're still thinking it over, no rush! We're here if you have any questions or need help deciding if it's the right fit for you.</p>

			<p>👉 <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php esc_html_e( 'View Product', 'product-availability-notifier-for-woocommerce' ); ?></a></p>

			<p>Sometimes all it takes is a second look. 😊</p>

			<p>Thanks again for your interest!</p>
		</div>
	</body>
</html>


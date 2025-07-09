<?php
/**
 * Urgency folowup email template.
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
		<title>Your <?php echo esc_html( $amount ); ?><?php echo ( 'percentage' === $discount_type ) ? '%' : ''; ?> Discount Is About to Expire</title>
	</head>
	<body>
		<div>
			<p>Hi,</p>

			<p>Just a quick reminder - your <?php echo esc_html( $amount ); ?><?php echo ( 'percentage' === $discount_type ) ? '%' : ''; ?> off discount code is expiring in <?php echo esc_html( $coupon_expires_in ); ?> days!</p>

			<p>If you've been thinking about getting <?php echo esc_html( $product->get_title() ); ?>, now's the perfect time. This is your last chance to grab it at a lower price before the offer disappears.</p>

			<p>🎁 Use code: <?php echo esc_html( $coupon ); ?></p>
			<p>🕒 Expires in: <?php echo esc_html( $coupon_expires_in ); ?> days</p>

			<p>👉 <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php esc_html_e( 'Buy Now', 'product-availability-notifier-for-woocommerce' ); ?></a></p>

			<p><?php esc_html_e( 'Don\'t miss out - after this, it\'s back to full price.', 'product-availability-notifier-for-woocommerce' ); ?></p>
		</div>
	</body>
</html>

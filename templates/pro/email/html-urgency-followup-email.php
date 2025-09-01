<?php
/**
 * Urgency folowup email template.
 *
 * @package restock-alerts-for-woocommerce\template\email\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$product = isset( $args['product'] ) ? $args['product'] : 0;

$product_id = $product->get_id();

if ( ! $product_id ) {
	return;
}

$discount_type     = isset( $args['discount_type'] ) ? $args['discount_type'] : 'percent';
$amount            = isset( $args['amount'] ) ? $args['amount'] : 20;
$coupon            = isset( $args['coupon'] ) ? $args['coupon'] : '';
$coupon_expires_in = isset( $args['coupon_expires_in'] ) ? $args['coupon_expires_in'] : 3;
?>
<html>
	<head>
		<title>
			<?php
			printf(
				/* translators: %1$s: Amount,  %2$s: Percentage */
				esc_html__( 'Your %1$s%2$s Discount Is About to Expire', 'restock-alerts-for-woocommerce' ),
				esc_html( $amount ),
				esc_html( ( 'percent' === $discount_type ) ? '%' : '' )
			);
			?>
		</title>
	</head>
	<body>
		<div>
			<p><?php esc_html_e( 'Hi,', 'restock-alerts-for-woocommerce' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %1$s: Amount,  %2$s: Percentage,  %3$s: Coupon expiration days */
					esc_html__( 'Just a quick reminder - your %1$s%2$s off discount code is expiring in %3$s', 'restock-alerts-for-woocommerce' ),
					esc_html( $amount ),
					esc_html( ( 'percent' === $discount_type ) ? '%' : '' ),
					esc_html( $coupon_expires_in )
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: Product title */
					esc_html__( 'If you\'ve been thinking about getting %s, now\'s the perfect time. This is your last chance to grab it at a lower price before the offer disappears.', 'restock-alerts-for-woocommerce' ),
					esc_html( $product->get_title() )
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: Coupon code */
					esc_html__( 'Use code: %s', 'restock-alerts-for-woocommerce' ),
					esc_html( $coupon )
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: Coupon expiration days */
					esc_html__( 'Expires in: %s days', 'restock-alerts-for-woocommerce' ),
					esc_html( $coupon_expires_in )
				);
				?>
			</p>
			<p>👉 <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php esc_html_e( 'Buy Now', 'restock-alerts-for-woocommerce' ); ?></a></p>

			<p><?php esc_html_e( 'Don\'t miss out - after this, it\'s back to full price.', 'restock-alerts-for-woocommerce' ); ?></p>
		</div>
	</body>
</html>

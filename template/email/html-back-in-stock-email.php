<?php
/**
 * Back in stock email template.
 *
 * @package product-availability-notifier-for-woocommerce\template\email\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$product = wc_get_product( $product_id );
?>
<html>
	<head>
		<title>Back in Stock!</title>
	</head>
	<body>
		<div>
			<h2><?php esc_html_e( 'Good news!', 'product-availability-notifier-for-woocommerce' ); ?></h2>
			<p>
				The product you were waiting for is now back in stock
			</p>
			<h3><?php echo esc_html( $product->get_name() ); ?></h3>
			<p>
				<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php esc_html_e( 'Buy Now', 'product-availability-notifier-for-woocommerce' ); ?></a>
			</p>
			<p>
				<?php esc_html_e( 'Don\'t wait too long, popular products sell out quickly!', 'product-availability-notifier-for-woocommerce' ); ?>
			</p>
		</div>
	</body>
</html>


<?php
/**
 * Verification email template.
 *
 * @package restock-alerts-for-woocommerce\template\email\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<html>
	<body>
		<div class="email-container">
			<h1><?php esc_html_e( 'Confirm your email', 'restock-alerts-for-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Hi there,', 'restock-alerts-for-woocommerce' ); ?></p>
			<p><?php esc_html_e( 'Thanks for signing up. Please confirm your email address by clicking the button below:', 'restock-alerts-for-woocommerce' ); ?></p>

			<a href="<?php echo esc_url( $verify_url ); ?>" class="button"><?php esc_html_e( 'Verify Email', 'restock-alerts-for-woocommerce' ); ?></a>

			<p><?php esc_html_e( 'If the button doesn\'t work, copy and paste this link into your browser:', 'restock-alerts-for-woocommerce' ); ?></p>
			<p><a href="<?php echo esc_url( $verify_url ); ?>"><?php echo esc_html( $verify_url ); ?></a></p>

			<div class="footer">
				<p><?php esc_html_e( 'This verification link will expire soon. If you did not request this, please ignore this email.', 'restock-alerts-for-woocommerce' ); ?></p>
			</div>
		</div>
	</body>
</html>

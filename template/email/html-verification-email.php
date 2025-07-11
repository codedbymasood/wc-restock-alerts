<?php
/**
 * Verification email template.
 *
 * @package product-availability-notifier-for-woocommerce\template\email\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TODO:
 * Instead of home_url() needs to add a shortcode that shown succuessfully subscribed notify notice
 */

$verify_url = add_query_arg(
	array(
		'verify_email' => 1,
		'email'        => rawurlencode( $email ),
		'token'        => $token,
	),
	home_url()
);
?>
<html>
	<body>
		<div class="email-container">
			<h1><?php esc_html_e( 'Confirm your email', 'product-availability-notifier-for-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Hi there,' ); ?></p>
			<p><?php esc_html_e( 'Thanks for signing up. Please confirm your email address by clicking the button below:', 'product-availability-notifier-for-woocommerce' ); ?></p>

			<a href="<?php echo esc_url( $verify_url ); ?>" class="button"><?php esc_html_e( 'Verify Email', 'product-availability-notifier-for-woocommerce' ); ?></a>

			<p><?php esc_html_e( 'If the button doesn\'t work, copy and paste this link into your browser:' ); ?></p>
			<p><a href="<?php echo esc_url( $verify_url ); ?>"><?php echo esc_html( $verify_url ); ?></a></p>

			<div class="footer">
				<p><?php esc_html_e( 'This verification link will expire soon. If you did not request this, please ignore this email.', 'product-availability-notifier-for-woocommerce' ); ?></p>
			</div>
		</div>
	</body>
</html>

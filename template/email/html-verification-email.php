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
 * Instead of home_url() needs to add a shortcode that shown succuessfully added notify notice
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
			<h1>Confirm your email</h1>
			<p>Hi there,</p>
			<p>Thanks for signing up. Please confirm your email address by clicking the button below:</p>

			<a href="<?php echo esc_url( $verify_url ); ?>" class="button">Verify Email</a>

			<p>If the button doesn't work, copy and paste this link into your browser:</p>
			<p><a href="<?php echo esc_url( $verify_url ); ?>"><?php echo esc_html( $verify_url ); ?></a></p>

			<div class="footer">
				<p>This verification link will expire soon. If you did not request this, please ignore this email.</p>
			</div>
		</div>
	</body>
</html>

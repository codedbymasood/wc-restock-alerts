<?php
/**
 * Email header template.
 *
 * @package plugin-slug\template\email\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$heading = isset( $args['heading'] ) ? $args['heading'] : '';
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
		<!-- Add CSS here -->
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">

		<div>
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<!-- Logo comes here -->
					<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_container">
						<tr>
							<td align="center" valign="top">
								<!-- Header -->
								<table border="0" cellpadding="0" cellspacing="0" width="520">
									<tr>
										<td>
											<h1><?php echo esc_html( $heading ); ?></h1>
										</td>
									</tr>
								</table>
								<!-- End Header -->
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<!-- Body -->
								<table border="0" cellpadding="0" cellspacing="0" width="520">
									<tr>
										<td valign="top">
											<!-- Content -->
											<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top">
														<div>
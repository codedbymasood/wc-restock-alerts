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
		<style>
			#template-body {
				font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
				background: #F8FAFC;
				padding: 120px;
			}

			#template-body h1,
			#template-body h2 {
				text-align: center;
				font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
				font-weight: 400;
				font-size: 28px;
				margin: 0;
			}

			#template-body h2 {
				font-size: 24px;				
				margin: 12px 0;
			}

			#template-container {
				background: #fff;
				padding: 40px 80px;
				border: 1px solid #E2E8F0;
			}

			#template-container a.btn-primary {
				font-weight: 500;
				background: #6366F1;
				padding: 10px 20px;
				display: inline-block;
				color: #fff;
				text-decoration: none;
				border-radius: 6px;
				margin: 8px 0;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">

		<div>
		<table id="template-body" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<!-- Logo comes here -->
					<table border="0" cellpadding="0" cellspacing="0" width="520" id="template-container">
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
													<td valign="top" style="padding: 0px">
														<div>
<?php
/**
 * Table holds all the notify details.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

defined( 'ABSPATH' ) || exit;

/**
 * Table holds all the notify details.
 */
class Notify_List_Table_Pro {

	public function __construct() {

		add_filter(
			'restock_alerts_table_columns',
			function () {
				return array(
					'cb'         => '<input type="checkbox" />',
					'id'         => 'ID',
					'email'      => 'Email',
					'product_id' => esc_html__( 'Product ID', 'plugin-slug' ),
					'status'     => esc_html__( 'Status', 'plugin-slug' ),
					'created_at' => esc_html__( 'Created At', 'plugin-slug' ),
				);
			},
			99
		);
	}
}

new Notify_List_Table_Pro();

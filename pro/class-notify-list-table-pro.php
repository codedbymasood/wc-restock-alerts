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
					'cb'           => '<input type="checkbox" />',
					'id'           => 'ID',
					'email'        => 'Email',
					'product_id'   => esc_html__( 'Product ID', 'plugin-slug' ),
					'variation_id' => esc_html__( 'Variation', 'plugin-slug' ),
					'status'       => esc_html__( 'Status', 'plugin-slug' ),
					'created_at'   => esc_html__( 'Created At', 'plugin-slug' ),
				);
			},
			99
		);

		add_filter( 'restock_alerts_table_column_default_variation_id', array( $this, 'column_variation_id' ), 99, 3 );
	}

	/**
	 * Variation ID column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_variation_id( $html = '', $item = array(), $column_name = '' ) {
		$variation_id = $item['variation_id'] ? $item['variation_id'] : 0;

		$variation_name = '';

		$variation = wc_get_product( $variation_id );

		if ( $variation && $variation->is_type( 'variation' ) ) {
			// Get variation attributes.
			$attributes = $variation->get_variation_attributes();

			// Get formatted variation name.
			$variation_name = wc_get_formatted_variation( $attributes, true );
		}
		return ( $variation_name ) ? sprintf( 'Variation: %s', esc_html( $variation_name ) ) : '';
	}
}

new Notify_List_Table_Pro();

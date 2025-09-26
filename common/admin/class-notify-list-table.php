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
class Notify_List_Table extends \STOBOKIT\List_Table {
	/**
	 * Constructor.
	 *
	 * @param array $args Arguements.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

		add_filter( 'restock_alerts_table_columns', array( $this, 'custom_columns' ) );
		add_filter( 'restock_alerts_table_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * Custom columns.
	 *
	 * @return array
	 */
	public function custom_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'id'           => esc_html__( 'ID', 'plugin-slug' ),
			'email'        => esc_html__( 'Email', 'plugin-slug' ),
			'product_id'   => esc_html__( 'Product', 'plugin-slug' ),
			'variation_id' => esc_html__( 'Variation', 'plugin-slug' ),
			'status'       => esc_html__( 'Status', 'plugin-slug' ),
			'created_at'   => esc_html__( 'Created At', 'plugin-slug' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function sortable_columns() {
		return array(
			'id'         => array( 'id', true ),
			'email'      => array( 'email', false ),
			'status'     => array( 'status', false ),
			'created_at' => array( 'created_at', false ),
		);
	}

	/**
	 * Email column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_email( $item ) {
		return isset( $item['email'] ) ? $item['email'] : '';
	}

	/**
	 * Status column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_status( $item ) {
		return $item['status'] ? $item['status'] : '-';
	}

	/**
	 * Product ID column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_product_id( $item ) {
		$product_id = $item['product_id'] ? $item['product_id'] : 0;

		$product = wc_get_product( $product_id );
		return ( null !== $product ) ? $product->get_name() : '';
	}

	/**
	 * Variation ID column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_variation_id( $item = array() ) {
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

	/**
	 * Created at column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_created_at( $item ) {
		return $item['created_at'];
	}
}

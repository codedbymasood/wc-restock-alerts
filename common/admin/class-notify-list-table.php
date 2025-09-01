<?php
/**
 * Table holds all the notify details.
 *
 * @package restock-alerts-for-woocommerce\admin\
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
			'cb'         => '<input type="checkbox" />',
			'id'         => esc_html__( 'ID', 'restock-alerts-for-woocommerce' ),
			'email'      => esc_html__( 'Email', 'restock-alerts-for-woocommerce' ),
			'product_id' => esc_html__( 'Product ID', 'restock-alerts-for-woocommerce' ),
			'status'     => esc_html__( 'Status', 'restock-alerts-for-woocommerce' ),
			'created_at' => esc_html__( 'Created At', 'restock-alerts-for-woocommerce' ),
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
		return $item['product_id'] ? $item['product_id'] : '';
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

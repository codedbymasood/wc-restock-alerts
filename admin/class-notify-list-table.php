<?php
/**
 * Table holds all the notify details.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace SBK_RAW;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Table holds all the notify details.
 */
class Notify_List_Table extends \WP_List_Table {

	private $data = [];
	private $total_items;

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'notification',
				'plural'   => 'notifications',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'id'         => esc_html__( 'ID', 'restock-alerts-for-woocommerce' ),
			'email'      => esc_html__( 'Email', 'restock-alerts-for-woocommerce' ),
			'product_id' => esc_html__( 'Product ID', 'restock-alerts-for-woocommerce' ),
			'status'     => esc_html__( 'Status', 'restock-alerts-for-woocommerce' ),
			'created_at' => esc_html__( 'Created At', 'restock-alerts-for-woocommerce' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'id'         => array( 'id', true ),
			'email'      => array( 'email', false ),
			'status'     => array( 'status', false ),
			'created_at' => array( 'created_at', false ),
		);
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="notification[]" value="%s" />', $item['id'] );
	}

	public function column_id( $item ) {
		return isset( $item['id'] ) ? $item['id'] : '';
	}

	public function column_email( $item ) {
		return isset( $item['email'] ) ? $item['email'] : '';
	}

	public function column_status( $item ) {
		return $item['status'] ? $item['status'] : '-';
	}

	public function column_product_id( $item ) {
		return $item['product_id'] ? $item['product_id'] : '';
	}

	public function column_created_at( $item ) {
		return $item['created_at'];
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => esc_html__( 'Delete permanently', 'restock-alerts-for-woocommerce' ),
		);
	}

	private function process_bulk_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'delete' === $this->current_action() && ! empty( $_REQUEST['notification'] ) ) {
			global $wpdb;

			$table = $wpdb->prefix . 'sbk_raw_product_notify';
			$ids   = array_map( 'absint', $_REQUEST['notification'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! empty( $ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$query        = "DELETE FROM $table WHERE id IN ($placeholders)";

				$wpdb->query( $wpdb->prepare( $query, ...$ids ) );
			}
		}
	}

	public function prepare_items() {
		$this->process_bulk_actions();
		global $wpdb;

		$table = $wpdb->prefix . 'sbk_raw_product_notify';

		$per_page     = 10;
		$current_page = $this->get_pagenum();

		$offset = ( $current_page - 1 ) * $per_page;

		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = ! empty( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // WPCS: cache ok, db call ok.

		$this->data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		); // WPCS: cache ok, db call ok.

		$this->items = $this->data;

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->total_items / $per_page ),
			)
		);
	}
}

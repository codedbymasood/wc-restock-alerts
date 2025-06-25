<?php
/**
 * Table holds all the notify details.
 *
 * @package product-availability-notifier-for-woocommerce\admin\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/*
	** TODO:
	--------
	* Bulk select, delete, sort, search
	* Import/Export
*/

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

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
			'cb'          => '<input type="checkbox" />',
			'id'          => 'ID',
			'email'       => 'Email',
			'product_id' => 'Product ID',
			'status'      => 'Status',
			'created_at'  => 'Created At',
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

	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'paw_product_notify';
		$per_page = 10;
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;

		$orderby = ! empty( $_GET['orderby'] ) ? esc_sql( $_GET['orderby'] ) : 'id';
		$order   = ! empty( $_GET['order'] ) ? esc_sql( $_GET['order'] ) : 'DESC';

		$this->total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		$this->data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

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

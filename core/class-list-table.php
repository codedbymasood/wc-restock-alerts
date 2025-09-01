<?php
/**
 * Table holds all the notify details.
 *
 * Usage Examples:
 * ==============
 * add_filter( 'your_table_where_clause_filter', function( $filter_data ) {
 *   // Add custom condition: only show active items
 *   $filter_data['where_clause'] .= ' AND status = %s';
 *   $filter_data['where_params'][] = 'active';

 *   // Add date filter
 *   if ( isset( $_GET['date_from'] ) && ! empty( $_GET['date_from'] ) ) {
 *     $filter_data['where_clause'] .= ' AND created_date >= %s';
 *     $filter_data['where_params'][] = sanitize_text_field( $_GET['date_from'] );
 *   } *
 *   return $filter_data;
 * });
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Table holds all the notify details.
 */
class List_Table extends \WP_List_Table {

	/**
	 * Table ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $table_name;

	/**
	 * Table title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Singular.
	 *
	 * @var string
	 */
	public $singular;

	/**
	 * Plural.
	 *
	 * @var string
	 */
	public $plural;

	/**
	 * Total items.
	 *
	 * @var integer
	 */
	private $total_items;

	/**
	 * Constructor.
	 *
	 * @param array $args Arguements.
	 */
	public function __construct( $args = array() ) {
		$this->id         = $args['id'];
		$this->title      = $args['title'];
		$this->singular   = $args['singular'];
		$this->plural     = $args['plural'];
		$this->table_name = $args['table_name'];

		parent::__construct(
			array(
				'singular' => $this->singular,
				'plural'   => $this->plural,
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return apply_filters(
			$this->id . '_table_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'id' => esc_html__( 'ID', 'store-boost-kit' ),
			)
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			$this->id . '_table_sortable_columns',
			array(
				'id' => array( 'id', true ),
			)
		);
	}

	public function get_searchable_columns() {
		return apply_filters(
			$this->id . '_table_searchable_columns',
			array( 'id' ),
		);
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%s[]" value="%s" />', esc_attr( $this->singular ), esc_attr( $item['id'] ) );
	}

	public function column_id( $item ) {
		return isset( $item['id'] ) ? $item['id'] : '';
	}

	public function column_default( $item, $column_name ) {
		$content = apply_filters( $this->id . '_table_column_default_' . $column_name, '', $item, $column_name );

		return $content;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return apply_filters(
			$this->id . '_table_bulk_actions',
			array(
				'delete' => esc_html__( 'Delete permanently', 'store-boost-kit' ),
			)
		);
	}

	/**
	 * Get the nonce action name for this table.
	 *
	 * @return string
	 */
	private function get_nonce_action() {
		return $this->id . '_bulk_action_nonce';
	}

	/**
	 * Get the nonce field name for this table.
	 *
	 * @return string
	 */
	private function get_nonce_field() {
		return $this->id . '_nonce';
	}

	private function process_bulk_actions() {

		$current_action = $this->current_action();

		// Verify nonce before processing any bulk actions.
		if ( ! empty( $current_action ) ) {
			check_admin_referer( $this->get_nonce_action(), $this->get_nonce_field() );
		}

		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		// Process delete action.
		if ( 'delete' === $current_action && ! empty( $_REQUEST[ $this->singular ] ) ) {

			$ids   = array_map( 'absint', $_REQUEST[ $this->singular ] );

			if ( ! empty( $ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$query        = "DELETE FROM $table WHERE id IN ($placeholders)";

				$wpdb->query( $wpdb->prepare( $query, ...$ids ) );
			}
		}

		do_action( $this->id . '_process_bulk_action', $current_action, $table, $this );
	}

	public function prepare_items() {
		$this->process_bulk_actions();
		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		$per_page     = 10;
		$current_page = $this->get_pagenum();

		$offset = ( $current_page - 1 ) * $per_page;

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id';
		$order   = ! empty( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$where_clause = '1=1';
		$where_params = array();

		if ( ! empty( $search ) ) {
			$searchable_columns = $this->get_searchable_columns();
			if ( ! empty( $searchable_columns ) ) {
				$search_conditions = array();

				foreach ( $searchable_columns as $column ) {
					$search_conditions[] = "$column LIKE %s";
					$where_params[] = '%' . $wpdb->esc_like( $search ) . '%';
				}

				if ( ! empty( $search_conditions ) ) {
					$where_clause .= ' AND (' . implode( ' OR ', $search_conditions ) . ')';
				}
			}
		}

		// Apply custom filter for additional WHERE conditions.
		$filter_data = apply_filters(
			$this->id . '_where_clause_filter',
			array(
				'where_clause' => $where_clause,
				'where_params' => $where_params,
				'table'        => $table,
				'search'       => $search,
			)
		);

		$where_clause = $filter_data['where_clause'];
		$where_params = $filter_data['where_params'];

		// Count query.
		if ( ! empty( $where_params ) ) {
			$count_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
			$this->total_items = $wpdb->get_var( $wpdb->prepare( $count_query, $where_params ) );
		} else {
			$this->total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where_clause" );
		}

		// Get the actual data.
		$query = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$query_params = array_merge( $where_params, array( $per_page, $offset ) );

		$this->items = $wpdb->get_results(
			$wpdb->prepare( $query, $query_params ),
			ARRAY_A
		); // WPCS: cache ok, db call ok.

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->total_items / $per_page ),
			)
		);
	}

	/**
	 * Print table.
	 *
	 * @return void
	 */
	public function display_table() {
		echo '<div class="stobokit-wrapper no-spacing">';
		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $this->title ) . '</h1>';

		$this->prepare_items();
		echo '<form method="post">';

		// Add nonce field.
		wp_nonce_field( $this->get_nonce_action(), $this->get_nonce_field() );

		$this->display();
		echo '</form>';
		echo '</div>';
		echo '</div>';
	}
}

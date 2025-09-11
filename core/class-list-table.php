<?php
/**
 * Table holds all the notify details.
 *
 * Usage Examples:
 * ==============
 *  // Basic filter for active status
 *  add_filter( '{table_id}_table_where_clause_filter', 'filter_active_items_only', 10, 1 );
 *  function filter_active_items_only( $filter_data ) {
 *    // Only show active items
 *    $filter_data['conditions'][] = array(
 *      'sql' => '`status` = %s',
 *      'params' => array( 'active' )
 *    );
 *
 *    return $filter_data;
 *  }
 *
 *  // Date range filter
 *  add_filter( '{table_id}_table_where_clause_filter', 'filter_by_date_range', 10, 1 );
 *  function filter_by_date_range( $filter_data ) {
 *    // Filter items created in the last 30 days
 *    $filter_data['conditions'][] = array(
 *      'sql' => '`created_date` >= %s',
 *      'params' => array( date( 'Y-m-d', strtotime( '-30 days' ) ) )
 *    );
 *
 *    return $filter_data;
 *  }
 *
 * @package plugin-slug\core\
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

		do_action( $this->id . '_table_init', $this );

		$this->handle_csv_export();
	}

	public function get_columns() {
		return apply_filters(
			$this->id . '_table_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'id' => esc_html__( 'ID', 'plugin-slug' ),
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
				'delete' => esc_html__( 'Delete permanently', 'plugin-slug' ),
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

		// Verify nonce before processing any bulk actions.
		if ( ! empty( $_REQUEST['action'] ) || ! empty( $_REQUEST['action2'] ) ) {
			check_admin_referer( $this->get_nonce_action(), $this->get_nonce_field() );
		}

		$current_action = $this->current_action();

		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		// Process delete action.
		if ( 'delete' === $current_action && ! empty( $_REQUEST[ $this->singular ] ) ) {

			$ids = array_map( 'absint', $_REQUEST[ $this->singular ] );

			if ( ! empty( $ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$query        = "DELETE FROM $table WHERE id IN ($placeholders)";

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( $wpdb->prepare( $query, ...$ids ) );
			}
		}

		if ( 'export_csv' === $current_action ) {
			if ( empty( $_REQUEST[ $this->singular ] ) ) {
				return;
			}

			$ids = array_map( 'absint', $_REQUEST[ $this->singular ] );
			$this->generate_csv_export( $ids );
		}

		do_action( $this->id . '_table_process_bulk_action', $current_action, $table, $this );
	}

	public function prepare_items() {
		$this->process_bulk_actions();
		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Validate orderby against allowed columns.
		$allowed_orderby   = $this->get_sortable_columns();
		$allowed_orderby   = array_keys( $allowed_orderby );
		$allowed_orderby[] = 'id';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby, true )
			? sanitize_key( $_GET['orderby'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 'id';

		// Validate order.
		$order = ! empty( $_GET['order'] ) && strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) === 'ASC' ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$where_conditions = array( '1=1' );
		$where_params     = array();

		// Search with validated columns.
		if ( ! empty( $search ) ) {
			$searchable_columns = $this->get_searchable_columns();

			if ( ! empty( $searchable_columns ) ) {
				$search_conditions = array();

				foreach ( $searchable_columns as $column ) {
					$search_conditions[] = "`$column` LIKE %s";
					$where_params[] = '%' . $wpdb->esc_like( $search ) . '%';
				}

				if ( ! empty( $search_conditions ) ) {
					$where_conditions[] = '(' . implode( ' OR ', $search_conditions ) . ')';
				}
			}
		}

		// Apply custom filter with validation.
		$filter_data = apply_filters(
			$this->id . '_table_where_clause_filter',
			array(
				'conditions' => array(),
				'params'     => array(),
				'table'      => $table,
				'search'     => $search,
			)
		);

		// Process filter conditions securely.
		if ( ! empty( $filter_data['conditions'] ) && is_array( $filter_data['conditions'] ) ) {
			foreach ( $filter_data['conditions'] as $condition ) {
				if ( $this->is_valid_filter_condition( $condition ) ) {
					$where_conditions[] = $condition['sql'];
					if ( ! empty( $condition['params'] ) ) {
						$where_params = array_merge( $where_params, $condition['params'] );
					}
				}
			}
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$count_query = "SELECT COUNT(*) FROM `$table` WHERE $where_clause";
		$this->total_items = empty( $where_params )
			? $wpdb->get_var( "SELECT COUNT(*) FROM `$table` WHERE $where_clause" )
			: $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table` WHERE $where_clause", $where_params ) );

		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `$table` WHERE $where_clause ORDER BY `$orderby` $order LIMIT %d OFFSET %d",
				array_merge( $where_params, array( $per_page, $offset ) )
			),
			ARRAY_A
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->total_items / $per_page ),
			)
		);
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' === $which && apply_filters( $this->id . '_table_allow_export_csv', false ) ) {
			$export_url = add_query_arg(
				array(
					'export_csv' => $this->id,
					'_wpnonce'   => wp_create_nonce( $this->id . '_export_csv_nonce' ),
				)
			);

			echo '<div class="alignleft actions">';
			echo '<a href="' . esc_url( $export_url ) . '" class="button button-secondary">' . esc_html__( 'Export All to CSV', 'plugin-slug' ) . '</a>';
			echo '</div>';
		}

		do_action( $this->id . '_table_extra_tablenav', $which );
	}

	private function is_valid_filter_condition( $condition ) {
		return is_array( $condition )
			&& isset( $condition['sql'] )
			&& is_string( $condition['sql'] )
			&& ! empty( $condition['sql'] );
	}

	/**
	 * Handle CSV export via URL parameter
	 */
	public function handle_csv_export() {
		if ( ! isset( $_GET['export_csv'] ) || $_GET['export_csv'] !== $this->id ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export data.', 'plugin-slug' ) );
		}

		// Verify nonce.
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $this->id . '_export_csv_nonce' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'plugin-slug' ) );
		}

		$this->generate_csv_export();
	}

	/**
	 * Export all items to CSV
	 */
	public function export_all_to_csv() {
		$this->generate_csv_export();
	}

	public function get_csv_columns() {
		$default_columns = $this->get_columns();
		unset( $default_columns['cb'] );

		return apply_filters(
			$this->id . '_table_csv_export_columns',
			$default_columns
		);
	}

	/**
	 * Export all items to CSV
	 */
	public function generate_csv_export( $ids = array() ) {
		// Clean any previous output.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		// Get columns for CSV export.
		$columns = $this->get_csv_columns();

		$where_conditions = array( '1=1' );
		$where_params     = array();

		if ( ! empty( $ids ) ) {
			$placeholders       = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$where_conditions[] = "id IN ($placeholders)";
			$where_params       = array_merge( $where_params, $ids );
		}

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		// Search functionality.
		if ( ! empty( $search ) ) {
			$searchable_columns = $this->get_searchable_columns();

			if ( ! empty( $searchable_columns ) ) {
				$search_conditions = array();

				foreach ( $searchable_columns as $column ) {
					$search_conditions[] = "`$column` LIKE %s";
					$where_params[] = '%' . $wpdb->esc_like( $search ) . '%';
				}

				if ( ! empty( $search_conditions ) ) {
					$where_conditions[] = '(' . implode( ' OR ', $search_conditions ) . ')';
				}
			}
		}

		// Apply custom filters.
		$filter_data = apply_filters(
			$this->id . '_table_where_clause_filter',
			array(
				'conditions' => array(),
				'params'     => array(),
				'table'      => $table,
				'search'     => $search,
			)
		);

		if ( ! empty( $filter_data['conditions'] ) && is_array( $filter_data['conditions'] ) ) {
			foreach ( $filter_data['conditions'] as $condition ) {
				if ( $this->is_valid_filter_condition( $condition ) ) {
					$where_conditions[] = $condition['sql'];
					if ( ! empty( $condition['params'] ) ) {
						$where_params = array_merge( $where_params, $condition['params'] );
					}
				}
			}
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Get data.
		$query = "SELECT * FROM `$table` WHERE $where_clause ORDER BY id DESC";

		$results = empty( $where_params )
				? $wpdb->get_results( "SELECT * FROM `$table` WHERE $where_clause ORDER BY id DESC", ARRAY_A )
				: $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$table` WHERE $where_clause ORDER BY id DESC", $where_params ), ARRAY_A );

		// Generate filename.
		$filename = sanitize_file_name( $this->plural . '_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv' );

		// Set headers for CSV download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Create file pointer.
		$output = fopen( 'php://output', 'w' );

		// Add BOM for UTF-8.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Add column headers.
		$csv_headers = array();
		foreach ( $columns as $key => $label ) {
			$csv_headers[] = wp_strip_all_tags( $label );
		}
		fputcsv( $output, $csv_headers );

		// Add data rows.
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$csv_row = array();
				foreach ( array_keys( $columns ) as $column_key ) {
					if ( method_exists( $this, 'column_' . $column_key ) ) {
						// Use the column method to format data.
						$value = call_user_func( array( $this, 'column_' . $column_key ), $row );

						$csv_row[] = wp_strip_all_tags( $value );
					} elseif ( $this->column_default( $row, $column_key ) ) {
						$value = $this->column_default( $row, $column_key );

						$csv_row[] = wp_strip_all_tags( $value );
					} elseif ( isset( $row[ $column_key ] ) ) {
						$csv_row[] = $row[ $column_key ];
					}
				}

				fputcsv( $output, $csv_row );
			}
		}

		fclose( $output );
		exit;
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

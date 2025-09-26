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
		add_filter( 'restock_alerts_table_allow_export_csv', '__return_true' );
		add_filter( 'restock_alerts_table_csv_export_columns', array( $this, 'csv_export_columns' ), 99 );
		add_filter( 'restock_alerts_table_bulk_actions', array( $this, 'bulk_actions' ), 99 );
	}

	public function bulk_actions( $actions = array() ) {
		$actions['export_csv'] = esc_html__( 'Export to CSV', 'plugin-slug' );
		return $actions;
	}

	public function csv_export_columns() {
		$columns = $this->default_columns();
		unset( $columns['cb'] );

		return $columns;
	}
}

new Notify_List_Table_Pro();

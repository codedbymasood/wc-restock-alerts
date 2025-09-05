<?php
/**
 * Table holds all the notify details.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Table holds all the notify details.
 */
class Cron_Table extends \STOBOKIT\List_Table {
	/**
	 * Constructor.
	 *
	 * @param array $args Arguements.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

		add_filter( 'scheduler_logs_table_columns', array( $this, 'custom_columns' ) );
		add_filter( 'scheduler_logs_table_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * Custom columns.
	 *
	 * @return array
	 */
	public function custom_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'id'         => esc_html__( 'ID', 'plugin-slug' ),
			'hook_name'  => esc_html__( 'Hook', 'plugin-slug' ),
			'status'     => esc_html__( 'Status', 'plugin-slug' ),
			'args'       => esc_html__( 'Arguments', 'plugin-slug' ),
			'created_at' => esc_html__( 'Created At', 'plugin-slug' ),
			'schedule'   => esc_html__( 'Schedule', 'plugin-slug' ),
			'next_run'   => esc_html__( 'Next Run', 'plugin-slug' ),
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
	 * Created at column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_created_at( $item ) {
		return $item['created_at'];
	}

	/**
	 * Created at column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_hook_name( $item ) {
		return $item['hook_name'];
	}

	/**
	 * Created at column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_args( $item ) {
		return $item['args'];
	}

	/**
	 * Created at column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_schedule( $item ) {
		return $item['schedule'];
	}

	/**
	 * Created at column.
	 *
	 * @param array $item Table row item.
	 * @return string
	 */
	public function column_next_run( $item ) {
		return $item['next_run'];
	}
}

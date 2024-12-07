<?php
/*
-----------------------------------------------------------------------------------*/
/*
 SITEPRESS IMPORTER ENGINE
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( class_exists( 'SITEPRESS_IMPORTER_ENGINE' ) ) {
	return;
}

class SITEPRESS_IMPORTER_ENGINE {


	public static $single_instance = null;
	public static $args            = array();


	public function __construct() {
		 add_action( 'wp_ajax_process_csv', array( $this, ST_IMP_PLUGIN_SLUG . '_process_csv' ) );
		add_action( 'wp_ajax_sitepress_importer_load_import_process', array( $this, ST_IMP_PLUGIN_SLUG . '_load_import_process' ) );
		// Enqueue JavaScript for AJAX
		add_action( 'admin_enqueue_scripts', array( $this, ST_IMP_PLUGIN_SLUG . '_enqueue_scripts' ) );
		add_action( 'wp_ajax_sitepress_importer_reset', array( $this, ST_IMP_PLUGIN_SLUG . '_reset' ) );

		add_action( ST_IMP_PLUGIN_SLUG . '_cron_hook', ST_IMP_PLUGIN_SLUG . '_cron_action' );
	}

	public static function instantiate( $args = array() ) {
		if ( self::$single_instance === null ) {
			self::$args            = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}


	static function sitepress_importer_enqueue_scripts() {
		wp_enqueue_script( ST_IMP_PLUGIN_NAME . '-script', ST_IMP_URL . 'assets/js/' . ST_IMP_PLUGIN_NAME . '-plugin.js', array( 'jquery' ), null, true );
		wp_localize_script( ST_IMP_PLUGIN_NAME . '-script', ST_IMP_PLUGIN_SLUG . '_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	function sitepress_importer_load_import_process() {
		if ( get_option( 'template_csv_file_url' ) && get_option( 'page_csv_file_url' ) ) {
			$progress_data = get_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message' );
			if ( empty( $progress_data ) ) {
				wp_clear_scheduled_hook( ST_IMP_PLUGIN_SLUG );
				wp_schedule_single_event( time(), ST_IMP_PLUGIN_SLUG . '_cron_hook' ); // Change 'hourly' to your desired frequency
				wp_send_json_success( SITEPRESS_IMPORTER_HELPERS::sitepress_importer_format_response( array(), true ) );
				wp_die();
			}

			wp_send_json_success( SITEPRESS_IMPORTER_HELPERS::sitepress_importer_format_response( json_encode( $progress_data ), true ) );
		} else {
			wp_send_json_error(
				array(
					'error'   => true,
					'message' => 'Required options do not exist.',
				)
			);
		}
	}
	/**
	 * Cron action
	 *
	 * @return void
	 */
	function sitepress_importer_cron_action() {
		 $template_csv_url = get_option( 'template_csv_file_url' );
		$page_csv_url      = get_option( 'page_csv_file_url' );

		if ( empty( $template_csv_url ) ) {
			$template_csv_content = file_get_contents( $template_csv_url );
			error_log( 'Template CSV Content: ' . $template_csv_content ); // Log the content

			SITEPRESS_IMPORTER_HELPERS::extend_update_option(
				ST_IMP_PLUGIN_SLUG . '_import_progress_message',
				array(
					'error'   => true,
					'message' => 'Template CSV not found',
				),
				'append'
			);
			wp_send_json_error( 'No file uploaded.' );
			wp_die();
		}

		if ( empty( $page_csv_url ) ) {
			$page_csv_content = file_get_contents( $page_csv_url );
			error_log( 'Page CSV Content: ' . $page_csv_content ); // Log the content
			SITEPRESS_IMPORTER_HELPERS::extend_update_option(
				ST_IMP_PLUGIN_SLUG . '_import_progress_message',
				array(
					'error'   => true,
					'message' => 'Page CSV not found',
				),
				'append'
			);
			wp_send_json_error( 'No file uploaded.' );
			wp_die();
		}
		// get last import action;
		$progress_action = get_option( ST_IMP_PLUGIN_SLUG . '_import_progress_action', null );
		error_log( json_encode( $progress_action ) );
		if ( $progress_action === null || ! isset( $progress_action['type'] ) ) {
			// SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Upload Files: \n [templates.csv] \n [page-content.csv]', 'override' );

			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', '[Start Import]', 'append' );
		}

		if ( $progress_action['type'] === 'template' ) {
			$csv_data = file_get_contents( $progress_action['type'] . 'csv_file_url' );
			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'CSV data retrieved', 'append' );
			return 1;
		}
		SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'could not CSV data retrieved', 'append' );
		return 1;
	}
	function sitepress_importer_process_csv() {
		 // @todo - do proper check
		if ( isset( $_FILES['template_csv_file'] ) && ! empty( $_FILES['template_csv_file']['tmp_name'] ) ) {
			$template_csv_content = file_get_contents( $_FILES['template_csv_file']['tmp_name'] );
			$page_csv_content     = file_get_contents( $_FILES['page_csv_file']['tmp_name'] );

			$template_csv_file_url = SITEPRESS_IMPORTER_HELPERS::handle_file_save( $template_csv_content, 'template_csv_' . time() . '.csv' );
			$page_csv_file_url     = SITEPRESS_IMPORTER_HELPERS::handle_file_save( $page_csv_content, 'page_csv_' . time() . '.csv' );
			update_option( 'template_csv_file_url', $template_csv_file_url ); // Store URL in options
			update_option( 'page_csv_file_url', $page_csv_file_url ); // Store URL in options
			// @todo: add proper name
			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Upload Files: \n [templates.csv] \n [page-content.csv]', 'override' );
			wp_schedule_single_event(
				time(),
				ST_IMP_PLUGIN_SLUG . '_cron_hook'
			); // Change 'hourly' to your desired frequency

			wp_send_json_success(
				SITEPRESS_IMPORTER_HELPERS::sitepress_importer_format_response(
					array(
						'template_csv' => $template_csv_file_url,
						'page_csv'     => $page_csv_file_url,
					),
					true
				)
			);
		} else {
			wp_send_json_error( 'No file uploaded.' );
		}
		wp_die(); // Required to terminate immediately and return a proper response
	}
	function sitepress_importer_reset() {
		delete_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message' );
		delete_option( 'template_csv_file_url' ); // Store URL in options
		delete_option( 'page_csv_file_url' );
		wp_send_json_success( 'complete' );
	}
}

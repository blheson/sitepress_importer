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

use League\Csv\Reader;
use League\Csv\Statement;
use Mtownsend\XmlToArray\XmlToArray;

class SITEPRESS_IMPORTER_ENGINE {



	public static $single_instance = null;
	public static $args            = array();

	public static $csv_reader = null;
	public function __construct() {
		add_action( 'wp_ajax_process_csv', array( $this, ST_IMP_PLUGIN_SLUG . '_process_csv' ) );
		add_action( 'wp_ajax_sitepress_importer_load_import_process', array( $this, ST_IMP_PLUGIN_SLUG . '_load_import_process' ) );
		// Enqueue JavaScript for AJAX
		add_action( 'admin_enqueue_scripts', array( $this, ST_IMP_PLUGIN_SLUG . '_enqueue_scripts' ) );
		add_action( 'wp_ajax_sitepress_importer_reset', array( $this, ST_IMP_PLUGIN_SLUG . '_reset' ) );

		add_action( ST_IMP_PLUGIN_SLUG . '_cron_hook', array( $this, ST_IMP_PLUGIN_SLUG . '_cron_action' ) );
	}

	public static function instantiate( $args = array() ) {
		// require_once ST_IMP_PATH . 'inc/vendor/league/csv/autoload.php';
		require_once ST_IMP_PATH . 'inc/vendor/autoload.php';

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
	function schedule_cron() {
		// wp_clear_scheduled_hook( ST_IMP_PLUGIN_SLUG );
		// wp_schedule_single_event( time(), ST_IMP_PLUGIN_SLUG . '_cron_hook' ); // Change 'hourly' to your desired frequency
		$this->sitepress_importer_cron_action();
	}
	function sitepress_importer_load_import_process() {
		if ( get_option( 'template_csv_file_url' ) && get_option( 'page_csv_file_url' ) ) {
			$progress_data = get_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message' );
			if ( empty( $progress_data ) ) {

				$this->schedule_cron();
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
		$page_csv_url     = get_option( 'page_csv_file_url' );

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

			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', '[Start Import]', 'append' );
			$progress_action = array(
				'type' => 'template',
				'id'   => 0,
			);
			update_option(
				ST_IMP_PLUGIN_SLUG . '_import_progress_action',
				$progress_action
			);
		}

		if ( $progress_action['type'] === 'template' ) {
			// Todo: check if file exists
			$reader = \League\Csv\Reader::createFromPath( get_option( $progress_action['type'] . '_csv_file_url' ), 'r' );
			$reader->setHeaderOffset( 0 );
			$stmt    = Statement::create();
			$records = $stmt->process( $reader );
			$headers = $reader->getHeader();
			// Validate the header array
			// $record = array(10)
			// ID = "2339"
			// Sitel
			// string 10"
			// Title = "head"
			// Content = "<head>
			// DateCreated = "2014-12-03 16:13:28.653"
			// CreatedBy = "1"
			// DateModified = "2019-06-28 11:39:00.813"
			// ModifiedBy = "1"
			// EditableAreacount ="o"
			// isDefault ="o"
			$expected_headers = array( 'ID', 'SiteID', 'Title', 'Content', 'DateCreated', 'CreatedBy', 'DateModified', 'ModifiedBy', 'EditableAreaCount', 'isDefault' );
			error_log( 'Header: ' . json_encode( $headers ) ); // Log the header

			if ( array_diff( $expected_headers, $headers ) ) {

				SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Invalid template CSV header.', 'append' );

				$this->sitepress_importer_reset_action();
				wp_send_json_error(
					array(
						'error'   => true,
						'message' => 'Invalid template CSV header.',
					)
				);

				wp_die();
			}

			// Iterate through filtered records
			// SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Progress: n/nn items processed', 'append' );

			foreach ( $records as $record ) {
				if ( $progress_action['id'] >= $record['ID'] ) {
					// Skip already processed records
					continue;
				}
				// Create a new post of type 'sitepress_template'
				$post_data = array(
					'post_title'   => $record['Title'],
					'post_content' => $record['Content'],
					'post_status'  => 'publish', // Set the post status
					'post_type'    => 'sitepress_template', // Set the custom post type
				);

				// Insert the post into the database
				$post_id = wp_insert_post( $post_data );

				// Set the custom field 'sitepress_template_id'
				if ( is_wp_error( $post_id ) ) {
					SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Error saving template for ID: ' . $record['ID'], 'append' );
					continue;
				}
				update_post_meta( $post_id, 'sitepress_template_id', $record['ID'] );
				// $record is now an associative array with column names as keys
				update_option(
					ST_IMP_PLUGIN_SLUG . '_import_progress_action',
					array(
						'type' => 'template',
						'id'   => $record['ID'],
					)
				);

				SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', '✓ Imported template: ' . $record['Title'], 'append' );
			}
			$progress_action = array(
				'type' => 'page',
				'id'   => 0,
			);
			update_option(
				ST_IMP_PLUGIN_SLUG . '_import_progress_action',
				$progress_action
			);
		}
		if ( $progress_action['type'] === 'page' ) {
			$this->handle_page_create_action( $progress_action );
			return 1;
		}
		// SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'could not completd', 'append' );
		return 1;
	}
	function handle_page_create_action( $progress_action ) {
		// Todo: check if file exists
		$reader = \League\Csv\Reader::createFromPath( get_option( $progress_action['type'] . '_csv_file_url' ), 'r' );
		$reader->setHeaderOffset( 0 );
		$stmt    = Statement::create();
		$records = $stmt->process( $reader );
		$headers = $reader->getHeader();
		// Validate the header array
		$expected_headers = array( 'ID', 'SiteID', 'ParentPageID', 'FolderID', 'PositionInGroup', 'TemplateID', 'PageName', 'PageURL', 'PageType', 'PageTitle', 'PageWindowTitle', 'PageDescription', 'PageKeywords', 'PageContent', 'PageASPXIncludeFile', 'Version', 'IsPublished', 'IsSearchable', 'IsInNavigation', 'IsIndexedInternally', 'RequireAuthentication', 'RequireSSL', 'CreatedBy', 'DateCreated', 'LastModifiedBy', 'LastModified', 'IsEndPoint', 'HasComments', 'StaticID', 'ExplicitSecurity', 'CanonicalLinkOverride' );

		if ( array_diff( $expected_headers, $headers ) ) {

			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Invalid page CSV header', 'append' );
			// ÷            $this->sitepress_importer_reset_action();

			wp_send_json_error(
				array(
					'error'   => true,
					'message' => 'Invalid page CSV header.',
				)
			);

			wp_die();
		}
		$post_data = array(
			'post_type'      => 'sitepress_template', // Set the custom post type
			'posts_per_page' => -1,
		);

		$templates        = get_posts( $post_data );
		$mapped_templates = array();
		foreach ( $templates as $template ) {
			$mapped_templates[ $template->post_title ] = $template;
		}
		foreach ( $records as $record ) {
			if ( $progress_action['id'] >= $record['ID'] ) {
				// Skip already processed records
				continue;
			}

			$post_parent = 0;

			if ( $record['ParentPageID'] !== null ) {
				$post_parent = $this->get_post_id_by_sitepress_page_id( $record['ParentPageID'] );
			}

			$template_post = $this->get_post_id_by_sitepress_template_id( $record['TemplateID'] );

			$post_content_build = $this->fill_template_with_content( $template_post->post_content, $record['PageContent'], $mapped_templates );

			$post_data = array(
				'post_title'   => $record['PageTitle'], // Set the page title
				'post_content' => wp_kses_post( $post_content_build ), // Set the page content
				'post_status'  => 'publish', // Set the post status
				'post_type'    => 'page', // Set the post type to 'page'
				'post_parent'  => $post_parent,
			);

			// Insert the post into the database
			$post_id = wp_insert_post( $post_data );
			// Set the custom field 'sitepress_template_id'
			if ( is_wp_error( $post_id ) ) {
				SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Error  creating page for ID: ' . $record['ID'], 'append' );
				continue;
			}
			update_post_meta( $post_id, 'sitepress_page_id', $record['ID'] );
			update_option(
				ST_IMP_PLUGIN_SLUG . '_import_progress_action',
				array(
					'type' => 'page',
					'id'   => $record['ID'],
				)
			);

			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', '✓ Imported page: ' . $record['PageTitle'], 'append' );
		}

		SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Data successfully imported', 'append' );
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
			SITEPRESS_IMPORTER_HELPERS::extend_update_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message', 'Upload Files: --n-- [templates.csv] --n-- [page-content.csv]', 'override' );
			$this->schedule_cron();

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
	function sitepress_importer_reset_action() {
		delete_option( ST_IMP_PLUGIN_SLUG . '_import_progress_message' );
		delete_option( 'template_csv_file_url' ); // Store URL in options
		delete_option( 'page_csv_file_url' );
		delete_option(
			ST_IMP_PLUGIN_SLUG . '_import_progress_action'
		);
		$args = array(
			'post_type'      => array( 'post', 'page', 'sitepress_template' ), // Specify post types
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'sitepress_template_id',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'sitepress_page_id',
					'compare' => 'EXISTS',
				),
			),
			'posts_per_page' => -1, // Get all posts
		);

		$posts = get_posts( $args );

		// Loop through the posts and delete them
		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true ); // true to force delete
		}
	}
	function sitepress_importer_reset() {
		$this->sitepress_importer_reset_action();
		wp_send_json_success( 'complete' );
	}
	function get_post_id_by_sitepress_page_id( $sitepress_page_id ) {
		$args = array(
			'post_type'   => 'page', // Specify the post type
			'meta_query'  => array(
				array(
					'key'     => 'sitepress_page_id',
					'value'   => $sitepress_page_id,
					'compare' => '=', // Compare for equality
				),
			),
			'fields'      => 'ids', // Only get post IDs
			'numberposts' => 1, // Limit to one post
		);

		$post_ids = get_posts( $args );
		return ! empty( $post_ids ) ? $post_ids[0] : null; // Return the first post ID or null if not found
	}
	function get_post_id_by_sitepress_template_id( $sitepress_template_id ) {
		$args = array(
			'post_type'   => 'sitepress_template', // Set the custom post type

			'meta_query'  => array(
				array(
					'key'     => 'sitepress_template_id',
					'value'   => $sitepress_template_id,
					'compare' => '=', // Compare for equality
				),
			),
			'fields'      => 'post_content', // Only get post IDs
			'numberposts' => 1, // Limit to one post
		);

		$post_ids = get_posts( $args );
		return ! empty( $post_ids ) ? $post_ids[0] : null; // Return the first post ID or null if not found
	}

	function fill_template_with_content( $template, $xml_content, $template_formats ) {
		// Load the XML content using DOMDocument
		$dom                     = new DOMDocument( '1.0', 'utf-8' );
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = true;
		$dom->recover            = true;
		libxml_use_internal_errors( true );
		$dom->loadXML( $xml_content );

		// Prepare an associative array to hold the content areas
		$content_areas     = array();
		$content_areas_obj = $dom->getElementsByTagName( 'ContentArea' );
		$template          = $this->extract_template_names( $template, $template_formats );
		// Loop through each ContentArea in the DOM and store it in the array
		foreach ( $content_areas_obj  as $content_area_obj ) {
			$name                   = $content_area_obj->getElementsByTagName( 'Name' )->item( 0 )->nodeValue;
			$content                = $content_area_obj->getElementsByTagName( 'Content' )->item( 0 )->nodeValue;
			$content_areas[ $name ] = htmlspecialchars_decode( $content ); // Decode HTML entities
		}
		$indx = 0;
		// Replace placeholders in the template with actual content
		foreach ( $content_areas as $name => $content ) {
			$indx++;
			$template = str_replace( "{{CONTENTAREA:$name:Index=$indx}}", $content, $template );
		}
		error_log( $template );

		return $template;
	}
	function extract_template_names( $template, $template_formats, $count = 0 ) {
		$pattern        = '/{{INCLUDE_TEMPLATE:([^}]+)}}/';
		$matches        = array();
		$template_names = array();
		if ( $count > 20 ) {
			return $template;
		}
		if ( ! preg_match_all( $pattern, $template, $matches ) ) {

			return $template;
		}
		$template_names = $matches[1];

		foreach ( $template_names as $key => $value ) {
			if ( isset( $template_formats[ $value ] ) ) {
				try {
					$template = str_replace( "{{INCLUDE_TEMPLATE:$value}}", $template_formats[ $value ]->post_content, $template );
				} catch ( \Throwable $th ) {
				}
			}
		}
		$count++;
		return $this->extract_template_names( $template, $template_formats, $count );
	}

	// function fill_template_with_content( $template, $xml_content ) {
	// try {
	// Load the XML content using XMLReader
	// $reader = new XMLReader();
	// $reader->xml( $xml_content );
	// $loaded = simplexml_load_string( $xml_content );
	// $array  = XmlToArray::convert( $xml_content );
	// Prepare an associative array to hold the content areas
	// $content_areas = array();

	// Loop through each ContentArea in the XML
	// while ( $reader->read() ) {
	// if ( $reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'ContentArea' ) {
	// $content_area           = new SimpleXMLElement( $reader->readOuterXML() );
	// $name                   = (string) $content_area->Name;
	// $content                = (string) $content_area->Content;
	// $content_areas[ $name ] = htmlspecialchars_decode( $content ); // Decode HTML entities
	// }
	// }
	// $reader->close();

	// Replace placeholders in the template with actual content
	// foreach ( $content_areas as $name => $content ) {
	// $template = str_replace( "{{CONTENTAREA:$name}}", $content, $template );
	// }
	// return $template;
	// } catch ( \Throwable $th ) {
	// throw new Exception( 'Error Processing Request', 1 );

	// }

	// }
}

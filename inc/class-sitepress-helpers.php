<?php
/*
-----------------------------------------------------------------------------------*/
/*
 SITEPRESS IMPORTER HELPERS
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( class_exists( 'SITEPRESS_IMPORTER_HELPERS' ) ) {
	return;
}

class SITEPRESS_IMPORTER_HELPERS {



	static function sitepress_importer_format_response( $response, $is_success ) {
		if ( $is_success ) {
			return $response;
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $option_name - option to update
	 * @param [type] $value - value to update
	 * @param [type] $action_type - to append or to ovveride (append, override)
	 * @return boolean
	 */
	static function extend_update_option( $option_name, $value, $action_type ) {
		switch ( $action_type ) {
			case 'append':
				$current_value = get_option( $option_name, array() ); // Retrieve current option value, default to empty array
				if ( ! is_array( $current_value ) ) {
					$current_value = array(); // Ensure it's an array
				}
				$current_value[] = $value; // Merge new values
				update_option( $option_name, $current_value ); // Update the option with the new array
				break;

			default:
				update_option( $option_name, array( $value ) ); // Update the option directly
				break;
		}
	}
	static function handle_file_save( $csv_content, $file_name ) {
		// Save CSV content to uploads directory
		$upload_dir = wp_upload_dir();
		// $file_name  = 'template_csv_' . time() . '.csv'; // Unique file name
		$file_path = $upload_dir['path'] . '/' . $file_name;

		file_put_contents( $file_path, $csv_content ); // Save the file

		// Save the URL to wp-options
		// $file_url = $upload_dir['url'] . '/' . $file_name;

		return $file_path;
	}
}

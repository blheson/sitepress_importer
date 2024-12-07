<?php
/*
-----------------------------------------------------------------------------------*/
/*
 SITEPRESS IMPORTER MENU
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( class_exists( 'SITEPRESS_IMPORTER_MENU' ) ) {
	return;
}

class SITEPRESS_IMPORTER_MENU {




	public static $single_instance = null;
	public static $args            = array();



	public function __construct() {
		 add_action( 'admin_menu', array( $this, ST_IMP_PLUGIN_SLUG . '_plugin_menu' ) );
	}
	public static function instantiate( $args = array() ) {
		if ( self::$single_instance === null ) {
			self::$args            = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	function sitepress_importer_plugin_menu() {
		 // Add a new submenu under Tools
		add_management_page(
			'Sitepress Importer settings', // Page title
			'Sitepress Importer settings', // Menu title
			'manage_options',   // Capability
			'sitepress-importer',  // Menu slug
			array( $this, ST_IMP_PLUGIN_SLUG . '_page' ) // Function to display the page
		);
	}
	// Function to display the plugin page
	function sitepress_importer_page() {
		// Check if a file has been uploaded
		if ( isset( $_POST['submit'] ) && ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
			$csv_content = file_get_contents( $_FILES['csv_file']['tmp_name'] );
			// Process the CSV content as needed
			// For now, we will just save it in a variable
			$csv_data = str_getcsv( $csv_content, "\n" ); // Split by new line
			// You can further process $csv_data as needed
			echo '<div class="updated"><p>CSV uploaded successfully!</p></div>';
		}
		?>
		<div class="wrap">
			<h1>Import Settings</h1>

			<div>
				<section class="form-section">
					<form method="post" id="csv-upload-form" enctype="multipart/form-data">
						<div class="d-block mb-3">
							<label for="template_csv_file">
								Template CSV <br>
								<input type="file" name="template_csv_file" accept=".csv" required>
							</label>
						</div>
						<div class="page-csv-box">
							<label for="page_csv_file">
								Page Content CSV <br>
								<input type="file" name="page_csv_file" accept=".csv" required>
							</label>
						</div>
						<div>
							<input type="submit" name="submit" value="Upload CSV" class="button button-primary">
						</div>
					</form>
				</section>
				<section class="progress-section">
					<div id="progress-message"></div>
				</section>
			</div>

			<div id="response-message"></div>
			<div>
				<span id="siteimporter-reset">Reset</span>
			</div>
			<style>
				.page-csv-box {
					margin-top: 30px;
				}

				.form-section {
					display: none;
				}
			</style>
		</div>
		<?php
	}
}

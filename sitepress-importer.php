<?php

/**
 * Plugin Name: Sitepress Importer
 * Description: A simple WordPress plugin that imports sitepress template
 * Version: 1.0
 * Author: Blessing Udor
 * Author_url: blessingudor.com
 * License: GPL
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

class SITEPRESS_IMPORTER_INIT {


	public static $ST_IMP = '1.0.0';
	// public static $PLUGIN_SLUG      = 'sitepress_importer';
	// public static $PLUGIN_NAME      = 'sitepress-importer';
	public static $PLUGIN_NAME_FULL = 'Sitepress Importer';

	public function __construct() {
		 $this->define_constants();
		// Hook to add the menu item

		add_action( 'plugins_loaded', array( $this, ST_IMP_PLUGIN_SLUG . '_classes' ), 0 );

		// Handle the AJAX request

		// add_action( 'deactivate_my_plugin', ST_IMP_PLUGIN_SLUG . '_deactivation' );

		// $this->sitepress_importer_classes();
	}
	private function define_constants() {
		define( 'ST_IMP_PLUGIN_SLUG', 'sitepress_importer' );
		define( 'ST_IMP_PLUGIN_NAME', 'sitepress-importer' );
		// define( 'CRON_NAME', 'sitepress-importer' );

		define( 'ST_IMP_URL', plugin_dir_url( __FILE__ ) );
		define( 'ST_IMP_PATH', plugin_dir_path( __FILE__ ) );
		define( 'ST_IMP_INC', ST_IMP_PATH . 'inc/' );
		define( 'ST_IMP_ADMIN', admin_url() );
	}

	/**
	 * OPP init
	 *
	 * @return void
	 */
	public function sitepress_importer_classes() {
		require_once ST_IMP_INC . 'class-sitepress-helpers.php';
		require_once ST_IMP_INC . 'class-sitepress-menu.php';
		require_once ST_IMP_INC . 'class-sitepress-engine.php';

		SITEPRESS_IMPORTER_MENU::instantiate();
		SITEPRESS_IMPORTER_ENGINE::instantiate();
	}
}
$sitepress_instance = new SITEPRESS_IMPORTER_INIT();
// register_activation_hook( __FILE__, array( $AG_opp, 'activate' ) );


function sitepress_importer_deactivation() {
	wp_clear_scheduled_hook( ST_IMP_PLUGIN_SLUG );
}

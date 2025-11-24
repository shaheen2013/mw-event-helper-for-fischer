<?php
/**
 * Plugin Name:         MW Helper Plugin
 * Plugin URI:          https://mediusware.com/
 * Description:         MW Helper Plugin
 * Version:             1.0.0
 * Requires at least:   5.2
 * Requires PHP:        7.2
 * Author:              Mediusware.com
 * Author URI:          https://mediusware.com/
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         mwhp
 * Domain Path:         /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( ! defined('MWHP_VERSION') ) define( 'MWHP_VERSION', '1.0.0' );
if( ! defined('MWHP_PATH_DIR') ) define( 'MWHP_PATH_DIR', plugin_dir_path(__FILE__) );
if( ! defined('MWHP_PATH_URI') ) define( 'MWHP_PATH_URI', plugin_dir_url(__FILE__) );
if(! defined('MWHP_PLUGIN_BASENAME')) define('MWHP_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once MWHP_PATH_DIR . '/autoloaders.php';

function mwhp_plugin_init() {
    MWHP\Inc\Mwhp_Init::instance();
}

add_action( 'plugins_loaded', 'mwhp_plugin_init' );

register_activation_hook( __FILE__, [ 'MWHP\Inc\Mwhp_Init', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'MWHP\Inc\Mwhp_Init', 'deactivate' ] );
register_uninstall_hook( __FILE__, [ 'MWHP\Inc\Mwhp_Init', 'uninstall' ] );

<?php
namespace MWHP\Inc;

use MWHP\Inc\Admin\Admin_Init;
use MWHP\Inc\Assets\Assets_Init;
use MWHP\Inc\Database\Inspiration_Tracker_Table;
use MWHP\Inc\Inspirations\Inspirations_Tracker_Init;
use MWHP\Inc\Metabox\Metabox_Init;

class Mwhp_Init {
    private static $instance;

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_hooks();

        Admin_Init::get_instance();
        Metabox_Init::get_instance();
        Assets_Init::get_instance();
        Inspirations_Tracker_Init::get_instance();

    }

    private function load_hooks(){
        add_action('init', [ $this, 'load_textdomain' ]);
        add_filter('plugin_action_links_' . MWHP_PLUGIN_BASENAME, [$this, 'action_link']);
    }

    public function action_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=mwhp-gp-api-key') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }


    public function load_textdomain() {
        load_plugin_textdomain(
            'mwhp',
            false,
            MWHP_PATH_DIR . 'languages/'
        );
    }

    

    /**
     * The activation hook for the plugin.
     * This method will run when the plugin is activated.
     */
    public static function activate() {

        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        Inspiration_Tracker_Table::maybe_upgrade();
        add_option( 'mwhp_plugin_activated', true );

    }

    /**
     * The deactivation hook for the plugin.
     * This method will run when the plugin is deactivated.
     */
    public static function deactivate() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        
    }

    public static function uninstall() {
        delete_option( 'mwai_plugin_activated' );
        Inspiration_Tracker_Table::drop_table();
    }

}

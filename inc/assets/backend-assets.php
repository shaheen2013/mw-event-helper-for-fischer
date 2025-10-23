<?php 

namespace MWHP\Inc\Assets;

use MWHP\Inc\Traits\Singleton;

class Backend_Assets{
    use Singleton;
    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    public function admin_styles($hook) {
        
        wp_register_style('mw-datepicker-style',MWHP_PATH_URI . '/assets/css/jquery-ui.css',[],'1.13.2');
        wp_register_style('mwhp-datatable', MWHP_PATH_URI . '/assets/css/dataTables.dataTables.min.css', [], wp_rand());
        wp_register_style('mwhp-tracker-summary', MWHP_PATH_URI . '/assets/css/tracker-summary.css', [], wp_rand());
        
        
        wp_enqueue_style('mwhp-datatable');
        wp_enqueue_style('mw-datepicker-style');
        wp_enqueue_style('mwhp-tracker-summary');
    }


    public function admin_scripts($hook) {

        wp_enqueue_script('jquery-ui-datepicker');

        wp_register_script('mwhp-datatable', MWHP_PATH_URI . '/assets/js/dataTables.min.js', ['jquery'], wp_rand(), true);

        wp_register_script('mwhp-clean-cache-js', MWHP_PATH_URI . '/assets/js/clean-cache.js', ['jquery'], wp_rand(), true);
        wp_register_script('mwhp-inspiration-dt', MWHP_PATH_URI . '/assets/js/admin-inspiration-dt.js', ['jquery', 'mwhp-datatable','jquery-ui-datepicker'], wp_rand(), true);
        

        wp_enqueue_script('mwhp-clean-cache-js');

        wp_enqueue_script('mwhp-inspiration-dt');

        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mwhp_plugin_nonce'),
        ];
        wp_localize_script('mwhp-clean-cache-js', 'mwhpJSObj', $localized_data);
    }

}
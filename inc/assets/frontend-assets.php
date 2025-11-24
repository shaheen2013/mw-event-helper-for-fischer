<?php 

namespace MWHP\Inc\Assets;

use MWHP\Inc\Traits\Singleton;

class Frontend_Assets {
    use Singleton;

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'styles']);
        add_action('wp_enqueue_scripts', [$this, 'scripts']);
    }

    public function styles() {

    }

    public function scripts() {
        wp_register_script(
            'mwhp-scripts',
            MWHP_PATH_URI . 'assets/js/zone-slider-tracker.js',
            ['jquery'],
            MWHP_VERSION,
            true
        );

        wp_enqueue_script('mwhp-scripts');

        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mwhp_plugin_nonce')
        ];
        wp_localize_script('mwhp-scripts', 'mwhpJSObj', $localized_data);
    }
}

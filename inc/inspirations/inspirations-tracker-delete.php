<?php 

namespace MWHP\Inc\Inspirations;

use MWHP\Inc\Database\Inspiration_Tracker_Table;
use MWHP\Inc\Traits\Singleton;

class Inspirations_Tracker_Delete {
    use Singleton;

    public function init() {
        add_action('wp_ajax_mwhp_delete_inspiration_records', [$this, 'callback']);
        add_action('wp_ajax_nopriv_mwhp_delete_inspiration_records', [$this, 'callback']);
    }

    public function callback(){
        check_ajax_referer('mwhp_plugin_nonce', 'nonce');

        $date  = sanitize_text_field($_POST['date'] ?? '');


        $deleted = Inspiration_Tracker_Table::delete_by_date($date);

        wp_send_json_success([
            'deleted' => (int) $deleted,
            'message' => sprintf(__('Deleted %d records older than %s', 'mwhp'), (int) $deleted, esc_html($date)),
        ]);
    }
}
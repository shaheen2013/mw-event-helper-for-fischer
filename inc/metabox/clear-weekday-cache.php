<?php 

namespace MWHP\Inc\Metabox;

use MWHP\Inc\Traits\Singleton;

class Clear_Weekday_Cache{
    use Singleton;

    private $transient_prefix = 'gp_business_hours_';

    public function init(){
        add_action( 'wp_ajax_mwhp_clear_cache', array( $this, 'ajax_clear_cache' ) );
    }
    public function ajax_clear_cache() {
        $ok = isset( $_POST['nonce'] ) && check_ajax_referer( 'mwhp_plugin_nonce', 'nonce', false );
        if ( ! $ok ) {
            wp_send_json_error( 'Invalid nonce', 403 );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'Missing post_id', 400 );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

        $trans_key = $this->transient_prefix . $post_id;
        if ( get_transient( $trans_key ) !== false ) {
            delete_transient( $trans_key );
        }
        wp_send_json_success( 'cleared' );
    }
}
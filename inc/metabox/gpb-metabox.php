<?php
namespace MWHP\Inc\Metabox;

use MWHP\Inc\Services\Business_Hour_Status;
use MWHP\Inc\Services\GPB_Places_Client;
use MWHP\Inc\Settings\Map_Settings;
use MWHP\Inc\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) exit;

class GPB_Metabox {
    use Singleton;
    private $transient_prefix = 'gp_business_hours_';

    private $business_status = false;

    public function init() {

        add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
        add_action( 'save_post_filialen', array( $this, 'clear_old_transient_on_save' ), 10, 2 );
        add_action( 'admin_head', array( $this, 'inject_metabox_status_into_title' ) );
    }

    public function register_metabox() {
        add_meta_box(
            'gp_business_hours',
            'Google Business Hours',
            array( $this, 'render_metabox' ),
            'filialen',
            'side',
            'default'
        );
    }

    public function render_metabox( $post ) {
        echo '<div style="font-size:13px;">';

        $business_name = Map_Settings::get_business_name();
        if ( empty( $business_name ) ) {
            echo '<p style="color:#d00;"><strong>Business Name is not added.</strong><br/>Go to Settings → Google Map API and add your business name (optional fallback).</p>';
            echo '</div>';
            return;
        }

        $api_key = Map_Settings::get_api_key();
        if ( empty( $api_key ) ) {
            echo '<p style="color:#d00;"><strong>Google Places API key not configured.</strong><br/>Go to Settings → Google Map API and add your key.</p>';
            echo '</div>';
            return;
        }

        $trans_key = $this->transient_prefix . $post->ID;
        $cached = get_transient( $trans_key );

        if ( false !== $cached && is_array( $cached ) ) {
            if ( ! empty( $cached['weekday'] ) && is_array( $cached['weekday'] ) ) {
                $address = isset( $cached['address'] ) ? esc_html( $cached['address'] ) : '';
                $this->render_weekdays( $cached['weekday'], $address );

                $fetched_html  = '<span class="mwhp-fetched">Fetched: ' . esc_html( $cached['fetched'] ) . ' — cached 6h.</span>';

                $clear_link = ' <a href="#" class="mwhp-clear-cache" data-postid="' . esc_attr( $post->ID ) . '">Clear cache</a>';

                echo '<p style="font-size:11px;color:#666;margin-top:6px;">' . $fetched_html . $clear_link . '</p>';
            } else {
                echo '<p><em>No opening hours available.</em></p>';
            }
            echo '</div>';
            return;
        }

        $query = trim( $business_name . ' | ' . ( $post->post_title ?: '' ) );
        if ( empty( $query ) ) {
            echo '<p><em>Post title and business name are empty. The plugin uses Business Name + post title as the place query.</em></p>';
            echo '</div>';
            return;
        }

        $result = GPB_Places_Client::fetch_place_opening_hours( $query, $api_key );

        if ( is_wp_error( $result ) ) {
            echo '<p style="color:#d00;"><strong>Error:</strong> ' . esc_html( $result->get_error_message() ) . '</p>';
            echo '<p style="font-size:11px;color:#666;">Tried query: <code>' . esc_html( $query ) . '</code></p>';
            echo '</div>';
            return;
        }

        list( $place_id, $name, $weekday_arr, $address ) = $result;

        if ( ! empty( $weekday_arr ) && is_array( $weekday_arr ) ) {
            $this->render_weekdays( $weekday_arr, $address );
        } else {
            echo '<p><em>No opening hours returned by Google for: </em><br/><strong>' . esc_html( $name ?: $query ) . '</strong></p>';
        }

        // Save to post meta
        update_post_meta( $post->ID, '_gp_weekday_text', $weekday_arr );
        update_post_meta( $post->ID, '_gp_place_id', sanitize_text_field( $place_id ) );
        update_post_meta( $post->ID, '_gp_place_name', sanitize_text_field( $name ) );

        // Conditional caching:
        if ( ! empty( $weekday_arr ) && is_array( $weekday_arr ) ) {
            $cache_value = array(
                'place_id' => $place_id,
                'name'     => $name,
                'address'  => $address,
                'weekday'  => $weekday_arr,
                'fetched'  => current_time( 'mysql' ),
            );
            set_transient( $trans_key, $cache_value, 6 * HOUR_IN_SECONDS );
            echo '<p style="font-size:11px;color:#666;margin-top:8px;">(Fetched and cached for 6 hours.)</p>';
        } else {
            // ensure stale transient removed
            if ( get_transient( $trans_key ) !== false ) {
                delete_transient( $trans_key );
            }
            echo '<p style="font-size:11px;color:#666;margin-top:8px;">(No opening hours returned — nothing cached.)</p>';
        }

        echo '</div>';
    }

    

    public function clear_old_transient_on_save( $post_id, $post ) {
        $trans_key = $this->transient_prefix . $post_id;
        if ( get_transient( $trans_key ) !== false ) {
            delete_transient( $trans_key );
        }
    }

    private function render_weekdays($weekday_arr, $name){
        echo '<em style="color: green"><strong>Address:</strong> ' . esc_html( $name ) . '</em>';
        echo '<hr>';
        echo '<ul style="margin:0; padding:0;">';
        foreach ( $weekday_arr as $line ) {
            $parts = explode( ':', $line, 2 );
            $day  = isset( $parts[0] ) ? trim( $parts[0] ) : '';
            $rest = isset( $parts[1] ) ? ltrim( $parts[1] ) : '';

            if ( $day !== '' ) {
                echo '<li style="margin:2px 0 5px; padding: 5px 0; border-bottom: 1px solid #ddd;"><strong>' . esc_html( $day ) . ':</strong> ' . esc_html( $rest ) . '</li>';
            } else {
                echo '<li style="margin:2px 0 5px; padding: 5px 0; border-bottom: 1px solid #ddd;">' . esc_html( $line ) . '</li>';
            }
        }
        echo '</ul>';
    }

    public function inject_metabox_status_into_title() {
        if ( ! is_admin() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || empty( $screen->post_type ) ) {
            return;
        }

        if ( 'filialen' !== $screen->post_type ) {
            return;
        }

        $post = get_post();
        if ( ! $post || ! $post->ID ) {
            return;
        }

        $status_simple = Business_Hour_Status::get_status( $post->ID );

        $badge_text  = __('No hours', 'mwhp');
        $badge_class = 'unknown';

        if ( $status_simple === null ) {
            $badge_text  = __('Unknown', 'mwhp');
            $badge_class = 'unknown';
        } elseif ( is_string( $status_simple ) && strcasecmp( $status_simple, 'open' ) === 0 ) {
            $badge_text  = __('Open', 'mwhp');
            $badge_class = 'open';
        } else {
            $badge_text  = __('Close', 'mwhp');
            $badge_class = 'close';
        }

        $badge_text_js  = wp_json_encode( $badge_text );
        $badge_class_js = wp_json_encode( $badge_class );

        echo '<style>
        .gpb-metabox-status{font-size:12px;margin-left:6px;padding:3px 7px;border-radius:4px;line-height:1;display:inline-block;vertical-align:middle}
        .gpb-metabox-status.open{color:#155724;background:#d4edda;border:1px solid #c3e6cb}
        .gpb-metabox-status.closed{color:#856404;background:#fff3cd;border:1px solid #ffeeba}
        .gpb-metabox-status.unknown{color:#6c757d;background:#e9ecef;border:1px solid #ddd}
        </style>';

        echo "<script>
        jQuery(function($){
            var txt = {$badge_text_js};
            var cls = {$badge_class_js};
            var box = $('#gp_business_hours');
            if ( box.length ) {
                console.log('inside box')
                var titleWrap = box.find('.hndle');
                if ( titleWrap.length ) {
                console.log('inside title')
                    if ( titleWrap.find('.gpb-metabox-status').length === 0 ) {
                        titleWrap.append(' <span class=\"gpb-metabox-status ' + cls + '\">' + txt + '</span>');
                    } else {
                        var existing = titleWrap.find('.gpb-metabox-status').first();
                        existing.attr('class','gpb-metabox-status ' + cls).text(txt);
                    }
                }
            }
        });
        </script>";
    }

}

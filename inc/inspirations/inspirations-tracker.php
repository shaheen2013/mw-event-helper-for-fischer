<?php 

namespace MWHP\Inc\Inspirations;

use MWHP\Inc\Database\Inspiration_Tracker_Table;
use MWHP\Inc\Traits\Singleton;

class Inspirations_Tracker {
    use Singleton;

    public function init() {
        add_action('wp_ajax_mwhp_log_inspiration', [$this, 'callback']);
        add_action('wp_ajax_nopriv_mwhp_log_inspiration', [$this, 'callback']);
    }

    public function callback(){
        check_ajax_referer('mwhp_plugin_nonce', 'nonce');

        $tracker = isset($_POST['tracker_name']) ? strtoupper(sanitize_text_field($_POST['tracker_name'])) : '';
        $meta    = isset($_POST['meta']) ? wp_unslash($_POST['meta']) : null;

        $allowed = ['OPENED','SECOND_PRODUCT','ALL_PRODUCTS'];
        if (!in_array($tracker, $allowed, true)) {
            wp_send_json_error(['message' => 'Invalid tracker_name'], 400);
        }

        $ip = $this->mwhp_get_client_ip();
        if (!$ip) {
            wp_send_json_error(['message' => 'Unable to determine IP'], 400);
        }

        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $meta = $decoded;
            }
        }

        $browser = $this->get_browser_parent();

        $id = Inspiration_Tracker_Table::insert([
            'tracker_name' => $tracker,
            'user_ip'      => $ip,
            'browser'      => $browser,
            'meta'         => $meta,
        ]);

        if ($id > 0) {
            wp_send_json_success(['id' => $id]);
        }

        wp_send_json_error(['message' => 'DB insert failed'], 500);
    }

    private function mwhp_get_client_ip(): string {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CF_CONNECTING_IP', 
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $raw = trim((string) $_SERVER[$k]);
                $candidates = array_map('trim', explode(',', $raw));
                foreach ($candidates as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return '';
    }

    private function get_browser_parent() {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($agent, 'Chrome') !== false && stripos($agent, 'Edge') === false) {
            return 'Chrome';
        } elseif (stripos($agent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (stripos($agent, 'Safari') !== false && stripos($agent, 'Chrome') === false) {
            return 'Safari';
        } elseif (stripos($agent, 'Edge') !== false) {
            return 'Edge';
        } elseif (stripos($agent, 'MSIE') !== false || stripos($agent, 'Trident') !== false) {
            return 'Internet Explorer';
        } else {
            return 'Unknown';
        }
    }
}
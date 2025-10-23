<?php
namespace MWHP\Inc\Inspirations;

use MWHP\Inc\Traits\Singleton;

class Inspiration_Tracker_Settings_Page {
    use Singleton;
    const PAGE_SLUG  = 'mwhp-inspiration-tracker';
    const PAGE_TITLE = 'Inspiration tracker';
    const CAP        = 'manage_options';
    const NONCE_KEY  = 'mwhp_insp_dt_nonce';

    public function init(): void {
        if (!is_admin()) return;
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('wp_ajax_mwhp_insp_dt_list', [$this, 'ajax_list']);   // server list
        add_action('wp_ajax_mwhp_insp_dt_del',  [$this, 'ajax_delete']); // bulk delete
    }

    /** Settings → Inspiration tracker */
    public function register_settings_page(): void {
        add_options_page(
            __(self::PAGE_TITLE, 'mwhp'),
            __(self::PAGE_TITLE, 'mwhp'),
            self::CAP,
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }


    /** Page markup */
    public function render_page(): void {
        if (!current_user_can(self::CAP)) wp_die(__('Insufficient permissions', 'mwhp'));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__(self::PAGE_TITLE, 'mwhp'); ?></h1>

            <div class="tablenav top" style="margin:12px 0;">
                <div class="alignleft actions">
                    <select id="mwhp-filter-tracker">
                        <option value="ALL"><?php esc_html_e('All', 'mwhp'); ?></option>
                        <option value="OPENED">OPENED</option>
                        <option value="SECOND_PRODUCT">SECOND_PRODUCT</option>
                        <option value="ALL_PRODUCTS">ALL_PRODUCTS</option>
                    </select>
                    <button id="mwhp-refresh" class="button"><?php esc_html_e('Apply', 'mwhp'); ?></button>
                    <button id="mwhp-bulk-delete" class="button button-danger" disabled><?php esc_html_e('Delete Selected', 'mwhp'); ?></button>
                </div>
                <div class="alignright">
                    <input type="search" id="mwhp-search" placeholder="<?php esc_attr_e('Search…', 'mwhp'); ?>" class="regular-text" />
                </div>
                <div style="clear:both;"></div>
            </div>

            <table id="mwhp-inspiration-table" class="display wp-list-table widefat fixed striped" style="width:100%">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="mwhp-select-all" /></th>
                        <th><?php esc_html_e('ID', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Tracker', 'mwhp'); ?></th>
                        <th><?php esc_html_e('User IP', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Meta', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Created At', 'mwhp'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <?php
    }

    /** DataTables provider */
    public function ajax_list(): void {
        if (!current_user_can(self::CAP)) wp_send_json_error(['message'=>'forbidden'], 403);
        check_ajax_referer(self::NONCE_KEY, 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'mw_inspiration_tracker';

        // DataTables params
        $draw   = isset($_POST['draw'])   ? (int) $_POST['draw']   : 0;
        $start  = isset($_POST['start'])  ? max(0, (int) $_POST['start']) : 0;
        $length = isset($_POST['length']) ? min(100, max(10, (int) $_POST['length'])) : 20;

        $orderIdx = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 5;
        $orderDir = isset($_POST['order'][0]['dir'])    ? strtoupper($_POST['order'][0]['dir']) : 'DESC';
        if (!in_array($orderDir, ['ASC','DESC'], true)) $orderDir = 'DESC';

        // 0 checkbox, 1 id, 2 tracker_name, 3 user_ip, 4 meta, 5 created_at
        $map = [ 1=>'id', 2=>'tracker_name', 3=>'user_ip', 4=>'meta', 5=>'created_at' ];
        $orderby = $map[$orderIdx] ?? 'created_at';

        $search  = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';
        $tracker = isset($_POST['tracker']) ? strtoupper(sanitize_text_field($_POST['tracker'])) : 'ALL';
        if (!in_array($tracker, ['ALL','OPENED','SECOND_PRODUCT','ALL_PRODUCTS'], true)) $tracker = 'ALL';

        $where  = 'WHERE 1=1';
        $params = [];

        if ($tracker !== 'ALL') {
            $where   .= ' AND tracker_name = %s';
            $params[] = $tracker;
        }
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= ' AND (user_ip LIKE %s OR tracker_name LIKE %s OR meta LIKE %s)';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $recordsTotal    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $recordsFiltered = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} {$where}", $params));

        $sql = "SELECT id, tracker_name, user_ip, meta, created_at
                FROM {$table}
                {$where}
                ORDER BY {$orderby} {$orderDir}
                LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results($wpdb->prepare($sql, array_merge($params, [$length, $start])), ARRAY_A) ?: [];

        $data = [];
        foreach ($rows as $r) {
            $id   = (int) $r['id'];
            $meta = $r['meta'];
            $metaOut = '<em>—</em>';
            if (!empty($meta) && is_string($meta)) {
                $dec = json_decode($meta, true);
                $metaOut = (json_last_error() === JSON_ERROR_NONE) ? wp_json_encode($dec) : $meta;
                $metaOut = esc_html(mb_strimwidth($metaOut, 0, 120, '…'));
                $metaOut = '<code>'.$metaOut.'</code>';
            }

            $data[] = [
                sprintf('<input type="checkbox" class="mwhp-row" value="%d" />', $id),
                esc_html((string) $id),
                esc_html((string) $r['tracker_name']),
                esc_html((string) $r['user_ip']),
                $metaOut,
                esc_html((string) $r['created_at']),
            ];
        }

        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /** Bulk delete */
    public function ajax_delete(): void {
        if (!current_user_can(self::CAP)) wp_send_json_error(['message'=>'forbidden'], 403);
        check_ajax_referer(self::NONCE_KEY, 'nonce');

        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        if (!$ids) wp_send_json_error(['message'=>'no ids'], 400);

        global $wpdb;
        $table = $wpdb->prefix . 'mw_inspiration_tracker';
        $ph = implode(',', array_fill(0, count($ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN ($ph)", $ids));

        if ($deleted === false) wp_send_json_error(['message'=>'delete failed'], 500);
        wp_send_json_success(['deleted' => (int) $deleted]);
    }
}

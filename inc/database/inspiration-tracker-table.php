<?php

namespace MWHP\Inc\Database;

class Inspiration_Tracker_Table {
    private static $table_name   = 'mw_inspiration_tracker';
    private static $version      = '1.0.1';
    private static $option_key   = 'inspiration_tracker_db_version';
    private static $allowed_vals = ['OPENED','SECOND_PRODUCT','ALL_PRODUCTS', 'OPEN_PRODUCT_PAGE', 'USER_LEFT', 'HALF_VIEWED'];

    private static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tracker_name ENUM('OPENED','SECOND_PRODUCT','ALL_PRODUCTS') NOT NULL,
            user_ip VARCHAR(45) NOT NULL DEFAULT '',
            browser VARCHAR(45) NOT NULL DEFAULT '',
            meta LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Safely alter the tracker_name ENUM column to include new values
     * without losing existing data.
     */
    public static function alter_enum_to_add_new_values() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if (!$exists) {
            return false;
        }

        $column_info = $wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'tracker_name'");
        if (!$column_info || stripos($column_info->Type, 'enum(') === false) {
            return false;
        }

        preg_match_all("/'([^']+)'/", $column_info->Type, $matches);
        $current_values = $matches[1] ?? [];

        $new_values = ['OPEN_PRODUCT_PAGE', 'USER_LEFT', 'HALF_VIEWED'];
        $merged_values = array_unique(array_merge($current_values, $new_values));

        $enum_list = implode("','", $merged_values);

        $sql = "ALTER TABLE {$table}
                MODIFY COLUMN tracker_name ENUM('{$enum_list}') NOT NULL";

        $result = $wpdb->query($sql);

        if ($result !== false) {
            update_option(self::$option_key, self::$version);
            return true;
        }
        return false;
    }


    /**
     * Insert a row.
     * $data = ['tracker_name'=> 'OPENED'|'SECOND_PRODUCT'|'ALL_PRODUCTS', 'user_ip'=> 'x.x.x.x'|ipv6, 'meta'=> array|string|null]
     */
    public static function insert(array $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $tracker = isset($data['tracker_name']) ? strtoupper(trim($data['tracker_name'])) : '';
        if (!in_array($tracker, self::$allowed_vals, true)) {
            return 0;
        }

        $ip = isset($data['user_ip']) ? trim((string) $data['user_ip']) : '';
        if (!self::is_valid_ip($ip)) {
            return 0;
        }

        $meta = array_key_exists('meta', $data)
            ? (is_string($data['meta']) ? $data['meta'] : wp_json_encode($data['meta']))
            : null;

        $browser = isset($data['browser']) ? trim((string) $data['browser']) : '';

        $created_at = current_time('mysql');
        $wpdb->insert(
            $table,
            [
                'tracker_name' => $tracker,
                'user_ip'      => $ip,
                'meta'         => $meta,
                'browser'         => $browser,
                'created_at'   => $created_at,
            ],
            ['%s','%s','%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Update by id; accepts any subset of ['tracker_name','user_ip','meta'].
     */
    public static function update($id, array $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $fields  = [];
        $formats = [];

        if (isset($data['tracker_name'])) {
            $tracker = strtoupper(trim($data['tracker_name']));
            if (!in_array($tracker, self::$allowed_vals, true)) {
                return false;
            }
            $fields['tracker_name'] = $tracker;
            $formats[] = '%s';
        }

        if (isset($data['user_ip'])) {
            $ip = trim((string) $data['user_ip']);
            if (!self::is_valid_ip($ip)) {
                return false;
            }
            $fields['user_ip'] = $ip;
            $formats[] = '%s';
        }

        if (array_key_exists('meta', $data)) {
            $fields['meta'] = is_string($data['meta']) ? $data['meta'] : wp_json_encode($data['meta']);
            $formats[] = '%s';
        }

        if (!$fields) {
            return 0;
        }

        return $wpdb->update(
            $table,
            $fields,
            ['id' => (int) $id],
            $formats,
            ['%d']
        );
    }

    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A) ?: [];
        foreach ($rows as &$row) {
            if (isset($row['meta']) && $row['meta'] !== '') {
                $decoded = json_decode($row['meta'], true);
                if (json_last_error() === JSON_ERROR_NONE) $row['meta'] = $decoded;
            }
        }
        unset($row);
        return $rows;
    }

    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $id), ARRAY_A);
        if ($row && isset($row['meta']) && $row['meta'] !== '') {
            $decoded = json_decode($row['meta'], true);
            if (json_last_error() === JSON_ERROR_NONE) $row['meta'] = $decoded;
        }
        return $row;
    }

    public static function get_last_30_days_summary(bool $unique = true): array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $to_date   = gmdate('Y-m-d');
        $from_date = gmdate('Y-m-d', time() - (30 * DAY_IN_SECONDS));

        // Convert to full-day range
        $from = $from_date . ' 00:00:00';
        $to   = $to_date . ' 23:59:59';

         $result = [
            'from' => $from,
            'to'   => $to,
            'OPENED'            => ['users' => 0, 'events' => 0],
            'SECOND_PRODUCT'    => ['users' => 0, 'events' => 0],
            'ALL_PRODUCTS'      => ['users' => 0, 'events' => 0],
            'OPEN_PRODUCT_PAGE' => ['users' => 0, 'events' => 0],
            'USER_LEFT'         => ['users' => 0, 'events' => 0],
            'HALF_VIEWED'       => ['users' => 0, 'events' => 0],
        ];

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT tracker_name,
                    COUNT(*) AS events,
                    COUNT(DISTINCT user_ip) AS users
                FROM {$table}
                WHERE created_at BETWEEN %s AND %s
                GROUP BY tracker_name
                ",
                $from, $to
            ),
            ARRAY_A
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $key = $row['tracker_name'];
                if (in_array($key, self::$allowed_vals, true)) {
                    $result[$key]['events'] = (int) ($row['events'] ?? 0);
                    $result[$key]['users']  =(int) ($row['users'] ?? 0);
                }
            }
        }

        return $result;
    }



    public static function delete_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        return $wpdb->delete($table, ['id' => (int) $id], ['%d']);
    }

    public static function delete_by_date($date) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        return $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE DATE(created_at) <= %s", $date));
    }

    public static function drop_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
        delete_option(self::$option_key);
    }

    public static function maybe_upgrade() {
        $installed = get_option(self::$option_key, '0.0.0');
        if ($installed !== self::$version) {
            self::create_table();
            self::alter_enum_to_add_new_values();
            update_option(self::$option_key, self::$version);
        }
    }

    /** Basic IP validator for IPv4/IPv6 */
    private static function is_valid_ip(string $ip): bool {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP);
    }
}

<?php
namespace MWHP\Inc\Admin;

use MWHP\Inc\Database\Inspiration_Tracker_Table;
use MWHP\Inc\Traits\Singleton;

class Inspiration_Tracker_Page {
    use Singleton;
    const PAGE_SLUG  = 'mwhp-inspiration-tracker';
    const PAGE_TITLE = 'Inspiration Tracker';
    const CAP        = 'manage_options';
    const NONCE_KEY  = 'mwhp_insp_dt_nonce';

    public function init(): void {
        if (!is_admin()) return;
        add_action('admin_menu', [$this, 'register_settings_page']);
    }

    /** Settings → Inspiration tracker */
    public function register_settings_page(): void {
        add_submenu_page(
            'mw-helper',
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
        $track_data = Inspiration_Tracker_Table::get_all();
        ?>
        <div class="wrap">
            <div class="header-wrapper" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1><?php echo esc_html__( self::PAGE_TITLE, 'mwhp' ); ?></h1>
                <button type="button" class="button button-danger" id="delete-all-inspire-data" style="background-color: red; color: #fff; border-color: red;">Delete Records</button>
            </div>
            <?php $this->track_summary(); ?>
            <table id="mwhp-inspiration-table" class="wp-list-table widefat fixed striped" style="width:100%">
                <thead>
                    <tr>
                        <th width="80"><?php esc_html_e('ID', 'mwhp'); ?></th>
                        <th width="200"><?php esc_html_e('Tracker', 'mwhp'); ?></th>
                        <th width="200"><?php esc_html_e('User IP', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Meta', 'mwhp'); ?></th>
                        <th width="200"><?php esc_html_e('Created At', 'mwhp'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( ! empty($track_data) ) : ?>
                        <?php $count = 1; ?>
                        <?php foreach ( $track_data as $row ) :
                            $id        = isset($row->id) ? (int) $row->id : (int) ($row['id'] ?? 0);
                            $tracker   = isset($row->tracker) ? (string) $row->tracker_name : (string) ($row['tracker_name'] ?? '');
                            $user_ip   = isset($row->user_ip) ? (string) $row->user_ip : (string) ($row['user_ip'] ?? '');
                            $meta_raw  = isset($row->meta) ? $row->meta : ($row['meta'] ?? '');
                            $created   = isset($row->created_at) ? $row->created_at : ($row['created_at'] ?? '');

                            // Normalize + compact meta preview
                            if (is_string($meta_raw)) {
                                $decoded = json_decode($meta_raw, true);
                                $meta_arr = json_last_error() === JSON_ERROR_NONE ? $decoded : $meta_raw;
                            } else {
                                $meta_arr = $meta_raw;
                            }
                            $meta_preview = is_array($meta_arr)
                                ? wp_json_encode($meta_arr, JSON_UNESCAPED_UNICODE)
                                : (string) $meta_arr;

                            $meta_preview_trim = mb_strimwidth($meta_preview, 0, 160, '…', 'UTF-8');
                            $created_disp = $created
                                ? date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime((string)$created) )
                                : '';
                            ?>
                            <tr data-tracker="<?php echo esc_attr($tracker); ?>">
                                <td><?php echo esc_html($count); ?></td>
                                <td><code><?php echo esc_html($tracker); ?></code></td>
                                <td><?php echo esc_html($user_ip); ?></td>
                                <td title="<?php echo esc_attr($meta_preview); ?>">
                                    <span><?php echo esc_html($meta_preview_trim); ?></span>
                                </td>
                                <td><?php echo esc_html($created_disp); ?></td>
                            </tr>
                            <?php $count++; ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No tracking data found.', 'mwhp'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th><?php esc_html_e('ID', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Tracker', 'mwhp'); ?></th>
                        <th><?php esc_html_e('User IP', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Meta', 'mwhp'); ?></th>
                        <th><?php esc_html_e('Created At', 'mwhp'); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    private function track_summary(){
        $summary =Inspiration_Tracker_Table::get_last_30_days_summary(true);
        ?>

        <div class="mwhp-card-grid" role="region" aria-label="<?php echo esc_attr__('Inspiration tracker summary (last 30 days)', 'mwhp'); ?>">
            <!-- OPENED -->
            <div class="mwhp-card">
                <div class="title">
                    <?php esc_html_e('Opened / Started', 'mwhp'); ?>
                    <span class="mwhp-pill"><?php esc_html_e('Last 30 days', 'mwhp'); ?></span>
                </div>
                <div class="mwhp-kpi" aria-label="<?php esc_attr_e('Unique users who opened the inspirator', 'mwhp'); ?>">
                    <div class="value"><?php echo esc_html( number_format_i18n( (int)($summary['OPENED']['users'] ?? 0) ) ); ?></div>
                    <div class="sub"><?php esc_html_e('users', 'mwhp'); ?></div>
                </div>
                <div class="hint">
                    <?php
                    printf(
                        /* translators: %s: total events number */
                        esc_html__('Total events: %s', 'mwhp'),
                        esc_html( number_format_i18n( (int)($summary['OPENED']['events'] ?? 0) ) )
                    );
                    ?>
                </div>
            </div>

            <!-- SECOND_PRODUCT -->
            <div class="mwhp-card">
                <div class="title">
                    <?php esc_html_e('Scrolled to Second Product', 'mwhp'); ?>
                    <span class="mwhp-pill"><?php esc_html_e('Last 30 days', 'mwhp'); ?></span>
                </div>
                <div class="mwhp-kpi" aria-label="<?php esc_attr_e('Unique users who reached the second product', 'mwhp'); ?>">
                    <div class="value"><?php echo esc_html( number_format_i18n( (int)($summary['SECOND_PRODUCT']['users'] ?? 0) ) ); ?></div>
                    <div class="sub"><?php esc_html_e('users', 'mwhp'); ?></div>
                </div>
                <div class="hint">
                    <?php
                    printf(
                        esc_html__('Total events: %s', 'mwhp'),
                        esc_html( number_format_i18n( (int)($summary['SECOND_PRODUCT']['events'] ?? 0) ) )
                    );
                    ?>
                </div>
            </div>

            <!-- ALL_PRODUCTS -->
            <div class="mwhp-card">
                <div class="title">
                    <?php esc_html_e('Viewed All Products', 'mwhp'); ?>
                    <span class="mwhp-pill"><?php esc_html_e('Last 30 days', 'mwhp'); ?></span>
                </div>
                <div class="mwhp-kpi" aria-label="<?php esc_attr_e('Unique users who reached the last product', 'mwhp'); ?>">
                    <div class="value"><?php echo esc_html( number_format_i18n( (int)($summary['ALL_PRODUCTS']['users'] ?? 0) ) ); ?></div>
                    <div class="sub"><?php esc_html_e('users', 'mwhp'); ?></div>
                </div>
                <div class="hint">
                    <?php
                    printf(
                        esc_html__('Total events: %s', 'mwhp'),
                        esc_html( number_format_i18n( (int)($summary['ALL_PRODUCTS']['events'] ?? 0) ) )
                    );
                    ?>
                </div>
            </div>
        </div>
    <?php
    }
}

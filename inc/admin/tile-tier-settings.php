<?php

namespace MWHP\Inc\Admin;

use MWHP\Inc\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) exit;

class Tile_Tier_Settings {

    use Singleton;

    private $option_group = 'tile_tier_group';
    private $page_slug    = 'mwhp-tile-tier-settings';
    protected $text_domain = 'mwhp';

    public function init() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function admin_menu() {
        add_submenu_page(
            'mw-helper',
            __("Tile Tier Settings", 'mwhp'),
            __("Tile Tier Settings", 'mwhp'),
            "manage_options",
            $this->page_slug,
            [$this, 'render_page']
        );
    }

    public function register_settings() {

        // TEXT + IMAGE URL SETTINGS

        $tiles = [9, 18, 27];

        foreach ( $tiles as $num ) {

            register_setting( $this->option_group, "tile_{$num}_text", array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ));

            register_setting( $this->option_group, "tile_{$num}_image", array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => '',
            ));
        }
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        wp_enqueue_media();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Tile Tier Settings', $this->text_domain ); ?></h1>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php settings_fields( $this->option_group ); ?>

                <table class="form-table">

                    <?php foreach ( [9, 18, 27] as $num ): ?>

                        <?php
                            $text_val = get_option("tile_{$num}_text", '');
                            $img_url  = get_option("tile_{$num}_image", '');
                        ?>

                        <tr valign="top">
                            <th scope="row"><strong><?php echo $num; ?> Tiles</strong></th>
                            <td>

                                <!-- TEXT INPUT -->
                                <label>URL:</label><br/>
                                <input type="text"
                                       name="tile_<?php echo $num; ?>_text"
                                       value="<?php echo esc_attr($text_val); ?>"
                                       class="regular-text">
                                <br><br>

                                <!-- IMAGE PREVIEW -->
                                <div class="tile-preview">
                                    <?php if ( $img_url ) : ?>
                                        <img src="<?php echo esc_url($img_url); ?>"
                                             style="width:80px;height:80px;object-fit:cover;border:1px solid #ccc;margin-bottom:10px;">
                                    <?php endif; ?>
                                </div>

                                <!-- UPLOAD BUTTON -->
                                <button class="button tile-upload-btn"
                                        data-target="tile_<?php echo $num; ?>_image">
                                        Upload Image
                                </button>

                                <!-- STORED VALUE -->
                                <input type="hidden"
                                       name="tile_<?php echo $num; ?>_image"
                                       id="tile_<?php echo $num; ?>_image"
                                       value="<?php echo esc_attr($img_url); ?>">

                            </td>
                        </tr>

                    <?php endforeach; ?>

                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
            jQuery(function($) {

                $(".tile-upload-btn").on("click", function(e) {
                    e.preventDefault();

                    let targetField = $(this).data("target");

                    let frame = wp.media({
                        title: "Select Image",
                        button: { text: "Use this image" },
                        library: { type: "image" },
                        multiple: false
                    });

                    frame.on("select", function() {
                        let attachment = frame.state().get("selection").first().toJSON();

                        $("#" + targetField).val(attachment.url);

                        let previewBox = $("#" + targetField).closest("td").find(".tile-preview");
                        previewBox.html(
                            "<img src='" + attachment.url +
                            "' style='width:80px;height:80px;object-fit:cover;border:1px solid #ccc;margin-bottom:10px;'>"
                        );
                    });

                    frame.open();
                });

            });
        </script>

        <?php
    }

    // Getters for frontend
    public static function get_tile_text($tiles) {
        return get_option("tile_{$tiles}_text", '');
    }

    public static function get_tile_image($tiles) {
        return get_option("tile_{$tiles}_image", '');
    }

}

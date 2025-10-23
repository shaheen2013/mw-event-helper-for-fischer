<?php
namespace MWHP\Inc\Admin;

use MWHP\Inc\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) exit;

class Map_Settings {

    use Singleton;

    private $option_group = 'gp_api_group';
    private $option_name_key = 'gp_places_api_key';
    private $option_name_business = 'gp_places_name';
    private $page_slug = 'mw-helper';

    /**
     * Text domain for translations.
     * Change this if your plugin uses a different text-domain.
     */
    protected $text_domain = 'mwhp';

    public function init() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function admin_menu() {
        add_menu_page(
            __( 'MW Helper', $this->text_domain ),
            __( 'MW Helper', $this->text_domain ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render_page' )
        );
    }

    public function register_settings() {
        register_setting( $this->option_group, $this->option_name_business, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( $this->option_group, $this->option_name_key, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Google Map API Key', $this->text_domain ); ?></h1>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( $this->page_slug );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__( 'Business Name', $this->text_domain ); ?></th>
                        <td>
                            <input name="<?php echo esc_attr( $this->option_name_business ); ?>" type="text"
                                   value="<?php echo esc_attr( get_option( $this->option_name_business, '' ) ); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr__( 'Optional default place name (fallback)', $this->text_domain ); ?>" />
                            <p class="description"><?php echo esc_html__( 'Optional default/fallback place name (e.g. Polstermöbel Fischer).', $this->text_domain ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__( 'Map API Key', $this->text_domain ); ?></th>
                        <td>
                            <input name="<?php echo esc_attr( $this->option_name_key ); ?>" type="text"
                                   value="<?php echo esc_attr( get_option( $this->option_name_key, '' ) ); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr__( 'Paste your Google Places API key here', $this->text_domain ); ?>" />
                            <p class="description"><?php echo esc_html__( 'Make sure Places API / Place Details is enabled and billing is set up.', $this->text_domain ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // small getters
    public static function get_api_key() {
        $self = new self();
        return trim( (string) get_option( $self->option_name_key, '' ) );
    }

    public static function get_business_name() {
        $self = new self();
        return trim( (string) get_option( $self->option_name_business, '' ) );
    }

}

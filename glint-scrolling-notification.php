<?php
/**
 * Plugin Name: CHT Scrolling Notification
 * Description: Displays a scrolling notification bar at the top of the website with expiry functionality.
 * Version: 1.0.0
 * Author: Kael
 * Text Domain: glint-scrolling-notification
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GLINT_SN_VERSION', '1.0.0');
define('GLINT_SN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GLINT_SN_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Main plugin class
class Glint_Scrolling_Notification {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->register_post_type();
        $this->register_assets();
        add_action('wp_footer', array($this, 'display_notification_bar'));
        add_action('admin_init', array($this, 'register_meta_fields'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('wp_head', array($this, 'inline_styles'));
    }
    
    private function load_dependencies() {
        require_once GLINT_SN_PLUGIN_PATH . 'includes/class-notification-manager.php';
        require_once GLINT_SN_PLUGIN_PATH . 'includes/class-swiper-integration.php';
    }
    
    public function register_post_type() {
        $labels = array(
            'name' => __('Notifications', 'glint-scrolling-notification'),
            'singular_name' => __('Notification', 'glint-scrolling-notification'),
            'menu_name' => __('Notifications', 'glint-scrolling-notification'),
            'name_admin_bar' => __('Notification', 'glint-scrolling-notification'),
            'add_new' => __('Add New', 'glint-scrolling-notification'),
            'add_new_item' => __('Add New Notification', 'glint-scrolling-notification'),
            'new_item' => __('New Notification', 'glint-scrolling-notification'),
            'edit_item' => __('Edit Notification', 'glint-scrolling-notification'),
            'view_item' => __('View Notification', 'glint-scrolling-notification'),
            'all_items' => __('All Notifications', 'glint-scrolling-notification'),
            'search_items' => __('Search Notifications', 'glint-scrolling-notification'),
            'not_found' => __('No notifications found.', 'glint-scrolling-notification'),
            'not_found_in_trash' => __('No notifications found in Trash.', 'glint-scrolling-notification')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'notification'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-megaphone'
        );
        
        register_post_type('notification', $args);
    }
    
    public function register_meta_fields() {
        register_post_meta('notification', '_gsn_enable_expiry', array(
            'show_in_rest' => true,
            'type' => 'boolean',
            'single' => true,
            'default' => false,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
        
        register_post_meta('notification', '_gsn_expiry_datetime', array(
            'show_in_rest' => true,
            'type' => 'string',
            'single' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }
    
    public function save_meta_fields($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (get_post_type($post_id) !== 'notification') return;
        
        if (isset($_POST['_gsn_enable_expiry'])) {
            update_post_meta($post_id, '_gsn_enable_expiry', sanitize_text_field($_POST['_gsn_enable_expiry']));
        } else {
            update_post_meta($post_id, '_gsn_enable_expiry', false);
        }
        
        if (isset($_POST['_gsn_expiry_datetime'])) {
            update_post_meta($post_id, '_gsn_expiry_datetime', sanitize_text_field($_POST['_gsn_expiry_datetime']));
        }
    }
    
    public function register_assets() {
        wp_register_style(
            'swiper-css',
            'https://cdn.jsdelivr.net/npm/swiper@11.0.5/swiper-bundle.min.css',
            array(),
            '11.0.5'
        );
        
        wp_register_script(
            'swiper-js',
            'https://cdn.jsdelivr.net/npm/swiper@11.0.5/swiper-bundle.min.js',
            array(),
            '11.0.5',
            true
        );
        
        wp_register_style(
            'glint-sn-styles',
            GLINT_SN_PLUGIN_URL . 'assets/css/frontend.css',
            array('swiper-css'),
            GLINT_SN_VERSION
        );
        
        wp_register_script(
            'glint-sn-script',
            GLINT_SN_PLUGIN_URL . 'assets/js/frontend.js',
            array('swiper-js', 'jquery'),
            GLINT_SN_VERSION,
            true
        );
    }
    
    public function display_notification_bar() {
        $notification_manager = new Notification_Manager();
        $notifications = $notification_manager->get_active_notifications();
        
        if (empty($notifications)) return;
        
        wp_enqueue_style('glint-sn-styles');
        wp_enqueue_script('glint-sn-script');
        
        // Localize script for passing data to JS
        wp_localize_script('glint-sn-script', 'glint_sn_vars', array(
            'autoplay_delay' => 4000,
            'animation_speed' => 500
        ));
        
        include GLINT_SN_PLUGIN_PATH . 'templates/notification-bar.php';
    }
    
    public function inline_styles() {
        $notification_manager = new Notification_Manager();
        $notifications = $notification_manager->get_active_notifications();
        
        if (!empty($notifications)) {
            echo '<style>
                body {
                    padding-top: 34px;
                    transition: padding-top 0.3s ease;
                }
                .glint-scrolling-notification {
                    height: 34px;
                }
            </style>';
        }
    }
}

// Initialize the plugin
Glint_Scrolling_Notification::get_instance();

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'glint_sn_activate');
register_deactivation_hook(__FILE__, 'glint_sn_deactivate');

function glint_sn_activate() {
    // Flush rewrite rules for custom post type
    flush_rewrite_rules();
}

function glint_sn_deactivate() {
    // Clean up on deactivation
    flush_rewrite_rules();
}


// Add meta box for expiry settings
add_action('add_meta_boxes', 'gsn_add_meta_boxes');
function gsn_add_meta_boxes() {
    add_meta_box(
        'gsn_expiry_settings',
        __('Expiry Settings', 'glint-scrolling-notification'),
        'gsn_expiry_meta_box_callback',
        'notification',
        'side',
        'default'
    );
}

function gsn_expiry_meta_box_callback($post) {
    wp_nonce_field('gsn_save_meta', 'gsn_meta_nonce');
    
    $enable_expiry = get_post_meta($post->ID, '_gsn_enable_expiry', true);
    $expiry_datetime = get_post_meta($post->ID, '_gsn_expiry_datetime', true);
    
    ?>
    <div id="gsn_expiry_fields">
        <p>
            <label for="gsn_enable_expiry">
                <input type="checkbox" id="gsn_enable_expiry" name="_gsn_enable_expiry" value="1" <?php checked($enable_expiry, '1'); ?>>
                <?php _e('Enable expiration', 'glint-scrolling-notification'); ?>
            </label>
        </p>
        <p id="gsn_expiry_datetime_field" style="<?php echo !$enable_expiry ? 'display:none;' : ''; ?>">
            <label for="gsn_expiry_datetime"><?php _e('Expiry date/time:', 'glint-scrolling-notification'); ?></label>
            <input type="datetime-local" id="gsn_expiry_datetime" name="_gsn_expiry_datetime" value="<?php echo esc_attr($expiry_datetime); ?>" class="gsn-datetime-picker">
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#gsn_enable_expiry').change(function() {
            if ($(this).is(':checked')) {
                $('#gsn_expiry_datetime_field').show();
            } else {
                $('#gsn_expiry_datetime_field').hide();
            }
        });
    });
    </script>
    <?php
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', 'gsn_admin_assets');
function gsn_admin_assets($hook) {
    global $post_type;
    
    if (($hook == 'post.php' || $hook == 'post-new.php') && $post_type == 'notification') {
        wp_enqueue_style(
            'glint-sn-admin',
            GLINT_SN_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            GLINT_SN_VERSION
        );
    }
}
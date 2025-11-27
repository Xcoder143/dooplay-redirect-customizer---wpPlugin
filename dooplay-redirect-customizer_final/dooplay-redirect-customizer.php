<?php
/**
 * Plugin Name: DooPlay Redirect Customizer
 * Description: Replace DooPlay link redirect page with a responsive redirect UI and admin settings page (tabs) for timer, colors and backgrounds.
 * Version: 1.2.1
 * Author: Xcoder Bulit with ChatGPT + Gemini AI's 
 */

if (!defined('ABSPATH')) exit;

function dprc_get_sequential_api_index( $total_shorteners ) {
    $global_index_key = 'dprc_global_api_index';
    $cached_index = get_transient( $global_index_key );
    if ( $cached_index !== false ) {
        return intval( $cached_index );
    }
    $last_index_key = 'dprc_last_api_index_used';
    $last_index = intval( get_option( $last_index_key, -1 ) );
    $new_index = ( $last_index + 1 ) % $total_shorteners;
    set_transient( $global_index_key, $new_index, 12 * HOUR_IN_SECONDS );
    update_option( $last_index_key, $new_index );
    return $new_index;
}

function dprc_get_shortened_url( $source_url, $post_id = 0 ) {
    if ( !filter_var( $source_url, FILTER_VALIDATE_URL) ) return $source_url;

    $options = get_option('dprc_options');
    
    // --- ROLE BASED LOGIC START ---
    $role_enable = !empty($options['role_logic_enable']);
    $cache_time = 12 * HOUR_IN_SECONDS; // Default Guest/Standard Cache

    if ($role_enable && is_user_logged_in()) {
        // 1. Administrators: NO SHORTENER (Return original)
        if (current_user_can('administrator')) {
            return $source_url;
        }
        // 2. Contributors: 24 Hours Cache
        if (current_user_can('contributor')) {
            $cache_time = 24 * HOUR_IN_SECONDS;
        }
        // 3. Subscribers: 12 Hours Cache (Explicitly set, though same as default)
        elseif (current_user_can('subscriber')) {
            $cache_time = 12 * HOUR_IN_SECONDS;
        }
    }
    // --- ROLE BASED LOGIC END ---

    if ( empty($options['shorteners_enable']) ) return $source_url;
    
    $shortener_text = isset( $options['shorteners'] ) ? $options['shorteners'] : '';
    if ( empty( $shortener_text ) ) return $source_url;

    $shortener_list = array_filter( array_map('trim', explode("\n", $shortener_text)) );
    $total_shorteners = count($shortener_list);
    if ( empty( $shortener_list ) || $total_shorteners === 0 ) return $source_url;

    $current_api_index = dprc_get_sequential_api_index( $total_shorteners );
    $cache_key = 'dprc_link_' . $post_id . '_api_' . $current_api_index;

    if ( $post_id ) {
        $cached_url = get_transient( $cache_key );
        if ( !empty( $cached_url ) ) return $cached_url;
    }

    $shortener_format = $shortener_list[$current_api_index];
    $api_request_url = str_replace('{{url}}', rawurlencode($source_url), $shortener_format);
    $final_url = $source_url;

    $response = wp_remote_get( $api_request_url, array('timeout' => 3, 'redirection' => 5) );

    if ( !is_wp_error( $response ) ) {
        $body = wp_remote_retrieve_body( $response );
        $loc  = wp_remote_retrieve_header( $response, 'location' );
        $json = json_decode( $body, true );

        if ( is_array($json) ) {
            foreach (['shortUrl','short','shortenedUrl','url'] as $k) {
                if (!empty($json[$k]) && preg_match('#https?://#',$json[$k])) {
                    $final_url = $json[$k];
                    break; 
                }
            }
        }
        if ($final_url === $source_url && $loc && preg_match('#https?://#',$loc)) {
            $final_url = $loc;
        }
        if ($final_url === $source_url && preg_match('#https?://[^\\s\'\"<>]+#',$body,$m)) {
            $final_url = $m[0];
        }
    }
    
    // Save with the calculated cache time (12h or 24h)
    if ( $post_id ) set_transient( $cache_key, $final_url, $cache_time );
    
    return $final_url;
}

class DPRC_Plugin {
    const OPTION_KEY = 'dprc_options';

    private $defaults = [
        'timeout' => 5,
        'accent' => '#e50914',
        'timer_behavior' => 'continue-btn',
        'fallback_enable' => 1,
        'fallback_type' => 'image',
        'fallback_url' => '',
        'default_enable' => 1,
        'default_type' => 'image',
        'default_url' => '',
        'shorteners' => '',
        'shorteners_enable' => 0,
        // New Settings
        'role_logic_enable' => 0,
        'ad_code_top' => '',
        'ad_code_bottom' => '',
        // 'ad_code_pop' removed
    ];

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_scripts']);
        add_filter('template_include', [$this, 'override_dooplay_link_template'], 99);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX Hooks for Click Counting
        add_action('wp_ajax_dprc_track_click', [$this, 'track_click_callback']);
        add_action('wp_ajax_nopriv_dprc_track_click', [$this, 'track_click_callback']);
    }

    public function enqueue_admin_scripts($hook) {
		if ($hook != 'toplevel_page_dprc_settings') return;
		wp_enqueue_media();
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_style('dprc-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], '1.2.1');
		wp_enqueue_script('dprc-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery', 'wp-color-picker', 'jquery-ui-tabs'], '1.2.1', true);
		$opts = get_option(self::OPTION_KEY, $this->defaults);
		wp_localize_script('dprc-admin-js', 'dprcAdmin', ['options' => $opts]);
	}   

    public function enqueue_front_scripts() {
    // FIX: Only load scripts on the specific redirect page type
    if ( !is_singular('dt_links') ) {
        return;
    }

    $opts = get_option(self::OPTION_KEY, $this->defaults);
    wp_enqueue_style('dprc-rc-css', plugin_dir_url(__FILE__) . 'assets/css/rc.css', [], '1.2.1');
    wp_enqueue_script('dprc-rc-js', plugin_dir_url(__FILE__) . 'assets/js/rc.js', [], '1.2.1', true);
    
    $localized = [
        'timeout' => intval($opts['timeout']),
        'accent' => esc_attr($opts['accent']),
        'timer_behavior' => esc_attr($opts['timer_behavior']),
        'fallback' => [
            'enable' => intval($opts['fallback_enable']),
            'type' => esc_attr($opts['fallback_type']),
            'url' => esc_url($opts['fallback_url']),
        ],
        'default_bg' => [
            'enable' => intval($opts['default_enable']),
            'type' => esc_attr($opts['default_type']),
            'url' => esc_url($opts['default_url']),
        ],
    ];
    wp_enqueue_script('dprc-rc-js', plugin_dir_url(__FILE__) . 'assets/js/rc.js', [], '1.2.1', true);
    wp_localize_script('dprc-rc-js', 'dprcOptions', $localized);
    wp_localize_script('dprc-rc-js', 'dprcServer', $localized);
}

    public function register_settings() {
        register_setting('dprc_settings_group', self::OPTION_KEY, [$this, 'sanitize_options']);
    }

    public function sanitize_options($input) {
        return [
            'timeout' => intval($input['timeout']),
            'accent' => sanitize_hex_color($input['accent']),
            'timer_behavior' => sanitize_text_field($input['timer_behavior']),
            'fallback_enable' => !empty($input['fallback_enable']) ? 1 : 0,
            'fallback_type' => sanitize_text_field($input['fallback_type']),
            'fallback_url' => esc_url_raw($input['fallback_url']),
            'default_enable' => !empty($input['default_enable']) ? 1 : 0,
            'default_type' => sanitize_text_field($input['default_type']),
            'default_url' => esc_url_raw($input['default_url']),
            'shorteners' => isset($input['shorteners']) ? sanitize_textarea_field($input['shorteners']) : '',
            'shorteners_enable' => !empty($input['shorteners_enable']) ? 1 : 0,
            // New Fields
            'role_logic_enable' => !empty($input['role_logic_enable']) ? 1 : 0,
            'ad_code_top' => isset($input['ad_code_top']) ? $input['ad_code_top'] : '', // Allow HTML
            'ad_code_bottom' => isset($input['ad_code_bottom']) ? $input['ad_code_bottom'] : '',
            // 'ad_code_pop' removed
        ];
    }

    public function register_settings_page() {
        add_menu_page('DP Redirects', 'DP Redirects', 'manage_options', 'dprc_settings', [$this, 'settings_page'], 'dashicons-admin-links', 3);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $opts = get_option(self::OPTION_KEY, $this->defaults);
        require_once plugin_dir_path(__FILE__) . 'templates/admin-page-template.php';
    }

    public function override_dooplay_link_template($template) {
        if (is_singular('dt_links')) {
            $custom = plugin_dir_path(__FILE__) . 'templates/netflix-redirect.php';
            if (file_exists($custom)) return $custom;
        }
        return $template;
    }
    
    public function track_click_callback() {
        // Security: Verify Nonce
        check_ajax_referer('dprc_click_nonce', 'security');

        if (isset($_POST['post_id'])) {
            $post_id = intval($_POST['post_id']);
            
            // Fix: Prioritize dt_views_count
            $possible_keys = ['dt_views_count', 'clicks', 'hits', '_dool_clicks'];
            $target_key = 'dt_views_count'; 
            $hits = 0;

            foreach ($possible_keys as $key) {
                $val = get_post_meta($post_id, $key, true);
                if ( is_numeric($val) ) {
                    $hits = intval($val);
                    $target_key = $key;
                    if ($key === 'dt_views_count') break; 
                }
            }
            $hits++;
            update_post_meta($post_id, $target_key, $hits);
            wp_send_json_success(['count' => $hits]);
        }
        wp_send_json_error('No ID provided');
    }
}

new DPRC_Plugin();
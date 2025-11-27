<?php
if (!defined('ABSPATH')) exit;

global $post;

$opts = get_option('dprc_options', []);
$defaults = [
    'timeout' => 5,
    'accent' => '#e50914',
    'timer_behavior' => 'continue-btn',
    'fallback_enable' => 1,
    'fallback_type' => 'image',
    'fallback_url' => '',
    'default_enable' => 1,
    'default_type' => 'image',
    'default_url' => '',
    'role_logic_enable' => 0,
    'ad_code_top' => '',
    'ad_code_bottom' => '',
    // 'ad_code_pop' removed
];

$opts = wp_parse_args($opts, $defaults);

// --- 1. FETCH METADATA ---
$ourl = get_post_meta($post->ID, '_dool_url', true);
$size = get_post_meta($post->ID, '_dool_size', true);
$qual = get_post_meta($post->ID, '_dool_quality', true);
$type = get_post_meta($post->ID, '_dool_type', true); 

// Read-only Click Counter
$hits = get_post_meta($post->ID, 'dt_views_count', true);
if (empty($hits) || !is_numeric($hits)) $hits = 0;

// Smart Poster Logic
$parent = wp_get_post_parent_id($post->ID);
$title = $parent ? get_the_title($parent) : get_the_title($post->ID);
$posterRaw = '';
if ($parent) {
    $posterRaw = get_post_meta($parent, 'dt_poster', true);
    if (empty($posterRaw) && get_post_type($parent) === 'episodes') {
        $tmdb_id = get_post_meta($parent, 'ids', true);
        if ($tmdb_id) {
            $shows = get_posts(['post_type'=>'tvshows','meta_key'=>'ids','meta_value'=>$tmdb_id,'posts_per_page'=>1,'fields'=>'ids']);
            if (!empty($shows)) $posterRaw = get_post_meta($shows[0], 'dt_poster', true);
        }
    }
}
$assets = plugin_dir_url(__FILE__) . '../assets';
$fallback_img = $assets . '/img/fallback.jpg';
$poster = ($posterRaw && strpos($posterRaw, "http") === 0) ? $posterRaw : ($posterRaw ? "https://image.tmdb.org/t/p/w500" . $posterRaw : $fallback_img);

// Background Logic
if (!empty($opts['default_enable']) && !empty($opts['default_url'])) {
    $bg = esc_url($opts['default_url']);
    $bg_type = ($opts['default_type'] === 'video') ? 'video' : 'image';
} elseif (!empty($opts['fallback_enable']) && !empty($opts['fallback_url'])) {
    $bg = esc_url($opts['fallback_url']);
    $bg_type = ($opts['fallback_type'] === 'video') ? 'video' : 'image';
} else {
    $bg = $fallback_img;
    $bg_type = 'image';
}

$accent  = sanitize_hex_color($opts['accent']);
$behavior = sanitize_text_field($opts['timer_behavior']);
$base_timeout = intval($opts['timeout']);

// --- 2. ROLE & LOGIC CONFIG ---
$role_active = !empty($opts['role_logic_enable']);
$timeout = $base_timeout;
$show_ads = 'all'; 

if ($role_active && is_user_logged_in()) {
    if (current_user_can('administrator')) {
        $timeout = 0;
        $show_ads = 'none';
    } elseif (current_user_can('contributor')) {
        $timeout = 5; 
        $show_ads = 'none';
    } elseif (current_user_can('subscriber')) {
        $timeout = floor($base_timeout / 2);
        $show_ads = 'less';
    }
}

// Check Ad Slots
$render_top = ($show_ads === 'all' || $show_ads === 'less') && !empty($opts['ad_code_top']);
$render_btm = ($show_ads === 'all' || $show_ads === 'less') && !empty($opts['ad_code_bottom']);
// $render_pop logic removed

// --- 3. LIMIT ADS TO 'DOWNLOAD' TYPE ONLY ---
$is_download = (stripos($type, 'Download') !== false);

if (!$is_download) {
    $render_top = false;
    $render_btm = false;
    // $render_pop = false;
}

$shortened_url = function_exists('dprc_get_shortened_url') ? dprc_get_shortened_url($ourl, $post->ID) : $ourl;
$target = esc_url($shortened_url);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($title); ?> — Redirect</title>
<link rel="stylesheet" href="<?php echo esc_url($assets . '/css/rc.css'); ?>">
<style>
    :root { --dprc-accent: <?php echo esc_attr($accent); ?>; } 
    
    .dprc-ad-box {
        background: transparent;
        border-radius: 8px;
        min-height: 100px; 
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        width: 100%;
        max-width: 985px; 
    }
    
    #dprc-top-ad {
       /* margin-bottom: 20px !important;
        position: relative; 
        z-index: 99998;*/
    }
    #dprc-top-ad iframe {
        border: 0;
        padding: 0;
        width: 70% !important; 
        height: auto;
        display: block;
        margin: auto;
    }

    #dprc-btm-ad {
        /*margin-top: 20px !important;*/
    }
    #dprc-btm-ad iframe {
        border: 0;
        padding: 0;
        width: 70% !important; 
        height: auto;
        display: block;
        margin: auto;
    }
</style>
</head>
<body>
<div class="dprc-root">
    <?php if ($bg_type === 'video'): ?>
        <video class="dprc-video-bg" autoplay muted loop playsinline><source src="<?php echo esc_url($bg); ?>"></video>
    <?php else: ?>
        <div class="dprc-backdrop" style="background-image:url('<?php echo esc_url($bg); ?>')"></div>
    <?php endif; ?>

    <main class="dprc-main">
        
        <?php if ($render_top): ?>
            <div id="dprc-top-ad" class="dprc-ad-box">
                 <?php 
                    // FIX: Removed hardcoded A-Ads iframe. Only shows if user provided code.
                    $top_ad = stripslashes($opts['ad_code_top']);
                    if(!empty($top_ad)) {
                        echo $top_ad;
                    }
                 ?>
            </div>
        <?php endif; ?>

        <div class="dprc-card">

            <div class="dprc-poster" style="background-image:url('<?php echo $poster; ?>')"></div>
            
            <div class="dprc-meta">
                <h1 class="dprc-title"><?php echo esc_html($title); ?></h1>
                <div class="dprc-sub">
                    <div class="dprc-infobox"><span class="dprc-info-label">SIZE:</span><span class="dprc-info-value"><?php echo esc_html($size ?: 'N/A'); ?></span></div>
                    <div class="dprc-infobox"><span class="dprc-info-label">QUALITY:</span><span class="dprc-info-value"><?php echo esc_html($qual ?: 'N/A'); ?></span></div>
                    <div class="dprc-infobox"><span class="dprc-info-label">HITS:</span><span class="dprc-info-value"><?php echo intval($hits); ?></span></div>
                </div>
                
                <div class="dprc-loader-area">
                    <div class="dprc-timer-wrap dprc-desktop-timer"><div class="dprc-timer-number"><?php echo $timeout; ?></div><div class="dprc-timer-label">seconds</div></div>
                    <div class="dprc-circle-timer dprc-mobile-timer">
                        <svg class="dprc-circle" width="110" height="110"><circle class="dprc-circle-bg" cx="55" cy="55" r="50"></circle><circle class="dprc-circle-progress" cx="55" cy="55" r="50"></circle></svg>
                        <div class="dprc-circle-number"><?php echo $timeout; ?></div>
                    </div>
                    <div class="dprc-progress"><div class="dprc-progress-bar"></div></div>
                    
                    <a class="dprc-progress-btn" href="<?php echo $target; ?>">Continue</a>
                </div>
                
                <footer class="dprc-footer"><p>You will be redirected shortly...</p></footer>
            </div> 
        </div> 
        <?php if ($render_btm): ?>
        <div id="dprc-btm-ad" class="dprc-ad-box">
            <?php echo stripslashes($opts['ad_code_bottom']); ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<div class="adsbox pub_300x250" style="position:absolute; top:-9999px; width:1px; height:1px;"></div>

<script>
window.dprcServer = {
    timeout: <?php echo $timeout; ?>,
    target: "<?php echo $target; ?>",
    timer_behavior: "<?php echo $behavior; ?>",
    accent: "<?php echo $accent; ?>",
    ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
    post_id: <?php echo $post->ID; ?>,
    nonce: "<?php echo wp_create_nonce('dprc_click_nonce'); ?>" // Security Nonce
};
</script>
<script src="<?php echo esc_url($assets . '/js/rc.js'); ?>"></script>
</body>
</html>
<?php
/*
Plugin Name: WPScan Lab Hardening
Description: Basic hardening for the DevSecOps remediation step.
*/

defined('ABSPATH') || exit;

add_filter('xmlrpc_enabled', '__return_false');
add_filter('the_generator', '__return_empty_string');

add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
});

function wpscan_lab_disable_feeds() {
    status_header(404);
    nocache_headers();
    exit;
}

add_action('do_feed', 'wpscan_lab_disable_feeds', 1);
add_action('do_feed_rdf', 'wpscan_lab_disable_feeds', 1);
add_action('do_feed_rss', 'wpscan_lab_disable_feeds', 1);
add_action('do_feed_rss2', 'wpscan_lab_disable_feeds', 1);
add_action('do_feed_atom', 'wpscan_lab_disable_feeds', 1);
add_action('do_feed_rss2_comments', 'wpscan_lab_disable_feeds', 1);
add_action('do_feed_atom_comments', 'wpscan_lab_disable_feeds', 1);

add_filter('login_errors', function () {
    return 'Invalid login details.';
});

add_filter('robots_txt', function () {
    return "User-agent: *\nDisallow:\n";
}, 10, 0);

add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';

    if (!is_user_logged_in() && strpos($uri, '/wp-json/wp/v2/users') !== false) {
        return new WP_Error(
            'rest_forbidden',
            'User enumeration is disabled.',
            array('status' => 403)
        );
    }

    return $result;
});

add_filter('redirect_canonical', function ($redirect_url) {
    if (isset($_GET['author'])) {
        return false;
    }

    return $redirect_url;
});

add_action('template_redirect', function () {
    if (is_author()) {
        global $wp_query;

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        include get_query_template('404');
        exit;
    }
});

function wpscan_lab_strip_asset_version($src) {
    return remove_query_arg('ver', $src);
}

add_filter('script_loader_src', 'wpscan_lab_strip_asset_version', 999);
add_filter('style_loader_src', 'wpscan_lab_strip_asset_version', 999);

<?php
/**
 * Plugin Name: Unique Visitor Counter
 * Description: A plugin that counts and displays unique visitors.
 * Version: 1.0
 * Author: Aditya Kumar
 */

// Start a session to track unique visitors
function uvc_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'uvc_start_session');

// Create a table to store unique visitor data (IP addresses)
function uvc_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitor_counter';

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            visitor_ip VARCHAR(55) NOT NULL,
            visit_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'uvc_create_table');

// Increment the counter for each unique visitor
function uvc_increment_unique_visitor() {
    if (!isset($_SESSION['uvc_visited'])) {
        global $wpdb;
        $visitor_ip = $_SERVER['REMOTE_ADDR'];
        $table_name = $wpdb->prefix . 'unique_visitor_counter';

        // Check if this IP has already visited
        $ip_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE visitor_ip = %s", $visitor_ip));

        if ($ip_exists == 0) {
            // Insert the new IP into the database
            $wpdb->insert(
                $table_name,
                array(
                    'visitor_ip' => $visitor_ip,
                    'visit_time' => current_time('mysql')
                )
            );

            // Set session to prevent multiple increments in the same session
            $_SESSION['uvc_visited'] = true;
        }
    }
}
add_action('wp', 'uvc_increment_unique_visitor');

// Function to display the counter
function uvc_display_counter() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitor_counter';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    return '<h3>Unique Visitors: ' . $count . '</h3>';
}

// Shortcode to display the counter in posts/pages
function uvc_counter_shortcode() {
    return uvc_display_counter();
}
add_shortcode('unique_visitor_counter', 'uvc_counter_shortcode');

// Enqueue custom CSS for the plugin
function uvc_enqueue_styles() {
    wp_enqueue_style('uvc-styles', plugins_url('/style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'uvc_enqueue_styles');


// Function to drop the table on plugin deactivation
function uvc_remove_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitor_counter';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}
register_deactivation_hook(__FILE__, 'uvc_remove_table');

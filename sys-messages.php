<?php
/*
 * Plugin Name: System Messages for WordPress
 * Plugin URI: http://sys-messages.vutran.vn
 * Author:  Hotdeal Team
 * Author URI: http://www.hotdeal.vn
 * Description: The plugin have been used sent, received each other on webste
 * Version: 1.0.0
 */

// Prevent access directly
defined( 'ABSPATH' ) || exit;

define( 'SM_DIR', plugin_dir_path( __FILE__ ));
define( 'SM_INC_DIR', trailingslashit( SM_DIR . 'inc') );

define( 'SM_URL', plugin_dir_url( __FILE__ ) );
define( 'SM_CSS_URL', trailingslashit( SM_URL . 'css' ) );
define( 'SM_JS_URL', trailingslashit( SM_URL . 'js' ) );
define( 'SM_IMAGES_URL', trailingslashit( SM_URL . 'images' ) );

include_once ( SM_INC_DIR . 'message-inbox.php');
include_once ( SM_INC_DIR . 'message-sent.php');
include_once ( SM_INC_DIR . 'message-compose.php');
include_once ( SM_INC_DIR . 'widget.php');

if ( is_admin() ) {
    include_once ( SM_INC_DIR . 'options-setting.php');
}

register_activation_hook( __FILE__, 'sm_active' );

function sm_active(){
    global $wpdb;

    $query = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'sys_messages (
                `id` bigint(20) NOT NULL,
                `status` smallint(1) NOT NULL DEFAULT 0,
                `subject` text NOT NULL,
                `content` text NOT NULL,
                `author_id` bigint(20) NOT NULL,
                `recipient_id` bigint(20) NOT NULL,
                `timestamp_gmt` datetime NOT NULL DEFAULT 0000-00-00 00:00:00
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';

    $wpdb->query( $query );

    // Add option to config add_option()
}

//sys-message-style.css
if( is_admin() ){
    add_action( 'admin_enqueue_scripts', 'inc_scripts_sys_message' );
}


function inc_scripts_sys_message(){
    wp_enqueue_script( 'sys-message-admin-css', SM_CSS_URL . 'sys-message-admin.css' );
}


add_action( 'admin_menu', 'sm_admin_menu' );

function sm_admin_menu(){
    add_menu_page( 'System Message', 'System Message', 'read', 'message-inbox', 'message_inbox' );
    add_submenu_page( 'message-inbox', 'Message Inbox', 'Inbox', 'read', 'message-inbox', 'message_inbox' );
    add_submenu_page( 'message-inbox', 'Message Send', 'Sent', 'read', 'message-sent', 'message_sent' );
    add_submenu_page( 'message-inbox', 'Compose Message', 'Compose', 'read', 'message-compose', 'message_compose' );
}


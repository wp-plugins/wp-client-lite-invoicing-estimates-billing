<?php
/*
Plugin Name: WP-Client LITE : Invoicing, Estimates, Billing Extension : Create Invoices
Plugin URI: http://www.WP-Client.com
Description: Easily create estimates and invoices that your clients can pay online using the provided payment gateways. You can display invoices on your website, send in PDF format via email, or print out and send in traditional snail mail.
Author: WP-Client.com
Version: 1.0.4
Author URI: http://www.WP-Client.com
*/

if ( !function_exists( 'is_wpc_lite_active' ) ) {
    function is_wpc_lite_active() {
        if ( !is_multisite() ) {
            $active_for_network = false;
        } else {
            $plugins = get_site_option( 'active_sitewide_plugins' );
            if ( isset( $plugins['web-portal-lite-client-portal-secure-file-sharing-private-messaging/web-portal-lite-client-portal-secure-file-sharing-private-messaging.php'] ) ) {
                $active_for_network = true;
            } else {
                $active_for_network = false;
            }
        }
        return in_array( 'web-portal-lite-client-portal-secure-file-sharing-private-messaging/web-portal-lite-client-portal-secure-file-sharing-private-messaging.php', (array) get_option( 'active_plugins', array() ) ) || $active_for_network;
    }
}


if ( is_wpc_lite_active() ) {

    define( 'WPC_INV_LITE_VER', '1.0.4' );  //1.3.6
    define( 'WPC_INV_REQUIRED_VER', '1.0.0' );
    define( 'WPC_INV_TEXT_DOMAIN', 'wp-client-invoicing' );

    if ( !defined( 'WPC_CLIENT_PAYMENTS' ) ) {
        define( 'WPC_CLIENT_PAYMENTS', 1 );
    }

    require_once 'includes/inv_class.common.php';

    if ( defined( 'DOING_AJAX' ) ) {
        require_once 'includes/inv_class.admin_common.php';
        require_once 'includes/inv_class.ajax.php';
    } elseif ( is_admin() || defined('DOING_CRON')  ) {
        require_once 'includes/inv_class.admin_common.php';
        require_once 'includes/inv_class.admin_meta_boxes.php';
        require_once 'includes/inv_class.admin.php';
    } else {
        require_once 'includes/inv_class.user_shortcodes.php';
        require_once 'includes/inv_class.user.php';
    }

} else {
    //checking for version required
    add_action( 'admin_notices', 'wpc_inv_rec_wpc_lite_notice', 5 );
    function wpc_inv_rec_wpc_lite_notice() {
        if ( current_user_can( 'install_plugins' ) ) {

            $download = ( !file_exists( WP_PLUGIN_DIR . '/web-portal-lite-client-portal-secure-file-sharing-private-messaging/web-portal-lite-client-portal-secure-file-sharing-private-messaging.php' ) ) ? true : false;

            if ( $download ) {
                echo '<div class="error fade wpc_notice"><p>To use the <b>WP-Client: Estimates/Invoices</b> Extension you should install <a href="https://wordpress.org/plugins/web-portal-lite-client-portal-secure-file-sharing-private-messaging/" target="_blank">WP-Client Lite</a> plugin. <a href="' . get_admin_url() . 'plugin-install.php?tab=search&s=web-portal-lite-client-portal-secure-file-sharing-private-messaging">Install Plugin</a></span></p></div>';
            } else {
                echo '<div class="error fade wpc_notice"><p>To use the <b>WP-Client: Estimates/Invoices</b> Extension you should activate <a href="https://wordpress.org/plugins/web-portal-lite-client-portal-secure-file-sharing-private-messaging/" target="_blank">WP-Client Lite</a> plugin. <a href="' . get_admin_url() . 'plugins.php?action=activate&plugin=web-portal-lite-client-portal-secure-file-sharing-private-messaging%2Fweb-portal-lite-client-portal-secure-file-sharing-private-messaging.php&plugin_status=all&paged=1&s&_wpnonce=' . wp_create_nonce( 'activate-plugin_web-portal-lite-client-portal-secure-file-sharing-private-messaging/web-portal-lite-client-portal-secure-file-sharing-private-messaging.php' ). '">Activate Plugin</a></span></p></div>';
            }
        }

    }


 }

?>

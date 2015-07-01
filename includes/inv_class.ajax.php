<?php

if ( !class_exists( 'WPC_INV_AJAX' ) ) {

    class WPC_INV_AJAX extends WPC_INV_Admin_Common {

        /**
        * PHP 5 constructor
        **/
        function __construct() {

            $this->inv_common_construct();
            $this->inv_admin_common_construct();

            //actions for change currency
            add_action( 'wp_ajax_inv_change_currency', array( &$this, 'ajax_inv_change_currency' ) );

            //actions for change invoice status on Invoices Page
            add_action( 'wp_ajax_inv_change_status', array( &$this, 'ajax_inv_change_status' ) );

        }


         /**
         * AJAX - Change invoice status on Invoices Page
         **/
         function ajax_inv_change_status() {
            global $wpdb;

            $all_statuses = array( 'open', 'sent', 'void', 'refunded', 'paid', 'partial', 'draft', 'pending', 'inprocess' ) ;

            if( isset( $_POST['id'] ) && 0 < $_POST['id'] && isset( $_POST['new_status'] ) && in_array( $_POST['new_status'], $all_statuses ) ) {
                $wpdb->update( $wpdb->posts, array( 'post_status' => $_POST['new_status'] ), array( 'ID' => $_POST['id'] ), array( '%s' ), array( '%d' ) ) ;
            }
            exit;
         }



        function ajax_inv_change_currency() {
            global $wpc_client;
            $key = $_REQUEST['selected_curr'];
            $wpc_currency = $wpc_client->cc_get_settings( 'currency' );

            echo json_encode( array( 'symbol' => $wpc_currency[ $key ]['symbol'], 'align' => $wpc_currency[ $key ]['align'] ) );
            exit;
        }

    //end class
    }

    //create class var
    add_action( 'plugins_loaded', 'wpc_create_class_inv_ajax', 1, 1 );
    function wpc_create_class_inv_ajax() {
        if ( class_exists( 'WPC_Client_Common' ) ) {
            //checking for version required
            if ( version_compare( WPC_CLIENT_LITE_VER, WPC_INV_REQUIRED_VER, '<' ) ) {

            } else {
                $GLOBALS['wpc_inv_ajax'] = new WPC_INV_ajax();
            }
        }
    }


}

?>

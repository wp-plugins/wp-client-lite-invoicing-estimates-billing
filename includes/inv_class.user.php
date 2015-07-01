<?php
if ( !class_exists( "WPC_INV_User" ) ) {

    class WPC_INV_User extends WPC_INV_User_Shortcodes {

        /**
        * constructor
        **/
        function __construct() {

            $this->inv_common_construct();
            $this->inv_shortcodes_construct();

            add_action( 'wp_enqueue_scripts', array( &$this, 'load_css' ), 100 );

            //filter posts
            add_filter( 'the_posts', array( &$this, 'filter_posts' ), 99 );

        }


        function load_css() {
            wp_register_style( 'wpc-inv_user-style', $this->extension_url . 'css/user.css' );
            wp_enqueue_style( 'wpc-inv_user-style' );
        }


        /*
        * Start payment steps
        */
        function start_payment_steps( $invoice_id, $client_id ) {
            global $wpdb, $wpc_client, $wpc_payments_core;

            //get client INV
            $invoice_data = $this->get_data( $invoice_id ) ;
            $prefix = ( isset( $invoice_data['prefix'] ) ) ? $invoice_data['prefix'] : '' ;


            //haven't permision or wrong INV number
            if ( !isset( $invoice_data['id'] )  ) {
                wp_redirect( $wpc_client->cc_get_slug( 'hub_page_id' ) );
                exit;
            }

            $paid_total = $this->get_amount_paid( $invoice_data['id'] );
            if( $paid_total ) $invoice_data['total'] -= $paid_total;
            $slide_amount = ( isset( $_REQUEST['slide_amount'] ) && $_REQUEST['slide_amount'] ) ? (float)$_REQUEST['slide_amount'] : 0;


             if ( $slide_amount && $invoice_data['total'] > $slide_amount ) {
                if( !isset($invoice_data['min_deposit']) ||
                    ( $invoice_data['min_deposit'] <= $slide_amount && ( $invoice_data['total'] - $invoice_data['min_deposit'] ) >= $slide_amount ) ) {
                    $step = $this->get_step( $invoice_data['total'] );

                    $rest = $slide_amount - floor( $slide_amount / $step ) * $step;
                    if ( 0 == $rest || $invoice_data['min_deposit'] == $slide_amount )
                        $invoice_data['total'] = $slide_amount ;
                }
            }

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );


            $data = array();

            if ( isset( $wpc_invoicing['description'] ) ) {
                $data['item_name'] = $wpc_invoicing['description'] . $this->get_number_format( $invoice_data['number'], $prefix, $invoice_data['custom_number'] );
            } else {
                $data['item_name'] = $this->get_number_format( $invoice_data['number'], $prefix, $invoice_data['custom_number'] );
            }


            $data['invoice_id']     = $invoice_id;

            $payment_type = 'one_time';
            if ( isset( $invoice_data['recurring_type'] ) && true == $invoice_data['recurring_type'] ) {
                $data['profile_id'] = ( isset( $invoice_data['parrent_id'] ) ) ? $invoice_data['parrent_id'] : '';
                $data['a1']         = $invoice_data['total'];
                $data['a3']         = $invoice_data['total'];
                $data['t3']         = isset( $invoice_data['billing_period'] ) ? $invoice_data['billing_period'] : '';
                $data['p3']         = isset( $invoice_data['billing_every'] ) ? $invoice_data['billing_every'] : '';
                $data['c']          = isset( $invoice_data['billing_cycle'] ) ? $invoice_data['billing_cycle'] : '';

                $payment_type = 'recurring';
            }

            //get correct currency
            $wpc_currency = $wpc_client->cc_get_settings( 'currency' );

            $currency = 'USD';
            if ( isset( $invoice_data['currency'] ) && isset( $wpc_currency[$invoice_data['currency']]['code'] ) ) {
                $currency = $wpc_currency[$invoice_data['currency']]['code'];
            }

            $args = array(
                'function' => 'invoicing',
                'client_id' => $client_id,
                'amount' => $invoice_data['total'],
                'currency' => $currency,
                'payment_type' => $payment_type,
                'data' => $data,
            );

            //create new order
            $order_id = $wpc_payments_core->create_new_order( $args );


            if ( $order_id ) {
                $order = $wpc_payments_core->get_order_by( $order_id );

                //make link
                if ( $wpc_client->permalinks ) {
                    $payment_link = $wpc_client->cc_get_slug( 'payment_process_page_id' ) . $order['order_id'] .'/step-2';
                } else {
                    $payment_link = add_query_arg( array( 'wpc_page' => 'payment_process', 'wpc_order_id' => $order['order_id'], 'wpc_page_value' => 2 ), get_home_url() );
                }

                do_action( 'wp_client_redirect', $payment_link );
                exit;
            }

            die( 'error' );

        }


        /**
         * filter posts
         */
        function filter_posts( $posts ) {
            global $wp_query, $wpdb, $wpc_client;

            $filtered_posts = array();

            //if empty
            if ( empty( $posts ) || is_admin() )
                return $posts;

            $wpc_pages = $wpc_client->cc_get_settings( 'pages' );

            $post_ids = array();
            foreach( $posts as $post ) {
                $post_ids[] = $post->ID;
            }
            $sticky_posts_array = array();
            if( ( isset( $wpc_pages['invoicing_page_id'] ) && in_array( $wpc_pages['invoicing_page_id'], $post_ids ) ) || ( isset( $wpc_pages['invoicing_list_page_id'] ) && in_array( $wpc_pages['invoicing_list_page_id'], $post_ids ) ) ) {
                $sticky_posts_array = get_option( 'sticky_posts' );
                $sticky_posts_array = ( is_array( $sticky_posts_array ) && 0 < count( $sticky_posts_array ) ) ? $sticky_posts_array : array();
            }



            //other filter
            foreach( $posts as $post ) {

                if( in_array( $post->ID, $sticky_posts_array ) ) {
                    continue;
                }

                if ( isset( $wpc_pages['invoicing_page_id'] ) && $post->ID == $wpc_pages['invoicing_page_id'] ) {

                    if ( is_user_logged_in() ) {

                        if ( isset( $wp_query->query_vars['wpc_page_value'] ) && '' != $wp_query->query_vars['wpc_page_value'] ) {

                            $wp_query->is_page      = true;
                            $wp_query->is_home      = false;
                            $wp_query->is_singular  = true;
                            $filtered_posts[] = $post;
                            continue;

                        }
                    }
                    continue;
                }

                //add all other posts
                $filtered_posts[] = $post;

            }

            return $filtered_posts;
        }


    //end class
    }

    //create class var
    add_action( 'plugins_loaded', 'wpc_create_class_inv_user', 1 );
    function wpc_create_class_inv_user() {
        if ( class_exists( 'WPC_Client_Common' ) ) {
            //checking for version required
            if ( version_compare( WPC_CLIENT_LITE_VER, WPC_INV_REQUIRED_VER, '<' ) ) {

            } else {
                $GLOBALS['wpc_inv_user'] = new WPC_INV_User();
            }
        }
    }


}

?>
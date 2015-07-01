<?php


if ( !class_exists( "WPC_INV_Common" ) ) {

    class WPC_INV_Common {

        var $extension_dir;
        var $extension_url;
        /**
        * constructor
        **/
        function inv_common_construct() {

            //setup proper directories
            if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/wp-client-lite-invoicing-estimates-billing.php' ) ) {
                $this->extension_dir = WPMU_PLUGIN_DIR . '/wp-client-lite-invoicing-estimates-billing/';
                $this->extension_url = WPMU_PLUGIN_URL . '/wp-client-lite-invoicing-estimates-billing/';
            } else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/wp-client-lite-invoicing-estimates-billing/wp-client-lite-invoicing-estimates-billing.php' ) ) {
                $this->extension_dir = WP_PLUGIN_DIR . '/wp-client-lite-invoicing-estimates-billing/';
                $this->extension_url = WP_PLUGIN_URL . '/wp-client-lite-invoicing-estimates-billing/';
            } else if ( defined('WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/wp-client-lite-invoicing-estimates-billing.php' ) ) {
                $this->extension_dir = WP_PLUGIN_DIR;
                $this->extension_url = WP_PLUGIN_URL;
            }

            //check on SSL
            if ( function_exists( 'set_url_scheme' ) ) {
                $this->extension_url = set_url_scheme( $this->extension_url );
            }


            add_action( 'init', array( &$this, 'pdf_downloader' ) );
            add_action( 'init', array( &$this, '_create_post_type' ) );

            add_action( 'wpc_client_inv_send_reminder', array( &$this, 'send_reminder' ) );

            //add in array excluded post types
            add_filter( 'wpc_added_excluded_post_types', array( &$this, 'added_excluded_pt' ) );

            //add rewrite rules
            add_filter( 'rewrite_rules_array', array( &$this, '_insert_rewrite_rules' ) );

            //permission for delete currency
            add_filter( 'wpc_currency_permission', array( &$this, 'currency_permission' ) );


            //get continue link for payment
            add_filter( 'wpc_payment_get_continue_link_invoicing', array( &$this, 'get_continue_link' ), 99, 3 );

            //get active payment gateways
            add_filter( 'wpc_payment_get_activate_gateways_invoicing', array( &$this, 'get_active_payment_gateways' ), 99 );

            add_action( 'wpc_client_payment_paid_invoicing', array( &$this, 'order_paid' ) );
            add_action( 'wpc_client_payment_subscription_payment_invoicing', array( &$this, 'order_subscription' ) );

            add_action( 'wpc_client_payment_subscription_start_invoicing', array( &$this, 'order_subscription_start' ) );


            add_action( 'wpc_change_status_expired', array( &$this, 'change_status_expired' ) );

            //add translation
            add_action( 'plugins_loaded', array( &$this, '_load_textdomain' ) );


            add_action( 'wpc_invoice_refund', array( &$this, 'invoice_refund' ) );

        }


        function added_excluded_pt( $excluded_post_types ) {
            $excluded_post_types[] = 'wpc_invoice';
            return $excluded_post_types;
        }

        function invoice_refund( $order_id ) {

            $data = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}wpc_client_payments WHERE id = '%s'", $order_id ) );
            $data = json_decode( $data, true );
            $id_inv = ( isset( $data['invoice_id'] ) ) ? $data['invoice_id']  : '';
            if( (int)$id_inv ) {
                $wpdb->update( $wpdb->posts, array( 'post_status' => 'refunded' ), array( 'ID' => $id_inv ), array( '%s' ), array( '%d' ) );
            }

        }


        /**
         * Load translate textdomain file.
         */
        function _load_textdomain() {
            load_plugin_textdomain( WPC_INV_TEXT_DOMAIN, false, dirname( 'wp-client-lite-invoicing-estimates-billing/wp-client-lite-invoicing-estimates-billing.php' ) . '/languages/' );
        }


        function change_status_expired( $profile_id ) {
            if ( !$profile_id )
                return;

            $billing_cycle = get_post_meta( $profile_id, 'wpc_inv_billing_cycle', true );

            if( !$billing_cycle )
                return;

            global $wpdb;

            $all_payments = $wpdb->get_col( "SELECT count(id) as count FROM {$wpdb->prefix}wpc_client_payments
                                    WHERE order_status = 'paid'
                                    AND subscription_status = 'active'
                                    AND data LIKE '%\"profile_id\":\"" . (int)$profile_id . "\"%'
                                    GROUP BY client_id" );

            $all_canceled = $wpdb->get_var( "SELECT count( DISTINCT client_id ) FROM {$wpdb->prefix}wpc_client_payments
                                    WHERE subscription_status = 'canceled'
                                    AND data LIKE '%\"profile_id\":\"" . (int)$profile_id . "\"%'
                                    " );

            $all_clients = get_post_meta( $profile_id, 'wpc_inv_count_create_inv', true );


            $cycles = (int) $billing_cycle;
            if ( $cycles && $all_clients && (  $all_clients == count( $all_payments ) + $all_canceled ) ) {
                $status = 'expired';
                if ( $all_clients != $all_canceled ) {
                    foreach ( $all_payments as $value ) {
                        if ( $value != $cycles ) {
                            unset( $status );
                            break;
                        }
                    }
                }
            }

            if( isset( $status ) ) {
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET
                    post_status = '%s'
                    WHERE id = '%d'
                    ",
                    $status,
                    $profile_id
                ));
            }
        }


        /*
        *  permission for delete currency
        */
        function currency_permission( $id ) {
            $use = get_posts( array(
                    'meta_key'        => 'wpc_inv_currency',
                    'meta_value'      => $id,
                    'post_type'       => 'wpc_invoice',
                    'post_status'     => 'any'
                    ) );
            if ( $use )
                return false;
            else
                return true;
        }


        /**
         * convert EST to INV
         */
        function convert_to_inv( $id ) {
            global $wpdb, $wpc_client;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            $new_prefix = ( isset( $wpc_invoicing['prefix'] ) ) ? $wpc_invoicing['prefix']  : '';
            $new_number = $this->get_next_number();
            $new_date = date( "Y-m-d H:i:s" );

            $is_est = get_post_meta( $id, 'wpc_inv_post_type', true ) ;

            if ( 'est' == $is_est ) {
                update_post_meta( $id, 'wpc_inv_prefix', $new_prefix ) ;
                update_post_meta( $id, 'wpc_inv_number', $new_number ) ;
                //change status of INV
                update_post_meta( $id, 'wpc_inv_post_type', 'inv' ) ;
                $wpdb->update( $wpdb->posts, array( 'post_date' => $new_date ), array( 'ID' => $id ) );
                $wpdb->update( $wpdb->prefix . 'wpc_client_objects_assigns', array( 'object_type' => 'invoice' ), array( 'object_type' => 'estimate', 'object_id' => $id ) );
            }

        }


        /*
        * Download PDF of INV\EST
        */
        function pdf_downloader() {
            global $wpdb, $wp_query, $wpc_client;

            if ( isset( $_GET['wpc_action'] ) && 'download_pdf' == $_GET['wpc_action'] && isset( $_GET['id'] ) && '' != $_GET['id'] ) {

                $invoice_id = $_GET['id'];
                $invoice_data = array();
                $type = 'inv';

                if ( current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) {
                    $invoice_data = $this->get_data( $invoice_id );
                } else {
                    if ( current_user_can( 'wpc_client_staff' ) ) {
                        $client_id = get_user_meta( get_current_user_id(), 'parent_client_id', true );
                    } else {
                        $client_id = get_current_user_id();
                    }

                    $invoice_data = $this->get_data( $invoice_id );
                    if( isset( $invoice_data['type'] ) && 'est' == $invoice_data['type'] ) {
                        $invoices_client = $wpc_client->cc_get_assign_data_by_assign( 'estimate', 'client', $client_id );
                    } else {
                        $invoices_client = $wpc_client->cc_get_assign_data_by_assign( 'invoice', 'client', $client_id );
                    }

                    if ( !in_array( $_GET['id'], $invoices_client ) ) {
                        $invoice_data = array();
                    }
                }

                if ( !isset( $invoice_data['id'] )  )
                    return;

                if ( isset( $invoice_data['type']  ) && 'est' == $invoice_data['type'] )
                    $type = 'est';

                if( !class_exists( 'DOMPDF' ) ) {
                    $uploads            = wp_upload_dir();
                    $wpc_target_path    = $uploads['basedir'] . '/wpclient/_pdf_temp';

                    if ( !is_dir( $wpc_target_path ) ) {
                        //create uploads dir
                        mkdir( $wpc_target_path, 0777 );
                    }

                    //code for adding fonts to DOMPDF
                    //exec('php ' . $wpc_client->plugin_dir . 'includes/libs/pdf/load_font.php WPCUnicode ' . $wpc_client->plugin_dir . 'fonts/UKIJTuzUnicode.ttf ' . $wpc_client->plugin_dir . 'fonts/UKIJTuzUnicode-Bold.ttf', $output, $error );
                    //exec('php ' . $wpc_client->plugin_dir . 'includes/libs/pdf/load_font.php WPCCyr ' . $wpc_client->plugin_dir . 'fonts/LiberationSans-Regular.ttf ' . $wpc_client->plugin_dir . 'fonts/LiberationSans-Bold.ttf ' . $wpc_client->plugin_dir . 'fonts/LiberationSans-Italic.ttf ' . $wpc_client->plugin_dir . 'fonts/LiberationSans-BoldItalic.ttf', $output, $error );

                    include( $wpc_client->plugin_dir . 'includes/libs/pdf/dompdf_config.inc.php' );
                }

                $content = $this->invoicing_put_values( $invoice_data );

                $prefix = ( isset( $invoice_data['prefix'] ) ) ? $invoice_data['prefix'] : '' ;

                $html_header = '<html><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type"></head><body>';
                $html_footer = '</body></html>';

                if ( !ini_get( 'safe_mode' ) ) {
                    $temp_memory_limit          = ini_get( "memory_limit" );
                    $temp_max_execution_time    = ini_get( "max_execution_time" );
                    ini_set( "memory_limit", "999M" );
                    ini_set( "max_execution_time", "999" );
                }

                $dompdf = new DOMPDF();
                $dompdf->load_html( $html_header . $content . $html_footer);
                $dompdf->set_paper( 'A4' , 'portrait' );
                $dompdf->render();

                $dompdf->stream( $invoice_data['type'] . '_' . $this->get_number_format( $invoice_data['number'], $prefix, $invoice_data['custom_number'], $type ) . '.pdf' );

                if ( isset( $temp_memory_limit ) && isset( $temp_max_execution_time ) ) {
                    ini_set( "memory_limit", $temp_memory_limit );
                    ini_set( "max_execution_time", $temp_max_execution_time );
                }
                exit;
            }

        }


        /*
        * Register post types
        */
        function _create_post_type() {

            //Clientpage (Portal page) post type
            $labels = array(
                'name'                  => __('Invoices', WPC_INV_TEXT_DOMAIN ),
                'singular_name'         => __('Invoice', WPC_INV_TEXT_DOMAIN ),
                'add_new'               => __( 'Add New', WPC_INV_TEXT_DOMAIN ),
                'add_new_item'          => __( 'Add New Invoice', WPC_INV_TEXT_DOMAIN ),
                'edit_item'             => __( 'Edit Invoice', WPC_INV_TEXT_DOMAIN ),
                'new_item'              => __( 'New Invoice', WPC_INV_TEXT_DOMAIN ),
                'view_item'             => __( 'View Invoice', WPC_INV_TEXT_DOMAIN ),
                'search_items'          => __( 'Search Invoices', WPC_INV_TEXT_DOMAIN ),
                'not_found'             => __( 'No invoices found', WPC_INV_TEXT_DOMAIN ),
                'not_found_in_trash'    => __( 'No invoices found in Trash', WPC_INV_TEXT_DOMAIN ),
                'parent_item_colon'     => ''
            );

            $args = array(
                'labels'                => $labels,
                'singular_label'        => __( 'Invoice', WPC_INV_TEXT_DOMAIN ),
                'public'                => false,
                'show_ui'               => false,
                //'rewrite'               => array( 'slug' => $wp_properties[ 'configuration' ][ 'base_slug' ] ),
                //'query_var'             => $wp_properties[ 'configuration' ][ 'base_slug' ],
                'capability_type'       => 'wpc_invoice',
                //'capabilities'          => array( 'edit_posts' => 'edit_published_clientpages' ),
                '_edit_link'            => 'admin.php?page=wpclients_invoicing&tab=invoice_edit&id=%d',
                //'hierarchical'          => false,
                //'_builtin'              => false,
                //'supports'              => array( 'title', 'editor', 'thumbnail' )

            );

            register_post_type('wpc_invoice', $args);

        }


        /**
         * send reminder
         */
         function send_reminder() {
            global $wpdb, $wpc_client;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            if( isset( $wpc_invoicing['reminder_days_enabled'] ) && 'yes' == $wpc_invoicing['reminder_days_enabled'] ) {

                $remind_days = ( isset( $wpc_invoicing['reminder_days'] ) && 0 < $wpc_invoicing['reminder_days'] && 32 > $wpc_invoicing['reminder_days'] ) ? (int)$wpc_invoicing['reminder_days'] : 1;

                /*
                $invs_for_remind = $wpdb->get_results(
                    "SELECT *
                    FROM {$wpdb->prefix}wpc_client_invoicing
                    WHERE ( ( status != 'paid' AND status != 'void' ) OR status IS NULL) AND
                        due_date > 0 AND
                        due_date < '" . time() . "' AND
                        ( ( last_reminder IS NULL AND due_date < '" . ( time() - 60*60*24*$remind_days ) . "' ) OR last_reminder < '" . ( time() - 60*60*24*$remind_days ) . "' )",
                ARRAY_A );
                */
                $time = time();
                $reminder_one_day = ( !isset( $wpc_invoicing['reminder_one_day'] ) || 'yes' == $wpc_invoicing['reminder_one_day'] ) ? " OR ( pm4.meta_value = '1' AND pm1.meta_value < '" . ( $time+60*60*24 ) . "' )" : '';

                $reminder_after_every = ( isset( $wpc_invoicing['reminder_after'] ) && 0 < $wpc_invoicing['reminder_after'] && 32 > $wpc_invoicing['reminder_after'] ) ?
                " OR pm1.meta_value < '" . $time . "' AND
                    (
                        pm4.meta_value < pm1.meta_value OR
                        pm4.meta_value < '" . ( $time - 60*60*24*$wpc_invoicing['reminder_after'] ) . "'
                    )

                " : '';

                $invs_for_remind = $wpdb->get_results(
                    "SELECT p.ID as id, coa.assign_id as client_id, pm2.meta_value as prefix, pm3.meta_value as number, pm5.meta_value as custom_number, pm4.meta_value as last_reminder
                        FROM {$wpdb->posts} p
                        INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                        LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'invoice' )
                        LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_due_date' )
                        LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_prefix' )
                        LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_number' )
                        LEFT JOIN {$wpdb->postmeta} pm4 ON ( p.ID = pm4.post_id AND pm4.meta_key = 'wpc_inv_last_reminder' )
                        LEFT JOIN {$wpdb->postmeta} pm5 ON ( p.ID = pm5.post_id AND pm5.meta_key = 'wpc_inv_custom_number' )
                        WHERE p.post_type = 'wpc_invoice' AND ( ( p.post_status != 'paid' AND p.post_status != 'void' AND p.post_status != 'refunded' AND p.post_status != 'draft' ) OR p.post_status IS NULL ) AND
                            pm1.meta_value > 0 AND
                            ( pm1.meta_value > '" . $time . "' AND
                                ( ( pm4.meta_value IS NULL AND pm1.meta_value < '" . ( $time+60*60*24*$remind_days ) . "' )
                                    {$reminder_one_day} ) {$reminder_after_every} )
                        ",
                        ARRAY_A );

                if ( is_array( $invs_for_remind ) && 0 < count( $invs_for_remind ) ) {
                    //send email to client
                    foreach ( $invs_for_remind as $inv ) {
                        if ( 0 < $inv['client_id'] ) {
                            $userdata = get_userdata( $inv['client_id'] );

                            $prefix = ( isset( $inv['prefix'] ) ) ? $inv['prefix'] : '';

                            $args = array(
                                'client_id' => $inv['client_id'],
                                'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] )
                            );
                            //send email
                            $wpc_client->cc_mail( 'pay_rem', $userdata->get( 'user_email' ), $args, 'invoice_reminder' );

                            //update last reminder time
                            if ( $inv['last_reminder'] ) {
                                update_post_meta( $inv['id'], 'wpc_inv_last_reminder', $time ) ;
                            } else {
                                update_post_meta( $inv['id'], 'wpc_inv_last_reminder', '1' ) ;
                            }

                        }
                    }
                }
            }

        }


        /**
         * Adding a new rule
         **/
        function _insert_rewrite_rules( $rules ) {
            global $wpc_client;
            $newrules = array();

            //invoicing pages
            $newrules[$wpc_client->cc_get_slug( 'invoicing_page_id', false, false ) . '/([\w\d_-]+)/?$'] = 'index.php?wpc_page=invoicing&wpc_page_value=$matches[1]';

            return $newrules + $rules;
        }


        /**
         * get status texts for display
         */
        function display_status_name( $status = '' ) {

            if ( $status ) {
                switch( $status ) {
                    case 'new':
                        return __( 'New', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'refunded':
                        return __( 'Refunded', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'paid':
                        return __( 'Paid', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'partial':
                        return __( 'Partial', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'inprocess':
                        return __( 'In-Process', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'active':
                        return __( 'Active', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'draft':
                        return __( 'Draft', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'void':
                        return __( 'Void', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'declined':
                        return __( 'Declined', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'ended':
                        return __( 'Ended', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'stopped':
                        return __( 'Stopped', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'pending':
                        return __( 'Pending', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'expired':
                        return __( 'Expired', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'sent':
                        return __( 'Open (Sent)', WPC_INV_TEXT_DOMAIN );
                        break;
                    case 'open':
                        return __( 'Open', WPC_INV_TEXT_DOMAIN );
                        break;

                }

            }

            return __( 'New', WPC_INV_TEXT_DOMAIN );
        }


        /**
         * get status texts for display
         */
        function get_currency( $number = 0, $span = false, $selected_curr = '' ) {
            global $wpc_client;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );
            $rate_capacity = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;
            $number = number_format( (float)$number, $rate_capacity, '.', '' );
            $thousands_separator = ( isset( $wpc_invoicing['thousands_separator'] ) && !empty( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '';

            $number = number_format( round( $number , 2 ), $rate_capacity, '.', $thousands_separator );

            if ( $span )
                $number = '<span class="amount">' . $number . '</span>' ;

            $ver = get_option( 'wp_client_ver' );

            if ( version_compare( $ver, '3.5.0' ) ) {
                $wpc_currency = $wpc_client->cc_get_settings( 'currency' );
                $def_currency = '';

                if ( '' != $selected_curr ) {
                    $def_currency = @$wpc_currency[ $selected_curr ];
                } else {
                    foreach( $wpc_currency as $key => $value ) {
                        if( 1 == $value['default'] ) {
                            $def_currency = $wpc_currency[ $key ];
                            break;
                        }
                    }
                }

                if ( 'left' ==  $def_currency['align'] ) {
                    $number = $def_currency['symbol'] . $number ;
                } else {
                    $number = $number . $def_currency['symbol'] ;
                }
            } else {

                $currency_symbol    = array(
                    'left'  => ( isset( $wpc_invoicing['currency_symbol'] )
                        && ( !isset( $wpc_invoicing['currency_symbol_align'] )
                        || 'left' == $wpc_invoicing['currency_symbol_align'] ) )
                        ? $wpc_invoicing['currency_symbol'] : '',
                    'right' => ( isset( $wpc_invoicing['currency_symbol'] )
                        && isset( $wpc_invoicing['currency_symbol_align'] )
                        && 'right' == $wpc_invoicing['currency_symbol_align'] )
                        ? $wpc_invoicing['currency_symbol'] : '',
                );


               $number = $currency_symbol['left'] . $number . $currency_symbol['right'];
}

            return $number;
        }


        function get_currency_and_side( $invoice_id ) {
            $result = array();
            global $wpc_client;
            $ver = get_option( 'wp_client_ver' );

            if ( version_compare( $ver, '3.5.0' ) ) {
                $wpc_currency = $wpc_client->cc_get_settings( 'currency' );
                $currency = '';
                $selected_curr = get_post_meta( $invoice_id, 'wpc_inv_currency', true );

                if ( $selected_curr && '' != $selected_curr ) {
                    $currency = $wpc_currency[ $selected_curr ];
                } else {
                    foreach( $wpc_currency as $key => $value ) {
                        if( 1 == $value['default'] ) {
                            $currency = $wpc_currency[ $key ];
                            break;
                        }
                    }
                }
                if ( 'left' ==  $currency['align'] ) {
                    $result['align'] = 'left';
                } else {
                    $result['align'] = 'right';
                }
                $result['symbol'] = $currency['symbol'];
            } else {
                $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );
                if ( !isset( $wpc_invoicing['currency_symbol_align'] ) || 'left' == $wpc_invoicing['currency_symbol_align']  ) {
                    $result['align'] = 'left';
                } else {
                    $result['align'] = 'right';
                }
                $result['symbol'] = ( isset( $wpc_invoicing['currency_symbol'] ) ) ? $wpc_invoicing['currency_symbol'] : '';
            }

            return $result;

        }


        /**
         * get step for slider for users
         */
        function get_step( $amount ) {
            if ( 100 >= $amount )
                $step = 1;
            else if( 1000 >= $amount )
                $step = 5;
            else
                $step = 10;
            return $step;
        }


        /**
         * put values in plase holders
         */
        function invoicing_put_values( $data ) {
            global $wpdb, $wpc_client, $wpc_payments_core;


            if ( $data ) {
                $wpc_business_info  = $wpc_client->cc_get_settings( 'business_info' );

                $wpc_templates_shortcodes   = $wpc_client->cc_get_settings( 'templates_shortcodes' );

                if ( isset( $wpc_templates_shortcodes['wpc_client_inv_' . $data['type']] ) && '' != $wpc_templates_shortcodes['wpc_client_inv_' . $data['type']] ) {
                    //get custom template
                    $template = $wpc_templates_shortcodes['wpc_client_inv_' . $data['type']];
                } else {
                    //get default template
                    $template = file_get_contents( $this->extension_dir . 'includes/templates/' . 'wpc_client_inv_' . $data['type'] . '.tpl' );
                    $wpc_templates_shortcodes['wpc_client_inv_' . $data['type']] = $template;
                    do_action( 'wp_client_settings_update', $wpc_templates_shortcodes, 'templates_shortcodes' );
                }

                //Set date format
                if ( get_option( 'date_format' ) ) {
                    $date_format = get_option( 'date_format' );
                } else {
                    $date_format = 'm/d/Y';
                }


                $selected_curr  = ( isset( $data['currency'] ) ) ? $data['currency'] : '' ;
                $prefix         = ( isset( $data['prefix'] ) ) ? $data['prefix'] : '' ;
                $custom_number  = ( isset( $data['custom_number'] ) ) ? $data['custom_number'] : '' ;
                $LateFee        = ( isset( $data['late_fee'] ) ) ? $data['late_fee'] : '0';
                $is_late_fee = false;
                $count_late_fee = 0;
                if ( isset( $data['due_date'] ) && '' != $data['due_date'] && $data['due_date'] < time() && 0 < $LateFee ) {
                     $is_late_fee = true;
                     $count_late_fee = $LateFee;
                }

                $type = ( 'est' == $data['type'] ) ? 'est' : 'inv' ;

                $arr_data       = array (
                    'InvoiceNumber' => $this->get_number_format( $data['number'], $prefix, $custom_number, $type ),

                    'CustomerName' => '',
                    'CustomerBAddress' => '',
                    'CustomerBCity' => '',
                    'CustomerBState' => '',

                    'InvoiceDescription' => ( isset( $data['description'] ) ) ? stripslashes( $data['description'] ) : '',

                    'InvoiceDate' => ( isset( $data['date'] ) && 0 < $data['date'] ) ? $wpc_client->cc_date_timezone( $date_format, strtotime( $data['date'] ) ) : '',

                    'DueDate' => ( isset( $data['due_date'] ) && '' != $data['due_date'] ) ? $wpc_client->cc_date_timezone( $date_format, $data['due_date'] ) : '',
                    'IsLateFee' => $is_late_fee,
                    'PONumber' => '',

                    'Notes' => ( isset( $data['note'] ) ) ? stripslashes( $data['note'] ) : '',

                    'InvoiceSubTotal' => '',

                    'TotalDiscount' => '',
                    'TaxName' => '',
                    'TaxRate' => '',
                    'TotalTax' => '',

                    'InvoiceTotal' => '',
                    'LateFee' =>  $this->get_currency( $LateFee, 0, $selected_curr ),
                    'PaymentMade' => '',


                );

                if ( isset( $data['terms'] ) ) $arr_data['TermsAndCondition'] = stripslashes( $data['terms'] ) ;

                //items
                $total_items = 0;
                $arr_data['CustomFields'] = $arr_data['TitleCustomFields'] = array();
                if ( '' != $data['items'] ) {
                    $data['items'] = unserialize( $data['items'] );
                    if ( isset( $data['custom_fields'] ) ) {
                        $wpc_inv_custom_fields = $wpc_client->cc_get_settings( 'inv_custom_fields' );
                        if( isset( $data['custom_fields']['description'] ) && 1 == $data['custom_fields']['description'] ) {
                            $arr_data['show_description'] = true;
                        }
                        foreach( $wpc_inv_custom_fields as $key => $field ) {
                            if( isset( $data['custom_fields'][ $key ] ) && 1 == $data['custom_fields'][ $key ] ) {
                                $arr_data['CustomFields'][] = array( 'type' => $field['type'], 'slug' => $key, 'options' => ( ( isset( $field['options'] ) ) ? $field['options'] : '' ), );
                                $arr_data['TitleCustomFields'][] = $field['title'];
                            }
                        }
                    } else {
                        $arr_data['show_description'] = true;
                    }

                    if ( is_array( $data['items'] ) && 0 < count( $data['items'] ) ) {
                        foreach( $data['items'] as $item ) {
                            $quantity = ( isset( $item['quantity'] ) ) ? $item['quantity'] : '1';

                            $total_items = $total_items + $item['price'] * $quantity;

                            $array_cf = array();
                            foreach( $arr_data['CustomFields'] as $cf ) {
                                if ( 'checkbox' == $cf['type'] ) {
                                    if ( isset( $item[ $cf['slug'] ] ) && 1 == $item[ $cf['slug'] ] ) {
                                        $array_cf[ $cf['slug'] ] = '<img src="' . $wpc_client->plugin_url . 'images/checkbox_check.png" border="0" width="16" height="16" alt="checkbox.png">' ;
                                    } else {
                                        $array_cf[ $cf['slug'] ] = '<img src="' . $wpc_client->plugin_url . 'images/checkbox_uncheck.png" border="0" width="16" height="16" alt="checkbox.png">' ;
                                    }
                                    //$array_cf[ $cf['slug'] ] = '<img type="checkbox" ' . $checked . ' disabled />' ;
                                } elseif ( 'selectbox' == $cf['type']  ) {
                                    $array_cf[ $cf['slug'] ] = ( isset( $item[ $cf['slug'] ] ) && isset ( $cf['options'][ $item[ $cf['slug'] ] ] ) ) ? $cf['options'][ $item[ $cf['slug'] ] ]  : '';
                                } else {
                                    $array_cf[ $cf['slug'] ] = ( isset( $item[ $cf['slug'] ] ) ) ? stripslashes( $item[ $cf['slug'] ] ) : '';
                                }
                            }

                            $arr_data['items'][] = array_merge( array (
                                'ItemName'          => ( isset( $item['name'] ) ) ? $item['name'] : '',
                                'ItemDescription'   => ( isset( $item['description'] ) ) ? stripslashes( $item['description'] ) : '',
                                'ItemQuantity'      => $quantity,
                                'ItemRate'          => ( isset( $item['price'] ) ) ? $this->get_currency( $item['price'], 0, $selected_curr ) : '',
                                'ItemTotal'         => ( isset( $item['price'] ) ) ? $this->get_currency( $item['price'] * $quantity, 0, $selected_curr ) : '',
                            ),
                            $array_cf );

                        }

                        if( 0 == count( $arr_data['CustomFields'] ) ){
                            unset( $arr_data['CustomFields'] ) ;
                            unset( $arr_data['TitleCustomFields'] ) ;
                        }
                    }
                }

                $arr_data['colspan_for_name'] = 1 ;
                if ( isset( $arr_data['CustomFields'] ) )
                    $arr_data['colspan_for_name'] += count( $arr_data['CustomFields'] ) ;

                //discounts
                $total_discounts = 0;
                if ( '' != $data['discounts'] ) {
                    $data['discounts'] = unserialize( $data['discounts'] );

                    if ( is_array( $data['discounts'] ) && 0 < count( $data['discounts'] ) ) {
                        foreach( $data['discounts'] as $disc ) {
                            if ( isset( $disc['type'] ) ) {
                                $type = ( '' != $disc['type'] ) ? ucfirst( $disc['type'] ) : '' ;
                            } else {
                                $type = '';
                            }
                            $total_discounts = $total_discounts + $disc['total'] ;

                            $arr_data['discounts'][] = array (
                                'name'          => ( isset( $disc['name'] ) ) ? $disc['name'] : '',
                                'description'   => ( isset( $disc['description'] ) ) ? $disc['description'] : '',
                                'type'          => $type,
                                'rate'          => $disc['rate'],
                                'total'         => ( isset( $disc['total'] ) ) ? $this->get_currency( $disc['total'], 0, $selected_curr ) : '',
                            );
                        }
                    }
                }

                //taxes
                $total_tax = 0;
                if ( '' != $data['taxes'] ) {
                    $data['taxes'] = unserialize( $data['taxes'] );

                    if ( is_array( $data['taxes'] ) && 0 < count( $data['taxes'] ) ) {
                        foreach( $data['taxes'] as $tax ) {
                            if ( isset( $tax['type'] ) ) {
                                if ( 'before' == $tax['type'] )
                                    $type = 'Before Discount' ;
                                else if ( 'after' == $tax['type'] )
                                    $type = 'After Discount' ;
                            } else {
                                $type = '';
                            }
                            $total_tax = $total_tax + $tax['total'] ;

                            $arr_data['taxes'][] = array (
                                'name'          => ( isset( $tax['name'] ) ) ? $tax['name'] : '',
                                'description'   => ( isset( $tax['description'] ) ) ? $tax['description'] : '',
                                'type'          => $type,
                                'rate'          => $tax['rate'],
                                'total'         => ( isset( $tax['total'] ) ) ? $this->get_currency( $tax['total'], 0, $selected_curr ) : '',
                            );

                        }
                    }
                }
               /*if ( isset( $data['taxes'] ) && '' != $data['taxes'] ) {
                    $data['taxes'] = unserialize( $data['taxes'] );
                    if ( is_array( $data['taxes'] ) && 0 < count( $data['taxes'] ) ) {
                        $tax_rate = $data['tax'][key( $data['tax'] )]['rate'] * 1;

                        $arr_data['TaxName'] = key( $data['tax'] );
                        $arr_data['TaxRate'] = $data['tax'][key( $data['tax'] )]['rate'];

                    }
                }  */

                $invoice_total = $total_items - $total_discounts + $total_tax + $count_late_fee;


                $arr_data['TotalDiscount']      = $this->get_currency( $total_discounts, 0, $selected_curr ) ;
                $arr_data['IsTotalDiscount']    = ( $total_discounts ) ? true : false ;
                $arr_data['TotalTax']           = $this->get_currency( $total_tax, 0, $selected_curr ) ;
                $arr_data['IsTotalTax']         = ( $total_tax ) ? true : false ;
                $arr_data['InvoiceSubTotal']    = $this->get_currency( $total_items, 0, $selected_curr ) ;

                $arr_data['InvoiceTotal']       = $this->get_currency( $invoice_total, 0, $selected_curr ) ;

                if ( isset( $data['order_id'] ) && '' != $data['order_id'] ) {
                    $payment_amount = $this->get_amount_paid( $data['id'] );

                    $arr_data['PaymentMade'] = $this->get_currency( $payment_amount, 0, $selected_curr ) ;

                    if ( !isset( $data['recurring_type'] )  ) {
                        $arr_data['TotalRemaining'] = $this->get_currency( $invoice_total - $payment_amount, 0, $selected_curr  ) ;
                    }
                }

                $args = array( 'client_id' => $data['client_id'] );
                $template = $wpc_client->cc_replace_placeholders( $template, $args, 'invoicing_' . $data['type'] . '_template' );

                return do_shortcode( $wpc_client->cc_getTemplateContent( 'wpc_client_inv_' . $data['type'], $arr_data, $data['client_id'], $template ) );

            }

            return '';

        }


        /**
         * make correct number format
         */
        function get_number_format( $number, $pref = '', $custom_number = '', $type = 'inv' ) {
            if ( $custom_number )
                return $number;

            global $wpc_client;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            $ending = ( 'est' == $type ) ? '_est' : '' ;

            if ( !isset( $wpc_invoicing['display_zeros' . $ending] ) || 'yes' == $wpc_invoicing['display_zeros' . $ending] ) {
                if ( !isset( $wpc_invoicing['digits_count' . $ending] )
                        || !is_numeric( $wpc_invoicing['digits_count' . $ending] )
                        || 3 > $wpc_invoicing['digits_count' . $ending] ) {
                    $number = str_pad( $number, 8, '0', STR_PAD_LEFT );
                } else {
                    $number = str_pad( $number, $wpc_invoicing['digits_count' . $ending], '0', STR_PAD_LEFT );
                }
            }

            return $pref . $number;
        }

        /**
         * get already paid amount
         */
        function get_amount_paid( $invoice_id ) {
            global $wpc_payments_core;

            $amount_paid = 0;

            $inv = $this->get_data( $invoice_id );

            if ( isset( $inv['order_id'] ) && is_array( $inv['order_id'] ) ) {
                $orders = $wpc_payments_core->get_orders( $inv['order_id'] );

                if ( is_array( $orders ) && $orders ) {
                    foreach( $orders as $order ) {
                        if ( isset($order['order_status'] ) && ( 'paid' == $order['order_status'] || 'order_paid' == $order['order_status'] ) ) {
                            $amount_paid += $order['amount'];
                        }
                    }
                }
            }

            return $amount_paid;
        }


        /**
         * Get items
         */
        function get_data( $id, $type = '' )  {
            global $wpdb, $wpc_client;

            if ( 'repeat_invoice' == $type ) {
                $data = $wpdb->get_row( $wpdb->prepare( "SELECT p.ID as id, p.post_title as title, p.post_content as description, pm1.meta_value as type, p.post_date as date, p.post_status as status
                                FROM {$wpdb->posts} p
                                LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_post_type' )
                                WHERE p.ID = %d
                            ", $id ), 'ARRAY_A' );

                $data['clients_id'] = implode( ',', $wpc_client->cc_get_assign_data_by_object( 'repeat_invoice', $id, 'client' ) ) ;
                $data['groups_id'] = implode( ',', $wpc_client->cc_get_assign_data_by_object( 'repeat_invoice', $id, 'circle' ) ) ;

            } else {
                $data = $wpdb->get_row( $wpdb->prepare( "SELECT p.ID as id, p.post_title as title, p.post_content as description, pm1.meta_value as type, coa.assign_id as client_id, p.post_date as date, p.post_status as status
                                FROM {$wpdb->posts} p
                                LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_post_type' )
                                LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type IN ( 'invoice', 'estimate', 'accum_invoice' ) )
                                WHERE p.ID = %d
                            ", $id ), 'ARRAY_A' );
            }
            if ( $data ) {
                $all_meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ", $id ), 'ARRAY_A' );
                $all_data = array();
                foreach ( $all_meta_data as $value ) {
                    $new_name_key = str_replace( 'wpc_inv_', '', $value['meta_key'] );
                    $all_data[ $new_name_key ] = $value['meta_value'];
                }
                $data = array_merge( $data, $all_data );

                if ( isset( $data['order_id'] ) && '' != $data['order_id'] ) {
                    $data['order_id'] = unserialize( $data['order_id'] );
                }

                if ( isset( $data['custom_fields'] ) && '' != $data['custom_fields'] ) {
                    $data['custom_fields'] = unserialize( $data['custom_fields'] );
                }

                if ( isset( $data['description'] ) ) {
                    $data['description'] = stripslashes( $data['description'] );
                }

                if ( isset( $data['terms'] ) ) {
                    $data['terms'] = stripslashes( $data['terms'] );
                }

                if ( isset( $data['note'] ) ) {
                    $data['note'] = stripslashes( $data['note'] );
                }

                if ( !isset( $data['custom_number'] ) ) {
                    $data['custom_number'] = false;
                }
            }

            return $data;
        }

        function create_inv_from_profile( $id_invoice_profile, $post_status = '', $only_client_id = '' ) {
            global $wpdb, $wpc_client;

            $data = $this->get_data( $id_invoice_profile );

            $change_count = 0;
            if ( !isset( $data['total'] ) || 0 >= $data['total'] )
                $change_count = -1;

            $count_created = ( isset( $data['count_created'] ) ) ? $data['count_created'] + 1 : 1 ;
            $count_created += $change_count;
            update_post_meta( $data['id'], 'wpc_inv_count_created', $count_created );

            $billing_period = ( isset( $data['billing_period'] ) ) ? $data['billing_period'] : 'day';
            $billing_every = ( isset( $data['billing_every'] ) ) ? $data['billing_every'] : 1 ;
            $time_create_inv = ( isset( $data['next_create_inv'] ) ) ? $data['next_create_inv'] : '';
            if ( $time_create_inv && ( !isset( $data['billing_cycle'] ) || $count_created < $data['billing_cycle'] ) ) {
                switch( $billing_period ) {
                    case 'week':
                        $next_create_inv = strtotime( "+$billing_every week", $time_create_inv );
                        break;
                    case 'month':
                        if ( isset( $data['last_day_month'] ) )
                            $next_create_inv = strtotime( date( "Y-m-d", strtotime( "last day of next month" ) ) . " 00:00:00" ) ;
                        else
                            $next_create_inv = strtotime( "+$billing_every month", $time_create_inv );
                        break;
                    case 'year':
                        $next_create_inv = strtotime( "+$billing_every year", $time_create_inv );
                        break;
                    case 'day':
                    default:
                        $next_create_inv = strtotime( "+$billing_every day", $time_create_inv );
                        break;
                }
            }

            if ( isset( $next_create_inv ) ) {
                $wpdb->update( $wpdb->posts, array( 'post_status' => 'active' ), array( 'ID' => $data['id'] ), array( '%s' ), array( '%d' ) );
                update_post_meta( $data['id'], 'wpc_inv_next_create_inv', $next_create_inv );
                update_post_meta( $data['id'], 'wpc_inv_from_date', date( "m/d/Y", $next_create_inv ) );
            } else {
                if ( 'auto_charge' == $data['recurring_type'] ) {
                    $wpdb->update( $wpdb->posts, array( 'post_status' => 'active' ), array( 'ID' => $data['id'] ), array( '%s' ), array( '%d' ) );
                } else {
                    $wpdb->update( $wpdb->posts, array( 'post_status' => 'ended' ), array( 'ID' => $data['id'] ), array( '%s' ), array( '%d' ) );
                }
                delete_post_meta( $data['id'], 'wpc_inv_next_create_inv' );
                delete_post_meta( $data['id'], 'wpc_inv_from_date' );
            }

            if ( '' != $only_client_id )
                $data['clients_id'] = $only_client_id ;

            $data['items']      = unserialize( $data['items'] );
            $data['discounts']  = unserialize( $data['discounts'] );
            $data['taxes']      = unserialize( $data['taxes'] );
            $data['cc_emails']  = unserialize( $data['cc_emails'] );


             if ( isset( $data['type'] ) && 'repeat_inv' == $data['type'] ) {
                 $clients_id = $wpc_client->cc_get_assign_data_by_object( 'repeat_invoice', $data['id'], 'client' );
                 $groups_id = $wpc_client->cc_get_assign_data_by_object( 'repeat_invoice', $data['id'], 'circle' );

                 $clients_of_groups = array();

                 foreach( $groups_id as $group_id )
                    $clients_of_groups = array_merge( $clients_of_groups, $wpc_client->cc_get_group_clients_id( $group_id ) );

                 $clients_id = array_unique( array_merge( $clients_id, $clients_of_groups ) ) ;
             } else {
                 $clients_id = array( $data['client_id'] );
             }

            foreach ( $clients_id as $client_id ) {
                $data['client_id'] = $client_id;
                $id = $this->create_inv( $data, $post_status );

                if( !$id )
                    continue;

                if ( 'repeat_inv' == $data['type'] && 'auto_charge' == $data['recurring_type'] ) {
                    update_post_meta( $id, 'wpc_inv_recurring_type', true );

                    if ( isset( $data['billing_every'] ) )
                        update_post_meta( $id, 'wpc_inv_billing_every', $data['billing_every'] );
                    if ( isset( $data['billing_cycle'] ) )
                        update_post_meta( $id, 'wpc_inv_billing_cycle', $data['billing_cycle'] );
                    if ( isset( $data['billing_period'] ) )
                        update_post_meta( $id, 'wpc_inv_billing_period', $data['billing_period'] );
                }

            }

            if ( 'accum_inv' == $data['type'] ) {

                $items = array();
                $new_value = 0;

                update_post_meta( $data['id'], 'wpc_inv_total', $new_value );
                update_post_meta( $data['id'], 'wpc_inv_sub_total', $new_value );
                update_post_meta( $data['id'], 'wpc_inv_total_tax', $new_value );
                update_post_meta( $data['id'], 'wpc_inv_items', $items );

                if ( isset( $data['not_delete_discounts'] ) && !$data['not_delete_discounts'] )
                    update_post_meta( $data['id'], 'wpc_inv_discounts', $items );

                if ( isset( $data['not_delete_taxes'] ) && !$data['not_delete_taxes'] )
                    update_post_meta( $data['id'], 'wpc_inv_taxes', $items );
            }

            return $id;

        }


        function create_inv( $data, $post_status = '' ) {
            global $wpdb, $wpc_client;

            if ( !isset( $data['items'] ) || !is_array( $data['items'] ) || 0 >= count( $data['items'] ) )
                return false;

            $items = $data['items'];

            if( isset( $data['sub_total'] ) ) {
                $sub_total = $data['sub_total'];
            } else {
                $sub_total = 0;
                if( is_array( $items ) ) {
                    foreach( $items as $item ) {
                        if ( isset( $item['price'] ) && isset( $item['quantity'] ) )
                        $sub_total += $item['price'] * $item['quantity'];
                    }
                }
            }

            if ( 0 == $sub_total )
                return false;

            if( isset( $data['total_discount'] ) ) {
                $total_discount = $data['total_discount'];
            } else {
                $total_discount = 0;
                if ( isset( $data['discounts'] ) && is_array( $data['discounts'] ) ) {
                    foreach ( $data['discounts'] as $disc ) {
                        if( isset( $disc['total'] ) &&  0 < (float)$disc['total'] ) {
                            $total_discount += (float)$disc['total'];
                        }
                    }
                }
            }

            if( isset( $data['total_tax'] ) ) {
                $total_tax = $data['total_tax'];
            } else {
                $total_tax = 0;
                if ( isset( $data['taxes'] ) && is_array( $data['taxes'] ) ) {
                    foreach ( $data['taxes'] as $tax ) {
                        if( isset( $tax['total'] ) &&  0 < (float)$tax['total'] ) {
                            $total_tax += (float)$tax['total'];
                        }
                    }
                }
            }

            $data['sub_total'] = $sub_total ;
            $data['total_discount'] = $total_discount ;
            $data['total_tax'] = $total_tax ;
            $data['total'] = ( isset( $data['total'] ) ) ? $data['total'] : $sub_total - $total_discount + $total_tax ;
            if ( !isset( $data['currency'] ) ) {
                $wpc_currency = $wpc_client->cc_get_settings( 'currency' );
                foreach( $wpc_currency as $key => $value ) {
                    if( 1 == $value['default'] ) {
                        $currency = $key;
                        break;
                    }
                }
                $data['currency'] = ( isset( $currency ) ) ? $currency : '' ;
            }

            //get client id
            if ( isset( $data['client_id'] ) )
                $client_id = $data['client_id'] ;
            else
                return false;

            $data['due_date'] = '';
            if ( isset( $data['due_date_number'] ) && (int)$data['due_date_number'] ) {
                $days = (int)$data['due_date_number'];
                $data['due_date'] = strtotime( date("m/d/Y", mktime(0,0,0,date("m"), (date("d") + $days ), date("Y") )) . ' ' . date( 'H:i:s' ) );
            }

            $inv_number = ( isset( $data['inv_number'] ) ) ? $data['inv_number'] : '';

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            if ( isset( $wpc_invoicing['prefix'] ) && '' != $wpc_invoicing['prefix'] )
                $prefix = $wpc_invoicing['prefix'] ;

            $date = date( "Y-m-d H:i:s");

            if ( '' == $post_status ) {
                if ( isset( $data['recurring_type'] ) && 'invoice_draft' == $data['recurring_type'] || isset( $data['accum_type'] ) && 'invoice_draft' == $data['accum_type']  )
                    $post_status = 'draft';
                elseif ( isset( $data['send_email_on_creation'] ) )
                    $post_status = 'sent';
                else
                    $post_status = 'open';
            }

            $new_post = array(
                'post_title'       => ( isset( $data['title'] ) ) ? $data['title'] : '',
                'post_content'     => ( isset( $data['description'] ) ) ? $data['description'] : '',
                'post_status'      => $post_status,
                'post_type'        => 'wpc_invoice',
                'post_date'        => $date,
                //'post_author'      => $all_clients_id[0],
            );

            $id = wp_insert_post( $new_post  );

            update_post_meta( $id, 'wpc_inv_post_type', 'inv' );

            $i = 0;

            //get new number
            if ( '' == $inv_number ) {
                $number = $this->get_next_number();
            } else {
                update_post_meta( $id, 'wpc_inv_custom_number', true );
                do {
                    $i++;
                    $number = $inv_number . '-' . $i;
                    $yes = $wpdb->get_var( $wpdb->prepare( "SELECT pm.meta_value
                        FROM {$wpdb->posts} p
                        INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                        LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_number' )
                        WHERE ( p.post_type = 'wpc_invoice' ) AND pm.meta_value='%s'", $number ) );
                } while( $yes ) ;
            }

            if( isset( $prefix ) )
                update_post_meta( $id, 'wpc_inv_prefix', $prefix );

            update_post_meta( $id, 'wpc_inv_number', $number );


            if ( isset( $data['deposit'] ) ) {
                update_post_meta( $id, 'wpc_inv_deposit', $data['deposit'] );
                if ( isset( $data['min_deposit'] ) && 0 < (float)$data['min_deposit'] ) {
                    update_post_meta( $id, 'wpc_inv_min_deposit',(float)$data['min_deposit'] );
                }
            }


            update_post_meta( $id, 'wpc_inv_items', $data['items'] );

            if ( isset( $data['id'] ) )
                update_post_meta( $id, 'wpc_inv_parrent_id', $data['id'] );

            if ( isset( $data['type'] ) )
                update_post_meta( $id, 'wpc_inv_parent_type', $data['type'] );

            if ( isset( $data['late_fee'] ) )
                update_post_meta( $id, 'wpc_inv_late_fee', $data['late_fee'] );

            if ( isset( $data['discounts'] ) )
                update_post_meta( $id, 'wpc_inv_discounts', $data['discounts'] );

            if ( isset( $data['taxes'] ) )
                update_post_meta( $id, 'wpc_inv_taxes', $data['taxes'] );

            update_post_meta( $id, 'wpc_inv_sub_total', $data['sub_total'] );
            update_post_meta( $id, 'wpc_inv_total_discount', $data['total_discount'] );
            update_post_meta( $id, 'wpc_inv_total_tax', $data['total_tax'] );
            update_post_meta( $id, 'wpc_inv_total', $data['total'] );
            update_post_meta( $id, 'wpc_inv_currency', $data['currency'] );

            if ( isset( $data['note'] ) )
                update_post_meta( $id, 'wpc_inv_note', $data['note'] );

            if ( isset( $data['terms'] ) )
                update_post_meta( $id, 'wpc_inv_terms', $data['terms'] );


            if ( isset( $data['cc_emails'] ) )
                update_post_meta( $id, 'wpc_inv_cc_emails', $data['cc_emails'] );

            if ( isset( $data['custom_fields'] ) )
                update_post_meta( $id, 'wpc_inv_custom_fields', $data['custom_fields'] );

            if ( isset( $data['due_date'] ) )
                update_post_meta( $id, 'wpc_inv_due_date', $data['due_date'] );

            $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wpc_client_objects_assigns SET
                object_type     = 'invoice',
                object_id       = '%d',
                assign_type     = 'client',
                assign_id       = '%d'
                ",
                $id,
                $client_id

            ));

            if ( 'sent' == $post_status )
                $this->send_invoice( $id );


            return $id;

        }


        /**
         * send invoice
         */
        function send_invoice( $id ) {
            global $wpdb, $wpc_client;

            $invoice_ids = ( is_array( $id ) ) ? $id : (array) $id;

            if( !class_exists( 'DOMPDF' ) ) {
                $uploads            = wp_upload_dir();
                $wpc_target_path    = $uploads['basedir'] . '/wpclient/_pdf_temp';

                if ( !is_dir( $wpc_target_path ) ) {
                    //create uploads dir
                    mkdir( $wpc_target_path, 0777 );
                }

                include( $wpc_client->plugin_dir . 'includes/libs/pdf/dompdf_config.inc.php' );
            }

            $uploads        = wp_upload_dir();
            $target_path    = $uploads['basedir'] . "/wpclient/_inv/";

            //send email to client
            foreach ( $invoice_ids as $invoice_id ) {
                //get data
                $inv = $this->get_data( $invoice_id );
                $prefix = ( isset( $inv['prefix'] ) ) ? $inv['prefix'] : '' ;
                if ( 0 < $inv['client_id'] ) {
                    ob_start();

                    $content = $this->invoicing_put_values( $inv );

                    $html_header = '<html><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type"></head><body>';
                    $html_footer = '</body></html>';

                    if ( !ini_get( 'safe_mode' ) ) {
                        $temp_memory_limit          = ini_get( "memory_limit" );
                        $temp_max_execution_time    = ini_get( "max_execution_time" );
                        ini_set( "memory_limit", "999M" );
                        ini_set( "max_execution_time", "999" );
                    }

                    $dompdf = new DOMPDF();
                    $dompdf->load_html( $html_header . $content . $html_footer);
                    $dompdf->set_paper( 'A4' , 'portrait' );
                    $dompdf->render();
                    $pdf_name = $inv['type'] . '_' . $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] ) . '.pdf';
                    $pdf = $dompdf->output();

                    if ( isset( $temp_memory_limit ) && isset( $temp_max_execution_time ) ) {
                        ini_set( "memory_limit", $temp_memory_limit );
                        ini_set( "max_execution_time", $temp_max_execution_time );
                    }

                    if( !is_dir( $target_path ) ) {
                        mkdir( $target_path, 0777);
                    }

                    $htp = fopen( $target_path . $pdf_name, 'w' );

                    fputs( $htp, $pdf );

                    ob_end_clean();

                    $userdata = get_userdata( $inv['client_id'] );

                    $args = array( 'client_id' => $inv['client_id'], 'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] ), );

                    //send email
                    $wpc_client->cc_mail( 'inv_not', $userdata->get( 'user_email' ), $args, 'invoice_notify', array( $target_path . $pdf_name ) );


                    $cc_emails = get_post_meta( $invoice_id, 'wpc_inv_cc_emails', true );

                    if ( is_array( $cc_emails ) && count( $cc_emails ) ) {
                        foreach( $cc_emails as $cc_email ) {
                            if ( is_email( $cc_email ) ) {
                                //send email to CC
                                $wpc_client->cc_mail( 'inv_not', $cc_email, $args, 'invoice_notify', array( $target_path . $pdf_name ) );
                            }
                        }
                    }

                    unlink( $target_path . $pdf_name );
                }
            }

        }


        /**
         * Get next inv\est number
         */
        function get_next_number( $increase = true, $type = 'inv' ) {
            global $wpdb, $wpc_client;

            $type = ( 'est' == $type ) ? $type : 'inv' ;

            $ending = ( 'est' == $type ) ? '_est' : '' ;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            if ( ( 'inv' == $type && ( !isset( $wpc_invoicing['next_number'] ) || '' == $wpc_invoicing['next_number'] ) )  ||
                 ( 'est' == $type && ( !isset( $wpc_invoicing['next_number_est'] ) || '' == $wpc_invoicing['next_number_est'] ) ) ) {
                $next_number = 1;

                $number = $wpdb->get_var( "SELECT MAX(pm.meta_value)
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = '{$type}' )
                    LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_number' ) WHERE ( p.post_type = 'wpc_invoice' ) " );
                if ( $number ) {
                    $next_number = $number;
                }
            } else {
                $next_number = $wpc_invoicing['next_number' . $ending];
            }

            if ( $increase ) {

                $number = $wpdb->get_var( $wpdb->prepare( "SELECT pm.meta_value
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = '{$type}' )
                    LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_number' )
                    WHERE ( p.post_type = 'wpc_invoice' ) AND pm.meta_value='%d'", $next_number ) );
                while( $number )  {
                    $next_number++;
                    $number = $wpdb->get_var( $wpdb->prepare( "SELECT pm.meta_value
                        FROM {$wpdb->posts} p
                        INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = '{$type}' )
                        LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_number' )
                        WHERE ( p.post_type = 'wpc_invoice' ) AND pm.meta_value='%d'", $next_number ) );
                }

                $wpc_invoicing['next_number' . $ending] = $next_number + 1;

            }

            update_option( 'wpc_invoicing', $wpc_invoicing );


            return $next_number;
        }


        /**
         * get Continue link after payment
         **/
        function get_continue_link( $link, $order, $with_text = true ) {
            global $wpdb, $wpc_client;

            $data = json_decode( $order['data'], true );

            //get client INV
            $inv = $this->get_data( $data['invoice_id'] ) ;
            //$prefix = ( isset( $inv['prefix'] ) ) ? $inv['prefix'] : '' ;
            $prefix = '' ; //ivan

            if ( $inv ) {
                //make link
                if ( $wpc_client->permalinks ) {
                    $url = $wpc_client->cc_get_slug( 'invoicing_page_id' ) . $prefix . $inv['id'] . '/';
                } else {
                    $url = add_query_arg( array( 'wpc_page' => 'invoicing', 'wpc_page_value' => $prefix . $inv['id'] ), $wpc_client->cc_get_slug( 'invoicing_page_id', false ) );
                }

                if ( $with_text ) {
                    $link = sprintf( __( 'To continue click <a href="%s">here</a>.', WPC_INV_TEXT_DOMAIN ), $url );
                } else {
                    $link = $url;
                }
            }




            return $link;
        }


        /**
         * get active payment gateways
         **/
        function get_active_payment_gateways( $gateways ) {
            global $wpc_client;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            $gateways =  isset( $wpc_invoicing['gateways'] ) ? $wpc_invoicing['gateways'] : array();

            return $gateways;
        }


        /**
         * order subscription_start
         **/
        function order_subscription_start( $order ) {
            global $wpdb, $wpc_client;

            $data = json_decode( $order['data'], true );

            //get client INV
            $inv = $this->get_data( $data['invoice_id'] ) ;


            if ( $inv ) {
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET
                post_status = 'pending'
                WHERE id = '%d' AND post_status != 'paid'
                ",
                $inv['id']
                ));
            }
        }


        /**
         * order subscription
         **/
        function order_subscription( $order ) {
            global $wpdb, $wpc_client;

            $data = json_decode( $order['data'], true );

            //get client INV

            if ( isset( $data['profile_id'] ) ) {
                $inv = $this->get_data( $data['profile_id'] ) ;

                $orders = get_post_meta( $data['invoice_id'], 'wpc_inv_order_id', true ) ;

                if ( $orders ) {
                    $inv_id = $this->create_inv_from_profile( $data['profile_id'], 'paid', $order['client_id'] ) ;
                } else {
                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET
                        post_status = 'paid'
                        WHERE id = '%d'
                        ",
                        $data['invoice_id']
                    ));

                    $inv_id = $data['invoice_id'] ;
                }

                if ( $inv ) {

                    update_post_meta( $inv_id, 'wpc_inv_order_id', (array) $order['id'] ) ;

                    $this->change_status_expired( $data['profile_id'] );

                    //send email to admin
                    $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

                    //notify selected
                    if ( isset( $wpc_invoicing['notify_payment_made'] ) && 'yes' == $wpc_invoicing['notify_payment_made'] ) {

                        //email to admins
                        $args = array(
                            'role'      => 'wpc_admin',
                            'fields'    => array( 'user_email' )
                        );
                        $admin_emails = get_users( $args );
                        $emails_array = array();
                        if( isset( $admin_emails ) && is_array( $admin_emails ) && 0 < count( $admin_emails ) ) {
                            foreach( $admin_emails as $admin_email ) {
                                 $emails_array[] = $admin_email->user_email;
                            }
                        }

                        $emails_array[] = get_option( 'admin_email' );

                        $inv_number = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'wpc_inv_number'", $inv_id ) ) ;
                        $prefix = ( isset( $inv['prefix'] ) ) ? $inv['prefix'] : '' ;
                        $custom_number = ( isset( $inv['custom_number'] ) ) ? $inv['custom_number'] : '' ;
                        $args = array(
                            'client_id' => $order['client_id'],
                            'inv_number' => $this->get_number_format( $inv_number, $prefix, $custom_number ),
                        );

                        foreach( $emails_array as $to_email ) {
                            $wpc_client->cc_mail( 'admin_notify', $to_email, $args, 'invoice_notify_admin' );
                        }
                    }
                }
            } else {
                //for old recurring invoice, may optimize this code
                $inv = $this->get_data( $data['invoice_id'] ) ;

                if ( $inv ) {
                    $prefix = ( isset( $inv['prefix'] ) ) ? $inv['prefix'] : '' ;

                    $orders = get_post_meta( $inv['id'], 'wpc_inv_order_id', true );
                    if ( is_array( $orders ) ) {
                        $orders[] = $order['id'];
                    } else {
                        $orders = array( $order['id'] );
                    }

                    update_post_meta( $inv['id'], 'wpc_inv_order_id', $orders );

                    if ( isset( $inv['recurring_type'] ) || isset( $inv['accum_type'] ) ) {
                        global $wpc_payments_core;
                        $orders = $wpc_payments_core->get_orders( $inv['order_id'] );

                        if ( isset( $inv['billing_cycle']  ) && 0 == $inv['billing_cycle'] - count( $orders ) ) {
                            $status = 'expired';
                        } else {
                            $status = 'active';
                        }
                    } else {
                        $paid_total = $this->get_amount_paid( $inv['id'] );

                        if ( 0 == $inv['total'] - $paid_total ) {
                            $status = 'paid';
                        } else {
                            $status = 'partial';
                        }
                    }


                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET
                        post_status = '%s'
                        WHERE id = '%d'
                        ",
                        $status,
                        $inv['id']
                    ));

                    //send email to admin
                    $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

                    //notify selected
                    if ( isset( $wpc_invoicing['notify_payment_made'] ) && 'yes' == $wpc_invoicing['notify_payment_made'] ) {

                        //email to admins
                        $args = array(
                            'role'      => 'wpc_admin',
                            'fields'    => array( 'user_email' )
                        );
                        $admin_emails = get_users( $args );
                        $emails_array = array();
                        if( isset( $admin_emails ) && is_array( $admin_emails ) && 0 < count( $admin_emails ) ) {
                            foreach( $admin_emails as $admin_email ) {
                                 $emails_array[] = $admin_email->user_email;
                            }
                        }

                        $emails_array[] = get_option( 'admin_email' );

                        $args = array(
                            'client_id' => $inv['client_id'],
                            'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] ),
                        );

                        foreach( $emails_array as $to_email ) {
                            $wpc_client->cc_mail( 'admin_notify', $to_email, $args, 'invoice_notify_admin' );
                        }
                    }
                }
            }

        }



        /**
         * order paid
         **/
        function order_paid( $order ) {
            global $wpdb, $wpc_client;

            $data = json_decode( $order['data'], true );

            //get client INV
            $inv = $this->get_data( $data['invoice_id'] ) ;

            if ( $inv ) {
                $prefix = ( isset( $inv['prefix'] ) ) ? $inv['prefix'] : '' ;

                $orders = get_post_meta( $inv['id'], 'wpc_inv_order_id', true );
                if ( is_array( $orders ) ) {
                    $orders[] = $order['id'];
                } else {
                    $orders = array( $order['id'] );
                }

                update_post_meta( $inv['id'], 'wpc_inv_order_id', $orders );

                    $paid_total = $this->get_amount_paid( $inv['id'] );

                    if ( 0 == $inv['total'] - $paid_total ) {
                        $status = 'paid';
                    } else {
                        $status = 'partial';
                    }


                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET
                    post_status = '%s'
                    WHERE id = '%d'
                    ",
                    $status,
                    $inv['id']
                ));

                //send email to admin
                $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

                //notify selected
                if ( isset( $wpc_invoicing['notify_payment_made'] ) && 'yes' == $wpc_invoicing['notify_payment_made'] ) {

                    //email to admins
                    $args = array(
                        'role'      => 'wpc_admin',
                        'fields'    => array( 'user_email' )
                    );
                    $admin_emails = get_users( $args );
                    $emails_array = array();
                    if( isset( $admin_emails ) && is_array( $admin_emails ) && 0 < count( $admin_emails ) ) {
                        foreach( $admin_emails as $admin_email ) {
                             $emails_array[] = $admin_email->user_email;
                        }
                    }

                    $emails_array[] = get_option( 'admin_email' );

                    $args = array(
                        'client_id' => $inv['client_id'],
                        'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] ),
                    );

                    foreach( $emails_array as $to_email ) {
                        $wpc_client->cc_mail( 'admin_notify', $to_email, $args, 'invoice_notify_admin' );
                    }
                }

                if ( 'paid' == $status && isset( $inv['send_for_paid'] ) && 1 == $inv['send_for_paid'] ) {
                    $userdata = get_userdata( $inv['client_id'] );

                    $args = array( 'client_id' => $inv['client_id'], 'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] ), );
//send email
                    $wpc_client->cc_mail( 'pay_tha', $userdata->get( 'user_email' ), $args, 'invoice_thank_you' );
                }
            }

        }




    //end class
    }
}

?>

<?php

if ( !class_exists( "WPC_INV_User_Shortcodes" ) ) {

    class WPC_INV_User_Shortcodes extends WPC_INV_Common {

        /**
        * constructor
        **/
        function inv_shortcodes_construct() {

            add_shortcode( 'wpc_client_invoicing', array( &$this, 'shortcode_invoicing' ) );
            add_shortcode( 'wpc_client_inv_invoicing_account_summary', array( &$this, 'shortcode_invoicing_account_summary' ) );
            add_shortcode( 'wpc_client_invoicing_list', array( &$this, 'shortcode_invoicing_list' ) );

        }


        /*
        * Shortcode for Show Account Summary
        */
        function shortcode_invoicing_account_summary( $atts, $contents = null ) {
            global $wpdb, $wpc_client;
            //checking access
            $client_id = $wpc_client->cc_checking_page_access();

            if ( false === $client_id ) {
                return '';
            }

            //display blanck for Staff
            if ( current_user_can( 'wpc_client_staff' ) && !current_user_can( 'wpc_view_invoices' ) && !current_user_can( 'administrator' ) ) {
                return '';
            }

            $data = array();

            $data['show_total_amount'] = ( !isset( $atts['show_total_amount'] ) || 'no' != $atts['show_total_amount'] ) ? true : false ;
            $data['show_total_payments'] = ( !isset( $atts['show_total_payments'] ) || 'no' != $atts['show_total_payments'] ) ? true : false;
            $data['show_balance'] = ( !isset( $atts['show_balance'] ) || 'no' != $atts['show_balance'] ) ? true : false;

            $data['text_total_amount'] = __( 'Total Amount Of Invoices Generated', WPC_INV_TEXT_DOMAIN );
            $data['text_total_payments'] = __( 'Total Payments Received', WPC_INV_TEXT_DOMAIN );
            $data['text_balance'] = __( 'Balance', WPC_INV_TEXT_DOMAIN );

            $data_all_currencies = $wpdb->get_results(
                "SELECT pm2.meta_value as currency, sum(pm.meta_value) as sum_amount, IFNULL( GROUP_CONCAT( pm3.meta_value ), '' ) as group_payments
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'invoice' )
                LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_total' )
                LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_currency' )
                LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_order_id' )
                LEFT JOIN {$wpdb->prefix}wpc_client_payments pay ON ( pm3.meta_value = pay.order_id AND pay.order_status = 'paid' )
                WHERE
                    p.post_type = 'wpc_invoice'
                    AND coa.assign_id = '$client_id'
                    AND `post_status` != 'refunded'
                    AND `post_status` != 'draft'
                    AND `post_status` != 'void'
                GROUP BY pm2.meta_value
                ", ARRAY_A );

            if( $data_all_currencies ) {
                $wpc_currency = $wpc_client->cc_get_settings( 'currency' );

                foreach( $data_all_currencies as $currency ) {
                    $sum_payments = 0;
                    $group_payments = explode( ',', $currency['group_payments'] );
                    if( is_array( $group_payments ) && 0 < count( $group_payments ) ) {
                        $all_payments_temp = $wpdb->get_results( $wpdb->prepare(
                                                "SELECT id, amount
                                                FROM {$wpdb->prefix}wpc_client_payments
                                                WHERE `client_id` ='%d'
                                                    AND `order_status` = 'paid'
                                                    ", $client_id
                                            ), ARRAY_A );
                        $all_payments = array();
                        foreach( $all_payments_temp as $val ) {
                            $all_payments[ $val['id'] ] = $val['amount'];
                        }
                        foreach( $group_payments as $payments ) {
                            $payments = unserialize( $payments );
                            if( is_array( $payments ) ) {
                                foreach( $payments as $payment ) {
                                    $sum_payments += ( isset( $all_payments[ $payment ] ) ) ? $all_payments[ $payment ] : 0 ;
                                }
                            }
                        }
                    }
                    $data['balance'][] = $this->get_currency( ($currency['sum_amount'] - $sum_payments), false, $currency['currency'] ) ;
                    $data['total_amount'][] = $this->get_currency( $currency['sum_amount'], false, $currency['currency'] );
                    $data['total_payments'][] = $this->get_currency( $sum_payments, false, $currency['currency'] );
                }

                /*$use_codes = array();
                foreach( $data_all_currencies as $currency ) {
                    $code = ( isset( $wpc_currency[ $currency['currency'] ] ) ) ? $wpc_currency[ $currency['currency'] ]['code'] : '';
                    if( $code ) {
                        $key = array_search( $code, $use_codes );
                        if( $key ) {
                            $data['total_amount'][ $key ] += $currency['sum'];
                        } else {
                            $use_codes[] = $code;
                            $data['total_amount'][] = $currency['sum'];
                            $data['total_payments'][] = $wpdb->get_var( $wpdb->prepare(
                                                "SELECT IFNULL( sum(amount), 0 )
                                                FROM {$wpdb->prefix}wpc_client_payments
                                                WHERE `client_id` ='%d'
                                                    AND `order_status` = 'paid'
                                                    AND `currency` = '%s'
                                                    ", $client_id, $code
                                            ) );
                            $data['code'][] = $code;
                        }

                    }
                }

                foreach( $data['total_amount'] as $key => $value ) {
                    $data['balance'][ $key ] = $value - $data['total_payments'][ $key ] . ' ' . $data['code'][ $key ];
                    $data['total_payments'][ $key ] .= ' ' . $data['code'][ $key ] ;
                    $data['total_amount'][ $key ] .= ' ' . $data['code'][ $key ] ;
                }*/
            }


            $wpc_templates_shortcodes   = $wpc_client->cc_get_settings( 'templates_shortcodes' );

            if ( isset( $wpc_templates_shortcodes['wpc_client_inv_invoicing_account_summary'] ) && '' != $wpc_templates_shortcodes['wpc_client_inv_invoicing_account_summary'] ) {
                //get custom template
                $template = $wpc_templates_shortcodes['wpc_client_inv_invoicing_account_summary'];
            } else {
                //get default template
                $template = file_get_contents( $this->extension_dir . 'includes/templates/' . 'wpc_client_inv_invoicing_account_summary.tpl' );
                $wpc_templates_shortcodes['wpc_client_inv_invoicing_account_summary'] = $template;
                do_action( 'wp_client_settings_update', $wpc_templates_shortcodes, 'templates_shortcodes' );
            }

            $post_contents = $wpc_client->cc_getTemplateContent( 'wpc_client_inv_invoicing_account_summary', $data, $client_id, $template );

            return do_shortcode( $post_contents );
        }


        /*
        * Shortcode for Show list of Invoices
        */
        function shortcode_invoicing_list( $atts, $contents = null ) {
            global $wpdb, $wpc_client;
            //checking access
            $client_id = $wpc_client->cc_checking_page_access();

            if ( false === $client_id ) {
                return '';
            }

            //display blanck for Staff
            if ( current_user_can( 'wpc_client_staff' ) && !current_user_can( 'wpc_view_invoices' ) && !current_user_can( 'administrator' ) ) {
                return '';
            }

            $data = array();
            $data['invoices'] = array();

            $type = ( isset( $atts['type'] ) && 'estimate' == $atts['type'] ) ? 'est' : 'inv';
            $data['show_pay_now'] = ( isset( $atts['pay_now_links'] ) && 'yes' == $atts['pay_now_links'] && $type == 'inv' && ( !current_user_can( 'wpc_client_staff' ) || ( current_user_can( 'wpc_client_staff' ) && current_user_can( 'wpc_paid_invoices' ) ) ) ) ? true : false;
            $data['show_date'] = ( isset( $atts['show_date'] ) && 'yes' == $atts['show_date'] ) ? true : false;
            $data['show_description'] = ( isset( $atts['show_description'] ) && 'yes' == $atts['show_description'] ) ? true : false;
            $data['show_type_payment'] = ( isset( $atts['show_type_payment'] ) && 'yes' == $atts['show_type_payment'] ) ? true : false;
            $data['show_invoicing_currency'] = ( isset( $atts['show_invoicing_currency'] ) && 'yes' == $atts['show_invoicing_currency'] ) ? true : false;

            $type_long = ( isset( $atts['type'] ) ) ? $atts['type'] : 'invoice';

            $status = " AND p.post_status != 'void'";
            if ( isset( $atts['status'] ) && in_array( strtolower( $atts['status'] ), array( 'paid', 'inprocess', 'sent', 'open', 'draft', 'partial', 'refunded'  ) ) ) {
                if( 'sent' == $atts['status'] )
                    $status = " AND ( p.post_status = 'open' OR p.post_status = 'sent' )";
                else
                    $status = " AND p.post_status = '{$atts['status']}' ";
            }

            //get invoices
            $invoices = $wpdb->get_results( $wpdb->prepare(
                "SELECT p.ID as id,
                    p.post_content as description,
                    p.post_date as date,
                    p.post_modified as date_modified,
                    p.post_status as status,
                    coa.assign_id as client_id,
                    pm1.meta_value as prefix,
                    pm2.meta_value as number,
                    pm3.meta_value as custom_number
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = '%s' )
                LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = '%s' )
                LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_prefix' )
                LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_number' )
                LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_custom_number' )
                WHERE p.post_type='wpc_invoice' AND
                    p.post_status != 'declined' AND
                    coa.assign_id = %d" . $status,
                $type,
                $type_long,
                $client_id
            ), ARRAY_A );

            if ( is_array( $invoices ) && 0 < count( $invoices ) ) {
                foreach( $invoices as $key=>$invoice ) {

                    $prefix = ( isset( $invoice['prefix'] ) ) ? $invoice['prefix'] : '' ;

                    //make link
                    if ( $wpc_client->permalinks ) {
                        $invoices[$key]['invoicing_link'] = $wpc_client->cc_get_slug( 'invoicing_page_id' ) . $invoice['id'] ;
                    } else {
                        $invoices[$key]['invoicing_link'] = add_query_arg( array( 'wpc_page' => 'invoicing', 'wpc_page_value' => $invoice['id'] ), $wpc_client->cc_get_slug( 'invoicing_page_id', false ) );
                    }
                    $invoices[$key]['invoicing_number'] = $this->get_number_format( $invoice['number'], $prefix, $invoice['custom_number'], $type );


                    if( $data['show_date'] ) {
                        $invoices[$key]['date'] = ( "0000-00-00 00:00:00" != $invoice['date_modified'] ) ? $invoice['date_modified'] : $invoice['date'] ;
                    }

                    if( $data['show_description'] ) {
                        if( 19 < strlen( $invoice['description'] ) )
                            $description = substr( $invoice['description'], 0, 16 ) . '...';
                        else
                            $description = $invoice['description'] ;
                        $invoices[$key]['description'] = '<span title="' . $invoice['description'] . '">' . $description . '</span>';
                    }

                    if( $data['show_type_payment'] ) {
                        $recurring_type = get_post_meta( $invoice['id'], 'wpc_inv_recurring_type', true  ) ;
                        $invoices[$key]['type_payment'] = ( $recurring_type ) ? __( 'Recurring Payment', WPC_INV_TEXT_DOMAIN ) : __( 'Deposit Payment', WPC_INV_TEXT_DOMAIN );
                    }

                    $total = get_post_meta( $invoice['id'], 'wpc_inv_total', true );
                    if ( !$total ) {
                        $total = 0;
                    } else {
                        if ( $invoice['status'] != 'paid' && $invoice['status'] != 'pending' && $invoice['status'] != 'refunded'  )
                            $total -= $this->get_amount_paid( $invoice['id'] );
                    }

                    if( $data['show_invoicing_currency'] ) {
                        $selected_curr = get_post_meta( $invoice['id'], 'wpc_inv_currency', true );
                        $invoices[$key]['invoicing_currency'] = $this->get_currency( $total, true, $selected_curr );
                    }

                    if( $data['show_pay_now'] ) {
                        //make link
                        if ( $wpc_client->permalinks ) {
                            $invoicing_payment_link = $wpc_client->cc_get_slug( 'invoicing_page_id' ) . $invoice['id'] . '/?pay_now=1';
                        } else {
                            $invoicing_payment_link = add_query_arg( array( 'wpc_page' => 'invoicing', 'wpc_page_value' => $invoice['id'], 'pay_now' => '1' ), $wpc_client->cc_get_slug( 'invoicing_page_id', false ) );
                        }
             if ( $invoice['status'] == 'paid' ) {
                            $invoices[$key]['inv_pay_now'] = __( 'Paid', WPC_INV_TEXT_DOMAIN ) ;
                        } elseif ( $invoice['status'] == 'pending' ) {
                            $invoices[$key]['inv_pay_now'] = __( 'Pending', WPC_INV_TEXT_DOMAIN ) ;
                        } elseif ( $invoice['status'] == 'refunded' ) {
                            $invoices[$key]['inv_pay_now'] = __( 'Refunded', WPC_INV_TEXT_DOMAIN ) ;
                        } elseif ( 0 > $total ) {
                            $invoices[$key]['inv_pay_now']  = '';
                        } else {
                            $invoices[$key]['inv_pay_now']  = '<a href="' . $invoicing_payment_link . '">' . __('Pay Now', WPC_INV_TEXT_DOMAIN ) . '</a>';
                        }
                    }
                }
                $data['invoices'] = $invoices;
            }

            $wpc_templates_shortcodes   = $wpc_client->cc_get_settings( 'templates_shortcodes' );

            if ( isset( $wpc_templates_shortcodes['wpc_client_inv_invoicing_list'] ) && '' != $wpc_templates_shortcodes['wpc_client_inv_invoicing_list'] ) {
                //get custom template
                $template = $wpc_templates_shortcodes['wpc_client_inv_invoicing_list'];
            } else {
                //get default template
                $template = file_get_contents( $this->extension_dir . 'includes/templates/' . 'wpc_client_inv_invoicing_list.tpl' );
                $wpc_templates_shortcodes['wpc_client_inv_invoicing_list'] = $template;
                do_action( 'wp_client_settings_update', $wpc_templates_shortcodes, 'templates_shortcodes' );
            }

            $post_contents = $wpc_client->cc_getTemplateContent( 'wpc_client_inv_invoicing_list', $data, $client_id, $template );

            return do_shortcode( $post_contents );
        }


        /*
        * Shortcode for Show invoices
        */
        function shortcode_invoicing( $atts, $contents = null ) {
            global $wpc_client;
            $client_id = $wpc_client->cc_checking_page_access();

            if ( false === $client_id ) {
                return '';
            }

            //display blanck for Staff
            if ( current_user_can( 'wpc_client_staff' ) && !current_user_can( 'wpc_view_invoices' ) && !current_user_can( 'administrator' ) ) {
                return '';
            }

            wp_register_style( 'wpc-ui-style', $wpc_client->plugin_url . 'css/jqueryui/jquery-ui-1.10.3.css' );
            wp_enqueue_style( 'wpc-ui-style' );

            wp_enqueue_script( 'jquery-ui-slider' );
            wp_enqueue_style( 'jquery-ui-slider' );
            ob_start();
                include $this->extension_dir . 'includes/user/invoicing_view.php' ;
                $new_content = ob_get_contents();
            ob_end_clean();
            return $new_content;
        }



    //end class
    }
}

?>
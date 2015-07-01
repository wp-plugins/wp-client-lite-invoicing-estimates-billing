<?php

if ( !class_exists( "WPC_INV_Admin_Meta_Boxes" ) ) {

    class WPC_INV_Admin_Meta_Boxes extends WPC_INV_Admin_Common {


        /**
        * Meta constructor
        **/
        function meta_construct() {

            add_action( 'wpc_client_add_meta_boxes', array( &$this, 'meta_init' ) );

        }


        /*
        * Add meta box
        */
        function meta_init() {

            //meta box
            add_meta_box( 'wpc_invoice_publish', __( 'Publish', WPC_INV_TEXT_DOMAIN ),  array( &$this, 'publish' ), 'wp-client_page_wpclients_invoicing', 'side', 'high' );

            add_meta_box( 'wpc_invoice_assign', __( 'User Information', WPC_INV_TEXT_DOMAIN ),  array( &$this, 'user_info' ), 'wp-client_page_wpclients_invoicing', 'side', 'high' );

            if( isset( $_GET['tab'] ) && 'invoice_edit' == $_GET['tab'] && isset( $_GET['id']) && ($orders = get_post_meta( $_GET['id'], 'wpc_inv_order_id', true ) ) && is_array( $orders ) && count( $orders ) ) {
                add_meta_box( 'wpc_invoice_history', __( 'Invoice Status and History', WPC_INV_TEXT_DOMAIN ), array( &$this, 'history' ), 'wp-client_page_wpclients_invoicing', 'normal', 'high' );
            }
            $title_box = __( 'Invoice Items', WPC_INV_TEXT_DOMAIN );
            if ( isset( $_GET['tab'] ) ) {
                switch ( $_GET['tab'] ) {
                    case 'estimate_edit':
                        $title_box = __( 'Estimate Items', WPC_INV_TEXT_DOMAIN ) ;
                        break;
                    case 'repeat_invoice_edit':
                        $title_box = __( 'Recurring Profile Items', WPC_INV_TEXT_DOMAIN ) ;
                        break;
                }
            }

            add_meta_box( 'wpc_invoice_inv_items', $title_box, array( &$this, 'inv_items' ), 'wp-client_page_wpclients_invoicing', 'normal', 'high' );

            add_meta_box( 'wpc_invoice_payment_settings', __( 'Payment Settings', WPC_INV_TEXT_DOMAIN ), array( &$this, 'payment_settings' ), 'wp-client_page_wpclients_invoicing', 'normal', 'high' );

            add_meta_box( 'wpc_invoice_note', __( 'Additional Information', WPC_INV_TEXT_DOMAIN ), array( &$this, 'note' ), 'wp-client_page_wpclients_invoicing', 'normal', 'high' );

        }

        function history( $data ) {
            global $wpc_payments_core;
            if ( isset( $data['data']['id'] ) ) {
                $orders = get_post_meta( $data['data']['id'], 'wpc_inv_order_id', true );
                ?>
                <div id="wpc_inv_history">
                    <table class="table_history" id="table_history">
                <?php

                if ( is_array( $orders ) && $orders ) {
                    global $wpc_client;
                    $selected_curr = isset ( $data['data']['currency'] ) ? $data['data']['currency'] : '';
                    //Set date format
                    if ( get_option( 'date_format' ) ) {
                        $my_time_format = get_option( 'date_format' );
                    } else {
                        $my_time_format = 'm/d/Y';
                    }
                    $date_format = $my_time_format;
                    if ( get_option( 'time_format' ) ) {
                        $my_time_format .= ' ' . get_option( 'time_format' );
                    } else {
                        $my_time_format .= ' g:i:s A';
                    }
                    $orders = $wpc_payments_core->get_orders( $orders );
                    foreach( $orders as $order ) {
                        ?>
                        <tr>
                            <td class="time_history"><?php echo $wpc_client->cc_date_timezone( $my_time_format, $order['time_paid'] ) ?></td>
                            <td class="text_history"><?php printf( __( '%s paid', WPC_INV_TEXT_DOMAIN ), $this->get_currency( $order['amount'], true, $selected_curr ) ) ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                    </table>
                </div>
                <?php
            }
        }


        //show metabox
        function publish( $data ) {
            global $current_screen;
            $screen_id = $current_screen->id;
            wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
            ?>
            <script type="text/javascript">

            var site_url = '<?php echo site_url(); ?>';

            function not_d(e) {
                if (!(e.which==8 ||e.which==0 ||(e.which>47 && e.which<58))) return false;
            }

            function not_price(e) {
                if ( e.which == 44 ) {
                    this.value += '.';
                    return false;
                }
                if (!(e.which==8 || e.which==46 ||e.which==0 ||(e.which>47 && e.which<58))) return false;
            }

            function not_due_date(e) {
                if ( !(e.which==8 ||e.which==0 ||(e.which>46 && e.which<58) )) return false;
            }


            jQuery(document).ready( function() {

                jQuery( '#wpc_billing_period' ).keypress( not_d );
                jQuery( '#wpc_billing_manually_period' ).keypress( not_d );
                jQuery( '#wpc_billing_cycle' ).keypress( not_d );
                jQuery( '#wpc_billing_manually_cycle' ).keypress( not_d );

                //jQuery( '#wpc_data_inv_number' ).keypress( not_d );
                jQuery( '#wpc_data_due_date' ).keypress( not_d );

                jQuery( '#wpc_data_late_fee' ).keypress( not_price );
                jQuery( '#wpc_minimum_deposit' ).keypress( not_price );

                jQuery('#wpc_billing_period_select').change( function() {
                    if ( 'month' == jQuery('#wpc_billing_period_select').val() ) {
                        jQuery('#block_last_day_month').css('display', 'block');
                    } else {
                        if ( jQuery('#wpc_last_day_month').prop("checked") )
                            jQuery('#wpc_last_day_month').click();
                        jQuery('#block_last_day_month').css('display', 'none');
                    }
                });

                jQuery('#wpc_last_day_month').change( function () {
                    if( jQuery(this).prop("checked") ) {
                        jQuery('#wpc_data_from_date').attr('disabled', true ) ;
                    }else{
                        jQuery('#wpc_data_from_date').removeAttr( 'disabled' ) ;
                    }
                });

                postboxes.add_postbox_toggles('<?php echo $screen_id; ?>');

                jQuery('#wpc_data_currency').change( function() {
                    jQuery.ajax({
                        type: 'POST',
                        url: site_url + '/wp-admin/admin-ajax.php',
                        data: 'action=inv_change_currency&selected_curr=' + jQuery(this).val(),
                        dataType: "json",
                        success: function( data ){
                            jQuery( '#wpc_data_currency_symbol' ).val( data.symbol );
                            jQuery( '#wpc_data_currency_align' ).val( data.align );
                            jQuery( '.amount' ).each( function() {
                                var number = jQuery( this ).html();
                                if( 'left' == data.align )
                                    jQuery( this ).parent().html( data.symbol + '<span class="amount">' + number + '</span>' );
                                else if( 'right' == data.align )
                                    jQuery( this ).parent().html( '<span class="amount">' + number + '</span>' + data.symbol );
                            });
                        }
                     });
                });

                jQuery('#wpc_deposit').change( function () {
                    if( jQuery(this).attr("checked") ) {
                        jQuery('#wpc_block_min').css('display', 'inline') ;
                        jQuery('#wpc_deposit').val('true') ;
                        jQuery('#label_rec').css('display', 'none') ;
                    }else{
                        jQuery('#wpc_block_min').css('display', 'none') ;
                        jQuery('#wpc_deposit').val('false') ;
                        jQuery('#label_rec').css('display', 'inline') ;
                    }
                });

              });
            </script>

            <?php
                global $wpc_client;
                $tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : '';
                $est = ( 'estimate_edit' == $tab ) ? true : false;
                $readonly = ( !$data['option']['can_edit'] ) ? ' disabled' : '';


                $val_deposit = 'false';
                $min_deposit = 0 ;
                $checked_deposit = '';
                $display_deposit_settings = 'style = " display: none; "';
                if ( isset( $data['data']['deposit'] ) ) {
                    $checked_deposit = 'checked';
                    $val_deposit = 'true';
                    $display_deposit_settings = '';
                    $min_deposit = ( isset( $data['data']['min_deposit'] ) ) ? $data['data']['min_deposit'] : 0 ;
                }

               ?>

            <div class="misc-pub-section">
                <label for="wpc_deposit" id="label_deposit">
                    <input id="wpc_deposit" type="checkbox" value="<?php echo $val_deposit ?>" name="wpc_data[deposit]" <?php echo $readonly ?> <?php echo $checked_deposit ?> />
                    <?php _e( 'Allow Partial Payment', WPC_INV_TEXT_DOMAIN ) ?>
                </label>
                <br />
                <label class="margin_desc" id="wpc_block_min" for="wpc_minimum_deposit" <?php echo  $display_deposit_settings ?> >
                    <?php _e( 'Minimum Payment', WPC_INV_TEXT_DOMAIN ) ?>
                    <input id="wpc_minimum_deposit" type="text" style="width: 75px" name="wpc_data[min_deposit]" value=<?php echo '"' . $min_deposit . '"' .  $readonly ?> />
                </label>
            </div>


            <div class="misc-pub-section">
                <?php
                $name_page = ( $est ) ? __( 'Estimate Number: ', WPC_INV_TEXT_DOMAIN ) : __( 'Invoice Number: ', WPC_INV_TEXT_DOMAIN );
                $type = ( $est ) ? 'est' : 'inv' ;

                if ( isset( $_GET['id'] ) && '' != $_GET['id'] && ( 'invoice_edit' == $_GET['tab'] || 'estimate_edit' == $_GET['tab'] ) ) {
                    $prefix = ( isset( $data['data']['prefix'] ) ) ? $data['data']['prefix'] : '' ;
                    $custom_number = ( isset( $data['data']['custom_number'] ) ) ? $data['data']['custom_number'] : '' ;
                    echo '<label for="invoice_id">' . $name_page . '</label><span id="invoice_id">' . $this->get_number_format( $data['data']['number'], $prefix, $custom_number, $type ) . '</span>';
                } else {
                    echo '<label for="wpc_data_inv_number">' . $name_page . '</label><input type="text" style="width: 75px" name="wpc_data[inv_number]" id="wpc_data_inv_number" value="' . ( isset( $data['data']['inv_number'] ) ? $data['data']['inv_number'] : '' ) . '" /><br /><p class="description margin_desc">' . __( 'Leave blank for Invoice # to be auto-generated in sequence', WPC_INV_TEXT_DOMAIN ) . '</p>';
                } ?>
            </div>

            <div class="misc-pub-section">
            <label for="wpc_data_due_date">
                <?php
                    echo __( 'Due Date', WPC_INV_TEXT_DOMAIN );
                    echo $wpc_client->tooltip( __( 'Due Date is required to be set if setting a Late Fee', WPC_INV_TEXT_DOMAIN ) );
                ?>
            </label>

            <input type="text" style="width: 100px" id="wpc_data_due_date" name="wpc_data[due_date]" value="<?php echo ( isset( $data['data']['due_date'] ) ? $data['data']['due_date'] : '' ) ?>" <?php echo $readonly ?> />

            <?php
            if ( $data['option']['can_edit'] ) { ?>
                <div>
                    <a href="javascript:;" class="wpc_set_due_date" rel="<?php echo date( 'm/d/Y', ( time() + 3600*24*15 ) ) ?>">15&nbsp;</a>
                    |
                    <a href="javascript:;" class="wpc_set_due_date" rel="<?php echo date( 'm/d/Y', ( time() + 3600*24*30 ) ) ?>">30&nbsp;</a>
                    |
                    <a href="javascript:;" class="wpc_set_due_date" rel="<?php echo date( 'm/d/Y', ( time() + 3600*24*45 ) ) ?>">45&nbsp;</a>
                    |
                    <a href="javascript:;" class="wpc_set_due_date" rel="<?php echo date( 'm/d/Y', ( time() + 3600*24*60 ) ) ?>">60&nbsp;</a>
                    |
                    <a href="javascript:;" class="wpc_set_due_date" rel="<?php echo date( 'm/d/Y', ( time() + 3600*24*90 ) ) ?>">90&nbsp;<?php _e( 'Days', WPC_INV_TEXT_DOMAIN ) ?></a>
                </div>
            <?php
            }
            ?>


             </div>

             <?php if ( !$est ) { ?>
                 <div class="misc-pub-section">
                    <label>
                        <?php _e( 'Late Fee', WPC_INV_TEXT_DOMAIN ) ?>

                        <input type="text" style="width: 75px" name="wpc_data[late_fee]" id="wpc_data_late_fee" value="<?php echo ( isset( $data['data']['late_fee'] ) ) ? $data['data']['late_fee'] : '0' ?>" <?php echo $readonly ?> />
                    </label>
                 </div>
             <?php } ?>


             <div class="misc-pub-section">
                <label>
                    <?php
                        $checked = '';
                        /*var_dump( $data['data']['send_for_paid'] );
                        exit;*/
                        if ( isset( $data['data']['send_for_paid'] ) ) {
                            if ( $data['data']['send_for_paid'] )
                                $checked = ' checked' ;
                        } else {
                            $wpc_invoicing  = $wpc_client->cc_get_settings( 'invoicing' );

                            if ( !isset( $_GET['id'] ) && isset( $wpc_invoicing['send_for_paid'] ) && 'yes' == $wpc_invoicing['send_for_paid'] )
                                $checked = ' checked';
                        }
                    ?>
                    <input type="checkbox" name="wpc_data[send_for_paid]" id="wpc_data_send_for_paid" value="1" <?php echo $readonly . $checked ?> />
                    <?php printf( __( 'Send Email for %s After Paid Invoice', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s'] ) ?>
                </label>
             </div>

             <hr />

                <div class="for_buttom">

                    <input type="button" name="save_open" id="save_open" class="button-primary" value="<?php _e( 'Save as Open', WPC_INV_TEXT_DOMAIN ) ?>" />
                    <label><input id="send_email" type="checkbox" name="wpc_data[send_email]" value="1" /><?php _e( 'Send Email', WPC_INV_TEXT_DOMAIN ) ?></label>
                    <?php echo $wpc_client->tooltip( sprintf( __( 'Send Email with PDF file to %s', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s'] ) ) ?>

                    <div class="wpc_clear"></div>

                    <?php if ( !isset( $_GET['id'] ) || 'draft' == $data['data']['status'] ) { ?>
                       <input type="button" name="save_draft" id="save_draft" class="button" value="<?php _e( 'Save as Draft', WPC_INV_TEXT_DOMAIN ) ?>" />
                       <div class="wpc_clear"></div>
                    <?php } ?>

                    <input type="button" style="vertical-align: middle;" name="data_cancel" id="data_cancel" class="button" value="<?php _e( 'Cancel', WPC_INV_TEXT_DOMAIN ) ?>" />

                    <?php
                    if ( isset( $_GET['id'] ) ) {
                       if ( current_user_can( 'wpc_delete_invoices' ) ) {
                           echo '<span class="perm_del">';
                           echo '<a onclick=\'return confirm("' . __( 'Are you sure to delete this Invoice?', WPC_INV_TEXT_DOMAIN ) . '");\' href="admin.php?page=wpclients_invoicing&action=delete&id=' . $data['data']['id'] . '&_wpnonce=' . wp_create_nonce( 'wpc_invoice_delete' . $data['data']['id'] . get_current_user_id() ) .'">' . __( 'Delete&nbsp;Permanently', WPC_INV_TEXT_DOMAIN ) . '</a>';
                           echo '</span><br />';
                       }
                    }
                    ?>

                   <div class="wpc_clear"></div>

                    <?php if ( isset( $_GET['id']) ) { ?>
                        <a href="admin.php?page=wpclients_invoicing&wpc_action=download_pdf&id=<?php echo $data['data']['id'] ?>"><input type="button" name="" id="" class="button" value="<?php _e( 'Download PDF', WPC_INV_TEXT_DOMAIN ) ?>" /></a>
                    <?php } ?>



                   </div>
            <?php
        }


        //show metabox
        function user_info( $data ) {
            global $wpc_client;
            $readonly = ( !$data['option']['can_edit'] ) ? ' disabled' : '';

            $current_page = ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) ) ? $_GET['page'] . $_GET['tab'] : '';
            $wpc_client->acc_get_assign_clients_popup( $current_page );

            if( isset( $_GET['id'] ) && 'repeat_invoice_edit' != $_GET['tab'] ) {
                $user_info = get_userdata( $data['data']['client_id'] );
                ?>
                <table class="user_information" cellpadding="0" cellspacing="0">
                    <tr><td></td><td><?php _e( 'Username: ', WPC_INV_TEXT_DOMAIN ) ?></td><td><?php echo $user_info->data->user_login ?></td></tr>
                    <tr><td></td><td><?php _e( 'Contact Name: ', WPC_INV_TEXT_DOMAIN ) ?></td><td><?php echo $user_info->data->display_name ?></td></tr>
                    <tr><td></td><td><?php _e( 'Business Name: ', WPC_INV_TEXT_DOMAIN ) ?></td><td><?php echo get_user_meta( $data['data']['client_id'], 'wpc_cl_business_name', true ) ?></td></tr>
                    <tr><td></td><td><?php _e( 'Email: ', WPC_INV_TEXT_DOMAIN ) ?></td><td><?php echo $user_info->data->user_email ?></td></tr>
                <?php
            } else {

                    ?>

                    <div class="misc-pub-section">
                        <?php
                            $link_array = array(
                                'title'         => sprintf( __( 'assign %s', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['p'] ),
                                'text'          => sprintf( __( 'Assign To %s', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['p'] ),
                                'data-marks'    => 'radio'
                            );
                            $input_array = array(
                                'name'  => 'wpc_data[clients_id]',
                                'id'    => 'wpc_clients',
                                'value' => ( isset( $data['data']['clients_id'] ) ) ? $data['data']['clients_id'] : ''
                            );
                            $additional_array = array(
                                'counter_value' => ( isset( $data['data']['clients_id'] ) && '' != $data['data']['clients_id'] ) ? count( explode( ',', $data['data']['clients_id'] ) ) : 0
                            );
                            $wpc_client->acc_assign_popup('client', isset( $current_page ) ? $current_page : '', $link_array, $input_array, $additional_array );
                        ?>
                    </div>

                    <div class="misc-pub-section" >
                        <span class="wpc_pro_menu_grey" style="font-size: 13px;">
                        <?php
                            printf( __( 'Assign To %s', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s'] . ' ' . $wpc_client->custom_titles['circle']['p'] );
                        ?>(0)
                        </span>
                        <span><b>(Pro)</b></span>
                    </div>

                <table class="user_information" cellpadding="0" cellspacing="0">
                <?php
            }
            if ( isset( $data['data']['cc_emails'] ) ) {
                if ( !is_array( $data['data']['cc_emails'] ) && '' != $data['data']['cc_emails'] )
                    $data['data']['cc_emails'] = unserialize( $data['data']['cc_emails'] );
                if ( is_array( $data['data']['cc_emails'] ) && count( $data['data']['cc_emails'] ) ) {
                    foreach( $data['data']['cc_emails'] as $cc_email ) {
                        if ( !empty( $cc_email ) ) {
                    ?>
                        <tr><td class="email_del" width="17"><span></span></td><td><?php _e( 'CC Email: ', WPC_INV_TEXT_DOMAIN ) ?></td><td><input type="text" name="wpc_data[cc_emails][]" value="<?php echo $cc_email ?>" style="width: 130px" /></td></tr>
                    <?php
                        }
                    }
                }
            }

            if ( !$readonly ) {
                ?>
                <tr><td></td><td colspan="2"><a id="wpc_add_cc_email" href="javascript: void(0);">+<?php _e( 'Add CC Email', WPC_INV_TEXT_DOMAIN ) ?></a></td></tr>
                <?php
            }
            ?>
                </table>

            <script type="text/javascript">

                jQuery(document).ready( function() {

                    jQuery('#wpc_add_cc_email').click( function () {
//                        jQuery( this ).parents( 'tr' ).remove() ;
                        jQuery( '<tr><td class="email_del" width="17"><span></span></td><td><?php _e( 'CC Email: ', WPC_INV_TEXT_DOMAIN ) ?></td><td><input type="text" name="wpc_data[cc_emails][]" value="" style="width: 130px" /></td></tr>' ).insertBefore( jQuery( this ).parents( 'tr' ) );
                    });

                });

                jQuery( '#wpc_invoice_assign' ).on( 'click', '.email_del', function() {
                    jQuery(this).parent().remove();
                });

            </script>

            <?php

        }


        function note( $data ) {
            ?>
            <div>
                <label>
                    <?php _e( 'Terms & Conditions:', WPC_INV_TEXT_DOMAIN ) ?>
                    <br />
                    <textarea name="wpc_data[terms]" rows="5" id="wpc_data_tc" <?php echo ( !$data['option']['can_edit'] ) ? 'readonly' : '' ?> ><?php echo ( isset( $data['data']['terms'] ) ) ? $data['data']['terms'] : '' ?></textarea>
                </label>
            </div>
            <br />
            <div>
                <label>
                    <?php _e( 'Note to Customer:', WPC_INV_TEXT_DOMAIN ) ?>
                    <br />
                    <textarea name="wpc_data[note]" rows="5" id="wpc_data_note" <?php echo ( !$data['option']['can_edit'] ) ? 'readonly' : '' ?> ><?php echo ( isset( $data['data']['note'] ) ) ? $data['data']['note'] : '' ?></textarea>
                </label>
            </div>


       <?php  }

        function payment_settings( $data ) {
            global $wpc_client;

            $ver = get_option( 'wp_client_ver' );

            if ( version_compare( $ver, '3.5.0' ) ) {
                $wpc_currency = $wpc_client->cc_get_settings( 'currency' );
                ?>
                <div>
                    <label for="wpc_data_currency"><?php _e( 'Currency', WPC_INV_TEXT_DOMAIN ) ?></label>
                    <select id="wpc_data_currency" name="wpc_data[currency]" <?php echo ( !$data['option']['can_edit'] ) ? 'disabled' : '' ?>>
                        <?php
                            if ( isset( $data['data']['currency'] ) ) {
                                foreach( $wpc_currency as $key => $curr ) {
                                    echo '<option value="' . $key . '" ' . ( ( $key == $data['data']['currency'] ) ? 'selected' : '' ) . '>' . $curr['code'] . ' (' . $curr['symbol'] . ')' . ( ( '' != $curr['title'] ) ? ' - ' . $curr['title'] : ''  ) . '</option>';
                                }
                            } else {
                                foreach( $wpc_currency as $key => $curr ) {
                                    echo '<option value="' . $key . '" ' . ( ( 1 == $curr['default'] ) ? 'selected' : '' ) . '>' . $curr['code'] . ' (' . $curr['symbol'] . ')' . ( ( '' != $curr['title'] ) ? ' - ' . $curr['title'] : ''  ) . '</option>';
                                }
                            }
                        ?>
                    </select>
                    <span class="description margin_desc">
                        <?php
                            $more_cur = '<a href="'. get_admin_url().'admin.php?page=wpclients_settings&tab=general" target="_blank">' . __( 'General Settings', WPC_INV_TEXT_DOMAIN ) . '</a>';
                            printf( __( 'You can add more currencies for use in Invoices from %s page.', WPC_INV_TEXT_DOMAIN ), $more_cur );
                        ?>
                    </span>
                    <input id="wpc_data_currency_symbol" type="hidden" name="wpc_data[currency_symbol]" value="<?php echo isset( $data['data']['currency_symbol'] ) ? $data['data']['currency_symbol'] : '' ?>" />
                    <input id="wpc_data_currency_align" type="hidden" name="wpc_data[currency_align]" value="<?php echo isset( $data['data']['currency_align'] ) ? $data['data']['currency_align'] : '' ?>" />
                </div>
       <?php
            }
        }

        //show metabox
        function inv_items( $data ) {
            global $wpc_client;

            $wpc_invoicing          = $wpc_client->cc_get_settings( 'invoicing' );
            $rate_capacity          = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;
            $thousands_separator    = ( isset( $wpc_invoicing['thousands_separator'] ) && !empty( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '';

             ?>
            <script type="text/javascript">

              jQuery(document).ready( function() {

                var rate_capacity = '<?php echo $rate_capacity; ?>';
                var thousands_separator = '<?php echo $thousands_separator; ?>';
                var price_null = '<?php echo number_format( 0, $rate_capacity, '.', $thousands_separator ); ?>';
                var num_items = jQuery( '.row_del' ).length;
                num_items = num_items - 1;


                function addSeparatorsNF( nStr, inD, outD, sep ) {

                    if( sep == '' ) {
                        return nStr;
                    }

                    nStr += '';
                    var dpos = nStr.indexOf( inD );
                    var nStrEnd = '';
                    if (dpos != -1) {
                        nStrEnd = outD + nStr.substring(dpos + 1, nStr.length);
                        nStr = nStr.substring(0, dpos);
                    }
                    var rgx = /(\d+)(\d{3})/;
                    while (rgx.test(nStr)) {
                        nStr = nStr.replace(rgx, '$1' + sep + '$2');
                    }
                    return nStr + nStrEnd;
                }

                function not_price_minus(e) {
                    if ( e.which == 44 ) {
                        this.value += '.';
                        return false;
                    }
                    if (!(e.which==45 || e.which==8 || e.which==46 ||e.which==0 ||(e.which>47 && e.which<58))) return false;
                }

                 function recalculation() {
                    var number;
                    var total;
                    var type;

                    number = jQuery(this).attr( 'data-number' );
                    if ( jQuery(this).hasClass('discount_rate') || jQuery(this).hasClass('discount_type') )
                        type = 'disc';
                    else if ( jQuery(this).hasClass('tax_rate') || jQuery(this).hasClass('tax_type') )
                        type = 'tax';
                    else type = 'item';

                    if ( 'item' == type ) {

                        total = parseFloat( jQuery( '#item_quantity' + number ).val(), 10) * parseFloat( jQuery( '#item_price' + number ).val(), 10);

                    } else if ( 'disc' == type ) {

                        var count_total = 0;
                        jQuery( '.item_total' ).each( function() {
                            count_total = count_total + parseFloat( jQuery(this).attr( 'data-total' ), 10 );
                        });

                        if ( 'amount' == jQuery( '#discount_type' + number ).val() )
                            total = parseFloat( jQuery( '#discount_rate' + number ).val(), 10);
                        else if ( 'percent' == jQuery( '#discount_type' + number ).val() )
                            total = parseFloat( jQuery( '#discount_rate' + number ).val(), 10) * count_total / 100;

                    } else if ( 'tax' == type ) {

                        var count_total = 0;
                        var count_discount = 0;

                        jQuery( '.item_total' ).each( function() {
                            count_total = count_total + parseFloat( jQuery(this).attr( 'data-total' ), 10 );
                        });

                        jQuery( '.discount_total' ).each( function() {
                            if ( 'percent' == jQuery('#discount_type' + jQuery(this).attr('data-number') ).val() ) {
                                var discont;
                                discont = count_total * parseFloat( jQuery( '#discount_rate' + jQuery(this).attr('data-number') ).val(), 10 ) / 100 ;
                                jQuery(this).html( addSeparatorsNF( discont.toFixed( rate_capacity ), '.', '.', thousands_separator ) );
                            }
                            count_discount = count_discount + parseFloat( jQuery(this).attr( 'data-total' ), 10 );
                        });

                        if ( 'before' == jQuery( '#tax_type' + number ).val() )
                            total = parseFloat( jQuery( '#tax_rate' + number ).val(), 10) * count_total / 100;
                        if ( 'after' == jQuery( '#tax_type' + number ).val() )
                            total = parseFloat( jQuery( '#tax_rate' + number ).val(), 10) * ( count_total - count_discount ) / 100;

                    }

                    total = total.toFixed( rate_capacity );
                    if( isNaN( total ) )
                        total = 0;

                    var html_total = addSeparatorsNF( total, '.', '.', thousands_separator );

                    if ( 'item' != type ) {
                        jQuery( '#hidden_total' + number ).val( total );
                    }
                    jQuery( '#item_total' + number ).attr( 'data-total', total );
                    jQuery( '#item_total' + number ).html( html_total );
                    all_total();

                    if ( 'item' == type ) {
                        jQuery( ".tax_rate" ).each(function(){

                            jQuery(this).change();

                        });

                        jQuery( ".discount_rate" ).each(function(){

                            jQuery(this).change();

                        });
                    }
                }

                function all_total() {
                    var count_total = 0;
                    var count_discount = 0;
                    var count_tax = 0;
                    var count_late_fee = 0;

                    jQuery( '.item_total' ).each( function() {
                        count_total = count_total + parseFloat( jQuery(this).attr( 'data-total' ), 10 );
                    });
                    if( isNaN( count_total ) ) count_total = 0;
                    jQuery( '#total_all_items .amount' ).html( addSeparatorsNF( count_total.toFixed( rate_capacity ), '.', '.', thousands_separator ) );

                    jQuery( '.discount_total' ).each( function() {
                        if ( 'percent' == jQuery('#discount_type' + jQuery(this).attr('data-number') ).val() ) {
                            var discont;
                            discont = count_total * parseFloat( jQuery( '#discount_rate' + jQuery(this).attr('data-number') ).val(), 10 ) / 100 ;
                            if( isNaN( discont ) ) discont = 0;
                            jQuery(this).html( addSeparatorsNF( discont.toFixed( rate_capacity ), '.', '.', thousands_separator ) );
                        }
                        count_discount = count_discount + parseFloat( jQuery(this).attr( 'data-total' ), 10 );
                    });

                    if( isNaN( count_discount ) ) count_discount = 0;

                    jQuery( '.tax_total' ).each( function() {

                        var tax;
                        var this_number = jQuery(this).attr('data-number');
                        if ( 'before' == jQuery('#tax_type' + this_number ).val() ) {

                            tax = count_total.toFixed( rate_capacity ) * parseFloat( jQuery( '#tax_rate' + jQuery(this).attr('data-number') ).val(), 10 ) / 100 ;

                        } else if ( 'after' == jQuery('#tax_type' + jQuery(this).attr('data-number') ).val() ) {

                            tax = ( count_total.toFixed( rate_capacity ) - count_discount.toFixed( rate_capacity ) ) * parseFloat( jQuery( '#tax_rate' + jQuery(this).attr('data-number') ).val(), 10 ) / 100 ;

                        }
                        if( isNaN( tax ) ) tax = 0;
                        jQuery(this).html( addSeparatorsNF( tax.toFixed( rate_capacity ), '.', '.', thousands_separator ) );
                        jQuery( '#hidden_total' + this_number ).val( tax.toFixed( rate_capacity ) );

                        count_tax = count_tax + parseFloat( jQuery(this).attr('data-total'), 10 );
                    });

                    count_late_fee = parseFloat( jQuery( '#late_fee .amount' ).html(), 10 ) || 0
                    if( isNaN( count_tax ) ) count_tax = 0;
                    count_total = count_total - count_discount + count_tax + count_late_fee;
                    if( isNaN( count_total ) ) count_total = 0;

                    if ( jQuery('.item_total')[1] || jQuery('.discount_total')[0] || jQuery('.tax_total')[0] ){ // use [1] because one tr always isset
                        jQuery('#added_items').css('display', 'block');
                    } else {
                        jQuery('#added_items').css('display', 'none');
                    }

                    jQuery( '#total_discount .amount' ).html( addSeparatorsNF( count_discount.toFixed( rate_capacity ), '.', '.', thousands_separator ) );
                    jQuery( '#total_tax .amount' ).html( addSeparatorsNF( count_tax.toFixed( rate_capacity ), '.', '.', thousands_separator ) );
                    jQuery( '#total_all .amount' ).html( addSeparatorsNF( count_total.toFixed( rate_capacity ), '.', '.', thousands_separator ) );
                    jQuery( '#total_all .real_amount' ).html( count_total.toFixed( rate_capacity ) );
                }

                all_total();


                function add_items( item_name, item_description, item_rate ) {
                    num_items = num_items + 1;
                    var nice_item_rate = addSeparatorsNF( item_rate, '.', '.', thousands_separator );


                    var html = jQuery( '#table_items tbody tr' ).first().clone().wrap('<p>').css( 'display', 'table-row' ).parent().html().replace( /\{num_items\}/g , num_items );

                    jQuery( '#added_items #table_items tbody' ).append( html );

                    jQuery( '#item_total' + num_items  ).data( 'total', item_rate ).val( nice_item_rate );
                    jQuery( '#item_price' + num_items  ).val( item_rate );
                    jQuery( '#item_description' + num_items  ).val( item_description );
                    jQuery( '#item_name' + num_items  ).val( item_name );
                    jQuery( '#item_quantity' + num_items  ).addClass( 'item_qty' );

                    jQuery( '#item_quantity' + num_items ).spinner({
                        min: 1,
                        numberFormat: "n",
                        stop: recalculation,
                    });

                    jQuery( '#item_price' + num_items ).trigger( 'change' );

                    jQuery.fancybox.close();

                    //all_total();

                }


                function add_taxes( name, description, rate) {
                    num_items = num_items + 1;

                    var html =
                    '<tr valign="top">' +
                        '<td class="row_del"><span></span></td>' +
                        '<td width="160px">' +
                            '<input type="text" size="15" class="tax_name" id="tax_name' + num_items + '" name="wpc_data[taxes][' + num_items + '][name]" value="' + name + '" />' +
                        '</td>' +
                        '<td>' +
                            '<textarea maxlength="300" class="description_tax" id="tax_description' + num_items + '" name="wpc_data[taxes][' + num_items + '][description]">' + description + '</textarea>' +
                        '</td>' +
                        '<td width="145px">' +
                            '<select id="tax_type' + num_items  + '" class="tax_type" data-number="' + num_items  + '" name="wpc_data[taxes][' + num_items  + '][type]" >' +
                                '<option value="before" selected="selected">' + '<?php _e( 'Before Discount', WPC_INV_TEXT_DOMAIN ) ?>' + '</option>' +
                                '<option value="after">' + '<?php _e( 'After Discount', WPC_INV_TEXT_DOMAIN ) ?>' + '</option>' +
                            '</select>' +
                        '</td>' +
                        '<td width="60px" class="add_procent">' +
                            '<input type="text" class="tax_rate" data-number="' + num_items + '" id="tax_rate' + num_items + '" name="wpc_data[taxes][' + num_items + '][rate]" size="4" value="' + rate + '" />%' +
                        '</td>' +
                        '<td width="50px" align="right">' +
                            '&nbsp;<span class="tax_total" data-total="' + rate + '" data-number="' + num_items + '" id="item_total' + num_items + '"></span>' +
                            '<input type="hidden" name="wpc_data[taxes][' + num_items + '][total]" value="" id="hidden_total' + num_items + '"  />' +
                        '</td>' +
                    '</tr>';

                    jQuery( '#added_items #table_taxes tbody' ).append( html );

                    jQuery.fancybox.close();
                }


                jQuery( '.item_qty' ).each( function() {
                    jQuery(this).spinner({
                        min: 1,
                        numberFormat: "n",
                        stop: recalculation
                    });
                });

                //show edit item form
                jQuery( '.various' ).live( 'click', function(e) {
                    var limit = 300;
                    var id = jQuery(this).attr('rel');

                    jQuery( '#item_id' ).val( '' );
                    jQuery( '#item_name' ).val( '' );
                    jQuery( '#item_description' ).val( '' );
                    jQuery( '#item_rate' ).val( '' );
                    jQuery( '#count_chars' ).html( limit );




                    if ( '' != id ) {

                        var item_name = jQuery( '#item_name_block_' + id ).html();
                        item_name = item_name.replace( /(^\s+)|(\s+$)/g, "" );

                        var item_description = jQuery( '#item_description_block_' + id ).html();

                        //check if there are more characters then allowed
                        if ( item_description.length > limit ){
                            //and if there are use substr to get the text before the limit
                            item_description = item_description.substr( 0, limit );

                        }
                        jQuery( '#count_chars' ).html( ( limit - item_description.length ) );

                        var item_rate = jQuery( '#item_rate2_block_' + id ).html();
                        item_rate = item_rate.replace( /(^\s+)|(\s+$)/g, "" );

                        jQuery( '#item_id' ).val( id );
                        jQuery( '#item_name' ).val( item_name );
                        jQuery( '#item_description' ).val( item_description );
                        jQuery( '#item_rate' ).val( item_rate );
                    }

                     jQuery.fancybox({
                        autoResize  : true,
                        autoSize    : true,
                        closeClick  : false,
                        openEffect  : 'none',
                        closeEffect : 'none',
                        href : '#add_new_item',
                        helpers : {
                            title : null,
                        },
                        onCleanup: function () {
                            jQuery('.fancybox-inline-tmp').replaceWith(jQuery(jQuery(this).attr('href')));
                        }
                    });






                });

                /*
                    jQuery( '.description_item, .description_tax' ).live( 'focus', function(){
                        jQuery(this).css( 'height', '142px' );
                    });

                    jQuery( '.description_item, .description_tax' ).live( 'blur', function(){
                    jQuery(this).css( 'height', '26px' );
                */

                jQuery( '#table_items, #table_taxes, #table_discounts' ).on( 'focus', '.description_tax, .description_item, .description_discount', function(){
                    jQuery(this).css( 'height', '142px' );
                });

                jQuery( '#table_items, #table_taxes, #table_discounts' ).on( 'blur', '.description_tax, .description_item, .description_discount', function(){
                    jQuery(this).css( 'height', '26px' );
                });


                jQuery('#add_new_item').on( 'keypress', '#item_rate', not_price_minus );

                jQuery('#added_items').on( 'change', '.item_price, .tax_rate, .discount_rate, .item_qty, .discount_type, .tax_type', recalculation );
                jQuery('#added_items').on( 'keyup', '.item_price, .tax_rate, .discount_rate, .item_qty', recalculation );
                jQuery('#added_items').on( '.item_price, .discount_rate', not_price_minus );
                jQuery('#added_items').on( 'keypress', '.tax_rate, .item_qty', not_price );
                jQuery('#added_items').on( 'change', '.discount_type', function() {
                    var id_discount = jQuery( this ).data('number');
                    if ( 'amount' == jQuery( this ).val() ) {
                        jQuery( '#discount_rate' + id_discount ).next().css( 'display', 'none' );
                    } else {
                        jQuery( '#discount_rate' + id_discount ).next().css( 'display', 'inline' );}
                });

                //for delete item link
                jQuery( '#table_items' ).on( 'click', '.row_del', function() {
                    jQuery(this).parent().remove();
                    recalculation();
                });

                //for delete discount link
                jQuery( '#table_discounts' ).on( 'click', '.row_del', function() {
                    jQuery(this).parent().remove();
                    if ( !jQuery( '.discount_invoice' ).length ) {
                        jQuery( '#table_discounts thead' ).css( 'display', 'none' );
                    }
                    recalculation();
                });

                //for check all preset items
                jQuery( '#check_all_preset_items' ).change(function(){
                    if ( jQuery(this).prop('checked') ) {
                        jQuery('.item_checkbox').attr( 'checked', true );
                    } else{
                        jQuery('.item_checkbox').attr( 'checked', false );
                    }
                });

                jQuery('.item_checkbox').live( 'change', function(){
                    if ( jQuery(this).is(':checked') ) {
                        if( jQuery('.item_checkbox').length == jQuery('.item_checkbox:checked').length )
                            jQuery('#check_all_preset_items').attr( 'checked', true );
                    } else{
                        jQuery('#check_all_preset_items').attr( 'checked', false );
                    }

                });

                //for delete tax link
                jQuery( '#table_taxes' ).on( 'click', '.row_del', function() {
                    jQuery(this).parent().remove();
                    if ( !jQuery( '.tax_name' ).length ) {
                        jQuery( '#table_taxes thead' ).css( 'display', 'none' );
                    }
                    recalculation();
                });

                jQuery( '#button_add_item' ).live( 'click', function(){
                    var select_checkbox = new Array;
                    jQuery( '.item_checkbox' ).each( function(){
                        if ( jQuery(this).attr("checked") ) {
                            var tds = jQuery(this).closest('tr').find('>td');
                            var name  = tds.eq(1).find('span').attr('data-info');
                            var description = tds.eq(2).find('span').attr('data-info');
                            var rate =  parseFloat( tds.eq(3).find('span').text().replace( ',', "." ) ).toFixed(rate_capacity);

                            add_items( name, description, rate );
                        }
                    });

                     recalculation();
                });

                jQuery( '#button_add_tax' ).live( 'click', function(){
                    var select_checkbox = new Array;
                    jQuery( '.tax_checkbox' ).each( function(){
                        if ( jQuery(this).prop("checked") ) {
                            var tds = jQuery(this).closest('tr').find('>td');
                            var name  = tds.eq(1).find('span').attr('data-info');
                            var description = tds.eq(2).find('span').attr('data-info');
                            var rate =  parseFloat( tds.eq(3).find('span').text() ).toFixed(rate_capacity);

                            add_taxes( name, description, rate);
                        }
                    });
                     recalculation();
                });

                //Save item
                jQuery( '#button_save_item' ).click( function() {
                    var errors = 0;

                    if ( '' == jQuery( "#item_name" ).val() ) {
                        jQuery( '#item_name' ).parent().parent().attr( 'class', 'wpc_error' );
                        errors = 1;
                    } else {
                        jQuery( '#item_name' ).parent().parent().removeClass( 'wpc_error' );
                    }

                    if ( '' == jQuery( "#item_rate" ).val() ) {
                        jQuery( '#item_rate' ).parent().parent().attr( 'class', 'wpc_error' );
                        errors = 1;
                    } else {
                        jQuery( '#item_rate' ).parent().parent().removeClass( 'wpc_error' );
                    }

                    if ( 0 == errors ) {
                        if  ( 0 != jQuery( '#item_id' ).val() ) {
                            var id = jQuery( '#item_id' ).val();
                            jQuery.fancybox.close();
                        } else {

                            var item_name = jQuery( '#item_name' ).val();
                            var item_description = jQuery( '#item_description' ).val();
                            var item_rate = parseFloat( jQuery( '#item_rate' ).val().replace( ',', "." ), 10 );
                            item_rate = item_rate.toFixed( rate_capacity );

                            add_items( item_name, item_description, item_rate );
                        }

                        recalculation();

                    }

                    return false;

                });

                //close edit item
                jQuery( '#close_edit_item' ).click( function() {
                    jQuery( '#item_id' ).val( '' );
                    jQuery( '#item_name' ).val( '' );
                    jQuery( '#item_description' ).val( '' );
                    jQuery( '#item_rate' ).val( '' );
                    jQuery.fancybox.close();
                });



                //Add New Discount
                jQuery( '#add_new_discount' ).click( function() {
                    var html;
                    num_items = num_items + 1;
                    html = html + '<tr class="discount_invoice" valign="top">';
                    html = html +      '<td class="row_del"><span></span></td>';
                    html = html +      '<td width="160px"><input type="text" class="discount_name" id="discount_name' + num_items  + '" name="wpc_data[discounts][' + num_items + '][name]" value="' + '<?php _e( 'New Item', WPC_INV_TEXT_DOMAIN ) ?>' + '" /></td>';
                    html = html +      '<td><textarea maxlength="300" class="description_discount" id="discount_description' + num_items + '" name="wpc_data[discounts][' + num_items + '][description]"></textarea></td>' ;
                    html = html +      '<td width="150px"><select id="discount_type' + num_items  + '" class="discount_type" data-number="' + num_items  + '" name="wpc_data[discounts][' + num_items  + '][type]" >';
                    html = html +           '<option value="amount" selected="selected">' + '<?php _e( 'Amount Discount', WPC_INV_TEXT_DOMAIN ) ?>' + '</option>';
                    html = html +           '<option value="percent">' + '<?php _e( 'Percent Discount', WPC_INV_TEXT_DOMAIN ) ?>' + '</option>';
                    html = html +      '</select></td>';
                    html = html +      '<td width="60px" class="add_procent"><input type="text" class="discount_rate" data-number="' + num_items  + '" id="discount_rate' + num_items  + '" name="wpc_data[discounts][' + num_items  + '][rate]" size="4" value="' + price_null + '" /><span style="display: none;">%</span></td>';
                    html = html +      '<td width="50px" align="right">&nbsp;<span class="discount_total" data-total="0" data-number="' + num_items  + '" id="item_total' + num_items  + '">' + price_null + '</span><input type="hidden" name="wpc_data[discounts][' + num_items + '][total]" value="" id="hidden_total' + num_items + '"  /></td>';
                    html = html + '</tr>';
                    jQuery( '#added_items #table_discounts tbody' ).append( html );

                    jQuery( '#added_items #table_discounts thead' ).css( 'display' , 'table-header-group' );

                    jQuery('#added_items').css('display', 'block');
                });

                //Add New Tax
                jQuery( '#add_new_tax' ).click( function() {

                    num_items = num_items + 1;

                    var html = '<tr valign="top">';
                    html = html + '<td class="row_del"><span></span></td>';
                    html = html + '<td width="160px"><input type="text" size="15" class="tax_name" id="tax_name' + num_items + '" name="wpc_data[taxes][' + num_items + '][name]" value="' + '<?php _e( 'New Item', WPC_INV_TEXT_DOMAIN ) ?>' + '" /></td>';
                    html = html + '<td><textarea maxlength="300" class="description_tax" id="tax_description' + num_items + '" name="wpc_data[taxes][' + num_items + '][description]"></textarea></td>' ;
                    html = html + '<td width="145px"><select id="tax_type' + num_items  + '" class="tax_type" data-number="' + num_items  + '" name="wpc_data[taxes][' + num_items  + '][type]" >';
                    html = html +   '<option value="before" selected="selected">' + '<?php _e( 'Before Discount', WPC_INV_TEXT_DOMAIN ) ?>' + '</option>';
                    html = html +   '<option value="after">' + '<?php _e( 'After Discount', WPC_INV_TEXT_DOMAIN ) ?>' + '</option>';
                    html = html + '</select></td>';
                    html = html + '<td width="60px" class="add_procent"><input type="text" class="tax_rate" data-number="' + num_items + '" id="tax_rate' + num_items + '" name="wpc_data[taxes][' + num_items + '][rate]" size="4" value="' + price_null + '" />%</td>';
                    html = html + '<td width="50px" align="right">&nbsp;<span class="tax_total" data-total="0" data-number="' + num_items + '" id="item_total' + num_items + '">' + price_null + '</span><input type="hidden" name="wpc_data[taxes][' + num_items + '][total]" value="" id="hidden_total' + num_items + '"  /></td>';
                    html = html + '</tr>';
                    jQuery( '#added_items #table_taxes tbody' ).append( html );

                    jQuery( '#added_items #table_taxes thead' ).css( 'display', 'table-header-group' );

                    jQuery('#added_items').css('display', 'block');
                });

                //close
                jQuery( '#close_add_payment, #close_add_new_item, #close_add_item, #close_add_new_discount, #close_add_tax' ).click( function() {
                    jQuery.fancybox.close();
                });

                //set maxlength
                jQuery('textarea[maxlength]').keyup(function(){
                    //get the limit from maxlength attribute
                    var limit = parseInt( jQuery( this ).attr( 'maxlength' ) );
                    //get the current text inside the textarea
                    var text = jQuery( this ).val();
                    //count the number of characters in the text
                    var chars = text.length;

                    //check if there are more characters then allowed
                    if ( chars > limit ){
                        //and if there are use substr to get the text before the limit
                        var new_text = text.substr( 0, limit );

                        //and change the current text with the new text
                        jQuery( this ).val( new_text );
                    }
                    jQuery( '#count_chars' ).html( ( limit - text.length ) );

                });

              });
            </script>

            <?php

            $readonly = ( !$data['option']['can_edit'] ) ? ' readonly' : '';
            $can_edit = ( $data['option']['can_edit'] ) ? true : false;
            if ( !isset( $data['data']['items'] ) )
                $data['data']['items'] = array();

            $sub_total = ( isset( $data['data']['sub_total'] ) ) ? $data['data']['sub_total'] : '0';
            $total_discount = ( isset( $data['data']['total_discount'] ) ) ? $data['data']['total_discount'] : '0';

            $wpc_custom_fields = $wpc_client->cc_get_settings( 'inv_custom_fields' );

            $new_cols = array( 'thead' => '', 'tbody' => array() );

            if ( isset( $data['data']['custom_fields'] ) ) {
                if ( $data['data']['custom_fields'] )
                    $array_display_cf = array_keys( $data['data']['custom_fields'] );
                else
                    $array_display_cf = array();
            }

            foreach ( $wpc_custom_fields as $key => $value ) {
                if ( isset( $array_display_cf ) ) {
                    if( !in_array( 'description', $array_display_cf) )
                        $add_class_hide_for_description = 'cf_hide';

                    $value['add_class_hide'] = ( !in_array( $key, $array_display_cf) ) ? 'cf_hide' : '';
                } elseif ( isset( $_GET['id'] ) ) {
                    $value['add_class_hide'] = 'cf_hide';
                } else {
                    $value['add_class_hide'] = ( isset( $value['display'] ) && 1 == $value['display'] ) ? '' : 'cf_hide';
                }


                if ( $readonly ) {
                    $value['readonly'] = $readonly;
                } elseif ( isset( $value['field_readonly'] ) && 1 == $value['field_readonly'] ) {
                    $value['readonly'] = ' disabled';
                } else {
                    $value['readonly'] = '';
                }

                $new_cols['thead'] .= '<td title="' . $value['description'] . '" class="icf_' . $key . ' ' . $value['add_class_hide'] . '">' . $value['title']  . '</td>';
                $new_cols['tbody'][ $key ] = $value ;
            }

            //for isset( $_POST[wpc_data] )
            if( isset( $data['data']['items'] ['{num_items}'] ) )
                unset( $data['data']['items'] ['{num_items}'] );

            ?>

                <div id="added_items" <?php if ( 0 == count( $data['data']['items'] ) ) echo 'style="display:none;"' ; ?> >
                    <table cellpadding="0" cellspacing="0" align="center" id="table_items">
                        <thead>
                            <tr>
                                <td></td>
                                <td width="160px"><?php _e( 'Name', WPC_INV_TEXT_DOMAIN ) ?></td>
                                <td class="descr_display <?php echo ( isset( $add_class_hide_for_description ) ) ? $add_class_hide_for_description : '' ?>"><?php _e( 'Description', WPC_INV_TEXT_DOMAIN ) ?></td>
                                <?php
                                    echo $new_cols['thead'] ;
                                ?>
                                <td style="padding: 0 0 0 15px" width="60px"><?php _e( 'Qty.', WPC_INV_TEXT_DOMAIN ) ?></td>
                                <td style="padding: 0 0 0 15px" width="60px"><?php _e( 'Rate', WPC_INV_TEXT_DOMAIN ) ?></td>
                                <td align="right" width="50px"><?php _e( 'Total', WPC_INV_TEXT_DOMAIN ) ?></td>
                            </tr>
                        </thead>
                        <tbody>
                        <?php

                        array_unshift( $data['data']['items'], array( "name"=> "", "description" => "", "quantity" => 1, "price" => ""  ) );

                        foreach ( $data['data']['items'] as $item ) {

                            $add_class = '';
                            $tds_body = '' ;
                            if ( '' != $item['name'] ) {
                                $data['option']['num_items'] ++;
                                $num_items = $data['option']['num_items'];
                                $add_class = ' class="item_qty"';
                            } else {
                                $num_items = '{num_items}';
                            }

                            foreach ( $new_cols['tbody'] as $field_slug => $field_settings ) {
                                if( isset( $item[ $field_slug ] ) )
                                    $item[ $field_slug ] = $item[ $field_slug ];
                                elseif ( isset( $field_settings['default_value'] ) )
                                    $item[ $field_slug ] = $field_settings['default_value'];
                                else
                                    $item[ $field_slug ] = '';
                                if( 'textarea' == $field_settings['type'] ) {

                                    $tds_body .= '<td class="icf_' . $field_slug . ' ' . $field_settings['add_class_hide'] . '"><textarea id="' . $field_slug . $num_items . '" class="item_custom_field" name="wpc_data[items][' . $num_items . '][' . $field_slug . ']" maxlength="300" style="height: 26px;" ' . $field_settings['readonly'] . '>' . $item[ $field_slug ] . '</textarea>' ;

                                } else if( 'selectbox' == $field_settings['type'] ) {

                                    $tds_body .= '<td class="icf_' . $field_slug . ' ' . $field_settings['add_class_hide'] . '"><select id="' . $field_slug . $num_items . '" class="item_custom_field" name="wpc_data[items][' . $num_items . '][' . $field_slug . ']"' . $field_settings['readonly'] . '>';
                                    if ( isset( $field_settings['options'] ) ) {
                                        if ( !empty( $item[ $field_slug ] ) )
                                            $selected_opt = $item[ $field_slug ];
                                        elseif ( isset( $field_settings['default_option'] ) )
                                            $selected_opt = $field_settings['default_option'];
                                        else
                                            $selected_opt = '';
                                        foreach( $field_settings['options'] as $key => $option ) {
                                            $selected = ( $key == $selected_opt ) ? ' selected' : '' ;
                                            $tds_body .= '<option value="' . $key . '" ' . $selected . '>' . $option . '</option>' ;
                                        }
                                    }
                                    $tds_body .= '</select></td>' ;

                                } elseif ( 'checkbox' == $field_settings['type'] ) {

                                    $tds_body .= '<td class="icf_' . $field_slug . ' ' . $field_settings['add_class_hide'] . '"><input type="' . $field_settings['type'] . '" class="item_custom_field" id="' . $field_slug . $num_items . '" name="wpc_data[items][' . $num_items . '][' . $field_slug . ']" value="1" ' . ( ( isset ( $item[ $field_slug ] ) && $item[ $field_slug ] ) ? ' checked' : '' ) . ' ' . $field_settings['readonly'] . ' /></td>';

                                } else {

                                    $tds_body .= '<td class="icf_' . $field_slug . ' ' . $field_settings['add_class_hide'] . '"><input type="' . $field_settings['type'] . '" class="item_custom_field" id="' . $field_slug . $num_items . '" name="wpc_data[items][' . $num_items . '][' . $field_slug . ']" value="' . $item[ $field_slug ] . '" ' . $field_settings['readonly'] . ' /></td>';

                                }
                            }

                            echo '<tr class="invoice_items"' . ( ( '' == $item['name'] ) ? ' style="display:none;"' : '' ) . ' valign="top">
                                    <td ' . ( ( $can_edit ) ? 'class="row_del"' : '' ) . '><span></span></td>
                                    <td><input type="text" class="item_name" id="item_name' . $num_items  . '" name="wpc_data[items][' . $num_items . '][name]" value="' . $item['name'] . '" ' . $readonly . ' /></td>
                                    <td class="descr_display ' . ( ( isset( $add_class_hide_for_description ) ) ? $add_class_hide_for_description : '' ) . '"><textarea maxlength="300" class="description_item" id="item_description' . $num_items  . '" name="wpc_data[items][' . $num_items  . '][description]" ' . $readonly . '>' . ( ( isset($item['description'] ) ) ? $item['description'] : '' ) . '</textarea></td>
                                    ' . $tds_body . '
                                    <td><input type="text" data-number="' . $num_items  . '" id="item_quantity' . $num_items  . '" name="wpc_data[items][' . $num_items  . '][quantity]" value="' . $item['quantity'] . '" size="1" ' . ( ( !$can_edit ) ? 'readonly' : $add_class ) . ' /></td>

                                    <td><input type="text" class="item_price" data-number="' . $num_items  . '" id="item_price' . $num_items  . '" name="wpc_data[items][' . $num_items  . '][price]" size="4" value="' . $item['price'] . '" ' . $readonly . ' /></td>
                                    <td align="right"><span class="item_total" id="item_total' . $num_items  . '" data-total="' . number_format( round( $item['price'] * $item['quantity'], 2 ), $rate_capacity, '.', '' ) . '">' . number_format( round( $item['price'] * $item['quantity'], 2 ), $rate_capacity, '.', $thousands_separator ) . '</span></td>
                                 </tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                    <div style="display: none;"><?php  ?></div>
                    <table cellpadding="0" cellspacing="0" align="center" id="table_discounts">
                        <thead <?php echo ( !isset( $data['data']['discounts'] ) || !$data['data']['discounts'] ) ? 'style="display: none;"' : '' ?>>
                            <tr>
                                <td></td>
                                <td colspan="5"><?php _e( 'Discounts', WPC_INV_TEXT_DOMAIN ) ?></td>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if ( isset( $data['data']['discounts'] ) ) {
                                foreach ( $data['data']['discounts'] as $disc ) {
                                $data['option']['num_items'] ++;
                                $num_items = $data['option']['num_items'];
                                $total_discont = ( 'amount' == $disc['type'] ) ? number_format( $disc['rate'], $rate_capacity, '.', '' ) : number_format( round( $sub_total * $disc['rate'] / 100 , 2 ), $rate_capacity, '.', '' );
                                $total_discont_html = ( 'amount' == $disc['type'] ) ? number_format( $disc['rate'], $rate_capacity, '.', $thousands_separator ) : number_format( round( $sub_total * $disc['rate'] / 100 , 2 ), $rate_capacity, '.', $thousands_separator );
                                echo '<tr class="discount_invoice" valign="top">
                                        <td  ' . ( ( $can_edit ) ? 'class="row_del"' : '' ) . '><span></span></td>
                                        <td width="160px"><input type="text" class="discount_name" id="discount_name' . $num_items  . '" name="wpc_data[discounts][' . $num_items . '][name]" value="' . $disc['name'] . '" ' . $readonly . ' /></td>
                                        <td><textarea maxlength="300" class="description_discount" id="discount_description' . $num_items  . '" name="wpc_data[discounts][' . $num_items  . '][description]" ' . $readonly . '>' . (( isset( $disc['description'] ) ) ? $disc['description'] : '' ) . '</textarea></td>
                                        <td width="150px"><select id="discount_type' . $num_items  . '" class="discount_type" data-number="' . $num_items  . '" name="wpc_data[discounts][' . $num_items  . '][type]" ' . ( ( !$can_edit ) ? 'disabled' : '' ) . ' >
                                                <option value="amount" '. ( ( 'amount' == $disc['type'] ) ? 'selected="selected"' : '' ) . '>' . __( 'Amount Discount', WPC_INV_TEXT_DOMAIN ) . '</option>
                                                <option value="percent" '. ( ( 'percent' == $disc['type'] ) ? 'selected="selected"' : '' ) . '>' . __( 'Percent Discount', WPC_INV_TEXT_DOMAIN ) . '</option>
                                        </select></td>
                                        <td width="60px" class="add_procent"><input type="text" class="discount_rate" data-number="' . $num_items  . '" id="discount_rate' . $num_items  . '" name="wpc_data[discounts][' . $num_items  . '][rate]" size="4" value="' . $disc['rate'] . '" ' . $readonly . ' /><span style="display: ' . ( ( 'percent' == $disc['type'] ) ? 'inline' : 'none' ) . ';">%</span></td>
                                        <td width="50px" align="right">&nbsp;<span class="discount_total" data-number="' . $num_items  . '" id="item_total' . $num_items  . '" data-total="' . $total_discont . '">' . $total_discont_html . '</span><input type="hidden" name="wpc_data[discounts][' . $num_items  . '][total]" value="' . $total_discont . '" id="hidden_total' . $num_items  . '"  /></td>
                                     </tr>';
                            }
                            }

                        ?>
                        </tbody>
                    </table>
                    <table cellpadding="0" cellspacing="0" align="center" id="table_taxes">
                        <thead <?php echo ( !isset( $data['data']['taxes'] ) || !$data['data']['taxes'] ) ? 'style="display: none;"' : '' ?>>
                            <tr>
                                <td></td>
                                <td colspan="5"><?php _e( 'Taxes', WPC_INV_TEXT_DOMAIN ) ?></td>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if ( isset( $data['data']['taxes'] ) ) {
                                foreach ( $data['data']['taxes'] as $tax ) {
                                $data['option']['num_items'] ++;
                                $num_items = $data['option']['num_items'];
                                $total_tax = ( 'before' == $tax['type'] ) ? number_format( round( $sub_total * $tax['rate'] / 100 , 2 ), $rate_capacity, '.', '' ) : number_format( round( ( $sub_total - $total_discount ) * $tax['rate'] / 100 , 2 ), $rate_capacity, '.', '' );
                                $total_tax_html = ( 'before' == $tax['type'] ) ? number_format( round( $sub_total  * $tax['rate'] / 100 , 2 ), $rate_capacity, '.', $thousands_separator ) : number_format( round( ( $sub_total - $total_discount ) * $tax['rate'] / 100 , 2 ), $rate_capacity, '.', $thousands_separator );


                                echo '<tr valign="top">
                                        <td ' . ( ( $can_edit ) ? 'class="row_del"' : '' ) . '><span></span></td>
                                        <td width="160px"><input type="text" size="15" class="tax_name" id="tax_name' . $num_items  . '" name="wpc_data[taxes][' . $num_items . '][name]" value="' . $tax['name'] . '" ' . $readonly . ' /></td>
                                        <td><textarea maxlength="300" class="description_tax" id="tax_description' . $num_items  . '" name="wpc_data[taxes][' . $num_items  . '][description]" ' . $readonly . '>' . $tax['description'] . '</textarea></td>
                                        <td width="145px"><select id="tax_type' . $num_items  . '" class="tax_type" data-number="' . $num_items  . '" name="wpc_data[taxes][' . $num_items  . '][type]" ' . ( ( !$can_edit ) ? 'disabled' : '' ) . ' >
                                                <option value="before" '. ( ( 'before' == $tax['type'] ) ? 'selected="selected"' : '' ) . '>' . __( 'Before Discount', WPC_INV_TEXT_DOMAIN ) . '</option>
                                                <option value="after" '. ( ( 'after' == $tax['type'] ) ? 'selected="selected"' : '' ) . '>' . __( 'After Discount', WPC_INV_TEXT_DOMAIN ) . '</option>
                                        </select></td>
                                        <td width="60px" class="add_procent"><input type="text" class="tax_rate" data-number="' . $num_items  . '" id="tax_rate' . $num_items  . '" name="wpc_data[taxes][' . $num_items  . '][rate]" size="4" value="' . $tax['rate'] . '" ' . $readonly . ' />%</td>
                                        <td width="50px" align="right">&nbsp;<span class="tax_total" data-number="' . $num_items  . '" id="item_total' . $num_items  . '" data-total="' . $total_tax . '">' . $total_tax_html . '</span><input type="hidden" name="wpc_data[taxes][' . $num_items  . '][total]" value="' . $total_tax . '" id="hidden_total' . $num_items  . '" /></td>
                                     </tr>';
                                }
                            }
                        ?>
                        </tbody>
                    </table>
                    <hr />
                    <?php
                        $selected_curr = isset ( $data['data']['currency'] ) ? $data['data']['currency'] : '' ;
                        $price_null = $this->get_currency( 0, true, $selected_curr );
                    ?>
                    <table class="total_all" align="right" cellpadding="0" cellspacing="0" >
                        <tr>
                            <td><?php _e( 'Sub Total:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td width="60px"><span id="total_all_items"><?php echo ( isset( $sub_total ) ) ? $this->get_currency( $sub_total, true, $selected_curr ) : $price_null ?></span></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Discount:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td><span id="total_discount"><?php echo ( isset( $total_discount ) ) ? $this->get_currency( $total_discount, true, $selected_curr ) : $price_null ?></span></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Tax:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td><span id="total_tax"><?php echo ( isset( $data['data']['total_tax'] ) ) ? $this->get_currency( $data['data']['total_tax'], true, $selected_curr ) : $price_null ?></span></td>
                        </tr>
                    <?php
                    if ( isset( $data['data']['due_date'] ) && '' != $data['data']['due_date'] )
                        $due_date = strtotime( $data['data']['due_date'] . ' ' . date( 'H:i:s' ) );
                    if ( isset( $due_date ) && $due_date < time() && isset( $data['data']['late_fee'] ) && 0 < $data['data']['late_fee'] ) {
                    ?>
                        <tr>
                            <td><?php _e( 'Late Fee:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td><span id="late_fee"><?php echo $this->get_currency( $data['data']['late_fee'], true, $selected_curr ) ?></span></td>
                        </tr>
                    <?php
                        }
                    if ( !isset( $data['option']['payment_amount'] ) )
                        $data['option']['payment_amount'] = 0;
                    ?>
                        <tr class="total_all bold">
                            <td><?php _e( 'Total:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td>
                                <span id="total_all">
                                    <?php echo ( isset( $data['data']['total'] ) ) ? $this->get_currency( $data['data']['total'] - $data['option']['payment_amount'], true, $selected_curr ) : $price_null ?>
                                    <span class="real_amount" style="display: none;"><?php echo ( isset( $data['data']['total'] ) ) ? $data['data']['total'] - $data['option']['payment_amount'] : $price_null ?></span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php if ( !$can_edit && '' != $data['option']['payment_amount'] ) { ?>
                        <tr>
                            <td><?php _e( 'Amount Paid:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td>
                                <span id="wpc_amount_paid">
                                    <?php echo $this->get_currency( $data['option']['payment_amount'], true, $selected_curr ) ?>
                                </span>
                            </td>
                        </tr>
                        <?php
                            if( !isset( $data['data']['recurring_type'] ) ) {
                        ?>
                        <tr>
                            <td><?php _e( 'Total Remaining:', WPC_INV_TEXT_DOMAIN ) ?></td>
                            <td>
                                <span id="total_remaining">
                                    <?php echo $this->get_currency( $data['data']['total'] - $data['option']['payment_amount'], true, $selected_curr ) ?>
                                    <span class="real_amount" style="display: none;"><?php echo $data['data']['total'] - $data['option']['payment_amount'] ?></span>
                                </span>
                            </td>
                        </tr>
                    <?php }
                        } else {
                        ?>
                        <tr>
                            <td></td>
                            <td><span id="wpc_amount_paid" style="display: none;"><?php echo $this->get_currency( 0, true, $selected_curr ) ?></span></td>
                        </tr>

                        <?php
                    } ?>
                    </table>
                    <div class="clear"></div>

                </div>
                <?php
                     if( $can_edit ) {
                     ?>
                        <br />
                        <span>
                            <a rel="" href="javascript:;" class="various"><?php _e( 'Add New Item', WPC_INV_TEXT_DOMAIN ) ?></a>
                        </span>
                        &nbsp;&nbsp;&#124;&nbsp;&nbsp;

                        <span>
                            <a rel="" id="add_new_discount"><?php _e( 'Add New Discount', WPC_INV_TEXT_DOMAIN ) ?></a>
                        </span>
                        &nbsp;&nbsp;&#124;&nbsp;&nbsp;
                        <span>
                            <a rel="" id="add_new_tax"><?php _e( 'Add New Tax', WPC_INV_TEXT_DOMAIN ) ?></a>
                        </span>
                        &nbsp;&nbsp;&#124;&nbsp;&nbsp;
                        <span>
                            <span class="wpc_pro_menu_grey" style="font-size: 13px;">
                                <?php _e( 'Add Preset Items', WPC_INV_TEXT_DOMAIN ) ?>
                            </span>
                            <span><b>(Pro)</b></span>
                        </span>
                        &nbsp;&nbsp;&#124;&nbsp;&nbsp;
                        <span>
                            <span class="wpc_pro_menu_grey" style="font-size: 13px;">
                                <?php _e( 'Add Preset Tax', WPC_INV_TEXT_DOMAIN ) ?>
                            </span>
                            <span><b>(Pro)</b></span>
                        </span>
                    <?php
                }


                //fansybox Add New Item
                if ( $can_edit ) { ?>
                <div style="display: none;">
                    <div class="wpc_add_new_item" id="add_new_item" >
                        <h3><?php _e( 'Item:', WPC_INV_TEXT_DOMAIN ) ?> </h3>
                        <form method="post" name="wpc_add_new_item" id="wpc_add_new_item">
                            <input type="hidden" id="item_id" value="" />
                            <table>
                                <tr>
                                    <td>
                                        <label>
                                            <?php _e( 'Item Name:', WPC_INV_TEXT_DOMAIN ) ?>
                                            <span class="description"><?php _e( '(required)', WPC_INV_TEXT_DOMAIN ) ?></span>
                                            <br />
                                            <input type="text" size="70" name="item_name" id="item_name" class="item_name"  value="" />

                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label>
                                            <?php _e( 'Description:', WPC_INV_TEXT_DOMAIN ) ?>
                                            <br />
                                            <textarea cols="67" rows="5" maxlength="300" id="item_description" ></textarea>
                                        </label>
                                        <p style="text-align: right;">
                                            <?php _e( 'characters remaining:', WPC_INV_TEXT_DOMAIN ) ?> <span id="count_chars">300</span>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label>
                                            <?php _e( 'Rate:', WPC_INV_TEXT_DOMAIN ) ?>
                                            <span class="description"><?php _e( '(required)', WPC_INV_TEXT_DOMAIN ) ?></span>
                                            <br />
                                            <input type="text" size="70" id="item_rate"  value="" />

                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <br />
                                        <label>
                                            <input type="checkbox" id="item_save" disabled />
                                            <span class="wpc_pro_menu_grey" style="font-size: 13px;">
                                                <?php _e( 'Save this Item as Preset Item for future use', WPC_INV_TEXT_DOMAIN ) ?>
                                            </span>
                                            <span><b>(Pro)</b></span>
                                        </label>
                                        <br />
                                    </td>
                                </tr>
                            </table>
                            <br />
                            <div style="clear: both; text-align: center;">
                                <input type="button" class='button-primary' id="button_save_item" value="<?php _e( 'Save Item', WPC_INV_TEXT_DOMAIN ) ?>" />
                                <input type="button" class='button' id="close_add_new_item" value="<?php _e( 'Close', WPC_INV_TEXT_DOMAIN ) ?>" />
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                }

        }
    //end class
    }

}

?>

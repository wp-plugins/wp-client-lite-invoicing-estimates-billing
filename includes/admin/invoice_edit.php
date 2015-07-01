<?php
//check auth
if ( !current_user_can( 'wpc_admin' ) && !current_user_can( 'administrator' ) && !current_user_can( 'wpc_create_invoices' ) ) {
    do_action( 'wp_client_redirect', get_admin_url() . 'admin.php?page=wpclient_clients' );
}

do_action( 'wpc_client_add_meta_boxes', 'wp-client_page_wpclients_invoicing', '' );
do_action( 'wpc_client_add_meta_boxes_wp-client_page_wpclients_invoicing' , '' );

//save data
if ( isset( $_POST['wpc_data'] ) ) {
    $error = $this->save_data( $_POST['wpc_data'] );
}


//save payment
if ( isset( $_POST['wpc_payment'] ) ) {
    $error = $this->save_payment( $_POST['wpc_payment'] );
}

global $wpdb, $wpc_client;
$wpc_currency = $wpc_client->cc_get_settings( 'currency' );

$wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );
$rate_capacity = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;
$thousands_separator = ( isset( $wpc_invoicing['thousands_separator'] ) && !empty( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '';

$option = array();
$num_items = $can_edit = 0;

//get data
if ( isset( $_POST['wpc_data'] ) ) {
    $data = $_POST['wpc_data'];
} elseif ( isset( $_GET['id'] ) && 0 < $_GET['id'] ) {
    $data = $this->get_data( $_GET['id'] );

    //wrong ID
    if ( !$data ) {
        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_invoicing' );
        exit;
    }

    if ( isset( $data['discounts'] ) && '' != $data['discounts'] ) {
        $data['discounts'] = unserialize( $data['discounts'] );
    } else {
       $data['discounts'] = array();
    }



    if ( isset( $data['due_date'] ) && '' != $data['due_date'] ) {
        $data['due_date'] = date(  'm/d/Y', $data['due_date'] );
    }

    if ( isset( $data['items'] ) && '' != $data['items'] ) {
        $data['items'] = unserialize( $data['items'] );
    } else {
       $data['items'] = array();
    }

    if ( isset( $data['taxes'] ) && '' != $data['taxes'] ) {
        $data['taxes'] = unserialize( $data['taxes'] );
    } else {
        $data['taxes'] = array();
    }

    if ( isset( $data['order_id'] ) && '' != $data['order_id'] ) {
       $payment_amount = $this->get_amount_paid( $_GET['id'] );
    }

} else {
    $data = array();
    $data['items'] = array();
    $data['discounts'] = array();
    $data['taxes'] = array();

    if ( isset( $wpc_invoicing['ter_con'] ) ) {
        $data['terms'] = $wpc_invoicing['ter_con'];
    }

    if ( isset( $wpc_invoicing['not_cus'] ) ) {
        $data['note'] = $wpc_invoicing['not_cus'];
    }
}

$option['num_items'] = & $num_items;
$option['can_edit'] = & $can_edit;
$option['payment_amount'] = ( isset( $payment_amount ) ) ? $payment_amount : 0;

//set return url
$return_url = get_admin_url(). 'admin.php?page=wpclients_invoicing';
if ( isset( $_SERVER['HTTP_REFERER'] ) && '' != $_SERVER['HTTP_REFERER'] ) {
    $return_url = $_SERVER['HTTP_REFERER'];
}

$status = '';
if ( isset( $data['status'] ) && '' != $data['status'] )
    $status = $data['status'];

$can_add_payment    = ( 'paid' != $status && 'void' != $status && 'refunded' != $status ) ? 1 : 0;
$can_edit           = ( in_array( $status, array( 'paid', 'pending', 'partial', 'void', 'active', 'refunded' ) ) ) ? 0 : 1;

?>
<div class="wrap">

     <?php echo $wpc_client->get_plugin_logo_block() ?>

    <h2>

        <?php

            if ( isset( $_GET['id'] ) && '' != $_GET['id'] ) {
                _e( 'Edit Invoice', WPC_INV_TEXT_DOMAIN );
                $pref = ( isset( $data['prefix']  ) ) ? $data['prefix'] : '' ;
                $custom_number = ( isset( $data['custom_number']  ) ) ? $data['custom_number'] : '' ;
                echo ' #' . $this->get_number_format( $data['number'], $pref, $custom_number );
                //display status
                if ( $this->display_status_name( $data['status'] ) )
                    echo ' - ' . $this->display_status_name( $data['status'] ) ;

            } else {
                _e( 'Add Invoice', WPC_INV_TEXT_DOMAIN );
            }
        ?>

        <?php /*if ( isset( $_GET['id'] ) && '' != $_GET['id'] && $can_add_payment ) { ?>
            <input type="button" id="open_add_payment" class="button" value="<?php _e( 'Add Payment', WPC_INV_TEXT_DOMAIN ) ?>" />
        <?php }*/ ?>

        <?php
            if ( isset( $data['parrent_id'] ) ) {
                $data['parrent_id'] = (int)$data['parrent_id'] ;
                $parrent_type = get_post_meta( $data['id'], 'wpc_inv_parent_type', true ) ;
                $isset_parrent = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID = %d", $data['parrent_id'] ) ) ;
                if( $isset_parrent ) {
                    switch ( $parrent_type ) {
                        case 'accum_inv':
                            echo '<p>' . __( 'This Invoice Created by - Accumulating Profile ', WPC_INV_TEXT_DOMAIN ) . '<a href="admin.php?page=wpclients_invoicing&tab=accum_invoice_edit&id=' . $data['parrent_id'] . '" target="_blank">' . $wpdb->get_var( "SELECT post_title FROM {$wpdb->posts} WHERE ID = " . $data['parrent_id'] ) . '</a></p>';
                        break;
                        case 'repeat_inv':
                            echo '<p>' . __( 'This Invoice Created by - Recurring Profile ', WPC_INV_TEXT_DOMAIN ) . '<a href="admin.php?page=wpclients_invoicing&tab=repeat_invoice_edit&id=' . $data['parrent_id'] . '" target="_blank">' . $wpdb->get_var( "SELECT post_title FROM {$wpdb->posts} WHERE ID = " . $data['parrent_id'] ) . '</a></p>';
                        break;
                    }
                } else {
                    switch ( $parrent_type ) {
                        case 'accum_inv':
                            echo '<p>' . __( 'This Invoice Created by - Accumulating Profile (deleted)', WPC_INV_TEXT_DOMAIN ) . '</p>';
                        break;
                        case 'repeat_inv':
                            echo '<p>' . __( 'This Invoice Created by - Recurring Profile (deleted)', WPC_INV_TEXT_DOMAIN ) . '</p>';
                        break;
                    }
                }
            }
        ?>

    </h2>

    <div id="message" class="error wpc_notice fade" <?php echo ( !isset( $error ) || empty( $error ) ) ? 'style="display: none;" ' : '' ?> ><?php echo ( isset( $error ) ) ? $error : '' ?></div>

<form id="edit_data" action="" method="post">
    <input type="hidden" name="wpc_data[id]" value="<?php echo ( isset( $_GET['id'] ) ) ? $_GET['id'] : '' ?>" />
    <input type="hidden" name="wpc_data[status]" id="inv_status" value="<?php echo ( isset( $data['status'] ) ) ? $data['status'] : '' ?>" />
    <?php
        if ( isset( $data['parrent_id'] ) ) {
            echo '<input type="hidden" name="wpc_data[parrent_id]" value="' . $data['parrent_id'] . '" />';
        }
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2 not_bold">
            <div id="post-body-content">
                <div id="titlediv">
                    <div id="titlewrap">
                        <label for="title"><?php _e( 'Title', WPC_INV_TEXT_DOMAIN ) ?></label><br />
                        <input type="text" name="wpc_data[title]" id="title" value="<?php echo ( isset( $data['title'] ) ) ? $data['title'] : '' ?>" <?php echo ( !$can_edit ) ? 'readonly' : '' ?> />
                    </div>
                </div>
                <div id="postdivrich" class="postarea edit-form-section">
                    <label><?php _e( 'Description', WPC_INV_TEXT_DOMAIN ) ?></label>
                    <div class="postarea">

                    <?php
                        $settings = array( 'media_buttons' => false, 'textarea_rows' => 5, 'tinymce' => 0   );
                        $description = ( isset( $data['description'] ) ) ? $data['description'] : '';
                        if ( $can_edit ) {
                            wp_editor( $description, 'wpc_data[description]', $settings );
                        } else {
                            echo '<textarea style="width: 100%;" rows="5" readonly>' . $description . '</textarea>';
                        }
                    ?>

                    </div>
                </div>
            </div><!-- #post-body-content -->
            <div id="postbox-container-1" class="postbox-container">

                <div id="side-info-column" class="inner-sidebar">
                    <?php
                        do_meta_boxes( 'wp-client_page_wpclients_invoicing', 'side', array( 'data' => $data, 'option' => $option )  ) ;
                    ?>
                </div>
             </div>
             <div id="postbox-container-2" class="postbox-container">
                <?php do_meta_boxes('wp-client_page_wpclients_invoicing', 'normal', array( 'data' => $data, 'option' => $option ) ); ?>
            </div>
        </div><!-- #post-body -->
  </div> <!-- #poststuff -->

</form>

<?php if ( isset( $_GET['id'] ) && '' != $_GET['id'] && $can_add_payment ) { ?>
<div style="display: none;">
    <div class="wpc_add_payment" id="add_payment">
        <h3><?php _e( 'Add Payment:', WPC_INV_TEXT_DOMAIN ) ?></h3>
        <form method="post" name="wpc_add_payment" id="wpc_add_payment">
            <input type="hidden" name="wpc_payment[inv_id]" id="wpc_payment_inv_id" value="<?php echo $_GET['id'] ?>" />
            <input type="hidden" name="wpc_payment[currency]" id="wpc_payment_currency" value="<?php echo get_post_meta( $_GET['id'], 'wpc_inv_currency', true ) ?>" />
            <table>
                <tr>
                    <td>
                        <label>
                            <?php _e( 'Invoice Total:', WPC_INV_TEXT_DOMAIN ) ?>
                            <span id="wpc_add_payment_total"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <?php _e( 'Amount Paid:', WPC_INV_TEXT_DOMAIN ) ?>
                            <span id="wpc_add_payment_amount_paid"></span>
                        </label>
                        <br />
                        <br />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <?php _e( 'Amount Received:', WPC_INV_TEXT_DOMAIN ) ?>
                            <span class="description"><?php _e( '(required)', WPC_INV_TEXT_DOMAIN ) ?></span>
                            <br />
                            <input type="text" size="70" name="wpc_payment[amount]" id="wpc_payment_amount"  value="" />
                        </label>
                        <br />
                        <span class="description"><?php _e( "Not to be more than total. Can be a partial payment.", WPC_INV_TEXT_DOMAIN ) ?></span>
                        <br />
                        <br />
                    </td>
                </tr>
                <tr>
                    <td>
                        <table>
                            <tr>
                                <td>
                                    <label>
                                        <?php _e( 'Payment date:', WPC_INV_TEXT_DOMAIN ) ?>
                                        <span class="description"><?php _e( '(required)', WPC_INV_TEXT_DOMAIN ) ?></span>
                                        <br />
                                        <input type="text" name="wpc_payment[date]" id="wpc_payment_date" value="<?php echo ( isset( $data['due_date'] ) ) ? $data['due_date'] : '' ?>"/>
                                    </label>
                                    <br />
                                    <br />
                                </td>
                                <td width="50"></td>
                                <td>
                                    <label>
                                        <?php _e( 'Payment Method:', WPC_INV_TEXT_DOMAIN ) ?>
                                        <span class="description"><?php _e( '(required)', WPC_INV_TEXT_DOMAIN ) ?></span>
                                        <br />
                                        <select name="wpc_payment[method]" id="wpc_payment_method" >
                                            <option value="p_cash" selected ><?php _e( 'Cash', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_check"><?php _e( 'Check', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_wire_transfer"><?php _e( 'Wire Transfer', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_credit_card"><?php _e( 'Credit Card', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_paypal" ><?php _e( 'PayPal', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_barter"><?php _e( 'Barter', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_contribution"><?php _e( 'Contribution', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            <option value="p_other"><?php _e( 'Other', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        </select>
                                    </label>
                                    <br />
                                    <br />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <?php _e( 'Notes:', WPC_INV_TEXT_DOMAIN ) ?>
                            <br />
                            <textarea cols="67" rows="3" name="wpc_payment[notes]" id="wpc_payment_notes" ></textarea>
                        </label>
                        <br />
                        <br />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" size="70" name="wpc_payment[thanks]" id="wpc_payment_thanks"  value="1" />
                            <?php _e( 'Send a "thank you" note for this payment', WPC_INV_TEXT_DOMAIN ) ?>
                        </label>
                    </td>
                </tr>
            </table>
            <br />
            <div style="clear: both; text-align: center;">
                <input type="button" class='button-primary' id="save_add_payment" value="<?php _e( 'Add Payment', WPC_INV_TEXT_DOMAIN ) ?>" />
                <input type="button" class='button' id="close_add_payment" value="<?php _e( 'Close', WPC_INV_TEXT_DOMAIN ) ?>" />
            </div>
        </form>
    </div>
</div>

<?php } ?>


</div>
<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function() {

        <?php if ( $can_edit ) { ?>
        //data piker
        jQuery( '#wpc_data_due_date' ).datepicker({
            dateFormat : 'mm/dd/yy'
        });
        <?php } ?>

        //Set pre-set due data
        jQuery( '.wpc_set_due_date' ).click( function() {
            jQuery( '#wpc_data_due_date' ).val( jQuery( this ).attr( 'rel' ) );
        });


        //Save Draft data
        jQuery( '#save_draft' ).click( function() {
            var errors = 0;

            if ( jQuery( "#wpc_clients" ).val() != '' || jQuery( "#wpc_circles" ).val() != '' ) {
                jQuery( '#wpc_clients' ).parent().removeClass( 'wpc_error' );
                jQuery( '#wpc_circles' ).parent().removeClass( 'wpc_error' );
            } else {
                errors = 1
                jQuery( '#wpc_clients' ).parent().attr( 'class', 'wpc_error' );
                jQuery( '#wpc_circles' ).parent().attr( 'class', 'wpc_error' );
                jQuery( '#save_data' ).focus();
            }

            if ( 0 == errors ) {
                jQuery( '#inv_status' ).val( 'draft' );
                jQuery( '#edit_data' ).submit();
            }
            return false;
        });


        //Save data
        jQuery( '#save_open' ).click( function() {
            var errors = 0;

            if ( jQuery( "#wpc_clients" ).val() != '' || jQuery( "#wpc_circles" ).val() != '' ) {
                jQuery( '#wpc_clients' ).parent().removeClass( 'wpc_error' );
                jQuery( '#wpc_circles' ).parent().removeClass( 'wpc_error' );
            } else {
                errors = 1
                jQuery( '#wpc_clients' ).parent().attr( 'class', 'wpc_error' );
                jQuery( '#wpc_circles' ).parent().attr( 'class', 'wpc_error' );
                jQuery( '#save_data' ).focus();
            }

            if ( 0 == errors ) {
                if ( jQuery( '#send_email' ).prop("checked") )
                    jQuery( '#inv_status' ).val( 'sent' );
                else
                    jQuery( '#inv_status' ).val( 'open' );
                jQuery( '#edit_data' ).submit();
            }
            return false;
        });


        //cancel edit INV
        jQuery( '#data_cancel' ).click( function() {
            self.location.href="<?php echo $return_url ?>";
            return false;
        });


        //open Add Payment
        jQuery( '#open_add_payment' ).click( function() {

            //set payment amount
            if ( jQuery( '#total_remaining .amount' ).length ) {
                jQuery( '#wpc_payment_amount' ).val( jQuery( '#total_remaining .real_amount' ).html() );
            } else {
                jQuery( '#wpc_payment_amount' ).val( jQuery( '#total_all .real_amount' ).html() );
            }

            if( 'false' == jQuery( '#wpc_deposit' ).val() )
                jQuery( '#wpc_payment_amount' ).attr( 'readonly', 'readonly' );

            jQuery( '#wpc_add_payment_total' ).html( jQuery( '#total_all' ).html() );
            jQuery( '#wpc_add_payment_amount_paid' ).html( jQuery( '#wpc_amount_paid' ).html() );

            jQuery( '#wpc_payment_date' ).val( '<?php echo date( 'm/d/Y', time() ) ?>' );

            jQuery.fancybox({
                autoResize  : true,
                autoSize    : true,
                closeClick  : false,
                openEffect  : 'none',
                closeEffect : 'none',
                href : '#add_payment',
                helpers : {
                    title : null,
                },
                onCleanup: function () {
                    jQuery('.fancybox-inline-tmp').replaceWith(jQuery(jQuery(this).attr('href')));
                }
            });


        });

        jQuery( '#wpc_payment_date' ).datepicker({
            dateFormat : 'mm/dd/yy'
        });

        //Save payment
        jQuery( '#save_add_payment' ).click( function() {

            var errors = 0;

            if ( '' == jQuery( "#wpc_payment_amount" ).val() ) {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else if ( 'true' == jQuery( '#wpc_deposit' ).val() && jQuery( "#wpc_minimum_deposit").val() > jQuery( "#wpc_payment_amount" ).val() ) {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else {
                jQuery( '#wpc_payment_amount' ).parent().parent().removeClass( 'wpc_error' );
            }

            if ( '' == jQuery( "#wpc_payment_date" ).val() ) {
                jQuery( '#wpc_payment_date' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else {
                jQuery( '#wpc_payment_date' ).parent().parent().removeClass( 'wpc_error' );
            }

            if ( '' == jQuery( "#wpc_payment_method" ).val() ) {
                jQuery( '#wpc_payment_method' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else {
                jQuery( '#wpc_payment_method' ).parent().parent().removeClass( 'wpc_error' );
            }

            if ( 0 == errors ) {
                jQuery( '#wpc_add_payment' ).submit();
            }

            return false;

        });

    });



</script>
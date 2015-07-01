<?php
//check auth
if ( !current_user_can( 'wpc_admin' ) && !current_user_can( 'administrator' ) && !current_user_can( 'wpc_create_estimates' ) ) {
    do_action( 'wp_client_redirect', get_admin_url() . 'admin.php?page=wpclient_clients' );
}

do_action( 'wpc_client_add_meta_boxes', 'wp-client_page_wpclients_invoicing', '' );
do_action( 'wpc_client_add_meta_boxes_wp-client_page_wpclients_invoicing' , '' );

//save data
if ( isset( $_POST['wpc_data'] ) ) {
    $error = $this->save_data( $_POST['wpc_data'] );
}


global $wpc_client;
$wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );
$rate_capacity = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;
$thousands_separator = ( isset( $wpc_invoicing['thousands_separator'] ) && !empty( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '';

$option = array();
$num_items = 0;


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
$option['payment_amount'] = 0;

//set return url
$return_url = get_admin_url(). 'admin.php?page=wpclients_invoicing&tab=estimates';
if ( isset( $_SERVER['HTTP_REFERER'] ) && '' != $_SERVER['HTTP_REFERER'] ) {
    $return_url = $_SERVER['HTTP_REFERER'];
}

$status = '';
if ( isset( $data['status'] ) && '' != $data['status'] )
    $status = $data['status'];

$can_edit = 1 ;

?>
<div class="wrap">

     <?php echo $wpc_client->get_plugin_logo_block() ?>

    <h2>

        <?php
            $pref = ( isset( $data['prefix'] ) ) ? $data['prefix'] : '' ;
            $custom_number = ( isset( $data['custom_number'] ) ) ? $data['custom_number'] : '' ;
            if ( 'invoice_edit' == $_GET['tab'] ) {
                if ( isset( $_GET['id'] ) && '' != $_GET['id'] ) {
                    _e( 'Edit Invoice', WPC_INV_TEXT_DOMAIN );
                    echo ' #' . $this->get_number_format( $data['number'], $pref, $custom_number, 'est' );
                    //display status
                    if ( $this->display_status_name( $data['status'] ) )
                        echo ' (' . $this->display_status_name( $data['status'] ) . ')';

                } else {
                    _e( 'Add Invoice', WPC_INV_TEXT_DOMAIN );
                }
            }

            else if ( 'estimate_edit' == $_GET['tab'] ) {
                if ( isset( $_GET['id'] ) && '' != $_GET['id'] ) {
                    _e( 'Edit Estimate', WPC_INV_TEXT_DOMAIN );
                    echo ' #' . $this->get_number_format( $data['number'], $pref, $custom_number, 'est' );

                } else {
                    _e( 'Add Estimate', WPC_INV_TEXT_DOMAIN );
                }
            }
        ?>


    </h2>

    <div id="message" class="error wpc_notice fade" <?php echo ( !( isset( $error ) && !empty( $error ) ) ) ? 'style="display: none;" ' : '' ?> ><?php echo ( isset( $error ) && !empty( $error ) ) ? $error : ''; ?></div>

<form id="edit_data" action="" method="post">
    <input type="hidden" name="wpc_data[id]" value="<?php echo ( isset( $_GET['id'] ) ) ? $_GET['id'] : '' ?>" />
    <input type="hidden" name="wpc_data[status]" id="inv_status" value="<?php echo ( isset( $data['status'] ) ) ? $data['status'] : '' ?>" />

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
                            echo '<textarea cols="120" rows="5" readonly>' . $description . '</textarea>';
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

    });



</script>
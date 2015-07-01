<?php
global $wpdb, $wp_query, $wpc_client;

$wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

/*
* Show Invocing
*/
if ( isset( $wp_query->query_vars['wpc_page_value'] ) && '' != $wp_query->query_vars['wpc_page_value'] ) {
    $invoice_id = $wp_query->query_vars['wpc_page_value'];
}

$id_inv = $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p
                                LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND ( coa.object_type = 'invoice' OR coa.object_type = 'estimate' ) )
                                WHERE p.ID = '%s' AND coa.assign_id = %d
                                ",
                                $invoice_id,
                                $client_id
                                ));

$invoice_data = $this->get_data( $id_inv ) ;
$prefix = ( isset( $invoice_data['prefix'] ) ) ? $invoice_data['prefix'] : '' ;

//have not access
if ( !isset( $invoice_data['id'] )  )
    return 'err';


//is void
if ( 'void' == $invoice_data['status'] ) {
    do_action( 'wp_client_redirect', $wpc_client->cc_get_slug( 'hub_page_id' ) );
    exit;
}



//payment process
if ( isset( $_GET['pay_now'] ) && 1 == $_GET['pay_now'] ) {

    if ( current_user_can( 'wpc_client_staff' ) && !current_user_can( 'wpc_paid_invoices' ) ) {
        return '';
    }

    //start payment process
    $this->start_payment_steps( $invoice_data['id'], $client_id );

}


$wpnonce = wp_create_nonce( 'wpc_invoice_view' . $invoice_id );
if ( is_array( $invoice_data ) ) {
    global $wpc_client;

    $wpc_templates_shortcodes   = $wpc_client->cc_get_settings( 'templates_shortcodes' );
    if ( isset( $wpc_templates_shortcodes['wpc_client_inv_' . $invoice_data['type'] . '_page'] ) && '' != $wpc_templates_shortcodes['wpc_client_inv_' . $invoice_data['type'] . '_page'] ) {
        //get custom template
        $template = $wpc_templates_shortcodes['wpc_client_inv_' . $invoice_data['type'] . '_page'];
    } else {
        //get default template
        $template = file_get_contents( $this->extension_dir . 'includes/templates/' . 'wpc_client_inv_' . $invoice_data['type'] . '_page.tpl' );
        $wpc_templates_shortcodes['wpc_client_inv_' . $invoice_data['type'] . '_page'] = $template;
        do_action( 'wp_client_settings_update', $wpc_templates_shortcodes, 'templates_shortcodes' );
    }

    if( 'inv' == $invoice_data['type'] ) {
        $data['invoice_data'] = $invoice_data;
        $data['invoice_title'] = __( 'Invoice #', WPC_INV_TEXT_DOMAIN );
        $data['invoice_number'] = $this->get_number_format( $invoice_data['number'], $prefix, $invoice_data['custom_number'] );
        $data['invoice_status'] = ( isset( $invoice_data['status'] ) && 'new' != $invoice_data['status'] ) ? ' - ' . $this->display_status_name( $invoice_data['status'] ) : '';
    } else {
        $data['estimate_data'] = $invoice_data;
        $data['estimate_title'] = __( 'Estimate #', WPC_INV_TEXT_DOMAIN );
        $data['estimate_number'] = $this->get_number_format( $invoice_data['number'], $prefix, $invoice_data['custom_number'], 'est' );
        $data['estimate_status'] = ( isset( $invoice_data['status'] ) && 'new' != $invoice_data['status'] ) ? '(' . $this->display_status_name( $invoice_data['status'] ) . ')' : '';
        $data['textarea'] = '<textarea cols="50" rows="5" name="wpc_est_decline_note" class="textarea_wpc_note"></textarea>';
    }

    //make link
    if ( $wpc_client->permalinks ) {
        $data['download_link'] = add_query_arg( array( 'wpc_action' => 'download_pdf', 'id' => $invoice_data['id'] ), $wpc_client->cc_get_slug( 'invoicing_page_id' ) . $invoice_data['id'] . '/' );
    } else {
        $data['download_link'] = add_query_arg( array( 'wpc_page' => 'invoicing', 'wpc_page_value' => $invoice_data['id'], 'wpc_action' => 'download_pdf', 'id' => $invoice_data['id'] ), $wpc_client->cc_get_slug( 'invoicing_page_id', false ) );
    }

    $data['download_link_text'] = __( 'Download PDF', WPC_INV_TEXT_DOMAIN );
    $data['text_slider'] = __( 'Payment Amount', WPC_INV_TEXT_DOMAIN );

    if ( isset( $wpc_invoicing['gateways'] ) && count( $wpc_invoicing['gateways'] ) ) {
        if ( isset( $invoice_data['status'] ) && 'inv' == $invoice_data['type'] && 'paid' != $invoice_data['status'] && 'refunded' != $invoice_data['status'] && 0 < $invoice_data['total'] ) {
            if( !current_user_can( 'wpc_client_staff' ) || ( current_user_can( 'wpc_client_staff' ) && current_user_can( 'wpc_paid_invoices' ) ) ) {
                //make link
                if ( $wpc_client->permalinks ) {
                    $data['paid_link'] = add_query_arg( array( 'pay_now' => '1' ), $wpc_client->cc_get_slug( 'invoicing_page_id' ) . $invoice_data['id'] . '/' );
                } else {
                    $data['paid_link'] = add_query_arg( array( 'wpc_page' => 'invoicing', 'wpc_page_value' => $invoice_data['id'], 'pay_now' => '1' ), $wpc_client->cc_get_slug( 'invoicing_page_id', false ) );
                }

                $data['paid_link_text'] = __( 'Pay now!', WPC_INV_TEXT_DOMAIN );
            }
        }
    }

    $currency = $this->get_currency_and_side( $invoice_data['id'] );
    if ('left' == $currency['align'] )
        $data['left_currency'] = '<span style="color:#f6931f; font-weight:bold;">' . $currency['symbol'] . '</span>' ;
    else
        $data['right_currency'] = '<span style="color:#f6931f; font-weight:bold;">' . $currency['symbol'] . '</span>' ;

    $total = $invoice_data['total'] - $this->get_amount_paid( $invoice_data['id'] );

    $selected_curr = ( isset( $invoice_data['currency'] ) ) ? $invoice_data['currency'] : '';
    $data['max_amount'] = $this->get_currency( $total, 0, $selected_curr );

    $step = $this->get_step( $total );

    if( isset( $invoice_data['min_deposit'] ) && 0 < $invoice_data['min_deposit'] ) {
        $data['min_amount'] = $this->get_currency( $invoice_data['min_deposit'], 0, $selected_curr );
        $slide_max = floor( ( $total - $invoice_data['min_deposit'] ) / $step ) * $step + 1;
        $slide_min = ( $invoice_data['min_deposit'] < $step ) ? 0 : ( floor( $invoice_data['min_deposit'] / $step ) * $step ) ;
        $min_deposit = $invoice_data['min_deposit'];
    } else {
        $min_deposit =  $step;
        $data['min_amount'] = $this->get_currency( $step, 0, $selected_curr );
        $slide_max = floor( $total / $step ) * $step ;
        $slide_min = $step;
    }
    if ( isset( $invoice_data['deposit'] ) && $invoice_data['deposit'] && $total > (2*$min_deposit) )
        $data['show_slide'] = true;

    $rate_capacity = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;
    $show_total = number_format( (float)$total, $rate_capacity, '.', '' );
    $thousands_separator = ( isset( $wpc_invoicing['thousands_separator'] ) && !empty( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '';

    $show_total = number_format( round( $show_total , 2 ), $rate_capacity, '.', $thousands_separator );


    $args['invoice_content'] = $this->invoicing_put_values( $invoice_data );
    $template = $wpc_client->cc_replace_placeholders( $template, $args );

    $out2 =  $wpc_client->cc_getTemplateContent( 'wpc_client_inv_' . $invoice_data['type'] . '_page', $data, '', $template );

    echo do_shortcode( $out2 );
}

?>

<script type="text/javascript">

    jQuery( document ).ready( function(){
        var max = <?php echo $slide_max ?> ;
        var min = <?php echo  $slide_min ?> ;
        var step = <?php echo  $step ?> ;
        var val_input;
        var real_max = <?php echo $total ?> ;
        var real_min = <?php echo $min_deposit ?> ;
        jQuery( "#slider-range-min" ).slider({
            range: "min",
            step: step,
            value: max,
            min: min,
            max: max,
            slide: function( event, ui ) {
                if ( max == ui.value )
                    val_input = real_max;
                else if ( min == ui.value )
                    val_input = real_min;
                else
                    val_input = ui.value;
                jQuery( "#text_amount" ).val( val_input );
            }
        });
        jQuery( "#text_amount" ).val( '<?php echo $show_total ?>' );

        jQuery( '#text_amount' ).on( 'keypress', function(e) {
            if (!(e.which==8 || e.which==46 || e.which==39 || e.which==37 ||e.which==0 ||(e.which>47 && e.which<58))) return false;
        } );


    });

</script>
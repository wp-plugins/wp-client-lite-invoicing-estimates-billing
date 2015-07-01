<?php
global $wpdb, $wpc_client, $wpc_gateway_plugins;


//save settings
if ( isset( $_POST['update_settings'] ) && '' != $_POST['update_settings'] ) {
    $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

    $wpc_invoicing['prefix']                    = ( isset( $_POST['settings']['prefix'] ) && '' != $_POST['settings']['prefix'] ) ? $_POST['settings']['prefix'] : '';
    $wpc_invoicing['next_number']               = ( isset( $_POST['settings']['next_number'] ) && '' != $_POST['settings']['next_number'] ) ? $_POST['settings']['next_number'] : $this->get_next_number(false);
    $wpc_invoicing['digits_count']              = ( isset( $_POST['settings']['digits_count'] ) && is_numeric( $_POST['settings']['digits_count'] ) && 2 < $_POST['settings']['digits_count'] ) ? $_POST['settings']['digits_count'] : 8;
    $wpc_invoicing['display_zeros']             = ( isset( $_POST['settings']['display_zeros'] ) && 'yes' == $_POST['settings']['display_zeros'] ) ? 'yes' : 'no';

    $wpc_invoicing['prefix_est']                = ( isset( $_POST['settings']['prefix_est'] ) && '' != $_POST['settings']['prefix_est'] ) ? $_POST['settings']['prefix_est'] : '';
    $wpc_invoicing['next_number_est']           = ( isset( $_POST['settings']['next_number_est'] ) && '' != $_POST['settings']['next_number_est'] ) ? $_POST['settings']['next_number_est'] : $this->get_next_number(false, 'est' );
    $wpc_invoicing['digits_count_est']          = ( isset( $_POST['settings']['digits_count_est'] ) && is_numeric( $_POST['settings']['digits_count_est'] ) && 2 < $_POST['settings']['digits_count_est'] ) ? $_POST['settings']['digits_count_est'] : 8;
    $wpc_invoicing['display_zeros_est']         = ( isset( $_POST['settings']['display_zeros_est'] ) && 'yes' == $_POST['settings']['display_zeros_est'] ) ? 'yes' : 'no';

    $wpc_invoicing['rate_capacity']             = ( isset( $_POST['settings']['rate_capacity'] ) && '2' < $_POST['settings']['rate_capacity'] && '6' > $_POST['settings']['rate_capacity'] ) ? $_POST['settings']['rate_capacity'] : 2;
    $wpc_invoicing['thousands_separator']       = ( isset( $_POST['settings']['thousands_separator'] ) && '' != $_POST['settings']['thousands_separator'] ) ? $_POST['settings']['thousands_separator'] : '';
    $wpc_invoicing['send_for_review']           = ( isset( $_POST['settings']['send_for_review'] ) && 'yes' == $_POST['settings']['send_for_review'] ) ? 'yes' : 'no';
    $wpc_invoicing['send_for_paid']             = ( isset( $_POST['settings']['send_for_paid'] ) && 'yes' == $_POST['settings']['send_for_paid'] ) ? 'yes' : 'no';
    $wpc_invoicing['notify_payment_made']       = ( isset( $_POST['settings']['notify_payment_made'] ) && 'yes' == $_POST['settings']['notify_payment_made'] ) ? 'yes' : 'no';
    $wpc_invoicing['reminder_days_enabled']     = ( isset( $_POST['settings']['reminder_days_enabled'] ) && 'yes' == $_POST['settings']['reminder_days_enabled'] ) ? $_POST['settings']['reminder_days_enabled'] : 'no';
    $wpc_invoicing['reminder_days']             = ( isset( $_POST['settings']['reminder_days'] ) && 0 < $_POST['settings']['reminder_days'] && 32 > $_POST['settings']['reminder_days'] ) ? $_POST['settings']['reminder_days'] : 1;
    $wpc_invoicing['reminder_one_day']          = ( isset( $_POST['settings']['reminder_one_day'] ) && 'yes' == $_POST['settings']['reminder_one_day'] && 32 > $_POST['settings']['reminder_one_day'] ) ? 'yes' : 'no';
    $wpc_invoicing['reminder_after']            = ( isset( $_POST['settings']['reminder_after'] ) && 0 <= $_POST['settings']['reminder_after'] && 32 > $_POST['settings']['reminder_after'] ) ? $_POST['settings']['reminder_after'] : 0;
    $wpc_invoicing['currency_symbol']           = $_POST['settings']['currency_symbol'];
    $wpc_invoicing['currency_symbol_align']     = ( isset( $_POST['settings']['currency_symbol_align'] ) ) ? $_POST['settings']['currency_symbol_align'] : 'left';
    $wpc_invoicing['gateways']                  = ( isset( $_POST['settings']['gateways'] ) ) ? $_POST['settings']['gateways'] : array();
    $wpc_invoicing['description']               = ( isset( $_POST['settings']['description'] ) ) ? $_POST['settings']['description'] : '';
    $wpc_invoicing['ter_con']                   = ( isset( $_POST['settings']['ter_con'] ) ) ? $_POST['settings']['ter_con'] : '';
    $wpc_invoicing['not_cus']                   = ( isset( $_POST['settings']['not_cus'] ) ) ? $_POST['settings']['not_cus'] : '';

    do_action( 'wp_client_settings_update', $wpc_invoicing, 'invoicing' );
    do_action( 'wp_client_redirect', get_admin_url() . 'admin.php?page=wpclients_invoicing&tab=settings&msg=u' );
    exit;
}


$wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );
$wpc_gateways = $wpc_client->cc_get_settings( 'gateways' );

//Set date format
if ( get_option( 'date_format' ) ) {
    $date_format = get_option( 'date_format' );
} else {
    $date_format = 'm/d/Y';
}

if ( get_option( 'time_format' ) ) {
    $time_format = get_option( 'time_format' );
} else {
    $time_format = 'g:i:s A';
}

$next_number = $this->get_next_number( false );
$next_number_est = $this->get_next_number( false, 'est' );


?>

<div style="" class='wrap'>

    <?php echo $wpc_client->get_plugin_logo_block() ?>


    <div class="wpc_clear"></div>

    <div id="container23">

        <ul class="menu">
            <?php echo $this->gen_tabs_menu() ?>
        </ul>

        <span class="wpc_clear"></span>

        <div class="content23 news" style="width: 100%; float: left;">

            <h3><?php _e( 'Settings', WPC_INV_TEXT_DOMAIN ) ?>:</h3>


            <?php if ( !empty( $_GET['msg'] ) ) { ?>
                <div id="message" class="updated wpc_notice fade">
                    <p>
                    <?php
                        switch( $_GET['msg'] ) {
                            case 'u':
                                _e( 'Settings Updated.', WPC_INV_TEXT_DOMAIN );
                                break;
                        }
                    ?>
                    </p>
                </div>
            <?php } ?>

            <form action="" method="post" name="wpc_settings" id="wpc_settings" >
                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'Preferences', WPC_INV_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">

                            <tr valign="top">
                                <th scope="row">
                                    <label><?php _e( 'Invoice Number Preview:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <span id="number_preview"></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="prefix"><?php _e( 'Invoice Prefix:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <input type="text" name="settings[prefix]" id="prefix" value="<?php echo ( isset( $wpc_invoicing['prefix'] ) ) ? $wpc_invoicing['prefix'] : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( 'This prefix will be added to Invoice number', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="next_number"><?php _e( 'Invoice Next Number:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <input type="text" name="settings[next_number]" id="next_number" value="<?php echo ( isset( $next_number ) ) ? $next_number : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( 'The next INV created will be this value', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label><?php _e( 'Display Zeros for Invoice:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="settings[display_zeros]" id="display_zeros" value="yes" <?php echo ( !isset( $wpc_invoicing['display_zeros'] ) || 'yes' == $wpc_invoicing['display_zeros'] ) ? 'checked' : '' ?> />
                                        <?php _e( 'Display the preceding zeros in the invoice number?', WPC_INV_TEXT_DOMAIN ) ?>
                                    </label>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="digits_count"><?php _e( 'Invoice Number of Digits:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[digits_count]" id="digits_count">
                                        <?php
                                        for( $i = 3; $i < 21 ; $i++ ) {
                                            $selected = '';
                                            if ( ( !isset( $wpc_invoicing['digits_count'] ) && 8 == $i ) || $i == $wpc_invoicing['digits_count'] ) {
                                                $selected = 'selected';
                                            }

                                            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span class="description"><?php _e( 'How may digits would you like in your INV ID numbers?', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label><?php _e( 'Estimate Number Preview:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <span id="number_preview_est"></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="prefix_est"><?php _e( 'Estimate Prefix:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <input type="text" name="settings[prefix_est]" id="prefix_est" value="<?php echo ( isset( $wpc_invoicing['prefix_est'] ) ) ? $wpc_invoicing['prefix_est'] : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( 'This prefix will be added to Estimate number', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="next_number_est"><?php _e( 'Estimate Next Number:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <input type="text" name="settings[next_number_est]" id="next_number_est" value="<?php echo ( isset( $next_number_est ) ) ? $next_number_est : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( 'The next EST created will be this value', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label><?php _e( 'Display Zeros for Estimate:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="settings[display_zeros_est]" id="display_zeros_est" value="yes" <?php echo ( !isset( $wpc_invoicing['display_zeros_est'] ) || 'yes' == $wpc_invoicing['display_zeros_est'] ) ? 'checked' : '' ?> />
                                        <?php _e( 'Display the preceding zeros in the estimate number?', WPC_INV_TEXT_DOMAIN ) ?>
                                    </label>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="digits_count"><?php _e( 'Estimate Number of Digits:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[digits_count_est]" id="digits_count_est">
                                        <?php
                                        for( $i = 3; $i < 21 ; $i++ ) {
                                            $selected = '';
                                            if ( ( !isset( $wpc_invoicing['digits_count_est'] ) && 8 == $i ) || $i == $wpc_invoicing['digits_count_est'] ) {
                                                $selected = 'selected';
                                            }

                                            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span class="description"><?php _e( 'How may digits would you like in your EST ID numbers?', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="rate_capacity"><?php _e( 'Rate capacity:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[rate_capacity]" id="rate_capacity">
                                        <?php
                                        for( $i = 2; $i < 6 ; $i++ ) {
                                            $selected = '';
                                            if ( ( !isset( $wpc_invoicing['rate_capacity'] ) && 2 == $i ) || $i == $wpc_invoicing['rate_capacity'] ) {
                                                $selected = 'selected';
                                            }

                                            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span class="description"><?php _e( 'How may digits after point would you like in rate item?', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="thousands_separator"><?php _e( 'Thousands separator:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <input type="text" name="settings[thousands_separator]" id="thousands_separator" value="<?php echo ( isset( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( 'What kind of thousands separator would you like in rate item?', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>
                            <?php
                                $ver = get_option( 'wp_client_ver' );

                                if ( version_compare( $ver, '3.5.0', '>' ) ) {
                            ?>

                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="currency_symbol"><?php _e( 'Currency Symbol:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="settings[currency_symbol]" id="currency_symbol" value="<?php echo ( isset( $wpc_invoicing['currency_symbol'] ) ) ? $wpc_invoicing['currency_symbol'] : '' ?>" />
                                        </td><span></span>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="currency_symbol_align"><?php _e( 'Display Currency Symbol:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                        </th>
                                        <td>
                                            <select name="settings[currency_symbol_align]" id="currency_symbol_align">
                                                <option value="left" <?php echo ( isset( $wpc_invoicing['currency_symbol_align'] ) && 'left' == $wpc_invoicing['currency_symbol_align'] ) ? 'selected' : '' ?> ><?php _e( 'On The Left', WPC_INV_TEXT_DOMAIN ) ?></option>
                                                <option value="right" <?php echo ( isset( $wpc_invoicing['currency_symbol_align'] ) && 'right' == $wpc_invoicing['currency_symbol_align'] ) ? 'selected' : '' ?>><?php _e( 'On The Right', WPC_INV_TEXT_DOMAIN ) ?></option>
                                            </select>
                                            <span class="description"><span id="symbol_left"></span>10.00<span id="symbol_right"></span></span>
                                        </td>
                                    </tr>
                            <?php
                                }
                            ?>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="send_for_review"><?php _e( 'Send Estimates/Invoices to me for Review?', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[send_for_review]" id="send_for_review" style="width: 100px;">
                                        <option value="no"><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $wpc_invoicing['send_for_review'] ) && 'yes' == $wpc_invoicing['send_for_review'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="send_for_paid"><?php printf( __( 'Send Email for %s After Paid Invoices?', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s']) ?></label>
                                </th>
                                <td>
                                    <select name="settings[send_for_paid]" id="send_for_paid" style="width: 100px;">
                                        <option value="no" <?php echo ( !isset( $wpc_invoicing['send_for_paid'] ) || 'yes' != $wpc_invoicing['send_for_paid'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $wpc_invoicing['send_for_paid'] ) && 'yes' == $wpc_invoicing['send_for_paid'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="notify_payment_made"><?php _e( 'Notify when online payment is made', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[notify_payment_made]" id="notify_payment_made" style="width: 100px;">
                                        <option value="no"><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $wpc_invoicing['notify_payment_made'] ) && 'yes' == $wpc_invoicing['notify_payment_made'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <?php
                                $reminder = ( isset( $wpc_invoicing['reminder_days_enabled'] ) && 'yes' == $wpc_invoicing['reminder_days_enabled'] ) ? true : false ;
                            ?>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="reminder_days_enabled"><?php _e( 'Send reminder emails for invoices?', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[reminder_days_enabled]" id="reminder_days_enabled" style="width: 100px;">
                                        <option value="yes" <?php echo ( $reminder ) ? 'selected="selected"' : '' ?>><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php echo ( !$reminder ) ? 'selected="selected"' : '' ?>><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top" class="wpc_block_reminder_days" <?php echo ( !$reminder ) ? 'style="display: none;"' : '' ?> >
                                <th scope="row">
                                    <label for="reminder_days"><?php _e( 'Send first reminder email:', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[reminder_days]" id="reminder_days">
                                    <?php for( $i = 1; $i < 32; $i++ ) {
                                        $selected = '';
                                        if ( $i == $wpc_invoicing['reminder_days'] ) {
                                            $selected = 'selected';
                                        }
                                        echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                    }
                                    ?>
                                    </select>
                                    <?php _e( 'days of due date', WPC_INV_TEXT_DOMAIN ) ?>
                                </td>
                            </tr>
                            <tr valign="top" class="wpc_block_reminder_days" <?php echo ( !$reminder ) ? 'style="display: none;"' : '' ?> >
                                <th scope="row">
                                    <label for="reminder_one_day"><?php _e( 'Send Final Reminder?', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="settings[reminder_one_day]" id="reminder_one_day" value="yes" <?php echo ( !isset( $wpc_invoicing['reminder_one_day'] ) || 'yes' == $wpc_invoicing['reminder_one_day'] ) ? 'checked' : '' ?> />
                                        <?php _e( 'Send final reminder one day before due date.', WPC_INV_TEXT_DOMAIN ) ?>
                                    </label>
                                </td>
                            </tr>
                            <tr valign="top" class="wpc_block_reminder_days" <?php echo ( !$reminder ) ? 'style="display: none;"' : '' ?> >
                                <th scope="row">
                                    <label for="reminder_after"><?php _e( 'Send Email Reminder every ', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="settings[reminder_after]" id="reminder_after">
                                    <?php for( $i = 0; $i < 32; $i++ ) {
                                        $selected = '';
                                        $reminder_after = ( isset( $wpc_invoicing['reminder_after'] ) ) ? $wpc_invoicing['reminder_after'] : 0 ;
                                        if ( $i == $reminder_after ) {
                                            $selected = 'selected';
                                        }
                                        echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                    }
                                    ?>
                                    </select>
                                    <?php _e( 'day(s) after due date', WPC_INV_TEXT_DOMAIN ) ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label><?php _e( 'Payment Gateways', WPC_INV_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <?php
                                    foreach ( (array)$wpc_gateway_plugins as $code => $plugin ) {
                                        if ( isset( $wpc_gateways['allowed'] ) && in_array( $code, (array) $wpc_gateways['allowed'] ) ) {
                                            $checked = '';
                                            if ( isset( $wpc_invoicing['gateways'] ) && in_array( $code, $wpc_invoicing['gateways'] ) ) {
                                                $checked = 'checked';
                                            }
                                            echo '<label><input type="checkbox" name="settings[gateways][]" value="' . $code .'" ' . $checked .' /> ' . esc_attr( $plugin[1] ) . '</label><br>';
                                        }
                                    }
                                    ?>
                                    <span class="description"><?php echo sprintf( __( 'To add or change payments gateway settings, please look in "%s"', WPC_INV_TEXT_DOMAIN ), '<a href="admin.php?page=wpclients_settings&tab=gateways" >' . __( 'Payment Settings', WPC_INV_TEXT_DOMAIN ) . '</a>' ) ?></span>
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    <label for="cost"><?php _e( 'Payment Description', WPC_INV_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <textarea style="width: 400px;" rows="2" name="settings[description]" id="description" ><?php echo ( isset( $wpc_invoicing['description'] ) && '' != $wpc_invoicing['description'] ) ? $wpc_invoicing['description'] : '' ?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Will be displayed on the payment page.', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="ter_con"><?php _e( 'Terms & Conditions', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <textarea style="width: 400px;" rows="5" name="settings[ter_con]" id="ter_con"><?php echo ( isset( $wpc_invoicing['ter_con'] ) && '' != $wpc_invoicing['ter_con'] ) ? stripslashes( $wpc_invoicing['ter_con'] ) : '' ?></textarea>
                                    <br>
                                    <span class="description"><?php _e( '  >> This template for use in the Estimates/Invoices - will be pre-loaded with this content', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="not_cus"><?php _e( 'Note to Customer', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <textarea style="width: 400px;" rows="5" name="settings[not_cus]" id="not_cus"><?php echo ( isset( $wpc_invoicing['not_cus'] ) && '' != $wpc_invoicing['not_cus'] ) ? stripslashes( $wpc_invoicing['not_cus'] ) : '' ?></textarea>
                                    <br>
                                    <span class="description"><?php _e( '  >> This template for use in the Estimates/Invoices - will be pre-loaded with this content', WPC_INV_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>


                        </table>
                    </div>
                </div>

                <input type='submit' name='update_settings' class='button-primary' value='<?php _e( 'Update Settings', WPC_INV_TEXT_DOMAIN ) ?>' />

            </form>
        </div>
    </div>
</div>


<script type="text/javascript" language="javascript">

    jQuery(document).ready(function(){

        //
        jQuery( '#reminder_days_enabled' ).change( function() {
            if ( 'yes' == jQuery( this ).val() ) {
                jQuery( '.wpc_block_reminder_days' ).css( 'display', 'table-row' );
            } else {
                jQuery( '.wpc_block_reminder_days' ).css( 'display', 'none' );
            }
        });

        //change currency symbol
        jQuery( '#currency_symbol' ).change( function() {
            jQuery( this ).display_symbol();
        });

        //change display currency symbol
        jQuery( '#currency_symbol_align' ).change( function() {
            jQuery( this ).display_symbol();
        });


        //display currency symbol
        jQuery.fn.display_symbol = function () {
            var symbol = jQuery( '#currency_symbol' ).val();
            var align = jQuery( '#currency_symbol_align' ).val();

             jQuery( '#symbol_left' ).html( '' );
             jQuery( '#symbol_right' ).html( '' );

            if ( 'right' != align ) {
                align = 'left';
            }

            jQuery( '#symbol_' + align ).html( symbol );

        };

        jQuery( this ).display_symbol();



        //change number preview for invoice
        jQuery( '#prefix, #next_number ' ).keyup( function() {
            jQuery( this ).gen_number_preview();
        });

        //change number preview for invoice
        jQuery( '#display_zeros, #digits_count' ).change( function() {
            jQuery( this ).gen_number_preview();
        });


        //gen number preview for invoice
        jQuery.fn.gen_number_preview = function () {
            var prefix = jQuery( '#prefix' ).val();
            var next_number = jQuery( '#next_number' ).val();
            var display_zeros = jQuery( '#display_zeros' ).attr( 'checked');
            var digits_count = jQuery( '#digits_count' ).val();

            if ( 'checked' == display_zeros ) {
                next_number = jQuery( this ).str_pad( next_number, digits_count, '0', 'STR_PAD_LEFT' );
            }

            jQuery( '#number_preview' ).html( prefix + next_number );

        };

        //change number preview for estimate
        jQuery( '#prefix_est, #next_number_est' ).keyup( function() {
            jQuery( this ).gen_number_preview_est();
        });

        //change number preview for estimate
        jQuery( '#display_zeros_est, #digits_count_est' ).change( function() {
            jQuery( this ).gen_number_preview_est();
        });


        //gen number preview for estimate
        jQuery.fn.gen_number_preview_est = function () {
            var prefix = jQuery( '#prefix_est' ).val();
            var next_number = jQuery( '#next_number_est' ).val();
            var display_zeros = jQuery( '#display_zeros_est' ).attr( 'checked');
            var digits_count = jQuery( '#digits_count_est' ).val();

            if ( 'checked' == display_zeros ) {
                next_number = jQuery( this ).str_pad( next_number, digits_count, '0', 'STR_PAD_LEFT' );
            }

            jQuery( '#number_preview_est' ).html( prefix + next_number );

        };


        //for add zero in number
        jQuery.fn.str_pad = function ( input, pad_length, pad_string, pad_type ) {

            var half = '', pad_to_go;

            var str_pad_repeater = function(s, len){
                    var collect = '', i;

                    while(collect.length < len) collect += s;
                    collect = collect.substr(0,len);

                    return collect;
                };

            if (pad_type != 'STR_PAD_LEFT' && pad_type != 'STR_PAD_RIGHT' && pad_type != 'STR_PAD_BOTH') { pad_type = 'STR_PAD_RIGHT'; }
            if ((pad_to_go = pad_length - input.length) > 0) {
                if (pad_type == 'STR_PAD_LEFT') { input = str_pad_repeater(pad_string, pad_to_go) + input; }
                else if (pad_type == 'STR_PAD_RIGHT') { input = input + str_pad_repeater(pad_string, pad_to_go); }
                else if (pad_type == 'STR_PAD_BOTH') {
                    half = str_pad_repeater(pad_string, Math.ceil(pad_to_go/2));
                    input = half + input + half;
                    input = input.substr(0, pad_length);
                }
            }

            return input;
        };


        jQuery( this ).gen_number_preview();
        jQuery( this ).gen_number_preview_est();

    });

</script>
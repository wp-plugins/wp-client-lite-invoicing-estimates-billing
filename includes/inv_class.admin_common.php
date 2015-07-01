<?php

if ( !class_exists( 'WPC_INV_Admin_Common' ) ) {

    class WPC_INV_Admin_Common extends WPC_INV_Common {


        /**
        * constructor
        **/
        function inv_admin_common_construct() {

            $this->inv_common_construct();


            //add ez hub settings
            add_filter( 'wpc_client_ez_hub_invoicing_list', array( &$this, 'add_ez_hub_settings' ), 12, 4 );
            add_filter( 'wpc_client_get_ez_shortcode_invoicing_list', array( &$this, 'get_ez_shortcode_invoicing_list' ), 10, 2 );
            add_filter( 'wpc_client_get_shortcode_elements', array( &$this, 'get_shortcode_element' ), 10 );
            add_filter( 'wp_client_capabilities_maps', array( &$this, 'add_capabilities_maps' ), 10 );

        }


        /*
        * Add ez hub settings
        */
        function add_ez_hub_settings( $return, $hub_settings = array(), $item_number = 0, $type = 'ez' ) {
            global $wpc_client;
            $title = __( 'Invoicing List', WPC_INV_TEXT_DOMAIN ) ;
            $text_copy = '{invoicing_list_' . $item_number . '}' ;

            ob_start();
            ?>

                <div class="inside">
                    <table class="form-table">
                        <tbody>
                            <?php if( isset( $type ) && 'ez' == $type ) { ?>
                                <tr>
                                    <td style="width:250px;">
                                        <label for="invoicing_list_text_<?php echo $item_number ?>"><?php _e( 'Text: "Invoicing List"',WPC_INV_TEXT_DOMAIN ) ?></label>
                                    </td>
                                    <td>
                                        <input type="text" name="hub_settings[<?php echo $item_number ?>][invoicing_list][text]" id="invoicing_list_text_<?php echo $item_number ?>" style="width: 300px;" value="<?php echo ( isset( $hub_settings['text'] ) ) ? $hub_settings['text'] : __( 'Invoicing List', WPC_INV_TEXT_DOMAIN ) ?>">
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td style="width:250px;">
                                        <label><?php _e( 'Placeholder',WPC_INV_TEXT_DOMAIN ) ?></label>
                                    </td>
                                    <td>
                                        <?php echo $text_copy ?><a class="wpc_shortcode_clip_button" href="javascript:void(0);" title="<?php _e( 'Click to copy', WPC_INV_TEXT_DOMAIN ) ?>" data-clipboard-text="<?php echo $text_copy ?>"><img src="<?php echo $wpc_client->plugin_url . "images/zero_copy.png"; ?>" border="0" width="16" height="16" alt="copy_button.png"></a><br><span class="wpc_complete_copy"><?php _e( 'Placeholder was copied', WPC_INV_TEXT_DOMAIN ) ?></span>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td style="width:250px;">
                                    <label for="invoicing_list_type_<?php echo $item_number ?>"><?php _e( 'Type', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][type]" id="invoicing_list_type_<?php echo $item_number ?>">
                                        <option value="invoice" <?php echo ( !isset( $hub_settings['type'] ) || 'invoice' == $hub_settings['type'] ) ? 'selected' : '' ?>><?php _e( 'Invoice', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="estimate" <?php echo ( isset( $hub_settings['type'] ) && 'estimate' == $hub_settings['type'] ) ? 'selected' : '' ?>><?php _e( 'Estimate', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:250px;">
                                    <label for="invoicing_list_show_date_<?php echo $item_number ?>"><?php _e( 'Show Date', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][show_date]" id="invoicing_list_show_date_<?php echo $item_number ?>">
                                        <option value="no" <?php echo ( !isset( $hub_settings['show_date'] ) || 'no' == $hub_settings['show_date'] ) ? 'selected' : '' ?>><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $hub_settings['show_date'] ) && 'yes' == $hub_settings['show_date'] ) ? 'selected' : '' ?>><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:250px;">
                                    <label for="invoicing_list_show_description_<?php echo $item_number ?>"><?php _e( 'Show Description', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][show_description]" id="invoicing_list_show_description_<?php echo $item_number ?>">
                                        <option value="no" <?php echo ( !isset( $hub_settings['show_description'] ) || 'no' == $hub_settings['show_description'] ) ? 'selected' : '' ?>><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $hub_settings['show_description'] ) && 'yes' == $hub_settings['show_description'] ) ? 'selected' : '' ?>><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:250px;">
                                    <label for="invoicing_list_show_type_payment_<?php echo $item_number ?>"><?php _e( 'Show Type Payment', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][show_type_payment]" id="invoicing_list_show_type_payment_<?php echo $item_number ?>">
                                        <option value="no" <?php echo ( !isset( $hub_settings['show_type_payment'] ) || 'no' == $hub_settings['show_type_payment'] ) ? 'selected' : '' ?>><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $hub_settings['show_type_payment'] ) && 'yes' == $hub_settings['show_type_payment'] ) ? 'selected' : '' ?>><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:250px;">
                                    <label for="invoicing_list_show_invoicing_currency_<?php echo $item_number ?>"><?php _e( 'Show Total Amount', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][show_invoicing_currency]" id="invoicing_list_show_invoicing_currency_<?php echo $item_number ?>">
                                        <option value="no" <?php echo ( !isset( $hub_settings['show_invoicing_currency'] ) || 'no' == $hub_settings['show_invoicing_currency'] ) ? 'selected' : '' ?>><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $hub_settings['show_invoicing_currency'] ) && 'yes' == $hub_settings['show_invoicing_currency'] ) ? 'selected' : '' ?>><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:250px;">
                                    <label for="invoicing_list_pay_now_links_<?php echo $item_number ?>"><?php _e( 'Show "Pay Now" Links', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][pay_now_links]" id="invoicing_list_pay_now_links_<?php echo $item_number ?>">
                                        <option value="no" <?php echo ( !isset( $hub_settings['pay_now_links'] ) || 'no' == $hub_settings['pay_now_links'] ) ? 'selected' : '' ?>><?php _e( 'No', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="yes" <?php echo ( isset( $hub_settings['pay_now_links'] ) && 'yes' == $hub_settings['pay_now_links'] ) ? 'selected' : '' ?>><?php _e( 'Yes', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="invoicing_list_status_<?php echo $item_number ?>"><?php _e( 'Status', WPC_INV_TEXT_DOMAIN ) ?></label>
                                </td>
                                <td>
                                    <select name="hub_settings[<?php echo $item_number ?>][invoicing_list][status]" id="invoicing_list_status_<?php echo $item_number ?>">
                                        <option value="" <?php echo ( !isset( $hub_settings['status'] ) || '' == $hub_settings['status'] ) ? 'selected' : '' ?>><?php _e( '---', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="sent" <?php echo ( isset( $hub_settings['status'] ) && 'sent' == $hub_settings['status'] ) ? 'selected' : '' ?>><?php _e( 'Open', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="draft" <?php echo ( isset( $hub_settings['status'] ) && 'draft' == $hub_settings['status'] ) ? 'selected' : '' ?>><?php _e( 'Draft', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="partial" <?php echo ( isset( $hub_settings['status'] ) && 'partial' == $hub_settings['status'] ) ? 'selected' : '' ?>><?php _e( 'Partial', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="paid" <?php echo ( isset( $hub_settings['status'] ) && 'paid' == $hub_settings['status'] ) ? 'selected' : '' ?>><?php _e( 'Paid', WPC_INV_TEXT_DOMAIN ) ?></option>
                                        <option value="refunded" <?php echo ( isset( $hub_settings['status'] ) && 'refunded' == $hub_settings['status'] ) ? 'selected' : '' ?>><?php _e( 'Refunded', WPC_INV_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php
            $content = ob_get_contents();
            ob_end_clean();

            return array( 'title' => $title, 'content' => $content, 'text_copy' => $text_copy );
        }


        /*
        * Add ez shortcode
        */
        function get_ez_shortcode_invoicing_list( $tabs_items, $hub_settings = array() ) {
            $temp_arr = array();
            $temp_arr['menu_items']['invoicing_list'] = ( isset( $hub_settings['text'] ) ) ? $hub_settings['text'] : '';

            $attrs = '';

            if ( isset( $hub_settings['type'] ) && '' != $hub_settings['type'] ) {
                $attrs .= ' type="' . $hub_settings['type'] . '" ';
            } else {
                $attrs .= ' type="invoice" ';
            }

            if ( isset( $hub_settings['status'] ) && '' != $hub_settings['status'] ) {
                $attrs .= ' status="' . $hub_settings['status'] . '" ';
            } else {
                $attrs .= ' status="" ';
            }

            if ( isset( $hub_settings['show_date'] ) && '' != $hub_settings['show_date'] ) {
                $attrs .= ' show_date="' . $hub_settings['show_date'] . '" ';
            } else {
                $attrs .= ' show_date="no" ';
            }

            if ( isset( $hub_settings['show_description'] ) && '' != $hub_settings['show_description'] ) {
                $attrs .= ' show_description="' . $hub_settings['show_description'] . '" ';
            } else {
                $attrs .= ' show_description="no" ';
            }

            if ( isset( $hub_settings['show_type_payment'] ) && '' != $hub_settings['show_type_payment'] ) {
                $attrs .= ' show_type_payment="' . $hub_settings['show_type_payment'] . '" ';
            } else {
                $attrs .= ' show_type_payment="no" ';
            }

            if ( isset( $hub_settings['show_invoicing_currency'] ) && '' != $hub_settings['show_invoicing_currency'] ) {
                $attrs .= ' show_invoicing_currency="' . $hub_settings['show_invoicing_currency'] . '" ';
            } else {
                $attrs .= ' show_invoicing_currency="no" ';
            }

            if ( isset( $hub_settings['pay_now_links'] ) && '' != $hub_settings['pay_now_links'] ) {
                $attrs .= ' pay_now_links="' . $hub_settings['pay_now_links'] . '" ';
            } else {
                $attrs .= ' pay_now_links="no" ';
            }

            $temp_arr['page_body'] = '[wpc_client_invoicing_list ' . $attrs . ' /]';

            $tabs_items[] = $temp_arr;

            return $tabs_items;
        }


        /*
        * get shortcode element
        */
        function get_shortcode_element( $elements ) {
            $elements['invoicing_list'] = __( 'Invoicing List', WPC_INV_TEXT_DOMAIN );
            return $elements;
        }


        /*
        * add capability for maneger
        */
        function add_capabilities_maps( $capabilities_maps ) {
            global $wpc_client;

            $staff_additional_caps = array(
                'wpc_view_invoices'               => array( 'cap' => false, 'label' => "View " . $wpc_client->custom_titles['client']['p'] . " invoices" ),
                'wpc_paid_invoices'               => array( 'cap' => false, 'label' => "Paid " . $wpc_client->custom_titles['client']['p'] . " invoices" )
            );

            if( isset( $capabilities_maps['wpc_client_staff'] ) )
                $capabilities_maps['wpc_client_staff'] = array_merge( $capabilities_maps['wpc_client_staff'], $staff_additional_caps );
            return $capabilities_maps;
        }


    //end class
    }

}

?>

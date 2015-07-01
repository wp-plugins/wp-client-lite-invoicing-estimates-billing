<?php


if ( !class_exists( "WPC_INV_Install" ) ) {

    class WPC_INV_Install extends WPC_INV_Common {

        /**
        * PHP 5 constructor
        **/
        function __construct() {


        }


        /*
        * Pre-set all plugin's pages
        */
        function pre_set_pages() {
            $wpc_pages = array(
                array(
                    'title'     => __( 'Invoicing', WPC_INV_TEXT_DOMAIN ),
                    'name'      => 'Invoicing',
                    'desc'      => __( 'Page content: [wpc_client_invoicing]', WPC_INV_TEXT_DOMAIN ),
                    'id'        => 'invoicing_page_id',
                    'old_id'    => 'invoicing',
                    'shortcode' => true,
                    'content'   => '[wpc_client_invoicing]',
                ),
                array(
                    'title'     => __( 'Invoicing List', WPC_INV_TEXT_DOMAIN ),
                    'name'      => 'Invoicing List',
                    'desc'      => __( 'Page content: [wpc_client_invoicing_list]', WPC_INV_TEXT_DOMAIN ),
                    'id'        => 'invoicing_list_page_id',
                    'old_id'    => '',
                    'shortcode' => true,
                    'content'   => '[wpc_client_invoicing_list]',
                ),
            );

            return $wpc_pages;
        }


        /*
        * Create DB tables
        */
        function creating_db() {
            global $wpdb;


        }



        /*
        * Updating to new version
        */
        function updating( $ver ) {
            global $wpdb;

            //updateing DB for new version

            if ( version_compare( $ver, '1.0.0', '<' ) ) {


            }

            update_option( 'wp_client_lite_ver', WPC_CLIENT_LITE_VER );


        }


        /**
        * Set Default Settings
        **/
        function default_settings() {

            $wpc_default_settings['invoicing'] = array(
                'send_for_review'           => 'no',
                'send_for_paid'             => 'no',
                'prefix'                    => '',
                'next_number'               => '',
                'rate_capacity'             => 2,
                'thousands_separator'       => '',
                'reminder_days_enabled'     => 'no',
                'reminder_days'             => 1,
                'display_zeros'             => 'yes',
                'digits_count'              => 8,
                'notify_payment_made'       => 'no',
                'currency_symbol'           => '$',
                'ter_con'                   => 'Thank you, we really appreciate your business. Please send payment within 21 days of receiving this invoice.',
                'not_cus'                   => 'Thanks for your business!',

            );

            //Set settings
            foreach( $wpc_default_settings as $key => $values ) {
                add_option( 'wpc_' . $key, $values );

                if ( is_array( $values ) && count( $values ) ) {
                    $current_setting = get_option( 'wpc_' . $key );
                    $new_setting = array_merge( $values, $current_setting );
                    update_option( 'wpc_' . $key, $new_setting );
                }
            }

        }


        /**
        * Set Default Templates
        **/
        function default_templates() {

    //email when
    $wpc_default_templates['templates_emails']['inv_not'] = array(
        'subject'               => 'Invoice for {client_name} - {business_name}',
        'body'                  => '<p>Hi,</p>
<p>Thanks for your business.</p>
<p>Your invoice is available in your Client Portal, and is attached with this email.</p>
<p>Looking forward to working with you for many years.</p>
<p>Thanks,</p>
<p>{business_name}</p>
<p>----</p>
<p>You can login here: {login_url}</p>',
    );

    //email when
    $wpc_default_templates['templates_emails']['est_not'] = array(
        'subject'               => 'Estimate for {client_name} - {business_name}',
        'body'                  => '<p>Hi,</p>
<p>Thanks for your business.</p>
<p>Your estimate is available in your Client Portal, and is attached with this email.</p>
<p>Looking forward to working with you for many years.</p>
<p>Thanks,</p>
<p>{business_name}</p>
<p>----</p>
<p>You can login here: {login_url}</p>',
    );

    //email when
    $wpc_default_templates['templates_emails']['pay_tha'] = array(
        'subject'               => 'Thanks for you payment - {business_name}',
        'body'                  => "Hi,

We have received your payment.

Thanks for the payment and your business.

Please don't hesitate to call or email at anytime with questions,

{business_name}",
    );

    //email when
    $wpc_default_templates['templates_emails']['admin_notify'] = array(
        'subject'               => 'Payment made by {client_name}',
        'body'                  => '<p>Payment Notification:</p>
<p>{client_name} has paid an invoice online.</p>',
    );

    //email when
    $wpc_default_templates['templates_emails']['pay_rem'] = array(
        'subject'               => 'Payment reminder for {client_name} - {business_name}',
        'body'                  => '<p>Hi,</p>
<p>May we kindly remind you that your invoice with us is overdue. If you have already paid for this invoice, accept our apologies and ignore this reminder.</p>
<p>Thanks in advance for the payment,</p>
<p>{business_name}</p>',
    );
    //email when
    $wpc_default_templates['templates_emails']['est_declined'] = array(
        'subject'               => 'Estimate #{invoice_number} was declined by {client_name}',
        'body'                  => '<p>Payment Notification:</p>
<p>{client_name} was declined #{invoice_number} estimate by reason of: {decline_note}.</p>
<p>To view the entire thread of messages and send a reply, click <a href="{admin_url}">HERE</a></p>',
    );



            //Set templates
            foreach( $wpc_default_templates as $key => $values ) {
                add_option( 'wpc_' . $key, $values );

                if ( is_array( $values ) && count( $values ) ) {
                    $current_setting = get_option( 'wpc_' . $key );
                    $new_setting = array_merge( $values, $current_setting );
                    update_option( 'wpc_' . $key, $new_setting );
                }
            }

        }


        /*
        * Uninstall extension
        */
        function uninstall() {
            global $wpdb, $wpc_client;

            /*
            * Delete all tables
            */


            $wpc_client->cc_delete_settings( 'invoice_settings' );
            $wpc_client->cc_delete_settings( 'invoicing' );



            //deactivate the extension
            $plugins = get_option( 'active_plugins' );
            if ( is_array( $plugins ) && 0 < count( $plugins ) ) {
                $new_plugins = array();
                foreach( $plugins as $plugin )
                    if ( 'wp-client-lite-invoicing-estimates-billing/wp-client-lite-invoicing-estimates-billing.php' != $plugin )
                        $new_plugins[] = $plugin;
            }
            update_option( 'active_plugins', $new_plugins );



        }




    //end class
    }

}

?>
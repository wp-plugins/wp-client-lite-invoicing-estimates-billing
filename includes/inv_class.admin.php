<?php

if ( !class_exists( 'WPC_INV_Admin' ) ) {

    class WPC_INV_Admin extends WPC_INV_Admin_Meta_Boxes {

        /**
        * PHP 5 constructor
        **/
        function __construct() {

            $this->inv_common_construct();
            $this->inv_admin_common_construct();
            $this->meta_construct();

            //add admin submenu
            add_filter( 'wpc_client_admin_submenus', array( &$this, 'add_admin_submenu' ) );

            add_action( 'admin_enqueue_scripts', array( &$this, 'load_css_js' ), 100 );

            add_action( 'wpc_client_dashboard_tables', array( &$this, 'show_dashboard_tables' ) );


            //notice for install pages
            add_action( 'wp_client_admin_notices', array( &$this, 'admin_notices' ), 1 );


            add_filter( 'wpc_client_pre_set_pages_array', array( &$this, 'pre_set_pages' ) );

            //add subsubmenu
            add_filter( 'wpc_client_add_subsubmenu', array( &$this, 'add_subsubmenu' ) );

            //uninstall
            add_action( 'wp_client_uninstall', array( &$this, 'uninstall_extension' ) );

            //delete client
            add_action( 'wpc_client_delete_client', array( &$this, 'delete_client' ) );


            add_action( 'wpc_invoice_cron', array( &$this, 'wpc_invoice_cron' ) );

            //add_filter( 'cron_schedules', array( &$this, 'cron_add_five_min' ) );

            //add array help
            add_filter( 'wpc_set_array_help', array( &$this, 'wpc_set_array_help' ), 10, 2 );

        }


        function wpc_set_array_help( $array_help, $method ) {
            global $wpc_client;
            switch( $method ) {
                case '_add_wpclients_invoicing_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . sprintf( __( 'You will see a list of all existing invoices in this tab. You can view/edit each invoice, download as a PDF, mark them as "Void", and delete them permanently. You can also filter the list by invoice status such as "Open" or "Pending", and by assigned %s', WPC_CLIENT_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s'] ) . '</p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002674-" target="_blank">' . __( 'Estimates/Invoices Overview', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002673-quick-start" target="_blank">' . __( 'Estimates/Invoices Quick Start Guide', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;

                case '_add_wpclients_invoicinginvoice_edit_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . sprintf( __( 'When you create a new Invoice, you are given multiple options. You can assign to specific %s or %s. Additionally, you can choose to drag-and-drop your previously created Items into the Invoice, or add new Items. You can also set a due date, and type a unique message for the %s.', WPC_CLIENT_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['p'], $wpc_client->custom_titles['circle']['p'], $wpc_client->custom_titles['client']['s'] ) . '</p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002674-" target="_blank">' . __( 'Estimates/Invoices Overview', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002673-quick-start" target="_blank">' . __( 'Estimates/Invoices Quick Start Guide', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;

                case '_add_wpclients_invoicingestimates_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . sprintf( __( 'You will see a list of all existing estimates in this tab. You can view/edit each estimate, download as a PDF, and delete them permanently. Additionally, choosing the "Convert to Invoice" option will automatically switch the estimate over to a invoice, keeping it\'s %1$s assignment. You can also filter the list by assigned %1$s.', WPC_CLIENT_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s'] ) . '</p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002674-" target="_blank">' . __( 'Estimates/Invoices Overview', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002673-quick-start" target="_blank">' . __( 'Estimates/Invoices Quick Start Guide', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;

                case '_add_wpclients_invoicingestimate_edit_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . __( 'Estimates can be thought of in a very similar manner as Invoices, and almost as "pre-Invoices". An estimate consists of one or more items with their associated title, description and price point. You can set a date for the estimate is good until, add tax to the estimate, add a discount to the estimate, set your terms and conditions and add a special note to the customer as needed.', WPC_CLIENT_TEXT_DOMAIN ) . '</p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002674-" target="_blank">' . __( 'Estimates/Invoices Overview', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002673-quick-start" target="_blank">' . __( 'Estimates/Invoices Quick Start Guide', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;

                case '_add_wpclients_invoicinginvoicing_items_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . __( 'The building blocks for Estimates and Invoices are what is known as Items. Items can be thought of as a line item title and description that quantifies a billable service or particular product or sku. The items created on this page are reusable, so for example you can create an item for "One Billable Hour of Design Work", and add it to as many Estimates/Invoices as you like, including adding multiples of each item to an Estimate or Invoice.', WPC_CLIENT_TEXT_DOMAIN ) . '</p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002674-" target="_blank">' . __( 'Estimates/Invoices Overview', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002673-quick-start" target="_blank">' . __( 'Estimates/Invoices Quick Start Guide', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;

                case '_add_wpclients_invoicinginvoicing_taxes_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . __( 'You can create and adjust your desired taxes from this page. You can setup multiple tax levels if desired, and then choose the appropriate tax on an individual Estimate/Invoice basis. The default tax rate is calculated in percent.', WPC_CLIENT_TEXT_DOMAIN ) . '</p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002674-" target="_blank">' . __( 'Estimates/Invoices Overview', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>' .
                                        '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000002673-quick-start" target="_blank">' . __( 'Estimates/Invoices Quick Start Guide', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;

                case '_add_wpclients_settingsinvoicing_page_help' :
                    $array_help = array(
                        'tabs' =>
                            array(
                                array(
                                    'id' => 'dr-main',
                                    'title' => __( 'MAIN', WPC_CLIENT_TEXT_DOMAIN ),
                                    'content' => '<p>' . sprintf( __( 'Adjust various Estimates/Invoices settings from this tab, including how the Estimate/Invoice numbers are formatted, what payment gateways your %1$s can use, what currency symbol to use, and whether or not you and your %1$s will receive email notifications related to Estimates and Invoices.', WPC_CLIENT_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['p'] ) . '</p>' .
                                    '<p><a href="https://support.webportalhq.com/support/solutions/articles/1000011816-extensions" target="_blank">' . __( 'Extensions Basics', WPC_CLIENT_TEXT_DOMAIN ) . '</a></p>',
                                ),
                            ),
                            'sidebar' => '',
                            'clear' => true
                    ) ;
                break;
            }
            return $array_help ;
        }


        function add_subsubmenu( $subsubmenu ) {
            $add_items = array(
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => __( 'Add Invoice', WPC_INV_TEXT_DOMAIN ),
                    'capability'        => ( current_user_can( 'wpc_create_invoices' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_invoicing&tab=invoice_edit',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => __( 'Estimates', WPC_INV_TEXT_DOMAIN ),
                    'capability'        => ( current_user_can( 'wpc_create_estimates' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_invoicing&tab=estimates',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => __( 'Add Estimate', WPC_INV_TEXT_DOMAIN ),
                    'capability'        => ( current_user_can( 'wpc_create_estimates' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_invoicing&tab=estimate_edit',
                    ),

                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => __( 'Settings', WPC_INV_TEXT_DOMAIN ),
                    'capability'        => ( current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_invoicing&tab=settings',
                    ),


                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Recurring Profiles', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_create_repeat_invoices' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#repeat_invoices',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Add Recurring Profile', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_create_repeat_invoices' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#repeat_invoices',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Accumulating Profiles', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_create_accum_invoices' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#accum_invoices',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Add Accumulating Profile', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_create_accum_invoices' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#accum_invoices',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Preset Items', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_modify_items' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#invoicing_items',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Preset Taxes', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_modify_taxes' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#invoicing_taxes',
                    ),
                array(
                    'parent_slug'       => 'admin.php?page=wpclients_invoicing',
                    'menu_title'        => '<span class="wpc_pro_menu_grey">' . __( 'Custom Fields', WPC_INV_TEXT_DOMAIN ) . '</span> <span class="wpc_pro_menu_text">Pro</span>',
                    'capability'        => ( current_user_can( 'wpc_create_inv_custom_fields' ) || current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) ? 'yes' : 'no',
                    'slug'              => 'admin.php?page=wpclients_pro_features#invoicing_custom_fields',
                    ),
            );

            $subsubmenu = array_merge( $subsubmenu, $add_items );

            return $subsubmenu;
        }


        function cron_add_five_min( $schedules ) {
            $schedules['five_min'] = array(
                'interval' => 30,
                'display' => __( 'Once Five Min' )
            );
            return $schedules;
        }

        function wpc_invoice_cron() {
            global $wpdb;

            $accum_inv_ids = $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_next_create_inv' AND pm.meta_value < UNIX_TIMESTAMP(NOW()) )
                LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_recurring_type' )
                WHERE p.post_status = 'pending' OR pm2.meta_value != 'auto_charge'
                " );
            foreach ( $accum_inv_ids as $accum_inv_id ) {
                $this->create_inv_from_profile( $accum_inv_id );
            }


            $inv_ids = $wpdb->get_results( "SELECT p.ID as id, pm1.meta_value as late_fee, pm2.meta_value as total
                                        FROM {$wpdb->posts} p
                                        INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                                        INNER JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_due_date' AND pm.meta_value < UNIX_TIMESTAMP(NOW()) )
                                        INNER JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_late_fee' AND pm1.meta_value > 0 )
                                        INNER JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_total' )
                                        LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_added_late_fee' )
                                        WHERE pm3.meta_value IS NULL AND p.post_type = 'wpc_invoice'
                                        ", ARRAY_A );
            foreach ( $inv_ids as $inv_id ) {
                update_post_meta( $inv_id['id'], 'wpc_inv_added_late_fee', $inv_id['late_fee'] ) ;
                update_post_meta( $inv_id['id'], 'wpc_inv_total', $inv_id['total'] + $inv_id['late_fee'] ) ;
            }

        }


        /*
        * Function activation
        */
        function activation() {

            add_option( 'wpc_inv_ver', '1.3.6' );
            add_option( 'wpc_inv_lite_ver', WPC_INV_LITE_VER );

            $ver = get_option( 'wpc_inv_lite_ver' );

            //run CRON reminder
            if ( !wp_next_scheduled( 'wpc_client_inv_send_reminder' ) )
                wp_schedule_event( time(), 'twicedaily', 'wpc_client_inv_send_reminder' );



            //delete invoice cron
            if ( wp_next_scheduled( 'wpc_invoice_cron' ) ) {
                wp_clear_scheduled_hook('wpc_invoice_cron');
            }

            //add invoice cron
            wp_schedule_event( time(), 'hourly', 'wpc_invoice_cron' );

            //include installation class
            include_once $this->extension_dir . 'includes/inv_class.install.php';
            $wpc_inv_install = new WPC_INV_Install();

            //create DB
            $wpc_inv_install->creating_db();
            $wpc_inv_install->default_settings();
            $wpc_inv_install->default_templates();
            $wpc_inv_install->updating( $ver );

            //update rewrite rules
            flush_rewrite_rules( false );
        }


        /*
        * Function unisntall
        */
        function uninstall_extension() {

           //delete invoice cron
            wp_clear_scheduled_hook('wpc_invoice_cron');

            //include installation class
            include_once $this->extension_dir . 'includes/inv_class.install.php';
            $wpc_inv_install = new WPC_INV_Install();

            //delete all data
            $wpc_inv_install->uninstall();
        }

        /*
        * Pre set pages
        */
        function pre_set_pages( $wpc_pre_pages_array ) {
            //include installation class
            include_once $this->extension_dir . 'includes/inv_class.install.php';
            $wpc_inv_install = new WPC_INV_Install();

            //pre set pages
            if ( is_array( $wpc_pre_pages_array ) ) {
                $wpc_pre_pages_array = array_merge( $wpc_pre_pages_array, $wpc_inv_install->pre_set_pages() );
            }

            return $wpc_pre_pages_array;
        }


        /*
        * Function for adding admin submenu
        */
        function add_admin_submenu( $plugin_submenus ) {
            global $current_user;

            $cap = "manage_options";

            $plugin_submenus['separator_2'] = array(
                'page_title'        => '',
                'menu_title'        => '- - - - - - - - - -',
                'slug'              => '#',
                'capability'        => $cap,
                'function'          => '',
                'hidden'            => false,
                'real'              => false,
                'order'             => 100,
            );

            $plugin_submenus['wpclients_invoicing'] = array(
                'page_title'        => __( 'Estimates/Invoices', WPC_INV_TEXT_DOMAIN ),
                'menu_title'        => __( 'Estimates/Invoices', WPC_INV_TEXT_DOMAIN ),
                'slug'              => 'wpclients_invoicing',
                'capability'        => $cap,
                'function'          => array( &$this, 'wpc_invoicing_pages' ),
                'hidden'            => false,
                'real'              => true,
                'order'             => 120,
            );

            return $plugin_submenus;
        }


        /*
        * display Invoicing page
        */
        function wpc_invoicing_pages() {
            global $wpc_client;
                if ( !isset( $_GET['tab'] ) || 'invoices' == $_GET['tab'] )
                    include $this->extension_dir . 'includes/admin/invoices.php';
                elseif ( isset( $_GET['tab'] ) && 'invoice_edit' == $_GET['tab'] )
                    include $this->extension_dir . 'includes/admin/invoice_edit.php';
                elseif ( isset( $_GET['tab'] ) && 'estimates' == $_GET['tab'] )
                    include $this->extension_dir . 'includes/admin/estimates.php';
                elseif ( isset( $_GET['tab'] ) && 'estimate_edit' == $_GET['tab'] )
                    include $this->extension_dir . 'includes/admin/estimate_edit.php';
                elseif ( isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] )
                    include $this->extension_dir . 'includes/admin/settings_invoicing.php';
                elseif ( isset( $_GET['tab'] ) && 'pro_features' == $_GET['tab'] )
                    include $this->extension_dir . 'includes/admin/_pro_features.php';
        }


        /**
         * Gen tabs manu
         */
        function gen_tabs_menu() {

            $tabs = '';
            $active = '';

                $active = ( !isset( $_GET['tab'] ) || 'invoices' == $_GET['tab'] || 'invoice_edit' == $_GET['tab'] ) ? 'class="active"' : '';
                $tabs .= '<li id="tutorials" ' . $active . ' ><a href="admin.php?page=wpclients_invoicing" >' . __( 'Invoices', WPC_INV_TEXT_DOMAIN ) . '</a></li>';

                $active = ( isset( $_GET['tab'] ) && ( 'estimates' == $_GET['tab'] || 'estimate_edit' == $_GET['tab'] ) ) ? 'class="active"' : '';
                $tabs .= '<li id="news" ' . $active . ' ><a href="admin.php?page=wpclients_invoicing&tab=estimates" >' . __( 'Estimates', WPC_INV_TEXT_DOMAIN ). '</a></li>';

                $active = '';
                $tabs .= '<li id="news" ' . $active . ' ><a href="admin.php?page=wpclients_payments&filter_function=invoicing&change_filter=function" >' . __( 'Payments', WPC_INV_TEXT_DOMAIN ). '</a></li>';


                $active = ( isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] ) ? 'class="active"' : '';
                $tabs .= '<li id="settings" ' . $active . ' ><a href="admin.php?page=wpclients_invoicing&tab=settings" >' . __( 'Settings', WPC_INV_TEXT_DOMAIN ) . '</a></li>';


                $active = ( isset( $_GET['tab'] ) && 'pro_features' == $_GET['tab'] ) ? 'class="active"' : '';
                $tabs .= '<li id="pro_features" ' . $active . ' ><a href="admin.php?page=wpclients_invoicing&tab=pro_features" style="color: #000 !important;" >' . __( 'Pro Features', WPC_INV_TEXT_DOMAIN ) . '</a></li>';




                $active = ( isset( $_GET['tab'] ) && ( 'repeat_invoices' == $_GET['tab'] || 'repeat_invoice_edit' == $_GET['tab'] ) ) ? 'class="active"' : '';
                $tabs .= '<li id="news" class="wpc_pro_tab" ><a href="admin.php?page=wpclients_invoicing&tab=pro_features#repeat_invoices" >' . __( 'Recurring Profiles', WPC_INV_TEXT_DOMAIN ) . '<span class="wpc_pro_tab_text"> Pro</span></a></li>';

                $active = ( isset( $_GET['tab'] ) && ( 'accum_invoices' == $_GET['tab'] || 'accum_invoice_edit' == $_GET['tab'] ) ) ? 'class="active"' : '';
                $tabs .= '<li id="news" class="wpc_pro_tab" ><a href="admin.php?page=wpclients_invoicing&tab=pro_features#accum_invoices" >' . __( 'Accumulating Profiles', WPC_INV_TEXT_DOMAIN ) . '<span class="wpc_pro_tab_text"> Pro</span></a></li>';


                $active = ( isset( $_GET['tab'] ) && 'invoicing_items' == $_GET['tab'] ) ? 'class="active"' : '';
                $tabs .= '<li id="news" class="wpc_pro_tab" ><a href="admin.php?page=wpclients_invoicing&tab=pro_features#invoicing_items" >' . __( 'Items', WPC_INV_TEXT_DOMAIN ). '<span class="wpc_pro_tab_text"> Pro</span></a></li>';

                $active = ( isset( $_GET['tab'] ) && 'invoicing_taxes' == $_GET['tab'] ) ? 'class="active"' : '';
                $tabs .= '<li id="news" class="wpc_pro_tab" ><a href="admin.php?page=wpclients_invoicing&tab=pro_features#invoicing_taxes" >' . __( 'Taxes', WPC_INV_TEXT_DOMAIN ). '<span class="wpc_pro_tab_text"> Pro</span></a></li>';

                $active = ( isset( $_GET['tab'] ) && 'custom_fields' == $_GET['tab'] ) ? 'class="active"' : '';
                $tabs .= '<li id="news" class="wpc_pro_tab" ><a href="admin.php?page=wpclients_invoicing&tab=pro_features#invoicing_custom_fields" >' . __( 'Invoice Custom Fields', WPC_INV_TEXT_DOMAIN ). '<span class="wpc_pro_tab_text"> Pro</span></a></li>';

            return $tabs;
        }


        /**
         * Load css and js
         */
        function load_css_js() {
            global $wpc_client;

            if ( isset( $_GET['page'] ) && 'wpclients_invoicing' == $_GET['page'] ) {
                wp_enqueue_script( 'postbox' );

                wp_register_style( 'wpc-fancybox-style', $wpc_client->plugin_url . 'js/fancybox/jquery.fancybox.css' );
                wp_enqueue_style( 'wpc-fancybox-style' );
                wp_register_script( 'wpc-fancybox-js', $wpc_client->plugin_url . 'js/fancybox/jquery.fancybox.pack.js' );
                wp_enqueue_script( 'wpc-fancybox-js' );

                wp_enqueue_script('jquery-ui-datepicker');

                wp_register_style( 'wpc-ui-datepicker', $wpc_client->plugin_url . 'css/datapiker/ui_datapiker.min.css' );
                wp_enqueue_style( 'wpc-ui-datepicker' );

                wp_register_style( 'wpc-invoices-style', $this->extension_url . 'css/style.css' );
                wp_enqueue_style( 'wpc-invoices-style' );

                if ( isset( $_GET['tab'] ) ) {
                    switch( $_GET['tab'] ) {
                        case 'invoicing_settings':
                        case 'invoicing_templates':
                            wp_enqueue_script( 'jquery-ui-tabs' );
                            wp_enqueue_script( 'jquery-ui' );
                            wp_enqueue_script( 'jquery-base64', $wpc_client->plugin_url . 'js/jquery.b_64.min.js', array( 'jquery' ) );
                            break;

                        case 'repeat_invoices':
                        case 'repeat_invoice_edit':

                        case 'accum_invoices':
                        case 'accum_invoice_edit':

                        case 'invoice_edit':
                        case 'estimate_edit':

                            wp_enqueue_script( 'jquery-ui-spinner' );

                            wp_register_style( 'wpc-jqueryui', $wpc_client->plugin_url . 'css/jqueryui/jquery-ui-1.10.3.css' );
                            wp_enqueue_style( 'wpc-jqueryui' );

                            wp_enqueue_script( 'jquery-base64', $wpc_client->plugin_url . 'js/jquery.b_64.min.js', array( 'jquery' ) );
                            break;

                    }
                }
            }

        }


        function delete_client( $user_id ) {
            global $wpc_client;
            $ids_inv = $wpc_client->cc_get_assign_data_by_assign( 'invoice', 'client', $user_id );
            $ids_est = $wpc_client->cc_get_assign_data_by_assign( 'estimate', 'client', $user_id );
            $ids = array_merge( $ids_inv, $ids_est ) ;
            $this->delete_data( $ids ) ;
        }


        /*
        * Show dashboard tables
        */
        function show_dashboard_tables() {
            global $wpdb, $wpc_client;

            $wpc_currency = $wpc_client->cc_get_settings( 'currency' );

            $total_outstanding_invoices_amount = $wpdb->get_results(
                "SELECT pm2.meta_value as currency, sum(pm.meta_value) as sum_amount, count(*) as count
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_total' )
                LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_currency' )
                WHERE p.post_type = 'wpc_invoice' AND
                ( p.post_status = 'draft' OR p.post_status = 'sent' OR p.post_status = 'open' )
                GROUP BY pm2.meta_value
                ", ARRAY_A
            );

            if( 0 < count( $total_outstanding_invoices_amount ) ) {
                $count = count( $total_outstanding_invoices_amount ) + 1;
            } else {
                $count = 2;
                $total_outstanding_invoices_amount = array( 0 => array ( 'currency' => '', 'sum_amount' => 0, 'count' => 0 ) ) ;
            }

            $timestamp = time();

            $total_past_due_invoices_amuont = $wpdb->get_results(
                "SELECT pm2.meta_value as currency, sum(pm1.meta_value) as sum_amount, count(*) as count
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_due_date' )
                LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_total' )
                LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_currency' )
                WHERE p.post_type = 'wpc_invoice' AND
                    p.post_status != 'paid' AND
                    pm.meta_value <= " . $timestamp . "
                GROUP BY pm2.meta_value
                ", ARRAY_A
            );

            if( 0 < count( $total_past_due_invoices_amuont ) ) {
                $count2 = count( $total_past_due_invoices_amuont ) + 1;
            } else {
                $count2 = 2;
                $total_past_due_invoices_amuont = array( 0 => array( 'currency' => '', 'sum_amount' => 0, 'count' => 0 ) ) ;
            }

        ?>
            <table class="wc_status_table widefat" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php _e( 'Outstanding Invoices', WPC_INV_TEXT_DOMAIN ) ?></th>
                         <?php
                             foreach( $total_outstanding_invoices_amount as $currency ) {
                                 echo '<th>' . ( ( $currency['currency'] ) ? $wpc_currency[ $currency['currency'] ]['code'] : '' ) . '</th>' ;
                             }
                         ?>
                    </tr>
                </thead>
                <tbody>
                     <tr>
                         <td><?php _e( 'No. of Outstanding Invoices', WPC_INV_TEXT_DOMAIN ) ?>:</td>
                         <?php
                             foreach( $total_outstanding_invoices_amount as $currency ) {
                                 echo '<td>' . $currency['count'] . '</td>' ;
                             }
                         ?>
                     </tr>
                    <tr>
                         <td><?php _e( 'Total amount of Outstanding Invoices', WPC_INV_TEXT_DOMAIN ) ?>:</td>
                         <?php
                             foreach( $total_outstanding_invoices_amount as $currency ) {
                                 echo '<td>' . $this->get_currency( $currency['sum_amount'], false, $currency['currency'] ) . '</td>' ;
                             }
                         ?>
                     </tr>
                </tbody>
            </table>
            <table class="wc_status_table widefat" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php _e( 'Past Due Invoices', WPC_INV_TEXT_DOMAIN ) ?></th>
                         <?php
                             foreach( $total_past_due_invoices_amuont as $currency ) {
                                 echo '<th>' . ( ( $currency['currency'] ) ? $wpc_currency[ $currency['currency'] ]['code'] : '' ) . '</th>' ;
                             }
                         ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e( 'No. of Past Due Invoices', WPC_INV_TEXT_DOMAIN ) ?>:</td>
                         <?php
                             foreach( $total_past_due_invoices_amuont as $currency ) {
                                 echo '<td>' . $currency['count'] . '</td>' ;
                             }
                         ?>
                    </tr>
                    <tr>
                        <td><?php _e( 'Total amount of Past Due Invoices', WPC_INV_TEXT_DOMAIN ) ?>:</td>
                         <?php
                             foreach( $total_past_due_invoices_amuont as $currency ) {
                                 echo '<td>' . $this->get_currency( $currency['sum_amount'], false, $currency['currency'] ) . '</td>' ;
                             }
                         ?>
                    </tr>
                </tbody>
            </table>
        <?php
        }



        /**
         * Save tax
         */
        function save_tax() {
            global $wpc_client;

            if ( isset( $_POST['tax']['name'] ) && '' != $_POST['tax']['name'] ) {
                $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

                $wpc_invoicing['taxes'][$_POST['tax']['name']]['description'] = isset( $_POST['tax']['description'] ) ? $_POST['tax']['description'] : '';
                $wpc_invoicing['taxes'][$_POST['tax']['name']]['rate'] = isset( $_POST['tax']['rate'] ) ? $_POST['tax']['rate'] : 1;

                do_action( 'wp_client_settings_update', $wpc_invoicing, 'invoicing' );
                do_action( 'wp_client_redirect', get_admin_url() . 'admin.php?page=wpclients_invoicing&tab=invoicing_taxes&msg=s' );
                exit;
            }

            do_action( 'wp_client_redirect', get_admin_url() . 'admin.php?page=wpclients_invoicing&tab=invoicing_taxes' );
            exit;

        }


        /**
         * Delete tax
         */
        function delete_tax( $id ) {
            global $wpc_client;

                $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

                if ( isset( $wpc_invoicing['taxes'][ $id ] ) ) {
                    unset( $wpc_invoicing['taxes'][ $id ] );

                    do_action( 'wp_client_settings_update', $wpc_invoicing, 'invoicing' );
                }
        }


        /**
         * return rate capacity
         */
        function get_rate_capacity() {
            global $wpc_client;
            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );
            $rate_capacity = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;
            return $rate_capacity;
        }


        /**
         * save data of INV\EST
         */
        function save_data( $data ) {
            global $wpdb, $wpc_client;

            $error = '';
            $options_update = array();
            $options_delete = array();

            //set type of data
            $type = 'inv';
            if( isset( $data['tab'] ) && 'invoice_edit' == $data['tab'] ) {
                $type = 'inv';
                $return_url = ( isset( $_POST['return_url'] ) ) ? $_POST['return_url'] : get_admin_url(). 'admin.php?page=wpclients_invoicing';
            } else {
                if ( isset( $_GET['tab'] ) ) {
                    switch( $_GET['tab'] ) {
                        case 'invoice_edit':
                            $type = 'inv';
                            $return_url = ( isset( $_POST['return_url'] ) ) ? $_POST['return_url'] : get_admin_url(). 'admin.php?page=wpclients_invoicing';
                            break;
                        case 'estimate_edit':
                            $type = 'est';
                            $return_url = ( isset( $_POST['return_url'] ) ) ? $_POST['return_url'] : get_admin_url(). 'admin.php?page=wpclients_invoicing&tab=estimates';
                            break;

                    }
                }
            }


            //get clients ids
            $clients_id = array();
            if ( isset( $data['clients_id'] ) && '' != $data['clients_id'] ) {
                $clients_id = explode(',', $data['clients_id']);
            }
            $all_clients_id = $clients_id;

            //get client id from circles
            $groups_id = array();
            if ( isset( $data['groups_id'] ) && '' != $data['groups_id'] ) {
                $groups_id = explode( ',', $data['groups_id'] );

                $clients_of_grops = array();

                foreach( $groups_id as $group_id )
                    $clients_of_grops = array_merge( $clients_of_grops, $wpc_client->cc_get_group_clients_id( $group_id ) );

                $all_clients_id = array_unique( array_merge( $all_clients_id, $clients_of_grops ) );
            }

            $status = ( isset( $data['status'] ) ) ? $data['status'] : '' ;


            //not edit action
            if ( !( isset( $_GET['id'] ) ) && ( 'draft' != $status ) )  {
                //error no any clients
                if ( ( !is_array( $all_clients_id ) ||  0 >= count( $all_clients_id ) )  ) {
                    $error .= __( "Sorry, you should select clients or not empty circles.<br>", WPC_INV_TEXT_DOMAIN ) ;
                }
            }


            $inv_number = '';
            if ( isset( $data['inv_number'] ) ) {
                $inv_number = $data['inv_number'];
                $new_number = $wpdb->get_var( $wpdb->prepare( "SELECT pm.meta_value
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND ( pm0.meta_value = 'inv' OR pm0.meta_value = 'est' ) )
                    LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_number' )
                    WHERE p.post_type = 'wpc_invoice' AND pm.meta_value='%d'", $inv_number ) );

                if( $new_number )
                    $error .= __( "Sorry, your invoice number already exists.<br>", WPC_INV_TEXT_DOMAIN );
            }

            //save data
            if ( '' == $error ) {

                $rate_capacity = $this->get_rate_capacity();

                if ( isset( $data['due_date'] ) && '' != $data['due_date'] ) {
                    //set date
                    $options_update['due_date'] = strtotime( $data['due_date'] . ' ' . date( 'H:i:s' ) );
                } else {
                    $options_delete[] = 'due_date';
                }


                $date = date( "Y-m-d H:i:s" );

                $title = ( isset( $data['title'] ) ) ? $data['title'] : '' ;
                $description = ( isset( $data['description'] ) ) ? $data['description'] : '' ;
                $options_update['note'] = ( isset( $data['note'] ) ) ? $data['note'] : '' ;
                $options_update['terms'] = ( isset( $data['terms'] ) ) ? $data['terms'] : '' ;
                $options_update['currency'] = ( isset( $data['currency'] ) ) ? $data['currency'] : '' ;
                $options_update['custom_fields'] = ( isset( $data['custom_fields'] ) && is_array( $data['custom_fields'] ) ) ? $data['custom_fields'] : '' ;

                if ( isset( $data['deposit'] ) && ( !isset( $data['recurring'] ) || 'auto_recurring' != $data['recurring'] ) ) {
                    $options_update['deposit'] = $data['deposit'];
                    if ( isset( $data['min_deposit'] ) && 0 < (float)$data['min_deposit'] ) {
                        $options_update['min_deposit'] = round( (float)$data['min_deposit'], $rate_capacity ) ;
                    } else {
                        $options_delete[] = 'min_deposit';
                    }
                } else {
                    $options_delete[] = 'deposit';
                }

                if ( isset( $data['send_for_paid'] ) )
                    $options_update['send_for_paid'] = $data['send_for_paid'] ;
                else
                    $options_delete[] = 'send_for_paid';


                //CC emails
                $options_update['cc_emails'] = ( isset( $data['cc_emails'] ) && is_array( $data['cc_emails'] ) ) ? $data['cc_emails'] : array();

                $send = ( isset( $data['send_email'] ) && 1 == $data['send_email'] ) ? 1 : 0 ;

                //update exist
                if ( isset( $data['id'] ) && 0 < $data['id'] ) {
                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET
                        post_title       = '%s',
                        post_content     = '%s',
                        post_modified    = '%s',
                        post_status      = '%s'
                        WHERE id = %d
                        ",
                        $title,
                        $description,
                        $date,
                        $status,
                        $data['id']
                    ) );


                    //convert EST to INV
                    if ( isset( $data['convert'] ) && '1' == $data['convert'] ) {
                        $this->convert_to_inv( $data['id'] );
                        $msg = 'c';
                    }

                    $this->calculate_items( $data, $data['id'] );
                    $this->save_meta_data( $data['id'], $options_update, $options_delete ) ;


                    //send INV to client
                    if ( $send ) {
                        $msg = 'us';
                        if ( 'inv' == $type )
                            $this->send_invoice( $data['id'] );
                        else if ( 'est' == $type )
                            $this->send_estimate( $data['id'] );
                    } else {
                        $msg = 'u';
                    }

                } else {
                    //create new

                    $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

                    $ending = ( 'est' == $type ) ? '_est' : '' ;

                    if ( isset( $wpc_invoicing['prefix' . $ending] ) && '' != $wpc_invoicing['prefix' . $ending] )
                        $prefix = $wpc_invoicing['prefix' . $ending] ;


                    if( !count( $all_clients_id ) )
                        $all_clients_id = array( 0 );

                    $i = 0;
                    foreach( $all_clients_id as $client_id ) {

                        $new_post = array(
                            'post_title'       => $title,
                            'post_content'     => $description,
                            'post_status'      => $status,
                            'post_type'        => 'wpc_invoice',
                            'post_date'        => $date,
                            //'post_author'      => $all_clients_id[0],
                        );

                        $id = wp_insert_post( $new_post  );

                        update_post_meta( $id, 'wpc_inv_post_type', $type );


                        //get new number
                        if ( '' == $inv_number ) {
                            $number = $this->get_next_number( true, $type );
                        } else {
                            update_post_meta( $id, 'wpc_inv_custom_number', true );
                            if ( 0 == $i ) {
                                $number = $inv_number;
                                $i++;
                            } else {
                                do {
                                    $number = $inv_number . '-' . $i;
                                    $yes = $wpdb->get_var( $wpdb->prepare( "SELECT pm.meta_value
                                        FROM {$wpdb->posts} p
                                        INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = '{$type}' )
                                        LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'wpc_inv_number' )
                                        WHERE ( p.post_type = 'wpc_invoice' ) AND pm.meta_value='%s'", $number ) );
                                    $i++;
                                } while( $yes ) ;
                            }
                        }

                        if( isset( $prefix ) )
                            update_post_meta( $id, 'wpc_inv_prefix', $prefix );

                        if( isset( $number ) && '' != $number )
                            update_post_meta( $id, 'wpc_inv_number', $number );



                        switch( $type ) {
                            case 'inv':
                                 $object_type = 'invoice' ;
                            break;
                            case 'est':
                                 $object_type = 'estimate';
                            break;
                        }


                        $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wpc_client_objects_assigns SET
                            object_type     = '%s',
                            object_id       = '%d',
                            assign_type     = 'client',
                            assign_id       = '%d'
                            ",
                            $object_type,
                            $id,
                            $client_id
                        ));

                        $this->calculate_items( $data, $id );
                        $this->save_meta_data( $id, $options_update, $options_delete ) ;


                        //send INV to client
                        if ( $send ) {
                            $msg = 'as';
                            if ( 'inv' == $type )
                                $this->send_invoice( $id );
                            else if ( 'est' == $type )
                                $this->send_estimate( $id );
                        } else {
                            $msg = 'a';
                        }

                    }
                }

                do_action( 'wp_client_redirect', $return_url . '&msg=' . $msg );
                exit;

            }
            return $error;
        }


        function calculate_items( $data, $id ) {

            $rate_capacity = $this->get_rate_capacity();

            //get items
            $items = $options = array();

            $options['late_fee'] = 0;
            if ( isset( $data['late_fee'] ) && '' != $data['late_fee'] ) {
                $options['late_fee'] = round( (float)$data['late_fee'], $rate_capacity );
            }

            $total_items = 0;

            if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
                array_shift( $data['items'] );
                foreach ( $data['items'] as $item ) {

                        //$temp_item = (array) json_decode( base64_decode( $item ) );
                        $temp_item = $item;
                        $temp_item['quantity'] = ( isset( $temp_item['quantity'] ) && is_numeric( $temp_item['quantity'] ) && 0 < $temp_item['quantity'] ) ? $temp_item['quantity'] : '1';
                        $temp_item['price'] = '' . round( (float)$temp_item['price'], $rate_capacity ) ;
                        $total_items += round( $temp_item['price'] * $temp_item['quantity'], $rate_capacity );
                        $items[] = $temp_item;
                }
            }
            $options['sub_total'] = '' . $total_items;
            $options['items'] = $items;

            //get discounts
            $discounts = array();
            $total_discount = 0;
            if ( isset( $data['discounts'] ) && is_array( $data['discounts'] ) ) {
                foreach ( $data['discounts'] as $discount ) {
                    $temp_disc = $discount;
                    $temp_disc['rate'] = ( isset( $discount['rate'] ) ) ? '' . round( (float)$discount['rate'], $rate_capacity ) : '0';

                    if ( isset( $temp_disc['type'] ) && 'amount' == $temp_disc['type'] ) {
                        $total_discount += $temp_disc['rate'];
                    } else if ( isset( $temp_disc['type'] ) && 'percent' == $temp_disc['type'] ) {
                        $total_discount += round( $total_items * $temp_disc['rate'] / 100, $rate_capacity );
                    }
                    $discounts[] = $temp_disc;
                }
            }
            $options['discounts'] = $discounts ;
            $options['total_discount'] = $total_discount ;

            //get tax
            $total_tax = 0;
            $taxes = array();
            if ( isset( $data['taxes'] ) && is_array( $data['taxes'] ) ) {
                foreach ( $data['taxes'] as $tax ) {
                    $temp_tax = $tax;
                    $temp_tax['rate'] = ( isset( $tax['rate'] ) ) ? '' . round( (float)$tax['rate'], $rate_capacity ) : '0';
                    if ( isset( $tax['type'] ) && 'before' == $tax['type'] ) {
                        $total_tax += round( $total_items * $temp_tax['rate'] / 100, $rate_capacity );
                    } else if ( isset( $tax['type'] ) && 'after' == $tax['type'] ) {
                        $total_tax += round( ( $total_items - $total_discount ) * $temp_tax['rate'] / 100, $rate_capacity );
                    }
                    $taxes[] = $temp_tax;
                }
            }
            $options['total_tax'] = $total_tax ;
            $options['taxes'] = $taxes ;

            $added_late_fee = 0;
            if ( isset( $_GET['id'] ) )
                $added_late_fee = get_post_meta( $_GET['id'], 'wpc_inv_added_late_fee', true );

            $options['total'] = round( $total_items - $total_discount + $total_tax + $added_late_fee, $rate_capacity );

            foreach( $options as $key => $option ) {
                update_post_meta( $id, 'wpc_inv_' . $key, $option);
            }

        }





        function save_meta_data( $id, $options_update, $options_delete = array() ) {

            if ( is_array( $options_update ) && count( $options_update ) ) {
                foreach( $options_update as $key => $option ) {
                    update_post_meta( $id, 'wpc_inv_' . $key, $option);
                }
            }


            if ( is_array( $options_delete ) && count( $options_delete ) ) {
                foreach( $options_delete as $key ) {
                    delete_post_meta( $id, 'wpc_inv_' . $key);
                }
            }

        }


        /**
         * send invoice
         */
         /*
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
            */


        /**
         * send estimate
         */
        function send_estimate( $id ) {
            global $wpdb, $wpc_client;

            $estimate_ids = ( is_array( $id ) ) ? $id : (array) $id;

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
            $target_path    = $uploads['basedir'] . "/wpclient/_est/";

            //send email to client
            foreach ( $estimate_ids as $estimate_id) {
                //get data
                $est = $this->get_data( $estimate_id );
                $prefix = ( isset( $est['prefix'] ) ) ? $est['prefix'] : '' ;
                if ( 0 < $est['client_id'] ) {

                    ob_start();

                    $content = $this->invoicing_put_values( $est );

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
                    $pdf_name = $est['type'] . '_' . $this->get_number_format( $est['number'], $prefix, $est['custom_number'], 'est' ) . '.pdf';
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


                    $userdata = get_userdata( $est['client_id'] );

                    $args = array( 'client_id' => $est['client_id'], 'inv_number' => $this->get_number_format( $est['number'], $prefix, $est['custom_number'], 'est' ), );

                    //send email
                    $wpc_client->cc_mail( 'est_not', $userdata->get( 'user_email' ), $args, 'estimate_notify', array( $target_path . $pdf_name ) );

                    unlink( $target_path . $pdf_name );

                }
            }

        }


        /**
         * Delete invoice
         */
        function delete_data( $id ) {
            global $wpdb;
            $invoice_ids = ( is_array( $id ) ) ? $id : (array) $id;
            $object_type = 'invoice';
            if( isset( $_GET['tab'] ) ) {
                switch( $_GET['tab'] ) {
                    case 'accum_invoices':
                    case 'accum_invoice_edit':
                        $object_type = 'accum_invoice';
                    break;
                    case 'repeat_invoices':
                    case 'repeat_invoice_edit':
                        $object_type = 'repeat_invoice';
                    break;
                    case 'estimates':
                    case 'estimate_edit':
                        $object_type = 'estimate';
                    break;
                }
            }
            foreach ( $invoice_ids as $invoice_id ) {
                $orders = get_post_meta( $invoice_id, 'wpc_inv_order_id', true );
                if ( 'invoice' == $object_type  ) {
                    $parrent_id = get_post_meta( $invoice_id, 'wpc_inv_parrent_id', true );
                    if( $parrent_id ) {
                        $parrent_status = $wpdb->get_var( "SELECT post_status
                            FROM {$wpdb->posts} p
                            INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'repeat_inv' )
                            WHERE p.post_type = 'wpc_invoice' AND p.id = " . (int)$parrent_id );
                        if( $parrent_status && 'expired' != $parrent_status )
                            break;
                    }
                }
                if( $orders ) {
                    if( !is_array( $orders ) )
                        $orders = (array)$orders ;
                    foreach ( $orders as $order_id ) {
                        $wpdb->delete( $wpdb->prefix . 'wpc_client_payments', array( 'id' => $order_id ) );
                    }
                }

                //delete item
                $wpdb->delete( $wpdb->posts, array( 'id' => $invoice_id ) );
                $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $invoice_id ) );
                $wpdb->delete( $wpdb->prefix . 'wpc_client_objects_assigns', array( 'object_type' => $object_type, 'object_id' => $invoice_id ) );
            }
        }



        /**
         * save payment of invoice
         */
        function save_payment( $data ) {
            global $wpdb, $wpc_client, $wpc_payments_core;

            $wpc_invoicing = $wpc_client->cc_get_settings( 'invoicing' );

            $prefix = ( isset( $data['prefix'] ) ) ? $data['prefix'] : '' ;

            $rate_capacity = ( isset( $wpc_invoicing['rate_capacity'] )&& '2' < $wpc_invoicing['rate_capacity'] && '6' > $wpc_invoicing['rate_capacity'] ) ? $wpc_invoicing['rate_capacity'] : 2;

            $thousands_separator = ( isset( $wpc_invoicing['thousands_separator'] ) && !empty( $wpc_invoicing['thousands_separator'] ) ) ? $wpc_invoicing['thousands_separator'] : '';

            $error = '';

            if ( isset( $data['inv_id'] ) && '' == trim( $data['inv_id'] ) ) {
                $error .= __( "Sorry, wrong Invoice number.<br>", WPC_INV_TEXT_DOMAIN );
            }

            if ( isset( $data['amount'] ) && '' == trim( $data['amount'] ) ) {
                $error .= __( "Sorry, Payment amount is required.<br>", WPC_INV_TEXT_DOMAIN );
            }

            if ( isset( $data['date'] ) && '' == trim( $data['date'] ) ) {
                $error .= __( "Sorry, Payment date is required.<br>", WPC_INV_TEXT_DOMAIN );
            }

            if ( isset( $data['method'] ) && '' == trim( $data['method'] ) ) {
                $error .= __( "Sorry, Payment method is required.<br>", WPC_INV_TEXT_DOMAIN );
            }


            //save data
            if ( '' == $error ) {

                $paid_total = 0;
                $status     = 'paid';

                $data['amount'] = number_format( $data['amount'], $rate_capacity, '.', '' );

                $inv = $this->get_data( $data['inv_id'] );


                if ( isset( $inv['order_id'] ) && $inv['order_id'] ) {

                    $order_id = $inv['order_id'] ;

                    //get alredy paid total
                    $amounts_arr = $wpdb->get_col( "SELECT amount FROM {$wpdb->prefix}wpc_client_payments WHERE id IN ('" . implode( "','", $order_id ) . "')" );

                    if ( is_array( $amounts_arr ) && $amounts_arr ) {
                        foreach ( $amounts_arr as $amount ) {
                            $amount = str_replace( ',', '.', $amount );
                            $paid_total += $amount;
                        }
                    }

                    $paid_total += str_replace( ',', '.', $data['amount'] );
                    if ( $paid_total < $inv['total'] ) {
                        $status = 'partial';
                    }

                } else {

                    if ( $data['amount'] < $inv['total'] ) {
                        $status = 'partial';
                    }

                    $order_id = array();
                }

                $timestamp = strtotime( $data['date'] );

                if ( isset( $data['currency'] ) ) {
                    $currency =  $data['currency'] ;
                } else {
                    $currency = $wpc_client->cc_get_default_currency();
                }

                $currencies = $wpc_client->cc_get_settings( 'currency' );

                $currency = ( isset( $currencies[ $currency ]['code'] ) ) ? $currencies[ $currency ]['code'] : 'USD' ;

                $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wpc_client_payments SET
                    order_status = %s,
                    function = %s,
                    payment_method = '%s',
                    client_id = '%d',
                    amount = '%s',
                    currency = '%s',
                    transaction_id = '%s',
                    transaction_status = '%s',
                    time_created = '%s',
                    time_paid = '%s'
                    ",
                    //order_id = %s,
                    //$order_id,
                    'paid',
                    'invoicing',
                    $data['method'],
                    $inv['client_id'],
                    $data['amount'],
                    $currency,
                    '',
                    '',
                    $timestamp,
                    $timestamp
                ) );

                $order_id[] = $wpdb->insert_id;

                //change status of INV
                $wpdb->update( $wpdb->posts, array( 'post_status' => $status ), array( 'id' => $inv['id'] ) ) ;
                update_post_meta( $inv['id'], 'wpc_inv_order_id', $order_id);


                $userdata       = get_userdata( $inv['client_id'] );
                //send thank you message to client
                if ( isset( $data['thanks'] ) && 1 == $data['thanks'] && 'paid' == $status ) {

                    //client are exist
                    if ( $userdata ) {
                        $args = array(
                            'client_id' => $inv['client_id'],
                            'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] )
                        );
                        //send email
                        $wpc_client->cc_mail( 'pay_tha', $userdata->get( 'user_email' ), $args, 'invoice_thank_you' );
                    }
                }


                //send invoice mail
                if ( 'paid' == $status && isset( $inv['send_for_paid'] ) && 1 == $inv['send_for_paid'] && $userdata ) {

                    $args = array( 'client_id' => $inv['client_id'], 'inv_number' => $this->get_number_format( $inv['number'], $prefix, $inv['custom_number'] ), );
//send email
                    $wpc_client->cc_mail( 'pay_tha', $userdata->get( 'user_email' ), $args, 'invoice_thank_you' );
                }

                $msg = 'pa';


                do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_invoicing&msg=' . $msg );
                exit;

            }
        }



        /*
        * Show admin notices
        */
        function admin_notices() {
            global $wpc_client;

            if ( current_user_can( 'wpc_admin' ) || current_user_can( 'administrator' ) ) {

                $wpc_client_flags = $wpc_client->cc_get_settings( 'client_flags' );

                if ( ( !isset( $wpc_client_flags['skip_install_extension_pages'] ) || !$wpc_client_flags['skip_install_extension_pages'] )
                    && '' == $wpc_client->cc_get_slug( 'invoicing_page_id' )
                    && !isset( $_GET['install_pages'] )
                    && !isset( $_GET['skip_install_extension_pages'] ) ) {

                    $wpc_client->extension_install_pages = true;
                }
            }

        }



    //end class
    }

    //create class var
    add_action( 'plugins_loaded', 'wpc_create_class_inv_admin', 1, 1 );
    function wpc_create_class_inv_admin() {
        if ( class_exists( 'WPC_Client_Common' ) ) {
            //checking for version required
            if ( version_compare( WPC_CLIENT_LITE_VER, WPC_INV_REQUIRED_VER, '<' ) ) {
                add_action( 'wp_client_admin_notices_all_pages', 'wpc_inv_rec_ver_notice', 5 );
                function wpc_inv_rec_ver_notice() {
                    global $wpc_client;

                    if ( current_user_can( 'install_plugins' ) )
                        echo '<div class="error fade wpc_notice"><p>Sorry, but for this version of extension "Estimates/Invoices" is required version of the ' . $wpc_client->plugin['title'] . ' core not lower than ' . WPC_INV_REQUIRED_VER . '. <br />Please update ' . $wpc_client->plugin['title'] . ' core to latest version or install previous versions of this extension.</span></p></div>';
                }

            } else {
                $GLOBALS['wpc_inv_admin'] = new WPC_INV_Admin();
            }
        }
    }

    //activation
    register_activation_hook( 'wp-client-lite-invoicing-estimates-billing/wp-client-lite-invoicing-estimates-billing.php', 'wpc_activation_inv' );
    function wpc_activation_inv() {
        $wpc_inv_admin = new WPC_INV_Admin();

        if ( defined( 'WPC_CLOUDS' ) ) {
            global $wpdb;

            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
            if ( is_array( $blog_ids ) ) {
                foreach( $blog_ids as $blog_id ) {
                    switch_to_blog( $blog_id );

                    $wpc_inv_admin->activation();

                    restore_current_blog();
                }
            }
        } else {
            $wpc_inv_admin->activation();
        }

        unset( $wpc_inv_admin );
    }

}

?>

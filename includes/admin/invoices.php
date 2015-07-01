<?php
//check auth
if ( !current_user_can( 'wpc_admin' ) && !current_user_can( 'administrator' ) && !current_user_can( 'wpc_create_invoices' ) ) {
    if ( current_user_can( 'wpc_create_estimates' ) )
        $adress = 'admin.php?page=wpclients_invoicing&tab=estimates';
    else if ( current_user_can( 'wpc_create_repeat_invoices' ) )
        $adress = 'admin.php?page=wpclients_invoicing&tab=repeat_invoices';
    else if ( current_user_can( 'wpc_create_accum_invoices' ) )
        $adress = 'admin.php?page=wpclients_invoicing&tab=accum_invoices';
    else if ( current_user_can( 'wpc_modify_items' ) )
        $adress = 'admin.php?page=wpclients_invoicing&tab=invoicing_items';
    else if ( current_user_can( 'wpc_modify_taxes' ) )
        $adress = 'admin.php?page=wpclients_invoicing&tab=invoicing_taxes';
    else
        $adress = 'admin.php?page=wpclient_clients';

    do_action( 'wp_client_redirect', get_admin_url() . $adress );
}

global $wpdb, $wpc_client, $my_time_format;

//save payment
if ( isset( $_POST['wpc_payment'] ) ) {
    $this->save_payment( $_POST['wpc_payment'] );
}

if ( isset($_REQUEST['_wp_http_referer']) ) {
    $redirect = remove_query_arg(array('_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
} else {
    $redirect = get_admin_url(). 'admin.php?page=wpclients_invoicing';
}

if ( isset( $_REQUEST['action'] ) ) {
    switch ( $_REQUEST['action'] ) {

        //delete
        case 'delete':
            $ids = array();
            if ( isset( $_GET['id'] ) ) {
                check_admin_referer( 'wpc_invoice_delete' .  $_GET['id'] . get_current_user_id() );
                $ids = (array) $_REQUEST['id'];
            } elseif( isset( $_REQUEST['item'] ) )  {
                check_admin_referer( 'bulk-' . sanitize_key( __( 'Invoices', WPC_INV_TEXT_DOMAIN ) ) );
                $ids = $_REQUEST['item'];
            }
            if ( count( $ids ) ) {
                //delete invoice
                $this->delete_data( $ids );
                do_action( 'wp_client_redirect', add_query_arg( 'msg', 'd', $redirect ) );
                exit;
            }
            do_action( 'wp_client_redirect', $redirect );
            exit;

        // Mark as Read
        case 'mark':
            if ( isset( $_REQUEST['id'] )  ) {
                check_admin_referer( 'wpc_invoice_mark' . get_current_user_id() );
                //update last reminder time
                $wpdb->update( $wpdb->posts, array( 'post_status' => 'void' ), array( 'ID' => $_REQUEST['id'] ) ) ;
                update_post_meta( $_REQUEST['id'], 'wpc_inv_void_note', $_REQUEST['void_note'] )  ;

                do_action( 'wp_client_redirect', add_query_arg( 'msg', 'v', $redirect ) );
            } else {
                do_action( 'wp_client_redirect', $redirect );
            }
            exit;
    }
}

//remove extra query arg
if ( !empty( $_GET['_wp_http_referer'] ) ) {
    do_action( 'wp_client_redirect', remove_query_arg( array( '_wp_http_referer', '_wpnonce'), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
    exit;
}

global $where_manager;
$where_manager = '';
//for manager
if ( current_user_can( 'wpc_manager' ) && !current_user_can( 'administrator' ) ) {
    $manager_clients = $wpc_client->cc_get_assign_data_by_object( 'manager', get_current_user_id(), 'client');
    $where_manager = " AND coa.assign_id IN ('" . implode( "','", $manager_clients ) . "')" ;
}

$where_client = '';
//filter by clients
if ( isset( $_GET['filter_client']  ) ) {
    if ( is_numeric( $_GET['filter_client'] ) && 0 < $_GET['filter_client'] )
        $where_client = " AND coa.assign_id = '" . mysql_real_escape_string($_GET['filter_client']) . "'" ;
}

//filter by status
$where_status = '';
if ( isset( $_GET['filter_status']  ) ) {
    $where_status = " AND p.post_status = '" . mysql_real_escape_string($_GET['filter_status']) . "'" ;
}

$where_clause = '';
if( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
     $search_text = strtolower( trim( mysql_real_escape_string( $_GET['s'] ) ) );
    $where_clause = " AND (
        LOWER(p.post_content) LIKE '%" . $search_text . "%' OR
        pm1.meta_value LIKE '%" . $search_text . "%' OR
        CONCAT( IFNULL( LOWER(pm2.meta_value), '' ), pm3.meta_value) LIKE '%" . $search_text . "%'
    )";
}

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

$order_by = 'p.post_date';
if ( isset( $_GET['orderby'] ) ) {
    switch( $_GET['orderby'] ) {
        case 'status' :
            $order_by = 'p.post_status';
            break;
        case 'type' :
            $order_by = 'pm4.meta_value';
            break;
        case 'client' :
            $order_by = 'u.user_login';
            break;
        case 'number' :
            $order_by = 'pm3.meta_value';
            break;
        case 'total' :
            $order_by = 'pm1.meta_value * 1';
            break;
        case 'date' :
            $order_by = 'p.post_date';
            break;
    }
}

$order = ( isset( $_GET['order'] ) && 'asc' ==  strtolower( $_GET['order'] ) ) ? 'ASC' : 'DESC';


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPC_Invoice_List_Table extends WP_List_Table {

    var $no_items_message = '';
    var $sortable_columns = array();
    var $default_sorting_field = '';
    var $actions = array();
    var $bulk_actions = array();
    var $columns = array();

    function __construct( $args = array() ){
        $args = wp_parse_args( $args, array(
            'singular'  => __( 'item', WPC_INV_TEXT_DOMAIN ),
            'plural'    => __( 'items', WPC_INV_TEXT_DOMAIN ),
            'ajax'      => false
        ) );

        $this->no_items_message = $args['plural'] . ' ' . __( 'not found.', WPC_INV_TEXT_DOMAIN );

        parent::__construct( $args );


    }

    function __call( $name, $arguments ) {
        return call_user_func_array( array( $this, $name ), $arguments );
    }

    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
    }

    function column_default( $item, $column_name ) {
        if( isset( $item[ $column_name ] ) ) {
            return $item[ $column_name ];
        } else {
            return '';
        }
    }

    function no_items() {
        _e( $this->no_items_message, WPC_INV_TEXT_DOMAIN );
    }

    function set_sortable_columns( $args = array() ) {
        $return_args = array();
        foreach( $args as $k=>$val ) {
            if( is_numeric( $k ) ) {
                $return_args[ $val ] = array( $val, $val == $this->default_sorting_field );
            } else if( is_string( $k ) ) {
                $return_args[ $k ] = array( $val, $k == $this->default_sorting_field );
            } else {
                continue;
            }
        }
        $this->sortable_columns = $return_args;
        return $this;
    }

    function get_sortable_columns() {
        return $this->sortable_columns;
    }

    function set_columns( $args = array() ) {
        if( count( $this->bulk_actions ) ) {
            $args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
        }
        $this->columns = $args;
        return $this;
    }

    function get_columns() {
        return $this->columns;
    }

    function set_actions( $args = array() ) {
        $this->actions = $args;
        return $this;
    }

    function get_actions() {
        return $this->actions;
    }

    function set_bulk_actions( $args = array() ) {
        $this->bulk_actions = $args;
        return $this;
    }

    function get_bulk_actions() {
        return $this->bulk_actions;
    }

    /**
     * Generate the table navigation above or below the table
     */
    function display_tablenav( $which ) {
        if ( 'top' == $which || 'bottom' == $which )
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions(); ?>
            </div>
        <?php
            $this->pagination( $which );
            $this->extra_tablenav( $which );
        ?>
            <br class="wpc_clear" />
        </div>
    <?php
    }

    function column_cb( $item ) {
        global $wpdb;
        if( $item['parent_id'] ) {
            $parrent_status = $wpdb->get_var( "SELECT p.post_status
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'repeat_inv' )
                WHERE p.post_type = 'wpc_invoice' AND p.id = " . (int)$item['parent_id'] );
            if( $parrent_status && 'expired' != $parrent_status )
                return '';
        }
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />', $item['id']
        );
    }

    function column_total( $item ) {
        global $wpc_inv_admin;

        $selected_curr = $item['currency'] ;
        $allow_partial = get_post_meta( $item['id'], 'wpc_inv_deposit', true ) ;
        //$readonly = ( !$allow_partial ) ? 'readonly' : '';
        $text =
        '<span id="total_' . $item['id'] . '">' . $wpc_inv_admin->get_currency( $item['total'], true, $selected_curr ) . '</span>';
        $text .= '<br />';
        $amount_paid = 0;
        if( 'partial' == $item['status'] ) {
            $amount_paid = $wpc_inv_admin->get_amount_paid( $item['id'] );
            $amount_paid = ( 0 < $amount_paid ) ? $amount_paid : 0 ;
            if (  0 < $amount_paid ) {
                $text .= '<span class="description">(<span id="total_amount_paid_' . $item['id'] . '">';
                $text .= $wpc_inv_admin->get_currency( $amount_paid, true, $selected_curr );
                $text .= '</span>)</span>';
            }
        }

        $text .=
            '<span id="total_remaining_' . $item['id'] . '" style="display: none;">' .
            $wpc_inv_admin->get_currency( $item['total'] - $amount_paid, true, $selected_curr ) .
            '<span class="real_amount" style="display:none;">' .
            ( $item['total'] - $amount_paid ) .
            '</span></span>';
        $text .= '<span id="allow_partial_' . $item['id'] . '" style="display: none;">' . $allow_partial . '</span>';
        return $text;
    }

    function column_client( $item ) {
        return $item['client_login'];
    }

    function column_type( $item ) {
        global $wpdb;
        if ( 0 < (int)$item['parent_id'] ) {
            $item['parent_id'] = (int)$item['parent_id'];
            $parrent_type = get_post_meta( $item['id'], 'wpc_inv_parent_type', true ) ;
            $isset_parrent = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE ID = " . $item['parent_id'] ) ;
            if ( $isset_parrent && $parrent_type ) {
                switch ( $parrent_type ) {
                        case 'accum_inv':
                             return '<a href="admin.php?page=wpclients_invoicing&tab=accum_invoice_edit&id=' . $item['parent_id'] . '" target="_blank">' . __( 'Accumulating Profile', WPC_INV_TEXT_DOMAIN ) . '</a>';
                        break;
                        case 'repeat_inv':
                            return '<a href="admin.php?page=wpclients_invoicing&tab=repeat_invoice_edit&id=' . $item['parent_id'] . '" target="_blank">' . __( 'Recurring Profile', WPC_INV_TEXT_DOMAIN ) . '</a>';
                        break;
                    }
                } elseif ( $parrent_type ) {
                    switch ( $parrent_type ) {
                            case 'accum_inv':
                                 return '<span>' . __( 'Accumulating Profile (deleted)', WPC_INV_TEXT_DOMAIN ) . '</span>';
                            break;
                            case 'repeat_inv':
                                return '<span>' . __( 'Recurring Profile (deleted)', WPC_INV_TEXT_DOMAIN ) . '</span>';
                            break;
                        }
                } else {
                    return __( 'Recurring', WPC_INV_TEXT_DOMAIN );
                }
        } else {
            return __( 'One Time', WPC_INV_TEXT_DOMAIN );
        }
    }

    function column_status( $item ) {
        global $wpc_inv_admin, $wpc_client;

        $all_statuses = array( 'open', 'sent', 'void', 'refunded', 'paid', 'partial', 'draft', 'pending', 'inprocess' ) ;

        $html = '<div><span id="status_' . $item['id'] . '">';

        if ( 'void' == $item['status'] )
            $html .= $wpc_inv_admin->display_status_name( $item['status'] ) . $wpc_client->tooltip( get_post_meta( $item['id'], 'wpc_inv_void_note', true ) );
        else
            $html .= $wpc_inv_admin->display_status_name( $item['status'] );

        $html .= '</span><div class="status_invoice"></div></div><select name="status" class="change_status" data-id="' . $item['id'] . '" style="display: none;">';
        foreach ( $all_statuses as $status ) {
            $selected = ( $status == $item['status'] ) ? ' selected' : '' ;
            $html .= '<option value="' . $status . '"' . $selected . '>' . $wpc_inv_admin->display_status_name( $status ) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    function column_date( $item ) {
        global $wpc_client, $my_time_format;
        return $wpc_client->cc_date_timezone( $my_time_format, strtotime( $item['date'] ) );
    }

    function column_number( $item ) {
        global $wpdb, $wpc_client, $wpc_inv_admin;
        $archive_client = $wpc_client->cc_get_excluded_clients( 'archive' );
        $not_show_status = array( 'new', 'particular' );
        $recurring_type = get_post_meta( $item['id'], 'wpc_inv_recurring_type', true ) ;
        $prefix = ( isset( $item['prefix'] ) ) ? $item['prefix'] : '' ;
        $number = $wpc_inv_admin->get_number_format( $item['number'], $prefix, $item['custom_number'] );
        if ( ( !in_array( $item['client_id'], $archive_client ) || !in_array( $item['status'], $not_show_status ) ) ) {
            if ( 'paid' != $item['status'] && 'void' != $item['status'] && 'refunded' != $item['status'] && 'partial' != $item['status'] ) {
                $actions['edit']        = '<a href="admin.php?page=wpclients_invoicing&tab=invoice_edit&id=' . $item['id'] . '" title="Edit ' . $number . '" >' . __( 'Edit', WPC_INV_TEXT_DOMAIN ) . '</a>';
                //$actions['mark']  = '<a onclick="return showNotice.warn();" href="admin.php?page=wpclients_invoicing&action=mark&id=' . $item['id'] . '&_wpnonce=' . wp_create_nonce( 'wpc_invoice_mark' . $item['id'] . get_current_user_id() ) . '">' . __( 'Mark as Void', WPC_INV_TEXT_DOMAIN ) . '</a>';
                $actions['mark']  = '<a href="javascript:;" rel="' . $item['id']  . '" class="void" title="Mark as Void">' . __( 'Mark as Void', WPC_INV_TEXT_DOMAIN ) . '</a>';
            } else {
                $actions['view'] = '<a href="admin.php?page=wpclients_invoicing&tab=invoice_edit&id=' . $item['id'] . '" title="View ' . $number . '" >' . __( 'View', WPC_INV_TEXT_DOMAIN ) . '</a>';
            }
            if( !$recurring_type && !in_array( $item['status'], array( 'paid', 'void', 'refunded' ) ) && ( 0 < $item['total'] ) && ( ( !current_user_can( 'wpc_manager' ) || current_user_can( 'wpc_add_payment' ) ) ) )
                $actions['add_payment'] = '<a href="javascript:;" data-currency="' . $item['currency']  . '" rel="' . $item['id']  . '" class="various" title="Add Payment ' . $number . '" >' . __( 'Add Payment', WPC_INV_TEXT_DOMAIN ) . '</a>';
        }
        /*if ( 100 > strlen( $item['description'] ) ) {
            $div = wp_trim_words( $item['description'], 25 );
        } elseif ( 140 > strlen( $item['description'] ) ) {
            $div = wp_trim_words( $item['description'], 20 );
        } else {
            $div = wp_trim_words( $item['description'], 15 );
        } */


        $div = '<div>' . $item['title'] . '</div>';
        $actions['download'] = '<a href="admin.php?page=wpclients_invoicing&wpc_action=download_pdf&id=' . $item['id'] . '" title="Download PDF ' . $number . '" >' . __( 'Download PDF', WPC_INV_TEXT_DOMAIN ) . '</a>';
        if ( !current_user_can( 'wpc_manager' ) || current_user_can( 'wpc_delete_invoices' ) ) {
            $actions['delete'] = '<a onclick=\'return confirm("' . __( 'Are you sure to delete this Invoice?', WPC_INV_TEXT_DOMAIN ) . '");\' href="admin.php?page=wpclients_invoicing&action=delete&id=' .$item['id'] . '&_wpnonce=' . wp_create_nonce( 'wpc_invoice_delete' . $item['id'] . get_current_user_id() ) .'">' . __( 'Delete Permanently', WPC_INV_TEXT_DOMAIN ) . '</a>';

            if( $item['parent_id'] ) {
                $parrent_status = $wpdb->get_var( "SELECT p.post_status
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'repeat_inv' )
                    WHERE p.post_type = 'wpc_invoice' AND p.id = " . (int)$item['parent_id'] );
                if( $parrent_status && 'expired' != $parrent_status && 'void' != $item['status'] && 'refunded' != $item['status'] )
                    $actions['delete'] = '<a onclick=\'return confirm("' . __( 'You does not can delete this Invoice, because exists active Recurring Profile which created this Invoice.', WPC_INV_TEXT_DOMAIN ) . '");\' href="#">' . __( 'Delete Permanently', WPC_INV_TEXT_DOMAIN ) . '</a>';

            }
        }

        return sprintf('%1$s %2$s', '<strong><a href="admin.php?page=wpclients_invoicing&tab=invoice_edit&id=' . $item['id'] . '" title="edit ' . $number . '">' . $number . '</a></strong>' . $div, $this->row_actions( $actions ) );
    }

    function extra_tablenav( $which ){
        if ( 'top' == $which || 'bottom' == $which ) {

            $limit_invs = ( isset( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) && 0 < $_GET['limit'] ) ? $_GET['limit'] : 25;
            ?>

            <div class="for_show_by">
                <?php _e( 'Show by', WPC_INV_TEXT_DOMAIN ) ?>:
                <select name="limit_invs" class="limit_invs">
                <?php
                    $array_value = array( 10, 25, 50, 75, 100, 150, 250 );
                    foreach ( $array_value as $val ) {
                        echo '<option value="' . $val . '"' . ( ( $val == $limit_invs ) ? 'selected' : '' ) . '>' . $val . '</option>' ;
                    }
                ?>
                </select>
            </div>

            <?php
        }

        if ( 'top' == $which ) {
            global $wpdb, $wpc_client, $where_manager;


            $all_clients = $wpdb->get_col( "SELECT DISTINCT assign_id FROM {$wpdb->prefix}wpc_client_objects_assigns coa WHERE object_type='invoice' {$where_manager}" );
            ?>
            <div class="alignleft actions">
                <select name="filter_client" id="filter_client">
                    <option value="-1" selected="selected"><?php printf( __( 'Select %s', WPC_INV_TEXT_DOMAIN ), $wpc_client->custom_titles['client']['s'] ) ?></option>
                    <?php
                    if ( is_array( $all_clients ) && 0 < count( $all_clients ) ) {
                        foreach( $all_clients as $client_id ) {
                            $selected = ( isset( $_GET['filter_client'] ) && $client_id == $_GET['filter_client'] ) ? 'selected' : '';
                            echo '<option value="' . $client_id . '" ' . $selected . ' >' .  get_userdata( $client_id )->user_login . '</option>';
                        }
                    }
                    ?>
                </select>
                <input type="button" value="<?php _e( 'Filter', WPC_INV_TEXT_DOMAIN ) ?>" class="button-secondary" id="client_filter_button" name="" />
                <a class="add-new-h2" id="cancel_filter" <?php if( !isset( $_GET['filter_client'] ) || 0 > $_GET['filter_client'] ) echo 'style="display: none;"'; ?> ><?php _e( 'Remove Filter', WPC_INV_TEXT_DOMAIN ) ?><span style="color: #BC0B0B;"> x </span></a>
            </div>


            <?php

        }
    }


    function wpc_get_items_per_page( $attr = false ) {
        return $this->get_items_per_page( $attr );
    }

    function wpc_set_pagination_args( $attr = false ) {
        return $this->set_pagination_args( $attr );
    }

}


$ListTable = new WPC_Invoice_List_Table( array(
        'singular'  => __( 'Invoice', WPC_INV_TEXT_DOMAIN ),
        'plural'    => __( 'Invoices', WPC_INV_TEXT_DOMAIN ),
        'ajax'      => false

));

$per_page   = ( isset( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) && 0 < $_GET['limit'] ) ? $_GET['limit'] : 25;;
$paged      = $ListTable->get_pagenum();

$ListTable->set_sortable_columns( array(
    'client'           => 'client',
    'status'           => 'status',
    'number'           => 'number',
    'type'             => 'type',
    'date'             => 'date',
    'total'            => 'total',
) );

$ListTable->set_bulk_actions(array(
    'delete'        => 'Delete',
));

$ListTable->set_columns(array(
    'number'                => __( 'Invoice Number', WPC_INV_TEXT_DOMAIN ),
    'client'                => $wpc_client->custom_titles['client']['s'],
    'total'                 => __( 'Total', WPC_INV_TEXT_DOMAIN ),
    'status'                => __( 'Status', WPC_INV_TEXT_DOMAIN ),
    'type'                  => __( 'Type', WPC_INV_TEXT_DOMAIN ),
    'date'                  => __( 'Date', WPC_INV_TEXT_DOMAIN ),
));


$sql = "SELECT count( p.ID )
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
    LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'invoice' )
    LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_total' )
    LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_prefix' )
    LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_number' )
    WHERE p.post_type='wpc_invoice'
        {$where_client}
        {$where_manager}
        {$where_status}
        {$where_clause}
    ";

$items_count = $wpdb->get_var( $sql );

$sql = "SELECT p.ID as id, p.post_title as title, p.post_date as date, coa.assign_id as client_id, p.post_status as status, u.user_login as client_login, pm1.meta_value as total, pm5.meta_value as parent_id, pm2.meta_value as prefix, pm3.meta_value as number, pm4.meta_value as type, pm6.meta_value as currency, pm7.meta_value as custom_number
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
    LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'invoice' )
    LEFT JOIN {$wpdb->users} u ON ( u.ID = coa.assign_id )
    LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_total' )
    LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_prefix' )
    LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_number' )
    LEFT JOIN {$wpdb->postmeta} pm4 ON ( p.ID = pm4.post_id AND pm4.meta_key = 'wpc_inv_recurring_type' )
    LEFT JOIN {$wpdb->postmeta} pm5 ON ( p.ID = pm5.post_id AND pm5.meta_key = 'wpc_inv_parrent_id' )
    LEFT JOIN {$wpdb->postmeta} pm6 ON ( p.ID = pm6.post_id AND pm6.meta_key = 'wpc_inv_currency' )
    LEFT JOIN {$wpdb->postmeta} pm7 ON ( p.ID = pm7.post_id AND pm7.meta_key = 'wpc_inv_custom_number' )
    WHERE p.post_type='wpc_invoice'
        {$where_client}
        {$where_manager}
        {$where_status}
        {$where_clause}
    ORDER BY $order_by $order
    LIMIT " . ( $per_page * ( $paged - 1 ) ) . ", $per_page";
$cols = $wpdb->get_results( $sql, ARRAY_A );

$ListTable->prepare_items();
$ListTable->items = $cols;
$ListTable->wpc_set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) );
?>

<div class="wrap">

    <?php echo $wpc_client->get_plugin_logo_block() ?>

    <?php
    if ( isset( $_GET['msg'] ) ) {
        switch( $_GET['msg'] ) {
            case 'a':
                echo  '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Invoice <strong>Created</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'as':
                echo  '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Invoice <strong>Created & Sent</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'pa':
                echo  '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Payment <strong>Added</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Invoice <strong>Updated</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'us':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Invoice <strong>Updated & Sent</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Invoice(s) <strong>Deleted</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'v':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Invoice Marked as Void', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

    <div class="clear"></div>

    <div id="container23">

        <ul class="menu">
            <?php echo $this->gen_tabs_menu() ?>
        </ul>
        <span class="clear"></span>

        <div class="content23 news" style="width: 100%; float: left;">
            <br>
            <?php
                if ( !current_user_can( 'wpc_manager' ) || current_user_can( 'wpc_create_invoices' ) ) {
            ?>
                    <div>
                        <a href="admin.php?page=wpclients_invoicing&tab=invoice_edit" class="add-new-h2"><?php _e( 'Add New Invoice', WPC_INV_TEXT_DOMAIN ) ?></a>
                    </div>
            <?php
                }
            ?>

            <hr />
            <?php

            global $wpdb;

            $count_all = 0;
            $all_count_status = $wpdb->get_results( "SELECT post_status, count(p.ID) as count
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'inv' )
                JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'invoice' )
                WHERE post_type='wpc_invoice' {$where_manager} GROUP BY post_status", ARRAY_A );
            foreach ( $all_count_status as $status ) {
                $count_all += $status['count'];
            }

            $filter_status = (string)@$_GET['filter_status'];
            $filter_client = (string)@$_GET['filter_client'];

            ?>

            <ul class="subsubsub" style="margin: 0px 0px 0px 0px;" >
                <li class="all"><a class="<?php echo ( '' == $filter_status ) ? 'current' : '' ?>" href="admin.php?page=wpclients_invoicing<?php echo ( '' != $filter_client ) ? '&filter_client=' . $filter_client : '' ?>"  ><?php _e( 'All', WPC_INV_TEXT_DOMAIN ) ?> <span class="count">(<?php echo $count_all ?>)</span></a></li>
            <?php
                foreach ( $all_count_status as $status ) {
                    $stat = strtolower( $status['post_status'] );
                    $class = ( $stat == $filter_status ) ? 'current' : '';
                    $params = ( '' != $filter_client ) ? '&filter_client=' . $filter_client : '';
                    echo ' | <li class="image"><a class="' . $class . '" href="admin.php?page=wpclients_invoicing' . $params . '&filter_status=' . $stat . '">' . sprintf( __( '%s', WPC_INV_TEXT_DOMAIN ), ucfirst( $stat ) ) . '<span class="count">(' . $status['count'] . ')</span></a></li>';
                }
            ?>
            </ul>
            <form action="" method="get" name="wpc_clients_form" id="wpc_clients_form">
                <input type="hidden" name="page" value="wpclients_invoicing" />
                <?php $ListTable->search_box( __( 'Search Invoices', WPC_INV_TEXT_DOMAIN ), 'search-submit' ); ?>
                <?php $ListTable->display(); ?>
            </form>

            <div style="display: none;">
                <div class="wpc_add_payment" id="add_payment">
                    <h3><?php _e( 'Add Payment:', WPC_INV_TEXT_DOMAIN ) ?></h3>
                    <form method="post" name="wpc_add_payment" id="wpc_add_payment">
                        <input type="hidden" name="wpc_payment[inv_id]" id="wpc_payment_inv_id" value="" />
                        <input type="hidden" name="wpc_payment[currency]" id="wpc_payment_currency" value="" />
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

            <div style="display: none;">
                <div class="wpc_mark_as_void" id="mark_as_void">
                    <h3><?php _e( 'Mark as Void:', WPC_INV_TEXT_DOMAIN ) ?></h3>
                    <form method="post" name="wpc_mark_as_void" id="wpc_mark_as_void">
                        <input type="hidden" name="id" id="wpc_void_inv_id" value="" />
                        <input type="hidden" name="action" value="mark" />
                        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'wpc_invoice_mark' . get_current_user_id() ) ?>" />
                        <table>
                            <tr>
                                <td>
                                    <label>
                                        <?php _e( 'Notes:', WPC_INV_TEXT_DOMAIN ) ?>
                                        <br />
                                        <textarea cols="67" rows="3" name="void_note" id="wpc_void_note" ></textarea>
                                    </label>
                                    <br />
                                    <br />
                                </td>
                            </tr>
                        </table>
                        <br />
                        <div style="clear: both; text-align: center;">
                            <input type="button" class='button-primary' id="save_mark_as_void" value="<?php _e( 'Mark as Void', WPC_INV_TEXT_DOMAIN ) ?>" />
                            <input type="button" class='button' id="close_mark_as_void" value="<?php _e( 'Close', WPC_INV_TEXT_DOMAIN ) ?>" />
                        </div>
                    </form>
                </div>
            </div>


        </div>
    </div>
</div>

<script type="text/javascript">
    var site_url = '<?php echo site_url();?>';

    jQuery(document).ready( function() {

        jQuery( '.status_invoice' ).click( function() {
            jQuery( this ).parent().css( 'display', 'none' );
            jQuery( this ).parent().next().css( 'display', 'block' );
        });

        jQuery( '.change_status' ).change( function() {
            var id = jQuery( this ).data( 'id' );
            var new_status = jQuery( this ).val();

            if( 'undefined' != typeof new_status && new_status ) {
                jQuery.ajax({
                    type: 'POST',
                    dataType    : 'json',
                    url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                    data: 'action=inv_change_status&id=' + id + '&new_status=' + new_status ,
                    success: function(){
                    }
                });
            }
            jQuery( this ).css( 'display', 'none' );
            jQuery( '#status_' + id ).text( jQuery( this ).children(':selected').text() );
            jQuery( this ).prev().css( 'display', 'block' );
        });

        jQuery( '.limit_invs' ).change( function(){
            var limit = jQuery( this ).val();
            var req_uri = "<?php echo preg_replace( '/&limit=[0-9]+|&msg=[^&]+/', '', $_SERVER['REQUEST_URI'] ); ?>&limit=" + limit;
            window.location = req_uri;
            return false;
        });

        jQuery( '#cancel_filter' ).click( function() {
            var req_uri = "<?php echo preg_replace( '/&filter_client=[0-9]+|&msg=[^&]+/', '', $_SERVER['REQUEST_URI'] ); ?>";
            window.location = req_uri;
            return false;
        });

        //filter by clients
        jQuery( '#client_filter_button' ).click( function() {
            if ( '-1' != jQuery( '#filter_client' ).val() ) {
                var req_uri = "<?php echo preg_replace( '/&filter_client=[0-9]+|&msg=[^&]+/', '', $_SERVER['REQUEST_URI'] ); ?>&filter_client=" + jQuery( '#filter_client' ).val();
                window.location = req_uri;
            }
            return false;
        });


        //open Add Payment
        jQuery( '.various' ).click( function() {
            var id = jQuery( this ).attr( 'rel' );
            var currency = jQuery( this ).data( 'currency' );
            //set payment amount
            jQuery( '#wpc_payment_amount' ).val( jQuery( '#total_remaining_' + id + ' .real_amount' ).html() );

            if( false == jQuery( '#allow_partial_' + id ).html() )
                jQuery( '#wpc_payment_amount' ).attr( 'readonly', 'readonly' );
            else
                jQuery( '#wpc_payment_amount' ).removeAttr( 'readonly' );

            jQuery( '#wpc_add_payment_total' ).html( jQuery( '#total_' + id ).html() );
            jQuery( '#wpc_add_payment_amount_paid' ).html( jQuery( '#total_amount_paid_' + id ).html() );

            jQuery( '#wpc_payment_date' ).val( '<?php echo date( 'm/d/Y', time() ) ?>' );

            jQuery( '#wpc_payment_inv_id' ).val( id );
            jQuery( '#wpc_payment_currency' ).val( currency );

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

        //open Mark as Void
        jQuery( '.void' ).click( function() {
            var id = jQuery( this ).attr( 'rel' );
            jQuery( '#wpc_void_inv_id' ).val( id );

            //show content for edit file
            jQuery.fancybox({
                autoResize  : true,
                autoSize    : true,
                closeClick  : false,
                openEffect  : 'none',
                closeEffect : 'none',
                href : '#mark_as_void',
                helpers : {
                    title : null,
                },
                onCleanup: function () {
                    jQuery('.fancybox-inline-tmp').replaceWith(jQuery(jQuery(this).attr('href')));
                }
            });

        });

        //close Mark as Void
        jQuery( '#close_mark_as_void' ).click( function() {
            jQuery( '#wpc_void_inv_id' ).val( '' );
            jQuery.fancybox.close();
        });

        //close Add Payment
        jQuery( '#close_add_payment' ).click( function() {
            jQuery( '#wpc_payment_inv_id' ).val( '' );
            jQuery.fancybox.close();
        });


        jQuery( '#wpc_payment_date' ).datepicker({
    //            dateFormat : '<?php echo $date_format ?>'
            dateFormat : 'mm/dd/yy'
        });


        //check payment amount
        jQuery( '#wpc_payment_amount' ).live( 'change', function(e) {
            var val = jQuery(this).val();

            if ( val > jQuery( '#wpc_add_payment_total .amount' ).html() * 1 ) {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', 'wpc_error' );
            } else {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', '' );
            }
        });

        jQuery( '#wpc_payment_amount' ).live( 'keypress', function(e) {

            if ( e.which == 8 || e.which == 0 ) {
                return true;
            }

            if ( ( e.which >= 48 && e.which <= 57 ) || e.which == 44 || e.which == 46 ) {
              //  if( val.length == 0 ) {
    //                    jQuery(this).val('0');
    //                }

                return true;
            }

            return false;
        });

        //check payment amount
       // jQuery( '#wpc_payment_amount' ).keyup( function( e ) {

    //            if ( val > jQuery( '#wpc_add_payment_total' ).html() * 1 ) {
    //                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', 'wpc_error' );
    //            } else {
    //                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', '' );
    //            }

    //        });

        //Save payment
        jQuery( '#save_add_payment' ).click( function() {
            var errors = 0;
            if ( jQuery( '#wpc_payment_amount' ).val() > jQuery( '#wpc_add_payment_total .amount' ).html() * 1 ) {
                errors = 1;

                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', 'wpc_error' );
            } else {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', '' );
            }

            if ( '' == jQuery( "#wpc_payment_amount" ).val() ) {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else {
                jQuery( '#wpc_payment_amount' ).parent().parent().attr( 'class', '' );
            }

            if ( '' == jQuery( "#wpc_payment_date" ).val() ) {
                jQuery( '#wpc_payment_date' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else {
                jQuery( '#wpc_payment_date' ).parent().parent().attr( 'class', '' );
            }

            if ( '' == jQuery( "#wpc_payment_method" ).val() ) {
                jQuery( '#wpc_payment_method' ).parent().parent().attr( 'class', 'wpc_error' );
                errors = 1;
            } else {
                jQuery( '#wpc_payment_method' ).parent().parent().attr( 'class', '' );
            }

            if ( 0 == errors ) {
                jQuery( '#wpc_add_payment' ).submit();
            }

        });


        //save option void
        jQuery( '#save_mark_as_void' ).click( function() {
            jQuery( '#wpc_mark_as_void' ).submit();
        });

            //reassign file from Bulk Actions
            jQuery( '#doaction2' ).click( function() {
                var action = jQuery( 'select[name="action2"]' ).val() ;
                jQuery( 'select[name="action"]' ).attr( 'value', action );
                return true;
            });

    });
</script>
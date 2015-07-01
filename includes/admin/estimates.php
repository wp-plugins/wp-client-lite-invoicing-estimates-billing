<?php
//check auth
if ( !current_user_can( 'wpc_admin' ) && !current_user_can( 'administrator' ) && !current_user_can( 'wpc_create_estimates' ) ) {
    do_action( 'wp_client_redirect', get_admin_url() . 'admin.php?page=wpclient_clients' );
}

global $wpdb, $wpc_client, $my_time_format;

if ( isset($_REQUEST['_wp_http_referer']) ) {
    $redirect = remove_query_arg(array('_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
} else {
    $redirect = get_admin_url(). 'admin.php?page=wpclients_invoicing&tab=estimates';
}

if ( isset( $_GET['action'] ) ) {
    switch ( $_GET['action'] ) {

        //delete
        case 'delete':
            $ids = array();
            if ( isset( $_GET['id'] ) ) {
                check_admin_referer( 'wpc_estimate_delete' .  $_GET['id'] . get_current_user_id() );
                $ids = (array) $_REQUEST['id'];
            } elseif( isset( $_REQUEST['item'] ) )  {
                check_admin_referer( 'bulk-' . sanitize_key( __( 'Estimates', WPC_INV_TEXT_DOMAIN ) ) );
                $ids = $_REQUEST['item'];
            }
            if ( count( $ids ) ) {
                //delete estimate

                $this->delete_data( $ids );
                do_action( 'wp_client_redirect', add_query_arg( 'msg', 'd', $redirect ) );
                exit;
            }
            do_action( 'wp_client_redirect', $redirect );
            exit;

        // Convert to INV
        case 'convert':
            if ( isset( $_GET['id'] ) ) {
                check_admin_referer( 'wpc_estimate_convert' .  $_GET['id'] . get_current_user_id() );
                $this->convert_to_inv( $_REQUEST['id'] );
                do_action( 'wp_client_redirect', add_query_arg( 'msg', 'c', $redirect ) );
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

$order_by = 'p.ID';
if ( isset( $_GET['orderby'] ) ) {
    switch( $_GET['orderby'] ) {
        case 'status' :
            $order_by = 'p.post_status';
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

class WPC_Estimates_List_Table extends WP_List_Table {

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
        return sprintf(
            '<input type="checkbox" name="item[]" value="%s" />', $item['id']
        );
    }

    function column_date( $item ) {
        global $wpc_client, $my_time_format;
        return $wpc_client->cc_date_timezone( $my_time_format, strtotime( $item['date'] ) );
    }

    function column_total( $item ) {
        global $wpc_inv_admin;
        $selected_curr = get_post_meta( $item['id'], 'wpc_inv_currency', true ) ;
        return $wpc_inv_admin->get_currency( $item['total'], false, $selected_curr );
    }

    function column_client( $item ) {
        return $item['client_login'];
    }

    function column_status( $item ) {
        global $wpc_inv_admin, $wpc_client;
        if ( 'declined' == $item['status'] ) {
            $comments = get_post_meta( $item['id'], 'wpc_inv_declined_note', true );
            $comments = ( !empty( $comments ) ) ? $comments : __( 'No Comments', WPC_INV_TEXT_DOMAIN );
            return $wpc_inv_admin->display_status_name( $item['status'] ) . $wpc_client->tooltip( $comments );
        } else {
            return $wpc_inv_admin->display_status_name( $item['status'] ) ;
        }
    }

    function column_number( $item ) {
        global $wpc_client, $wpc_inv_admin;
        $archive_client = $wpc_client->cc_get_excluded_clients( 'archive' );
        $not_show_status = array( 'new', 'particular' );
        $prefix = ( isset( $item['prefix'] ) ) ? $item['prefix'] : '' ;
        $number = $wpc_inv_admin->get_number_format( $item['number'], $prefix, $item['custom_number'], 'est' );
        if ( 'paid' != $item['status'] ) {
            if ( ( !in_array( $item['client_id'], $archive_client ) || !in_array( $item['status'], $not_show_status ) ) ) {
                $actions['edit'] = '<a href="admin.php?page=wpclients_invoicing&tab=estimate_edit&id=' . $item['id'] . '" title="Edit ' . $number . '" >' . __( 'Edit', WPC_INV_TEXT_DOMAIN ) . '</a>';
                $actions['convert'] = '<a href="admin.php?page=wpclients_invoicing&tab=estimates&id=' . $item['id'] . '&action=convert&_wpnonce=' . wp_create_nonce( 'wpc_estimate_convert' . $item['id'] . get_current_user_id() ) . '"  title="Convert to invoice ' . $number . '" >' . __( 'Convert to Invoice', WPC_INV_TEXT_DOMAIN ) . '</a>';
            }
        } else {
            $actions['view'] = '<a href="admin.php?page=wpclients_invoicing&tab=estimate_edit&id=' . $item['id'] . '" title="view ' . $number . '" >' . __( 'View', WPC_INV_TEXT_DOMAIN ) . '</a>';
        }
        $div = '<div>' . $item['title'] . '</div>';
        $actions['download'] = '<a href="admin.php?page=wpclients_invoicing&wpc_action=download_pdf&id=' . $item['id'] . '" title="Download PDF ' . $number . '" >' . __( 'Download PDF', WPC_INV_TEXT_DOMAIN ) . '</a>';
        if ( !current_user_can( 'wpc_manager' ) || current_user_can( 'wpc_delete_estimates' ) ) {
            $actions['delete'] = '<a onclick=\'return confirm("' . __( 'Are you sure to delete this Estimate?', WPC_INV_TEXT_DOMAIN ) . '");\' href="admin.php?page=wpclients_invoicing&tab=estimates&action=delete&id=' .$item['id'] . '&_wpnonce=' . wp_create_nonce( 'wpc_estimate_delete' . $item['id'] . get_current_user_id() ) .'">' . __( 'Delete Permanently', WPC_INV_TEXT_DOMAIN ) . '</a>';
        }

        return sprintf('%1$s %2$s', '<strong><a href="admin.php?page=wpclients_invoicing&tab=estimate_edit&id=' . $item['id'] . '" title="edit ' . $number . '">' . $number . '</a></strong>' . $div, $this->row_actions( $actions ) );
    }

    function extra_tablenav( $which ) {

        if ( 'top' == $which ) {
            global $wpdb, $wpc_client, $where_manager;
            $all_clients = $wpdb->get_col( "SELECT DISTINCT assign_id FROM {$wpdb->prefix}wpc_client_objects_assigns coa WHERE object_type='estimate' {$where_manager}" );
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


        if ( 'top' == $which || 'bottom' == $which ) {
            $limit_ests = ( isset( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) && 0 < $_GET['limit'] ) ? $_GET['limit'] : 25;
            ?>

            <div class="for_show_by">
                <?php _e( 'Show by', WPC_INV_TEXT_DOMAIN ) ?>:
                <select name="limit_ests" class="limit_ests">
                <?php
                    $array_value = array( 10, 25, 50, 75, 100, 150, 250 );
                    foreach ( $array_value as $val ) {
                        echo '<option value="' . $val . '"' . ( ( $val == $limit_ests ) ? 'selected' : '' ) . '>' . $val . '</option>' ;
                    }
                ?>
                </select>
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


$ListTable = new WPC_Estimates_List_Table( array(
        'singular'  => __( 'Estimate', WPC_INV_TEXT_DOMAIN ),
        'plural'    => __( 'Estimates', WPC_INV_TEXT_DOMAIN ),
        'ajax'      => false

));

$per_page   = ( isset( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) && 0 < $_GET['limit'] ) ? $_GET['limit'] : 25;
$paged      = $ListTable->get_pagenum();

$ListTable->set_sortable_columns( array(
    'client'           => 'client',
    'status'           => 'status',
    'number'           => 'number',
    'date'             => 'date',
    'total'            => 'total',
) );

$ListTable->set_bulk_actions(array(
    'delete'        => 'Delete',
));

$ListTable->set_columns(array(
    'number'                => __( 'Estimate Number', WPC_INV_TEXT_DOMAIN ),
    'client'                => $wpc_client->custom_titles['client']['s'],
    'total'                 => __( 'Total', WPC_INV_TEXT_DOMAIN ),
    'status'                => __( 'Status', WPC_INV_TEXT_DOMAIN ),
    'date'                  => __( 'Date', WPC_INV_TEXT_DOMAIN ),
));


$sql = "SELECT count( p.ID )
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'est' )
    LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'estimate' )
    WHERE p.post_type='wpc_invoice'
        {$where_client}
        {$where_manager}
        {$where_clause}
    ";
$items_count = $wpdb->get_var( $sql );

$sql = "SELECT p.ID as id, p.post_title as title, p.post_date as date, coa.assign_id as client_id, p.post_status as status, u.user_login as client_login, pm1.meta_value as total, pm2.meta_value as prefix, pm3.meta_value as number, pm4.meta_value as custom_number
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm0 ON ( p.ID = pm0.post_id AND pm0.meta_key = 'wpc_inv_post_type' AND pm0.meta_value = 'est' )
    LEFT JOIN {$wpdb->prefix}wpc_client_objects_assigns coa ON ( p.ID = coa.object_id AND coa.object_type = 'estimate' )
    LEFT JOIN {$wpdb->users} u ON ( u.ID = coa.assign_id )
    LEFT JOIN {$wpdb->postmeta} pm1 ON ( p.ID = pm1.post_id AND pm1.meta_key = 'wpc_inv_total' )
    LEFT JOIN {$wpdb->postmeta} pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'wpc_inv_prefix' )
    LEFT JOIN {$wpdb->postmeta} pm3 ON ( p.ID = pm3.post_id AND pm3.meta_key = 'wpc_inv_number' )
    LEFT JOIN {$wpdb->postmeta} pm4 ON ( p.ID = pm4.post_id AND pm4.meta_key = 'wpc_inv_custom_number' )
    WHERE p.post_type='wpc_invoice'
        {$where_client}
        {$where_manager}
        {$where_clause}
    ORDER BY $order_by $order
    LIMIT " . ( $per_page * ( $paged - 1 ) ) . ", $per_page";
$cols = $wpdb->get_results( $sql, ARRAY_A );

$ListTable->prepare_items();
$ListTable->items = $cols;
$ListTable->wpc_set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) ); ?>

<div class="wrap">

    <?php echo $wpc_client->get_plugin_logo_block() ?>

    <?php
    if ( isset( $_GET['msg'] ) ) {
        switch( $_GET['msg'] ) {
            case 'a':
                echo  '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Estimate <strong>Created</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'as':
                echo  '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Estimate <strong>Created & Sent</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Estimate <strong>Updated</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'us':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Estimate <strong>Updated & Sent</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'c':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Estimate <strong>Converted</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated wpc_notice fade"><p>' . __( 'Estimate(s) <strong>Deleted</strong> Successfully.', WPC_INV_TEXT_DOMAIN ) . '</p></div>';
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
            <div>
                <a href="admin.php?page=wpclients_invoicing&tab=estimate_edit" class="add-new-h2"><?php _e( 'Add New Estimate', WPC_INV_TEXT_DOMAIN ) ?></a>
            </div>

            <hr />
            <form action="" method="get" name="wpc_clients_form" id="wpc_clients_form">
                <input type="hidden" name="page" value="wpclients_invoicing" />
                <input type="hidden" name="tab" value="estimates" />
                <?php $ListTable->search_box( __( 'Search Estimates', WPC_INV_TEXT_DOMAIN ), 'search-submit' ); ?>
                <?php $ListTable->display(); ?>
            </form>

        </div>
    </div>
</div>

<script type="text/javascript">
    var site_url = '<?php echo site_url();?>';

    jQuery(document).ready(function(){
        jQuery( '.limit_ests' ).change( function(){
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

        //reassign file from Bulk Actions
        jQuery( '#doaction2' ).click( function() {
            var action = jQuery( 'select[name="action2"]' ).val() ;
            jQuery( 'select[name="action"]' ).attr( 'value', action );
            return true;
        });

    });
</script>
<?php
global $wpc_client;


    wp_register_style( 'wpc-fancybox-style', $wpc_client->plugin_url . 'js/fancybox/jquery.fancybox.css' );
    wp_enqueue_style( 'wpc-fancybox-style' );
    wp_register_script( 'wpc-fancybox-js', $wpc_client->plugin_url . 'js/fancybox/jquery.fancybox.pack.js' );
    wp_enqueue_script( 'wpc-fancybox-js' );


$pro_features = array(
    'repeat_invoices' => array(
        'title' => 'Recurring Profiles',
        'desc' => 'Recurring Billing/Invoicing functionality, which allows you to assign a Recurring Profile to a client, and that profile will automatically create new invoices and automatically assign them to the client on a set increment (daily, weekly, monthly, etc). These invoices can be set to need to be manually paid each time by the client, or they can optionally be set as "auto-charge", which will allow the client to pay the initial invoice, and any recurring charges beyond that will be automatically charged to their account (such as their PayPal account).',
    ),
    'accum_invoices' => array(
        'title' => 'Accumulating Profiles ',
        'desc' => 'Accumulating invoices allow you to bill your client on a regular schedule, but for instances where you may not be charging them the same amount every time. You can think of it as keeping a "running tab" for a client, and then sending them a bill for what they have charged up to a certain point.',
    ),
    'invoicing_items' => array(
        'title' => 'Items',
        'desc' => 'The building blocks for Estimates and Invoices are what is known as Items. Items can be thought of as a line item title and description that quantifies a billable service or particular product or SKU. Businesses can use this in many different ways, including describing and quantifying the scope and price for one hours work, and then when adding the item to the estimate/invoice, set the quantity to reflect the number of hours. Within WP-Client PRO, you will have the option to create and save invoicing Items for future use, allowing you to quickly add your most common charges to an Invoice or Estimate.',
    ),
    'invoicing_taxes' => array(
        'title' => 'Taxes',
        'desc' => 'WP-Client LITE allows you to add new Tax rates when creating a particular Estimate or Invoice. WP-Client PRO takes it one step further, and allows you to create and store Taxes within the plugin settings, allowing you easy access to them when creating new Estimates/Invoices. Multiple Taxes can be created, with varying percentage rates, allowing you to customize the Taxes to fit your particular business needs.',
    ),
    'invoicing_custom_fields' => array(
        'title' => 'Invoice Custom Fields',
        'desc' => 'Invoice Custom Fields allow you to easily create and implement additional fields to the existing Invoice layout. For example, if you would like to add a column in the item list where you can include the stock ID number of a particular item, this could be easily accomplished using Invoice Custom Fields. Any kind of quantifiable data regarding items (manufacture date, country of origin, etc) can be created and shown using Invoice Custom Fields.',
    ),


);


//ksort( $pro_features );


$screenshots = array();

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

            <h3><?php _e( 'Estimates/Invoices Pro Features', WPC_INV_TEXT_DOMAIN ) ?>:</h3>



            <p><?php _e( "The PRO version of the Estimates/Invoices Extension allows you more control over your invoicing system within WP-Client. Estimates/Invoices PRO allows you to manage a library of preset invoicing items, taxes, and discounts, allowing you to quickly and easily generate new invoices for your clients. Additionally, the PRO version of this Extension allows you to create Recurring and Accumulating invoices, giving you the option to set automatic recurring charges for your clients. Please read below for more information about all of the features included in Estimates/Invoices PRO.", WPC_CLIENT_TEXT_DOMAIN ) ?></p>

            <div class="wpc_pro_features_table">
                <?php foreach( $pro_features as $key => $value ) {
                    $dir = $this->extension_dir . 'images/screenshots/'. $key . '/';

                    if ( is_dir( $dir ) ) {
                        $dh = opendir( $dir );
                        if ( $dh ) {

                            $screenshots = array();
                            while ( ( $img = readdir( $dh ) ) !== false ) {
                                if ( '..' != $img && '.' != $img ) {
                                    $screenshots[] = $img;
                                }
                            }
                            closedir( $dh );

                        }
                    }
                ?>

                <div class="postbox">
                    <a name="<?php echo $key ?>" style="margin-top: -50px; float: left;"></a>
                    <h3 class='hndle'><span><?php echo $value['title'] ?></span></h3>
                    <div class="inside">
                        <p class="description">
                            <?php if ( count( $screenshots ) ) { ?>
                                <a href="<?php echo $this->extension_url . 'images/screenshots/' . $key . '/' . $screenshots[0] ?>" rel="wpclients_<?php echo $key ?>" class="fancybox_<?php echo $key ?>" title="<?php echo $value['title'] ?>">
                                <img alt="" class="wpc_pro_screenshot" src="<?php echo $this->extension_url . 'images/screenshots/' . $key . '/' . $screenshots[0] ?>" class="image">
                                </a>
                            <?php
                                unset( $screenshots[0] );
                            }
                            ?>
                            <?php echo $value['desc'] ?>
                        </p>

                        <?php if ( count( $screenshots ) ) { ?>
                            <div class="wpc_pro_screenshots">
                                <span class="wpc_pro_screenshots_text"><?php _e( 'Additional Screenshots', WPC_CLIENT_TEXT_DOMAIN ) ?>:</span>
                                <div class="wpc_pro_gallery">
                                    <?php
                                    foreach( $screenshots as $file_name ) {
                                    ?>
                                        <a href="<?php echo $this->extension_url . 'images/screenshots/' . $key . '/' . $file_name ?>" rel="wpclients_<?php echo $key ?>" class="fancybox_<?php echo $key ?>" title="<?php echo $value['title'] ?>"><img alt="" class="wpc_pro_screenshot" src="<?php echo $this->plugin_url . 'images/screenshots/' . $key . '/' . $file_name ?>" class="image"></a>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>

                        <?php } ?>
                    </div>
                </div>

                <script type="text/javascript">
                    jQuery(document).ready(function() {

                        jQuery(".fancybox_<?php echo $key ?>").fancybox({
                            openEffect    : 'none',
                            closeEffect    : 'none'
                        });
                    });
                </script>

                <?php
                    }
                ?>

            </div>





        </div>
    </div>
</div>
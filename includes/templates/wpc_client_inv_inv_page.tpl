<div class="wrap">
    {if isset( $invoice_data )}
        <h1 class="invoicing_title" style="display: block; float: left;">
            {$invoice_title}
            {$invoice_number}
            {$invoice_status}
        </h1>
        <a href="{$download_link}" style="margin: 20px; line-height: 80px ">{$download_link_text}</a>
        <div style="clear: both;"></div>

        {if isset( $paid_link ) }
            <form method="post" action="{$paid_link}" id="wpc_paid_link_form">
            <p>
                <label for="text_amount" style="margin-right: 10px;">{$text_slider}</label>
                {if isset( $left_currency )}{$left_currency}{/if}
                <input type="text" size="10" name="slide_amount" id="text_amount" style="border:0; padding-left: 0; padding-right: 0; color:#f6931f; font-weight:bold;" readonly="readonly">
                {if isset( $right_currency )}{$right_currency}{/if}
                <br />
            </p>
            {if isset( $show_slide )}
                <span style="float: left; margin: -3px 0 0 5px;">{$min_amount}</span>
                <span style="float: right; margin:  -3px 5px 0 0;">{$max_amount}</span>
                <br />

                <div id="slider-range-min"></div>
                <br />
            {/if}
        {/if}

        {if isset( $paid_link )}
            <input type="submit" value="{$paid_link_text}" id="wpc_paid_now_link">
            </form>
        {/if}

        <hr>
        <br>

        <div class="">
            {invoice_content}
        </div>
    {/if}
</div>
<div class="wrap">
    {if isset( $estimate_data )}
        <h1 class="invoicing_title">
            {$estimate_title}
            {$estimate_number}
            {$estimate_status}
        </h1>

        <a href="{$download_link}">{$download_link_text}</a>


        <hr />
        <br />

        <div class="">
            {invoice_content}
        </div>

    {/if}
</div>
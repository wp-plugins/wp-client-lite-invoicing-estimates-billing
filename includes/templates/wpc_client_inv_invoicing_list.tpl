<div class="wpc_client_invoicing_list">
    <div class="clear"></div>
    <table>
        <tbody style="white-space: nowrap;">
        {if is_array( $invoices ) && 0 < count( $invoices )}
            {foreach $invoices as $invoice}
                <tr>
                    <td>
                        <a href="{$invoice.invoicing_link}"># {$invoice.invoicing_number}</a>
                    </td>
                    {if $show_date}
                        <td>|&nbsp;&nbsp;{$invoice.date}</td>
                    {/if}
                    {if $show_description}
                        <td>|&nbsp;&nbsp;{$invoice.description}</td>
                    {/if}
                    {if $show_type_payment}
                        <td>|&nbsp;&nbsp;{$invoice.type_payment}</td>
                    {/if}
                    {if $show_invoicing_currency}
                        <td>|&nbsp;&nbsp;{$invoice.invoicing_currency}&nbsp;&nbsp;|</td>
                    {/if}
                    {if $show_pay_now}
                        <td>{$invoice.inv_pay_now}</td>
                    {/if}
                </tr>
            {/foreach}
        {/if}
        </tbody>
    </table>
</div>
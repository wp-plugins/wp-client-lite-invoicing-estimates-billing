<div class="wpc_client_inv_invoicing_total_amount">
    <div class="clear"></div>
    <table>
        <tbody style="white-space: nowrap;">
            {if $show_total_amount}
                <tr>
                    <td>
                        {$text_total_amount}
                    </td>
                    {foreach $total_amount as $amount}
                        <td>
                            {$amount}
                        </td>
                    {/foreach}
                </tr>
            {/if}
            {if $show_total_payments}
                <tr>
                    <td>
                        {$text_total_payments}
                    </td>
                    {foreach $total_payments as $payments}
                        <td>
                            {$payments}
                        </td>
                    {/foreach}
                </tr>
            {/if}
            {if $show_balance}
                <tr>
                    <td>
                        {$text_balance}
                    </td>
                    {foreach $balance as $bal}
                        <td>
                            {$bal}
                        </td>
                    {/foreach}
                </tr>
            {/if}
        </tbody>
    </table>
</div>
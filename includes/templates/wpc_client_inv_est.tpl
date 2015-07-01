<div style="font-family: WPCCyr;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" color="black" id="important_table">
        <tbody>
            <tr>
                <td width="50%" rowspan="3" style="padding-bottom:6px; font-size: xx-small; vertical-align: top;">
                    <img src="{business_logo_url}" width="290" height="110" style="box-shadow: 0 0 0 ;" />
                </td>
                <td style="text-align: right; line-height:14px; font-size: xx-large;">
                    ESTIMATE
                </td>
            </tr>
            <tr>
                <td valign="top" align="right" style="text-align: right; color: #757575; line-height:14px; font-weight: bold; font-size: x-small;">
                    Estimate# {$InvoiceNumber}
                </td>
            </tr>
            <tr>
                <td class="color_black" style="text-align: right; line-height:14px; font-weight: bold; font-size: x-small;">
                    Total<br />
                    <span style="font-size: small;" >{$InvoiceTotal}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <b class="color_black">{business_name}</b>
                    <font size="1" style="color: #757575;">
                        <br />
                        {business_address}<br />
                        {business_mailing_address}<br />
                        Website: {business_website}<br />
                        Email: {business_email}<br />
                        Phone: {business_phone}<br />
                        Fax: {business_fax}<br />
                    </font>
                </td>
            </tr>
            <tr style="font-size: x-small;">
                <td valign="bottom" style="vertical-align: bottom !important;">
                    <span style="color: #757575;">Bill To</span><br />
                    <span class="color_black">{client_name}</span>
                </td>
                <td align="right">
                    <table cellspacing="0" cellpadding="5" bordercolor="#000000" id="date_informs" style="width: 100%; margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <td align="right">
                                    <span style="color: #757575;">Date :</span>
                                </td>
                                <td align="right">
                                    <span class="color_black">{$InvoiceDate}</span>
                                </td>
                            </tr>
                            <tr>
                                <td align="right">
                                    <span style="color: #757575;">Terms :</span>
                                </td>
                                <td align="right">
                                    <span class="color_black">{$PONumber}</span>
                                </td>
                            </tr>
                            <tr>
                                <td align="right">
                                    <span style="color: #757575;">Due Date :</span>
                                </td>
                                <td align="right">
                                    <span class="color_black">{$DueDate}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 15px 0">
                    <table width="100%"id="all_items" border="0" bordercolor="#000000" cellspacing="0" cellpadding="5" style="border:1px solid #000; font-size: x-small;">
                        <thead>
                            <tr bgcolor="#363636" height="20" style="color: #fff; font-weight: normal;">
                                <td valign="bottom" style="font-weight: normal; padding:6px 0px 6px 3px;">
                                    Item
                                    {if isset($show_description)}
                                        & Description
                                    {/if}
                                </td>
                                {if isset($TitleCustomFields)}
                                    {foreach $TitleCustomFields as $field}
                                        <td style="font-weight: normal;">
                                            {$field}
                                        </td>
                                    {/foreach}
                                {/if}
                                <td style="width: 8%; font-weight: normal;" align="center">
                                    Qty
                                </td>
                                <td style="width: 14%; font-weight: normal;" align="center">
                                    Rate
                                </td>
                                <td style="width: 11%; font-weight: normal;" align="center">
                                    Amount
                                </td>
                            </tr>
                        </thead>
                        <tbody id="lineItem">

                        {if isset($items)}
                            {foreach $items as $item}
                                <tr height="20" style="padding: 3px 0;">
                                    <td style="line-height: 14px; padding-left: 5px;">
                                        <span>{$item.ItemName}</span>
                                        {if isset($show_description)}
                                            <br />
                                            <span style="color: #757575;" style="">
                                                {$item.ItemDescription}
                                            </span>
                                        {/if}
                                    </td>
                                    {if isset($CustomFields)}
                                        {foreach $CustomFields as $filed}
                                            <td valign="top" style="">
                                                {$item[$filed.slug]}
                                            </td>
                                        {/foreach}
                                    {/if}
                                    <td valign="top" style="" align="center">
                                        {$item.ItemQuantity}
                                    </td>
                                    <td valign="top" style="" align="center">
                                        {$item.ItemRate}
                                    </td>
                                    <td valign="top" style="" align="center">
                                        {$item.ItemTotal}
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr height="20">
                                <td valign="top" style="">
                                </td>
                                <td valign="top" style="">
                                </td>
                                <td valign="top" style="">
                                </td>
                                <td valign="top" style="">
                                </td>
                                <td valign="top" style="">
                                </td>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                    {if isset($discounts)}
                        <table width="100%"id="all_items" border="0" bordercolor="#000000" cellspacing="0" cellpadding="5" style="border:1px solid #000; font-size: x-small;">
                            <thead>
                                <tr bgcolor="#363636" height="20" style="color: #fff; font-weight: normal;">
                                    <td colspan="{$colspan_for_name}" valign="bottom" style="font-weight: normal; padding:6px 0px 6px 3px;">
                                        Discount Name & Description
                                    </td>
                                    <td style="width: 8%; font-weight: normal;" align="center">
                                        Type
                                    </td>
                                    <td style="width: 14%; font-weight: normal;" align="center">
                                        Rate
                                    </td>
                                    <td style="width: 11%; font-weight: normal;" align="center">
                                        Total
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $discounts as $disc}
                                    <tr height="20" style="padding: 3px 0;">
                                        <td colspan="{$colspan_for_name}" style="line-height: 14px; padding-left: 5px;">
                                            <span>{$disc.name}</span>
                                            <br />
                                            <span style="color: #757575;" style="">
                                                {$disc.description}
                                            </span>
                                        </td>
                                        <td valign="top" style="" align="center">
                                            {$disc.type}
                                        </td>
                                        <td valign="top" style="" align="center">
                                            {$disc.rate}
                                        </td>
                                        <td valign="top" style="" align="center">
                                            {$disc.total}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {/if}
                    {if isset($taxes)}
                        <table width="100%"id="all_items" border="0" bordercolor="#000000" cellspacing="0" cellpadding="5" style="border:1px solid #000; font-size: x-small;">
                            <thead>
                                <tr bgcolor="#363636" height="20" style="color: #fff; font-weight: normal;">
                                    <td colspan="{$colspan_for_name}" valign="bottom" style="font-weight: normal; padding:6px 0px 6px 3px;">
                                        Tax Name & Description
                                    </td>
                                    <td style="width: 8%; font-weight: normal;" align="center">
                                        Type
                                    </td>
                                    <td style="width: 14%; font-weight: normal;" align="center">
                                        Rate
                                    </td>
                                    <td style="width: 11%; font-weight: normal;" align="center">
                                        Total
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $taxes as $tax}
                                    <tr height="20" style="padding: 3px 0;">
                                        <td colspan="{$colspan_for_name}" style="line-height: 14px; padding-left: 5px;">
                                            <span>{$tax.name}</span>
                                            <br />
                                            <span style="color: #757575;" style="">
                                                {$tax.description}
                                            </span>
                                        </td>
                                        <td valign="top" style="" align="center">
                                            {$tax.type}
                                        </td>
                                        <td valign="top" style="" align="center">
                                            {$tax.rate}
                                        </td>
                                        <td valign="top" style="" align="center">
                                            {$tax.total}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {/if}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <table width="100%" border="0" cellspacing="0" cellpadding="5" id="sub_block" style="font-size: x-small;">
                        <tbody>
                            <tr>
                                <td width="67%">&nbsp;</td>
                                <td width="18%" align="right">
                                    Sub Total
                                </td>
                                <td width="15%" align="right">
                                    {$InvoiceSubTotal}
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="right">
                                    Discount
                                </td>
                                <td align="right">
                                    {$TotalDiscount}
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="right">
                                    Tax
                                </td>
                                <td align="right">
                                    {$TotalTax}
                                </td>
                            </tr>
                            {if $IsLateFee}
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align="right">
                                        Late Fee:
                                    </td>
                                    <td align="right">
                                        {$LateFee}
                                    </td>
                                </tr>
                            {/if}
                            <tr>
                                <td>&nbsp;</td>
                                <td align="right" style="border-top:1px solid black; font-weight: bold;">
                                    Total
                                </td>
                                <td align="right" style="border-top:1px solid black; font-weight: bold;">
                                    {$InvoiceTotal}
                                </td>
                            </tr>
                            {if isset($TotalRemaining)}
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align="right" style="">
                                        Payment Made
                                    </td>
                                    <td align="right" style="">
                                        {$PaymentMade}
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align="right" style="">
                                        Total Remaining
                                    </td>
                                    <td align="right" style="">
                                        {$TotalRemaining}
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" valign="top" style="font-size: x-small;">
                    <span style="color: #757575">Notes</span>
                    <br />
                    <br />
                    <font class="color_black">
                        {$Notes}
                    </font>
                </td>
            </tr>
            {if isset($TermsAndCondition)}
                <tr>
                    <td colspan="2" style="padding-top:20px; font-size: xx-small;">
                        <span style="background-color: rgb(255, 255, 255);">
                            {$TermsAndCondition}
                        </span>
                    </td>
                </tr>
            {/if}
        </tbody>
    </table>

    {$InvoiceDescription}
</div>
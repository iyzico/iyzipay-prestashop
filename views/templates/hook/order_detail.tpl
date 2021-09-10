{* Installment table*}
{if !empty({$error})} <div class="alert alert-danger">{$error}</div> {/if}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-credit-card"></i>
        <label>{l s="iyzico Installment Details" mod='iyzicocheckoutform'}</label>
    </div>
    <table class="table" id="iyzico-installment-table">
        <thead>
            <tr>
                <th>
                    <span class="title_box ">
                        <label>{l s="Transaction Id" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box ">
                        <label>{l s="Paid Price" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box ">
                        <label>{l s="Installment Fee" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box ">
                        <label>{l s="Installment No" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{$iyzico_installment_data['transaction_id']}</td>
                <td>
                    {assign var=price value=Tools::displayPrice($iyzico_installment_data['installment_amount'], $params['currency']->sign, false) }
                    {$price}
                </td>
                <td>
                    {assign var=installment_price value=Tools::displayPrice($iyzico_installment_data['installment_fee'], $params['currency']->sign, false)}
                    {$installment_price}
                </td>
                <td>{$iyzico_installment_data['installment_no']} Taksit </td>
            </tr>
        </tbody>
    </table>
</div>
{* Cancel table*}
{if !empty({$transaction_id})}
<div class='panel'>
    <div class='panel-heading'>
        <i class='icon-remove-circle'></i>
        <label>{l s="Iyzico Cancel Order detail" mod='iyzicocheckoutform'}</label>
    </div>
    <div class='well hidden-print'> 
        <form name='cancelOrder' action='{$form_action}' method='post'>
            <input type='hidden' name='id_employee' value='{$id_employee}'/>
            <input type='hidden' name='transaction_id' value='{$transaction_id}'/>
            <input type='hidden' name='token' value='{$token}'/>
            <label>{l s="Total amount" mod='iyzico'}:&nbsp;&nbsp;</label> {$currency} &nbsp;&nbsp;
            <input type='submit' name='cancel' value='Cancel Iyzico Order' class='btn btn-default'/>
        </form>
    </div>
</div>
{/if}
{* Refund table *}
{assign var='refund_count' value=$order_detail|@count}
    {if $refund_count gt 0}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-share"></i>
        <label>{l s="Iyzico Order Refund" mod='iyzicocheckoutform'}</label>
    </div>

    <table class="table" id="iyzico-installment-table">
        <thead>
            <tr>
                <th>
                    <span class="title_box ">
                        <label>{l s="Product" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box ">
                        <label>{l s="Paid Price" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box ">
                        <label>{l s="Total Refunded Price" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box ">
                        <label>{l s="Refund Amount" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$order_detail item=i}
            {assign var="amount" value=$i.paid_price- $i.total_refunded_amount}
            {if $i.paid_price != $i.total_refunded_amount}
            <tr>
                <td>{$i.name}</td>
                <td>{$i.paid_price}</td>
                <td>{$i.total_refunded_amount}</td>
                <td>
                    <input class="form-control" type="text" name="paid_price{$i.item_id}" id="paid_price{$i.item_id}" value="{$amount}" required/>
                    <input class="form-control" type="hidden" name="payment_id_{$i.item_id}" id="payment_id_{$i.item_id}" value="{$i.payment_transaction_id}"/>
                    <input class="form-control" type="hidden" name="product_price_{$i.item_id}" id="product_price_{$i.item_id}" value="{$i.paid_price}"/>
                    <input class="form-control" type="hidden" name="refunded_{$i.item_id}" id="refunded_{$i.item_id}" value="{$i.total_refunded_amount}"/>
                    <input type='hidden' name='token' value='{$token}'/>
                    <div id="refund_error" class="error-msg" style="color: red;"></div>
                    <a item-id="{$i.item_id}" class="refund-button">
                        <input type="button" name="refund" value="Refund" class='btn btn-default'/>
                    </a>
                </td>
            </tr>
            {/if}
            {/foreach}
        </tbody>
    </table>
</div>
 {/if}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-th"></i>
        <label>{l s="Iyzico Order History" mod='iyzicocheckoutform'} </label>
    </div>
    <table class="table" id="iyzico-installment-table">
        <thead>
            <tr>
                <th>
                    <span class="title_box">
                        <label>{l s="Date" mod='iyzicocheckoutform'}</label>
                    </span>

                </th>
                <th>
                    <span class="title_box">
                        <label>{l s="Status" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
                <th>
                    <span class="title_box">
                        <label>{l s="Note" mod='iyzicocheckoutform'}</label>
                    </span>
                </th>
            </tr>
            {if empty($order_history)}
            <tr>
                <td>No order history found.</td>
            </tr>
            {/if}

            {foreach from=$order_history item=j}
            <tr>
                <td>{$j.updated}</td>
                <td>{$j.transaction_status}</td>
                <td>
                    {$j.note} <br/>
                    {if $j.name != ''}
                    Product Detail: {$j.name}
                    {/if}
                </td>
            </tr>
            {/foreach}
        </thead>
    </table>
</div>

<script type="text/javascript">

    $(".refund-button").click(function () {
        var id = $(this).attr("item-id");
        var refund_price = $("#paid_price" + id).val();
        var payment_transaction_id = $("#payment_id_" + id).val();
        var product_price = $("#product_price_" + id).val();
        var refunded = $("#refunded_" + id).val();
        var refund_limit = (product_price - refunded).toFixed(2);
        var token        = "{{$token}}";

        $.ajax({
            url: "{$refund_url}",
            type: "POST",
            data: "payment_id=" + payment_transaction_id + "&refund_price=" + refund_price + "&refunded=" + refunded+"&token="+token,refunded,
            success: function (result) {
                var message = JSON.parse(result);
                if (message['msg'] == 'Fail') {
                    $("#refund_error").html(message['response']);
                    return false;
                } else {
                    location.reload();
                    return false;
                }

            }});
    });
</script>

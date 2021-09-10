<div class= "row"> 
    <div class="col-xs-12">

        <p class="payment_module">
<style>
p.payment_module a.iyzicocheckoutform {
    background: url({$module_dir}img/iyzicoicon.png) 15px 12px no-repeat #fbfbfb;
</style>   <a class="iyzicocheckoutform" href="javascript:toggleform();" title="{$credit_card}">
              <span>{$credit_card}</span>
            </a>
        </p>
        {if (isset($error)) }
        <div class="paiement_block">
            <p class="alert alert-warning">{$error}</p>
        </div>
        {else}
        {if ($form_class == 'popup')}
        <div id="iyzipay-checkout-form" style="display: none;" class="popup"> {$response}</div>  
        {else}
        <div id="iyzipay-checkout-form" class="responsive" style="display: none;"> {$response}</div>  
        {/if}
        {/if}
        {if (isset($currency_error) && $currency_error != '')}
        <p class="alert alert-warning">{$currency_error}</p> 
        {/if}
    </div>
</div>

{literal}
<script type="text/javascript">
    function toggleform() {
        var ele = document.getElementById("iyzipay-checkout-form");

        if (ele.style.display == "block") {
            ele.style.display = "none";
        }
        else {
            ele.style.display = "block";
        }
    }
</script>
{/literal}
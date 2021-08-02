<style>
    p.payment_module a.paywithiyzico {
        background: url({$this_logo}) 15px 8px no-repeat #fbfbfb;
    }

</style>
<p class="payment_module">
    <a class="paywithiyzico" href="{$pwi_page_url}" title="{l s='Pay with iyzico-It’s Easy Now!' mod='paywithiyzico'}">
        {l s='Pay with iyzico-It’s Easy Now!' mod='paywithiyzico'}
        <br>
        <span style="font-size: 12px; font-weight: normal">
            {$this_description}
        </span>
    </a>
</p>

<div class= "row">
    <div class="col-xs-12">
        {if (isset($error)) }
                <p>{l s='PWI Error !' mod='paywithiyzico'}</p>
                <p class="alert alert-warning">{$error}</p>
        {/if}
    </div>
</div>
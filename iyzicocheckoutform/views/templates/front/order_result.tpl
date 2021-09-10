<p>
   {if !empty({$error})}
        {$error}
   {else}
       {if $locale == 'tr'}
        <span class="bold">Siparişiniz ve Ödemeniz Başarıyla Alındı.</span>
        <br/>
        <br/>
        Kredi Kartınızdan çekilen miktar {$total} {$currency}
        <br/>
        <br/>
        Teşekkür ederiz.
        {else}
        <span class="bold">Successfully Received your order and your payment.</span>
        <br/>
        <br/>
        The amount withdrawn from your credit card {$total} {$currency}
        <br/>
        <br/>
        Thank you.
        {/if}
    {/if}
</p>